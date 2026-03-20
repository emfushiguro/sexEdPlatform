<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePlanRequest;
use App\Http\Requests\Admin\UpdatePlanRequest;
use App\Models\FeatureCatalog;
use App\Models\PlanFeatureEntitlement;
use App\Models\PlanPrice;
use App\Models\SubscriptionPlan;
use App\Services\AdminActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Handles subscription PLAN management only.
 * Subscriber management is handled by SubscriberAdminController.
 */
class UnifiedSubscriptionAdminController extends Controller
{

    public function createPlan()
    {
        return redirect()->route('admin.subscription-plans.index');
    }

    public function storePlan(StorePlanRequest $request)
    {
        $validated = $request->validated();

        $plan = DB::transaction(function () use ($validated) {
            $slug = $this->generateUniqueSlug($validated['name']);
            $sortOrder = $validated['sort_order'] ?? ((SubscriptionPlan::max('sort_order') ?? 0) + 10);

            $plan = SubscriptionPlan::create([
                'name' => $validated['name'],
                'slug' => $slug,
                'description' => $validated['description'] ?? null,
                'price' => 0,
                'features' => [],
                'trial_days' => $validated['trial_days'] ?? 0,
                'is_active' => (bool) ($validated['is_active'] ?? false),
                'sort_order' => $sortOrder,
            ]);

            $prices = $validated['prices'] ?? [];
            if (empty($prices) && array_key_exists('price', $validated)) {
                $prices[] = [
                    'duration_mode' => 'preset',
                    'duration_unit' => 'month',
                    'duration_count' => 1,
                    'duration_label' => 'Monthly',
                    'amount_minor' => (int) round(((float) $validated['price']) * 100),
                    'currency' => 'PHP',
                    'compare_at_minor' => null,
                    'is_default' => true,
                    'is_active' => true,
                ];
            }

            $defaultAmountMinor = 0;
            foreach ($prices as $index => $priceData) {
                $price = $plan->planPrices()->create([
                    'duration_mode' => $priceData['duration_mode'],
                    'duration_unit' => $priceData['duration_unit'],
                    'duration_count' => (int) $priceData['duration_count'],
                    'duration_label' => $priceData['duration_label'],
                    'amount_minor' => (int) $priceData['amount_minor'],
                    'currency' => strtoupper($priceData['currency'] ?? 'PHP'),
                    'compare_at_minor' => $priceData['compare_at_minor'] ?? null,
                    'is_default' => (bool) ($priceData['is_default'] ?? $index === 0),
                    'is_active' => (bool) ($priceData['is_active'] ?? true),
                ]);

                if ($price->is_default) {
                    $defaultAmountMinor = (int) $price->amount_minor;
                }
            }

            $enabledKeys = [];
            foreach ($validated['entitlements'] ?? [] as $row) {
                $feature = FeatureCatalog::firstOrCreate(
                    ['key' => $row['feature_key']],
                    [
                        'name' => $row['feature_name'] ?? Str::headline($row['feature_key']),
                        'description' => null,
                        'value_type' => $row['value_type'],
                        'unit_label' => $row['unit_label'] ?? null,
                        'category' => $row['category'] ?? 'general',
                        'is_active' => true,
                    ]
                );

                $isEnabled = (bool) ($row['is_enabled'] ?? false);
                if ($isEnabled) {
                    $enabledKeys[] = $feature->key;
                }

                PlanFeatureEntitlement::create([
                    'plan_id' => $plan->id,
                    'feature_id' => $feature->id,
                    'is_enabled' => $isEnabled,
                    'quota_value' => $row['quota_value'] ?? null,
                    'is_unlimited' => (bool) ($row['is_unlimited'] ?? false),
                ]);
            }

            $legacyFeatureKeys = array_values(array_unique(array_merge(
                $enabledKeys,
                array_values(array_filter(array_map('trim', $validated['feature_keys'] ?? [])))
            )));

            $plan->update([
                'price' => $defaultAmountMinor > 0 ? $defaultAmountMinor / 100 : 0,
                'features' => $legacyFeatureKeys,
            ]);

            return $plan;
        });

        app(AdminActivityLogService::class)->logModelMutation(
            action: 'plans.create',
            entity: $plan,
            before: null,
            after: $plan->fresh()->only(['id', 'name', 'slug', 'price', 'is_active']),
            meta: ['source' => 'admin.subscribers.store-plan'],
            request: $request,
        );

        return redirect()->route('admin.subscription-plans.index')
            ->with('success', 'Subscription plan created successfully!');
    }

