<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ParentChildModerationReason;
use App\Enums\VerificationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RejectChildVerificationRequest;
use App\Http\Requests\Admin\RejectParentVerificationRequest;
use App\Models\ParentChildAccount;
use App\Models\User;
use App\Services\ParentChildVerificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ParentChildVerificationController extends Controller
{
    public function __construct(private readonly ParentChildVerificationService $service)
    {
    }

    public function index(Request $request): View
    {
        $type = $request->string('type')->toString() ?: 'parents';
        if (! in_array($type, ['parents', 'children'], true)) {
            $type = 'parents';
        }

        $status = $request->string('status')->toString() ?: VerificationStatus::Pending->value;
        if (! in_array($status, VerificationStatus::values(), true)) {
            $status = VerificationStatus::Pending->value;
        }

        return view('admin.parent-verifications.index', [
            'type' => $type,
            'status' => $status,
            'parentApplications' => $this->parentApplications(),
            'childApplications' => $this->childApplications(),
            'pendingParentCount' => User::query()
                ->where('is_parent_registration', true)
                ->where(function ($query): void {
                    $query->where('parent_verification_status', VerificationStatus::Pending->value)
                        ->orWhereNull('parent_verification_status');
                })
                ->count(),
            'approvedParentCount' => User::query()
                ->where('is_parent_registration', true)
                ->where('parent_verification_status', VerificationStatus::Approved->value)
                ->count(),
            'rejectedParentCount' => User::query()
                ->where('is_parent_registration', true)
                ->where('parent_verification_status', VerificationStatus::Rejected->value)
                ->count(),
            'pendingChildCount' => ParentChildAccount::query()
                ->where(function ($query): void {
                    $query->where('verification_status', VerificationStatus::Pending->value)
                        ->orWhereNull('verification_status');
                })
                ->count(),
            'approvedChildCount' => ParentChildAccount::query()
                ->where('verification_status', VerificationStatus::Approved->value)
                ->count(),
            'rejectedChildCount' => ParentChildAccount::query()
                ->where('verification_status', VerificationStatus::Rejected->value)
                ->count(),
        ]);
    }

    public function approveParent(Request $request, User $user): RedirectResponse|JsonResponse
    {
        if (! $user->isParentRegistration()) {
            return $this->respondError($request, 'Selected account is not a parent verification application.', 422);
        }

        if (! $this->isPendingStatus($user->parent_verification_status)) {
            return $this->respondError($request, 'Decision already finalized. Only pending records can be moderated.', 409);
        }

        $this->service->approveParent($user);
        $user->refresh();

        return $this->respondSuccess(
            request: $request,
            message: 'Parent verification approved successfully.',
            status: $this->normalizedStatus($user->parent_verification_status),
            rejectionReason: $user->parent_verification_rejection_reason,
        );
    }

    public function rejectParent(RejectParentVerificationRequest $request, User $user): RedirectResponse|JsonResponse
    {
        if (! $user->isParentRegistration()) {
            return $this->respondError($request, 'Selected account is not a parent verification application.', 422);
        }

        if (! $this->isPendingStatus($user->parent_verification_status)) {
            return $this->respondError($request, 'Decision already finalized. Only pending records can be moderated.', 409);
        }

        $reason = $this->composeRejectionReason(
            (string) $request->string('reason_code'),
            $request->filled('custom_reason') ? (string) $request->string('custom_reason') : null,
            $request->boolean('issue_warning')
        );

        $this->service->rejectParent($user, $reason);
        $user->refresh();

        return $this->respondSuccess(
            request: $request,
            message: 'Parent verification rejected successfully.',
            status: $this->normalizedStatus($user->parent_verification_status),
            rejectionReason: $user->parent_verification_rejection_reason,
        );
    }

    public function approveChild(Request $request, ParentChildAccount $parentChildAccount): RedirectResponse|JsonResponse
    {
        if (! $this->isPendingStatus($parentChildAccount->verification_status)) {
            return $this->respondError($request, 'Decision already finalized. Only pending records can be moderated.', 409);
        }

        $this->service->approveChild($parentChildAccount);
        $parentChildAccount->refresh();

        return $this->respondSuccess(
            request: $request,
            message: 'Child verification approved successfully.',
            status: $this->normalizedStatus($parentChildAccount->verification_status),
            rejectionReason: $parentChildAccount->verification_rejection_reason,
        );
    }

    public function rejectChild(RejectChildVerificationRequest $request, ParentChildAccount $parentChildAccount): RedirectResponse|JsonResponse
    {
        if (! $this->isPendingStatus($parentChildAccount->verification_status)) {
            return $this->respondError($request, 'Decision already finalized. Only pending records can be moderated.', 409);
        }

        $reason = $this->composeRejectionReason(
            (string) $request->string('reason_code'),
            $request->filled('custom_reason') ? (string) $request->string('custom_reason') : null,
            $request->boolean('issue_warning')
        );

        $this->service->rejectChild($parentChildAccount, $reason);
        $parentChildAccount->refresh();

        return $this->respondSuccess(
            request: $request,
            message: 'Child verification rejected successfully.',
            status: $this->normalizedStatus($parentChildAccount->verification_status),
            rejectionReason: $parentChildAccount->verification_rejection_reason,
        );
    }

    private function composeRejectionReason(string $reasonCode, ?string $customReason = null, bool $issueWarning = false): string
    {
        $reason = ParentChildModerationReason::tryFrom($reasonCode);

        $baseReason = $reason?->label() ?? str($reasonCode)->replace('_', ' ')->title()->toString();

        if ($reason === ParentChildModerationReason::Others && trim((string) $customReason) !== '') {
            $baseReason = trim((string) $customReason);
        }

        if (! $issueWarning) {
            return $baseReason;
        }

        return $baseReason."\n\nAdministrative warning issued to account holder.";
    }

    private function parentApplications()
    {
        return User::query()
            ->where('is_parent_registration', true)
            ->with('learnerProfile')
            ->latest()
            ->get();
    }

    private function childApplications()
    {
        return ParentChildAccount::query()
            ->with([
                'parent.learnerProfile',
                'child.learnerProfile',
            ])
            ->latest()
            ->get();
    }

    private function isPendingStatus(?string $status): bool
    {
        return $this->normalizedStatus($status) === VerificationStatus::Pending->value;
    }

    private function normalizedStatus(?string $status): string
    {
        return $status ?: VerificationStatus::Pending->value;
    }

    private function respondSuccess(
        Request $request,
        string $message,
        string $status,
        ?string $rejectionReason = null,
    ): RedirectResponse|JsonResponse
    {
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'message' => $message,
                'status' => $status,
                'rejection_reason' => $rejectionReason,
            ]);
        }

        return back()->with('success', $message);
    }

    private function respondError(Request $request, string $message, int $httpStatus = 422): RedirectResponse|JsonResponse
    {
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'message' => $message,
            ], $httpStatus);
        }

        return back()->with('error', $message);
    }
}
