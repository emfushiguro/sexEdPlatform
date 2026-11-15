<?php

namespace App\Services\Learning;

use App\Models\User;
use App\Services\GamificationService;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class CompletionRewardService
{
    public function __construct(private readonly GamificationService $gamification) {}

    public function award(User $user, string $reason, int $subjectId): int
    {
        if (! in_array($reason, ['topic_complete', 'lesson_complete'], true)) {
            throw new InvalidArgumentException('Unsupported completion reward.');
        }

        return DB::transaction(function () use ($user, $reason, $subjectId): int {
            User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();
            $inserted = DB::table('learning_completion_rewards')->insertOrIgnore(['user_id' => $user->id, 'reason' => $reason, 'subject_id' => $subjectId, 'created_at' => now(), 'updated_at' => now()]);

            return $inserted ? $this->gamification->awardConfiguredPoints($user, $reason) : 0;
        });
    }
}
