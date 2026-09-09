<?php

namespace Tests\Unit\Chat;

use App\Models\Conversation;
use App\Models\ConversationRead;
use App\Models\Message;
use App\Models\MessageRequest;
use App\Models\ParentChildInvitation;
use App\Models\User;
use App\Services\Chat\ChatService;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ChatServiceTest extends TestCase
{
    public function test_create_or_get_conversation_is_unique_for_normalized_pair_and_context(): void
    {
        $service = app(ChatService::class);

        $admin = User::factory()->create(['role' => 'admin']);
        $instructor = User::factory()->create(['role' => 'instructor']);

        $first = $service->createOrGetConversation($admin, $instructor, Conversation::TYPE_DIRECT);
        $second = $service->createOrGetConversation($instructor, $admin, Conversation::TYPE_DIRECT);

        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, Conversation::query()->count());
    }

    public function test_create_message_request_for_non_enrolled_learner_to_instructor(): void
    {
        $service = app(ChatService::class);

        $learner = User::factory()->create(['role' => 'learner']);
        $instructor = User::factory()->create(['role' => 'instructor']);

        $request = $service->createMessageRequest($learner, $instructor, 'Need help with your module.');

        $this->assertSame(MessageRequest::STATUS_PENDING, $request->status);
        $this->assertSame($learner->id, $request->requester_id);
        $this->assertSame($instructor->id, $request->instructor_id);
    }

    public function test_send_message_persists_message_and_updates_last_message_at(): void
    {
        $service = app(ChatService::class);

        $admin = User::factory()->create(['role' => 'admin']);
        $instructor = User::factory()->create(['role' => 'instructor']);

        $conversation = $service->createOrGetConversation($admin, $instructor, Conversation::TYPE_DIRECT);
        $message = $service->sendMessage($admin, $conversation, 'Hello there.');

        $this->assertInstanceOf(Message::class, $message);
        $this->assertSame($conversation->id, $message->conversation_id);
        $this->assertSame($admin->id, $message->sender_id);

        $freshConversation = $conversation->fresh();
        $this->assertNotNull($freshConversation?->last_message_at);
        $this->assertSame(
            $message->created_at?->format('Y-m-d H:i:s'),
            $freshConversation?->last_message_at?->format('Y-m-d H:i:s')
        );
    }

    public function test_mark_conversation_read_updates_single_read_state_row(): void
    {
        $service = app(ChatService::class);

        $admin = User::factory()->create(['role' => 'admin']);
        $instructor = User::factory()->create(['role' => 'instructor']);

        $conversation = $service->createOrGetConversation($admin, $instructor, Conversation::TYPE_DIRECT);

        $firstMessage = $service->sendMessage($admin, $conversation, 'First message.');
        $readOne = $service->markConversationRead($instructor, $conversation, $firstMessage);

        $this->assertInstanceOf(ConversationRead::class, $readOne);
        $this->assertSame($firstMessage->id, $readOne->last_read_message_id);

        $secondMessage = $service->sendMessage($admin, $conversation, 'Second message.');
        $readTwo = $service->markConversationRead($instructor, $conversation, $secondMessage);

        $this->assertSame($readOne->id, $readTwo->id);
        $this->assertSame($secondMessage->id, $readTwo->last_read_message_id);
        $this->assertSame(1, ConversationRead::query()->count());
    }

    public function test_guardian_invitation_chat_rejects_attachments_at_the_service_boundary(): void
    {
        $service = app(ChatService::class);
        $guardian = User::factory()->create(['role' => 'learner', 'status' => User::STATUS_ACTIVE]);
        $child = User::factory()->create(['role' => 'learner', 'status' => User::STATUS_ACTIVE]);
        $invitation = ParentChildInvitation::query()->create([
            'inviter_parent_user_id' => $guardian->id,
            'child_user_id' => $child->id,
            'invite_token' => (string) \Illuminate\Support\Str::uuid(),
            'relationship_type' => 'legal_guardian',
            'status' => 'pending',
            'expires_at' => now()->addDays(3),
        ]);
        $conversation = Conversation::query()->create([
            'participant_one_id' => min($guardian->id, $child->id),
            'participant_two_id' => max($guardian->id, $child->id),
            'pair_key' => Conversation::makePairKey($guardian->id, $child->id),
            'conversation_type' => Conversation::TYPE_GUARDIAN_INVITATION,
            'status' => Conversation::STATUS_ACTIVE,
            'parent_child_invitation_id' => $invitation->id,
            'context_key' => Conversation::makeContextKey(Conversation::TYPE_GUARDIAN_INVITATION, $invitation->id),
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Guardian invitation conversations support text messages only.');

        $service->sendMessage(
            $child,
            $conversation,
            'This text must not be accompanied by a file.',
            [UploadedFile::fake()->create('attachment.txt', 2, 'text/plain')],
        );
    }
}
