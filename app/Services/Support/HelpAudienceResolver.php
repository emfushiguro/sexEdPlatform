<?php

namespace App\Services\Support;

use App\Models\Connector;
use App\Models\User;

class HelpAudienceResolver
{
    public function resolve(?User $user, ?Connector $connector = null): string
    {
        if ($connector !== null) {
            return 'connector';
        }

        if ($user === null) {
            return 'guest';
        }

        if ($user->role === 'learner' && $user->account_type === 'parent') {
            return 'parent';
        }

        return in_array($user->role, ['learner', 'instructor', 'admin'], true)
            ? $user->role
            : 'guest';
    }
}
