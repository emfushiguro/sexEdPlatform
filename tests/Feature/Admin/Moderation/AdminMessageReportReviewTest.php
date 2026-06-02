<?php

namespace Tests\Feature\Admin\Moderation;

use App\Enums\MessageReportAction;
use App\Enums\MessageReportReason;
use App\Enums\EnforcementActionType;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\MessageReport;
use App\Models\ModerationCase;
use App\Models\User;
use App\Services\Moderation\SourceAdapters\ChatReportModerationAdapter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\DatabaseTestCase;

class AdminMessageReportReviewTest extends DatabaseTestCase
{
    use RefreshDatabase;

    public function test_admin_can_review_reported_message_context_and_log_action(): void
    {
        $admin = $this->createUserWithRole('admin');
        $reporter = $this->createUserWithRole('learner');
        $reportedUser = $this->createUserWithRole('instructor');

        $conversation = Conversation::query()->create([
            'participant_one_id' => $reporter->id,
            'participant_two_id' => $reportedUser->id,
            'pair_key' => Conversation::makePairKey($reporter->id, $reportedUser->id),
            'conversation_type' => Conversation::TYPE_DIRECT,
            'status' => Conversation::STATUS_ACTIVE,
            'context_key' => Conversation::makeContextKey(Conversation::TYPE_DIRECT, null),
        ]);

        $message = Message::query()->create([
            'conversation_id' => $conversation->id,
            'sender_id' => $reportedUser->id,
            'message_body' => 'Reported message body with full context.',
        ]);

        $report = MessageReport::query()->create([
            'message_id' => $message->id,
            'conversation_id' => $conversation->id,
            'reporter_id' => $reporter->id,
            'status' => 'open',
            'reason_code' => MessageReportReason::ThreateningBehavior,
            'custom_reason' => 'Specific threat context.',
        ]);

        app(ChatReportModerationAdapter::class)->syncReport($report);
        $case = ModerationCase::query()
            ->where('content_type', 'message_report')
            ->where('content_id', $report->id)
            ->firstOrFail();

        $this->actingAs($admin)
            ->get(route('admin.moderation-suspensions.reports.show', $case))
            ->assertOk()
            ->assertSee('Reported message body with full context.')
            ->assertSee($reporter->email)
            ->assertSee($reportedUser->email)
            ->assertSee('Conversation #'.$conversation->id);

        $this->actingAs($admin)
            ->put(route('admin.moderation-suspensions.reports.update', $case), [
                'status' => 'resolved',
                'action_taken' => MessageReportAction::ChatRestriction->value,
                'moderation_notes' => 'Restricted chat while the case is reviewed.',
            ])
            ->assertRedirect(route('admin.moderation-suspensions.reports.show', $case));

        $this->assertDatabaseHas('message_reports', [
            'id' => $report->id,
            'status' => 'resolved',
            'action_taken' => MessageReportAction::ChatRestriction->value,
            'reviewed_by_admin_id' => $admin->id,
            'moderation_notes' => 'Restricted chat while the case is reviewed.',
        ]);

        $this->assertDatabaseHas('enforcement_actions', [
            'user_id' => $reportedUser->id,
            'action_type' => EnforcementActionType::ChatRestriction->value,
            'issued_by_admin_id' => $admin->id,
            'trigger_type' => 'manual',
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
