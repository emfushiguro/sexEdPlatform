<?php

namespace App\Services\Identity;

use App\Models\LearnerIdentityVerification;
use App\Models\User;
use App\Support\LearnerAgePolicy;
use DomainException;
use Illuminate\Support\Facades\DB;

class LearnerIdentityRequirement
{
    public function current(User $user): ?LearnerIdentityVerification
    {
        if (! $user->isLearner() || $user->isParentRegistration()) {
            return null;
        }

        $pathway = $this->pathwayFor($user);

        return DB::transaction(function () use ($user, $pathway): LearnerIdentityVerification {
            $lockedUser = User::query()->lockForUpdate()->findOrFail($user->getKey());
            $active = $lockedUser->identityVerifications()
                ->whereNull('superseded_at')
                ->latest('id')
                ->lockForUpdate()
                ->first();

            if ($active?->pathway === $pathway) {
                return $active;
            }

            if ($active !== null) {
                $active->forceFill(['superseded_at' => now()])->save();
                $this->audit($active, 'superseded', $active->status, $active->status);
            }

            $case = $lockedUser->identityVerifications()->create([
                'pathway' => $pathway,
                'submission_round' => 0,
            ]);
            $this->audit($case, 'created');

            return $case;
        });
    }

    public function createForNewLearner(User $user): LearnerIdentityVerification
    {
        $case = $this->current($user);
        if ($case === null) {
            throw new DomainException('Learner identity verification requires an eligible learner.');
        }

        return $case;
    }

    public function pathwayFor(User $user): string
    {
        return match (LearnerAgePolicy::classify($user->birthdate)) {
            LearnerAgePolicy::ELIGIBLE_TEENAGER => 'teen',
            LearnerAgePolicy::ELIGIBLE_ADULT => 'adult',
            default => throw new DomainException('Learner is not currently eligible for identity verification.'),
        };
    }

    private function audit(LearnerIdentityVerification $case, string $action, ?string $from = null, ?string $to = null): void
    {
        $case->audits()->create([
            'actor_id' => null,
            'action' => $action,
            'from_status' => $from,
            'to_status' => $to,
            'submission_round' => $case->submission_round,
            'created_at' => now(),
        ]);
    }
}
