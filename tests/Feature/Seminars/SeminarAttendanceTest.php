<?php

namespace Tests\Feature\Seminars;

use App\Models\Connector;
use App\Models\Seminar;
use App\Models\User;
use App\Services\Seminars\SeminarAttendanceService;
use Illuminate\Testing\TestResponse;
use Tests\Feature\Connectors\ConnectorTestHelpers;
use Tests\TestCase;

class SeminarAttendanceTest extends TestCase
{
    use ConnectorTestHelpers;

    public function test_finalize_preserves_migrated_legacy_attendance_on_native_webinar(): void
    {
        $connector = $this->connector();
        $learner = $this->createCompletedLearner(['age_bracket_cached' => 'adults']);
        $native = $this->seminar($connector);
        $attendedAt = now()->subDay();
        $legacy = $native->attendances()->create([
            'user_id' => $learner->id,
            'status' => 'attended',
            'attendance_method' => 'legacy',
            'attended_at' => $attendedAt,
            'total_seconds' => 0,
        ]);

        app(SeminarAttendanceService::class)->finalize($native);

        $legacy->refresh();
        $this->assertSame('attended', $legacy->status);
        $this->assertSame('legacy', $legacy->attendance_method);
        $this->assertSame($attendedAt->timestamp, $legacy->attended_at->timestamp);
        $this->assertSame(0, $legacy->total_seconds);
        $this->assertNull($legacy->left_at);
    }

    public function test_finalize_preserves_manual_decisions_and_skips_external_events(): void
    {
        $connector = $this->connector();
        $learner = $this->createCompletedLearner(['age_bracket_cached' => 'adults']);
        $presentLearner = $this->createCompletedLearner(['age_bracket_cached' => 'adults']);
        $native = $this->seminar($connector);
        $manualTime = now()->subMinutes(10);
        $manual = $native->attendances()->create([
            'user_id' => $learner->id,
            'joined_at' => now()->subMinutes(6),
            'total_seconds' => 0,
            'status' => 'not_present',
            'attendance_method' => 'manual',
        ]);
        $manualPresent = $native->attendances()->create([
            'user_id' => $presentLearner->id,
            'joined_at' => now()->subMinutes(6),
            'total_seconds' => 0,
            'status' => 'attended',
            'attendance_method' => 'manual',
            'attended_at' => $manualTime,
        ]);
        $external = $this->seminar($connector, ['event_format' => 'external', 'external_url' => 'https://meet.example.test/room']);
        $externalAttendance = $external->attendances()->create([
            'user_id' => $learner->id,
            'joined_at' => now()->subMinutes(6),
            'total_seconds' => 0,
            'status' => 'registered',
            'attendance_method' => 'manual',
        ]);

        app(SeminarAttendanceService::class)->finalize($native);
        app(SeminarAttendanceService::class)->finalize($external);

        $this->assertSame('not_present', $manual->fresh()->status);
        $this->assertSame('manual', $manual->fresh()->attendance_method);
        $this->assertGreaterThanOrEqual(300, $manual->fresh()->total_seconds);
        $this->assertNotNull($manual->fresh()->left_at);
        $this->assertSame('attended', $manualPresent->fresh()->status);
        $this->assertSame($manualTime->timestamp, $manualPresent->fresh()->attended_at->timestamp);
        $this->assertSame('registered', $externalAttendance->fresh()->status);
        $this->assertNull($externalAttendance->fresh()->left_at);
        $this->assertSame(0, $externalAttendance->fresh()->total_seconds);
    }

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
        $this->assertSame('native', $attendance->attendance_method);
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
        $this->register($seminar, $learner);
        $attendance = $seminar->attendances()->create([
            'user_id' => $learner->id,
            'joined_at' => now()->subMinutes(6),
            'total_seconds' => 0,
            'status' => 'joined',
            'attendance_method' => 'native',
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

    public function test_connector_can_export_owned_attendance_only(): void
    {
        $owner = User::factory()->create(['role' => 'learner']);
        $owner->assignRole('learner');
        $connector = $this->createVerifiedConnector($owner);
        $otherConnector = $this->connector();
        $seminar = $this->seminar($connector);
        $otherSeminar = $this->seminar($otherConnector, ['title' => 'Other']);
        $learner = $this->createCompletedLearner(['age_bracket_cached' => 'adults']);
        $this->register($seminar, $learner);

        $seminar->attendances()->create([
            'user_id' => $learner->id,
            'joined_at' => now()->subMinutes(8),
            'left_at' => now(),
            'total_seconds' => 480,
            'status' => 'attended',
            'attendance_method' => 'native',
        ]);

        $response = $this->actingAs($owner)
            ->get(route('connector.seminars.attendance.export', [$connector, $seminar]));

        $response->assertOk();
        $this->assertStringContainsString('text/csv', $response->headers->get('content-type'));
        $this->assertStringContainsString($learner->email, $this->streamedContent($response));

        $this->actingAs($owner)
            ->get(route('connector.seminars.attendance.export', [$connector, $otherSeminar]))
            ->assertNotFound();
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
            'event_format' => 'native',
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
            'livestream_status' => 'live',
            'livestream_started_at' => now(),
        ], $overrides));
    }

    private function streamedContent(TestResponse $response): string
    {
        ob_start();
        $response->baseResponse->sendContent();

        return (string) ob_get_clean();
    }
}
