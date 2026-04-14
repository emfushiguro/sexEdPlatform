<?php

namespace Tests\Unit\Services;

use App\Models\Lesson;
use App\Models\LessonTopic;
use App\Models\Module;
use App\Models\Quiz;
use App\Models\User;
use App\Services\Content\ContentOwnershipGuard;
use Tests\TestCase;

class ContentOwnershipGuardTest extends TestCase
{
    public function test_resolves_owner_types_for_module_lesson_topic_and_quiz(): void
    {
        $admin = $this->createUser('admin');
        $instructor = $this->createUser('instructor');

        $adminModule = Module::factory()->create([
            'created_by' => $admin->id,
            'content_owner_type' => 'admin',
        ]);

        $instructorModule = Module::factory()->create([
            'created_by' => $instructor->id,
            'content_owner_type' => 'instructor',
        ]);

        $lesson = Lesson::factory()->create([
            'module_id' => $instructorModule->id,
        ]);

        $topic = LessonTopic::factory()->create([
            'lesson_id' => $lesson->id,
        ]);

        $moduleQuiz = Quiz::factory()->create([
            'module_id' => $adminModule->id,
            'lesson_id' => null,
        ]);

        $lessonQuiz = Quiz::factory()->create([
            'module_id' => null,
            'lesson_id' => $lesson->id,
        ]);

        $guard = app(ContentOwnershipGuard::class);

        $this->assertSame('admin', $guard->ownerTypeForModule($adminModule));
        $this->assertSame('instructor', $guard->ownerTypeForModule($instructorModule));
        $this->assertSame('instructor', $guard->ownerTypeForLesson($lesson));
        $this->assertSame('instructor', $guard->ownerTypeForTopic($topic));
        $this->assertSame('admin', $guard->ownerTypeForQuiz($moduleQuiz));
        $this->assertSame('instructor', $guard->ownerTypeForQuiz($lessonQuiz));
    }

    public function test_allows_admin_mutations_only_for_platform_owner_types(): void
    {
        $guard = app(ContentOwnershipGuard::class);

        $this->assertTrue($guard->canAdminMutateOwnerType('admin'));
        $this->assertTrue($guard->canAdminMutateOwnerType('platform'));
        $this->assertFalse($guard->canAdminMutateOwnerType('instructor'));
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