<?php

namespace Tests\Feature\Connectors;

use App\Models\Seminar;
use App\Models\User;
use Tests\TestCase;

class EducationalEventAuthoringTest extends TestCase
{
    use ConnectorTestHelpers;

    private function ownerAndConnector(): array
    {
        $owner = User::factory()->create(['role' => 'learner']);
        $owner->assignRole('learner');

        return [$owner, $this->createVerifiedConnector($owner)];
    }

    public function test_creates_each_supported_event_combination_without_native_channel(): void
    {
        [$owner, $connector] = $this->ownerAndConnector();

        foreach ([
            ['seminar', 'in_person'],
            ['seminar', 'external'],
            ['webinar', 'external'],
        ] as [$type, $format]) {
            $payload = $this->educationalEventPayload([
                'title' => "$type $format",
                'type' => $type,
                'event_format' => $format,
                'location' => 'Community Hall',
                'venue_address' => '123 Main Street, Cavite',
            ]);

            $this->actingAs($owner)->post(route('connector.seminars.store', $connector), $payload)->assertRedirect();
            $seminar = $connector->seminars()->where('title', "$type $format")->firstOrFail();
            $this->assertSame($type, $seminar->type);
            $this->assertSame($format, $seminar->event_format);
            $this->assertSame($seminar->starts_at->toDateTimeString(), $seminar->schedule->toDateTimeString());
            $this->assertNull($seminar->livestream_channel);
            $this->assertSame($format === 'external' ? 'https://zoom.example.test/j/123' : null, $seminar->external_url);
        }
    }

    public function test_rejects_unsupported_pairs_and_incomplete_delivery_fields(): void
    {
        [$owner, $connector] = $this->ownerAndConnector();

        foreach ([
            [['type' => 'webinar', 'event_format' => 'in_person'], 'event_format'],
            [['type' => 'webinar', 'event_format' => 'native'], 'event_format'],
            [['type' => 'physical', 'event_format' => 'in_person'], 'type'],
            [['description' => ''], 'description'],
            [['purpose' => ''], 'purpose'],
            [['type' => 'seminar', 'event_format' => 'in_person', 'location' => ''], 'location'],
            [['type' => 'seminar', 'event_format' => 'in_person', 'location' => 'Hall', 'venue_address' => ''], 'venue_address'],
            [['external_platform' => ''], 'external_platform'],
            [['external_platform' => 'other', 'external_platform_name' => ''], 'external_platform_name'],
            [['external_url' => 'ftp://example.test/meeting'], 'external_url'],
            [['external_url' => '/relative/meeting'], 'external_url'],
        ] as [$overrides, $field]) {
            $this->actingAs($owner)
                ->post(route('connector.seminars.store', $connector), $this->educationalEventPayload($overrides))
                ->assertSessionHasErrors($field);
        }
    }

    public function test_in_person_authoring_discards_stale_external_fields(): void
    {
        [$owner, $connector] = $this->ownerAndConnector();
        $this->actingAs($owner)->post(route('connector.seminars.store', $connector), $this->educationalEventPayload([
            'type' => 'seminar',
            'event_format' => 'in_person',
            'location' => 'Community Hall',
            'venue_address' => '123 Main Street',
            'external_platform' => 'other',
            'external_platform_name' => null,
            'external_url' => 'ftp://example.test/old-link',
        ]))->assertRedirect();

        $this->assertSame(1, $connector->seminars()->count());
        $seminar = $connector->seminars()->firstOrFail();
        $this->assertNull($seminar->external_platform);
        $this->assertNull($seminar->external_url);
    }

