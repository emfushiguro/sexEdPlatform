<?php

namespace Tests\Feature\Connectors;

use App\Models\InstructorProfile;
use App\Models\Seminar;
use App\Models\SeminarAttendance;
use App\Models\SeminarComment;
use App\Models\SeminarQuestion;
use App\Models\SeminarRegistrant;
use App\Models\SeminarSpeaker;
use App\Models\User;
use App\Notifications\Seminars\SeminarCancelledNotification;
use App\Notifications\Seminars\SeminarSpeakerAssignedNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class ConnectorSeminarManagementTest extends TestCase
{
    use ConnectorTestHelpers;

    public function test_connector_owned_seminar_relationships_are_available(): void
    {
        $owner = User::factory()->create(['role' => 'learner']);
        $owner->assignRole('learner');
        $connector = $this->createVerifiedConnector($owner);
        $participant = $this->createCompletedLearner();

        $seminar = Seminar::query()->create([
            'connector_id' => $connector->id,
            'type' => 'webinar',
            'title' => 'Community Wellness Webinar',
            'description' => 'A free community session.',
            'purpose' => 'Support learner wellness.',
            'category' => 'health',
            'status' => 'draft',
            'schedule' => now()->addDay(),
            'starts_at' => now()->addDay(),
            'ends_at' => now()->addDay()->addHour(),
            'capacity' => 50,
            'target_participants' => 'learners_and_instructors',
            'learner_age_categories' => ['kids', 'teen'],
            'livestream_channel' => 'seminar-test-channel',
        ]);

        $registrant = SeminarRegistrant::query()->create([
            'seminar_id' => $seminar->id,
            'user_id' => $participant->id,
            'status' => 'registered',
            'participant_type' => 'learner',
            'registered_at' => now(),
        ]);

        $speaker = SeminarSpeaker::query()->create([
            'seminar_id' => $seminar->id,
            'user_id' => $participant->id,
            'display_name' => $participant->name,
            'title' => 'Speaker',
            'role' => 'speaker',
        ]);

        $comment = SeminarComment::query()->create([
            'seminar_id' => $seminar->id,
            'user_id' => $participant->id,
            'body' => 'This is helpful.',
            'status' => 'visible',
        ]);

        $question = SeminarQuestion::query()->create([
            'seminar_id' => $seminar->id,
            'user_id' => $participant->id,
            'question' => 'Can I share this with my class?',
            'status' => 'pending',
        ]);

        $attendance = SeminarAttendance::query()->create([
            'seminar_id' => $seminar->id,
            'user_id' => $participant->id,
            'joined_at' => now(),
            'total_seconds' => 0,
            'status' => 'joined',
        ]);

        $seminar->refresh();

        $this->assertTrue($connector->seminars()->whereKey($seminar)->exists());
        $this->assertSame($connector->id, $seminar->connector->id);
        $this->assertSame($registrant->id, $seminar->registrants->first()->id);
        $this->assertSame($speaker->id, $seminar->speakers->first()->id);
        $this->assertSame($comment->id, $seminar->comments->first()->id);
        $this->assertSame($question->id, $seminar->questions->first()->id);
        $this->assertSame($attendance->id, $seminar->attendances->first()->id);
        $this->assertSame(['kids', 'teen'], $seminar->learner_age_categories);
    }

    public function test_unverified_connector_cannot_open_create_page(): void
    {
        $owner = User::factory()->create(['role' => 'learner']);
        $owner->assignRole('learner');
        $connector = $this->createVerifiedConnector($owner);
        $connector->update(['status' => 'pending']);

        $this->actingAs($owner)
            ->get(route('connector.seminars.create', $connector))
            ->assertForbidden();
    }

    public function test_member_without_manage_seminars_permission_is_denied(): void
    {
        $owner = User::factory()->create(['role' => 'learner']);
        $owner->assignRole('learner');
        $member = User::factory()->create(['role' => 'learner']);
        $member->assignRole('learner');
        $connector = $this->createVerifiedConnector($owner);
        $role = $this->createCustomRole($connector, ['connector.view_subscription']);

        $connector->memberships()->create([
            'user_id' => $member->id,
            'connector_role_id' => $role->id,
            'status' => 'active',
            'accepted_at' => now(),
        ]);

        $this->actingAs($member)
            ->get(route('connector.seminars.create', $connector))
            ->assertForbidden();
    }

    public function test_authorized_connector_member_can_create_and_manage_lifecycle(): void
    {
        $owner = User::factory()->create(['role' => 'learner']);
        $owner->assignRole('learner');
        $connector = $this->createVerifiedConnector($owner);

        $response = $this->actingAs($owner)
            ->post(route('connector.seminars.store', $connector), $this->seminarPayload());

        $seminar = Seminar::query()->where('connector_id', $connector->id)->firstOrFail();

        $response->assertRedirect(route('connector.seminars.show', [$connector, $seminar]));
        $this->assertSame('draft', $seminar->status);
        $this->assertSame('webinar', $seminar->type);
        $this->assertNotNull($seminar->livestream_channel);

        $this->actingAs($owner)
            ->post(route('connector.seminars.submit-review', [$connector, $seminar]))
            ->assertRedirect();

        $this->assertSame('pending_review', $seminar->fresh()->status);

        $seminar->update(['status' => 'approved']);

        $this->actingAs($owner)
            ->post(route('connector.seminars.publish', [$connector, $seminar]))
            ->assertRedirect();

        $this->assertSame('published', $seminar->fresh()->status);

        $this->actingAs($owner)
            ->post(route('connector.seminars.cancel', [$connector, $seminar]), [
                'cancellation_reason' => 'Severe weather advisory.',
            ])
            ->assertRedirect();

        $seminar->refresh();
        $this->assertSame('cancelled', $seminar->status);
        $this->assertSame($owner->id, $seminar->cancelled_by);

        $seminar->update(['status' => 'published']);

        $this->actingAs($owner)
            ->post(route('connector.seminars.complete', [$connector, $seminar]))
            ->assertRedirect();

        $this->assertSame('completed', $seminar->fresh()->status);
    }

    public function test_connector_submits_draft_for_review(): void
    {
        $owner = User::factory()->create(['role' => 'learner']);
        $owner->assignRole('learner');
        $connector = $this->createVerifiedConnector($owner);
        $seminar = Seminar::query()->create([
            ...$this->seminarPayload(),
            'connector_id' => $connector->id,
            'schedule' => now()->addDay(),
            'status' => 'draft',
        ]);

        $this->actingAs($owner)
            ->post(route('connector.seminars.submit-review', [$connector, $seminar]))
            ->assertRedirect();

        $seminar->refresh();
        $this->assertSame('pending_review', $seminar->status);
        $this->assertSame($owner->id, $seminar->submitted_for_review_by);
        $this->assertNotNull($seminar->submitted_for_review_at);
    }

    public function test_connector_cannot_publish_draft_directly(): void
    {
        $owner = User::factory()->create(['role' => 'learner']);
        $owner->assignRole('learner');
        $connector = $this->createVerifiedConnector($owner);
        $seminar = Seminar::query()->create([
            ...$this->seminarPayload(),
            'connector_id' => $connector->id,
            'schedule' => now()->addDay(),
            'status' => 'draft',
        ]);

        $this->actingAs($owner)
            ->post(route('connector.seminars.publish', [$connector, $seminar]))
            ->assertStatus(422);

        $this->assertSame('draft', $seminar->fresh()->status);
    }

    public function test_connector_can_publish_approved_seminar(): void
    {
        $owner = User::factory()->create(['role' => 'learner']);
        $owner->assignRole('learner');
        $connector = $this->createVerifiedConnector($owner);
        $seminar = Seminar::query()->create([
            ...$this->seminarPayload(),
            'connector_id' => $connector->id,
            'schedule' => now()->addDay(),
            'status' => 'approved',
        ]);

        $this->actingAs($owner)
            ->post(route('connector.seminars.publish', [$connector, $seminar]))
            ->assertRedirect();

        $seminar->refresh();
        $this->assertSame('published', $seminar->status);
        $this->assertSame($owner->id, $seminar->published_by);
        $this->assertNotNull($seminar->published_at);
    }

    public function test_connector_can_archive_non_active_seminar(): void
    {
        $owner = User::factory()->create(['role' => 'learner']);
        $owner->assignRole('learner');
        $connector = $this->createVerifiedConnector($owner);
        $seminar = Seminar::query()->create([
            ...$this->seminarPayload(),
            'connector_id' => $connector->id,
            'schedule' => now()->addDay(),
            'status' => 'rejected',
        ]);

        $this->actingAs($owner)
            ->post(route('connector.seminars.archive', [$connector, $seminar]))
            ->assertRedirect();

        $seminar->refresh();
        $this->assertSame('archived', $seminar->status);
        $this->assertSame($owner->id, $seminar->archived_by);
        $this->assertNotNull($seminar->archived_at);
    }

    public function test_connector_detail_groups_lifecycle_actions_and_confirms_completion(): void
    {
        $owner = User::factory()->create(['role' => 'learner']);
        $owner->assignRole('learner');
        $connector = $this->createVerifiedConnector($owner);
        $seminar = Seminar::query()->create([
            ...$this->seminarPayload(),
            'connector_id' => $connector->id,
            'schedule' => now()->addDay(),
            'status' => 'published',
        ]);

        $this->actingAs($owner)
            ->get(route('connector.seminars.show', [$connector, $seminar]))
            ->assertOk()
            ->assertDontSee('Channel Details')
            ->assertDontSee($seminar->livestream_channel)
            ->assertSee('Edit')
            ->assertSee('Archive')
            ->assertSee('Complete Seminar')
            ->assertSee('Cancel Seminar')
            ->assertSee('Complete seminar?')
            ->assertSee('This marks the seminar completed and finalizes attendance records.');
    }

    public function test_connector_can_create_seminar_without_description(): void
    {
        $owner = User::factory()->create(['role' => 'learner']);
        $owner->assignRole('learner');
        $connector = $this->createVerifiedConnector($owner);

        $response = $this->actingAs($owner)
            ->post(route('connector.seminars.store', $connector), $this->seminarPayload([
                'title' => 'Family Learning Webinar',
                'description' => null,
                'purpose' => 'Help families understand safe online learning habits.',
                'category' => 'education',
                'learner_age_categories' => ['teen'],
            ]));

        $response->assertRedirect();
        $this->assertDatabaseHas('seminars', [
            'title' => 'Family Learning Webinar',
            'purpose' => 'Help families understand safe online learning habits.',
            'description' => null,
            'category' => 'education',
        ]);
    }

    public function test_seminar_times_are_entered_in_philippine_time_and_stored_in_utc(): void
    {
        $this->travelTo('2026-07-19 00:00:00');
        config(['app.timezone' => 'UTC', 'app.display_timezone' => 'Asia/Manila']);

        $owner = User::factory()->create(['role' => 'learner']);
        $owner->assignRole('learner');
        $connector = $this->createVerifiedConnector($owner);

        $this->actingAs($owner)
            ->post(route('connector.seminars.store', $connector), $this->seminarPayload([
                'title' => 'Philippine Time Webinar',
                'starts_at' => '2026-07-20T16:00',
                'ends_at' => '2026-07-20T18:00',
            ]))
            ->assertRedirect();

        $seminar = Seminar::query()->where('title', 'Philippine Time Webinar')->firstOrFail();

        $this->assertSame('2026-07-20 08:00:00', $seminar->starts_at->format('Y-m-d H:i:s'));
        $this->assertSame('2026-07-20 10:00:00', $seminar->ends_at->format('Y-m-d H:i:s'));

        $this->actingAs($owner)
            ->get(route('connector.seminars.edit', [$connector, $seminar]))
            ->assertOk()
            ->assertSee('value="2026-07-20T16:00"', false)
            ->assertSee('Starts At (Philippine Time)');
    }

    public function test_other_category_requires_custom_category(): void
    {
        $owner = User::factory()->create(['role' => 'learner']);
        $owner->assignRole('learner');
        $connector = $this->createVerifiedConnector($owner);

        $this->actingAs($owner)
            ->post(route('connector.seminars.store', $connector), $this->seminarPayload([
                'title' => 'Local Skills Session',
                'purpose' => 'Introduce community learning options.',
                'type' => 'physical',
                'category' => 'other',
                'learner_age_categories' => ['adult'],
                'location' => 'Community Hall',
            ]))
            ->assertSessionHasErrors('custom_category');
    }

    public function test_connector_cannot_manage_another_connectors_seminar(): void
    {
        $owner = User::factory()->create(['role' => 'learner']);
        $owner->assignRole('learner');
        $otherOwner = User::factory()->create(['role' => 'learner']);
        $otherOwner->assignRole('learner');

        $connector = $this->createVerifiedConnector($owner);
        $otherConnector = $this->createVerifiedConnector($otherOwner);
        $seminar = Seminar::query()->create([
            ...$this->seminarPayload(),
            'connector_id' => $otherConnector->id,
            'schedule' => now()->addDay(),
            'status' => 'draft',
        ]);

        $this->actingAs($owner)
            ->get(route('connector.seminars.show', [$connector, $seminar]))
            ->assertNotFound();
    }

    public function test_speaker_search_returns_only_active_approved_instructors(): void
    {
        $owner = User::factory()->create(['role' => 'learner']);
        $owner->assignRole('learner');
        $connector = $this->createVerifiedConnector($owner);
        $seminar = Seminar::query()->create([
            ...$this->seminarPayload(),
            'connector_id' => $connector->id,
            'schedule' => now()->addDay(),
            'status' => 'draft',
        ]);
        $instructor = User::factory()->create(['role' => 'instructor', 'name' => 'Ada Instructor', 'status' => 'active']);
        $instructor->assignRole('instructor');
        InstructorProfile::create(['user_id' => $instructor->id, 'bio' => 'Instructor bio.']);
        $availableInstructor = User::factory()->create(['role' => 'instructor', 'name' => 'Grace Instructor', 'status' => 'active']);
        $availableInstructor->assignRole('instructor');
        InstructorProfile::create(['user_id' => $availableInstructor->id, 'bio' => 'Instructor bio.']);
        $inactiveInstructor = User::factory()->create(['role' => 'instructor', 'name' => 'Inactive Instructor', 'status' => 'inactive']);
        $inactiveInstructor->assignRole('instructor');
        InstructorProfile::create(['user_id' => $inactiveInstructor->id, 'bio' => 'Instructor bio.']);
        $learner = User::factory()->create(['role' => 'learner']);
        $learner->assignRole('learner');

        $seminar->speakers()->create([
            'user_id' => $instructor->id,
            'display_name' => $instructor->name,
            'role' => 'speaker',
        ]);

        $response = $this->actingAs($owner)
            ->getJson(route('connector.seminars.speakers.search', [$connector, $seminar, 'search' => 'Instructor']));

        $response->assertOk()
            ->assertJsonFragment(['id' => $availableInstructor->id])
            ->assertJsonMissing(['id' => $instructor->id])
            ->assertJsonMissing(['id' => $inactiveInstructor->id])
            ->assertJsonMissing(['id' => $learner->id]);
    }

    public function test_connector_adds_speaker_by_selected_instructor(): void
    {
        Notification::fake();

        $owner = User::factory()->create(['role' => 'learner']);
        $owner->assignRole('learner');
        $connector = $this->createVerifiedConnector($owner);
        $seminar = Seminar::query()->create([
            ...$this->seminarPayload(),
            'connector_id' => $connector->id,
            'schedule' => now()->addDay(),
            'status' => 'draft',
        ]);
        $instructor = User::factory()->create(['role' => 'instructor']);
        $instructor->assignRole('instructor');
        InstructorProfile::create(['user_id' => $instructor->id, 'bio' => 'Instructor bio.']);

        $this->actingAs($owner)
            ->post(route('connector.seminars.speakers.store', [$connector, $seminar]), [
                'user_id' => $instructor->id,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('seminar_speakers', [
            'seminar_id' => $seminar->id,
            'user_id' => $instructor->id,
            'display_name' => $instructor->name,
        ]);
        Notification::assertSentTo($instructor, SeminarSpeakerAssignedNotification::class);
    }

    public function test_ineligible_user_cannot_be_added_as_speaker(): void
    {
        $owner = User::factory()->create(['role' => 'learner']);
        $owner->assignRole('learner');
        $connector = $this->createVerifiedConnector($owner);
        $seminar = Seminar::query()->create([
            ...$this->seminarPayload(),
            'connector_id' => $connector->id,
            'schedule' => now()->addDay(),
            'status' => 'draft',
        ]);
        $learner = User::factory()->create(['role' => 'learner']);
        $learner->assignRole('learner');

        $this->actingAs($owner)
            ->post(route('connector.seminars.speakers.store', [$connector, $seminar]), [
                'user_id' => $learner->id,
            ])
            ->assertSessionHasErrors('user_id');
    }

    public function test_speaker_invitation_can_be_reviewed_once_and_cancelled_without_deleting_history(): void
    {
        Notification::fake();
        $owner = User::factory()->create(['role' => 'learner']);
        $owner->assignRole('learner');
        $connector = $this->createVerifiedConnector($owner);
        $seminar = Seminar::query()->create([
            ...$this->seminarPayload(),
            'connector_id' => $connector->id,
            'schedule' => now()->addDay(),
            'status' => 'draft',
        ]);
        $instructor = User::factory()->create(['role' => 'instructor', 'status' => 'active']);
        $instructor->assignRole('instructor');
        InstructorProfile::create(['user_id' => $instructor->id, 'bio' => 'Instructor bio.']);

        $this->actingAs($owner)->post(route('connector.seminars.speakers.store', [$connector, $seminar]), [
            'user_ids' => [$instructor->id],
            'invitation_message' => 'Please share your practical experience.',
        ])->assertRedirect();

        $invitation = $seminar->speakers()->where('user_id', $instructor->id)->firstOrFail();
        $this->assertSame('pending', $invitation->status);
        $this->assertSame('Please share your practical experience.', $invitation->invitation_message);

        $this->actingAs($instructor)
            ->post(route('instructor.speaker-invitations.accept', $invitation))
            ->assertRedirect();
        $this->assertSame('accepted', $invitation->fresh()->status);

        $this->actingAs($instructor)
            ->post(route('instructor.speaker-invitations.decline', $invitation))
            ->assertStatus(422);

        $this->actingAs($owner)
            ->delete(route('connector.seminars.speakers.destroy', [$connector, $seminar, $invitation]))
            ->assertRedirect();
        $this->assertSame('cancelled', $invitation->fresh()->status);
        $this->assertModelExists($invitation);
    }

    public function test_connector_cancellation_notifies_active_registrants(): void
    {
        Notification::fake();

        $owner = User::factory()->create(['role' => 'learner']);
        $owner->assignRole('learner');
        $connector = $this->createVerifiedConnector($owner);
        $learner = $this->createCompletedLearner();
        $cancelledLearner = $this->createCompletedLearner();
        $seminar = Seminar::query()->create([
            ...$this->seminarPayload(),
            'connector_id' => $connector->id,
            'schedule' => now()->addDay(),
            'status' => 'published',
        ]);

        $seminar->registrants()->create([
            'user_id' => $learner->id,
            'status' => 'registered',
            'participant_type' => 'learner',
            'registered_at' => now(),
        ]);
        $seminar->registrants()->create([
            'user_id' => $cancelledLearner->id,
            'status' => 'cancelled',
            'participant_type' => 'learner',
            'registered_at' => now(),
            'cancelled_at' => now(),
        ]);

        $this->actingAs($owner)
            ->post(route('connector.seminars.cancel', [$connector, $seminar]), [
                'cancellation_reason' => 'Venue emergency.',
            ])
            ->assertRedirect();

        Notification::assertSentTo($learner, SeminarCancelledNotification::class);
        Notification::assertNotSentTo($cancelledLearner, SeminarCancelledNotification::class);
    }

    public function test_connector_can_export_owned_registrants_only(): void
    {
        $owner = User::factory()->create(['role' => 'learner']);
        $owner->assignRole('learner');
        $connector = $this->createVerifiedConnector($owner);
        $otherOwner = User::factory()->create(['role' => 'learner']);
        $otherOwner->assignRole('learner');
        $otherConnector = $this->createVerifiedConnector($otherOwner);
        $learner = $this->createCompletedLearner();
        $seminar = Seminar::query()->create([
            ...$this->seminarPayload(),
            'connector_id' => $connector->id,
            'schedule' => now()->addDay(),
            'status' => 'published',
        ]);
        $otherSeminar = Seminar::query()->create([
            ...$this->seminarPayload(['title' => 'Other Seminar']),
            'connector_id' => $otherConnector->id,
            'schedule' => now()->addDay(),
            'status' => 'published',
        ]);

        $seminar->registrants()->create([
            'user_id' => $learner->id,
            'status' => 'registered',
            'participant_type' => 'learner',
            'registered_at' => now(),
        ]);

        $response = $this->actingAs($owner)
            ->get(route('connector.seminars.registrants.export', [$connector, $seminar]));

        $response->assertOk();
        $this->assertStringContainsString('text/csv', $response->headers->get('content-type'));
        $this->assertStringContainsString($learner->email, $this->streamedContent($response));

        $this->actingAs($owner)
            ->get(route('connector.seminars.registrants.export', [$connector, $otherSeminar]))
            ->assertNotFound();
    }

    public function test_duplicate_platform_speaker_and_other_connector_speaker_edits_are_blocked(): void
    {
        $owner = User::factory()->create(['role' => 'learner']);
        $owner->assignRole('learner');
        $otherOwner = User::factory()->create(['role' => 'learner']);
        $otherOwner->assignRole('learner');
        $connector = $this->createVerifiedConnector($owner);
        $otherConnector = $this->createVerifiedConnector($otherOwner);
        $speakerUser = User::factory()->create(['role' => 'instructor']);
        $speakerUser->assignRole('instructor');
        InstructorProfile::create(['user_id' => $speakerUser->id, 'bio' => 'Instructor bio.']);
        $seminar = Seminar::query()->create([
            ...$this->seminarPayload(),
            'connector_id' => $connector->id,
            'schedule' => now()->addDay(),
            'status' => 'draft',
        ]);
        $otherSeminar = Seminar::query()->create([
            ...$this->seminarPayload(['title' => 'Other Seminar']),
            'connector_id' => $otherConnector->id,
            'schedule' => now()->addDay(),
            'status' => 'draft',
        ]);

        $this->actingAs($owner)
            ->post(route('connector.seminars.speakers.store', [$connector, $seminar]), [
                'user_id' => $speakerUser->id,
            ])
            ->assertRedirect();

        $this->actingAs($owner)
            ->post(route('connector.seminars.speakers.store', [$connector, $seminar]), [
                'user_id' => $speakerUser->id,
            ])
            ->assertSessionHasErrors('user_id');

        $speaker = $otherSeminar->speakers()->create([
            'user_id' => $speakerUser->id,
            'display_name' => $speakerUser->name,
            'role' => 'speaker',
        ]);

        $this->actingAs($owner)
            ->delete(route('connector.seminars.speakers.destroy', [$connector, $otherSeminar, $speaker]))
            ->assertNotFound();
    }

    private function seminarPayload(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Community Wellness Webinar',
            'description' => 'A free community session.',
            'purpose' => 'Support learner wellness.',
            'type' => 'webinar',
            'category' => 'health',
            'starts_at' => now()->addDay()->format('Y-m-d H:i:s'),
            'ends_at' => now()->addDay()->addHour()->format('Y-m-d H:i:s'),
            'capacity' => 50,
            'target_participants' => 'learners_and_instructors',
            'learner_age_categories' => ['kids', 'teen'],
            'location' => null,
        ], $overrides);
    }

    private function streamedContent(TestResponse $response): string
    {
        ob_start();
        $response->baseResponse->sendContent();

        return (string) ob_get_clean();
    }
}
