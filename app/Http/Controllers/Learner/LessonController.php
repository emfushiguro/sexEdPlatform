<?php

namespace App\Http\Controllers\Learner;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use App\Models\UserProgress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LessonController extends Controller
{
    public function show(Lesson $lesson)
    {
        $user = Auth::user();
        
        // Check if user is enrolled in the module
        $enrollment = $user->moduleEnrollments()->where('module_id', $lesson->module_id)->first();
        if (!$enrollment) {
            return redirect()->route('learner.modules.show', $lesson->module)
                ->with('error', 'You must be enrolled in this module to view lessons.');
        }
        
        // Get user progress for this lesson
        $progress = UserProgress::where('user_id', $user->id)
            ->where('lesson_id', $lesson->id)
            ->first();
            
        // Load lesson with topics/content
        $lesson->load(['topics' => function($query) {
            $query->orderBy('order');
        }]);
        
        return view('learner.lessons.show', compact('lesson', 'progress', 'enrollment'));
    }
    
    public function complete(Request $request, Lesson $lesson)
    {
        $user = Auth::user();
        
        // Check enrollment
        $enrollment = $user->moduleEnrollments()->where('module_id', $lesson->module_id)->first();
        if (!$enrollment) {
            return redirect()->back()->with('error', 'You are not enrolled in this module.');
        }
        
        // Create or update progress
        UserProgress::updateOrCreate(
            [
                'user_id' => $user->id,
                'lesson_id' => $lesson->id
            ],
            [
                'module_id' => $lesson->module_id,
                'completed_at' => now(),
                'status' => 'completed'
            ]
        );
        
        // Update gamification if available
        if ($user->gamification) {
            $user->gamification->addPoints(10); // 10 points for completing a lesson
            $user->gamification->updateStreak();
        }
        
        return redirect()->back()->with('success', 'Lesson marked as completed!');
    }
}