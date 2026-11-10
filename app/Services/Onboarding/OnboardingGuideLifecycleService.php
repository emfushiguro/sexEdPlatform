<?php

namespace App\Services\Onboarding;

use App\Models\OnboardingGuide;
use App\Models\OnboardingGuideVersion;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

final class OnboardingGuideLifecycleService
{
    public function createDraft(User $actor, array $attributes, UploadedFile $video): OnboardingGuide
    {
        $stored = null;

        try {
            return DB::transaction(function () use ($actor, $attributes, $video, &$stored): OnboardingGuide {
                $guide = OnboardingGuide::create([
                    'guide_key' => $attributes['guide_key'] ?? OnboardingGuide::GUIDE_KEY_DASHBOARD_NAVIGATION,
                    'target_role' => $attributes['target_role'] ?? OnboardingGuide::ROLE_LEARNER,
                    'status' => OnboardingGuide::STATUS_DRAFT,
                    'created_by' => $actor->id,
                ]);

                $stored = $this->storeVideo($guide, $video);

                $guide->versions()->create([
                    'version_number' => 1,
                    'title' => $attributes['title'],
                    'description' => $attributes['description'],
                    ...$stored,
                    'requires_recompletion' => false,
                    'published_at' => null,
                    'created_by' => $actor->id,
                ]);

                return $guide->fresh(['versions']);
            });
        } catch (Throwable $exception) {
            if (is_array($stored)) {
                Storage::disk($stored['video_disk'])->delete($stored['video_path']);
            }

            throw $exception;
        }
    }

    public function updateDraft(
        User $actor,
        OnboardingGuide $guide,
        array $attributes,
        ?UploadedFile $video,
    ): OnboardingGuide {
        $stored = null;

        try {
            return DB::transaction(function () use (
                $actor,
                $guide,
                $attributes,
                $video,
                &$stored,
            ): OnboardingGuide {
                $locked = OnboardingGuide::query()
                    ->whereKey($guide->getKey())
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($locked->status === OnboardingGuide::STATUS_ARCHIVED) {
                    throw ValidationException::withMessages([
                        'guide' => 'Archived onboarding guides cannot be edited.',
                    ]);
                }

                $targetRole = array_key_exists('target_role', $attributes)
                    ? $attributes['target_role']
                    : $locked->target_role;
                if ($locked->current_version_id !== null && $targetRole !== $locked->target_role) {
                    throw ValidationException::withMessages([
                        'target_role' => 'The target role cannot change after publication.',
                    ]);
                }

                $this->lockCurrentVersion($locked);
                $draft = $locked->versions()
                    ->whereNull('published_at')
                    ->latest('version_number')
                    ->lockForUpdate()
                    ->first();

                if (! $draft) {
                    $current = $this->lockCurrentVersion($locked);
                    if (! $current) {
                        throw ValidationException::withMessages([
                            'version' => 'The onboarding guide has no draft version to edit.',
                        ]);
                    }

                    $draft = $locked->versions()->create([
                        'version_number' => $current->version_number + 1,
                        'title' => $current->title,
                        'description' => $current->description,
                        'video_disk' => $current->video_disk,
                        'video_path' => $current->video_path,
                        'video_original_name' => $current->video_original_name,
                        'video_mime_type' => $current->video_mime_type,
                        'video_size' => $current->video_size,
                        'requires_recompletion' => $current->requires_recompletion,
                        'published_at' => null,
                        'created_by' => $actor->id,
                    ]);
                }

                $oldMedia = null;
                if ($video !== null) {
                    $oldMedia = [$draft->video_disk, $draft->video_path];
                    $stored = $this->storeVideo($locked, $video);
                    $draft->fill($stored);
                }

                foreach (['title', 'description'] as $field) {
                    if (array_key_exists($field, $attributes)) {
                        $draft->{$field} = $attributes[$field];
                    }
                }
                $draft->save();

                if ($targetRole !== $locked->target_role) {
                    $locked->target_role = $targetRole;
                    $locked->save();
                }

                if ($oldMedia !== null) {
                    DB::afterCommit(function () use ($oldMedia): void {
                        $this->deleteIfUnreferenced($oldMedia[0], $oldMedia[1]);
                    });
                }

                return $locked->fresh(['versions', 'currentVersion']);
            });
        } catch (Throwable $exception) {
            if (is_array($stored)) {
                Storage::disk($stored['video_disk'])->delete($stored['video_path']);
            }

            throw $exception;
        }
    }

