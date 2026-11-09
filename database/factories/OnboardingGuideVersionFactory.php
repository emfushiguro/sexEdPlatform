<?php

namespace Database\Factories;

use App\Models\OnboardingGuide;
use App\Models\OnboardingGuideVersion;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\OnboardingGuideVersion>
 */
class OnboardingGuideVersionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'onboarding_guide_id' => OnboardingGuide::factory(),
            'version_number' => 1,
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'video_disk' => 'local',
            'video_path' => 'onboarding-guides/testing/guide.mp4',
            'video_original_name' => 'guide.mp4',
            'video_mime_type' => 'video/mp4',
            'video_size' => 1024,
            'requires_recompletion' => true,
            'published_at' => null,
            'created_by' => User::factory(),
        ];
    }
}
