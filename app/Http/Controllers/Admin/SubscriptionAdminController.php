<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SubscriptionAdminController extends Controller
{
    public function index(Request $request)
    {
        $query = Subscription::with(['user', 'payments', 'plan']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('plan_id')) {
            $query->where('plan_id', $request->plan_id);
        }
        if ($request->filled('plan')) {
            $query->where('plan', $request->plan);
        }
        if ($request->filled('search')) {
            $query->whereHas('user', function ($q) use ($request) {
                $q->where('name', 'like', '%'.$request->search.'%')
                  ->orWhere('email', 'like', '%'.$request->search.'%');
            });
        }

        $subscriptions = $query->latest()->paginate(15);
        $plans = SubscriptionPlan::active()->ordered()->get();
        
        // Enhanced subscription statistics
        $stats = [
            'total' => Subscription::count(),
            'active' => Subscription::where('status', 'active')->count(),
            'cancelled' => Subscription::where('status', 'cancelled')->count(),
            'expired' => Subscription::where('status', 'expired')->count(),
            'past_due' => Subscription::where('status', 'past_due')->count(),
            'monthly_active' => Subscription::where('status', 'active')->where('plan', 'monthly')->count(),
            'annual_active' => Subscription::where('status', 'active')->where('plan', 'annual')->count(),
            'total_revenue' => Subscription::where('status', 'active')->sum('price_paid'),
            'expiring_soon' => Subscription::expiringSoon()->count(),
        ];
        
        return view('admin.subscriptions.index', compact('subscriptions', 'stats', 'plans'));
    }

    public function create()
    {
        $users = User::whereDoesntHave('subscription', function ($query) {
            $query->where('status', 'active');
        })->orderBy('name')->get();
        
        $plans = SubscriptionPlan::active()->ordered()->get();
        
        return view('admin.subscriptions.create', compact('users', 'plans'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'plan_id' => 'required|exists:subscription_plans,id',
            'billing_cycle' => 'required|in:monthly,annual',
            'start_date' => 'nullable|date|after_or_equal:today',
            'trial_days' => 'nullable|integer|min:0|max:365',
            'custom_price' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:500',
            'auto_renew' => 'boolean',
            'send_email' => 'boolean',
        ]);

        // Check if user already has active subscription
        $user = User::find($validated['user_id']);
        if ($user->subscription && $user->subscription->isActive()) {
            return redirect()->back()
                ->with('error', 'User already has an active subscription.')
                ->withInput();
        }

        $plan = SubscriptionPlan::find($validated['plan_id']);
        $startDate = $validated['start_date'] ? now()->parse($validated['start_date']) : now();
        
        // Calculate subscription period
        $trialDays = $validated['trial_days'] ?? $plan->trial_days ?? 0;
        $trialEnds = $trialDays > 0 ? $startDate->copy()->addDays($trialDays) : null;
        
        // Calculate end date based on billing cycle
        if ($validated['billing_cycle'] === 'annual') {
            $endDate = $startDate->copy()->addYear();
            $price = $validated['custom_price'] ?? $plan->annual_price;
        } else {
            $endDate = $startDate->copy()->addMonth();
            $price = $validated['custom_price'] ?? $plan->monthly_price;
        }

        // Check for test plan (10 minutes)
        if ($plan->hasFeature('duration_minutes')) {
            $durationMinutes = $plan->getFeatureValue('duration_minutes', 10);
            $endDate = $startDate->copy()->addMinutes($durationMinutes);
        }

        DB::beginTransaction();

        try {
            // Create subscription
            $subscription = Subscription::create([
                'user_id' => $validated['user_id'],
                'plan_id' => $validated['plan_id'],
                'plan' => $plan->slug, // Legacy support
                'status' => $trialDays > 0 ? 'trialing' : 'active',
                'start_date' => $startDate,
                'end_date' => $endDate,
                'trial_ends_at' => $trialEnds,
                'price_paid' => $price,
                'auto_renew' => $validated['auto_renew'] ?? true,
            ]);

            // Create payment record
            $subscription->payments()->create([
                'user_id' => $validated['user_id'],
                'amount' => $price,
                'method' => 'admin_created',
                'status' => 'completed',
                'transaction_id' => 'ADM-' . strtoupper(uniqid()),
                'payment_details' => [
                    'created_by_admin' => true,
                    'admin_notes' => $validated['notes'],
                    'billing_cycle' => $validated['billing_cycle'],
                ],
                'paid_at' => now(),
            ]);

            DB::commit();

            return redirect()->route('admin.subscriptions.show', $subscription)
                ->with('success', 'Subscription created successfully for ' . $user->name . '!');

        } catch (\Exception $e) {
            DB::rollback();
            
            return redirect()->back()
                ->with('error', 'Failed to create subscription: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function show(Subscription $subscription)
    {
        $subscription->load(['user', 'payments', 'plan']);
        return view('admin.subscriptions.show', compact('subscription'));
    }

    public function edit(Subscription $subscription)
    {
        $subscription->load(['user', 'plan']);
        $plans = SubscriptionPlan::active()->ordered()->get();
        
        return view('admin.subscriptions.edit', compact('subscription', 'plans'));
    }

    public function update(Request $request, Subscription $subscription)
    {
        $validated = $request->validate([
            'plan_id' => 'nullable|exists:subscription_plans,id',
            'status' => 'required|in:active,cancelled,expired,past_due,pending',
            'end_date' => 'nullable|date',
            'price_paid' => 'nullable|numeric|min:0',
            'auto_renew' => 'boolean',
            'cancellation_reason' => 'nullable|string|max:500',
            'admin_notes' => 'nullable|string|max:1000',
        ]);

        // If status is being changed to cancelled, set cancelled_at
        if ($validated['status'] === 'cancelled' && $subscription->status !== 'cancelled') {
            $validated['cancelled_at'] = now();
        }

        // If reactivating, clear cancellation fields
        if ($validated['status'] === 'active' && $subscription->status === 'cancelled') {
            $validated['cancelled_at'] = null;
            $validated['cancellation_reason'] = null;
        }

        $subscription->update($validated);

        return redirect()->route('admin.subscriptions.show', $subscription)
            ->with('success', 'Subscription updated successfully!');
    }

    public function activate(Subscription $subscription)
    {
        $subscription->update([
            'status' => 'active',
            'cancelled_at' => null,
            'cancellation_reason' => null,
        ]);
        
        // Mark pending payments as completed
        $subscription->payments()
            ->where('status', 'pending')
            ->update(['status' => 'completed', 'paid_at' => now()]);
            
        return back()->with('success', 'Subscription activated.');
    }

    public function cancel(Subscription $subscription)
    {
        $subscription->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'cancellation_reason' => 'Cancelled by admin',
        ]);
        
        return back()->with('success', 'Subscription cancelled.');
    }

    public function extend(Request $request, Subscription $subscription)
    {
        $request->validate([
            'extend_type' => 'required|in:days,weeks,months,years',
            'extend_amount' => 'required|integer|min:1',
        ]);

        $currentEndDate = $subscription->end_date ?: now();
        
        $newEndDate = match($request->extend_type) {
            'days' => $currentEndDate->addDays($request->extend_amount),
            'weeks' => $currentEndDate->addWeeks($request->extend_amount),
            'months' => $currentEndDate->addMonths($request->extend_amount),
            'years' => $currentEndDate->addYears($request->extend_amount),
        };

        $subscription->update(['end_date' => $newEndDate]);

        return back()->with('success', 
            "Subscription extended by {$request->extend_amount} {$request->extend_type}. New end date: " . 
            $newEndDate->format('M d, Y')
        );
    }
}
