<?php

namespace App\Http\Controllers\Connector;

use App\Http\Controllers\Controller;
use App\Models\Connector;
use App\Services\Connectors\ConnectorAccessService;
use App\Services\Connectors\ConnectorEntitlementService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        private readonly ConnectorAccessService $access,
        private readonly ConnectorEntitlementService $entitlements,
    ) {
    }

    public function index(Request $request, Connector $connector): View|\Illuminate\Http\RedirectResponse
    {
        $membership = $this->workspaceMembership($request, $connector);
        if (! $membership) {
            return redirect()->route('connector.status', $connector);
        }

        return view('connectors.dashboard', [
            'connector' => $connector->loadCount(['memberships', 'invitations']),
            'membership' => $membership,
            'plan' => $this->entitlements->activePlan($connector),
            'enabledEntitlements' => $this->entitlements->enabledKeys($connector),
        ]);
    }

    public function seminars(Request $request, Connector $connector): View|\Illuminate\Http\RedirectResponse
    {
        if (! $this->workspaceMembership($request, $connector)) {
            return redirect()->route('connector.status', $connector);
        }

        return view('connectors.stubs.seminars', ['connector' => $connector]);
    }

    public function modules(Request $request, Connector $connector): View|\Illuminate\Http\RedirectResponse
    {
        if (! $this->workspaceMembership($request, $connector)) {
            return redirect()->route('connector.status', $connector);
        }

        return view('connectors.stubs.modules', ['connector' => $connector]);
    }

    public function educators(Request $request, Connector $connector): View|\Illuminate\Http\RedirectResponse
    {
        if (! $this->workspaceMembership($request, $connector)) {
            return redirect()->route('connector.status', $connector);
        }

        return view('connectors.stubs.educators', ['connector' => $connector]);
    }

    private function workspaceMembership(Request $request, Connector $connector)
    {
        if ($connector->status !== 'verified') {
            return null;
        }

        $membership = $this->access->activeMembership($request->user(), $connector);
        abort_unless($membership, 403);

        return $membership;
    }
}
