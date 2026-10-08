<?php

namespace App\Policies;

use App\Models\ParentChildAccount;
use App\Models\User;

class ParentChildPolicy
{
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
