<?php

namespace Tests\Feature\Identity;

use App\Models\GuardianIdentityAudit;
use App\Models\LearnerIdentityAudit;
use App\Models\LearnerIdentityVerification;
use App\Models\PersonaInquiry;
use App\Models\User;
use Tests\TestCase;

class VerificationPresentationTest extends TestCase
{
    public function test_persona_summary_shows_back_preview_for_front_only_id_types(): void
    {
        $attempt = new PersonaInquiry([
            'attempt_number' => 1, 'flow_type' => PersonaInquiry::FLOW_GUARDIAN_GOVERNMENT,
            'local_government_id_type' => 'philhealth', 'government_id_verification_id' => 'test-gov',
            'is_sandbox' => true, 'verification_outcome' => PersonaInquiry::OUTCOME_ELIGIBLE,
        ]);
        $attempt->id = 123;
        $this->view('admin.parent-verifications.partials.persona-summary', ['personaAttempts' => collect([$attempt])])
            ->assertSee(route('admin.parent-verifications.persona.evidence', [$attempt, 'government-id-back']), false);
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->withViewErrors([]);
    }

    public function test_learner_status_cards_use_plain_statuses_and_keep_the_approval_gate(): void
    {
        $learner = User::factory()->create(['role' => 'learner']);
        $this->actingAs($learner);
        config(['persona.enabled' => true, 'persona.environment' => 'sandbox', 'persona.local_manual_review' => true]);
        $this->app->instance('env', 'local');

        foreach (['pending', 'rejected', 'approved'] as $status) {
            $case = new LearnerIdentityVerification([
                'status' => $status, 'pathway' => 'adult', 'rejection_reason' => 'Please provide a clearer ID.',
            ]);
            $case->forceFill(['sandbox_manual_review' => true, 'evidence_source' => 'persona']);
            $case->setRelation('learner', $learner);
            $view = $this->view('auth.learner-identity-status', ['case' => $case, 'supportHold' => false]);
            $view->assertSee($status === 'rejected' ? 'Changes required' : ucfirst($status));
            $this->assertPlainPresentation((string) $view);
            if ($status === 'approved') {
                $view->assertSee('Continue to profile');
                $this->app->instance('env', 'production');
                $blocked = $this->view('auth.learner-identity-status', ['case' => $case, 'supportHold' => false]);
                $blocked->assertSee('Contact support to continue.')->assertDontSee('Continue to profile');
                $this->assertPlainPresentation((string) $blocked);
            }
        }
    }

    public function test_guardian_status_cards_and_modal_keep_plain_copy_and_block_unavailable_approval(): void
    {
        $guardian = User::factory()->create(['role' => 'learner', 'is_parent_registration' => true]);
        $guardian->forceFill([
            'parent_identity_sandbox_manual_review' => true,
            'parent_identity_evidence_source' => 'persona',
            'parent_identity_requested_corrections' => ['id', 'selfie'],
        ]);
        $this->actingAs($guardian)->withSession(['show_parent_approved_modal' => true]);
        config(['persona.enabled' => true, 'persona.environment' => 'sandbox', 'persona.local_manual_review' => true]);
        $this->app->instance('env', 'local');

        foreach (['pending', 'rejected', 'approved'] as $status) {
            $guardian->parent_verification_status = $status;
            $view = $this->view('auth.parent-verification-status', ['user' => $guardian]);
            $view->assertSee(ucfirst($status));
            $view->assertDontSee('Open Gmail Inbox');
            if ($status === 'pending') {
                $view->assertSee('Check status')->assertSee(route('guardian.verification.status'), false);
            }
            if ($status === 'rejected') {
                $view->assertSee('Requested updates:')->assertSee('ID and selfie');
            }
            $this->assertPlainPresentation((string) $view);
        }
        $this->app->instance('env', 'production');
        $view = $this->view('auth.parent-verification-status', ['user' => $guardian]);
        $view->assertSee('Contact support to continue.')->assertDontSee('Start Learning')->assertDontSee('Create Dependent Account');
        $this->assertPlainPresentation((string) $view);
    }

    public function test_persona_step_hides_environment_copy_and_keeps_unavailable_review_blocked(): void
    {
        $this->app->instance('env', 'production');
        $attempt = new PersonaInquiry([
            'verification_outcome' => PersonaInquiry::OUTCOME_SANDBOX_REVIEW,
            'local_document_type' => 'government_id', 'local_government_id_type' => 'passport',
        ]);
        $view = $this->view('auth.partials.persona-identity-step', ['attempt' => $attempt]);
        $view->assertSee('Contact support to continue.')
            ->assertDontSee('Check manual review submission')->assertDontSee('<form', false);
        $this->assertPlainPresentation((string) $view);
    }

