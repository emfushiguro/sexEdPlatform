<?php

namespace App\Services;

use App\Models\LessonTopic;
use App\Models\LessonTopicCaption;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

final class LessonTopicCaptionService
{
    /**
     * @param  array<int, array<string, mixed>>  $tracks
     * @return array{stored: array<int, string>, obsolete: array<int, string>}
     */
    public function sync(
        LessonTopic $topic,
        array $tracks,
        ?int $defaultIndex,
    ): array {
        $disk = Storage::disk('public');
        $storedPaths = [];
        $obsoletePaths = [];

        try {
            $existing = $topic->captions()
                ->lockForUpdate()
                ->get()
                ->keyBy(fn (LessonTopicCaption $caption): int => (int) $caption->id);
            $activeTracks = collect($tracks)->reject(
                fn (array $track): bool => filter_var(
                    $track['remove'] ?? false,
                    FILTER_VALIDATE_BOOL,
                ),
            );
            $submittedIds = $activeTracks
                ->pluck('id')
                ->filter()
                ->map(fn ($id): int => (int) $id)
                ->unique()
                ->values();

            if ($submittedIds->diff($existing->keys())->isNotEmpty()) {
                throw ValidationException::withMessages([
                    'captions' => 'A submitted caption does not belong to this topic.',
                ]);
            }

            foreach ($existing as $id => $caption) {
                if (! $submittedIds->contains((int) $id)) {
                    $obsoletePaths[] = $caption->file_path;
                    $caption->deleteOrFail();

                    continue;
                }

                $caption->forceFill([
                    'language_code' => 'x-tmp-'.$caption->id,
                    'is_default' => false,
                ])->saveOrFail();
            }

            foreach ($activeTracks as $index => $track) {
                $caption = ! empty($track['id'])
                    ? $existing->get((int) $track['id'])
                    : new LessonTopicCaption;
                $oldPath = $caption->file_path;
                $file = $track['file'] ?? null;

                if ($file instanceof UploadedFile) {
                    $path = $file->store('captions/'.$topic->id, 'public');
                    if (! is_string($path) || $path === '') {
                        throw new RuntimeException('Failed to store caption upload.');
                    }

                    $storedPaths[] = $path;
                    $caption->file_path = $path;
                    if ($caption->exists && $oldPath) {
                        $obsoletePaths[] = $oldPath;
                    }
                }

                if (! $caption->file_path) {
                    throw ValidationException::withMessages([
                        'captions.'.$index.'.file' => 'A WebVTT file is required.',
                    ]);
                }

                $caption->lesson_topic_id = $topic->id;
                $caption->language_code = strtolower(trim($track['language_code']));
                $caption->label = trim($track['label']);
                $caption->is_default = $defaultIndex !== null
                    && (int) $index === $defaultIndex;
                $caption->saveOrFail();
            }

            return [
                'stored' => array_values(array_unique($storedPaths)),
                'obsolete' => array_values(array_unique(array_filter($obsoletePaths))),
            ];
        } catch (Throwable $exception) {
            $disk->delete($storedPaths);
            throw $exception;
        }
    }
}
