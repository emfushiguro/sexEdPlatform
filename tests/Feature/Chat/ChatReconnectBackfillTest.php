<?php

namespace Tests\Feature\Chat;

use App\Models\Conversation;
use App\Models\User;
use App\Services\Chat\ChatService;
use Tests\TestCase;

class ChatReconnectBackfillTest extends TestCase
{
    public function test_backfill_endpoint_returns_only_messages_newer_than_last_known_id_and_is_authorized(): void
    {
        $sender = User::factory()->create(['role' => 'admin']);
        $sender->assignRole('admin');

        $receiver = User::factory()->create(['role' => 'instructor']);
        $receiver->assignRole('instructor');

        $outsider = User::factory()->create(['role' => 'learner']);
        $outsider->assignRole('learner');

        /** @var ChatService $chatService */
        $chatService = app(ChatService::class);

        $conversation = $chatService->createOrGetConversation($sender, $receiver, Conversation::TYPE_DIRECT);
        $first = $chatService->sendMessage($sender, $conversation, 'First');
        $second = $chatService->sendMessage($sender, $conversation, 'Second');
        $third = $chatService->sendMessage($sender, $conversation, 'Third');

        $this->actingAs($receiver)
            ->getJson(route('chat.messages.since', ['conversation' => $conversation->id, 'lastMessageId' => $first->id]))
            ->assertOk()
            ->assertJsonCount(2, 'messages')
            ->assertJsonPath('messages.0.id', $second->id)
            ->assertJsonPath('messages.1.id', $third->id);

        $this->actingAs($outsider)
            ->getJson(route('chat.messages.since', ['conversation' => $conversation->id, 'lastMessageId' => $first->id]))
            ->assertForbidden();
    }

    public function test_failed_optimistic_message_can_be_retried_via_existing_send_api(): void
    {
        $sender = User::factory()->create(['role' => 'admin']);
        $sender->assignRole('admin');

        $receiver = User::factory()->create(['role' => 'instructor']);
        $receiver->assignRole('instructor');

        /** @var ChatService $chatService */
        $chatService = app(ChatService::class);

        $conversation = $chatService->createOrGetConversation($sender, $receiver, Conversation::TYPE_DIRECT);

        $this->actingAs($sender)
            ->postJson(route('chat.messages.store', ['conversation' => $conversation->id]), [
                'message_body' => 'retry payload',
                'retry_of' => 'optimistic-123',
            ])
            ->assertCreated()
            ->assertJsonPath('message.message_body', 'retry payload');
    }
}
