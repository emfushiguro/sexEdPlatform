<?php

namespace Tests\Feature\Connectors;

use App\Models\Seminar;
use App\Models\SeminarAttendance;
use App\Models\SeminarComment;
use App\Models\SeminarQuestion;
use App\Models\SeminarRegistrant;
use App\Models\SeminarSpeaker;
use App\Models\User;
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

    public function test_connector_can_assign_platform_and_external_speakers(): void
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
        $instructor = User::factory()->create(['role' => 'instructor']);
        $instructor->assignRole('instructor');
        $learner = User::factory()->create(['role' => 'learner']);
        $learner->assignRole('learner');

        $this->actingAs($owner)
            ->post(route('connector.seminars.speakers.store', [$connector, $seminar]), [
                'speaker_type' => 'platform',
                'user_id' => $instructor->id,
                'title' => 'Instructor Speaker',
            ])
            ->assertRedirect();

        $this->actingAs($owner)
            ->post(route('connector.seminars.speakers.store', [$connector, $seminar]), [
                'speaker_type' => 'platform',
                'user_id' => $learner->id,
                'title' => 'Community Speaker',
            ])
            ->assertRedirect();

        $this->actingAs($owner)
            ->post(route('connector.seminars.speakers.store', [$connector, $seminar]), [
                'speaker_type' => 'external',
                'display_name' => 'External Advocate',
                'title' => 'Guest',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('seminar_speakers', [
            'seminar_id' => $seminar->id,
            'user_id' => $instructor->id,
        ]);
        $this->assertDatabaseHas('seminar_speakers', [
            'seminar_id' => $seminar->id,
            'display_name' => 'External Advocate',
            'user_id' => null,
        ]);
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
                'speaker_type' => 'platform',
                'user_id' => $speakerUser->id,
            ])
            ->assertRedirect();

        $this->actingAs($owner)
            ->post(route('connector.seminars.speakers.store', [$connector, $seminar]), [
                'speaker_type' => 'platform',
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
}
