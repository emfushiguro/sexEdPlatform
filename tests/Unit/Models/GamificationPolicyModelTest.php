<?php

namespace Tests\Unit\Models;

use App\Models\GamificationPolicy;
use App\Models\GamificationPolicyVersion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class GamificationPolicyModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_policy_payload_is_cast_to_array(): void
    {
        $policy = GamificationPolicy::query()->create([
            'is_active' => true,
            'policy_payload' => [
                'points_config' => ['topic_complete_points' => 10],
                'streak_config' => [],
                'leveling_config' => [],
                'shield_config' => [],
                'safeguards_config' => [],
            ],
            'version_label' => 'test',
        ]);

        $policy->refresh();

        $this->assertIsArray($policy->policy_payload);
        $this->assertSame(10, $policy->policy_payload['points_config']['topic_complete_points']);
    }

    public function test_policy_has_many_versions_relationship(): void
    {
        $policy = GamificationPolicy::query()->create([
            'is_active' => true,
            'policy_payload' => [
                'points_config' => [],
                'streak_config' => [],
                'leveling_config' => [],
                'shield_config' => [],
                'safeguards_config' => [],
            ],
            'version_label' => 'v1',
        ]);

        GamificationPolicyVersion::query()->create([
            'policy_id' => $policy->id,
            'policy_payload' => [
                'points_config' => [],
                'streak_config' => [],
                'leveling_config' => [],
                'shield_config' => [],
                'safeguards_config' => [],
            ],
            'version_label' => 'v1',
        ]);

        $this->assertSame(1, $policy->versions()->count());
    }

    public function test_active_scope_returns_only_active_policies(): void
    {
        DB::table('gamification_policies')->insert([
            [
                'is_active' => true,
                'policy_payload' => json_encode([
                    'points_config' => [],
                    'streak_config' => [],
                    'leveling_config' => [],
                    'shield_config' => [],
                    'safeguards_config' => [],
                ], JSON_THROW_ON_ERROR),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'is_active' => false,
                'policy_payload' => json_encode([
                    'points_config' => [],
                    'streak_config' => [],
                    'leveling_config' => [],
                    'shield_config' => [],
                    'safeguards_config' => [],
                ], JSON_THROW_ON_ERROR),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $this->assertSame(1, GamificationPolicy::query()->active()->count());
    }
}
