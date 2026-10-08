<?php

namespace Tests\Feature\Support;

use App\Enums\PlatformFeedbackStatus;
use App\Models\PlatformFeedback;
use App\Models\PlatformFeedbackMessage;
use App\Models\User;
use App\Notifications\PlatformFeedbackMessageNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\DatabaseTestCase;

class PlatformFeedbackConversationTest extends DatabaseTestCase
{
    use RefreshDatabase;

    public function test_first_admin_reply_marks_new_ticket_reviewed_and_notifies_owner_once(): void
    {
        Notification::fake();
        $owner = $this->learner();
        $admin = User::factory()->create(['role' => 'admin', 'status' => User::STATUS_ACTIVE]);
        $admin->assignRole('admin');
        $ticket = PlatformFeedback::factory()->for($owner)->create([
            'status' => PlatformFeedbackStatus::New,
            'may_contact' => true,
        ]);

        $this->actingAs($admin)
            ->post("/admin/feedback/{$ticket->id}/messages", ['body' => 'We are reviewing this now.'])
            ->assertRedirect(route('admin.feedback.show', $ticket));

        $this->assertSame(PlatformFeedbackStatus::Reviewed, $ticket->fresh()->status);
        $this->assertDatabaseHas('platform_feedback_messages', [
            'platform_feedback_id' => $ticket->id,
            'sender_id' => $admin->id,
            'sender_role' => 'admin',
            'body' => 'We are reviewing this now.',
        ]);
        $this->assertDatabaseHas('platform_feedback_histories', [
            'platform_feedback_id' => $ticket->id,
            'from_status' => 'new',
            'to_status' => 'reviewed',
        ]);
        Notification::assertSentToTimes($owner, PlatformFeedbackMessageNotification::class, 1);
        Notification::assertCount(1);
    }

    public function test_owner_cannot_reply_before_an_admin_message_exists(): void
    {
        Notification::fake();
        $owner = $this->learner();
        $ticket = PlatformFeedback::factory()->for($owner)->create(['status' => PlatformFeedbackStatus::Reviewed]);

        $this->actingAs($owner)
            ->post("/feedback/submissions/{$ticket->id}/messages", ['body' => 'Is there an update?'])
            ->assertStatus(422);

        $this->assertDatabaseCount('platform_feedback_messages', 0);
        Notification::assertCount(0);
    }

    public function test_admin_cannot_message_when_the_author_did_not_request_follow_up(): void
    {
        Notification::fake();
        $owner = $this->learner();
        $admin = User::factory()->create(['role' => 'admin', 'status' => User::STATUS_ACTIVE]);
        $admin->assignRole('admin');
        $ticket = PlatformFeedback::factory()->for($owner)->create([
            'status' => PlatformFeedbackStatus::Reviewed,
            'may_contact' => false,
        ]);

        $this->actingAs($admin)
            ->post("/admin/feedback/{$ticket->id}/messages", ['body' => 'We reviewed your ticket.'])
            ->assertStatus(422);

        $this->assertDatabaseCount('platform_feedback_messages', 0);
        Notification::assertCount(0);

        $this->actingAs($admin)
            ->get(route('admin.feedback.show', $ticket))
            ->assertOk()
            ->assertSee('Follow-up was not requested.')
            ->assertDontSee('name="body"', false);
    }

    public function test_owner_reply_reopens_resolved_ticket_and_notifies_active_reviewer_once(): void
    {
        Notification::fake();
        $owner = $this->learner();
        $admin = User::factory()->create(['role' => 'admin', 'status' => User::STATUS_ACTIVE]);
        $admin->assignRole('admin');
        $ticket = PlatformFeedback::factory()->for($owner)->create([
            'status' => PlatformFeedbackStatus::Resolved,
            'reviewed_by' => $admin->id,
            'reviewed_at' => now()->subHour(),
            'resolved_at' => now(),
        ]);
        $ticket->messages()->create([
            'sender_id' => $admin->id,
            'sender_role' => 'admin',
            'body' => 'This is resolved now.',
        ]);

        $this->actingAs($owner)
            ->post("/feedback/submissions/{$ticket->id}/messages", ['body' => 'I still need help with this.'])
            ->assertRedirect(route('feedback.show', $ticket));

        $ticket->refresh();
        $this->assertSame(PlatformFeedbackStatus::Reviewed, $ticket->status);
        $this->assertNull($ticket->resolved_at);
        $this->assertDatabaseHas('platform_feedback_messages', [
            'platform_feedback_id' => $ticket->id,
            'sender_id' => $owner->id,
            'sender_role' => 'learner',
            'body' => 'I still need help with this.',
        ]);
        $this->assertDatabaseHas('platform_feedback_histories', [
            'platform_feedback_id' => $ticket->id,
            'from_status' => 'resolved',
            'to_status' => 'reviewed',
        ]);
        Notification::assertSentToTimes($admin, PlatformFeedbackMessageNotification::class, 1);
        Notification::assertCount(1);
    }

    public function test_message_body_has_a_1000_character_server_limit(): void
    {
        $owner = $this->learner();
        $ticket = PlatformFeedback::factory()->for($owner)->create(['status' => PlatformFeedbackStatus::Reviewed]);

        $this->actingAs($owner)
            ->post("/feedback/submissions/{$ticket->id}/messages", ['body' => str_repeat('x', 1001)])
            ->assertSessionHasErrors('body');

        $this->assertDatabaseCount('platform_feedback_messages', 0);
    }

