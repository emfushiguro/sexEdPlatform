<?php

namespace Tests\Feature\Connectors;

use App\Models\User;
use Tests\TestCase;

class ConnectorRegistrationStepperTest extends TestCase
{
    use ConnectorTestHelpers;

    public function test_registration_form_exposes_the_five_step_multipart_experience(): void
    {
        $this->seedCaviteAddress();

        $this->actingAs(User::factory()->create(['role' => 'learner']))
            ->get(route('connectors.register'))
            ->assertOk()
            ->assertSee('enctype="multipart/form-data"', false)
            ->assertSee('Step 1 of 5')
            ->assertSee('Organization type')
            ->assertSee('name="organization_type"', false)
            ->assertSee('Organization logo')
            ->assertSee('name="logo"', false)
            ->assertSee('Verification evidence')
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

    public function test_server_validation_errors_return_to_named_step_fields_without_a_draft_endpoint(): void
    {
        $this->seedCaviteAddress();
        $user = User::factory()->create(['role' => 'learner']);

        $response = $this->from(route('connectors.register'))
            ->actingAs($user)
            ->post(route('connectors.store'), [
                'name' => '',
                'category' => 'ngo',
                'organization_type' => 'ngo_nonprofit_foundation',
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
            ->assertDontSee('/connector-registration/draft');
    }
}
