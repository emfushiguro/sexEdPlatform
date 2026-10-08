# Local Video Caption Support Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add authorized, multi-language WebVTT caption management to local lesson-topic videos and expose those tracks through the existing Plyr.js player.

**Architecture:** Normalize caption metadata into a LessonTopic child table, preserve the legacy single-caption path through an incremental migration, and synchronize caption rows/files through the existing Topic create/update/delete workflow. Render native HTML5 track elements for local videos only and derive Plyr caption options from those elements without adding another player initializer.

**Tech Stack:** Laravel 12, PHP 8.2+, Eloquent, MySQL test database, Blade, public-disk storage, vanilla JavaScript, Node's built-in test runner, Plyr 3.8, Tailwind CSS, PHPUnit 11, Vite 7.

## Global Constraints

- Phase 1 accepts WebVTT (.vtt) caption files only.
- The caption-file limit is exactly 2 MiB, expressed to Laravel as 2,048 KiB.
- Each active track requires a BCP 47-style language code and a learner-facing label.
- A topic may have at most one active track per normalized language code and zero or one default track.
- Existing caption_file_path values become en / Subtitles / default records before that column is removed.
- Use the existing public storage disk and store generated filenames below captions/{topic-id}.
- Caption authoring stays inside the existing authorized Topic create/edit forms and their FormData submission.
- YouTube/Vimeo iframe behavior and provider caption management remain unchanged.
- Preserve play, pause, seeking, progress, volume, mute, speed, fullscreen, and responsive behavior.
- Add no third-party parser, upload library, or player dependency.
- Never reset, wipe, truncate, recreate, or destructively reseed the development database.
- Automated database tests must use only cc_db_test from phpunit.xml.

---

## File Map

- database/migrations/2026_09_20_000001_create_lesson_topic_captions_table.php: normalize caption metadata, backfill the legacy path, and provide a reversible downgrade.
- app/Models/LessonTopicCaption.php: caption record, inverse relation, boolean cast, and public URL accessor.
- app/Models/LessonTopic.php: remove the legacy fillable field and expose captions().
- app/Rules/WebVttFile.php: dependency-free WebVTT extension, MIME, encoding, header, and cue validation.
- app/Services/LessonTopicCaptionService.php: synchronize rows, stage new files, and defer obsolete-file deletion.
- app/Http/Controllers/Instructor/TopicController.php: authorize, validate nested caption input, coordinate transactions, and invoke synchronization.
- resources/views/instructor/topics/partials/caption-tracks.blade.php: shared create/edit caption rows.
- resources/js/caption-tracks-form.js: row addition/removal and client file feedback.
- resources/js/video-player.js: pure, testable Plyr options derived from track elements.
- resources/js/app.js: import the caption form module and keep one Plyr initialization loop.
- app/Http/Controllers/Learner/LessonController.php: eager-load captions.
- resources/views/learner/lessons/partials/topic-page.blade.php: render native track elements for local video only.
- tests/Feature/Database/VideoCaptionMigrationTest.php: schema, relation, accessor, and legacy backfill.
- tests/Unit/Rules/WebVttFileTest.php: WebVTT trust-boundary tests.
- tests/Feature/Instructor/VideoCaptionManagementTest.php: authoring, authorization, persistence, and cleanup.
- tests/Unit/JavaScript/caption-tracks-form.test.js: browser-side file and row helpers.
- tests/Feature/Learner/VideoCaptionRenderingTest.php: learner track and provider rendering.
- tests/Unit/JavaScript/video-player.test.js: Plyr caption/default/control configuration.

---

### Task 1: Normalize caption records and preserve legacy data

**Files:**
- Create: database/migrations/2026_09_20_000001_create_lesson_topic_captions_table.php
- Create: app/Models/LessonTopicCaption.php
- Modify: app/Models/LessonTopic.php:17-47
- Create: tests/Feature/Database/VideoCaptionMigrationTest.php

**Interfaces:**
- Consumes: lesson_topics.id and the legacy lesson_topics.caption_file_path column.
- Produces: LessonTopic::captions(): HasMany, LessonTopicCaption::topic(): BelongsTo, and LessonTopicCaption::file_url.

- [ ] **Step 1: Write the failing schema test**

Create tests/Feature/Database/VideoCaptionMigrationTest.php with the first test:

    <?php

    namespace Tests\Feature\Database;

    use App\Models\LessonTopic;
    use Illuminate\Foundation\Testing\DatabaseMigrations;
    use Illuminate\Support\Facades\DB;
    use Illuminate\Support\Facades\Schema;
    use Tests\TestCase;

    class VideoCaptionMigrationTest extends TestCase
    {
        use DatabaseMigrations;

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
    }

- [ ] **Step 2: Run the schema test and verify it fails**

Run:

    php artisan test tests/Feature/Database/VideoCaptionMigrationTest.php

Expected: FAIL because lesson_topic_captions and LessonTopic::captions() do not exist. Confirm the command is using DB_DATABASE=cc_db_test before continuing.

- [ ] **Step 3: Add the migration**

Create database/migrations/2026_09_20_000001_create_lesson_topic_captions_table.php:

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

- [ ] **Step 4: Add the model and relationship**

Create app/Models/LessonTopicCaption.php:

    <?php

    namespace App\Models;

    use Illuminate\Database\Eloquent\Model;
    use Illuminate\Database\Eloquent\Relations\BelongsTo;
    use Illuminate\Support\Facades\Storage;

    class LessonTopicCaption extends Model
    {
        protected $fillable = [
            'file_path',
            'language_code',
            'label',
            'is_default',
        ];

        protected function casts(): array
        {
            return ['is_default' => 'boolean'];
        }

        public function topic(): BelongsTo
        {
            return $this->belongsTo(LessonTopic::class, 'lesson_topic_id');
        }

        public function getFileUrlAttribute(): string
        {
            return Storage::disk('public')->url($this->file_path);
        }
    }

In app/Models/LessonTopic.php, remove caption_file_path from $fillable and add:

    public function captions(): HasMany
    {
        return $this->hasMany(LessonTopicCaption::class)
            ->orderByDesc('is_default')
            ->orderBy('id');
    }

- [ ] **Step 5: Add and run the legacy backfill test**

Add this method to VideoCaptionMigrationTest:

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

Run:

    php artisan test tests/Feature/Database/VideoCaptionMigrationTest.php

Expected: PASS with 2 tests and 0 failures. The test may migrate or roll back only cc_db_test; never point it at the development database.

