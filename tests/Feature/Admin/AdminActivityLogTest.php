<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminActivityLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_mutation_creates_activity_log_record(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);
        $admin->assignRole('admin');

        $this->actingAs($admin)->post(route('admin.users.store'), [
            'name' => 'Audit Target',
            'email' => 'audit-target@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'learner',
            'status' => 'active',
        ])->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseHas('admin_activity_logs', [
            'admin_user_id' => $admin->id,
            'action' => 'users.create',
            'entity_type' => User::class,
        ]);
    }
}
