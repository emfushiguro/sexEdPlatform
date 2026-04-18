<?php

namespace App\Http\Controllers\Learner;

use App\Http\Controllers\Controller;
use App\Models\UserDailyShield;
use App\Services\Gamification\GamificationPolicyResolver;
use Illuminate\Support\Facades\Auth;

class GamificationController extends Controller
{
    public function __construct(
        private readonly GamificationPolicyResolver $gamificationPolicyResolver,
    ) {
    }

    public function rules()
    {
        $user = Auth::user();
        $gamification = $user->gamification;
        $shieldsRemaining = UserDailyShield::getShields($user);
        $policy = $this->gamificationPolicyResolver->resolve();

        $pointsConfig = data_get($policy, 'points_config', []);
        $streakConfig = data_get($policy, 'streak_config', []);
        $shieldConfig = data_get($policy, 'shield_config', []);

        $rules = [
            'points' => [
                'topic_complete_points' => max(0, (int) data_get($pointsConfig, 'topic_complete_points', 10)),
                'lesson_complete_points' => max(0, (int) data_get($pointsConfig, 'lesson_complete_points', 15)),
                'module_complete_points' => max(0, (int) data_get($pointsConfig, 'module_complete_points', 100)),
                'certificate_earned_points' => max(0, (int) data_get($pointsConfig, 'certificate_earned_points', 50)),
                'quiz_perfect_score_points' => max(0, (int) data_get($pointsConfig, 'quiz_bands.perfect_score_points', 30)),
                'quiz_pass_score_points' => max(0, (int) data_get($pointsConfig, 'quiz_bands.pass_score_points', 25)),
                'quiz_fail_attempt_points' => max(0, (int) data_get($pointsConfig, 'quiz_bands.fail_attempt_points', 5)),
            ],
            'streak' => [
                'max_savers_held' => max(0, (int) data_get($streakConfig, 'max_savers_held', 3)),
                'saver_purchase_cost_points' => max(0, (int) data_get($streakConfig, 'saver_purchase_cost_points', 75)),
                'milestones' => collect(data_get($streakConfig, 'milestones', []))
                    ->map(fn (array $milestone): array => [
                        'days' => max(0, (int) data_get($milestone, 'days', 0)),
                        'bonus_points' => max(0, (int) data_get($milestone, 'bonus_points', 0)),
                    ])
                    ->filter(fn (array $milestone): bool => $milestone['days'] > 0)
                    ->values(),
            ],
            'shield' => [
                'daily_shields_default' => max(0, (int) data_get($shieldConfig, 'daily_shields_default', 3)),
                'max_shields_per_day_cap' => max(0, (int) data_get($shieldConfig, 'max_shields_per_day_cap', 3)),
                'refill_single_cost_points' => max(0, (int) data_get($shieldConfig, 'refill_single_cost_points', 50)),
                'refill_full_cost_points' => max(0, (int) data_get($shieldConfig, 'refill_full_cost_points', 100)),
                'refill_full_target_shields' => max(0, (int) data_get($shieldConfig, 'refill_full_target_shields', 3)),
            ],
        ];

        return view('learner.gamification.rules', compact('gamification', 'shieldsRemaining', 'rules'));
    }
}
