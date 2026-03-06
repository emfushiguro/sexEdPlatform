<?php

namespace Database\Seeders;

use App\Models\SubscriptionPlan;
use Illuminate\Database\Seeder;

class SubscriptionPlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [

            // ─────────────────────────────────────────────────────
            // 1. BASIC — Free Plan
            // ─────────────────────────────────────────────────────
            [
                'name'        => 'Basic',
                'slug'        => 'basic',
                'description' => 'Start learning for free with limited access. Perfect for exploring the platform.',
                'price'       => 0.00,
                'trial_days'  => 0,
                'max_users'   => 1,
                'max_modules' => 5,
                'is_active'   => true,
                'sort_order'  => 1,
                'features'    => [

                    // ── Learning Access ──
                    'learning' => [
                        'module_access'          => 'limited',      // only 3–5 lessons
                        'max_modules'            => 5,
                        'full_course_access'     => false,
                        'offline_access'         => false,
                        'expert_video_sessions'  => false,
                        'exclusive_content'      => false,
                        'parental_guidance_resources' => false,
                        'age_based_content_filtering' => true,
                        'mental_health_resources'     => false,
                    ],

                    // ── Assessment ──
                    'assessment' => [
                        'quiz_attempts'       => 'limited',     // 3 per day
                        'max_quiz_per_day'    => 3,
                        'certificates'        => false,
                        'progress_analytics'  => 'basic',
                    ],

                    // ── Content & Resources ──
                    'content' => [
                        'downloadable_materials' => false,      // no PDFs
                        'anonymous_qa'           => false,
                    ],

                    // ── Community ──
                    'community' => [
                        'forum_access'      => 'read_only',     // cannot post
                        'private_community' => false,
                    ],

                    // ── Support ──
                    'support' => [
                        'type'             => 'standard',       // email support
                        'priority_support' => false,
                    ],

                    // ── Experience ──
                    'experience' => [
                        'ads_enabled' => true,
                    ],

                    // ── Future / VIP Reserved ──
                    'future' => [
                        'appointment_booking' => false,
                        'ai_chatbot'          => false,
                    ],
                ],
            ],

            // ─────────────────────────────────────────────────────
            // 2. PREMIUM MONTHLY — ₱129 / month
            // ─────────────────────────────────────────────────────
            [
                'name'        => 'Premium Monthly',
                'slug'        => 'premium-monthly',
                'description' => 'Full unlimited access to all learning modules, quizzes, and premium features. Billed monthly at ₱129.',
                'price'       => 129.00,
                'trial_days'  => 0,
                'max_users'   => 1,
                'max_modules' => null,    // unlimited
                'is_active'   => true,
                'sort_order'  => 2,
                'features'    => [

                    // ── Learning Access ──
                    'learning' => [
                        'module_access'               => 'unlimited',
                        'max_modules'                 => null,
                        'full_course_access'          => true,
                        'offline_access'              => true,
                        'expert_video_sessions'       => true,
                        'exclusive_content'           => true,
                        'parental_guidance_resources' => true,
                        'age_based_content_filtering' => true,
                        'mental_health_resources'     => true,
                    ],

                    // ── Assessment ──
                    'assessment' => [
                        'quiz_attempts'      => 'unlimited',
                        'max_quiz_per_day'   => null,
                        'certificates'       => true,
                        'progress_analytics' => 'advanced',
                    ],

                    // ── Content & Resources ──
                    'content' => [
                        'downloadable_materials' => true,       // PDFs
                        'anonymous_qa'           => true,
                    ],

                    // ── Community ──
                    'community' => [
                        'forum_access'      => 'full',
                        'private_community' => true,
                    ],

                    // ── Support ──
                    'support' => [
                        'type'             => 'priority',
                        'priority_support' => true,
                    ],

                    // ── Experience ──
                    'experience' => [
                        'ads_enabled' => false,
                    ],

                    // ── Future / VIP Reserved ──
                    'future' => [
                        'appointment_booking' => false,
                        'ai_chatbot'          => false,
                    ],
                ],
            ],

            // ─────────────────────────────────────────────────────
            // 3. PREMIUM ANNUAL — ₱1,299 / year  (~₱108.25/mo)
            //    Same features as Monthly — discounted billing only
            // ─────────────────────────────────────────────────────
            [
                'name'        => 'Premium Annual',
                'slug'        => 'premium-annual',
                'description' => 'Everything in Premium Monthly — save ₱249 by paying annually. Billed once at ₱1,299/year.',
                'price'       => 1299.00,
                'trial_days'  => 0,
                'max_users'   => 1,
                'max_modules' => null,    // unlimited
                'is_active'   => true,
                'sort_order'  => 3,
                'features'    => [

                    // ── Learning Access ──
                    'learning' => [
                        'module_access'               => 'unlimited',
                        'max_modules'                 => null,
                        'full_course_access'          => true,
                        'offline_access'              => true,
                        'expert_video_sessions'       => true,
                        'exclusive_content'           => true,
                        'parental_guidance_resources' => true,
                        'age_based_content_filtering' => true,
                        'mental_health_resources'     => true,
                    ],

                    // ── Assessment ──
                    'assessment' => [
                        'quiz_attempts'      => 'unlimited',
                        'max_quiz_per_day'   => null,
                        'certificates'       => true,
                        'progress_analytics' => 'advanced',
                    ],

                    // ── Content & Resources ──
                    'content' => [
                        'downloadable_materials' => true,
                        'anonymous_qa'           => true,
                    ],

                    // ── Community ──
                    'community' => [
                        'forum_access'      => 'full',
                        'private_community' => true,
                    ],

                    // ── Support ──
                    'support' => [
                        'type'             => 'priority',
                        'priority_support' => true,
                    ],

                    // ── Experience ──
                    'experience' => [
                        'ads_enabled' => false,
                    ],

                    // ── Future / VIP Reserved ──
                    'future' => [
                        'appointment_booking' => false,
                        'ai_chatbot'          => false,
                    ],
                ],
            ],

            // ─────────────────────────────────────────────────────
            // 4. PREMIUM PLUS — ₱1,999 / year (ALL features unlocked)
            // ─────────────────────────────────────────────────────
            [
                'name'        => 'Premium Plus',
                'slug'        => 'premium-plus',
                'description' => 'The ultimate all-access plan. Every feature unlocked — including AI Chatbot and expert appointment booking.',
                'price'       => 1999.00,
                'trial_days'  => 0,
                'max_users'   => 1,
                'max_modules' => null,
                'is_active'   => true,
                'sort_order'  => 4,
                'features'    => [
                    'full_course_access',
                    'offline_access',
                    'expert_video_sessions',
                    'exclusive_content',
                    'parental_guidance_resources',
                    'age_based_content_filtering',
                    'mental_health_resources',
                    'unlimited_quizzes',
                    'certificates',
                    'advanced_analytics',
                    'downloadable_materials',
                    'anonymous_qa',
                    'private_community',
                    'priority_support',
                    'ad_free',
                    'appointment_booking',
                    'ai_chatbot',
                ],
            ],
        ];

        foreach ($plans as $planData) {
            SubscriptionPlan::updateOrCreate(
                ['slug' => $planData['slug']],
                $planData
            );
        }

        $this->command->info('✅ Subscription plans seeded:');
        $this->command->info('   • Basic (Free)           — ₱0.00');
        $this->command->info('   • Premium Monthly        — ₱129.00 / month');
        $this->command->info('   • Premium Annual         — ₱1,299.00 / year');
        $this->command->info('   • Premium Plus (Annual)  — ₱1,999.00 / year');
    }
}