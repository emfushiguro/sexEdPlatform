<?php

namespace App\Services\Seminars;

use App\Models\Seminar;
use App\Models\SeminarAttendance;
use App\Models\User;

class SeminarAttendanceService
{
    public function __construct(private readonly AgoraTokenService $tokens) {}

    public function recordJoin(User $user, Seminar $seminar): SeminarAttendance
    {
        $this->authorizeAttendance($user, $seminar);

        $attendance = SeminarAttendance::query()->firstOrCreate(
            ['seminar_id' => $seminar->id, 'user_id' => $user->id],
            ['status' => 'registered', 'total_seconds' => 0]
        );

        $attendance->update([
            'joined_at' => now(),
            'left_at' => null,
            'status' => 'joined',
            'role' => $this->tokens->roleFor($user, $seminar),
        ]);

        return $attendance->fresh();
    }

    public function heartbeat(User $user, Seminar $seminar): SeminarAttendance
    {
        $this->authorizeAttendance($user, $seminar);

        $attendance = SeminarAttendance::query()->firstOrCreate(
            ['seminar_id' => $seminar->id, 'user_id' => $user->id],
            ['joined_at' => now(), 'status' => 'joined', 'total_seconds' => 0]
        );

        $elapsed = $attendance->joined_at ? $attendance->joined_at->diffInSeconds(now()) : 0;
        $attendance->update([
            'joined_at' => now(),
            'total_seconds' => (int) $attendance->total_seconds + $elapsed,
            'status' => $this->statusForSeconds((int) $attendance->total_seconds + $elapsed, true),
        ]);

        return $attendance->fresh();
    }

    public function recordLeave(User $user, Seminar $seminar): SeminarAttendance
    {
        $this->authorizeAttendance($user, $seminar);

        $attendance = SeminarAttendance::query()->firstOrCreate(
            ['seminar_id' => $seminar->id, 'user_id' => $user->id],
            ['status' => 'registered', 'total_seconds' => 0]
        );
        $elapsed = $attendance->joined_at ? $attendance->joined_at->diffInSeconds(now()) : 0;
        $total = (int) $attendance->total_seconds + $elapsed;

        $attendance->update([
            'left_at' => now(),
            'total_seconds' => $total,
            'status' => $this->statusForSeconds($total, false),
        ]);

        return $attendance->fresh();
    }

    public function finalize(Seminar $seminar): void
    {
        $seminar->attendances()->each(function (SeminarAttendance $attendance): void {
            $total = (int) $attendance->total_seconds;

            if ($attendance->joined_at && $attendance->left_at === null) {
                $total += $attendance->joined_at->diffInSeconds(now());
            }

            $attendance->update([
                'left_at' => $attendance->left_at ?? now(),
                'total_seconds' => $total,
                'status' => $this->statusForSeconds($total, false),
            ]);
        });
    }

    public function statusForSeconds(int $seconds, bool $currentlyJoined): string
    {
        $minimumSeconds = max(0, (int) config('seminars.attendance.minimum_minutes', 5)) * 60;

        if ($seconds >= $minimumSeconds) {
            return 'attended';
        }

        return $currentlyJoined ? 'joined' : 'left';
    }

    private function authorizeAttendance(User $user, Seminar $seminar): void
    {
        abort_unless($this->tokens->canJoinLivestream($user, $seminar), 403);
    }
}
