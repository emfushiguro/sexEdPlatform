<?php

namespace Database\Seeders;

use App\Models\FeatureCatalog;
use Illuminate\Database\Seeder;

class FeatureCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $features = [
            // Learner-specific features
            [
                'key' => 'unlimited_shields',
                'name' => 'Unlimited Quiz Shields',
                'description' => 'Remove daily limit on learning shields for quiz attempts',
                'value_type' => 'boolean',
                'unit_label' => null,
                'category' => 'learner',
                'is_active' => true,
            ],
            [
                'key' => 'certificate_pdf_download_access',
                'name' => 'Certificate PDF Download',
                'description' => 'Allow learners to download and print completion certificates',
                'value_type' => 'boolean',
                'unit_label' => null,
                'category' => 'learner',
                'is_active' => true,
            ],
            [
                'key' => 'monthly_streak_savers_quota',
                'name' => 'Monthly Streak Savers',
                'description' => 'Number of streak savers provided per month',
                'value_type' => 'quota',
                'unit_label' => 'savers',
                'category' => 'learner',
                'is_active' => true,
            ],
            [
                'key' => 'full_course_access',
                'name' => 'Full Course Access',
                'description' => 'Access to all course modules and content',
                'value_type' => 'boolean',
                'unit_label' => null,
                'category' => 'learner',
                'is_active' => true,
            ],
            [
                'key' => 'downloadable_materials',
                'name' => 'Downloadable Materials',
                'description' => 'Access to downloadable PDFs and resources',
                'value_type' => 'boolean',
                'unit_label' => null,
                'category' => 'learner',
                'is_active' => true,
            ],
            [
                'key' => 'anonymous_qa_access',
                'name' => 'Anonymous Q&A with Educators',
                'description' => 'Ask questions anonymously to educators',
                'value_type' => 'boolean',
                'unit_label' => null,
                'category' => 'learner',
                'is_active' => true,
            ],

            // Instructor-specific features
            [
                'key' => 'unlimited_seminar_creation',
                'name' => 'Unlimited Seminar Creation',
                'description' => 'Create unlimited seminars and webinars',
                'value_type' => 'boolean',
                'unit_label' => null,
                'category' => 'instructor',
                'is_active' => true,
            ],
            [
                'key' => 'seminar_quota',
                'name' => 'Seminar Creation Quota',
                'description' => 'Number of seminars that can be created per month',
                'value_type' => 'quota',
                'unit_label' => 'seminars',
                'category' => 'instructor',
                'is_active' => true,
            ],
            [
                'key' => 'advanced_analytics_dashboard',
                'name' => 'Advanced Analytics Dashboard',
                'description' => 'Access to detailed learner analytics and insights',
                'value_type' => 'boolean',
                'unit_label' => null,
                'category' => 'instructor',
                'is_active' => true,
            ],
            [
                'key' => 'priority_listing',
                'name' => 'Priority Listing',
                'description' => 'Seminars appear at top of listings',
                'value_type' => 'boolean',
                'unit_label' => null,
                'category' => 'instructor',
                'is_active' => true,
            ],
            [
                'key' => 'custom_branding',
                'name' => 'Custom Branding',
                'description' => 'Add custom logos and branding to seminars',
                'value_type' => 'boolean',
                'unit_label' => null,
                'category' => 'instructor',
                'is_active' => true,
            ],

            // Connector-specific features
            [
                'key' => 'organization_management',
                'name' => 'Organization Management',
                'description' => 'Manage organization profiles and members',
                'value_type' => 'boolean',
                'unit_label' => null,
                'category' => 'connectors',
                'is_active' => true,
            ],
            [
                'key' => 'bulk_user_invitations',
                'name' => 'Bulk User Invitations',
                'description' => 'Invite multiple users to the platform at once',
                'value_type' => 'boolean',
                'unit_label' => null,
                'category' => 'connectors',
                'is_active' => true,
            ],
            [
                'key' => 'user_seats_quota',
                'name' => 'User Seats Quota',
                'description' => 'Maximum number of users in organization',
                'value_type' => 'quota',
                'unit_label' => 'seats',
                'category' => 'connectors',
                'is_active' => true,
            ],
            [
                'key' => 'organization_reporting',
                'name' => 'Organization Reporting',
                'description' => 'Access to organization-wide reports and analytics',
                'value_type' => 'boolean',
                'unit_label' => null,
                'category' => 'connectors',
                'is_active' => true,
            ],

            // General features (available for all audiences)
            [
                'key' => 'priority_support',
                'name' => 'Priority Support',
                'description' => 'Get priority assistance from support team',
                'value_type' => 'boolean',
                'unit_label' => null,
                'category' => 'general',
                'is_active' => true,
            ],
            [
                'key' => 'ad_free_experience',
                'name' => 'Ad-Free Experience',
                'description' => 'Browse platform without advertisements',
                'value_type' => 'boolean',
                'unit_label' => null,
                'category' => 'general',
                'is_active' => true,
            ],
        ];

        foreach ($features as $featureData) {
            FeatureCatalog::updateOrCreate(
                ['key' => $featureData['key']],
                $featureData
            );
        }

        $this->command->info('Feature catalog seeded successfully with ' . count($features) . ' features.');
    }
}
