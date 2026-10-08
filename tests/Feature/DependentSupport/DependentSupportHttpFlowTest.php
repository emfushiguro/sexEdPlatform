<?php

namespace Tests\Feature\DependentSupport;

use App\Models\DependentSupportProfile;
use App\Models\ParentChildAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DependentSupportHttpFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('provinces')->insertOrIgnore([
            'code' => '402100000',
            'name' => 'Test Province',
            'region_code' => '040000000',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('cities')->insertOrIgnore([
            'code' => '402101000',
            'name' => 'Test City',
            'region_code' => '040000000',
            'province_code' => '402100000',
            'is_city' => true,
            'city_class' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('barangays')->insertOrIgnore([
            'code' => '402101001',
            'name' => 'Test Barangay',
            'city_code' => '402101000',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_dependent_can_open_empty_editor_and_create_a_record(): void
    {
        $dependent = $this->learner();

        $response = $this->actingAs($dependent)
            ->get(route('learner.support-information.edit'))
            ->assertOk();
        $this->assertStringContainsString('private', (string) $response->headers->get('Cache-Control'));
        $this->assertStringContainsString('no-store', (string) $response->headers->get('Cache-Control'));
        $response->assertSee('Health &amp; Support Information', false)
            ->assertSee('optional');

        $this->actingAs($dependent)
            ->put(route('learner.support-information.save'), [
                'has_relevant_support_information' => '1',
                'relevant_health_considerations' => 'Quiet transitions help.',
                'purpose_acknowledged' => '1',
            ])
            ->assertRedirect(route('learner.support-information.edit'));

        $this->assertDatabaseHas('dependent_support_profiles', [
            'dependent_user_id' => $dependent->id,
        ]);
    }

    public function test_dependent_can_view_decrypted_content_and_html_is_escaped(): void
    {
        $dependent = $this->learner();
        $marker = 'SUPPORT_SCRIPT_MARKER';
        $profile = $this->profile($dependent, '<script>'.$marker.'</script>');

        $this->actingAs($dependent)
            ->get(route('learner.support-information.edit'))
            ->assertOk()
            ->assertSee('&lt;script&gt;'.$marker.'&lt;/script&gt;', false)
            ->assertDontSee('<script>'.$marker.'</script>', false)
            ->assertSee('Last updated');

        $this->assertNotSame('<script>'.$marker.'</script>', $profile->getRawOriginal('relevant_health_considerations'));
    }

    public function test_update_requires_current_version_and_stale_write_preserves_newer_content(): void
    {
        $dependent = $this->learner();
        $profile = $this->profile($dependent, 'Original support');
        $staleVersion = $profile->getRawOriginal('updated_at');

        $profile->update(['accessibility_support_needs' => 'Newer support']);

        $this->actingAs($dependent)
            ->put(route('learner.support-information.save'), [
                'has_relevant_support_information' => '1',
                'accessibility_support_needs' => 'Stale overwrite',
                'purpose_acknowledged' => '1',
                'expected_updated_at' => $staleVersion,
            ])
            ->assertSessionHasErrors('support_information');

        $this->assertSame('Newer support', $profile->fresh()->accessibility_support_needs);
    }

    public function test_delete_requires_current_version_and_hard_deletes_the_record(): void
    {
        $dependent = $this->learner();
        $profile = $this->profile($dependent, 'Remove me');
        $staleVersion = $profile->getRawOriginal('updated_at');
        $profile->update(['accessibility_support_needs' => 'Changed first']);

        $this->actingAs($dependent)
            ->delete(route('learner.support-information.destroy'), [
                'expected_updated_at' => $staleVersion,
                'confirm_removal' => '1',
            ])
            ->assertSessionHasErrors('support_information');

        $currentVersion = $profile->fresh()->getRawOriginal('updated_at');
        $this->actingAs($dependent)
            ->delete(route('learner.support-information.destroy'), [
                'expected_updated_at' => $currentVersion,
                'confirm_removal' => '1',
            ])
            ->assertRedirect(route('learner.support-information.edit'));

        $this->assertDatabaseMissing('dependent_support_profiles', ['id' => $profile->id]);
    }

    public function test_suspended_dependent_cannot_use_self_service_route(): void
    {
        $dependent = $this->learner(['status' => User::STATUS_SUSPENDED]);

        $this->actingAs($dependent)
            ->get(route('learner.support-information.edit'))
            ->assertForbidden();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('learner.support-information.edit'))
            ->assertRedirect(route('login'));
    }

    public function test_dashboard_link_is_neutral_and_does_not_reveal_support_presence_or_content(): void
    {
        $dependent = $this->learner();
        $marker = 'DASHBOARD_PRIVATE_MARKER';
        $this->profile($dependent, $marker);

        $this->actingAs($dependent)
            ->get(route('learner.dashboard'))
            ->assertOk()
            ->assertSee(route('learner.support-information.edit'))
            ->assertSee('Health &amp; Support Information', false)
            ->assertDontSee($marker, false)
            ->assertDontSee('support information available', false);
    }

    public function test_permission_bearing_verified_guardian_can_view_and_update_shared_record(): void
    {
        [$guardian, $dependent, $relationship] = $this->guardianSetup(true);
        $profile = $this->profile($dependent, 'Original guardian-visible detail');
        $marker = 'Updated guardian detail';

        $response = $this->actingAs($guardian)
            ->get(route('parent.children.support-information.edit', $dependent))
            ->assertOk();
        $this->assertStringContainsString('private', (string) $response->headers->get('Cache-Control'));
        $this->assertStringContainsString('no-store', (string) $response->headers->get('Cache-Control'));
        $response->assertSee('Original guardian-visible detail');

        $this->actingAs($guardian)
            ->put(route('parent.children.support-information.save', $dependent), [
                'has_relevant_support_information' => '1',
                'relevant_health_considerations' => $marker,
                'purpose_acknowledged' => '1',
                'expected_updated_at' => $profile->getRawOriginal('updated_at'),
            ])
            ->assertRedirect(route('parent.children.support-information.edit', $dependent));

        $this->assertSame($marker, $profile->fresh()->relevant_health_considerations);
        $this->assertSame($profile->id, DependentSupportProfile::query()->where('dependent_user_id', $dependent->id)->sole()->id);
        $this->assertSame($relationship->id, $relationship->fresh()->id);
    }

    public function test_permission_bearing_verified_guardian_can_create_shared_record(): void
    {
        [$guardian, $dependent] = $this->guardianSetup(true);

        $this->actingAs($guardian)
            ->get(route('parent.children.support-information.edit', $dependent))
            ->assertOk()
            ->assertDontSee('Remove all information', false);

        $this->actingAs($guardian)
            ->put(route('parent.children.support-information.save', $dependent), [
                'has_relevant_support_information' => '1',
                'accessibility_support_needs' => 'Guardian-created detail',
                'purpose_acknowledged' => '1',
            ])
            ->assertRedirect(route('parent.children.support-information.edit', $dependent));

        $this->assertDatabaseHas('dependent_support_profiles', ['dependent_user_id' => $dependent->id]);
    }

    public function test_verified_guardian_without_permission_is_denied(): void
    {
        [$guardian, $dependent] = $this->guardianSetup(false);

        $this->actingAs($guardian)
            ->get(route('parent.children.support-information.edit', $dependent))
            ->assertForbidden();
    }

    public function test_pending_rejected_revoked_inactive_and_suspended_relationships_are_denied(): void
    {
        foreach ([
            ParentChildAccount::STATUS_PENDING,
            ParentChildAccount::STATUS_REJECTED,
            ParentChildAccount::STATUS_REVOKED,
            ParentChildAccount::STATUS_INACTIVE,
        ] as $status) {
            [$guardian, $dependent, $relationship] = $this->guardianSetup(true);
            $relationship->update(['relationship_status' => $status]);

            $this->actingAs($guardian)
                ->get(route('parent.children.support-information.edit', $dependent))
                ->assertForbidden();
        }

        [$guardian, $dependent] = $this->guardianSetup(true);
        $guardian->update(['status' => User::STATUS_SUSPENDED]);

        $this->actingAs($guardian)
            ->get(route('parent.children.support-information.edit', $dependent))
            ->assertForbidden();
    }

    public function test_unrelated_guardian_cannot_use_route_bound_child(): void
    {
        [$guardian, $dependent] = $this->guardianSetup(true);
        $unrelated = $this->guardian();

        $this->actingAs($unrelated)
            ->get(route('parent.children.support-information.edit', $dependent))
            ->assertForbidden();
    }

    public function test_instructor_and_administrator_cannot_open_guardian_detail_route(): void
    {
        [, $dependent] = $this->guardianSetup(true);
        $admin = User::factory()->create(['role' => 'admin', 'status' => User::STATUS_ACTIVE]);
        $admin->assignRole('admin');
        $instructor = User::factory()->create(['role' => 'instructor', 'status' => User::STATUS_ACTIVE]);
        $instructor->assignRole('instructor');

        foreach ([$admin, $instructor] as $unauthorized) {
            $this->actingAs($unauthorized)
                ->get(route('parent.children.support-information.edit', $dependent))
                ->assertForbidden();
        }
    }

    public function test_revoked_guardian_cannot_use_an_older_enabled_detail_link(): void
    {
        [$guardian, $dependent, $relationship] = $this->guardianSetup(true);
        $relationship->update([
            'relationship_status' => ParentChildAccount::STATUS_REVOKED,
            'relationship_verified_status' => ParentChildAccount::VERIFICATION_REVOKED,
            'can_manage_support_information' => false,
        ]);

        $this->actingAs($guardian)
            ->get(route('parent.children.support-information.edit', $dependent))
            ->assertForbidden();
    }

    public function test_removing_shared_record_does_not_modify_another_guardians_relationship(): void
    {
        [$firstGuardian, $dependent, $firstRelationship] = $this->guardianSetup(true);
        $secondGuardian = $this->guardian();
        $secondRelationship = $this->relationship($secondGuardian, $dependent, true);
        $profile = $this->profile($dependent, 'Shared record');

        $this->actingAs($firstGuardian)
            ->delete(route('parent.children.support-information.destroy', $dependent), [
                'expected_updated_at' => $profile->getRawOriginal('updated_at'),
                'confirm_removal' => '1',
            ])
            ->assertRedirect(route('parent.children.support-information.edit', $dependent));

        $this->assertDatabaseMissing('dependent_support_profiles', ['id' => $profile->id]);
        $this->assertTrue($firstRelationship->fresh()->can_manage_support_information);
        $this->assertTrue($secondRelationship->fresh()->can_manage_support_information);
    }

    public function test_guardian_stale_update_and_delete_are_rejected(): void
    {
        [$guardian, $dependent] = $this->guardianSetup(true);
        $profile = $this->profile($dependent, 'Newer guardian detail');
        $staleVersion = $profile->getRawOriginal('updated_at');
        $profile->update(['accessibility_support_needs' => 'Authoritative newer detail']);

        $this->actingAs($guardian)
            ->put(route('parent.children.support-information.save', $dependent), [
                'has_relevant_support_information' => '1',
                'accessibility_support_needs' => 'Stale guardian overwrite',
                'purpose_acknowledged' => '1',
                'expected_updated_at' => $staleVersion,
            ])
            ->assertSessionHasErrors('support_information');

        $this->actingAs($guardian)
            ->delete(route('parent.children.support-information.destroy', $dependent), [
                'expected_updated_at' => $staleVersion,
                'confirm_removal' => '1',
            ])
            ->assertSessionHasErrors('support_information');

        $this->assertSame('Authoritative newer detail', $profile->fresh()->accessibility_support_needs);
    }

    /** @param array<string, mixed> $attributes */
    private function learner(array $attributes = []): User
    {
        $learner = User::factory()->create([
            'role' => 'learner',
            'status' => User::STATUS_ACTIVE,
            ...$attributes,
        ]);
        $learner->assignRole('learner');
        $learner->learnerProfile()->create([
            'username' => 'learner'.$learner->id,
            'birthdate' => now()->subYears(20)->toDateString(),
            'gender' => 'prefer_not_to_say',
            'city_code' => '402101000',
            'barangay_code' => '402101001',
        ]);

        return $learner;
    }

    private function profile(User $dependent, string $health): DependentSupportProfile
    {
        return DependentSupportProfile::query()->create([
            'dependent_user_id' => $dependent->id,
            'relevant_health_considerations' => $health,
            'accessibility_support_needs' => null,
            'additional_relevant_information' => null,
            'privacy_notice_version' => DependentSupportProfile::NOTICE_VERSION,
            'purpose_acknowledged_at' => now(),
            'purpose_acknowledged_by_user_id' => $dependent->id,
            'created_by_user_id' => $dependent->id,
            'updated_by_user_id' => $dependent->id,
        ]);
    }

    /** @return array{0: User, 1: User, 2: ParentChildAccount} */
    private function guardianSetup(bool $permission): array
    {
        $guardian = $this->guardian();
        $dependent = $this->learner();
        $relationship = $this->relationship($guardian, $dependent, $permission);

        return [$guardian, $dependent, $relationship];
    }

    private function guardian(): User
    {
        $guardian = User::factory()->create([
            'role' => 'learner',
            'status' => User::STATUS_ACTIVE,
            'email_verified_at' => now(),
            'is_parent_registration' => true,
            'parent_verification_status' => 'approved',
        ]);
        $guardian->assignRole('learner');
        $guardian->learnerProfile()->create([
            'username' => 'guardian'.$guardian->id,
            'birthdate' => now()->subYears(30)->toDateString(),
            'gender' => 'prefer_not_to_say',
            'city_code' => '402101000',
            'barangay_code' => '402101001',
            'is_parent_account' => true,
            'requires_parental_consent' => false,
        ]);

        return $guardian;
    }

    private function relationship(User $guardian, User $dependent, bool $permission): ParentChildAccount
    {
        return ParentChildAccount::query()->create([
            'parent_user_id' => $guardian->id,
            'child_user_id' => $dependent->id,
            'relationship_type' => 'biological_mother',
            'verification_pathway' => 'biological_parent',
            'relationship_status' => ParentChildAccount::STATUS_ACTIVE,
            'relationship_verified_status' => ParentChildAccount::VERIFICATION_VERIFIED,
            'relationship_verified_at' => now(),
            'relationship_verification_submitted_at' => now(),
            'verification_status' => 'approved',
            'can_view_progress' => true,
            'can_view_quiz_answers' => true,
            'can_approve_content' => false,
            'can_manage_support_information' => $permission,
        ]);
    }
}
