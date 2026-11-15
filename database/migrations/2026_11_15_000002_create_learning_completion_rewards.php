<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('learning_completion_rewards', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('reason', 40);
            $table->unsignedBigInteger('subject_id');
            $table->timestamps();
            $table->unique(['user_id', 'reason', 'subject_id'], 'completion_reward_once');
        });
        // Preserve known past awards without changing scores or progress.
        // shortcut: lessons undone before this migration have no reliable award history; reconstruct only if a historical ledger becomes available.
        foreach (['lesson_topic_progress' => ['lesson_topic_id', 'topic_complete'], 'user_progress' => ['lesson_id', 'lesson_complete']] as $table => [$column,$reason]) {
            $query = DB::table($table)->whereNotNull($column);
            if ($table === 'user_progress') {
                $query->where(function ($q) {
                    $q->where('completed', true)->orWhereNotNull('completed_at');
                });
            }
            $query->orderBy('id')->chunkById(500, function ($rows) use ($column, $reason): void {
                $values = [];
                foreach ($rows as $row) {
                    $values[] = ['user_id' => $row->user_id, 'reason' => $reason, 'subject_id' => $row->{$column}, 'created_at' => now(), 'updated_at' => now()];
                }
                if ($values) {
                    DB::table('learning_completion_rewards')->insertOrIgnore($values);
                }
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('learning_completion_rewards');
    }
};
