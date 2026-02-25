<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionPlan;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SubscriptionPlanAdminController extends Controller
{
    public function index(Request $request)
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
        
        $stats = [
            'total' => SubscriptionPlan::count(),
            'active' => SubscriptionPlan::where('is_active', true)->count(),
            'inactive' => SubscriptionPlan::where('is_active', false)->count(),
        ];
        
        return view('admin.subscription-plans.index', compact('plans', 'stats'));
    }

    public function create()
    {
        return view('admin.subscription-plans.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'trial_days' => 'nullable|integer|min:0|max:365',
            'max_modules' => 'nullable|integer|min:0',
            'feature_keys' => 'array',
            'feature_keys.*' => 'nullable|string|max:255',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $validated['slug'] = Str::slug($validated['name']);
        $originalSlug = $validated['slug'];
        $counter = 1;
        while (SubscriptionPlan::where('slug', $validated['slug'])->exists()) {
            $validated['slug'] = $originalSlug . '-' . $counter;
            $counter++;
        }
        if (empty($validated['sort_order'])) {
            $validated['sort_order'] = SubscriptionPlan::max('sort_order') + 10;
        }

        // Process features as simple array of strings
        $features = $request->input('feature_keys', []);
        $featureNames = array_values(array_unique(array_filter(array_map('trim', $features))));
        $validated['features'] = $featureNames;

        SubscriptionPlan::create($validated);

        return redirect()->route('admin.subscription-plans.index')
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

    public function update(Request $request, SubscriptionPlan $subscriptionPlan)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'trial_days' => 'nullable|integer|min:0|max:365',
            'max_modules' => 'nullable|integer|min:0',
            'feature_keys' => 'array',
            'feature_keys.*' => 'nullable|string|max:255',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);

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

        // Process features as simple array of strings
        $features = $request->input('feature_keys', []);
        $featureNames = array_values(array_unique(array_filter(array_map('trim', $features))));
        $validated['features'] = $featureNames;

        $subscriptionPlan->update($validated);

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
        $subscriptionPlan->update([
            'is_active' => !$subscriptionPlan->is_active
        ]);

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