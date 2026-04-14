<?php

namespace Tests\Feature\Rbac;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RbacSuperAdminGateTest extends TestCase
{
    public function test_admin_role_user_passes_gate_before_even_for_non_mapped_ability(): void
    {
        Role::findOrCreate('admin', 'web');

        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);
        $admin->assignRole('admin');

        $this->assertTrue(Gate::forUser($admin)->allows('rbac.synthetic.ability'));
    }

    public function test_non_admin_user_does_not_pass_gate_for_non_mapped_ability(): void
    {
        Role::findOrCreate('learner', 'web');

        $learner = User::factory()->create([
            'role' => 'learner',
            'status' => 'active',
        ]);
        $learner->assignRole('learner');

        $this->assertFalse(Gate::forUser($learner)->allows('rbac.synthetic.ability'));
    }
}
