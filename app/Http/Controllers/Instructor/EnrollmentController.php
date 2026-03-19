<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Enums\EnrollmentStatus;
use App\Models\Module;
use App\Models\ModuleEnrollment;
use App\Notifications\Learner\EnrollmentApprovedNotification;
use App\Notifications\Learner\EnrollmentRejectedNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnrollmentController extends Controller
{
    /**
     * Show pending enrollment requests for instructor's modules
     */
    public function index()
    {
        // Get all modules (instructor can see all for now)
        // TODO: Filter by instructor's modules when instructor relationship is added
        $pendingEnrollments = ModuleEnrollment::with(['user.learnerProfile', 'module'])
            ->pending()
            ->latest()
            ->paginate(20);

        return view('instructor.enrollments.index', compact('pendingEnrollments'));
    }

    /**
     * Show learner details for review before approval
     */
    public function show(ModuleEnrollment $enrollment)
    {
        // Load relationships
        $enrollment->load([
            'user.learnerProfile.city', 
            'user.learnerProfile.barangay',
            'user.moduleEnrollments.module',
            'module'
        ]);

        // Calculate learner statistics
        $totalEnrollments = $enrollment->user->moduleEnrollments()->approved()->count();
        $completedModules = $enrollment->user->moduleEnrollments()
            ->approved()
            ->whereNotNull('completed_at')
            ->count();

        // Calculate completion rate
        $completionRate = $totalEnrollments > 0 
            ? round(($completedModules / $totalEnrollments) * 100) 
            : 0;

        // Get recent enrollments
        $recentEnrollments = $enrollment->user->moduleEnrollments()
            ->with('module')
            ->approved()
            ->latest()
            ->take(5)
            ->get();

        return view('instructor.enrollments.show', compact(
            'enrollment',
            'totalEnrollments',
            'completedModules',
            'completionRate',
            'recentEnrollments'
        ));
    }

    /**
     * Approve an enrollment request
     */
    public function approve(ModuleEnrollment $enrollment)
    {
        if ($enrollment->status !== EnrollmentStatus::Pending) {
            return redirect()->back()
                ->with('error', 'This enrollment request is not pending.');
        }

        $enrollment->loadMissing('module', 'user');

        // Update enrollment to approved
        $enrollment->update([
            'status' => EnrollmentStatus::Approved,
            'enrolled_at' => now(),
            'rejection_reason_code' => null,
            'rejection_reason_note' => null,
            'rejected_by_instructor_id' => null,
            'rejected_at' => null,
        ]);

        $enrollment->user->notify(new EnrollmentApprovedNotification($enrollment));

        // Note: UserProgress records are created per-lesson as learners progress,
        // not at enrollment time. The user_progress table tracks lesson-level completion.

        return redirect()->back()
            ->with('success', 'Enrollment request approved successfully!');
    }

    /**
     * Reject an enrollment request
     */
    public function reject(Request $request, ModuleEnrollment $enrollment)
    {
        if ($enrollment->status !== EnrollmentStatus::Pending) {
            return redirect()->back()
                ->with('error', 'This enrollment request is not pending.');
        }

        $validated = $request->validate([
            'rejection_reason_code' => ['required', 'string', 'max:100'],
            'rejection_reason_note' => ['nullable', 'string', 'max:1000'],
        ]);

        $enrollment->loadMissing('module', 'user');

        $instructor = Auth::user();
        $instructorName = trim((string) ($instructor?->full_name ?? $instructor?->name ?? 'your instructor'));

        $reasonCode = (string) $validated['rejection_reason_code'];
        $reasonNote = $validated['rejection_reason_note'] ?? null;

        // Update enrollment to rejected
        $enrollment->update([
            'status' => EnrollmentStatus::Rejected,
            'rejection_reason_code' => $reasonCode,
            'rejection_reason_note' => $reasonNote,
            'rejected_by_instructor_id' => $instructor?->id,
            'rejected_at' => now(),
        ]);

        $enrollment->user->notify(new EnrollmentRejectedNotification($enrollment, $instructorName));

        return redirect()->back()
            ->with('success', 'Enrollment request rejected.');
    }

    /**
     * Show all enrollments for a specific module
     */
    public function moduleEnrollments(Module $module)
    {
        $enrollments = $module->enrollments()
            ->with(['user.learnerProfile'])
            ->latest()
            ->paginate(20);

        return view('instructor.enrollments.module', compact('module', 'enrollments'));
    }
}
