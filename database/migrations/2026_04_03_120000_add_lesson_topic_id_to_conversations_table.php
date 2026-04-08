<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->foreignId('lesson_topic_id')
                ->nullable()
                ->after('lesson_id')
                ->constrained('lesson_topics')
                ->nullOnDelete();

            $table->index('lesson_topic_id', 'conversations_lesson_topic_id_idx');
        });
    }

    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->dropIndex('conversations_lesson_topic_id_idx');
            $table->dropConstrainedForeignId('lesson_topic_id');
        });
    }
};
