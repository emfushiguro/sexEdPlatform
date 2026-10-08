<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lesson_topic_captions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('lesson_topic_id')->constrained()->cascadeOnDelete();
            $table->string('file_path');
            $table->string('language_code', 35);
            $table->string('label', 100);
            $table->boolean('is_default')->default(false);
            $table->timestamps();
            $table->unique(['lesson_topic_id', 'language_code']);
        });

        $timestamp = now();
        DB::table('lesson_topics')
            ->select(['id', 'caption_file_path'])
            ->whereNotNull('caption_file_path')
            ->where('caption_file_path', '<>', '')
            ->orderBy('id')
            ->chunkById(100, function ($topics) use ($timestamp): void {
                DB::table('lesson_topic_captions')->insert(
                    $topics->map(fn ($topic): array => [
                        'lesson_topic_id' => $topic->id,
                        'file_path' => $topic->caption_file_path,
                        'language_code' => 'en',
                        'label' => 'Subtitles',
                        'is_default' => true,
                        'created_at' => $timestamp,
                        'updated_at' => $timestamp,
                    ])->all(),
                );
            });

        Schema::table('lesson_topics', function (Blueprint $table): void {
            $table->dropColumn('caption_file_path');
        });
    }

    public function down(): void
    {
        Schema::table('lesson_topics', function (Blueprint $table): void {
            $table->string('caption_file_path')->nullable()->after('video_file_path');
        });

        DB::table('lesson_topic_captions')
            ->orderBy('lesson_topic_id')
            ->orderByDesc('is_default')
            ->orderBy('id')
            ->get()
            ->groupBy('lesson_topic_id')
            ->each(function ($captions, $topicId): void {
                DB::table('lesson_topics')
                    ->where('id', $topicId)
                    ->update(['caption_file_path' => $captions->first()->file_path]);
            });

        Schema::dropIfExists('lesson_topic_captions');
    }
};
