<?php

namespace Tests\Feature\Seminars;

use App\Models\Connector;
use App\Models\Seminar;
use App\Models\User;
use App\Notifications\Seminars\SeminarRegistrationConfirmedNotification;
use App\Services\Seminars\SeminarExternalAccessService;
use Tests\Feature\Connectors\ConnectorTestHelpers;
use Tests\TestCase;

class SeminarExternalAccessTest extends TestCase
{
    use ConnectorTestHelpers;

    private const URL = 'https://classroom.example.test/course/1?token=secret';

    public function test_confirmed_eligible_registrant_gets_link_at_release_with_private_response_headers(): void
    {
        $seminar = $this->event(['external_link_visible_at' => now()->addHour()]);
        $registered = $this->createCompletedLearner();
        $this->registerUser($seminar, $registered);

        $this->travelTo($seminar->external_link_visible_at->copy()->subSecond());
        $this->actingAs($registered)->get(route('seminars.external.join', $seminar))->assertForbidden();
        $this->travelTo($seminar->external_link_visible_at);
        $this->actingAs($registered)->get(route('seminars.external.join', $seminar))
            ->assertRedirect(self::URL)
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertHeader('Referrer-Policy', 'no-referrer');
    }

    public function test_pending_cancelled_ineligible_and_unregistered_people_cannot_get_link(): void
    {
        $seminar = $this->event();
        $pending = $this->createCompletedLearner();
        $cancelled = $this->createCompletedLearner();
        $ineligible = $this->createCompletedLearner();
        $unregistered = $this->createCompletedLearner();
        $this->registerUser($seminar, $pending, 'pending');
        $this->registerUser($seminar, $cancelled, 'cancelled');
        $this->registerUser($seminar, $ineligible);

        foreach ([$pending, $cancelled, $unregistered] as $person) {
            $this->actingAs($person)->get(route('seminars.external.join', $seminar))->assertForbidden();
        }
        $seminar->update(['learner_age_categories' => ['teen']]);
        $this->actingAs($ineligible)->get(route('seminars.external.join', $seminar))->assertForbidden();
    }

    public function test_accepted_speaker_and_authorized_management_can_prepare_before_release(): void
    {
        $owner = $this->createCompletedLearner();
        $connector = $this->createVerifiedConnector($owner);
        $seminar = $this->event(['external_link_visible_at' => now()->addHour(), 'target_participants' => 'learners'], $connector);
        $speaker = User::factory()->create(['role' => 'instructor']);
        $speaker->assignRole('instructor');
        $unaccepted = User::factory()->create(['role' => 'instructor']);
        $unaccepted->assignRole('instructor');
        $memberWithoutPermission = $this->createAdultConnectorMember($connector);
        $admin = User::factory()->create(['role' => 'admin']);
        $admin->assignRole('admin');
        $seminar->speakers()->create(['user_id' => $speaker->id, 'display_name' => 'Accepted', 'role' => 'speaker', 'status' => 'accepted']);
        $seminar->speakers()->create(['user_id' => $unaccepted->id, 'display_name' => 'Invited', 'role' => 'speaker', 'status' => 'invited']);

        foreach ([$speaker, $owner, $admin] as $person) {
            $this->actingAs($person)->get(route('seminars.external.join', $seminar))->assertRedirect(self::URL);
        }
        $this->actingAs($unaccepted)->get(route('seminars.external.join', $seminar))->assertForbidden();
        $this->actingAs($memberWithoutPermission)->get(route('seminars.external.join', $seminar))->assertForbidden();
    }

    public function test_expiry_is_exclusive_for_custom_and_event_end_modes(): void
    {
        $registered = $this->createCompletedLearner();
        foreach (['custom', 'event_end'] as $mode) {
            $expiry = now()->addHour();
            $seminar = $this->event([
                'external_link_expiry_mode' => $mode,
                'external_link_expires_at' => $mode === 'custom' ? $expiry : null,
                'ends_at' => $expiry,
            ]);
            $this->registerUser($seminar, $registered);
            $this->travelTo($expiry->copy()->subSecond());
            $this->actingAs($registered)->get(route('seminars.external.join', $seminar))->assertRedirect(self::URL);
            $this->travelTo($expiry);
            $this->actingAs($registered)->get(route('seminars.external.join', $seminar))->assertForbidden();
        }
    }

    public function test_completed_ongoing_remains_accessible_but_blocked_statuses_never_redirect(): void
    {
        $registered = $this->createCompletedLearner();
        $seminar = $this->event(['status' => 'completed', 'ends_at' => now()->subDay()]);
        $this->registerUser($seminar, $registered);
        $this->actingAs($registered)->get(route('seminars.external.join', $seminar))->assertRedirect(self::URL);

        foreach (['cancelled', 'archived', 'draft'] as $status) {
            $seminar->update(['status' => $status]);
            $this->actingAs($registered)->get(route('seminars.external.join', $seminar))->assertForbidden();
        }
    }

