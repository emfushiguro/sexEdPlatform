<?php

namespace Tests\Feature\Seminars;

use App\Models\User;
use Illuminate\Support\MessageBag;
use Spatie\Permission\Models\Role;
use Tests\Feature\Connectors\ConnectorTestHelpers;
use Tests\TestCase;

class EducationalEventPresentationTest extends TestCase
{
    use ConnectorTestHelpers;

    public function test_delivery_errors_reopen_access_without_opening_admin_rejection(): void
    {
        $owner = $this->createCompletedLearner();
        $connector = $this->createVerifiedConnector($owner);
        $seminar = $connector->seminars()->create([
            ...$this->educationalEventPayload(),
            'status' => 'published',
            'schedule' => now()->addDays(3),
        ]);
        $admin = User::factory()->create(['role' => 'admin']);
        $admin->assignRole(Role::findOrCreate('admin', 'web'));

        $this->actingAs($admin)->withSession(['errors' => (new \Illuminate\Support\ViewErrorBag)->put('default', new MessageBag(['external_url' => 'Enter a valid event link.']))])
            ->get(route('admin.seminars.show', $seminar))
            ->assertOk()
            ->assertSee('rejectOpen: false', false)
            ->assertSee('init() { if (true)', false)
            ->assertSee('Enter a valid event link.')
            ->assertSee('z-[100000]', false)
            ->assertSee('aria-modal="true"', false);

        $this->withSession(['errors' => (new \Illuminate\Support\ViewErrorBag)->put('default', new MessageBag(['reason' => 'Choose a moderation reason.']))])
            ->get(route('admin.seminars.show', $seminar))
            ->assertOk()
            ->assertSee('rejectOpen: true', false)
            ->assertSee('init() { if (false)', false);

        $this->actingAs($owner)->withSession(['errors' => (new \Illuminate\Support\ViewErrorBag)->put('default', new MessageBag(['external_url' => 'Enter a valid event link.']))])
            ->get(route('connector.seminars.show', [$connector, $seminar]))
            ->assertOk()
            ->assertSee('init() { if (true)', false)
            ->assertSee('Enter a valid event link.')
            ->assertSee('Manage event access');
    }
}
