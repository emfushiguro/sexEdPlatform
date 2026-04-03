<?php

namespace Tests\Feature\Instructor;

use App\Models\User;
use Tests\TestCase;

class InstructorPaidModuleEntitlementTest extends TestCase
{
    public function test_instructor_can_save_paid_module_without_entitlement_during_rollout(): void
    {
        /** @var User $instructor */
        $instructor = User::factory()->create(['role' => 'instructor']);
        $instructor->assignRole('instructor');

        $this->actingAs($instructor)
            ->post(route('instructor.modules.store'), [
                'title' => 'Paid module',
                'description' => 'desc',
                'age_bracket' => 'adults',
                'enrollment_mode' => 'manual',
                'access_type' => 'paid',
                'price_amount' => 99.99,
                'price_currency' => 'PHP',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('modules', [
            'title' => 'Paid module',
            'access_type' => 'paid',
        ]);
    }

    public function test_admin_acting_as_instructor_can_save_paid_module(): void
    {
        /** @var User $admin */
        $admin = User::factory()->create(['role' => 'admin']);
        $admin->assignRole('admin');
        $admin->assignRole('instructor');

        $this->actingAs($admin)
            ->post(route('instructor.modules.store'), [
                'title' => 'Admin paid module',
                'description' => 'desc',
                'age_bracket' => 'adults',
                'enrollment_mode' => 'manual',
                'access_type' => 'paid',
                'price_amount' => 49.99,
                'price_currency' => 'PHP',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('modules', [
                'title' => 'Admin paid module',
            'access_type' => 'paid',
        ]);
    }
}
