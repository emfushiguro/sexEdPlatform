<?php

namespace Tests\Feature\Admin;

use App\Models\OnboardingGuide;
use App\Models\User;
use App\Models\UserOnboardingGuideState;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class AdminOnboardingGuideManagementTest extends TestCase
{
    public function test_non_admin_users_cannot_reach_any_onboarding_guide_route(): void
    {
        $learner = $this->user('learner');
        $guide = OnboardingGuide::factory()->create(['created_by' => null]);

        $responses = [
            $this->actingAs($learner)->get(route('admin.onboarding-guides.index')),
            $this->actingAs($learner)->get(route('admin.onboarding-guides.create')),
            $this->actingAs($learner)->post(route('admin.onboarding-guides.store')),
            $this->actingAs($learner)->get(route('admin.onboarding-guides.edit', $guide)),
            $this->actingAs($learner)->put(route('admin.onboarding-guides.update', $guide)),
            $this->actingAs($learner)->get(route('admin.onboarding-guides.history', $guide)),
            $this->actingAs($learner)->post(route('admin.onboarding-guides.publish', $guide)),
            $this->actingAs($learner)->post(route('admin.onboarding-guides.unpublish', $guide)),
            $this->actingAs($learner)->post(route('admin.onboarding-guides.archive', $guide)),
        ];

        foreach ($responses as $response) {
            $response->assertForbidden();
        }
    }

    public function test_admin_can_create_learner_and_instructor_drafts_and_invalid_files_are_rejected(): void
    {
        Storage::fake('local');
        $admin = $this->admin();

        foreach (['learner', 'instructor'] as $role) {
            $response = $this->actingAs($admin)->post(route('admin.onboarding-guides.store'), [
                'title' => ucfirst($role).' dashboard guide',
                'description' => 'A guide for the '.$role.' dashboard.',
                'target_role' => $role,
                'video' => UploadedFile::fake()->create($role.'.mp4', 512, 'video/mp4'),
            ]);

            $response->assertRedirect();
            $guide = OnboardingGuide::query()->where('target_role', $role)->latest('id')->firstOrFail();
            $version = $guide->versions()->firstOrFail();

            $this->assertSame(OnboardingGuide::STATUS_DRAFT, $guide->status);
            $this->assertSame(1, $version->version_number);
            Storage::disk('local')->assertExists($version->video_path);
        }

        $base = [
            'title' => 'Invalid upload guide',
            'description' => 'This guide should not be stored.',
            'target_role' => 'learner',
        ];

        $this->actingAs($admin)
            ->post(route('admin.onboarding-guides.store'), $base)
            ->assertSessionHasErrors('video');

        $this->actingAs($admin)
            ->post(route('admin.onboarding-guides.store'), $base + [
                'video' => UploadedFile::fake()->create('guide.txt', 1, 'text/plain'),
            ])
            ->assertSessionHasErrors('video');

        $this->actingAs($admin)
            ->post(route('admin.onboarding-guides.store'), $base + [
                'video' => UploadedFile::fake()->create('large.mp4', 102401, 'video/mp4'),
            ])
            ->assertSessionHasErrors('video');
    }

    public function test_draft_edit_can_change_target_role_but_published_guide_cannot(): void
    {
        Storage::fake('local');
        $admin = $this->admin();
        $guide = $this->createGuide($admin, 'learner');

        $this->actingAs($admin)
            ->put(route('admin.onboarding-guides.update', $guide), [
                'title' => 'Instructor dashboard guide',
                'description' => 'Updated before publication.',
                'target_role' => 'instructor',
            ])
            ->assertRedirect();

        $guide->refresh();
        $this->assertSame('instructor', $guide->target_role);

        $this->publishGuide($admin, $guide);

        $this->actingAs($admin)
            ->put(route('admin.onboarding-guides.update', $guide->fresh()), [
                'title' => 'Published guide edit',
                'description' => 'The role must remain immutable.',
                'target_role' => 'learner',
            ])
            ->assertSessionHasErrors('target_role');
    }

    public function test_revision_publication_requires_choice_and_supersede_confirmation(): void
    {
        Storage::fake('local');
        $admin = $this->admin();
        $guide = $this->createGuide($admin, 'learner');
        $this->publishGuide($admin, $guide);

        $this->actingAs($admin)
            ->put(route('admin.onboarding-guides.update', $guide->fresh()), [
                'title' => 'Revised guide',
                'description' => 'A revised onboarding guide.',
            ])
            ->assertRedirect();

        $this->actingAs($admin)
            ->post(route('admin.onboarding-guides.publish', $guide->fresh()))
            ->assertSessionHasErrors('requires_recompletion');

        $replacement = $this->createGuide($admin, 'learner', 'Replacement guide');

        $this->actingAs($admin)
            ->post(route('admin.onboarding-guides.publish', $replacement), [
                'requires_recompletion' => '1',
            ])
            ->assertSessionHasErrors('confirm_supersede');

        $this->actingAs($admin)
            ->post(route('admin.onboarding-guides.publish', $replacement->fresh()), [
                'requires_recompletion' => '1',
                'confirm_supersede' => '1',
            ])
            ->assertRedirect();

        $this->assertSame(OnboardingGuide::STATUS_DRAFT, $guide->fresh()->status);
        $this->assertSame(OnboardingGuide::STATUS_PUBLISHED, $replacement->fresh()->status);
    }

    public function test_unpublish_republish_and_archive_preserve_records_files_and_states(): void
    {
        Storage::fake('local');
        $admin = $this->admin();
        $guide = $this->createGuide($admin, 'instructor');
        $published = $this->publishGuide($admin, $guide);
        $version = $published->fresh()->currentVersion;
        $state = UserOnboardingGuideState::factory()->create([
            'user_id' => $admin->id,
            'onboarding_guide_version_id' => $version->id,
            'completed_at' => now(),
        ]);

        $this->actingAs($admin)
            ->post(route('admin.onboarding-guides.unpublish', $published))
            ->assertRedirect();

        $unpublished = $published->fresh();
        $this->assertSame(OnboardingGuide::STATUS_DRAFT, $unpublished->status);
        $this->assertSame($version->id, $unpublished->current_version_id);
        Storage::disk('local')->assertExists($version->video_path);

        $this->actingAs($admin)
            ->post(route('admin.onboarding-guides.publish', $unpublished))
            ->assertRedirect();

        $republished = $unpublished->fresh();
        $this->assertSame(OnboardingGuide::STATUS_PUBLISHED, $republished->status);
        $this->assertSame($version->id, $republished->current_version_id);
        $this->assertDatabaseCount('onboarding_guide_versions', 1);

        $this->actingAs($admin)
            ->post(route('admin.onboarding-guides.archive', $republished))
            ->assertRedirect();

        $archived = $republished->fresh();
        $this->assertSame(OnboardingGuide::STATUS_ARCHIVED, $archived->status);
        $this->assertNotNull($archived->archived_at);
        $state->refresh();
        $this->assertSame($version->id, $state->onboarding_guide_version_id);
        $this->assertNotNull($state->completed_at);
        Storage::disk('local')->assertExists($version->video_path);
    }

    public function test_index_and_history_show_metadata_analytics_direct_counts_and_authorized_preview(): void
    {
        Storage::fake('local');
        $admin = $this->admin();
        $completed = $this->user('instructor');
        $dismissed = $this->user('instructor');
        $guide = $this->createGuide($admin, 'instructor', 'Instructor navigation guide');
        $published = $this->publishGuide($admin, $guide);
        $version = $published->fresh()->currentVersion;

        UserOnboardingGuideState::factory()->create([
            'user_id' => $completed->id,
            'onboarding_guide_version_id' => $version->id,
            'completed_at' => now(),
        ]);
        UserOnboardingGuideState::factory()->create([
            'user_id' => $dismissed->id,
            'onboarding_guide_version_id' => $version->id,
            'dismissed_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.onboarding-guides.index'))
            ->assertOk()
            ->assertSee('Instructor navigation guide')
            ->assertSee('Instructor')
            ->assertSee('Published')
            ->assertSee('Version 1')
            ->assertSee('Eligible users')
            ->assertSee('Completed')
            ->assertSee('Dismissed')
            ->assertSee('1');

        $this->actingAs($admin)
            ->get(route('admin.onboarding-guides.history', $published))
            ->assertOk()
            ->assertSee('Version 1')
            ->assertSee('Direct completions')
            ->assertSee('Direct dismissals without completion')
            ->assertSee('video/mp4')
            ->assertSee('1');

        $this->actingAs($admin)
            ->get(route('admin.onboarding-guides.edit', $published))
            ->assertOk()
            ->assertSee(route('onboarding-guides.video', $version), false)
            ->assertDontSee($version->video_path, false);
    }

    public function test_admin_navigation_is_permission_gated_and_ordinary_layouts_do_not_include_it(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->get(route('admin.onboarding-guides.index'))
            ->assertSee('Onboarding Guides');

        $adminLayout = file_get_contents(resource_path('views/layouts/admin.blade.php'));
        $ordinaryLayout = file_get_contents(resource_path('views/layouts/app.blade.php'));

        $this->assertIsString($adminLayout);
        $this->assertIsString($ordinaryLayout);
        $this->assertStringContainsString('@can(\'manage onboarding guides\')', $adminLayout);
        $this->assertStringContainsString('admin.onboarding-guides.*', $adminLayout);
        $this->assertStringNotContainsString('Onboarding Guides', $ordinaryLayout);
    }

    public function test_xhr_create_and_update_responses_contain_the_edit_redirect(): void
    {
        Storage::fake('local');
        $admin = $this->admin();
        $headers = [
            'Accept' => 'application/json',
            'X-Requested-With' => 'XMLHttpRequest',
        ];

        $create = $this->actingAs($admin)->post(route('admin.onboarding-guides.store'), [
            'title' => 'XHR guide',
            'description' => 'Created through the upload module.',
            'target_role' => 'learner',
            'video' => UploadedFile::fake()->create('xhr.mp4', 1, 'video/mp4'),
        ], $headers);

        $create->assertOk()->assertJsonPath('success', true);
        $guide = OnboardingGuide::query()->latest('id')->firstOrFail();
        $create->assertJsonPath('redirect', route('admin.onboarding-guides.edit', $guide));

        $update = $this->actingAs($admin)->post(route('admin.onboarding-guides.update', $guide), [
            '_method' => 'PUT',
            'title' => 'XHR guide updated',
            'description' => 'Updated through the upload module.',
        ], $headers);

        $update->assertOk()->assertJsonPath('success', true);
        $update->assertJsonPath('redirect', route('admin.onboarding-guides.edit', $guide));
    }

    private function admin(): User
    {
        return $this->user('admin');
    }

    private function user(string $role): User
    {
        $user = User::factory()->create([
            'role' => $role,
            'status' => User::STATUS_ACTIVE,
        ]);
        $user->assignRole($role);

        return $user->fresh();
    }

    private function createGuide(User $admin, string $role, ?string $title = null): OnboardingGuide
    {
        $response = $this->actingAs($admin)->post(route('admin.onboarding-guides.store'), [
            'title' => $title ?? ucfirst($role).' dashboard guide',
            'description' => 'A guide for the '.$role.' dashboard.',
            'target_role' => $role,
            'video' => UploadedFile::fake()->create($role.'-'.uniqid().'.mp4', 1, 'video/mp4'),
        ]);

        $response->assertRedirect();

        return OnboardingGuide::query()->latest('id')->firstOrFail();
    }

    private function publishGuide(User $admin, OnboardingGuide $guide): OnboardingGuide
    {
        $this->actingAs($admin)
            ->post(route('admin.onboarding-guides.publish', $guide), ['requires_recompletion' => '1'])
            ->assertRedirect();

        return $guide->fresh(['currentVersion']);
    }
}
