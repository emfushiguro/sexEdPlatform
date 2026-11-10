<?php

declare(strict_types=1);

namespace Tests\Feature\Onboarding;

use App\Models\LearnerProfile;
use App\Models\OnboardingGuide;
use App\Models\OnboardingGuideVersion;
use App\Models\User;
use App\Models\UserOnboardingGuideState;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class OnboardingGuidePageTest extends TestCase
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

    public function test_learner_page_uses_only_the_learner_guide_and_marks_navigation_active(): void
    {
        [, $learnerVersion] = $this->publishedGuide('Learner navigation guide', OnboardingGuide::ROLE_LEARNER);
        [, $instructorVersion] = $this->publishedGuide('Instructor navigation guide', OnboardingGuide::ROLE_INSTRUCTOR);

        $response = $this->actingAs($this->learner())
            ->get(route('learner.guide'));

        $response
            ->assertOk()
            ->assertSee($learnerVersion->title)
            ->assertSee(route('onboarding-guides.video', $learnerVersion), false)
            ->assertSee('Mark guide complete')
            ->assertSee('href="'.route('learner.guide').'"', false)
            ->assertSee('aria-current="page"', false)
            ->assertDontSee($instructorVersion->title);
    }

    public function test_instructor_page_uses_the_instructor_guide_and_marks_navigation_active(): void
    {
        [, $learnerVersion] = $this->publishedGuide('Learner navigation guide', OnboardingGuide::ROLE_LEARNER);
        [, $instructorVersion] = $this->publishedGuide('Instructor navigation guide', OnboardingGuide::ROLE_INSTRUCTOR);

        $response = $this->actingAs($this->instructor())
            ->get(route('instructor.guide'));

        $response
            ->assertOk()
            ->assertSee($instructorVersion->title)
            ->assertSee(route('onboarding-guides.video', $instructorVersion), false)
            ->assertSee('Mark guide complete')
            ->assertSee('href="'.route('instructor.guide').'"', false)
            ->assertSee('aria-current="page"', false)
            ->assertDontSee($learnerVersion->title);
    }

    public function test_inherited_completion_date_is_shown_for_the_current_version(): void
    {
        $guide = OnboardingGuide::factory()->create([
            'created_by' => null,
            'target_role' => OnboardingGuide::ROLE_LEARNER,
            'status' => OnboardingGuide::STATUS_PUBLISHED,
            'current_version_id' => null,
        ]);
        $first = $this->version($guide, 1, 'First guide', now(), true);
        $current = $this->version($guide, 2, 'Updated guide', now(), false);
        $guide->update(['current_version_id' => $current->id]);
        $learner = $this->learner();
        $completedAt = now()->subDay();

        UserOnboardingGuideState::factory()->create([
            'user_id' => $learner->id,
            'onboarding_guide_version_id' => $first->id,
            'completed_at' => $completedAt,
            'dismissed_at' => null,
        ]);

        $this->actingAs($learner)
            ->get(route('learner.guide'))
            ->assertOk()
            ->assertSee($current->title)
            ->assertSee('Completed on '.$completedAt->format('F j, Y'))
            ->assertDontSee('Mark guide complete');
    }

    public function test_dismissed_but_incomplete_guide_still_offers_completion(): void
    {
        [, $version] = $this->publishedGuide('Dismissed guide', OnboardingGuide::ROLE_LEARNER);
        $learner = $this->learner();

        UserOnboardingGuideState::factory()->create([
            'user_id' => $learner->id,
            'onboarding_guide_version_id' => $version->id,
            'dismissed_at' => now()->subMinute(),
            'completed_at' => null,
        ]);

        $this->actingAs($learner)
            ->get(route('learner.guide'))
            ->assertOk()
            ->assertSee('Dismissed')
            ->assertSee('Mark guide complete');
    }

    public function test_completed_replay_does_not_create_a_duplicate_state(): void
    {
        [, $version] = $this->publishedGuide('Completed guide', OnboardingGuide::ROLE_LEARNER);
        $learner = $this->learner();
        UserOnboardingGuideState::factory()->create([
            'user_id' => $learner->id,
            'onboarding_guide_version_id' => $version->id,
            'completed_at' => now()->subMinute(),
            'dismissed_at' => null,
        ]);

        $this->actingAs($learner)
            ->get(route('learner.guide'))
            ->assertOk()
            ->assertSee('Completed')
            ->assertDontSee('Mark guide complete');

        $this->assertDatabaseCount('user_onboarding_guide_states', 1);
    }

    public function test_empty_page_excludes_unpublished_and_archived_guides(): void
    {
        $this->publishedGuide('Draft guide', OnboardingGuide::ROLE_LEARNER, OnboardingGuide::STATUS_DRAFT, null);
        $this->publishedGuide('Archived guide', OnboardingGuide::ROLE_LEARNER, OnboardingGuide::STATUS_ARCHIVED, now());

        $this->actingAs($this->learner())
            ->get(route('learner.guide'))
            ->assertOk()
            ->assertSee('No onboarding guide is available right now.')
            ->assertDontSee('Draft guide')
            ->assertDontSee('Archived guide');
    }

    public function test_learner_cannot_use_the_instructor_video_from_the_page_contract(): void
    {
        [, $version] = $this->publishedGuide('Instructor guide', OnboardingGuide::ROLE_INSTRUCTOR);

        $this->actingAs($this->learner())
            ->get(route('onboarding-guides.video', $version))
            ->assertForbidden();
    }

    private function publishedGuide(
        string $title,
        string $role,
        string $status = OnboardingGuide::STATUS_PUBLISHED,
        mixed $publishedAt = null,
    ): array {
        $guide = OnboardingGuide::factory()->create([
            'created_by' => null,
            'target_role' => $role,
            'status' => $status,
            'current_version_id' => null,
        ]);
        $version = $this->version($guide, 1, $title, $publishedAt ?? now());
        $guide->update(['current_version_id' => $version->id]);

        return [$guide->fresh(['currentVersion']), $version->fresh(['guide'])];
    }

    private function version(
        OnboardingGuide $guide,
        int $number,
        string $title,
        mixed $publishedAt,
        bool $requiresRecompletion = true,
    ): OnboardingGuideVersion {
        return OnboardingGuideVersion::factory()
            ->for($guide, 'guide')
            ->create([
                'created_by' => null,
                'version_number' => $number,
                'title' => $title,
                'description' => $title.' description',
                'requires_recompletion' => $requiresRecompletion,
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

    private function instructor(): User
    {
        $user = User::factory()->create([
            'role' => 'instructor',
            'status' => User::STATUS_ACTIVE,
        ]);
        $user->assignRole('instructor');

        return $user->fresh();
    }
}
