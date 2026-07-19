<?php

namespace App\Services\Seminars;

use App\Enums\SeminarStatus;
use App\Models\Seminar;
use App\Models\User;
use Illuminate\Support\Collection;

class SeminarDiscoveryService
{
    public function __construct(private readonly SeminarRegistrationService $registrations)
    {
    }

    public function visibleSeminarsFor(User $user, array $filters = []): Collection
    {
        return Seminar::query()
            ->with(['connector', 'speakers.user'])
            ->withCount([
                'registrants as active_registrants_count' => fn ($query) => $query->active(),
            ])
            ->where('status', SeminarStatus::Published->value)
            ->when(filled($filters['search'] ?? null), fn ($query) => $query->where(function ($searchQuery) use ($filters): void {
                $search = $filters['search'];
                $searchQuery->where('title', 'like', "%{$search}%")
                    ->orWhere('purpose', 'like', "%{$search}%")
                    ->orWhereHas('connector', fn ($connector) => $connector->where('name', 'like', "%{$search}%"));
            }))
            ->when(filled($filters['type'] ?? null), fn ($query) => $query->where('type', $filters['type']))
            ->when(filled($filters['category'] ?? null), fn ($query) => $query->where('category', $filters['category']))
            ->when((bool) ($filters['upcoming'] ?? false), fn ($query) => $query->upcoming())
            ->orderBy('starts_at')
            ->get()
            ->filter(fn (Seminar $seminar): bool => $this->registrations->matchesParticipantEligibility($user, $seminar))
            ->values();
    }

    public function canView(User $user, Seminar $seminar): bool
    {
        return $seminar->status === SeminarStatus::Published->value
            && $this->registrations->matchesParticipantEligibility($user, $seminar);
    }
}
