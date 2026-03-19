<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class AdminTableUxTest extends TestCase
{
    use DatabaseTransactions;

    public function test_management_tables_expose_standard_columns_and_actions(): void
    {
        $this->withoutVite();
        config()->set('paymongo.secret_key', 'sk_test_dummy');
        config()->set('paymongo.api_base_url', 'https://api.paymongo.com/v1');

        $admin = User::withoutEvents(fn () => User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]));
        $admin->assignRole('admin');

        $this->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('Actions', false)
            ->assertSee('data-testid="admin-table-filter-bar"', false)
            ->assertSee('data-testid="admin-row-actions"', false);

        $this->actingAs($admin)
            ->get(route('admin.subscribers.index'))
            ->assertOk()
            ->assertSee('Subscriber', false)
            ->assertSee('Actions', false)
            ->assertSee('data-testid="admin-table-filter-bar"', false)
            ->assertSee('data-testid="admin-row-actions"', false);

        $this->actingAs($admin)
            ->get(route('admin.subscription-plans.index'))
            ->assertOk()
            ->assertSee('Plan', false)
            ->assertSee('Actions', false)
            ->assertSee('data-testid="admin-table-filter-bar"', false)
            ->assertSee('data-testid="admin-row-actions"', false);

        $this->actingAs($admin)
            ->get(route('admin.payments.index'))
            ->assertOk()
            ->assertSee('Amount', false)
            ->assertSee('Actions', false)
            ->assertSee('data-testid="admin-table-filter-bar"', false)
            ->assertSee('data-testid="admin-row-actions"', false);
    }
}
