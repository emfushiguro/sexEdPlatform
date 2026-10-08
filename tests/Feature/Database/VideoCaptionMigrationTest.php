<?php

namespace Tests\Feature\Database;

use App\Models\LessonTopic;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class VideoCaptionMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_caption_schema_and_topic_relationship_exist(): void
    {
        $this->assertTrue(Schema::hasTable('lesson_topic_captions'));
        $this->assertFalse(Schema::hasColumn('lesson_topics', 'caption_file_path'));

        $topic = LessonTopic::factory()->create();
        $caption = $topic->captions()->create([
            'file_path' => 'captions/'.$topic->id.'/english.vtt',
            'language_code' => 'en',
            'label' => 'English',
            'is_default' => true,
        ]);

        $this->assertTrue($caption->topic->is($topic));
        $this->assertTrue($caption->is_default);
        $this->assertStringContainsString(
            '/storage/captions/'.$topic->id.'/english.vtt',
            $caption->file_url,
        );
    }

    public function test_migration_backfills_the_legacy_caption_before_dropping_its_column(): void
    {
        $migration = require database_path(
            'migrations/2026_09_20_000001_create_lesson_topic_captions_table.php',
        );
        $migration->down();

        $topic = LessonTopic::factory()->create();
        DB::table('lesson_topics')->where('id', $topic->id)->update([
            'caption_file_path' => 'captions/legacy.vtt',
        ]);

        $migration->up();

        $this->assertDatabaseHas('lesson_topic_captions', [
            'lesson_topic_id' => $topic->id,
            'file_path' => 'captions/legacy.vtt',
            'language_code' => 'en',
            'label' => 'Subtitles',
            'is_default' => true,
        ]);
        $this->assertFalse(Schema::hasColumn('lesson_topics', 'caption_file_path'));
    }
}
