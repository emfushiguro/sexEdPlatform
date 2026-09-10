<?php

namespace App\Policies;

use App\Models\DependentSupportProfile;
use App\Models\ParentChildAccount;
use App\Models\User;

class DependentSupportProfilePolicy
{
    public function create(User $actor, User $dependent): bool
    {
        return $this->canManage($actor, $dependent);
    }

    public function view(User $actor, DependentSupportProfile $profile): bool
    {
        $dependent = $profile->dependent;

        return $dependent !== null && $this->canManage($actor, $dependent);
    }

    public function update(User $actor, DependentSupportProfile $profile): bool
    {
        return $this->view($actor, $profile);
    }

    public function delete(User $actor, DependentSupportProfile $profile): bool
    {
        return $this->view($actor, $profile);
    }

    private function canManage(User $actor, User $dependent): bool
    {
        if ($actor->status !== User::STATUS_ACTIVE || $dependent->status !== User::STATUS_ACTIVE) {
            return false;
        }

        if ((int) $actor->id === (int) $dependent->id) {
            return true;
        }

        return ParentChildAccount::query()
            ->accessEligible()
            ->withPermission('can_manage_support_information')
            ->where('parent_user_id', $actor->id)
            ->where('child_user_id', $dependent->id)
            ->exists();
    }
}
