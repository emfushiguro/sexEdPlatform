<?php

namespace Database\Seeders;

use App\Models\SubscriptionPlan;
use Illuminate\Database\Seeder;

class SubscriptionPlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Free Plan',
                'slug' => 'free',
                'description' => 'Basic access to learning materials with limited features',
                'price' => 0.00,
                'features' => [
                    'modules' => 3,
                    'quizzes' => true,
                    'certificates' => false,
                    'support' => 'community',
                    'consultations' => 0,
                    'downloadable_resources' => false,
                    'priority_support' => false,
                ],
                'trial_days' => 0,
                'max_users' => 1,
                'max_modules' => 3,
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'name' => 'Premium Plan',
                'slug' => 'premium',
                'description' => 'Unlimited access to all features with priority support',
                'price' => 299.00,
                'features' => [
                    'modules' => 'unlimited',
                    'quizzes' => true,
                    'certificates' => true,
                    'support' => 'priority',
                    'consultations' => 5,
                    'downloadable_resources' => true,
                    'offline_access' => true,
                    'progress_analytics' => true,
                    'priority_support' => true,
                ],
                'trial_days' => 14,
                'max_users' => 1,
                'max_modules' => null, // unlimited
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'name' => 'Organization Plan',
                'slug' => 'organization',
                'description' => 'Multi-user plan for educational institutions and organizations',
                'price' => 999.00,
                'features' => [
                    'modules' => 'unlimited',
                    'users' => 50,
                    'admin_dashboard' => true,
                    'progress_tracking' => true,
                    'bulk_enrollment' => true,
                    'custom_branding' => true,
                    'api_access' => true,
                    'priority_support' => true,
                    'dedicated_account_manager' => true,
                    'custom_reporting' => true,
                ],
                'trial_days' => 30,
                'max_users' => 50,
                'max_modules' => null, // unlimited
                'is_active' => true,
                'sort_order' => 3,
            ],
        ];

        foreach ($plans as $planData) {
            SubscriptionPlan::updateOrCreate(
                ['slug' => $planData['slug']],
                $planData
            );
        }

        $this->command->info('Subscription plans seeded successfully!');
    }
}