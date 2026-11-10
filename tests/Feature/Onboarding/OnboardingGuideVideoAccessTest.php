<?php

declare(strict_types=1);

namespace Tests\Feature\Onboarding;

use App\Models\LearnerProfile;
use App\Models\OnboardingGuide;
use App\Models\OnboardingGuideVersion;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class OnboardingGuideVideoAccessTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        DB::table('provinces')->updateOrInsert(
            ['code' => '402100000'],
            [
                'name' => 'Sample Province',
                'region_code' => '040000000',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );
        DB::table('cities')->updateOrInsert(
            ['code' => '402101000'],
            [
                'name' => 'Sample City',
                'region_code' => '040000000',
                'province_code' => '402100000',
                'is_city' => true,
                'city_class' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );
        DB::table('barangays')->updateOrInsert(
            ['code' => '402101001'],
            [
                'name' => 'Sample Barangay',
                'city_code' => '402101000',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );
    }

    public function test_authorized_learner_receives_the_current_private_video(): void
    {
        Storage::fake('local');
        [, $version] = $this->publishedGuide(OnboardingGuide::ROLE_LEARNER);
        $learner = $this->learner();
        Storage::disk('local')->put($version->video_path, 'video-bytes');

        $this->actingAs($learner)
            ->get(route('onboarding-guides.video', $version))
            ->assertOk()
            ->assertHeader('Content-Type', 'video/mp4')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Cache-Control', 'max-age=0, no-store, private');
    }

    public function test_guest_cannot_access_a_private_video(): void
    {
        Storage::fake('local');
        [, $version] = $this->publishedGuide(OnboardingGuide::ROLE_LEARNER);

        $this->get(route('onboarding-guides.video', $version))
            ->assertRedirect(route('login'));
    }

    public function test_learner_cannot_access_an_instructor_video(): void
    {
        Storage::fake('local');
        [, $version] = $this->publishedGuide(OnboardingGuide::ROLE_INSTRUCTOR);
        $learner = $this->learner();
        Storage::disk('local')->put($version->video_path, 'video-bytes');

        $this->actingAs($learner)
            ->get(route('onboarding-guides.video', $version))
            ->assertForbidden();
    }

    public function test_unpublished_version_is_not_available_to_a_learner(): void
    {
        Storage::fake('local');
        [, $version] = $this->guideWithVersion(OnboardingGuide::STATUS_PUBLISHED, null);
        $learner = $this->learner();
        Storage::disk('local')->put($version->video_path, 'video-bytes');

        $this->actingAs($learner)
            ->get(route('onboarding-guides.video', $version))
            ->assertForbidden();
    }

    public function test_archived_guide_video_is_not_available_to_a_learner(): void
    {
        Storage::fake('local');
        [, $version] = $this->guideWithVersion(OnboardingGuide::STATUS_ARCHIVED, now());
        $learner = $this->learner();
        Storage::disk('local')->put($version->video_path, 'video-bytes');

        $this->actingAs($learner)
            ->get(route('onboarding-guides.video', $version))
            ->assertForbidden();
    }

    public function test_historical_version_is_not_available_to_a_learner(): void
    {
        Storage::fake('local');
        $guide = OnboardingGuide::factory()->create([
            'created_by' => null,
            'target_role' => OnboardingGuide::ROLE_LEARNER,
            'status' => OnboardingGuide::STATUS_PUBLISHED,
            'current_version_id' => null,
        ]);
        $historical = $this->version($guide, 1, now()->subMinute());
        $current = $this->version($guide, 2, now());
        $guide->update(['current_version_id' => $current->id]);
        $learner = $this->learner();
        Storage::disk('local')->put($historical->video_path, 'video-bytes');

        $this->actingAs($learner)
            ->get(route('onboarding-guides.video', $historical))
            ->assertForbidden();
    }

    public function test_missing_video_file_returns_not_found(): void
    {
        Storage::fake('local');
        [, $version] = $this->publishedGuide(OnboardingGuide::ROLE_LEARNER);

        $this->actingAs($this->learner())
            ->get(route('onboarding-guides.video', $version))
            ->assertNotFound();
    }

    public function test_non_local_or_unsupported_video_is_not_served(): void
    {
        Storage::fake('local');
        [, $version] = $this->publishedGuide(OnboardingGuide::ROLE_LEARNER);
        $learner = $this->learner();

        $version->update(['video_disk' => 'public']);
        $this->actingAs($learner)
            ->get(route('onboarding-guides.video', $version))
            ->assertNotFound();

        $version->update(['video_disk' => 'local', 'video_mime_type' => 'text/plain']);
        $this->actingAs($learner)
            ->get(route('onboarding-guides.video', $version))
            ->assertNotFound();
    }

    public function test_admin_can_preview_draft_and_historical_versions(): void
    {
        Storage::fake('local');
        $admin = $this->admin();
        [, $draft] = $this->guideWithVersion(OnboardingGuide::STATUS_DRAFT, null);
        Storage::disk('local')->put($draft->video_path, 'draft-video');

        $this->actingAs($admin)
            ->get(route('onboarding-guides.video', $draft))
            ->assertOk();

        $guide = OnboardingGuide::factory()->create([
            'created_by' => null,
            'target_role' => OnboardingGuide::ROLE_LEARNER,
            'status' => OnboardingGuide::STATUS_PUBLISHED,
            'current_version_id' => null,
        ]);
        $historical = $this->version($guide, 1, now()->subMinute());
        $current = $this->version($guide, 2, now());
        $guide->update(['current_version_id' => $current->id]);
        Storage::disk('local')->put($historical->video_path, 'historical-video');

        $this->actingAs($admin)
            ->get(route('onboarding-guides.video', $historical))
            ->assertOk();
    }

    public function test_real_local_video_supports_byte_ranges(): void
    {
        [, $version] = $this->publishedGuide(OnboardingGuide::ROLE_LEARNER);
        $learner = $this->learner();
        $path = 'onboarding-guides/testing/range-'.bin2hex(random_bytes(8)).'.mp4';
        Storage::disk('local')->put($path, '0123456789');
        $version->update(['video_path' => $path]);

        try {
            $this->actingAs($learner)
                ->withHeader('Range', 'bytes=0-3')
                ->get(route('onboarding-guides.video', $version))
                ->assertStatus(206)
                ->assertHeader('Content-Range', 'bytes 0-3/10');
        } finally {
            Storage::disk('local')->delete($path);
        }
    }

    private function publishedGuide(string $role): array
    {
        return $this->guideWithVersion(OnboardingGuide::STATUS_PUBLISHED, now(), $role);
    }

    private function guideWithVersion(string $status, mixed $publishedAt, string $role = OnboardingGuide::ROLE_LEARNER): array
    {
        $guide = OnboardingGuide::factory()->create([
            'created_by' => null,
            'target_role' => $role,
            'status' => $status,
            'current_version_id' => null,
        ]);
        $version = $this->version($guide, 1, $publishedAt);
        $guide->update(['current_version_id' => $version->id]);

        return [$guide->fresh(['currentVersion']), $version->fresh(['guide'])];
    }

    private function version(OnboardingGuide $guide, int $number, mixed $publishedAt): OnboardingGuideVersion
    {
        return OnboardingGuideVersion::factory()
            ->for($guide, 'guide')
            ->create([
                'created_by' => null,
                'version_number' => $number,
                'published_at' => $publishedAt,
            ]);
    }

    private function learner(): User
    {
        $user = User::factory()->create([
            'role' => 'learner',
            'status' => User::STATUS_ACTIVE,
            'birthdate' => '2000-01-01',
        ]);
        $user->assignRole('learner');

        LearnerProfile::unguarded(fn (): LearnerProfile => $user->learnerProfile()->create([
            'username' => 'learner_'.$user->id,
            'birthdate' => '2000-01-01',
            'age_range' => 'adult_18_plus',
            'gender' => 'prefer_not_to_say',
            'province_code' => '402100000',
            'city_code' => '402101000',
            'barangay_code' => '402101001',
            'barangay' => 'Sample Barangay',
            'is_parent_account' => false,
            'requires_parental_consent' => false,
        ]));

        return $user->fresh();
    }

    private function admin(): User
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => User::STATUS_ACTIVE,
        ]);
        $admin->assignRole('admin');

        return $admin->fresh();
    }
}
