<?php

namespace App\Http\Controllers\Learner;

use App\Http\Controllers\Controller;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\UserDailyShield;
use App\Services\GamificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class QuizController extends Controller
{
    public function __construct(
        private GamificationService $gamificationService,
    ) {}

    /**
     * Show quiz start page (redirects to start method)
     */
    public function show(Quiz $quiz)
    {
        // Redirect to start method which handles the quiz taking page
        return $this->start($quiz);
    }

    /**
     * Start quiz attempt
     */
    public function start(Quiz $quiz)
    {
        $user = auth()->user();

        // Check enrollment
        $moduleId = $quiz->module_id ?? $quiz->lesson?->module_id;
        if (!$user->moduleEnrollments()->where('module_id', $moduleId)->exists()) {
            return redirect()->route('learner.modules.show', $moduleId)
                ->with('error', 'You must enroll in the module first.');
        }

        // Check shields for free users (premium users have unlimited attempts)
        if (!$user->isPremium()) {
            if (UserDailyShield::getShields($user) <= 0) {
                return redirect()->route('subscription.upgrade')
                    ->with('error', 'You are out of shields for today. Refill with points or upgrade to premium for unlimited attempts!');
            }
        }

        // Load quiz with questions and options
        $quiz->load(['questions.options']);

        return view('quizzes.take', compact('quiz'));
    }

    /**
     * Submit quiz attempt
     */
    public function submit(Request $request, Quiz $quiz)
    {
        $request->validate([
            'answers' => 'required|array',
        ]);

        $user = auth()->user();

        // Verify enrollment
        $moduleId = $quiz->module_id ?? $quiz->lesson?->module_id;
        if (!$user->moduleEnrollments()->where('module_id', $moduleId)->exists()) {
            abort(403);
        }

        // Re-check shields before submitting (race-condition guard for free users)
        if (!$user->isPremium() && UserDailyShield::getShields($user) <= 0) {
            return redirect()->route('subscription.upgrade')
                ->with('error', 'You are out of shields for today.');
        }

        DB::beginTransaction();

        try {
            // Calculate score
            $quiz->load(['questions.options']);
            $totalQuestions = $quiz->questions->count();
            $correctAnswers = 0;
            $userAnswers = [];

            foreach ($quiz->questions as $question) {
                $isCorrect = false;
                $selectedAnswer = $request->answers[$question->id] ?? null;
                $correctOptions = $question->options->where('is_correct', true);

                if ($question->question_type === 'multiple_select') {
                    // For multiple select, answer should be an array
                    $selectedIds = is_array($selectedAnswer) ? array_map('intval', $selectedAnswer) : [];
                    $correctIds = $correctOptions->pluck('id')->toArray();
                    
                    // Check if selected answers match exactly
                    sort($selectedIds);
                    sort($correctIds);
                    $isCorrect = $selectedIds === $correctIds;

                    $userAnswers[$question->id] = [
                        'selected' => $selectedIds,
                        'correct' => $correctIds,
                        'is_correct' => $isCorrect,
                        'type' => 'multiple_select',
                    ];
                } elseif ($question->question_type === 'fill_blank_text') {
                    // For fill in the blanks (text input)
                    // New format: semicolons separate different blanks, pipes separate alternatives
                    // Example: "blue|Blue;sky|Sky" means blank1 accepts "blue" OR "Blue", blank2 accepts "sky" OR "Sky"
                    
                    // Check if we have multiple blanks with specific answers (semicolon-separated)
                    if (strpos($question->acceptable_answers, ';') !== false) {
                        // Multiple blanks with specific answer sets
                        $blankAnswerSets = explode(';', $question->acceptable_answers);
                        $blankAnswerSets = array_map(function($set) {
                            return array_map('trim', explode('|', $set));
                        }, $blankAnswerSets);
                        
                        $isCorrect = true;
                        if (is_array($selectedAnswer) && count($selectedAnswer) === count($blankAnswerSets)) {
                            foreach ($selectedAnswer as $index => $userInput) {
                                $acceptableForThisBlank = $blankAnswerSets[$index];
                                $blankCorrect = false;
                                foreach ($acceptableForThisBlank as $acceptable) {
                                    if ($question->case_sensitive) {
                                        if (trim($userInput) === $acceptable) {
                                            $blankCorrect = true;
                                            break;
                                        }
                                    } else {
                                        if (strtolower(trim($userInput)) === strtolower($acceptable)) {
                                            $blankCorrect = true;
                                            break;
                                        }
                                    }
                                }
                                if (!$blankCorrect) {
                                    $isCorrect = false;
                                    break;
                                }
                            }
                        } else {
                            $isCorrect = false;
                        }
                        
                        // Flatten for display in results
                        $allAcceptableAnswers = [];
                        foreach ($blankAnswerSets as $set) {
                            $allAcceptableAnswers = array_merge($allAcceptableAnswers, $set);
                        }
                        
                        $userAnswers[$question->id] = [
                            'selected' => $selectedAnswer,
                            'correct' => $allAcceptableAnswers,
                            'is_correct' => $isCorrect,
                            'type' => 'fill_blank_text',
                            'case_sensitive' => $question->case_sensitive,
                        ];
                    } else {
                        // Old format or single blank with alternatives (pipe-separated)
                        $acceptableAnswers = explode('|', $question->acceptable_answers);
                        $acceptableAnswers = array_map('trim', $acceptableAnswers);
                        
                        // Handle single or multiple blanks (old behavior for backwards compatibility)
                        if (is_array($selectedAnswer)) {
                            // Multiple blanks - check if any acceptable answer matches each blank
                            $isCorrect = true;
                            foreach ($selectedAnswer as $userInput) {
                                $blankCorrect = false;
                                foreach ($acceptableAnswers as $acceptable) {
                                    if ($question->case_sensitive) {
                                        if (trim($userInput) === $acceptable) {
                                            $blankCorrect = true;
                                            break;
                                        }
                                    } else {
                                        if (strtolower(trim($userInput)) === strtolower($acceptable)) {
                                            $blankCorrect = true;
                                            break;
                                        }
                                    }
                                }
                                if (!$blankCorrect) {
                                    $isCorrect = false;
                                    break;
                                }
                            }
                        } else {
                            // Single blank
                            foreach ($acceptableAnswers as $acceptable) {
                                if ($question->case_sensitive) {
                                    if (trim($selectedAnswer) === $acceptable) {
                                        $isCorrect = true;
                                        break;
                                    }
                                } else {
                                    if (strtolower(trim($selectedAnswer)) === strtolower($acceptable)) {
                                        $isCorrect = true;
                                        break;
                                    }
                                }
                            }
                        }

                        $userAnswers[$question->id] = [
                            'selected' => $selectedAnswer,
                            'correct' => $acceptableAnswers,
                            'is_correct' => $isCorrect,
                            'type' => 'fill_blank_text',
                            'case_sensitive' => $question->case_sensitive,
                        ];
                    }
                } elseif ($question->question_type === 'fill_blank_select') {
                    // For fill in the blanks (word selection)
                    // New format: semicolons separate answers for different blanks
                    // Example: "grass;sky" for 2 blanks where blank1="grass", blank2="sky"
                    
                    if (strpos($question->acceptable_answers, ';') !== false) {
                        // Multiple blanks with semicolon-separated answers
                        $expectedAnswers = explode(';', $question->acceptable_answers);
                        $expectedAnswers = array_map('trim', $expectedAnswers);
                    } else {
                        // Old format: pipe-separated
                        $expectedAnswers = explode('|', $question->acceptable_answers);
                        $expectedAnswers = array_map('trim', $expectedAnswers);
                    }
                    
                    // User answers should be an array of selected words in order
                    $selectedWords = is_array($selectedAnswer) ? array_values($selectedAnswer) : [];
                    
                    // Check if selected words match correct answers exactly (in order)
                    $isCorrect = count($selectedWords) === count($expectedAnswers);
                    if ($isCorrect) {
                        foreach ($selectedWords as $index => $word) {
                            if (!isset($expectedAnswers[$index]) || trim($word) !== $expectedAnswers[$index]) {
                                $isCorrect = false;
                                break;
                            }
                        }
                    }

                    $userAnswers[$question->id] = [
                        'selected' => $selectedWords,
                        'correct' => $expectedAnswers,
                        'is_correct' => $isCorrect,
                        'type' => 'fill_blank_select',
                    ];
                } elseif ($question->question_type === 'identification') {
                    // For identification (similar to fill_blank_text but with optional image)
                    $acceptableAnswers = explode('|', $question->acceptable_answers);
                    $acceptableAnswers = array_map('trim', $acceptableAnswers);
                    
                    foreach ($acceptableAnswers as $acceptable) {
                        if ($question->case_sensitive) {
                            if (trim($selectedAnswer) === $acceptable) {
                                $isCorrect = true;
                                break;
                            }
                        } else {
                            if (strtolower(trim($selectedAnswer)) === strtolower($acceptable)) {
                                $isCorrect = true;
                                break;
                            }
                        }
                    }

                    $userAnswers[$question->id] = [
                        'selected' => $selectedAnswer,
                        'correct' => $acceptableAnswers,
                        'is_correct' => $isCorrect,
                        'type' => 'identification',
                        'case_sensitive' => $question->case_sensitive,
                        'image_url' => $question->image_path ? asset('storage/' . $question->image_path) : null,
                    ];
                } else {
                    // For single answer (multiple_choice, true_false)
                    $correctOption = $correctOptions->first();
                    $isCorrect = $selectedAnswer && $selectedAnswer == $correctOption->id;

                    $userAnswers[$question->id] = [
                        'selected' => $selectedAnswer,
                        'correct' => $correctOption->id,
                        'is_correct' => $isCorrect,
                        'type' => $question->question_type,
                    ];
                }

                if ($isCorrect) {
                    $correctAnswers++;
                }
            }

            $score = $totalQuestions > 0 ? round(($correctAnswers / $totalQuestions) * 100) : 0;
            $passed = $score >= ($quiz->passing_score ?? 70);

            // Create quiz attempt
            $attempt = QuizAttempt::create([
                'user_id' => $user->id,
                'quiz_id' => $quiz->id,
                'score' => $score,
                'passed' => $passed,
                'answers' => $userAnswers,
                'started_at' => now(),
                'completed_at' => now(),
            ]);

            // Pass Protection: deduct 1 shield on attempt; refund on pass (net 0 if passed)
            $shieldDelta = null; // null = premium (no cost)
            if (!$user->isPremium()) {
                UserDailyShield::drainShield($user);
                if ($passed) {
                    UserDailyShield::refillOne($user); // refund — pass protects your shield
                    $shieldDelta = 0;
                } else {
                    $shieldDelta = -1;
                }
            }

            // Award points based on performance
            if ($passed) {
                $points = $score === 100 ? 30 : 25;
                $this->gamificationService->awardPoints($user, 'quiz_passed', $points);
                $message = "Congratulations! You passed and earned {$points} points!";
            } else {
                $points = 5;
                $this->gamificationService->awardPoints($user, 'quiz_attempted', $points);
                $message = "You earned 5 points for trying! Keep practicing!";
            }

            $this->gamificationService->updateStreak($user);

            DB::commit();

            // Redirect: lesson quizzes return to the lesson viewer; standalone go to result page
            if ($quiz->lesson_id) {
                return redirect(route('learner.lessons.show', $quiz->lesson_id) . '?quiz=1')
                    ->with('success', $message)
                    ->with('quiz_result', true)
                    ->with('quiz_attempt_id', $attempt->id)
                    ->with('shield_delta', $shieldDelta)
                    ->with('xp_earned', $points);
            }

            return redirect()->route('quizzes.result', $attempt)
                ->with('success', $message)
                ->with('shield_delta', $shieldDelta)
                ->with('xp_earned', $points);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return redirect()->back()
                ->with('error', 'Failed to submit quiz. Please try again.');
        }
    }

    /**
     * Show quiz result
     */
    public function result(QuizAttempt $attempt)
    {
        // Verify attempt belongs to authenticated user
        if ($attempt->user_id !== auth()->id()) {
            abort(403);
        }

        $attempt->load(['quiz.questions.options']);

        $user = auth()->user();
        $shieldsRemaining = $user->isPremium() ? null : UserDailyShield::getShields($user);
        $remainingAttempts = $shieldsRemaining; // shields are the retry gate for free users
        $shieldDelta = session('shield_delta');
        $xpEarned = session('xp_earned');

        return view('quizzes.result', compact('attempt', 'shieldsRemaining', 'remainingAttempts', 'shieldDelta', 'xpEarned'));
    }

    /**
     * Show quiz history for user
     */
    public function history()
    {
        $attempts = auth()->user()->quizAttempts()
            ->with('quiz')
            ->latest()
            ->paginate(10);

        $shieldsRemaining = UserDailyShield::getShields(auth()->user());

        return view('quizzes.history', compact('attempts', 'shieldsRemaining'));
    }

    /**
     * Check if lesson is completed
     */
    private function checkLessonCompletion($user, $lessonId)
    {
        // Update user progress for the lesson
        // This is a simplified version - you may want more complex logic
        $lesson = \App\Models\Lesson::find($lessonId);
        if ($lesson) {
            $progress = $user->progress()->where('module_id', $lesson->module_id)->first();
            if ($progress) {
                $progress->increment('completed_lessons');
            }
        }
    }

    /**
     * Check if module is completed
     */
    private function checkModuleCompletion($user, $moduleId)
    {
        $module = \App\Models\Module::find($moduleId);
        if (!$module) return;

        $progress = $user->progress()->where('module_id', $moduleId)->first();
        if (!$progress) return;

        // Check if all lessons and quizzes are completed
        $totalLessons = $module->lessons()->count();
        $completedLessons = $progress->completed_lessons ?? 0;

        if ($completedLessons >= $totalLessons) {
            $progress->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);

            // Award completion bonus
            $this->gamificationService->awardPoints($user, 'module_completed', 100);
        }
    }
}