    public function showPlan(SubscriptionPlan $subscriptionPlan)
    {
        $subscriptionPlan->load('subscriptions');
        return view('admin.subscription-plans.show', compact('subscriptionPlan'));
    }

    public function editPlan(SubscriptionPlan $subscriptionPlan)
    {
        return view('admin.subscriber.plan-edit', compact('subscriptionPlan'));
    }

    public function updatePlan(UpdatePlanRequest $request, SubscriptionPlan $subscriptionPlan)
    {
        $before = $subscriptionPlan->only(['id', 'name', 'slug', 'price', 'is_active', 'features']);
        $validated = $request->validated();

        DB::transaction(function () use ($subscriptionPlan, $validated) {
            $subscriptionPlan->update([
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'trial_days' => $validated['trial_days'] ?? 0,
                'is_active' => (bool) ($validated['is_active'] ?? false),
                'sort_order' => $validated['sort_order'] ?? $subscriptionPlan->sort_order,
            ]);

            if (array_key_exists('prices', $validated)) {
                $subscriptionPlan->planPrices()->delete();
                foreach ($validated['prices'] as $index => $priceData) {
                    $subscriptionPlan->planPrices()->create([
                        'duration_mode' => $priceData['duration_mode'],
                        'duration_unit' => $priceData['duration_unit'],
                        'duration_count' => (int) $priceData['duration_count'],
                        'duration_label' => $priceData['duration_label'],
                        'amount_minor' => (int) $priceData['amount_minor'],
                        'currency' => strtoupper($priceData['currency'] ?? 'PHP'),
                        'compare_at_minor' => $priceData['compare_at_minor'] ?? null,
                        'is_default' => (bool) ($priceData['is_default'] ?? $index === 0),
                        'is_active' => (bool) ($priceData['is_active'] ?? true),
                    ]);
                }
            }

            if (array_key_exists('entitlements', $validated)) {
                $subscriptionPlan->featureEntitlements()->delete();
                foreach ($validated['entitlements'] as $row) {
                    $feature = FeatureCatalog::firstOrCreate(
                        ['key' => $row['feature_key']],
                        [
                            'name' => $row['feature_name'] ?? Str::headline($row['feature_key']),
                            'description' => null,
                            'value_type' => $row['value_type'],
                            'unit_label' => $row['unit_label'] ?? null,
                            'category' => $row['category'] ?? 'general',
                            'is_active' => true,
                        ]
                    );

                    PlanFeatureEntitlement::create([
                        'plan_id' => $subscriptionPlan->id,
                        'feature_id' => $feature->id,
                        'is_enabled' => (bool) ($row['is_enabled'] ?? false),
                        'quota_value' => $row['quota_value'] ?? null,
                        'is_unlimited' => (bool) ($row['is_unlimited'] ?? false),
                    ]);
                }
            }

            if (array_key_exists('price', $validated)) {
                $subscriptionPlan->price = (float) $validated['price'];
            } elseif ($subscriptionPlan->planPrices()->exists()) {
                $default = $subscriptionPlan->planPrices()->where('is_default', true)->first()
                    ?? $subscriptionPlan->planPrices()->orderBy('id')->first();
                $subscriptionPlan->price = $default ? ((int) $default->amount_minor) / 100 : $subscriptionPlan->price;
            }

            $subscriptionPlan->features = array_values(array_unique(array_filter(array_map('trim', $validated['feature_keys'] ?? []))));
            $subscriptionPlan->save();
        });

        app(AdminActivityLogService::class)->logModelMutation(
            action: 'plans.update',
            entity: $subscriptionPlan,
            before: $before,
            after: $subscriptionPlan->fresh()->only(['id', 'name', 'slug', 'price', 'is_active', 'features']),
            meta: ['source' => 'admin.subscribers.update-plan'],
            request: $request,
        );

        return redirect()->route('admin.subscription-plans.index')
            ->with('success', 'Subscription plan updated successfully!');
    }

