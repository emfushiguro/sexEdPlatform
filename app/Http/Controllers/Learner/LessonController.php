<?php

namespace App\Http\Controllers\Learner;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use App\Models\LessonTopic;
use App\Models\UserProgress;
use App\Services\GamificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LessonController extends Controller
{
    /**
     * Display a specific lesson
     */
    public function show(Lesson $lesson)
    {
        $user = Auth::user();
        $module = $lesson->module;

        // Security: Check if lesson and module are published
        if (!$lesson->is_published || !$module->is_published) {
            abort(404);
        }

        // Security: Check if user is enrolled
        $isEnrolled = $user->moduleEnrollments()
            ->where('module_id', $module->id)
            ->exists();

        if (!$isEnrolled) {
            return redirect()->route('learner.modules.show', $module)
                ->with('error', 'Please enroll in this module first.');
        }

        // Get all lessons for navigation
        $allLessons = $module->lessons()
            ->where('is_published', true)
            ->orderBy('order')
            ->get();

        // Security: Check sequential access - must complete previous lessons first
        $currentIndex = $allLessons->search(fn($l) => $l->id === $lesson->id);
        
        if ($currentIndex > 0) {
            // Check if all previous lessons are completed
            for ($i = 0; $i < $currentIndex; $i++) {
                $previousLesson = $allLessons[$i];
                $isCompleted = UserProgress::where('user_id', $user->id)
                    ->where('lesson_id', $previousLesson->id)
                    ->where('completed', true)
                    ->exists();
                
                if (!$isCompleted) {
                    return redirect()->route('learner.lessons.show', $previousLesson)
                        ->with('error', 'Please complete the previous lessons first.');
                }
            }
        }

        // Check if current lesson is completed
        $isLessonCompleted = UserProgress::where('user_id', $user->id)
            ->where('lesson_id', $lesson->id)
            ->where('completed', true)
            ->exists();

        // Find previous and next lessons
        $previousLesson = $currentIndex > 0 ? $allLessons[$currentIndex - 1] : null;
        $nextLesson = $currentIndex < $allLessons->count() - 1 ? $allLessons[$currentIndex + 1] : null;

        // Get completion status for all lessons (for sidebar)
        $completedLessonIds = UserProgress::where('user_id', $user->id)
            ->where('module_id', $module->id)
            ->where('completed', true)
            ->pluck('lesson_id')
            ->toArray();

        // Get lesson quiz if exists
        $lessonQuiz = $lesson->quiz()->where('is_active', true)->with('questions')->first();
        $quizAttempt = null;
        if ($lessonQuiz) {
            $quizAttempt = $user->quizAttempts()
                ->where('quiz_id', $lessonQuiz->id)
                ->orderByDesc('score')
                ->first();
        }

        // Get lesson topics with progress
        $lessonTopics = $lesson->topics()->ordered()->get();
        $completedTopicIds = [];
        if ($lessonTopics->count() > 0) {
            $completedTopicIds = \App\Models\LessonTopicProgress::where('user_id', $user->id)
                ->whereIn('lesson_topic_id', $lessonTopics->pluck('id'))
                ->where('completed', true)
                ->pluck('lesson_topic_id')
                ->toArray();
        }

        // Calculate locked topics based on prerequisite dependencies
        $lockedTopicIds = [];
        foreach ($lessonTopics as $index => $topic) {
            if ($topic->is_prerequisite) {
                // Prerequisite topics must be completed sequentially
                // Lock if any previous prerequisite topics are not completed
                for ($i = 0; $i < $index; $i++) {
                    $previousTopic = $lessonTopics[$i];
                    if ($previousTopic->is_prerequisite && !in_array($previousTopic->id, $completedTopicIds)) {
                        $lockedTopicIds[] = $topic->id;
                        break;
                    }
                }
            }
            // Optional topics (is_prerequisite = false) are always accessible
        }

        // Determine current topic (allow navigation via URL parameter)
        $currentTopic = null;
        $currentTopicIndex = 0;
        
        if ($lessonTopics->count() > 0) {
            // Check if specific topic requested via URL
            $requestedTopicIndex = request()->query('topic');
            
            if ($requestedTopicIndex !== null && isset($lessonTopics[$requestedTopicIndex])) {
                $requestedTopic = $lessonTopics[$requestedTopicIndex];
                
                // Only allow access if topic is not locked
                if (!in_array($requestedTopic->id, $lockedTopicIds)) {
                    $currentTopic = $requestedTopic;
                    $currentTopicIndex = $requestedTopicIndex;
                } else {
                    // Redirect to first unlocked topic with error message
                    foreach ($lessonTopics as $index => $topic) {
                        if (!in_array($topic->id, $lockedTopicIds)) {
                            return redirect()->route('learner.lessons.show', ['lesson' => $lesson->id, 'topic' => $index])
                                ->with('error', 'Please complete the required prerequisite topics first.');
                        }
                    }
                }
            }
            
            // If no topic specified or requested was locked, find appropriate topic
            if (!$currentTopic) {
                // Find first incomplete unlocked topic
                foreach ($lessonTopics as $index => $topic) {
                    if (!in_array($topic->id, $completedTopicIds) && !in_array($topic->id, $lockedTopicIds)) {
                        $currentTopic = $topic;
                        $currentTopicIndex = $index;
                        break;
                    }
                }
                
                // If all unlocked topics are completed, show the last completed unlocked topic
                if (!$currentTopic) {
                    for ($i = $lessonTopics->count() - 1; $i >= 0; $i--) {
                        if (!in_array($lessonTopics[$i]->id, $lockedTopicIds)) {
                            $currentTopic = $lessonTopics[$i];
                            $currentTopicIndex = $i;
                            break;
                        }
                    }
                }
                
                // Fallback to first topic if nothing found
                if (!$currentTopic) {
                    $currentTopic = $lessonTopics->first();
                    $currentTopicIndex = 0;
                }
            }
        }

        return view('learner.lessons.show', compact(
            'lesson',
            'module',
            'previousLesson',
            'nextLesson',
            'allLessons',
            'isLessonCompleted',
            'completedLessonIds',
            'lessonQuiz',
            'quizAttempt',
            'lessonTopics',
            'completedTopicIds',
            'lockedTopicIds',
            'currentTopic',
            'currentTopicIndex'
        ));
    }

    /**
     * Mark lesson as completed
     */
    public function complete(Lesson $lesson)
    {
        $user = Auth::user();
        $module = $lesson->module;

        // Security checks
        if (!$lesson->is_published || !$module->is_published) {
            abort(404);
        }

        $isEnrolled = $user->moduleEnrollments()
            ->where('module_id', $module->id)
            ->exists();

        if (!$isEnrolled) {
            return back()->with('error', 'You are not enrolled in this module.');
        }

        // Check if already completed
        $existingProgress = UserProgress::where('user_id', $user->id)
            ->where('lesson_id', $lesson->id)
            ->where('completed', true)
            ->first();

        if ($existingProgress) {
            return back()->with('info', 'You have already completed this lesson.');
        }

        // Mark this specific lesson as completed
        UserProgress::create([
            'user_id' => $user->id,
            'module_id' => $module->id,
            'lesson_id' => $lesson->id,
            'completed' => true,
            'progress_percentage' => 100,
            'completed_at' => now(),
        ]);

        // Award gamification points (15 points per lesson)
        $gamificationService = app(GamificationService::class);
        $gamificationService->awardPoints($user, 'lesson_complete', 15);
        $gamificationService->updateStreak($user);
        session()->flash('points_earned', ['points' => 15, 'reason' => 'lesson complete']);

        return back()->with('success', 'Lesson completed! You earned 15 points! 🎉');
    }

    /**
     * Mark a lesson topic as completed
     */
    public function completeTopic(LessonTopic $topic)
    {
        $user = Auth::user();
        $lesson = $topic->lesson;
        $module = $lesson->module;

        // Security checks
        if (!$lesson->is_published || !$module->is_published) {
            abort(404);
        }

        $isEnrolled = $user->moduleEnrollments()
            ->where('module_id', $module->id)
            ->exists();

        if (!$isEnrolled) {
            return back()->with('error', 'You are not enrolled in this module.');
        }

        // Mark topic as completed
        $topic->markCompleted($user->id);

        // Award topic completion points (+10) and update streak
        $gamificationService = app(GamificationService::class);
        $gamificationService->awardPoints($user, 'topic_complete', 10);
        $gamificationService->updateStreak($user);
        session()->flash('points_earned', ['points' => 10, 'reason' => 'topic complete']);

        // Check if all topics are completed to auto-complete lesson
        $allTopics = $lesson->topics()->ordered()->get();
        $completedCount = \App\Models\LessonTopicProgress::where('user_id', $user->id)
            ->whereIn('lesson_topic_id', $allTopics->pluck('id'))
            ->where('completed', true)
            ->count();

        if ($completedCount === $allTopics->count()) {
            // Mark lesson as completed
            UserProgress::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'lesson_id' => $lesson->id,
                ],
                [
                    'module_id' => $module->id,
                    'completed' => true,
                    'progress_percentage' => 100,
                    'completed_at' => now(),
                ]
            );
            // Award lesson complete bonus (+15)
            $gamificationService->awardPoints($user, 'lesson_complete', 15);
            session()->flash('points_earned', ['points' => 15, 'reason' => 'lesson complete']);
        }

        // Check if we should navigate to next topic
        $nextTopicIndex = request()->input('next_topic_index');
        if ($nextTopicIndex !== null) {
            return redirect()->route('learner.lessons.show', ['lesson' => $lesson->id, 'topic' => $nextTopicIndex])
                ->with('success', 'Topic completed! +10 points ✓');
        }

        return redirect()->route('learner.lessons.show', $lesson)
            ->with('success', 'Topic completed! +10 points ✓');
    }
}
