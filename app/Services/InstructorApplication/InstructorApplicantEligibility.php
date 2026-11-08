<?php

namespace App\Services\InstructorApplication;

use App\Models\User;
use DomainException;

final class InstructorApplicantEligibility
{
    public function assertStageOneEligible(User $user): void
    {
        if (! config('instructor_application.enabled')) {
            throw new DomainException('Direct instructor applications are currently unavailable.');
        }

        if ($user->status !== User::STATUS_ACTIVE) {
            throw new DomainException('This account cannot continue an instructor application.');
        }

        if (! $user->hasVerifiedEmail()) {
            throw new DomainException('Verify your email before starting identity verification.');
        }

        if (($user->calculateAge() ?? 0) < (int) config('instructor_application.minimum_age', 18)) {
            throw new DomainException('Instructor applicants must be at least 18 years old.');
        }

        if ($user->isAdmin() || $user->isInstructor()) {
            throw new DomainException('This account is not eligible for instructor application.');
        }

        if (! $user->isInstructorApplicant() && ! $user->isLearner()) {
            throw new DomainException('This account is not eligible for instructor application.');
        }

        if ($user->isParentRegistration()) {
            throw new DomainException('Guardian-registration accounts cannot apply as instructors in this workflow.');
        }
    }

    public function assertDirectApplicant(User $user): void
    {
        $this->assertStageOneEligible($user);

        if (! $user->isInstructorApplicant()) {
            throw new DomainException('Only direct applicant accounts can start this identity attempt.');
        }
    }
}
