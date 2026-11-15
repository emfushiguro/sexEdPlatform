<?php

namespace Tests\Feature\AgePolicy;

use App\Models\User;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AgePolicySchemaTest extends TestCase
{
    public function test_age_policy_columns_exist(): void
    {
        $this->assertTrue(Schema::hasColumns('users', [
            'age_eligibility_cached',
            'minor_account_activation_required',
            'minor_account_activated_at',
            'minor_privacy_notice_version',
            'minor_privacy_accepted_at',
        ]));
    }

    public function test_refresh_writes_eligible_teenager_without_defaulting_invalid_dob_to_adult(): void
    {
        $teenager = User::factory()->create(['birthdate' => now()->subYears(10)->toDateString()]);
        $teenager->assignRole('learner');
        $teenager->refreshClassificationCache();

        $this->assertSame('eligible_teenager', $teenager->fresh()->age_eligibility_cached);
        $this->assertSame('teens', $teenager->fresh()->age_bracket_cached);
        $this->assertSame(User::ACCOUNT_TYPE_LEARNER_TEEN, $teenager->fresh()->account_type);

        $unknown = User::factory()->create([
            'birthdate' => null,
            'account_type' => User::ACCOUNT_TYPE_LEARNER_CHILD,
        ]);
        $unknown->assignRole('learner');
        $unknown->refreshClassificationCache();

        $this->assertSame('unclassified', $unknown->fresh()->age_eligibility_cached);
        $this->assertNull($unknown->fresh()->age_bracket_cached);
        $this->assertSame(User::ACCOUNT_TYPE_LEARNER_CHILD, $unknown->fresh()->account_type);
    }
}
