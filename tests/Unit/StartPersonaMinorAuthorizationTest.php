<?php

namespace Tests\Unit;

use App\Http\Requests\Auth\StartPersonaInquiryRequest;
use App\Models\ParentChildAccount;
use App\Models\User;
use Mockery;
use PHPUnit\Framework\TestCase;

class StartPersonaMinorAuthorizationTest extends TestCase
{
    private ?\Illuminate\Container\Container $previousContainer = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->previousContainer = \Illuminate\Container\Container::getInstance();
        $container = new \Illuminate\Container\Container;
        $container->instance('config', new \Illuminate\Config\Repository(['app' => ['timezone' => 'Asia/Taipei']]));
        \Illuminate\Container\Container::setInstance($container);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        \Illuminate\Container\Container::setInstance($this->previousContainer);
        parent::tearDown();
    }

    public function test_activated_teenager_with_approved_guardian_does_not_need_personal_email(): void
    {
        $this->assertTrue($this->requestFor(15, true, true)->authorize());
    }

    public function test_missing_activation_or_guardian_does_not_allow_selfie_start(): void
    {
        $this->assertFalse($this->requestFor(15, false, true)->authorize());
        $this->assertFalse($this->requestFor(15, true, false)->authorize());
    }

    public function test_unverified_adult_still_needs_email_verification(): void
    {
        $this->assertFalse($this->requestFor(25, true, true)->authorize());
    }

    private function requestFor(int $age, bool $activated, bool $guardian): StartPersonaInquiryRequest
    {
        $user = Mockery::mock(User::class)->makePartial();
        $user->birthdate = now()->subYears($age);
        $user->shouldReceive('isLearner')->andReturn(true);
        $user->shouldReceive('isParentRegistration')->andReturn(false);
        $user->shouldReceive('hasVerifiedEmail')->andReturn(false);
        $user->shouldReceive('hasAcceptedDependentAdultAuthorization')->andReturn(false);
        $user->shouldReceive('hasCompletedMinorActivation')->andReturn($activated);
        $user->shouldReceive('qualifyingMinorGuardianRelationship')->andReturn($guardian ? new ParentChildAccount : null);
        $request = new StartPersonaInquiryRequest;
        $request->setUserResolver(fn () => $user);

        return $request;
    }
}
