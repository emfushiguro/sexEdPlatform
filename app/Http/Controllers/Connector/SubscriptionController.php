<?php

namespace App\Http\Controllers\Connector;

use App\Http\Controllers\Controller;
use App\Models\Connector;
use App\Models\SubscriptionPlan;
use App\Services\Connectors\ConnectorAccessService;
use App\Services\Connectors\ConnectorEntitlementService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SubscriptionController extends Controller
{
    public function __construct(
        private readonly ConnectorAccessService $access,
        private readonly ConnectorEntitlementService $entitlements,
    ) {
    }

    public function show(Request $request, Connector $connector): View
    {
        $this->access->abortUnlessPermission($request->user(), $connector, 'connector.view_subscription');

        return view('connectors.subscription', [
            'connector' => $connector,
            'plan' => $this->entitlements->activePlan($connector),
            'enabledEntitlements' => $this->entitlements->enabledKeys($connector),
            'plans' => SubscriptionPlan::query()->forConnectors()->active()->ordered()->get(),
        ]);
    }
}
