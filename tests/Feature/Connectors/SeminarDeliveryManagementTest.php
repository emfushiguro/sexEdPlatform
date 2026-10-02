<?php

namespace Tests\Feature\Connectors;

use App\Models\Seminar;
use App\Models\User;
use App\Notifications\Seminars\SeminarDeliveryNotification;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class SeminarDeliveryManagementTest extends TestCase
{
    use ConnectorTestHelpers;

    private function event($connector, array $attributes = []): Seminar
    {
        return $connector->seminars()->create(array_merge([
            'title' => 'External class', 'description' => 'Description', 'purpose' => 'Purpose',
            'type' => 'webinar', 'event_format' => 'external', 'status' => 'published',
            'external_platform' => 'zoom', 'external_url' => 'https://zoom.example.test/old',
            'external_link_expiry_mode' => 'ongoing', 'starts_at' => now()->addDay(),
            'ends_at' => now()->addDay()->addHour(), 'target_participants' => 'learners',
            'learner_age_categories' => ['adult'],
        ], $attributes));
    }

    public function test_owner_updates_delivery_once_and_only_eligible_recipients_are_notified(): void
    {
        Notification::fake();
        $owner = $this->createCompletedLearner();
        $connector = $this->createVerifiedConnector($owner);
        $seminar = $this->event($connector);
        $eligible = $this->createCompletedLearner();
        $pending = $this->createCompletedLearner();
        $cancelled = $this->createCompletedLearner();
        $ineligible = User::factory()->create(['role' => 'instructor']);
        $ineligible->assignRole('instructor');
        $speaker = User::factory()->create(['role' => 'instructor']);
        $speaker->assignRole('instructor');
        foreach ([[$eligible, 'registered'], [$pending, 'pending'], [$cancelled, 'cancelled'], [$ineligible, 'registered']] as [$user, $status]) {
            $seminar->registrants()->create(['user_id' => $user->id, 'status' => $status, 'registered_at' => now(), 'cancelled_at' => $status === 'cancelled' ? now() : null]);
        }
        $seminar->speakers()->create(['user_id' => $speaker->id, 'display_name' => 'Speaker', 'status' => 'accepted']);
        $seminar->speakers()->create(['user_id' => $pending->id, 'display_name' => 'Invited', 'status' => 'invited']);

        $this->actingAs($owner)->put(route('connector.seminars.delivery.update', [$connector, $seminar]), [
            'external_url' => 'https://zoom.example.test/new',
            'external_link_expiry_mode' => 'ongoing',
            'delivery_instructions' => 'Use your registered name.',
            'title' => 'Injected title',
        ])->assertRedirect();

        $this->assertSame('External class', $seminar->fresh()->title);
        $this->assertSame('https://zoom.example.test/new', $seminar->fresh()->external_url);
        foreach ([$eligible, $speaker] as $user) {
            Notification::assertSentToTimes($user, SeminarDeliveryNotification::class, 1);
            $notice = Notification::sent($user, SeminarDeliveryNotification::class)->first();
            $this->assertStringNotContainsString('https://zoom.example.test/old', json_encode($notice->toDatabase($user)));
            $this->assertStringNotContainsString('https://zoom.example.test/new', json_encode($notice->toDatabase($user)));
            $this->assertStringNotContainsString('https://zoom.example.test/old', json_encode($notice->toMail($user)->toArray()));
            $this->assertStringNotContainsString('https://zoom.example.test/new', json_encode($notice->toMail($user)->toArray()));
        }
        foreach ([$pending, $cancelled, $ineligible, $owner] as $user) {
            Notification::assertNotSentTo($user, SeminarDeliveryNotification::class);
        }
        $this->actingAs($owner)->put(route('connector.seminars.delivery.update', [$connector, $seminar]), [
            'external_url' => 'https://zoom.example.test/new', 'external_link_expiry_mode' => 'ongoing',
            'delivery_instructions' => 'Use your registered name.',
        ])->assertRedirect();
        Notification::assertSentToTimes($eligible, SeminarDeliveryNotification::class, 1);
    }

    public function test_permission_and_status_boundaries(): void
    {
        $owner = $this->createCompletedLearner();
        $connector = $this->createVerifiedConnector($owner);
        $other = $this->createVerifiedConnector($this->createCompletedLearner());
        $member = $this->createAdultConnectorMember($connector);
        $seminar = $this->event($connector);
        $payload = ['external_url' => 'https://zoom.example.test/new', 'external_link_expiry_mode' => 'ongoing'];
        $this->actingAs($member)->put(route('connector.seminars.delivery.update', [$connector, $seminar]), $payload)->assertForbidden();
        $this->actingAs($owner)->put(route('connector.seminars.delivery.update', [$other, $seminar]), $payload)->assertNotFound();
        foreach (['draft', 'pending_review', 'approved', 'cancelled', 'archived'] as $status) {
            $seminar->update(['status' => $status]);
            $this->actingAs($owner)->put(route('connector.seminars.delivery.update', [$connector, $seminar]), $payload)->assertStatus(422);
        }
        foreach (['native', 'in_person'] as $format) {
            $seminar->update(['status' => 'published', 'event_format' => $format]);
            $this->actingAs($owner)->put(route('connector.seminars.delivery.update', [$connector, $seminar]), $payload)->assertStatus(422);
        }
    }

    public function test_validation_rejects_unsafe_url_and_invalid_release_or_expiry(): void
    {
        $owner = $this->createCompletedLearner();
        $connector = $this->createVerifiedConnector($owner);
        $seminar = $this->event($connector);
        foreach ([
            [['external_url' => 'ftp://example.test/meeting'], 'external_url'],
            [['external_link_expiry_mode' => 'custom', 'external_link_expires_at' => now()->subHour()->timezone(config('app.display_timezone'))->format('Y-m-d H:i:s')], 'external_link_expires_at'],
            [['external_link_expiry_mode' => 'event_end', 'external_link_visible_at' => $seminar->ends_at->timezone(config('app.display_timezone'))->format('Y-m-d H:i:s')], 'external_link_visible_at'],
        ] as [$override, $field]) {
            $this->actingAs($owner)->put(route('connector.seminars.delivery.update', [$connector, $seminar]), array_merge([
                'external_url' => 'https://zoom.example.test/new', 'external_link_expiry_mode' => 'ongoing',
            ], $override))->assertSessionHasErrors($field);
        }
        $this->assertSame('https://zoom.example.test/old', $seminar->fresh()->external_url);
    }

    public function test_release_change_to_blank_resets_availability_marker(): void
    {
        $owner = $this->createCompletedLearner();
        $connector = $this->createVerifiedConnector($owner);
        $release = now()->addHours(2)->startOfMinute();
        $seminar = $this->event($connector, [
            'external_link_visible_at' => $release,
            'link_available_sent_for_visible_at' => $release,
        ]);
        $this->actingAs($owner)->put(route('connector.seminars.delivery.update', [$connector, $seminar]), [
            'external_link_visible_at' => '',
            'external_link_expiry_mode' => 'ongoing',
        ])->assertRedirect();
        $this->assertNull($seminar->fresh()->external_link_visible_at);
        $this->assertNull($seminar->fresh()->link_available_sent_for_visible_at);
    }
}
