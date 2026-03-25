<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreSubscriptionPlanRequest;
use App\Http\Requests\Admin\UpdateSubscriptionPlanRequest;
use App\Models\FeatureCatalog;
use App\Models\SubscriptionPlan;
use App\Services\Admin\PlanLifecycleService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SubscriptionPlanAdminController extends Controller
{
    public function __construct(protected PlanLifecycleService $lifecycleService)
    {
    }

    public function index(Request $request)
    {
        $highlightPlanId = (int) $request->integer('highlight_plan');

        $query = SubscriptionPlan::query()
            ->with(['planPrices', 'featureEntitlements.feature'])
            ->notArchived();

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

        if ($highlightPlanId > 0) {
            $query->orderByRaw('CASE WHEN id = ? THEN 0 ELSE 1 END', [$highlightPlanId]);
        }

        $plans = $query->ordered()->paginate(15);

        $stats = [
            'total' => SubscriptionPlan::query()->notArchived()->count(),
            'active' => SubscriptionPlan::query()->notArchived()->where('is_active', true)->count(),
            'inactive' => SubscriptionPlan::query()->notArchived()->where('is_active', false)->count(),
            'archived' => SubscriptionPlan::query()->archived()->count(),
        ];

        return view('admin.subscription-plans.index', compact('plans', 'stats', 'highlightPlanId'));
    }

    public function archived(Request $request)
    {
        $query = SubscriptionPlan::query()->archived();

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%'.$request->search.'%')
                    ->orWhere('description', 'like', '%'.$request->search.'%');
            });
        }

        $plans = $query->latest('archived_at')->paginate(15);

        return view('admin.subscription-plans.archived', compact('plans'));
    }

    public function create()
    {
        return view('admin.subscription-plans.create');
    }

    public function store(StoreSubscriptionPlanRequest $request)
    {
        $validated = $request->validated();

        $priceRows = is_array($request->input('prices')) ? $request->input('prices') : [];
        $defaultAmountMinor = $this->resolveDefaultAmountMinor($priceRows);

        // Generate unique slug
        $validated['slug'] = Str::slug($validated['name']);
        $originalSlug = $validated['slug'];
        $counter = 1;
        while (SubscriptionPlan::where('slug', $validated['slug'])->exists()) {
            $validated['slug'] = $originalSlug . '-' . $counter;
            $counter++;
        }

        // Set sort order if not provided
        if (empty($validated['sort_order'])) {
            $validated['sort_order'] = 0;
        }

        // Handle legacy features (keep for backward compatibility but might be empty)
        $features = $request->input('feature_keys', []);
        $featureNames = array_values(array_unique(array_filter(array_map('trim', $features))));
        $validated['features'] = $featureNames;
        $validated['price'] = $defaultAmountMinor > 0 ? $defaultAmountMinor / 100 : 0;
        $validated['trial_days'] = 0;

        // Create the plan
        $plan = SubscriptionPlan::create($validated);

        // Create plan prices
        if ($request->has('prices') && is_array($request->prices)) {
            foreach ($request->prices as $priceData) {
                $plan->planPrices()->create([
                    'duration_mode' => $priceData['duration_mode'] ?? 'preset',
                    'duration_unit' => $priceData['duration_unit'] ?? 'month',
                    'duration_count' => $priceData['duration_count'] ?? 1,
                    'duration_label' => $priceData['duration_label'] ?? 'Monthly',
                    'amount_minor' => (int) ($priceData['amount_minor'] ?? 0),
                    'currency' => $priceData['currency'] ?? 'PHP',
                    'compare_at_minor' => $priceData['compare_at_minor'] ?? null,
                    'is_default' => (bool) ($priceData['is_default'] ?? false),
                    'is_active' => (bool) ($priceData['is_active'] ?? true),
                ]);
            }
        }

        // Create plan entitlements
        if ($request->has('entitlements') && is_array($request->entitlements)) {
            foreach ($request->entitlements as $entitlementData) {
                // Skip if not enabled
                if (empty($entitlementData['is_enabled'])) {
                    continue;
                }

                // Find or create the feature in catalog
                $feature = FeatureCatalog::firstOrCreate(
                    ['key' => $entitlementData['feature_key']],
                    [
                        'name' => $entitlementData['feature_name'] ?? $entitlementData['feature_key'],
                        'description' => $entitlementData['description'] ?? null,
                        'value_type' => $entitlementData['value_type'] ?? 'boolean',
                        'unit_label' => $entitlementData['unit_label'] ?? null,
                        'category' => $entitlementData['category'] ?? 'general',
                        'is_active' => true,
                    ]
                );

                // Create the entitlement
                $plan->featureEntitlements()->create([
                    'feature_id' => $feature->id,
                    'is_enabled' => true,
                    'quota_value' => $entitlementData['quota_value'] ?? null,
                    'is_unlimited' => (bool) ($entitlementData['is_unlimited'] ?? false),
                ]);
            }
        }

        return redirect()->route('admin.subscription-plans.index', ['highlight_plan' => $plan->id])
            ->with('success', 'Subscription plan created successfully!');
    }

    public function show(SubscriptionPlan $subscriptionPlan)
    {
        $subscriptionPlan->load(['subscriptions' => function ($query) {
            $query->with('user')->latest();
        }]);

        $stats = [
            'total_subscribers' => $subscriptionPlan->subscriptions()->count(),
            'active_subscribers' => $subscriptionPlan->subscriptions()->where('status', 'active')->count(),
            'monthly_revenue' => $subscriptionPlan->subscriptions()
                ->where('status', 'active')
                ->sum('price_paid'),
        ];

        return view('admin.subscription-plans.show', compact('subscriptionPlan', 'stats'));
    }

    public function edit(SubscriptionPlan $subscriptionPlan)
    {
        return view('admin.subscription-plans.edit', compact('subscriptionPlan'));
    }

    public function update(UpdateSubscriptionPlanRequest $request, SubscriptionPlan $subscriptionPlan)
    {
        $validated = $request->validated();

        $priceRows = is_array($request->input('prices')) ? $request->input('prices') : [];
        $defaultAmountMinor = $this->resolveDefaultAmountMinor($priceRows);

        // Update slug if name changed
        if ($subscriptionPlan->name !== $validated['name']) {
            $newSlug = Str::slug($validated['name']);
            $originalSlug = $newSlug;
            $counter = 1;
            while (SubscriptionPlan::where('slug', $newSlug)
                    ->where('id', '!=', $subscriptionPlan->id)
                    ->exists()) {
                $newSlug = $originalSlug . '-' . $counter;
                $counter++;
            }
            $validated['slug'] = $newSlug;
        }

        // Handle legacy features
        $features = $request->input('feature_keys', []);
        $featureNames = array_values(array_unique(array_filter(array_map('trim', $features))));
        $validated['features'] = $featureNames;
        $validated['trial_days'] = 0;

        if (!empty($priceRows)) {
            $validated['price'] = $defaultAmountMinor > 0 ? $defaultAmountMinor / 100 : 0;
        }

        // Update the plan
        $subscriptionPlan->update($validated);

        // Sync plan prices
        if ($request->has('prices') && is_array($request->prices)) {
            // Delete existing prices and recreate (simpler than complex sync logic)
            $subscriptionPlan->planPrices()->delete();

            foreach ($request->prices as $priceData) {
                $subscriptionPlan->planPrices()->create([
                    'duration_mode' => $priceData['duration_mode'] ?? 'preset',
                    'duration_unit' => $priceData['duration_unit'] ?? 'month',
                    'duration_count' => $priceData['duration_count'] ?? 1,
                    'duration_label' => $priceData['duration_label'] ?? 'Monthly',
                    'amount_minor' => (int) ($priceData['amount_minor'] ?? 0),
                    'currency' => $priceData['currency'] ?? 'PHP',
                    'compare_at_minor' => $priceData['compare_at_minor'] ?? null,
                    'is_default' => (bool) ($priceData['is_default'] ?? false),
                    'is_active' => (bool) ($priceData['is_active'] ?? true),
                ]);
            }
        }

        // Sync plan entitlements
        if ($request->has('entitlements') && is_array($request->entitlements)) {
            // Delete existing entitlements first
            $subscriptionPlan->featureEntitlements()->delete();

            foreach ($request->entitlements as $entitlementData) {
                // Skip if not enabled
                if (empty($entitlementData['is_enabled'])) {
                    continue;
                }

                // Find or create the feature in catalog
                $feature = FeatureCatalog::firstOrCreate(
                    ['key' => $entitlementData['feature_key']],
                    [
                        'name' => $entitlementData['feature_name'] ?? $entitlementData['feature_key'],
                        'description' => $entitlementData['description'] ?? null,
                        'value_type' => $entitlementData['value_type'] ?? 'boolean',
                        'unit_label' => $entitlementData['unit_label'] ?? null,
                        'category' => $entitlementData['category'] ?? 'general',
                        'is_active' => true,
                    ]
                );

                // Create the entitlement
                $subscriptionPlan->featureEntitlements()->create([
                    'feature_id' => $feature->id,
                    'is_enabled' => true,
                    'quota_value' => $entitlementData['quota_value'] ?? null,
                    'is_unlimited' => (bool) ($entitlementData['is_unlimited'] ?? false),
                ]);
            }
        }

        return redirect()->route('admin.subscription-plans.show', $subscriptionPlan)
            ->with('success', 'Subscription plan updated successfully!');
    }

    public function destroy(SubscriptionPlan $subscriptionPlan)
    {
        // Check if plan has active subscriptions
        if ($subscriptionPlan->subscriptions()->where('status', 'active')->exists()) {
            return redirect()->back()
                ->with('error', 'Cannot delete plan with active subscriptions. Deactivate it instead.');
        }

        $subscriptionPlan->delete();

        return redirect()->route('admin.subscription-plans.index')
            ->with('success', 'Subscription plan deleted successfully!');
    }

    public function toggle(SubscriptionPlan $subscriptionPlan)
    {
        if ($subscriptionPlan->is_active) {
            $subscriptionPlan = $this->lifecycleService->deactivate($subscriptionPlan);
        } else {
            $subscriptionPlan = $this->lifecycleService->activate($subscriptionPlan);
        }

        $status = $subscriptionPlan->is_active ? 'activated' : 'deactivated';
        
        return redirect()->back()
            ->with('success', "Plan has been {$status} successfully!");
    }

    public function duplicate(SubscriptionPlan $subscriptionPlan)
    {
        $newPlan = $subscriptionPlan->replicate();
        $newPlan->name = $subscriptionPlan->name . ' (Copy)';
        $newPlan->slug = Str::slug($newPlan->name);
        $newPlan->is_active = false; // Duplicated plans start as inactive
        $newPlan->sort_order = SubscriptionPlan::max('sort_order') + 10;
        
        // Ensure slug is unique
        $originalSlug = $newPlan->slug;
        $counter = 1;
        while (SubscriptionPlan::where('slug', $newPlan->slug)->exists()) {
            $newPlan->slug = $originalSlug . '-' . $counter;
            $counter++;
        }
        
        $newPlan->save();

        return redirect()->route('admin.subscription-plans.edit', $newPlan)
            ->with('success', 'Plan duplicated successfully! Please review and update the details.');
    }

    public function reorder(Request $request)
    {
        $request->validate([
            'plan_ids' => 'required|array',
            'plan_ids.*' => 'exists:subscription_plans,id',
        ]);

        foreach ($request->plan_ids as $index => $planId) {
            SubscriptionPlan::where('id', $planId)->update([
                'sort_order' => ($index + 1) * 10
            ]);
        }

        return response()->json(['success' => true]);
    }

    public function impact(SubscriptionPlan $subscriptionPlan)
    {
        return response()->json([
            'data' => $this->lifecycleService->impactSnapshot($subscriptionPlan),
        ]);
    }

    public function archive(SubscriptionPlan $subscriptionPlan)
    {
        $this->lifecycleService->archive($subscriptionPlan);

        return redirect()->back()->with('success', 'Plan archived successfully.');
    }

    public function restore(SubscriptionPlan $subscriptionPlan)
    {
        $this->lifecycleService->restore($subscriptionPlan);

        return redirect()->back()->with('success', 'Plan restored as inactive.');
    }

    /**
     * Get features from catalog (API endpoint for plan wizard)
     */
    public function getFeatures(Request $request)
    {
        $features = FeatureCatalog::query()
            ->where('is_active', true)
            ->orderBy('category')
            ->orderBy('name')
            ->get()
            ->map(function ($feature) {
                return [
                    'id' => $feature->id,
                    'key' => $feature->key,
                    'name' => $feature->name,
                    'description' => $feature->description,
                    'value_type' => $feature->value_type,
                    'unit_label' => $feature->unit_label,
                    'category' => $feature->category,
                ];
            });

        return response()->json(['features' => $features]);
    }

    private function resolveDefaultAmountMinor(array $priceRows): int
    {
        if (empty($priceRows)) {
            return 0;
        }

        foreach ($priceRows as $priceRow) {
            if (!empty($priceRow['is_default'])) {
                return (int) ($priceRow['amount_minor'] ?? 0);
            }
        }

        return (int) ($priceRows[0]['amount_minor'] ?? 0);
    }

    /**
     * Create a test subscription plan for 10 minutes
     */
    public function createTestPlan()
    {
        $testPlan = SubscriptionPlan::create([
            'name' => 'Test Plan (10 Minutes)',
            'slug' => 'test-10min-' . time(),
            'description' => 'Test subscription plan that expires in 10 minutes. For testing purposes only.',
            'price' => 1.00,
            'trial_days' => 0,
            'max_modules' => 999,
            'features' => [
                'test_mode' => true,
                'duration_minutes' => 10,
                'unlimited_quizzes' => true,
                'all_modules' => true,
            ],
            'is_active' => true,
            'sort_order' => 0, // Show at top
        ]);

        return redirect()->route('admin.subscription-plans.show', $testPlan)
            ->with('success', 'Test plan created! Users can now subscribe for 10-minute testing.');
    }
}