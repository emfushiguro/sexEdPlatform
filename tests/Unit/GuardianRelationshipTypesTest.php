<?php

namespace Tests\Unit;

use App\Support\GuardianRelationshipTypes;
use Tests\TestCase;

class GuardianRelationshipTypesTest extends TestCase
{
    public function test_relationship_verification_policy_is_centralized_by_type(): void
    {
        $this->assertTrue(GuardianRelationshipTypes::requiresVerification('biological_mother'));
        $this->assertTrue(GuardianRelationshipTypes::requiresVerification('adoptive_parent'));
        $this->assertContains('adoption_order', GuardianRelationshipTypes::acceptedDocumentTypes('adoptive_parent'));
    }

    public function test_every_selectable_relationship_requires_admin_verification(): void
    {
        foreach (GuardianRelationshipTypes::selectableValues() as $type) {
            $this->assertTrue(GuardianRelationshipTypes::requiresVerification($type), $type);
            $this->assertSame('pending', GuardianRelationshipTypes::initialVerificationStatus($type));
        }

        $this->assertNotContains(GuardianRelationshipTypes::LEGACY_PARENT, GuardianRelationshipTypes::selectableValues());
    }

    public function test_relationship_types_map_to_the_expected_evidence_pathways(): void
    {
        $this->assertSame('biological_parent', GuardianRelationshipTypes::pathway('biological_mother'));
        $this->assertSame('adoptive_parent', GuardianRelationshipTypes::pathway('adoptive_parent'));
        $this->assertSame('non_parental_care', GuardianRelationshipTypes::pathway('grandmother'));
        $this->assertSame('non_parental_care', GuardianRelationshipTypes::pathway('aunt'));
        $this->assertSame('custom_care', GuardianRelationshipTypes::pathway('other'));
        $this->assertSame('legacy', GuardianRelationshipTypes::pathway('parent'));
    }

    public function test_non_parental_pathways_accept_flexible_evidence_and_require_context(): void
    {
        $types = GuardianRelationshipTypes::acceptedDocumentTypes('aunt');

        $this->assertContains('court_order', $types);
        $this->assertContains('agency_document', $types);
        $this->assertContains('care_arrangement', $types);
        $this->assertContains('other_supporting_document', $types);
        $this->assertTrue(GuardianRelationshipTypes::requiresCircumstances('aunt'));
    }
}
