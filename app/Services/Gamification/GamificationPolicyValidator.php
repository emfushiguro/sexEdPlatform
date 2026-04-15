<?php

namespace App\Services\Gamification;

class GamificationPolicyValidator
{
    public function validate(array $payload): array
    {
        $errors = [];

        $nonNegativePaths = [
            'points_config.topic_complete_points',
            'points_config.lesson_complete_points',
            'points_config.module_complete_points',
            'points_config.certificate_earned_points',
            'points_config.level_up_bonus_points',
            'points_config.quiz_bands.perfect_score_points',
            'points_config.quiz_bands.pass_score_points',
            'points_config.quiz_bands.fail_attempt_points',
            'streak_config.max_savers_held',
            'shield_config.daily_shields_default',
            'shield_config.max_shields_per_day_cap',
            'shield_config.refill_single_cost_points',
            'shield_config.refill_full_cost_points',
            'shield_config.refill_full_target_shields',
        ];

        foreach ($nonNegativePaths as $path) {
            $value = $this->dataGet($payload, $path);

            if (!is_numeric($value) || (int) $value < 0) {
                $errors[$path] = 'Value must be a non-negative number.';
            }
        }

        $milestones = $payload['streak_config']['milestones'] ?? [];
        $seenDays = [];

        foreach ($milestones as $milestone) {
            $days = (int) ($milestone['days'] ?? 0);
            $bonus = (int) ($milestone['bonus_points'] ?? 0);

            if ($days <= 0) {
                $errors['streak_config.milestones'] = 'Milestone days must be greater than zero.';
                break;
            }

            if (in_array($days, $seenDays, true)) {
                $errors['streak_config.milestones'] = 'Milestone days must be unique.';
                break;
            }

            if ($bonus < 0) {
                $errors['streak_config.milestones'] = 'Milestone bonus points must be non-negative.';
                break;
            }

            $seenDays[] = $days;
        }

        $thresholds = $payload['leveling_config']['explicit_thresholds'] ?? [];
        if (is_array($thresholds) && !empty($thresholds)) {
            uksort($thresholds, static fn (string $a, string $b): int => ((int) $a) <=> ((int) $b));

            $previousXp = null;
            foreach ($thresholds as $level => $xp) {
                if (!is_numeric($xp)) {
                    $errors['leveling_config.explicit_thresholds'] = 'Threshold values must be numeric.';
                    break;
                }

                $currentXp = (int) $xp;
                if ($previousXp !== null && $currentXp <= $previousXp) {
                    $errors['leveling_config.explicit_thresholds'] = 'Threshold values must be strictly increasing by level.';
                    break;
                }

                $previousXp = $currentXp;
            }
        }

        $singleRefillCost = (int) ($payload['shield_config']['refill_single_cost_points'] ?? 0);
        $fullRefillCost = (int) ($payload['shield_config']['refill_full_cost_points'] ?? 0);

        if ($fullRefillCost < $singleRefillCost) {
            $errors['shield_config.refill_full_cost_points'] = 'Full refill cost cannot be lower than single refill cost.';
        }

        $fullTarget = (int) ($payload['shield_config']['refill_full_target_shields'] ?? 0);
        $shieldCap = (int) ($payload['shield_config']['max_shields_per_day_cap'] ?? 0);

        if ($fullTarget > $shieldCap) {
            $errors['shield_config.refill_full_target_shields'] = 'Full refill target cannot exceed shield cap.';
        }

        return $errors;
    }

    private function dataGet(array $payload, string $path): mixed
    {
        $segments = explode('.', $path);
        $current = $payload;

        foreach ($segments as $segment) {
            if (!is_array($current) || !array_key_exists($segment, $current)) {
                return null;
            }

            $current = $current[$segment];
        }

        return $current;
    }
}