    public function test_missing_and_malformed_legacy_urls_fail_closed(): void
    {
        $registered = $this->createCompletedLearner();
        $seminar = $this->event();
        $this->registerUser($seminar, $registered);

        foreach ([null, '/relative/course', 'javascript:alert(1)', 'https://'] as $url) {
            $seminar->update(['external_url' => $url]);
            $this->actingAs($registered)->get(route('seminars.external.join', $seminar))->assertForbidden();
        }

        $seminar->update(['external_url' => self::URL, 'external_link_expiry_mode' => 'custom', 'external_link_expires_at' => null]);
        $this->actingAs($registered)->get(route('seminars.external.join', $seminar))->assertForbidden();
        $seminar->update(['external_link_expiry_mode' => 'event_end', 'ends_at' => null]);
        $this->actingAs($registered)->get(route('seminars.external.join', $seminar))->assertForbidden();
    }

    public function test_redirect_checks_fresh_event_and_registration_state(): void
    {
        $registered = $this->createCompletedLearner();
        $seminar = $this->event();
        $this->registerUser($seminar, $registered);
        $access = app(SeminarExternalAccessService::class);
        $this->assertTrue($access->canJoin($registered, $seminar));
        $stale = $seminar->fresh();

        $seminar->update(['external_url' => 'https://classroom.example.test/replacement']);
        $this->assertSame('https://classroom.example.test/replacement', $access->redirectUrl($registered, $stale));
        $seminar->registrants()->where('user_id', $registered->id)->update(['cancelled_at' => now()]);
        $this->actingAs($registered)->get(route('seminars.external.join', $seminar))->assertForbidden();
    }

    public function test_detail_and_confirmation_notification_do_not_expose_raw_url(): void
    {
        $registered = $this->createCompletedLearner();
        $seminar = $this->event(['external_link_visible_at' => now()->addHour()]);
        $this->registerUser($seminar, $registered);

        $this->actingAs($registered)->get(route('seminars.show', $seminar))
            ->assertOk()->assertDontSee(self::URL, false)->assertSee('available');
        $notification = new SeminarRegistrationConfirmedNotification($seminar->load('connector'));
        $this->assertStringNotContainsString(self::URL, json_encode($notification->toDatabase($registered)));
        $this->assertStringNotContainsString(self::URL, $notification->toMail($registered)->render());

        $this->travelTo($seminar->external_link_visible_at);
        $this->actingAs($registered)->get(route('seminars.show', $seminar))
            ->assertOk()->assertSee(route('seminars.external.join', $seminar))->assertDontSee(self::URL, false);
    }

    public function test_detail_shows_safe_expired_and_unavailable_messages(): void
    {
        $registered = $this->createCompletedLearner();
        $seminar = $this->event(['external_link_expiry_mode' => 'custom', 'external_link_expires_at' => now()->addMinute()]);
        $this->registerUser($seminar, $registered);

        $this->travelTo($seminar->external_link_expires_at);
        $this->actingAs($registered)->get(route('seminars.show', $seminar))
            ->assertOk()->assertSee('The join link has expired.')
            ->assertDontSee(route('seminars.external.join', $seminar))
            ->assertDontSee(self::URL, false);

        $seminar->update(['external_url' => null]);
        $this->actingAs($registered)->get(route('seminars.show', $seminar))
            ->assertOk()->assertSee('The join link is unavailable.')
            ->assertDontSee(self::URL, false);
    }

    private function event(array $overrides = [], ?Connector $connector = null): Seminar
    {
        $connector ??= $this->createVerifiedConnector($this->createCompletedLearner());

        return $connector->seminars()->create(array_merge([
            'title' => 'External Class',
            'description' => 'A useful class.',
            'purpose' => 'Learn skills.',
            'type' => 'webinar',
            'event_format' => 'external',
            'external_platform' => 'google_classroom',
            'external_url' => self::URL,
            'external_link_expiry_mode' => 'ongoing',
            'category' => 'health',
            'status' => 'published',
            'starts_at' => now()->addDay(),
            'ends_at' => now()->addDay()->addHour(),
            'schedule' => now()->addDay(),
            'target_participants' => 'learners',
            'learner_age_categories' => ['adult'],
        ], $overrides));
    }

    private function registerUser(Seminar $seminar, User $user, string $status = 'registered'): void
    {
        $seminar->registrants()->create([
            'user_id' => $user->id,
            'status' => $status,
            'participant_type' => 'learner',
            'registered_at' => now(),
        ]);
    }
}
