<?php

namespace Tests\Feature\Admin;

use App\Models\Lesson;
use App\Models\LessonTopic;
use App\Models\Module;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\DatabaseTestCase;

class AdminSharedTopicQuizControllerTest extends DatabaseTestCase
{
    use DatabaseTransactions;

    public function test_admin_topic_store_redirects_to_admin_lesson_show(): void
    {
        $admin = $this->createUser('admin');
        $module = Module::factory()->create([
            'created_by' => $admin->id,
            'content_owner_type' => 'admin',
        ]);
        $lesson = Lesson::factory()->create([
            'module_id' => $module->id,
        ]);

        $response = $this->actingAs($admin)->post(route('admin.topics.store'), [
            'lesson_id' => $lesson->id,
            'title' => 'Admin Topic',
            'type' => 'text',
            'duration' => 5,
            'text_content' => 'Topic body',
        ]);

        $topic = LessonTopic::query()->where('title', 'Admin Topic')->firstOrFail();

        $response->assertRedirect(route('admin.lessons.show', $topic->lesson));
    }

    public function test_admin_quiz_store_redirects_to_admin_quiz_show(): void
    {
        $admin = $this->createUser('admin');
        $module = Module::factory()->create([
            'created_by' => $admin->id,
            'content_owner_type' => 'admin',
        ]);

        $response = $this->actingAs($admin)->post(route('admin.quizzes.store'), [
            'title' => 'Admin Quiz',
            'description' => 'Quiz description',
            'module_id' => $module->id,
            'passing_score' => 70,
            'is_active' => 1,
        ]);

        $quiz = Quiz::query()->where('title', 'Admin Quiz')->firstOrFail();

        $response->assertRedirect(route('admin.quizzes.show', $quiz));
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
