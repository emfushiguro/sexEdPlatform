<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('module_marketplace_listings', function (Blueprint $table): void {
            $table->json('seat_packages')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('module_marketplace_listings', function (Blueprint $table): void {
            $table->dropColumn('seat_packages');
        });
    }
};