- [ ] **Step 6: Commit Task 1**

    git add database/migrations/2026_09_20_000001_create_lesson_topic_captions_table.php app/Models/LessonTopicCaption.php app/Models/LessonTopic.php tests/Feature/Database/VideoCaptionMigrationTest.php
    git commit -m "feat(video): normalize caption tracks"

---

### Task 2: Validate WebVTT files at the upload boundary

**Files:**
- Create: app/Rules/WebVttFile.php
- Create: tests/Unit/Rules/WebVttFileTest.php

**Interfaces:**
- Consumes: Illuminate Http UploadedFile.
- Produces: WebVttFile implementing ValidationRule with one validation message per rejected file.

- [ ] **Step 1: Write failing rule tests**

Create tests/Unit/Rules/WebVttFileTest.php:

    <?php

    namespace Tests\Unit\Rules;

    use App\Rules\WebVttFile;
    use Illuminate\Http\UploadedFile;
    use PHPUnit\Framework\TestCase;

    class WebVttFileTest extends TestCase
    {
        public function test_it_accepts_a_valid_webvtt_file(): void
        {
            $this->assertSame([], $this->failures(
                UploadedFile::fake()->createWithContent(
                    'english.vtt',
                    "WEBVTT\n\n00:00.000 --> 00:02.000\nHello\n",
                ),
            ));
        }

        public function test_it_rejects_wrong_extensions_headers_and_missing_cues(): void
        {
            $this->assertNotEmpty($this->failures(
                UploadedFile::fake()->createWithContent(
                    'english.txt',
                    "WEBVTT\n\n00:00.000 --> 00:02.000\nHello\n",
                ),
            ));
            $this->assertNotEmpty($this->failures(
                UploadedFile::fake()->createWithContent('english.vtt', "Hello\n"),
            ));
            $this->assertNotEmpty($this->failures(
                UploadedFile::fake()->createWithContent('english.vtt', "WEBVTT\n\nHello\n"),
            ));
        }

        public function test_it_rejects_nul_and_invalid_utf8_content(): void
        {
            $this->assertNotEmpty($this->failures(
                UploadedFile::fake()->createWithContent(
                    'nul.vtt',
                    "WEBVTT\n\n00:00.000 --> 00:02.000\nBad\0cue\n",
                ),
            ));
            $this->assertNotEmpty($this->failures(
                UploadedFile::fake()->createWithContent(
                    'encoding.vtt',
                    "WEBVTT\n\n00:00.000 --> 00:02.000\n\xC3\x28\n",
                ),
            ));
        }

        public function test_it_rejects_non_text_mime_content(): void
        {
            $this->assertNotEmpty($this->failures(
                UploadedFile::fake()->create('caption.vtt', 1, 'application/pdf'),
            ));
        }

        private function failures(UploadedFile $file): array
        {
            $failures = [];
            (new WebVttFile)->validate(
                'caption',
                $file,
                function (string $message) use (&$failures): void {
                    $failures[] = $message;
                },
            );

            return $failures;
        }
    }

- [ ] **Step 2: Run the rule test and verify it fails**

Run:

    php artisan test tests/Unit/Rules/WebVttFileTest.php

Expected: FAIL because App\Rules\WebVttFile does not exist.

- [ ] **Step 3: Implement the minimum rule**

Create app/Rules/WebVttFile.php:

    <?php

    namespace App\Rules;

    use Closure;
    use Illuminate\Contracts\Validation\ValidationRule;
    use Illuminate\Http\UploadedFile;

    final class WebVttFile implements ValidationRule
    {
        private const MIMES = ['text/vtt', 'text/plain'];

        public function validate(string $attribute, mixed $value, Closure $fail): void
        {
            if (! $value instanceof UploadedFile || ! $value->isValid()) {
                $fail('The :attribute must be a valid WebVTT file.');
                return;
            }

            if (strtolower($value->getClientOriginalExtension()) !== 'vtt') {
                $fail('The :attribute must use the .vtt extension.');
                return;
            }

            if (! in_array($value->getMimeType(), self::MIMES, true)) {
                $fail('The :attribute must be a WebVTT text file.');
                return;
            }

            $contents = file_get_contents($value->getRealPath());
            if ($contents === false
                || str_contains($contents, "\0")
                || ! mb_check_encoding($contents, 'UTF-8')) {
                $fail('The :attribute must contain valid UTF-8 WebVTT text.');
                return;
            }

            $contents = preg_replace('/^\xEF\xBB\xBF/', '', $contents);
            if (! preg_match('/\AWEBVTT(?:[ \t][^\r\n]*)?\r?\n/', $contents)) {
                $fail('The :attribute must begin with a WEBVTT header.');
                return;
            }

            $timestamp = '(?:[0-9]{2,}:)?[0-9]{2}:[0-9]{2}\.[0-9]{3}';
            if (! preg_match('/^'.$timestamp.'[ \t]+-->[ \t]+'.$timestamp.'(?:[ \t].*)?$/m', $contents)) {
                $fail('The :attribute must contain at least one WebVTT cue.');
            }
        }
    }

- [ ] **Step 4: Run and format**

    php artisan test tests/Unit/Rules/WebVttFileTest.php
    vendor/bin/pint --test app/Rules/WebVttFile.php tests/Unit/Rules/WebVttFileTest.php

Expected: 4 tests pass and Pint exits 0.

- [ ] **Step 5: Commit Task 2**

    git add app/Rules/WebVttFile.php tests/Unit/Rules/WebVttFileTest.php
    git commit -m "feat(video): validate WebVTT captions"

---

### Task 3: Persist, replace, remove, and authorize caption tracks

**Files:**
- Create: app/Services/LessonTopicCaptionService.php
- Modify: app/Http/Controllers/Instructor/TopicController.php:5-25,37-317,320-586,655-689
- Create: tests/Feature/Instructor/VideoCaptionManagementTest.php

**Interfaces:**
- Consumes: captions[index][id|file|language_code|label|remove] and optional caption_default=index.
- Produces: LessonTopicCaptionService::sync(LessonTopic $topic, array $tracks, ?int $defaultIndex): array{stored: array, obsolete: array}.

- [ ] **Step 1: Write the failing authoring tests**

