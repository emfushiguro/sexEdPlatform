<?php

namespace Tests\Feature\Connectors;

use App\Models\Connector;
use App\Models\User;
use Tests\TestCase;

class ConnectorHomeTest extends TestCase
{
    use ConnectorTestHelpers;

    public function test_sidebar_links_to_connector_home(): void
    {
        $user = $this->createCompletedLearner();

        $this->actingAs($user)
            ->get(route('learner.dashboard'))
            ->assertOk()
            ->assertSee(route('connectors.index'), false)
            ->assertSee('Connectors');
    }

    public function test_user_with_connectors_sees_management_hub_and_discovery_option(): void
    {
        $this->seedCaviteAddress();
        $owner = User::factory()->create();
        $connector = $this->createVerifiedConnector($owner);
        Connector::create([
            ...$this->connectorPayload(['name' => 'Public Health Partner']),
            'slug' => 'public-health-partner',
            'status' => 'verified',
            'created_by' => $owner->id,
            'primary_representative_user_id' => $owner->id,
        ]);

        $this->actingAs($owner)
            ->get(route('connectors.index'))
            ->assertOk()
            ->assertSee('My Connectors')
            ->assertSee($connector->name)
            ->assertSee('Discover Connectors')
            ->assertSee('Public Health Partner');
    }

    public function test_user_without_connectors_sees_verified_discovery_feed_only(): void
    {
        $this->seedCaviteAddress();
        $user = User::factory()->create();
        $admin = User::factory()->create();
        Connector::create([
            ...$this->connectorPayload(['name' => 'Verified Advocacy Group', 'description' => 'Community learning partner.']),
            'slug' => 'verified-advocacy-group',
            'status' => 'verified',
            'created_by' => $admin->id,
            'primary_representative_user_id' => $admin->id,
        ]);
        Connector::create([
            ...$this->connectorPayload(['name' => 'Pending Hidden Group']),
            'slug' => 'pending-hidden-group',
            'status' => 'pending',
            'created_by' => $admin->id,
            'primary_representative_user_id' => $admin->id,
        ]);

        $this->actingAs($user)
            ->get(route('connectors.index'))
            ->assertOk()
            ->assertSee('Connector Discovery')
            ->assertSee('Verified Advocacy Group')
            ->assertSee('Community learning partner.')
            ->assertSee('Request to Join')
            ->assertDontSee('Pending Hidden Group');
    }
}
