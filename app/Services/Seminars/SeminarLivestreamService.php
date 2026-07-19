<?php

namespace App\Services\Seminars;

use App\Enums\SeminarStatus;
use App\Enums\SeminarType;
use App\Models\Seminar;
use App\Models\User;
use App\Notifications\Seminars\SeminarLiveNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

class SeminarLivestreamService
{
    public function __construct(
        private readonly AgoraTokenService $tokens,
        private readonly SeminarLifecycleService $lifecycle,
        private readonly SeminarAttendanceService $attendance,
    ) {}

    public function prepare(Seminar $seminar): Seminar
    {
        $this->assertStartable($seminar);

        if ($seminar->livestream_status === 'scheduled') {
            $seminar->forceFill(['livestream_status' => 'waiting_room'])->save();
        }

        return $seminar->fresh();
    }

    public function start(Seminar $seminar): array
    {
        $started = false;
        $seminar = DB::transaction(function () use ($seminar, &$started): Seminar {
            $locked = Seminar::query()->lockForUpdate()->findOrFail($seminar->id);
            $this->assertStartable($locked);

            if ($locked->livestream_status === 'live') {
                return $locked;
            }

            $locked->forceFill([
                'livestream_status' => 'live',
                'livestream_started_at' => $locked->livestream_started_at ?? now(),
                'livestream_ended_at' => null,
            ])->save();
            $started = true;

            return $locked->fresh(['connector']);
        });

        if ($started) {
            $this->notifyEligibleParticipants($seminar);
        }

        return ['seminar' => $seminar, 'started' => $started];
    }

    public function end(Seminar $seminar, User $user): Seminar
    {
        $seminar = DB::transaction(function () use ($seminar, $user): Seminar {
            $locked = Seminar::query()->lockForUpdate()->findOrFail($seminar->id);

            if ($locked->status === SeminarStatus::Completed->value) {
                return $locked;
            }

            abort_unless(in_array($locked->livestream_status, ['waiting_room', 'live'], true), 422, 'This livestream is not active.');
            $locked->forceFill([
                'livestream_status' => 'completed',
                'livestream_ended_at' => now(),
            ])->save();

            return $this->lifecycle->complete($locked, $user);
        });

        $this->attendance->finalize($seminar);

        return $seminar->fresh();
    }

    public function status(Seminar $seminar): array
    {
        $joined = $seminar->attendances()->whereNull('left_at');

        return [
            'status' => $seminar->livestream_status ?? 'scheduled',
            'started_at' => $seminar->livestream_started_at?->toISOString(),
            'participant_count' => (clone $joined)->count(),
            'viewer_count' => (clone $joined)->where('role', 'audience')->count(),
            'speaker_count' => (clone $joined)->where('role', 'speaker')->count(),
        ];
    }

    private function assertStartable(Seminar $seminar): void
    {
        if ($seminar->type !== SeminarType::Webinar->value || $seminar->status !== SeminarStatus::Published->value) {
            throw ValidationException::withMessages(['livestream' => 'Only published webinars can go live.']);
        }

        abort_unless($this->tokens->isInJoinWindow($seminar), 403, 'Livestream access is outside the scheduled session window.');
    }

    private function notifyEligibleParticipants(Seminar $seminar): void
    {
        $speakerIds = $seminar->speakers()->where('status', 'accepted')->whereNotNull('user_id')->pluck('user_id');

        User::query()->whereIn('id', $speakerIds)->each(
            fn (User $user) => $this->notifySafely($user, new SeminarLiveNotification($seminar, 'speaker'))
        );

        $seminar->registrants()->where('status', 'registered')->whereNotIn('user_id', $speakerIds)->with('user')->each(
            fn ($registrant) => $registrant->user && $this->notifySafely($registrant->user, new SeminarLiveNotification($seminar, 'audience'))
        );
    }

    private function notifySafely(User $user, SeminarLiveNotification $notification): void
    {
        try {
            $user->notify($notification);
        } catch (Throwable $error) {
            report($error);
        }
    }
}
