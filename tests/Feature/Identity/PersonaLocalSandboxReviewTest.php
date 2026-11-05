<?php

namespace Tests\Feature\Identity;

use App\Models\PersonaInquiry;
use App\Models\User;
use App\Services\Identity\LearnerIdentityRequirement;
use App\Services\Identity\LearnerIdentityReview;
use App\Services\ParentChildVerificationService;
use App\Services\Persona\PersonaInquirySynchronizer;
use App\Services\Persona\PersonaPolicyEvaluator;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PersonaLocalSandboxReviewTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->app['env'] = 'local';
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
        Notification::fake();
        Storage::fake('local');
        config([
            'persona.enabled' => true, 'persona.environment' => 'sandbox',
            'persona.api_key' => 'test-only', 'persona.local_manual_review' => true,
        ]);
        Http::preventStrayRequests();
    }

    public function test_completed_us_sample_submits_both_account_types_once_without_claiming_ph_eligibility(): void
    {
        foreach ([false, true] as $guardian) {
            $attempt = $this->attempt($guardian);
            $this->fakePersona();
            $sync = app(PersonaInquirySynchronizer::class);
            $sync->sync($attempt);
            $result = $attempt->fresh();
            $this->assertSame('sandbox_review', $result->verification_outcome);
            $this->assertSame('us', $result->country_code);
            $this->assertSame('dl', $result->id_class);
            $this->assertSame('ineligible', $result->country_policy_status);
            $this->assertSame('ineligible', $result->document_policy_status);
            $this->assertFalse($result->isPolicyEligible());
            $subject = $guardian ? $attempt->user->fresh() : $attempt->learnerVerification->fresh();
            $this->assertSame('pending', $guardian ? $subject->parent_verification_status : $subject->status);
            $this->assertTrue($guardian ? $subject->parent_identity_sandbox_manual_review : $subject->sandbox_manual_review);
            $sync->sync($result);
            $audits = $guardian ? $subject->guardianIdentityAudits() : $subject->audits();
            $this->assertSame(1, $audits->where('action', 'submitted')->count());
            $this->assertStringContainsString('Persona Sandbox', (string) $audits->first()->reason);
            $this->actingAs($attempt->user)->get(route($guardian ? 'guardian.verification.create' : 'learner.identity.create'))
                ->assertRedirect(route($guardian ? 'guardian.verification.status' : 'learner.identity.status'));
        }
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_exception_requires_an_explicit_local_enabled_sandbox_configuration(): void
    {
        $attempt = $this->attempt();
        foreach ([
            ['local', false, true, 'sandbox', true],
            ['production', true, true, 'sandbox', true],
            ['staging', true, true, 'sandbox', true],
            ['testing', true, true, 'sandbox', true],
            ['local', true, false, 'sandbox', true],
            ['local', true, true, 'production', true],
            ['local', true, true, 'sandbox', false],
        ] as [$environment, $enabled, $personaEnabled, $personaEnvironment, $isSandbox]) {
            $this->app['env'] = $environment;
            config(['persona.local_manual_review' => $enabled, 'persona.enabled' => $personaEnabled, 'persona.environment' => $personaEnvironment]);
            $attempt->is_sandbox = $isSandbox;
            $result = app(PersonaPolicyEvaluator::class)->evaluate($attempt, $this->response());
            $this->assertSame('ineligible', $result['verification_outcome']);
        }
    }

    public function test_deployed_demo_requires_an_explicit_opt_in(): void
    {
        $attempt = $this->attempt();
        foreach ([
            ['staging', false, true, true, 'sandbox', true, false],
            ['staging', true, false, true, 'sandbox', true, true],
            ['staging', true, true, false, 'sandbox', true, false],
            ['staging', true, true, true, 'production', true, false],
            ['staging', true, true, true, 'sandbox', false, false],
            ['production', false, true, true, 'sandbox', true, false],
            ['production', true, false, true, 'sandbox', true, true],
            ['production', true, true, false, 'sandbox', true, false],
            ['production', true, true, true, 'production', true, false],
            ['production', true, true, true, 'sandbox', false, false],
            ['testing', true, true, true, 'sandbox', true, false],
            ['local', true, false, true, 'sandbox', true, false],
            ['local', false, true, true, 'sandbox', true, true],
        ] as [$environment, $demoEnabled, $localEnabled, $personaEnabled, $personaEnvironment, $isSandbox, $expected]) {
            $this->app['env'] = $environment;
            config([
                'persona.demo_manual_review' => $demoEnabled,
                'persona.local_manual_review' => $localEnabled,
                'persona.enabled' => $personaEnabled,
                'persona.environment' => $personaEnvironment,
            ]);
            $attempt->is_sandbox = $isSandbox;
            $result = app(PersonaPolicyEvaluator::class)->evaluate($attempt, $this->response());
            $this->assertSame($expected ? 'sandbox_review' : 'ineligible', $result['verification_outcome'], $environment);
        }
    }

    public function test_deployed_demo_supports_submission_approval_and_access_only_while_enabled(): void
    {
        config(['persona.local_manual_review' => false, 'persona.demo_manual_review' => true]);
        $admin = $this->admin();
        foreach (['staging', 'production'] as $environment) {
            foreach ([false, true] as $guardian) {
                $this->app['env'] = $environment;
                $attempt = $this->attempt($guardian);
                $this->fakePersona();
                app(PersonaInquirySynchronizer::class)->sync($attempt);
                $attempt->refresh();
                $this->assertSame('sandbox_review', $attempt->verification_outcome);
                $this->assertSame('us', $attempt->country_code);
                $this->assertFalse($attempt->isPolicyEligible());
                $subject = $guardian ? $attempt->user->fresh() : $attempt->learnerVerification->fresh();
                $this->assertSame('pending', $guardian ? $subject->parent_verification_status : $subject->status);
                $route = $guardian ? 'admin.parent-verifications.parents.approve' : 'admin.parent-verifications.learners.approve';
                $data = ['submission_round' => 1, 'persona_attempt_id' => $attempt->id,
                    'sandbox_philippine_id_confirmed' => '1', 'sandbox_id_type_confirmed' => '1', 'persona_portrait_compared' => '1'];
                $this->actingAs($admin)->postJson(route($route, $subject), [])->assertUnprocessable();
                $this->post(route($route, $subject), $data)->assertRedirect();
                $subject->refresh();
                $this->assertSame('approved', $guardian ? $subject->parent_verification_status : $subject->status);
                $accessRoute = $guardian ? 'guardian.onboarding.show' : 'profile.complete';
                $statusRoute = $guardian ? 'guardian.verification.status' : 'learner.identity.status';
                $this->actingAs($attempt->user->fresh())->get(route($accessRoute))->assertOk();
                config(['persona.demo_manual_review' => false]);
                $this->get(route($accessRoute))->assertRedirect(route($statusRoute));
                config(['persona.demo_manual_review' => true]);
                $this->app['env'] = 'testing';
                $this->get(route($accessRoute))->assertRedirect(route($statusRoute));
            }
        }
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_existing_completed_mismatch_can_be_rechecked_without_new_uploads(): void
    {
        $attempt = $this->attempt();
        config(['persona.local_manual_review' => false]);
        $this->fakePersona();
        app(PersonaInquirySynchronizer::class)->sync($attempt);
        $this->assertSame('ineligible', $attempt->fresh()->verification_outcome);
        config(['persona.local_manual_review' => true]);
        $this->actingAs($attempt->user)->get(route('learner.identity.create'))
            ->assertOk()->assertSee('Recheck existing Persona evidence');
        $this->post(route('persona.identity.refresh'))->assertRedirect(route('learner.identity.status'));
        $this->assertSame('pending', $attempt->learnerVerification->fresh()->status);
        $this->assertSame(1, PersonaInquiry::count());
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_matching_ph_result_keeps_normal_eligibility_when_local_fallback_is_enabled(): void
    {
        $result = app(PersonaPolicyEvaluator::class)->evaluate($this->attempt(), $this->response(country: 'PH', class: 'id'));
        $this->assertSame('eligible', $result['verification_outcome']);
        $this->assertSame('eligible', $result['country_policy_status']);
        $this->assertSame('eligible', $result['document_policy_status']);
    }

    public function test_failed_incomplete_and_missing_resource_results_remain_blocked(): void
    {
        $attempt = $this->attempt();
        foreach ([
            ['declined', 'passed', 'passed', 'US', 'dl'],
            ['failed', 'passed', 'passed', 'US', 'dl'],
            ['expired', 'passed', 'passed', 'US', 'dl'],
            ['needs-review', 'passed', 'passed', 'US', 'dl'],
            ['pending', 'passed', 'passed', 'US', 'dl'],
            ['completed', 'failed', 'passed', 'US', 'dl'],
            ['completed', 'passed', 'failed', 'US', 'dl'],
            ['completed', 'passed', 'passed', null, 'dl'],
            ['completed', 'passed', 'passed', 'US', null],
        ] as [$status, $gov, $selfie, $country, $class]) {
            $response = $this->response($status, $gov, $selfie, $country, $class);
            $this->assertNotSame('sandbox_review', app(PersonaPolicyEvaluator::class)->evaluate($attempt, $response)['verification_outcome']);
        }
        foreach ([0, 1] as $missing) {
            $response = $this->response();
            unset($response['included'][$missing], $response['data']['relationships']['verifications']['data'][$missing]);
            $this->assertNotSame('sandbox_review', app(PersonaPolicyEvaluator::class)->evaluate($attempt, $response)['verification_outcome']);
        }
        $attempt->local_government_id_type = 'other';
        $this->assertNotSame('sandbox_review', app(PersonaPolicyEvaluator::class)->evaluate($attempt, $this->response())['verification_outcome']);
    }

    public function test_administrator_must_confirm_all_three_checks_for_each_account_type(): void
    {
        $admin = $this->admin();
        foreach ([false, true] as $guardian) {
            $attempt = $this->attempt($guardian);
            $this->fakePersona();
            app(PersonaInquirySynchronizer::class)->sync($attempt);
            $subject = $guardian ? $attempt->user->fresh() : $attempt->learnerVerification->fresh();
            $route = $guardian ? 'admin.parent-verifications.parents.approve' : 'admin.parent-verifications.learners.approve';
            $this->actingAs($admin);
            if ($guardian) {
                $this->get(route('admin.parent-verifications.parents.show', $subject))->assertOk()
                    ->assertSee('name="persona_attempt_id" value="'.$attempt->id.'"', false);
            }
            $data = ['submission_round' => 1, 'persona_attempt_id' => $attempt->id, 'sandbox_philippine_id_confirmed' => '1', 'sandbox_id_type_confirmed' => '1', 'persona_portrait_compared' => '1'];
            foreach (['sandbox_philippine_id_confirmed', 'sandbox_id_type_confirmed', 'persona_portrait_compared'] as $field) {
                $incomplete = $data;
                unset($incomplete[$field]);
                $this->postJson(route($route, $subject), $incomplete)->assertUnprocessable();
            }
            $this->post(route($route, $subject), $data)->assertRedirect();
            $subject->refresh();
            $this->assertSame('approved', $guardian ? $subject->parent_verification_status : $subject->status);
            $audits = $guardian ? $subject->guardianIdentityAudits() : $subject->audits();
            $this->assertStringContainsString('Persona Sandbox', (string) $audits->where('action', 'approved')->first()->reason);
            if ($guardian) {
                $this->assertTrue($subject->isParentVerificationApproved());
                $this->actingAs($subject)->get(route('guardian.onboarding.show'))->assertOk()->assertSee('Your Guardian account is verified.')->assertDontSee('test-only approval');
                $this->app['env'] = 'production';
                $this->assertFalse($subject->isParentVerificationApproved());
                $this->actingAs($subject)->get(route('learner.dashboard'))->assertRedirect(route('guardian.verification.status'));
            } else {
                $this->actingAs($attempt->user)->get(route('profile.complete'))->assertOk()->assertSee('Complete Your Profile')->assertDontSee('Sandbox');
                $this->app['env'] = 'production';
                $this->get(route('profile.complete'))->assertRedirect(route('learner.identity.status'));
            }
            $this->app['env'] = 'local';
        }
    }

    public function test_direct_service_approval_cannot_bypass_manual_confirmations(): void
    {
        $this->actingAs($this->admin());
        foreach ([false, true] as $guardian) {
            $attempt = $this->attempt($guardian);
            $this->fakePersona();
            app(PersonaInquirySynchronizer::class)->sync($attempt);
            try {
                if ($guardian) {
                    app(ParentChildVerificationService::class)->approveParent($attempt->user);
                } else {
                    app(LearnerIdentityReview::class)->approve($this->admin(), $attempt->learnerVerification->fresh(), 1);
                }
                $this->fail('Expected manual review confirmations to be enforced by the service.');
            } catch (\Symfony\Component\HttpKernel\Exception\HttpException $exception) {
                $this->assertSame(422, $exception->getStatusCode());
            }
        }
    }

    public function test_pending_sandbox_review_cannot_be_approved_after_exception_is_disabled(): void
    {
        $attempt = $this->attempt();
        $this->fakePersona();
        app(PersonaInquirySynchronizer::class)->sync($attempt);
        config(['persona.local_manual_review' => false, 'persona.enabled' => false]);
        $this->actingAs($this->admin())->postJson(route('admin.parent-verifications.learners.approve', $attempt->learnerVerification), [
            'submission_round' => 1, 'sandbox_philippine_id_confirmed' => '1', 'sandbox_id_type_confirmed' => '1', 'persona_portrait_compared' => '1',
        ])->assertUnprocessable();
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('replacementResults')]
    public function test_stale_guardian_confirmation_cannot_approve_a_newer_attempt(string $country, string $class): void
    {
        $this->actingAs($this->admin());
        $old = $this->attempt(true);
        $this->fakePersona();
        app(PersonaInquirySynchronizer::class)->sync($old);
        app(ParentChildVerificationService::class)->rejectParent($old->user->fresh(), 'Please submit a clearer ID.', personaAttemptId: $old->id);
        $old->forceFill(['superseded_at' => now()])->save();
        $new = $old->replicate()->forceFill([
            'attempt_number' => 2, 'target_submission_round' => 2, 'superseded_at' => null,
            'reference_id' => (string) str()->uuid(), 'idempotency_key' => (string) str()->uuid(),
            'persona_inquiry_id' => 'inq_'.str()->random(12),
        ]);
        $new->save();
        $this->fakePersona($country, $class);
        app(PersonaInquirySynchronizer::class)->sync($new);
        $data = ['persona_attempt_id' => $old->id, 'sandbox_philippine_id_confirmed' => '1', 'sandbox_id_type_confirmed' => '1', 'persona_portrait_compared' => '1'];
        $this->postJson(route('admin.parent-verifications.parents.approve', $old->user), $data)->assertConflict();
        $this->assertSame('pending', $old->user->fresh()->parent_verification_status);
        $this->assertSame(0, $old->user->guardianIdentityAudits()->where('action', 'approved')->count());
        $data['persona_attempt_id'] = $new->id;
        $this->post(route('admin.parent-verifications.parents.approve', $old->user), $data)->assertRedirect();
        $this->assertSame('approved', $old->user->fresh()->parent_verification_status);
    }

    public static function replacementResults(): array
    {
        return ['fallback again' => ['US', 'dl'], 'normal PH result' => ['PH', 'id']];
    }

    public function test_invitation_metadata_distinguishes_normal_and_test_only_guardian_approvals(): void
    {
        $guardian = $this->attempt(true)->user;
        $child = User::factory()->create(['role' => 'learner']);
        $invitation = \App\Models\ParentChildInvitation::create([
            'inviter_parent_user_id' => $guardian->id, 'child_user_id' => $child->id,
            'relationship_type' => 'parent', 'invite_token' => (string) str()->uuid(), 'status' => 'pending',
            'expires_at' => now()->addDay(),
        ]);
        $this->app['env'] = 'production';
        foreach ([false, true] as $testOnly) {
            $guardian->forceFill(['parent_verification_status' => 'approved', 'parent_identity_sandbox_manual_review' => $testOnly])->save();
            $request = \Illuminate\Http\Request::create('/');
            $request->setUserResolver(fn () => $child);
            $view = app(\App\Http\Controllers\ParentInvitationController::class)->show($request, $invitation->fresh());
            $this->assertSame(! $testOnly, $view->getData()['guardianSummary']['identity_verified']);
        }
    }

    public function test_partially_loaded_guardian_does_not_assume_missing_provenance_is_real_approval(): void
    {
        $guardian = $this->attempt(true)->user;
        $guardian->forceFill(['parent_verification_status' => 'approved', 'parent_identity_sandbox_manual_review' => true])->save();
        $this->app['env'] = 'production';
        $partial = User::select(['id', 'parent_verification_status'])->findOrFail($guardian->id);
        $this->assertFalse($partial->isParentVerificationApproved());
    }

    public function test_test_only_guardian_cannot_submit_relationships_outside_local_mode(): void
    {
        $guardian = $this->attempt(true)->user;
        $guardian->forceFill(['parent_verification_status' => 'approved', 'parent_identity_sandbox_manual_review' => true])->save();
        $child = User::factory()->create(['role' => 'learner', 'status' => User::STATUS_ACTIVE]);
        $relationship = \App\Models\ParentChildAccount::create([
            'parent_user_id' => $guardian->id, 'child_user_id' => $child->id, 'relationship_type' => 'parent',
            'relationship_status' => 'pending', 'relationship_verified_status' => 'pending', 'current_evidence_round' => 0,
        ]);
        $this->app['env'] = 'production';
        $this->expectException(\Illuminate\Auth\Access\AuthorizationException::class);
        app(\App\Services\GuardianRelationshipVerificationService::class)->submitDeclaration($relationship, $guardian);
    }

    public function test_relationship_access_query_excludes_test_only_guardians_when_mode_is_disabled(): void
    {
        $guardian = $this->attempt(true)->user;
        $guardian->forceFill(['parent_verification_status' => 'approved', 'parent_identity_sandbox_manual_review' => true])->save();
        $child = User::factory()->create(['role' => 'learner', 'status' => User::STATUS_ACTIVE]);
        $relationship = \App\Models\ParentChildAccount::create([
            'parent_user_id' => $guardian->id, 'child_user_id' => $child->id, 'relationship_type' => 'parent',
            'relationship_status' => 'active', 'relationship_verified_status' => 'verified', 'relationship_verified_at' => now(),
        ]);
        $this->assertTrue(\App\Models\ParentChildAccount::accessEligible()->whereKey($relationship->id)->exists());
        $this->app['env'] = 'production';
        $this->assertFalse(\App\Models\ParentChildAccount::accessEligible()->whereKey($relationship->id)->exists());
    }

    private function admin(): User
    {
        $user = User::factory()->create(['role' => 'admin']);
        $user->assignRole('admin');

        return $user;
    }

    private function attempt(bool $guardian = false): PersonaInquiry
    {
        $user = User::factory()->create([
            'role' => 'learner', 'birthdate' => '2000-01-01', 'email_verified_at' => now(),
            'is_parent_registration' => $guardian, 'parent_verification_status' => null,
        ]);
        $user->assignRole('learner');
        $case = $guardian ? null : app(LearnerIdentityRequirement::class)->createForNewLearner($user);

        return PersonaInquiry::create([
            'user_id' => $user->id, 'learner_identity_verification_id' => $case?->id,
            'flow_type' => $guardian ? PersonaInquiry::FLOW_GUARDIAN_GOVERNMENT : PersonaInquiry::FLOW_ADULT_GOVERNMENT,
            'target_submission_round' => 1, 'attempt_number' => 1, 'local_document_type' => 'government_id',
            'local_government_id_type' => 'national_id', 'template_key' => 'govid_selfie', 'template_id' => 'itmpl_test',
            'reference_id' => (string) str()->uuid(), 'idempotency_key' => (string) str()->uuid(),
            'persona_inquiry_id' => 'inq_'.str()->random(12), 'is_sandbox' => true,
        ]);
    }

    private function response(string $status = 'completed', string $gov = 'passed', string $selfie = 'passed', ?string $country = 'US', ?string $class = 'dl'): array
    {
        return ['data' => ['attributes' => ['status' => $status], 'relationships' => ['verifications' => ['data' => [
            ['type' => 'verification/government-id', 'id' => 'ver_gov'], ['type' => 'verification/selfie', 'id' => 'ver_selfie'],
        ]]]], 'included' => [
            ['type' => 'verification/government-id', 'id' => 'ver_gov', 'attributes' => ['status' => $gov, 'country-code' => $country, 'id-class' => $class]],
            ['type' => 'verification/selfie', 'id' => 'ver_selfie', 'attributes' => ['status' => $selfie]],
        ]];
    }

    private function fakePersona(string $country = 'US', string $class = 'dl'): void
    {
        Http::swap(new \Illuminate\Http\Client\Factory);
        Http::preventStrayRequests();
        $response = $this->response(country: $country, class: $class);
        Http::fake(['*/inquiries/*' => Http::response($response),
            '*/verification/government-ids/*' => Http::response(['data' => $response['included'][0]]),
            '*/verification/selfies/*' => Http::response(['data' => $response['included'][1]]),
        ]);
    }
}
