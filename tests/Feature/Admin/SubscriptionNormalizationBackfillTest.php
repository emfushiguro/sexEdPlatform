<?php

namespace Tests\Feature\Admin;

use App\Models\PlanFeatureEntitlement;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class SubscriptionNormalizationBackfillTest extends TestCase
{
    public function test_normalization_seeder_backfills_plan_prices_features_and_subscription_fields(): void
    {
        $this->withoutVite();
        $this->ensureNormalizedTables();

        $plan = SubscriptionPlan::create([
            'name' => 'Premium Monthly',
            'slug' => 'premium-monthly-seed-' . Str::random(8),
            'description' => 'Premium access',
            'price' => 129.00,
            'features' => [
                'learning' => [
                    'full_course_access' => true,
                ],
                'assessment' => [
                    'unlimited_quizzes' => 'unlimited',
                ],
            ],
            'trial_days' => 0,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $user = User::factory()->create();

        $subscription = Subscription::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'plan' => 'premium',
            'status' => 'active',
            'start_date' => now()->subDay(),
            'end_date' => now()->addMonth(),
            'price_paid' => 129,
            'auto_renew' => true,
        ]);

        (new \Database\Seeders\SubscriptionNormalizationSeeder())->run();

        $this->assertDatabaseHas('feature_catalog', ['key' => 'full_course_access']);
        $this->assertDatabaseHas('feature_catalog', ['key' => 'unlimited_quizzes']);

        $entitlement = PlanFeatureEntitlement::query()
            ->where('plan_id', $plan->id)
            ->whereHas('feature', fn ($query) => $query->where('key', 'unlimited_quizzes'))
            ->first();

        $this->assertNotNull($entitlement);
        $this->assertTrue($entitlement->is_enabled);
        $this->assertTrue($entitlement->is_unlimited);

        $this->assertNotNull($plan->fresh()->planPrices()->where('is_default', true)->first());

        $subscription->refresh();
        $this->assertNotNull($subscription->plan_price_id);
        $this->assertNotNull($subscription->starts_at);
        $this->assertNotNull($subscription->ends_at);
    }

    private function ensureNormalizedTables(): void
    {
        if (
            Schema::hasTable('plan_prices')
            && Schema::hasTable('feature_catalog')
            && Schema::hasTable('plan_feature_entitlements')
            && Schema::hasColumn('subscribers', 'plan_price_id')
        ) {
            return;
        }

        $paths = [
            'database/migrations/2026_02_17_000001_create_subscription_plans_table.php',
            'database/migrations/2026_03_19_100001_create_plan_prices_table.php',
            'database/migrations/2026_03_19_100002_create_feature_catalog_table.php',
            'database/migrations/2026_03_19_100003_create_plan_feature_entitlements_table.php',
            'database/migrations/2026_03_19_100004_add_normalized_columns_to_subscribers_table.php',
        ];

        foreach ($paths as $path) {
            Artisan::call('migrate', ['--path' => $path, '--force' => true]);
        }
    }
}
