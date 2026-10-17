<?php

namespace App\Http\Controllers\Connector;

use App\Http\Controllers\Controller;
use App\Models\Connector;
use App\Models\ConnectorModuleEntitlement;
use App\Services\Connectors\ConnectorAccessService;
use App\Services\Connectors\ConnectorModuleAccessService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ModuleLibraryController extends Controller
{
    public function __construct(
        private readonly ConnectorAccessService $access,
        private readonly ConnectorModuleAccessService $moduleAccess,
    ) {}

    public function index(Request $request, Connector $connector): View
    {
        $this->access->abortUnlessWorkspace($request->user(), $connector);
        $entitlements = $connector->connectorModuleEntitlements()
            ->with(['module', 'listing', 'purchase'])
            ->latest('id')
            ->get();

        $entitlements->each(fn (ConnectorModuleEntitlement $entitlement) => $this->addSeatCounts($entitlement));

        return view('connectors.modules.library.index', [
            'connector' => $connector,
            'entitlements' => $entitlements,
            'canManage' => $this->access->hasPermission($request->user(), $connector, 'connector.manage_modules'),
        ]);
    }

    public function show(Request $request, Connector $connector, ConnectorModuleEntitlement $entitlement): View
    {
        $this->access->abortUnlessWorkspace($request->user(), $connector);
        $this->assertEntitlement($connector, $entitlement);
        $entitlement->load(['module', 'listing', 'purchase', 'connector']);
        $this->addSeatCounts($entitlement);

        return view('connectors.modules.library.show', [
            'connector' => $connector,
            'entitlement' => $entitlement,
            'canManage' => $this->access->hasPermission($request->user(), $connector, 'connector.manage_modules'),
        ]);
    }

    public function access(Request $request, Connector $connector, ConnectorModuleEntitlement $entitlement): View
    {
        $this->access->abortUnlessPermission($request->user(), $connector, 'connector.manage_modules');
        $this->assertEntitlement($connector, $entitlement);
        $entitlement->load([
            'module',
            'purchase',
            'rules.creator',
            'assignments.user',
            'assignments.membership.role',
        ]);
        $this->addSeatCounts($entitlement);
        $members = $this->moduleAccess->managementRoster($entitlement);

        return view('connectors.modules.library.access', [
            'connector' => $connector,
            'entitlement' => $entitlement,
            'canManage' => true,
            'members' => $members,
        ]);
    }

    private function assertEntitlement(Connector $connector, ConnectorModuleEntitlement $entitlement): void
    {
        abort_unless((int) $entitlement->connector_id === (int) $connector->id, 404);
    }

    private function addSeatCounts(ConnectorModuleEntitlement $entitlement): void
    {
        $used = $entitlement->assignments()
            ->where('consumes_seat', true)
            ->whereNull('released_at')
            ->count();
        $entitlement->setAttribute('used_seats', $used);
        $entitlement->setAttribute(
            'available_seats',
            max(0, (int) $entitlement->seat_capacity - $used),
        );
    }
}