    public function test_summary_labels_all_three_poses_and_explains_missing_images_without_exposing_provider_urls(): void
    {
        $attempt = new PersonaInquiry([
            'attempt_number' => 1, 'is_sandbox' => true, 'selfie_verification_id' => 'selfie_preview',
            'flow_type' => PersonaInquiry::FLOW_TEEN_SCHOOL, 'verification_outcome' => PersonaInquiry::OUTCOME_ELIGIBLE,
        ]);
        $attempt->id = 123;
        $view = $this->view('admin.parent-verifications.partials.persona-summary', ['personaAttempts' => collect([$attempt])]);
        foreach (['selfie' => 'center', 'selfie-left' => 'left', 'selfie-right' => 'right'] as $artifact => $pose) {
            $view->assertSee('Persona selfie — '.$pose)
                ->assertSee(route('admin.parent-verifications.persona.evidence', [$attempt, $artifact]), false);
        }
        $view->assertSee('This pose or image may not have been collected')
            ->assertDontSee('files.withpersona.com');
        $this->assertPlainPresentation((string) $view);
    }

    public function test_school_selfie_start_and_retry_keep_the_staged_document_choice_hidden(): void
    {
        foreach (['school_id', 'institution_id'] as $documentType) {
            foreach ([null, new PersonaInquiry(['persona_status' => 'failed'])] as $attempt) {
                $view = $this->view('auth.partials.persona-identity-step', [
                    'attempt' => $attempt, 'selfieOnly' => true, 'documentType' => $documentType,
                ]);
                $view->assertSee('Start selfie verification')
                    ->assertSee('type="hidden" name="document_type" value="'.$documentType.'"', false)
                    ->assertDontSee('<select', false)
                    ->assertDontSee('government_id_type');
                $this->assertPlainPresentation((string) $view);
            }
        }
    }

    public function test_admin_approval_dialog_keeps_required_confirmation_fields_with_plain_labels(): void
    {
        $learner = User::factory()->create(['role' => 'learner']);
        $this->actingAs($learner);
        $case = new LearnerIdentityVerification([
            'pathway' => 'adult', 'document_type' => 'government_id', 'government_id_type' => 'passport',
            'status' => 'pending', 'submission_round' => 1,
        ]);
        $case->forceFill(['id' => 123, 'sandbox_manual_review' => true, 'evidence_source' => 'persona']);
        $audit = new LearnerIdentityAudit(['reason' => PersonaInquiry::SANDBOX_APPROVAL_REASON]);
        $audit->setRelation('actor', null);
        $case->setRelation('learner', $learner)->setRelation('evidence', collect())
            ->setRelation('personaInquiries', collect())->setRelation('audits', collect([$audit]));
        $view = $this->view('admin.parent-verifications.show-learner', ['case' => $case, 'reviewer' => null]);
        foreach (['sandbox_philippine_id_confirmed', 'sandbox_id_type_confirmed', 'persona_portrait_compared'] as $field) {
            $view->assertSee('name="'.$field.'" value="1" required', false);
        }
        $view->assertSee('Required confirmations');
        $view->assertSee('Approved after manual review');
        $this->assertMatchesRegularExpression('/<option value="1"[^>]*selected/', (string) $view);
        $this->assertPlainPresentation((string) $view);
    }

    public function test_guardian_approval_dialog_keeps_the_attempt_and_confirmation_fields(): void
    {
        $guardian = User::factory()->create([
            'role' => 'learner', 'is_parent_registration' => true, 'parent_verification_status' => 'pending',
        ]);
        $guardian->forceFill(['parent_identity_sandbox_manual_review' => true, 'parent_identity_evidence_source' => 'persona']);
        $attempt = new PersonaInquiry([
            'attempt_number' => 1, 'flow_type' => PersonaInquiry::FLOW_GUARDIAN_GOVERNMENT,
            'local_government_id_type' => 'passport', 'verification_outcome' => PersonaInquiry::OUTCOME_SANDBOX_REVIEW,
        ]);
        $attempt->id = 123;
        $guardian->setRelation('personaInquiries', collect([$attempt]));
        $this->actingAs($guardian);

        $view = $this->view('admin.parent-verifications.show-parent', ['guardian' => $guardian, 'idTypeLabel' => 'Passport']);
        $view->assertSee('name="persona_attempt_id" value="123"', false)->assertSee('Required confirmations');
        foreach (['sandbox_philippine_id_confirmed', 'sandbox_id_type_confirmed', 'persona_portrait_compared'] as $field) {
            $view->assertSee('name="'.$field.'" value="1" required', false);
        }
        $this->assertPlainPresentation((string) $view);
    }

