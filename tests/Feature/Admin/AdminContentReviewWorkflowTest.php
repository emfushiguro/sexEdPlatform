<?php

namespace Tests\Feature\Admin;

use App\Models\Module;
use App\Models\ModuleReviewRequest;
use App\Models\ModuleRevision;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\DatabaseTestCase;

class AdminContentReviewWorkflowTest extends DatabaseTestCase
{
    use DatabaseTransactions;

    public function test_admin_can_see_pending_module_review_queue(): void
    {
        $admin = $this->createUserWithRole('admin');
        $reviewRequest = $this->createPendingReviewRequest();

        $this->actingAs($admin)
            ->get(route('admin.content-reviews.index'))
            ->assertOk()
            ->assertSee($reviewRequest->module->title, false);
    }

    public function test_admin_can_approve_instructor_module_submission(): void
    {
        $admin = $this->createUserWithRole('admin');
        $reviewRequest = $this->createPendingReviewRequest();

        $this->actingAs($admin)
            ->post(route('admin.content-reviews.approve', $reviewRequest))
            ->assertRedirect(route('admin.content-reviews.show', $reviewRequest));

        $this->assertDatabaseHas('module_review_requests', [
            'id' => $reviewRequest->id,
            'status' => 'approved',
            'reviewed_by' => $admin->id,
        ]);
    }

    public function test_admin_can_reject_with_required_feedback(): void
    {
        $admin = $this->createUserWithRole('admin');
        $reviewRequest = $this->createPendingReviewRequest();

        $this->actingAs($admin)
            ->post(route('admin.content-reviews.reject', $reviewRequest), [
                'feedback' => 'Please tighten the lesson package.',
            ])
            ->assertRedirect(route('admin.content-reviews.show', $reviewRequest));

        $this->assertDatabaseHas('module_review_requests', [
            'id' => $reviewRequest->id,
            'status' => 'needs_revision',
            'feedback' => 'Please tighten the lesson package.',
        ]);
    }

    public function test_non_admin_users_cannot_access_review_queue(): void
    {
        $instructor = $this->createUserWithRole('instructor');

        $this->actingAs($instructor)
            ->get(route('admin.content-reviews.index'))
            ->assertStatus(403);
    }

    private function createPendingReviewRequest(): ModuleReviewRequest
    {
        $instructor = $this->createUserWithRole('instructor');
        $module = Module::factory()->create([
            'created_by' => $instructor->id,
            'content_owner_type' => 'instructor',
            'is_published' => false,
            'current_review_status' => 'in_review',
        ]);

        $revision = ModuleRevision::query()->create([
            'module_id' => $module->id,
            'revision_number' => 1,
            'snapshot_payload' => [
                'module' => [
                    'id' => $module->id,
                    'title' => $module->title,
                ],
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