    public function test_converts_optional_local_times_to_utc_and_validates_boundaries(): void
    {
        [$owner, $connector] = $this->ownerAndConnector();
        $start = now()->addDays(5)->timezone(config('app.display_timezone'))->startOfHour();
        $payload = $this->educationalEventPayload([
            'starts_at' => $start->format('Y-m-d H:i:s'),
            'ends_at' => $start->copy()->addHours(2)->format('Y-m-d H:i:s'),
            'registration_deadline_at' => $start->copy()->subHour()->format('Y-m-d H:i:s'),
            'external_link_visible_at' => $start->copy()->subHours(3)->format('Y-m-d H:i:s'),
            'external_link_expiry_mode' => 'custom',
            'external_link_expires_at' => $start->copy()->addHours(3)->format('Y-m-d H:i:s'),
            'attendance_start_at' => $start->copy()->subMinutes(15)->format('Y-m-d H:i:s'),
            'attendance_end_at' => $start->copy()->addHours(2)->addMinutes(30)->format('Y-m-d H:i:s'),
        ]);

        $this->actingAs($owner)->post(route('connector.seminars.store', $connector), $payload)->assertRedirect();
        $seminar = $connector->seminars()->latest('id')->firstOrFail();
        foreach (['starts_at', 'registration_deadline_at', 'external_link_visible_at', 'external_link_expires_at', 'attendance_start_at', 'attendance_end_at'] as $field) {
            $this->assertNotNull($seminar->$field, $field);
            $this->assertSame(
                \Illuminate\Support\Carbon::parse($payload[$field], config('app.display_timezone'))->utc()->toDateTimeString(),
                $seminar->$field->toDateTimeString(),
                $field
            );
        }

        foreach ([
            ['registration_deadline_at' => $payload['starts_at']],
            ['registration_deadline_at' => $start->copy()->addMinute()->format('Y-m-d H:i:s')],
        ] as $invalid) {
            $this->actingAs($owner)->post(route('connector.seminars.store', $connector), array_merge($payload, $invalid))
                ->assertSessionHasErrors('registration_deadline_at');
        }
        $this->actingAs($owner)->post(route('connector.seminars.store', $connector), array_merge($payload, [
            'external_link_expires_at' => $payload['external_link_visible_at'],
        ]))->assertSessionHasErrors('external_link_expires_at');
        $this->actingAs($owner)->post(route('connector.seminars.store', $connector), array_merge($payload, [
            'external_link_expiry_mode' => 'event_end',
            'external_link_visible_at' => $start->copy()->addHours(2)->format('Y-m-d H:i:s'),
        ]))->assertSessionHasErrors('external_link_visible_at');
    }

    public function test_registered_event_rejects_identity_schedule_capacity_and_audience_changes(): void
    {
        [$owner, $connector] = $this->ownerAndConnector();
        $this->actingAs($owner)->post(route('connector.seminars.store', $connector), $this->educationalEventPayload(['type' => 'seminar']))->assertRedirect();
        $seminar = $connector->seminars()->firstOrFail();
        $seminar->update(['status' => 'published']);
        $learner = $this->createCompletedLearner();
        $seminar->registrants()->create([
            'user_id' => $learner->id,
            'status' => 'registered',
            'participant_type' => 'learner',
            'registered_at' => now(),
        ]);
        $secondLearner = $this->createCompletedLearner();
        $seminar->registrants()->create([
            'user_id' => $secondLearner->id,
            'status' => 'registered',
            'participant_type' => 'learner',
            'registered_at' => now(),
        ]);
        $route = route('connector.seminars.update', [$connector, $seminar]);
        $base = $this->educationalEventPayload([
            'type' => 'seminar',
            'starts_at' => $seminar->starts_at->copy()->timezone(config('app.display_timezone'))->format('Y-m-d H:i:s'),
            'ends_at' => $seminar->ends_at->copy()->timezone(config('app.display_timezone'))->format('Y-m-d H:i:s'),
        ]);

        foreach ([
            ['type' => 'webinar'],
            ['event_format' => 'in_person', 'type' => 'seminar', 'location' => 'Hall', 'venue_address' => 'Street'],
            ['starts_at' => now()->addDays(4)->timezone(config('app.display_timezone'))->format('Y-m-d H:i:s'), 'ends_at' => now()->addDays(4)->addHour()->timezone(config('app.display_timezone'))->format('Y-m-d H:i:s')],
            ['ends_at' => now()->addDays(4)->timezone(config('app.display_timezone'))->format('Y-m-d H:i:s')],
            ['capacity' => 1],
            ['target_participants' => 'instructors'],
            ['learner_age_categories' => ['kids']],
        ] as $changes) {
            $this->actingAs($owner)->put($route, array_merge($base, $changes))->assertStatus(422);
        }
    }

    public function test_legacy_native_webinar_can_be_edited_and_missing_channel_is_generated(): void
    {
        [$owner, $connector] = $this->ownerAndConnector();
        $payload = $this->educationalEventPayload([
            'type' => 'webinar',
            'event_format' => 'native',
            'title' => 'Legacy native webinar',
        ]);
        $seminar = $connector->seminars()->create([
            ...$payload,
            'schedule' => now()->addDays(3),
            'status' => 'draft',
            'external_url' => null,
            'external_platform' => null,
            'livestream_channel' => null,
        ]);

        $this->actingAs($owner)->put(route('connector.seminars.update', [$connector, $seminar]), $payload)->assertRedirect();
        $this->assertNotNull($seminar->fresh()->livestream_channel);
    }

    public function test_incomplete_persisted_event_cannot_enter_review_or_publication(): void
    {
        [$owner, $connector] = $this->ownerAndConnector();
        $seminar = $connector->seminars()->create([
            ...$this->educationalEventPayload(),
            'description' => null,
            'schedule' => now()->addDays(3),
            'status' => 'draft',
        ]);
        $this->actingAs($owner)->post(route('connector.seminars.submit-review', [$connector, $seminar]))->assertStatus(422);
        $seminar->update(['status' => 'approved']);
        $this->actingAs($owner)->post(route('connector.seminars.publish', [$connector, $seminar]))->assertStatus(422);
    }
}