Create tests/Feature/Instructor/VideoCaptionManagementTest.php. Use RefreshDatabase and Storage::fake('public'). Include these complete scenarios:

    public function test_owner_can_create_multiple_tracks_with_one_default(): void
    {
        [$instructor, $lesson] = $this->topicAuthoringFixture();

        $this->actingAs($instructor)->post(route('instructor.topics.store'), [
            'lesson_id' => $lesson->id,
            'title' => 'Captioned video',
            'type' => 'video',
            'duration' => 3,
            'video_source' => 'upload',
            'video_file' => $this->video(),
            'captions' => [
                0 => ['file' => $this->vtt('english.vtt', 'Hello'), 'language_code' => 'EN', 'label' => 'English'],
                1 => ['file' => $this->vtt('filipino.vtt', 'Kumusta'), 'language_code' => 'fil', 'label' => 'Filipino'],
            ],
            'caption_default' => 1,
        ])->assertRedirect();

        $topic = LessonTopic::where('title', 'Captioned video')->firstOrFail();
        $this->assertDatabaseHas('lesson_topic_captions', [
            'lesson_topic_id' => $topic->id,
            'language_code' => 'en',
            'label' => 'English',
            'is_default' => false,
        ]);
        $this->assertDatabaseHas('lesson_topic_captions', [
            'lesson_topic_id' => $topic->id,
            'language_code' => 'fil',
            'label' => 'Filipino',
            'is_default' => true,
        ]);
        $this->assertCount(2, Storage::disk('public')->allFiles('captions/'.$topic->id));
    }

    public function test_duplicate_languages_and_external_caption_uploads_are_rejected(): void
    {
        [$instructor, $lesson] = $this->topicAuthoringFixture();

        $this->actingAs($instructor)->post(route('instructor.topics.store'), [
            'lesson_id' => $lesson->id,
            'title' => 'Duplicates',
            'type' => 'video',
            'duration' => 3,
            'video_source' => 'upload',
            'video_file' => $this->video(),
            'captions' => [
                ['file' => $this->vtt('one.vtt'), 'language_code' => 'en', 'label' => 'English'],
                ['file' => $this->vtt('two.vtt'), 'language_code' => 'EN', 'label' => 'English alternate'],
            ],
        ])->assertSessionHasErrors('captions.1.language_code');

        $this->actingAs($instructor)->post(route('instructor.topics.store'), [
            'lesson_id' => $lesson->id,
            'title' => 'External',
            'type' => 'video',
            'duration' => 3,
            'video_source' => 'url',
            'video_url' => 'https://youtu.be/dQw4w9WgXcQ',
            'captions' => [
                ['file' => $this->vtt('external.vtt'), 'language_code' => 'en', 'label' => 'English'],
            ],
        ])->assertSessionHasErrors('captions');
    }

    public function test_owner_can_replace_remove_and_change_the_default_track(): void
    {
        [$instructor, $lesson] = $this->topicAuthoringFixture();
        $topic = $this->localVideoTopic($lesson);
        $english = $topic->captions()->create([
            'file_path' => 'captions/'.$topic->id.'/english-old.vtt',
            'language_code' => 'en',
            'label' => 'English',
            'is_default' => true,
        ]);
        $korean = $topic->captions()->create([
            'file_path' => 'captions/'.$topic->id.'/korean-old.vtt',
            'language_code' => 'ko',
            'label' => 'Korean',
            'is_default' => false,
        ]);
        Storage::disk('public')->put($english->file_path, $this->vttText());
        Storage::disk('public')->put($korean->file_path, $this->vttText());

        $this->actingAs($instructor)->put(route('instructor.topics.update', $topic), [
            'title' => $topic->title,
            'type' => 'video',
            'duration' => 3,
            'video_source' => 'upload',
            'captions' => [
                0 => ['id' => $english->id, 'remove' => 1],
                1 => [
                    'id' => $korean->id,
                    'file' => $this->vtt('korean-new.vtt', 'Annyeong'),
                    'language_code' => 'ko',
                    'label' => '한국어',
                ],
            ],
            'caption_default' => 1,
        ])->assertRedirect();

        $this->assertDatabaseMissing('lesson_topic_captions', ['id' => $english->id]);
        $updated = $korean->fresh();
        $this->assertTrue($updated->is_default);
        $this->assertNotSame('captions/'.$topic->id.'/korean-old.vtt', $updated->file_path);
        Storage::disk('public')->assertMissing($english->file_path);
        Storage::disk('public')->assertMissing('captions/'.$topic->id.'/korean-old.vtt');
        Storage::disk('public')->assertExists($updated->file_path);
    }

    public function test_switching_provider_and_deleting_topic_clean_caption_files(): void
    {
        [$instructor, $lesson] = $this->topicAuthoringFixture();
        $topic = $this->localVideoTopic($lesson);
        $caption = $topic->captions()->create([
            'file_path' => 'captions/'.$topic->id.'/english.vtt',
            'language_code' => 'en',
            'label' => 'English',
            'is_default' => true,
        ]);
        Storage::disk('public')->put($caption->file_path, $this->vttText());

        $this->actingAs($instructor)->put(route('instructor.topics.update', $topic), [
            'title' => $topic->title,
            'type' => 'video',
            'duration' => 3,
            'video_source' => 'url',
            'video_url' => 'https://youtu.be/dQw4w9WgXcQ',
        ])->assertRedirect();

        $this->assertDatabaseMissing('lesson_topic_captions', ['id' => $caption->id]);
        Storage::disk('public')->assertMissing($caption->file_path);

        $local = $this->localVideoTopic($lesson);
        $path = 'captions/'.$local->id.'/delete.vtt';
        $local->captions()->create([
            'file_path' => $path,
            'language_code' => 'en',
            'label' => 'English',
            'is_default' => true,
        ]);
        Storage::disk('public')->put($path, $this->vttText());

        $this->actingAs($instructor)
            ->delete(route('instructor.topics.destroy', $local))
            ->assertRedirect();
        Storage::disk('public')->assertMissing($path);
    }

    public function test_another_instructor_cannot_manage_caption_tracks(): void
    {
        [$owner, $lesson] = $this->topicAuthoringFixture();
        $topic = $this->localVideoTopic($lesson);
        $other = User::factory()->create();
        $other->assignRole('instructor');

        $this->actingAs($other)->put(route('instructor.topics.update', $topic), [
            'title' => $topic->title,
            'type' => 'video',
            'duration' => 3,
            'video_source' => 'upload',
            'captions' => [
                ['file' => $this->vtt('attack.vtt'), 'language_code' => 'en', 'label' => 'English'],
            ],
        ])->assertForbidden();

        $this->assertSame([], Storage::disk('public')->allFiles('captions'));
    }

    public function test_caption_validation_rejects_invalid_and_oversized_webvtt(): void
    {
        [$instructor, $lesson] = $this->topicAuthoringFixture();
        $base = [
            'lesson_id' => $lesson->id,
            'title' => 'Invalid captions',
            'type' => 'video',
            'duration' => 3,
            'video_source' => 'upload',
        ];

        $this->actingAs($instructor)->post(route('instructor.topics.store'), $base + [
            'video_file' => $this->video(),
            'captions' => [[
                'file' => UploadedFile::fake()->createWithContent('invalid.vtt', 'not webvtt'),
                'language_code' => 'en',
                'label' => 'English',
            ]],
        ])->assertSessionHasErrors('captions.0.file');

        $this->actingAs($instructor)->post(route('instructor.topics.store'), $base + [
            'video_file' => $this->video(),
            'captions' => [[
                'file' => UploadedFile::fake()->create('oversized.vtt', 2049, 'text/plain'),
                'language_code' => 'en',
                'label' => 'English',
            ]],
        ])->assertSessionHasErrors('captions.0.file');
    }

    public function test_captions_may_be_saved_without_a_default(): void
    {
        [$instructor, $lesson] = $this->topicAuthoringFixture();

        $this->actingAs($instructor)->post(route('instructor.topics.store'), [
            'lesson_id' => $lesson->id,
            'title' => 'Optional default',
            'type' => 'video',
            'duration' => 3,
            'video_source' => 'upload',
            'video_file' => $this->video(),
            'captions' => [[
                'file' => $this->vtt('english.vtt'),
                'language_code' => 'en',
                'label' => 'English',
            ]],
        ])->assertRedirect();

        $this->assertDatabaseHas('lesson_topic_captions', [
            'language_code' => 'en',
            'is_default' => false,
        ]);
    }

    public function test_caption_ids_cannot_be_moved_between_topics(): void
    {
        [$instructor, $lesson] = $this->topicAuthoringFixture();
        $target = $this->localVideoTopic($lesson);
        $other = $this->localVideoTopic($lesson);
        $foreignCaption = $other->captions()->create([
            'file_path' => 'captions/'.$other->id.'/foreign.vtt',
            'language_code' => 'en',
            'label' => 'English',
            'is_default' => false,
        ]);

        $this->actingAs($instructor)->put(route('instructor.topics.update', $target), [
            'title' => $target->title,
            'type' => 'video',
            'duration' => 3,
            'video_source' => 'upload',
            'captions' => [[
                'id' => $foreignCaption->id,
                'language_code' => 'en',
                'label' => 'English',
            ]],
        ])->assertSessionHasErrors('captions.0.id');
    }

    public function test_failed_caption_persistence_removes_new_files(): void
    {
        [$instructor, $lesson] = $this->topicAuthoringFixture();
        LessonTopicCaption::creating(static function (): void {
            throw new RuntimeException('caption persistence failed');
        });

        try {
            $this->actingAs($instructor)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'X-Requested-With' => 'XMLHttpRequest',
                ])
                ->post(route('instructor.topics.store'), [
                    'lesson_id' => $lesson->id,
                    'title' => 'Failed caption',
                    'type' => 'video',
                    'duration' => 3,
                    'video_source' => 'upload',
                    'video_file' => $this->video(),
                    'captions' => [[
                        'file' => $this->vtt('english.vtt'),
                        'language_code' => 'en',
                        'label' => 'English',
                    ]],
                ])
                ->assertStatus(500);
        } finally {
            LessonTopicCaption::flushEventListeners();
        }

        $this->assertSame([], Storage::disk('public')->allFiles('captions'));
        $this->assertDatabaseMissing('lesson_topics', ['title' => 'Failed caption']);
    }

