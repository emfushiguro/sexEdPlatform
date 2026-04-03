<?php

namespace Tests\Feature\Chat;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ChatSchemaCoreTest extends TestCase
{
    public function test_conversations_and_messages_tables_have_required_columns(): void
    {
        $this->assertTrue(Schema::hasTable('conversations'));
        $this->assertTrue(Schema::hasTable('messages'));

        $this->assertTrue(Schema::hasColumns('conversations', [
            'participant_one_id',
            'participant_two_id',
            'pair_key',
            'conversation_type',
            'status',
            'module_id',
            'lesson_id',
            'quiz_id',
            'context_key',
            'last_message_at',
            'created_at',
            'updated_at',
        ]));

        $this->assertTrue(Schema::hasColumns('messages', [
            'conversation_id',
            'sender_id',
            'message_body',
            'created_at',
            'updated_at',
        ]));
    }

    public function test_foreign_keys_are_enforced_for_chat_core_tables(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $conversation = Conversation::create([
            'participant_one_id' => $userA->id,
            'participant_two_id' => $userB->id,
            'pair_key' => Conversation::makePairKey($userA->id, $userB->id),
            'conversation_type' => Conversation::TYPE_DIRECT,
            'status' => Conversation::STATUS_ACTIVE,
            'context_key' => Conversation::makeContextKey(Conversation::TYPE_DIRECT, null),
        ]);

        Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $userA->id,
            'message_body' => 'hello',
        ]);

        $this->expectException(QueryException::class);

        Message::create([
            'conversation_id' => 999999,
            'sender_id' => $userA->id,
            'message_body' => 'invalid conversation FK',
        ]);
    }

    public function test_user_relationships_resolve_for_conversations_and_messages(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

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
            'message_body' => 'relationship check',
        ]);

        $this->assertTrue($userA->chatConversationsAsParticipantOne()->exists());
        $this->assertTrue($userB->chatConversationsAsParticipantTwo()->exists());
        $this->assertTrue($userA->chatMessages()->exists());
        $this->assertFalse($userB->chatMessages()->exists());

        $this->assertSame($conversation->id, $userA->chatConversationsAsParticipantOne()->first()?->id);
        $this->assertSame($conversation->id, $userB->chatConversationsAsParticipantTwo()->first()?->id);
        $this->assertSame($message->id, $userA->chatMessages()->first()?->id);
    }
}
