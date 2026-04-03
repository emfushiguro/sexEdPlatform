<?php

namespace App\Services;

use App\Enums\InstructorRestrictionAction;
use App\Models\InstructorModerationProfile;
use App\Models\InstructorViolationHistory;
use App\Models\Module;
use App\Models\ModuleReviewRequest;
use App\Models\User;

class InstructorModerationPenaltyService
{
    public function recordViolation(
        User $instructor,
        Module $module,
        ModuleReviewRequest $reviewRequest,
        string $reasonCode,
        string $guidanceNote,
    ): InstructorViolationHistory {
        $profile = InstructorModerationProfile::query()->firstOrCreate(
            ['user_id' => $instructor->id],
            [
                'warning_count' => 0,
                'escalation_level' => 0,
            ],
        );

        $nextSequence = ((int) InstructorViolationHistory::query()
            ->where('user_id', $instructor->id)
            ->max('violation_sequence')) + 1;

        $suggestedAction = $this->suggestActionForNextViolation($nextSequence);

        $violation = InstructorViolationHistory::query()->create([
            'user_id' => $instructor->id,
            'module_id' => $module->id,
            'module_review_request_id' => $reviewRequest->id,
            'reason_code' => $reasonCode,
            'guidance_note' => $guidanceNote,
            'violation_sequence' => $nextSequence,
            'suggested_penalty_action' => $suggestedAction,
        ]);

        $profile->forceFill([
            'warning_count' => $nextSequence,
            'last_violation_at' => now(),
            'escalation_level' => min($nextSequence, 4),
        ])->save();

        return $violation;
    }

    public function suggestActionForNextViolation(int $violationSequence): string
    {
        return match (true) {
            $violationSequence <= 1 => InstructorRestrictionAction::WarningOnly->value,
            $violationSequence === 2 => InstructorRestrictionAction::Restrict3Days->value,
            $violationSequence === 3 => InstructorRestrictionAction::Restrict14Days->value,
            default => InstructorRestrictionAction::SuspensionReview->value,
        };
    }

    public function applyConfirmedAction(
        User $instructor,
        InstructorViolationHistory $violation,
        string $action,
        User $admin,
    ): InstructorModerationProfile {
        $profile = InstructorModerationProfile::query()->firstOrCreate(
            ['user_id' => $instructor->id],
            [
                'warning_count' => 0,
                'escalation_level' => 0,
            ],
        );

        $now = now();
        $status = $profile->current_restriction_status;
        $startsAt = $profile->restriction_starts_at;
        $endsAt = $profile->restriction_ends_at;

        if ($action === InstructorRestrictionAction::Restrict3Days->value) {
            $status = 'restricted';
            $startsAt = $now;
            $endsAt = $now->copy()->addDays(3);
        } elseif ($action === InstructorRestrictionAction::Restrict14Days->value) {
            $status = 'restricted';
            $startsAt = $now;
            $endsAt = $now->copy()->addDays(14);
        } elseif ($action === InstructorRestrictionAction::SuspensionReview->value) {
            $status = 'pending_suspension_review';
            $startsAt = $now;
            $endsAt = null;
        }

        $profile->forceFill([
            'current_restriction_status' => $status,
            'restriction_starts_at' => $startsAt,
            'restriction_ends_at' => $endsAt,
        ])->save();

        $violation->forceFill([
            'confirmed_penalty_action' => $action,
            'confirmed_by_admin_id' => $admin->id,
        ])->save();

        return $profile->fresh();
    }
}
