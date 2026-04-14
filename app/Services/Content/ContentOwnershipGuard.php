<?php

namespace App\Services\Content;

use App\Models\Lesson;
use App\Models\LessonTopic;
use App\Models\Module;
use App\Models\Quiz;
use App\Models\User;

class ContentOwnershipGuard
{
    public function ownerTypeForModule(Module $module): string
    {
        $module->loadMissing('creator');

        return $this->normalizeOwnerType(
            (string) ($module->content_owner_type ?? ''),
            $module->creator,
        );
    }

    public function ownerTypeForLesson(Lesson $lesson): string
    {
        $lesson->loadMissing('module.creator');

        $module = $lesson->module;
        if (!$module) {
            return 'instructor';
        }

        return $this->ownerTypeForModule($module);
    }

    public function ownerTypeForTopic(LessonTopic $topic): string
    {
        $topic->loadMissing('lesson.module.creator');

        $lesson = $topic->lesson;
        if (!$lesson) {
            return 'instructor';
        }

        return $this->ownerTypeForLesson($lesson);
    }

    public function ownerTypeForQuiz(Quiz $quiz): string
    {
        $quiz->loadMissing('module.creator', 'lesson.module.creator');

        if ($quiz->module) {
            return $this->ownerTypeForModule($quiz->module);
        }

        if ($quiz->lesson) {
            return $this->ownerTypeForLesson($quiz->lesson);
        }

        return 'instructor';
    }

    public function canAdminMutateOwnerType(string $ownerType): bool
    {
        return $this->normalizeOwnerType($ownerType) === 'admin';
    }

    private function normalizeOwnerType(?string $ownerType, ?User $creator = null): string
    {
        $owner = strtolower(trim((string) $ownerType));

        if (in_array($owner, ['admin', 'platform'], true)) {
            return 'admin';
        }

        if ($owner === 'instructor') {
            return 'instructor';
        }

        if ($creator && $creator->isAdmin()) {
            return 'admin';
        }

        return 'instructor';
    }
}