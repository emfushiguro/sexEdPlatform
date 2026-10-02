<?php

namespace Tests\Feature\Seminars;

use App\Models\Seminar;
use App\Models\User;
use App\Services\Seminars\SeminarCodeAttendanceService;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Tests\Feature\Connectors\ConnectorTestHelpers;
use Tests\TestCase;

class SeminarCodeAttendanceTest extends TestCase
{
    use ConnectorTestHelpers;

    protected function tearDown(): void
    {
        RateLimiter::clear('seminar-code-ip:'.hash('sha256', '127.0.0.1'));
        parent::tearDown();
    }

    public function test_owner_generates_hashed_code_once_and_registered_learner_submits(): void
    {
        Notification::fake();
        [$owner, $connector, $seminar, $learner] = $this->setupEvent();

        $response = $this->actingAs($owner)->post(route('connector.seminars.attendance.code.generate', [$connector, $seminar]));
        $response->assertRedirect()->assertSessionHas('generated_attendance_code');
        $code = Crypt::decryptString(session('generated_attendance_code'));
        $this->assertNotSame($code, session('generated_attendance_code'));
        $this->assertMatchesRegularExpression('/^\d{8}$/', $code);
        $this->assertTrue(Hash::check($code, $seminar->fresh()->attendance_code_hash));
        $this->assertFalse(str_contains(json_encode(DB::table('seminars')->where('id', $seminar->id)->first()), $code));
        $this->assertFalse(str_contains(json_encode(DB::table('notifications')->get()), $code));
        $this->actingAs($owner)->get(route('connector.seminars.attendance', [$connector, $seminar]))->assertOk()->assertSee($code);
        $this->actingAs($owner)->get(route('connector.seminars.attendance', [$connector, $seminar]))->assertOk()->assertDontSee($code);

        $this->actingAs($learner)->post(route('seminars.attendance.code.submit', $seminar), ['code' => $code])->assertRedirect()->assertSessionHas('success');
        $this->assertDatabaseHas('seminar_attendances', ['seminar_id' => $seminar->id, 'user_id' => $learner->id, 'status' => 'attended', 'attendance_method' => 'attendance_code']);
        $this->assertNotNull($seminar->registrants()->where('user_id', $learner->id)->first()->attended_at);
        $this->actingAs($learner)->get(route('seminars.show', $seminar))->assertOk()->assertSee('Attendance submitted')->assertDontSee($code);
        $this->actingAs($learner)->post(route('seminars.attendance.code.submit', $seminar), ['code' => $code])->assertSessionHasErrors(['code' => 'Attendance already submitted.']);
        $this->assertSame(1, $seminar->attendances()->where('user_id', $learner->id)->count());
    }

    public function test_regeneration_invalidates_old_code_and_disable_blocks_submission(): void
    {
        [$owner, $connector, $seminar, $learner] = $this->setupEvent();
        $old = app(SeminarCodeAttendanceService::class)->generate($seminar, null, null);
        $new = app(SeminarCodeAttendanceService::class)->generate($seminar, null, null);
        $this->actingAs($learner)->post(route('seminars.attendance.code.submit', $seminar), ['code' => $old])->assertSessionHasErrors('code');
        $this->assertArrayNotHasKey('code', session('_old_input', []));
        $this->actingAs($learner)->post(route('seminars.attendance.code.submit', $seminar), ['code' => 'bad'])->assertSessionHasErrors('code');
        $this->assertArrayNotHasKey('code', session('_old_input', []));
        $this->actingAs($learner)->post(route('seminars.attendance.code.submit', $seminar), ['code' => $new])->assertSessionHas('success');
        $other = $this->registeredLearner($seminar);
        $this->actingAs($owner)->post(route('connector.seminars.attendance.code.disable', [$connector, $seminar]))->assertRedirect();
        $this->assertNull($seminar->fresh()->attendance_code_hash);
        $this->actingAs($other)->post(route('seminars.attendance.code.submit', $seminar), ['code' => $new])->assertSessionHasErrors('code');
    }

