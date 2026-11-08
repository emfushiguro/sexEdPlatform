<?php

namespace Tests\Feature\Seminars;

use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Tests\Feature\Connectors\ConnectorTestHelpers;
use Tests\TestCase;

class SeminarSpeakerExperienceTest extends TestCase
{
    use ConnectorTestHelpers;

    private const URL = 'https://example.test/private-class?secret=speaker-only';

    public function test_invitation_displays_organization_logo_and_accepted_speaker_has_no_cancel_action(): void
    {
        Storage::fake(config('connector_verification.logo_disk', 'public'));
        [$event, $user, $speaker] = $this->invitation();
        $connector = $event->connector;
        $connector->update(['logo_path' => 'connectors/logos/organization.png']);
        $logoUrl = Storage::disk(config('connector_verification.logo_disk', 'public'))->url($connector->logo_path);
        $this->actingAs($user)->get(route('instructor.speaker-invitations.show', $speaker))
            ->assertOk()->assertSee($logoUrl, false)->assertSee('Confirmed speaker')
            ->assertDontSee('Accept Invitation')->assertDontSee('>Cancel</button>', false);
        $this->get(route('instructor.speaker-invitations.index'))->assertOk()->assertSee($logoUrl, false);
        $this->actingAs($connector->primaryRepresentative)->get(route('connector.seminars.show', [$connector, $event]))
            ->assertOk()->assertDontSee(route('connector.seminars.speakers.destroy', [$connector, $event, $speaker]), false);
        $speaker->update(['status' => 'pending']);
        $this->get(route('connector.seminars.show', [$connector, $event]))->assertOk()
            ->assertSee(route('connector.seminars.speakers.destroy', [$connector, $event, $speaker]), false);
    }

    public function test_accepted_external_speaker_prepares_before_release_without_url_in_html(): void
    {
        [$event, $user, $speaker] = $this->invitation();
        $event->speakers()->create(['display_name' => 'Confirmed colleague', 'status' => 'accepted', 'role' => 'speaker']);
        $event->speakers()->create(['display_name' => 'Pending colleague', 'status' => 'pending', 'role' => 'speaker']);
        $event->speakers()->create(['display_name' => 'Declined colleague', 'status' => 'declined', 'role' => 'speaker']);
        $this->actingAs($user)->get(route('instructor.speaker-invitations.show', $speaker))
            ->assertOk()->assertSee('Event: Upcoming')->assertSee('Open external event')
            ->assertSee('Confirmed colleague')->assertDontSee('Pending colleague')->assertDontSee('Declined colleague')
            ->assertDontSee('Join Livestream')->assertDontSee(self::URL, false);
        $this->get(route('seminars.external.join', $event))->assertRedirect(self::URL);
        $this->get(route('instructor.speaker-invitations.index'))->assertOk()->assertSee('Event: Upcoming');
    }

    public function test_external_lifecycle_guidance_and_access_recheck(): void
    {
        [$event, $user, $speaker] = $this->invitation();
        $this->actingAs($user);
        foreach (['draft' => 'Awaiting publication', 'cancelled' => 'Cancelled'] as $status => $label) {
            $event->update(['status' => $status]);
            $this->get(route('instructor.speaker-invitations.show', $speaker))->assertOk()->assertSee('Event: '.$label)->assertDontSee('Open external event')->assertDontSee(self::URL, false);
            $this->get(route('seminars.external.join', $event))->assertForbidden();
        }
        $event->update(['status' => 'published', 'starts_at' => now()->subMinute()]);
        $this->get(route('instructor.speaker-invitations.show', $speaker))->assertOk()->assertSee('Event: Happening now');
        $event->update(['status' => 'completed', 'ends_at' => now()->subMinute()]);
        $this->get(route('instructor.speaker-invitations.show', $speaker))->assertOk()->assertSee('Event: Completed')->assertSee('Open external event');
        $event->update(['external_link_expiry_mode' => 'event_end']);
        $this->get(route('instructor.speaker-invitations.show', $speaker))->assertOk()->assertSee('The join link has expired.')->assertDontSee('Open external event');
        $this->get(route('seminars.external.join', $event))->assertForbidden();
        $event->update(['external_url' => null]);
        $this->get(route('instructor.speaker-invitations.show', $speaker))->assertOk()->assertSee('The join link is unavailable.');
    }

