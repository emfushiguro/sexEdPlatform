<?php

namespace Tests\Feature\Admin;

use App\Http\Requests\Admin\UpdateAdminCreatorProfileRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class UpdateAdminCreatorProfileRequestTest extends TestCase
{
    public function test_request_requires_display_name_and_affiliation(): void
    {
        $request = new UpdateAdminCreatorProfileRequest();
        $rules = $request->rules();

        $validator = Validator::make([
            'public_display_name' => '',
            'affiliation' => '',
        ], $rules);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('public_display_name', $validator->errors()->messages());
        $this->assertArrayHasKey('affiliation', $validator->errors()->messages());
    }

    public function test_request_validates_avatar_type_and_attribution_toggle(): void
    {
        $request = new UpdateAdminCreatorProfileRequest();
        $rules = $request->rules();

        $invalid = Validator::make([
            'public_display_name' => 'Creator Name',
            'affiliation' => 'Conscious Connections Team',
            'show_individual_attribution' => 'maybe',
            'avatar' => UploadedFile::fake()->create('not-image.pdf', 100, 'application/pdf'),
        ], $rules);

        $this->assertTrue($invalid->fails());
        $this->assertArrayHasKey('show_individual_attribution', $invalid->errors()->messages());
        $this->assertArrayHasKey('avatar', $invalid->errors()->messages());

        $valid = Validator::make([
            'public_display_name' => 'Creator Name',
            'affiliation' => 'Conscious Connections Team',
            'show_individual_attribution' => true,
            'avatar' => UploadedFile::fake()->create('avatar.jpg', 120, 'image/jpeg'),
        ], $rules);

        $this->assertFalse($valid->fails());
    }

    public function test_credentials_tab_requires_email_but_not_public_profile_fields(): void
    {
        $request = new UpdateAdminCreatorProfileRequest();

        $missingEmail = Validator::make([
            'profile_tab' => 'credentials',
        ], $request->rules());

        $this->assertTrue($missingEmail->fails());
        $this->assertArrayHasKey('email', $missingEmail->errors()->messages());
        $this->assertArrayNotHasKey('public_display_name', $missingEmail->errors()->messages());
        $this->assertArrayNotHasKey('affiliation', $missingEmail->errors()->messages());

        $validCredentialsOnly = Validator::make([
            'profile_tab' => 'credentials',
            'email' => 'admin.profile.owner@gmail.com',
        ], $request->rules());

        $this->assertFalse($validCredentialsOnly->fails());
    }

    public function test_credentials_tab_rejects_non_gmail_email(): void
    {
        $request = new UpdateAdminCreatorProfileRequest();

        $invalidCredentials = Validator::make([
            'profile_tab' => 'credentials',
            'email' => 'admin.owner@yahoo.com',
        ], $request->rules());

        $this->assertTrue($invalidCredentials->fails());
        $this->assertArrayHasKey('email', $invalidCredentials->errors()->messages());
    }
}
