<?php

namespace Tests\Feature\Chat;

use App\Models\Conversation;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\Quiz;
use App\Models\User;
use Tests\TestCase;

class ChatContextConversationFlowTest extends TestCase
{
    public function test_module_lesson_and_quiz_context_start_and_send_flows(): void
    {
        $admin = $this->createUserWithRole('admin');
        $instructor = $this->createUserWithRole('instructor');

        $module = Module::factory()->create([
            'created_by' => $instructor->id,
            'is_published' => true,
            'content_owner_type' => 'instructor',
        ]);

        $lesson = Lesson::factory()->create([
            'module_id' => $module->id,
        ]);

        $quiz = Quiz::factory()->create([
            'module_id' => $module->id,
            'lesson_id' => $lesson->id,
        ]);

        $moduleStart = $this->actingAs($admin)
            ->postJson(route('chat.conversations.start'), [
                'target_user_id' => $instructor->id,
                'conversation_type' => Conversation::TYPE_MODULE_CHAT,
                'module_id' => $module->id,
            ])
            ->assertCreated();

        $moduleConversationId = (int) $moduleStart->json('conversation.id');

        $this->assertDatabaseHas('conversations', [
            'id' => $moduleConversationId,
            'conversation_type' => Conversation::TYPE_MODULE_CHAT,
            'module_id' => $module->id,
            'lesson_id' => null,
            'quiz_id' => null,
        ]);

        $this->actingAs($admin)
            ->postJson(route('chat.messages.store', ['conversation' => $moduleConversationId]), [
                'message_body' => 'Module-level discussion.',
            ])
            ->assertCreated();

        $lessonStart = $this->actingAs($admin)
            ->postJson(route('chat.conversations.start'), [
                'target_user_id' => $instructor->id,
                'conversation_type' => Conversation::TYPE_LESSON_CHAT,
                'module_id' => $module->id,
                'lesson_id' => $lesson->id,
            ])
            ->assertCreated();

        $lessonConversationId = (int) $lessonStart->json('conversation.id');

        $this->assertDatabaseHas('conversations', [
            'id' => $lessonConversationId,
            'conversation_type' => Conversation::TYPE_LESSON_CHAT,
            'module_id' => $module->id,
            'lesson_id' => $lesson->id,
            'quiz_id' => null,
        ]);

        $quizStart = $this->actingAs($admin)
            ->postJson(route('chat.conversations.start'), [
                'target_user_id' => $instructor->id,
                'conversation_type' => Conversation::TYPE_QUIZ_HELP,
                'module_id' => $module->id,
                'lesson_id' => $lesson->id,
                'quiz_id' => $quiz->id,
            ])
            ->assertCreated();

        $quizConversationId = (int) $quizStart->json('conversation.id');

        $this->assertDatabaseHas('conversations', [
            'id' => $quizConversationId,
            'conversation_type' => Conversation::TYPE_QUIZ_HELP,
            'module_id' => $module->id,
            'lesson_id' => $lesson->id,
            'quiz_id' => $quiz->id,
        ]);

        $this->actingAs($instructor)
            ->postJson(route('chat.messages.store', ['conversation' => $quizConversationId]), [
                'message_body' => 'Quiz-level guidance sent.',
            ])
            ->assertCreated()
            ->assertJsonPath('message.conversation_id', $quizConversationId);
    }

    public function test_context_start_rejects_lineage_mismatch_requests(): void
    {
        $admin = $this->createUserWithRole('admin');
        $instructor = $this->createUserWithRole('instructor');

        $moduleA = Module::factory()->create([
            'created_by' => $instructor->id,
            'is_published' => true,
            'content_owner_type' => 'instructor',
        ]);
        $moduleB = Module::factory()->create([
            'created_by' => $instructor->id,
            'is_published' => true,
            'content_owner_type' => 'instructor',
        ]);

        $lessonInModuleA = Lesson::factory()->create([
            'module_id' => $moduleA->id,
        ]);

        $quizInModuleA = Quiz::factory()->create([
            'module_id' => $moduleA->id,
            'lesson_id' => $lessonInModuleA->id,
        ]);

        $this->actingAs($admin)
            ->postJson(route('chat.conversations.start'), [
                'target_user_id' => $instructor->id,
                'conversation_type' => Conversation::TYPE_LESSON_CHAT,
                'module_id' => $moduleB->id,
                'lesson_id' => $lessonInModuleA->id,
            ])
            ->assertForbidden();

        $this->actingAs($admin)
            ->postJson(route('chat.conversations.start'), [
                'target_user_id' => $instructor->id,
                'conversation_type' => Conversation::TYPE_QUIZ_HELP,
                'module_id' => $moduleB->id,
                'lesson_id' => $lessonInModuleA->id,
                'quiz_id' => $quizInModuleA->id,
            ])
            ->assertForbidden();
    }

    private function createUserWithRole(string $role): User
    {
        $user = User::factory()->create(['role' => $role]);
        $user->assignRole($role);

        return $user;
    }
}
