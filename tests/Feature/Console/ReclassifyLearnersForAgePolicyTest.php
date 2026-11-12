<?php

namespace Tests\Feature\Console;

use App\Models\LearnerProfile;
use App\Models\User;
use Illuminate\Console\Scheduling\Schedule;
use Tests\TestCase;

class ReclassifyLearnersForAgePolicyTest extends TestCase
{
    public function test_dry_run_reports_without_writing(): void
    {
        $learner = $this->createLearner('dry-run', '2014-01-01', [
            'account_type' => User::ACCOUNT_TYPE_LEARNER_CHILD,
            'age_bracket_cached' => 'kids',
            'age_eligibility_cached' => null,
        ]);
        $before = $learner->getRawOriginal([
            'account_type',
            'age_bracket_cached',
            'age_eligibility_cached',
            'updated_at',
        ]);

        $this->artisan('learners:reclassify-age-policy', ['--chunk' => 1])
            ->expectsOutputToContain('DRY RUN')
            ->expectsOutputToContain('eligible_teenager')
            ->assertSuccessful();

        $this->assertSame($before, $learner->fresh()->getRawOriginal(array_keys($before)));
    }

    public function test_apply_is_batched_and_idempotent(): void
    {
        $learner = $this->createLearner('apply', '2014-01-01', [
            'account_type' => User::ACCOUNT_TYPE_LEARNER_CHILD,
            'age_bracket_cached' => 'kids',
            'age_eligibility_cached' => null,
        ]);

        $this->artisan('learners:reclassify-age-policy', ['--apply' => true, '--chunk' => 1])
            ->assertSuccessful();
        $first = $learner->fresh()->only([
            'account_type',
            'age_bracket_cached',
            'age_eligibility_cached',
        ]);

        $this->artisan('learners:reclassify-age-policy', ['--apply' => true, '--chunk' => 1])
            ->assertSuccessful();

        $this->assertSame($first, $learner->fresh()->only(array_keys($first)));
    }

    public function test_summary_reports_all_policy_results_soft_deleted_learners_and_profile_mismatch(): void
    {
        $this->createLearner('underage', '2017-11-13');
        $this->createLearner('teenager', '2016-11-12');
        $this->createLearner('adult', '2008-11-12');
        $this->createLearner('overage', '1925-11-12');
        $this->createLearner('unclassified', null);

        $mismatch = $this->createLearner('mismatch', '2008-11-12');
        $mismatch->learnerProfile->update(['birthdate' => '2014-01-01']);

        $deleted = $this->createLearner('deleted', '2009-11-12');
        $deleted->delete();

        $this->artisan('learners:reclassify-age-policy', ['--chunk' => 2])
            ->expectsOutputToContain('eligible_teenager')
            ->expectsOutputToContain('eligible_adult')
            ->expectsOutputToContain('ineligible_underage')
            ->expectsOutputToContain('ineligible_overage')
            ->expectsOutputToContain('unclassified')
            ->expectsOutputToContain('profile_dob_mismatches: 1')
            ->assertSuccessful();
    }

    public function test_chunk_must_be_between_one_and_one_thousand(): void
    {
        $this->artisan('learners:reclassify-age-policy', ['--chunk' => 0])
            ->assertFailed();

        $this->artisan('learners:reclassify-age-policy', ['--chunk' => 1001])
            ->assertFailed();
    }

    public function test_reclassification_schedule_is_disabled_by_default_and_opt_in(): void
    {
        $defaultCommands = collect(app(Schedule::class)->events())
            ->map(fn ($event): string => (string) ($event->command ?? ''));

        $this->assertFalse($defaultCommands->contains(fn (string $command): bool => str_contains(
            $command,
            'learners:reclassify-age-policy',
        )));

        config(['learner_age_policy.schedule_reclassification' => true]);
        app()->forgetInstance(Schedule::class);

        $optInCommands = collect(app(Schedule::class)->events())
            ->map(fn ($event): string => (string) ($event->command ?? ''));

        $this->assertSame(1, $optInCommands->filter(fn (string $command): bool => str_contains(
            $command,
            'learners:reclassify-age-policy --apply',
        ))->count());
    }

    private function createLearner(string $username, ?string $birthdate, array $overrides = []): User
    {
        $user = User::factory()->create(array_merge([
            'name' => $username,
            'role' => 'learner',
            'status' => User::STATUS_ACTIVE,
            'birthdate' => $birthdate,
        ], $overrides));
        $user->assignRole('learner');

        LearnerProfile::unguarded(fn (): LearnerProfile => $user->learnerProfile()->create([
            'username' => $username.'_'.$user->id,
            'birthdate' => $birthdate,
            'age_range' => 'adult_18_plus',
            'gender' => 'prefer_not_to_say',
            'province_code' => '402100000',
            'city_code' => '402101000',
            'barangay_code' => '402101001',
            'barangay' => 'Sample Barangay',
            'is_parent_account' => false,
            'requires_parental_consent' => false,
        ]));

        return $user->fresh(['learnerProfile']);
    }
}
