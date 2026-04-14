<?php

namespace App\Services\Admin;

use App\Models\RoleTransition;
use App\Models\User;
use App\Support\Permissions\PermissionOverrideDelta;
use App\Services\AdminActivityLogService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Spatie\Permission\Models\Role;

class UserManagementService
{
    public function __construct(
        private readonly AdminActivityLogService $activityLogService,
        private readonly RoleSyncService $roleSyncService,
    ) {
    }

    public function paginateForAdmin(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $this->warmClassificationCache();

        $perPage = in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 25;

        $query = User::query()
            ->with(['roles', 'permissions'])
            ->withCount([
                'childLinks as children_count',
                'parentLinks as parents_count',
                'instructorApplications as instructor_applications_count',
            ]);

        if (! empty($filters['search'])) {
            $search = trim((string) $filters['search']);

            $query->where(function ($nested) use ($search): void {
                $nested->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('role', 'like', "%{$search}%")
                    ->orWhereHas('roles', function ($roleQuery) use ($search): void {
                        $roleQuery->where('name', 'like', "%{$search}%");
                    });
            });
        }

        if (! empty($filters['segment'])) {
            $segment = (string) $filters['segment'];

            if ($segment === 'learners') {
                $query->where('role', 'learner');
            } elseif ($segment === 'parents') {
                $query->where(function ($nested): void {
                    $nested->where('account_type', User::ACCOUNT_TYPE_PARENT)
                        ->orWhereHas('childLinks');
                });
            } elseif ($segment === 'instructors') {
                $query->where('role', 'instructor');
            } elseif ($segment === 'admins') {
                $query->where('role', 'admin');
            } elseif ($segment === 'archived') {
                $query->where('status', User::STATUS_ARCHIVED);
            }
        }

        if (! empty($filters['role'])) {
            $roleFilter = (string) $filters['role'];

            $query->where(function ($nested) use ($roleFilter): void {
                $nested->where('role', $roleFilter)
                    ->orWhereHas('roles', function ($roleQuery) use ($roleFilter): void {
                        $roleQuery->where('name', $roleFilter);
                    });
            });
        }

        if (! empty($filters['status'])) {
            $query->where('status', (string) $filters['status']);
        }

        if (! empty($filters['account_type'])) {
            $query->where('account_type', (string) $filters['account_type']);
        }

        if (! empty($filters['age_bracket'])) {
            $query->where('age_bracket_cached', (string) $filters['age_bracket']);
        }

        if (! empty($filters['learner_scope']) && (string) $filters['learner_scope'] !== 'all') {
            $scope = (string) $filters['learner_scope'];

            $query->where('role', 'learner')
                ->whereHas('moduleEnrollments.module', function ($moduleQuery) use ($scope): void {
                    if ($scope === 'platform') {
                        $moduleQuery->where('content_owner_type', 'admin');
                    } elseif ($scope === 'instructor') {
                        $moduleQuery->where('content_owner_type', 'instructor');
                    }
                });
        }

        if (! empty($filters['date_preset'])) {
            $preset = (string) $filters['date_preset'];

            if ($preset === 'today') {
                $query->whereDate('created_at', now()->toDateString());
            } elseif ($preset === '7d') {
                $query->where('created_at', '>=', now()->subDays(7)->startOfDay());
            } elseif ($preset === '30d') {
                $query->where('created_at', '>=', now()->subDays(30)->startOfDay());
            }
        }

        if (! empty($filters['created_from'])) {
            $query->whereDate('created_at', '>=', (string) $filters['created_from']);
        }

        if (! empty($filters['created_to'])) {
            $query->whereDate('created_at', '<=', (string) $filters['created_to']);
        }

        return $query->latest()->paginate($perPage);
    }

    public function stats(): array
    {
        return [
            'total' => User::query()->count(),
            'active' => User::query()->where('status', User::STATUS_ACTIVE)->count(),
            'suspended' => User::query()->where('status', User::STATUS_SUSPENDED)->count(),
            'learners' => User::query()->where('role', 'learner')->count(),
            'parents' => User::query()->where(function ($query): void {
                $query->where('account_type', User::ACCOUNT_TYPE_PARENT)
                    ->orWhereHas('childLinks');
            })->count(),
            'instructors' => User::query()->where('role', 'instructor')->count(),
            'admins' => User::query()->where('role', 'admin')->count(),
            'archived' => User::query()->where('status', User::STATUS_ARCHIVED)->count(),
            'premium' => User::query()
                ->whereHas('subscription', function ($query): void {
                    $query->where('status', 'active');
                })
                ->count(),
        ];
    }

