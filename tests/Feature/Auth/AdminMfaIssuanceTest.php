<?php

namespace Tests\Feature\Auth;

use App\Models\ActivityLog;
use App\Models\User;
use App\Models\UserSuspension;
use App\Notifications\AdminLoginCodeNotification;
use App\Services\Auth\AdminMfaService;
use Illuminate\Contracts\Notifications\Dispatcher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Tests\TestCase;

class AdminMfaIssuanceTest extends TestCase
{
    public function test_admin_credentials_create_a_hashed_session_bound_challenge_and_send_mail(): void
    {
        Notification::fake();
        $admin = $this->admin();
        $request = $this->request();

        $challenge = app(AdminMfaService::class)->beginFromAdminPortal(
            $request,
            $admin->email,
            'password',
        );

        $this->assertFalse(Auth::check());
        $this->assertSame($challenge->id, $request->session()->get(AdminMfaService::PENDING_CHALLENGE_KEY));
        $this->assertNull($challenge->consumed_at);
        $this->assertNotSame('000000', $challenge->code_hash);

        Notification::assertSentTo(
            $admin,
            AdminLoginCodeNotification::class,
            function (AdminLoginCodeNotification $notification) use ($challenge): bool {
                $this->assertMatchesRegularExpression('/^\d{6}$/', $notification->code);
                $this->assertTrue(Hash::check($notification->code, $challenge->fresh()->code_hash));

                return true;
            },
        );

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $admin->id,
            'activity_type' => 'admin_mfa.challenge_created',
        ]);
        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $admin->id,
            'activity_type' => 'admin_mfa.email_sent',
        ]);
    }

    public function test_general_login_returns_null_for_a_non_admin_without_changing_its_authentication(): void
    {
        $learner = User::factory()->create(['status' => User::STATUS_ACTIVE]);

        $challenge = app(AdminMfaService::class)->beginIfAdmin(
            $this->request('/login'),
            $learner->email,
            'password',
        );

        $this->assertNull($challenge);
        $this->assertDatabaseCount('admin_mfa_challenges', 0);
    }

    public function test_challenge_start_limits_use_keyed_account_and_ip_digests(): void
    {
        Notification::fake();
        $admin = $this->admin();
        $service = app(AdminMfaService::class);

        foreach (range(1, 5) as $attempt) {
            $service->beginFromAdminPortal(
                $this->request('/secure-panel-access', '198.51.100.'.$attempt),
                $admin->email,
                'password',
            );
        }

        try {
            $service->beginFromAdminPortal(
                $this->request('/secure-panel-access', '198.51.100.6'),
                $admin->email,
                'password',
            );
            $this->fail('Expected the per-account challenge-start limit to fail.');
        } catch (ValidationException $exception) {
            $this->assertSame(
                ['Too many verification requests. Please wait before trying again.'],
                $exception->errors()['email'],
            );
        }

        $appKey = (string) config('app.key');
        $accountKey = 'admin-mfa-start:user:'.hash_hmac('sha256', (string) $admin->id, $appKey);
        $ipKey = 'admin-mfa-start:ip:'.hash_hmac('sha256', '198.51.100.1', $appKey);

        $this->assertSame(5, RateLimiter::attempts($accountKey));
        $this->assertSame(1, RateLimiter::attempts($ipKey));
        $this->assertSame(0, RateLimiter::attempts('admin-mfa-start:user:'.$admin->id));
        $this->assertSame(0, RateLimiter::attempts('admin-mfa-start:ip:198.51.100.1'));
    }

    public function test_password_limit_uses_a_keyed_ip_digest_and_releases_after_a_limited_request(): void
    {
        Notification::fake();
        $admin = $this->admin();
        $service = app(AdminMfaService::class);
        $ip = '203.0.113.44';
        $passwordKey = 'admin-login:'.hash_hmac('sha256', $ip, (string) config('app.key'));

        foreach (range(1, 5) as $attempt) {
            try {
                $service->beginFromAdminPortal(
                    $this->request('/secure-panel-access', $ip),
                    $admin->email,
                    'wrong-password',
                );
            } catch (ValidationException) {
                // Expected invalid-credential response.
            }
        }

        $this->assertSame(5, RateLimiter::attempts($passwordKey));
        $this->assertSame(0, RateLimiter::attempts('admin-login:'.$ip));

        try {
            $service->beginFromAdminPortal(
                $this->request('/secure-panel-access', $ip),
                $admin->email,
                'password',
            );
            $this->fail('Expected the password limit to fail.');
        } catch (ValidationException $exception) {
            $this->assertSame(
                ['Too many login attempts. Please try again in 60 seconds.'],
                $exception->errors()['email'],
            );
        }

        RateLimiter::clear($passwordKey);

        $challenge = $service->beginFromAdminPortal(
            $this->request('/secure-panel-access', $ip),
            $admin->email,
            'password',
        );

        $this->assertSame($admin->id, $challenge->user_id);
    }

    public function test_challenge_start_limit_is_enforced_per_account_across_ips(): void
    {
        Notification::fake();
        $admin = $this->admin();
        $service = app(AdminMfaService::class);

        foreach (range(1, 5) as $attempt) {
            $service->beginFromAdminPortal(
                $this->request('/secure-panel-access', '192.0.2.'.$attempt),
                $admin->email,
                'password',
            );
        }

        $this->expectException(ValidationException::class);
        $service->beginFromAdminPortal(
            $this->request('/secure-panel-access', '192.0.2.99'),
            $admin->email,
            'password',
        );
    }

    public function test_challenge_start_limit_is_enforced_per_ip_across_accounts(): void
    {
        Notification::fake();
        $service = app(AdminMfaService::class);

        foreach (range(1, 5) as $attempt) {
            $admin = $this->admin(['email' => "admin{$attempt}@example.com"]);
            $service->beginFromAdminPortal(
                $this->request('/secure-panel-access', '198.51.100.20'),
                $admin->email,
                'password',
            );
        }

        $blocked = $this->admin(['email' => 'blocked-admin@example.com']);
        $this->expectException(ValidationException::class);
        $service->beginFromAdminPortal(
            $this->request('/secure-panel-access', '198.51.100.20'),
            $blocked->email,
            'password',
        );
    }

    public function test_invalid_or_ineligible_admin_credentials_fail_with_the_same_public_message(): void
    {
        $unverified = $this->admin(['email_verified_at' => null]);
        $inactive = $this->admin(['status' => User::STATUS_INACTIVE]);
        $suspended = $this->admin();
        UserSuspension::query()->create([
            'user_id' => $suspended->id,
            'status' => 'active',
            'starts_at' => now()->subMinute(),
            'ends_at' => now()->addHour(),
        ]);

        foreach ([
            [$unverified, 'password'],
            [$inactive, 'password'],
            [$suspended, 'password'],
            [$this->admin(), 'wrong-password'],
        ] as $index => [$admin, $password]) {
            try {
                app(AdminMfaService::class)->beginFromAdminPortal(
                    $this->request('/secure-panel-access', '10.0.0.'.($index + 10)),
                    $admin->email,
                    $password,
                );
                $this->fail('Expected credential validation to fail.');
            } catch (ValidationException $exception) {
                $this->assertSame(
                    ['The provided credentials do not match our records.'],
                    $exception->errors()['email'],
                );
            }
        }

        $this->assertDatabaseCount('admin_mfa_challenges', 0);
    }

    public function test_delivery_failure_is_recorded_without_authenticating_or_logging_the_code(): void
    {
        $this->mock(Dispatcher::class)
            ->shouldReceive('sendNow')
            ->once()
            ->andThrow(new RuntimeException('SMTP unavailable'));

        $admin = $this->admin();
        $challenge = app(AdminMfaService::class)->beginFromAdminPortal(
            $this->request(),
            $admin->email,
            'password',
        )->fresh();

        $this->assertNull($challenge->delivered_at);
        $this->assertNotNull($challenge->delivery_failed_at);
        $this->assertFalse(Auth::check());

        $log = ActivityLog::query()
            ->where('activity_type', 'admin_mfa.email_failed')
            ->latest('id')
            ->firstOrFail();

        $this->assertSame($admin->id, $log->user_id);
        $this->assertStringNotContainsString($challenge->code_hash, json_encode($log->metadata));
    }

    public function test_notification_renders_the_current_email_visual_language_without_an_otp_link(): void
    {
        $admin = $this->admin();
        $html = (new AdminLoginCodeNotification('123456'))->toMail($admin)->render();

        $this->assertStringContainsString('Admin Login Verification', $html);
        $this->assertStringContainsString('123456', $html);
        $this->assertStringContainsString('#A30EB2', $html);
        $this->assertStringContainsString('#730DB1', $html);
        $this->assertStringContainsString('#3B0CB1', $html);
        $this->assertStringContainsString('Never share this code', $html);
        $this->assertStringContainsString('Copy code', $html);
        $this->assertSame(6, substr_count($html, 'class="code-digit"'));
        $this->assertStringNotContainsString('<script', strtolower($html));
        $this->assertStringNotContainsString('123456?', $html);
        $this->assertStringNotContainsString('/verify?code=123456', $html);
    }

    private function admin(array $attributes = []): User
    {
        $admin = User::factory()->create(array_replace([
            'role' => 'admin',
            'status' => User::STATUS_ACTIVE,
        ], $attributes));
        $admin->assignRole('admin');

        return $admin;
    }

    private function request(string $path = '/secure-panel-access', string $ip = '127.0.0.1'): Request
    {
        $request = Request::create($path, 'POST', [], [], [], ['REMOTE_ADDR' => $ip]);
        $request->setLaravelSession(app('session')->driver());

        return $request;
    }
}
