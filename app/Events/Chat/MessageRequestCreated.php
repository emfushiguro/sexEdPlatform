<?php

namespace App\Events\Chat;

use App\Models\MessageRequest;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageRequestCreated implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public string $connection;

    public string $queue = 'broadcasts';

    public function __construct(public MessageRequest $messageRequest)
    {
        $this->connection = config('queue.default') === 'redis'
            ? 'redis'
            : config('queue.default', 'sync');
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('chat.requests.user.'.$this->messageRequest->instructor_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'chat.request.created';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->messageRequest->id,
            'requester_id' => $this->messageRequest->requester_id,
            'instructor_id' => $this->messageRequest->instructor_id,
            'status' => $this->messageRequest->status,
            'initial_message' => $this->messageRequest->initial_message,
            'conversation_id' => $this->messageRequest->accepted_conversation_id,
            'created_at' => $this->messageRequest->created_at?->toIso8601String(),
        ];
    }
}
