<?php

namespace App\Policies;

use App\Models\User;

class ParentChildPolicy
{
    /**
     * Determine if the authenticated parent can view a child's monitoring page.
     * Checks that a parent_child_accounts row exists linking them.
     */
    public function view(User $parent, User $child): bool
    {
        return $parent->children()->where('child_user_id', $child->id)->exists();
    }
}
