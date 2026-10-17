<?php

namespace App\Http\Controllers\Connector;

use App\Http\Controllers\Controller;
use App\Http\Requests\Connector\StoreConnectorModuleAccessRuleRequest;
use App\Models\Connector;
use App\Models\ConnectorModuleAccessRule;
use App\Models\ConnectorModuleEntitlement;
use App\Models\User;
use App\Services\Connectors\ConnectorAccessService;
use App\Services\Connectors\ConnectorModuleAccessService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ModuleAccessController extends Controller
{
    public function __construct(
        private readonly ConnectorModuleAccessService $access,
        private readonly ConnectorAccessService $workspace,
    ) {}

    public function member(Request $request, Connector $connector, ConnectorModuleEntitlement $entitlement): RedirectResponse
    {
        $this->assertEntitlement($connector, $entitlement);
        $this->workspace->abortUnlessPermission($request->user(), $connector, 'connector.manage_modules');
        $data = $request->validate([
            'user_id' => ['required', 'integer', 'min:1'],
            'action' => ['required', 'in:grant,revoke'],
            'confirmed' => ['accepted'],
        ]);
        $redirect = redirect()->route('connector.modules.library.access', [$connector, $entitlement]);
        try {
            $this->access->manageMember($entitlement, $request->user(), $data['action'], (int) $data['user_id']);
        } catch (ValidationException $exception) {
            return $redirect->withErrors($exception->errors());
        } catch (\Throwable $exception) {
            if ($exception instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface) {
                throw $exception;
            }
            report($exception);

            return $redirect->withErrors(['operation' => 'The request could not be completed. Reload the roster to confirm current access before retrying.']);
        }

        return $redirect->with('success', $data['action'] === 'grant'
            ? 'Member access granted. Existing guardian approval requirements still apply.'
            : 'Connector access revoked. Independent access and learning history are preserved.');
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
