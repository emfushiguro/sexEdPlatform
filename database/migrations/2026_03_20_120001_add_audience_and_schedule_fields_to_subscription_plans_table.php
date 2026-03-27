<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscription_plans', function (Blueprint $table) {
            $table->string('plan_audience')->default('learner')->after('features');
            $table->string('billing_mode')->default('monthly')->after('plan_audience');
            $table->date('availability_starts_on')->nullable()->after('billing_mode');
            $table->date('availability_ends_on')->nullable()->after('availability_starts_on');
            $table->date('admin_preview_starts_on')->nullable()->after('availability_ends_on');
            $table->date('admin_preview_ends_on')->nullable()->after('admin_preview_starts_on');
        });
    }

    public function down(): void
    {
        Schema::table('subscription_plans', function (Blueprint $table) {
            $table->dropColumn([
                'plan_audience',
                'billing_mode',
                'availability_starts_on',
                'availability_ends_on',
                'admin_preview_starts_on',
                'admin_preview_ends_on',
            ]);
        });
    }
};
