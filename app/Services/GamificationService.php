<?php

namespace App\Services;

use App\Models\User;
use App\Services\Gamification\GamificationPolicyResolver;

class GamificationService
{
    private array $resolvedPolicy;

    public function __construct(?GamificationPolicyResolver $policyResolver = null)
    {
        $resolver = $policyResolver ?? app(GamificationPolicyResolver::class);
        $this->resolvedPolicy = $resolver->resolve();
    }

    public function awardPoints(User $user, string $reason, int $points): void
    {
        $gamification = $user->gamification;
        if (!$gamification) {
            return;
        }

        $gamification->increment('score', $points);
        $gamification->increment('total_points', $points);

        $freshGamification = $gamification->fresh();
        $newScore = (int) $freshGamification->score;
        $currentLevel = (int) $freshGamification->level;
        $newLevel = $this->resolveLevelForScore($newScore);

        if ($newLevel > $currentLevel) {
            $levelsGained = $newLevel - $currentLevel;
            $levelUpBonus = (int) data_get($this->resolvedPolicy, 'points_config.level_up_bonus_points', 0);

            if ($levelUpBonus > 0) {
                $bonusPoints = $levelsGained * $levelUpBonus;
                $freshGamification->increment('score', $bonusPoints);
                $freshGamification->increment('total_points', $bonusPoints);
                $newScore = (int) $freshGamification->fresh()->score;
                $newLevel = $this->resolveLevelForScore($newScore);
            }

            $freshGamification->update(['level' => $newLevel]);
        }
    }

    public function spendPoints(User $user, int $points): bool
    {
        $gamification = $user->gamification;
        if (!$gamification || $gamification->score < $points) {
            return false;
        }

        $gamification->decrement('score', $points);
        // total_points is lifetime total — never decremented

        return true;
    }

    public function updateStreak(User $user): void
    {
        $gamification = $user->gamification;
        if (!$gamification) {
            return;
        }

        $lastAct = $gamification->last_act_at;

        if ($lastAct === null || $lastAct->isYesterday()) {
            // Normal increment
            $gamification->increment('streak_count');
        } elseif ($lastAct->isToday()) {
            // Already counted today — no change needed
            return;
        } else {
            // Missed one or more days
            if ($gamification->streak_savers > 0) {
                // Consume a streak saver and preserve streak
                $gamification->decrement('streak_savers');
                $remainingSavers = $gamification->fresh()->streak_savers;
                session()->flash('streak_saved', [
                    'streak'      => $gamification->streak_count,
                    'savers_left' => $remainingSavers,
                ]);
                // Update last_act_at without overwriting other fields
                $gamification->update(['last_act_at' => now()]);
                return;
            } else {
                // Reset streak
                $gamification->update(['streak_count' => 1, 'last_act_at' => now()]);
                return;
            }
        }

        // Refresh to get the incremented streak_count from DB
        $gamification->refresh();

        // Update last_act_at
        $gamification->last_act_at = now();

        // Update longest streak if exceeded
        if ($gamification->streak_count > ($gamification->longest_streak ?? 0)) {
            $gamification->longest_streak = $gamification->streak_count;
        }

        $gamification->save();

        // Check for milestone bonus
        $bonus = $this->checkStreakMilestone($user);
        if ($bonus !== null) {
            $this->awardPoints($user, 'streak_milestone', $bonus);
            session()->flash('streak_milestone', [
                'days'  => $gamification->streak_count,
                'bonus' => $bonus,
            ]);
        }
    }

    public function checkStreakMilestone(User $user): ?int
    {
        $gamification = $user->gamification;
        if (!$gamification || $gamification->streak_count === 0) {
            return null;
        }

        $count = (int) $gamification->streak_count;
        $milestones = data_get($this->resolvedPolicy, 'streak_config.milestones', []);

        if (!is_array($milestones) || empty($milestones)) {
            return null;
        }

        foreach ($milestones as $milestone) {
            $days = (int) data_get($milestone, 'days', 0);
            $bonus = (int) data_get($milestone, 'bonus_points', 0);

            if ($days <= 0) {
                continue;
            }

            if ($count % $days === 0) {
                return $bonus;
            }
        }

        return null;
    }

    private function resolveLevelForScore(int $score): int
    {
        $levelingConfig = data_get($this->resolvedPolicy, 'leveling_config', []);
        $resolutionMode = (string) data_get($levelingConfig, 'threshold_resolution', 'explicit_then_formula');
        $thresholds = data_get($levelingConfig, 'explicit_thresholds', []);

        if (!is_array($thresholds)) {
            $thresholds = [];
        }

        $normalizedThresholds = [];
        foreach ($thresholds as $level => $xp) {
            $normalizedThresholds[(int) $level] = (int) $xp;
        }
        ksort($normalizedThresholds);

        $resolvedLevel = 1;
        $highestThresholdLevel = 1;
        $highestThresholdXp = 0;

        foreach ($normalizedThresholds as $level => $requiredXp) {
            if ($score >= $requiredXp) {
                $resolvedLevel = max($resolvedLevel, $level);
                $highestThresholdLevel = $level;
                $highestThresholdXp = $requiredXp;
            }
        }

        $baseXpPerLevel = max(1, (int) data_get($levelingConfig, 'formula.base_xp_per_level', 100));
        $growthFactor = max(1, (int) data_get($levelingConfig, 'formula.growth_factor', 1));
        $effectiveStep = max(1, $baseXpPerLevel * $growthFactor);

        if ($resolutionMode === 'explicit_then_formula' && $score > $highestThresholdXp) {
            $additionalLevels = intdiv($score - $highestThresholdXp, $effectiveStep);
            $resolvedLevel = max($resolvedLevel, $highestThresholdLevel + $additionalLevels);
        } elseif ($resolutionMode !== 'explicit_then_formula' && empty($normalizedThresholds)) {
            $resolvedLevel = max(1, intdiv($score, $effectiveStep) + 1);
        }

        return $resolvedLevel;
    }

}
