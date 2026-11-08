<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lesson_topics', function (Blueprint $table): void {
            $table->json('video_attribution')->nullable();
        });
    }

    public function down(): void
    {
        // Preserve recorded credits on rollback; removal requires a deliberate data migration.
    }
};
