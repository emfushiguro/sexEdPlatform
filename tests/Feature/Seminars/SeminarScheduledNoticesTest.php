<?php

namespace Tests\Feature\Seminars;

use App\Models\Seminar;
use App\Models\User;
use App\Notifications\Seminars\SeminarDeliveryNotification;
use App\Notifications\Seminars\SeminarReminderNotification;
use App\Services\Seminars\SeminarDeliveryService;
use App\Services\Seminars\SeminarNoticeService;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Tests\Feature\Connectors\ConnectorTestHelpers;
use Tests\TestCase;

class SeminarScheduledNoticesTest extends TestCase
{
    use ConnectorTestHelpers;

    private function event(array $attributes = []): Seminar
    {
        $owner = $this->createCompletedLearner();
        $connector = $this->createVerifiedConnector($owner);

        return $connector->seminars()->create(array_merge([
            'title' => 'Protected educational event',
            'description' => 'Description',
            'purpose' => 'Purpose',
            'type' => 'webinar',
            'event_format' => 'external',
            'status' => 'published',
            'external_platform' => 'zoom',
            'external_url' => 'https://zoom.example.test/private-meeting',
            'external_link_expiry_mode' => 'ongoing',
            'starts_at' => now()->addHour(),
            'ends_at' => now()->addHours(2),
            'target_participants' => 'learners',
            'learner_age_categories' => ['adult'],
        ], $attributes));
    }

    private function registered(Seminar $seminar): User
    {
        $user = $this->createCompletedLearner();
        $seminar->registrants()->create(['user_id' => $user->id, 'status' => 'registered', 'registered_at' => now()]);

        return $user;
    }

    public function test_reminder_is_sent_once_to_current_eligible_participants(): void
    {
        $this->travelTo(now()->startOfMinute());
        Notification::fake();
        $seminar = $this->event();
        $registered = $this->registered($seminar);
        $pending = $this->createCompletedLearner();
        $cancelled = $this->createCompletedLearner();
        $ineligible = User::factory()->create(['role' => 'instructor']);
        $ineligible->assignRole('instructor');
        foreach ([[$pending, 'pending'], [$cancelled, 'cancelled'], [$ineligible, 'registered']] as [$user, $status]) {
            $seminar->registrants()->create(['user_id' => $user->id, 'status' => $status, 'registered_at' => now(), 'cancelled_at' => $status === 'cancelled' ? now() : null]);
        }
        $speaker = User::factory()->create(['role' => 'instructor']);
        $speaker->assignRole('instructor');
        $seminar->speakers()->create(['user_id' => $speaker->id, 'display_name' => 'Speaker', 'status' => 'accepted']);

        $this->artisan('seminars:send-notices')->assertExitCode(0);
        $this->artisan('seminars:send-notices')->assertExitCode(0);

        foreach ([$registered, $speaker] as $user) {
            Notification::assertSentToTimes($user, SeminarReminderNotification::class, 1);
            $notice = Notification::sent($user, SeminarReminderNotification::class)->first();
            $this->assertSame(route('seminars.show', $seminar), $notice->toDatabase($user)['action_url']);
            $this->assertStringNotContainsString($seminar->external_url, json_encode($notice->toDatabase($user)));
            $this->assertStringNotContainsString($seminar->external_url, json_encode($notice->toMail($user)->toArray()));
            $this->assertStringContainsString('Educational Event', $notice->toMail($user)->subject);
        }
        foreach ([$pending, $cancelled, $ineligible, $seminar->connector->creator] as $user) {
            Notification::assertNotSentTo($user, SeminarReminderNotification::class);
        }
        $this->assertEquals($seminar->starts_at, $seminar->fresh()->reminder_sent_for_starts_at);
    }

