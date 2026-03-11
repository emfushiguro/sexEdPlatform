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
     * Display all published modules filtered by learner's age
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $learnerProfile = $user->learnerProfile;
        
        if (!$learnerProfile) {
            return redirect()->route('profile.complete')
                ->with('error', 'Please complete your profile to access modules.');
        }

        $learnerAge = $learnerProfile->getAge();
        $search = $request->get('search');

        $modules = Module::where('is_published', true)
            ->where(function ($query) use ($learnerAge) {
                $query->where('min_age', '<=', $learnerAge)
                      ->where('max_age', '>=', $learnerAge);
            })
            ->when($search, fn($q) => $q->where('title', 'like', "%{$search}%"))
            ->withCount('lessons')
            ->with(['lessons' => function ($query) {
                $query->where('is_published', true)->orderBy('order');
            }])
            ->orderBy('order')
            ->get();

        // Get user's enrollments
        $enrolledModuleIds = $user->moduleEnrollments()->pluck('module_id')->toArray();
        $enrollments = ModuleEnrollment::where('user_id', $user->id)
            ->get()
            ->keyBy('module_id');
        
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

        return view('learner.modules.index', compact('modules', 'enrolledModuleIds', 'progress', 'search', 'enrollments'));
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

        // Security: Check age-based access
        $learnerAge = $learnerProfile->getAge();
        if (!$this->canAccessModule($module, $learnerAge)) {
            return redirect()->route('learner.modules.index')
                ->with('error', 'This module is not available for your age group.');
        }

        // Get published lessons only
        $lessons = $module->lessons()
            ->where('is_published', true)
            ->orderBy('order')
            ->get();

        // Check enrollment status
        $enrollment = $user->moduleEnrollments()
            ->where('module_id', $module->id)
            ->first();

        $isEnrolled = $enrollment && $enrollment->status === 'approved';
        $enrollmentStatus = $enrollment ? $enrollment->status : null;

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
            'enrollmentStatus',
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

        // Check age-based access
        $learnerAge = $user->learnerProfile->getAge();
        if (!$this->canAccessModule($module, $learnerAge)) {
            return back()->with('error', 'This module is not available for your age group.');
        }

        // Check if already enrolled or has pending request
        $existingEnrollment = $user->moduleEnrollments()->where('module_id', $module->id)->first();
        
        if ($existingEnrollment) {
            if ($existingEnrollment->status === 'pending') {
                return back()->with('info', 'Your enrollment request is pending instructor approval.');
            }
            if ($existingEnrollment->status === 'approved') {
                return back()->with('info', 'You are already enrolled in this module.');
            }
            if ($existingEnrollment->status === 'rejected') {
                return back()->with('error', 'Your enrollment request was rejected by the instructor.');
            }
            if ($existingEnrollment->status === 'pending_parent_approval') {
                return back()->with('info', 'Your enrollment request is awaiting your parent\'s approval.');
            }
        }

        // Check if child requires parental content approval before enrolling
        $learnerProfile = $user->learnerProfile;
        if ($learnerProfile?->requires_parental_consent) {
            $parentUser = $user->parent();
            if ($parentUser) {
                $parentChildAccount = \App\Models\ParentChildAccount::where('parent_user_id', $parentUser->id)
                    ->where('child_user_id', $user->id)
                    ->first();
                if ($parentChildAccount?->can_approve_content) {
                    ModuleEnrollment::create([
                        'user_id'     => $user->id,
                        'module_id'   => $module->id,
                        'status'      => 'pending_parent_approval',
                        'enrolled_at' => null,
                    ]);
                    return redirect()->route('learner.modules.show', $module)
                        ->with('info', 'Your enrollment request has been sent to your parent for approval.');
                }
            }
        }

        // Check enrollment mode
        if ($module->enrollment_mode === 'manual') {
            // Manual approval - create pending enrollment
            ModuleEnrollment::create([
                'user_id' => $user->id,
                'module_id' => $module->id,
                'status' => 'pending',
                'enrolled_at' => null,
            ]);

            return redirect()->route('learner.modules.show', $module)
                ->with('success', 'Enrollment request submitted! Waiting for instructor approval.');
        }

        // Auto approval - create approved enrollment
        ModuleEnrollment::create([
            'user_id' => $user->id,
            'module_id' => $module->id,
            'status' => 'approved',
            'enrolled_at' => now(),
        ]);

        return redirect()->route('learner.modules.show', $module)
            ->with('success', 'Successfully enrolled in module!');
    }

    /**
     * Check if learner can access module based on age
     */
    private function canAccessModule(Module $module, int $learnerAge): bool
    {
        // Check if learner's age falls within module's age range
        return $learnerAge >= $module->min_age && $learnerAge <= $module->max_age;
    }
}
