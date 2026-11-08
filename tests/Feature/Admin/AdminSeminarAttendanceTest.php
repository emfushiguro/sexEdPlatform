<?php

namespace Tests\Feature\Admin;

use App\Models\ActivityLog;
use App\Models\User;
use App\Services\Seminars\SeminarCodeAttendanceService;
use App\Services\Seminars\SeminarManualAttendanceService;
use Illuminate\Support\Facades\Crypt;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\Feature\Connectors\ConnectorTestHelpers;
use Tests\TestCase;

class AdminSeminarAttendanceTest extends TestCase
{
    use ConnectorTestHelpers;

    public function test_admin_roster_and_event_detail_are_read_only_and_never_reveal_a_host_code(): void
    {
        [$owner, $connector, $seminar, $registrant, $admin] = $this->event();
        $this->actingAs($owner)->post(route('connector.seminars.attendance.code.generate', [$connector, $seminar]))->assertRedirect();
        $code = Crypt::decryptString(session('generated_attendance_code'));
        foreach (['admin.seminars.attendance', 'admin.seminars.show'] as $route) {
            $this->actingAs($admin)->withSession([
                'generated_attendance_code' => Crypt::encryptString($code),
                'generated_attendance_seminar_id' => $seminar->id,
            ])->get(route($route, $seminar))->assertOk()
                ->assertDontSee($code)->assertDontSee($seminar->fresh()->attendance_code_hash)
                ->assertDontSee('Regenerate code')->assertDontSee('Save decision');
        }
        $this->actingAs($admin)->get(route('admin.seminars.attendance', $seminar))
            ->assertSee($registrant->user->name)->assertSee('Not submitted')->assertSee('Read-only');
        $this->assertArrayNotHasKey('attendance_code_hash', $seminar->fresh()->toArray());
    }

    public function test_admin_cannot_write_through_legacy_or_connector_urls_even_with_connector_membership(): void
    {
        [$owner, $connector, $seminar, $registrant, $admin] = $this->event();
        $connector->memberships()->create([
            'user_id' => $admin->id, 'connector_role_id' => $connector->roles()->firstOrFail()->id,
            'status' => 'active', 'accepted_at' => now(),
        ]);
        $this->actingAs($owner)->post(route('connector.seminars.attendance.code.generate', [$connector, $seminar]));
        $hash = $seminar->fresh()->attendance_code_hash;
        foreach (["/admin/seminars/{$seminar->id}/attendance/code", "/admin/seminars/{$seminar->id}/attendance/code/disable", "/admin/seminars/{$seminar->id}/attendance/{$registrant->id}/manual"] as $url) {
            $this->actingAs($admin)->postJson($url, ['attended' => true])->assertNotFound();
        }
        foreach (['code.generate', 'code.disable', 'manual'] as $action) {
            $parameters = [$connector, $seminar];
            if ($action === 'manual') {
                $parameters[] = $registrant;
            }
            $this->actingAs($admin)->postJson(route('connector.seminars.attendance.'.$action, $parameters), ['attended' => true])->assertForbidden();
        }
        $this->actingAs($admin)->get(route('connector.seminars.attendance', [$connector, $seminar]))->assertForbidden();
        foreach (['putJson', 'patchJson', 'deleteJson'] as $verb) {
            $this->actingAs($admin)->{$verb}("/admin/seminars/{$seminar->id}/attendance/{$registrant->id}", ['attended' => true])->assertNotFound();
        }
        $this->assertSame($hash, $seminar->fresh()->attendance_code_hash);
        $this->assertSame(0, $seminar->attendances()->count());
        $this->assertNull($registrant->fresh()->attended_at);
    }

    public function test_services_reject_an_admin_actor_without_changing_attendance(): void
    {
        [, , $seminar, $registrant, $admin] = $this->event();
        foreach ([
            fn () => app(SeminarCodeAttendanceService::class)->generate($seminar, $admin),
            fn () => app(SeminarCodeAttendanceService::class)->disable($seminar, $admin),
            fn () => app(SeminarManualAttendanceService::class)->set($seminar, $registrant, $admin, true, 'Admin attempt'),
        ] as $operation) {
            try {
                $operation();
                $this->fail('Admin service mutation must be forbidden.');
            } catch (HttpException $exception) {
                $this->assertSame(403, $exception->getStatusCode());
            }
        }
        $this->assertSame(0, $seminar->attendances()->count());
        $this->assertNull($seminar->fresh()->attendance_code_hash);
    }

