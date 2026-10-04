<?php

namespace Tests\Feature\Connectors;

use App\Models\ActivityLog;
use App\Services\Seminars\SeminarCodeAttendanceService;
use Tests\TestCase;

class SeminarManualAttendanceTest extends TestCase
{
    use ConnectorTestHelpers;

    public function test_manager_marks_registered_participant_present_and_audits_the_decision(): void
    {
        [$owner, $connector, $seminar, $registrant] = $this->event();

        $this->actingAs($owner)->post(route('connector.seminars.attendance.manual', [$connector, $seminar, $registrant]), [
            'attended' => true,
        ])->assertRedirect()->assertSessionHas('success');

        $attendance = $seminar->attendances()->where('user_id', $registrant->user_id)->firstOrFail();
        $this->assertSame('manual', $attendance->attendance_method);
        $this->assertSame('attended', $attendance->status);
        $this->assertNotNull($attendance->attended_at);
        $this->assertSame($attendance->attended_at->timestamp, $registrant->fresh()->attended_at->timestamp);
        $this->assertSame(1, $seminar->attendances()->where('user_id', $registrant->user_id)->count());

        $log = ActivityLog::where('activity_type', 'seminar_attendance_corrected')->latest('id')->firstOrFail();
        $this->assertSame($owner->id, $log->user_id);
        $this->assertSame($owner->id, $log->metadata['actor_id']);
        $this->assertSame($seminar->id, $log->metadata['seminar_id']);
        $this->assertSame($registrant->user_id, $log->metadata['participant_id']);
        $this->assertSame('mark_present', $log->metadata['action']);
        $this->assertNull($log->metadata['reason']);
        $this->assertNull($log->metadata['before']);
        $this->assertSame('attended', $log->metadata['after']['status']);
    }

    public function test_code_decision_can_be_corrected_with_reason_and_code_cannot_restore_it(): void
    {
        [$owner, $connector, $seminar, $registrant] = $this->event();
        $code = app(SeminarCodeAttendanceService::class)->generate($seminar, null, null);
        $this->actingAs($registrant->user)->post(route('seminars.attendance.code.submit', $seminar), ['code' => $code])->assertSessionHas('success');

        $this->actingAs($owner)->post(route('connector.seminars.attendance.manual', [$connector, $seminar, $registrant]), [
            'attended' => false,
            'reason' => 'Host corrected mistaken check-in',
        ])->assertRedirect()->assertSessionHas('success');

        $attendance = $seminar->attendances()->where('user_id', $registrant->user_id)->firstOrFail();
        $this->assertSame('manual', $attendance->attendance_method);
        $this->assertSame('not_present', $attendance->status);
        $this->assertNull($attendance->attended_at);
        $this->assertNull($registrant->fresh()->attended_at);
        $log = ActivityLog::where('activity_type', 'seminar_attendance_corrected')->latest('id')->firstOrFail();
        $this->assertSame('attendance_code', $log->metadata['before']['attendance_method']);
        $this->assertSame('attended', $log->metadata['before']['status']);
        $this->assertSame('not_present', $log->metadata['after']['status']);
        $this->assertSame('Host corrected mistaken check-in', $log->metadata['reason']);

        $this->actingAs($registrant->user)->post(route('seminars.attendance.code.submit', $seminar), ['code' => $code])->assertSessionHasErrors('code');
        $this->assertSame('not_present', $attendance->fresh()->status);
    }

    public function test_same_state_code_decision_requires_a_reason_to_transfer_to_manual_control(): void
    {
        [$owner, $connector, $seminar, $registrant] = $this->event();
        $code = app(SeminarCodeAttendanceService::class)->generate($seminar, null, null);
        $this->actingAs($registrant->user)->post(route('seminars.attendance.code.submit', $seminar), ['code' => $code])->assertSessionHas('success');
        $route = route('connector.seminars.attendance.manual', [$connector, $seminar, $registrant]);

        $this->actingAs($owner)->post($route, ['attended' => true])->assertSessionHasErrors('reason');
        $this->assertSame('attendance_code', $seminar->attendances()->firstOrFail()->attendance_method);
        $this->actingAs($owner)->post($route, ['attended' => true, 'reason' => 'Host verified the check-in'])->assertRedirect();
        $this->assertSame('manual', $seminar->attendances()->firstOrFail()->attendance_method);
    }