    public function test_onboarding_and_profile_have_no_environment_approval_notices(): void
    {
        $guardian = User::factory()->create(['role' => 'learner', 'is_parent_registration' => true]);
        $guardian->forceFill(['parent_identity_sandbox_manual_review' => true]);
        $this->actingAs($guardian);
        $data = ['learnerProfile' => null, 'cities' => collect(), 'barangays' => collect()];
        $onboarding = $this->view('auth.guardian-onboarding', $data);
        $onboarding->assertSee('Your Guardian account is verified.');
        $this->assertPlainPresentation((string) $onboarding);
        $profile = $this->view('profile.complete', $data);
        $profile->assertSee('Complete Your Profile');
        $this->assertPlainPresentation((string) $profile);
    }

    public function test_learner_correction_status_lists_requested_items_and_final_rejection_has_no_retry_link(): void
    {
        $learner = User::factory()->create(['role' => 'learner']);
        $this->actingAs($learner);
        $case = new LearnerIdentityVerification(['status' => 'rejected', 'pathway' => 'teen']);
        $case->forceFill([
            'resubmission_allowed' => true, 'requested_corrections' => ['identity_front', 'identity_back', 'selfie', 'persona_identity'],
            'correction_note' => 'Please keep all corners visible.',
        ]);
        $view = $this->view('auth.learner-identity-status', ['case' => $case, 'supportHold' => false]);
        $view->assertSee('Changes required')->assertSee('Update requested items')
            ->assertSee(route('learner.identity.create'), false)->assertSee('Please keep all corners visible.');
        foreach (['ID front', 'ID back', 'Selfie', 'ID and selfie'] as $label) {
            $view->assertSee($label);
        }
        $this->assertPlainPresentation((string) $view);

        $case->resubmission_allowed = false;
        $view = $this->view('auth.learner-identity-status', ['case' => $case, 'supportHold' => false]);
        $view->assertSee('Rejected')->assertSee('Contact support to continue.')
            ->assertDontSee('Update requested items')->assertDontSee('href="'.route('learner.identity.create').'"', false);
        $this->assertPlainPresentation((string) $view);
    }

    public function test_admin_correction_choices_match_the_case_evidence_source_and_keep_entered_values(): void
    {
        $learner = User::factory()->create(['role' => 'learner']);
        $this->actingAs($learner)->withSession(['_old_input' => [
            'allow_resubmission' => '0', 'requested_corrections' => ['identity_front'], 'correction_note' => 'Use a clear image.',
        ]]);
        $this->app['request']->setLaravelSession($this->app['session']->driver());
        $case = new LearnerIdentityVerification(['status' => 'pending', 'pathway' => 'adult', 'submission_round' => 1]);
        $case->id = 123;
        $case->setRelation('learner', $learner)->setRelation('evidence', collect())
            ->setRelation('personaInquiries', collect())->setRelation('audits', collect());

        foreach (['persona', 'persona_selfie', 'local'] as $source) {
            $case->forceFill(['evidence_source' => $source]);
            $view = $this->view('admin.parent-verifications.show-learner', ['case' => $case, 'reviewer' => null]);
            $view->assertSee('name="allow_resubmission"', false)->assertSee('name="correction_note"', false)
                ->assertSee('Use a clear image.')->assertSee('Record decision')->assertSee('Request changes');
            $html = (string) $view;
            preg_match_all('/name="requested_corrections\[\]" value="([^"]+)"/', $html, $matches);
            $this->assertSame($source === 'persona' ? ['persona_identity'] : ['identity_front', 'selfie'], $matches[1]);
            $this->assertMatchesRegularExpression('/<option value="0"[^>]*selected/', $html);
            $this->assertPlainPresentation($html);
        }
        $case->setRelation('evidence', collect([(object) ['slot' => 'identity_back']]));
        $view = $this->view('admin.parent-verifications.show-learner', ['case' => $case, 'reviewer' => null]);
        $view->assertSee('name="requested_corrections[]" value="identity_back"', false);
    }

