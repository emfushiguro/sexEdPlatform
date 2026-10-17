<?php

namespace Tests\Feature\Connectors;

use App\Models\User;
use Tests\TestCase;

class ConnectorRegistrationStepperTest extends TestCase
{
    use ConnectorTestHelpers;

    public function test_registration_form_exposes_the_six_step_multipart_experience(): void
    {
        $this->seedCaviteAddress();

        $this->actingAs(User::factory()->create(['role' => 'learner']))
            ->get(route('connectors.register'))
            ->assertOk()
            ->assertViewHas('organizationTypes', fn (array $types): bool => array_keys($types) === \App\Support\ConnectorOptions::categoryKeys())
            ->assertSee('enctype="multipart/form-data"', false)
            ->assertSee('Step 1 of 6')
            ->assertSee('Step 5 of 6')
            ->assertSee('Step 6 of 6')
            ->assertSee('Organization type')
            ->assertSee('name="organization_type"', false)
            ->assertDontSee('name="category"', false)
            ->assertSee('value="government"', false)
            ->assertSee('value="ngo"', false)
            ->assertSee('Community-based Organization')
            ->assertSee('School / Educational Institution')
            ->assertSee('Health Organization')
            ->assertSee('Advocacy Group')
            ->assertSee('value="other"', false)
            ->assertSee('name="organization_type_detail"', false)
            ->assertDontSee('value="barangay_lgu"', false)
            ->assertSee('Organization logo')
            ->assertSee('name="logo"', false)
            ->assertSee('Organization evidence')
            ->assertSee('name="evidence[sec_nonstock_registration][files][]"', false)
            ->assertSee('Representative account')
            ->assertSee('name="confirm_accuracy"', false)
            ->assertSee('Cavite City / Municipality')
            ->assertSee('loadBarangays')
            ->assertSee('Back')
            ->assertSee('Continue')
            ->assertSee('Submit application')
            ->assertSee('aria-current="step"', false)
            ->assertSee('id="error-name"', false)
            ->assertDontSee('connector-verifications')
            ->assertDontSee('data-registration-modal', false);
    }

    public function test_evidence_is_scoped_to_the_selected_type_and_representative_documents_have_their_own_step(): void
    {
        $this->seedCaviteAddress();
        $user = User::factory()->create(['role' => 'learner']);

        $this->actingAs($user)
            ->get(route('connectors.register'))
            ->assertOk()
            ->assertSee('return this.availableRequirementKeys().includes(key);', false)
            ->assertDontSee('activeRequirementKeys:', false);

        $response = $this->withSession(['_old_input' => ['organization_type' => 'school_educational_institution']])
            ->get(route('connectors.register'))
            ->assertOk()
            ->assertViewHas('initialRequirementKeys', fn (array $keys): bool => $keys === [
                'other_organization_identity',
                'deped_authority',
                'ched_authority',
                'tesda_program_registration',
                'representative_identity',
                'representative_authority',
            ]);

        $html = $response->getContent();
        $this->assertSame(1, preg_match('/<fieldset data-step-panel="4".*?<\/fieldset>/s', $html, $organizationStep));
        $this->assertSame(1, preg_match('/<fieldset data-step-panel="5".*?<\/fieldset>/s', $html, $representativeStep));
        $this->assertSame(1, preg_match('/<fieldset data-step-panel="6".*?<\/fieldset>/s', $html, $confirmationStep));
        $this->assertStringNotContainsString('data-requirement-key="representative_identity"', $organizationStep[0]);
        $this->assertStringNotContainsString('data-requirement-key="representative_authority"', $organizationStep[0]);
        $this->assertStringContainsString('data-requirement-key="representative_identity"', $representativeStep[0]);
        $this->assertStringContainsString('data-requirement-key="representative_authority"', $representativeStep[0]);
        $this->assertStringContainsString('name="reuse_representative_identity"', $representativeStep[0]);
        $this->assertStringContainsString('name="confirm_accuracy"', $confirmationStep[0]);
        $this->assertStringNotContainsString('name="confirm_accuracy"', $representativeStep[0]);
    }

    public function test_representative_and_confirmation_errors_return_to_their_own_steps(): void
    {
        $this->seedCaviteAddress();
        $user = User::factory()->create(['role' => 'learner']);
        $payload = $this->connectorPayload(['organization_type' => 'ngo']);
        unset($payload['evidence']['representative_identity']);

        $this->from(route('connectors.register'))->actingAs($user)
            ->post(route('connectors.store'), $payload)
            ->assertSessionHasErrors('representative_identity.files');
        $this->get(route('connectors.register'))->assertOk()->assertSee('currentStep: 5', false);

        $this->from(route('connectors.register'))
            ->post(route('connectors.store'), $this->connectorPayload([
                'organization_type' => 'ngo',
                'confirm_accuracy' => '0',
            ]))
            ->assertSessionHasErrors('confirm_accuracy');
        $this->get(route('connectors.register'))->assertOk()->assertSee('currentStep: 6', false);
    }

