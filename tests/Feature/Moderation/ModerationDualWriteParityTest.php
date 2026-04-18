<?php

namespace Tests\Feature\Moderation;

use App\Enums\ModerationCaseSource;
use App\Models\Conversation;
use App\Models\Lesson;
use App\Models\LessonTopic;
use App\Models\Message;
use App\Models\ModerationCase;
use App\Models\Module;
use App\Models\User;
use App\Services\ContentGovernanceService;
use App\Services\ContentReportService;
use App\Services\InstructorApplicationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\DatabaseTestCase;

class ModerationDualWriteParityTest extends DatabaseTestCase
{
    use RefreshDatabase;

    public function test_module_review_submission_keeps_legacy_write_and_creates_central_case(): void
    {
        $instructor = $this->createUserWithRole('instructor');
        $module = $this->createInstructorModule($instructor);

        $reviewRequest = app(ContentGovernanceService::class)->submitForReview($module, $instructor);

        $this->assertDatabaseHas('module_review_requests', [
            'id' => $reviewRequest->id,
            'module_id' => $module->id,
            'submitted_by' => $instructor->id,
        ]);

        $case = ModerationCase::query()
            ->where('case_source', ModerationCaseSource::ModuleReview->value)
            ->where('content_type', 'module_review_request')
            ->where('content_id', $reviewRequest->id)
            ->first();

        $this->assertNotNull($case);
        $this->assertSame($instructor->id, $case->reporter_id);
        $this->assertSame($instructor->id, $case->reported_user_id);
        $this->assertSame($reviewRequest->id, data_get($case->metadata, 'source_trace.source_record_id'));
        $this->assertSame($module->id, data_get($case->metadata, 'source_trace.module_id'));
    }

    public function test_learner_report_flow_merges_legacy_report_and_updates_same_central_case(): void
    {
        $instructor = $this->createUserWithRole('instructor');
        $learner = $this->createUserWithRole('learner');

        $module = Module::factory()->create([
            'created_by' => $instructor->id,
            'is_published' => true,
            'current_review_status' => null,
        ]);

        $service = app(ContentReportService::class);

        $initialReport = $service->submitOrUpdateActive(
            reporter: $learner,
            targetType: 'module',
            targetId: $module->id,
            reasonCode: 'misleading_information',
            detailsHtml: '<p>Initial details.</p>',
        );

        $updatedReport = $service->submitOrUpdateActive(
            reporter: $learner,
            targetType: 'module',
            targetId: $module->id,
            reasonCode: 'harmful_material',
            detailsHtml: '<p>Updated details.</p>',
        );

        $this->assertSame($initialReport->id, $updatedReport->id);

        $this->assertDatabaseCount('content_reports', 1);
        $this->assertDatabaseHas('content_reports', [
            'id' => $initialReport->id,
            'reason_code' => 'harmful_material',
        ]);

        $cases = ModerationCase::query()
            ->where('case_source', ModerationCaseSource::LearnerReport->value)
            ->where('content_type', 'content_report')
            ->where('content_id', $initialReport->id)
            ->get();

        $this->assertCount(1, $cases);

        $case = $cases->first();
        $this->assertNotNull($case);
        $this->assertSame('harmful_material', data_get($case->metadata, 'source_trace.reason_code'));
        $this->assertSame('module', data_get($case->metadata, 'source_trace.target_type'));
    }

    public function test_chat_message_report_keeps_legacy_row_and_creates_central_case_with_trace_context(): void
    {
        $admin = $this->createUserWithRole('admin');
        $instructor = $this->createUserWithRole('instructor');

        $conversation = Conversation::query()->create([
            'participant_one_id' => $admin->id,
            'participant_two_id' => $instructor->id,
            'pair_key' => Conversation::makePairKey($admin->id, $instructor->id),
            'conversation_type' => Conversation::TYPE_DIRECT,
            'status' => Conversation::STATUS_ACTIVE,
            'context_key' => Conversation::makeContextKey(Conversation::TYPE_DIRECT, null),
        ]);

        $message = Message::query()->create([
            'conversation_id' => $conversation->id,
            'sender_id' => $instructor->id,
            'message_body' => 'Reportable chat message.',
        ]);

        $this->actingAs($admin)
            ->postJson(route('chat.messages.report', ['message' => $message->id]), [
                'reason' => 'Policy concern in message content.',
            ])
            ->assertOk();

        $this->assertDatabaseHas('message_reports', [
            'message_id' => $message->id,
            'reporter_id' => $admin->id,
            'status' => 'open',
        ]);

        $case = ModerationCase::query()
            ->where('case_source', ModerationCaseSource::ChatReport->value)
            ->where('content_type', 'message_report')
            ->first();

        $this->assertNotNull($case);
        $this->assertSame($admin->id, $case->reporter_id);
        $this->assertSame($instructor->id, $case->reported_user_id);
        $this->assertSame($conversation->id, data_get($case->metadata, 'source_trace.conversation_id'));
        $this->assertSame($message->id, data_get($case->metadata, 'source_trace.message_id'));
    }

    public function test_instructor_application_submission_keeps_legacy_row_and_creates_central_case(): void
    {
        Storage::fake('public');

        $learner = $this->createUserWithRole('learner');

        $application = app(InstructorApplicationService::class)->submitApplication($learner, [
            'government_id' => UploadedFile::fake()->create('id.pdf', 200, 'application/pdf'),
            'clearance' => UploadedFile::fake()->create('clearance.pdf', 200, 'application/pdf'),
            'cv_resume' => UploadedFile::fake()->create('cv_resume.pdf', 200, 'application/pdf'),
            'bio' => str_repeat('B', 120),
        ]);

        $this->assertDatabaseHas('instructor_applications', [
            'id' => $application->id,
            'user_id' => $learner->id,
            'status' => 'pending',
        ]);

        $case = ModerationCase::query()
            ->where('case_source', ModerationCaseSource::InstructorApplication->value)
            ->where('content_type', 'instructor_application')
            ->where('content_id', $application->id)
            ->first();

        $this->assertNotNull($case);
        $this->assertSame($learner->id, $case->reporter_id);
        $this->assertSame($learner->id, $case->reported_user_id);
        $this->assertSame($application->id, data_get($case->metadata, 'source_trace.source_record_id'));
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

        return $module;
    }
}