Add imports for Lesson, LessonTopic, LessonTopicCaption, Module, User,
RefreshDatabase, UploadedFile, Storage, and RuntimeException. In setUp(), fake
the public disk. Add these helpers:

    private function vtt(string $name, string $cue = 'Hello'): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($name, $this->vttText($cue));
    }

    private function vttText(string $cue = 'Hello'): string
    {
        return "WEBVTT\n\n00:00.000 --> 00:02.000\n".$cue."\n";
    }

    private function video(): UploadedFile
    {
        return UploadedFile::fake()->create('video.mp4', 100, 'video/mp4');
    }

    private function localVideoTopic(Lesson $lesson): LessonTopic
    {
        Storage::disk('public')->put('videos/current.mp4', 'video');

        return LessonTopic::factory()->create([
            'lesson_id' => $lesson->id,
            'type' => 'video',
            'duration' => 3,
            'video_provider' => 'local',
            'video_file_path' => 'videos/current.mp4',
        ]);
    }

    private function topicAuthoringFixture(): array
    {
        $instructor = User::factory()->create();
        $instructor->assignRole('instructor');
        $module = Module::factory()->create(['created_by' => $instructor->id]);
        $lesson = Lesson::factory()->create(['module_id' => $module->id]);

        return [$instructor, $lesson];
    }

- [ ] **Step 2: Run the feature test and verify it fails**

    php artisan test tests/Feature/Instructor/VideoCaptionManagementTest.php

Expected: FAIL because the controller ignores caption input and caption rows/files are not synchronized. The authorization test should already return 403, characterizing the existing TopicPolicy.

- [ ] **Step 3: Implement the caption synchronization service**

