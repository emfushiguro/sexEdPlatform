<?php

namespace App\Services\Identity;

use App\Models\LearnerIdentityVerification;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class LearnerIdentityRequirement
{
    public function current(User $user): ?LearnerIdentityVerification
    {
        if (! $user->isLearner() || $user->isParentRegistration()) {
            return null;
        }

        // A learner without a case predates this requirement.
        if (! $user->identityVerifications()->exists()) {
            return null;
        }

        $pathway = $this->pathwayFor($user);
        $active = $user->identityVerifications()->whereNull('superseded_at')->first();

        if ($active?->pathway === $pathway) {
            return $active;
        }

        $pathsToDelete = [];
        $case = DB::transaction(function () use ($user, $pathway, &$pathsToDelete): LearnerIdentityVerification {
            $user->newQuery()->whereKey($user->getKey())->lockForUpdate()->firstOrFail();
            $active = $user->identityVerifications()->whereNull('superseded_at')->lockForUpdate()->firstOrFail();

            if ($active->pathway === $pathway) {
                return $active;
            }

            $pathsToDelete = $active->evidence()->pluck('storage_path')->all();
            $active->evidence()->delete();
            $active->forceFill(['superseded_at' => now()])->save();
            $this->audit($active, 'superseded', $active->status, $active->status);

            $target = $user->identityVerifications()->firstOrCreate(
                ['pathway' => $pathway],
                ['submission_round' => 0],
            );

            if ($target->wasRecentlyCreated) {
                $this->audit($target, 'created');
            } elseif ($target->superseded_at !== null) {
                $pathsToDelete = array_merge($pathsToDelete, $target->evidence()->pluck('storage_path')->all());
                $target->evidence()->delete();
                $target->forceFill([
                    'status' => null,
                    'document_type' => null,
                    'government_id_type' => null,
                    'government_id_type_other' => null,
                    'submitted_at' => null,
                    'reviewed_by' => null,
                    'reviewed_at' => null,
                    'approved_at' => null,
                    'rejection_reason' => null,
                    'superseded_at' => null,
                ])->save();
                $this->audit($target, 'reactivated');
            }

            return $target;
        });

        if ($pathsToDelete) {
            DB::afterCommit(function () use ($pathsToDelete): void {
                try {
                    if (! Storage::disk('local')->delete($pathsToDelete)) {
                        Log::warning('Learner identity evidence deletion failed.', ['paths' => $pathsToDelete]);
                    }
                } catch (\Throwable $e) {
                    Log::warning('Learner identity evidence deletion failed.', [
                        'paths' => $pathsToDelete,
                        'error' => $e->getMessage(),
                    ]);
                }
            });
        }

        return $case;
    }

    public function createForNewLearner(User $user): LearnerIdentityVerification
    {
        $pathway = $this->pathwayFor($user);

        return DB::transaction(function () use ($user, $pathway): LearnerIdentityVerification {
            $case = $user->identityVerifications()->firstOrCreate(
                ['pathway' => $pathway],
                ['submission_round' => 0],
            );

            if ($case->wasRecentlyCreated) {
                $this->audit($case, 'created');
            }

            return $case;
        });
    }

    public function pathwayFor(User $user): string
    {
        return match ($user->deriveAgeBracketCache()) {
            'teens' => 'teen',
            'adults' => 'adult',
            default => throw new DomainException('Learner identity verification requires support for an under-13 or missing birthdate.'),
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
