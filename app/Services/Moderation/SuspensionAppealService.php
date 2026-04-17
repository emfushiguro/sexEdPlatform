<?php

namespace App\Services\Moderation;

use App\Enums\EnforcementActionType;
use App\Models\SuspensionAppeal;
use App\Models\User;
use App\Models\UserSuspension;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class SuspensionAppealService
{
    public function __construct(
        private readonly SuspensionService $suspensionService,
    ) {
    }

    /**
     * @param array<string, mixed>|null $evidencePayload
     */
    public function submitAppeal(
        UserSuspension $suspension,
        User $user,
        string $reason,
        ?array $evidencePayload = null,
    ): SuspensionAppeal {
        $this->guardSubmissionEligibility($suspension, $user);

        return DB::transaction(function () use ($suspension, $user, $reason, $evidencePayload): SuspensionAppeal {
            $appeal = SuspensionAppeal::query()->create([
                'user_suspension_id' => $suspension->id,
                'user_id' => $user->id,
                'status' => 'pending_review',
                'appeal_reason' => $reason,
                'evidence_payload' => $evidencePayload,
                'submitted_at' => now(),
            ]);

            $suspension->forceFill([
                'appeal_status' => 'appeal_pending',
                'appeal_submitted_at' => now(),
            ])->save();

            return $appeal;
        });
    }

    public function reviewAppeal(
        SuspensionAppeal $appeal,
        User $admin,
        string $action,
        string $decisionNotes,
    ): SuspensionAppeal {
        if (!in_array($action, ['approve', 'reject', 'clarification_requested'], true)) {
            throw new InvalidArgumentException('Unsupported appeal review action.');
        }

        return DB::transaction(function () use ($appeal, $admin, $action, $decisionNotes): SuspensionAppeal {
            $status = match ($action) {
                'approve' => 'approved',
                'reject' => 'rejected',
                'clarification_requested' => 'clarification_requested',
            };

            $appeal->forceFill([
                'status' => $status,
                'reviewed_at' => now(),
                'reviewed_by_admin_id' => $admin->id,
                'review_decision_notes' => $decisionNotes,
                'clarification_requested_at' => $action === 'clarification_requested' ? now() : null,
            ])->save();

            /** @var UserSuspension|null $suspension */
            $suspension = $appeal->suspension;
            if ($suspension) {
                if ($action === 'approve' && $suspension->status === 'active') {
                    $this->suspensionService->revoke($suspension, $admin, 'Appeal approved: ' . $decisionNotes);
                }

                $suspension->forceFill([
                    'appeal_status' => $action === 'clarification_requested' ? 'clarification_requested' : 'resolved',
                ])->save();
            }

            return $appeal->fresh();
        });
    }

    private function guardSubmissionEligibility(UserSuspension $suspension, User $user): void
    {
        if ((int) $suspension->user_id !== (int) $user->id) {
            throw new InvalidArgumentException('Users can only appeal their own suspension.');
        }

        if (!$suspension->starts_at || $suspension->starts_at->lt(now()->subDays(14))) {
            throw new InvalidArgumentException('Appeals must be submitted within 14 days of suspension start.');
        }

        $actionType = $suspension->enforcementAction?->action_type;
        $normalizedActionType = $actionType instanceof EnforcementActionType
            ? $actionType
            : ($actionType ? EnforcementActionType::from((string) $actionType) : null);

        if (in_array($normalizedActionType, [
            EnforcementActionType::TemporarySuspension,
            EnforcementActionType::ExtendedSuspension,
        ], true)) {
            return;
        }

        if ($normalizedActionType === EnforcementActionType::PermanentSuspension
            && $suspension->appeal_status === 'permanent_appeal_allowed') {
            return;
        }

        throw new InvalidArgumentException('This suspension is not currently eligible for appeal submission.');
    }
}