Create app/Services/LessonTopicCaptionService.php:

    <?php

    namespace App\Services;

    use App\Models\LessonTopic;
    use App\Models\LessonTopicCaption;
    use Illuminate\Http\UploadedFile;
    use Illuminate\Support\Facades\Storage;
    use Illuminate\Validation\ValidationException;
    use RuntimeException;
    use Throwable;

    final class LessonTopicCaptionService
    {
        /**
         * @param array<int, array<string, mixed>> $tracks
         * @return array{stored: array<int, string>, obsolete: array<int, string>}
         */
        public function sync(
            LessonTopic $topic,
            array $tracks,
            ?int $defaultIndex,
        ): array {
            $disk = Storage::disk('public');
            $storedPaths = [];
            $obsoletePaths = [];

            try {
                $existing = $topic->captions()
                    ->lockForUpdate()
                    ->get()
                    ->keyBy(fn (LessonTopicCaption $caption): int => (int) $caption->id);
                $activeTracks = collect($tracks)->reject(
                    fn (array $track): bool => filter_var(
                        $track['remove'] ?? false,
                        FILTER_VALIDATE_BOOL,
                    ),
                );
                $submittedIds = $activeTracks
                    ->pluck('id')
                    ->filter()
                    ->map(fn ($id): int => (int) $id)
                    ->unique()
                    ->values();

                if ($submittedIds->diff($existing->keys())->isNotEmpty()) {
                    throw ValidationException::withMessages([
                        'captions' => 'A submitted caption does not belong to this topic.',
                    ]);
                }

                foreach ($existing as $id => $caption) {
                    if (! $submittedIds->contains((int) $id)) {
                        $obsoletePaths[] = $caption->file_path;
                        $caption->deleteOrFail();
                        continue;
                    }

                    $caption->forceFill([
                        'language_code' => 'x-tmp-'.$caption->id,
                        'is_default' => false,
                    ])->saveOrFail();
                }

                foreach ($activeTracks as $index => $track) {
                    $caption = ! empty($track['id'])
                        ? $existing->get((int) $track['id'])
                        : new LessonTopicCaption;
                    $oldPath = $caption->file_path;
                    $file = $track['file'] ?? null;

                    if ($file instanceof UploadedFile) {
                        $path = $file->store('captions/'.$topic->id, 'public');
                        if (! is_string($path) || $path === '') {
                            throw new RuntimeException('Failed to store caption upload.');
                        }

                        $storedPaths[] = $path;
                        $caption->file_path = $path;
                        if ($caption->exists && $oldPath) {
                            $obsoletePaths[] = $oldPath;
                        }
                    }

                    if (! $caption->file_path) {
                        throw ValidationException::withMessages([
                            'captions.'.$index.'.file' => 'A WebVTT file is required.',
                        ]);
                    }

                    $caption->lesson_topic_id = $topic->id;
                    $caption->language_code = strtolower(trim($track['language_code']));
                    $caption->label = trim($track['label']);
                    $caption->is_default = $defaultIndex !== null
                        && (int) $index === $defaultIndex;
                    $caption->saveOrFail();
                }

                return [
                    'stored' => array_values(array_unique($storedPaths)),
                    'obsolete' => array_values(array_unique(array_filter($obsoletePaths))),
                ];
            } catch (Throwable $exception) {
                $disk->delete($storedPaths);
                throw $exception;
            }
        }
    }

Do not authorize or open a second transaction in the service; the controller supplies the authorized topic and ambient transaction.

- [ ] **Step 4: Add nested caption validation to TopicController**

Import App\Rules\WebVttFile, App\Services\LessonTopicCaptionService,
Illuminate\Support\Facades\DB, Illuminate\Support\Facades\Storage,
Illuminate\Support\Facades\Validator, and Throwable. Inject
LessonTopicCaptionService as the third constructor dependency. Add:

    private function validateCaptionInput(
        Request $request,
        ?LessonTopic $topic,
        string $type,
        ?string $videoSource,
    ): array {
        $validator = Validator::make($request->all(), [
            'captions' => ['nullable', 'array'],
            'captions.*.id' => ['nullable', 'integer'],
            'captions.*.file' => ['nullable', 'file', 'max:2048', new WebVttFile],
            'captions.*.language_code' => [
                'nullable',
                'string',
                'max:35',
                'regex:/^[A-Za-z]{2,3}(?:-[A-Za-z0-9]{2,8})*$/',
            ],
            'captions.*.label' => ['nullable', 'string', 'max:100'],
            'captions.*.remove' => ['nullable', 'boolean'],
            'caption_default' => ['nullable', 'integer', 'min:0'],
        ]);

        $validator->after(function ($validator) use (
            $request,
            $topic,
            $type,
            $videoSource,
        ): void {
            $tracks = $request->input('captions', []);
            $isLocalVideo = $type === 'video' && $videoSource === 'upload';
            $allowedIds = $topic
                ? $topic->captions()->pluck('id')->map(fn ($id): int => (int) $id)->all()
                : [];
            $activeIndexes = [];
            $seenLanguages = [];

            if (! $isLocalVideo && ($tracks !== [] || $request->hasFile('captions'))) {
                $validator->errors()->add(
                    'captions',
                    'Caption files are available only for uploaded local videos.',
                );
                return;
            }

            foreach ($tracks as $index => $track) {
                if (filter_var($track['remove'] ?? false, FILTER_VALIDATE_BOOL)) {
                    continue;
                }

                $activeIndexes[] = (int) $index;
                $id = isset($track['id']) ? (int) $track['id'] : null;
                $file = $request->file('captions.'.$index.'.file');
                $language = strtolower(trim((string) ($track['language_code'] ?? '')));
                $label = trim((string) ($track['label'] ?? ''));

                if ($id && (! $topic || ! in_array($id, $allowedIds, true))) {
                    $validator->errors()->add(
                        'captions.'.$index.'.id',
                        'The selected caption does not belong to this topic.',
                    );
                }
                if (! $id && ! $file) {
                    $validator->errors()->add(
                        'captions.'.$index.'.file',
                        'A WebVTT file is required.',
                    );
                }
                if ($language === '') {
                    $validator->errors()->add(
                        'captions.'.$index.'.language_code',
                        'A caption language code is required.',
                    );
                } elseif (isset($seenLanguages[$language])) {
                    $validator->errors()->add(
                        'captions.'.$index.'.language_code',
                        'Each caption language may be added only once.',
                    );
                } else {
                    $seenLanguages[$language] = true;
                }
                if ($label === '') {
                    $validator->errors()->add(
                        'captions.'.$index.'.label',
                        'A learner-facing caption label is required.',
                    );
                }
            }

            $default = $request->input('caption_default');
            if ($default !== null
                && $default !== ''
                && ! in_array((int) $default, $activeIndexes, true)) {
                $validator->errors()->add(
                    'caption_default',
                    'The default caption must reference an active track.',
                );
            }
        });

        $validated = $validator->validate();

        return [
            'tracks' => $validated['captions'] ?? [],
            'default' => isset($validated['caption_default'])
                ? (int) $validated['caption_default']
                : null,
        ];
    }

Call this method after the existing topic validation and before any file is stored.

- [ ] **Step 5: Coordinate create/update transactions and cleanup**

For store(), wrap topic creation, duration updates, module-duration updates, and the final caption sync in DB::transaction(). Track the returned file-change array. On any exception, delete the newly stored video and the returned stored caption paths; the service cleans paths created before an internal exception. After commit, delete only obsolete paths.

For update(), preserve captions when a local update request omits both captions and caption_default, which keeps existing non-caption clients backward compatible. Call sync when:

- caption input is present; or
- the final type/provider is not a local video, in which case pass an empty array and null default.

