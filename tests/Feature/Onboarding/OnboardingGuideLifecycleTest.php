<?php

namespace Tests\Feature\Onboarding;

use App\Models\OnboardingGuide;
use App\Models\OnboardingGuideVersion;
use App\Models\User;
use App\Models\UserOnboardingGuideState;
use App\Services\Onboarding\OnboardingGuideLifecycleService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Tests\TestCase;

final class OnboardingGuideLifecycleTest extends TestCase
{
    public function test_create_draft_stores_private_uuid_video_metadata(): void
    {
        Storage::fake('local');
        $actor = User::factory()->create();
        $service = app(OnboardingGuideLifecycleService::class);

        $guide = $service->createDraft($actor, [
            'title' => 'Learner dashboard guide',
            'description' => 'Learn the dashboard.',
            'target_role' => 'learner',
        ], UploadedFile::fake()->create('walkthrough.mp4', 512, 'video/mp4'));

        $version = $service->draftVersion($guide);

        $this->assertNotNull($version);
        $this->assertSame(1, $version->version_number);
        $this->assertSame('local', $version->video_disk);
        $this->assertStringStartsWith(
            'onboarding-guides/'.$guide->id.'/',
            $version->video_path
        );
        $this->assertStringNotContainsString('walkthrough', $version->video_path);
        $this->assertSame('walkthrough.mp4', $version->video_original_name);
        $this->assertSame('video/mp4', $version->video_mime_type);
        $this->assertSame(512 * 1024, $version->video_size);
        Storage::disk('local')->assertExists($version->video_path);
    }

    public function test_text_only_editing_of_a_published_guide_creates_one_unpublished_next_version_sharing_media_safely(): void
    {
        Storage::fake('local');
        $actor = User::factory()->create();
        $service = app(OnboardingGuideLifecycleService::class);
        $guide = $this->createDraft($actor);

        $service->publish($actor, $guide, true, false);
        $published = $guide->fresh()->currentVersion;

        $updated = $service->updateDraft($actor, $guide->fresh(), [
            'title' => 'Revised dashboard guide',
            'description' => 'The revised dashboard guide.',
        ], null);
        $draft = $service->draftVersion($updated);

        $this->assertNotNull($draft);
        $this->assertSame(2, $draft->version_number);
        $this->assertNull($draft->published_at);
        $this->assertSame($published->video_path, $draft->video_path);
        $this->assertSame(2, OnboardingGuideVersion::query()->count());
        Storage::disk('local')->assertExists($published->video_path);
    }

    public function test_repeated_editing_reuses_the_same_draft_version(): void
    {
        Storage::fake('local');
        $actor = User::factory()->create();
        $service = app(OnboardingGuideLifecycleService::class);
        $guide = $this->createDraft($actor);

        $service->publish($actor, $guide, true, false);
        $firstEdit = $service->updateDraft($actor, $guide->fresh(), [
            'title' => 'First revision',
            'description' => 'First revision description.',
        ], null);
        $draft = $service->draftVersion($firstEdit);

        $secondEdit = $service->updateDraft($actor, $firstEdit->fresh(), [
            'title' => 'Second revision',
            'description' => 'Second revision description.',
        ], null);
        $updatedDraft = $service->draftVersion($secondEdit);

        $this->assertNotNull($draft);
        $this->assertNotNull($updatedDraft);
        $this->assertSame($draft->id, $updatedDraft->id);
        $this->assertSame('Second revision', $updatedDraft->title);
        $this->assertSame(2, OnboardingGuideVersion::query()->count());
    }

    public function test_replacing_draft_media_never_deletes_a_path_referenced_by_a_published_version(): void
    {
        Storage::fake('local');
        $actor = User::factory()->create();
        $service = app(OnboardingGuideLifecycleService::class);
        $guide = $this->createDraft($actor);

        $service->publish($actor, $guide, true, false);
        $published = $guide->fresh()->currentVersion;
        $draftGuide = $service->updateDraft($actor, $guide->fresh(), [
            'title' => 'Draft replacement',
            'description' => 'Draft replacement description.',
        ], null);
        $draft = $service->draftVersion($draftGuide);

        $updated = $service->updateDraft(
            $actor,
            $draftGuide->fresh(),
            ['title' => 'Draft replacement with new video'],
            UploadedFile::fake()->create('replacement.mp4', 256, 'video/mp4')
        );
        $replaced = $service->draftVersion($updated);

        $this->assertNotNull($draft);
        $this->assertNotNull($replaced);
        $this->assertSame($draft->id, $replaced->id);
        $this->assertNotSame($published->video_path, $replaced->video_path);
        Storage::disk('local')->assertExists($published->video_path);
        Storage::disk('local')->assertExists($replaced->video_path);
    }

