<?php

namespace App\Policies;

use App\Models\Clinic;
use App\Models\User;

class ClinicPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['admin', 'super-admin', 'clinic-reviewer']);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Clinic $clinic): bool
    {
        // Admins can view all clinics
        if (in_array($user->role, ['admin', 'super-admin', 'clinic-reviewer'])) {
            return true;
        }

        // Users can only view their own clinics
        return $clinic->user_id === $user->id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true; // All authenticated users can create clinics
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Clinic $clinic): bool
    {
        // Super admin can edit any clinic
        if ($user->role === 'super-admin') {
            return true;
        }

        // Regular admin can edit approved clinics
        if ($user->role === 'admin' && $clinic->isApproved()) {
            return true;
        }

        // Users can edit their own pending or rejected clinics
        return $clinic->user_id === $user->id && !$clinic->isApproved();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Clinic $clinic): bool
    {
        // Only super admin or clinic owner can delete
        return $user->role === 'super-admin' || $clinic->user_id === $user->id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Clinic $clinic): bool
    {
        return in_array($user->role, ['admin', 'super-admin']);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Clinic $clinic): bool
    {
        return $user->role === 'super-admin';
    }

    /**
     * Determine whether the user can approve the model.
     */
    public function approve(User $user, Clinic $clinic): bool
    {
        $status = \App\Enums\ApprovalStatus::tryFrom($clinic->approval_status);
        return in_array($user->role, ['admin', 'super-admin']) && $status && $status->value === 'pending';
    }

    /**
     * Determine whether the user can reject the model.
     */
    public function reject(User $user, Clinic $clinic): bool
    {
        $status = \App\Enums\ApprovalStatus::tryFrom($clinic->approval_status);
        return in_array($user->role, ['admin', 'super-admin']) && $status && in_array($status->value, ['pending', 'approved']);
    }

    /**
     * Determine whether the user can reset approval status.
     */
    public function resetApproval(User $user, Clinic $clinic): bool
    {
        return $user->role === 'super-admin';
    }

    /**
     * Determine whether the user can perform bulk operations.
     */
    public function bulkActions(User $user): bool
    {
        return in_array($user->role, ['admin', 'super-admin']);
    }

    /**
     * Determine whether the user can view audit logs.
     */
    public function viewAuditLogs(User $user): bool
    {
        return in_array($user->role, ['admin', 'super-admin']);
    }
}