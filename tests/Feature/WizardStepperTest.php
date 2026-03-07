<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\View\Components\WizardStepper;

class WizardStepperTest extends TestCase
{
    use RefreshDatabase;

    public function test_learner_flow_detected_on_register_route(): void
    {
        $component = new WizardStepper('register', false);
        $steps = $component->steps;

        $this->assertCount(3, $steps);
        $this->assertTrue($steps[0]['isActive']);
        $this->assertFalse($steps[1]['isActive']);
        $this->assertFalse($steps[2]['isActive']);
    }

    public function test_learner_flow_step_2_active_on_verification_notice(): void
    {
        $component = new WizardStepper('verification.notice', false);
        $steps = $component->steps;

        $this->assertCount(3, $steps);
        $this->assertTrue($steps[0]['isCompleted']);
        $this->assertTrue($steps[1]['isActive']);
        $this->assertFalse($steps[2]['isActive']);
    }

    public function test_learner_flow_step_3_active_on_profile_complete(): void
    {
        $component = new WizardStepper('profile.complete', false);
        $steps = $component->steps;

        $this->assertCount(3, $steps);
        $this->assertTrue($steps[0]['isCompleted']);
        $this->assertTrue($steps[1]['isCompleted']);
        $this->assertTrue($steps[2]['isActive']);
    }

    public function test_parent_flow_detected_when_session_flag_is_set(): void
    {
        $component = new WizardStepper('verification.notice', true);
        $steps = $component->steps;

        $this->assertCount(5, $steps);
    }

    public function test_parent_flow_step_3_active_on_verification_notice(): void
    {
        $component = new WizardStepper('verification.notice', true);
        $steps = $component->steps;

        $this->assertCount(5, $steps);
        $this->assertTrue($steps[0]['isCompleted']);
        $this->assertTrue($steps[1]['isCompleted']);
        $this->assertTrue($steps[2]['isActive']);
        $this->assertFalse($steps[3]['isActive']);
        $this->assertFalse($steps[4]['isActive']);
    }

    public function test_parent_flow_step_5_active_on_create_child(): void
    {
        $component = new WizardStepper('parent.create-child', true);
        $steps = $component->steps;

        $this->assertTrue($steps[4]['isActive']);
        $this->assertTrue($steps[0]['isCompleted']);
        $this->assertTrue($steps[3]['isCompleted']);
    }

    public function test_unknown_route_returns_null_steps(): void
    {
        $component = new WizardStepper('some.unknown.route', false);

        $this->assertNull($component->steps);
    }

    public function test_verification_notice_shows_learner_flow_without_session_flag(): void
    {
        $component = new WizardStepper('verification.notice', false);
        $this->assertCount(3, $component->steps);
    }

    public function test_verification_notice_shows_parent_flow_with_session_flag(): void
    {
        $component = new WizardStepper('verification.notice', true);
        $this->assertCount(5, $component->steps);
    }

    public function test_profile_complete_shows_parent_flow_with_session_flag(): void
    {
        $component = new WizardStepper('profile.complete', true);
        $steps = $component->steps;
        $this->assertCount(5, $steps);
        $this->assertTrue($steps[3]['isActive']); // step 4 of 5
    }

    public function test_parent_required_page_shows_parent_flow_step_1_active(): void
    {
        // is_parent_registration is now set before the redirect to this page
        $component = new WizardStepper('parent.registration.required', true);
        $steps = $component->steps;

        $this->assertCount(5, $steps);
        $this->assertTrue($steps[0]['isActive']);
        $this->assertFalse($steps[1]['isActive']);
    }
}
