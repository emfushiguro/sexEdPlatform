<?php

namespace Tests\Feature\Admin;

use App\Models\Lesson;
use App\Models\LessonTopic;
use App\Models\Module;
use App\Models\Quiz;
use App\Models\User;
use App\Policies\LessonPolicy;
use App\Policies\QuizPolicy;
use App\Policies\TopicPolicy;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\DatabaseTestCase;

class AdminSharedContentPolicyTest extends DatabaseTestCase
{
    use DatabaseTransactions;

    public function test_admin_can_update_instructor_owned_lesson_topic_and_quiz(): void
    {
        $admin = $this->createUser('admin');
        $owner = $this->createUser('instructor');

        $module = Module::factory()->create(['created_by' => $owner->id]);
        $lesson = Lesson::factory()->create(['module_id' => $module->id]);
        $topic = LessonTopic::factory()->create(['lesson_id' => $lesson->id]);
        $quiz = Quiz::factory()->create(['module_id' => $module->id, 'lesson_id' => null]);

        $this->assertTrue((new LessonPolicy())->update($admin, $lesson));
        $this->assertTrue((new TopicPolicy())->update($admin, $topic));
        $this->assertTrue((new QuizPolicy())->update($admin, $quiz));
    }

    public function test_instructor_cannot_update_another_instructors_content(): void
    {
        $owner = $this->createUser('instructor');
        $otherInstructor = $this->createUser('instructor');

        $module = Module::factory()->create(['created_by' => $owner->id]);
        $lesson = Lesson::factory()->create(['module_id' => $module->id]);
        $topic = LessonTopic::factory()->create(['lesson_id' => $lesson->id]);
        $quiz = Quiz::factory()->create(['module_id' => $module->id, 'lesson_id' => null]);

        $this->assertFalse((new LessonPolicy())->update($otherInstructor, $lesson));
        $this->assertFalse((new TopicPolicy())->update($otherInstructor, $topic));
        $this->assertFalse((new QuizPolicy())->update($otherInstructor, $quiz));
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
