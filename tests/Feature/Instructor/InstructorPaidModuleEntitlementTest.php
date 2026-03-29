<?php

namespace Tests\Feature\Instructor;

use App\Models\User;
use App\Services\EntitlementService;
use Tests\TestCase;

class InstructorPaidModuleEntitlementTest extends TestCase
{
    public function test_instructor_without_entitlement_cannot_save_paid_module(): void
    {
        $instructor = User::factory()->create(['role' => 'instructor']);
        $instructor->assignRole('instructor');

        $this->app->instance(EntitlementService::class, new class extends EntitlementService {
            public function __construct() {}
            public function canAccessFeature(\App\Models\User $user, string $featureKey): bool
            {
                return false;
            }
        });

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
            ->assertSessionHasErrors(['access_type']);
    }

    public function test_entitled_instructor_can_save_paid_module(): void
    {
        $instructor = User::factory()->create(['role' => 'instructor']);
        $instructor->assignRole('instructor');

        $this->app->instance(EntitlementService::class, new class extends EntitlementService {
            public function __construct() {}
            public function canAccessFeature(\App\Models\User $user, string $featureKey): bool
            {
                return true;
            }
        });

        $this->actingAs($instructor)
            ->post(route('instructor.modules.store'), [
                'title' => 'Paid module allowed',
                'description' => 'desc',
                'age_bracket' => 'adults',
                'enrollment_mode' => 'manual',
                'access_type' => 'paid',
                'price_amount' => 49.99,
                'price_currency' => 'PHP',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('modules', [
            'title' => 'Paid module allowed',
            'access_type' => 'paid',
        ]);
    }

    public function test_admin_acting_as_instructor_can_save_paid_module_without_entitlement(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $admin->assignRole('admin');
        $admin->assignRole('instructor');

        $this->app->instance(EntitlementService::class, new class extends EntitlementService {
            public function __construct() {}
            public function canAccessFeature(\App\Models\User $user, string $featureKey): bool
            {
                return false;
            }
        });

        $this->actingAs($admin)
            ->post(route('instructor.modules.store'), [
                'title' => 'Admin paid module',
                'description' => 'desc',
                'age_bracket' => 'adults',
                'enrollment_mode' => 'manual',
                'access_type' => 'paid',
                'price_amount' => 59.99,
                'price_currency' => 'PHP',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('modules', [
            'title' => 'Admin paid module',
            'access_type' => 'paid',
        ]);
    }
}
