<?php

namespace App\Services\Seminars;

use App\Models\ActivityLog;
use App\Models\Seminar;
use App\Models\SeminarAttendance;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class SeminarCodeAttendanceService
{
    public function __construct(
        private readonly SeminarRegistrationService $registrations,
        private readonly SeminarAccessService $access,
    ) {}

    public function generate(Seminar $seminar, User $actor, ?Carbon $opensAt = null, ?Carbon $closesAt = null): string
    {
        return DB::transaction(function () use ($seminar, $actor, $opensAt, $closesAt): string {
            $current = Seminar::query()->lockForUpdate()->findOrFail($seminar->id);
            $this->access->abortUnlessCanManageAttendance($actor, $current);
            abort_unless($this->canManageCode($current), 403);
            $effectiveOpen = $opensAt ?? $current->starts_at?->copy()->subMinutes(15);
            $effectiveClose = $closesAt ?? $current->ends_at?->copy()->addMinutes(30);
            if (! $effectiveOpen || ! $effectiveClose || $effectiveOpen->gte($effectiveClose)) {
                throw ValidationException::withMessages(['attendance_end_at' => 'Closing time must be after opening time.']);
            }

            $regenerating = $current->attendance_code_enabled && filled($current->attendance_code_hash);
            do {
                $code = str_pad((string) random_int(0, 99999999), 8, '0', STR_PAD_LEFT);
            } while ($regenerating && Hash::check($code, $current->attendance_code_hash));
            $current->forceFill([
                'attendance_code_hash' => Hash::make($code),
                'attendance_code_enabled' => true,
                'attendance_code_generated_at' => now(),
                'attendance_code_posted_ciphertext' => null,
                'attendance_code_posted_at' => null,
                'attendance_start_at' => $opensAt,
                'attendance_end_at' => $closesAt,
            ])->save();

            ActivityLog::log($regenerating ? 'seminar_attendance_code_regenerated' : 'seminar_attendance_code_generated', 'Attendance window activated', [
                'actor_id' => $actor->id, 'seminar_id' => $current->id,
                'opens_at' => $effectiveOpen->toIso8601String(), 'closes_at' => $effectiveClose->toIso8601String(),
            ], $actor->id);

            return $code;
        });
    }

    public function disable(Seminar $seminar, User $actor): void
    {
        DB::transaction(function () use ($seminar, $actor): void {
            $current = Seminar::query()->lockForUpdate()->findOrFail($seminar->id);
            $this->access->abortUnlessCanManageAttendance($actor, $current);
            abort_unless($this->canManageCode($current), 403);
            $current->forceFill([
                'attendance_code_enabled' => false, 'attendance_code_hash' => null,
                'attendance_code_posted_ciphertext' => null, 'attendance_code_posted_at' => null,
            ])->save();
            ActivityLog::log('seminar_attendance_code_closed', 'Attendance window closed', [
                'actor_id' => $actor->id, 'seminar_id' => $current->id,
            ], $actor->id);
        });
    }

    public function post(Seminar $seminar, User $actor, string $code): void
    {
        DB::transaction(function () use ($seminar, $actor, $code): void {
            $current = Seminar::query()->lockForUpdate()->findOrFail($seminar->id);
            $this->access->abortUnlessCanManageAttendance($actor, $current);
            $opensAt = $current->attendance_start_at ?? $current->starts_at?->copy()->subMinutes(15);
            $closesAt = $current->attendance_end_at ?? $current->ends_at?->copy()->addMinutes(30);
            if (! $this->canSubmit($current) || ! $opensAt || ! $closesAt || $opensAt->gte($closesAt) || now()->gt($closesAt)
                || ! preg_match('/^[0-9]{8}$/D', $code) || ! Hash::check($code, $current->attendance_code_hash)) {
                throw ValidationException::withMessages(['attendance_share_code' => 'Enter the current enabled code for an upcoming or open attendance window.']);
            }
            $current->forceFill([
                'attendance_code_posted_ciphertext' => Crypt::encryptString($code),
                'attendance_code_posted_at' => now(),
            ])->save();
            ActivityLog::log('seminar_attendance_code_posted', 'Attendance code posted for registrants', [
                'actor_id' => $actor->id, 'seminar_id' => $current->id,
            ], $actor->id);
        });
    }

    public function unpost(Seminar $seminar, User $actor): void
    {
        DB::transaction(function () use ($seminar, $actor): void {
            $current = Seminar::query()->lockForUpdate()->findOrFail($seminar->id);
            $this->access->abortUnlessCanManageAttendance($actor, $current);
            $current->forceFill(['attendance_code_posted_ciphertext' => null, 'attendance_code_posted_at' => null])->save();
            ActivityLog::log('seminar_attendance_code_unposted', 'Attendance code removed from event page', [
                'actor_id' => $actor->id, 'seminar_id' => $current->id,
            ], $actor->id);
        });
    }

    public function postedCodeFor(User $user, Seminar $seminar): ?string
    {
        $current = $seminar->fresh();
        if (! $current || $user->role === 'admin' || $user->hasRole('admin')
            || ! $this->canSubmit($current)
            || ! $this->registrations->activeRegistration($user, $current)
            || ! $this->registrations->matchesParticipantEligibility($user, $current)) {
            return null;
        }
        $opensAt = $current->attendance_start_at ?? $current->starts_at?->copy()->subMinutes(15);
        $closesAt = $current->attendance_end_at ?? $current->ends_at?->copy()->addMinutes(30);
        if (! $opensAt || ! $closesAt || now()->lt($opensAt) || now()->gt($closesAt)) {
            return null;
        }

        return $this->decryptPostedCode($current);
    }

    public function postedCodeForHost(User $actor, Seminar $seminar): ?string
    {
        $current = $seminar->fresh();
        abort_unless($current, 404);
        $this->access->abortUnlessCanManageAttendance($actor, $current);

        return $this->canSubmit($current) ? $this->decryptPostedCode($current) : null;
    }

    private function decryptPostedCode(Seminar $seminar): ?string
    {
        if (! $seminar->attendance_code_posted_ciphertext || ! $seminar->attendance_code_posted_at) {
            return null;
        }
        try {
            $code = Crypt::decryptString($seminar->attendance_code_posted_ciphertext);
        } catch (\Illuminate\Contracts\Encryption\DecryptException) {
            return null;
        }

        return Hash::check($code, $seminar->attendance_code_hash) ? $code : null;
    }

    public function submit(User $user, Seminar $seminar, string $code, string $ip): SeminarAttendance
    {
        abort_if($user->role === 'admin' || $user->hasRole('admin'), 403);
        $userKey = "seminar-code:{$seminar->id}:{$user->id}";
        $ipKey = 'seminar-code-ip:'.hash('sha256', $ip);
        abort_if(RateLimiter::tooManyAttempts($userKey, 5) || RateLimiter::tooManyAttempts($ipKey, 20), 429);

        $attendance = DB::transaction(function () use ($user, $seminar, $code): ?SeminarAttendance {
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
                return null;
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

            ActivityLog::log('seminar_attendance_submitted', 'Attendance code submitted', [
                'actor_id' => $user->id, 'seminar_id' => $current->id,
                'participant_id' => $user->id, 'attended_at' => $attendedAt->toIso8601String(),
            ], $user->id);

            return $attendance;
        });

        if ($attendance === null) {
            RateLimiter::hit($userKey, 600);
            RateLimiter::hit($ipKey, 600);
            throw ValidationException::withMessages(['code' => 'The attendance code is incorrect.']);
        }

        RateLimiter::clear($userKey);
        RateLimiter::clear($ipKey);

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
