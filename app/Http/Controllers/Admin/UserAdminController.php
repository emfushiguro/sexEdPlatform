<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ChangeUserRoleRequest;
use App\Http\Requests\Admin\IndexUserRequest;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Http\Requests\Admin\UpdateUserStatusRequest;
use App\Models\User;
use App\Services\Admin\UserManagementService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use InvalidArgumentException;

class UserAdminController extends Controller
{
    public function __construct(
        private readonly UserManagementService $userManagementService,
    ) {
    }

    public function index(IndexUserRequest $request)
    {
        $this->authorizeUserManagementAccess($request, 'view users');

        $isLearnersContext = $request->routeIs('admin.learners.*');

        $filters = [
            'search' => $request->string('search')->toString(),
            'segment' => $isLearnersContext
                ? (string) ($request->string('segment')->toString() ?: 'learners')
                : $request->string('segment')->toString(),
            'role' => $request->string('role')->toString(),
            'status' => $request->string('status')->toString(),
            'account_type' => $request->string('account_type')->toString(),
            'age_bracket' => $request->string('age_bracket')->toString(),
            'learner_scope' => $isLearnersContext
                ? (string) ($request->string('learner_scope')->toString() ?: 'all')
                : $request->string('learner_scope')->toString(),
            'created_from' => $request->string('created_from')->toString(),
            'created_to' => $request->string('created_to')->toString(),
            'date_preset' => $request->string('date_preset')->toString(),
        ];

        $perPage = max(10, min((int) $request->integer('per_page', 25), 100));

        $users = $this->userManagementService->paginateForAdmin($filters, $perPage)->withQueryString();
        $stats = $this->userManagementService->stats();

        if ($request->boolean('partial')) {
            return response()->json([
                'rows' => view('admin.users.partials.users-table-rows', ['users' => $users])->render(),
                'pagination' => view('admin.users.partials.users-pagination', ['users' => $users])->render(),
            ]);
        }

        $canCreateUsers = $this->canManageUsers($request, 'create users');
        $canEditUsers = $this->canManageUsers($request, 'edit users');
        $wizard = $this->buildIndexWizardPayload($request, $canCreateUsers, $canEditUsers);

        return view('admin.users.index', compact('users', 'stats', 'filters', 'wizard', 'canCreateUsers', 'canEditUsers'));
    }

    public function create(Request $request)
    {
        $this->authorizeUserManagementAccess($request, 'create users');

        return redirect()->route('admin.users.index', array_merge($request->query(), [
            'wizard' => 'create',
        ]));
    }

    public function store(StoreUserRequest $request)
    {
        $this->userManagementService->createUser(
            payload: $request->validated(),
            actorId: (int) $request->user()->id,
            request: $request,
        );

        return redirect()->route('admin.users.index')
            ->with('success', 'User created successfully!');
    }

    public function show(Request $request, User $user)
    {
        $this->authorizeUserManagementAccess($request, 'view users');

        $user->load([
            'subscription',
            'gamification',
            'moduleEnrollments',
            'quizAttempts',
            'certificates.module',
            'payments',
            'instructorApplications.approvedBy',
            'instructorApplications.reviews.reviewedBy',
            'roleTransitions.approvedBy',
        ]);

        $parentRelationships = $user->parentLinks()->with('parent')->latest()->get();
        $childRelationships = $user->childLinks()->with('child')->latest()->get();
        $linkedParent = optional($parentRelationships->first())->parent;

        $stats = [
            'total_payments' => $user->payments()->sum('amount'),
            'completed_modules' => $user->moduleEnrollments()->whereNotNull('completed_at')->count(),
            'quiz_attempts' => $user->quizAttempts()->count(),
            'certificates' => $user->certificates()->count(),
        ];

        $instructorLineage = $user->instructorApplications;
        $roleTransitions = $user->roleTransitions->sortByDesc('transitioned_at')->values();

        return view('admin.users.show', compact(
            'user',
            'stats',
            'parentRelationships',
            'childRelationships',
            'linkedParent',
            'instructorLineage',
            'roleTransitions',
        ));
    }

    public function edit(Request $request, User $user)
    {
        $this->authorizeUserManagementAccess($request, 'edit users');

        return redirect()->route('admin.users.index', array_merge($request->query(), [
            'wizard' => 'edit',
            'wizard_user' => $user->id,
        ]));
    }

    public function update(UpdateUserRequest $request, User $user)
    {
        try {
            $this->userManagementService->updateUser(
                user: $user,
                payload: $request->validated(),
                actorId: (int) $request->user()->id,
                request: $request,
            );
        } catch (InvalidArgumentException $exception) {
            return back()
                ->withErrors(['role_change_reason' => $exception->getMessage()])
                ->withInput();
        }

        return redirect()->route('admin.users.index')
            ->with('success', 'User updated successfully!');
    }

    public function updateStatus(UpdateUserStatusRequest $request, User $user)
    {
        $this->userManagementService->updateStatus(
            user: $user,
            status: (string) $request->string('status'),
            reason: $request->filled('reason') ? (string) $request->string('reason') : null,
            actorId: (int) $request->user()->id,
            request: $request,
        );

        return redirect()->route('admin.users.show', $user)
            ->with('success', 'User status updated successfully.');
    }

