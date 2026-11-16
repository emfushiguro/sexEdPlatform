<?php
namespace Tests\Unit;

use App\Http\Requests\Admin\SaveIdentityReferenceRequest;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;

class IdentityReferenceRequestTest extends TestCase
{
    public function test_guardian_document_types_can_open_the_reference_editor(): void
    {
        $controller = new \App\Http\Controllers\Admin\IdentityReferenceController();
        $label = new \ReflectionMethod($controller, 'label');
        foreach (config('guardian_relationships.document_types') as $type => $name) {
            $this->assertNotEmpty($label->invoke($controller, $type));
        }
    }
    public function test_official_source_and_safe_images_are_required_for_new_examples(): void
    {
        $request = new SaveIdentityReferenceRequest();
        $valid = ['issuer'=>'PhilHealth', 'version'=>'2016 PVC', 'published_on'=>'2016-08-01', 'source_url'=>'https://www.philhealth.gov.ph/example', 'image'=>new UploadedFile(public_path('media/Logo.png'), 'sample.png', 'image/png', null, true)];
        $this->assertTrue(Validator::make($valid, $request->rules())->passes());
        $this->assertFalse(Validator::make(array_replace($valid, ['source_url'=>'javascript:alert(1)']), $request->rules())->passes());
        $this->assertFalse(Validator::make(array_replace($valid, ['image'=>UploadedFile::fake()->create('bad.svg', 1, 'image/svg+xml')]), $request->rules())->passes());
    }
    public function test_reference_metadata_may_be_blank_but_invalid_metadata_is_rejected(): void
    {
        $rules = (new SaveIdentityReferenceRequest())->rules();
        $metadata = array_intersect_key($rules, array_flip(['issuer', 'version', 'published_on']));
        $this->assertTrue(Validator::make(['issuer' => null, 'version' => null, 'published_on' => null], $metadata)->passes());
        $this->assertFalse(Validator::make(['published_on' => '2999-01-01'], $metadata)->passes());
        $this->assertFalse(Validator::make(['issuer' => str_repeat('x', 121)], $metadata)->passes());
    }

    public function test_birth_record_guidance_links_to_official_sources(): void
    {
        $guide = config('identity_reference_guides.dependent_birth_record');
        $this->assertIsArray($guide);
        $this->assertSame('https://psa.gov.ph/birth-certificate', $guide['source']);
        $this->assertSame('https://elibrary.judiciary.gov.ph/thebookshelf/showdocs/2/100958', $guide['rules_url']);
    }

    public function test_new_reference_fields_keep_metadata_blank_and_require_the_attachment(): void
    {
        $html = view('admin.parent-verifications.partials.identity-reference-fields', ['record' => null, 'type' => 'dependent_birth_record'])->render();
        $document = new \DOMDocument;
        @$document->loadHTML($html);
        $fields = new \DOMXPath($document);
        foreach (['issuer', 'version', 'published_on'] as $name) {
            $input = $fields->query('//input[@name="'.$name.'"]')->item(0);
            $this->assertNotNull($input);
            $this->assertFalse($input->hasAttribute('required'));
        }
        $this->assertTrue($fields->query('//input[@name="image"]')->item(0)->hasAttribute('required'));
        $this->assertStringNotContainsString('(optional', $html);
    }

    public function test_new_instructor_and_guardian_choices_use_only_the_seven_approved_ids(): void
    {
        $expected = ['philhealth','national_id','umid','nbi_clearance','postal_id','drivers_license','tin_card'];
        $this->assertSame($expected, config('guardian_identity.accepted_id_types'));
        $instructor = new \App\Http\Requests\InstructorApplication\StartPersonaInquiryRequest();
        foreach ($expected as $type) {
            $this->assertTrue(Validator::make(['government_id_type'=>$type], $instructor->rules())->passes(), $type);
        }
        $this->assertFalse(Validator::make(['government_id_type'=>'passport'], $instructor->rules())->passes());
        $guardian = new \App\Http\Requests\Auth\StoreGuardianIdentityVerificationRequest();
        $rules = ['government_id_type' => $guardian->rules()['government_id_type']];
        $this->assertFalse(Validator::make(['government_id_type'=>'passport'], $rules)->passes());
        $this->assertTrue(Validator::make(['government_id_type'=>'tin_card'], $rules)->passes());
        $this->assertSame('Passport', config('guardian_identity.id_types.passport.label'));
    }
    public function test_reference_routes_require_admin_mfa_and_reference_link_opens_in_new_tab(): void
    {
        foreach (['index','show','store','update','image'] as $action) {
            $route = app('router')->getRoutes()->getByName('admin.identity-references.'.$action);
            $this->assertNotNull($route);
            $this->assertContains('role:admin', $route->gatherMiddleware());
            $this->assertContains('admin.mfa', $route->gatherMiddleware());
        }
        $html = view('admin.parent-verifications.partials.id-reference', ['idType'=>'philhealth', 'idLabel'=>'PhilHealth ID'])->render();
        $this->assertStringContainsString('View reference images', $html);
        $this->assertStringContainsString('target="_blank"', $html);
    }
}
