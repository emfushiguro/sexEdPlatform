<?php

namespace Tests\Unit\Services\Gamification;

use App\Services\Gamification\GamificationPolicyDefaults;
use App\Services\Gamification\GamificationPolicyNormalizer;
use App\Services\Gamification\GamificationPolicyValidator;
use Tests\TestCase;

class GamificationPolicyValidatorTest extends TestCase
{
    private GamificationPolicyNormalizer $normalizer;

    private GamificationPolicyValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->normalizer = new GamificationPolicyNormalizer();
        $this->validator = new GamificationPolicyValidator();
    }

    public function test_required_sections_exist_after_merge(): void
    {
        $normalized = $this->normalizer->normalize([
            'points_config' => ['topic_complete_points' => 12],
        ]);

        $this->assertArrayHasKey('points_config', $normalized);
        $this->assertArrayHasKey('streak_config', $normalized);
        $this->assertArrayHasKey('leveling_config', $normalized);
        $this->assertArrayHasKey('shield_config', $normalized);
        $this->assertArrayHasKey('safeguards_config', $normalized);
    }

    public function test_negative_values_are_rejected(): void
    {
        $payload = GamificationPolicyDefaults::baseline();
        $payload['points_config']['topic_complete_points'] = -1;

        $errors = $this->validator->validate($payload);

        $this->assertNotEmpty($errors);
        $this->assertArrayHasKey('points_config.topic_complete_points', $errors);
    }

    public function test_milestone_days_must_be_unique(): void
    {
        $payload = GamificationPolicyDefaults::baseline();
        $payload['streak_config']['milestones'] = [
            ['days' => 7, 'bonus_points' => 50, 'priority' => 20],
            ['days' => 7, 'bonus_points' => 70, 'priority' => 10],
        ];

        $errors = $this->validator->validate($payload);

        $this->assertArrayHasKey('streak_config.milestones', $errors);
    }

    public function test_level_thresholds_must_be_monotonic(): void
    {
        $payload = GamificationPolicyDefaults::baseline();
        $payload['leveling_config']['explicit_thresholds'] = [
            '1' => 0,
            '2' => 200,
            '3' => 150,
        ];

        $errors = $this->validator->validate($payload);

        $this->assertArrayHasKey('leveling_config.explicit_thresholds', $errors);
    }

    public function test_full_refill_cost_cannot_be_lower_than_single_refill_cost(): void
    {
        $payload = GamificationPolicyDefaults::baseline();
        $payload['shield_config']['refill_single_cost_points'] = 80;
        $payload['shield_config']['refill_full_cost_points'] = 60;

        $errors = $this->validator->validate($payload);

        $this->assertArrayHasKey('shield_config.refill_full_cost_points', $errors);
    }
}
