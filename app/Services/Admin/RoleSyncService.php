<?php

namespace App\Services\Admin;

use App\Models\User;

class RoleSyncService
{
    /**
     * Legacy users.role supports a narrower value set than Spatie role names.
     * Keep the column consistent with a safe mapped value during transition.
     */
    private const LEGACY_ROLE_VALUES = [
        'learner',
        'instructor',
        'organization',
        'clinic',
        'counselor',
        'admin',
    ];

    public function assignPrimaryRole(User $user, string $targetRole): void
    {
        $user->syncRoles([$targetRole]);

        $legacyRole = $this->toLegacyColumnValue($targetRole);
        if ($user->role === $legacyRole) {
            return;
        }

        $user->forceFill(['role' => $legacyRole]);
        $user->save();
    }

    public function toLegacyColumnValue(string $role): string
    {
        $role = trim(strtolower($role));

        if (in_array($role, self::LEGACY_ROLE_VALUES, true)) {
            return $role;
        }

        // Parent still maps to learner in the transitional legacy column.
        return 'learner';
    }
}
