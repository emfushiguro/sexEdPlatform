<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionPlan;
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
            'limited_quiz_attempts' => 'boolean',
            'limited_modules_access' => 'boolean',
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

        $validated['features'] = $this->buildPlanFeatures(
            $request->input('feature_keys', []),
            $request->boolean('limited_quiz_attempts'),
            $request->boolean('limited_modules_access')
        );
        unset($validated['feature_keys'], $validated['limited_quiz_attempts'], $validated['limited_modules_access']);

        SubscriptionPlan::create($validated);

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
        $validated = $request->validate([
            'name'           => 'required|string|max:255',
            'description'    => 'nullable|string',
            'price'          => 'required|numeric|min:0',
            'trial_days'     => 'nullable|integer|min:0|max:365',
            'feature_keys'   => 'array',
            'feature_keys.*' => 'nullable|string|max:255',
            'limited_quiz_attempts' => 'boolean',
            'limited_modules_access' => 'boolean',
            'is_active'      => 'boolean',
            'sort_order'     => 'nullable|integer|min:0',
        ]);

        $validated['features'] = $this->buildPlanFeatures(
            $request->input('feature_keys', []),
            $request->boolean('limited_quiz_attempts'),
            $request->boolean('limited_modules_access'),
            $subscriptionPlan->features ?? []
        );
        unset($validated['feature_keys'], $validated['limited_quiz_attempts'], $validated['limited_modules_access']);

        $subscriptionPlan->update($validated);

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

        return redirect()->back()->with('success', "Plan {$status} successfully.");
    }

    private function deletePlan(Request $request)
    {
        $plan = SubscriptionPlan::findOrFail($request->plan_id);

        if ($plan->subscriptions()->where('status', 'active')->exists()) {
            return redirect()->back()
                ->with('error', 'Cannot delete a plan with active subscribers. Deactivate it first.');
        }

        $plan->delete();

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

    private function buildPlanFeatures(
        array $featureKeys,
        bool $limitedQuizAttempts,
        bool $limitedModulesAccess,
        array $existingFeatures = []
    ): array {
        $standardFeatureKeys = $this->getStandardFeatureKeys();

        $cleanedFeatureKeys = array_values(array_unique(array_filter(array_map(
            fn($feature) => is_string($feature) ? trim($feature) : '',
            $featureKeys
        ))));

        $existingCustomKeys = array_values(array_filter(
            $existingFeatures,
            fn($feature) => is_string($feature) && !in_array($feature, $standardFeatureKeys, true)
        ));

        $features = array_values(array_unique(array_merge($existingCustomKeys, $cleanedFeatureKeys)));

        if ($limitedQuizAttempts) {
            $features = array_values(array_filter($features, fn($feature) => $feature !== 'unlimited_quizzes'));
            $features[] = 'limited_quiz_attempts';
        } else {
            $features = array_values(array_filter($features, fn($feature) => $feature !== 'limited_quiz_attempts'));
        }

        if ($limitedModulesAccess) {
            $features = array_values(array_filter($features, fn($feature) => !in_array($feature, ['full_course_access', 'all_modules'], true)));
            $features[] = 'limited_modules_access';
        } else {
            $features = array_values(array_filter($features, fn($feature) => $feature !== 'limited_modules_access'));
        }

        return array_values(array_unique($features));
    }

    private function getStandardFeatureKeys(): array
    {
        $groupFeatureKeys = [];

        foreach (config('subscription_features.groups', []) as $group) {
            $groupFeatureKeys = array_merge($groupFeatureKeys, array_keys($group['features'] ?? []));
        }

        return array_values(array_unique(array_merge($groupFeatureKeys, [
            'limited_quiz_attempts',
            'limited_modules_access',
            'downloadable_content',
            'downloadable_resources',
            'progress_analytics',
            'all_modules',
        ])));
    }
}
