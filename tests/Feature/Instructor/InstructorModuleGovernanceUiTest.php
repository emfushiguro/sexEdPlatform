<?php

namespace Tests\Feature\Instructor;

use App\Models\Module;
use App\Models\ModuleReviewRequest;
use App\Models\ModuleRevision;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\DatabaseTestCase;

class InstructorModuleGovernanceUiTest extends DatabaseTestCase
{
    use DatabaseTransactions;

    public function test_instructor_module_pages_render_governance_status_feedback_and_actions(): void
    {
        $instructor = $this->createInstructor();
        $module = Module::factory()->create([
            'created_by' => $instructor->id,
            'content_owner_type' => 'instructor',
            'is_published' => false,
            'current_review_status' => 'needs_revision',
            'title' => 'Governed UI Module',
        ]);

        $revision = ModuleRevision::query()->create([
            'module_id' => $module->id,
            'revision_number' => 1,
            'snapshot_payload' => [
                'module' => ['id' => $module->id, 'title' => $module->title],
                'lessons' => [],
                'quizzes' => [],
            ],
            'submitted_by' => $instructor->id,
            'status' => 'needs_revision',
            'submitted_at' => now()->subDay(),
            'reviewed_at' => now(),
            'reviewed_by' => $instructor->id,
            'review_feedback' => 'Please revise the introduction.',
        ]);

        ModuleReviewRequest::query()->create([
            'module_id' => $module->id,
            'module_revision_id' => $revision->id,
            'status' => 'needs_revision',
            'submitted_by' => $instructor->id,
            'reviewed_by' => $instructor->id,
            'submitted_at' => now()->subDay(),
            'reviewed_at' => now(),
            'feedback' => 'Please revise the introduction.',
        ]);

        $this->actingAs($instructor)
            ->get(route('instructor.modules.index'))
            ->assertOk()
            ->assertSee('Review Status', false)
            ->assertSee('needs_revision', false);

        $this->actingAs($instructor)
            ->get(route('instructor.modules.show', $module))
            ->assertOk()
            ->assertSee('Review feedback', false)
            ->assertSee('Please revise the introduction.', false)
            ->assertSee('Resubmit for Review', false);
    }

    private function createInstructor(): User
    {
        $user = User::factory()->create([
            'role' => 'instructor',
            'status' => 'active',
        ]);
        $user->assignRole('instructor');

        return $user;
    }
}
