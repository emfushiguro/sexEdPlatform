<?php

namespace App\Http\Controllers\Learner;

use App\Http\Controllers\Controller;
use App\Models\Module;
use App\Models\ModuleEnrollment;
use App\Models\UserProgress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ModuleController extends Controller
{
    /**
     * Display all published modules filtered by learner's grade level
     */
    public function index()
    {
        $user = Auth::user();
        $learnerProfile = $user->learnerProfile;
        
        if (!$learnerProfile) {
            return redirect()->route('profile.complete')
                ->with('error', 'Please complete your profile to access modules.');
        }

        // The age_range field already contains the grade level (grade_4_up, grade_6_up, etc.)
        $gradeLevel = $learnerProfile->age_range;
        
        // Get published modules appropriate for grade level
        $modules = Module::where('is_published', true)
            ->where(function ($query) use ($gradeLevel) {
                // Show all modules at or below learner's grade level
                $query->whereIn('grade_level', $this->getAccessibleGradeLevels($gradeLevel))
                      ->orWhereNull('grade_level'); // Include modules without grade restriction
            })
            ->withCount('lessons')
            ->with(['lessons' => function ($query) {
                $query->where('is_published', true)->orderBy('order');
            }])
            ->orderBy('order')
            ->get();

        // Get user's enrollments
        $enrolledModuleIds = $user->moduleEnrollments()->pluck('module_id')->toArray();
        
        // Calculate progress for each module
        $progress = [];
        foreach ($modules as $module) {
            $totalLessons = $module->lessons->count();
            $completedLessons = UserProgress::where('user_id', $user->id)
                ->where('module_id', $module->id)
                ->where('completed', true)
                ->count();
            
            if ($totalLessons > 0) {
                $progressPercentage = ($completedLessons / $totalLessons) * 100;
            } else {
                $progressPercentage = 0;
            }
            
            $progress[$module->id] = (object)[
                'progress_percentage' => round($progressPercentage),
                'completed_lessons' => $completedLessons,
                'total_lessons' => $totalLessons,
            ];
        }

        return view('learner.modules.index', compact('modules', 'enrolledModuleIds', 'progress'));
    }

    /**
     * Display a specific module with its lessons
     */
    public function show(Module $module)
    {
        $user = Auth::user();
        $learnerProfile = $user->learnerProfile;

        // Security: Check if module is published
        if (!$module->is_published) {
            abort(404);
        }

        // Security: Check grade level access (age_range is already the grade level)
        $gradeLevel = $learnerProfile->age_range;
        if (!$this->canAccessModule($module, $gradeLevel)) {
            return redirect()->route('learner.modules.index')
                ->with('error', 'This module is not available for your age group.');
        }

        // Get published lessons only
        $lessons = $module->lessons()
            ->where('is_published', true)
            ->orderBy('order')
            ->get();

        // Check enrollment status
        $isEnrolled = $user->moduleEnrollments()
            ->where('module_id', $module->id)
            ->exists();

        // Calculate progress
        $totalLessons = $lessons->count();
        $completedLessons = UserProgress::where('user_id', $user->id)
            ->where('module_id', $module->id)
            ->where('completed', true)
            ->count();
        
        $progressPercentage = $totalLessons > 0 ? ($completedLessons / $totalLessons) * 100 : 0;
        
        $progress = (object)[
            'progress_percentage' => round($progressPercentage),
            'completed_lessons' => $completedLessons,
            'total_lessons' => $totalLessons,
        ];

        // Get completed lesson IDs for UI
        $completedLessonIds = UserProgress::where('user_id', $user->id)
            ->where('module_id', $module->id)
            ->where('completed', true)
            ->pluck('lesson_id')
            ->toArray();

        // Get quizzes for this module
        $moduleQuizzes = $module->quizzes()->where('is_active', true)->get();
        
        // Get lesson quizzes
        $lessonQuizzes = [];
        foreach ($lessons as $lesson) {
            if ($lesson->quiz && $lesson->quiz->is_active) {
                $lessonQuizzes[$lesson->id] = $lesson->quiz;
            }
        }
        
        // Get user's quiz attempts
        $quizAttempts = $user->quizAttempts()
            ->whereIn('quiz_id', $moduleQuizzes->pluck('id')->merge(collect($lessonQuizzes)->pluck('id')))
            ->get()
            ->groupBy('quiz_id');

        return view('learner.modules.show', compact(
            'module', 
            'lessons', 
            'isEnrolled', 
            'progress', 
            'completedLessonIds',
            'moduleQuizzes',
            'lessonQuizzes',
            'quizAttempts'
        ));
    }

    /**
     * Enroll in a module
     */
    public function enroll(Module $module)
    {
        $user = Auth::user();

        // Security checks
        if (!$module->is_published) {
            abort(404);
        }

        // age_range is already the grade level
        $gradeLevel = $user->learnerProfile->age_range;
        if (!$this->canAccessModule($module, $gradeLevel)) {
            return back()->with('error', 'This module is not available for your age group.');
        }

        // Check if already enrolled
        if ($user->moduleEnrollments()->where('module_id', $module->id)->exists()) {
            return back()->with('info', 'You are already enrolled in this module.');
        }

        // Create enrollment
        ModuleEnrollment::create([
            'user_id' => $user->id,
            'module_id' => $module->id,
        ]);

        return redirect()->route('learner.modules.show', $module)
            ->with('success', 'Successfully enrolled in module!');
    }

    /**
     * Get all grade levels accessible to a learner
     */
    private function getAccessibleGradeLevels(string $learnerGradeLevel): array
    {
        $allLevels = ['grade_4_up', 'grade_6_up', 'grade_8_up', 'grade_10_up', 'adult_18_plus'];
        $gradeHierarchy = [
            'grade_4_up' => 1,
            'grade_6_up' => 2,
            'grade_8_up' => 3,
            'grade_10_up' => 4,
            'adult_18_plus' => 5,
        ];

        $learnerLevel = $gradeHierarchy[$learnerGradeLevel] ?? 1;
        
        // Return all levels at or below learner's level
        return array_filter($allLevels, function ($level) use ($gradeHierarchy, $learnerLevel) {
            return ($gradeHierarchy[$level] ?? 1) <= $learnerLevel;
        });
    }

    /**
     * Check if learner can access module based on grade level
     */
    private function canAccessModule(Module $module, string $learnerGradeLevel): bool
    {
        if (!$module->grade_level) {
            return true; // No restriction
        }

        $gradeHierarchy = [
            'grade_4_up' => 1,
            'grade_6_up' => 2,
            'grade_8_up' => 3,
            'grade_10_up' => 4,
            'adult_18_plus' => 5,
        ];

        $moduleLevel = $gradeHierarchy[$module->grade_level] ?? 1;
        $learnerLevel = $gradeHierarchy[$learnerGradeLevel] ?? 1;

        // Learners can access content at or below their level
        return $learnerLevel >= $moduleLevel;
    }
}
