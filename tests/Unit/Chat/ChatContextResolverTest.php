<?php

namespace Tests\Unit\Chat;

use App\Models\Conversation;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\Quiz;
use App\Services\Chat\ChatContextResolver;
use InvalidArgumentException;
use Tests\TestCase;

class ChatContextResolverTest extends TestCase
{
    public function test_module_chat_context_resolves_with_valid_module(): void
    {
        $resolver = app(ChatContextResolver::class);
        $module = Module::factory()->create();

        $resolved = $resolver->resolve(
            conversationType: Conversation::TYPE_MODULE_CHAT,
            moduleId: $module->id,
            lessonId: null,
            quizId: null,
        );

        $this->assertSame($module->id, $resolved['module_id']);
        $this->assertNull($resolved['lesson_id']);
        $this->assertNull($resolved['quiz_id']);
        $this->assertSame(Conversation::TYPE_MODULE_CHAT.':'.$module->id, $resolved['context_key']);
    }

    public function test_direct_type_rejects_context_ids(): void
    {
        $resolver = app(ChatContextResolver::class);
        $module = Module::factory()->create();

        $this->expectException(InvalidArgumentException::class);

        $resolver->resolve(
            conversationType: Conversation::TYPE_DIRECT,
            moduleId: $module->id,
            lessonId: null,
            quizId: null,
        );
    }

    public function test_lesson_chat_rejects_mismatched_module_lineage(): void
    {
        $resolver = app(ChatContextResolver::class);

        $moduleA = Module::factory()->create();
        $moduleB = Module::factory()->create();

        $lesson = Lesson::factory()->create([
            'module_id' => $moduleA->id,
        ]);

        $this->expectException(InvalidArgumentException::class);

        $resolver->resolve(
            conversationType: Conversation::TYPE_LESSON_CHAT,
            moduleId: $moduleB->id,
            lessonId: $lesson->id,
            quizId: null,
        );
    }

    public function test_quiz_help_rejects_invalid_lineage_between_quiz_lesson_and_module(): void
    {
        $resolver = app(ChatContextResolver::class);

        $moduleA = Module::factory()->create();
        $moduleB = Module::factory()->create();

        $lesson = Lesson::factory()->create([
            'module_id' => $moduleA->id,
        ]);

        $quiz = Quiz::factory()->create([
            'module_id' => $moduleA->id,
            'lesson_id' => $lesson->id,
        ]);

        $this->expectException(InvalidArgumentException::class);

        $resolver->resolve(
            conversationType: Conversation::TYPE_QUIZ_HELP,
            moduleId: $moduleB->id,
            lessonId: $lesson->id,
            quizId: $quiz->id,
        );
    }
}
