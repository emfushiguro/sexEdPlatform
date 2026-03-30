<?php

namespace Tests\Unit;

use App\Enums\InstructorRestrictionAction;
use App\Enums\ModuleReviewRejectionReason;
use App\Models\Module;
use App\Models\ModuleReviewRequest;
use App\Models\ModuleRevision;
use App\Models\User;
use App\Services\ContentGovernanceService;
use App\Services\InstructorModerationPenaltyService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\DatabaseTestCase;

class InstructorModerationPenaltyServiceTest extends DatabaseTestCase
{
    use DatabaseTransactions;

    public function test_first_violation_suggests_warning_only(): void
    {
        $instructor = $this->createUserWithRole('instructor');
        $reviewRequest = $this->createPendingReviewRequestFor($instructor);

        $violation = app(InstructorModerationPenaltyService::class)->recordViolation(
            $instructor,
            $reviewRequest->module,
            $reviewRequest,
            ModuleReviewRejectionReason::LowQualityLessons->value,
            'Please improve clarity in lesson explanations.'
        );

        $this->assertSame(1, $violation->violation_sequence);
        $this->assertSame(InstructorRestrictionAction::WarningOnly->value, $violation->suggested_penalty_action);
    }

    public function test_second_violation_suggests_three_day_restriction(): void
    {
        $instructor = $this->createUserWithRole('instructor');
        $first = $this->createPendingReviewRequestFor($instructor);
        $second = $this->createPendingReviewRequestFor($instructor);

        $service = app(InstructorModerationPenaltyService::class);
        $service->recordViolation($instructor, $first->module, $first, ModuleReviewRejectionReason::MissingContent->value, 'Missing sections.');
        $violation = $service->recordViolation($instructor, $second->module, $second, ModuleReviewRejectionReason::QuizErrors->value, 'Fix incorrect answers.');

        $this->assertSame(2, $violation->violation_sequence);
        $this->assertSame(InstructorRestrictionAction::Restrict3Days->value, $violation->suggested_penalty_action);
    }

    public function test_third_violation_suggests_fourteen_day_restriction(): void
    {
        $instructor = $this->createUserWithRole('instructor');
        $service = app(InstructorModerationPenaltyService::class);

        for ($i = 0; $i < 2; $i++) {
            $request = $this->createPendingReviewRequestFor($instructor);
            $service->recordViolation($instructor, $request->module, $request, ModuleReviewRejectionReason::PoorModuleStructure->value, 'Fix flow.');
        }

        $third = $this->createPendingReviewRequestFor($instructor);
        $violation = $service->recordViolation($instructor, $third->module, $third, ModuleReviewRejectionReason::InaccurateEducationalInformation->value, 'Review educational accuracy.');

        $this->assertSame(3, $violation->violation_sequence);
        $this->assertSame(InstructorRestrictionAction::Restrict14Days->value, $violation->suggested_penalty_action);
    }

    public function test_fourth_violation_suggests_suspension_review(): void
    {
        $instructor = $this->createUserWithRole('instructor');
        $service = app(InstructorModerationPenaltyService::class);

        for ($i = 0; $i < 3; $i++) {
            $request = $this->createPendingReviewRequestFor($instructor);
            $service->recordViolation($instructor, $request->module, $request, ModuleReviewRejectionReason::Other->value, 'Violation seed.');
        }

        $fourth = $this->createPendingReviewRequestFor($instructor);
        $violation = $service->recordViolation($instructor, $fourth->module, $fourth, ModuleReviewRejectionReason::InappropriateContent->value, 'Unsafe content detected.');

        $this->assertSame(4, $violation->violation_sequence);
        $this->assertSame(InstructorRestrictionAction::SuspensionReview->value, $violation->suggested_penalty_action);
    }

    public function test_reject_review_creates_violation_record(): void
    {
        $instructor = $this->createUserWithRole('instructor');
        $admin = $this->createUserWithRole('admin');
        $reviewRequest = $this->createPendingReviewRequestFor($instructor);

        app(ContentGovernanceService::class)->rejectReview(
            $reviewRequest,
            $admin,
            'Please correct the incorrect scoring logic.',
            ModuleReviewRejectionReason::QuizErrors->value,
            'Quiz contains incorrect correct-answer markers.'
        );

        $this->assertDatabaseHas('instructor_violation_histories', [
            'user_id' => $instructor->id,
            'module_id' => $reviewRequest->module_id,
            'module_review_request_id' => $reviewRequest->id,
            'reason_code' => ModuleReviewRejectionReason::QuizErrors->value,
        ]);
    }

    private function createPendingReviewRequestFor(User $instructor): ModuleReviewRequest
    {
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
