<?php

namespace Tests\Feature\Instructor;

use App\Models\Module;
use App\Models\User;
use Tests\TestCase;

class ConnectorMarketplaceDrawerTest extends TestCase
{
    public function test_owner_sees_compact_listing_control_and_capacity_in_a_drawer(): void
    {
        $instructor = User::factory()->create(['role' => 'instructor']);
        $instructor->assignRole('instructor');
        $module = Module::factory()->create([
            'created_by' => $instructor->id,
            'enrollment_limit' => 50,
            'current_review_status' => 'approved',
        ]);

        $this->actingAs($instructor)->get(route('instructor.modules.show', $module))
            ->assertOk()
            ->assertSee('Manage connector listing')
            ->assertSee('role="dialog"', false)
            ->assertSee('aria-modal="true"', false)
            ->assertSee('Seats per Connector purchase')
            ->assertSee('50 seats available')
            ->assertSee('x-teleport="body"', false);
    }

    public function test_listing_errors_reopen_drawer_and_preserve_input(): void
    {
        $instructor = User::factory()->create(['role' => 'instructor']);
        $instructor->assignRole('instructor');
        $module = Module::factory()->create([
            'created_by' => $instructor->id,
            'enrollment_limit' => 5,
            'current_review_status' => 'approved',
        ]);

        $this->actingAs($instructor)->from(route('instructor.modules.show', $module))
            ->put(route('instructor.modules.marketplace.update', $module), [
                'action' => 'publish',
                'marketplace_listing' => '1',
                'price_amount' => 5000,
                'currency' => config('billing.currency.code'),
                'seat_capacity' => 50,
            ])
            ->assertRedirect(route('instructor.modules.show', $module))
            ->assertSessionHasErrors('capacity');

        $this->get(route('instructor.modules.show', $module))
            ->assertOk()
            ->assertSee('data-marketplace-errors="true"', false)
            ->assertSee('Module capacity is not available.')
            ->assertSee('value="5000"', false)
            ->assertSee('value="50"', false);
    }
}