    public function test_window_boundaries_and_pht_overrides(): void
    {
        [$owner, $connector, $seminar, $learner] = $this->setupEvent();
        $code = app(SeminarCodeAttendanceService::class)->generate($seminar, null, null);
        $this->travelTo($seminar->starts_at->copy()->subMinutes(15)->subSecond());
        $this->actingAs($learner)->post(route('seminars.attendance.code.submit', $seminar), ['code' => $code])->assertSessionHasErrors('code');
        $this->travelTo($seminar->starts_at->copy()->subMinutes(15));
        $this->actingAs($learner)->post(route('seminars.attendance.code.submit', $seminar), ['code' => $code])->assertSessionHas('success');

        $other = $this->registeredLearner($seminar);
        $this->travelTo($seminar->ends_at->copy()->addMinutes(30));
        $this->actingAs($other)->post(route('seminars.attendance.code.submit', $seminar), ['code' => $code])->assertSessionHas('success');
        $late = $this->registeredLearner($seminar);
        $this->travelTo($seminar->ends_at->copy()->addMinutes(30)->addSecond());
        $this->actingAs($late)->post(route('seminars.attendance.code.submit', $seminar), ['code' => $code])->assertSessionHasErrors('code');

        $this->travelBack();
        $open = now()->addMinutes(3);
        $close = now()->addMinutes(5);
        $this->actingAs($owner)->post(route('connector.seminars.attendance.code.generate', [$connector, $seminar]), [
            'attendance_start_at' => $open->copy()->timezone(config('app.display_timezone'))->format('Y-m-d H:i:s'),
            'attendance_end_at' => $close->copy()->timezone(config('app.display_timezone'))->format('Y-m-d H:i:s'),
        ])->assertSessionHas('generated_attendance_code');
        $this->assertSame($open->format('Y-m-d H:i:s'), $seminar->fresh()->attendance_start_at->format('Y-m-d H:i:s'));
        $this->assertSame($close->format('Y-m-d H:i:s'), $seminar->fresh()->attendance_end_at->format('Y-m-d H:i:s'));
        $this->actingAs($owner)->post(route('connector.seminars.attendance.code.generate', [$connector, $seminar]), [
            'attendance_start_at' => $close->toDateTimeString(), 'attendance_end_at' => $open->toDateTimeString(),
        ])->assertSessionHasErrors('attendance_end_at');
        $this->actingAs($owner)->post(route('connector.seminars.attendance.code.generate', [$connector, $seminar]), [
            'attendance_start_at' => $seminar->ends_at->copy()->addHour()->timezone(config('app.display_timezone'))->format('Y-m-d H:i:s'),
        ])->assertSessionHasErrors('attendance_end_at');
    }

    public function test_rejects_unregistered_ineligible_speaker_and_blocked_status_or_native(): void
    {
        [$owner, $connector, $seminar, $learner] = $this->setupEvent();
        $code = app(SeminarCodeAttendanceService::class)->generate($seminar, null, null);
        $unregistered = $this->createCompletedLearner(['age_bracket_cached' => 'adults']);
        $this->actingAs($unregistered)->post(route('seminars.attendance.code.submit', $seminar), ['code' => $code])->assertSessionHasErrors('code');
        $pending = $this->createCompletedLearner(['age_bracket_cached' => 'adults']);
        $seminar->registrants()->create(['user_id' => $pending->id, 'status' => 'pending', 'participant_type' => 'learner', 'registered_at' => now()]);
        $this->actingAs($pending)->post(route('seminars.attendance.code.submit', $seminar), ['code' => $code])->assertSessionHasErrors('code');
        $speaker = User::factory()->create(['role' => 'instructor']);
        $speaker->assignRole('instructor');
        $seminar->speakers()->create(['user_id' => $speaker->id, 'display_name' => 'Speaker', 'role' => 'speaker', 'status' => 'accepted']);
        $this->actingAs($speaker)->post(route('seminars.attendance.code.submit', $seminar), ['code' => $code])->assertSessionHasErrors('code');
        $seminar->update(['target_participants' => 'instructors']);
        $this->actingAs($learner)->post(route('seminars.attendance.code.submit', $seminar), ['code' => $code])->assertSessionHasErrors('code');
        $seminar->update(['target_participants' => 'learners']);
        $seminar->registrants()->where('user_id', $learner->id)->update(['cancelled_at' => now()]);
        $this->actingAs($learner)->post(route('seminars.attendance.code.submit', $seminar), ['code' => $code])->assertSessionHasErrors('code');
        $seminar->registrants()->where('user_id', $learner->id)->update(['cancelled_at' => null]);
        $seminar->update(['target_participants' => 'learners', 'status' => 'cancelled']);
        $this->actingAs($learner)->post(route('seminars.attendance.code.submit', $seminar), ['code' => $code])->assertSessionHasErrors('code');
        $seminar->update(['status' => 'published', 'event_format' => 'native']);
        $this->actingAs($learner)->post(route('seminars.attendance.code.submit', $seminar), ['code' => $code])->assertSessionHasErrors('code');
        $this->actingAs($owner)->post(route('connector.seminars.attendance.code.generate', [$connector, $seminar]))->assertForbidden();
        $seminar->update(['event_format' => 'in_person', 'status' => 'draft']);
        $this->actingAs($learner)->post(route('seminars.attendance.code.submit', $seminar), ['code' => $code])->assertSessionHasErrors('code');
    }

