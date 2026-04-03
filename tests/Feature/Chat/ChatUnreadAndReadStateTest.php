<?php

namespace Tests\Feature\Chat;

use App\Models\Conversation;
use App\Models\User;
use App\Services\Chat\ChatService;
use Tests\TestCase;

class ChatUnreadAndReadStateTest extends TestCase
{
    public function test_unread_increments_on_send_and_clears_after_mark_read(): void
    {
        $sender = User::factory()->create(['role' => 'admin']);
        $sender->assignRole('admin');

        $receiver = User::factory()->create(['role' => 'instructor']);
        $receiver->assignRole('instructor');

        /** @var ChatService $chatService */
        $chatService = app(ChatService::class);

        $conversation = $chatService->createOrGetConversation($sender, $receiver, Conversation::TYPE_DIRECT);
        $chatService->sendMessage($sender, $conversation, 'Unread counter check');

        $beforeRead = $this->actingAs($receiver)
            ->getJson(route('chat.conversations.index'))
            ->assertOk();

        $this->assertSame(1, $beforeRead->json('conversations.0.unread_count'));

        $this->actingAs($receiver)
            ->postJson(route('chat.conversations.read', ['conversation' => $conversation->id]))
            ->assertOk();

        $afterRead = $this->actingAs($receiver)
            ->getJson(route('chat.conversations.index'))
            ->assertOk();

        $this->assertSame(0, $afterRead->json('conversations.0.unread_count'));
    }
}
