<?php

namespace App\Services\Seminars;

use App\Models\ActivityLog;
use App\Models\Seminar;
use App\Models\SeminarAttendance;
use App\Models\SeminarRegistrant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SeminarManualAttendanceService
{
    public function set(Seminar $seminar, SeminarRegistrant $registrant, User $actor, bool $attended, ?string $reason): SeminarAttendance
    {
        return DB::transaction(function () use ($seminar, $registrant, $actor, $attended, $reason): SeminarAttendance {
            $current = $seminar->registrants()->whereKey($registrant->id)->lockForUpdate()->firstOrFail();
            if ($current->status !== 'registered' || $current->cancelled_at !== null) {
                throw ValidationException::withMessages(['attended' => 'An active confirmed registration is required.']);
            }

            $attendance = $seminar->attendances()->where('user_id', $current->user_id)->lockForUpdate()->first();
            $status = $attended ? 'attended' : 'not_present';
            if ($attendance?->attendance_method === 'manual' && $attendance->status === $status) {
                return $attendance;
            }

            $hasDecision = $attendance && (
                in_array($attendance->attendance_method, ['manual', 'attendance_code', 'legacy'], true)
                || in_array($attendance->status, ['attended', 'not_present'], true)
            );
            $reason = trim((string) $reason);
            if ((! $attended || $hasDecision) && $reason === '') {
                throw ValidationException::withMessages(['reason' => 'A reason is required to remove or correct attendance.']);
            }

            $before = $attendance ? $this->snapshot($attendance) : null;
            $attendedAt = $attended ? now() : null;
            if (! $attendance) {
                $attendance = $seminar->attendances()->make([
                    'user_id' => $current->user_id,
                    'role' => 'audience',
                    'total_seconds' => 0,
                ]);
            }
            $attendance->fill([
                'attendance_method' => 'manual',
                'status' => $status,
                'attended_at' => $attendedAt,
            ])->save();
            $current->forceFill(['attended_at' => $attendedAt])->save();

            ActivityLog::log('seminar_attendance_corrected', 'Event attendance corrected', [
                'actor_id' => $actor->id,
                'seminar_id' => $seminar->id,
                'participant_id' => $current->user_id,
                'action' => $attended ? 'mark_present' : 'mark_not_present',
                'reason' => $reason !== '' ? $reason : null,
                'before' => $before,
                'after' => $this->snapshot($attendance),
            ]);

            return $attendance;
        });
    }

    private function snapshot(SeminarAttendance $attendance): array
    {
        return [
            'attendance_method' => $attendance->attendance_method,
            'status' => $attendance->status,
            'attended_at' => $attendance->attended_at?->toIso8601String(),
            'joined_at' => $attendance->joined_at?->toIso8601String(),
            'left_at' => $attendance->left_at?->toIso8601String(),
            'total_seconds' => $attendance->total_seconds,
            'role' => $attendance->role,
        ];
    }
}
