<?php

namespace Tests\Feature\Connectors;

use App\Models\ActivityLog;
use App\Services\Seminars\SeminarCodeAttendanceService;
use App\Services\Seminars\SeminarManualAttendanceService;
use Illuminate\Support\Facades\Crypt;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class ConnectorAttendanceOwnershipTest extends TestCase
{
    use ConnectorTestHelpers;

    public function test_management_checks_route_ownership_membership_and_assigned_permission(): void
    {
        [$owner, $connector, $seminar, $registrant] = $this->event();
        $outsider = $this->createCompletedLearner();
        $otherConnector = $this->createVerifiedConnector($outsider);
        $member = $this->createAdultConnectorMember($connector);
        $manager = $this->createAdultConnectorMember($connector, ['connector.manage_seminars']);
        foreach (['code.generate', 'code.disable', 'manual'] as $action) {
            $parameters = [$connector, $seminar];
            if ($action === 'manual') {
                $parameters[] = $registrant;
            }
            $this->actingAs($member)->postJson(route('connector.seminars.attendance.'.$action, $parameters), ['attended' => true])->assertForbidden();
            $parameters[0] = $otherConnector;
            $this->actingAs($outsider)->postJson(route('connector.seminars.attendance.'.$action, $parameters), ['attended' => true])->assertNotFound();
        }
        foreach ([
            fn () => app(SeminarCodeAttendanceService::class)->generate($seminar, $outsider),
            fn () => app(SeminarCodeAttendanceService::class)->disable($seminar, $member),
            fn () => app(SeminarManualAttendanceService::class)->set($seminar, $registrant, $outsider, true, 'Attempt'),
        ] as $operation) {
            try {
                $operation();
                $this->fail('Unauthorized service mutation must be forbidden.');
            } catch (HttpException $exception) {
                $this->assertSame(403, $exception->getStatusCode());
            }
        }
        $this->actingAs($manager)->post(route('connector.seminars.attendance.code.generate', [$connector, $seminar]))->assertRedirect();
        $connector->memberships()->where('user_id', $manager->id)->update(['status' => 'inactive']);
        $this->actingAs($manager)->get(route('connector.seminars.attendance', [$connector, $seminar]))->assertForbidden();
        $this->actingAs($owner)->get(route('connector.seminars.attendance', [$connector, $seminar]))->assertOk();
    }

    public function test_lifecycle_and_submission_are_audited_without_secrets_and_use_server_identity_and_time(): void
    {
        [$owner, $connector, $seminar, $registrant] = $this->event();
        $this->travelTo(now()->startOfSecond());
        $this->actingAs($owner)->post(route('connector.seminars.attendance.code.generate', [$connector, $seminar]))->assertRedirect();
        $first = Crypt::decryptString(session('generated_attendance_code'));
        $this->actingAs($owner)->post(route('connector.seminars.attendance.code.generate', [$connector, $seminar]))->assertRedirect();
        $code = Crypt::decryptString(session('generated_attendance_code'));
        $this->actingAs($registrant->user)->post(route('seminars.attendance.code.submit', $seminar), [
            'code' => $code, 'user_id' => $owner->id, 'seminar_id' => $seminar->id + 1,
            'attended_at' => '2000-01-01 00:00:00', 'status' => 'not_present',
        ])->assertSessionHas('success');
        $attendance = $seminar->attendances()->sole();
        $this->assertSame($registrant->user_id, $attendance->user_id);
        $this->assertSame(now()->timestamp, $attendance->attended_at->timestamp);
        $this->assertSame($seminar->id, $attendance->seminar_id);
        $this->actingAs($owner)->post(route('connector.seminars.attendance.code.disable', [$connector, $seminar]))->assertRedirect();
        $logs = ActivityLog::where('metadata->seminar_id', $seminar->id)->orderBy('id')->get();
        $this->assertSame([
            'seminar_attendance_code_generated', 'seminar_attendance_code_regenerated',
            'seminar_attendance_submitted', 'seminar_attendance_code_closed',
        ], $logs->pluck('activity_type')->all());
        $this->assertSame([$owner->id, $owner->id, $registrant->user_id, $owner->id], $logs->pluck('user_id')->all());
        $this->assertStringNotContainsString($first, $logs->toJson());
        $this->assertStringNotContainsString($code, $logs->toJson());
        $this->assertStringNotContainsString('attendance_code_hash', $logs->toJson());
        $this->assertStringNotContainsString($code, $seminar->fresh()->toJson());
        $this->actingAs($owner)->get(route('connector.seminars.attendance', [$connector, $seminar]))
            ->assertOk()->assertSee('1 attendance submissions');
    }

    public function test_service_audits_use_the_authorized_actor_instead_of_ambient_authentication(): void
    {
        [$owner, , $seminar, $registrant] = $this->event();
        $code = app(SeminarCodeAttendanceService::class)->generate($seminar, $owner);
        $this->actingAs($registrant->user);
        app(SeminarCodeAttendanceService::class)->submit($registrant->user, $seminar, $code, '127.0.0.2');
        app(SeminarManualAttendanceService::class)->set($seminar, $registrant, $owner, false, 'Host correction');
        app(SeminarCodeAttendanceService::class)->disable($seminar, $owner);
        $logs = ActivityLog::where('metadata->seminar_id', $seminar->id)->orderBy('id')->get();
        $this->assertSame([$owner->id, $registrant->user_id, $owner->id, $owner->id], $logs->pluck('user_id')->all());
        $this->assertSame($logs->pluck('user_id')->all(), $logs->pluck('metadata.actor_id')->all());
    }

    private function event(): array
    {
        $owner = $this->createCompletedLearner();
        $connector = $this->createVerifiedConnector($owner);
        $seminar = $connector->seminars()->create([
            'title' => 'Host attendance', 'description' => 'Class', 'purpose' => 'Learn',
            'type' => 'seminar', 'event_format' => 'in_person', 'category' => 'health',
            'status' => 'published', 'starts_at' => now()->subMinutes(5), 'ends_at' => now()->addHour(),
            'schedule' => now()->subMinutes(5), 'target_participants' => 'learners', 'learner_age_categories' => ['adult'],
        ]);
        $learner = $this->createCompletedLearner();
        $registrant = $seminar->registrants()->create([
            'user_id' => $learner->id, 'status' => 'registered', 'participant_type' => 'learner', 'registered_at' => now(),
        ]);

        return [$owner, $connector, $seminar, $registrant->load('user')];
    }
}
