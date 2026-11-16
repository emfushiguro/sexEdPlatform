<?php

namespace App\Console\Commands;

use App\Models\ParentChildAccount;
use App\Models\User;
use App\Support\LearnerAgePolicy;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class ReclassifyLearnersForAgePolicy extends Command
{
    protected $signature = 'learners:reclassify-age-policy
        {--apply : Persist classification caches}
        {--chunk=100 : Number of learner rows per batch}';

    protected $description = 'Report or refresh learner age-policy classification caches';

    public function handle(): int
    {
        $chunk = (string) $this->option('chunk');
        if (! preg_match('/^[1-9][0-9]{0,3}$/', $chunk) || (int) $chunk > 1000) {
            $this->error('The --chunk option must be an integer from 1 through 1000.');

            return self::FAILURE;
        }

        $apply = (bool) $this->option('apply');
        $counts = [
            LearnerAgePolicy::ELIGIBLE_TEENAGER => 0,
            LearnerAgePolicy::ELIGIBLE_ADULT => 0,
            LearnerAgePolicy::INELIGIBLE_UNDERAGE => 0,
            LearnerAgePolicy::INELIGIBLE_OVERAGE => 0,
            LearnerAgePolicy::UNCLASSIFIED => 0,
        ];
        $cacheMismatches = 0;
        $guardianGaps = 0;
        $profileDobMismatches = 0;
        $failures = 0;

        $this->line($apply
            ? 'APPLY: learner age-policy caches will be refreshed; no account or relationship state will change.'
            : 'DRY RUN: no database writes will be made.');

        $query = User::withTrashed()
            ->with('learnerProfile')
            ->where(function ($roleQuery): void {
                $roleQuery
                    ->where('role', 'learner')
                    ->orWhereHas('roles', fn ($roles) => $roles->where('name', 'learner'));
            })
            ->orderBy('id');

        $query->chunkById((int) $chunk, function ($learners) use (
            &$counts,
            &$cacheMismatches,
            &$guardianGaps,
            &$profileDobMismatches,
            &$failures,
            $apply,
        ): void {
            foreach ($learners as $learner) {
                try {
                    $classification = LearnerAgePolicy::classify($learner->birthdate);
                    $counts[$classification]++;

                    if ($this->hasCacheMismatch($learner, $classification)) {
                        $cacheMismatches++;
                    }

                    if ($classification === LearnerAgePolicy::ELIGIBLE_TEENAGER
                        && ! ParentChildAccount::query()
                            ->accessEligible()
                            ->where('child_user_id', $learner->getKey())
                            ->exists()) {
                        $guardianGaps++;
                    }

                    if ($this->hasProfileDobMismatch($learner)) {
                        $profileDobMismatches++;
                    }

                    if ($apply) {
                        DB::transaction(function () use ($learner): void {
                            $locked = User::withTrashed()
                                ->whereKey($learner->getKey())
                                ->lockForUpdate()
                                ->firstOrFail();

                            $locked->refreshClassificationCache();
                        });
                    }
                } catch (Throwable $exception) {
                    $failures++;
                    $this->error(sprintf(
                        'user %s failed: %s',
                        (string) $learner->getKey(),
                        $exception::class,
                    ));
                }
            }
        });

        $total = array_sum($counts);
        $this->newLine();
        $this->line('Age-policy reclassification summary:');
        foreach ($counts as $classification => $count) {
            $this->line(sprintf('%s: %d', $classification, $count));
        }
        $this->line(sprintf('total: %d', $total));
        $this->line(sprintf('cache_mismatches: %d', $cacheMismatches));
        $this->line(sprintf('guardian_gaps: %d', $guardianGaps));
        $this->line(sprintf('profile_dob_mismatches: %d', $profileDobMismatches));
        $this->line(sprintf('failures: %d', $failures));

        return $failures === 0 ? self::SUCCESS : self::FAILURE;
    }

    private function hasCacheMismatch(User $learner, string $classification): bool
    {
        return $learner->age_bracket_cached === 'kids'
            || $learner->age_bracket_cached !== LearnerAgePolicy::category($learner->birthdate)
            || $learner->age_eligibility_cached !== $classification;
    }

    private function hasProfileDobMismatch(User $learner): bool
    {
        if (! $learner->relationLoaded('learnerProfile') || $learner->learnerProfile === null) {
            return false;
        }

        $userBirthdate = $learner->birthdate?->toDateString();
        $profileBirthdate = $learner->learnerProfile->birthdate?->toDateString();

        return $userBirthdate !== $profileBirthdate;
    }
}
