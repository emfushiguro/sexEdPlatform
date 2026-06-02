<?php

namespace Tests\Feature\Seminars;

use App\Models\Connector;
use App\Models\Seminar;
use App\Models\User;
use Tests\Feature\Connectors\ConnectorTestHelpers;
use Tests\TestCase;

class SeminarAttendanceTest extends TestCase
{
    use ConnectorTestHelpers;

    public function test_join_leave_records_and_aggregates_attendance_duration(): void
    {
        $connector = $this->connector();
        $learner = $this->createCompletedLearner(['age_bracket_cached' => 'adults']);
        $seminar = $this->seminar($connector);
        $this->register($seminar, $learner);

        $this->actingAs($learner)
            ->postJson(route('seminars.attendance.join', $seminar))
            ->assertOk()
            ->assertJsonPath('attendance.status', 'joined');

        $attendance = $seminar->attendances()->where('user_id', $learner->id)->firstOrFail();
        $attendance->update(['joined_at' => now()->subMinutes(6)]);

        $this->actingAs($learner)
            ->postJson(route('seminars.attendance.leave', $seminar))
            ->assertOk()
            ->assertJsonPath('attendance.status', 'attended');

        $this->assertGreaterThanOrEqual(300, $attendance->fresh()->total_seconds);

        $this->actingAs($learner)->postJson(route('seminars.attendance.join', $seminar))->assertOk();
        $attendance->fresh()->update(['joined_at' => now()->subMinutes(2)]);
        $this->actingAs($learner)->postJson(route('seminars.attendance.leave', $seminar))->assertOk();

        $this->assertGreaterThanOrEqual(420, $attendance->fresh()->total_seconds);
    }

    public function test_connector_can_view_owned_attendance_only_and_completion_finalizes(): void
    {
        $owner = User::factory()->create(['role' => 'learner']);
        $owner->assignRole('learner');
        $connector = $this->createVerifiedConnector($owner);
        $otherConnector = $this->connector();
        $seminar = $this->seminar($connector);
        $otherSeminar = $this->seminar($otherConnector, ['title' => 'Other']);
        $learner = $this->createCompletedLearner(['age_bracket_cached' => 'adults']);
        $attendance = $seminar->attendances()->create([
            'user_id' => $learner->id,
            'joined_at' => now()->subMinutes(6),
            'total_seconds' => 0,
            'status' => 'joined',
        ]);

        $this->actingAs($owner)
            ->get(route('connector.seminars.attendance', [$connector, $seminar]))
            ->assertOk()
            ->assertSee($learner->name);

        $this->actingAs($owner)
            ->get(route('connector.seminars.attendance', [$connector, $otherSeminar]))
            ->assertNotFound();

        $this->actingAs($owner)
            ->post(route('connector.seminars.complete', [$connector, $seminar]))
            ->assertRedirect();

        $this->assertSame('attended', $attendance->fresh()->status);
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

    private function seminar(Connector $connector, array $overrides = []): Seminar
    {
        return Seminar::query()->create(array_merge([
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
        ], $overrides));
    }
}
