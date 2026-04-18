<?php

namespace App\Services\Moderation;

use App\Enums\ViolationSeverity;
use App\Models\ModerationCase;
use App\Models\User;
use App\Models\Violation;
use InvalidArgumentException;

class ViolationService
{
    public function issueFromCase(
        ModerationCase $moderationCase,
        string $violationType,
        ViolationSeverity $severity,
        string $triggerSource = 'manual',
        ?User $issuedByAdmin = null,
    ): Violation {
        if ($moderationCase->decision !== 'confirmed_violation') {
            throw new InvalidArgumentException('Violations can only be issued from confirmed violation cases.');
        }

        return Violation::query()->create([
            'user_id' => $moderationCase->reported_user_id,
            'moderation_case_id' => $moderationCase->id,
            'violation_type' => $violationType,
            'severity_level' => $severity,
            'violation_points' => $severity->points(),
            'trigger_source' => $triggerSource,
            'expires_at' => now()->addDays($severity->expiryDays()),
            'issued_by_admin_id' => $issuedByAdmin?->id,
        ]);
    }
}
