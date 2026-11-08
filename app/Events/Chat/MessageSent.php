<?php

namespace App\Events\Chat;

use App\Models\Conversation;
use App\Models\Message;
use App\Services\Chat\ConnectorConversationService;
use App\Support\Chat\MessagePayloadFormatter;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public string $connection;

    public string $queue = 'broadcasts';

    public function __construct(public Message $message)
    {
        $this->connection = config('queue.default') === 'redis'
            ? 'redis'
            : config('queue.default', 'sync');
    }

    public function broadcastWhen(): bool
    {
        $conversation = $this->message->conversation;

        return $conversation->conversation_type !== Conversation::TYPE_CONNECTOR_NEGOTIATION
            || ($conversation->participantOne !== null
                && app(ConnectorConversationService::class)->visibleTo($conversation->participantOne)->whereKey($conversation->id)->exists());
    }

    public function broadcastOn(): PrivateChannel|array
    {
        if (! $this->broadcastWhen()) {
            return [];
        }

        return new PrivateChannel('chat.conversation.'.$this->message->conversation_id);
    }

    public function broadcastAs(): string
    {
        return 'chat.message.sent';
    }

    public function broadcastWith(): array
    {
        return app(MessagePayloadFormatter::class)->format($this->message);
    }
}
