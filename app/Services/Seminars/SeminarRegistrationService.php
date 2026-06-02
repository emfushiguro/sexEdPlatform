<?php

namespace App\Services\Seminars;

use App\Enums\SeminarParticipantType;
use App\Enums\SeminarStatus;
use App\Models\Seminar;
use App\Models\SeminarRegistrant;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SeminarRegistrationService
{
    public function eligiblePublishedSeminarsFor(User $user): Collection
    {
        return Seminar::query()
            ->with('connector')
            ->published()
            ->orderBy('starts_at')
            ->get()
            ->filter(fn (Seminar $seminar): bool => $this->matchesParticipantEligibility($user, $seminar))
            ->values();
    }

    public function canRegister(User $user, Seminar $seminar): bool
    {
        return $this->registrationError($user, $seminar) === null;
    }

    public function register(User $user, Seminar $seminar): SeminarRegistrant
    {
        if ($message = $this->registrationError($user, $seminar)) {
            throw ValidationException::withMessages(['seminar' => $message]);
        }

        return DB::transaction(function () use ($user, $seminar): SeminarRegistrant {
            $existing = $seminar->registrants()->where('user_id', $user->id)->lockForUpdate()->first();

            if ($existing) {
                if ($existing->status === 'registered' && $existing->cancelled_at === null) {
                    throw ValidationException::withMessages(['seminar' => 'You are already registered for this seminar.']);
                }

                $existing->update([
                    'status' => 'registered',
                    'participant_type' => $this->participantTypeFor($user),
                    'registered_at' => now(),
                    'attended_at' => null,
                    'cancelled_at' => null,
                    'cancellation_reason' => null,
                ]);

                return $existing->fresh();
            }

            return $seminar->registrants()->create([
                'user_id' => $user->id,
                'status' => 'registered',
                'participant_type' => $this->participantTypeFor($user),
                'registered_at' => now(),
            ]);
        });
    }

    public function cancel(User $user, Seminar $seminar): void
    {
        $registrant = $this->activeRegistration($user, $seminar);

        if (! $registrant) {
            throw ValidationException::withMessages(['seminar' => 'You are not actively registered for this seminar.']);
        }

        if ($this->hasStarted($seminar)) {
            throw ValidationException::withMessages(['seminar' => 'Registration can no longer be cancelled after the seminar starts.']);
        }

        $registrant->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
        ]);
    }

    public function activeRegistration(User $user, Seminar $seminar): ?SeminarRegistrant
    {
        return $seminar->registrants()
            ->where('user_id', $user->id)
            ->where('status', 'registered')
            ->whereNull('cancelled_at')
            ->first();
    }

    public function matchesParticipantEligibility(User $user, Seminar $seminar): bool
    {
        $participantType = $this->participantTypeFor($user);

        if ($participantType === null) {
            return false;
        }

        $target = $seminar->target_participants ?: SeminarParticipantType::LearnersAndInstructors->value;

        if ($participantType === 'instructor') {
            return in_array($target, [
                SeminarParticipantType::Instructors->value,
                SeminarParticipantType::LearnersAndInstructors->value,
            ], true);
        }

        if (! in_array($target, [
            SeminarParticipantType::Learners->value,
            SeminarParticipantType::LearnersAndInstructors->value,
        ], true)) {
            return false;
        }

        $allowed = (array) ($seminar->learner_age_categories ?? []);

        return $allowed === [] || in_array($this->learnerAgeCategoryFor($user), $allowed, true);
    }

    public function participantTypeFor(User $user): ?string
    {
        if ($user->role === 'instructor' || $user->hasRole('instructor')) {
            return 'instructor';
        }

        if ($user->role === 'learner' || $user->hasRole('learner')) {
            return 'learner';
        }

        return null;
    }

    public function learnerAgeCategoryFor(User $user): ?string
    {
        $bracket = $user->age_bracket_cached ?: $user->learnerProfile?->getAgeBracket();

        return match ($bracket) {
            'kids' => 'kids',
            'teens', 'teen' => 'teen',
            'adults', 'adult' => 'adult',
            default => null,
        };
    }

    public function registrationError(User $user, Seminar $seminar): ?string
    {
        if ($seminar->status === SeminarStatus::Cancelled->value) {
            return 'This seminar has been cancelled.';
        }

        if ($seminar->status === SeminarStatus::Completed->value) {
            return 'This seminar has already been completed.';
        }

        if ($seminar->status !== SeminarStatus::Published->value) {
            return 'Registration is not open for this seminar.';
        }

        if ($this->hasStarted($seminar)) {
            return 'Registration is closed because this seminar has already started.';
        }

        if (! $this->matchesParticipantEligibility($user, $seminar)) {
            return $this->participantTypeFor($user) === 'learner'
                ? 'This seminar is not available for your learner age category.'
                : 'This seminar is not available for your participant type.';
        }

        if ($this->activeRegistration($user, $seminar)) {
            return 'You are already registered for this seminar.';
        }

        if ($seminar->capacity !== null && $seminar->registrants()->active()->count() >= (int) $seminar->capacity) {
            return 'This seminar has reached capacity.';
        }

        return null;
    }

    public function hasStarted(Seminar $seminar): bool
    {
        $startsAt = $seminar->starts_at ?? $seminar->schedule;

        return $startsAt !== null && $startsAt->lte(now());
    }
}
