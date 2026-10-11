<?php

namespace App\Http\Controllers\Connector;

use App\Http\Controllers\Controller;
use App\Http\Requests\Connector\StoreConnectorModuleAccessRuleRequest;
use App\Models\Connector;
use App\Models\ConnectorModuleAccessRule;
use App\Models\ConnectorModuleEntitlement;
use App\Models\User;
use App\Services\Connectors\ConnectorModuleAccessService;
use Illuminate\Http\RedirectResponse;

class ModuleAccessController extends Controller
{
    public function __construct(
        private readonly ConnectorModuleAccessService $access,
    ) {
    }

    public function storeRule(
        StoreConnectorModuleAccessRuleRequest $request,
        Connector $connector,
        ConnectorModuleEntitlement $entitlement,
    ): RedirectResponse {
        $this->assertEntitlement($connector, $entitlement);
        $this->access->storeRule($entitlement, $request->user(), $request->validated());

        return back()->with('success', 'Module access rule saved.');
    }

    public function destroyRule(
        Connector $connector,
        ConnectorModuleEntitlement $entitlement,
        ConnectorModuleAccessRule $rule,
    ): RedirectResponse {
        $this->assertEntitlement($connector, $entitlement);
        abort_unless((int) $rule->connector_module_entitlement_id === (int) $entitlement->id, 404);
        $this->access->removeRule($rule, request()->user());

        return back()->with('success', 'Module access rule removed.');
    }

    public function revoke(
        Connector $connector,
        ConnectorModuleEntitlement $entitlement,
        User $learner,
    ): RedirectResponse {
        $this->assertEntitlement($connector, $entitlement);
        $this->access->revoke($entitlement, $learner, request()->user(), 'manual_revocation');

        return back()->with('success', 'Learner access revoked.');
    }

    public function clearRevocation(
        Connector $connector,
        ConnectorModuleEntitlement $entitlement,
        User $learner,
    ): RedirectResponse {
        $this->assertEntitlement($connector, $entitlement);
        $this->access->clearRevocation($entitlement, $learner, request()->user());

        return back()->with('success', 'Learner revocation cleared.');
    }

    private function assertEntitlement(
        Connector $connector,
        ConnectorModuleEntitlement $entitlement,
    ): void {
        abort_unless((int) $entitlement->connector_id === (int) $connector->id, 404);
    }
}
