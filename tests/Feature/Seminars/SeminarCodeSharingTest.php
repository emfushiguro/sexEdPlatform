<?php

namespace Tests\Feature\Seminars;

use App\Models\ActivityLog;
use App\Models\User;
use App\Services\Seminars\SeminarCodeAttendanceService;
use Illuminate\Support\Facades\Crypt;
use Tests\Feature\Connectors\ConnectorTestHelpers;
use Tests\TestCase;

class SeminarCodeSharingTest extends TestCase
{
    use ConnectorTestHelpers;

    public function test_posted_code_is_encrypted_hidden_from_json_and_shown_only_to_eligible_registrants(): void
    {
        [$owner, $connector, $event, $learner, $codes, $code] = $this->event();
        $this->actingAs($owner)->post(route('connector.seminars.attendance.code.post', [$connector, $event]), ['attendance_share_code' => $code])->assertRedirect();
        $event->refresh();
        $ciphertext = $event->getRawOriginal('attendance_code_posted_ciphertext');
        $this->assertNotSame($code, $ciphertext);
        $this->assertSame($code, Crypt::decryptString($ciphertext));
        $this->assertStringNotContainsString('attendance_code_posted_ciphertext', $event->toJson());
        $this->assertSame($code, $codes->postedCodeFor($learner, $event));
        $this->assertNull($codes->postedCodeFor($owner, $event));
        $this->assertSame(0, $event->attendances()->count());
        $this->actingAs($learner)->get(route('seminars.show', $event))->assertOk()->assertSee('Posted attendance code')->assertSee($code)->assertHeader('Cache-Control', 'no-store, private');
        $logs = ActivityLog::where('metadata->seminar_id', $event->id)->get();
        $this->assertStringNotContainsString($code, $logs->toJson());
        $this->assertSame($owner->id, $logs->last()->user_id);
        $event->registrants()->where('user_id', $learner->id)->update(['status' => 'pending']);
        $this->assertNull($codes->postedCodeFor($learner, $event));
        $this->actingAs($learner)->get(route('seminars.show', $event))->assertDontSee($code);
        $event->registrants()->where('user_id', $learner->id)->update(['status' => 'registered', 'cancelled_at' => now()]);
        $this->assertNull($codes->postedCodeFor($learner, $event));
    }

    public function test_unpost_keeps_code_valid_but_regeneration_and_closing_clear_the_post(): void
    {
        [$owner, $connector, $event, $learner, $codes, $code] = $this->event();
        $codes->post($event, $owner, $code);
        $this->actingAs($owner)->post(route('connector.seminars.attendance.code.unpost', [$connector, $event]))->assertRedirect();
        $this->assertNull($codes->postedCodeFor($learner, $event->fresh()));
        $this->assertTrue($event->fresh()->attendance_code_enabled);
        $codes->submit($learner, $event, $code, '127.0.0.7');
        $codes->post($event, $owner, $code);
        $newCode = $codes->generate($event, $owner);
        $this->assertNull($event->fresh()->attendance_code_posted_ciphertext);
        $codes->post($event, $owner, $newCode);
        $codes->disable($event, $owner);
        $this->assertNull($event->fresh()->attendance_code_posted_ciphertext);
        $this->assertNull($event->fresh()->attendance_code_posted_at);
    }

    public function test_window_and_lifecycle_are_rechecked_and_ineligible_users_never_receive_posted_code(): void
    {
        [$owner, , $event, $learner, $codes, $code] = $this->event();
        $this->travelTo(now()->startOfSecond());
        $event->update(['attendance_start_at' => now()->addMinutes(2), 'attendance_end_at' => now()->addMinutes(5)]);
        $codes->post($event, $owner, $code);
        $this->assertNull($codes->postedCodeFor($learner, $event->fresh()));
        $this->travelTo($event->fresh()->attendance_start_at);
        $this->assertSame($code, $codes->postedCodeFor($learner, $event->fresh()));
        $this->travelTo($event->fresh()->attendance_end_at);
        $this->assertSame($code, $codes->postedCodeFor($learner, $event->fresh()));
        $this->travel(1)->seconds();
        $this->assertNull($codes->postedCodeFor($learner, $event->fresh()));
        $this->travelBack();
        $event->update(['attendance_start_at' => now()->subMinute(), 'attendance_end_at' => now()->addHour()]);
        foreach (['draft', 'pending_review', 'cancelled', 'archived'] as $status) {
            $event->update(['status' => $status]);
            $this->assertNull($codes->postedCodeFor($learner, $event->fresh()));
        }
        $event->update(['status' => 'completed']);
        $this->assertSame($code, $codes->postedCodeFor($learner, $event->fresh()));
        $event->update(['learner_age_categories' => ['kids']]);
        $this->assertNull($codes->postedCodeFor($learner, $event->fresh()));
    }

