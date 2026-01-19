<?php

namespace App\Http\Controllers;

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizDailyLimit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class QuizController extends Controller
{
    /**
     * Show quiz start page
     */
    public function show(Quiz $quiz)
    {
        $user = auth()->user();
        $quiz->load(['questions.options', 'module', 'lesson']);

        // Check enrollment
        $moduleId = $quiz->module_id ?? $quiz->lesson?->module_id;
        $isEnrolled = $user->moduleEnrollments()->where('module_id', $moduleId)->exists();

        if (!$isEnrolled) {
            return redirect()->route('modules.show', $moduleId)
                ->with('error', 'You must enroll in the module first.');
        }

        // Check remaining attempts for free users
        $remainingAttempts = QuizDailyLimit::getRemainingAttempts($user, $quiz->id);
        
        // Get user's previous attempts for this quiz
        $previousAttempts = $user->quizAttempts()
            ->where('quiz_id', $quiz->id)
            ->latest()
            ->take(5)
            ->get();

        return view('quizzes.show', compact('quiz', 'remainingAttempts', 'previousAttempts'));
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
            return redirect()->route('modules.show', $moduleId)
                ->with('error', 'You must enroll in the module first.');
        }

        // Check daily limit for free users
        $remainingAttempts = QuizDailyLimit::getRemainingAttempts($user, $quiz->id);
        
        if ($remainingAttempts <= 0) {
            return redirect()->route('subscription.upgrade')
                ->with('error', 'You have reached your daily quiz limit. Upgrade to premium for unlimited attempts!');
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

        // Check daily limit again before submitting
        $remainingAttempts = QuizDailyLimit::getRemainingAttempts($user, $quiz->id);
        if ($remainingAttempts <= 0) {
            return redirect()->route('subscription.upgrade')
                ->with('error', 'You have reached your daily quiz limit.');
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
                    $selectedIds = is_array($selectedAnswer) ? $selectedAnswer : [];
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

            // Increment daily limit for free users
            QuizDailyLimit::incrementAttempts($user, $quiz->id);

            // Award points based on performance
            $gamification = $user->gamification;
            if ($gamification) {
                if ($passed) {
                    // 25 points for passing
                    $points = 25;
                    // Bonus for perfect score
                    if ($score == 100) {
                        $points = 30;
                    }
                    $gamification->addPoints($points);
                    $message = "Congratulations! You passed and earned {$points} points! 🎉";
                } else {
                    // 5 points for attempt (participation)
                    $gamification->addPoints(5);
                    $message = "You earned 5 points for trying! Keep practicing! 💪";
                }
                
                // Update streak
                $gamification->updateStreak();
            } else {
                $message = $passed ? 'Congratulations! You passed!' : 'Keep trying!';
            }

            DB::commit();

            return redirect()->route('quizzes.result', $attempt)
                ->with('success', $message);

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

        return view('quizzes.result', compact('attempt'));
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

        $remainingAttempts = QuizDailyLimit::getRemainingAttempts(auth()->user());

        return view('quizzes.history', compact('attempts', 'remainingAttempts'));
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
            $user->gamification->addPoints(100);
        }
    }
}
