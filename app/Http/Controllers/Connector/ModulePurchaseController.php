<?php

namespace App\Http\Controllers\Connector;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProcessModulePaymentRequest;
use App\Models\Connector;
use App\Models\ConnectorModulePurchase;
use App\Models\ModuleMarketplaceListing;
use App\Services\Connectors\ConnectorAccessService;
use App\Services\Connectors\ConnectorModulePurchaseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ModulePurchaseController extends Controller
{
    public function __construct(
        private readonly ConnectorAccessService $access,
        private readonly ConnectorModulePurchaseService $purchases,
    ) {
    }

    public function checkout(Request $request, Connector $connector, ModuleMarketplaceListing $listing): View|RedirectResponse
    {
        $this->access->abortUnlessPermission($request->user(), $connector, 'connector.manage_modules');
        $listing->loadMissing('module.creator');
        abort_unless($listing->status === ModuleMarketplaceListing::STATUS_PUBLISHED && $listing->module?->isLearnerVisible(), 404);

        return view('payments.checkout-summary', [
            'layout' => 'layouts.connector-app',
            'scope' => 'connector_module_purchase',
            'connector' => $connector,
            'listing' => $listing,
            'module' => $listing->module,
            'amount' => (float) $listing->price_amount,
            'currency' => $listing->currency,
            'seatCapacity' => $listing->seat_capacity,
            'paymentMethod' => 'gcash',
            'submitUrl' => route('connector.modules.purchase.store', [$connector, $listing]),
            'backUrl' => route('connector.modules.marketplace.show', [$connector, $listing]),
        ]);
    }

    public function store(
        ProcessModulePaymentRequest $request,
        Connector $connector,
        ModuleMarketplaceListing $listing,
    ): RedirectResponse {
        $this->access->abortUnlessPermission($request->user(), $connector, 'connector.manage_modules');

        try {
            $checkout = $this->purchases->createCheckout(
                $connector,
                $request->user(),
                $listing,
                $request->input('payment_method'),
                [
                    'name' => (string) ($request->input('billing_name') ?: $request->user()->name),
                    'email' => (string) ($request->input('billing_email') ?: ($request->user()->email ?? '')),
                    'phone' => (string) ($request->input('billing_phone') ?: ''),
                ],
            );
        } catch (\Illuminate\Validation\ValidationException $exception) {
            return back()->withInput()->withErrors($exception->errors());
        } catch (\Throwable $exception) {
            report($exception);

            return back()->withInput()->with('error', 'The payment provider is unavailable. Your checkout was not charged.');
        }

        if (($checkout['status'] ?? null) === 'already_purchased') {
            return redirect()->route('connector.modules.marketplace.show', [$connector, $listing])
                ->with('info', $checkout['message']);
        }

        return redirect((string) $checkout['checkout_url']);
    }

    public function confirmation(
        Request $request,
        Connector $connector,
        ConnectorModulePurchase $purchase,
    ): View {
        $this->access->abortUnlessWorkspace($request->user(), $connector);
        abort_unless(
            (int) $purchase->connector_id === (int) $connector->id
                && $purchase->status === ConnectorModulePurchase::STATUS_COMPLETED,
            404,
        );
        $purchase->load(['connector', 'module', 'entitlement']);

        return view('connectors.modules.purchase-confirmation', [
            'connector' => $connector,
            'purchase' => $purchase,
            'entitlement' => $purchase->entitlement,
            'canManage' => $this->access->hasPermission($request->user(), $connector, 'connector.manage_modules'),
        ]);
    }
}
