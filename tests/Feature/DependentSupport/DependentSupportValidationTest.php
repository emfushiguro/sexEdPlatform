<?php

namespace Tests\Feature\DependentSupport;

use App\Http\Requests\DependentSupport\StoreDependentSupportInformationRequest;
use App\Http\Requests\DependentSupport\UpdateGuardianSupportAccessRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class DependentSupportValidationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware('web')->post('/support-test', function (StoreDependentSupportInformationRequest $request) {
            return response()->json($request->supportPayload());
        });

        Route::middleware('web')->post('/support-access-test', function (UpdateGuardianSupportAccessRequest $request) {
            return response()->json(['enabled' => $request->enabled()]);
        });
    }

    public function test_valid_payload_is_trimmed_and_contains_only_content_fields(): void
    {
        $response = $this->actingAs(User::factory()->create())->post('/support-test', [
            'has_relevant_support_information' => '1',
            'relevant_health_considerations' => '  Synthetic participation consideration  ',
            'accessibility_support_needs' => '',
            'additional_relevant_information' => 'Synthetic additional context',
            'purpose_acknowledged' => '1',
            'expected_updated_at' => '2026-09-10 10:20:30',
            'unexpected' => 'ignored',
        ]);

        $response->assertOk()->assertJson([
            'relevant_health_considerations' => 'Synthetic participation consideration',
            'accessibility_support_needs' => null,
            'additional_relevant_information' => 'Synthetic additional context',
        ]);
        $this->assertSame([
            'relevant_health_considerations',
            'accessibility_support_needs',
            'additional_relevant_information',
        ], array_keys($response->json()));
    }

    public function test_each_content_field_can_be_submitted_independently(): void
    {
        foreach ([
            'relevant_health_considerations',
            'accessibility_support_needs',
            'additional_relevant_information',
        ] as $field) {
            $response = $this->actingAs(User::factory()->create())->post('/support-test', [
                'has_relevant_support_information' => '1',
                $field => 'Synthetic support text',
                'purpose_acknowledged' => '1',
            ]);

            $response->assertOk();
        }
    }

    public function test_yes_requires_acknowledgement_and_at_least_one_non_blank_content_field(): void
    {
        $dependent = User::factory()->create();
        $response = $this->actingAs($dependent)->from('/support-test')->post('/support-test', [
            'has_relevant_support_information' => '1',
            'relevant_health_considerations' => '   ',
            'accessibility_support_needs' => "\t",
            'additional_relevant_information' => '',
        ]);

        $response->assertRedirect('/support-test')
            ->assertSessionHasErrors(['purpose_acknowledged', 'has_relevant_support_information']);
    }

    public function test_invalid_shapes_html_documents_and_oversized_values_are_rejected_without_sensitive_flash(): void
    {
        $dependent = User::factory()->create();
        $response = $this->actingAs($dependent)->from('/support-test')->post('/support-test', [
            'has_relevant_support_information' => '1',
            'relevant_health_considerations' => ['not' => ['text' => 'object-shaped']],
            'accessibility_support_needs' => 'Synthetic support text',
            'additional_relevant_information' => 'Synthetic private marker',
            'purpose_acknowledged' => '1',
            'medical_document' => 'file.pdf',
            'documents' => UploadedFile::fake()->create('support.pdf', 10, 'application/pdf'),
            'expected_updated_at' => 'not-a-timestamp',
            'unexpected' => str_repeat('x', 1001),
        ]);

        $response->assertRedirect('/support-test')
            ->assertSessionHasErrors([
                'relevant_health_considerations',
                'medical_document',
                'documents',
                'expected_updated_at',
            ]);
        $response->assertSessionMissing('_old_input.relevant_health_considerations');
        $response->assertSessionMissing('_old_input.accessibility_support_needs');
        $response->assertSessionMissing('_old_input.additional_relevant_information');
        $this->assertStringNotContainsString('Synthetic private marker', serialize(session()->all()));

        $htmlResponse = $this->actingAs($dependent)->from('/support-test')->post('/support-test', [
            'has_relevant_support_information' => '1',
            'relevant_health_considerations' => '<b>Synthetic</b>',
            'purpose_acknowledged' => '1',
        ]);

        $htmlResponse->assertSessionHasErrors('relevant_health_considerations');

        $oversizedResponse = $this->actingAs($dependent)->from('/support-test')->post('/support-test', [
            'has_relevant_support_information' => '1',
            'relevant_health_considerations' => str_repeat('x', 1001),
            'purpose_acknowledged' => '1',
        ]);

        $oversizedResponse->assertSessionHasErrors('relevant_health_considerations');
    }

    public function test_no_selection_is_valid_and_returns_null_content(): void
    {
        $response = $this->actingAs(User::factory()->create())->post('/support-test', [
            'has_relevant_support_information' => '0',
        ]);

        $response->assertOk()->assertJson([
            'relevant_health_considerations' => null,
            'accessibility_support_needs' => null,
            'additional_relevant_information' => null,
        ]);
    }

    public function test_guardian_access_request_normalizes_boolean_and_rejects_missing_value(): void
    {
        $dependent = User::factory()->create();
        $this->actingAs($dependent)
            ->post('/support-access-test', ['enabled' => '1'])
            ->assertOk()
            ->assertJson(['enabled' => true]);

        $this->actingAs($dependent)
            ->from('/support-access-test')
            ->post('/support-access-test', [])
            ->assertRedirect('/support-access-test')
            ->assertSessionHasErrors('enabled');
    }
}
