<?php

namespace Tests\Feature\Learner;

use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class LearnerSubscriptionParityTest extends TestCase
{
    use DatabaseTransactions;

    public function test_learner_subscription_pages_show_multi_duration_pricing_and_normalized_status_labels(): void
    {
        $this->withoutVite();
        $this->ensureNormalizedTables();

        $learner = User::withoutEvents(fn () => User::factory()->create([
            'role' => 'learner',
            'status' => 'active',
        ]));
        $learner->assignRole('learner');

        $plan = SubscriptionPlan::create([
            'name' => 'Premium Learner',
            'slug' => 'premium-learner-parity',
            'description' => 'Premium access',
            'price' => 499,
            'features' => [],
            'trial_days' => 0,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $plan->planPrices()->createMany([
            [
                'duration_mode' => 'preset',
                'duration_unit' => 'month',
                'duration_count' => 1,
                'duration_label' => 'Monthly',
                'amount_minor' => 49900,
                'currency' => 'PHP',
                'is_default' => true,
                'is_active' => true,
            ],
            [
                'duration_mode' => 'preset',
                'duration_unit' => 'year',
                'duration_count' => 1,
                'duration_label' => 'Yearly',
                'amount_minor' => 499900,
                'currency' => 'PHP',
                'is_default' => false,
                'is_active' => true,
            ],
        ]);

        Subscription::create([
            'user_id' => $learner->id,
            'plan_id' => $plan->id,
            'plan' => 'premium',
            'status' => 'active',
            'start_date' => now()->subDay(),
            'end_date' => now()->addMonth(),
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addMonth(),
            'price_paid' => 499,
            'auto_renew' => true,
        ]);

        $this->actingAs($learner)
            ->get(route('subscription.upgrade'))
            ->assertOk()
            ->assertSee('Monthly', false)
            ->assertSee('Yearly', false);

        $this->actingAs($learner)
            ->get(route('subscription.index'))
            ->assertOk()
            ->assertSee('Subscription Status', false)
            ->assertSee('Active', false);
    }

    private function ensureNormalizedTables(): void
    {
        if (Schema::hasTable('plan_prices') && Schema::hasTable('feature_catalog') && Schema::hasTable('plan_feature_entitlements')) {
            return;
        }

        $paths = [
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
