<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Route;
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
            ->get(route('admin.subscribers.index'))
            ->assertOk()
            ->assertSee('Subscriber', false)
            ->assertSee('No.', false)
            ->assertSee('Actions', false)
            ->assertDontSee('Retry Payment', false)
            ->assertDontSee('Extend Grace', false)
            ->assertDontSee('Schedule Cancel', false)
            ->assertDontSee('Reactivate', false)
            ->assertSee('data-testid="admin-table-filter-bar"', false)
            ->assertDontSee('data-testid="admin-row-actions"', false);

        $this->actingAs($admin)
            ->get(route('admin.subscription-plans.index'))
            ->assertOk()
            ->assertSee('Plan', false)
            ->assertSee('No.', false)
            ->assertSee('Actions', false)
            ->assertDontSee('Trial', false)
            ->assertSee('title="Archive"', false)
            ->assertDontSee('title="Delete"', false)
            ->assertSee('data-testid="admin-table-filter-bar"', false)
            ->assertDontSee('data-testid="admin-row-actions"', false);

        $this->actingAs($admin)
            ->get(route('admin.subscription-plans.archived'))
            ->assertOk()
            ->assertSee('Archived Plans', false);

        $this->actingAs($admin)
            ->get(route('admin.payments.index'))
            ->assertOk()
            ->assertSee('Amount', false)
            ->assertSee('#', false)
            ->assertSee('Actions', false)
            ->assertDontSee('Process Refund', false)
            ->assertDontSee('title="Refund"', false)
            ->assertDontSee('Add Internal Note', false)
            ->assertSee('data-testid="admin-table-filter-bar"', false)
            ->assertDontSee('data-testid="admin-row-actions"', false);

        $this->assertFalse(Route::has('admin.subscribers.extend-grace'));
        $this->assertFalse(Route::has('admin.subscribers.schedule-cancel'));
        $this->assertFalse(Route::has('admin.subscribers.reactivate'));
        $this->assertFalse(Route::has('admin.payments.refund'));
        $this->assertFalse(Route::has('admin.payments.internal-note'));
    }
}
