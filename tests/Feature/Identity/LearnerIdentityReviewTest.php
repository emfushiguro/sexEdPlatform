<?php

namespace Tests\Feature\Identity;

use App\Enums\LearnerIdentityRejectionReason;
use App\Models\LearnerIdentityVerification;
use App\Models\User;
use App\Services\Identity\LearnerIdentityRequirement;
use App\Services\Identity\LearnerIdentityReview;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LearnerIdentityReviewTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-09-24 10:00:00');
        Storage::fake('local');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_queue_filters_status_and_learner_pathway(): void
    {
        $admin = $this->admin();
        $pendingTeen = $this->case('2010-01-01', 'pending', 'TeenPending');
        $approvedTeen = $this->case('2010-01-01', 'approved', 'TeenApproved');
        $rejectedAdult = $this->case('2000-01-01', 'rejected', 'AdultRejected');

        $this->actingAs($admin)->get(route('admin.parent-verifications.index', ['type' => 'learners', 'status' => 'pending', 'pathway' => 'teen']))
            ->assertOk()->assertSee($pendingTeen->learner->full_name)->assertDontSee($approvedTeen->learner->full_name)
            ->assertDontSee($rejectedAdult->learner->full_name);
        $this->get(route('admin.parent-verifications.index', ['type' => 'learners', 'status' => 'approved', 'pathway' => 'teen']))
            ->assertOk()->assertSee($approvedTeen->learner->full_name)->assertDontSee($pendingTeen->learner->full_name);
        $this->get(route('admin.parent-verifications.index', ['type' => 'learners', 'status' => 'rejected', 'pathway' => 'adult']))
            ->assertOk()->assertSee($rejectedAdult->learner->full_name)->assertDontSee($pendingTeen->learner->full_name);
    }

    public function test_filtered_learner_queue_reaches_page_two_with_bounded_eager_loading(): void
    {
        $admin = $this->admin();
        $older = $this->case('2000-01-01', 'pending', 'OlderPending');
        $older->update(['submitted_at' => now()->subDay()]);
        for ($index = 0; $index < 25; $index++) {
            $this->case('2000-01-01', 'pending', 'NewerPending'.$index);
        }
        $teen = $this->case('2010-01-01', 'pending', 'ExcludedTeen');
        $rejected = $this->case('2000-01-01', 'rejected', 'ExcludedRejected');
        $url = route('admin.parent-verifications.index', [
            'type' => 'learners', 'status' => 'pending', 'pathway' => 'adult',
        ]);

        $firstPage = $this->actingAs($admin)->get($url)->assertOk()
            ->assertDontSee($older->learner->full_name)
            ->assertDontSee($teen->learner->full_name)
            ->assertDontSee($rejected->learner->full_name)
            ->assertViewHas('learnerApplications', fn ($page) => $page->count() === 25
                && $page->total() === 26
                && $page->getCollection()->every(fn ($case) => $case->relationLoaded('learner')));
        $nextUrl = $firstPage->viewData('learnerApplications')->nextPageUrl();
        parse_str(parse_url($nextUrl, PHP_URL_QUERY), $nextQuery);
        $this->assertSame(['type' => 'learners', 'status' => 'pending', 'pathway' => 'adult', 'page' => '2'], $nextQuery);
        $firstPage->assertSee('href="'.e($nextUrl).'"', false);

        $this->get($nextUrl)->assertOk()
            ->assertSee($older->learner->full_name)
            ->assertDontSee($teen->learner->full_name)
            ->assertDontSee($rejected->learner->full_name)
            ->assertViewHas('learnerApplications', fn ($page) => $page->count() === 1
                && $page->currentPage() === 2
                && $page->getCollection()->every(fn ($case) => $case->relationLoaded('learner')));
    }

    public function test_only_admin_can_view_details_and_private_evidence(): void
    {
        $admin = $this->admin();
        $case = $this->case('2000-01-01');
        $detail = route('admin.parent-verifications.learners.show', $case);
        $image = route('admin.parent-verifications.learners.evidence', [$case, 'selfie']);

        $this->actingAs($case->learner)->get($detail)->assertForbidden();
        $this->actingAs($case->learner)->get($image)->assertForbidden();
        $this->actingAs($case->learner)->post(route('admin.parent-verifications.learners.approve', $case))->assertForbidden();
        $this->actingAs($admin)->get($detail)->assertOk()->assertSee($case->learner->full_name);
        $response = $this->actingAs($admin)->get($image)->assertOk()
            ->assertHeader('Content-Type', 'image/png')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Content-Disposition', 'inline; filename="selfie.png"');
        $this->assertStringContainsString('private', $response->headers->get('Cache-Control'));
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $this->get(route('admin.parent-verifications.learners.evidence', [$case, 'identity_back']))->assertNotFound();
        $otherCase = $this->case('2000-01-01', 'pending', 'OtherLearner', 'national_id');
        $this->assertTrue($otherCase->evidence()->where('slot', 'identity_back')->exists());
        $this->get(route('admin.parent-verifications.learners.evidence', [$case, 'identity_back']))->assertNotFound();
        $this->get('/admin/parent-verifications/learners/'.$case->id.'/evidence/../selfie')->assertNotFound();
    }

    public function test_approval_records_manual_decision_and_audit_then_conflicts_on_repeat(): void
    {
        $admin = $this->admin();
        $case = $this->case('2000-01-01');
        $this->actingAs($admin)->post(route('admin.parent-verifications.learners.approve', $case), [
            'submission_round' => $case->submission_round,
        ])->assertRedirect();
        $case->refresh();
        $this->assertSame('approved', $case->status);
        $this->assertSame($admin->id, $case->reviewed_by);
        $this->assertNotNull($case->reviewed_at);
        $this->assertNotNull($case->approved_at);
        $this->assertNull($case->rejection_reason);
        $this->assertSame('approved', $case->audits()->latest('id')->firstOrFail()->action);
        $this->actingAs($admin)->postJson(route('admin.parent-verifications.learners.approve', $case), [
            'submission_round' => $case->submission_round,
        ])->assertStatus(409);
        $this->assertSame(1, $case->audits()->where('action', 'approved')->count());
    }

    public function test_rejection_requires_reason_and_records_decision(): void
    {
        $admin = $this->admin();
        $case = $this->case('2010-01-01');
        $url = route('admin.parent-verifications.learners.reject', $case);
        $this->actingAs($admin)->post($url, [
            'reason' => '',
            'submission_round' => $case->submission_round,
        ])->assertSessionHasErrors('reason');
        $this->assertSame('pending', $case->fresh()->status);
        $this->post($url, [
            'reason' => LearnerIdentityRejectionReason::UnclearId->value,
            'submission_round' => $case->submission_round,
        ])->assertRedirect();
        $case->refresh();
        $this->assertSame('rejected', $case->status);
        $this->assertSame($admin->id, $case->reviewed_by);
        $this->assertNotNull($case->reviewed_at);
        $this->assertNull($case->approved_at);
        $this->assertSame(LearnerIdentityRejectionReason::UnclearId->label(), $case->rejection_reason);
        $this->assertSame(LearnerIdentityRejectionReason::UnclearId->label(), $case->audits()->latest('id')->firstOrFail()->reason);
    }

    public function test_reject_request_refuses_free_text_paths_filenames_and_data_uris(): void
    {
        Notification::fake();
        $admin = $this->admin();
        foreach ([
            'Please replace learner-verifications/42/adult/identity_front.png.',
            'Please replace C:\\private\\learner-verifications\\42\\selfie.png.',
            'Please replace portrait.avif.',
            'data:image/png;base64,aGVsbG8=',
            'The birthdate on your ID does not match your profile.',
        ] as $reason) {
            $case = $this->case('2000-01-01');
            $this->actingAs($admin)->post(route('admin.parent-verifications.learners.reject', $case), [
                'reason' => $reason,
                'submission_round' => $case->submission_round,
            ])
                ->assertSessionHasErrors('reason');
            $this->assertSame('pending', $case->fresh()->status);
            $this->assertSame(0, $case->audits()->where('action', 'rejected')->count());
            Notification::assertNothingSent();
        }
    }

    public function test_birthdate_mismatch_option_persists_safe_label_and_uses_it_in_both_channels(): void
    {
        Notification::fake();
        $admin = $this->admin();
        $case = $this->case('2000-01-01');
        $label = 'The birthdate on your ID does not match your profile.';

        $this->actingAs($admin)->get(route('admin.parent-verifications.learners.show', $case))->assertOk()
            ->assertSee('value="birthdate_mismatch"', false)
            ->assertDontSee('<textarea id="reason"', false);
        $this->post(route('admin.parent-verifications.learners.reject', $case), [
            'reason' => 'birthdate_mismatch',
            'submission_round' => $case->submission_round,
        ])
            ->assertRedirect();

        $this->assertSame($label, $case->fresh()->rejection_reason);
        $this->assertSame($label, $case->audits()->latest('id')->firstOrFail()->reason);
        Notification::assertSentTo($case->learner, \App\Notifications\LearnerIdentityRejectedNotification::class,
            function ($notification) use ($case, $label): bool {
                $this->assertSame($label, $notification->toArray($case->learner)['reason']);
                $this->assertContains('Reason: '.$label, $notification->toMail($case->learner)->viewData['details']);

                return true;
            });
    }

    public function test_decisions_notify_learner_once_with_safe_payload_and_rejection_reason(): void
    {
        Notification::fake();
        $admin = $this->admin();
        $approved = $this->case('2000-01-01');
        $rejected = $this->case('2010-01-01');
        app(LearnerIdentityReview::class)->approve($admin, $approved, (int) $approved->submission_round);
        app(LearnerIdentityReview::class)->reject($admin, $rejected, LearnerIdentityRejectionReason::UnclearId, (int) $rejected->submission_round);

        Notification::assertSentToTimes($approved->learner, \App\Notifications\LearnerIdentityApprovedNotification::class, 1);
        Notification::assertSentToTimes($rejected->learner, \App\Notifications\LearnerIdentityRejectedNotification::class, 1);
        Notification::assertSentTo($approved->learner, \App\Notifications\LearnerIdentityApprovedNotification::class,
            fn ($notification) => $notification->via($approved->learner) === ['mail', 'database']
                && ($payload = $notification->toArray($approved->learner))['verification_id'] === $approved->id
                && $payload['action_url'] === route('learner.identity.status')
                && ! str_contains(json_encode($payload), 'learner-verifications/'));
        Notification::assertSentTo($rejected->learner, \App\Notifications\LearnerIdentityRejectedNotification::class,
            fn ($notification) => ($payload = $notification->toArray($rejected->learner))
                && $payload['reason'] === 'Please upload a clearer ID photo.'
                && in_array('Reason: Please upload a clearer ID photo.', $notification->toMail($rejected->learner)->viewData['details'], true)
                && $payload['action_url'] === route('learner.identity.status')
                && ! str_contains(json_encode($payload), 'learner-verifications/'));
    }

    public function test_every_rejection_option_uses_only_its_safe_label_in_case_audit_mail_and_database(): void
    {
        Notification::fake();
        $admin = $this->admin();
        foreach (LearnerIdentityRejectionReason::cases() as $reason) {
            $case = $this->case('2000-01-01');
            app(LearnerIdentityReview::class)->reject($admin, $case, $reason, (int) $case->submission_round);
            $this->assertSame($reason->label(), $case->fresh()->rejection_reason);
            $this->assertSame($reason->label(), $case->audits()->latest('id')->firstOrFail()->reason);
            Notification::assertSentTo($case->learner, \App\Notifications\LearnerIdentityRejectedNotification::class,
                function ($notification) use ($case, $reason): bool {
                    $database = $notification->toArray($case->learner);
                    $mailData = $notification->toMail($case->learner)->viewData;
                    $this->assertSame($reason->label(), $database['reason']);
                    $this->assertContains('Reason: '.$reason->label(), $mailData['details']);
                    foreach (['learner-verifications', 'passport-scan.jpg', 'portrait.avif', 'data:image'] as $sensitive) {
                        $this->assertStringNotContainsString($sensitive, json_encode($database));
                        $this->assertStringNotContainsString($sensitive, json_encode($mailData));
                    }

                    return true;
                });
        }
    }

    public function test_safe_rejection_reason_keeps_useful_guidance_in_mail_and_database(): void
    {
        Notification::fake();
        $admin = $this->admin();
        $case = $this->case('2000-01-01');
        $reason = LearnerIdentityRejectionReason::BirthdateMismatch;

        app(LearnerIdentityReview::class)->reject($admin, $case, $reason, (int) $case->submission_round);

        Notification::assertSentTo($case->learner, \App\Notifications\LearnerIdentityRejectedNotification::class,
            function ($notification) use ($case, $reason): bool {
                $this->assertSame($reason->label(), $notification->toArray($case->learner)['reason']);
                $this->assertContains('Reason: '.$reason->label(), $notification->toMail($case->learner)->viewData['details']);

                return true;
            });
    }

    public function test_outer_rollback_does_not_notify_learner_of_decision(): void
    {
        Notification::fake();
        $admin = $this->admin();
        $case = $this->case('2000-01-01');
        try {
            DB::transaction(function () use ($admin, $case): void {
                app(LearnerIdentityReview::class)->approve($admin, $case, (int) $case->submission_round);
                Notification::assertNothingSent();
                throw new \RuntimeException('rollback');
            });
        } catch (\RuntimeException $e) {
            $this->assertSame('rollback', $e->getMessage());
        }
        Notification::assertNothingSent();
    }

    public function test_other_adult_id_needs_separate_government_issued_confirmation(): void
    {
        $admin = $this->admin();
        $case = $this->case('2000-01-01', 'pending', 'OtherAdult', 'other');
        $url = route('admin.parent-verifications.learners.approve', $case);
        $this->actingAs($admin)->post($url, [
            'checklist' => ['government_issued' => '1'],
            'submission_round' => $case->submission_round,
        ])
            ->assertSessionHasErrors('confirm_government_issued');
        $this->assertSame('pending', $case->fresh()->status);
        $this->post($url, [
            'confirm_government_issued' => '1',
            'submission_round' => $case->submission_round,
        ])->assertRedirect();
        $this->assertSame('approved', $case->fresh()->status);
    }

    public function test_superseded_or_dob_mismatched_cases_cannot_be_decided(): void
    {
        $admin = $this->admin();
        $superseded = $this->case('2010-01-01');
        $superseded->update(['superseded_at' => now()]);
        $this->actingAs($admin)->postJson(route('admin.parent-verifications.learners.approve', $superseded), [
            'submission_round' => $superseded->submission_round,
        ])->assertStatus(409);
        $mismatch = $this->case('2010-01-01');
        $mismatch->learner->update(['birthdate' => '2000-01-01']);
        $this->postJson(route('admin.parent-verifications.learners.reject', $mismatch), [
            'reason' => LearnerIdentityRejectionReason::InformationMismatch->value,
            'submission_round' => $mismatch->submission_round,
        ])->assertStatus(409);
        $this->assertSame('pending', $mismatch->fresh()->status);
    }

    public function test_missing_required_private_file_prevents_decision(): void
    {
        $admin = $this->admin();
        $case = $this->case('2000-01-01');
        Storage::disk('local')->delete($case->evidence()->where('slot', 'selfie')->firstOrFail()->storage_path);
        $this->actingAs($admin)->postJson(route('admin.parent-verifications.learners.approve', $case), [
            'submission_round' => $case->submission_round,
        ])->assertStatus(422);
        $this->assertSame('pending', $case->fresh()->status);
    }

    public function test_invalid_document_type_cannot_be_approved(): void
    {
        $admin = $this->admin();
        $case = $this->case('2000-01-01');
        $case->update(['document_type' => 'school_id']);
        $this->actingAs($admin)->postJson(route('admin.parent-verifications.learners.approve', $case), [
            'submission_round' => $case->submission_round,
        ])->assertStatus(422);
        $this->assertSame('pending', $case->fresh()->status);
    }

    public function test_stale_review_form_cannot_decide_a_newer_submission_round(): void
    {
        Notification::fake();
        $admin = $this->admin();
        $case = $this->case('2000-01-01');
        $staleRound = (int) $case->submission_round;
        $reviewPage = $this->actingAs($admin)
            ->get(route('admin.parent-verifications.learners.show', $case))
            ->assertOk();
        $this->assertSame(2, preg_match_all('/name="submission_round" value="1"/', $reviewPage->getContent()));
        $case->forceFill(['submission_round' => $staleRound + 1])->save();

        $this->actingAs($admin)
            ->post(route('admin.parent-verifications.learners.approve', $case), ['submission_round' => $staleRound])
            ->assertStatus(409);
        $this->post(route('admin.parent-verifications.learners.reject', $case), [
            'submission_round' => $staleRound,
            'reason' => LearnerIdentityRejectionReason::InformationMismatch->value,
        ])->assertStatus(409);

        $this->assertSame('pending', $case->fresh()->status);
        $this->assertSame($staleRound + 1, $case->fresh()->submission_round);
        $this->assertDatabaseMissing('learner_identity_audits', [
            'verification_id' => $case->id,
            'action' => 'approved',
        ]);
        Notification::assertNothingSent();
    }

    public function test_review_service_denies_non_admin_even_when_called_directly(): void
    {
        $case = $this->case('2000-01-01');
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        app(LearnerIdentityReview::class)->approve($case->learner, $case, (int) $case->submission_round);
    }

    private function admin(): User
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $admin->assignRole('admin');

        return $admin;
    }

    private function case(string $birthdate, string $status = 'pending', string $name = 'ReviewLearner', string $subtype = 'philhealth'): LearnerIdentityVerification
    {
        $user = User::factory()->create(['first_name' => $name, 'last_name' => 'Identity', 'role' => 'learner', 'birthdate' => $birthdate, 'email_verified_at' => now()]);
        $user->assignRole('learner');
        $case = app(LearnerIdentityRequirement::class)->createForNewLearner($user);
        $case->update(['status' => $status, 'document_type' => 'government_id', 'government_id_type' => $subtype,
            'government_id_type_other' => $subtype === 'other' ? 'Government-issued card' : null,
            'submission_round' => 1, 'submitted_at' => now()]);
        $slots = ['identity_front', 'selfie'];
        if ((bool) data_get(config('guardian_identity.id_types'), $subtype.'.requires_back', false)) {
            $slots[] = 'identity_back';
        }
        foreach ($slots as $slot) {
            $path = "learner-verifications/{$user->id}/{$case->pathway}/{$slot}.png";
            Storage::disk('local')->put($path, 'private image bytes');
            $case->evidence()->create(['slot' => $slot, 'storage_path' => $path, 'mime_type' => 'image/png',
                'byte_size' => 19, 'width' => 800, 'height' => 600, 'submitted_at' => now()]);
        }

        return $case->load('learner');
    }
}
