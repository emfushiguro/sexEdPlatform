<?php

namespace App\Services\Seminars;

use App\Models\Seminar;
use App\Models\User;

class SeminarExternalAccessService
{
    public function __construct(
        private readonly SeminarRegistrationService $registrations,
        private readonly SeminarAccessService $access,
    ) {}

    public function canJoin(User $user, Seminar $seminar): bool
    {
        if (! $seminar->isExternalDelivery()
            || ! in_array($seminar->status, ['published', 'completed'], true)
            || ! $this->hasValidUrl($seminar)
            || $this->isExpired($seminar)) {
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

        return ($seminar->external_link_visible_at === null || now()->greaterThanOrEqualTo($seminar->external_link_visible_at))
            && $this->registrations->activeRegistration($user, $seminar) !== null
            && $this->registrations->matchesParticipantEligibility($user, $seminar);
    }

    public function redirectUrl(User $user, Seminar $seminar): string
    {
        $fresh = $seminar->fresh();
        abort_unless($fresh !== null && $this->canJoin($user, $fresh), 403);

        return $fresh->external_url;
    }

    public function messageFor(User $user, Seminar $seminar): ?string
    {
        if ($this->canJoin($user, $seminar)) {
            return null;
        }

        if (! $this->hasValidUrl($seminar)) {
            return 'The join link is unavailable.';
        }

        if ($this->isExpired($seminar)) {
            return 'The join link has expired.';
        }

        if ($this->registrations->activeRegistration($user, $seminar)
            && $seminar->external_link_visible_at !== null
            && now()->lessThan($seminar->external_link_visible_at)) {
            return 'The join link will be available at the scheduled release time.';
        }

        return 'The join link is unavailable.';
    }

    private function hasValidUrl(Seminar $seminar): bool
    {
        $url = $seminar->external_url;

        return is_string($url)
            && filter_var($url, FILTER_VALIDATE_URL) !== false
            && in_array(strtolower((string) parse_url($url, PHP_URL_SCHEME)), ['http', 'https'], true)
            && filled(parse_url($url, PHP_URL_HOST));
    }

    private function isExpired(Seminar $seminar): bool
    {
        if (($seminar->external_link_expiry_mode === 'event_end' && $seminar->ends_at === null)
            || ($seminar->external_link_expiry_mode === 'custom' && $seminar->external_link_expires_at === null)) {
            return true;
        }

        $expiry = match ($seminar->external_link_expiry_mode) {
            'ongoing' => null,
            'event_end' => $seminar->ends_at,
            'custom' => $seminar->external_link_expires_at,
            default => now(),
        };

        return $expiry !== null && now()->greaterThanOrEqualTo($expiry);
    }
}
