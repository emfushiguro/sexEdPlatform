<?php

namespace App\Services\Moderation;

use App\Enums\EnforcementActionType;
use App\Enums\ViolationSeverity;
use App\Models\EnforcementAction;
use App\Models\ModerationCase;
use App\Models\User;
use App\Notifications\Moderation\EnforcementIssuedNotification;
use InvalidArgumentException;

class EnforcementActionService
{
    public function issueAction(
        User $user,
        EnforcementActionType $actionType,
        ViolationSeverity $severity,
        string $triggerType = 'manual',
        bool $skipLadder = false,
        ?string $skipRationale = null,
        ?ModerationCase $moderationCase = null,
        ?User $issuedByAdmin = null,
        ?string $notes = null,
    ): EnforcementAction {
        $this->guardEscalationSkipPolicy($severity, $skipLadder, $skipRationale);
        $this->guardPermanentSuspensionIssuance($actionType, $triggerType);

        $action = EnforcementAction::query()->create([
            'user_id' => $user->id,
            'moderation_case_id' => $moderationCase?->id,
            'action_type' => $actionType,
            'severity_level' => $severity,
            'trigger_type' => $triggerType,
            'starts_at' => now(),
            'ends_at' => $this->resolveEndsAt($actionType),
            'status' => 'executed',
            'issued_by_admin_id' => $issuedByAdmin?->id,
            'skip_ladder' => $skipLadder,
            'skip_rationale' => $skipRationale,
            'notes' => $notes,
        ]);

        $user->notify(new EnforcementIssuedNotification($action));

        return $action;
    }

    private function guardEscalationSkipPolicy(
        ViolationSeverity $severity,
        bool $skipLadder,
        ?string $skipRationale,
    ): void {
        if (!$skipLadder) {
            return;
        }

        if (in_array($severity, [ViolationSeverity::Minor, ViolationSeverity::Moderate], true)) {
            throw new InvalidArgumentException('Minor and moderate severities cannot skip the escalation ladder.');
        }

        if (trim((string) $skipRationale) === '') {
            throw new InvalidArgumentException('Escalation ladder skips require rationale for major and critical severities.');
        }
    }

    private function guardPermanentSuspensionIssuance(
        EnforcementActionType $actionType,
        string $triggerType,
    ): void {
        if ($actionType === EnforcementActionType::PermanentSuspension && $triggerType !== 'manual') {
            throw new InvalidArgumentException('Permanent suspension can only be issued manually by an admin.');
        }
    }

    private function resolveEndsAt(EnforcementActionType $actionType): ?\Illuminate\Support\Carbon
    {
        return match ($actionType) {
            EnforcementActionType::TemporarySuspension => now()->addDays(3),
            EnforcementActionType::ExtendedSuspension => now()->addDays(14),
            EnforcementActionType::PermanentSuspension => null,
            default => null,
        };
    }
}
