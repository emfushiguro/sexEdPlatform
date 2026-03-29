<?php

namespace Tests\Feature\Instructor;

use App\Models\Module;
use App\Models\User;
use Tests\TestCase;

class InstructorModuleConfigValidationTest extends TestCase
{
    public function test_paid_module_requires_price_amount(): void
    {
        $instructor = User::factory()->create(['role' => 'instructor']);
        $instructor->assignRole('instructor');

        $this->actingAs($instructor)
            ->post(route('instructor.modules.store'), [
                'title' => 'Paid module',
                'description' => 'desc',
                'age_bracket' => 'teens',
                'enrollment_mode' => 'auto',
                'access_type' => 'paid',
                'price_currency' => 'PHP',
            ])
            ->assertSessionHasErrors(['price_amount']);
    }

    public function test_free_module_stores_null_price_amount(): void
    {
        $instructor = User::factory()->create(['role' => 'instructor']);
        $instructor->assignRole('instructor');

        $this->actingAs($instructor)
            ->post(route('instructor.modules.store'), [
                'title' => 'Free module',
                'description' => 'desc',
                'age_bracket' => 'teens',
                'enrollment_mode' => 'auto',
                'access_type' => 'free',
                'price_amount' => 100,
                'price_currency' => 'PHP',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('modules', [
            'title' => 'Free module',
            'access_type' => 'free',
            'price_amount' => null,
        ]);
    }

    public function test_enrollment_limit_accepts_null_or_positive_integer(): void
    {
        $instructor = User::factory()->create(['role' => 'instructor']);
        $instructor->assignRole('instructor');

        $this->actingAs($instructor)
            ->post(route('instructor.modules.store'), [
                'title' => 'No cap module',
                'description' => 'desc',
                'age_bracket' => 'kids',
                'enrollment_mode' => 'manual',
                'access_type' => 'free',
                'enrollment_limit' => null,
            ])
            ->assertRedirect();

        $module = Module::where('title', 'No cap module')->firstOrFail();

        $this->actingAs($instructor)
            ->put(route('instructor.modules.update', $module), [
                'title' => 'No cap module',
                'description' => 'desc updated',
                'age_bracket' => 'kids',
                'enrollment_mode' => 'manual',
                'access_type' => 'free',
                'enrollment_limit' => 30,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('modules', [
            'id' => $module->id,
            'enrollment_limit' => 30,
        ]);
    }
}
