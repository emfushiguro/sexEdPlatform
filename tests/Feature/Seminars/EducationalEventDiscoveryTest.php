<?php

namespace Tests\Feature\Seminars;

use App\Models\Connector;
use App\Models\Seminar;
use App\Models\User;
use Tests\Feature\Connectors\ConnectorTestHelpers;
use Tests\TestCase;

class EducationalEventDiscoveryTest extends TestCase
{
    use ConnectorTestHelpers;

    public function test_published_detail_admits_accepted_speaker_and_manager_outside_audience(): void
    {
        $owner = $this->createCompletedLearner();
        $connector = $this->createVerifiedConnector($owner);
        $speaker = User::factory()->create(['role' => 'instructor']);
        $speaker->assignRole('instructor');
        $seminar = $this->event($connector, ['target_participants' => 'learners']);
        $seminar->speakers()->create(['user_id' => $speaker->id, 'display_name' => 'Guest Speaker', 'role' => 'speaker', 'status' => 'accepted']);

        $this->actingAs($speaker)->get(route('seminars.show', $seminar))->assertOk();
        $this->actingAs($owner)->get(route('seminars.show', $seminar))->assertOk();
        $this->actingAs($speaker)->get(route('seminars.index'))->assertOk()->assertDontSee($seminar->title);
    }

    public function test_completed_detail_requires_active_registration_or_authorized_role(): void
    {
        $owner = $this->createCompletedLearner();
        $connector = $this->createVerifiedConnector($owner);
        $registered = $this->createCompletedLearner();
        $workspaceMember = $this->createAdultConnectorMember($connector);
        $pending = $this->createCompletedLearner();
        $unrelated = $this->createCompletedLearner();
        $admin = User::factory()->create(['role' => 'admin']);
        $admin->assignRole('admin');
        $speaker = User::factory()->create(['role' => 'instructor']);
        $speaker->assignRole('instructor');
        $seminar = $this->event($connector, ['status' => 'completed']);
        $seminar->registrants()->create(['user_id' => $registered->id, 'status' => 'registered', 'participant_type' => 'learner', 'registered_at' => now()]);
        $seminar->registrants()->create(['user_id' => $workspaceMember->id, 'status' => 'registered', 'participant_type' => 'learner', 'registered_at' => now()]);
        $seminar->registrants()->create(['user_id' => $pending->id, 'status' => 'pending', 'participant_type' => 'learner', 'registered_at' => now()]);
        $seminar->speakers()->create(['user_id' => $speaker->id, 'display_name' => $speaker->name, 'role' => 'speaker', 'status' => 'accepted']);
        $seminar->attendances()->create([
            'user_id' => $workspaceMember->id,
            'role' => 'audience',
            'status' => 'attended',
            'attendance_method' => 'attendance_code',
            'attended_at' => now(),
        ]);

        $this->actingAs($registered)->get(route('seminars.show', $seminar))
            ->assertOk()->assertSee('Registered')->assertDontSee('Cancel Registration');
        $this->actingAs($speaker)->get(route('seminars.show', $seminar))->assertOk();
        $this->actingAs($owner)->get(route('seminars.show', $seminar))->assertOk();
        $this->actingAs($admin)->get(route('seminars.show', $seminar))->assertOk();
        $this->actingAs($workspaceMember)->get(route('connector.seminars.show', [$connector, $seminar]))
            ->assertOk()->assertSee('Attendance submitted');
        $this->actingAs($pending)->get(route('seminars.show', $seminar))->assertForbidden();
        $this->actingAs($unrelated)->get(route('seminars.show', $seminar))->assertForbidden();
        $this->actingAs($registered)->get(route('seminars.index'))->assertOk()->assertDontSee($seminar->title);

        $seminar->update(['learner_age_categories' => ['teen']]);
        $this->actingAs($registered)->get(route('seminars.show', $seminar))->assertForbidden();
        $this->actingAs($speaker)->get(route('seminars.show', $seminar))->assertOk();
    }

    public function test_discovery_lists_only_published_eligible_events_and_detail_shows_safe_metadata(): void
    {
        $owner = $this->createCompletedLearner();
        $connector = $this->createVerifiedConnector($owner);
        $learner = $this->createCompletedLearner();
        $seminar = $this->event($connector, [
            'title' => 'External Skills Webinar',
            'type' => 'webinar',
            'event_format' => 'external',
            'external_platform' => 'zoom',
            'external_url' => 'https://zoom.example.test/secret-join',
        ]);
        $seminar->speakers()->create(['display_name' => 'Speaker Example', 'role' => 'speaker', 'status' => 'accepted']);
        $this->event($connector, ['title' => 'Hidden Draft', 'status' => 'draft']);
        $this->event($connector, ['title' => 'Instructor Only', 'target_participants' => 'instructors']);
        $this->event($connector, ['title' => 'Completed Event', 'status' => 'completed']);

        $this->actingAs($learner)->get(route('seminars.index'))
            ->assertOk()->assertSee('External Skills Webinar')->assertSee('Webinar')
            ->assertSee('External Platform')->assertSee($connector->name)
            ->assertDontSee('Hidden Draft')->assertDontSee('Instructor Only')->assertDontSee('Completed Event')
            ->assertDontSee('secret-join');
        $this->actingAs($learner)->get(route('seminars.show', $seminar))
            ->assertOk()->assertSee('Webinar')->assertSee('External Platform')
            ->assertSee($connector->name)->assertSee('Speaker Example')->assertSee('Zoom')
            ->assertSee('Attendance')->assertDontSee('secret-join');

        $venue = $this->event($connector, ['title' => 'Onsite Skills', 'type' => 'seminar', 'event_format' => 'in_person', 'location' => 'Community Hall', 'venue_address' => 'Main Street', 'venue_room' => 'Room 2']);
        $this->actingAs($learner)->get(route('seminars.show', $venue))
            ->assertOk()->assertSee('In Person')->assertSee('Community Hall')->assertSee('Main Street')->assertSee('Room 2');
    }

    private function event(Connector $connector, array $overrides = []): Seminar
    {
        return $connector->seminars()->create(array_merge([
            'title' => 'Education Session',
            'description' => 'A learning session.',
            'purpose' => 'Learn useful skills.',
            'type' => 'seminar',
            'event_format' => 'in_person',
            'location' => 'Community Hall',
            'category' => 'health',
            'status' => 'published',
            'starts_at' => now()->addDay(),
            'ends_at' => now()->addDay()->addHour(),
            'schedule' => now()->addDay(),
            'target_participants' => 'learners',
            'learner_age_categories' => ['adult'],
        ], $overrides));
    }
}
