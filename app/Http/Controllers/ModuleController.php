<?php

namespace App\Http\Controllers;

use App\Models\Module;
use App\Models\ModuleAttachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ModuleController extends Controller
{
    /**
     * Display all modules
     */
    public function index()
    {
        $user = auth()->user();
        
        $query = Module::published()->withCount('lessons')->latest();

        // Filter by grade level if user is a learner with completed profile
        if ($user->isLearner() && $user->learnerProfile && $user->learnerProfile->age_range) {
            $query->forGradeLevel($user->learnerProfile->age_range);
        }

        $modules = $query->paginate(12);

        return view('modules.index', compact('modules'));
    }

    /**
     * Show module details
     */
    public function show(Module $module)
    {
        $module->load(['lessons.quiz', 'attachments']);

        // Check enrollment status
        $enrollment = auth()->check() 
            ? auth()->user()->moduleEnrollments()->where('module_id', $module->id)->first()
            : null;

        $isEnrolled = $enrollment && $enrollment->status === 'approved';
        $enrollmentStatus = $enrollment ? $enrollment->status : null;

        // Get user progress if enrolled
        $progress = $isEnrolled 
            ? auth()->user()->progress()->where('module_id', $module->id)->first()
            : null;

        return view('modules.show', compact('module', 'isEnrolled', 'enrollmentStatus', 'progress'));
    }

    /**
     * Enroll in a module
     */
    public function enroll(Module $module)
    {
        $user = auth()->user();

        // Check if already enrolled or has pending request
        $existingEnrollment = $user->moduleEnrollments()->where('module_id', $module->id)->first();
        
        if ($existingEnrollment) {
            if ($existingEnrollment->status === 'pending') {
                return redirect()->route('modules.show', $module)
                    ->with('info', 'Your enrollment request is pending instructor approval.');
            }
            if ($existingEnrollment->status === 'approved') {
                return redirect()->route('modules.show', $module)
                    ->with('info', 'You are already enrolled in this module.');
            }
            if ($existingEnrollment->status === 'rejected') {
                return redirect()->route('modules.show', $module)
                    ->with('error', 'Your enrollment request was rejected by the instructor.');
            }
        }

        // Check enrollment mode
        if ($module->enrollment_mode === 'manual') {
            // Manual approval - create pending enrollment
            $user->moduleEnrollments()->create([
                'module_id' => $module->id,
                'status' => 'pending',
                'enrolled_at' => null,
            ]);

            return redirect()->route('modules.show', $module)
                ->with('success', 'Enrollment request submitted! Waiting for instructor approval.');
        }

        // Auto approval - create approved enrollment
        $user->moduleEnrollments()->create([
            'module_id' => $module->id,
            'status' => 'approved',
            'enrolled_at' => now(),
        ]);

        // Note: UserProgress is tracked per-lesson, not per-module.
        // Progress records will be created as the user accesses lessons.

        return redirect()->route('modules.show', $module)
            ->with('success', 'Successfully enrolled in module!');
    }

    /**
     * Download module attachment
     * Only available for premium users
     */
    public function downloadAttachment(ModuleAttachment $attachment)
    {
        $user = auth()->user();

        // Check if user is premium
        if (!$user->isPremium()) {
            return redirect()->route('subscription.upgrade')
                ->with('error', 'Module downloads are only available for premium members.');
        }

        // Check if user is enrolled in the module
        if (!$user->moduleEnrollments()->where('module_id', $attachment->module_id)->exists()) {
            return redirect()->route('modules.show', $attachment->module_id)
                ->with('error', 'You must enroll in the module first.');
        }

        // Check if file exists
        if (!Storage::exists($attachment->file_path)) {
            return redirect()->back()
                ->with('error', 'File not found.');
        }

        // Update download count
        $attachment->increment('download_count');

        // Download file
        return Storage::download($attachment->file_path, $attachment->file_name);
    }

    /**
     * Get downloadable attachments for a module
     * Premium feature
     */
    public function attachments(Module $module)
    {
        $user = auth()->user();

        // Check if user is premium
        if (!$user->isPremium()) {
            return redirect()->route('subscription.upgrade')
                ->with('error', 'Module downloads are only available for premium members.');
        }

        // Check if user is enrolled
        if (!$user->moduleEnrollments()->where('module_id', $module->id)->exists()) {
            return redirect()->route('modules.show', $module)
                ->with('error', 'You must enroll in the module first.');
        }

        $attachments = $module->attachments;

        return view('modules.attachments', compact('module', 'attachments'));
    }
}
