<?php

namespace Tests\Feature\Rbac;

use App\Models\Lesson;
use App\Models\LessonTopic;
use App\Models\Module;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class RbacContentPolicyEnforcementTest extends TestCase
{
    public function test_owner_can_update_content_resources_but_non_owner_cannot(): void
    {
        $owner = User::factory()->create(['role' => 'learner', 'status' => 'active']);
        $owner->assignRole('instructor');

        $other = User::factory()->create(['role' => 'learner', 'status' => 'active']);
        $other->assignRole('instructor');

        $module = Module::factory()->create([
            'created_by' => $owner->id,
            'content_owner_type' => 'instructor',
        ]);

        $lesson = Lesson::factory()->create([
            'module_id' => $module->id,
        ]);

        $topic = LessonTopic::factory()->create([
            'lesson_id' => $lesson->id,
        ]);

        $quiz = Quiz::factory()->create([
            'module_id' => $module->id,
            'lesson_id' => $lesson->id,
        ]);

        $this->assertTrue(Gate::forUser($owner)->allows('update', $module));
        $this->assertFalse(Gate::forUser($other)->allows('update', $module));

        $this->assertTrue(Gate::forUser($owner)->allows('update', $lesson));
        $this->assertFalse(Gate::forUser($other)->allows('update', $lesson));

        $this->assertTrue(Gate::forUser($owner)->allows('update', $topic));
        $this->assertFalse(Gate::forUser($other)->allows('update', $topic));

        $this->assertTrue(Gate::forUser($owner)->allows('update', $quiz));
        $this->assertFalse(Gate::forUser($other)->allows('update', $quiz));
    }
}
