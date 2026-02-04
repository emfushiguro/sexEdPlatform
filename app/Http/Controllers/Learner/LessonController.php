<?php

namespace App\Http\Controllers\Learner;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use App\Models\LessonTopic;
use App\Models\UserProgress;
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

        // Determine current topic (allow navigation via URL parameter)
        $currentTopic = null;
        $currentTopicIndex = 0;
        
        if ($lessonTopics->count() > 0) {
            // Check if specific topic requested via URL
            $requestedTopicIndex = request()->query('topic');
            
            if ($requestedTopicIndex !== null && isset($lessonTopics[$requestedTopicIndex])) {
                // Show requested topic
                $currentTopic = $lessonTopics[$requestedTopicIndex];
                $currentTopicIndex = $requestedTopicIndex;
            } else {
                // Find first incomplete topic, or show first topic if all complete
                foreach ($lessonTopics as $index => $topic) {
                    if (!in_array($topic->id, $completedTopicIds)) {
                        $currentTopic = $topic;
                        $currentTopicIndex = $index;
                        break;
                    }
                }
                
                // If all topics are completed, show the last one
                if (!$currentTopic) {
                    $currentTopic = $lessonTopics->last();
                    $currentTopicIndex = $lessonTopics->count() - 1;
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

        // Award gamification points (10 points per lesson)
        $gamification = $user->gamification;
        if ($gamification) {
            $gamification->addPoints(10);
            $gamification->updateStreak();
        }

        return back()->with('success', 'Lesson completed! You earned 10 points! 🎉');
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

        // Award points (5 points per topic)
        $gamification = $user->gamification;
        if ($gamification) {
            $gamification->addPoints(5);
        }

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
        }

        return redirect()->route('learner.lessons.show', $lesson)
            ->with('success', 'Topic completed! +5 points ✓');
    }
}
