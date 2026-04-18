<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GamificationPolicySeeder extends Seeder
{
    public function run(): void
    {
        $existingActive = DB::table('gamification_policies')
            ->where('is_active', true)
            ->exists();

        if ($existingActive) {
            return;
        }

        $baselinePayload = [
            'points_config' => [
                'topic_complete_points' => 10,
                'lesson_complete_points' => 15,
                'module_complete_points' => 100,
                'certificate_earned_points' => 50,
                'quiz_bands' => [
                    'perfect_score_points' => 30,
                    'pass_score_points' => 25,
                    'fail_attempt_points' => 5,
                ],
                'level_up_bonus_points' => 0,
            ],
            'streak_config' => [
                'qualifying_event' => 'topic_completion',
                'auto_consume_saver' => true,
                'max_savers_held' => 3,
                'milestones' => [
                    ['days' => 7, 'bonus_points' => 50, 'priority' => 20],
                    ['days' => 30, 'bonus_points' => 200, 'priority' => 10],
                ],
            ],
            'leveling_config' => [
                'formula' => [
                    'base_xp_per_level' => 100,
                    'growth_mode' => 'linear',
                    'growth_factor' => 1,
                ],
                'explicit_thresholds' => [
                    '1' => 0,
                    '2' => 100,
                    '3' => 200,
                    '4' => 300,
                    '5' => 400,
                ],
                'threshold_resolution' => 'explicit_then_formula',
            ],
            'shield_config' => [
                'daily_shields_default' => 3,
                'max_shields_per_day_cap' => 3,
                'refill_single_cost_points' => 50,
                'refill_full_cost_points' => 100,
                'refill_full_target_shields' => 3,
            ],
            'safeguards_config' => [
                'allow_negative_rewards' => false,
                'allow_negative_costs' => false,
                'enforce_monotonic_thresholds' => true,
                'enforce_unique_milestone_days' => true,
            ],
        ];

        $now = now();

        $policyId = DB::table('gamification_policies')->insertGetId([
            'is_active' => true,
            'policy_payload' => json_encode($baselinePayload, JSON_THROW_ON_ERROR),
            'version_label' => 'baseline-v1',
            'change_summary' => 'Baseline policy seeded from pre-dynamic hardcoded mechanics.',
            'updated_by' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('gamification_policy_versions')->insert([
            'policy_id' => $policyId,
            'policy_payload' => json_encode($baselinePayload, JSON_THROW_ON_ERROR),
            'version_label' => 'baseline-v1',
            'change_summary' => 'Initial baseline snapshot.',
            'changed_by' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
}