    public function test_errors_reopen_the_earliest_step_that_needs_attention(): void
    {
        $this->seedCaviteAddress();
        $user = User::factory()->create(['role' => 'learner']);

        $this->from(route('connectors.register'))->actingAs($user)
            ->post(route('connectors.store'), ['organization_type' => 'invalid'])
            ->assertSessionHasErrors('organization_type');
        $this->get(route('connectors.register'))->assertOk()->assertSee('currentStep: 1', false);

        $this->from(route('connectors.register'))
            ->post(route('connectors.store'), $this->connectorPayload([
                'organization_type' => 'ngo',
                'evidence' => [],
            ]))
            ->assertSessionHasErrors(['evidence', 'sec_nonstock_registration.files', 'representative_identity.files']);
        $this->get(route('connectors.register'))->assertOk()->assertSee('currentStep: 4', false);
    }

    public function test_evidence_upload_uses_the_platform_attachment_pattern(): void
    {
        $this->seedCaviteAddress();

        $response = $this->actingAs(User::factory()->create(['role' => 'learner']))
            ->get(route('connectors.register'))
            ->assertOk()
            ->assertSee('data-evidence-dropzone', false)
            ->assertSee('name="evidence[representative_identity][files][]"', false)
            ->assertSee('Selected files')
            ->assertSee('Clear files')
            ->assertSee("clearEvidenceFiles('representative_identity', true)", false)
            ->assertSee('focus-within:ring-brand-100', false);

        $this->assertSame(1, preg_match('/<input\b(?=[^>]*data-requirement-file="representative_identity")[^>]*>/s', $response->getContent(), $representativeInput));
        $this->assertDoesNotMatchRegularExpression('/\sdisabled(?:\s|>)/', $representativeInput[0]);
        $this->assertStringNotContainsString('sr-only', $representativeInput[0]);
        $this->assertStringContainsString('file:bg-brand-50', $representativeInput[0]);
        $this->assertStringContainsString('To upload an identity document, turn off approved identity reuse.', $response->getContent());
    }

    public function test_organization_step_renders_all_configured_non_representative_evidence_groups(): void
    {
        $this->seedCaviteAddress();

        $response = $this->actingAs(User::factory()->create(['role' => 'learner']))
            ->get(route('connectors.register'));

        $response->assertOk();
        $this->assertSame(1, preg_match('/<fieldset data-step-panel="4".*?<\/fieldset>/s', $response->getContent(), $organizationStep));

        foreach (['activity_specific_authority', 'optional_regulatory_eligibility'] as $group) {
            $this->assertStringContainsString("isGroupVisible('{$group}')", $organizationStep[0]);
        }

        $this->assertStringContainsString('data-requirement-key="activity_specific_authority"', $organizationStep[0]);
        $this->assertStringContainsString('data-requirement-key="dswd_program_accreditation"', $organizationStep[0]);
    }

    public function test_other_type_error_returns_to_step_one_with_the_entered_detail(): void
    {
        $this->seedCaviteAddress();
        $user = User::factory()->create(['role' => 'learner']);

        $this->from(route('connectors.register'))
            ->actingAs($user)
            ->post(route('connectors.store'), [
                'organization_type' => 'other',
                'organization_type_detail' => '',
            ])
            ->assertSessionHasErrors('organization_type_detail');

        $this->get(route('connectors.register'))
            ->assertOk()
            ->assertSee('currentStep: 1', false)
            ->assertSee('value="other" selected', false)
            ->assertSee('id="error-organization_type_detail"', false);
    }

    public function test_server_validation_errors_return_to_named_step_fields_without_a_draft_endpoint(): void
    {
        $this->seedCaviteAddress();
        $user = User::factory()->create(['role' => 'learner']);

        $response = $this->from(route('connectors.register'))
            ->actingAs($user)
            ->post(route('connectors.store'), [
                'name' => '',
                'organization_type' => 'ngo',
                'confirm_accuracy' => '0',
                'evidence' => [],
            ]);

        $response->assertRedirect(route('connectors.register'))
            ->assertSessionHasErrors(['name', 'confirm_accuracy', 'evidence']);

        $this->get(route('connectors.register'))
            ->assertOk()
            ->assertSee('id="error-name"', false)
            ->assertSee('id="error-confirm_accuracy"', false)
            ->assertSee('id="error-evidence"', false)
            ->assertSee('value="ngo" selected', false)
            ->assertDontSee('/connector-registration/draft');
    }

    public function test_inline_stepper_selectors_are_safe_inside_the_html_data_attribute(): void
    {
        $this->seedCaviteAddress();

        $response = $this->actingAs(User::factory()->create(['role' => 'learner']))
            ->get(route('connectors.register'));

        $response->assertOk();

        foreach ([
            "querySelectorAll('[data-requirement-file]')",
            "querySelectorAll('[data-step-heading]')",
            "querySelectorAll('[data-step-panel]')",
        ] as $selector) {
            $this->assertMatchesRegularExpression(
                '/<div\\s+[^>]*x-data="[^"]*'.preg_quote($selector, '/').'[^"]*"[^>]*>/s',
                $response->getContent()
            );
        }
    }

    public function test_registration_form_uses_the_platform_connector_visual_language(): void
    {
        $this->seedCaviteAddress();

        $this->actingAs(User::factory()->create(['role' => 'learner']))
            ->get(route('connectors.register'))
            ->assertOk()
            ->assertSee('from-brand-500 via-brand-700 to-brand-900', false)
            ->assertSee('border-brand-100 bg-brand-50', false)
            ->assertSee('bg-brand-700', false)
            ->assertDontSee('border-cyan-100 bg-cyan-50', false)
            ->assertDontSee('border-b border-slate-200 bg-slate-950', false);
    }
}