Keep new video upload before old video deletion. Put topic update, duration updates, module update, and caption sync in one transaction. After commit, delete the prior video plus obsolete caption paths. On failure, delete only the replacement video and newly stored caption paths.

For destroy(), load caption paths before deleting the topic, delete the topic, then delete its video, worksheet/image, and caption files only after persistence succeeds.

Add caption_default and captions to the existing temporary-field cleanup so nested request data is never mass-assigned to LessonTopic.

- [ ] **Step 6: Run the caption and existing upload tests**

    php artisan test tests/Feature/Instructor/VideoCaptionManagementTest.php
    php artisan test tests/Feature/Instructor/VideoUploadTest.php

Expected: both files pass with 0 failures. Existing local video replacement behavior remains green.

- [ ] **Step 7: Format and commit Task 3**

    vendor/bin/pint --test app/Services/LessonTopicCaptionService.php app/Http/Controllers/Instructor/TopicController.php tests/Feature/Instructor/VideoCaptionManagementTest.php
    git add app/Services/LessonTopicCaptionService.php app/Http/Controllers/Instructor/TopicController.php tests/Feature/Instructor/VideoCaptionManagementTest.php
    git commit -m "feat(video): manage caption track files"

---

### Task 4: Add the shared caption authoring interface

**Files:**
- Create: resources/views/instructor/topics/partials/caption-tracks.blade.php
- Create: resources/js/caption-tracks-form.js
- Modify: resources/js/app.js:1-18
- Modify: resources/js/video-upload-form.js:203-215
- Modify: resources/views/instructor/topics/create.blade.php:179-220,590-607
- Modify: resources/views/instructor/topics/edit.blade.php:211-261,552-564
- Create: tests/Unit/JavaScript/caption-tracks-form.test.js
- Modify: tests/Feature/Instructor/VideoCaptionManagementTest.php

**Interfaces:**
- Consumes: data-caption-tracks-form, data-caption-row, data-caption-template, data-add-caption, data-remove-caption, and video_source controls.
- Produces: indexed nested caption fields compatible with Task 3 and accessible add/remove/default controls.

- [ ] **Step 1: Write failing JavaScript helper tests**

Create tests/Unit/JavaScript/caption-tracks-form.test.js:

    import test from 'node:test';
    import assert from 'node:assert/strict';
    import {
        CAPTION_MAX_BYTES,
        captionFileError,
        captionRowHtml,
    } from '../../../resources/js/caption-tracks-form.js';

    test('accepts VTT through the exact 2 MiB boundary', () => {
        assert.equal(CAPTION_MAX_BYTES, 2097152);
        assert.equal(captionFileError({
            name: 'english.vtt',
            size: CAPTION_MAX_BYTES,
            type: 'text/vtt',
        }), null);
    });

    test('rejects oversized and non-VTT selections', () => {
        assert.match(captionFileError({
            name: 'english.vtt',
            size: CAPTION_MAX_BYTES + 1,
            type: 'text/vtt',
        }), /2 MB/);
        assert.match(captionFileError({
            name: 'english.srt',
            size: 100,
            type: 'text/plain',
        }), /WebVTT/);
    });

    test('substitutes every template index', () => {
        assert.equal(
            captionRowHtml('captions[__INDEX__][file]-__INDEX__', 4),
            'captions[4][file]-4',
        );
    });

- [ ] **Step 2: Add a failing form-contract test**

Add test_authoring_pages_render_caption_management_only_for_authorized_topic_forms() to VideoCaptionManagementTest. Assert the owner's create and edit responses contain:

    data-caption-tracks-form
    data-caption-template
    data-add-caption
    name="caption_default"
    accept=".vtt,text/vtt,text/plain"
    WebVTT up to 2 MB

Assert another instructor receives 403 for the edit page and therefore never receives the caption controls.

- [ ] **Step 3: Run both focused tests and verify they fail**

    node --test tests/Unit/JavaScript/caption-tracks-form.test.js
    php artisan test tests/Feature/Instructor/VideoCaptionManagementTest.php --filter=authoring_pages

Expected: FAIL because the module and shared partial do not exist.

- [ ] **Step 4: Implement caption-tracks-form.js**

Create resources/js/caption-tracks-form.js:

    export const CAPTION_MAX_BYTES = 2 * 1024 * 1024;
    const CAPTION_MIMES = new Set(['text/vtt', 'text/plain']);

    export function captionFileError(file) {
        if (!file) {
            return null;
        }
        if (file.size > CAPTION_MAX_BYTES) {
            return 'WebVTT caption files must be 2 MB or smaller.';
        }
        if (!file.name.toLowerCase().endsWith('.vtt')) {
            return 'Caption files must use the WebVTT (.vtt) format.';
        }
        if (file.type && !CAPTION_MIMES.has(file.type)) {
            return 'Caption files must contain WebVTT text.';
        }

        return null;
    }

    export function captionRowHtml(html, index) {
        return html.replaceAll('__INDEX__', String(index));
    }

    export function initializeCaptionTracksForm(root) {
        if (root.dataset.captionTracksInitialized === 'true') {
            return;
        }
        root.dataset.captionTracksInitialized = 'true';

        const form = root.closest('form');
        const rows = root.querySelector('[data-caption-rows]');
        const template = root.querySelector('[data-caption-template]');
        const noDefault = root.querySelector('[data-caption-no-default]');
        let nextIndex = Number(root.dataset.nextIndex || 0);

        const sourceIsLocal = () => {
            const select = form?.querySelector('select[name="video_source"]');
            const checked = form?.querySelector('input[name="video_source"]:checked');
            return (select?.value || checked?.value) === 'upload';
        };

        const syncAvailability = () => {
            const enabled = sourceIsLocal();
            root.classList.toggle('hidden', !enabled);
            root.querySelectorAll('[data-caption-row]').forEach((row) => {
                const removed = row.dataset.removed === 'true';
                row.querySelectorAll('input, button').forEach((control) => {
                    control.disabled = !enabled || removed;
                });
                if (enabled && removed) {
                    row.querySelector('[data-caption-id]')?.removeAttribute('disabled');
                    row.querySelector('[data-caption-remove-value]')?.removeAttribute('disabled');
                }
            });
        };

        root.addEventListener('click', (event) => {
            if (event.target.closest('[data-add-caption]')) {
                rows.insertAdjacentHTML(
                    'beforeend',
                    captionRowHtml(template.innerHTML, nextIndex),
                );
                nextIndex += 1;
                root.dataset.nextIndex = String(nextIndex);
                return;
            }

            const removeButton = event.target.closest('[data-remove-caption]');
            if (!removeButton) {
                return;
            }

            const row = removeButton.closest('[data-caption-row]');
            const id = row.querySelector('[data-caption-id]')?.value;
            if (row.querySelector('input[name="caption_default"]:checked')) {
                noDefault.checked = true;
            }

            if (!id) {
                row.remove();
                return;
            }

            row.dataset.removed = 'true';
            row.hidden = true;
            row.querySelector('[data-caption-remove-value]').value = '1';
            syncAvailability();
        });

        root.addEventListener('change', (event) => {
            if (!event.target.matches('[data-caption-file]')) {
                return;
            }

            const row = event.target.closest('[data-caption-row]');
            const file = event.target.files?.[0];
            const error = captionFileError(file);
            const errorElement = row.querySelector('[data-caption-file-error]');
            const nameElement = row.querySelector('[data-caption-file-name]');

            errorElement.textContent = error || '';
            errorElement.classList.toggle('hidden', !error);
            event.target.setAttribute('aria-invalid', error ? 'true' : 'false');
            if (error) {
                event.target.value = '';
            } else if (file) {
                nameElement.textContent = file.name;
            }
        });

        form?.querySelectorAll('[name="video_source"]').forEach((control) => {
            control.addEventListener('change', syncAvailability);
        });
        syncAvailability();
    }

    if (typeof document !== 'undefined') {
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('[data-caption-tracks-form]')
                .forEach((root) => initializeCaptionTracksForm(root));
        });
    }

