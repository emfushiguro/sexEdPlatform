<?php

namespace Database\Factories;

use App\Models\OnboardingGuide;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\OnboardingGuide>
 */
class OnboardingGuideFactory extends Factory
{
    public function definition(): array
    {
        return [
            'guide_key' => OnboardingGuide::GUIDE_KEY_DASHBOARD_NAVIGATION,
            'target_role' => OnboardingGuide::ROLE_LEARNER,
            'status' => OnboardingGuide::STATUS_DRAFT,
            'created_by' => User::factory(),
        ];
    }
}