    public function test_guardian_final_rejection_hides_retry_and_moderation_binds_the_current_attempt(): void
    {
        $guardian = User::factory()->create(['role' => 'learner', 'is_parent_registration' => true, 'parent_verification_status' => 'rejected']);
        $this->actingAs($guardian);
        foreach ([true, false] as $allowed) {
            $guardian->forceFill(['parent_identity_resubmission_allowed' => $allowed]);
            $view = $this->view('auth.parent-verification-status', ['user' => $guardian]);
            if ($allowed) {
                $view->assertSee('Resubmit Guardian Verification')->assertSee(route('guardian.verification.create'), false);
            } else {
                $view->assertSee('Contact support to continue.')->assertDontSee('Resubmit Guardian Verification')
                    ->assertDontSee('href="'.route('guardian.verification.create').'"', false);
            }
            $this->assertPlainPresentation((string) $view);
        }
        $guardian->parent_verification_status = 'pending';
        $oldAttempt = new PersonaInquiry(['attempt_number' => 1, 'flow_type' => PersonaInquiry::FLOW_GUARDIAN_GOVERNMENT, 'superseded_at' => now()]);
        $oldAttempt->id = 10;
        $currentAttempt = new PersonaInquiry(['attempt_number' => 2, 'flow_type' => PersonaInquiry::FLOW_GUARDIAN_GOVERNMENT]);
        $currentAttempt->id = 11;
        $guardian->forceFill(['parent_identity_evidence_source' => 'persona']);
        $guardian->setRelation('personaInquiries', collect([$oldAttempt, $currentAttempt]));
        $view = $this->view('admin.parent-verifications.show-parent', ['guardian' => $guardian, 'idTypeLabel' => 'Passport']);
        $view->assertSee('name="allow_resubmission"', false)->assertSee('name="persona_attempt_id" value="11"', false)
            ->assertSee('ID and selfie must be submitted again together.')
            ->assertSee('name="requested_corrections[]" value="persona_identity"', false)
            ->assertDontSee('name="requested_corrections[]" value="id"', false)
            ->assertDontSee('name="requested_corrections[]" value="selfie"', false);
        $this->assertMatchesRegularExpression('/<option value="1"[^>]*selected/', (string) $view);
        $this->assertPlainPresentation((string) $view);
    }

    public function test_admin_audit_views_hide_generated_environment_reasons_and_preserve_raw_history(): void
    {
        $guardian = User::factory()->create(['role' => 'learner', 'is_parent_registration' => true, 'parent_verification_status' => 'approved']);
        $guardian->setRelation('personaInquiries', collect());
        $this->actingAs($guardian);
        $reasons = [
            PersonaInquiry::SANDBOX_REVIEW_REASON,
            PersonaInquiry::SANDBOX_APPROVAL_REASON,
            str_replace('Persona ', 'Local ', PersonaInquiry::SANDBOX_REVIEW_REASON),
            str_replace('Persona ', 'Local ', PersonaInquiry::SANDBOX_APPROVAL_REASON),
            'Local '.PersonaInquiry::SANDBOX_REVIEW_REASON,
            'Local '.PersonaInquiry::SANDBOX_APPROVAL_REASON,
            'Document is unreadable. Please upload a clearer image.',
        ];
        $case = new LearnerIdentityVerification(['status' => 'approved', 'pathway' => 'adult']);
        $case->id = 123;
        $case->setRelation('learner', $guardian)->setRelation('evidence', collect())->setRelation('personaInquiries', collect());

        foreach (['learner' => LearnerIdentityAudit::class, 'parent' => GuardianIdentityAudit::class] as $type => $auditClass) {
            $audits = collect($reasons)->map(function ($reason, $index) use ($auditClass) {
                $audit = new $auditClass;
                $audit->setRawAttributes(['id' => $index + 1, 'reason' => $reason, 'action' => 'submitted'], true);
                $audit->setRelation('actor', null);

                return $audit;
            });
            $case->setRelation('audits', $audits);
            $guardian->setRelation('guardianIdentityAudits', $audits);
            $view = $this->view('admin.parent-verifications.show-'.$type, [
                'case' => $case, 'reviewer' => null, 'guardian' => $guardian, 'idTypeLabel' => 'Passport',
            ]);
            $view->assertSee('Submitted for administrator review')->assertSee('Approved after manual review')
                ->assertSee('Document is unreadable. Please upload a clearer image.');
            $this->assertPlainPresentation((string) $view);
            $this->assertSame($reasons, $audits->pluck('reason')->all());
            $this->assertTrue($audits->every(fn ($audit) => ! $audit->isDirty()));
        }
    }

    private function assertPlainPresentation(string $html): void
    {
        $visibleText = html_entity_decode(strip_tags($html));
        $this->assertDoesNotMatchRegularExpression('/sandbox|test.only|test.environment|local\s*mode|simulated/i', $visibleText);
    }
}
