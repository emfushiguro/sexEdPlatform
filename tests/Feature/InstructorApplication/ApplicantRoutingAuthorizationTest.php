<?php

namespace Tests\Feature\InstructorApplication;

use App\Models\User;
use App\Support\AuthenticatedHomeRouteResolver;
use Tests\TestCase;

class ApplicantRoutingAuthorizationTest extends TestCase
{
    public function test_applicant_cannot_open_learner_instructor_or_admin_routes(): void
    {
        $applicant = User::factory()->create(['role' => 'applicant']);
        $applicant->assignRole('applicant');

        $this->actingAs($applicant)->get(route('learner.dashboard'))->assertForbidden();
        $this->actingAs($applicant)->get(route('instructor.dashboard'))->assertForbidden();
        $this->actingAs($applicant)->get(route('admin.dashboard'))->assertForbidden();
    }

    public function test_home_resolver_keeps_existing_role_destinations_and_adds_applicant_destination(): void
    {
        $resolver = app(AuthenticatedHomeRouteResolver::class);

        $this->assertSame('instructor-application.dashboard', $resolver->routeName(
            User::factory()->create(['role' => 'applicant']),
        ));
        $this->assertSame('instructor.dashboard', $resolver->routeName(
            User::factory()->create(['role' => 'instructor']),
        ));
        $this->assertSame('admin.dashboard', $resolver->routeName(
            User::factory()->create(['role' => 'admin']),
        ));
        $this->assertSame('learner.dashboard', $resolver->routeName(
            User::factory()->create(['role' => 'learner']),
        ));
    }

    public function test_applicant_login_verified_email_continuation_password_confirmation_and_root_use_applicant_hub(): void
    {
        $applicant = User::factory()->create([
            'role' => 'applicant',
            'password' => 'password',
            'email_verified_at' => now(),
        ]);
        $applicant->assignRole('applicant');

        $this->post(route('login'), [
            'email' => $applicant->email,
            'password' => 'password',
        ])->assertRedirect(route('instructor-application.dashboard'));

        $this->actingAs($applicant)->get(route('verification.notice'))
            ->assertRedirect(route('instructor-application.dashboard'));
        $this->actingAs($applicant)->post(route('verification.send'))
            ->assertRedirect(route('instructor-application.dashboard'));
        $this->actingAs($applicant)->post(route('password.confirm'), ['password' => 'password'])
            ->assertRedirect(route('instructor-application.dashboard'));
        $this->actingAs($applicant)->get('/')
            ->assertRedirect(route('instructor-application.dashboard'));
    }
}
