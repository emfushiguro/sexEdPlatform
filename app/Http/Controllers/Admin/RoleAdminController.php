<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Admin\UserManagementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class RoleAdminController extends Controller
{
    public function __construct(
        private readonly UserManagementService $userManagementService,
    ) {
    }

    public function assignToUser(Request $request, User $user): RedirectResponse
    {
        abort_unless((bool) $request->user()?->can('assign roles'), 403);

        $request->merge([
            'role' => strtolower((string) $request->input('role', '')),
            'new_role_name' => trim((string) $request->input('new_role_name', '')),
        ]);

        $assignableRoles = Role::query()
            ->whereNotIn('name', ['super-admin'])
            ->pluck('name')
            ->map(static fn ($name) => strtolower((string) $name))
            ->values()
            ->all();

        $assignableRoles[] = 'others';

        $validated = $request->validate([
            'role' => ['required', 'string', Rule::in($assignableRoles)],
            'new_role_name' => [
                'nullable',
                'string',
                'max:100',
                Rule::requiredIf(fn () => $request->input('role') === 'others'),
                Rule::unique('roles', 'name'),
            ],
            'new_role_permissions' => [
                'nullable',
                'array',
                Rule::requiredIf(fn () => $request->input('role') === 'others'),
                'min:1',
            ],
            'new_role_permissions.*' => ['string', Rule::exists('permissions', 'name')],
            'reason' => ['nullable', 'string', 'max:500'],
            'custom_notes' => ['nullable', 'string', 'max:10000'],
        ]);

        $this->userManagementService->changeRole(
            user: $user,
            newRole: (string) $validated['role'],
            reason: isset($validated['reason']) && trim((string) $validated['reason']) !== ''
                ? (string) $validated['reason']
                : null,
            customNotes: isset($validated['custom_notes']) && trim((string) $validated['custom_notes']) !== ''
                ? (string) $validated['custom_notes']
                : null,
            actorId: (int) $request->user()->id,
            request: $request,
            newRoleName: $validated['new_role_name'] ?? null,
            newRolePermissions: $validated['new_role_permissions'] ?? [],
        );

        return redirect()->route('admin.users.show', $user)
            ->with('success', 'User role assignment updated successfully.');
    }
}