    public function publish(
        User $actor,
        OnboardingGuide $guide,
        bool $requiresRecompletion,
        bool $confirmSupersede,
    ): OnboardingGuide {
        return DB::transaction(function () use (
            $actor,
            $guide,
            $requiresRecompletion,
            $confirmSupersede,
        ): OnboardingGuide {
            $lockedGuides = OnboardingGuide::query()
                ->where('guide_key', $guide->guide_key)
                ->where('target_role', $guide->target_role)
                ->lockForUpdate()
                ->get();

            $locked = $lockedGuides->firstWhere('id', $guide->id)
                ?? throw new ModelNotFoundException();

            if ($locked->status === OnboardingGuide::STATUS_ARCHIVED) {
                throw ValidationException::withMessages([
                    'guide' => 'Archived onboarding guides cannot be published.',
                ]);
            }

            $current = $this->lockCurrentVersion($locked);
            $draft = $locked->versions()
                ->whereNull('published_at')
                ->latest('version_number')
                ->lockForUpdate()
                ->first();

            if (! $draft && $locked->status === OnboardingGuide::STATUS_PUBLISHED) {
                return $locked->fresh(['versions', 'currentVersion']);
            }

            if (! $draft) {
                throw ValidationException::withMessages([
                    'version' => 'The onboarding guide has no draft version to publish.',
                ]);
            }

            if (! Storage::disk($draft->video_disk)->exists($draft->video_path)) {
                throw ValidationException::withMessages([
                    'video' => 'The onboarding guide video is unavailable.',
                ]);
            }

            $otherPublished = $lockedGuides->first(
                fn (OnboardingGuide $candidate): bool => $candidate->id !== $locked->id
                    && $candidate->status === OnboardingGuide::STATUS_PUBLISHED
            );
            if ($otherPublished && ! $confirmSupersede) {
                throw ValidationException::withMessages([
                    'confirm_supersede' => 'Confirm that the existing published guide should be superseded.',
                ]);
            }

            if ($otherPublished) {
                $otherPublished->update(['status' => OnboardingGuide::STATUS_DRAFT]);
            }

            $isRepublish = $current !== null && (int) $current->id === (int) $draft->id;
            $draft->requires_recompletion = $current === null
                ? true
                : ($isRepublish ? $draft->requires_recompletion : $requiresRecompletion);
            $draft->published_at = now();
            $draft->save();

            $locked->update([
                'status' => OnboardingGuide::STATUS_PUBLISHED,
                'current_version_id' => $draft->id,
            ]);

            return $locked->fresh(['versions', 'currentVersion']);
        });
    }

    public function unpublish(OnboardingGuide $guide): OnboardingGuide
    {
        return DB::transaction(function () use ($guide): OnboardingGuide {
            $locked = OnboardingGuide::query()
                ->whereKey($guide->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($locked->status !== OnboardingGuide::STATUS_PUBLISHED) {
                return $locked->fresh(['versions', 'currentVersion']);
            }

            $current = $this->lockCurrentVersion($locked);
            $current?->update(['published_at' => null]);
            $locked->update(['status' => OnboardingGuide::STATUS_DRAFT]);

            return $locked->fresh(['versions', 'currentVersion']);
        });
    }

    public function archive(OnboardingGuide $guide): OnboardingGuide
    {
        return DB::transaction(function () use ($guide): OnboardingGuide {
            $locked = OnboardingGuide::query()
                ->whereKey($guide->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($locked->status !== OnboardingGuide::STATUS_ARCHIVED) {
                $locked->update([
                    'status' => OnboardingGuide::STATUS_ARCHIVED,
                    'archived_at' => now(),
                ]);
            }

            return $locked->fresh(['versions', 'currentVersion']);
        });
    }

    public function draftVersion(OnboardingGuide $guide): ?OnboardingGuideVersion
    {
        return OnboardingGuideVersion::query()
            ->where('onboarding_guide_id', $guide->getKey())
            ->whereNull('published_at')
            ->latest('version_number')
            ->first();
    }

    private function storeVideo(OnboardingGuide $guide, UploadedFile $video): array
    {
        $extension = strtolower((string) $video->getClientOriginalExtension());
        $filename = Str::uuid().($extension === '' ? '' : '.'.$extension);
        $directory = 'onboarding-guides/'.$guide->id;
        $path = Storage::disk('local')->putFileAs($directory, $video, $filename);

        if (! is_string($path) || $path === '') {
            throw new RuntimeException('Failed to store onboarding guide video.');
        }

        return [
            'video_disk' => 'local',
            'video_path' => $path,
            'video_original_name' => $this->safeDisplayName(
                $video->getClientOriginalName(),
                $extension,
            ),
            'video_mime_type' => (string) $video->getMimeType(),
            'video_size' => (int) $video->getSize(),
        ];
    }

    private function safeDisplayName(string $originalName, string $extension): string
    {
        $name = basename(str_replace('\\', '/', $originalName));
        $name = trim((string) preg_replace('/[\x00-\x1F\x7F]/', '', $name));

        if ($name === '' || $name === '.' || $name === '..') {
            return 'video'.($extension === '' ? '' : '.'.$extension);
        }

        return $name;
    }

    private function lockCurrentVersion(OnboardingGuide $guide): ?OnboardingGuideVersion
    {
        if ($guide->current_version_id === null) {
            return null;
        }

        $current = OnboardingGuideVersion::query()
            ->whereKey($guide->current_version_id)
            ->lockForUpdate()
            ->first();

        if (! $current || (int) $current->onboarding_guide_id !== (int) $guide->id) {
            throw ValidationException::withMessages([
                'current_version_id' => 'The current version must belong to this onboarding guide.',
            ]);
        }

        return $current;
    }

    private function deleteIfUnreferenced(string $disk, string $path): void
    {
        $referenced = OnboardingGuideVersion::query()
            ->where('video_disk', $disk)
            ->where('video_path', $path)
            ->exists();

        if (! $referenced) {
            Storage::disk($disk)->delete($path);
        }
    }
}
