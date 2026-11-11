<?php

namespace Tests\Feature\Admin\Moderation;

use App\Enums\EnforcementActionType;
use App\Enums\MessageReportAction;
use App\Enums\MessageReportReason;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\MessageReport;
use App\Models\ModerationCase;
use App\Models\User;
use App\Notifications\Moderation\SuspensionIssuedNotification;
use App\Services\Chat\ChatAuthorizationService;
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
        $reporter->learnerProfile()->create(['username' => 'reporter-'.$reporter->id, 'avatar_path' => 'avatars/reporter.jpg']);
        $reportedUser->instructorProfile()->create(['bio' => 'Instructor', 'profile_photo_path' => 'avatars/instructor.jpg']);

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
            ->assertSee('/storage/avatars/reporter.jpg')
            ->assertSee('/storage/avatars/instructor.jpg')
            ->assertSee('duration_amount')
            ->assertSee('duration_unit')
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

        $authorization = app(ChatAuthorizationService::class);
        $this->assertFalse($authorization->canSendMessage($reportedUser, $conversation));
        $this->assertTrue($authorization->canSendMessage($reporter, $conversation));

        $this->actingAs($reportedUser)
            ->postJson(route('chat.messages.store', $conversation), ['message_body' => 'Blocked message'])
            ->assertForbidden();
        $this->assertDatabaseMissing('messages', ['message_body' => 'Blocked message']);

        $otherMessage = Message::query()->create([
            'conversation_id' => $conversation->id,
            'sender_id' => $reportedUser->id,
            'message_body' => 'Another reported message',
        ]);
        $otherReport = MessageReport::query()->create([
            'message_id' => $otherMessage->id,
            'conversation_id' => $conversation->id,
            'reporter_id' => $reporter->id,
            'status' => 'resolved',
            'action_taken' => MessageReportAction::ChatRestriction->value,
        ]);

        $this->actingAs($admin)
            ->put(route('admin.moderation-suspensions.reports.update', $case), [
                'status' => 'dismissed',
                'action_taken' => MessageReportAction::DismissReport->value,
            ])->assertRedirect();

        $this->assertFalse($authorization->canSendMessage($reportedUser, $conversation));
        $otherReport->update(['action_taken' => MessageReportAction::DismissReport->value]);
        $this->assertTrue($authorization->canSendMessage($reportedUser, $conversation));
    }

    public function test_admin_can_set_a_bounded_suspension_duration_and_user_sees_end_time(): void
    {
        $this->travelTo(now()->startOfMinute());
        $startsAt = now()->copy();
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
            'message_body' => 'Reported message.',
        ]);
        $report = MessageReport::query()->create([
            'message_id' => $message->id,
            'conversation_id' => $conversation->id,
            'reporter_id' => $reporter->id,
            'status' => 'open',
            'reason_code' => MessageReportReason::ThreateningBehavior,
        ]);
        app(ChatReportModerationAdapter::class)->syncReport($report);
        $case = ModerationCase::query()
            ->where('content_type', 'message_report')
            ->where('content_id', $report->id)
            ->firstOrFail();

        $this->actingAs($admin)->from(route('admin.moderation-suspensions.reports.show', $case))
            ->put(route('admin.moderation-suspensions.reports.update', $case), [
                'status' => 'resolved',
                'action_taken' => MessageReportAction::TemporarySuspension->value,
                'duration_amount' => 366,
                'duration_unit' => 'days',
            ])
            ->assertSessionHasErrors('duration_amount');

        $this->actingAs($admin)
            ->put(route('admin.moderation-suspensions.reports.update', $case), [
                'status' => 'resolved',
                'action_taken' => MessageReportAction::TemporarySuspension->value,
                'duration_amount' => 8,
                'duration_unit' => 'hours',
                'moderation_notes' => 'Internal review note.',
            ])
            ->assertRedirect(route('admin.moderation-suspensions.reports.show', $case));

        $expectedEnd = $startsAt->copy()->addHours(8);
        $suspension = $reportedUser->userSuspensions()->latest('id')->firstOrFail();
        $action = $suspension->enforcementAction;
        $this->assertSame($expectedEnd->timestamp, $action->ends_at->timestamp);
        $this->assertSame($expectedEnd->timestamp, $suspension->ends_at->timestamp);

        $notification = $reportedUser->notifications()
            ->where('type', SuspensionIssuedNotification::class)
            ->firstOrFail();
        $this->assertSame($expectedEnd->toDateTimeString(), $notification->data['ends_at']);

        $this->actingAs($reportedUser)
            ->get(route('moderation.suspension-status'))
            ->assertOk()
            ->assertSee($expectedEnd->format('M d, Y h:i A'))
            ->assertSee('Access is expected to resume');
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
