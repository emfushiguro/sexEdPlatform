<?php

namespace App\Http\Controllers;

use App\Models\ModuleEnrollment;
use App\Models\User;
use App\Services\ParentChildService;
use Illuminate\Http\RedirectResponse;

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

        if ($enrollment->user_id !== $child->id || $enrollment->status !== 'pending_parent_approval') {
            abort(403);
        }

        $newStatus = $enrollment->module->enrollment_mode === 'manual' ? 'pending' : 'approved';

        $enrollment->update([
            'status'      => $newStatus,
            'enrolled_at' => $newStatus === 'approved' ? now() : null,
        ]);

        return redirect()->route('parent.children.show', $child)
            ->with('success', 'Enrollment approved.');
    }

    public function rejectEnrollment(User $child, ModuleEnrollment $enrollment): RedirectResponse
    {
        $this->authorize('view', $child);

        if ($enrollment->user_id !== $child->id || $enrollment->status !== 'pending_parent_approval') {
            abort(403);
        }

        $enrollment->update(['status' => 'rejected']);

        return redirect()->route('parent.children.show', $child)
            ->with('info', 'Enrollment request rejected.');
    }
}