    public function test_manual_override_is_preserved_and_external_event_and_admin_can_generate(): void
    {
        [$owner, $connector, $seminar, $learner] = $this->setupEvent(['event_format' => 'external', 'type' => 'webinar']);
        $admin = User::factory()->create(['role' => 'admin']);
        $admin->assignRole('admin');
        $this->actingAs($admin)->post(route('admin.seminars.attendance.code.generate', $seminar))->assertSessionHas('generated_attendance_code');
        $code = Crypt::decryptString(session('generated_attendance_code'));
        $other = $this->registeredLearner($seminar);
        $this->actingAs($other)->post(route('seminars.attendance.code.submit', $seminar), ['code' => $code])->assertSessionHas('success');
        $seminar->attendances()->create(['user_id' => $learner->id, 'status' => 'not_present', 'attendance_method' => 'manual', 'total_seconds' => 0]);
        $this->actingAs($learner)->post(route('seminars.attendance.code.submit', $seminar), ['code' => $code])->assertSessionHasErrors('code');
        $this->assertSame('manual', $seminar->attendances()->first()->attendance_method);
        $this->actingAs($admin)->post(route('admin.seminars.attendance.code.disable', $seminar))->assertRedirect();
        $this->assertFalse($seminar->fresh()->attendance_code_enabled);
    }

    public function test_management_requires_owner_permission_or_admin_role(): void
    {
        [$owner, $connector, $seminar] = $this->setupEvent();
        $outsider = $this->createCompletedLearner(['age_bracket_cached' => 'adults']);
        $this->actingAs($outsider)->post(route('connector.seminars.attendance.code.generate', [$connector, $seminar]))->assertForbidden();
        $this->actingAs($outsider)->post(route('admin.seminars.attendance.code.generate', $seminar))->assertForbidden();
        $otherConnector = $this->createVerifiedConnector($outsider);
        $this->actingAs($owner)->post(route('connector.seminars.attendance.code.generate', [$otherConnector, $seminar]))->assertForbidden();
    }

    public function test_wrong_codes_are_limited_per_user_event_and_per_ip_across_events(): void
    {
        [, , $seminar, $learner] = $this->setupEvent();
        app(SeminarCodeAttendanceService::class)->generate($seminar, null, null);
        for ($i = 0; $i < 5; $i++) {
            $this->actingAs($learner)->post(route('seminars.attendance.code.submit', $seminar), ['code' => '00000000'])->assertSessionHasErrors('code');
        }
        $this->actingAs($learner)->post(route('seminars.attendance.code.submit', $seminar), ['code' => '00000000'])->assertStatus(429);
        $this->assertSame(5, RateLimiter::attempts("seminar-code:{$seminar->id}:{$learner->id}"));

        $event = $seminar->replicate();
        $event->title = 'Second class';
        $event->save();
        app(SeminarCodeAttendanceService::class)->generate($event, null, null);
        for ($i = 0; $i < 15; $i++) {
            $person = $this->registeredLearner($event);
            $this->actingAs($person)->post(route('seminars.attendance.code.submit', $event), ['code' => '00000000'])->assertSessionHasErrors('code');
        }
        $person = $this->registeredLearner($event);
        $this->actingAs($person)->post(route('seminars.attendance.code.submit', $event), ['code' => '00000000'])->assertStatus(429);
    }

    private function setupEvent(array $overrides = []): array
    {
        $owner = $this->createCompletedLearner(['age_bracket_cached' => 'adults']);
        $connector = $this->createVerifiedConnector($owner);
        $seminar = $connector->seminars()->create(array_merge([
            'title' => 'Community class', 'description' => 'Class', 'purpose' => 'Learn',
            'type' => 'seminar', 'event_format' => 'in_person', 'category' => 'health',
            'status' => 'published', 'starts_at' => now()->subMinutes(5), 'ends_at' => now()->addMinutes(55),
            'schedule' => now()->subMinutes(5), 'target_participants' => 'learners',
            'learner_age_categories' => ['adult'],
        ], $overrides));
        $learner = $this->registeredLearner($seminar);

        return [$owner, $connector, $seminar, $learner];
    }

    private function registeredLearner(Seminar $seminar): User
    {
        $learner = $this->createCompletedLearner(['age_bracket_cached' => 'adults']);
        $seminar->registrants()->create(['user_id' => $learner->id, 'status' => 'registered', 'participant_type' => 'learner', 'registered_at' => now()]);

        return $learner;
    }
}
