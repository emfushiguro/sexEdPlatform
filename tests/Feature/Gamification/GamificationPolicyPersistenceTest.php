<?php

namespace Tests\Feature\Gamification;

use Database\Seeders\GamificationPolicySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class GamificationPolicyPersistenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_gamification_policy_tables_exist(): void
    {
        $this->assertTrue(Schema::hasTable('gamification_policies'));
        $this->assertTrue(Schema::hasTable('gamification_policy_versions'));
    }

    public function test_baseline_policy_is_seeded_with_required_sections(): void
    {
        $this->seed(GamificationPolicySeeder::class);

        $policy = DB::table('gamification_policies')
            ->where('is_active', true)
            ->first();

        $this->assertNotNull($policy);

        $payload = json_decode((string) $policy->policy_payload, true);

        $this->assertIsArray($payload);
        $this->assertArrayHasKey('points_config', $payload);
        $this->assertArrayHasKey('streak_config', $payload);
        $this->assertArrayHasKey('leveling_config', $payload);
        $this->assertArrayHasKey('shield_config', $payload);
        $this->assertArrayHasKey('safeguards_config', $payload);
    }
}