    public function createUser(array $payload, int $actorId, ?Request $request = null): User
    {
        return DB::transaction(function () use ($payload, $actorId, $request): User {
            $targetRole = $this->resolveTargetRole($payload);
            $directPermissions = $this->resolveDirectPermissions($payload, $targetRole);

            $user = User::query()->create([
                'name' => trim((string) $payload['name']),
                'email' => trim((string) $payload['email']),
                'password' => Hash::make((string) $payload['password']),
                'role' => $this->roleSyncService->toLegacyColumnValue($targetRole),
                'status' => (string) $payload['status'],
                'birthdate' => $payload['birthdate'] ?? null,
                'verified' => true,
                'email_verified_at' => now(),
            ]);

            $this->roleSyncService->assignPrimaryRole($user, $targetRole);

            if ($directPermissions !== null) {
                $user->syncPermissions($directPermissions);
            }

            $user->refreshClassificationCache();

            $this->activityLogService->logModelMutation(
                action: 'users.create',
                entity: $user,
                before: null,
                after: $user->fresh()->only([
                    'id',
                    'name',
                    'email',
                    'role',
                    'status',
                    'account_type',
                    'age_bracket_cached',
                ]),
                meta: ['source' => 'admin.users.store'],
                request: $request,
                adminUserId: $actorId,
            );

            return $user->fresh(['roles']);
        });
    }

    public function updateUser(User $user, array $payload, int $actorId, ?Request $request = null): User
    {
        return DB::transaction(function () use ($user, $payload, $actorId, $request): User {
            $before = $user->only(['id', 'name', 'email', 'role', 'status', 'account_type', 'age_bracket_cached']);
            $currentRole = $this->currentAssignedRole($user);
            $targetRole = array_key_exists('role', $payload)
                ? $this->resolveTargetRole($payload)
                : $currentRole;
            $roleChanged = $targetRole !== $currentRole;

            if ($roleChanged) {
                $reason = isset($payload['role_change_reason'])
                    ? trim((string) $payload['role_change_reason'])
                    : null;
                $customNotes = isset($payload['role_change_custom_notes'])
                    ? trim((string) $payload['role_change_custom_notes'])
                    : null;

                $this->applyRoleChange(
                    user: $user,
                    targetRole: $targetRole,
                    reason: $reason !== '' ? $reason : null,
                    customNotes: $customNotes !== '' ? $customNotes : null,
                    actorId: $actorId,
                );
            }

            $user->fill([
                'name' => trim((string) $payload['name']),
                'email' => trim((string) $payload['email']),
                'status' => (string) $payload['status'],
                'birthdate' => $payload['birthdate'] ?? $user->birthdate,
            ]);

            if (! empty($payload['password'])) {
                $user->password = Hash::make((string) $payload['password']);
            }

            $user->save();

            $directPermissions = $this->resolveDirectPermissions($payload, $targetRole);
            if ($directPermissions !== null) {
                $user->syncPermissions($directPermissions);
            }

            $user->refreshClassificationCache();

            $this->activityLogService->logModelMutation(
                action: 'users.update',
                entity: $user,
                before: $before,
                after: $user->fresh()->only([
                    'id',
                    'name',
                    'email',
                    'role',
                    'status',
                    'account_type',
                    'age_bracket_cached',
                ]),
                meta: [
                    'source' => 'admin.users.update',
                    'role_changed' => $roleChanged,
                ],
                request: $request,
                adminUserId: $actorId,
            );

            return $user->fresh(['roles']);
        });
    }

    public function updateStatus(User $user, string $status, ?string $reason, int $actorId, ?Request $request = null): User
    {
        $before = $user->only(['id', 'status']);

        $status = trim(strtolower($status));
        $reason = is_string($reason) ? trim($reason) : null;

        if ($status === '' || ! in_array($status, [
            User::STATUS_ACTIVE,
            User::STATUS_INACTIVE,
            User::STATUS_SUSPENDED,
            User::STATUS_ARCHIVED,
        ], true)) {
            throw new InvalidArgumentException('Invalid lifecycle status provided.');
        }

        if ($status !== (string) $user->status) {
            $movingToArchived = $status === User::STATUS_ARCHIVED;
            $restoringFromArchived = (string) $user->status === User::STATUS_ARCHIVED && $status !== User::STATUS_ARCHIVED;

            if (($movingToArchived || $restoringFromArchived) && ($reason === null || $reason === '')) {
                throw new InvalidArgumentException('Please provide a reason when archiving or restoring an archived user.');
            }
        }

        $user->update(['status' => $status]);
        $user->refreshClassificationCache();

        $this->activityLogService->logModelMutation(
            action: 'users.status.update',
            entity: $user,
            before: $before,
            after: $user->fresh()->only(['id', 'status']),
            meta: [
                'source' => 'admin.users.update-status',
                'reason' => $reason,
            ],
            request: $request,
            adminUserId: $actorId,
        );

        return $user->fresh();
    }

