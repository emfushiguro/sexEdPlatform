<?php

namespace Tests\Feature\DependentSupport;

use App\Models\DependentSupportInformationAudit;
use App\Models\ParentChildAccount;
use App\Models\User;
use App\Services\DependentSupportInformationService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class DependentSupportServiceTest extends TestCase
{
    public function test_dependent_can_create_and_update_with_encrypted_audits(): void
    {
        $dependent = User::factory()->create(['status' => User::STATUS_ACTIVE]);
        $service = app(DependentSupportInformationService::class);

        $created = $service->save($dependent, $dependent, [
            'relevant_health_considerations' => null,
            'accessibility_support_needs' => 'Synthetic reading support',
            'additional_relevant_information' => null,
        ], null);

        $this->assertSame($dependent->id, $created->dependent_user_id);
        $this->assertDatabaseHas('dependent_support_information_audits', [
            'dependent_user_id' => $dependent->id,
            'actor_user_id' => $dependent->id,
            'action' => DependentSupportInformationAudit::ACTION_CREATED,
        ]);
        $this->assertNotSame(
            'Synthetic reading support',
            DB::table('dependent_support_profiles')->where('id', $created->id)->value('accessibility_support_needs'),
        );

        $expected = $created->getRawOriginal('updated_at');
        $updated = $service->save($dependent, $dependent, [
            'relevant_health_considerations' => null,
            'accessibility_support_needs' => 'Updated synthetic support',
            'additional_relevant_information' => null,
        ], $expected);

        $this->assertSame('Updated synthetic support', $updated->accessibility_support_needs);
        $this->assertDatabaseHas('dependent_support_information_audits', [
            'dependent_user_id' => $dependent->id,
            'actor_user_id' => $dependent->id,
            'action' => DependentSupportInformationAudit::ACTION_UPDATED,
        ]);
    }

    public function test_create_rejects_duplicate_and_empty_payloads(): void
    {
        $dependent = User::factory()->create(['status' => User::STATUS_ACTIVE]);
        $service = app(DependentSupportInformationService::class);
        $payload = [
            'relevant_health_considerations' => 'Synthetic participation consideration',
            'accessibility_support_needs' => null,
            'additional_relevant_information' => null,
        ];

        $this->expectException(ValidationException::class);
        $service->save($dependent, $dependent, $payload, null);
        $service->save($dependent, $dependent, $payload, null);
    }

    public function test_empty_payload_is_rejected_before_a_record_is_created(): void
    {
        $dependent = User::factory()->create(['status' => User::STATUS_ACTIVE]);

        try {
            app(DependentSupportInformationService::class)->save($dependent, $dependent, [
                'relevant_health_considerations' => null,
                'accessibility_support_needs' => '   ',
                'additional_relevant_information' => null,
            ], null);
            $this->fail('Expected empty payload to fail.');
        } catch (ValidationException) {
            $this->assertDatabaseMissing('dependent_support_profiles', ['dependent_user_id' => $dependent->id]);
        }
    }

    public function test_stale_update_and_stale_update_after_delete_fail_without_overwrite_or_recreation(): void
    {
        $dependent = User::factory()->create(['status' => User::STATUS_ACTIVE]);
        $service = app(DependentSupportInformationService::class);
        $profile = $service->save($dependent, $dependent, [
            'relevant_health_considerations' => 'Synthetic original value',
            'accessibility_support_needs' => null,
            'additional_relevant_information' => null,
        ], null);
        $staleVersion = $profile->getRawOriginal('updated_at');

        $fresh = $service->save($dependent, $dependent, [
            'relevant_health_considerations' => 'Synthetic newer value',
            'accessibility_support_needs' => null,
            'additional_relevant_information' => null,
        ], $staleVersion);
        $this->assertNotSame($staleVersion, $fresh->getRawOriginal('updated_at'));

        try {
            $service->save($dependent, $dependent, [
                'relevant_health_considerations' => 'Synthetic stale overwrite',
                'accessibility_support_needs' => null,
                'additional_relevant_information' => null,
            ], $staleVersion);
            $this->fail('Expected stale update to fail.');
        } catch (ValidationException) {
            $this->assertSame('Synthetic newer value', $fresh->fresh()->relevant_health_considerations);
        }

        $deleteVersion = $fresh->fresh()->getRawOriginal('updated_at');
        $service->remove($fresh, $dependent, $deleteVersion);
        $this->assertDatabaseMissing('dependent_support_profiles', ['id' => $profile->id]);

        try {
            $service->save($dependent, $dependent, [
                'relevant_health_considerations' => 'Synthetic recreated value',
                'accessibility_support_needs' => null,
                'additional_relevant_information' => null,
            ], $deleteVersion);
            $this->fail('Expected stale create-after-delete to fail.');
        } catch (ValidationException) {
            $this->assertDatabaseMissing('dependent_support_profiles', ['dependent_user_id' => $dependent->id]);
        }
    }

    public function test_hard_delete_leaves_only_metadata_audit_field_names(): void
    {
        $dependent = User::factory()->create(['status' => User::STATUS_ACTIVE]);
        $service = app(DependentSupportInformationService::class);
        $profile = $service->save($dependent, $dependent, [
            'relevant_health_considerations' => 'Synthetic private marker',
            'accessibility_support_needs' => 'Synthetic access context',
            'additional_relevant_information' => null,
        ], null);
        $version = $profile->getRawOriginal('updated_at');

        $service->remove($profile, $dependent, $version);

        $this->assertDatabaseMissing('dependent_support_profiles', ['id' => $profile->id]);
        $audit = DependentSupportInformationAudit::query()
            ->where('dependent_user_id', $dependent->id)
            ->where('action', DependentSupportInformationAudit::ACTION_REMOVED)
            ->latest('id')
            ->firstOrFail();
        $this->assertNull($audit->changed_fields);
        $this->assertStringNotContainsString('Synthetic private marker', json_encode($audit->toArray()));
        $this->assertStringNotContainsString('Synthetic access context', json_encode($audit->toArray()));
    }

    public function test_registration_requires_exact_guardian_and_allows_pending_nonterminal_relationship(): void
    {
        $dependent = User::factory()->create(['status' => User::STATUS_ACTIVE]);
        $guardian = User::factory()->create(['status' => User::STATUS_ACTIVE]);
        $otherGuardian = User::factory()->create(['status' => User::STATUS_ACTIVE]);
        $relationship = $this->relationship($guardian, $dependent, [
            'relationship_status' => ParentChildAccount::STATUS_PENDING,
            'relationship_verified_status' => ParentChildAccount::VERIFICATION_PENDING,
            'relationship_verified_at' => null,
        ]);
        $service = app(DependentSupportInformationService::class);

        $profile = $service->saveDuringRegistration($relationship, $guardian, [
            'relevant_health_considerations' => null,
            'accessibility_support_needs' => 'Synthetic registration support',
            'additional_relevant_information' => null,
        ]);

        $this->assertSame($dependent->id, $profile->dependent_user_id);

        $this->expectException(AuthorizationException::class);
        $service->saveDuringRegistration($relationship, $otherGuardian, [
            'relevant_health_considerations' => 'Synthetic unauthorized value',
            'accessibility_support_needs' => null,
            'additional_relevant_information' => null,
        ]);
    }

    public function test_guardian_access_is_independent_and_lifecycle_reset_is_scoped(): void
    {
        $dependent = User::factory()->create(['status' => User::STATUS_ACTIVE]);
        $guardianA = User::factory()->create([
            'status' => User::STATUS_ACTIVE,
            'parent_verification_status' => 'approved',
        ]);
        $guardianB = User::factory()->create([
            'status' => User::STATUS_ACTIVE,
            'parent_verification_status' => 'approved',
        ]);
        $relationshipA = $this->relationship($guardianA, $dependent);
        $relationshipB = $this->relationship($guardianB, $dependent);
        $service = app(DependentSupportInformationService::class);

        $service->setGuardianAccess($relationshipA, $dependent, true);

        $this->assertTrue($relationshipA->fresh()->can_manage_support_information);
        $this->assertFalse($relationshipB->fresh()->can_manage_support_information);

        $service->resetGuardianAccessForLifecycle($relationshipA->fresh(), $guardianA);

        $this->assertFalse($relationshipA->fresh()->can_manage_support_information);
        $this->assertFalse($relationshipB->fresh()->can_manage_support_information);
        $this->assertDatabaseHas('dependent_support_information_audits', [
            'parent_child_account_id' => $relationshipA->id,
            'action' => DependentSupportInformationAudit::ACTION_PERMISSION_REVOKED,
        ]);
    }

    public function test_unauthorized_service_calls_throw_without_exposing_record(): void
    {
        $dependent = User::factory()->create(['status' => User::STATUS_ACTIVE]);
        $unrelated = User::factory()->create(['status' => User::STATUS_ACTIVE]);
        $service = app(DependentSupportInformationService::class);
        $profile = $service->save($dependent, $dependent, [
            'relevant_health_considerations' => 'Synthetic private marker',
            'accessibility_support_needs' => null,
            'additional_relevant_information' => null,
        ], null);

        try {
            $service->save($dependent, $unrelated, [
                'relevant_health_considerations' => 'Synthetic unauthorized update',
                'accessibility_support_needs' => null,
                'additional_relevant_information' => null,
            ], $profile->getRawOriginal('updated_at'));
            $this->fail('Expected unauthorized update to fail.');
        } catch (AuthorizationException) {
            $this->assertSame('Synthetic private marker', $profile->fresh()->relevant_health_considerations);
        }

        $this->expectException(AuthorizationException::class);
        $service->remove($profile, $unrelated, $profile->getRawOriginal('updated_at'));
    }

    public function test_registration_rejects_trashed_relationship_with_authorization_exception(): void
    {
        $guardian = User::factory()->create(['status' => User::STATUS_ACTIVE]);
        $dependent = User::factory()->create(['status' => User::STATUS_ACTIVE]);
        $relationship = $this->relationship($guardian, $dependent);
        $relationship->delete();

        $this->expectException(AuthorizationException::class);

        app(DependentSupportInformationService::class)->saveDuringRegistration(
            $relationship,
            $guardian,
            ['relevant_health_considerations' => 'Private detail'],
        );
    }

    /** @param array<string, mixed> $overrides */
    private function relationship(User $guardian, User $dependent, array $overrides = []): ParentChildAccount
    {
        return ParentChildAccount::query()->create(array_merge([
            'parent_user_id' => $guardian->id,
            'child_user_id' => $dependent->id,
            'relationship_status' => ParentChildAccount::STATUS_ACTIVE,
            'relationship_verified_status' => ParentChildAccount::VERIFICATION_VERIFIED,
            'relationship_verified_at' => now(),
            'can_manage_support_information' => false,
        ], $overrides));
    }
}
