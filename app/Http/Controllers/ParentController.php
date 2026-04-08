<?php

namespace App\Http\Controllers;

use App\Enums\EnrollmentStatus;
use App\Models\ModuleEnrollment;
use App\Models\User;
use App\Notifications\Learner\ParentEnrollmentApprovedNotification;
use App\Notifications\Learner\ParentEnrollmentRejectedNotification;
use App\Notifications\Parent\ChildEnrollmentApprovedNotification;
use App\Notifications\Parent\ChildEnrollmentRejectedNotification;
use App\Services\ParentChildService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ParentController extends Controller
{
    public function __construct(private ParentChildService $service) {}

    public function show(User $child)
    {
        $this->authorize('view', $child);

        $parent = auth()->user();

        $canApproveContent = $parent->children()
            ->where('users.id', $child->id)
            ->first()
            ?->pivot->can_approve_content ?? false;

        return view('parent.children.show', [
            'child'              => $child,
            'progress'           => $this->service->getProgress($child),
            'quizResults'        => $this->service->getQuizResults($child),
            'achievements'       => $this->service->getAchievements($child),
            'pendingEnrollments' => $canApproveContent ? $this->service->getPendingEnrollments($child) : collect(),
            'canApproveContent'  => $canApproveContent,
        ]);
    }

    public function approveEnrollment(User $child, ModuleEnrollment $enrollment): RedirectResponse
    {
        $this->authorize('view', $child);

        /** @var User $parent */
        $parent = auth()->user();

        if ($enrollment->user_id !== $child->id || $enrollment->status !== EnrollmentStatus::PendingParentApproval) {
            abort(403);
        }

        $newStatus = $enrollment->module->enrollment_mode === 'manual' ? 'pending' : 'approved';

        if ($enrollment->module->access_type === 'paid') {
            // Paid modules require checkout completion before full access.
            $newStatus = EnrollmentStatus::Pending->value;
        }

        $enrollment->update([
            'status'      => $newStatus,
            'enrolled_at' => $newStatus === 'approved' ? now() : null,
        ]);

        $freshEnrollment = $enrollment->fresh(['module']);
        $child->notify(new ParentEnrollmentApprovedNotification($freshEnrollment, $parent));
        $parent->notify(new ChildEnrollmentApprovedNotification($freshEnrollment, $child));

        return redirect()->route('parent.children.show', $child)
            ->with('success', 'Enrollment approved.');
    }

    public function rejectEnrollment(Request $request, User $child, ModuleEnrollment $enrollment): RedirectResponse
    {
        $this->authorize('view', $child);

        /** @var User $parent */
        $parent = auth()->user();

        if ($enrollment->user_id !== $child->id || $enrollment->status !== EnrollmentStatus::PendingParentApproval) {
            abort(403);
        }

        $enrollment->update(['status' => 'rejected']);

        $reason = trim((string) $request->input('reason', ''));
        $normalizedReason = $reason !== '' ? $reason : null;

        $freshEnrollment = $enrollment->fresh(['module']);
        $child->notify(new ParentEnrollmentRejectedNotification($freshEnrollment, $parent, $normalizedReason));
        $parent->notify(new ChildEnrollmentRejectedNotification($freshEnrollment, $child, $normalizedReason));

        return redirect()->route('parent.children.show', $child)
            ->with('info', 'Enrollment request rejected.');
    }
}
