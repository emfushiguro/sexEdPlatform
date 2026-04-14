<?php

namespace Tests\Unit;

use App\Models\Lesson;
use App\Models\LessonTopic;
use App\Models\Module;
use App\Models\ModuleReviewRequest;
use App\Models\Quiz;
use App\Models\User;
use App\Services\ContentGovernanceService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\DatabaseTestCase;

class ContentGovernanceServiceTest extends DatabaseTestCase
{
    use DatabaseTransactions;

    public function test_submit_for_review_creates_revision_snapshot_and_review_request(): void
    {
        $instructor = $this->createUserWithRole('instructor');
        $module = $this->createInstructorModule($instructor);

        $reviewRequest = app(ContentGovernanceService::class)->submitForReview($module, $instructor);

        $this->assertDatabaseHas('module_revisions', [
            'module_id' => $module->id,
            'status' => ContentGovernanceService::STATUS_SUBMITTED,
            'submitted_by' => $instructor->id,
        ]);

        $this->assertDatabaseHas('module_review_requests', [
            'id' => $reviewRequest->id,
            'module_id' => $module->id,
            'status' => ContentGovernanceService::STATUS_SUBMITTED,
            'submitted_by' => $instructor->id,
        ]);
    }

    public function test_reject_review_requires_feedback_and_marks_review_request_rejected(): void
    {
        $instructor = $this->createUserWithRole('instructor');
        $admin = $this->createUserWithRole('admin');
        $module = $this->createInstructorModule($instructor);

        $reviewRequest = app(ContentGovernanceService::class)->submitForReview($module, $instructor);
        $reviewRequest = app(ContentGovernanceService::class)->startReview($reviewRequest, $admin);

        $rejectedRevision = app(ContentGovernanceService::class)->rejectReview($reviewRequest, $admin, 'Please improve the lesson flow.');

        $this->assertSame('needs_revision', $rejectedRevision->status);

        $this->assertDatabaseHas('module_revisions', [
            'id' => $rejectedRevision->id,
            'status' => 'needs_revision',
            'reviewed_by' => $admin->id,
            'review_feedback' => 'Please improve the lesson flow.',
        ]);

        $this->assertDatabaseHas('module_review_requests', [
            'id' => $reviewRequest->id,
            'status' => 'rejected',
            'reviewed_by' => $admin->id,
            'feedback' => 'Please improve the lesson flow.',
        ]);

        $this->assertDatabaseMissing('instructor_violation_histories', [
            'module_review_request_id' => $reviewRequest->id,
        ]);
    }

    public function test_reject_review_with_warning_creates_violation_record(): void
    {
        $instructor = $this->createUserWithRole('instructor');
        $admin = $this->createUserWithRole('admin');
        $module = $this->createInstructorModule($instructor);

        $reviewRequest = app(ContentGovernanceService::class)->submitForReview($module, $instructor);
        $reviewRequest = app(ContentGovernanceService::class)->startReview($reviewRequest, $admin);

        app(ContentGovernanceService::class)->rejectReview(
            $reviewRequest,
            $admin,
            'Unsafe educational guidance detected.',
            'inaccurate_educational_information',
            'Please correct the unsafe educational guidance.',
            true,
        );

        $this->assertDatabaseHas('instructor_violation_histories', [
            'user_id' => $instructor->id,
            'module_review_request_id' => $reviewRequest->id,
            'reason_code' => 'inaccurate_educational_information',
        ]);
    }

    public function test_approve_review_marks_revision_as_published_and_updates_module(): void
    {
        $instructor = $this->createUserWithRole('instructor');
        $admin = $this->createUserWithRole('admin');
        $module = $this->createInstructorModule($instructor);

        $reviewRequest = app(ContentGovernanceService::class)->submitForReview($module, $instructor);
        $reviewRequest = app(ContentGovernanceService::class)->startReview($reviewRequest, $admin);

        $approvedRevision = app(ContentGovernanceService::class)->approveReview($reviewRequest, $admin, 'Looks good.');

        $this->assertSame('approved', $approvedRevision->status);

        $this->assertDatabaseHas('modules', [
            'id' => $module->id,
            'published_revision_id' => $approvedRevision->id,
            'published_by_admin_id' => $admin->id,
        ]);

        $this->assertDatabaseHas('module_review_requests', [
            'id' => $reviewRequest->id,
            'status' => 'approved',
            'reviewed_by' => $admin->id,
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

    private function createInstructorModule(User $instructor): Module
    {
        $module = Module::factory()->create([
            'created_by' => $instructor->id,
            'content_owner_type' => 'instructor',
            'is_published' => false,
            'enrollment_mode' => 'auto',
        ]);

        $lesson = Lesson::factory()->create([
            'module_id' => $module->id,
            'is_published' => true,
        ]);

        LessonTopic::query()->create([
            'lesson_id' => $lesson->id,
            'title' => 'Topic 1',
            'type' => 'text',
            'text_content' => '<p>Topic body</p>',
            'order' => 1,
        ]);

        Quiz::factory()->create([
            'module_id' => $module->id,
            'lesson_id' => null,
            'is_active' => true,
        ]);

        return $module;
    }
}
