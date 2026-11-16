<?php

namespace Tests\Unit;

use App\Http\Controllers\Admin\ParentChildVerificationController;
use App\Models\ParentChildAccount;
use App\Models\User;
use App\Services\GuardianRelationshipVerificationService;
use App\Services\ParentChildVerificationService;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Mockery;

class RelationshipApprovalChecklistTest extends TestCase
{
    public function test_approval_requires_each_review_confirmation_before_service_is_called(): void
    {
        $relationship = new ParentChildAccount;
        $relationship->relationship_verified_status = ParentChildAccount::VERIFICATION_UNDER_REVIEW;
        $service = Mockery::mock(GuardianRelationshipVerificationService::class);
        $service->shouldNotReceive('approve');
        $controller = new ParentChildVerificationController(Mockery::mock(ParentChildVerificationService::class), $service);

        foreach (['dependent_identity_confirmed', 'guardian_identity_confirmed', 'relationship_evidence_confirmed'] as $missing) {
            $payload = array_fill_keys(['dependent_identity_confirmed', 'guardian_identity_confirmed', 'relationship_evidence_confirmed'], '1');
            $payload['current_evidence_round'] = 1;
            $payload[$missing] = '0';
            try {
                $request = Request::create('/', 'POST', $payload, server: ['HTTP_ACCEPT' => 'application/json']);
                $request->setUserResolver(fn () => new User);
                $controller->approveRelationship($request, $relationship);
                $this->fail('Approval should reject an unchecked review confirmation.');
            } catch (ValidationException $exception) {
                $this->assertArrayHasKey($missing, $exception->errors());
            }
        }
    }
}
