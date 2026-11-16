<?php

namespace Tests\Unit;

use App\Http\Requests\Auth\StoreChildRelationshipVerificationRequest;
use App\Models\User;
use App\Support\NotificationDeepLinkResolver;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use Mockery;

class DependentRefinementsTest extends TestCase
{
    public function test_birth_record_can_support_biological_parentage_without_context(): void
    {
        session(['child_step1.relationship_type' => 'biological_mother']);
        $request = new StoreChildRelationshipVerificationRequest();
        $request->merge(['documents' => [['document_type' => 'dependent_birth_record', 'document_side' => 'not_applicable']], 'confirm_submission' => '1']);
        $data = $request->all();
        $data['documents'][0]['file'] = UploadedFile::fake()->create('record.pdf', 10, 'application/pdf');
        $validator = Validator::make($data, $request->rules());
        foreach ($request->after() as $callback) {
            $validator->after($callback);
        }
        $this->assertTrue($validator->passes(), json_encode($validator->errors()->toArray()));
    }

    public function test_admin_authority_notification_resolves_even_if_old_host_changed(): void
    {
        $admin = Mockery::mock(User::class)->makePartial();
        $admin->shouldReceive('isAdmin')->andReturnTrue();
        $resolver = app(NotificationDeepLinkResolver::class);
        $expected = route('admin.parent-verifications.relationships.show', 42, false);
        $this->assertSame($expected, $resolver->resolve(['type' => 'adult_authority_review_requested', 'parent_child_account_id' => 42], '/fallback', $admin));
        $this->assertSame($expected, $resolver->resolve(['type' => 'adult_authority_review_requested', 'action_url' => 'http://old-host/admin/parent-verifications/relationships/42'], '/fallback', $admin));
        $this->assertSame('/fallback', $resolver->resolve(['type' => 'adult_authority_review_requested', 'action_url' => 'http://old-host/other/42'], '/fallback', $admin));
    }
}
