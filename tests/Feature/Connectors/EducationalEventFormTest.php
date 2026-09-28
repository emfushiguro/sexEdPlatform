<?php

namespace Tests\Feature\Connectors;

use App\Models\User;
use Tests\TestCase;

class EducationalEventFormTest extends TestCase
{
    use ConnectorTestHelpers;

    private function ownerAndConnector(): array
    {
        $owner = User::factory()->create(['role' => 'learner']);
        $owner->assignRole('learner');

        return [$owner, $this->createVerifiedConnector($owner)];
    }

    public function test_create_form_offers_supported_types_and_delivery_fields(): void
    {
        [$owner, $connector] = $this->ownerAndConnector();

        $this->actingAs($owner)->get(route('connector.seminars.create', $connector))
            ->assertOk()
            ->assertSee('Educational Event')
            ->assertSee('Objectives')
            ->assertSee('name="description"', false)
            ->assertSee('value="seminar"', false)
            ->assertSee('value="webinar"', false)
            ->assertSee('value="in_person"', false)
            ->assertSee('value="external"', false)
            ->assertSee('name="venue_address"', false)
            ->assertSee('name="external_platform"', false)
            ->assertSee('name="external_url"', false)
            ->assertSee('name="registration_deadline_at"', false)
            ->assertDontSee('value="native"', false);
    }

    public function test_external_form_shows_release_and_expiry_choices(): void
    {
        [$owner, $connector] = $this->ownerAndConnector();

        $this->actingAs($owner)->get(route('connector.seminars.create', $connector))
            ->assertOk()
            ->assertSee('name="external_link_visible_at"', false)
            ->assertSee('name="external_link_expiry_mode"', false)
            ->assertSee('name="external_link_expires_at"', false)
            ->assertSee('immediate access after confirmation')
            ->assertSee('remains available after completion');
    }

    public function test_registered_native_edit_keeps_fixed_delivery_and_native_controls(): void
    {
        [$owner, $connector] = $this->ownerAndConnector();
        $seminar = $connector->seminars()->create([
            ...$this->educationalEventPayload(['type' => 'webinar', 'event_format' => 'native']),
            'status' => 'published',
            'schedule' => now()->addDays(3),
            'external_platform' => null,
            'external_url' => null,
            'livestream_channel' => 'existing-native-channel',
        ]);
        $learner = $this->createCompletedLearner();
        $seminar->registrants()->create([
            'user_id' => $learner->id,
            'status' => 'registered',
            'participant_type' => 'learner',
            'registered_at' => now(),
        ]);

        $this->actingAs($owner)->get(route('connector.seminars.edit', [$connector, $seminar]))
            ->assertOk()
            ->assertSee('Native (Agora)')
            ->assertSee('name="event_format" value="native"', false)
            ->assertDontSee('value="in_person"', false);
        $this->actingAs($owner)->get(route('connector.seminars.show', [$connector, $seminar]))
            ->assertOk()->assertSee('Native (Agora)')->assertSee('Host Livestream');
    }

    public function test_external_webinar_is_classified_on_list_and_detail_without_native_control(): void
    {
        [$owner, $connector] = $this->ownerAndConnector();
        $seminar = $connector->seminars()->create([
            ...$this->educationalEventPayload(),
            'status' => 'published',
            'schedule' => now()->addDays(3),
        ]);

        $this->actingAs($owner)->get(route('connector.seminars.index', $connector))
            ->assertOk()->assertSee('Webinar')->assertSee('External Platform');
        $this->actingAs($owner)->get(route('connector.seminars.show', [$connector, $seminar]))
            ->assertOk()->assertSee('Webinar')->assertSee('External Platform')->assertDontSee('Host Livestream');
    }
}