The shared partial must give data-caption-id to the hidden ID input,
data-caption-remove-value to the hidden removal input, data-caption-file to
the upload, and the remaining hooks used above. Event delegation ensures
dynamically added rows require no extra listeners.

- [ ] **Step 5: Create the shared Blade partial**

The partial must reconstruct rows from old('captions') first and otherwise from $topic?->captions. Render:

- a heading and concise .vtt / 2 MB help;
- a No default radio with value "";
- one saved/old-input row per index;
- hidden id and remove inputs;
- language_code input with list="caption-language-codes";
- label input;
- replacement/new .vtt file input;
- one default radio whose value is the row index;
- remove button and inline field errors;
- saved-track View caption file link with target="_blank" and rel="noopener";
- a datalist containing en, fil, ko, es, fr, de, ja, and zh suggestions;
- a template using __INDEX__ placeholders;
- an Add caption track button.

Include the partial immediately after the local video file input in both create and edit views. In TopicController::edit(), eager-load captions before returning the view.

- [ ] **Step 6: Keep source toggles and AJAX errors compatible**

Import ./caption-tracks-form beside ./video-upload-form in app.js.

Update both existing toggleVideoSource functions to dispatch change normally; the caption module reads the same control and disables its own fields. Do not add another submit listener.

In video-upload-form.js, replace the video-only server error lookup with the first validation message:

    const firstValidationError = Object.values(payload.errors || {})
        .flat()
        .find(Boolean);
    const message = xhr.status === 413
        ? 'The upload is larger than the server request limit.'
        : firstValidationError
            || payload.message
            || 'The topic could not be saved. Please try again.';

Extend video-upload-form.test.js with a failed XHR payload containing captions.0.file and assert that its message reaches the shared form error.

- [ ] **Step 7: Run focused tests and build**

    node --test tests/Unit/JavaScript/caption-tracks-form.test.js tests/Unit/JavaScript/video-upload-form.test.js
    php artisan test tests/Feature/Instructor/VideoCaptionManagementTest.php
    npm run build

Expected: all focused tests pass and Vite exits 0.

- [ ] **Step 8: Commit Task 4**

    git add resources/views/instructor/topics/partials/caption-tracks.blade.php resources/views/instructor/topics/create.blade.php resources/views/instructor/topics/edit.blade.php resources/js/caption-tracks-form.js resources/js/video-upload-form.js resources/js/app.js tests/Unit/JavaScript/caption-tracks-form.test.js tests/Unit/JavaScript/video-upload-form.test.js tests/Feature/Instructor/VideoCaptionManagementTest.php public/build
    git commit -m "feat(video): add caption authoring fields"

---

### Task 5: Render tracks and configure the existing Plyr instance

**Files:**
- Create: resources/js/video-player.js
- Modify: resources/js/app.js:114-139
- Modify: app/Http/Controllers/Learner/LessonController.php:57-65,212-216
- Modify: resources/views/learner/lessons/partials/topic-page.blade.php:79-105
- Create: tests/Unit/JavaScript/video-player.test.js
- Create: tests/Feature/Learner/VideoCaptionRenderingTest.php

**Interfaces:**
- Consumes: static HTML track elements on one .plyr-video element.
- Produces: plyrOptionsFor(video): object and one new Plyr(video, options) call per detected element.

- [ ] **Step 1: Write failing Plyr option tests**

Create tests/Unit/JavaScript/video-player.test.js:

    import test from 'node:test';
    import assert from 'node:assert/strict';
    import { plyrOptionsFor } from '../../../resources/js/video-player.js';

    const video = (tracks) => ({
        querySelectorAll() {
            return tracks;
        },
    });

    test('omits dead caption controls when no tracks exist', () => {
        const options = plyrOptionsFor(video([]));
        assert.equal(options.controls.includes('captions'), false);
        assert.deepEqual(options.settings, ['speed']);
        assert.equal('captions' in options, false);
    });

    test('keeps captions off with automatic language when no default exists', () => {
        const options = plyrOptionsFor(video([
            { default: false, srclang: 'en' },
            { default: false, srclang: 'ko' },
        ]));
        assert.equal(options.captions.active, false);
        assert.equal(options.captions.language, 'auto');
        assert.equal(options.controls.includes('captions'), true);
        assert.deepEqual(options.settings, ['captions', 'speed']);
    });

    test('activates the configured default language', () => {
        const options = plyrOptionsFor(video([
            { default: false, srclang: 'en' },
            { default: true, srclang: 'fil' },
        ]));
        assert.equal(options.captions.active, true);
        assert.equal(options.captions.language, 'fil');
        assert.equal(options.captions.update, false);
    });

- [ ] **Step 2: Write failing learner-rendering tests**

Create VideoCaptionRenderingTest using the enrollment fixture pattern from tests/Feature/Learner/LessonPageTest.php. Add three tests:

