<?php

namespace Tests\Feature\InstructorApplication;

use App\Models\User;
use Tests\TestCase;

class InstructorRecruitmentTest extends TestCase
{
    public function test_recruitment_page_keeps_start_disabled_when_direct_applications_are_off(): void
    {
        config(['instructor_application.enabled' => false]);

        $this->get(route('instructor-application.recruitment'))
            ->assertOk()
            ->assertSee('Be Our Partner')
            ->assertSee('Government ID plus Selfie')
            ->assertSee('Sandbox is simulated where applicable')
            ->assertSee('Start is currently unavailable')
            ->assertDontSee(route('instructor-application.register.personal'))
            ->assertDontSee('compensation')
            ->assertDontSee('employment');
    }

    public function test_recruitment_page_exposes_start_resume_and_sign_in_actions_when_enabled(): void
    {
        config(['instructor_application.enabled' => true]);

        $this->get(route('instructor-application.recruitment'))
            ->assertOk()
            ->assertSee(route('instructor-application.register.personal'))
            ->assertSee(route('login'))
            ->assertSee('Start your application')
            ->assertSee('Sign in to resume');
    }

    public function test_verified_learner_gets_application_hub_as_resume_destination(): void
    {
        config(['instructor_application.enabled' => true]);

        $learner = User::factory()->create([
            'role' => 'learner',
            'email_verified_at' => now(),
        ]);

        $this->actingAs($learner)->get(route('instructor-application.recruitment'))
            ->assertOk()
            ->assertSee(route('instructor-application.dashboard'));
    }
}