    public function test_scheduled_link_release_is_sent_once_and_new_release_time_gets_one_new_notice(): void
    {
        $this->travelTo(now()->startOfMinute());
        Notification::fake();
        $seminar = $this->event(['starts_at' => now()->addHours(3), 'ends_at' => now()->addHours(4), 'external_link_visible_at' => now()]);
        $registered = $this->registered($seminar);
        $pending = $this->createCompletedLearner();
        $cancelled = $this->createCompletedLearner();
        $ineligible = User::factory()->create(['role' => 'instructor']);
        $ineligible->assignRole('instructor');
        foreach ([[$pending, 'pending'], [$cancelled, 'cancelled'], [$ineligible, 'registered']] as [$user, $status]) {
            $seminar->registrants()->create(['user_id' => $user->id, 'status' => $status, 'registered_at' => now(), 'cancelled_at' => $status === 'cancelled' ? now() : null]);
        }
        $speaker = User::factory()->create(['role' => 'instructor']);
        $speaker->assignRole('instructor');
        $seminar->speakers()->create(['user_id' => $speaker->id, 'display_name' => 'Speaker', 'status' => 'accepted']);

        $this->artisan('seminars:send-notices')->assertExitCode(0);
        $this->artisan('seminars:send-notices')->assertExitCode(0);
        Notification::assertSentToTimes($registered, SeminarDeliveryNotification::class, 1);
        Notification::assertSentToTimes($speaker, SeminarDeliveryNotification::class, 1);
        foreach ([$pending, $cancelled, $ineligible, $seminar->connector->creator] as $user) {
            Notification::assertNotSentTo($user, SeminarDeliveryNotification::class);
        }
        $notice = Notification::sent($registered, SeminarDeliveryNotification::class)->first();
        $this->assertSame('available', $notice->kind);
        $this->assertSame(route('seminars.show', $seminar), $notice->toDatabase($registered)['action_url']);
        $this->assertStringNotContainsString($seminar->external_url, json_encode($notice->toDatabase($registered)));
        $this->assertStringNotContainsString($seminar->external_url, json_encode($notice->toMail($registered)->toArray()));
        $this->assertEquals($seminar->external_link_visible_at, $seminar->fresh()->link_available_sent_for_visible_at);

        $nextRelease = now()->addMinutes(10)->startOfMinute();
        app(SeminarDeliveryService::class)->update($seminar, ['external_link_visible_at' => $nextRelease->toDateTimeString()], $seminar->connector->creator);
        $this->assertNull($seminar->fresh()->link_available_sent_for_visible_at);
        Notification::fake();
        $this->artisan('seminars:send-notices')->assertExitCode(0);
        Notification::assertNothingSent();
        $this->travelTo($nextRelease);
        $this->artisan('seminars:send-notices')->assertExitCode(0);
        $this->artisan('seminars:send-notices')->assertExitCode(0);
        Notification::assertSentToTimes($registered, SeminarDeliveryNotification::class, 1);
        Notification::assertSentToTimes($speaker, SeminarDeliveryNotification::class, 1);
        $this->assertEquals($nextRelease, $seminar->fresh()->link_available_sent_for_visible_at);
    }

    public function test_expired_immediate_and_inactive_events_do_not_send_link_notices(): void
    {
        $this->travelTo(now()->startOfMinute());
        Notification::fake();
        $events = [
            $this->event(['external_link_visible_at' => null]),
            $this->event(['external_link_visible_at' => now()->subMinute(), 'external_link_expiry_mode' => 'custom', 'external_link_expires_at' => now()]),
            $this->event(['external_link_visible_at' => now(), 'external_link_expiry_mode' => 'event_end', 'ends_at' => now()]),
            $this->event(['external_link_visible_at' => now(), 'status' => 'cancelled']),
            $this->event(['external_link_visible_at' => now(), 'status' => 'archived']),
        ];
        foreach ($events as $seminar) {
            $this->registered($seminar);
        }

        $this->artisan('seminars:send-notices')->assertExitCode(0);

        foreach ($events as $seminar) {
            foreach ($seminar->registrants as $registrant) {
                Notification::assertNotSentTo($registrant->user, SeminarDeliveryNotification::class);
            }
        }
        foreach ($events as $seminar) {
            $this->assertNull($seminar->fresh()->link_available_sent_for_visible_at);
        }
    }

