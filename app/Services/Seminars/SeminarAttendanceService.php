<?php

namespace App\Services\Seminars;

use App\Models\Seminar;
use App\Models\SeminarAttendance;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class SeminarAttendanceService
{
    public function __construct(private readonly AgoraTokenService $tokens) {}

    public function recordJoin(User $user, Seminar $seminar): SeminarAttendance
    {
        $this->authorizeAttendance($user, $seminar);

        return DB::transaction(function () use ($user, $seminar): SeminarAttendance {
            $attendance = $this->lockedAttendance($user, $seminar, [
                'status' => 'registered', 'total_seconds' => 0, 'attendance_method' => 'native',
            ]);

            $updates = [
                'joined_at' => now(),
                'left_at' => null,
                'role' => $this->tokens->roleFor($user, $seminar),
            ];
            if ($attendance->attendance_method !== 'manual') {
                $updates['status'] = 'joined';
            }
            $attendance->update($updates);

            return $attendance->fresh();
        });
    }

    public function heartbeat(User $user, Seminar $seminar): SeminarAttendance
    {
        $this->authorizeAttendance($user, $seminar);

        return DB::transaction(function () use ($user, $seminar): SeminarAttendance {
            $attendance = $this->lockedAttendance($user, $seminar, [
                'joined_at' => now(), 'status' => 'joined', 'total_seconds' => 0, 'attendance_method' => 'native',
            ]);

            $elapsed = $attendance->joined_at ? $attendance->joined_at->diffInSeconds(now()) : 0;
            $updates = [
                'joined_at' => now(),
                'total_seconds' => (int) $attendance->total_seconds + $elapsed,
            ];
            if ($attendance->attendance_method !== 'manual') {
                $updates['status'] = $this->statusForSeconds($updates['total_seconds'], true);
            }
            $attendance->update($updates);

            return $attendance->fresh();
        });
    }

    public function recordLeave(User $user, Seminar $seminar): SeminarAttendance
    {
        $this->authorizeAttendance($user, $seminar);

        return DB::transaction(function () use ($user, $seminar): SeminarAttendance {
            $attendance = $this->lockedAttendance($user, $seminar, [
                'status' => 'registered', 'total_seconds' => 0, 'attendance_method' => 'native',
            ]);
            $elapsed = $attendance->joined_at ? $attendance->joined_at->diffInSeconds(now()) : 0;
            $total = (int) $attendance->total_seconds + $elapsed;

            $updates = [
                'left_at' => now(),
                'total_seconds' => $total,
            ];
            if ($attendance->attendance_method !== 'manual') {
                $updates['status'] = $this->statusForSeconds($total, false);
            }
            $attendance->update($updates);

            return $attendance->fresh();
        });
    }

    public function finalize(Seminar $seminar): void
    {
        if (! $seminar->isNativeDelivery()) {
            return;
        }

        DB::transaction(function () use ($seminar): void {
            $seminar->attendances()->lockForUpdate()->each(function (SeminarAttendance $attendance): void {
                if (! in_array($attendance->attendance_method, ['native', 'manual'], true)) {
                    return;
                }

                $total = (int) $attendance->total_seconds;

                if ($attendance->joined_at && $attendance->left_at === null) {
                    $total += $attendance->joined_at->diffInSeconds(now());
                }

                $updates = [
                    'left_at' => $attendance->left_at ?? now(),
                    'total_seconds' => $total,
                ];
                if ($attendance->attendance_method !== 'manual') {
                    $updates['status'] = $this->statusForSeconds($total, false);
                }
                $attendance->update($updates);
            });
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
        abort_unless($seminar->isNativeDelivery(), 403);
        abort_unless($this->tokens->canJoinLivestream($user, $seminar), 403);
    }

    private function lockedAttendance(User $user, Seminar $seminar, array $defaults): SeminarAttendance
    {
        $registrant = $seminar->registrants()->where('user_id', $user->id)->lockForUpdate()->first();
        if (! $registrant) {
            $seminar->newQuery()->lockForUpdate()->findOrFail($seminar->getKey());
        }

        $attendance = $seminar->attendances()->where('user_id', $user->id)->lockForUpdate()->first();
        if ($attendance) {
            return $attendance;
        }

        $attendance = $seminar->attendances()->make(array_merge(['user_id' => $user->id], $defaults));
        $attendance->save();

        return $attendance;
    }
}
