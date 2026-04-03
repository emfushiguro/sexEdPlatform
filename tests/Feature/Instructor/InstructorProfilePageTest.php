<?php

namespace Tests\Feature\Instructor;

use App\Models\User;
use Tests\TestCase;

class InstructorProfilePageTest extends TestCase
{
    public function test_instructor_can_open_profile_page_with_required_sections(): void
    {
        $instructor = User::factory()->create(['role' => 'instructor']);
        $instructor->assignRole('instructor');

        $this->actingAs($instructor)
            ->get(route('instructor.profile.show'))
            ->assertOk()
            ->assertSee($instructor->name)
            ->assertSee('Educational Background')
            ->assertSee('Professional Background')
            ->assertSee('Impact Overview');
    }

    public function test_learner_cannot_access_instructor_profile_route(): void
    {
        $learner = User::factory()->create(['role' => 'learner']);
        $learner->assignRole('learner');

        $this->actingAs($learner)
            ->get(route('instructor.profile.show'))
            ->assertForbidden();
    }
}