    public function test_posting_requires_current_code_and_connector_ownership_without_flashing_plaintext(): void
    {
        [$owner, $connector, $event, , $codes, $code] = $this->event();
        $admin = User::factory()->create(['role' => 'admin']);
        $admin->assignRole('admin');
        $outsider = $this->createCompletedLearner();
        $other = $this->createVerifiedConnector($outsider);
        $url = route('connector.seminars.attendance.code.post', [$connector, $event]);
        $this->actingAs($admin)->post($url, ['attendance_share_code' => $code])->assertForbidden();
        $this->actingAs($outsider)->post(route('connector.seminars.attendance.code.post', [$other, $event]), ['attendance_share_code' => $code])->assertNotFound();
        $codes->generate($event, $owner);
        $this->actingAs($owner)->post($url, ['attendance_share_code' => $code])->assertSessionHasErrors('attendance_share_code');
        $this->assertNull(session()->getOldInput('attendance_share_code'));
        $this->assertNull($event->fresh()->attendance_code_posted_ciphertext);
        $this->actingAs($owner)->post($url, ['attendance_share_code' => '<not-eight-digits>'])->assertSessionHasErrors('attendance_share_code');
        $this->assertNull(session()->getOldInput('attendance_share_code'));
    }

    public function test_host_can_copy_or_present_a_posted_code_but_admin_cannot_reveal_it(): void
    {
        [$owner, $connector, $event, , $codes, $code] = $this->event();
        $codes->post($event, $owner, $code);
        $this->actingAs($owner)->get(route('connector.seminars.attendance', [$connector, $event]))
            ->assertOk()->assertSee('Copy code')->assertSee('Present code')->assertSee('Unpost code')->assertSee($code);
        $admin = User::factory()->create(['role' => 'admin']);
        $admin->assignRole('admin');
        $this->assertNull($codes->postedCodeFor($admin, $event->fresh()));
        $this->actingAs($admin)->get(route('admin.seminars.attendance', $event))->assertOk()->assertDontSee($code);
    }

    public function test_workspace_learner_receives_the_same_protected_posted_code(): void
    {
        [$owner, $connector, $event, , $codes, $code] = $this->event();
        $member = $this->createAdultConnectorMember($connector);
        $event->registrants()->create(['user_id' => $member->id, 'status' => 'registered', 'participant_type' => 'learner', 'registered_at' => now()]);
        $codes->post($event, $owner, $code);
        $this->actingAs($member)->get(route('connector.seminars.show', [$connector, $event]))->assertOk()->assertSee('Posted attendance code')->assertSee($code);
    }

    public function test_tampered_posts_and_non_native_only_rule_fail_closed(): void
    {
        [$owner, $connector, $event, $learner, $codes, $code] = $this->event();
        $codes->post($event, $owner, $code);
        $event->forceFill(['attendance_code_posted_ciphertext' => 'not-valid-ciphertext'])->save();
        $this->assertNull($codes->postedCodeFor($learner, $event));
        $event->forceFill(['attendance_code_posted_ciphertext' => Crypt::encryptString('non-current-code')])->save();
        $this->assertNull($codes->postedCodeFor($learner, $event));
        $event->update(['event_format' => 'native']);
        $this->assertNull($codes->postedCodeFor($learner, $event));
        $this->actingAs($owner)->post(route('connector.seminars.attendance.code.post', [$connector, $event]), ['attendance_share_code' => $code])->assertSessionHasErrors('attendance_share_code');
        $event->update(['event_format' => 'in_person', 'attendance_end_at' => now()->subMinute()]);
        $this->actingAs($owner)->post(route('connector.seminars.attendance.code.post', [$connector, $event]), ['attendance_share_code' => $code])->assertSessionHasErrors('attendance_share_code');
    }

    public function test_post_unpost_and_host_reveal_authorize_the_actual_event_in_services(): void
    {
        [$owner, $connector, $event, , $codes, $code] = $this->event();
        $member = $this->createAdultConnectorMember($connector);
        $outsider = $this->createCompletedLearner();
        $otherConnector = $this->createVerifiedConnector($outsider);
        $forged = clone $event;
        $forged->connector_id = $otherConnector->id;
        foreach ([
            fn () => $codes->post($event, $member, $code),
            fn () => $codes->unpost($event, $member),
            fn () => $codes->postedCodeForHost($outsider, $forged),
        ] as $operation) {
            try {
                $operation();
                $this->fail('Service authorization must reject this actor.');
            } catch (\Symfony\Component\HttpKernel\Exception\HttpException $exception) {
                $this->assertSame(403, $exception->getStatusCode());
            }
        }
        $this->assertNull($event->fresh()->attendance_code_posted_ciphertext);
    }

    private function event(): array
    {
        $owner = $this->createCompletedLearner(['age_bracket_cached' => 'adults']);
        $connector = $this->createVerifiedConnector($owner);
        $event = $connector->seminars()->create([
            'title' => 'Shared attendance event', 'description' => 'QA class', 'purpose' => 'Learn',
            'type' => 'seminar', 'event_format' => 'in_person', 'category' => 'health', 'status' => 'published',
            'starts_at' => now()->subMinutes(5), 'ends_at' => now()->addHour(), 'schedule' => now()->subMinutes(5),
            'target_participants' => 'learners', 'learner_age_categories' => ['adult'],
        ]);
        $learner = $this->createCompletedLearner(['age_bracket_cached' => 'adults']);
        $event->registrants()->create(['user_id' => $learner->id, 'status' => 'registered', 'participant_type' => 'learner', 'registered_at' => now()]);
        $codes = app(SeminarCodeAttendanceService::class);
        $code = $codes->generate($event, $owner);

        return [$owner, $connector, $event, $learner, $codes, $code];
    }
}
