<?php

namespace App\Http\Controllers;

use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SubscriptionController extends Controller
{
    /**
     * Display subscription plans and current status
     */
    public function index()
    {
        $user = auth()->user();
        $subscription = $user->subscription;

        return view('subscriptions.index', compact('subscription'));
    }

    /**
     * Show the upgrade page
     */
    public function upgrade()
    {
        $user = auth()->user();
        
        if ($user->isPremium()) {
            return redirect()->route('subscription.index')
                ->with('info', 'You already have an active premium subscription.');
        }

        return view('subscriptions.upgrade');
    }

    /**
     * Process subscription upgrade
     */
    public function processUpgrade(Request $request)
    {
        $request->validate([
            'plan' => 'required|in:monthly,annual',
        ]);

        $user = auth()->user();

        // Check if user already has premium subscription
        if ($user->isPremium()) {
            return redirect()->route('subscription.index')
                ->with('error', 'You already have an active premium subscription.');
        }

        DB::beginTransaction();

        try {
            // Create or update subscription
            $subscription = $user->subscription()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'plan' => $request->plan,
                    'status' => 'pending', // Will be activated after payment
                    'start_date' => now(),
                    'end_date' => $request->plan === 'monthly' 
                        ? now()->addMonth() 
                        : now()->addYear(),
                ]
            );

            DB::commit();

            // Redirect to payment
            return redirect()->route('payment.create', ['subscription' => $subscription->id])
                ->with('success', 'Please complete the payment to activate your subscription.');

        } catch (\Exception $e) {
            DB::rollBack();
            
            return redirect()->back()
                ->with('error', 'Failed to process subscription. Please try again.');
        }
    }

    /**
     * Cancel subscription
     */
    public function cancel()
    {
        $user = auth()->user();
        $subscription = $user->subscription;

        if (!$subscription || !$subscription->isActive()) {
            return redirect()->route('subscription.index')
                ->with('error', 'No active subscription to cancel.');
        }

        return view('subscriptions.cancel', compact('subscription'));
    }

    /**
     * Process subscription cancellation
     */
    public function processCancel(Request $request)
    {
        $user = auth()->user();
        $subscription = $user->subscription;

        if (!$subscription || !$subscription->isActive()) {
            return redirect()->route('subscription.index')
                ->with('error', 'No active subscription to cancel.');
        }

        DB::beginTransaction();

        try {
            $subscription->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
            ]);

            DB::commit();

            return redirect()->route('subscription.index')
                ->with('success', 'Your subscription has been cancelled. You can continue to use premium features until ' . $subscription->end_date->format('M d, Y'));

        } catch (\Exception $e) {
            DB::rollBack();
            
            return redirect()->back()
                ->with('error', 'Failed to cancel subscription. Please try again.');
        }
    }

    /**
     * Renew cancelled subscription
     */
    public function renew()
    {
        $user = auth()->user();
        $subscription = $user->subscription;

        if (!$subscription || $subscription->status !== 'cancelled') {
            return redirect()->route('subscription.index')
                ->with('error', 'No cancelled subscription to renew.');
        }

        DB::beginTransaction();

        try {
            $subscription->update([
                'status' => 'active',
                'cancelled_at' => null,
                'end_date' => $subscription->plan === 'monthly'
                    ? now()->addMonth()
                    : now()->addYear(),
            ]);

            DB::commit();

            return redirect()->route('subscription.index')
                ->with('success', 'Your subscription has been renewed successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            
            return redirect()->back()
                ->with('error', 'Failed to renew subscription. Please try again.');
        }
    }

    /**
     * Check subscription status (API endpoint)
     */
    public function checkStatus()
    {
        $user = auth()->user();
        $subscription = $user->subscription;

        return response()->json([
            'has_subscription' => $subscription !== null,
            'is_premium' => $user->isPremium(),
            'plan' => $subscription?->plan,
            'status' => $subscription?->status,
            'end_date' => $subscription?->end_date,
        ]);
    }
}
