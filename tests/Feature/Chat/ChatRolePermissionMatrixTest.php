<?php

namespace Tests\Feature\Chat;

use App\Enums\EnrollmentStatus;
use App\Models\Conversation;
use App\Models\Module;
use App\Models\ModuleEnrollment;
use App\Models\User;
use Tests\TestCase;

class ChatRolePermissionMatrixTest extends TestCase
{
    public function test_allowed_role_pairs_can_start_direct_conversations(): void
    {
        $admin = $this->createUserWithRole('admin');
        $instructor = $this->createUserWithRole('instructor');
        $learner = $this->createUserWithRole('learner');

        $this->actingAs($admin)
            ->postJson(route('chat.conversations.start'), [
                'target_user_id' => $instructor->id,
                'conversation_type' => Conversation::TYPE_DIRECT,
            ])
            ->assertCreated()
            ->assertJsonPath('requires_request', false);

        $this->actingAs($admin)
            ->postJson(route('chat.conversations.start'), [
                'target_user_id' => $learner->id,
                'conversation_type' => Conversation::TYPE_DIRECT,
            ])
            ->assertCreated()
            ->assertJsonPath('requires_request', false);

        $this->actingAs($instructor)
            ->postJson(route('chat.conversations.start'), [
                'target_user_id' => $admin->id,
                'conversation_type' => Conversation::TYPE_DIRECT,
            ])
            ->assertCreated()
            ->assertJsonPath('requires_request', false);

        $this->actingAs($learner)
            ->postJson(route('chat.conversations.start'), [
                'target_user_id' => $admin->id,
                'conversation_type' => Conversation::TYPE_DIRECT,
            ])
            ->assertCreated()
            ->assertJsonPath('requires_request', false);
    }

    public function test_denied_role_paths_and_non_participant_send_are_forbidden(): void
    {
        $learnerA = $this->createUserWithRole('learner');
        $learnerB = $this->createUserWithRole('learner');
        $instructorA = $this->createUserWithRole('instructor');
        $instructorB = $this->createUserWithRole('instructor');

        $this->actingAs($learnerA)
            ->postJson(route('chat.conversations.start'), [
                'target_user_id' => $learnerB->id,
                'conversation_type' => Conversation::TYPE_DIRECT,
            ])
            ->assertForbidden();

        $this->actingAs($instructorA)
            ->postJson(route('chat.conversations.start'), [
                'target_user_id' => $instructorB->id,
                'conversation_type' => Conversation::TYPE_DIRECT,
            ])
            ->assertForbidden();

        $admin = $this->createUserWithRole('admin');
        $started = $this->actingAs($admin)
            ->postJson(route('chat.conversations.start'), [
                'target_user_id' => $instructorA->id,
                'conversation_type' => Conversation::TYPE_DIRECT,
            ])
            ->assertCreated();

        $conversationId = (int) $started->json('conversation.id');

        $this->actingAs($learnerA)
            ->postJson(route('chat.messages.store', ['conversation' => $conversationId]), [
                'message_body' => 'I should not be allowed here.',
            ])
            ->assertForbidden();
    }

    public function test_instructor_to_learner_requires_enrollment_relation(): void
    {
        $instructor = $this->createUserWithRole('instructor');
        $learner = $this->createUserWithRole('learner');

        $this->actingAs($instructor)
            ->postJson(route('chat.conversations.start'), [
                'target_user_id' => $learner->id,
                'conversation_type' => Conversation::TYPE_DIRECT,
            ])
            ->assertForbidden();

        $module = Module::factory()->create([
            'created_by' => $instructor->id,
            'is_published' => true,
            'content_owner_type' => 'instructor',
        ]);

        ModuleEnrollment::query()->create([
            'user_id' => $learner->id,
            'module_id' => $module->id,
            'status' => EnrollmentStatus::Approved,
            'enrolled_at' => now(),
        ]);

        $this->actingAs($instructor)
            ->postJson(route('chat.conversations.start'), [
                'target_user_id' => $learner->id,
                'conversation_type' => Conversation::TYPE_DIRECT,
            ])
            ->assertCreated()
            ->assertJsonPath('requires_request', false);
    }

    private function createUserWithRole(string $role): User
    {
        $user = User::factory()->create(['role' => $role]);
        $user->assignRole($role);

        return $user;
    }
}
