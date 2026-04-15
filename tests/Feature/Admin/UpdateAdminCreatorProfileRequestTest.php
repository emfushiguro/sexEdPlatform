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
}
