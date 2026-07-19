<?php

namespace Tests\Feature\Connectors;

use App\Models\User;
use Illuminate\Support\Str;
use Tests\TestCase;

class ConnectorNotificationCenterTest extends TestCase
{
    use ConnectorTestHelpers;

    public function test_connector_notification_page_scopes_to_current_connector(): void
    {
        $owner = User::factory()->create();
        $connector = $this->createVerifiedConnector($owner);
        $otherConnector = $this->createVerifiedConnector($owner);

        $owner->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => 'connector.update',
            'data' => [
                'type' => 'connector_membership_request_submitted',
                'title' => 'Visible connector notice',
                'message' => 'A member request arrived.',
                'connector_id' => $connector->id,
                'action_url' => route('connector.members.index', $connector),
            ],
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $otherNotification = $owner->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => 'connector.update',
            'data' => [
                'type' => 'connector_membership_request_submitted',
                'title' => 'Hidden connector notice',
                'message' => 'This belongs elsewhere.',
                'connector_id' => $otherConnector->id,
                'action_url' => route('connector.members.index', $otherConnector),
            ],
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($owner)
            ->get(route('connector.notifications.index', $connector))
            ->assertOk()
            ->assertSee('Visible connector notice')
            ->assertDontSee('Hidden connector notice');

        $this->actingAs($owner)
            ->get(route('connector.notifications.read', [$connector, $otherNotification->id]))
            ->assertNotFound();
    }

    public function test_connector_notification_read_route_deep_links_to_action_url(): void
    {
        $owner = User::factory()->create();
        $connector = $this->createVerifiedConnector($owner);

        $notification = $owner->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => 'connector.update',
            'data' => [
                'title' => 'Open members',
                'message' => 'Invitation accepted.',
                'connector_id' => $connector->id,
                'action_url' => route('connector.members.index', $connector),
            ],
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($owner)
            ->get(route('connector.notifications.read', [$connector, $notification->id]))
            ->assertRedirect(route('connector.members.index', $connector));

        $this->assertNotNull($notification->fresh()->read_at);
    }
}
