<?php

namespace Tests\Feature\Admin;

use App\Models\ParentChildAccount;
use App\Models\PersonaInquiry;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Pagination\LengthAwarePaginator;

class DependentGuardianIdentityPresentationTest extends TestCase
{
    public function test_dependent_modal_renders_guardian_identity_and_review_link(): void
    {
        $this->withoutVite();
        $this->withViewErrors([]);
        $guardian = new User(['name' => 'Guardian', 'email' => 'guardian@example.test']);
        $guardian->id = 123;
        $guardian->forceFill(['parent_identity_evidence_source' => 'persona', 'guardian_identity_audits_max_attempt_number' => 1]);
        $guardian->setRelation('roles', collect())->setRelation('learnerProfile', null);
        $this->actingAs($guardian);
        $attempt = new PersonaInquiry(['local_government_id_type' => 'national_id', 'target_submission_round' => 1]);
        $attempt->id = 789;
        $attempt->government_id_verification_id = 'test-verification';
        $guardian->setRelation('personaInquiries', collect([$attempt]));
        $application = new ParentChildAccount(['verification_status' => 'pending', 'verification_document_path' => 'child-verifications/test.pdf']);
        $application->id = 456;
        $application->setRelation('parent', $guardian)->setRelation('child', null);
        $data = [
            'type' => 'children', 'status' => 'pending', 'parentApplications' => collect(),
            'childApplications' => collect([$application]), 'relationshipApplications' => collect(),
            'learnerApplications' => new LengthAwarePaginator([], 0, 25),
            'learnerPathway' => 'all', 'learnerStatusCounts' => ['pending' => 0, 'approved' => 0, 'rejected' => 0],
            'relationshipFilters' => [], 'relationshipPathways' => [], 'relationshipStatuses' => [], 'relationshipVerificationStatuses' => [],
        ];
        foreach (['Parent', 'Child', 'Relationship'] as $type) {
            foreach (['pending', 'approved', 'rejected'] as $status) {
                $data[$status.$type.'Count'] = 0;
            }
        }
        $render = fn () => $this->view('admin.parent-verifications.index', $data);
        $render()->assertSee(route('admin.parent-verifications.persona.evidence', [$attempt, 'government-id-front']), false)
            ->assertSee(route('admin.parent-verifications.persona.evidence', [$attempt, 'government-id-back']), false)
            ->assertSee(route('admin.parent-verifications.parents.show', $guardian), false)
            ->assertSee('View guardian information');

        $attempt->local_government_id_type = 'philhealth';
        $render()->assertSee('government-id-front')->assertSee('government-id-back');

        $attempt->target_submission_round = 2;
        $render()->assertSee('Guardian ID images are unavailable.')->assertDontSee('government-id-front');

        $guardian->forceFill(['parent_identity_evidence_source' => 'local', 'parent_id_document_path' => 'parent-verifications/front.jpg', 'parent_id_document_back_path' => 'parent-verifications/back.pdf']);
        $render()->assertSee(route('admin.parent-verifications.parents.document', [$guardian, 'front']), false)
            ->assertSee(route('admin.parent-verifications.parents.document', [$guardian, 'back']), false)
            ->assertDontSee('government-id-front');
    }
}
