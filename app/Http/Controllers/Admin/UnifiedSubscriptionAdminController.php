<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionPlan;
use App\Services\AdminActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Handles subscription PLAN management only.
 * Subscriber management is handled by SubscriberAdminController.
 */
class UnifiedSubscriptionAdminController extends Controller
{

    public function createPlan()
    {
        return view('admin.subscription-plans.create');
    }

    public function storePlan(Request $request)
    {
        $validated = $request->validate([
            'name'           => 'required|string|max:255',
            'description'    => 'nullable|string',
            'price'          => 'required|numeric|min:0',
            'trial_days'     => 'nullable|integer|min:0|max:365',
            'feature_keys'   => 'array',
            'feature_keys.*' => 'nullable|string|max:255',
            'is_active'      => 'boolean',
            'sort_order'     => 'nullable|integer|min:0',
        ]);

        $validated['slug'] = Str::slug($validated['name']);
        $base = $validated['slug'];
        $i = 1;
        while (SubscriptionPlan::where('slug', $validated['slug'])->exists()) {
            $validated['slug'] = $base . '-' . $i++;
        }

        if (empty($validated['sort_order'])) {
            $validated['sort_order'] = (SubscriptionPlan::max('sort_order') ?? 0) + 10;
        }

        $validated['features'] = array_values(array_unique(array_filter(
            array_map('trim', $request->input('feature_keys', []))
        )));

        $plan = SubscriptionPlan::create($validated);

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
        return view('admin.subscription-plans.edit', compact('subscriptionPlan'));
    }

    public function updatePlan(Request $request, SubscriptionPlan $subscriptionPlan)
    {
        $before = $subscriptionPlan->only(['id', 'name', 'slug', 'price', 'is_active', 'features']);

        $validated = $request->validate([
            'name'           => 'required|string|max:255',
            'description'    => 'nullable|string',
            'price'          => 'required|numeric|min:0',
            'trial_days'     => 'nullable|integer|min:0|max:365',
            'feature_keys'   => 'array',
            'feature_keys.*' => 'nullable|string|max:255',
            'is_active'      => 'boolean',
            'sort_order'     => 'nullable|integer|min:0',
        ]);

        // Preserve non-standard custom feature keys already saved on the plan
        $standardFeatures = [
            'full_course_access', 'offline_access', 'expert_video_sessions', 'exclusive_content',
            'unlimited_quizzes', 'certificates', 'advanced_analytics',
            'downloadable_materials', 'anonymous_qa', 'private_community',
            'priority_support', 'ad_free',
            // legacy compat
            'downloadable_content', 'downloadable_resources', 'progress_analytics', 'all_modules',
        ];

        $existingCustom = [];
        $existing = $subscriptionPlan->features ?? [];
        if (!empty($existing) && is_array($existing) && is_string(reset($existing))) {
            $existingCustom = array_values(array_filter(
                $existing,
                fn($f) => is_string($f) && !in_array($f, $standardFeatures)
            ));
        }

        $checked = array_values(array_filter(array_map('trim', $request->input('feature_keys', []))));
        $validated['features'] = array_values(array_unique(array_merge($existingCustom, $checked)));

        $subscriptionPlan->update($validated);

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
