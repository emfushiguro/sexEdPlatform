<?php

namespace Tests\Feature\Seminars;

use App\Models\Connector;
use App\Models\Seminar;
use App\Models\User;
use App\Notifications\Seminars\SeminarLiveNotification;
use Illuminate\Support\Facades\Notification;
use Tests\Feature\Connectors\ConnectorTestHelpers;
use Tests\TestCase;

class SeminarLivestreamAccessTest extends TestCase
{
    use ConnectorTestHelpers;

    public function test_registered_audience_can_join_during_window_but_cannot_publish(): void
    {
        config()->set('services.agora.app_id', 'agora-app');
        config()->set('services.agora.app_certificate', 'agora-secret');

        $connector = $this->connector();
        $learner = $this->createCompletedLearner(['age_bracket_cached' => 'adults']);
        $seminar = $this->seminar($connector, ['livestream_status' => 'live', 'livestream_started_at' => now()]);
        $seminar->registrants()->create([
            'user_id' => $learner->id,
            'status' => 'registered',
            'participant_type' => 'learner',
            'registered_at' => now(),
        ]);

        $this->actingAs($learner)
            ->get(route('seminars.join', $seminar))
            ->assertOk()
            ->assertSee('Audience');

        $this->actingAs($learner)
            ->get(route('seminars.show', $seminar))
            ->assertOk()
            ->assertSee('Join Livestream');

        $this->actingAs($learner)
            ->postJson(route('seminars.agora-token', $seminar))
            ->assertOk()
            ->assertJsonPath('role', 'audience')
            ->assertJsonPath('can_publish', false);
    }

    public function test_assigned_platform_speaker_can_publish_and_external_speaker_cannot_join(): void
    {
        config()->set('services.agora.app_id', 'agora-app');
        config()->set('services.agora.app_certificate', 'agora-secret');

        $connector = $this->connector();
        $speaker = User::factory()->create(['role' => 'instructor']);
        $speaker->assignRole('instructor');
        $externalUser = User::factory()->create(['role' => 'instructor']);
        $externalUser->assignRole('instructor');
        $seminar = $this->seminar($connector, ['livestream_status' => 'live', 'livestream_started_at' => now()]);
        $seminar->speakers()->create([
            'user_id' => $speaker->id,
            'display_name' => $speaker->name,
            'role' => 'speaker',
        ]);
        $seminar->speakers()->create([
            'user_id' => null,
            'display_name' => 'External Speaker',
            'role' => 'speaker',
        ]);

        $this->actingAs($speaker)
            ->postJson(route('seminars.agora-token', $seminar))
            ->assertOk()
            ->assertJsonPath('role', 'speaker')
            ->assertJsonPath('can_publish', true);

        $this->actingAs($externalUser)
            ->get(route('seminars.join', $seminar))
            ->assertForbidden();
    }

    public function test_connector_host_can_publish_only_for_owned_seminar_and_access_closes_after_end(): void
    {
        config()->set('services.agora.app_id', 'agora-app');
        config()->set('services.agora.app_certificate', 'agora-secret');

        $owner = User::factory()->create(['role' => 'learner']);
        $owner->assignRole('learner');
        $connector = $this->createVerifiedConnector($owner);
        $otherConnector = $this->connector();
        $seminar = $this->seminar($connector);
        $ended = $this->seminar($connector, [
            'title' => 'Ended Seminar',
            'starts_at' => now()->subHour(),
            'ends_at' => now()->subMinute(),
            'schedule' => now()->subHour(),
        ]);

        $this->actingAs($owner)
            ->postJson(route('connector.seminars.agora-token', [$connector, $seminar]))
            ->assertOk()
            ->assertJsonPath('role', 'host')
            ->assertJsonPath('can_publish', true);

        $this->actingAs($owner)
            ->get(route('connector.seminars.show', [$connector, $seminar]))
            ->assertOk()
            ->assertSee('Host Livestream');

        $this->actingAs($owner)
            ->get(route('connector.seminars.livestream', [$otherConnector, $seminar]))
            ->assertForbidden();

        $this->actingAs($owner)
            ->postJson(route('connector.seminars.agora-token', [$connector, $ended]))
            ->assertForbidden();
    }

    public function test_audience_and_speakers_are_blocked_until_host_goes_live(): void
    {
        config()->set('services.agora.app_id', 'agora-app');
        config()->set('services.agora.app_certificate', 'agora-secret');

        $connector = $this->connector();
        $learner = $this->createCompletedLearner(['age_bracket_cached' => 'adults']);
        $speaker = User::factory()->create(['role' => 'instructor']);
        $speaker->assignRole('instructor');
        $seminar = $this->seminar($connector);
        $seminar->registrants()->create(['user_id' => $learner->id, 'status' => 'registered', 'participant_type' => 'learner', 'registered_at' => now()]);
        $seminar->speakers()->create(['user_id' => $speaker->id, 'display_name' => $speaker->name, 'role' => 'speaker', 'status' => 'accepted']);

        $this->actingAs($learner)->postJson(route('seminars.agora-token', $seminar))->assertForbidden();
        $this->actingAs($speaker)->postJson(route('seminars.agora-token', $seminar))->assertForbidden();
    }

    public function test_start_is_idempotent_notifies_once_and_end_completes_the_session(): void
    {
        Notification::fake();
        config()->set('services.agora.app_id', 'agora-app');
        config()->set('services.agora.app_certificate', 'agora-secret');

        $owner = User::factory()->create(['role' => 'learner']);
        $owner->assignRole('learner');
        $connector = $this->createVerifiedConnector($owner);
        $learner = $this->createCompletedLearner(['age_bracket_cached' => 'adults']);
        $speaker = User::factory()->create(['role' => 'instructor']);
        $speaker->assignRole('instructor');
        $seminar = $this->seminar($connector);
        $seminar->registrants()->create(['user_id' => $learner->id, 'status' => 'registered', 'participant_type' => 'learner', 'registered_at' => now()]);
        $seminar->speakers()->create(['user_id' => $speaker->id, 'display_name' => $speaker->name, 'role' => 'speaker', 'status' => 'accepted']);

        $this->actingAs($owner)
            ->postJson(route('connector.seminars.livestream.start', [$connector, $seminar]))
            ->assertOk()->assertJsonPath('status', 'live')->assertJsonPath('started', true);
        $this->actingAs($owner)
            ->postJson(route('connector.seminars.livestream.start', [$connector, $seminar]))
            ->assertOk()->assertJsonPath('started', false);

        $this->assertSame('live', $seminar->fresh()->livestream_status);
        $this->assertCount(1, Notification::sent($learner, SeminarLiveNotification::class));
        $this->assertCount(1, Notification::sent($speaker, SeminarLiveNotification::class));

        $this->actingAs($owner)
            ->postJson(route('connector.seminars.livestream.end', [$connector, $seminar]))
            ->assertOk()->assertJsonPath('status', 'completed');
        $this->assertSame('completed', $seminar->fresh()->status);
    }

    private function connector(): Connector
    {
        $owner = User::factory()->create(['role' => 'learner']);
        $owner->assignRole('learner');

        return $this->createVerifiedConnector($owner);
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
