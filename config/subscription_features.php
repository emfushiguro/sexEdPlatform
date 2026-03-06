<?php

/**
 * Subscription Feature Definitions
 * ─────────────────────────────────
 * Single source of truth for all feature keys and their display labels.
 * Used by: admin modals (Create & Edit Plan), table display, upgrade page.
 *
 * To add a new feature:
 *  1. Add it to the correct group under `groups`.
 *  2. That is all – every view picks it up automatically.
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Feature Groups
    |--------------------------------------------------------------------------
    | Each group has:
    |   'label'    – displayed as a section header in the checkbox lists
    |   'features' – key => human-readable label map
    |                key  = the value stored in the DB features column
    |                label = shown in modals, tables, and the upgrade page
    */
    'groups' => [

        'learning' => [
            'label'    => '📚 Learning',
            'features' => [
                'full_course_access'          => 'Full Course Access (All Modules)',
                'offline_access'              => 'Offline Access',
                'expert_video_sessions'       => 'Expert Video Sessions',
                'exclusive_content'           => 'Exclusive / Bonus Content',
            ],
        ],

        'assessment' => [
            'label'    => '📝 Assessment',
            'features' => [
                'unlimited_quizzes'  => 'Unlimited Quiz Attempts',
                'certificates'       => 'Completion Certificates',
                'advanced_analytics' => 'Advanced Progress Analytics',
            ],
        ],

        'content_community' => [
            'label'    => '💬 Content & Community',
            'features' => [
                'downloadable_materials' => 'Downloadable Materials (PDFs)',
                'anonymous_qa'           => 'Anonymous Q&A with Educators',
                'private_community'      => 'Private Community Discussion',
            ],
        ],

        'support_experience' => [
            'label'    => '🎧 Support & Experience',
            'features' => [
                'priority_support' => 'Priority Support',
                'ad_free'          => 'Ad-Free Experience',
            ],
        ],

        'future' => [
            'label'      => '🚀 Future / VIP Reserved',
            'dimmed'     => true,   // rendered with reduced opacity in modals
            'features'   => [
                'appointment_booking' => 'Appointment Booking (Future)',
                'ai_chatbot'          => 'AI Chatbot (Future Premium)',
            ],
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Flat label map (auto-generated from groups + legacy backwards-compat keys)
    |--------------------------------------------------------------------------
    | Used for table display and the upgrade page feature lists.
    */
    'labels' => [
        // ── Current canonical keys (keep in sync with groups above) ──
        'full_course_access'          => 'Full Course Access',
        'offline_access'              => 'Offline Access',
        'expert_video_sessions'       => 'Expert Video Sessions',
        'exclusive_content'           => 'Exclusive / Bonus Content',
        'unlimited_quizzes'           => 'Unlimited Quiz Attempts',
        'certificates'                => 'Completion Certificates',
        'advanced_analytics'          => 'Advanced Progress Analytics',
        'downloadable_materials'      => 'Downloadable Materials',
        'anonymous_qa'                => 'Anonymous Q&A with Educators',
        'private_community'           => 'Private Community Discussion',
        'priority_support'            => 'Priority Support',
        'ad_free'                     => 'Ad-Free Experience',
        'appointment_booking'         => 'Appointment Booking',
        'ai_chatbot'                  => 'AI Chatbot',

        // ── Legacy / seeder internal keys ──
        'downloadable_content'        => 'Downloadable Resources',
        'downloadable_resources'      => 'Downloadable Resources',
        'progress_analytics'          => 'Progress Analytics',
        'all_modules'                 => 'All Modules Access',
        'consultations'               => 'Live Consultations',
        'module_access'               => 'Module Access',
        'quiz_attempts'               => 'Quiz Attempts',
    ],

    /*
    |--------------------------------------------------------------------------
    | Internal / system keys to exclude from display
    |--------------------------------------------------------------------------
    */
    'hidden' => [
        'test_mode',
        'duration_minutes',
        'max_modules',
        'max_quiz_per_day',
        'forum_access',
        'ads_enabled',
        'type',
    ],

];
