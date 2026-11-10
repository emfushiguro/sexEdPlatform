<?php

namespace Tests\Feature\Auth;

use App\Models\ActivityLog;
use App\Models\User;
use App\Notifications\AdminLoginCodeNotification;
use App\Services\Auth\AdminMfaService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AdminMfaLifecycleTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_correct_code_is_consumed_once_before_authentication(): void
    {
        [$service, $request, $challenge, $code] = $this->issued();

        $user = $service->verify($request, $code);

        $this->assertSame($challenge->user_id, $user->id);
        $this->assertNotNull($challenge->fresh()->consumed_at);
        $this->assertFalse(Auth::check());

        $this->expectException(ValidationException::class);
        $service->verify($request, $code);
    }

    public function test_fifth_incorrect_code_locks_the_challenge_and_preserves_attempts(): void
    {
        [$service, $request, $challenge] = $this->issued();

        foreach (range(1, 5) as $attempt) {
            try {
                $service->verify($request, '999999');
                $this->fail('Expected the incorrect code to fail.');
            } catch (ValidationException $exception) {
                $this->assertArrayHasKey('code', $exception->errors());
            }

            $this->assertSame($attempt, $challenge->fresh()->attempt_count);
        }

        $this->assertNotNull($challenge->fresh()->locked_at);
        $this->assertSame(5, ActivityLog::query()
            ->where('activity_type', 'admin_mfa.verification_failed')
            ->count());
    }

    public function test_resend_obeys_cooldown_replaces_the_code_and_never_extends_login_expiry(): void
    {
        Carbon::setTestNow('2026-11-11 10:00:00');
        [$service, $request, $challenge, $firstCode, $admin] = $this->issued();
        $loginExpiry = $challenge->login_expires_at->copy();

        try {
            $service->resend($request);
            $this->fail('Expected resend cooldown to fail.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('resend', $exception->errors());
        }

        Carbon::setTestNow(now()->addSeconds(61));
        $resent = $service->resend($request)->fresh();
        $notifications = Notification::sent($admin, AdminLoginCodeNotification::class);
        $replacementCode = $notifications->last()->code;

        $this->assertFalse(Hash::check($firstCode, $resent->code_hash));
        $this->assertTrue(Hash::check($replacementCode, $resent->code_hash));
        $this->assertTrue($resent->login_expires_at->equalTo($loginExpiry));
        $this->assertSame(1, $resent->resend_count);
    }

    public function test_three_resends_are_allowed_and_the_fourth_is_rejected(): void
    {
        Carbon::setTestNow('2026-11-11 10:00:00');
        [$service, $request, $challenge] = $this->issued();

        foreach (range(1, 3) as $count) {
            Carbon::setTestNow(now()->addSeconds(61));
            $service->resend($request);
            $this->assertSame($count, $challenge->fresh()->resend_count);
        }

        Carbon::setTestNow(now()->addSeconds(61));
        $this->expectException(ValidationException::class);
        $service->resend($request);
    }

    public function test_expired_password_transaction_and_changed_credentials_fail_closed(): void
    {
        Carbon::setTestNow('2026-11-11 10:00:00');
        [$service, $request, $challenge, $code, $admin] = $this->issued();
        $challenge->forceFill(['login_expires_at' => now()->subSecond()])->save();

        try {
            $service->verify($request, $code);
            $this->fail('Expected the expired transaction to fail.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('code', $exception->errors());
        }

        [$service, $request, $challenge, $code, $admin] = $this->issued($admin);
        $admin->forceFill(['password' => Hash::make('new-password')])->save();

        $this->expectException(ValidationException::class);
        $service->verify($request, $code);
    }

    public function test_challenge_cannot_be_used_when_the_session_fingerprint_changes(): void
    {
        [$service, $request, $challenge, $code] = $this->issued();
        $challenge->forceFill([
            'session_fingerprint' => str_repeat('f', 64),
        ])->save();

        $this->expectException(ValidationException::class);
        $service->verify($request, $code);
    }

    public function test_cancellation_clears_pending_state_and_verified_session_data_is_user_bound(): void
    {
        [$service, $request, $challenge, $code, $admin] = $this->issued();

        $service->cancel($request);

        $this->assertNotNull($challenge->fresh()->cancelled_at);
        $this->assertNull($request->session()->get(AdminMfaService::PENDING_CHALLENGE_KEY));

        [$service, $request, $challenge, $code, $admin] = $this->issued($admin);
        $user = $service->verify($request, $code);
        $service->authenticate($request, $user);

        $this->assertAuthenticatedAs($admin);
        $this->assertTrue($service->sessionIsVerified($request));
        $this->assertSame($admin->id, $request->session()->get(AdminMfaService::VERIFIED_USER_KEY));
    }

    public function test_audit_metadata_never_contains_the_plaintext_code_or_hash(): void
    {
        [$service, $request, $challenge, $code] = $this->issued();
        $service->verify($request, $code);

        $payload = ActivityLog::query()
            ->where('user_id', $challenge->user_id)
            ->get()
            ->toJson();

        $this->assertStringNotContainsString($code, $payload);
        $this->assertStringNotContainsString($challenge->code_hash, $payload);
    }

    private function issued(?User $admin = null): array
    {
        Notification::fake();
        $admin ??= $this->admin();
        $request = $this->request();
        $service = app(AdminMfaService::class);
        $challenge = $service->beginFromAdminPortal($request, $admin->email, 'password');
        $notification = Notification::sent($admin, AdminLoginCodeNotification::class)->last();

        return [$service, $request, $challenge, $notification->code, $admin];
    }

    private function admin(): User
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => User::STATUS_ACTIVE,
        ]);
        $admin->assignRole('admin');

        return $admin;
    }

    private function request(string $ip = '127.0.0.1'): Request
    {
        $request = Request::create('/secure-panel-access/verify', 'POST', [], [], [], [
            'REMOTE_ADDR' => $ip,
        ]);
        $request->setLaravelSession(app('session')->driver());

        return $request;
    }
}
