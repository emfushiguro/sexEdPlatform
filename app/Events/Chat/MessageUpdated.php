<?php

namespace App\Events\Chat;

use App\Models\Message;
use App\Support\Chat\MessagePayloadFormatter;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageUpdated implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public string $connection;

    public string $queue = 'broadcasts';

    public function __construct(public Message $message, public string $action)
    {
        $this->connection = config('queue.default') === 'redis'
            ? 'redis'
            : config('queue.default', 'sync');
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('chat.conversation.'.$this->message->conversation_id);
    }

    public function broadcastAs(): string
    {
        return 'chat.message.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'action' => $this->action,
            'message' => app(MessagePayloadFormatter::class)->format($this->message),
        ];
    }
}
