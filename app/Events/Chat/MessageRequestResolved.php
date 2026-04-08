<?php

namespace App\Events\Chat;

use App\Models\MessageRequest;
use App\Models\Conversation;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageRequestResolved implements ShouldBroadcast
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
            new PrivateChannel('chat.requests.user.'.$this->messageRequest->requester_id),
            new PrivateChannel('chat.requests.user.'.$this->messageRequest->instructor_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'chat.request.resolved';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->messageRequest->id,
            'status' => $this->messageRequest->status,
            'conversation_id' => $this->messageRequest->accepted_conversation_id,
            'accepted_conversation_id' => $this->messageRequest->accepted_conversation_id,
            'conversation_status' => $this->resolveConversationStatus(),
            'decided_by_id' => $this->messageRequest->decided_by_id,
            'decided_at' => $this->messageRequest->decided_at?->toIso8601String(),
        ];
    }

    protected function resolveConversationStatus(): ?string
    {
        if ($this->messageRequest->accepted_conversation_id === null) {
            return null;
        }

        return Conversation::query()
            ->whereKey($this->messageRequest->accepted_conversation_id)
            ->value('status');
    }
}
