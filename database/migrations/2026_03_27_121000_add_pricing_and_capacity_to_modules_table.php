<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('modules', function (Blueprint $table) {
            $table->enum('access_type', ['free', 'paid'])->default('free')->after('is_premium');
            $table->decimal('price_amount', 10, 2)->nullable()->after('access_type');
            $table->char('price_currency', 3)->default('PHP')->after('price_amount');
            $table->unsignedInteger('enrollment_limit')->nullable()->after('price_currency');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('modules', function (Blueprint $table) {
            $table->dropColumn([
                'access_type',
                'price_amount',
                'price_currency',
                'enrollment_limit',
            ]);
        });
    }
};
