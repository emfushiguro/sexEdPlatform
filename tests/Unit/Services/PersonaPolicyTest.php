<?php

namespace Tests\Unit\Services;

use App\Models\LearnerIdentityVerification;
use App\Models\PersonaInquiry;
use App\Models\User;
use App\Services\Persona\PersonaPolicyEvaluator;
use App\Services\Persona\PersonaTemplateSelector;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PersonaPolicyTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-10-26 10:00:00');
        config([
            'persona.enabled' => true,
            'persona.environment' => 'sandbox',
            'persona.templates.selfie' => 'itmpl_selfie',
            'persona.templates.govid_selfie' => 'itmpl_govid',
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_selector_uses_server_owned_age_pathway_and_templates(): void
    {
        $selector = app(PersonaTemplateSelector::class);
        $teen = $this->learner(13);
        $teenCase = $this->case($teen, 'teen');
        $adult = $this->learner(18);
        $adultCase = $this->case($adult, 'adult');
        $guardian = User::factory()->create([
            'role' => 'learner',
            'birthdate' => Carbon::now()->subYears(30)->toDateString(),
            'is_parent_registration' => true,
        ]);

        $this->assertSame([
            'flow_type' => PersonaInquiry::FLOW_TEEN_SCHOOL,
            'template_key' => PersonaInquiry::TEMPLATE_SELFIE,
            'template_id' => 'itmpl_selfie',
            'local_document_type' => 'school_id',
            'local_government_id_type' => null,
        ], $selector->select($teen, $teenCase, 'school_id', null));
        $this->assertSame(PersonaInquiry::FLOW_TEEN_SCHOOL, $selector->select($teen, $teenCase, 'institution_id', null)['flow_type']);
        $this->assertSame(PersonaInquiry::FLOW_TEEN_GOVERNMENT, $selector->select($teen, $teenCase, 'government_id', 'passport')['flow_type']);
        $this->assertSame('itmpl_govid', $selector->select($adult, $adultCase, 'government_id', 'passport')['template_id']);
        $this->assertSame(PersonaInquiry::FLOW_GUARDIAN_GOVERNMENT, $selector->select($guardian, null, 'government_id', 'passport')['flow_type']);
    }

    public function test_selector_ignores_client_template_choices_and_rejects_invalid_contexts(): void
    {
        $selector = app(PersonaTemplateSelector::class);
        $teen = $this->learner(17);
        $teenCase = $this->case($teen, 'teen');

        $this->assertSame('itmpl_govid', $selector->select($teen, $teenCase, 'government_id', 'passport')['template_id']);

        foreach ([
            ['document_type' => 'government_id', 'government_id_type' => 'other'],
            ['document_type' => 'government_id', 'government_id_type' => 'senior_citizen_id'],
            ['document_type' => 'government_id', 'government_id_type' => 'pwd_id'],
            ['document_type' => 'government_id', 'government_id_type' => 'prc_id'],
            ['document_type' => 'school_id', 'government_id_type' => 'passport'],
            ['document_type' => 'unknown', 'government_id_type' => null],
        ] as $input) {
            try {
                $selector->select($teen, $teenCase, $input['document_type'], $input['government_id_type']);
                $this->fail('Expected invalid Persona context to be rejected.');
            } catch (ValidationException) {
                $this->addToAssertionCount(1);
            }
        }

        $underage = $this->learner(12);
        $this->expectException(ValidationException::class);
        $selector->select($underage, null, 'school_id', null);
    }

    public function test_selector_rejects_dependents_unrelated_roles_and_contradictory_cases(): void
    {
        $selector = app(PersonaTemplateSelector::class);

        foreach ([
            [User::factory()->create(['role' => 'instructor', 'birthdate' => '1990-01-01']), null],
            [User::factory()->create(['role' => 'organization', 'birthdate' => '1990-01-01']), null],
            [$this->learner(12), null],
        ] as [$user, $case]) {
            try {
                $selector->select($user, $case, 'government_id', 'passport');
                $this->fail('Expected unrelated Persona context to be rejected.');
            } catch (ValidationException) {
                $this->addToAssertionCount(1);
            }
        }

        $adult = $this->learner(18);
        $this->expectException(ValidationException::class);
        $selector->select($adult, $this->case($adult, 'teen'), 'government_id', 'passport');
    }

    public function test_missing_template_or_non_sandbox_configuration_fails_closed(): void
    {
        $selector = app(PersonaTemplateSelector::class);
        $user = $this->learner(15);
        $case = $this->case($user, 'teen');

        config(['persona.templates.selfie' => null]);
        try {
            $selector->select($user, $case, 'school_id', null);
            $this->fail('Expected blank template to be rejected.');
        } catch (ValidationException) {
            $this->addToAssertionCount(1);
        }

        config(['persona.environment' => 'production']);
        $this->expectException(ValidationException::class);
        $selector->select($user, $case, 'school_id', null);
    }

    public function test_school_selfie_completion_is_eligible_without_government_policy(): void
    {
        $attempt = $this->attempt(PersonaInquiry::FLOW_TEEN_SCHOOL, 'school_id', null);

        $result = app(PersonaPolicyEvaluator::class)->evaluate($attempt, $this->inquiryResponse());

        $this->assertSame(PersonaInquiry::OUTCOME_ELIGIBLE, $result['verification_outcome']);
        $this->assertSame(PersonaInquiry::POLICY_NOT_APPLICABLE, $result['country_policy_status']);
        $this->assertSame(PersonaInquiry::POLICY_NOT_APPLICABLE, $result['document_policy_status']);
        $this->assertSame('selfie_123', $result['selfie_verification_id']);
    }

    public function test_government_policy_requires_exact_philippine_class_and_passed_resources(): void
    {
        $attempt = $this->attempt(PersonaInquiry::FLOW_ADULT_GOVERNMENT, 'government_id', 'passport');
        $result = app(PersonaPolicyEvaluator::class)->evaluate($attempt, $this->inquiryResponse());

        $this->assertSame(PersonaInquiry::OUTCOME_ELIGIBLE, $result['verification_outcome']);
        $this->assertSame(PersonaInquiry::POLICY_ELIGIBLE, $result['country_policy_status']);
        $this->assertSame(PersonaInquiry::POLICY_ELIGIBLE, $result['document_policy_status']);
    }

    public function test_all_template_philippine_ids_can_be_selected_and_require_their_exact_class(): void
    {
        $expected = [
            'drivers_license' => 'dl', 'national_id' => 'id', 'passport' => 'pp',
            'philhealth' => 'hic', 'nbi_clearance' => 'nbi', 'ofw_id' => 'ofw',
            'postal_id' => 'pid', 'seafarers_id' => 'sid', 'sss_id' => 'sss',
            'tin_card' => 'tin', 'umid' => 'umid', 'voters_id' => 'vid',
        ];
        $this->assertSame($expected, config('persona.government_id_classes'));
        $learner = $this->learner(21);
        $case = $this->case($learner, 'adult');
        $guardian = User::factory()->create([
            'role' => 'learner', 'birthdate' => '1990-01-01', 'is_parent_registration' => true,
        ]);
        $selector = app(PersonaTemplateSelector::class);
        $policy = app(PersonaPolicyEvaluator::class);

        foreach ($expected as $type => $class) {
            $this->assertSame($type, $selector->select($learner, $case, 'government_id', $type)['local_government_id_type']);
            $this->assertSame($type, $selector->select($guardian, null, 'government_id', $type)['local_government_id_type']);
            $attempt = $this->attempt(PersonaInquiry::FLOW_ADULT_GOVERNMENT, 'government_id', $type);
            $this->assertSame(PersonaInquiry::OUTCOME_ELIGIBLE, $policy->evaluate($attempt, $this->inquiryResponse('PH', $class))['verification_outcome']);
            foreach ([['US', $class], ['PH', $class === 'pp' ? 'id' : 'pp'], ['PH', 'other'], ['PH', null]] as [$country, $wrongClass]) {
                $this->assertNotSame(PersonaInquiry::OUTCOME_ELIGIBLE, $policy->evaluate($attempt, $this->inquiryResponse($country, $wrongClass))['verification_outcome']);
            }
        }
    }

    public function test_shared_persona_selector_lists_all_twelve_template_choices_only(): void
    {
        $view = $this->view('auth.partials.persona-identity-step', [
            'documentType' => 'government_id', 'governmentIdType' => null,
            'errors' => new \Illuminate\Support\ViewErrorBag,
        ]);
        foreach ([
            'drivers_license', 'national_id', 'passport', 'philhealth', 'nbi_clearance', 'ofw_id',
            'postal_id', 'seafarers_id', 'sss_id', 'tin_card', 'umid', 'voters_id',
        ] as $type) {
            $view->assertSee('value="'.$type.'"', false);
            $view->assertSee(config('guardian_identity.id_types.'.$type.'.label'));
        }
        foreach (['senior_citizen_id', 'pwd_id', 'prc_id', 'other'] as $type) {
            $view->assertDontSee('value="'.$type.'"', false);
        }
    }

    /** @dataProvider ineligibleGovernmentResults */
    public function test_government_policy_fails_closed(
        ?string $country,
        ?string $idClass,
        string $governmentStatus,
        string $selfieStatus,
        string $inquiryStatus,
        bool $includeGovernment,
        bool $includeSelfie,
    ): void {
        $result = app(PersonaPolicyEvaluator::class)->evaluate(
            $this->attempt(PersonaInquiry::FLOW_ADULT_GOVERNMENT, 'government_id', 'passport'),
            $this->inquiryResponse($country, $idClass, $governmentStatus, $selfieStatus, $inquiryStatus, $includeGovernment, $includeSelfie),
        );

        $this->assertNotSame(PersonaInquiry::OUTCOME_ELIGIBLE, $result['verification_outcome']);
    }

    public static function ineligibleGovernmentResults(): array
    {
        return [
            'foreign country' => ['US', 'pp', 'passed', 'passed', 'completed', true, true],
            'missing country' => [null, 'pp', 'passed', 'passed', 'completed', true, true],
            'missing class' => ['PH', null, 'passed', 'passed', 'completed', true, true],
            'generic class' => ['PH', 'id', 'passed', 'passed', 'completed', true, true],
            'mismatched class' => ['PH', 'dl', 'passed', 'passed', 'completed', true, true],
            'missing government resource' => ['PH', 'pp', 'passed', 'passed', 'completed', false, true],
            'missing selfie resource' => ['PH', 'pp', 'passed', 'passed', 'completed', true, false],
            'failed government resource' => ['PH', 'pp', 'failed', 'passed', 'completed', true, true],
            'incomplete inquiry' => ['PH', 'pp', 'passed', 'passed', 'pending', true, true],
            'expired inquiry' => ['PH', 'pp', 'passed', 'passed', 'expired', true, true],
        ];
    }

    private function learner(int $age): User
    {
        $user = User::factory()->create([
            'role' => 'learner',
            'birthdate' => Carbon::now()->subYears($age)->toDateString(),
        ]);
        $user->assignRole('learner');

        return $user;
    }

    private function case(User $user, string $pathway): LearnerIdentityVerification
    {
        return LearnerIdentityVerification::query()->create([
            'user_id' => $user->id,
            'pathway' => $pathway,
            'submission_round' => 0,
        ]);
    }

    private function attempt(string $flow, string $documentType, ?string $governmentIdType): PersonaInquiry
    {
        return new PersonaInquiry([
            'flow_type' => $flow,
            'local_document_type' => $documentType,
            'local_government_id_type' => $governmentIdType,
            'verification_outcome' => PersonaInquiry::OUTCOME_NOT_READY,
            'is_sandbox' => true,
        ]);
    }

    private function inquiryResponse(
        ?string $country = 'PH',
        ?string $idClass = 'pp',
        string $governmentStatus = 'passed',
        string $selfieStatus = 'passed',
        string $inquiryStatus = 'completed',
        bool $includeGovernment = true,
        bool $includeSelfie = true,
    ): array {
        $references = [];
        $included = [];
        if ($includeGovernment) {
            $references[] = ['id' => 'gov_123', 'type' => 'verification/government-id'];
            $included[] = [
                'id' => 'gov_123',
                'type' => 'verification/government-id',
                'attributes' => [
                    'status' => $governmentStatus,
                    'country-code' => $country,
                    'id-class' => $idClass,
                ],
            ];
        }
        if ($includeSelfie) {
            $references[] = ['id' => 'selfie_123', 'type' => 'verification/selfie'];
            $included[] = [
                'id' => 'selfie_123',
                'type' => 'verification/selfie',
                'attributes' => ['status' => $selfieStatus],
            ];
        }

        return [
            'data' => [
                'id' => 'inq_123',
                'type' => 'inquiry',
                'attributes' => [
                    'status' => $inquiryStatus,
                    'completed-at' => $inquiryStatus === 'completed' ? '2026-10-26T09:00:00Z' : null,
                    'expired-at' => $inquiryStatus === 'expired' ? '2026-10-26T09:00:00Z' : null,
                ],
                'relationships' => [
                    'verifications' => ['data' => $references],
                ],
            ],
            'included' => $included,
        ];
    }
}
