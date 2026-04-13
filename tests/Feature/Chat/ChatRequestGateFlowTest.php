<?php

namespace Tests\Feature\Chat;

use App\Enums\EnrollmentStatus;
use App\Models\Conversation;
use App\Models\MessageRequest;
use App\Models\Module;
use App\Models\ModuleEnrollment;
use App\Models\User;
use Tests\TestCase;

class ChatRequestGateFlowTest extends TestCase
{
    public function test_non_enrolled_learner_to_instructor_is_request_gated_until_accepted(): void
    {
        $learner = $this->createUserWithRole('learner');
        $instructor = $this->createUserWithRole('instructor');

        $startResponse = $this->actingAs($learner)
            ->postJson(route('chat.conversations.start'), [
                'target_user_id' => $instructor->id,
                'conversation_type' => Conversation::TYPE_DIRECT,
                'initial_message' => 'Can we discuss course fit?',
            ])
            ->assertStatus(202)
            ->assertJsonPath('requires_request', true)
            ->assertJsonPath('message_request.status', MessageRequest::STATUS_PENDING);

        $messageRequestId = (int) $startResponse->json('message_request.id');
        $pendingConversationId = (int) $startResponse->json('conversation.id');

        $this->assertGreaterThan(0, $pendingConversationId);

        $this->assertDatabaseHas('conversations', [
            'id' => $pendingConversationId,
            'status' => Conversation::STATUS_PENDING_REQUEST,
            'conversation_type' => Conversation::TYPE_DIRECT,
        ]);

        $this->assertDatabaseHas('message_requests', [
            'id' => $messageRequestId,
            'requester_id' => $learner->id,
            'instructor_id' => $instructor->id,
            'status' => MessageRequest::STATUS_PENDING,
            'accepted_conversation_id' => $pendingConversationId,
        ]);

        $this->assertDatabaseHas('messages', [
            'conversation_id' => $pendingConversationId,
            'sender_id' => $learner->id,
            'message_body' => 'Can we discuss course fit?',
        ]);

        $this->actingAs($learner)
            ->postJson(route('chat.messages.store', ['conversation' => $pendingConversationId]), [
                'message_body' => 'Follow-up while pending',
            ])
            ->assertForbidden();

        $this->actingAs($learner)
            ->postJson(route('chat.requests.accept', ['messageRequest' => $messageRequestId]))
            ->assertForbidden();

        $acceptResponse = $this->actingAs($instructor)
            ->postJson(route('chat.requests.accept', ['messageRequest' => $messageRequestId]))
            ->assertOk()
            ->assertJsonPath('message_request.status', MessageRequest::STATUS_ACCEPTED);

        $conversationId = (int) $acceptResponse->json('conversation.id');
        $this->assertSame($pendingConversationId, $conversationId);

        $this->assertDatabaseHas('conversations', [
            'id' => $conversationId,
            'status' => Conversation::STATUS_ACCEPTED,
            'conversation_type' => Conversation::TYPE_DIRECT,
        ]);

        $this->actingAs($learner)
            ->postJson(route('chat.messages.store', ['conversation' => $conversationId]), [
                'message_body' => 'Thanks for accepting my request.',
            ])
            ->assertCreated()
            ->assertJsonPath('message.conversation_id', $conversationId);
    }

    public function test_enrolled_learner_to_instructor_starts_direct_without_request_row(): void
    {
        $learner = $this->createUserWithRole('learner');
        $instructor = $this->createUserWithRole('instructor');

        $module = Module::factory()->create([
            'created_by' => $instructor->id,
            'is_published' => true,
            'content_owner_type' => 'instructor',
        ]);

        ModuleEnrollment::query()->create([
            'user_id' => $learner->id,
            'module_id' => $module->id,
            'status' => EnrollmentStatus::Approved,
            'enrolled_at' => now(),
        ]);

        $response = $this->actingAs($learner)
            ->postJson(route('chat.conversations.start'), [
                'target_user_id' => $instructor->id,
                'conversation_type' => Conversation::TYPE_DIRECT,
                'initial_message' => 'This should not create a request.',
            ])
            ->assertCreated()
            ->assertJsonPath('requires_request', false);

        $conversationId = (int) $response->json('conversation.id');

        $this->assertDatabaseHas('conversations', [
            'id' => $conversationId,
            'conversation_type' => Conversation::TYPE_DIRECT,
        ]);

        $this->assertDatabaseMissing('message_requests', [
            'requester_id' => $learner->id,
            'instructor_id' => $instructor->id,
            'status' => MessageRequest::STATUS_PENDING,
        ]);
    }

    private function createUserWithRole(string $role): User
    {
        $user = User::factory()->create(['role' => $role]);
        $user->assignRole($role);

        return $user;
    }
}
