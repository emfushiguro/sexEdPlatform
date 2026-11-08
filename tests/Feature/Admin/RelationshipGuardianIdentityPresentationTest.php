<?php

namespace Tests\Feature\Admin;

use App\Models\ParentChildAccount;
use App\Models\PersonaInquiry;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase;

class RelationshipGuardianIdentityPresentationTest extends TestCase
{
    public function test_relationship_review_renders_guardian_ids_information_link_and_moderation_states(): void
    {
        $this->withoutVite();
        $this->withViewErrors([]);
        $guardian = new User(['name' => 'Guardian', 'email' => 'guardian@example.test', 'is_parent_registration' => true]);
        $guardian->id = 123;
        $guardian->forceFill(['parent_identity_evidence_source' => 'persona']);
        $guardian->setRelation('roles', collect());
        $this->actingAs($guardian);
        $relationship = new ParentChildAccount(['relationship_type' => 'biological_parent', 'relationship_verified_status' => 'pending']);
        $relationship->id = 456;
        $relationship->setRelation('parent', $guardian)->setRelation('child', null)
            ->setRelation('verificationDocuments', collect())->setRelation('verificationAudits', collect());
        $attempt = new PersonaInquiry(['local_government_id_type' => 'national_id']);
        $attempt->id = 789;
        $attempt->government_id_verification_id = 'test-verification';
        $render = fn (?PersonaInquiry $attempt) => $this->view('admin.parent-verifications.show-relationship', [
            'relationship' => $relationship, 'guardianPersonaAttempt' => $attempt, 'rejectionReasons' => [],
        ]);

        $render($attempt)->assertSee(route('admin.parent-verifications.persona.evidence', [$attempt, 'government-id-front']), false)
            ->assertSee(route('admin.parent-verifications.persona.evidence', [$attempt, 'government-id-back']), false)
            ->assertSee(route('admin.parent-verifications.parents.show', $guardian), false)
            ->assertSee('View guardian information')->assertDontSee('Not submitted');
        $attempt->local_government_id_type = 'philhealth';
        $render($attempt)->assertSee('government-id-front')->assertSee('government-id-back');
        $render(null)->assertSee('Guardian ID images are unavailable.')->assertSee('View guardian information');
        $guardian->forceFill(['parent_identity_evidence_source' => 'local', 'parent_id_document_path' => 'parent-verifications/test.pdf']);
        $render(null)->assertSee(route('admin.parent-verifications.parents.document', [$guardian, 'front']), false)
            ->assertSee('<iframe', false)->assertDontSee('government-id-front');

        foreach (['pending', 'under_review', 'resubmission_required', 'verified', 'rejected', 'revoked'] as $status) {
            $relationship->relationship_verified_status = $status;
            $html = (string) $render(null);
            foreach (['approve', 'changes', 'reject'] as $decision) {
                preg_match('/<button[^>]*@click="decisionModal = \x27'.$decision.'\x27"[^>]*>/', $html, $matches);
                $this->assertNotEmpty($matches, 'Missing moderation action for '.$status);
                $this->assertSame($status !== 'under_review', (bool) preg_match('/\sdisabled(?:\s|=|>)/', $matches[0]), 'Incorrect action availability for '.$status);
            }
        }
    }
}
