<?php

namespace App\Services\Seminars;

use App\Enums\SeminarStatus;
use App\Models\Seminar;
use App\Models\User;
use Illuminate\Support\Collection;

class SeminarDiscoveryService
{
    public function __construct(
        private readonly SeminarRegistrationService $registrations,
        private readonly SeminarAccessService $access,
    ) {}

    public function visibleSeminarsFor(User $user, array $filters = []): Collection
    {
        return Seminar::query()
            ->with(['connector', 'speakers.user'])
            ->withCount([
                'registrants as active_registrants_count' => fn ($query) => $query->active(),
            ])
            ->withExists([
                'registrants as viewer_is_registered' => fn ($query) => $query->active()->where('user_id', $user->id),
                'registrants as viewer_registration_pending' => fn ($query) => $query->where('user_id', $user->id)->where('status', 'pending')->whereNull('cancelled_at'),
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
        if (! in_array($seminar->status, [SeminarStatus::Published->value, SeminarStatus::Completed->value], true)) {
            return false;
        }

        if ($user->role === 'admin' || $user->hasRole('admin')) {
            return true;
        }

        if ($seminar->connector && $this->access->canManageConnectorSeminars($user, $seminar->connector)) {
            return true;
        }

        if ($seminar->speakers()->where('user_id', $user->id)->where('status', 'accepted')->exists()) {
            return true;
        }

        if (! $this->registrations->matchesParticipantEligibility($user, $seminar)) {
            return false;
        }

        return $seminar->status === SeminarStatus::Published->value
            || $this->registrations->activeRegistration($user, $seminar) !== null;
    }
}