    public function test_failed_database_update_removes_only_the_new_upload_and_leaves_the_prior_draft_intact(): void
    {
        Storage::fake('local');
        $actor = User::factory()->create();
        $service = app(OnboardingGuideLifecycleService::class);
        $guide = $this->createDraft($actor);
        $priorDraft = $service->draftVersion($guide);
        $filesBefore = Storage::disk('local')->allFiles('onboarding-guides/'.$guide->id);

        OnboardingGuideVersion::updating(function (OnboardingGuideVersion $version) use ($priorDraft): void {
            if ($version->is($priorDraft)) {
                throw new RuntimeException('Forced draft update failure.');
            }
        });

        try {
            $service->updateDraft(
                $actor,
                $guide->fresh(),
                ['title' => 'This update must fail'],
                UploadedFile::fake()->create('failed-replacement.mp4', 128, 'video/mp4')
            );
            $this->fail('The forced database update failure was not raised.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Forced draft update failure.', $exception->getMessage());
        } finally {
            OnboardingGuideVersion::flushEventListeners();
        }

        $remainingDraft = $service->draftVersion($guide->fresh());
        $filesAfter = Storage::disk('local')->allFiles('onboarding-guides/'.$guide->id);

        $this->assertNotNull($priorDraft);
        $this->assertNotNull($remainingDraft);
        $this->assertSame($priorDraft->id, $remainingDraft->id);
        $this->assertSame($priorDraft->title, $remainingDraft->title);
        $this->assertSame($priorDraft->video_path, $remainingDraft->video_path);
        $this->assertSame($filesBefore, $filesAfter);
        Storage::disk('local')->assertExists($priorDraft->video_path);
    }

    public function test_first_publication_forces_requires_recompletion_true(): void
    {
        Storage::fake('local');
        $actor = User::factory()->create();
        $service = app(OnboardingGuideLifecycleService::class);
        $guide = $this->createDraft($actor);

        $published = $service->publish($actor, $guide, false, false);
        $version = $published->fresh()->currentVersion;

        $this->assertSame(OnboardingGuide::STATUS_PUBLISHED, $published->status);
        $this->assertTrue($version->requires_recompletion);
    }

    public function test_informational_revision_records_false_and_preserves_earlier_rows(): void
    {
        Storage::fake('local');
        $actor = User::factory()->create();
        $service = app(OnboardingGuideLifecycleService::class);
        $guide = $this->createDraft($actor);

        $service->publish($actor, $guide, true, false);
        $first = $guide->fresh()->currentVersion;
        $draftGuide = $service->updateDraft($actor, $guide->fresh(), [
            'title' => 'Informational revision',
            'description' => 'No learner recompletion is required.',
        ], null);
        $published = $service->publish($actor, $draftGuide, false, false);
        $second = $published->fresh()->currentVersion;

        $this->assertSame(2, OnboardingGuideVersion::query()->count());
        $this->assertTrue($first->fresh()->requires_recompletion);
        $this->assertFalse($second->requires_recompletion);
        $this->assertNotNull($first->fresh()->published_at);
        $this->assertSame($first->id, OnboardingGuideVersion::query()->find($first->id)->id);
    }

