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
            // Drop the index first
            $table->dropIndex(['publish_status', 'publish_at']);
            
            // Then drop the columns
            $table->dropColumn(['publish_at', 'publish_status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('modules', function (Blueprint $table) {
            // Re-add the columns
            $table->timestamp('publish_at')->nullable()->after('is_published');
            $table->enum('publish_status', ['draft', 'scheduled', 'published'])->default('draft')->after('publish_at');
            
            // Re-add the index
            $table->index(['publish_status', 'publish_at']);
        });
    }
};
