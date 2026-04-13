<?php

namespace App\Services\Admin;

use App\Models\RoleTransition;
use App\Models\User;
use App\Services\AdminActivityLogService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use InvalidArgumentException;

class UserManagementService
{
    public function __construct(private readonly AdminActivityLogService $activityLogService)
    {
    }

    public function paginateForAdmin(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $this->warmClassificationCache();

        $query = User::query()
            ->with('roles')
            ->withCount([
                'childLinks as children_count',
                'parentLinks as parents_count',
                'instructorApplications as instructor_applications_count',
            ]);

        if (! empty($filters['search'])) {
            $search = trim((string) $filters['search']);

            $query->where(function ($nested) use ($search): void {
                $nested->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
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
            }
        }

        if (! empty($filters['role'])) {
            $query->where('role', (string) $filters['role']);
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

        return $query->latest()->paginate($perPage);
    }

    public function stats(): array
    {
        return [
            'total' => User::query()->count(),
            'active' => User::query()->where('status', User::STATUS_ACTIVE)->count(),
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
            $user = User::query()->create([
                'name' => trim((string) $payload['name']),
                'email' => trim((string) $payload['email']),
                'password' => Hash::make((string) $payload['password']),
                'role' => (string) $payload['role'],
                'status' => (string) $payload['status'],
                'birthdate' => $payload['birthdate'] ?? null,
                'verified' => true,
                'email_verified_at' => now(),
            ]);

            $user->syncRoles([(string) $payload['role']]);
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
            $targetRole = (string) ($payload['role'] ?? $user->role);
            $roleChanged = $targetRole !== $user->role;

            if ($roleChanged) {
                $reason = trim((string) ($payload['role_change_reason'] ?? ''));

                if ($reason === '') {
                    throw new InvalidArgumentException('Role change reason is required when changing role.');
                }

                $this->applyRoleChange($user, $targetRole, $reason, $actorId);
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

    public function changeRole(User $user, string $newRole, string $reason, int $actorId, ?Request $request = null): User
    {
        $before = $user->only(['id', 'role', 'status']);

        $this->applyRoleChange($user, $newRole, $reason, $actorId);
        $user->refreshClassificationCache();

        $this->activityLogService->logModelMutation(
            action: 'users.role.update',
            entity: $user,
            before: $before,
            after: $user->fresh()->only(['id', 'role', 'account_type']),
            meta: [
                'source' => 'admin.users.change-role',
                'reason' => $reason,
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

    private function applyRoleChange(User $user, string $targetRole, string $reason, int $actorId): void
    {
        if ($targetRole === $user->role) {
            return;
        }

        RoleTransition::query()->create([
            'user_id' => $user->id,
            'from_role' => $user->role,
            'to_role' => $targetRole,
            'approved_by' => $actorId,
            'reason' => $reason,
            'preserved_data' => [
                'previous_status' => $user->status,
                'changed_via' => 'admin_user_management',
            ],
            'transitioned_at' => now(),
        ]);

        $user->role = $targetRole;
        $user->save();
        $user->syncRoles([$targetRole]);
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
