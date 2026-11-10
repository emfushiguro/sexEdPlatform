<?php

declare(strict_types=1);

namespace Tests\Feature\Onboarding;

use App\Models\LearnerProfile;
use App\Models\OnboardingGuide;
use App\Models\OnboardingGuideVersion;
use App\Models\User;
use App\Models\UserOnboardingGuideState;
use App\Models\UserSuspension;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class OnboardingGuideStateTest extends TestCase
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

    public function test_state_routes_are_post_only_and_have_no_user_parameter(): void
    {
        foreach (['onboarding-guides.dismiss', 'onboarding-guides.complete'] as $name) {
            $route = app('router')->getRoutes()->getByName($name);

            $this->assertNotNull($route, $name.' must be registered.');
            if ($route === null) {
                continue;
            }

            $this->assertSame(['POST'], $route->methods());
            $this->assertStringNotContainsString('user', $route->uri());
        }
    }

    public function test_learner_can_dismiss_the_current_guide_once_and_keep_the_first_timestamp(): void
    {
        [, $version] = $this->publishedGuide();
        $learner = $this->learner();
        $otherUser = User::factory()->create();
        $firstTime = now()->subMinute();

        $this->travelTo($firstTime);
        $this->actingAs($learner)
            ->postJson(route('onboarding-guides.dismiss', $version), [
                'user_id' => $otherUser->id,
            ])
            ->assertOk()
            ->assertJsonPath('completed_at', null)
            ->assertJsonPath('dismissed_at', $firstTime->toIso8601String());

        $this->travelTo($firstTime->copy()->addMinute());
        $this->actingAs($learner)
            ->postJson(route('onboarding-guides.dismiss', $version))
            ->assertOk()
            ->assertJsonPath('dismissed_at', $firstTime->toIso8601String());
        $this->travelBack();

        $this->assertDatabaseCount('user_onboarding_guide_states', 1);
        $this->assertDatabaseHas('user_onboarding_guide_states', [
            'user_id' => $learner->id,
            'onboarding_guide_version_id' => $version->id,
            'completed_at' => null,
        ]);
        $this->assertDatabaseMissing('user_onboarding_guide_states', [
            'user_id' => $otherUser->id,
            'onboarding_guide_version_id' => $version->id,
        ]);
    }

    public function test_completion_preserves_dismissal_and_is_idempotent(): void
    {
        [, $version] = $this->publishedGuide();
        $learner = $this->learner();
        $dismissedAt = now()->subMinutes(2);
        $completedAt = $dismissedAt->copy()->addMinute();

        $this->travelTo($dismissedAt);
        $this->actingAs($learner)
            ->postJson(route('onboarding-guides.dismiss', $version))
            ->assertOk();

        $this->travelTo($completedAt);
        $this->actingAs($learner)
            ->postJson(route('onboarding-guides.complete', $version))
            ->assertOk()
            ->assertJsonPath('dismissed_at', $dismissedAt->toIso8601String())
            ->assertJsonPath('completed_at', $completedAt->toIso8601String());

        $this->travelTo($completedAt->copy()->addMinute());
        $this->actingAs($learner)
            ->postJson(route('onboarding-guides.complete', $version))
            ->assertOk()
            ->assertJsonPath('dismissed_at', $dismissedAt->toIso8601String())
            ->assertJsonPath('completed_at', $completedAt->toIso8601String());
        $this->travelBack();

        $this->assertDatabaseCount('user_onboarding_guide_states', 1);
        $state = UserOnboardingGuideState::query()->firstOrFail();
        $this->assertSame($dismissedAt->toIso8601String(), $state->dismissed_at->toIso8601String());
        $this->assertSame($completedAt->toIso8601String(), $state->completed_at->toIso8601String());
    }

    public function test_dismissal_after_direct_completion_is_a_no_op(): void
    {
        [, $version] = $this->publishedGuide();
        $learner = $this->learner();
        $completedAt = now()->subMinute();

        $this->travelTo($completedAt);
        $this->actingAs($learner)
            ->postJson(route('onboarding-guides.complete', $version))
            ->assertOk();

        $this->travelTo($completedAt->copy()->addMinute());
        $this->actingAs($learner)
            ->postJson(route('onboarding-guides.dismiss', $version))
            ->assertOk()
            ->assertJsonPath('dismissed_at', null)
            ->assertJsonPath('completed_at', $completedAt->toIso8601String());
        $this->travelBack();

        $this->assertDatabaseCount('user_onboarding_guide_states', 1);
        $this->assertDatabaseHas('user_onboarding_guide_states', [
            'user_id' => $learner->id,
            'onboarding_guide_version_id' => $version->id,
            'dismissed_at' => null,
        ]);
    }

    public function test_normal_form_submission_redirects_while_json_requests_return_state(): void
    {
        [, $version] = $this->publishedGuide();
        $learner = $this->learner();

        $this->actingAs($learner)
            ->post(route('onboarding-guides.complete', $version))
            ->assertRedirect();

        $this->actingAs($learner)
            ->postJson(route('onboarding-guides.complete', $version))
            ->assertOk()
            ->assertJsonStructure(['dismissed_at', 'completed_at']);
    }

    public function test_wrong_role_unpublished_archived_historical_and_stale_versions_are_forbidden(): void
    {
        $learner = $this->learner();

        [, $instructorVersion] = $this->publishedGuide(OnboardingGuide::ROLE_INSTRUCTOR);
        $this->actingAs($learner)
            ->postJson(route('onboarding-guides.dismiss', $instructorVersion))
            ->assertForbidden();

        [, $unpublishedVersion] = $this->guideWithVersion(OnboardingGuide::STATUS_DRAFT, null);
        $this->actingAs($learner)
            ->postJson(route('onboarding-guides.dismiss', $unpublishedVersion))
            ->assertForbidden();

        [, $archivedVersion] = $this->guideWithVersion(OnboardingGuide::STATUS_ARCHIVED, now());
        $this->actingAs($learner)
            ->postJson(route('onboarding-guides.dismiss', $archivedVersion))
            ->assertForbidden();

        [$guide, $historicalVersion] = $this->publishedGuide();
        $currentVersion = $this->version($guide, 2, now());
        $guide->update(['current_version_id' => $currentVersion->id]);

        $this->actingAs($learner)
            ->postJson(route('onboarding-guides.dismiss', $historicalVersion))
            ->assertForbidden();
        $this->actingAs($learner)
            ->postJson(route('onboarding-guides.complete', $historicalVersion))
            ->assertForbidden();
    }

    public function test_active_suspension_gets_the_existing_json_response_before_state_mutation(): void
    {
        [, $version] = $this->publishedGuide();
        $learner = $this->learner();

        UserSuspension::query()->create([
            'user_id' => $learner->id,
            'status' => 'active',
            'starts_at' => now()->subMinute(),
            'ends_at' => null,
        ]);

        $this->actingAs($learner)
            ->postJson(route('onboarding-guides.dismiss', $version))
            ->assertStatus(423)
            ->assertJsonPath('message', 'Account access is restricted due to an active suspension.');

        $this->assertDatabaseCount('user_onboarding_guide_states', 0);
    }

    private function publishedGuide(string $role = OnboardingGuide::ROLE_LEARNER): array
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
}