    public function test_monitoring_filters_event_connector_local_date_and_attendance_and_shows_audit_history(): void
    {
        [$owner, $connector, $seminar, $registrant, $admin] = $this->event();
        [, , $otherSeminar, $otherRegistrant] = $this->event();
        $otherSeminar->update(['starts_at' => now()->addDay(), 'schedule' => now()->addDay()]);
        $this->actingAs($owner)->post(route('connector.seminars.attendance.manual', [$connector, $seminar, $registrant]), [
            'attended' => true, 'reason' => 'Confirmed by host',
        ])->assertRedirect();
        foreach ([
            ['event' => $seminar->title], ['connector_id' => $connector->id],
            ['event_date' => $seminar->localStartsAt()->toDateString()], ['attendance_status' => 'attended'],
        ] as $filter) {
            $this->actingAs($admin)->get('/admin/seminars/attendance?'.http_build_query($filter))
                ->assertOk()->assertSee($registrant->user->email)->assertDontSee($otherRegistrant->user->email);
        }
        $this->actingAs($admin)->get('/admin/seminars/attendance?'.http_build_query([
            'event' => $seminar->title, 'connector_id' => $connector->id,
            'event_date' => $seminar->localStartsAt()->toDateString(), 'attendance_status' => 'attended',
        ]))->assertOk()->assertSee($seminar->title)->assertSee($registrant->user->email)
            ->assertDontSee($otherRegistrant->user->email)->assertDontSee('Save decision');
        $this->actingAs($admin)->get('/admin/seminars/attendance?'.http_build_query([
            'connector_id' => $connector->id, 'attendance_status' => 'not_submitted',
        ]))->assertOk()->assertDontSee($registrant->user->email);
        $log = ActivityLog::where('activity_type', 'seminar_attendance_corrected')->latest('id')->firstOrFail();
        $this->actingAs($admin)->get(route('admin.seminars.attendance', $seminar))
            ->assertOk()->assertSee('Confirmed by host')->assertSee($owner->name)->assertSee('Attendance audit history');
        $this->assertSame($seminar->id, $log->metadata['seminar_id']);
        $this->actingAs($owner)->get('/admin/seminars/attendance')->assertForbidden();
    }

    public function test_admin_cannot_submit_a_code_or_use_native_attendance_writes(): void
    {
        [$owner, $connector, $seminar, , $admin] = $this->event();
        $code = app(SeminarCodeAttendanceService::class)->generate($seminar, $owner);
        $this->actingAs($admin)->postJson(route('seminars.attendance.code.submit', $seminar), ['code' => $code])->assertForbidden();
        $connector->memberships()->create([
            'user_id' => $admin->id, 'connector_role_id' => $connector->roles()->firstOrFail()->id,
            'status' => 'active', 'accepted_at' => now(),
        ]);
        $seminar->update([
            'type' => 'webinar', 'event_format' => 'native', 'livestream_status' => 'live',
            'livestream_channel' => 'admin-attendance-check', 'livestream_started_at' => now(),
        ]);
        foreach (['join', 'heartbeat', 'leave'] as $action) {
            $this->actingAs($admin)->postJson(route('seminars.attendance.'.$action, $seminar))->assertForbidden();
        }
        $this->assertSame(0, $seminar->attendances()->count());
    }

    private function event(): array
    {
        $owner = $this->createCompletedLearner();
        $connector = $this->createVerifiedConnector($owner);
        $seminar = $connector->seminars()->create([
            'title' => 'Attendance class '.str()->random(8), 'description' => 'Class', 'purpose' => 'Learn',
            'type' => 'seminar', 'event_format' => 'in_person', 'category' => 'health',
            'status' => 'published', 'starts_at' => now()->subMinutes(5), 'ends_at' => now()->addHour(),
            'schedule' => now()->subMinutes(5), 'target_participants' => 'learners', 'learner_age_categories' => ['adult'],
        ]);
        $learner = $this->createCompletedLearner();
        $registrant = $seminar->registrants()->create([
            'user_id' => $learner->id, 'status' => 'registered', 'participant_type' => 'learner', 'registered_at' => now(),
        ]);
        $admin = User::factory()->create(['role' => 'admin']);
        $admin->assignRole('admin');

        return [$owner, $connector, $seminar, $registrant->load('user'), $admin];
    }
}
