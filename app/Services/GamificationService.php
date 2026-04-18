<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserGamification;
use App\Services\Gamification\GamificationPolicyResolver;

class GamificationService
{
    private array $resolvedPolicy;

    public function __construct(?GamificationPolicyResolver $policyResolver = null)
    {
        $resolver = $policyResolver ?? app(GamificationPolicyResolver::class);
        $this->resolvedPolicy = $resolver->resolve();
    }

    public function awardPoints(User $user, string $reason, int $points): int
    {
        if ($points <= 0) {
            return 0;
        }

        $gamification = $this->resolveGamification($user);
        $beforeTotalPoints = (int) $gamification->total_points;

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

        $persisted = $freshGamification->fresh();

        return max(0, ((int) $persisted->total_points) - $beforeTotalPoints);
    }

    public function spendPoints(User $user, int $points): bool
    {
        if ($points <= 0) {
            return true;
        }

        $gamification = $this->resolveGamification($user);
        if ((int) $gamification->score < $points) {
            return false;
        }

        $gamification->decrement('score', $points);
        // total_points is lifetime total — never decremented

        return true;
    }

    public function resolvePointsForReason(string $reason, array $context = []): int
    {
        return match ($reason) {
            'topic_complete' => (int) data_get($this->resolvedPolicy, 'points_config.topic_complete_points', 0),
            'lesson_complete' => (int) data_get($this->resolvedPolicy, 'points_config.lesson_complete_points', 0),
            'module_completed' => (int) data_get($this->resolvedPolicy, 'points_config.module_complete_points', 0),
            'certificate_earned' => (int) data_get($this->resolvedPolicy, 'points_config.certificate_earned_points', 0),
            'quiz_passed' => ((int) ($context['score'] ?? 0) === 100)
                ? (int) data_get($this->resolvedPolicy, 'points_config.quiz_bands.perfect_score_points', 0)
                : (int) data_get($this->resolvedPolicy, 'points_config.quiz_bands.pass_score_points', 0),
            'quiz_attempted' => (int) data_get($this->resolvedPolicy, 'points_config.quiz_bands.fail_attempt_points', 0),
            default => 0,
        };
    }

    public function awardConfiguredPoints(User $user, string $reason, array $context = []): int
    {
        $points = $this->resolvePointsForReason($reason, $context);

        if ($points > 0) {
            return $this->awardPoints($user, $reason, $points);
        }

        return 0;
    }

    public function maxStreakSaversHeld(): int
    {
        return max(0, (int) data_get($this->resolvedPolicy, 'streak_config.max_savers_held', 0));
    }

    public function streakSaverCost(): int
    {
        return max(0, (int) data_get($this->resolvedPolicy, 'streak_config.saver_purchase_cost_points', 0));
    }

    public function shieldRefillCost(string $type): int
    {
        return $type === 'full'
            ? max(0, (int) data_get($this->resolvedPolicy, 'shield_config.refill_full_cost_points', 0))
            : max(0, (int) data_get($this->resolvedPolicy, 'shield_config.refill_single_cost_points', 0));
    }

    public function shieldFullRefillTarget(): int
    {
        return max(0, (int) data_get($this->resolvedPolicy, 'shield_config.refill_full_target_shields', 0));
    }

    public function dailyShieldDefault(): int
    {
        return max(0, (int) data_get($this->resolvedPolicy, 'shield_config.daily_shields_default', 0));
    }

    public function dailyShieldCap(): int
    {
        return max(0, (int) data_get($this->resolvedPolicy, 'shield_config.max_shields_per_day_cap', 0));
    }

    public function updateStreak(User $user): void
    {
        $gamification = $this->resolveGamification($user);

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
        $gamification = $this->resolveGamification($user);
        if ($gamification->streak_count === 0) {
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

    private function resolveGamification(User $user): UserGamification
    {
        $existing = $user->gamification()->first();
        if ($existing) {
            return $existing;
        }

        return $user->gamification()->create([
            'level' => 1,
            'score' => 0,
            'total_points' => 0,
            'streak_count' => 0,
            'longest_streak' => 0,
            'streak_savers' => 0,
            'last_act_at' => null,
        ]);
    }

}