    public function test_removal_and_correction_require_reason_but_repeat_same_manual_state_is_idempotent(): void
    {
        [$owner, $connector, $seminar, $registrant] = $this->event();
        $route = route('connector.seminars.attendance.manual', [$connector, $seminar, $registrant]);

        $this->actingAs($owner)->post($route, ['attended' => false])->assertSessionHasErrors('reason');
        $this->assertSame(0, $seminar->attendances()->count());
        $this->actingAs($owner)->post($route, ['attended' => true])->assertSessionHas('success');
        $first = $seminar->attendances()->firstOrFail();
        $firstTime = $first->attended_at->timestamp;
        $logCount = ActivityLog::where('activity_type', 'seminar_attendance_corrected')->count();

        $this->actingAs($owner)->post($route, ['attended' => true])->assertSessionHas('success');
        $this->assertSame($firstTime, $first->fresh()->attended_at->timestamp);
        $this->assertSame($logCount, ActivityLog::where('activity_type', 'seminar_attendance_corrected')->count());
        $this->actingAs($owner)->post($route, ['attended' => false])->assertSessionHasErrors('reason');
        $this->assertSame('attended', $first->fresh()->status);
        $this->actingAs($owner)->post($route, ['attended' => false, 'reason' => 'Absent'])->assertSessionHas('success');
        $this->assertSame('not_present', $first->fresh()->status);
        $this->assertNull($registrant->fresh()->attended_at);
        $this->assertSame($logCount + 1, ActivityLog::where('activity_type', 'seminar_attendance_corrected')->count());
        $this->actingAs($owner)->post($route, ['attended' => false])->assertSessionHas('success');
        $this->assertSame($logCount + 1, ActivityLog::where('activity_type', 'seminar_attendance_corrected')->count());
    }

    public function test_manager_authorization_and_active_registration_are_enforced(): void
    {
        [$owner, $connector, $seminar, $registrant] = $this->event();
        $outsider = $this->createCompletedLearner();
        $otherConnector = $this->createVerifiedConnector($outsider);
        $route = route('connector.seminars.attendance.manual', [$connector, $seminar, $registrant]);
        $this->actingAs($outsider)->post($route, ['attended' => true])->assertForbidden();
        $this->actingAs($owner)->post(route('connector.seminars.attendance.manual', [$otherConnector, $seminar, $registrant]), ['attended' => true])->assertForbidden();
        $this->actingAs($outsider)->post(route('connector.seminars.attendance.manual', [$otherConnector, $seminar, $registrant]), ['attended' => true])->assertNotFound();

        $walkIn = $this->createCompletedLearner();
        $this->actingAs($owner)->post(route('connector.seminars.attendance.manual', [$connector, $seminar, $walkIn->id + 10000]), ['attended' => true])->assertNotFound();
        $registrant->update(['status' => 'pending']);
        $this->actingAs($owner)->post($route, ['attended' => true])->assertSessionHasErrors('attended');
        $registrant->update(['status' => 'registered', 'cancelled_at' => now()]);
        $this->actingAs($owner)->post($route, ['attended' => true])->assertSessionHasErrors('attended');
        $this->assertSame(0, $seminar->attendances()->count());
    }

