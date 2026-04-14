<?php

namespace Tests\Feature\Admin;

use App\Models\Lesson;
use App\Models\LessonTopic;
use App\Models\Module;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\DatabaseTestCase;

class AdminLearningContentOwnershipMutationBoundaryTest extends DatabaseTestCase
{
    use DatabaseTransactions;

    public function test_admin_cannot_mutate_instructor_owned_lessons_topics_and_quizzes(): void
    {
        $admin = $this->createUser('admin');
        $instructor = $this->createUser('instructor');

        $module = Module::factory()->create([
            'created_by' => $instructor->id,
            'content_owner_type' => 'instructor',
        ]);

        $lesson = Lesson::factory()->create([
            'module_id' => $module->id,
        ]);

        $topic = LessonTopic::factory()->create([
            'lesson_id' => $lesson->id,
            'type' => 'text',
        ]);

        $quiz = Quiz::factory()->create([
            'module_id' => $module->id,
            'lesson_id' => null,
            'passing_score' => 70,
        ]);

        $question = QuizQuestion::query()->create([
            'quiz_id' => $quiz->id,
            'question_text' => 'Existing question',
            'question_type' => 'multiple_choice',
            'points' => 1,
            'order' => 1,
        ]);

        $this->actingAs($admin)
            ->put(route('admin.lessons.update', $lesson), [
                'module_id' => $module->id,
                'title' => 'Blocked Lesson Update',
                'description' => 'Blocked',
                'is_published' => 1,
            ])
            ->assertForbidden();

        $this->actingAs($admin)
            ->patch(route('admin.lessons.move', $lesson), ['direction' => 'down'])
            ->assertForbidden();

        $this->actingAs($admin)
            ->patch(route('admin.lessons.reorder'), ['order' => [$lesson->id]])
            ->assertForbidden();

        $this->actingAs($admin)
            ->post(route('admin.topics.store'), [
                'lesson_id' => $lesson->id,
                'title' => 'Blocked Topic Create',
                'type' => 'text',
                'duration' => 5,
                'text_content' => 'Body',
            ])
            ->assertForbidden();

        $this->actingAs($admin)
            ->put(route('admin.topics.update', $topic), [
                'title' => 'Blocked Topic Update',
                'type' => 'text',
                'duration' => 5,
                'text_content' => 'Updated body',
            ])
            ->assertForbidden();

        $this->actingAs($admin)
            ->patch(route('admin.topics.reorder'), ['order' => [$topic->id]])
            ->assertForbidden();

        $this->actingAs($admin)
            ->put(route('admin.quizzes.update', $quiz), [
                'title' => 'Blocked Quiz Update',
                'description' => 'Blocked',
                'module_id' => $module->id,
                'passing_score' => 70,
                'is_active' => 1,
            ])
            ->assertForbidden();

        $this->actingAs($admin)
            ->post(route('admin.quizzes.store-question', $quiz), [
                'question_text' => 'Blocked Question Create',
            ])
            ->assertForbidden();

        $this->actingAs($admin)
            ->put(route('admin.quizzes.update-question', ['quiz' => $quiz, 'question' => $question]), [
                'question_text' => 'Blocked question update',
            ])
            ->assertForbidden();

        $this->actingAs($admin)
            ->delete(route('admin.quizzes.delete-question', ['quiz' => $quiz, 'question' => $question]))
            ->assertForbidden();

        $this->actingAs($admin)
            ->post(route('admin.quizzes.import.confirm', $quiz))
            ->assertForbidden();
    }

    public function test_admin_can_mutate_platform_owned_lessons_topics_and_quizzes(): void
    {
        $admin = $this->createUser('admin');

        $module = Module::factory()->create([
            'created_by' => $admin->id,
            'content_owner_type' => 'admin',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.lessons.store'), [
                'module_id' => $module->id,
                'title' => 'Platform Lesson',
                'description' => 'Platform lesson description',
                'is_published' => 1,
            ])
            ->assertRedirect();

        $lesson = Lesson::query()->where('title', 'Platform Lesson')->firstOrFail();

        $this->actingAs($admin)
            ->post(route('admin.topics.store'), [
                'lesson_id' => $lesson->id,
                'title' => 'Platform Topic',
                'type' => 'text',
                'duration' => 5,
                'text_content' => 'Topic body',
            ])
            ->assertRedirect();

        $this->actingAs($admin)
            ->post(route('admin.quizzes.store'), [
                'title' => 'Platform Quiz',
                'description' => 'Platform quiz description',
                'module_id' => $module->id,
                'passing_score' => 70,
                'is_active' => 1,
            ])
            ->assertRedirect();

        $quiz = Quiz::query()->where('title', 'Platform Quiz')->firstOrFail();

        $this->actingAs($admin)
            ->post(route('admin.quizzes.store-question', $quiz), [
                'question_text' => 'What is 2 + 2?',
                'question_type' => 'multiple_choice',
                'points' => 1,
                'options' => ['1', '4'],
                'correct_options' => [1],
            ])
            ->assertRedirect();
    }

    private function createUser(string $role): User
    {
        $user = User::factory()->create([
            'role' => $role,
            'status' => 'active',
        ]);

        $user->assignRole($role);

        return $user;
    }
}