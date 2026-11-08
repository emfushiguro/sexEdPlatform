<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class VerifiedAccessGateTest extends TestCase
{
    use RefreshDatabase;

    public function test_unverified_admin_is_redirected_to_email_verification(): void
    {
        $role = Role::findOrCreate('admin', 'web');
        $admin = User::factory()->unverified()->create(['role' => 'admin']);
        $admin->assignRole($role);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertRedirect(route('verification.notice'));
    }

    public function test_unverified_instructor_is_redirected_to_email_verification(): void
    {
        $permission = Permission::findOrCreate('access instructor panel', 'web');
        $instructor = User::factory()->unverified()->create(['role' => 'instructor']);
        $instructor->givePermissionTo($permission);

        $this->actingAs($instructor)
            ->get(route('instructor.dashboard'))
            ->assertRedirect(route('verification.notice'));
    }
}
