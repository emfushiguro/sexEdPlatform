<?php

namespace App\Services\Chat;

use App\Models\Conversation;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\Quiz;
use InvalidArgumentException;

class ChatContextResolver
{
    /**
     * @return array{module_id: ?int, lesson_id: ?int, quiz_id: ?int, context_key: string}
     */
    public function resolve(string $conversationType, ?int $moduleId, ?int $lessonId, ?int $quizId): array
    {
        if (!Conversation::isSupportedConversationType($conversationType)) {
            throw new InvalidArgumentException('Unsupported conversation type.');
        }

        if (in_array($conversationType, [Conversation::TYPE_DIRECT, Conversation::TYPE_ADMIN_SUPPORT], true)) {
            if ($moduleId !== null || $lessonId !== null || $quizId !== null) {
                throw new InvalidArgumentException('This conversation type cannot include context IDs.');
            }

            return [
                'module_id' => null,
                'lesson_id' => null,
                'quiz_id' => null,
                'context_key' => Conversation::makeContextKey($conversationType, null),
            ];
        }

        if ($conversationType === Conversation::TYPE_MODULE_CHAT) {
            if ($moduleId === null || $lessonId !== null || $quizId !== null) {
                throw new InvalidArgumentException('Module chat requires only a module context.');
            }

            $resolvedModule = Module::query()->find($moduleId);

            if ($resolvedModule === null) {
                throw new InvalidArgumentException('Module context does not exist.');
            }

            return [
                'module_id' => $resolvedModule->id,
                'lesson_id' => null,
                'quiz_id' => null,
                'context_key' => Conversation::makeContextKey(Conversation::TYPE_MODULE_CHAT, $resolvedModule->id),
            ];
        }

        if ($conversationType === Conversation::TYPE_LESSON_CHAT) {
            if ($lessonId === null || $quizId !== null) {
                throw new InvalidArgumentException('Lesson chat requires a lesson context and no quiz context.');
            }

            $resolvedLesson = Lesson::query()->find($lessonId);

            if ($resolvedLesson === null) {
                throw new InvalidArgumentException('Lesson context does not exist.');
            }

            if ($moduleId !== null && $moduleId !== (int) $resolvedLesson->module_id) {
                throw new InvalidArgumentException('Lesson does not belong to the provided module.');
            }

            return [
                'module_id' => (int) $resolvedLesson->module_id,
                'lesson_id' => $resolvedLesson->id,
                'quiz_id' => null,
                'context_key' => Conversation::makeContextKey(Conversation::TYPE_LESSON_CHAT, $resolvedLesson->id),
            ];
        }

        if ($quizId === null) {
            throw new InvalidArgumentException('Quiz help chat requires a quiz context.');
        }

        $resolvedQuiz = Quiz::query()->find($quizId);

        if ($resolvedQuiz === null) {
            throw new InvalidArgumentException('Quiz context does not exist.');
        }

        $resolvedLessonId = $resolvedQuiz->lesson_id !== null ? (int) $resolvedQuiz->lesson_id : null;
        $resolvedModuleId = $resolvedQuiz->module_id !== null ? (int) $resolvedQuiz->module_id : null;

        if ($resolvedLessonId !== null) {
            $resolvedLesson = Lesson::query()->find($resolvedLessonId);

            if ($resolvedLesson === null) {
                throw new InvalidArgumentException('Quiz lesson lineage is invalid.');
            }

            if ($resolvedModuleId !== null && $resolvedModuleId !== (int) $resolvedLesson->module_id) {
                throw new InvalidArgumentException('Quiz module and lesson lineage is invalid.');
            }

            $resolvedModuleId = (int) $resolvedLesson->module_id;
        }

        if ($lessonId !== null && $resolvedLessonId !== null && $lessonId !== $resolvedLessonId) {
            throw new InvalidArgumentException('Quiz does not belong to the provided lesson.');
        }

        if ($moduleId !== null && $resolvedModuleId !== null && $moduleId !== $resolvedModuleId) {
            throw new InvalidArgumentException('Quiz does not belong to the provided module.');
        }

        return [
            'module_id' => $resolvedModuleId ?? $moduleId,
            'lesson_id' => $resolvedLessonId ?? $lessonId,
            'quiz_id' => $resolvedQuiz->id,
            'context_key' => Conversation::makeContextKey(Conversation::TYPE_QUIZ_HELP, $resolvedQuiz->id),
        ];
    }
}
