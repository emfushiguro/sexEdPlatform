<?php

namespace Tests\Feature\DependentSupport;

use App\Models\DependentSupportProfile;
use App\Models\ParentChildAccount;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class DependentSupportAuthorizationTest extends TestCase
{
    public function test_dependent_and_exact_eligible_guardian_can_manage_when_enabled(): void
    {
        [$dependent, $guardian, $relationship, $profile] = $this->scenario();

        $this->assertTrue(Gate::forUser($dependent)->allows('view', $profile));
        $this->assertTrue(Gate::forUser($dependent)->allows('update', $profile));
        $this->assertTrue(Gate::forUser($dependent)->allows('delete', $profile));
        $this->assertTrue(Gate::forUser($dependent)->allows('create', [DependentSupportProfile::class, $dependent]));

        $relationship->update(['can_manage_support_information' => true]);
        $this->assertTrue(Gate::forUser($guardian)->allows('view', $profile));
        $this->assertTrue(Gate::forUser($guardian)->allows('update', $profile));
        $this->assertTrue(Gate::forUser($guardian)->allows('delete', $profile));

        $relationship->update(['can_manage_support_information' => false]);
        $this->assertFalse(Gate::forUser($guardian)->allows('view', $profile));
        $this->assertFalse(Gate::forUser($guardian)->allows('update', $profile));
        $this->assertFalse(Gate::forUser($guardian)->allows('delete', $profile));

        $relationship->update([
            'relationship_status' => ParentChildAccount::STATUS_PENDING,
            'relationship_verified_status' => ParentChildAccount::VERIFICATION_UNDER_REVIEW,
            'relationship_verified_at' => null,
            'can_manage_support_information' => true,
        ]);
        $this->assertFalse(Gate::forUser($guardian)->allows('view', $profile));
    }

    public function test_unresolved_dependent_denies_without_throwing(): void
    {
        $actor = User::factory()->create(['status' => User::STATUS_ACTIVE]);
        $profile = new DependentSupportProfile;
        $profile->setRelation('dependent', null);

        $this->assertFalse(Gate::forUser($actor)->allows('view', $profile));
    }

    public function test_instructor_unrelated_and_suspended_guardians_are_denied(): void
    {
        [$dependent, $guardian, $relationship, $profile] = $this->scenario();
        $relationship->update(['can_manage_support_information' => true]);

        $instructor = User::factory()->create([
            'role' => 'instructor',
            'status' => User::STATUS_ACTIVE,
        ]);
        $unrelated = User::factory()->create(['status' => User::STATUS_ACTIVE]);

        $this->assertFalse(Gate::forUser($instructor)->allows('view', $profile));
        $this->assertFalse(Gate::forUser($unrelated)->allows('view', $profile));

        $guardian->update(['status' => User::STATUS_SUSPENDED]);
        $this->assertFalse(Gate::forUser($guardian->fresh())->allows('view', $profile));
    }

    public function test_administrator_is_denied_support_content_but_keeps_ordinary_bypass(): void
    {
        [$dependent, , , $profile] = $this->scenario();
        $admin = User::factory()->create(['status' => User::STATUS_ACTIVE]);
        $admin->assignRole('admin');

        $this->assertFalse(Gate::forUser($admin)->allows('view', $profile));
        $this->assertTrue(Gate::forUser($admin)->allows('view', $dependent));
    }

    /** @return array{0: User, 1: User, 2: ParentChildAccount, 3: DependentSupportProfile} */
    private function scenario(): array
    {
        $dependent = User::factory()->create(['status' => User::STATUS_ACTIVE]);
        $guardian = User::factory()->create([
            'account_type' => User::ACCOUNT_TYPE_PARENT,
            'parent_verification_status' => 'approved',
            'status' => User::STATUS_ACTIVE,
        ]);
        $relationship = ParentChildAccount::query()->create([
            'parent_user_id' => $guardian->id,
            'child_user_id' => $dependent->id,
            'relationship_status' => ParentChildAccount::STATUS_ACTIVE,
            'relationship_verified_status' => ParentChildAccount::VERIFICATION_VERIFIED,
            'relationship_verified_at' => now(),
            'can_manage_support_information' => false,
        ]);
        $profile = DependentSupportProfile::query()->create([
            'dependent_user_id' => $dependent->id,
            'relevant_health_considerations' => 'Synthetic participation consideration',
            'privacy_notice_version' => DependentSupportProfile::NOTICE_VERSION,
            'purpose_acknowledged_at' => now(),
            'purpose_acknowledged_by_user_id' => $dependent->id,
            'created_by_user_id' => $dependent->id,
            'updated_by_user_id' => $dependent->id,
        ]);

        return [$dependent, $guardian, $relationship, $profile];
    }
}
