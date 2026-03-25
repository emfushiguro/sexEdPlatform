<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\AdminActivityLogService;
use Illuminate\Http\Request;

/**
 * Handles subscriber (subscription record) management in the admin panel.
 * Separated from plan management for clarity.
 */
class SubscriberAdminController extends Controller
{
    /**
     * List all subscribers with filtering and statistics.
     */
    public function index(Request $request)
    {
        $query = Subscription::with(['user', 'payments', 'plan']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('plan_id')) {
            $query->where('plan_id', $request->plan_id);
        }
        if ($request->filled('search')) {
            $query->whereHas('user', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('email', 'like', '%' . $request->search . '%');
            });
        }

        $subscriptions = $query->latest()->paginate(15);
        $plans = SubscriptionPlan::active()->ordered()->get();

        $subscriptionStats = [
            'total'          => Subscription::count(),
            'active'         => Subscription::where('status', 'active')->count(),
            'cancelled'      => Subscription::where('status', 'cancelled')->count(),
            'expired'        => Subscription::where('status', 'expired')->count(),
            'past_due'       => Subscription::where('status', 'past_due')->count(),
            'trialing'       => Subscription::where('status', 'trialing')->count(),
            'total_revenue'  => Subscription::where('status', 'active')->sum('price_paid'),
            'expiring_soon'  => Subscription::expiringSoon()->count(),
        ];

        $planStats = [
            'total'    => SubscriptionPlan::count(),
            'active'   => SubscriptionPlan::where('is_active', true)->count(),
            'inactive' => SubscriptionPlan::where('is_active', false)->count(),
        ];

        return view('admin.subscriber.index', compact(
            'subscriptions',
            'plans',
            'subscriptionStats',
            'planStats'
        ));
    }

    /**
     * Show a single subscription's details.
     */
    public function show(Subscription $subscription)
    {
        $subscription->load(['user', 'plan', 'payments']);
        $plans = SubscriptionPlan::active()->ordered()->get();
        $user  = $subscription->user;
        $plan  = $subscription->plan;

        return view('admin.subscriber.show', compact('subscription', 'plans', 'user', 'plan'));
    }

    /**
     * Handle quick actions on subscriber records (activate, cancel).
     */
    public function quickAction(Request $request)
    {
        return match ($request->input('action')) {
            'activate_subscription' => $this->activate($request),
            'cancel_subscription'   => $this->cancel($request),
            default                 => redirect()->back()->with('error', 'Unknown action.'),
        };
    }

    private function activate(Request $request)
    {
        $subscription = Subscription::findOrFail($request->subscription_id);
        $before = $subscription->only(['id', 'status', 'cancelled_at', 'cancellation_reason']);

        try {
            app(\App\Services\SubscriptionService::class)->activate($subscription);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Activation failed: ' . $e->getMessage());
        }

        app(AdminActivityLogService::class)->logModelMutation(
            action: 'subscribers.activate',
            entity: $subscription,
            before: $before,
            after: $subscription->fresh()->only(['id', 'status', 'cancelled_at', 'cancellation_reason']),
            meta: ['source' => 'admin.subscribers.quick-action'],
            request: $request,
        );

        return redirect()->back()->with('success', 'Subscription activated successfully.');
    }

    private function cancel(Request $request)
    {
        $subscription = Subscription::findOrFail($request->subscription_id);
        $before = $subscription->only(['id', 'status', 'cancelled_at', 'cancellation_reason']);
        $subscription->update([
            'status'               => 'cancelled',
            'cancelled_at'         => now(),
            'cancellation_reason'  => 'Cancelled by admin',
        ]);

        app(AdminActivityLogService::class)->logModelMutation(
            action: 'subscribers.cancel',
            entity: $subscription,
            before: $before,
            after: $subscription->fresh()->only(['id', 'status', 'cancelled_at', 'cancellation_reason']),
            meta: ['source' => 'admin.subscribers.quick-action'],
            request: $request,
        );

        return redirect()->back()->with('success', 'Subscription cancelled.');
    }

}
