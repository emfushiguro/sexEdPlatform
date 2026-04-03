<?php

namespace Tests\Feature\Chat;

use App\Models\Conversation;
use App\Models\MessageRequest;
use App\Models\User;
use Tests\TestCase;

class ChatHttpFlowTest extends TestCase
{
    public function test_list_conversations_endpoint_returns_user_scoped_results(): void
    {
        $learner = User::factory()->create(['role' => 'learner']);
        $learner->assignRole('learner');
        $instructor = User::factory()->create(['role' => 'instructor']);
        $instructor->assignRole('instructor');
        $admin = User::factory()->create(['role' => 'admin']);
        $admin->assignRole('admin');

        $forLearner = Conversation::create([
            'participant_one_id' => $learner->id,
            'participant_two_id' => $instructor->id,
            'pair_key' => Conversation::makePairKey($learner->id, $instructor->id),
            'conversation_type' => Conversation::TYPE_DIRECT,
            'status' => Conversation::STATUS_ACTIVE,
            'context_key' => Conversation::makeContextKey(Conversation::TYPE_DIRECT, null),
        ]);

        Conversation::create([
            'participant_one_id' => $admin->id,
            'participant_two_id' => $instructor->id,
            'pair_key' => Conversation::makePairKey($admin->id, $instructor->id),
            'conversation_type' => Conversation::TYPE_DIRECT,
            'status' => Conversation::STATUS_ACTIVE,
            'context_key' => Conversation::makeContextKey(Conversation::TYPE_DIRECT, null).':alt',
        ]);

        $response = $this->actingAs($learner)
            ->getJson(route('chat.conversations.index'))
            ->assertOk();

        $this->assertCount(1, $response->json('conversations'));
        $this->assertSame($forLearner->id, $response->json('conversations.0.id'));
    }

    public function test_start_send_accept_decline_and_forbidden_flows(): void
    {
        $learner = User::factory()->create(['role' => 'learner']);
        $learner->assignRole('learner');
        $instructor = User::factory()->create(['role' => 'instructor']);
        $instructor->assignRole('instructor');
        $admin = User::factory()->create(['role' => 'admin']);
        $admin->assignRole('admin');

        $startDirect = $this->actingAs($admin)
            ->postJson(route('chat.conversations.start'), [
                'target_user_id' => $instructor->id,
                'conversation_type' => Conversation::TYPE_DIRECT,
            ])
            ->assertCreated();

        $conversationId = (int) $startDirect->json('conversation.id');

        $this->actingAs($admin)
            ->postJson(route('chat.messages.store', ['conversation' => $conversationId]), [
                'message_body' => 'Hello from admin.',
            ])
            ->assertCreated()
            ->assertJsonPath('message.conversation_id', $conversationId);

        $requestStart = $this->actingAs($learner)
            ->postJson(route('chat.conversations.start'), [
                'target_user_id' => $instructor->id,
                'conversation_type' => Conversation::TYPE_DIRECT,
                'initial_message' => 'Can we discuss your module?',
            ])
            ->assertStatus(202)
            ->assertJsonPath('requires_request', true);

        $messageRequestId = (int) $requestStart->json('message_request.id');

        $this->actingAs($instructor)
            ->postJson(route('chat.requests.accept', ['messageRequest' => $messageRequestId]))
            ->assertOk()
            ->assertJsonPath('message_request.status', MessageRequest::STATUS_ACCEPTED);

        $toDecline = MessageRequest::create([
            'requester_id' => $learner->id,
            'instructor_id' => $instructor->id,
            'status' => MessageRequest::STATUS_PENDING,
            'initial_message' => 'Second request',
        ]);

        $this->actingAs($instructor)
            ->postJson(route('chat.requests.decline', ['messageRequest' => $toDecline->id]))
            ->assertOk()
            ->assertJsonPath('message_request.status', MessageRequest::STATUS_DECLINED);

        $this->actingAs($learner)
            ->postJson(route('chat.messages.store', ['conversation' => $conversationId]), [
                'message_body' => 'I should be forbidden from this conversation.',
            ])
            ->assertForbidden();

        $anotherRequest = MessageRequest::create([
            'requester_id' => $admin->id,
            'instructor_id' => $instructor->id,
            'status' => MessageRequest::STATUS_PENDING,
            'initial_message' => 'Admin request',
        ]);

        $this->actingAs($learner)
            ->postJson(route('chat.requests.accept', ['messageRequest' => $anotherRequest->id]))
            ->assertForbidden();
    }
}