1. A local video with English and Korean captions renders two kind="subtitles" tracks with their exact src, srclang, and escaped label values, and exactly one default attribute.
2. A local video with no captions renders no track element.
3. An external YouTube topic renders the existing iframe and no local track even if a caption relation is manually attached.

Use Storage::fake('public'), create the caption rows through $topic->captions(), and request route('learner.lessons.show', $lesson) as an approved enrolled learner.

- [ ] **Step 3: Run both tests and verify they fail**

    node --test tests/Unit/JavaScript/video-player.test.js
    php artisan test tests/Feature/Learner/VideoCaptionRenderingTest.php

Expected: FAIL because video-player.js does not exist and the learner partial still reads caption_file_path.

- [ ] **Step 4: Implement video-player.js**

Create resources/js/video-player.js:

    const BASE_CONTROLS = [
        'play-large',
        'play',
        'progress',
        'current-time',
        'mute',
        'volume',
        'settings',
        'fullscreen',
    ];

    export function plyrOptionsFor(video) {
        const tracks = Array.from(video.querySelectorAll(
            'track[kind="subtitles"], track[kind="captions"]',
        ));
        const hasCaptions = tracks.length > 0;
        const defaultTrack = tracks.find((track) => track.default);
        const controls = [...BASE_CONTROLS];

        if (hasCaptions) {
            controls.splice(6, 0, 'captions');
        }

        const options = {
            speed: { selected: 1, options: [0.5, 0.75, 1, 1.25, 1.5, 2] },
            controls,
            settings: hasCaptions ? ['captions', 'speed'] : ['speed'],
        };

        if (hasCaptions) {
            options.captions = {
                active: Boolean(defaultTrack),
                language: defaultTrack?.srclang || 'auto',
                update: false,
            };
        }

        return options;
    }

Import plyrOptionsFor at the top of app.js. Keep the existing DOMContentLoaded handler, dynamic Plyr/CSS import, .plyr-video query, and one players.forEach loop. Replace only the inline options object with:

    new Plyr(el, plyrOptionsFor(el));

- [ ] **Step 5: Eager-load and render caption records**

Add topics.captions to the all-lessons eager load and captions to the current lessonTopics eager load in Learner\LessonController::show().

Replace the legacy caption_file_path block in topic-page.blade.php with:

    @foreach($currentTopic->captions as $caption)
        <track
            kind="subtitles"
            src="{{ $caption->file_url }}"
            srclang="{{ $caption->language_code }}"
            label="{{ $caption->label }}"
            @if($caption->is_default) default @endif
        >
    @endforeach

Do not add track elements to the iframe branch and do not add custom caption controls or caption CSS.

- [ ] **Step 6: Run learner, JavaScript, and build checks**

    node --test tests/Unit/JavaScript/video-player.test.js
    php artisan test tests/Feature/Learner/VideoCaptionRenderingTest.php tests/Feature/Learner/LessonPageTest.php
    npm run build

Expected: all tests pass, Vite exits 0, and the compiled manifest references the rebuilt application bundle.

- [ ] **Step 7: Commit Task 5**

    git add resources/js/video-player.js resources/js/app.js app/Http/Controllers/Learner/LessonController.php resources/views/learner/lessons/partials/topic-page.blade.php tests/Unit/JavaScript/video-player.test.js tests/Feature/Learner/VideoCaptionRenderingTest.php public/build
    git commit -m "feat(video): expose caption tracks in Plyr"

---

### Task 6: Verify migrations, regressions, accessibility, and responsive playback

**Files:**
- Create: docs/superpowers/verification/2026-09-20-video-caption-support.md

**Interfaces:**
- Consumes: all completed caption tasks.
- Produces: fresh automated evidence and a manual QA record without changing development data outside normal disposable-topic flows.

- [ ] **Step 1: Confirm the development schema before applying the incremental migration**

    php artisan migrate:status

Expected: existing migrations are listed without errors. Do not run migrate:fresh, db:wipe, or any reset command.

- [ ] **Step 2: Apply only normal pending migrations**

    php artisan migrate

Expected: 2026_09_20_000001_create_lesson_topic_captions_table runs once, preserves legacy paths in lesson_topic_captions, and reports no destructive reset.

- [ ] **Step 3: Run all focused server tests**

    php artisan test tests/Feature/Database/VideoCaptionMigrationTest.php tests/Unit/Rules/WebVttFileTest.php tests/Feature/Instructor/VideoCaptionManagementTest.php tests/Feature/Instructor/VideoUploadTest.php tests/Feature/Learner/VideoCaptionRenderingTest.php tests/Feature/Learner/LessonPageTest.php

Expected: 0 failures.

- [ ] **Step 4: Run all JavaScript tests and the production build**

    node --test tests/Unit/JavaScript
    npm run build

Expected: 0 Node test failures and Vite exit code 0.

- [ ] **Step 5: Run formatting, whitespace, and full regressions**

    vendor/bin/pint --test
    git diff --check
    php artisan test

Expected: Pint, whitespace validation, and the full PHPUnit suite exit 0. Record exact unrelated pre-existing failures instead of changing unrelated code.

- [ ] **Step 6: Perform authoring QA with disposable records**

Record each result in docs/superpowers/verification/2026-09-20-video-caption-support.md:

1. Create a local MP4 topic with English VTT and save.
2. Reopen edit and confirm metadata plus View caption file.
3. Replace the English file and confirm the old URL no longer resolves.
4. Add Filipino and Korean, change the default, and save.
5. Remove one track and confirm its file is gone.
6. Confirm an unrelated instructor receives 403 and no controls.
7. Switch a disposable local topic to YouTube and confirm captions are removed.

- [ ] **Step 7: Perform learner and responsive QA**

For local topics with zero, one, and multiple caption tracks, verify:

- no dead caption control with zero tracks;
- toggle and language selection;
- configured default and no-default behavior;
- cue synchronization after seeking;
- desktop and mobile responsive layout;
- fullscreen captions;
- play/pause, progress, speed, volume, and mute regressions.

Verify a YouTube/Vimeo topic still uses the unchanged iframe. Include browser/viewport, result, and any limitation in the verification document.

- [ ] **Step 8: Inspect final scope**

    git status --short
    git diff --stat HEAD~5
    git diff --check

Expected: only caption feature source/tests, generated Vite assets, and the verification record are present. Leave storage/framework/lsp-b7c5039063be9f4e.php untracked and unstaged.

- [ ] **Step 9: Commit verification evidence**

    git add docs/superpowers/verification/2026-09-20-video-caption-support.md
    git commit -m "docs(video): verify caption support"