    private function generateUniqueSlug(string $name): string
    {
        $slug = Str::slug($name);
        $base = $slug;
        $i = 1;

        while (SubscriptionPlan::where('slug', $slug)->exists()) {
            $slug = $base . '-' . $i++;
        }

        return $slug;
    }

    // -------------------------------------------------------------------------
    // Quick Actions (plan-related only: toggle, delete, create test plan)
    // -------------------------------------------------------------------------

    public function quickAction(Request $request)
    {
        return match ($request->input('action')) {
            'toggle_plan'      => $this->togglePlan($request),
            'delete_plan'      => $this->deletePlan($request),
            'create_test_plan' => $this->createTestPlan($request),
            default            => redirect()->back()->with('error', 'Unknown action.'),
        };
    }

    private function togglePlan(Request $request)
    {
        $plan = SubscriptionPlan::findOrFail($request->plan_id);
        $before = $plan->only(['id', 'name', 'is_active']);

        if ($plan->is_active) {
            if ($plan->isFree()) {
                $count = \App\Models\User::where('role', 'learner')
                    ->whereDoesntHave('subscriptions', fn($q) =>
                        $q->where('status', 'active')->whereHas('plan', fn($p) => $p->where('price', '>', 0))
                    )->count();
                if ($count > 0) {
                    return redirect()->back()
                        ->with('error', "Cannot deactivate the Free Plan — {$count} learner(s) are using it.");
                }
            } elseif ($plan->subscriptions()->where('status', 'active')->exists()) {
                return redirect()->back()
                    ->with('error', 'Cannot deactivate this plan while it has active subscribers.');
            }
        }

        $plan->update(['is_active' => !$plan->is_active]);
        $status = $plan->fresh()->is_active ? 'activated' : 'deactivated';

        app(AdminActivityLogService::class)->logModelMutation(
            action: 'plans.toggle',
            entity: $plan,
            before: $before,
            after: $plan->fresh()->only(['id', 'name', 'is_active']),
            meta: ['source' => 'admin.subscribers.quick-action-plan', 'status' => $status],
            request: $request,
        );

        return redirect()->back()->with('success', "Plan {$status} successfully.");
    }

    private function deletePlan(Request $request)
    {
        $plan = SubscriptionPlan::findOrFail($request->plan_id);
        $before = $plan->only(['id', 'name', 'slug', 'price', 'is_active']);

        if ($plan->subscriptions()->where('status', 'active')->exists()) {
            return redirect()->back()
                ->with('error', 'Cannot delete a plan with active subscribers. Deactivate it first.');
        }

        $plan->delete();

        app(AdminActivityLogService::class)->log(
            action: 'plans.delete',
            entityType: SubscriptionPlan::class,
            entityId: $before['id'],
            before: $before,
            after: null,
            meta: ['source' => 'admin.subscribers.quick-action-plan'],
            request: $request,
        );

        return redirect()->back()->with('success', 'Plan deleted successfully.');
    }

    private function createTestPlan(Request $request)
    {
        $duration = (int) $request->get('duration', 10);

        SubscriptionPlan::create([
            'name'        => "Test Plan ({$duration} min)",
            'slug'        => 'test-' . $duration . 'min-' . time(),
            'description' => "Quick test plan that expires in {$duration} minutes.",
            'price'       => 1.00,
            'trial_days'  => 0,
            'features'    => ['test_mode', "duration_minutes:{$duration}", 'unlimited_quizzes', 'all_modules'],
            'is_active'   => true,
            'sort_order'  => 0,
        ]);

        return redirect()->route('admin.subscription-plans.index')
            ->with('success', "Test plan ({$duration} min) created.");
    }
}
