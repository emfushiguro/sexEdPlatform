<?php

namespace Tests\Feature\Admin;

use App\Models\Module;
use App\Models\ModuleReviewRequest;
use App\Models\ModuleRevision;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\DatabaseTestCase;

class AdminContentReviewUiTest extends DatabaseTestCase
{
    use DatabaseTransactions;

    public function test_admin_review_queue_displays_pending_submissions(): void
    {
        $admin = $this->createUserWithRole('admin');
        $reviewRequest = $this->createPendingReviewRequest();

        $this->actingAs($admin)
            ->get(route('admin.content-reviews.index'))
            ->assertOk()
            ->assertSee('Pending Content Reviews', false)
            ->assertSee('Module Thumbnail', false)
            ->assertSee('Module Name', false)
            ->assertSee('Publisher', false)
            ->assertSee('Under Review', false)
            ->assertSee($reviewRequest->module->title, false)
            ->assertSee('Review', false)
            ->assertSee('Archive', false);
    }

    public function test_admin_show_page_renders_author_attribution_and_feedback_area(): void
    {
        $admin = $this->createUserWithRole('admin');
        $reviewRequest = $this->createPendingReviewRequest();

        $this->actingAs($admin)
            ->get(route('admin.content-reviews.show', $reviewRequest))
            ->assertOk()
            ->assertSee('Instructor', false)
            ->assertSee('Approve', false)
            ->assertSee('Reject', false)
            ->assertSee('Confirm Module Approval', false)
            ->assertSee('Reject Module Submission', false)
            ->assertSee('Rejection Reason', false)
                ->assertSee('Moderation Notes (optional)', false)
            ->assertSee('Issue Warning to Instructor', false);
    }

    public function test_admin_review_queue_renders_when_module_is_soft_deleted(): void
    {
        $admin = $this->createUserWithRole('admin');
        $reviewRequest = $this->createPendingReviewRequest();

        $reviewRequest->module->delete();

        $this->actingAs($admin)
            ->get(route('admin.content-reviews.index'))
            ->assertOk()
            ->assertSee('Pending Content Reviews', false)
            ->assertSee($reviewRequest->module_title, false);
    }

    private function createPendingReviewRequest(): ModuleReviewRequest
    {
        $instructor = $this->createUserWithRole('instructor');
        $module = Module::factory()->create([
            'created_by' => $instructor->id,
            'content_owner_type' => 'instructor',
            'is_published' => false,
            'current_review_status' => 'in_review',
            'title' => 'Queue Module',
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
            'status' => 'in_review',
            'submitted_at' => now(),
        ]);

        return ModuleReviewRequest::query()->create([
            'module_id' => $module->id,
            'module_revision_id' => $revision->id,
            'status' => 'in_review',
            'submitted_by' => $instructor->id,
            'submitted_at' => now(),
        ]);
    }

    private function createUserWithRole(string $role): User
    {
        $user = User::factory()->create([
            'role' => $role,
            'status' => 'active',
        ]);
        $user->assignRole($role);

        return $user;
    }
}
