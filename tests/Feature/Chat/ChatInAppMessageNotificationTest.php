<?php

namespace Tests\Feature\Chat;

use App\Events\Chat\MessageSent;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Notifications\Chat\NewChatMessageNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ChatInAppMessageNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_message_sent_event_notifies_recipient_not_sender_with_chat_deep_link(): void
    {
        Notification::fake();

        $sender = User::factory()->create(['role' => 'learner']);
        $sender->assignRole('learner');

        $recipient = User::factory()->create(['role' => 'instructor']);
        $recipient->assignRole('instructor');

        $conversation = Conversation::query()->create([
            'participant_one_id' => $sender->id,
            'participant_two_id' => $recipient->id,
            'pair_key' => Conversation::makePairKey($sender->id, $recipient->id),
            'conversation_type' => Conversation::TYPE_DIRECT,
            'status' => Conversation::STATUS_ACTIVE,
            'context_key' => Conversation::makeContextKey(Conversation::TYPE_DIRECT, null),
            'last_message_at' => now(),
        ]);

        $message = Message::query()->create([
            'conversation_id' => $conversation->id,
            'sender_id' => $sender->id,
            'message_body' => 'Hello from learner!',
            'message_type' => 'text',
        ]);

        event(new MessageSent($message));

        Notification::assertSentTo(
            [$recipient],
            NewChatMessageNotification::class,
            function (NewChatMessageNotification $notification, array $channels, User $notifiable) use ($conversation, $message, $sender) {
                $payload = $notification->toDatabase($notifiable);

                return data_get($payload, 'conversation_id') === $conversation->id
                    && data_get($payload, 'chat_message_id') === $message->id
                    && data_get($payload, 'sender_name') === $sender->name
                    && data_get($payload, 'message_preview') === 'Hello from learner!'
                    && str_contains((string) data_get($payload, 'message'), 'Hello from learner!')
                    && filled(data_get($payload, 'sender_avatar_url'))
                    && str_contains((string) data_get($payload, 'action_url'), '/chat/conversation/');
            }
        );

        Notification::assertNotSentTo([$sender], NewChatMessageNotification::class);
    }
}
