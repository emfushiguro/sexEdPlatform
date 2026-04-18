<?php

namespace Tests\Feature\Console;

use App\Enums\ContentReportStatus;
use App\Enums\ContentReportTargetType;
use App\Enums\ModerationCaseSource;
use App\Models\ContentReport;
use App\Models\Conversation;
use App\Models\InstructorApplication;
use App\Models\Message;
use App\Models\MessageReport;
use App\Models\Module;
use App\Models\ModuleReviewRequest;
use App\Models\ModuleRevision;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\DatabaseTestCase;

class BackfillCentralizedModerationTest extends DatabaseTestCase
{
    use RefreshDatabase;

    public function test_command_backfills_legacy_artifacts_into_centralized_tables(): void
    {
        $artifacts = $this->seedLegacyModerationArtifacts();

        $this->artisan('moderation:backfill-centralized')
            ->assertExitCode(0);

        $this->assertDatabaseHas('moderation_cases', [
            'case_source' => ModerationCaseSource::ModuleReview->value,
            'content_type' => 'module_review_request',
            'content_id' => $artifacts['module_review_request']->id,
        ]);

        $this->assertDatabaseHas('moderation_cases', [
            'case_source' => ModerationCaseSource::ChatReport->value,
            'content_type' => 'message_report',
            'content_id' => $artifacts['message_report']->id,
        ]);

        $this->assertDatabaseHas('moderation_cases', [
            'case_source' => ModerationCaseSource::LearnerReport->value,
            'content_type' => 'content_report',
            'content_id' => $artifacts['content_report']->id,
        ]);

        $this->assertDatabaseHas('moderation_cases', [
            'case_source' => ModerationCaseSource::InstructorApplication->value,
            'content_type' => 'instructor_application',
            'content_id' => $artifacts['instructor_application']->id,
        ]);
    }

    public function test_backfill_command_is_idempotent_on_rerun(): void
    {
        $this->seedLegacyModerationArtifacts();

        $this->artisan('moderation:backfill-centralized')->assertExitCode(0);
        $this->artisan('moderation:backfill-centralized')->assertExitCode(0);

        $this->assertSame(4, (int) \App\Models\ModerationCase::query()->count());
    }

    /**
     * @return array<string, mixed>
     */
    private function seedLegacyModerationArtifacts(): array
    {
        $admin = $this->createUserWithRole('admin');
        $instructor = $this->createUserWithRole('instructor');
        $learner = $this->createUserWithRole('learner');

        $module = Module::factory()->create([
            'created_by' => $instructor->id,
            'content_owner_type' => 'instructor',
            'enrollment_mode' => 'auto',
            'is_published' => false,
        ]);

        $revision = ModuleRevision::query()->create([
            'module_id' => $module->id,
            'revision_number' => 1,
            'snapshot_payload' => ['module' => ['title' => $module->title]],
            'submitted_by' => $instructor->id,
            'status' => 'submitted',
            'submitted_at' => now()->subHour(),
        ]);

        $moduleReviewRequest = ModuleReviewRequest::query()->create([
            'module_id' => $module->id,
            'module_revision_id' => $revision->id,
            'status' => 'submitted',
            'submitted_by' => $instructor->id,
            'submitted_at' => now()->subHour(),
        ]);

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
            'message_body' => 'Legacy chat message report fixture.',
        ]);

        $messageReport = MessageReport::query()->create([
            'message_id' => $message->id,
            'conversation_id' => $conversation->id,
            'reporter_id' => $admin->id,
            'status' => 'open',
            'reason' => 'Policy concern for historical backfill.',
        ]);

        $contentReport = ContentReport::query()->create([
            'reporter_id' => $learner->id,
            'target_type' => ContentReportTargetType::Instructor,
            'target_id' => $instructor->id,
            'reason_code' => 'harmful_material',
            'status' => ContentReportStatus::Submitted,
        ]);

        $instructorApplication = InstructorApplication::query()->create([
            'user_id' => $learner->id,
            'status' => 'pending',
            'educational_background' => 'college_graduate',
            'government_id_path' => 'instructor-applications/id.pdf',
            'clearance_path' => 'instructor-applications/clearance.pdf',
            'bio' => 'Legacy application fixture for moderation backfill.',
        ]);

        return [
            'module_review_request' => $moduleReviewRequest,
            'message_report' => $messageReport,
            'content_report' => $contentReport,
            'instructor_application' => $instructorApplication,
        ];
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