    public function test_closed_and_withdrawn_tickets_reject_messages(): void
    {
        $owner = $this->learner();
        $admin = User::factory()->create(['role' => 'admin', 'status' => User::STATUS_ACTIVE]);
        $admin->assignRole('admin');

        foreach ([PlatformFeedbackStatus::Closed, PlatformFeedbackStatus::Withdrawn] as $status) {
            $ticket = PlatformFeedback::factory()->for($owner)->create(['status' => $status]);
            $this->actingAs($admin)
                ->post("/admin/feedback/{$ticket->id}/messages", ['body' => 'This must be rejected.'])
                ->assertStatus(422);
        }

        $this->assertDatabaseCount('platform_feedback_messages', 0);
    }

    public function test_conversation_is_visible_only_to_owner_and_admin_and_escapes_message_text(): void
    {
        $owner = $this->learner();
        $other = $this->learner();
        $admin = User::factory()->create(['role' => 'admin', 'status' => User::STATUS_ACTIVE]);
        $admin->assignRole('admin');
        $ticket = PlatformFeedback::factory()->for($owner)->create(['status' => PlatformFeedbackStatus::Reviewed]);
        $ticket->messages()->create([
            'sender_id' => $admin->id,
            'sender_role' => 'admin',
            'body' => '<script>alert("private")</script>',
        ]);

        $this->actingAs($owner)->get(route('feedback.show', $ticket))
            ->assertOk()
            ->assertSee('data-ticket-conversation', false)
            ->assertSee('&lt;script&gt;alert(&quot;private&quot;)&lt;/script&gt;', false)
            ->assertDontSee('<script>alert("private")</script>', false);

        $this->actingAs($admin)->get(route('admin.feedback.show', $ticket))
            ->assertOk()
            ->assertSee('data-ticket-conversation', false);

        $this->actingAs($other)->get(route('feedback.show', $ticket))->assertForbidden();
    }

    public function test_legacy_staff_response_is_migrated_once_to_a_platform_team_message(): void
    {
        $owner = $this->learner();
        $ticket = PlatformFeedback::factory()->for($owner)->create([
            'status' => PlatformFeedbackStatus::Reviewed,
            'staff_response' => 'Legacy response body.',
            'reviewed_by' => null,
            'reviewed_at' => now()->subDay(),
        ]);
        $path = database_path('migrations/2026_09_14_000003_migrate_legacy_platform_feedback_responses.php');

        (require $path)->up();
        (require $path)->up();

        $this->assertDatabaseCount('platform_feedback_messages', 1);
        $message = PlatformFeedbackMessage::query()->firstOrFail();
        $this->assertSame($ticket->id, $message->platform_feedback_id);
        $this->assertNull($message->sender_id);
        $this->assertSame('admin', $message->sender_role);
        $this->assertSame('Legacy response body.', $message->body);

        $this->actingAs($owner)->get(route('feedback.show', $ticket))
            ->assertOk()
            ->assertSee('Platform team')
            ->assertSee('Legacy response body.')
            ->assertDontSee('Staff response');
    }

    public function test_deleting_an_admin_preserves_their_ticket_message(): void
    {
        $owner = $this->learner();
        $admin = User::factory()->create(['role' => 'admin']);
        $ticket = PlatformFeedback::factory()->for($owner)->create();
        $message = $ticket->messages()->create([
            'sender_id' => $admin->id,
            'sender_role' => 'admin',
            'body' => 'Retain this support record.',
        ]);

        $admin->forceDelete();

        $this->assertDatabaseHas('platform_feedback_messages', [
            'id' => $message->id,
            'sender_id' => null,
            'body' => 'Retain this support record.',
        ]);
    }

    public function test_instructor_can_submit_and_continue_a_private_ticket_after_admin_reply(): void
    {
        Notification::fake();
        $instructor = User::factory()->create([
            'role' => 'instructor',
            'account_type' => User::ACCOUNT_TYPE_INSTRUCTOR,
            'age' => 30,
            'birthdate' => now()->subYears(30),
        ]);
        $instructor->assignRole('instructor');
        $admin = User::factory()->create(['role' => 'admin', 'status' => User::STATUS_ACTIVE]);
        $admin->assignRole('admin');

        $this->actingAs($instructor)->post(route('feedback.store'), [
            'type' => 'general',
            'subject' => 'Instructor support question',
            'description' => 'I need help with a teaching workflow.',
            'rating' => 5,
            'may_contact' => true,
        ])->assertRedirect();
        $ticket = PlatformFeedback::query()->where('user_id', $instructor->id)->firstOrFail();

        $this->actingAs($admin)->post(route('admin.feedback.messages.store', $ticket), ['body' => 'We can help with that workflow.'])->assertRedirect();
        $this->actingAs($instructor)->post(route('feedback.messages.store', $ticket), ['body' => 'Thank you for the clarification.'])->assertRedirect();

        $this->assertSame(2, $ticket->messages()->count());
        $this->assertSame(PlatformFeedbackStatus::Reviewed, $ticket->fresh()->status);
        Notification::assertSentToTimes($instructor, PlatformFeedbackMessageNotification::class, 1);
        Notification::assertSentToTimes($admin, PlatformFeedbackMessageNotification::class, 1);
    }

    private function learner(): User
    {
        $user = User::factory()->create([
            'role' => 'learner',
            'account_type' => User::ACCOUNT_TYPE_LEARNER_ADULT,
            'birthdate' => now()->subYears(25),
        ]);
        $user->assignRole('learner');

        return $user;
    }
}
