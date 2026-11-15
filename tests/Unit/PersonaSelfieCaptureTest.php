<?php

namespace Tests\Unit;

use App\Models\PersonaInquiry;
use App\Services\Persona\PersonaPolicyEvaluator;
use Illuminate\Foundation\Testing\TestCase;

class PersonaSelfieCaptureTest extends TestCase
{
    public function test_server_reported_upload_or_failed_liveness_cannot_become_eligible(): void
    {
        foreach ([['capture-method' => 'upload'], ['checks' => [['name' => 'selfie_liveness_detection', 'status' => 'failed']]]] as $metadata) {
            $attempt = new PersonaInquiry(['flow_type' => PersonaInquiry::FLOW_TEEN_SCHOOL, 'local_document_type' => 'assisted_review']);
            $result = app(PersonaPolicyEvaluator::class)->evaluate($attempt, [
                'data' => ['attributes' => ['status' => 'completed']],
                'included' => [['id' => 'selfie_test', 'type' => 'verification/selfie', 'attributes' => ['status' => 'passed', ...$metadata]]],
            ]);
            $this->assertSame(PersonaInquiry::OUTCOME_INELIGIBLE, $result['verification_outcome']);
        }
    }

    public function test_passed_provider_liveness_and_camera_capture_are_eligible(): void
    {
        $attempt = new PersonaInquiry(['flow_type' => PersonaInquiry::FLOW_TEEN_SCHOOL, 'local_document_type' => 'assisted_review']);
        $result = app(PersonaPolicyEvaluator::class)->evaluate($attempt, [
            'data' => ['attributes' => ['status' => 'completed']],
            'included' => [['id' => 'selfie_test', 'type' => 'verification/selfie', 'attributes' => [
                'status' => 'passed', 'capture-method' => 'photo',
                'checks' => [['name' => 'selfie_liveness_detection', 'status' => 'passed']],
            ]]],
        ]);
        $this->assertSame(PersonaInquiry::OUTCOME_ELIGIBLE, $result['verification_outcome']);
    }

    public function test_missing_liveness_cannot_establish_live_learner_capture(): void
    {
        $attempt = new PersonaInquiry(['flow_type' => PersonaInquiry::FLOW_TEEN_SCHOOL, 'local_document_type' => 'assisted_review']);
        $result = app(PersonaPolicyEvaluator::class)->evaluate($attempt, [
            'data' => ['attributes' => ['status' => 'completed']],
            'included' => [['id' => 'selfie_test', 'type' => 'verification/selfie', 'attributes' => ['status' => 'passed']]],
        ]);
        $this->assertSame(PersonaInquiry::OUTCOME_INCONCLUSIVE, $result['verification_outcome']);
    }
}