    public function test_pending_declined_and_expired_invitations_have_no_event_access(): void
    {
        [$event, $user, $speaker] = $this->invitation();
        $this->actingAs($user);
        foreach (['pending', 'declined'] as $state) {
            $speaker->update(['status' => $state]);
            $this->get(route('instructor.speaker-invitations.show', $speaker))->assertOk()->assertSee('Invitation: '.$state)->assertDontSee('Open external event');
            $this->get(route('seminars.external.join', $event))->assertForbidden();
        }
        $speaker->update(['status' => 'pending', 'expires_at' => now()->subMinute()]);
        $this->get(route('instructor.speaker-invitations.show', $speaker))->assertOk()->assertSee('Invitation: expired')->assertDontSee('Accept Invitation');
        $other = User::factory()->create(['role' => 'instructor']);
        $other->assignRole('instructor');
        $this->actingAs($other)->get(route('instructor.speaker-invitations.show', $speaker))->assertForbidden();
    }

    public function test_in_person_and_native_speaker_guidance_matches_delivery(): void
    {
        [$event, $user, $speaker] = $this->invitation();
        $this->actingAs($user);
        $event->update(['event_format' => 'in_person', 'location' => 'Learning Hall', 'venue_address' => '123 Main Street', 'venue_room' => 'Room 4', 'delivery_instructions' => 'Bring your workbook.']);
        $this->get(route('instructor.speaker-invitations.show', $speaker))->assertOk()->assertSee('Learning Hall')->assertSee('123 Main Street')->assertSee('Room 4')->assertSee('Bring your workbook.')->assertDontSee('Join Livestream')->assertDontSee('Open external event');
        $event->update(['event_format' => 'native', 'livestream_channel' => 'test-speaker-channel', 'livestream_status' => 'scheduled', 'starts_at' => now()->addMinutes(5)]);
        $this->get(route('instructor.speaker-invitations.show', $speaker))->assertOk()->assertSee('The livestream is not open.')->assertDontSee('Join Livestream');
        $event->update(['livestream_status' => 'live']);
        $this->get(route('instructor.speaker-invitations.show', $speaker))->assertOk()->assertSee('Join Livestream')->assertSee(route('seminars.join', $event));
        $event->update(['status' => 'completed']);
        $this->get(route('instructor.speaker-invitations.show', $speaker))->assertOk()->assertSee('Livestream access is closed.')->assertDontSee('Join Livestream');
    }

    public function test_admin_review_contains_delivery_submission_metadata_and_no_code_secret(): void
    {
        [$event, $user] = $this->invitation();
        $event->update(['description' => 'Detailed review description', 'purpose' => 'Review learning objectives', 'delivery_instructions' => 'Read the host handbook.', 'submitted_for_review_by' => $user->id, 'submitted_for_review_at' => now(), 'published_by' => $user->id, 'published_at' => now(), 'registration_deadline_at' => null, 'attendance_code_hash' => 'hashed-attendance-secret', 'external_link_visible_at' => null]);
        $admin = User::factory()->create(['role' => 'admin']);
        $admin->assignRole('admin');
        $this->actingAs($admin)->get(route('admin.seminars.show', $event))->assertOk()
            ->assertSee('Detailed review description')->assertSee('Review learning objectives')->assertSee('Read the host handbook.')
            ->assertSee('Platform')->assertSee('Link release')->assertSee('Link expiry')->assertSee('Duration')->assertSee('Registration deadline')
            ->assertSee('Immediately after confirmation')->assertSee('Closes when the event starts')->assertDontSee('No Location Provided')->assertSee('Submitted by / at')->assertSee('Published by / at')->assertSee('Confirmed speaker')
            ->assertDontSee('hashed-attendance-secret')->assertDontSee('Generate attendance code')->assertDontSee('Post code');
    }

    private function invitation(): array
    {
        $connector = $this->createVerifiedConnector($this->createCompletedLearner());
        $user = User::factory()->create(['role' => 'instructor']);
        $user->assignRole('instructor');
        $event = $connector->seminars()->create([
            'title' => 'Speaker experience event', 'description' => 'A useful event', 'purpose' => 'Learn together',
            'type' => 'webinar', 'event_format' => 'external', 'external_platform' => 'google_classroom',
            'external_url' => self::URL, 'external_link_expiry_mode' => 'ongoing', 'external_link_visible_at' => now()->addHours(2),
            'category' => 'education', 'status' => 'published', 'starts_at' => now()->addDay(), 'ends_at' => now()->addDay()->addHour(),
        ]);
        $speaker = $event->speakers()->create(['user_id' => $user->id, 'display_name' => 'Test speaker', 'status' => 'accepted', 'role' => 'speaker']);

        return [$event, $user, $speaker];
    }
}
