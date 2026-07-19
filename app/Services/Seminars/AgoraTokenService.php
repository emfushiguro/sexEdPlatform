<?php

namespace App\Services\Seminars;

use App\Enums\SeminarStatus;
use App\Enums\SeminarType;
use App\Models\Seminar;
use App\Models\User;
use App\Services\Connectors\ConnectorAccessService;
use App\Support\Agora\RtcTokenBuilder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Validation\ValidationException;

class AgoraTokenService
{
    public function __construct(
        private readonly ConnectorAccessService $connectorAccess,
        private readonly SeminarRegistrationService $registrations,
        private readonly SeminarSpeakerService $speakers,
    ) {}

    public function tokenFor(User $user, Seminar $seminar, string $role): array
    {
        $role = strtolower($role);

        $this->ensureConfigured();
        if ($role === 'audience') {
            abort_unless($this->canJoinAsAudience($user, $seminar) && $this->isLive($seminar), 403);
        } elseif ($role === 'host') {
            abort_unless($this->canHost($user, $seminar), 403);
        } elseif ($role === 'speaker') {
            abort_unless($this->speakers->isSpeaker($user, $seminar) && $this->isLive($seminar), 403);
        } else {
            abort(422, 'Unknown livestream role.');
        }

        $this->abortUnlessJoinWindow($seminar);

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
            'livestream_status' => $seminar->livestream_status ?? 'scheduled',
            'livestream_started_at' => $seminar->livestream_started_at?->toISOString(),
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
        return $this->speakers->isSpeaker($user, $seminar) || $this->canHost($user, $seminar);
    }

    public function canHost(User $user, Seminar $seminar): bool
    {

        $connector = $seminar->connector;

        return $connector !== null
            && $this->connectorAccess->hasPermission($user, $connector, 'connector.manage_seminars');
    }

    public function roleFor(User $user, Seminar $seminar): ?string
    {
        if ($this->canHost($user, $seminar)) {
            return 'host';
        }

        if ($this->speakers->isSpeaker($user, $seminar)) {
            return 'speaker';
        }

        return $this->canJoinAsAudience($user, $seminar) ? 'audience' : null;
    }

    public function canJoinLivestream(User $user, Seminar $seminar): bool
    {
        $role = $this->roleFor($user, $seminar);

        return $this->isInJoinWindow($seminar)
            && ($role === 'host' || ($role !== null && $this->isLive($seminar)));
    }

    public function isLive(Seminar $seminar): bool
    {
        return $seminar->livestream_status === 'live';
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
        $token = RtcTokenBuilder::build(
            (string) Config::get('services.agora.app_id'),
            (string) Config::get('services.agora.app_certificate'),
            $channel,
            $uid,
            in_array($role, ['host', 'speaker'], true),
            $expiresAt->timestamp,
        );

        if ($token !== '') {
            return $token;
        }

        // ponytail: test/local fallback for non-Agora fake credentials; real 32-char Agora IDs use AccessToken2 above.
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
