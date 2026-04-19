<?php

namespace App\Services\Chat;

use App\Models\User;

class SupportAdminResolver
{
    public function resolve(?int $excludeUserId = null): ?User
    {
        $configuredAdminId = (int) config('chat.support_admin_user_id', 0);

        if ($configuredAdminId > 0) {
            $configuredAdmin = $this->baseQuery($excludeUserId)
                ->whereKey($configuredAdminId)
                ->first();

            if ($configuredAdmin !== null) {
                return $configuredAdmin;
            }
        }

        return $this->baseQuery($excludeUserId)
            ->orderBy('id')
            ->first();
    }

    protected function baseQuery(?int $excludeUserId)
    {
        return User::query()
            ->whereNull('deleted_at')
            ->when((bool) config('chat.support_admin_requires_active', true), function ($query) {
                $query->where('status', User::STATUS_ACTIVE);
            })
            ->when($excludeUserId !== null && $excludeUserId > 0, function ($query) use ($excludeUserId) {
                $query->where('id', '!=', $excludeUserId);
            })
            ->where(function ($query) {
                $query->where('role', 'admin')
                    ->orWhereHas('roles', function ($roleQuery) {
                        $roleQuery->where('name', 'admin');
                    })
                    ->orWhereHas('permissions', function ($permissionQuery) {
                        $permissionQuery->whereIn('name', ['access admin panel', 'moderate chat']);
                    })
                    ->orWhereHas('roles.permissions', function ($permissionQuery) {
                        $permissionQuery->whereIn('name', ['access admin panel', 'moderate chat']);
                    });
            })
            ->with([
                'learnerProfile:id,user_id,avatar_path',
                'instructorProfile:id,user_id,profile_photo_path',
                'adminCreatorProfile:id,user_id,affiliation',
            ]);
    }
}
