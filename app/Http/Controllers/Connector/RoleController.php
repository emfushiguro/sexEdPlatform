<?php

namespace App\Http\Controllers\Connector;

use App\Http\Controllers\Controller;
use App\Http\Requests\Connector\StoreConnectorRoleRequest;
use App\Http\Requests\Connector\UpdateConnectorRoleRequest;
use App\Models\Connector;
use App\Models\ConnectorRole;
use App\Services\Connectors\ConnectorAccessService;
use App\Services\Connectors\ConnectorRoleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RoleController extends Controller
{
    public function __construct(
        private readonly ConnectorAccessService $access,
        private readonly ConnectorRoleService $roles,
    ) {
    }

    public function index(Request $request, Connector $connector): View
    {
        $this->access->abortUnlessPermission($request->user(), $connector, 'connector.manage_roles');

        return view('connectors.roles.index', [
            'connector' => $connector->load('roles.permissions'),
            'permissionGroups' => config('connector_permissions.permissions', []),
        ]);
    }

    public function store(StoreConnectorRoleRequest $request, Connector $connector): RedirectResponse
    {
        $this->access->abortUnlessPermission($request->user(), $connector, 'connector.manage_roles');
        $this->roles->createRole($connector, $request->validated());

        return back()->with('success', 'Connector role created.');
    }

    public function update(UpdateConnectorRoleRequest $request, Connector $connector, ConnectorRole $role): RedirectResponse
    {
        $this->access->abortUnlessPermission($request->user(), $connector, 'connector.manage_roles');
        abort_unless($role->connector_id === $connector->id, 404);

        $this->roles->updateRole($role, $request->validated());

        return back()->with('success', 'Connector role updated.');
    }

    public function destroy(Request $request, Connector $connector, ConnectorRole $role): RedirectResponse
    {
        $this->access->abortUnlessPermission($request->user(), $connector, 'connector.manage_roles');
        abort_unless($role->connector_id === $connector->id, 404);

        $this->roles->deleteRole($role);

        return back()->with('success', 'Connector role deleted.');
    }
}
