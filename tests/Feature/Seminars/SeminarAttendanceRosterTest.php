<?php

namespace Tests\Feature\Seminars;

use App\Models\Seminar;
use App\Models\SeminarRegistrant;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Testing\TestResponse;
use Tests\Feature\Connectors\ConnectorTestHelpers;
use Tests\TestCase;

class SeminarAttendanceRosterTest extends TestCase
{
    use ConnectorTestHelpers;

    public function test_connector_roster_and_csv_start_from_registrations_and_pair_attendance(): void
    {
        [$owner, $connector, $seminar] = $this->event();
        $notSubmitted = User::factory()->create(['name' => 'Not Submitted Learner', 'role' => 'learner']);
        $codeParticipant = User::factory()->create(['name' => 'Code Participant', 'role' => 'learner']);
        $manualParticipant = User::factory()->create(['name' => 'Manual Participant', 'role' => 'learner']);
        $pendingParticipant = User::factory()->create(['name' => 'Pending Participant', 'role' => 'learner']);

        $notSubmittedRegistration = $this->register($seminar, $notSubmitted);
        $codeRegistration = $this->register($seminar, $codeParticipant);
        $manualRegistration = $this->register($seminar, $manualParticipant);
        $pendingRegistration = $this->register($seminar, $pendingParticipant, 'pending');
        $seminar->attendances()->create([
            'user_id' => $codeParticipant->id,
            'role' => 'audience',
            'status' => 'attended',
            'attendance_method' => 'attendance_code',
            'attended_at' => now(),
        ]);
        $seminar->attendances()->create([
            'user_id' => $manualParticipant->id,
            'role' => 'audience',
            'status' => 'not_present',
            'attendance_method' => 'manual',
        ]);
        $orphan = User::factory()->create(['name' => 'Unregistered Attendance', 'role' => 'learner']);
        $seminar->attendances()->create([
            'user_id' => $orphan->id,
            'role' => 'audience',
            'status' => 'attended',
            'attendance_method' => 'native',
        ]);

        $fillers = User::factory()->count(22)->create(['role' => 'learner']);
        foreach ($fillers as $index => $filler) {
            $this->register($seminar, $filler, 'registered', now()->addSeconds($index + 1));
        }

        $page = $this->actingAs($owner)->get(route('connector.seminars.attendance', [$connector, $seminar]));
        $page->assertOk()
            ->assertSee($notSubmitted->name)
            ->assertSee('Not submitted')
            ->assertSee($codeParticipant->name)
            ->assertSee('Attendance submitted')
            ->assertSee($manualParticipant->name)
            ->assertSee('Not present')
            ->assertSee($pendingParticipant->name)
            ->assertDontSee($orphan->name);
        $page->assertViewHas('registrants', function (LengthAwarePaginator $registrants): bool {
            $this->assertSame(26, $registrants->total());
            $this->assertSame(25, $registrants->perPage());

            return true;
        });
        $page->assertDontSee(route('connector.seminars.attendance.manual', [$connector, $seminar, $pendingRegistration]));
        $page->assertSee(route('connector.seminars.attendance.manual', [$connector, $seminar, $notSubmittedRegistration]));
        $page->assertSee(route('connector.seminars.attendance.manual', [$connector, $seminar, $codeRegistration]));
        $page->assertSee(route('connector.seminars.attendance.manual', [$connector, $seminar, $manualRegistration]));

        $secondPage = $this->actingAs($owner)->get(route('connector.seminars.attendance', [$connector, $seminar]).'?page=2');
        $secondPage->assertOk()->assertSee($fillers->last()->name);

        $csvResponse = $this->actingAs($owner)->get(route('connector.seminars.attendance.export', [$connector, $seminar]));
        $csvResponse->assertOk();
        $csv = $this->streamedContent($csvResponse);
        $csvHandle = fopen('php://temp', 'r+');
        fwrite($csvHandle, $csv);
        rewind($csvHandle);
        $this->assertSame([
            'name', 'email', 'participant type', 'registration status', 'attendance status',
            'method', 'attended at', 'joined at', 'left at', 'total minutes',
        ], fgetcsv($csvHandle));
        fclose($csvHandle);
        $this->assertStringContainsString($notSubmitted->email, $csv);
        $this->assertStringContainsString($codeParticipant->email, $csv);
        $this->assertStringContainsString($manualParticipant->email, $csv);
        $this->assertStringContainsString('not_present', $csv);
        $this->assertStringNotContainsString($orphan->email, $csv);
    }

