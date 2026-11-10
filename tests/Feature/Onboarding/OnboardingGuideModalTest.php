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

final class OnboardingGuideModalTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        DB::table('provinces')->updateOrInsert(
            ['code' => '402100000'],
            ['name' => 'Sample Province', 'region_code' => '040000000', 'created_at' => now(), 'updated_at' => now()],
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
            ['name' => 'Sample Barangay', 'city_code' => '402101000', 'created_at' => now(), 'updated_at' => now()],
        );
    }

    public function test_learner_dashboard_renders_the_current_incomplete_guide_modal(): void
    {
        $learner = $this->learner();
        $version = $this->publishedGuide(OnboardingGuide::ROLE_LEARNER, 'Learner navigation tour');

        $this->actingAs($learner)
            ->get(route('learner.dashboard'))
            ->assertOk()
            ->assertSee('Welcome to Conscious Connections')
            ->assertSee($version->title)
            ->assertSee(route('onboarding-guides.video', $version), false)
            ->assertSee('Start Watching')
            ->assertSee('Skip')
            ->assertSee('Mark guide complete');
    }

    public function test_dismissed_and_completed_guides_do_not_render_the_dashboard_modal(): void
    {
        $dismissedLearner = $this->learner();
        $dismissedVersion = $this->publishedGuide(OnboardingGuide::ROLE_LEARNER, 'Dismissed tour');
        UserOnboardingGuideState::factory()->create([
            'user_id' => $dismissedLearner->id,
            'onboarding_guide_version_id' => $dismissedVersion->id,
            'dismissed_at' => now(),
            'completed_at' => null,
        ]);

        $this->actingAs($dismissedLearner)
            ->get(route('learner.dashboard'))
            ->assertOk()
            ->assertDontSee('Welcome to Conscious Connections')
            ->assertDontSee('Dismissed tour');

        $completedLearner = $this->learner();
        $completedVersion = $this->publishedGuide(OnboardingGuide::ROLE_LEARNER, 'Completed tour');
        UserOnboardingGuideState::factory()->create([
            'user_id' => $completedLearner->id,
            'onboarding_guide_version_id' => $completedVersion->id,
            'dismissed_at' => null,
            'completed_at' => now(),
        ]);

        $this->actingAs($completedLearner)
            ->get(route('learner.dashboard'))
            ->assertOk()
            ->assertDontSee('Welcome to Conscious Connections')
            ->assertDontSee('Completed tour');
    }

    public function test_instructor_dashboard_uses_the_instructor_guide_context(): void
    {
        $instructor = User::factory()->create([
            'role' => 'instructor',
            'status' => User::STATUS_ACTIVE,
        ]);
        $instructor->assignRole('instructor');
        $version = $this->publishedGuide(OnboardingGuide::ROLE_INSTRUCTOR, 'Instructor navigation tour');

        $this->actingAs($instructor->fresh())
            ->get(route('instructor.dashboard'))
            ->assertOk()
            ->assertSee('Instructor navigation tour')
            ->assertSee(route('onboarding-guides.video', $version), false)
            ->assertSee('Mark guide complete');
    }

    public function test_no_current_guide_renders_no_modal(): void
    {
        $this->actingAs($this->learner())
            ->get(route('learner.dashboard'))
            ->assertOk()
            ->assertDontSee('Welcome to Conscious Connections');
    }

    private function publishedGuide(string $role, string $title): OnboardingGuideVersion
    {
        $guide = OnboardingGuide::factory()->create([
            'created_by' => null,
            'target_role' => $role,
            'status' => OnboardingGuide::STATUS_PUBLISHED,
            'current_version_id' => null,
        ]);
        $version = OnboardingGuideVersion::factory()->for($guide, 'guide')->create([
            'created_by' => null,
            'title' => $title,
            'published_at' => now(),
            'requires_recompletion' => true,
        ]);
        $guide->update(['current_version_id' => $version->id]);

        return $version->fresh(['guide']);
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
}
