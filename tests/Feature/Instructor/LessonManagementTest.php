<?php

namespace Tests\Feature\Instructor;

use App\Models\Lesson;
use App\Models\LessonTopic;
use App\Models\Module;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LessonManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_lesson_create_page_loads_for_instructor(): void
    {
        /** @var User $instructor */
        $instructor = User::factory()->createOne();
        $instructor->assignRole('instructor');

        Module::factory()->create([
            'created_by' => $instructor->id,
            'is_published' => true,
        ]);

        $this->actingAs($instructor)
            ->get(route('instructor.lessons.create'))
            ->assertOk();
    }

    public function test_updating_a_lesson_excludes_optional_interactions_from_duration(): void
    {
        $instructor = User::factory()->createOne();
        $instructor->assignRole('instructor');
        $module = Module::factory()->create(['created_by' => $instructor->id]);
        $lesson = Lesson::factory()->create(['module_id' => $module->id, 'duration' => 99]);
        LessonTopic::factory()->create(['lesson_id' => $lesson->id, 'type' => 'text', 'duration' => 6]);
        LessonTopic::factory()->create(['lesson_id' => $lesson->id, 'type' => 'interactive', 'duration' => 30]);
        LessonTopic::factory()->create(['lesson_id' => $lesson->id, 'type' => 'interactive_checkpoint', 'duration' => 20]);

        $this->actingAs($instructor)
            ->put(route('instructor.lessons.update', $lesson), [
                'module_id' => $module->id,
                'title' => $lesson->title,
                'description' => $lesson->description,
            ])
            ->assertRedirect();

        $this->assertSame(6, $lesson->fresh()->duration);
        $this->assertSame(6, $module->fresh()->duration_minutes);
    }

    public function test_generic_topic_authoring_rejects_legacy_interactive_topics(): void
    {
        $instructor = User::factory()->createOne();
        $instructor->assignRole('instructor');
        $module = Module::factory()->create(['created_by' => $instructor->id]);
        $lesson = Lesson::factory()->create(['module_id' => $module->id]);
        $topic = LessonTopic::factory()->create(['lesson_id' => $lesson->id, 'type' => 'text']);

        $this->actingAs($instructor)
            ->post(route('instructor.topics.store'), [
                'lesson_id' => $lesson->id,
                'title' => 'Legacy interactive',
                'type' => 'interactive',
                'duration' => 99,
                'is_prerequisite' => 1,
            ])
            ->assertSessionHasErrors('type');

        $this->assertSame(1, $lesson->topics()->count());

        $this->actingAs($instructor)
            ->put(route('instructor.topics.update', $topic), [
                'title' => 'Legacy interactive update',
                'type' => 'interactive',
                'duration' => 99,
                'is_prerequisite' => 1,
            ])
            ->assertSessionHasErrors('type');

        $topic->refresh();
        $this->assertSame('text', $topic->type);
        $this->assertSame(5, $topic->duration);
        $this->assertFalse($topic->is_prerequisite);
    }

    public function test_instructor_can_create_a_video_topic_from_topic_creation(): void
    {
        [$instructor, $lesson] = $this->topicAuthoringFixture();

        $this->actingAs($instructor)
            ->post(route('instructor.topics.store'), [
                'lesson_id' => $lesson->id,
                'title' => 'Video topic',
                'type' => 'video',
                'duration' => 5,
                'video_source' => 'url',
                'video_url' => 'https://youtu.be/dQw4w9WgXcQ',
            ])
            ->assertRedirect(route('instructor.lessons.show', $lesson));

        $this->assertDatabaseHas('lesson_topics', [
            'lesson_id' => $lesson->id,
            'title' => 'Video topic',
            'type' => 'video',
        ]);
    }

    public function test_instructor_can_create_a_text_topic_from_topic_creation(): void
    {
        [$instructor, $lesson] = $this->topicAuthoringFixture();

        $this->actingAs($instructor)
            ->post(route('instructor.topics.store'), [
                'lesson_id' => $lesson->id,
                'title' => 'Text topic',
                'type' => 'text',
                'duration' => 5,
                'text_content' => '<p>Topic body</p>',
            ])
            ->assertRedirect(route('instructor.lessons.show', $lesson));

        $this->assertDatabaseHas('lesson_topics', [
            'lesson_id' => $lesson->id,
            'title' => 'Text topic',
            'type' => 'text',
        ]);
    }

    public function test_instructor_can_create_a_worksheet_topic_from_topic_creation(): void
    {
        Storage::fake('public');
        [$instructor, $lesson] = $this->topicAuthoringFixture();

        $this->actingAs($instructor)
            ->post(route('instructor.topics.store'), [
                'lesson_id' => $lesson->id,
                'title' => 'Worksheet topic',
                'type' => 'worksheet',
                'duration' => 5,
                'worksheet_files' => [UploadedFile::fake()->create('worksheet.pdf', 100, 'application/pdf')],
                'worksheet_instructions' => 'Complete the worksheet.',
            ])
            ->assertRedirect(route('instructor.lessons.show', $lesson));

        $this->assertDatabaseHas('lesson_topics', [
            'lesson_id' => $lesson->id,
            'title' => 'Worksheet topic',
            'type' => 'worksheet',
        ]);
    }

    /** @return array{User, Lesson} */
    private function topicAuthoringFixture(): array
    {
        $instructor = User::factory()->createOne();
        $instructor->assignRole('instructor');
        $module = Module::factory()->create(['created_by' => $instructor->id]);
        $lesson = Lesson::factory()->create(['module_id' => $module->id]);

        return [$instructor, $lesson];
    }
}
