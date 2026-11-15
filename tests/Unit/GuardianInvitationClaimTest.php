<?php

namespace Tests\Unit;

use App\Http\Controllers\Auth\MinorRegistrationRequestController;
use App\Models\MinorRegistrationRequest;
use App\Models\User;
use App\Services\Auth\MinorAccountHandoffService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Mockery;

class GuardianInvitationClaimTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        Schema::create('minor_registration_requests', function (Blueprint $table): void {
            $table->id();
            $table->text('guardian_email');
            $table->string('guardian_email_fingerprint');
            $table->string('token_hash');
            $table->string('status');
            $table->timestamp('expires_at');
            $table->unsignedBigInteger('accepted_by_user_id')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamps();
        });
    }

    public function test_another_verified_email_can_claim_once_without_changing_delivery_address(): void
    {
        $handoffs = app(MinorAccountHandoffService::class);
        $invitation = MinorRegistrationRequest::create([
            'guardian_email' => 'invited@example.test', 'guardian_email_fingerprint' => 'original',
            'token_hash' => $handoffs->digestForStorage('testcode'), 'status' => 'pending', 'expires_at' => now()->addHour(),
        ]);
        $controller = app(MinorRegistrationRequestController::class);
        $controller->claim($this->claimRequest(12), 'testcode', $handoffs);
        $this->assertSame(12, $invitation->fresh()->accepted_by_user_id);
        $this->assertNotNull($invitation->fresh()->accepted_at);
        $this->assertSame('invited@example.test', $invitation->fresh()->guardian_email);
        $controller->claim($this->claimRequest(12), 'testcode', $handoffs);
        $this->expectException(ValidationException::class);
        $controller->claim($this->claimRequest(13), 'testcode', $handoffs);
    }

    public function test_expired_request_is_never_claimed(): void
    {
        $handoffs = app(MinorAccountHandoffService::class);
        $invitation = MinorRegistrationRequest::create([
            'guardian_email' => 'invited@example.test', 'guardian_email_fingerprint' => 'original',
            'token_hash' => $handoffs->digestForStorage('expiredcode'), 'status' => 'pending', 'expires_at' => now()->subMinute(),
        ]);
        try {
            app(MinorRegistrationRequestController::class)->claim($this->claimRequest(12), 'expiredcode', $handoffs);
            $this->fail('Expired invitation was accepted.');
        } catch (ValidationException) {
            $this->assertNull($invitation->fresh()->accepted_by_user_id);
        }
    }

    public function test_claim_route_requires_authentication_verification_and_throttling(): void
    {
        $route = app('router')->getRoutes()->getByName('minor.registration.claim');
        foreach (['auth', 'verified', 'throttle:5,1'] as $middleware) {
            $this->assertContains($middleware, $route->gatherMiddleware());
        }
        $this->assertSame(['POST'], $route->methods());
    }

    public function test_claim_requires_explicit_confirmation(): void
    {
        $request = $this->claimRequest(12);
        $request->replace([]);
        $this->expectException(ValidationException::class);
        app(MinorRegistrationRequestController::class)->claim($request, 'testcode', app(MinorAccountHandoffService::class));
    }

    public function test_invalid_invitation_cannot_be_claimed(): void
    {
        $this->expectException(ValidationException::class);
        app(MinorRegistrationRequestController::class)->claim($this->claimRequest(12), 'missingcode', app(MinorAccountHandoffService::class));
    }

    public function test_invalid_accept_clears_only_its_matching_session_marker(): void
    {
        $controller = app(MinorRegistrationRequestController::class);
        $handoffs = app(MinorAccountHandoffService::class);
        session(['minor_registration_token' => 'activecode', 'minor_registration_request_id' => 12, 'pending_child_registration' => ['first_name' => 'Dependent']]);

        $controller->accept(Request::create('/'), 'othercode', $handoffs);
        $this->assertSame('activecode', session('minor_registration_token'));

        $controller->accept(Request::create('/'), 'activecode', $handoffs);
        foreach (['minor_registration_token', 'minor_registration_request_id', 'pending_child_registration'] as $key) {
            $this->assertFalse(session()->has($key));
        }
    }

    public function test_competing_claim_clears_its_unusable_session_marker(): void
    {
        $handoffs = app(MinorAccountHandoffService::class);
        MinorRegistrationRequest::create([
            'guardian_email' => 'invited@example.test', 'guardian_email_fingerprint' => 'original',
            'token_hash' => $handoffs->digestForStorage('claimedcode'), 'status' => 'pending',
            'expires_at' => now()->addHour(), 'accepted_by_user_id' => 13,
        ]);
        session(['minor_registration_token' => 'claimedcode', 'minor_registration_request_id' => 12]);

        try {
            app(MinorRegistrationRequestController::class)->claim($this->claimRequest(12), 'claimedcode', $handoffs);
            $this->fail('Another guardian already claimed this invitation.');
        } catch (ValidationException) {
            $this->assertFalse(session()->has('minor_registration_token'));
            $this->assertFalse(session()->has('minor_registration_request_id'));
        }
    }

    private function claimRequest(int $id): Request
    {
        $guardian = Mockery::mock(User::class)->makePartial();
        $guardian->forceFill(['id' => $id, 'email' => 'other@example.test', 'status' => User::STATUS_ACTIVE]);
        foreach (['hasVerifiedEmail', 'isParentRegistration', 'isParentVerificationApproved', 'hasCompletedGuardianOnboarding'] as $method) {
            $guardian->shouldReceive($method)->andReturnTrue();
        }
        $request = Request::create('/', 'POST', ['confirm_claim' => '1']);
        $request->setUserResolver(fn () => $guardian);
        return $request;
    }
}