    public function test_native_heartbeat_leave_and_completion_preserve_manual_decision_and_duration(): void
    {
        [$owner, $connector, $seminar, $registrant] = $this->event([
            'type' => 'webinar', 'event_format' => 'native',
            'livestream_channel' => 'manual-native-channel', 'livestream_status' => 'live', 'livestream_started_at' => now(),
        ]);
        $attendance = $seminar->attendances()->create([
            'user_id' => $registrant->user_id, 'role' => 'audience', 'joined_at' => now()->subMinutes(8),
            'total_seconds' => 60, 'status' => 'joined', 'attendance_method' => 'native',
        ]);
        $this->actingAs($owner)->post(route('connector.seminars.attendance.manual', [$connector, $seminar, $registrant]), [
            'attended' => false, 'reason' => 'Participant did not attend the teaching session',
        ])->assertRedirect()->assertSessionHas('success');
        $this->assertSame('audience', $attendance->fresh()->role);
        $this->assertNotNull($attendance->fresh()->joined_at);
        $this->assertSame(60, $attendance->fresh()->total_seconds);

        $this->actingAs($registrant->user)->postJson(route('seminars.attendance.join', $seminar))->assertOk();
        $attendance->fresh()->update(['joined_at' => now()->subMinutes(8)]);
        $this->actingAs($registrant->user)->postJson(route('seminars.attendance.heartbeat', $seminar))->assertOk();
        $this->actingAs($registrant->user)->postJson(route('seminars.attendance.leave', $seminar))->assertOk();
        $this->actingAs($owner)->post(route('connector.seminars.complete', [$connector, $seminar]))->assertRedirect();
        $attendance->refresh();
        $this->assertSame('manual', $attendance->attendance_method);
        $this->assertSame('not_present', $attendance->status);
        $this->assertNull($attendance->attended_at);
        $this->assertNull($registrant->fresh()->attended_at);
        $this->assertNotNull($attendance->left_at);
        $this->assertGreaterThan(60, $attendance->total_seconds);
        $this->assertSame('audience', $attendance->role);
    }

    public function test_native_interactions_preserve_manual_present_timestamp(): void
    {
        [$owner, $connector, $seminar, $registrant] = $this->event([
            'type' => 'webinar', 'event_format' => 'native',
            'livestream_channel' => 'manual-present-channel', 'livestream_status' => 'live', 'livestream_started_at' => now(),
        ]);
        $this->actingAs($owner)->post(route('connector.seminars.attendance.manual', [$connector, $seminar, $registrant]), ['attended' => true])->assertSessionHas('success');
        $attendance = $seminar->attendances()->where('user_id', $registrant->user_id)->firstOrFail();
        $markedAt = $attendance->attended_at->timestamp;

        $this->actingAs($registrant->user)->postJson(route('seminars.attendance.join', $seminar))->assertOk();
        $this->actingAs($registrant->user)->postJson(route('seminars.attendance.heartbeat', $seminar))->assertOk();
        $this->actingAs($registrant->user)->postJson(route('seminars.attendance.leave', $seminar))->assertOk();
        $this->actingAs($owner)->post(route('connector.seminars.complete', [$connector, $seminar]))->assertRedirect();

        $this->assertSame('manual', $attendance->fresh()->attendance_method);
        $this->assertSame('attended', $attendance->fresh()->status);
        $this->assertSame($markedAt, $attendance->fresh()->attended_at->timestamp);
        $this->assertSame($markedAt, $registrant->fresh()->attended_at->timestamp);
    }

    public function test_external_completion_does_not_finalize_manual_duration(): void
    {
        [$owner, $connector, $seminar, $registrant] = $this->event(['event_format' => 'external', 'type' => 'webinar', 'external_url' => 'https://example.test/meeting']);
        $attendance = $seminar->attendances()->create([
            'user_id' => $registrant->user_id, 'role' => 'audience', 'joined_at' => now()->subMinutes(8),
            'total_seconds' => 20, 'status' => 'attended', 'attendance_method' => 'manual', 'attended_at' => now(),
        ]);
        $this->actingAs($owner)->post(route('connector.seminars.complete', [$connector, $seminar]))->assertRedirect();
        $this->assertNull($attendance->fresh()->left_at);
        $this->assertSame(20, $attendance->fresh()->total_seconds);
    }

    private function event(array $overrides = []): array
    {
        $owner = $this->createCompletedLearner();
        $connector = $this->createVerifiedConnector($owner);
        $seminar = $connector->seminars()->create(array_merge([
            'title' => 'Attendance event', 'description' => 'Class', 'purpose' => 'Learn',
            'type' => 'seminar', 'event_format' => 'in_person', 'category' => 'health',
            'status' => 'published', 'starts_at' => now()->subMinutes(5), 'ends_at' => now()->addMinutes(55),
            'schedule' => now()->subMinutes(5), 'target_participants' => 'learners', 'learner_age_categories' => ['adult'],
        ], $overrides));
        $learner = $this->createCompletedLearner();
        $registrant = $seminar->registrants()->create([
            'user_id' => $learner->id, 'status' => 'registered', 'participant_type' => 'learner', 'registered_at' => now(),
        ]);

        return [$owner, $connector, $seminar, $registrant->load('user')];
    }
}
