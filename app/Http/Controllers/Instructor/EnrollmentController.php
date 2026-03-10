<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Models\Module;
use App\Models\ModuleEnrollment;
use App\Notifications\EnrollmentApproved;
use App\Notifications\EnrollmentRejected;
use Illuminate\Http\Request;

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
        if ($enrollment->status !== 'pending') {
            return redirect()->back()
                ->with('error', 'This enrollment request is not pending.');
        }

        // Update enrollment to approved
        $enrollment->update([
            'status' => 'approved',
            'enrolled_at' => now(),
        ]);

        // Notify the learner
        $enrollment->user->notify(new EnrollmentApproved($enrollment));

        return redirect()->back()
            ->with('success', 'Enrollment request approved successfully!');
    }

    /**
     * Reject an enrollment request
     */
    public function reject(ModuleEnrollment $enrollment)
    {
        if ($enrollment->status !== 'pending') {
            return redirect()->back()
                ->with('error', 'This enrollment request is not pending.');
        }

        // Update enrollment to rejected
        $enrollment->update([
            'status' => 'rejected',
        ]);

        // Notify the learner
        $enrollment->user->notify(new EnrollmentRejected($enrollment));

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
