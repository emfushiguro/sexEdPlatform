<?php

namespace Tests\Feature\Admin;

use App\Enums\ModuleReviewRejectionReason;
use App\Models\Module;
use App\Models\ModuleReviewRequest;
use App\Models\ModuleRevision;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\DatabaseTestCase;

class ModuleReviewRejectValidationTest extends DatabaseTestCase
{
    use DatabaseTransactions;

    public function test_reject_requires_reason_code(): void
    {
        $admin = $this->createUserWithRole('admin');
        $reviewRequest = $this->createPendingReviewRequest();

        $this->actingAs($admin)
            ->from(route('admin.content-reviews.show', $reviewRequest))
            ->post(route('admin.content-reviews.reject', $reviewRequest), [
                'reason_code' => '',
                'guidance_note' => 'Please correct the content flow.',
            ])
            ->assertRedirect(route('admin.content-reviews.show', $reviewRequest))
            ->assertSessionHasErrors(['reason_code']);
    }

    public function test_reject_requires_custom_reason_when_reason_is_other(): void
    {
        $admin = $this->createUserWithRole('admin');
        $reviewRequest = $this->createPendingReviewRequest();

        $this->actingAs($admin)
            ->from(route('admin.content-reviews.show', $reviewRequest))
            ->post(route('admin.content-reviews.reject', $reviewRequest), [
                'reason_code' => ModuleReviewRejectionReason::Other->value,
                'guidance_note' => '',
            ])
            ->assertRedirect(route('admin.content-reviews.show', $reviewRequest))
            ->assertSessionHasErrors(['guidance_note']);
    }

    public function test_reject_accepts_reason_without_custom_reason_for_non_other_reason(): void
    {
        $admin = $this->createUserWithRole('admin');
        $reviewRequest = $this->createPendingReviewRequest();

        $this->actingAs($admin)
            ->post(route('admin.content-reviews.reject', $reviewRequest), [
                'reason_code' => ModuleReviewRejectionReason::QuizErrors->value,
                'guidance_note' => '',
            ])
            ->assertRedirect(route('admin.content-reviews.show', $reviewRequest));
    }

    public function test_reject_accepts_structured_reason_payload(): void
    {
        $admin = $this->createUserWithRole('admin');
        $reviewRequest = $this->createPendingReviewRequest();

        $this->actingAs($admin)
            ->post(route('admin.content-reviews.reject', $reviewRequest), [
                'reason_code' => ModuleReviewRejectionReason::PoorModuleStructure->value,
                'guidance_note' => 'Reorder lessons and add missing transitions between topics.',
            ])
            ->assertRedirect(route('admin.content-reviews.show', $reviewRequest));
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
