<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class PermissionAdminController extends Controller
{
    public function syncRolePermissions(Request $request, Role $role): RedirectResponse
    {
        abort_unless((bool) $request->user()?->can('manage permissions'), 403);

        $validated = $request->validate([
            'permissions' => ['required', 'array', 'min:1'],
            'permissions.*' => ['required', 'string', 'exists:permissions,name'],
        ]);

        $role->syncPermissions($validated['permissions']);

        return back()->with('success', 'Role permissions synchronized successfully.');
    }
}
