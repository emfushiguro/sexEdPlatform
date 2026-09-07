<?php

namespace Tests\Feature;

use App\Models\GuardianRelationshipVerificationAudit;
use App\Models\GuardianRelationshipVerificationDocument;
use App\Models\ParentChildAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class GuardianRelationshipSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_relationship_and_evidence_round_columns_exist(): void
    {
        $this->assertTrue(Schema::hasColumns('parent_child_accounts', [
            'verification_pathway', 'current_evidence_round', 'relationship_deactivated_at',
        ]));
        $this->assertTrue(Schema::hasColumns('guardian_relationship_verification_documents', [
            'submission_round', 'document_side', 'pairing_key', 'display_order',
            'content_sha256', 'submitted_at', 'superseded_at',
        ]));
        $this->assertTrue(Schema::hasColumn('guardian_relationship_verification_audits', 'submission_round'));
    }

    public function test_verified_active_scope_excludes_pending_and_revoked_guardians(): void
    {
        $guardian = User::factory()->create([
            'status' => 'active',
            'is_parent_registration' => true,
            'parent_verification_status' => 'approved',
        ]);
        $dependent = User::factory()->create(['status' => 'active']);

        $active = ParentChildAccount::create([
            'parent_user_id' => $guardian->id,
            'child_user_id' => $dependent->id,
            'relationship_type' => 'biological_mother',
            'verification_pathway' => 'biological_parent',
            'relationship_status' => 'active',
            'relationship_verified_status' => 'verified',
            'relationship_verified_at' => now(),
        ]);

        $this->assertTrue(ParentChildAccount::verifiedActive()->whereKey($active->getKey())->exists());

        $active->update(['relationship_status' => 'revoked', 'relationship_verified_status' => 'revoked']);

        $this->assertFalse(ParentChildAccount::verifiedActive()->whereKey($active->getKey())->exists());
    }
}
