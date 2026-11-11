<?php

namespace App\Policies;

use App\Models\ParentChildAccount;
use App\Models\User;

class ParentChildPolicy
{
    public function giftSubscription(User $guardian, User $dependent): bool
    {
        return $guardian->id !== $dependent->id
            && $guardian->status === User::STATUS_ACTIVE
            && $dependent->status === User::STATUS_ACTIVE
            && $guardian->hasVerifiedEmail()
            && $guardian->canBeParent()
            && $guardian->isParentRegistration()
            && $guardian->isParentVerificationApproved()
            && $guardian->hasCompletedProfile()
            && $dependent->isLearner()
            && ParentChildAccount::query()
                ->accessEligible()
                ->where('parent_user_id', $guardian->id)
                ->where('child_user_id', $dependent->id)
                ->exists();
    }

    /**
     * Determine if the authenticated parent can view a child's monitoring page.
     * Checks the active, verified relationship and progress permission.
     */
    public function view(User $guardian, User $dependent): bool
    {
        return ParentChildAccount::query()
            ->accessEligible()
            ->withPermission('can_view_progress')
            ->where('parent_user_id', $guardian->id)
            ->where('child_user_id', $dependent->id)
            ->exists();
    }
}