    public function test_publication_requires_confirmation_before_superseding_another_guide_for_the_same_key_and_role(): void
    {
        Storage::fake('local');
        $actor = User::factory()->create();
        $service = app(OnboardingGuideLifecycleService::class);
        $current = $this->createDraft($actor);
        $service->publish($actor, $current, true, false);
        $replacement = $this->createDraft($actor);

        try {
            $service->publish($actor, $replacement, true, false);
            $this->fail('Superseding publication should require confirmation.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('confirm_supersede', $exception->errors());
        }

        $this->assertSame(OnboardingGuide::STATUS_PUBLISHED, $current->fresh()->status);
        $this->assertSame(OnboardingGuide::STATUS_DRAFT, $replacement->fresh()->status);

        $published = $service->publish($actor, $replacement->fresh(), true, true);

        $this->assertSame(OnboardingGuide::STATUS_PUBLISHED, $published->status);
        $this->assertSame(OnboardingGuide::STATUS_DRAFT, $current->fresh()->status);
        $this->assertNotNull($current->fresh()->current_version_id);
    }

    public function test_a_separate_superseding_guide_keeps_its_own_state_boundary(): void
    {
        Storage::fake('local');
        $actor = User::factory()->create();
        $service = app(OnboardingGuideLifecycleService::class);
        $learner = $this->createDraft($actor, ['target_role' => OnboardingGuide::ROLE_LEARNER]);
        $instructor = $this->createDraft($actor, ['target_role' => OnboardingGuide::ROLE_INSTRUCTOR]);
        $learnerReplacement = $this->createDraft($actor, ['target_role' => OnboardingGuide::ROLE_LEARNER]);

        $service->publish($actor, $learner, true, false);
        $service->publish($actor, $instructor, true, false);
        $instructorVersion = $instructor->fresh()->currentVersion;
        $state = UserOnboardingGuideState::factory()->for($instructorVersion, 'version')->create();

        $service->publish($actor, $learnerReplacement, true, true);

        $this->assertSame(OnboardingGuide::STATUS_DRAFT, $learner->fresh()->status);
        $this->assertSame(OnboardingGuide::STATUS_PUBLISHED, $instructor->fresh()->status);
        $this->assertSame($instructorVersion->id, $instructor->fresh()->current_version_id);
        $this->assertTrue($state->fresh()->version->is($instructorVersion));
    }

    public function test_unpublish_and_republish_without_a_new_draft_creates_no_version(): void
    {
        Storage::fake('local');
        $actor = User::factory()->create();
        $service = app(OnboardingGuideLifecycleService::class);
        $guide = $this->createDraft($actor);

        $published = $service->publish($actor, $guide, true, false);
        $versionId = $published->current_version_id;
        $unpublished = $service->unpublish($published);
        $republished = $service->publish($actor, $unpublished->fresh(), false, false);

        $this->assertSame($versionId, $unpublished->current_version_id);
        $this->assertSame($versionId, $republished->current_version_id);
        $this->assertSame(OnboardingGuide::STATUS_PUBLISHED, $republished->status);
        $this->assertSame(1, OnboardingGuideVersion::query()->count());
    }

    public function test_archive_retains_versions_files_and_states(): void
    {
        Storage::fake('local');
        $actor = User::factory()->create();
        $service = app(OnboardingGuideLifecycleService::class);
        $guide = $this->createDraft($actor);
        $published = $service->publish($actor, $guide, true, false);
        $version = $published->fresh()->currentVersion;
        $state = UserOnboardingGuideState::factory()->for($version, 'version')->create();

        $archived = $service->archive($published);

        $this->assertSame(OnboardingGuide::STATUS_ARCHIVED, $archived->status);
        $this->assertNotNull($archived->archived_at);
        $this->assertTrue($version->fresh()->is($archived->fresh()->currentVersion));
        $this->assertTrue($state->fresh()->version->is($version));
        Storage::disk('local')->assertExists($version->video_path);
        $this->assertSame(1, OnboardingGuideVersion::query()->count());
    }

    public function test_target_role_can_change_before_first_publication_but_is_rejected_afterward(): void
    {
        Storage::fake('local');
        $actor = User::factory()->create();
        $service = app(OnboardingGuideLifecycleService::class);
        $guide = $this->createDraft($actor, ['target_role' => OnboardingGuide::ROLE_LEARNER]);

        $changed = $service->updateDraft($actor, $guide, [
            'target_role' => OnboardingGuide::ROLE_INSTRUCTOR,
        ], null);

        $this->assertSame(OnboardingGuide::ROLE_INSTRUCTOR, $changed->target_role);
        $service->publish($actor, $changed, true, false);

        try {
            $service->updateDraft($actor, $changed->fresh(), [
                'target_role' => OnboardingGuide::ROLE_LEARNER,
            ], null);
            $this->fail('Target role changes after publication must be rejected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('target_role', $exception->errors());
        }

        $this->assertSame(OnboardingGuide::ROLE_INSTRUCTOR, $changed->fresh()->target_role);
    }

    public function test_publication_rejects_a_current_version_id_that_points_to_another_guides_version(): void
    {
        Storage::fake('local');
        $actor = User::factory()->create();
        $service = app(OnboardingGuideLifecycleService::class);
        $guide = $this->createDraft($actor);
        $otherGuide = $this->createDraft($actor);
        $otherVersion = $service->draftVersion($otherGuide);

        $guide->update(['current_version_id' => $otherVersion->id]);

        try {
            $service->publish($actor, $guide->fresh(), true, false);
            $this->fail('A foreign current version must be rejected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('current_version_id', $exception->errors());
        }

        $this->assertSame(OnboardingGuide::STATUS_DRAFT, $guide->fresh()->status);
    }

    public function test_duplicate_publish_requests_are_idempotent(): void
    {
        Storage::fake('local');
        $actor = User::factory()->create();
        $service = app(OnboardingGuideLifecycleService::class);
        $guide = $this->createDraft($actor);

        $first = $service->publish($actor, $guide, true, false);
        $second = $service->publish($actor, $first->fresh(), false, false);

        $this->assertSame($first->id, $second->id);
        $this->assertSame($first->current_version_id, $second->current_version_id);
        $this->assertSame(OnboardingGuide::STATUS_PUBLISHED, $second->status);
        $this->assertSame(1, OnboardingGuideVersion::query()->count());
    }

    private function createDraft(User $actor, array $attributes = []): OnboardingGuide
    {
        return app(OnboardingGuideLifecycleService::class)->createDraft(
            $actor,
            array_merge([
                'title' => 'Dashboard guide',
                'description' => 'Learn the dashboard.',
                'target_role' => OnboardingGuide::ROLE_LEARNER,
            ], $attributes),
            UploadedFile::fake()->create('guide.mp4', 512, 'video/mp4')
        );
    }
}