    public function changeRole(
        User $user,
        string $newRole,
        ?string $reason,
        ?string $customNotes,
        int $actorId,
        ?Request $request = null,
        ?string $newRoleName = null,
        array $newRolePermissions = [],
    ): User
    {
        $before = $user->only(['id', 'role', 'status']);

        $newRole = trim(strtolower($newRole));
        if ($newRole === 'others') {
            $newRole = $this->resolveTargetRole([
                'role' => 'others',
                'new_role_name' => $newRoleName,
                'new_role_permissions' => $newRolePermissions,
            ]);
        }
        $reason = is_string($reason) ? trim($reason) : null;
        $customNotes = is_string($customNotes) ? trim($customNotes) : null;

        $this->applyRoleChange(
            user: $user,
            targetRole: $newRole,
            reason: $reason === '' ? null : $reason,
            customNotes: $customNotes === '' ? null : $customNotes,
            actorId: $actorId,
        );
        $user->refreshClassificationCache();

        $this->activityLogService->logModelMutation(
            action: 'users.role.update',
            entity: $user,
            before: $before,
            after: $user->fresh()->only(['id', 'role', 'account_type']),
            meta: [
                'source' => 'admin.users.change-role',
                'reason' => $reason,
                'custom_notes' => $customNotes,
            ],
            request: $request,
            adminUserId: $actorId,
        );

        return $user->fresh(['roles']);
    }

    public function deleteUser(User $user, int $actorId, ?Request $request = null): void
    {
        $before = $user->only(['id', 'name', 'email', 'role', 'status']);

        $user->delete();

        $this->activityLogService->log(
            action: 'users.delete',
            entityType: User::class,
            entityId: $before['id'],
            before: $before,
            after: null,
            meta: ['source' => 'admin.users.destroy'],
            request: $request,
            adminUserId: $actorId,
        );
    }

    private function applyRoleChange(User $user, string $targetRole, ?string $reason, ?string $customNotes, int $actorId): void
    {
        $currentRole = $this->currentAssignedRole($user);

        if ($targetRole === $currentRole) {
            return;
        }

        RoleTransition::query()->create([
            'user_id' => $user->id,
            'from_role' => $currentRole,
            'to_role' => $targetRole,
            'approved_by' => $actorId,
            'reason' => $reason,
            'custom_notes' => $customNotes,
            'preserved_data' => [
                'previous_status' => $user->status,
                'changed_via' => 'admin_user_management',
            ],
            'transitioned_at' => now(),
        ]);

        $this->roleSyncService->assignPrimaryRole($user, $targetRole);
    }

    private function resolveTargetRole(array $payload): string
    {
        $requestedRole = trim(strtolower((string) ($payload['role'] ?? '')));

        if ($requestedRole === '' || $requestedRole === 'others') {
            $newRoleName = trim((string) ($payload['new_role_name'] ?? ''));
            $normalizedName = Str::of($newRoleName)
                ->lower()
                ->replace(' ', '-')
                ->replaceMatches('/[^a-z0-9\-_]/', '')
                ->trim('-_')
                ->toString();

            if ($normalizedName === '') {
                throw new InvalidArgumentException('A valid new role name is required when selecting Others.');
            }

            $role = Role::query()->firstOrCreate([
                'name' => $normalizedName,
                'guard_name' => 'web',
            ]);

            $newRolePermissions = PermissionOverrideDelta::normalize((array) ($payload['new_role_permissions'] ?? []));
            if ($newRolePermissions !== []) {
                $role->syncPermissions($newRolePermissions);
            }

            return $role->name;
        }

        return $requestedRole;
    }

    private function resolveDirectPermissions(array $payload, string $targetRole): ?array
    {
        $applyOverrides = (bool) ($payload['apply_permission_overrides'] ?? false);
        $hasDirectPermissions = array_key_exists('direct_permissions', $payload);
        $hasDelta = array_key_exists('permission_overrides_add', $payload)
            || array_key_exists('permission_overrides_remove', $payload);

        if (! $applyOverrides && ! $hasDirectPermissions && ! $hasDelta) {
            return null;
        }

        if ($hasDirectPermissions) {
            return PermissionOverrideDelta::normalize((array) ($payload['direct_permissions'] ?? []));
        }

        $inheritedPermissions = Role::query()
            ->where('name', $targetRole)
            ->first()?->permissions->pluck('name')->all() ?? [];
        $deltaAdd = PermissionOverrideDelta::normalize((array) ($payload['permission_overrides_add'] ?? []));
        $deltaRemove = PermissionOverrideDelta::normalize((array) ($payload['permission_overrides_remove'] ?? []));

        // Direct user permissions are additive; this computes the additive subset from the override panel.
        return PermissionOverrideDelta::apply($inheritedPermissions, $deltaAdd, $deltaRemove);
    }

    private function currentAssignedRole(User $user): string
    {
        $assignedRole = $user->roles()->value('name');

        if (is_string($assignedRole) && $assignedRole !== '') {
            return $assignedRole;
        }

        return (string) $user->role;
    }

    private function warmClassificationCache(): void
    {
        $targets = User::query()
            ->where(function ($query): void {
                $query->whereNull('account_type')
                    ->orWhere(function ($nested): void {
                        $nested->where('role', 'learner')
                            ->whereNull('age_bracket_cached');
                    });
            })
            ->limit(200)
            ->get();

        foreach ($targets as $target) {
            if ($target instanceof User) {
                $target->refreshClassificationCache();
            }
        }
    }
}
