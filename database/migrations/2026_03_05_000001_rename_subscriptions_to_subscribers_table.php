<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop the FK from payments pointing to subscriptions before renaming
        Schema::table('payments', function ($table) {
            $table->dropForeign(['subscription_id']);
        });

        Schema::rename('subscriptions', 'subscribers');

        // Recreate the FK pointing to the renamed table
        Schema::table('payments', function ($table) {
            $table->foreign('subscription_id')
                  ->references('id')
                  ->on('subscribers')
                  ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function ($table) {
            $table->dropForeign(['subscription_id']);
        });

        Schema::rename('subscribers', 'subscriptions');

        Schema::table('payments', function ($table) {
            $table->foreign('subscription_id')
                  ->references('id')
                  ->on('subscriptions')
                  ->onDelete('set null');
        });
    }
};