    public function changeRole(ChangeUserRoleRequest $request, User $user)
    {
        abort_unless((bool) $request->user()?->can('assign roles'), 403);

        $this->userManagementService->changeRole(
            user: $user,
            newRole: (string) $request->string('role'),
            reason: $request->filled('reason') ? (string) $request->string('reason') : null,
            customNotes: $request->filled('custom_notes') ? (string) $request->string('custom_notes') : null,
            actorId: (int) $request->user()->id,
            request: $request,
            newRoleName: $request->filled('new_role_name') ? (string) $request->string('new_role_name') : null,
            newRolePermissions: $request->input('new_role_permissions', []),
        );

        return redirect()->route('admin.users.show', $user)
            ->with('success', 'User role changed successfully.');
    }

    public function destroy(Request $request, User $user)
    {
        if ($user->id === Auth::id()) {
            return redirect()->back()
                ->with('error', 'You cannot delete your own account!');
        }

        $this->userManagementService->deleteUser(
            user: $user,
            actorId: (int) $request->user()->id,
            request: $request,
        );

        return redirect()->route('admin.users.index')
            ->with('success', 'User deleted successfully!');
    }

    private function authorizeUserManagementAccess(Request $request, string $permission): void
    {
        $user = $request->user();

        // Admin routes are already role-protected, but this fallback prevents false 403s
        // when permission cache/assignment is temporarily out of sync.
        abort_unless(
            (bool) $user?->hasRole('admin') || (bool) $user?->can($permission),
            403
        );
    }

    private function allowedWizardRoleNames(): array
    {
        return ['admin', 'instructor', 'learner'];
    }

    private function buildIndexWizardPayload(Request $request, bool $canCreateUsers, bool $canEditUsers): array
    {
        $query = $request->query();
        unset($query['wizard'], $query['wizard_user']);

        $closeUrl = route('admin.users.index', $query);
        $oldInput = (array) $request->session()->get('_old_input', []);
        $oldMode = (string) ($oldInput['wizard_mode'] ?? '');
        $requestedMode = (string) ($request->string('wizard')->toString() ?: $oldMode);
        $wizardMode = in_array($requestedMode, ['create', 'edit'], true) ? $requestedMode : null;
        $wizardUserId = (int) ($request->integer('wizard_user') ?: (int) ($oldInput['wizard_user_id'] ?? 0));

        $shouldRenderWizard = $canCreateUsers || $canEditUsers || $wizardMode !== null;
        if (! $shouldRenderWizard) {
            return [
                'render' => false,
                'open' => false,
                'mode' => 'create',
                'closeUrl' => $closeUrl,
            ];
        }

        $wizardReference = $this->buildWizardReferenceData($request);

        $payload = [
            'render' => true,
            'open' => $wizardMode !== null,
            'mode' => 'create',
            'title' => 'Create New User',
            'subtitle' => 'Use the guided wizard to configure identity, role, permissions, and confirmation.',
            'action' => route('admin.users.store'),
            'method' => 'POST',
            'user' => null,
            'directPermissions' => [],
            'selectedRole' => old('role', ''),
            'selectedStatus' => old('status', 'active'),
            'closeUrl' => $closeUrl,
            ...$wizardReference,
        ];

        if ($wizardMode === 'edit' && $canEditUsers && $wizardUserId > 0) {
            $editingUser = User::query()->find($wizardUserId);

            if ($editingUser !== null) {
                $payload['mode'] = 'edit';
                $payload['title'] = 'Edit User: ' . $editingUser->name;
                $payload['subtitle'] = 'Use the guided wizard to update identity, role lifecycle, and permission overrides.';
                $payload['action'] = route('admin.users.update', $editingUser);
                $payload['method'] = 'PUT';
                $payload['user'] = $editingUser;
                $payload['directPermissions'] = $editingUser->permissions()
                    ->orderBy('name')
                    ->pluck('name')
                    ->all();
                $payload['selectedRole'] = old('role', $editingUser->roles()->value('name') ?: $editingUser->role);
                $payload['selectedStatus'] = old('status', $editingUser->status);
            }
        }

        if ($wizardMode === 'create' && ! $canCreateUsers) {
            $payload['open'] = false;
        }

        return $payload;
    }

    private function buildWizardReferenceData(Request $request): array
    {
        $allowedRoleNames = $this->allowedWizardRoleNames();

        $roles = Role::query()
            ->whereIn('name', $allowedRoleNames)
            ->with('permissions:id,name')
            ->orderBy('name')
            ->get(['id', 'name']);

        $permissionsCollection = Permission::query()
            ->orderBy('name')
            ->get(['name', 'description']);

        $permissions = $permissionsCollection->pluck('name')->all();
        $permissionDescriptions = $permissionsCollection
            ->mapWithKeys(fn (Permission $permission): array => [
                $permission->name => (string) ($permission->description ?? ''),
            ])
            ->all();

        $rolePermissionMap = $roles->mapWithKeys(function (Role $role): array {
            return [$role->name => $role->permissions->pluck('name')->values()->all()];
        })->all();

        return [
            'roles' => $roles,
            'permissions' => $permissions,
            'permissionDescriptions' => $permissionDescriptions,
            'rolePermissionMap' => $rolePermissionMap,
            'canManagePermissions' => $this->canManageUsers($request, 'manage permissions'),
        ];
    }

    private function canManageUsers(Request $request, string $permission): bool
    {
        $user = $request->user();

        return (bool) $user?->hasRole('admin') || (bool) $user?->can($permission);
    }
}