    public function test_admin_can_open_complete_roster_and_csv_but_learners_cannot(): void
    {
        [$owner, , $seminar] = $this->event();
        $learner = User::factory()->create(['name' => 'Admin Roster Learner', 'role' => 'learner']);
        $this->register($seminar, $learner);
        $admin = User::factory()->create(['role' => 'admin']);
        $admin->assignRole('admin');

        $this->actingAs($admin)
            ->get(route('admin.seminars.attendance', $seminar))
            ->assertOk()
            ->assertSee($learner->name)
            ->assertSee('Not submitted');

        $csvResponse = $this->actingAs($admin)->get(route('admin.seminars.attendance.export', $seminar));
        $csvResponse->assertOk();
        $this->assertStringContainsString($learner->email, $this->streamedContent($csvResponse));

        $this->actingAs($owner)
            ->get(route('admin.seminars.attendance', $seminar))
            ->assertForbidden();
        $this->actingAs($owner)
            ->get(route('admin.seminars.attendance.export', $seminar))
            ->assertForbidden();
    }

    public function test_learner_sees_only_own_attendance_and_provenance_wording(): void
    {
        [$owner, , $seminar] = $this->event(['attendance_code_enabled' => true]);
        $learner = $this->createCompletedLearner(['name' => 'Learner With Attendance', 'age_bracket_cached' => 'adults']);
        $this->register($seminar, $learner);
        $other = $this->createCompletedLearner(['name' => 'Other Participant Private', 'email' => 'other-participant@example.test', 'age_bracket_cached' => 'adults']);
        $this->register($seminar, $other);
        $seminar->attendances()->create([
            'user_id' => $learner->id,
            'role' => 'audience',
            'status' => 'attended',
            'attendance_method' => 'attendance_code',
            'attended_at' => now(),
        ]);
        $seminar->attendances()->create([
            'user_id' => $other->id,
            'role' => 'audience',
            'status' => 'attended',
            'attendance_method' => 'manual',
            'attended_at' => now(),
        ]);

        $this->actingAs($learner)
            ->get(route('seminars.show', $seminar))
            ->assertOk()
            ->assertSee('Attendance submitted')
            ->assertDontSee($other->name)
            ->assertDontSee($other->email);

        $seminar->attendances()->where('user_id', $learner->id)->update([
            'status' => 'not_present',
            'attendance_method' => 'manual',
            'attended_at' => null,
        ]);
        $this->actingAs($learner)
            ->get(route('seminars.show', $seminar))
            ->assertOk()
            ->assertSee('Not present')
            ->assertSee('Recorded by the event organizer')
            ->assertDontSee('Enter the eight-digit code');

        [, , $legacySeminar] = $this->event([
            'event_format' => 'external',
            'external_url' => 'https://meet.example.test/legacy-event',
            'attendance_code_enabled' => true,
        ]);
        $this->register($legacySeminar, $learner);
        $legacySeminar->attendances()->create([
            'user_id' => $learner->id,
            'role' => 'audience',
            'status' => 'attended',
            'attendance_method' => 'legacy',
            'attended_at' => now(),
        ]);
        $code = app(\App\Services\Seminars\SeminarCodeAttendanceService::class)->generate($legacySeminar, null, null);

        $this->actingAs($learner)
            ->get(route('seminars.show', $legacySeminar))
            ->assertOk()
            ->assertSee('Attendance status: Attended.')
            ->assertSee('Submit attendance code')
            ->assertDontSee($code);

        $nativeSeminar = $this->event([
            'type' => 'webinar',
            'event_format' => 'native',
            'livestream_channel' => 'learner-attendance-channel',
            'livestream_status' => 'completed',
            'livestream_started_at' => now()->subHour(),
            'attendance_code_enabled' => false,
        ])[2];
        $this->register($nativeSeminar, $learner);
        $nativeSeminar->attendances()->create([
            'user_id' => $learner->id,
            'role' => 'audience',
            'joined_at' => now()->subMinutes(10),
            'left_at' => now(),
            'total_seconds' => 600,
            'status' => 'attended',
            'attendance_method' => 'native',
        ]);

        $this->actingAs($learner)
            ->get(route('seminars.show', $nativeSeminar))
            ->assertOk()
            ->assertSee('Livestream attendance: Attended');
    }

    private function event(array $overrides = []): array
    {
        $owner = $this->createCompletedLearner();
        $connector = $this->createVerifiedConnector($owner);
        $seminar = $connector->seminars()->create(array_merge([
            'title' => 'Roster event',
            'description' => 'Class description',
            'purpose' => 'Class purpose',
            'type' => 'seminar',
            'event_format' => 'in_person',
            'category' => 'health',
            'status' => 'published',
            'starts_at' => now()->subMinutes(5),
            'ends_at' => now()->addHour(),
            'schedule' => now()->subMinutes(5),
            'target_participants' => 'learners',
            'learner_age_categories' => ['adult'],
        ], $overrides));

        return [$owner, $connector, $seminar];
    }

    private function register(Seminar $seminar, User $user, string $status = 'registered', ?Carbon $registeredAt = null): SeminarRegistrant
    {
        return $seminar->registrants()->create([
            'user_id' => $user->id,
            'status' => $status,
            'participant_type' => 'learner',
            'registered_at' => $registeredAt ?? now(),
        ]);
    }

    private function streamedContent(TestResponse $response): string
    {
        ob_start();
        $response->baseResponse->sendContent();

        return (string) ob_get_clean();
    }
}
