<?php

namespace Tests\Unit;

use App\Http\Controllers\Auth\GuardianOnboardingController;
use App\Http\Controllers\Auth\ParentRegistrationController;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Mockery;

class GuardianInvitationContinuationTest extends TestCase
{
    public function test_completed_onboarding_resumes_pending_invitation(): void
    {
        session(['minor_registration_token' => 'pendingcode']);
        $request = Request::create('/');
        $request->setUserResolver(fn () => $this->guardian());

        $response = app(GuardianOnboardingController::class)->show($request);

        $this->assertSame(route('minor.registration.accept', 'pendingcode'), $response->getTargetUrl());
    }

    public function test_child_form_resumes_invitation_before_clearing_its_session(): void
    {
        session(['minor_registration_request_id' => 12, 'minor_registration_token' => 'pendingcode']);
        Auth::shouldReceive('user')->andReturn($this->guardian());

        $response = app(ParentRegistrationController::class)->createChildForm();

        $this->assertInstanceOf(\Illuminate\Http\RedirectResponse::class, $response);
        $this->assertSame(route('minor.registration.accept', 'pendingcode'), $response->getTargetUrl());
        $this->assertSame(12, session('minor_registration_request_id'));
        $this->assertSame('pendingcode', session('minor_registration_token'));
    }

    public function test_completed_onboarding_without_invitation_keeps_dashboard_destination(): void
    {
        $request = Request::create('/');
        $request->setUserResolver(fn () => $this->guardian());

        $response = app(GuardianOnboardingController::class)->show($request);

        $this->assertSame(route('learner.dashboard'), $response->getTargetUrl());
    }

    private function guardian(): User
    {
        $guardian = Mockery::mock(User::class)->makePartial();
        foreach (['isParentRegistration', 'isParentVerificationApproved', 'hasCompletedGuardianOnboarding', 'hasVerifiedEmail', 'canBeParent'] as $method) {
            $guardian->shouldReceive($method)->andReturnTrue();
        }

        return $guardian;
    }
}
