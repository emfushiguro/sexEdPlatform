<?php

namespace Database\Factories;

use App\Models\OnboardingGuideVersion;
use App\Models\User;
use App\Models\UserOnboardingGuideState;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\UserOnboardingGuideState>
 */
class UserOnboardingGuideStateFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'onboarding_guide_version_id' => OnboardingGuideVersion::factory(),
            'dismissed_at' => null,
            'completed_at' => null,
        ];
    }
}
