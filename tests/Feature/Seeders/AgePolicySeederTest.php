<?php

namespace Tests\Feature\Seeders;

use App\Models\LearnerProfile;
use App\Support\LearnerAgePolicy;
use Tests\TestCase;

class AgePolicySeederTest extends TestCase
{
    public function test_learner_profile_factory_produces_only_active_policy_ages(): void
    {
        $profiles = LearnerProfile::factory()->count(20)->create();

        foreach ($profiles as $profile) {
            $this->assertContains(
                LearnerAgePolicy::classify($profile->birthdate),
                [LearnerAgePolicy::ELIGIBLE_TEENAGER, LearnerAgePolicy::ELIGIBLE_ADULT],
            );
        }
    }
}
