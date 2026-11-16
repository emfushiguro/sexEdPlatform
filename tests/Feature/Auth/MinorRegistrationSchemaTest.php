<?php

namespace Tests\Feature\Auth;

use App\Models\LearnerProfile;
use App\Models\MinorAccountHandoff;
use App\Models\MinorRegistrationRequest;
use App\Models\ParentChildAccount;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use Tests\TestCase;

class MinorRegistrationSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_minor_workflow_schema_exists(): void
    {
        $this->assertTrue(Schema::hasColumns('users', ['email', 'minor_account_activation_required']));
        $this->assertTrue(Schema::hasColumn('learner_profiles', 'username'));
        $this->assertTrue(Schema::hasColumns('parent_child_accounts', [
            'minor_registration_authorized_at',
            'minor_registration_consent_version',
            'minor_registration_authorized_by_user_id',
        ]));
        $this->assertTrue(Schema::hasColumns('minor_registration_requests', [
            'guardian_email',
            'guardian_email_fingerprint',
            'minor_payload',
            'token_hash',
            'status',
            'expires_at',
        ]));
        $this->assertTrue(Schema::hasColumns('minor_account_handoffs', [
            'child_user_id',
            'guardian_user_id',
            'parent_child_account_id',
            'purpose',
            'token_hash',
            'approved_at',
            'expires_at',
            'used_at',
        ]));
    }

    public function test_nullable_identity_fields_keep_non_null_uniqueness(): void
    {
        User::factory()->create(['email' => null]);
        User::factory()->create(['email' => null]);

        $this->expectException(QueryException::class);
        User::factory()->create(['email' => 'duplicate@example.test']);
        User::factory()->create(['email' => 'duplicate@example.test']);
    }

    public function test_nullable_usernames_keep_non_null_uniqueness(): void
    {
        $first = User::factory()->create();
        $second = User::factory()->create();

        LearnerProfile::factory()->create(['user_id' => $first->id, 'username' => null]);
        LearnerProfile::factory()->create(['user_id' => $second->id, 'username' => null]);

        $third = User::factory()->create();
        $fourth = User::factory()->create();
        LearnerProfile::factory()->create(['user_id' => $third->id, 'username' => 'same-username']);

        $this->expectException(QueryException::class);
        LearnerProfile::factory()->create(['user_id' => $fourth->id, 'username' => 'same-username']);
    }

    public function test_sensitive_request_values_are_encrypted_at_rest(): void
    {
        $request = MinorRegistrationRequest::create([
            'guardian_email' => 'guardian@example.test',
            'guardian_email_fingerprint' => hash('sha256', 'guardian@example.test'),
            'minor_payload' => ['name' => 'Private learner', 'birthdate' => '2012-01-01'],
            'token_hash' => hash('sha256', 'one-time-token'),
            'status' => MinorRegistrationRequest::STATUS_PENDING,
            'expires_at' => now()->addDay(),
        ]);

        $raw = DB::table('minor_registration_requests')->where('id', $request->id)->first();

        $this->assertNotSame('guardian@example.test', $raw->guardian_email);
        $this->assertStringNotContainsString('Private learner', (string) $raw->minor_payload);
        $this->assertSame('guardian@example.test', $request->fresh()->guardian_email);
        $this->assertSame('Private learner', $request->fresh()->minor_payload['name']);
    }

    public function test_minor_access_scope_requires_current_authorized_relationship(): void
    {
        $guardian = $this->guardian();
        $child = $this->minor();
        $relationship = $this->relationship($guardian, $child);

        $this->assertTrue(ParentChildAccount::minorAccessEligible()->whereKey($relationship)->exists());

        foreach ([
            ['relationship_status' => ParentChildAccount::STATUS_REVOKED],
            ['relationship_status' => ParentChildAccount::STATUS_INACTIVE],
            ['relationship_verified_status' => ParentChildAccount::VERIFICATION_PENDING],
            ['minor_registration_authorized_by_user_id' => User::factory()->create()->id],
            ['minor_registration_consent_version' => 'old-version'],
        ] as $invalidAttributes) {
            $relationship->update(array_merge([
                'relationship_status' => ParentChildAccount::STATUS_ACTIVE,
                'relationship_verified_status' => ParentChildAccount::VERIFICATION_VERIFIED,
                'minor_registration_authorized_by_user_id' => $guardian->id,
                'minor_registration_consent_version' => config('learner_age_policy.guardian_consent_version'),
            ], $invalidAttributes));

            $this->assertFalse(ParentChildAccount::minorAccessEligible()->whereKey($relationship)->exists());
        }

        $relationship->delete();
        $this->assertFalse(ParentChildAccount::minorAccessEligible()->whereKey($relationship)->exists());
    }

    public function test_latest_qualifying_guardian_relationship_is_selected_deterministically(): void
    {
        $child = $this->minor();
        $olderGuardian = $this->guardian();
        $newerGuardian = $this->guardian();

        $older = $this->relationship($olderGuardian, $child, now()->subDay());
        $newer = $this->relationship($newerGuardian, $child, now());

        $this->assertSame($newer->id, $child->fresh()->qualifyingMinorGuardianRelationship()?->id);

        $newer->update(['relationship_status' => ParentChildAccount::STATUS_REVOKED]);
        $this->assertSame($older->id, $child->fresh()->qualifyingMinorGuardianRelationship()?->id);

        $older->update(['minor_registration_consent_version' => 'old-version']);
        $this->assertNull($child->fresh()->qualifyingMinorGuardianRelationship());
    }

    public function test_handoff_purpose_is_limited_to_supported_workflows(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new MinorAccountHandoff(['purpose' => 'unsupported']);
    }

    private function guardian(): User
    {
        return User::factory()->create([
            'status' => User::STATUS_ACTIVE,
            'parent_verification_status' => 'approved',
            'birthdate' => now()->subYears(40)->toDateString(),
        ]);
    }

    private function minor(): User
    {
        return User::factory()->create([
            'role' => 'learner',
            'status' => User::STATUS_ACTIVE,
            'birthdate' => now()->subYears(15)->toDateString(),
        ]);
    }

    private function relationship(User $guardian, User $child, $authorizedAt = null): ParentChildAccount
    {
        return ParentChildAccount::create([
            'parent_user_id' => $guardian->id,
            'child_user_id' => $child->id,
            'relationship_type' => 'biological_mother',
            'relationship_status' => ParentChildAccount::STATUS_ACTIVE,
            'relationship_verified_status' => ParentChildAccount::VERIFICATION_VERIFIED,
            'relationship_verified_at' => now(),
            'minor_registration_authorized_at' => $authorizedAt ?? now(),
            'minor_registration_consent_version' => config('learner_age_policy.guardian_consent_version'),
            'minor_registration_authorized_by_user_id' => $guardian->id,
        ]);
    }
}
