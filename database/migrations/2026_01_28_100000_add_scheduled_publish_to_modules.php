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
            // Add scheduled publishing fields
            $table->timestamp('publish_at')->nullable()->after('is_published');
            $table->enum('publish_status', ['draft', 'scheduled', 'published'])->default('draft')->after('publish_at');
            
            // Index for scheduled publishing queries
            $table->index(['publish_status', 'publish_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('modules', function (Blueprint $table) {
            $table->dropIndex(['publish_status', 'publish_at']);
            $table->dropColumn(['publish_at', 'publish_status']);
        });
    }
};
