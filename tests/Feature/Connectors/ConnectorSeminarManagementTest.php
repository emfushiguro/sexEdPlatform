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
}
