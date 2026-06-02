<?php

namespace Tests\Feature\Connectors;

use App\Models\Connector;
use App\Models\User;
use Tests\TestCase;

class ConnectorRegistrationTest extends TestCase
{
    use ConnectorTestHelpers;

    public function test_authenticated_verified_learner_can_register_connector_without_creating_user(): void
    {
        $this->seedCaviteAddress();
        $user = User::factory()->create(['role' => 'learner']);
        $beforeUsers = User::count();

        $response = $this->actingAs($user)->post(route('connectors.store'), $this->connectorPayload([
            'organization_email' => null,
        ]));

        $connector = Connector::first();
        $response->assertRedirect(route('connector.status', $connector));
        $this->assertSame($beforeUsers, User::count());
        $this->assertSame('pending', $connector->status);
        $this->assertNull($connector->organization_email);
        $this->assertTrue($connector->roles()->where('name', 'Owner')->where('is_owner', true)->exists());
        $this->assertTrue($connector->memberships()->where('user_id', $user->id)->where('status', 'pending')->exists());
    }

    public function test_registration_requires_auth_verified_required_fields_and_unique_email(): void
    {
        $this->seedCaviteAddress();
        $this->get(route('connectors.register'))->assertRedirect(route('login'));

        $unverified = User::factory()->unverified()->create();
        $this->actingAs($unverified)->get(route('connectors.register'))->assertRedirect(route('verification.notice'));

        $user = User::factory()->create();
        Connector::create([
            ...$this->connectorPayload(['organization_email' => 'org@example.test']),
            'slug' => 'existing',
            'status' => 'pending',
            'created_by' => $user->id,
            'primary_representative_user_id' => $user->id,
        ]);

        $this->actingAs($user)
            ->post(route('connectors.store'), $this->connectorPayload([
                'name' => '',
                'organization_email' => 'org@example.test',
            ]))
            ->assertSessionHasErrors(['name', 'organization_email']);
    }

    public function test_registration_form_uses_fallback_categories_and_all_cavite_psgc_cities(): void
    {
        $this->seedCaviteAddress();
        config(['connector_permissions.categories' => []]);
        \DB::table('cities')->insert([
            'code' => '402199999',
            'name' => 'Cavite Prefix City',
            'region_code' => '400000000',
            'province_code' => null,
            'is_city' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs(User::factory()->create())
            ->get(route('connectors.register'));

        $response->assertOk()
            ->assertSee('Government')
            ->assertSee('NGO')
            ->assertSee('Cavite City')
            ->assertSee('Cavite Prefix City')
            ->assertSee('loadBarangays')
            ->assertSee('/api/barangays/');
    }
}
