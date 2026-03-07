<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Collection;

class ParentChildService
{
    public function getProgress(User $child): Collection
    {
        return collect();
    }

    public function getQuizResults(User $child): Collection
    {
        return collect();
    }

    public function getAchievements(User $child): array
    {
        return ['gamification' => null, 'rewardLogs' => collect()];
    }

    public function getPendingEnrollments(User $child): Collection
    {
        return collect();
    }
}
