<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Update payments table with additional fields and statuses
        Schema::table('payments', function (Blueprint $table) {
            // Add new payment statuses and methods to enum (MySQL only — SQLite uses string columns)
            if (DB::getDriverName() === 'mysql') {
                DB::statement("ALTER TABLE payments MODIFY COLUMN status ENUM('pending', 'completed', 'failed', 'refunded', 'cancelled', 'processing', 'expired') DEFAULT 'pending'");
                DB::statement("ALTER TABLE payments MODIFY COLUMN method ENUM('gcash', 'paymaya', 'grab_pay', 'card', 'bank_transfer', 'paymongo', 'retry_gcash', 'retry_paymaya', 'retry_grab_pay', 'retry_card') DEFAULT NULL");
            }
            
            // Add notes field if it doesn't exist (might be added by refunds migration)
            if (!Schema::hasColumn('payments', 'notes')) {
                $table->text('notes')->nullable()->after('payment_details');
            }
        });

        // Update users table to support analytics
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'last_login_at')) {
                $table->timestamp('last_login_at')->nullable();
            }
        });

        // Create subscription plan seeds
        if (Schema::hasTable('subscription_plans')) {
            DB::table('subscription_plans')->insert([
                [
                    'name' => 'Free Plan',
                    'slug' => 'free',
                    'description' => 'Basic access to learning materials',
                    'monthly_price' => 0.00,
                    'annual_price' => 0.00,
                    'features' => json_encode([
                        'modules' => 3,
                        'quizzes' => true,
                        'certificates' => false,
                        'support' => 'community',
                    ]),
                    'trial_days' => 0,
                    'is_active' => true,
                    'sort_order' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'name' => 'Basic Plan',
                    'slug' => 'basic',
                    'description' => 'Access to more modules and basic support',
                    'monthly_price' => 199.00,
                    'annual_price' => 1990.00,
                    'features' => json_encode([
                        'modules' => 10,
                        'quizzes' => true,
                        'certificates' => true,
                        'support' => 'email',
                    ]),
                    'trial_days' => 7,
                    'is_active' => true,
                    'sort_order' => 2,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'name' => 'Premium Plan',
                    'slug' => 'premium',
                    'description' => 'Unlimited access to all features',
                    'monthly_price' => 299.00,
                    'annual_price' => 2999.00,
                    'features' => json_encode([
                        'modules' => 'unlimited',
                        'quizzes' => true,
                        'certificates' => true,
                        'support' => 'priority',
                        'consultations' => 5,
                        'downloadable_resources' => true,
                    ]),
                    'trial_days' => 14,
                    'is_active' => true,
                    'sort_order' => 3,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ]);
        }
    }

    public function down(): void
    {
        // Revert payment status and method enums
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE payments MODIFY COLUMN status ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'pending'");
            DB::statement("ALTER TABLE payments MODIFY COLUMN method ENUM('gcash', 'paymaya', 'card', 'bank_transfer') DEFAULT NULL");
        }

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'last_login_at')) {
                $table->dropColumn('last_login_at');
            }
        });

        // Clear subscription plans
        if (Schema::hasTable('subscription_plans')) {
            DB::table('subscription_plans')->truncate();
        }
    }
};