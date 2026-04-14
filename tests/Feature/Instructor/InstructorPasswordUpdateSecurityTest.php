<?php

namespace Tests\Feature\Instructor;

use App\Models\User;
use Tests\TestCase;

class InstructorPasswordUpdateSecurityTest extends TestCase
{
    public function test_instructor_profile_edit_page_includes_password_update_form(): void
    {
        /** @var User $instructor */
        $instructor = User::factory()->create(['role' => 'instructor']);
        $instructor->assignRole('instructor');

        $this->actingAs($instructor)
            ->get(route('instructor.profile.edit'))
            ->assertOk()
            ->assertSee('Profile Information')
            ->assertSee('Professional Details')
            ->assertSee('Change Password')
            ->assertSee('Update Password')
            ->assertSee('name="current_password"', false)
            ->assertSee(route('profile.password.update'), false);
    }

    public function test_password_update_requires_current_password_from_instructor_context(): void
    {
        /** @var User $instructor */
        $instructor = User::factory()->create(['role' => 'instructor']);
        $instructor->assignRole('instructor');

        $this->actingAs($instructor)
            ->put(route('profile.password.update'), [
                'password' => 'NewPass#123',
                'password_confirmation' => 'NewPass#123',
            ])
            ->assertSessionHasErrors(['current_password']);
    }
}
