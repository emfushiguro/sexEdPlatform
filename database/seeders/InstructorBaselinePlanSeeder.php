<?php

namespace Database\Seeders;

use App\Models\FeatureCatalog;
use App\Models\PlanFeatureEntitlement;
use App\Models\SubscriptionPlan;
use App\Support\SubscriptionFeatureKeys;
use Illuminate\Database\Seeder;

class InstructorBaselinePlanSeeder extends Seeder
{
    public function run(): void
    {
        $plan = SubscriptionPlan::query()->updateOrCreate(
            ['slug' => 'instructor-free-plan'],
            [
                'name' => 'Free Plan',
                'description' => 'Default instructor baseline access without premium checkout.',
                'price' => 0,
                'features' => [],
                'plan_audience' => 'instructor',
                'billing_mode' => 'monthly',
                'trial_days' => 0,
                'is_active' => true,
                'sort_order' => 1,
            ]
        );

        $defaults = [
            [
                'key' => SubscriptionFeatureKeys::INSTRUCTOR_PUBLISHED_MODULES_LIMIT,
                'name' => 'Published Modules Limit',
                'value_type' => 'quota',
                'unit_label' => 'modules',
                'is_enabled' => true,
                'quota_value' => 3,
                'is_unlimited' => false,
            ],
            [
                'key' => SubscriptionFeatureKeys::INSTRUCTOR_MAX_LEARNERS_PER_FREE_MODULE,
                'name' => 'Free Module Learner Cap',
                'value_type' => 'quota',
                'unit_label' => 'learners',
                'is_enabled' => true,
                'quota_value' => 100,
                'is_unlimited' => false,
            ],
            [
                'key' => SubscriptionFeatureKeys::INSTRUCTOR_MAX_LEARNERS_PER_PAID_MODULE,
                'name' => 'Paid Module Learner Cap',
                'value_type' => 'quota',
                'unit_label' => 'learners',
                'is_enabled' => true,
                'quota_value' => 100,
                'is_unlimited' => false,
            ],
            [
                'key' => SubscriptionFeatureKeys::INSTRUCTOR_CAN_PUBLISH_PAID_MODULES,
                'name' => 'Can Publish Paid Modules',
                'value_type' => 'boolean',
                'unit_label' => null,
                'is_enabled' => true,
                'quota_value' => null,
                'is_unlimited' => false,
            ],
            [
                'key' => SubscriptionFeatureKeys::INSTRUCTOR_CAN_RECEIVE_PAID_ENROLLMENTS,
                'name' => 'Can Receive Paid Enrollments',
                'value_type' => 'boolean',
                'unit_label' => null,
                'is_enabled' => true,
                'quota_value' => null,
                'is_unlimited' => false,
            ],
            [
                'key' => SubscriptionFeatureKeys::INSTRUCTOR_CAN_VIEW_EARNINGS,
                'name' => 'Can View Earnings',
                'value_type' => 'boolean',
                'unit_label' => null,
                'is_enabled' => true,
                'quota_value' => null,
                'is_unlimited' => false,
            ],
        ];

        foreach ($defaults as $definition) {
            $feature = FeatureCatalog::query()->updateOrCreate(
                ['key' => $definition['key']],
                [
                    'name' => $definition['name'],
                    'description' => $definition['name'],
                    'value_type' => $definition['value_type'],
                    'unit_label' => $definition['unit_label'],
                    'category' => 'instructor',
                    'is_active' => true,
                ]
            );

            PlanFeatureEntitlement::query()->updateOrCreate(
                [
                    'plan_id' => $plan->id,
                    'feature_id' => $feature->id,
                ],
                [
                    'is_enabled' => (bool) $definition['is_enabled'],
                    'quota_value' => $definition['quota_value'],
                    'is_unlimited' => (bool) $definition['is_unlimited'],
                ]
            );
        }
    }
}
