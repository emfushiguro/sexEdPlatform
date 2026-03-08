<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Payment Configuration
    |--------------------------------------------------------------------------
    */
    
    'subscription' => [
        'plans' => [
            'free' => [
                'name'  => 'Free Plan',
                'price' => 0,
                'features' => [
                    'modules'       => 3,
                    'quizzes'       => true,
                    'certificates'  => false,
                    'support'       => 'community',
                    'consultations' => 0,
                ]
            ],
            'premium' => [
                'name'  => 'Premium Plan',
                'price' => 299.00,
                'features' => [
                    'modules'                => 'unlimited',
                    'quizzes'               => true,
                    'certificates'          => true,
                    'support'               => 'priority',
                    'consultations'         => 5,
                    'downloadable_resources' => true,
                ]
            ],
            'organization' => [
                'name'  => 'Organization Plan',
                'price' => 999.00,
                'features' => [
                    'modules'          => 'unlimited',
                    'users'            => 50,
                    'admin_dashboard'  => true,
                    'progress_tracking'=> true,
                    'priority_support' => true,
                ]
            ],
        ],

        'trial_days' => 7,
        'grace_period_days' => 7,
        'max_retry_attempts' => 3,
        'retry_schedule' => [3, 7, 14], // days between retries

        // Number of calendar days after payment within which the user may request a refund.
        // Changing this value here automatically updates the controller, service, and Terms page.
        'refund_window_days' => 3,
    ],

    'payment_methods' => [
        'gcash' => [
            'name' => 'GCash',
            'enabled' => true,
            'icon' => 'gcash.png',
            'description' => 'Pay using your GCash wallet'
        ],
        'paymaya' => [
            'name' => 'PayMaya',
            'enabled' => true,
            'icon' => 'paymaya.png',
            'description' => 'Pay using your PayMaya account'
        ],
        'grab_pay' => [
            'name' => 'GrabPay',
            'enabled' => true,
            'icon' => 'grabpay.png',
            'description' => 'Pay using your GrabPay wallet'
        ],
        'card' => [
            'name' => 'Credit/Debit Card',
            'enabled' => true,
            'icon' => 'card.png',
            'description' => 'Visa, Mastercard, JCB'
        ],
        'bank_transfer' => [
            'name' => 'Bank Transfer',
            'enabled' => false,
            'icon' => 'bank.png',
            'description' => 'Direct bank transfer'
        ],
    ],

    'currency' => [
        'code' => 'PHP',
        'symbol' => '₱',
        'decimal_places' => 2,
    ],

    'tax' => [
        'enabled' => false,
        'rate' => 0.12, // 12% VAT
        'label' => 'VAT',
        'included_in_price' => true,
    ],

    'invoicing' => [
        'auto_generate' => true,
        'prefix' => 'INV-',
        'due_days' => 30,
        'late_fee_enabled' => false,
        'late_fee_amount' => 0,
        'company_details' => [
            'name' => env('COMPANY_NAME', config('app.name')),
            'address' => env('COMPANY_ADDRESS', ''),
            'tin' => env('COMPANY_TIN', ''),
            'email' => env('COMPANY_EMAIL', config('mail.from.address')),
            'phone' => env('COMPANY_PHONE', ''),
            'website' => env('APP_URL', ''),
        ],
    ],

    'notifications' => [
        'payment_success' => true,
        'payment_failed' => true,
        'subscription_expiring' => true,
        'subscription_expired' => true,
        'refund_processed' => true,
        'invoice_generated' => true,
        
        // Email timing (days before event)
        'expiry_reminder_days' => [7, 3, 1],
        
        // Admin notifications
        'admin_new_subscriber' => true,
        'admin_payment_failed' => true,
        'admin_high_churn' => true,
    ],

    'analytics' => [
        'track_user_behavior' => true,
        'retention_cohorts' => true,
        'revenue_recognition' => 'accrual', // 'accrual' or 'cash'
        'dashboard_refresh_minutes' => 15,
        
        'metrics' => [
            'mrr' => true,
            'arr' => true,
            'ltv' => true,
            'churn_rate' => true,
            'conversion_rate' => true,
            'payment_success_rate' => true,
        ],
    ],

    'security' => [
        'webhook_verification' => true,
        'rate_limiting' => [
            'enabled' => true,
            'requests_per_minute' => 10,
        ],
        'fraud_detection' => [
            'enabled' => true,
            'max_failed_attempts' => 3,
            'lockout_minutes' => 30,
        ],
    ],

    'features' => [
        'proration' => true,
        'downgrades' => true,
        'pause_subscription' => false,
        'multiple_subscriptions' => false,
        'gift_subscriptions' => false,
        'referral_program' => false,
        'coupon_system' => false,
    ],

];