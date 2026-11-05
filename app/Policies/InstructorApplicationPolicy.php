<?php

namespace App\Policies;

use App\Models\InstructorApplication;
use App\Models\InstructorIdentityVerification;
use App\Models\User;

class InstructorApplicationPolicy
{
    public function view(User $user): bool
    {
        return $this->canContinue($user);
    }

    public function saveDraft(User $user): bool
    {
        return $this->canContinue($user);
    }

    public function submit(User $user): bool
    {
        return $this->canContinue($user)
            && ! $user->instructorApplications()->where('status', 'pending')->exists();
    }

    public function withdraw(User $user, InstructorApplication $application): bool
    {
        return (int) $application->user_id === (int) $user->id
            && in_array($application->status, ['draft', 'pending'], true);
    }

    private function canContinue(User $user): bool
    {
        if ($user->isAdmin() || $user->isInstructor() || $user->isParentRegistration()) {
            return false;
        }

        if (! $user->isInstructorApplicant() && ! $user->isLearner()) {
            return false;
        }

        if (! $user->isInstructorApplicant() && ! config('instructor_application.identity_enforced_from')) {
            return true;
        }

        if ($user->status !== User::STATUS_ACTIVE || ! $user->hasVerifiedEmail()) {
            return false;
        }

        if (($user->calculateAge() ?? 0) < (int) config('instructor_application.minimum_age', 18)) {
            return false;
        }

        if (! $user->isInstructorApplicant() && config('instructor_application.identity_enforced_from')) {
            return $user->instructorIdentityVerifications()
                ->current()
                ->accessApproved()
                ->exists();
        }

        return $user->isInstructorApplicant()
            ? $user->instructorIdentityVerifications()->current()->accessApproved()->exists()
            : true;
    }
}
