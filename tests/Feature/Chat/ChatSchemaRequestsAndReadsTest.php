<?php

namespace Tests\Feature\Chat;

use App\Models\Conversation;
use App\Models\ConversationRead;
use App\Models\MessageRequest;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ChatSchemaRequestsAndReadsTest extends TestCase
{
    public function test_message_requests_and_conversation_reads_tables_exist_with_required_columns(): void
    {
        $this->assertTrue(Schema::hasTable('message_requests'));
        $this->assertTrue(Schema::hasTable('conversation_reads'));

        $this->assertTrue(Schema::hasColumns('message_requests', [
            'requester_id',
            'instructor_id',
            'status',
            'initial_message',
            'accepted_conversation_id',
            'decided_by_id',
            'decided_at',
            'created_at',
            'updated_at',
        ]));

        $this->assertTrue(Schema::hasColumns('conversation_reads', [
            'conversation_id',
            'user_id',
            'last_read_message_id',
            'last_read_at',
            'created_at',
            'updated_at',
        ]));
    }

    public function test_conversation_reads_enforces_single_row_per_user_per_conversation(): void
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

        ConversationRead::create([
            'conversation_id' => $conversation->id,
            'user_id' => $userA->id,
        ]);

        $this->expectException(QueryException::class);

        ConversationRead::create([
            'conversation_id' => $conversation->id,
            'user_id' => $userA->id,
        ]);
    }

    public function test_message_request_status_transitions_are_representable(): void
    {
        $requester = User::factory()->create(['role' => 'learner']);
        $instructor = User::factory()->create(['role' => 'instructor']);
        $decider = User::factory()->create(['role' => 'instructor']);

        $request = MessageRequest::create([
            'requester_id' => $requester->id,
            'instructor_id' => $instructor->id,
            'status' => MessageRequest::STATUS_PENDING,
            'initial_message' => 'Can I ask for help?',
        ]);

        $this->assertSame(MessageRequest::STATUS_PENDING, $request->status);

        $request->update([
            'status' => MessageRequest::STATUS_ACCEPTED,
            'decided_by_id' => $decider->id,
            'decided_at' => now(),
        ]);

        $this->assertSame(MessageRequest::STATUS_ACCEPTED, $request->fresh()->status);

        $request->update([
            'status' => MessageRequest::STATUS_DECLINED,
            'decided_by_id' => $decider->id,
            'decided_at' => now(),
        ]);

        $this->assertSame(MessageRequest::STATUS_DECLINED, $request->fresh()->status);
    }
}
