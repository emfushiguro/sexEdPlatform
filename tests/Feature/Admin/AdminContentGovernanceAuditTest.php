<?php

namespace Tests\Feature\Admin;

use App\Enums\ModuleReviewRejectionReason;
use App\Models\Module;
use App\Models\ModuleReviewRequest;
use App\Models\ModuleRevision;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\DatabaseTestCase;

class AdminContentGovernanceAuditTest extends DatabaseTestCase
{
    use DatabaseTransactions;

    public function test_admin_approval_creates_activity_log_record(): void
    {
        $admin = $this->createUserWithRole('admin');
        $reviewRequest = $this->createPendingReviewRequest();

        $this->actingAs($admin)
            ->post(route('admin.content-reviews.approve', $reviewRequest))
            ->assertRedirect();

        $this->assertDatabaseHas('admin_activity_logs', [
            'admin_user_id' => $admin->id,
            'action' => 'content_reviews.approve',
        ]);
    }

    public function test_admin_rejection_creates_activity_log_record_with_feedback_context(): void
    {
        $admin = $this->createUserWithRole('admin');
        $reviewRequest = $this->createPendingReviewRequest();

        $this->actingAs($admin)
            ->post(route('admin.content-reviews.reject', $reviewRequest), [
                'reason_code' => ModuleReviewRejectionReason::MissingContent->value,
                'feedback' => 'Please revise the content package.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('admin_activity_logs', [
            'admin_user_id' => $admin->id,
            'action' => 'content_reviews.reject',
        ]);
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
