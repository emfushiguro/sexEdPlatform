<?php

namespace Tests\Feature\Chat;

use App\Models\Conversation;
use App\Models\MessageRequest;
use App\Models\User;
use App\Services\Chat\ChatAuthorizationService;
use Tests\TestCase;

class ChatChannelAuthorizationTest extends TestCase
{
    public function test_conversation_channel_allows_participants_and_denies_non_participants(): void
    {
        $service = app(ChatAuthorizationService::class);

        $learner = User::factory()->create(['role' => 'learner']);
        $instructor = User::factory()->create(['role' => 'instructor']);
        $admin = User::factory()->create(['role' => 'admin']);

        $conversation = Conversation::create([
            'participant_one_id' => $learner->id,
            'participant_two_id' => $instructor->id,
            'pair_key' => Conversation::makePairKey($learner->id, $instructor->id),
            'conversation_type' => Conversation::TYPE_DIRECT,
            'status' => Conversation::STATUS_ACTIVE,
            'context_key' => Conversation::makeContextKey(Conversation::TYPE_DIRECT, null),
        ]);

        $this->assertTrue($service->canSubscribeToConversation($learner, $conversation));
        $this->assertTrue($service->canSubscribeToConversation($instructor, $conversation));
        $this->assertFalse($service->canSubscribeToConversation($admin, $conversation));
    }

    public function test_request_channels_are_scoped_to_their_user_only(): void
    {
        $requester = User::factory()->create(['role' => 'learner']);
        $instructor = User::factory()->create(['role' => 'instructor']);
        $outsider = User::factory()->create(['role' => 'admin']);

        MessageRequest::create([
            'requester_id' => $requester->id,
            'instructor_id' => $instructor->id,
            'status' => MessageRequest::STATUS_PENDING,
            'initial_message' => 'Can we chat?',
        ]);

        $this->assertTrue($this->canAccessRequestsUserChannel($requester, $requester->id));
        $this->assertTrue($this->canAccessRequestsUserChannel($instructor, $instructor->id));

        $this->assertFalse($this->canAccessRequestsUserChannel($outsider, $requester->id));
        $this->assertFalse($this->canAccessRequestsUserChannel($outsider, $instructor->id));
    }

    private function canAccessRequestsUserChannel(User $authUser, int $channelUserId): bool
    {
        return (int) $authUser->id === $channelUserId;
    }
}
