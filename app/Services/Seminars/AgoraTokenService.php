<?php

namespace App\Services\Seminars;

use App\Enums\SeminarStatus;
use App\Enums\SeminarType;
use App\Models\Seminar;
use App\Models\User;
use App\Services\Connectors\ConnectorAccessService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Validation\ValidationException;

class AgoraTokenService
{
    public function __construct(
        private readonly ConnectorAccessService $connectorAccess,
        private readonly SeminarRegistrationService $registrations,
        private readonly SeminarSpeakerService $speakers,
    ) {
    }

    public function tokenFor(User $user, Seminar $seminar, string $role): array
    {
        $role = strtolower($role);

        $this->ensureConfigured();
        $this->abortUnlessJoinWindow($seminar);

        if ($role === 'audience') {
            abort_unless($this->canJoinAsAudience($user, $seminar), 403);
        } elseif (in_array($role, ['host', 'speaker'], true)) {
            abort_unless($this->canPublish($user, $seminar), 403);
            $role = $this->speakers->isSpeaker($user, $seminar) ? 'speaker' : 'host';
        } else {
            abort(422, 'Unknown livestream role.');
        }

        $ttl = (int) config('services.agora.token_ttl_seconds', 900);
        $expiresAt = now()->addSeconds($ttl);
        $uid = $this->agoraUidFor($user);

        return [
            'app_id' => config('services.agora.app_id'),
            'channel' => $seminar->livestream_channel,
            'uid' => $uid,
            'role' => $role,
            'can_publish' => in_array($role, ['host', 'speaker'], true),
            'token' => $this->buildRtcToken($seminar->livestream_channel, $uid, $role, $expiresAt),
            'expires_at' => $expiresAt->toISOString(),
        ];
    }

    public function agoraUidFor(User $user): int
    {
        return (int) $user->id;
    }

    public function canJoinAsAudience(User $user, Seminar $seminar): bool
    {
        return $this->registrations->activeRegistration($user, $seminar) !== null
            && $this->registrations->matchesParticipantEligibility($user, $seminar);
    }

    public function canPublish(User $user, Seminar $seminar): bool
    {
        if ($this->speakers->isSpeaker($user, $seminar)) {
            return true;
        }

        $connector = $seminar->connector;

        return $connector !== null
            && $this->connectorAccess->hasPermission($user, $connector, 'connector.manage_seminars');
    }

    public function isInJoinWindow(Seminar $seminar): bool
    {
        if ($seminar->type !== SeminarType::Webinar->value || $seminar->status !== SeminarStatus::Published->value) {
            return false;
        }

        $startsAt = $seminar->starts_at ?? $seminar->schedule;
        $endsAt = $seminar->ends_at;

        if (! $startsAt || ! $endsAt) {
            return false;
        }

        $opensAt = $startsAt->copy()->subMinutes((int) config('seminars.join_window_before_minutes', 15));

        return now()->betweenIncluded($opensAt, $endsAt);
    }

    protected function buildRtcToken(string $channel, int $uid, string $role, Carbon $expiresAt): string
    {
        $payload = implode('|', [
            Config::get('services.agora.app_id'),
            $channel,
            $uid,
            $role,
            $expiresAt->timestamp,
        ]);

        return hash_hmac('sha256', $payload, (string) Config::get('services.agora.app_certificate'));
    }

    private function ensureConfigured(): void
    {
        if (blank(config('services.agora.app_id')) || blank(config('services.agora.app_certificate'))) {
            throw ValidationException::withMessages(['agora' => 'Agora livestream credentials are not configured.']);
        }
    }

    private function abortUnlessJoinWindow(Seminar $seminar): void
    {
        abort_unless($this->isInJoinWindow($seminar), 403);
        abort_if(blank($seminar->livestream_channel), 403);
    }
}
