<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class UnifiedSubscriptionAdminController extends Controller
{
    /**
     * Show a single subscription (admin view)
     */
    public function showSubscription(Subscription $subscription)
    {
        // Eager load related models for admin view
        $subscription->load(['user', 'plan', 'payments']);
        $plans = SubscriptionPlan::active()->ordered()->get();
        $user = $subscription->user;
        $plan = $subscription->plan;
        // Optionally add stats or other admin data
        return view('admin.payments.subscription-details', compact('subscription', 'plans', 'user', 'plan'));
    }
    /**
     * Main subscription management dashboard
     */
    public function index(Request $request)
    {
        $tab = $request->get('tab', 'subscriptions');

        if ($tab === 'plans') {
            return $this->plansIndex($request);
        }

        return $this->subscriptionsIndex($request);
    }

    /**
     * Subscriptions management
     */
    private function subscriptionsIndex(Request $request)
    {
        $query = Subscription::with(['user', 'payments', 'plan']);

        // Apply filters
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
        $subscriptionStats = [
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

        // Plan statistics
        $planStats = [
            'total' => SubscriptionPlan::count(),
            'active' => SubscriptionPlan::where('is_active', true)->count(),
            'inactive' => SubscriptionPlan::where('is_active', false)->count(),
        ];
        
        return view('admin.subscriptions.index', compact(
            'subscriptions', 
            'plans', 
            'subscriptionStats', 
            'planStats'
        ));
    }

    /**
     * Plans management
     */
    private function plansIndex(Request $request)
    {
        $query = SubscriptionPlan::query();

        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->where('is_active', true);
            } elseif ($request->status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%'.$request->search.'%')
                  ->orWhere('description', 'like', '%'.$request->search.'%');
            });
        }

        $plans = $query->ordered()->paginate(15);
        
        $planStats = [
            'total' => SubscriptionPlan::count(),
            'active' => SubscriptionPlan::where('is_active', true)->count(),
            'inactive' => SubscriptionPlan::where('is_active', false)->count(),
        ];

        $subscriptionStats = [
            'total' => Subscription::count(),
            'active' => Subscription::where('status', 'active')->count(),
            'total_revenue' => Subscription::where('status', 'active')->sum('price_paid'),
        ];
        
        return view('admin.subscriptions.index', compact(
            'plans', 
            'planStats', 
            'subscriptionStats'
        ))->with('tab', 'plans');
    }

    /**
     * Create new subscription
     */
    public function createSubscription()
    {
        $users = User::whereDoesntHave('subscription', function ($query) {
            $query->where('status', 'active');
        })->orderBy('name')->get();
        
        $plans = SubscriptionPlan::active()->ordered()->get();
        
        // If accessed directly, redirect to index with modal open via session
        return redirect()->route('admin.subscriptions.index')
            ->with('openModal', 'createSubscription');
    }

    /**
     * Store new subscription
     */
    public function storeSubscription(Request $request)
    {
        $validated = $request->validate([
            'user_id'      => 'required|exists:users,id',
            'plan_id'      => 'required|exists:subscription_plans,id',
            'start_date'   => 'nullable|date|after_or_equal:today',
            'trial_days'   => 'nullable|integer|min:0|max:365',
            'custom_price' => 'nullable|numeric|min:0',
            'notes'        => 'nullable|string|max:500',
            'auto_renew'   => 'boolean',
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
        $endDate   = $trialDays > 0 ? $startDate->copy()->addDays($trialDays) : $startDate->copy()->addMonth();
        $price     = $validated['custom_price'] ?? $plan->price;

        // Override end date for test plans with custom duration_minutes feature
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
                'plan' => $plan->slug,
                'status' => $trialDays > 0 ? 'trialing' : 'active',
                'start_date' => $startDate,
                'end_date' => $endDate,
                'trial_ends_at' => $trialEnds,
                'price_paid' => $price,
                'auto_renew' => $validated['auto_renew'] ?? true,
            ]);

            // Create payment record
            $subscription->payments()->create([
                'user_id'         => $validated['user_id'],
                'amount'          => $price,
                'method'          => 'admin_created',
                'status'          => 'completed',
                'transaction_id'  => 'ADM-' . strtoupper(uniqid()),
                'payment_details' => [
                    'created_by_admin' => true,
                    'admin_notes'      => $validated['notes'],
                ],
                'paid_at' => now(),
            ]);

            DB::commit();
            return redirect()->route('admin.subscriptions.index')
                ->with('success', 'Subscription created successfully for ' . $user->name . '!');
        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()
                ->with('error', 'Failed to create subscription: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Handle quick actions (activate/cancel subscription, toggle/delete plan, create test plan)
     */
    public function quickAction(Request $request)
    {
        $action = $request->input('action');

        return match ($action) {
            'activate_subscription' => $this->activateSubscription($request),
            'cancel_subscription'   => $this->cancelSubscription($request),
            'toggle_plan'           => $this->togglePlan($request),
            'delete_plan'           => $this->deletePlan($request),
            'create_test_plan'      => $this->createQuickTestPlan($request),
            default                 => redirect()->back()->with('error', "Unknown action: {$action}"),
        };
    }

    private function createQuickTestPlan(Request $request)
    {
        $duration = $request->get('duration', 10);
        
        $testPlan = SubscriptionPlan::create([
            'name'         => "Test Plan ({$duration} Minutes)",
            'slug'         => "test-{$duration}min-" . time(),
            'description'  => "Quick test plan that expires in {$duration} minutes.",
            'price'        => 1.00,
            'trial_days'   => 0,
            'features'     => [
                'test_mode',
                'duration_minutes:' . $duration,
                'unlimited_quizzes',
                'all_modules',
            ],
            'is_active'    => true,
            'sort_order'   => 0,
        ]);

        return redirect()->route('admin.subscriptions.index', ['tab' => 'plans'])
            ->with('success', "Test plan ({$duration} minutes) created successfully!");
    }

    private function activateSubscription(Request $request)
    {
        $subscription = Subscription::findOrFail($request->subscription_id);

        try {
            // Use service so SubscriptionCreated event fires (invoice + email dispatched).
            app(\App\Services\SubscriptionService::class)->activate($subscription);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Activation failed: ' . $e->getMessage());
        }

        return redirect()->back()->with('success', 'Subscription activated successfully.');
    }

    private function cancelSubscription(Request $request)
    {
        $subscription = Subscription::findOrFail($request->subscription_id);
        $subscription->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'cancellation_reason' => 'Cancelled by admin',
        ]);
        
        return redirect()->back()->with('success', 'Subscription cancelled.');
    }

    private function togglePlan(Request $request)
    {
        $plan = SubscriptionPlan::findOrFail($request->plan_id);

        // Block deactivation if there are active subscribers
        if ($plan->is_active) {
            if ($plan->isFree()) {
                $freeLearnerCount = \App\Models\User::where('role', 'learner')
                    ->whereDoesntHave('subscriptions', function ($q) {
                        $q->where('status', 'active')
                          ->whereHas('plan', function ($p) {
                              $p->where('price', '>', 0);
                          });
                    })
                    ->count();
                if ($freeLearnerCount > 0) {
                    return redirect()->back()->with('error', "Cannot deactivate the Free Plan while it has {$freeLearnerCount} learner(s) using it.");
                }
            } elseif ($plan->subscriptions()->where('status', 'active')->exists()) {
                return redirect()->back()->with('error', 'Cannot deactivate this plan while it has active subscribers. Cancel or migrate their subscriptions first.');
            }
        }

        $plan->update([
            'is_active' => !$plan->is_active
        ]);

        $status = $plan->is_active ? 'activated' : 'deactivated';
        
        return redirect()->back()->with('success', "Plan has been {$status} successfully!");
    }

    private function deletePlan(Request $request)
    {
        $plan = SubscriptionPlan::findOrFail($request->plan_id);
        
        // Check if plan has active subscriptions
        if ($plan->subscriptions()->where('status', 'active')->exists()) {
            return redirect()->back()
                ->with('error', 'Cannot delete plan with active subscriptions. Deactivate it instead.');
        }

        $plan->delete();

        return redirect()->back()->with('success', 'Subscription plan deleted successfully!');
    }

    /**
     * Store a newly created plan
     */
    public function storePlan(Request $request)
    {
        $validated = $request->validate([
            'name'          => 'required|string|max:255',
            'description'   => 'nullable|string',
            'price'         => 'required|numeric|min:0',
            'trial_days'    => 'nullable|integer|min:0|max:365',
            'max_modules'   => 'nullable|integer|min:0',
            'feature_keys'  => 'array',
            'feature_keys.*'=> 'nullable|string|max:255',
            'is_active'     => 'boolean',
            'sort_order'    => 'nullable|integer|min:0',
        ]);

        // Generate unique slug from name
        $validated['slug'] = Str::slug($validated['name']);
        $originalSlug = $validated['slug'];
        $counter = 1;
        while (SubscriptionPlan::where('slug', $validated['slug'])->exists()) {
            $validated['slug'] = $originalSlug . '-' . $counter;
            $counter++;
        }

        if (empty($validated['sort_order'])) {
            $validated['sort_order'] = (SubscriptionPlan::max('sort_order') ?? 0) + 10;
        }

        // Process features as a simple array of strings
        $features = $request->input('feature_keys', []);
        $validated['features'] = array_values(array_unique(array_filter(array_map('trim', $features))));

        // Remove max_modules from validated — no longer used
        unset($validated['max_modules']);

        SubscriptionPlan::create($validated);

        return redirect()->route('admin.subscriptions.index', ['tab' => 'plans'])
            ->with('success', 'Subscription plan created successfully!');
    }

    /**
     * Update existing plan
     */
    public function updatePlan(Request $request, SubscriptionPlan $subscriptionPlan)
    {
        $validated = $request->validate([
            'name'          => 'required|string|max:255',
            'description'   => 'nullable|string',
            'price'         => 'required|numeric|min:0',
            'trial_days'    => 'nullable|integer|min:0|max:365',
            'max_modules'   => 'nullable|integer|min:0',
            'feature_keys'  => 'array',
            'feature_keys.*'=> 'nullable|string|max:255',
            'is_active'     => 'boolean',
            'sort_order'    => 'nullable|integer|min:0',
        ]);

        // Standard checkbox feature keys
        $standardFeatures = [
            'unlimited_quizzes', 'certificates', 'priority_support',
            'downloadable_content', 'consultations', 'offline_access', 'progress_analytics',
        ];

        // Preserve any custom (non-standard) features already saved on this plan
        $existingCustomFeatures = array_values(array_filter(
            $subscriptionPlan->features ?? [],
            fn($f) => !in_array($f, $standardFeatures)
        ));

        // Merge: custom preserved + new checkbox selections
        $checkedFeatures = array_values(array_filter(array_map('trim', $request->input('feature_keys', []))));
        $validated['features'] = array_values(array_unique(array_merge($existingCustomFeatures, $checkedFeatures)));

        // Remove max_modules from validated — no longer used
        unset($validated['max_modules']);

        // Update the plan
        $subscriptionPlan->update($validated);

        return redirect()->route('admin.subscriptions.index', ['tab' => 'plans'])
            ->with('success', 'Subscription plan updated successfully!');
    }
}