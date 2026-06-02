<?php

namespace Tests\Feature\Seminars;

use App\Models\Connector;
use App\Models\Seminar;
use App\Models\User;
use Tests\Feature\Connectors\ConnectorTestHelpers;
use Tests\TestCase;

class SeminarInteractionTest extends TestCase
{
    use ConnectorTestHelpers;

    public function test_registered_user_can_post_comment_and_question_during_join_window(): void
    {
        $connector = $this->connector();
        $learner = $this->createCompletedLearner(['age_bracket_cached' => 'adults']);
        $seminar = $this->seminar($connector);
        $this->register($seminar, $learner);

        $this->actingAs($learner)
            ->post(route('seminars.comments.store', $seminar), ['body' => 'Helpful session.'])
            ->assertRedirect();

        $this->actingAs($learner)
            ->post(route('seminars.questions.store', $seminar), ['question' => 'Can I get the slides?'])
            ->assertRedirect();

        $this->assertDatabaseHas('seminar_comments', [
            'seminar_id' => $seminar->id,
            'user_id' => $learner->id,
            'body' => 'Helpful session.',
            'status' => 'visible',
        ]);
        $this->assertDatabaseHas('seminar_questions', [
            'seminar_id' => $seminar->id,
            'user_id' => $learner->id,
            'question' => 'Can I get the slides?',
            'status' => 'pending',
        ]);
    }

    public function test_unregistered_user_cannot_post_and_completed_is_read_only(): void
    {
        $connector = $this->connector();
        $learner = $this->createCompletedLearner(['age_bracket_cached' => 'adults']);
        $seminar = $this->seminar($connector);

        $this->actingAs($learner)
            ->post(route('seminars.comments.store', $seminar), ['body' => 'Hello'])
            ->assertForbidden();

        $this->register($seminar, $learner);
        $seminar->update(['status' => 'completed']);

        $this->actingAs($learner)
            ->post(route('seminars.questions.store', $seminar), ['question' => 'Still open?'])
            ->assertSessionHasErrors('seminar');
    }

    public function test_connector_manager_can_hide_comment_and_answer_or_hide_question(): void
    {
        $owner = User::factory()->create(['role' => 'learner']);
        $owner->assignRole('learner');
        $connector = $this->createVerifiedConnector($owner);
        $learner = $this->createCompletedLearner(['age_bracket_cached' => 'adults']);
        $seminar = $this->seminar($connector);
        $comment = $seminar->comments()->create([
            'user_id' => $learner->id,
            'body' => 'Moderate me',
            'status' => 'visible',
        ]);
        $question = $seminar->questions()->create([
            'user_id' => $learner->id,
            'question' => 'Answer me',
            'status' => 'pending',
        ]);

        $this->actingAs($owner)
            ->post(route('connector.seminars.comments.hide', [$connector, $seminar, $comment]), ['reason' => 'Off topic'])
            ->assertRedirect();

        $this->actingAs($owner)
            ->post(route('connector.seminars.questions.answer', [$connector, $seminar, $question]), ['answer' => 'Yes.'])
            ->assertRedirect();

        $this->assertDatabaseHas('seminar_comments', [
            'id' => $comment->id,
            'status' => 'hidden',
            'hidden_by' => $owner->id,
            'hidden_reason' => 'Off topic',
        ]);
        $this->assertDatabaseHas('seminar_questions', [
            'id' => $question->id,
            'status' => 'answered',
            'answered_by' => $owner->id,
            'answer' => 'Yes.',
        ]);
    }

    private function connector(): Connector
    {
        $owner = User::factory()->create(['role' => 'learner']);
        $owner->assignRole('learner');

        return $this->createVerifiedConnector($owner);
    }

    private function register(Seminar $seminar, User $user): void
    {
        $seminar->registrants()->create([
            'user_id' => $user->id,
            'status' => 'registered',
            'participant_type' => 'learner',
            'registered_at' => now(),
        ]);
    }

    private function seminar(Connector $connector): Seminar
    {
        return Seminar::query()->create([
            'connector_id' => $connector->id,
            'type' => 'webinar',
            'title' => 'Live Webinar',
            'description' => 'A free community session.',
            'purpose' => 'Support learner wellness.',
            'category' => 'health',
            'status' => 'published',
            'schedule' => now()->addMinutes(5),
            'starts_at' => now()->addMinutes(5),
            'ends_at' => now()->addHour(),
            'capacity' => 50,
            'target_participants' => 'learners_and_instructors',
            'learner_age_categories' => ['adult'],
            'livestream_channel' => 'seminar-test-channel-'.str()->random(6),
        ]);
    }
}
