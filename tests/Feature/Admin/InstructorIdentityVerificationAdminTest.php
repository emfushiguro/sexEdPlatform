<?php

namespace Tests\Feature\Admin;

use App\Models\InstructorIdentityAudit;
use App\Models\InstructorIdentityVerification;
use App\Models\PersonaInquiry;
use App\Models\User;
use App\Notifications\InstructorIdentityStatusNotification;
use App\Services\Admin\RoleSyncService;
use App\Support\NotificationDeepLinkResolver;
use DomainException;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\PermissionRegistrar;
use Illuminate\Support\Str;
use Tests\TestCase;

class InstructorIdentityVerificationAdminTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'instructor_application.enabled' => true,
            'persona.enabled' => true,
            'persona.environment' => 'sandbox',
            'persona.api_key' => 'test-only',
            'persona.media_hosts' => ['media.persona.test'],
        ]);
        Notification::fake();
        Http::preventStrayRequests();
    }

    public function test_queue_view_can_use_stage_two_permission_but_identity_actions_cannot(): void
    {
        $applicant = $this->applicant();
        $case = $this->pendingCase($applicant);
        $reviewer = $this->admin();
        $reviewer->givePermissionTo('review instructor identities');

        $this->actingAs($reviewer)
            ->get(route('admin.instructor-applications.identities.index'))
            ->assertOk()
            ->assertSee($applicant->email);

        $this->actingAs($reviewer)
            ->get(route('admin.instructor-applications.identities.show', $case))
            ->assertOk()
            ->assertSee('Instructor Applications');

        $reviewer->roles()->where('name', 'admin')->firstOrFail()->revokePermissionTo('review instructor identities');
        $reviewer->revokePermissionTo('review instructor identities');
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $reviewer = $reviewer->fresh();
        $this->assertFalse($reviewer->hasPermissionTo('review instructor identities'));
        $this->actingAs($reviewer)
            ->get(route('admin.instructor-applications.identities.index'))
            ->assertOk();

        $this->actingAs($reviewer)
            ->post(route('admin.instructor-applications.identities.reject', $case), [
                'submission_round' => $case->submission_round,
                'persona_inquiry_id' => $case->personaInquiries()->value('id'),
                'reason_code' => 'identity_mismatch',
            ])
            ->assertForbidden();

        $this->actingAs($applicant)
            ->get(route('admin.instructor-applications.identities.show', $case))
            ->assertForbidden();
    }

    public function test_detail_fetches_only_allowlisted_provider_values_and_keeps_success_pending(): void
    {
        $applicant = $this->applicant();
        $case = $this->pendingCase($applicant);
        $reviewer = $this->reviewer();
        $this->fakeProviderAttributes();

        $response = $this->actingAs($reviewer)
            ->get(route('admin.instructor-applications.identities.show', $case));

        $response->assertOk()
            ->assertSee('Submitted identity evidence')
            ->assertSee('Approve identity')
            ->assertSee('Request changes')
            ->assertDontSee('<h2 class="text-lg font-bold text-gray-900">Identity details</h2>', false)
            ->assertDontSee('Provider First')
            ->assertDontSee('provider-secret-document-number');
        $this->assertSame(InstructorIdentityVerification::STATUS_PENDING_REVIEW, $case->fresh()->status);
        $this->assertStringNotContainsString('Provider First', json_encode($case->fresh()->comparison_summary));
    }

    public function test_approval_requires_current_round_and_creates_one_audit_and_one_status_notification(): void
    {
        $applicant = $this->applicant();
        $case = $this->pendingCase($applicant);
        $reviewer = $this->reviewer();
        $auditCount = $case->audits()->count();

        $this->actingAs($reviewer)
            ->post(route('admin.instructor-applications.identities.approve', $case), [
                'submission_round' => $case->submission_round,
                'persona_inquiry_id' => $case->personaInquiries()->value('id'),
                'declared_confirmed' => '1',
                'provider_confirmed' => '1',
            ])
            ->assertRedirect();

        $this->assertSame(InstructorIdentityVerification::STATUS_APPROVED, $case->fresh()->status);
        $this->assertSame($auditCount + 1, $case->audits()->count());
        $this->assertSame(1, $case->audits()->where('action', 'reviewer_decision')->count());
        Notification::assertSentTo($applicant, InstructorIdentityStatusNotification::class, function (InstructorIdentityStatusNotification $notification): bool {
            return $notification->status === InstructorIdentityVerification::STATUS_APPROVED;
        });
    }

    public function test_production_demo_instructor_review_requires_opt_in_and_confirmations(): void
    {
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
        $this->app['env'] = 'production';
        config(['persona.demo_manual_review' => false]);
        $case = $this->pendingCase($this->applicant(), sandbox: true);
        $case->forceFill(['sandbox_manual_review' => true])->save();
        $attempt = $case->personaInquiries()->firstOrFail();
        $attempt->forceFill(['verification_outcome' => PersonaInquiry::OUTCOME_SANDBOX_REVIEW, 'country_code' => 'US', 'id_class' => 'dl'])->save();
        $payload = [
            'submission_round' => $case->submission_round,
            'persona_inquiry_id' => $attempt->id,
            'declared_confirmed' => '1', 'provider_confirmed' => '1',
            'sandbox_philippine_id_confirmed' => '1', 'sandbox_id_type_confirmed' => '1', 'sandbox_portraits_compared' => '1',
        ];
        $this->actingAs($this->reviewer())->post(route('admin.instructor-applications.identities.approve', $case), $payload)->assertRedirect();
        $this->assertSame(InstructorIdentityVerification::STATUS_PENDING_REVIEW, $case->fresh()->status);

        config(['persona.demo_manual_review' => true]);
        $incomplete = $payload;
        unset($incomplete['sandbox_portraits_compared']);
        $this->postJson(route('admin.instructor-applications.identities.approve', $case), $incomplete)->assertUnprocessable();
        $this->post(route('admin.instructor-applications.identities.approve', $case), $payload)->assertRedirect();
        $this->assertTrue($case->fresh()->isAccessEligible());
        config(['persona.demo_manual_review' => false]);
        $this->assertFalse($case->fresh()->isAccessEligible());
    }

    public function test_mismatched_approval_does_not_show_or_require_a_rationale(): void
    {
        $applicant = $this->applicant();
        $case = $this->pendingCase($applicant);
        $case->update([
            'comparison_summary' => [
                'normalization_version' => 'v1',
                'fields' => ['first_name' => ['outcome' => 'mismatch']],
            ],
        ]);
        $reviewer = $this->reviewer();
        $payload = [
            'submission_round' => $case->submission_round,
            'persona_inquiry_id' => $case->personaInquiries()->value('id'),
            'declared_confirmed' => '1',
            'provider_confirmed' => '1',
        ];

        $this->actingAs($reviewer)
            ->post(route('admin.instructor-applications.identities.approve', $case), $payload)
            ->assertRedirect();

        $this->assertSame(InstructorIdentityVerification::STATUS_APPROVED, $case->fresh()->status);

        $this->actingAs($reviewer)
            ->get(route('admin.instructor-applications.identities.show', $case))
            ->assertOk()
            ->assertDontSee('Field comparison summary')
            ->assertDontSee('A field mismatch was detected')
            ->assertDontSee('Note about any mismatch');
    }

    public function test_stale_round_returns_a_reload_message_and_does_not_decide(): void
    {
        $applicant = $this->applicant();
        $case = $this->pendingCase($applicant);
        $reviewer = $this->reviewer();

        $this->actingAs($reviewer)
            ->post(route('admin.instructor-applications.identities.reject', $case), [
                'submission_round' => $case->submission_round + 1,
                'persona_inquiry_id' => $case->personaInquiries()->value('id'),
                'reason_code' => 'identity_mismatch',
            ])
            ->assertRedirect()
            ->assertSessionHasErrors(['review']);

        $this->assertSame(InstructorIdentityVerification::STATUS_PENDING_REVIEW, $case->fresh()->status);
    }

    public function test_reject_and_request_action_are_audited_and_notify_the_applicant(): void
    {
        $applicant = $this->applicant();
        $reviewer = $this->reviewer();
        $rejected = $this->pendingCase($applicant);

        $this->actingAs($reviewer)
            ->post(route('admin.instructor-applications.identities.reject', $rejected), [
                'submission_round' => $rejected->submission_round,
                'persona_inquiry_id' => $rejected->personaInquiries()->value('id'),
                'reason_code' => 'identity_mismatch',
                'reason_note' => 'Please clarify the submitted details.',
            ])
            ->assertRedirect();

        $this->assertSame(InstructorIdentityVerification::STATUS_REJECTED, $rejected->fresh()->status);
        $this->assertSame('identity_mismatch', $rejected->fresh()->reason_code);
        $this->assertSame(1, $rejected->audits()->where('action', 'reviewer_decision')->count());

        $actionApplicant = $this->applicant();
        $actionRequired = $this->pendingCase($actionApplicant);
        $this->actingAs($reviewer)
            ->post(route('admin.instructor-applications.identities.request-action', $actionRequired), [
                'submission_round' => $actionRequired->submission_round,
                'persona_inquiry_id' => $actionRequired->personaInquiries()->value('id'),
                'requested_action_codes' => ['retry_persona'],
                'reason_note' => 'Please retry the identity step.',
            ])
            ->assertRedirect();

        $this->assertSame(InstructorIdentityVerification::STATUS_ACTION_REQUIRED, $actionRequired->fresh()->status);
        $this->assertSame(['retry_persona'], $actionRequired->fresh()->requested_action_codes);
        Notification::assertSentTo($actionApplicant, InstructorIdentityStatusNotification::class, function (InstructorIdentityStatusNotification $notification): bool {
            return $notification->status === InstructorIdentityVerification::STATUS_ACTION_REQUIRED;
        });
    }

    public function test_owner_cannot_approve_and_notification_links_reconstruct_from_local_case_id(): void
    {
        $applicant = $this->applicant();
        $case = $this->pendingCase($applicant);
        $reviewer = $this->reviewer();

        try {
            app(\App\Services\InstructorApplication\InstructorIdentityService::class)
                ->approve($case, $applicant, ['declared' => true, 'provider' => true]);
            $this->fail('The case owner must not be able to approve their own identity case.');
        } catch (DomainException) {
            $this->assertSame(InstructorIdentityVerification::STATUS_PENDING_REVIEW, $case->fresh()->status);
        }

        $resolver = app(NotificationDeepLinkResolver::class);
        $payload = [
            'type' => 'instructor_identity_submitted',
            'instructor_identity_verification_id' => $case->id,
            'action_url' => 'https://evil.example/provider-case',
        ];
        $this->assertSame(
            route('admin.instructor-applications.identities.show', $case, false),
            $resolver->resolve($payload, '/fallback', $reviewer),
        );
        $this->assertSame(
            route('instructor-application.dashboard', [], false),
            $resolver->resolve([
                ...$payload,
                'type' => 'instructor_identity_approved',
            ], '/fallback', $applicant),
        );
        $this->assertSame('/fallback', $resolver->resolve($payload, '/fallback'));
    }

    public function test_evidence_proxy_is_permission_protected_and_private(): void
    {
        $applicant = $this->applicant();
        $case = $this->pendingCase($applicant, sandbox: true);
        $reviewer = $this->reviewer();
        $attempt = $case->personaInquiries()->firstOrFail();

        Http::fake(function (HttpRequest $request) {
            if (str_contains($request->url(), '/verification/government-ids/')) {
                return Http::response(['data' => ['attributes' => [
                    'photo-urls' => [['page' => 'front', 'url' => 'https://media.persona.test/id-front']],
                ]]]);
            }

            return Http::response('image-bytes', 200, [
                'Content-Type' => 'image/jpeg',
                'Content-Length' => '11',
            ]);
        });

        $this->actingAs($reviewer)
            ->get(route('admin.parent-verifications.persona.evidence', [$attempt, 'government-id-front']))
            ->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertHeader('X-Content-Type-Options', 'nosniff');

        $reviewer->roles()->where('name', 'admin')->firstOrFail()->revokePermissionTo('review instructor identities');
        $reviewer->revokePermissionTo('review instructor identities');
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $reviewer = $reviewer->fresh();
        $this->assertFalse($reviewer->hasPermissionTo('review instructor identities'));
        $this->actingAs($reviewer)
            ->get(route('admin.parent-verifications.persona.evidence', [$attempt, 'government-id-front']))
            ->assertNotFound();
    }

    private function reviewer(): User
    {
        $admin = $this->admin();
        $admin->givePermissionTo('review instructor identities');

        return $admin->fresh();
    }

    private function admin(): User
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => User::STATUS_ACTIVE]);
        $admin->assignRole('admin');

        return $admin->fresh();
    }

    private function applicant(): User
    {
        $user = User::factory()->create([
            'role' => User::ACCOUNT_TYPE_APPLICANT,
            'account_type' => User::ACCOUNT_TYPE_APPLICANT,
            'birthdate' => now()->subYears(24)->format('Y-m-d'),
            'status' => User::STATUS_ACTIVE,
            'email_verified_at' => now(),
        ]);
        app(RoleSyncService::class)->assignPrimaryRole($user, User::ACCOUNT_TYPE_APPLICANT);

        return $user->fresh();
    }

    private function pendingCase(User $user, bool $sandbox = false): InstructorIdentityVerification
    {
        $case = InstructorIdentityVerification::create([
            'user_id' => $user->id,
            'source' => InstructorIdentityVerification::SOURCE_DIRECT_PERSONA,
            'status' => InstructorIdentityVerification::STATUS_PENDING_REVIEW,
            'submission_round' => 1,
            'declared_snapshot' => [
                'first_name' => 'Declared',
                'last_name' => 'Applicant',
                'birthdate' => '2002-01-01',
                'city_name' => 'Cavite City',
                'barangay_name' => 'Barangay Test',
            ],
            'comparison_summary' => [
                'normalization_version' => 'v1',
                'fields' => ['first_name' => ['outcome' => 'match']],
            ],
            'sandbox_manual_review' => false,
            'submitted_at' => now(),
            'resubmission_allowed' => true,
        ]);
        $case->personaInquiries()->create([
            'user_id' => $user->id,
            'flow_type' => PersonaInquiry::FLOW_INSTRUCTOR_GOVERNMENT,
            'target_submission_round' => 1,
            'attempt_number' => 1,
            'local_document_type' => 'government_id',
            'local_government_id_type' => 'national_id',
            'template_key' => PersonaInquiry::TEMPLATE_GOVID_SELFIE,
            'template_id' => 'template-instructor',
            'reference_id' => (string) Str::uuid(),
            'idempotency_key' => (string) Str::uuid(),
            'persona_inquiry_id' => 'inq-instructor-review-'.$case->id,
            'government_id_verification_id' => 'ver-gov-review',
            'selfie_verification_id' => 'ver-selfie-review',
            'persona_status' => 'completed',
            'verification_outcome' => PersonaInquiry::OUTCOME_ELIGIBLE,
            'country_code' => 'PH',
            'id_class' => 'id',
            'is_sandbox' => $sandbox,
            'completed_at' => now(),
            'last_synced_at' => now(),
        ]);

        return $case->fresh();
    }

    private function fakeProviderAttributes(): void
    {
        Http::fake(function (HttpRequest $request) {
            if (str_contains($request->url(), '/inquiries/')) {
                return Http::response(['data' => [
                    'relationships' => ['verifications' => ['data' => [
                        ['type' => 'verification/government-id', 'id' => 'ver-gov-review'],
                    ]]],
                ]]);
            }

            return Http::response(['data' => ['type' => 'verification/government-id', 'id' => 'ver-gov-review', 'attributes' => [
                'name-first' => 'Provider First',
                'name-last' => 'Provider Last',
                'birthdate' => '2002-01-01',
                'country-code' => 'PH',
                'id-class' => 'id',
                'document-number' => 'provider-secret-document-number',
            ]]]);
        });
    }
}
