<?php

declare(strict_types=1);

namespace Tests\Feature\Onboarding;

use App\Models\LearnerIdentityVerification;
use App\Models\LearnerProfile;
use App\Models\OnboardingGuide;
use App\Models\OnboardingGuideVersion;
use App\Models\ParentChildAccount;
use App\Models\RoleTransition;
use App\Models\User;
use App\Models\UserOnboardingGuideState;
use App\Models\UserSuspension;
use App\Services\Onboarding\OnboardingGuideAudienceService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class OnboardingGuideAudienceTest extends TestCase
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

    public function test_current_guides_are_presented_to_current_and_future_eligible_users(): void
    {
        [$learnerGuide, $learnerVersion] = $this->publishedGuide(OnboardingGuide::ROLE_LEARNER);
        [$instructorGuide, $instructorVersion] = $this->publishedGuide(OnboardingGuide::ROLE_INSTRUCTOR);
        $learner = $this->learner();
        $instructor = $this->instructor();
        $service = app(OnboardingGuideAudienceService::class);

        $presentation = $service->presentationFor($learner, OnboardingGuide::ROLE_LEARNER);

        $this->assertNotNull($presentation);
        $this->assertTrue($presentation['version']->is($learnerVersion));
        $this->assertSame('incomplete', $presentation['status']['status']);
        $this->assertNull($service->currentFor($learner, OnboardingGuide::ROLE_INSTRUCTOR));
        $this->assertTrue($service->currentFor($instructor, OnboardingGuide::ROLE_INSTRUCTOR)?->is($instructorVersion));

        $futureLearner = $this->learner();

        $this->assertTrue($service->currentFor($futureLearner, OnboardingGuide::ROLE_LEARNER)?->is($learnerVersion));
        $this->assertSame(OnboardingGuide::ROLE_LEARNER, $learnerGuide->target_role);
        $this->assertSame(OnboardingGuide::ROLE_INSTRUCTOR, $instructorGuide->target_role);
    }

    public function test_current_for_ignores_missing_nonpublished_historical_and_wrong_role_versions(): void
    {
        $learner = $this->learner();
        $service = app(OnboardingGuideAudienceService::class);

        $this->assertNull($service->currentFor($learner, OnboardingGuide::ROLE_LEARNER));

        $this->guideWithVersion(OnboardingGuide::STATUS_DRAFT, OnboardingGuide::ROLE_LEARNER);
        $this->guideWithVersion(OnboardingGuide::STATUS_PUBLISHED, OnboardingGuide::ROLE_LEARNER, false);
        $this->guideWithVersion(OnboardingGuide::STATUS_ARCHIVED, OnboardingGuide::ROLE_LEARNER);
        $this->guideWithVersion(OnboardingGuide::STATUS_PUBLISHED, OnboardingGuide::ROLE_INSTRUCTOR);

        $this->assertNull($service->currentFor($learner, OnboardingGuide::ROLE_LEARNER));

        [$guide, $first] = $this->publishedGuide(OnboardingGuide::ROLE_LEARNER);
        $second = $this->version($guide, 2, false);
        $guide->update(['current_version_id' => $second->id]);

        $current = $service->currentFor($learner, OnboardingGuide::ROLE_LEARNER);

        $this->assertNotNull($current);
        $this->assertTrue($current->is($second));
        $this->assertFalse($current->is($first));
    }

    public function test_role_context_defaults_to_instructor_but_allows_an_explicit_learner_switch(): void
    {
        [, $learnerVersion] = $this->publishedGuide(OnboardingGuide::ROLE_LEARNER);
        [, $instructorVersion] = $this->publishedGuide(OnboardingGuide::ROLE_INSTRUCTOR);
        $learner = $this->learner();
        $instructor = $this->instructor();
        $service = app(OnboardingGuideAudienceService::class);

        $this->assertNull($service->currentFor($learner, OnboardingGuide::ROLE_INSTRUCTOR));
        $this->assertTrue($service->currentFor($instructor, OnboardingGuide::ROLE_INSTRUCTOR)?->is($instructorVersion));
        $this->assertNull($service->currentFor($instructor, OnboardingGuide::ROLE_LEARNER));
        $this->assertFalse($service->canAccessRole($learner, 'unsupported'));

        $this->profile($instructor);
        RoleTransition::query()->create([
            'user_id' => $instructor->id,
            'from_role' => 'learner',
            'to_role' => 'instructor',
            'transitioned_at' => now(),
        ]);

        $this->assertTrue($service->currentFor($instructor, OnboardingGuide::ROLE_LEARNER)?->is($learnerVersion));
    }

    public function test_learner_access_matches_active_profile_identity_guardian_and_child_gates(): void
    {
        $service = app(OnboardingGuideAudienceService::class);

        $legacy = $this->learner();
        $this->assertTrue($service->canAccessRole($legacy, OnboardingGuide::ROLE_LEARNER));

        $incompleteProfile = $this->learner([], ['barangay_code' => null]);
        $this->assertFalse($service->canAccessRole($incompleteProfile, OnboardingGuide::ROLE_LEARNER));

        foreach (['pending', 'rejected'] as $status) {
            $user = $this->learner();
            $this->identityCase($user, $status);

            $this->assertFalse($service->canAccessRole($user, OnboardingGuide::ROLE_LEARNER));
        }

        $approved = $this->learner();
        $this->identityCase($approved, 'approved');
        $this->assertTrue($service->canAccessRole($approved, OnboardingGuide::ROLE_LEARNER));

        $sandboxReview = $this->learner();
        $this->identityCase($sandboxReview, 'approved', true);
        $this->assertFalse($service->canAccessRole($sandboxReview, OnboardingGuide::ROLE_LEARNER));

        $guardian = $this->learner([
            'is_parent_registration' => true,
            'parent_verification_status' => 'approved',
            'guardian_onboarding_status' => 'not_started',
        ]);
        $this->assertFalse($service->canAccessRole($guardian, OnboardingGuide::ROLE_LEARNER));
        $guardian->update(['guardian_onboarding_status' => 'completed']);
        $this->assertTrue($service->canAccessRole($guardian->fresh(), OnboardingGuide::ROLE_LEARNER));

        $pendingGuardian = $this->learner([
            'is_parent_registration' => true,
            'parent_verification_status' => 'pending',
            'guardian_onboarding_status' => 'completed',
        ]);
        $this->assertFalse($service->canAccessRole($pendingGuardian, OnboardingGuide::ROLE_LEARNER));

        $child = $this->learner();
        $parent = $this->learner();
        ParentChildAccount::query()->create([
            'parent_user_id' => $parent->id,
            'child_user_id' => $child->id,
            'relationship_type' => 'mother',
            'relationship_status' => ParentChildAccount::STATUS_ACTIVE,
            'relationship_verified_status' => ParentChildAccount::VERIFICATION_VERIFIED,
            'relationship_verified_at' => now(),
            'verification_status' => ParentChildAccount::VERIFICATION_PENDING,
            'verification_document_path' => 'private/child-id.png',
        ]);
        $this->assertFalse($service->canAccessRole($child, OnboardingGuide::ROLE_LEARNER));

        $child->parentLinks()->firstOrFail()->update([
            'verification_status' => 'approved',
        ]);
        $this->assertTrue($service->canAccessRole($child->fresh(), OnboardingGuide::ROLE_LEARNER));

        $inactive = $this->learner(['status' => User::STATUS_INACTIVE]);
        $this->assertFalse($service->canAccessRole($inactive, OnboardingGuide::ROLE_LEARNER));

        $suspendedStatus = $this->learner(['status' => User::STATUS_SUSPENDED]);
        $this->assertFalse($service->canAccessRole($suspendedStatus, OnboardingGuide::ROLE_LEARNER));

        $activeSuspension = $this->learner();
        UserSuspension::query()->create([
            'user_id' => $activeSuspension->id,
            'status' => 'active',
            'starts_at' => now()->subMinute(),
            'ends_at' => null,
        ]);
        $this->assertFalse($service->canAccessRole($activeSuspension, OnboardingGuide::ROLE_LEARNER));
    }

    public function test_assert_can_use_version_requires_the_current_published_role_version(): void
    {
        [$guide, $first] = $this->publishedGuide(OnboardingGuide::ROLE_LEARNER);
        $second = $this->version($guide, 2, true);
        $guide->update(['current_version_id' => $second->id]);
        $learner = $this->learner();
        $service = app(OnboardingGuideAudienceService::class);

        $service->assertCanUseVersion($learner, $second);

        $this->expectException(AuthorizationException::class);
        $service->assertCanUseVersion($learner, $first);
    }

    public function test_status_uses_informational_boundaries_and_completion_precedence(): void
    {
        [$guide, $first] = $this->publishedGuide(OnboardingGuide::ROLE_LEARNER);
        $second = $this->version($guide, 2, false);
        $guide->update(['current_version_id' => $second->id]);
        $dismissedLearner = $this->learner();
        $dismissedAt = now()->subMinutes(2);
        UserOnboardingGuideState::factory()->create([
            'user_id' => $dismissedLearner->id,
            'onboarding_guide_version_id' => $first->id,
            'dismissed_at' => $dismissedAt,
            'completed_at' => null,
        ]);
        $completedLearner = $this->learner();
        $completedAt = now()->subMinute();
        UserOnboardingGuideState::factory()->create([
            'user_id' => $completedLearner->id,
            'onboarding_guide_version_id' => $first->id,
            'dismissed_at' => null,
            'completed_at' => $completedAt,
        ]);
        UserOnboardingGuideState::factory()->create([
            'user_id' => $completedLearner->id,
            'onboarding_guide_version_id' => $second->id,
            'dismissed_at' => now(),
            'completed_at' => null,
        ]);
        $service = app(OnboardingGuideAudienceService::class);

        $dismissedStatus = $service->statusFor($dismissedLearner, $second);
        $completedStatus = $service->statusFor($completedLearner, $second);

        $this->assertSame('dismissed', $dismissedStatus['status']);
        $this->assertSame($first->id, $dismissedStatus['source_version_id']);
        $this->assertSame($first->id, $dismissedStatus['boundary_version_id']);
        $this->assertEquals($dismissedAt->toDateTimeString(), $dismissedStatus['dismissed_at']->toDateTimeString());
        $this->assertNull($service->presentationFor($dismissedLearner, OnboardingGuide::ROLE_LEARNER));
        $this->assertSame('completed', $completedStatus['status']);
        $this->assertSame($first->id, $completedStatus['source_version_id']);
        $this->assertEquals($completedAt->toDateTimeString(), $completedStatus['completed_at']->toDateTimeString());
        $this->assertNull($service->presentationFor($completedLearner, OnboardingGuide::ROLE_LEARNER));
    }

    public function test_required_revisions_start_a_new_boundary_without_copying_state_rows(): void
    {
        [$guide, $first] = $this->publishedGuide(OnboardingGuide::ROLE_LEARNER);
        $informational = $this->version($guide, 2, false);
        $learner = $this->learner();
        UserOnboardingGuideState::factory()->create([
            'user_id' => $learner->id,
            'onboarding_guide_version_id' => $first->id,
            'completed_at' => now()->subHour(),
        ]);
        $required = $this->version($guide, 3, true);
        $guide->update(['current_version_id' => $required->id]);
        $service = app(OnboardingGuideAudienceService::class);

        $this->assertSame(1, $learner->onboardingGuideStates()->count());
        $status = $service->statusFor($learner, $required);

        $this->assertSame('incomplete', $status['status']);
        $this->assertNull($status['source_version_id']);
        $this->assertSame($required->id, $status['boundary_version_id']);
        $presentation = $service->presentationFor($learner, OnboardingGuide::ROLE_LEARNER);
        $this->assertNotNull($presentation);
        $this->assertTrue($presentation['version']->is($required));
        $this->assertNotSame($informational->id, $required->id);
    }

    public function test_analytics_counts_effective_states_and_excludes_ineligible_users(): void
    {
        [$guide, $first] = $this->publishedGuide(OnboardingGuide::ROLE_LEARNER);
        $current = $this->version($guide, 2, false);
        $guide->update(['current_version_id' => $current->id]);
        $completed = $this->learner();
        $dismissed = $this->learner();
        $both = $this->learner();
        $untouched = $this->learner();
        $ineligible = $this->learner([], ['city_code' => null]);
        $inactive = $this->learner(['status' => User::STATUS_INACTIVE]);
        $completedAt = now()->subMinutes(3);

        UserOnboardingGuideState::factory()->create([
            'user_id' => $completed->id,
            'onboarding_guide_version_id' => $first->id,
            'completed_at' => $completedAt,
        ]);
        UserOnboardingGuideState::factory()->create([
            'user_id' => $dismissed->id,
            'onboarding_guide_version_id' => $current->id,
            'dismissed_at' => now()->subMinutes(2),
        ]);
        UserOnboardingGuideState::factory()->create([
            'user_id' => $both->id,
            'onboarding_guide_version_id' => $first->id,
            'completed_at' => now()->subMinutes(4),
        ]);
        UserOnboardingGuideState::factory()->create([
            'user_id' => $both->id,
            'onboarding_guide_version_id' => $current->id,
            'dismissed_at' => now()->subMinute(),
        ]);
        $service = app(OnboardingGuideAudienceService::class);

        $analytics = $service->analyticsFor($guide->fresh(['currentVersion']));

        $this->assertSame(4, $analytics['eligible']);
        $this->assertSame(2, $analytics['completed']);
        $this->assertSame(1, $analytics['dismissed']);
        $this->assertSame(2, $analytics['incomplete']);
        $this->assertSame(50.0, $analytics['completion_percentage']);
        $this->assertSame(0, $untouched->onboardingGuideStates()->count());
        $this->assertSame(0, $ineligible->onboardingGuideStates()->count());
        $this->assertSame(0, $inactive->onboardingGuideStates()->count());
    }

    public function test_analytics_returns_zero_percentage_when_no_role_users_are_eligible(): void
    {
        [$guide] = $this->publishedGuide(OnboardingGuide::ROLE_INSTRUCTOR);

        $analytics = app(OnboardingGuideAudienceService::class)->analyticsFor($guide->fresh(['currentVersion']));

        $this->assertSame([
            'eligible' => 0,
            'completed' => 0,
            'dismissed' => 0,
            'incomplete' => 0,
            'completion_percentage' => 0.0,
        ], $analytics);
    }

    private function publishedGuide(string $role): array
    {
        return $this->guideWithVersion(OnboardingGuide::STATUS_PUBLISHED, $role, true, true);
    }

    private function guideWithVersion(
        string $status,
        string $role,
        bool $published = true,
        bool $requiresRecompletion = true,
    ): array {
        $guide = OnboardingGuide::factory()->create([
            'created_by' => null,
            'target_role' => $role,
            'status' => $status,
            'current_version_id' => null,
        ]);
        $version = $this->version($guide, 1, $requiresRecompletion, $published);
        $guide->update(['current_version_id' => $version->id]);

        return [$guide->fresh(['currentVersion']), $version->fresh(['guide'])];
    }

    private function version(
        OnboardingGuide $guide,
        int $number,
        bool $requiresRecompletion,
        bool $published = true,
    ): OnboardingGuideVersion {
        return OnboardingGuideVersion::factory()
            ->for($guide, 'guide')
            ->create([
                'created_by' => null,
                'version_number' => $number,
                'requires_recompletion' => $requiresRecompletion,
                'published_at' => $published ? now() : null,
            ]);
    }

    private function learner(array $userOverrides = [], ?array $profileOverrides = []): User
    {
        $user = User::factory()->create(array_merge([
            'role' => 'learner',
            'status' => User::STATUS_ACTIVE,
            'birthdate' => '2000-01-01',
        ], $userOverrides));
        $user->assignRole('learner');

        if ($profileOverrides !== null) {
            $this->profile($user, $profileOverrides);
        }

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

    private function profile(User $user, array $overrides = []): LearnerProfile
    {
        return LearnerProfile::unguarded(fn (): LearnerProfile => $user->learnerProfile()->create(array_merge([
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
        ], $overrides)));
    }

    private function identityCase(User $user, string $status, bool $sandboxManualReview = false): LearnerIdentityVerification
    {
        $case = LearnerIdentityVerification::query()->create([
            'user_id' => $user->id,
            'pathway' => 'adult',
            'status' => $status,
            'submission_round' => 1,
        ]);
        $case->forceFill(['sandbox_manual_review' => $sandboxManualReview])->save();

        return $case->fresh();
    }
}
