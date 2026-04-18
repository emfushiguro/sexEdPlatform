<?php

namespace App\Services\Gamification;

class GamificationPolicyNormalizer
{
    public function normalize(array $payload): array
    {
        $merged = array_replace_recursive(GamificationPolicyDefaults::baseline(), $payload);

        if (isset($payload['streak_config']) && is_array($payload['streak_config']) && array_key_exists('milestones', $payload['streak_config']) && is_array($payload['streak_config']['milestones'])) {
            $merged['streak_config']['milestones'] = $payload['streak_config']['milestones'];
        }

        if (isset($payload['leveling_config']) && is_array($payload['leveling_config']) && array_key_exists('explicit_thresholds', $payload['leveling_config']) && is_array($payload['leveling_config']['explicit_thresholds'])) {
            $merged['leveling_config']['explicit_thresholds'] = $payload['leveling_config']['explicit_thresholds'];
        }

        $merged['points_config']['topic_complete_points'] = $this->toInt($merged['points_config']['topic_complete_points']);
        $merged['points_config']['lesson_complete_points'] = $this->toInt($merged['points_config']['lesson_complete_points']);
        $merged['points_config']['module_complete_points'] = $this->toInt($merged['points_config']['module_complete_points']);
        $merged['points_config']['certificate_earned_points'] = $this->toInt($merged['points_config']['certificate_earned_points']);
        $merged['points_config']['level_up_bonus_points'] = $this->toInt($merged['points_config']['level_up_bonus_points']);

        $merged['points_config']['quiz_bands']['perfect_score_points'] = $this->toInt($merged['points_config']['quiz_bands']['perfect_score_points']);
        $merged['points_config']['quiz_bands']['pass_score_points'] = $this->toInt($merged['points_config']['quiz_bands']['pass_score_points']);
        $merged['points_config']['quiz_bands']['fail_attempt_points'] = $this->toInt($merged['points_config']['quiz_bands']['fail_attempt_points']);

        $merged['streak_config']['auto_consume_saver'] = (bool) $merged['streak_config']['auto_consume_saver'];
        $merged['streak_config']['max_savers_held'] = $this->toInt($merged['streak_config']['max_savers_held']);
        $merged['streak_config']['saver_purchase_cost_points'] = $this->toInt($merged['streak_config']['saver_purchase_cost_points']);
        $merged['streak_config']['milestones'] = $this->normalizeMilestones($merged['streak_config']['milestones'] ?? []);

        $merged['leveling_config']['formula']['base_xp_per_level'] = $this->toInt($merged['leveling_config']['formula']['base_xp_per_level']);
        $merged['leveling_config']['formula']['growth_factor'] = $this->toInt($merged['leveling_config']['formula']['growth_factor']);
        $merged['leveling_config']['explicit_thresholds'] = $this->normalizeThresholds($merged['leveling_config']['explicit_thresholds'] ?? []);

        $merged['shield_config']['daily_shields_default'] = $this->toInt($merged['shield_config']['daily_shields_default']);
        $merged['shield_config']['max_shields_per_day_cap'] = $this->toInt($merged['shield_config']['max_shields_per_day_cap']);
        $merged['shield_config']['refill_single_cost_points'] = $this->toInt($merged['shield_config']['refill_single_cost_points']);
        $merged['shield_config']['refill_full_cost_points'] = $this->toInt($merged['shield_config']['refill_full_cost_points']);
        $merged['shield_config']['refill_full_target_shields'] = $this->toInt($merged['shield_config']['refill_full_target_shields']);

        return $merged;
    }

    private function toInt(mixed $value): int
    {
        return (int) $value;
    }

    private function normalizeMilestones(array $milestones): array
    {
        $normalized = [];

        foreach ($milestones as $milestone) {
            $normalized[] = [
                'days' => $this->toInt($milestone['days'] ?? 0),
                'bonus_points' => $this->toInt($milestone['bonus_points'] ?? 0),
                'priority' => $this->toInt($milestone['priority'] ?? 0),
            ];
        }

        usort($normalized, static function (array $a, array $b): int {
            return $a['priority'] <=> $b['priority'];
        });

        return $normalized;
    }

    private function normalizeThresholds(array $thresholds): array
    {
        $normalized = [];

        foreach ($thresholds as $level => $xp) {
            $normalized[(string) ((int) $level)] = $this->toInt($xp);
        }

        uksort($normalized, static function (string $a, string $b): int {
            return ((int) $a) <=> ((int) $b);
        });

        return $normalized;
    }
}
