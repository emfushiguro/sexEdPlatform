<?php

namespace Tests\Feature\Chat;

use App\Events\Chat\MessageRequestCreated;
use App\Events\Chat\MessageRequestResolved;
use App\Events\Chat\MessageSent;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\MessageRequest;
use App\Models\User;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class ChatRealtimeUiContractTest extends TestCase
{
    public function test_chat_store_is_bootstrapped_in_app_entry(): void
    {
        $contents = File::get(resource_path('js/app.js'));

        $this->assertStringContainsString("./chat/store", $contents);

        $echoContents = File::get(resource_path('js/echo.js'));
        $this->assertStringContainsString('.chat.message.updated', $echoContents);
        $this->assertStringNotContainsString('.chat.message.reaction.updated', $echoContents);
    }

    public function test_chat_conversations_api_shape_matches_frontend_contract(): void
    {
        $userA = User::factory()->create(['role' => 'admin']);
        $userA->assignRole('admin');

        $userB = User::factory()->create(['role' => 'instructor']);
        $userB->assignRole('instructor');

        Conversation::create([
            'participant_one_id' => $userA->id,
            'participant_two_id' => $userB->id,
            'pair_key' => Conversation::makePairKey($userA->id, $userB->id),
            'conversation_type' => Conversation::TYPE_DIRECT,
            'status' => Conversation::STATUS_ACTIVE,
            'context_key' => Conversation::makeContextKey(Conversation::TYPE_DIRECT, null),
        ]);

        $response = $this->actingAs($userA)
            ->getJson(route('chat.conversations.index'))
            ->assertOk();

        $response->assertJsonStructure([
            'conversations' => [
                [
                    'id',
                    'participant_one_id',
                    'participant_two_id',
                    'conversation_type',
                    'status',
                    'context_key',
                ],
            ],
        ]);
    }

    public function test_chat_event_payloads_include_required_realtime_fields(): void
    {
        $userA = User::factory()->create(['role' => 'learner']);
        $userB = User::factory()->create(['role' => 'instructor']);

        $conversation = Conversation::create([
            'participant_one_id' => $userA->id,
            'participant_two_id' => $userB->id,
            'pair_key' => Conversation::makePairKey($userA->id, $userB->id),
            'conversation_type' => Conversation::TYPE_DIRECT,
            'status' => Conversation::STATUS_ACTIVE,
            'context_key' => Conversation::makeContextKey(Conversation::TYPE_DIRECT, null),
        ]);

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $userA->id,
            'message_body' => 'Payload check',
        ]);

        $request = MessageRequest::create([
            'requester_id' => $userA->id,
            'instructor_id' => $userB->id,
            'status' => MessageRequest::STATUS_PENDING,
            'initial_message' => 'Need help',
        ]);

        $sentPayload = (new MessageSent($message))->broadcastWith();
        $createdPayload = (new MessageRequestCreated($request))->broadcastWith();

        $request->status = MessageRequest::STATUS_ACCEPTED;
        $request->accepted_conversation_id = $conversation->id;
        $request->decided_by_id = $userB->id;
        $request->decided_at = now();

        $resolvedPayload = (new MessageRequestResolved($request))->broadcastWith();

        $this->assertArrayHasKey('id', $sentPayload);
        $this->assertArrayHasKey('conversation_id', $sentPayload);
        $this->assertArrayHasKey('sender_id', $sentPayload);
        $this->assertArrayHasKey('message_body', $sentPayload);
        $this->assertArrayHasKey('attachments', $sentPayload);
        $this->assertArrayHasKey('is_deleted', $sentPayload);

        $this->assertArrayHasKey('id', $createdPayload);
        $this->assertArrayHasKey('requester_id', $createdPayload);
        $this->assertArrayHasKey('instructor_id', $createdPayload);
        $this->assertArrayHasKey('status', $createdPayload);
        $this->assertArrayHasKey('conversation_id', $createdPayload);

        $this->assertArrayHasKey('id', $resolvedPayload);
        $this->assertArrayHasKey('status', $resolvedPayload);
        $this->assertArrayHasKey('conversation_id', $resolvedPayload);
        $this->assertArrayHasKey('conversation_status', $resolvedPayload);
        $this->assertArrayHasKey('accepted_conversation_id', $resolvedPayload);
    }
}
