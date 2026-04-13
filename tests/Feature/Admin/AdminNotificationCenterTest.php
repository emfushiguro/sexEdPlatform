<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminNotificationCenterTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_dashboard_uses_database_notification_unread_count_and_items(): void
    {
        /** @var User $admin */
        $admin = User::factory()->create(['role' => 'admin']);
        $admin->assignRole('admin');

        $admin->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => 'admin.update',
            'data' => [
                'title' => 'New report submitted',
                'message' => 'A learner report needs review.',
                'action_url' => route('admin.dashboard'),
            ],
            'created_at' => now()->subMinute(),
            'updated_at' => now()->subMinute(),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('New report submitted', false)
            ->assertSee('A learner report needs review.', false);
    }

    public function test_admin_can_mark_notifications_as_read_via_dropdown_and_mark_all_actions(): void
    {
        /** @var User $admin */
        $admin = User::factory()->create(['role' => 'admin']);
        $admin->assignRole('admin');

        $admin->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => 'admin.update',
            'data' => [
                'title' => 'Unread A',
                'message' => 'First unread notification.',
            ],
            'created_at' => now()->subMinutes(2),
            'updated_at' => now()->subMinutes(2),
        ]);

        $admin->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => 'admin.update',
            'data' => [
                'title' => 'Unread B',
                'message' => 'Second unread notification.',
            ],
            'created_at' => now()->subMinute(),
            'updated_at' => now()->subMinute(),
        ]);

        $this->actingAs($admin)
            ->postJson(route('admin.notifications.dropdown-open'))
            ->assertOk()
            ->assertJson([
                'ok' => true,
                'updated' => 2,
            ]);

        $this->assertSame(0, $admin->fresh()->unreadNotifications()->count());

        $admin->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => 'admin.update',
            'data' => [
                'title' => 'Unread C',
                'message' => 'Third unread notification.',
            ],
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($admin)
            ->from(route('admin.notifications.index'))
            ->post(route('admin.notifications.mark-all-read'))
            ->assertRedirect(route('admin.notifications.index'));

        $this->assertSame(0, $admin->fresh()->unreadNotifications()->count());
    }

    public function test_admin_operational_signal_metrics_are_still_rendered_in_layout_dropdown(): void
    {
        /** @var User $admin */
        $admin = User::factory()->create(['role' => 'admin']);
        $admin->assignRole('admin');

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Operational Signals', false)
            ->assertSee('Pending payments', false)
            ->assertSee('Subscriptions expiring soon', false)
            ->assertSee('Inactive plans', false);
    }
}
