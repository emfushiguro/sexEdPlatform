<?php

namespace Tests\Feature\Admin;

use App\Enums\InstructorRestrictionAction;
use App\Enums\ModuleReviewRejectionReason;
use App\Models\Module;
use App\Models\ModuleReviewRequest;
use App\Models\ModuleRevision;
use App\Models\User;
use App\Services\InstructorModerationPenaltyService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\DatabaseTestCase;

class AdminModulePenaltyConfirmationTest extends DatabaseTestCase
{
    use DatabaseTransactions;

    public function test_admin_can_confirm_penalty_action(): void
    {
        $admin = $this->createUserWithRole('admin');
        [$instructor, $reviewRequest] = $this->createReviewRequestWithViolation();

        $this->actingAs($admin)
            ->post(route('admin.content-reviews.penalty.confirm', $reviewRequest), [
                'action' => InstructorRestrictionAction::Restrict3Days->value,
            ])
            ->assertRedirect(route('admin.content-reviews.show', $reviewRequest));

        $this->assertDatabaseHas('instructor_violation_histories', [
            'module_review_request_id' => $reviewRequest->id,
            'confirmed_penalty_action' => InstructorRestrictionAction::Restrict3Days->value,
            'confirmed_by_admin_id' => $admin->id,
        ]);

        $this->assertDatabaseHas('instructor_moderation_profiles', [
            'user_id' => $instructor->id,
            'current_restriction_status' => 'restricted',
        ]);
    }

    public function test_non_admin_cannot_confirm_penalty_action(): void
    {
        $instructor = $this->createUserWithRole('instructor');
        [, $reviewRequest] = $this->createReviewRequestWithViolation();

        $this->actingAs($instructor)
            ->post(route('admin.content-reviews.penalty.confirm', $reviewRequest), [
                'action' => InstructorRestrictionAction::Restrict3Days->value,
            ])
            ->assertStatus(403);
    }

    private function createReviewRequestWithViolation(): array
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

        $reviewRequest = ModuleReviewRequest::query()->create([
            'module_id' => $module->id,
            'module_revision_id' => $revision->id,
            'status' => 'rejected',
            'submitted_by' => $instructor->id,
            'submitted_at' => now(),
        ]);

        app(InstructorModerationPenaltyService::class)->recordViolation(
            $instructor,
            $module,
            $reviewRequest,
            ModuleReviewRejectionReason::LowQualityLessons->value,
            'Improve lesson quality and sequencing.'
        );

        return [$instructor, $reviewRequest];
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
