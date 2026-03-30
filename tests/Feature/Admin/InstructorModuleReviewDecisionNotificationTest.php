<?php

namespace Tests\Feature\Admin;

use App\Enums\ModuleReviewRejectionReason;
use App\Models\Module;
use App\Models\ModuleReviewRequest;
use App\Models\ModuleRevision;
use App\Models\User;
use App\Notifications\InstructorModuleReviewDecisionNotification;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Notification;
use Tests\DatabaseTestCase;

class InstructorModuleReviewDecisionNotificationTest extends DatabaseTestCase
{
    use DatabaseTransactions;

    public function test_approval_sends_in_app_notification_to_instructor(): void
    {
        Notification::fake();

        $admin = $this->createUserWithRole('admin');
        [$instructor, $reviewRequest] = $this->createPendingReviewRequest();

        $this->actingAs($admin)
            ->post(route('admin.content-reviews.approve', $reviewRequest))
            ->assertRedirect(route('admin.content-reviews.show', $reviewRequest));

        Notification::assertSentTo(
            [$instructor],
            InstructorModuleReviewDecisionNotification::class,
            fn (InstructorModuleReviewDecisionNotification $notification) => $notification->status === 'approved'
        );
    }

    public function test_rejection_sends_in_app_notification_with_reason_and_guidance(): void
    {
        Notification::fake();

        $admin = $this->createUserWithRole('admin');
        [$instructor, $reviewRequest] = $this->createPendingReviewRequest();

        $this->actingAs($admin)
            ->post(route('admin.content-reviews.reject', $reviewRequest), [
                'reason_code' => ModuleReviewRejectionReason::QuizErrors->value,
                'guidance_note' => 'Please correct quiz answer keys and scoring.',
            ])
            ->assertRedirect(route('admin.content-reviews.show', $reviewRequest));

        Notification::assertSentTo(
            [$instructor],
            InstructorModuleReviewDecisionNotification::class,
            fn (InstructorModuleReviewDecisionNotification $notification) => $notification->status === 'needs_revision'
                && $notification->reasonCode === ModuleReviewRejectionReason::QuizErrors->value
                && $notification->guidanceNote === 'Please correct quiz answer keys and scoring.'
        );
    }

    private function createPendingReviewRequest(): array
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
            'status' => 'in_review',
            'submitted_by' => $instructor->id,
            'submitted_at' => now(),
        ]);

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
