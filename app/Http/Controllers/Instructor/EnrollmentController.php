<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Instructor\RejectEnrollmentRequest;
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
        $pendingEnrollments = ModuleEnrollment::with([
            'user.learnerProfile.city',
            'user.learnerProfile.barangay',
            'module' => fn ($query) => $query->withCount('lessons'),
        ])
            ->whereHas('module', fn ($query) => $query->where('created_by', Auth::id()))
            ->latest()
            ->get();

        return view('instructor.enrollments.index', compact('pendingEnrollments'));
    }

    /**
     * Show learner details for review before approval
     */
    public function show(ModuleEnrollment $enrollment)
    {
        $this->ensureEnrollmentBelongsToInstructor($enrollment);

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
    public function approve(Request $request, ModuleEnrollment $enrollment)
    {
        $this->ensureEnrollmentBelongsToInstructor($enrollment);

        if ($enrollment->status !== EnrollmentStatus::Pending) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'This enrollment request is not pending.',
                ], 422);
            }

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

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Enrollment request approved successfully!',
                'status' => EnrollmentStatus::Approved->value,
            ]);
        }

        return redirect()->back()->with('success', 'Enrollment request approved successfully!');
    }

    /**
     * Reject an enrollment request
     */
    public function reject(RejectEnrollmentRequest $request, ModuleEnrollment $enrollment)
    {
        $this->ensureEnrollmentBelongsToInstructor($enrollment);

        if ($enrollment->status !== EnrollmentStatus::Pending) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'This enrollment request is not pending.',
                ], 422);
            }

            return redirect()->back()
                ->with('error', 'This enrollment request is not pending.');
        }

        $validated = $request->validated();

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

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Enrollment request rejected.',
                'status' => EnrollmentStatus::Rejected->value,
            ]);
        }

        return redirect()->back()->with('success', 'Enrollment request rejected.');
    }

    /**
     * Show all enrollments for a specific module
     */
    public function moduleEnrollments(Module $module)
    {
        abort_unless((int) $module->created_by === (int) Auth::id(), 403);

        $enrollments = $module->enrollments()
            ->with(['user.learnerProfile'])
            ->latest()
            ->paginate(20);

        return view('instructor.enrollments.module', compact('module', 'enrollments'));
    }

    /**
     * Remove an enrollment record owned by the instructor.
     */
    public function destroy(Request $request, ModuleEnrollment $enrollment)
    {
        $this->ensureEnrollmentBelongsToInstructor($enrollment);

        $learnerName = $enrollment->user?->name ?? 'Learner';
        $moduleTitle = $enrollment->module?->title ?? 'module';

        $enrollment->delete();

        $message = sprintf('%s was removed from %s enrollment records.', $learnerName, $moduleTitle);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
            ]);
        }

        return redirect()->back()->with('success', $message);
    }

    private function ensureEnrollmentBelongsToInstructor(ModuleEnrollment $enrollment): void
    {
        $enrollment->loadMissing('module');
        abort_unless((int) ($enrollment->module?->created_by ?? 0) === (int) Auth::id(), 403);
    }
}
