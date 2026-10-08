<?php

namespace Tests\Feature\Admin;

use App\Models\ActivityLog;
use App\Models\User;
use Tests\Feature\Connectors\ConnectorTestHelpers;
use Tests\TestCase;

class AdminSeminarAttendanceTest extends TestCase
{
    use ConnectorTestHelpers;

    public function test_admin_can_correct_registered_attendance_on_another_connectors_event(): void
    {
        $owner = $this->createCompletedLearner();
        $connector = $this->createVerifiedConnector($owner);
        $seminar = $connector->seminars()->create([
            'title' => 'External class', 'description' => 'Class', 'purpose' => 'Learn',
            'type' => 'webinar', 'event_format' => 'external', 'external_url' => 'https://example.test/meeting',
            'category' => 'health', 'status' => 'published', 'starts_at' => now()->subMinutes(5),
            'ends_at' => now()->addHour(), 'schedule' => now()->subMinutes(5),
            'target_participants' => 'learners', 'learner_age_categories' => ['adult'],
        ]);
        $learner = $this->createCompletedLearner();
        $registrant = $seminar->registrants()->create([
            'user_id' => $learner->id, 'status' => 'registered', 'participant_type' => 'learner', 'registered_at' => now(),
        ]);
        $admin = User::factory()->create(['role' => 'admin']);
        $admin->assignRole('admin');

        $this->actingAs($admin)->post(route('admin.seminars.attendance.manual', [$seminar, $registrant]), [
            'attended' => false, 'reason' => 'Attendance list corrected by host',
        ])->assertRedirect();
        $this->assertDatabaseHas('seminar_attendances', [
            'seminar_id' => $seminar->id, 'user_id' => $learner->id,
            'attendance_method' => 'manual', 'status' => 'not_present', 'attended_at' => null,
        ]);
        $this->assertNull($registrant->fresh()->attended_at);
        $log = ActivityLog::where('activity_type', 'seminar_attendance_corrected')->latest('id')->firstOrFail();
        $this->assertSame($admin->id, $log->user_id);
        $this->assertSame('mark_not_present', $log->metadata['action']);
    }

    public function test_non_admin_cannot_use_admin_manual_action_and_cross_event_registrant_is_rejected(): void
    {
        $owner = $this->createCompletedLearner();
        $connector = $this->createVerifiedConnector($owner);
        $seminar = $connector->seminars()->create([
            'title' => 'Class', 'description' => 'Class', 'purpose' => 'Learn',
            'type' => 'seminar', 'event_format' => 'in_person', 'category' => 'health',
            'status' => 'published', 'starts_at' => now(), 'ends_at' => now()->addHour(),
            'schedule' => now(), 'target_participants' => 'learners', 'learner_age_categories' => ['adult'],
        ]);
        $other = $seminar->replicate();
        $other->title = 'Other class';
        $other->save();
        $learner = $this->createCompletedLearner();
        $registrant = $other->registrants()->create([
            'user_id' => $learner->id, 'status' => 'registered', 'participant_type' => 'learner', 'registered_at' => now(),
        ]);
        $route = route('admin.seminars.attendance.manual', [$seminar, $registrant]);
        $this->actingAs($owner)->post($route, ['attended' => true])->assertForbidden();
        $admin = User::factory()->create(['role' => 'admin']);
        $admin->assignRole('admin');
        $this->actingAs($admin)->post($route, ['attended' => true])->assertNotFound();
    }
}