    public function test_failed_dispatch_clears_only_its_claim_for_retry(): void
    {
        $this->travelTo(now()->startOfMinute());
        $seminar = $this->event(['starts_at' => now()->addHours(3), 'external_link_visible_at' => now()]);
        $this->registered($seminar);
        $notices = $this->mock(SeminarNoticeService::class);
        $notices->shouldReceive('sendLinkAvailable')->once()->andThrow(new \RuntimeException('Transport unavailable'));

        $this->artisan('seminars:send-notices')->assertExitCode(0);
        $this->assertNull($seminar->fresh()->link_available_sent_for_visible_at);
    }

    public function test_stale_release_and_reminder_slots_cannot_be_claimed(): void
    {
        $this->travelTo(now()->startOfMinute());
        Notification::fake();
        $seminar = $this->event(['external_link_visible_at' => now()]);
        $registered = $this->registered($seminar);
        $stale = $seminar->fresh();
        $newStart = now()->addHours(3);
        $newRelease = now()->addMinutes(10);
        $seminar->update(['starts_at' => $newStart, 'external_link_visible_at' => $newRelease]);

        $command = app(\App\Console\Commands\SendSeminarNotices::class);
        $send = new \ReflectionMethod($command, 'send');
        $send->invoke($command, $stale, 'starts_at', 'reminder_sent_for_starts_at', 'sendReminder');
        $send->invoke($command, $stale, 'external_link_visible_at', 'link_available_sent_for_visible_at', 'sendLinkAvailable');

        Notification::assertNotSentTo($registered, SeminarReminderNotification::class);
        Notification::assertNotSentTo($registered, SeminarDeliveryNotification::class);
        $this->assertNull($seminar->fresh()->reminder_sent_for_starts_at);
        $this->assertNull($seminar->fresh()->link_available_sent_for_visible_at);
    }

    public function test_failed_dispatch_does_not_clear_marker_after_release_changes(): void
    {
        $this->travelTo(now()->startOfMinute());
        $seminar = $this->event(['starts_at' => now()->addHours(3), 'external_link_visible_at' => now()]);
        $this->registered($seminar);
        $nextRelease = now()->addMinute();
        $notices = $this->mock(SeminarNoticeService::class);
        $notices->shouldReceive('sendLinkAvailable')->once()->andReturnUsing(function () use ($seminar, $nextRelease): void {
            $seminar->update(['external_link_visible_at' => $nextRelease, 'link_available_sent_for_visible_at' => $nextRelease]);
            throw new \RuntimeException('Transport unavailable');
        });

        $this->artisan('seminars:send-notices')->assertExitCode(0);

        $this->assertEquals($nextRelease, $seminar->fresh()->link_available_sent_for_visible_at);
    }

    public function test_release_moved_to_future_after_claim_sends_no_early_availability_notice(): void
    {
        $this->travelTo(now()->startOfMinute());
        Notification::fake();
        $seminar = $this->event(['starts_at' => now()->addHours(3), 'external_link_visible_at' => now()]);
        $registered = $this->registered($seminar);
        $nextRelease = now()->addMinutes(10);
        $releaseChanged = false;
        DB::listen(function (QueryExecuted $query) use ($seminar, $nextRelease, &$releaseChanged): void {
            if ($releaseChanged || ! str_contains(strtolower($query->sql), 'update `seminars`')
                || ! str_contains($query->sql, 'link_available_sent_for_visible_at')) {
                return;
            }

            $releaseChanged = true;
            app(SeminarDeliveryService::class)->update(
                $seminar,
                ['external_link_visible_at' => $nextRelease->toDateTimeString()],
                $seminar->connector->creator,
            );
        });

        $this->artisan('seminars:send-notices')->assertExitCode(0);

        $this->assertTrue($releaseChanged, 'The release edit must occur after the scheduler claim.');
        Notification::assertNotSentTo($registered, SeminarDeliveryNotification::class, fn ($notice) => $notice->kind === 'available');
        $this->assertEquals($nextRelease, $seminar->fresh()->external_link_visible_at);
        $this->assertNull($seminar->fresh()->link_available_sent_for_visible_at);
    }
}
