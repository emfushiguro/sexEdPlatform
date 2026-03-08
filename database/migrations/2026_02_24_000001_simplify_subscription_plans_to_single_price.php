<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Simplify subscription_plans table: replace monthly_price + annual_price with a single price column.
 * Data is preserved: price = monthly_price (the primary billing value).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscription_plans', function (Blueprint $table) {
            // Add unified price column (nullable first so existing rows don't violate NOT NULL)
            $table->decimal('price', 10, 2)->nullable()->after('description');
        });

        // Migrate existing data: carry monthly_price → price
        DB::table('subscription_plans')->update([
            'price' => DB::raw('monthly_price'),
        ]);

        Schema::table('subscription_plans', function (Blueprint $table) {
            // Make price non-nullable now that all rows have a value
            $table->decimal('price', 10, 2)->default(0)->nullable(false)->change();
            // Drop the two old columns
            $table->dropColumn(['monthly_price', 'annual_price']);
        });
    }

    public function down(): void
    {
        Schema::table('subscription_plans', function (Blueprint $table) {
            $table->decimal('monthly_price', 10, 2)->default(0)->after('description');
            $table->decimal('annual_price', 10, 2)->default(0)->after('monthly_price');
        });

        DB::table('subscription_plans')->update([
            'monthly_price' => DB::raw('price'),
            'annual_price'  => DB::raw('price'),
        ]);

        Schema::table('subscription_plans', function (Blueprint $table) {
            $table->dropColumn('price');
        });
    }
};
