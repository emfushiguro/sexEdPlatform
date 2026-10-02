<?php

namespace App\Services\Seminars;

use App\Models\Seminar;
use App\Models\SeminarAttendance;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class SeminarCodeAttendanceService
{
    public function __construct(private readonly SeminarRegistrationService $registrations) {}

    public function generate(Seminar $seminar, ?Carbon $opensAt, ?Carbon $closesAt): string
    {
        return DB::transaction(function () use ($seminar, $opensAt, $closesAt): string {
            $current = Seminar::query()->lockForUpdate()->findOrFail($seminar->id);
            abort_unless($this->canManageCode($current), 403);
            $effectiveOpen = $opensAt ?? $current->starts_at?->copy()->subMinutes(15);
            $effectiveClose = $closesAt ?? $current->ends_at?->copy()->addMinutes(30);
            if ($effectiveOpen && $effectiveClose && $effectiveOpen->gte($effectiveClose)) {
                throw ValidationException::withMessages(['attendance_end_at' => 'Closing time must be after opening time.']);
            }

            $code = str_pad((string) random_int(0, 99999999), 8, '0', STR_PAD_LEFT);
            $current->forceFill([
                'attendance_code_hash' => Hash::make($code),
                'attendance_code_enabled' => true,
                'attendance_code_generated_at' => now(),
                'attendance_start_at' => $opensAt,
                'attendance_end_at' => $closesAt,
            ])->save();

            return $code;
        });
    }

    public function disable(Seminar $seminar): void
    {
        DB::transaction(function () use ($seminar): void {
            $current = Seminar::query()->lockForUpdate()->findOrFail($seminar->id);
            abort_unless($this->canManageCode($current), 403);
            $current->forceFill(['attendance_code_enabled' => false, 'attendance_code_hash' => null])->save();
        });
    }

    public function submit(User $user, Seminar $seminar, string $code, string $ip): SeminarAttendance
    {
        $userKey = "seminar-code:{$seminar->id}:{$user->id}";
        $ipKey = 'seminar-code-ip:'.hash('sha256', $ip);
        abort_if(RateLimiter::tooManyAttempts($userKey, 5) || RateLimiter::tooManyAttempts($ipKey, 20), 429);

        $attendance = DB::transaction(function () use ($user, $seminar, $code, $userKey, $ipKey): SeminarAttendance {
            $current = Seminar::query()->lockForUpdate()->findOrFail($seminar->id);
            if (! $this->canSubmit($current)) {
                throw ValidationException::withMessages(['code' => 'Attendance code is unavailable.']);
            }

            $registrant = $current->registrants()->where('user_id', $user->id)->lockForUpdate()->first();
            if (! $registrant || $registrant->status !== 'registered' || $registrant->cancelled_at !== null
                || ! $this->registrations->matchesParticipantEligibility($user, $current)) {
                throw ValidationException::withMessages(['code' => 'An active eligible registration is required.']);
            }

            $opensAt = $current->attendance_start_at ?? $current->starts_at?->copy()->subMinutes(15);
            $closesAt = $current->attendance_end_at ?? $current->ends_at?->copy()->addMinutes(30);
            if (! $opensAt || ! $closesAt || now()->lt($opensAt) || now()->gt($closesAt)) {
                throw ValidationException::withMessages(['code' => 'Attendance code entry is closed.']);
            }

            $existing = $current->attendances()->where('user_id', $user->id)->lockForUpdate()->first();
            if ($existing?->attendance_method === 'manual') {
                throw ValidationException::withMessages(['code' => 'Attendance was set by the organizer. Contact them for a correction.']);
            }
            if ($existing?->attendance_method === 'attendance_code') {
                throw ValidationException::withMessages(['code' => 'Attendance already submitted.']);
            }

            if (! Hash::check($code, $current->attendance_code_hash)) {
                RateLimiter::hit($userKey, 600);
                RateLimiter::hit($ipKey, 600);
                throw ValidationException::withMessages(['code' => 'The attendance code is incorrect.']);
            }

            $attendedAt = now();
            if ($existing) {
                $existing->update(['status' => 'attended', 'attendance_method' => 'attendance_code', 'attended_at' => $attendedAt]);
                $attendance = $existing;
            } else {
                $attendance = $current->attendances()->create([
                    'user_id' => $user->id, 'role' => 'audience', 'status' => 'attended',
                    'attendance_method' => 'attendance_code', 'attended_at' => $attendedAt,
                    'total_seconds' => 0,
                ]);
            }
            $registrant->update(['attended_at' => $attendedAt]);
            RateLimiter::clear($userKey);
            RateLimiter::clear($ipKey);

            return $attendance;
        });

        return $attendance;
    }

    private function canManageCode(Seminar $seminar): bool
    {
        return in_array($seminar->event_format, ['in_person', 'external'], true)
            && ! in_array($seminar->status, ['cancelled', 'archived'], true);
    }

    private function canSubmit(Seminar $seminar): bool
    {
        return $this->canManageCode($seminar)
            && in_array($seminar->status, ['published', 'completed'], true)
            && $seminar->attendance_code_enabled
            && filled($seminar->attendance_code_hash);
    }
}
