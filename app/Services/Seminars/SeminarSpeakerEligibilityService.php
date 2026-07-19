<?php

namespace App\Services\Seminars;

use App\Models\Seminar;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class SeminarSpeakerEligibilityService
{
    public function searchEligibleInstructors(Seminar $seminar, string $search = ''): Collection
    {
        return $this->eligibleQuery($seminar)
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($searchQuery) use ($search): void {
                    $searchQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhereHas('instructorProfile', fn ($profile) => $profile->where('professional_background', 'like', "%{$search}%"));
                });
            })
            ->orderBy('name')
            ->limit(20)
            ->get(['id', 'name', 'email']);
    }

    public function isEligible(Seminar $seminar, User $user): bool
    {
        return $this->eligibleQuery($seminar)->whereKey($user->id)->exists();
    }

    private function eligibleQuery(Seminar $seminar): Builder
    {
        return User::query()
            ->with('instructorProfile')
            ->where('status', 'active')
            ->where(function ($query): void {
                $query->where('role', 'instructor')
                    ->orWhereHas('roles', fn ($roleQuery) => $roleQuery->where('name', 'instructor'));
            })
            ->whereHas('instructorProfile')
            ->whereDoesntHave('seminarSpeakerAssignments', fn ($speakerQuery) => $speakerQuery->where('seminar_id', $seminar->id));
    }
}
