<?php

namespace Tests\Feature\Seminars;

use App\Enums\SeminarFormat;
use App\Enums\SeminarType;
use App\Models\Seminar;
use App\Models\SeminarAttendance;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Feature\Connectors\ConnectorTestHelpers;
use Tests\TestCase;

class EducationalEventMigrationTest extends TestCase
{
    use ConnectorTestHelpers;

    public function test_schema_and_backfill_preserve_legacy_events_and_attendance(): void
    {
        $owner = User::factory()->create(['role' => 'learner']);
        $owner->assignRole('learner');
        $connector = $this->createVerifiedConnector($owner);
        $nativeUser = $this->createCompletedLearner();
        $legacyUser = $this->createCompletedLearner();

        $physical = $this->seminar($connector->id, 'physical', [
            'location' => 'Old community hall',
        ]);
        $webinar = $this->seminar($connector->id, 'webinar', [
            'livestream_channel' => 'legacy-agora-channel',
            'livestream_status' => 'live',
        ]);
        $nativeAttendance = $webinar->attendances()->create([
            'user_id' => $nativeUser->id,
            'status' => 'attended',
            'joined_at' => now()->subHour(),
            'left_at' => now(),
            'total_seconds' => 3600,
        ]);
        $registeredAt = now()->subDay();
        $attendedAt = now()->subHour();
        $registrant = $webinar->registrants()->create([
            'user_id' => $legacyUser->id,
            'status' => 'attended',
            'participant_type' => 'learner',
            'registered_at' => $registeredAt,
            'attended_at' => $attendedAt,
        ]);

        $this->assertTrue(Schema::hasColumns('seminars', [
            'event_format', 'venue_address', 'venue_room', 'delivery_instructions',
            'external_platform', 'external_platform_name', 'external_url',
            'external_link_visible_at', 'external_link_expiry_mode', 'external_link_expires_at',
            'registration_deadline_at', 'attendance_code_hash', 'attendance_code_enabled',
            'attendance_code_generated_at', 'attendance_start_at', 'attendance_end_at',
            'reminder_sent_for_starts_at', 'link_available_sent_for_visible_at',
        ]));
        $this->assertTrue(Schema::hasColumns('seminar_attendances', ['attendance_method', 'attended_at']));

        $migration = require database_path('migrations/2026_09_28_000001_extend_seminars_for_external_delivery.php');
        $migration->backfill();

        $this->assertDatabaseHas('seminars', ['id' => $physical->id, 'type' => 'seminar', 'event_format' => 'in_person']);
        $this->assertDatabaseHas('seminars', ['id' => $webinar->id, 'type' => 'webinar', 'event_format' => 'native']);
        $this->assertDatabaseHas('seminars', ['id' => $physical->id, 'external_link_expiry_mode' => 'ongoing', 'attendance_code_enabled' => false]);
        $this->assertDatabaseHas('seminars', ['id' => $physical->id, 'location' => 'Old community hall', 'connector_id' => $connector->id]);
        $this->assertDatabaseHas('seminars', [
            'id' => $webinar->id,
            'livestream_channel' => 'legacy-agora-channel',
            'livestream_status' => 'live',
            'external_url' => null,
        ]);
        $this->assertDatabaseHas('seminar_attendances', [
            'id' => $nativeAttendance->id,
            'seminar_id' => $webinar->id,
            'user_id' => $nativeUser->id,
            'attendance_method' => 'native',
            'status' => 'attended',
            'total_seconds' => 3600,
        ]);
        $this->assertDatabaseHas('seminar_attendances', [
            'seminar_id' => $webinar->id,
            'user_id' => $legacyUser->id,
            'attendance_method' => 'legacy',
            'status' => 'attended',
            'attended_at' => $attendedAt->toDateTimeString(),
        ]);
        $this->assertDatabaseHas('seminar_registrants', [
            'id' => $registrant->id,
            'seminar_id' => $webinar->id,
            'user_id' => $legacyUser->id,
            'status' => 'attended',
        ]);

        $seminarsBefore = DB::table('seminars')->orderBy('id')->get()->toArray();
        $attendancesBefore = DB::table('seminar_attendances')->orderBy('id')->get()->toArray();
        $migration->backfill();
        $this->assertEquals($seminarsBefore, DB::table('seminars')->orderBy('id')->get()->toArray());
        $this->assertEquals($attendancesBefore, DB::table('seminar_attendances')->orderBy('id')->get()->toArray());
        $this->assertSame(1, SeminarAttendance::where('seminar_id', $webinar->id)->where('user_id', $legacyUser->id)->count());
        $this->assertSame(1, $webinar->registrants()->where('user_id', $legacyUser->id)->count());
    }

    public function test_model_values_and_sensitive_serialization(): void
    {
        $this->assertSame('seminar', SeminarType::Seminar->value);
        $this->assertSame('in_person', SeminarFormat::InPerson->value);
        $this->assertSame('external', SeminarFormat::External->value);
        $this->assertSame('native', SeminarFormat::Native->value);

        $seminar = new Seminar([
            'event_format' => 'external',
            'external_url' => 'https://meet.example.test/room',
            'attendance_code_hash' => 'secret-hash',
            'attendance_code_enabled' => true,
            'external_link_visible_at' => now(),
        ]);
        $this->assertTrue($seminar->isExternalDelivery());
        $this->assertFalse($seminar->isNativeDelivery());
        $this->assertTrue($seminar->attendance_code_enabled);
        $this->assertNotNull($seminar->external_link_visible_at->format('Y-m-d'));
        $this->assertArrayNotHasKey('external_url', $seminar->toArray());
        $this->assertArrayNotHasKey('attendance_code_hash', $seminar->toArray());

        $seminar->event_format = 'native';
        $this->assertTrue($seminar->isNativeDelivery());
        $this->assertFalse($seminar->isExternalDelivery());
    }

    public function test_rollback_refuses_to_discard_existing_seminars(): void
    {
        $owner = User::factory()->create(['role' => 'learner']);
        $owner->assignRole('learner');
        $connector = $this->createVerifiedConnector($owner);
        $seminar = $this->seminar($connector->id, 'webinar');
        $migration = require database_path('migrations/2026_09_28_000001_extend_seminars_for_external_delivery.php');

        try {
            $migration->down();
            $this->fail('Rollback must refuse to discard existing seminars.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('Cannot reverse', $exception->getMessage());
        }
        $this->assertDatabaseHas('seminars', ['id' => $seminar->id]);
    }

    private function seminar(int $connectorId, string $type, array $overrides = []): Seminar
    {
        return Seminar::create(array_merge([
            'connector_id' => $connectorId,
            'type' => $type,
            'title' => 'Legacy event',
            'description' => 'Legacy description',
            'purpose' => 'Community education',
            'category' => 'health',
            'status' => 'published',
            'schedule' => now()->addDay(),
            'starts_at' => now()->addDay(),
            'ends_at' => now()->addDay()->addHour(),
        ], $overrides));
    }
}
