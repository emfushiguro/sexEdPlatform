# Admin-Only Email MFA Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Require every Admin account to complete a secure email OTP challenge after password validation and before any authenticated Admin session or protected Admin access is established.

**Architecture:** Add one database-backed `AdminMfaChallenge` aggregate and one `AdminMfaService` that owns credential validation, challenge issuance, synchronous delivery, atomic verification, resend, cancellation, session marking, and logout audit. Both existing login entry points call the service before Laravel authenticates an Admin; central `admin.mfa` middleware then enforces the verified session marker on the existing Admin route group. A dedicated signed email-verification route prevents Admin email-change lockout without authenticating the visitor.

**Tech Stack:** PHP 8.2, Laravel 12, Eloquent/MySQL, Laravel Notifications and Mail, Spatie Permission, Blade, Alpine.js, PHPUnit 11 with the isolated MySQL `cc_db_test` database.

**Approved specification:** `docs/superpowers/specs/2026-11-10-admin-email-mfa-design.md`

## Global Constraints

- Never reset, wipe, truncate, recreate, or destructively reseed the development database. Do not run `migrate:fresh`, `db:wipe`, `TRUNCATE`, `DROP TABLE`, or an unscoped `DELETE` against development data.
- PHPUnit is allowed to use `RefreshDatabase` only through `phpunit.xml`, which fixes `APP_ENV=testing` and `DB_DATABASE=cc_db_test`. Confirm both values before the first test command.
- Do not run `composer setup`; it invokes `php artisan migrate --force` against the configured database.
- Use one forward-only additive migration. Its `down()` method must not drop the challenge table or delete authentication history.
- The `admin` role is the MFA and Admin-route boundary. A standalone `access admin panel` permission does not make an account an Admin.
- Every email-based login path that receives valid Admin credentials must start MFA. Username-based non-Admin login remains unchanged.
- Do not create an authenticated Admin session before OTP verification succeeds and the challenge is consumed.
- Generate exactly six numeric digits with `random_int()`. Store only a Laravel hash; never persist or log plaintext.
- A code lasts five minutes. The password-authenticated transaction lasts 15 minutes and is not extended by resends.
- Permit five incorrect codes, a 60-second resend cooldown, three resends per challenge, five challenge starts per 15 minutes per account and IP, and the existing five password attempts per minute per IP.
- Bind pending challenges to a keyed digest of the server session ID and to a keyed credential digest of user ID, password hash, and normalized email.
- Send synchronously through `mail.verification_mailer`. Do not add a queue, package, provider, or environment variable.
- A delivery failure must leave the code unusable and the Admin unauthenticated; recovery is resend when allowed or cancellation.
- Public password-stage errors remain generic. Detailed incorrect/expired/used feedback is allowed only after a valid pending challenge exists.
- Protect the central Admin web route group with `admin.mfa`. There are currently no Admin routes in `routes/api.php`; do not invent one. Test the middleware's JSON failure using a temporary test route.
- Existing Admin sessions without an MFA marker must be forced through login. Non-Admin sessions remain valid.
- Preserve existing email verification for non-Admins. The new Admin signed link may verify email ownership only; it must never authenticate the visitor.
- Reuse `<x-auth-split-layout>` and the current email colors `#A30EB2`, `#730DB1`, and `#3B0CB1`. Do not refactor unrelated auth or email templates.
- Keep OTP copy as selectable email text. Never put the OTP in a URL, browser storage, log, exception message, or queued payload.
- Do not add trusted devices, backup codes, TOTP, SMS, WebAuthn, bypass switches, or unrelated role/authentication refactors.
- Preserve unrelated worktree changes. Stage only paths owned by the current task; never use `git add .` or `git add -A`.
- Separate source, PHPUnit, formatting, browser, SMTP/sandbox, deployment, and production evidence in the final report. Never claim an unrun gate passed.

---

## Planned File Map

### Persistence and lifecycle

- Create `database/migrations/2026_11_11_000001_create_admin_mfa_challenges_table.php` — forward-only challenge schema.
- Create `app/Models/AdminMfaChallenge.php` — UUID challenge model, casts, relationship, and 24-hour pruning query.
- Modify `routes/console.php` — schedule Laravel model pruning once daily.
- Create `app/Services/Auth/AdminMfaService.php` — the only OTP lifecycle and session-state service.
- Create `app/Notifications/AdminLoginCodeNotification.php` — synchronous mail notification carrying the in-memory code.

### HTTP and presentation

- Create `app/Http/Controllers/Auth/AdminMfaController.php` — show, verify, resend, and cancel actions.
- Create `app/Http/Requests/Auth/VerifyAdminMfaRequest.php` — combine and validate six submitted digits.
- Create `app/Http/Middleware/EnsureAdminMfaVerified.php` — Admin session-marker enforcement for HTML and JSON.
- Create `app/Http/Controllers/Auth/AdminEmailVerificationController.php` — signed, guest-safe Admin email verification without login.
- Modify `app/Http/Controllers/Auth/AdminAuthController.php` — start MFA instead of authenticating, and use shared logout.
- Modify `app/Http/Controllers/Auth/AuthenticatedSessionController.php` — intercept email-based Admin candidates before normal authentication and audit Admin logout.
- Modify `app/Notifications/CustomVerifyEmail.php` — use the Admin-only signed URL for Admin recipients.
- Modify `bootstrap/app.php` — register the `admin.mfa` alias.
- Modify `routes/auth.php` — add MFA and Admin email-verification routes.
- Modify `routes/admin.php` — add central MFA middleware.
- Create `resources/views/auth/admin-mfa.blade.php` — accessible six-digit prompt, countdowns, resend, and cancellation.
- Create `resources/views/emails/admin-mfa-code.blade.php` — branded, selectable-code email.

### Test compatibility and verification

- Create `tests/Concerns/InteractsWithAdminMfa.php` — treat ordinary `actingAs($admin)` calls as fully MFA-authenticated while exposing an explicit pre-MFA helper.
- Modify `tests/TestCase.php` and `tests/DatabaseTestCase.php` — use the shared test concern so existing Admin feature tests retain their intent.
- Create `tests/Feature/Auth/AdminMfaChallengeTest.php`.
- Create `tests/Feature/Auth/AdminMfaIssuanceTest.php`.
- Create `tests/Feature/Auth/AdminMfaLifecycleTest.php`.
- Create `tests/Feature/Auth/AdminMfaAuthenticationTest.php`.
- Create `tests/Feature/Auth/AdminMfaMiddlewareTest.php`.
- Create `tests/Feature/Auth/AdminEmailVerificationTest.php`.
- Test `tests/Feature/Auth/AdminLoginPageUiTest.php` — preserve the existing Admin login UI contract.

---

### Task 1: Add the forward-only challenge domain and pruning schedule

**Files:**
- Create: `database/migrations/2026_11_11_000001_create_admin_mfa_challenges_table.php`
- Create: `app/Models/AdminMfaChallenge.php`
- Modify: `routes/console.php`
- Create: `tests/Feature/Auth/AdminMfaChallengeTest.php`

**Interfaces:**
- Produces: table `admin_mfa_challenges` keyed by UUID and related to `users.id`.
- Produces: `AdminMfaChallenge::user(): BelongsTo`.
- Produces: `AdminMfaChallenge::prunable(): Builder`, selecting terminal or expired rows older than 24 hours.
- Produces: daily `model:prune` scheduler registration.

- [ ] **Step 1: Confirm the isolated test database contract**

Run:

~~~powershell
Select-String -Path phpunit.xml -Pattern 'APP_ENV.*testing','DB_DATABASE.*cc_db_test'
~~~

Expected: one match for `APP_ENV=testing` and one for `DB_DATABASE=cc_db_test`. Stop if either value differs.

- [ ] **Step 2: Write the failing schema, cast, UUID, and pruning tests**

Create `tests/Feature/Auth/AdminMfaChallengeTest.php`:

~~~php
<?php

namespace Tests\Feature\Auth;

use App\Models\AdminMfaChallenge;
use App\Models\User;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdminMfaChallengeTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_challenge_schema_uses_uuid_and_datetime_casts(): void
    {
        $this->assertTrue(Schema::hasColumns('admin_mfa_challenges', [
            'id',
            'user_id',
            'code_hash',
            'session_fingerprint',
            'credential_fingerprint',
            'attempt_count',
            'resend_count',
            'code_expires_at',
            'login_expires_at',
            'resend_available_at',
            'delivered_at',
            'delivery_failed_at',
            'consumed_at',
            'cancelled_at',
            'locked_at',
        ]));

        $challenge = AdminMfaChallenge::query()->create($this->attributes(User::factory()->create()));

        $this->assertIsString($challenge->id);
        $this->assertSame(36, strlen($challenge->id));
        $this->assertTrue($challenge->code_expires_at->isFuture());
        $this->assertTrue($challenge->user->is($challenge->user()->firstOrFail()));
    }

    public function test_prunable_query_keeps_terminal_rows_for_twenty_four_hours(): void
    {
        Carbon::setTestNow('2026-11-11 10:00:00');
        $user = User::factory()->create();

        $old = AdminMfaChallenge::query()->create($this->attributes($user, [
            'login_expires_at' => now()->subHours(25),
            'consumed_at' => now()->subHours(25),
        ]));
        $recent = AdminMfaChallenge::query()->create($this->attributes($user, [
            'login_expires_at' => now()->subHours(23),
            'consumed_at' => now()->subHours(23),
        ]));
        $active = AdminMfaChallenge::query()->create($this->attributes($user));

        $ids = (new AdminMfaChallenge())->prunable()->pluck('id')->all();

        $this->assertContains($old->id, $ids);
        $this->assertNotContains($recent->id, $ids);
        $this->assertNotContains($active->id, $ids);
    }

    public function test_model_pruning_is_scheduled(): void
    {
        $commands = collect(app(Schedule::class)->events())
            ->map(fn ($event): string => (string) ($event->command ?? ''));

        $this->assertTrue(
            $commands->contains(fn (string $command): bool => str_contains($command, 'model:prune')),
            'Expected model:prune to be registered in scheduler.',
        );
    }

    private function attributes(User $user, array $overrides = []): array
    {
        return array_replace([
            'user_id' => $user->id,
            'code_hash' => 'hashed-code',
            'session_fingerprint' => str_repeat('a', 64),
            'credential_fingerprint' => str_repeat('b', 64),
            'attempt_count' => 0,
            'resend_count' => 0,
            'code_expires_at' => now()->addMinutes(5),
            'login_expires_at' => now()->addMinutes(15),
            'resend_available_at' => now()->addMinute(),
            'delivered_at' => now(),
        ], $overrides);
    }
}
~~~

- [ ] **Step 3: Run the domain test to verify it fails**

Run:

~~~powershell
php artisan test --compact tests/Feature/Auth/AdminMfaChallengeTest.php
~~~

Expected: FAIL because the table and `AdminMfaChallenge` model do not exist.

- [ ] **Step 4: Add the additive migration**

Create `database/migrations/2026_11_11_000001_create_admin_mfa_challenges_table.php`:

~~~php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_mfa_challenges', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('code_hash');
            $table->string('session_fingerprint', 64);
            $table->string('credential_fingerprint', 64);
            $table->unsignedTinyInteger('attempt_count')->default(0);
            $table->unsignedTinyInteger('resend_count')->default(0);
            $table->timestamp('code_expires_at');
            $table->timestamp('login_expires_at');
            $table->timestamp('resend_available_at');
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('delivery_failed_at')->nullable();
            $table->timestamp('consumed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('locked_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at'], 'admin_mfa_user_created_index');
            $table->index('login_expires_at', 'admin_mfa_login_expiry_index');
            $table->index('consumed_at', 'admin_mfa_consumed_index');
            $table->index('cancelled_at', 'admin_mfa_cancelled_index');
            $table->index('locked_at', 'admin_mfa_locked_index');
        });
    }

    public function down(): void
    {
        // Forward-only: retain authentication challenge history.
    }
};
~~~

- [ ] **Step 5: Add the model and pruning boundary**

Create `app/Models/AdminMfaChallenge.php`:

~~~php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminMfaChallenge extends Model
{
    use HasUuids, MassPrunable;

    protected $fillable = [
        'user_id',
        'code_hash',
        'session_fingerprint',
        'credential_fingerprint',
        'attempt_count',
        'resend_count',
        'code_expires_at',
        'login_expires_at',
        'resend_available_at',
        'delivered_at',
        'delivery_failed_at',
        'consumed_at',
        'cancelled_at',
        'locked_at',
    ];

    protected function casts(): array
    {
        return [
            'attempt_count' => 'integer',
            'resend_count' => 'integer',
            'code_expires_at' => 'datetime',
            'login_expires_at' => 'datetime',
            'resend_available_at' => 'datetime',
            'delivered_at' => 'datetime',
            'delivery_failed_at' => 'datetime',
            'consumed_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'locked_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function prunable(): Builder
    {
        $cutoff = now()->subDay();

        return static::query()->where(function (Builder $query) use ($cutoff): void {
            $query->where('login_expires_at', '<=', $cutoff)
                ->orWhere('consumed_at', '<=', $cutoff)
                ->orWhere('cancelled_at', '<=', $cutoff)
                ->orWhere('locked_at', '<=', $cutoff);
        });
    }
}
~~~

- [ ] **Step 6: Register daily pruning**

Append to `routes/console.php`:

~~~php
Schedule::command('model:prune')
    ->dailyAt('02:00')
    ->withoutOverlapping();
~~~

- [ ] **Step 7: Run the domain test and inspect the migration without touching development data**

Run:

~~~powershell
php artisan test --compact tests/Feature/Auth/AdminMfaChallengeTest.php
php artisan migrate --pretend --path=database/migrations/2026_11_11_000001_create_admin_mfa_challenges_table.php
~~~

Expected: the test passes; `migrate --pretend` prints one `CREATE TABLE admin_mfa_challenges` statement and does not apply it.

- [ ] **Step 8: Commit the domain task**

~~~powershell
git add -- database/migrations/2026_11_11_000001_create_admin_mfa_challenges_table.php app/Models/AdminMfaChallenge.php routes/console.php tests/Feature/Auth/AdminMfaChallengeTest.php
git commit -m "feat(auth): add admin MFA challenge domain"
~~~

---

### Task 2: Issue session-bound challenges and send the branded email

**Files:**
- Create: `app/Services/Auth/AdminMfaService.php`
- Create: `app/Notifications/AdminLoginCodeNotification.php`
- Create: `resources/views/emails/admin-mfa-code.blade.php`
- Create: `tests/Feature/Auth/AdminMfaIssuanceTest.php`

**Interfaces:**
- Produces: `AdminMfaService::beginFromAdminPortal(Request $request, string $email, string $password): AdminMfaChallenge`.
- Produces: `AdminMfaService::beginIfAdmin(Request $request, string $login, string $password): ?AdminMfaChallenge`.
- Produces: session key constant `AdminMfaService::PENDING_CHALLENGE_KEY`.
- Produces: `AdminLoginCodeNotification::__construct(public readonly string $code)` with mail-only delivery.
- Consumes: `AdminMfaChallenge` from Task 1, `AccountEmail`, `User::STATUS_ACTIVE`, `User::userSuspensions()`, `ActivityLog`, and `mail.verification_mailer`.

- [ ] **Step 1: Write failing issuance, eligibility, hashing, audit, delivery-failure, and email-rendering tests**

Create `tests/Feature/Auth/AdminMfaIssuanceTest.php`:

~~~php
<?php

namespace Tests\Feature\Auth;

use App\Models\ActivityLog;
use App\Models\AdminMfaChallenge;
use App\Models\User;
use App\Models\UserSuspension;
use App\Notifications\AdminLoginCodeNotification;
use App\Services\Auth\AdminMfaService;
use Illuminate\Contracts\Notifications\Dispatcher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
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
~~~

- [ ] **Step 2: Run the issuance test to verify it fails**

Run:

~~~powershell
php artisan test --compact tests/Feature/Auth/AdminMfaIssuanceTest.php
~~~

Expected: FAIL because `AdminMfaService` and `AdminLoginCodeNotification` do not exist.

- [ ] **Step 3: Add the synchronous notification**

Create `app/Notifications/AdminLoginCodeNotification.php`:

~~~php
<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AdminLoginCodeNotification extends Notification
{
    use Queueable;

    public function __construct(public readonly string $code)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->mailer((string) config('mail.verification_mailer', config('mail.default')))
            ->from(
                (string) config('mail.from.address'),
                (string) config('mail.from.name'),
            )
            ->subject('Admin Login Verification — '.config('app.name'))
            ->view('emails.admin-mfa-code', [
                'user' => $notifiable,
                'code' => $this->code,
            ]);
    }
}
~~~

Do not implement `ShouldQueue`; the code must not be serialized into a queue payload.

- [ ] **Step 4: Add the complete branded email template**

Create `resources/views/emails/admin-mfa-code.blade.php`:

~~~blade
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login Verification — Conscious Connections</title>
    <style>
        body { margin: 0; padding: 0; background: #f3f4f6; color: #374151; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .wrapper { max-width: 560px; margin: 40px auto; overflow: hidden; border-radius: 16px; background: #ffffff; box-shadow: 0 4px 24px rgba(0, 0, 0, .10); }
        .header { padding: 40px 32px; text-align: center; background: linear-gradient(135deg, #A30EB2 0%, #730DB1 50%, #3B0CB1 100%); }
        .logo-card { display: inline-block; width: 80px; height: 80px; margin-bottom: 16px; border: 1px solid rgba(255, 255, 255, .25); border-radius: 20px; background: rgba(255, 255, 255, .18); color: #730DB1; font-size: 26px; font-weight: 800; line-height: 80px; }
        .brand { margin: 0 0 4px; color: #ffffff; font-size: 22px; font-weight: 700; letter-spacing: .5px; }
        .divider { width: 40px; height: 2px; margin: 0 auto 20px; border-radius: 2px; background: rgba(255, 255, 255, .4); }
        .title { margin: 0 0 8px; color: #ffffff; font-size: 26px; }
        .subtitle { margin: 0; color: rgba(255, 255, 255, .8); font-size: 14px; }
        .body { padding: 36px 40px; }
        .greeting { margin: 0 0 16px; font-size: 16px; }
        .message { margin: 0 0 24px; color: #6b7280; font-size: 15px; line-height: 1.7; }
        .code-label { margin: 0 0 8px; color: #6b7280; text-align: center; font-size: 12px; font-weight: 700; letter-spacing: .8px; text-transform: uppercase; }
        .code { margin: 0 0 12px; padding: 20px 12px; border: 1px solid #e5e7eb; border-radius: 12px; background: #f9fafb; color: #3B0CB1; text-align: center; font-family: Consolas, Monaco, monospace; font-size: 36px; font-weight: 800; letter-spacing: 10px; }
        .copy-note, .expiry { margin: 0 0 24px; color: #9ca3af; text-align: center; font-size: 13px; line-height: 1.5; }
        .warning { margin-bottom: 24px; padding: 14px 16px; border-left: 4px solid #f59e0b; border-radius: 0 8px 8px 0; background: #fef9f0; }
        .warning p { margin: 0; color: #92400e; font-size: 13px; line-height: 1.5; }
        .footer { padding: 24px 40px; border-top: 1px solid #f3f4f6; background: #f9fafb; text-align: center; }
        .footer a { margin: 0 8px; color: #9ca3af; font-size: 12px; text-decoration: none; }
        .copyright { margin: 12px 0 0; color: #d1d5db; font-size: 12px; }
        @media (max-width: 600px) { .wrapper { margin: 0; border-radius: 0; } .header, .body, .footer { padding-left: 24px; padding-right: 24px; } .code { font-size: 30px; letter-spacing: 7px; } }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="header">
            <div class="logo-card">CC</div>
            <p class="brand">Conscious Connections</p>
            <div class="divider"></div>
            <h1 class="title">Admin Login Verification</h1>
            <p class="subtitle">Secure access to the Admin panel</p>
        </div>

        <div class="body">
            <p class="greeting">Hi, <strong>{{ $user->first_name ?? $user->name }}</strong>!</p>
            <p class="message">Use the verification code below to finish signing in to the Conscious Connections Admin panel.</p>

            <p class="code-label">Your verification code</p>
            <div class="code" aria-label="Verification code {{ implode(' ', str_split($code)) }}">{{ $code }}</div>
            <p class="copy-note">Select and copy this code into the verification page already open in your browser.</p>
            <p class="expiry">This code expires in <strong>5 minutes</strong>.</p>

            <div class="warning">
                <p><strong>Never share this code.</strong> Conscious Connections will never ask you to disclose it. If you did not request this login, ignore this email and secure your account.</p>
            </div>
        </div>

        <div class="footer">
            <div>
                <a href="{{ url('/') }}">Home</a>
                <a href="{{ route('privacy') }}">Privacy Policy</a>
                <a href="{{ route('terms') }}">Terms of Service</a>
            </div>
            <p class="copyright">&copy; {{ date('Y') }} Conscious Connections. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
~~~

- [ ] **Step 5: Implement challenge eligibility, rate limits, hashing, session binding, audit, and delivery**

Create `app/Services/Auth/AdminMfaService.php` with the following class, constants, constructor, and issuance methods. Task 3 will add lifecycle methods to this same class.

~~~php
<?php

namespace App\Services\Auth;

use App\Models\ActivityLog;
use App\Models\AdminMfaChallenge;
use App\Models\User;
use App\Notifications\AdminLoginCodeNotification;
use App\Support\AccountEmail;
use Illuminate\Contracts\Notifications\Dispatcher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Throwable;

class AdminMfaService
{
    public const PENDING_CHALLENGE_KEY = 'admin_mfa.pending_challenge_id';
    public const VERIFIED_USER_KEY = 'admin_mfa.verified_user_id';
    public const VERIFIED_AT_KEY = 'admin_mfa.verified_at';
    public const VERIFIED_FINGERPRINT_KEY = 'admin_mfa.credential_fingerprint';

    private const MAX_PASSWORD_ATTEMPTS = 5;
    private const MAX_CHALLENGE_STARTS = 5;

    public function __construct(private readonly Dispatcher $notifications)
    {
    }

    public function beginFromAdminPortal(Request $request, string $email, string $password): AdminMfaChallenge
    {
        $this->ensurePasswordIsNotLimited($request);
        $user = User::query()->where('email', AccountEmail::normalize($email))->first();

        if (! $user?->hasRole('admin')) {
            $this->failCredentials($request);
        }

        return $this->beginForAdmin($request, $user, $password, 'admin_portal');
    }

    public function beginIfAdmin(Request $request, string $login, string $password): ?AdminMfaChallenge
    {
        if (! str_contains($login, '@')) {
            return null;
        }

        $user = User::query()
            ->where('email', AccountEmail::normalize($login))
            ->first();

        if (! $user?->hasRole('admin')) {
            return null;
        }

        $this->ensurePasswordIsNotLimited($request);

        return $this->beginForAdmin($request, $user, $password, 'general_login');
    }

    private function beginForAdmin(Request $request, User $user, string $password, string $source): AdminMfaChallenge
    {
        $validPassword = Auth::getProvider()->validateCredentials($user, ['password' => $password]);

        if (! $validPassword || ! $this->isEligible($user)) {
            $this->failCredentials($request);
        }

        RateLimiter::clear($this->passwordRateKey($request));
        $this->ensureChallengeStartIsNotLimited($request, $user);
        $this->cancelPendingChallenge($request, 'replaced_by_new_login');
        $request->session()->regenerate();

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $challenge = DB::transaction(fn (): AdminMfaChallenge => AdminMfaChallenge::query()->create([
            'user_id' => $user->id,
            'code_hash' => Hash::make($code),
            'session_fingerprint' => $this->sessionFingerprint($request),
            'credential_fingerprint' => $this->credentialFingerprint($user),
            'attempt_count' => 0,
            'resend_count' => 0,
            'code_expires_at' => now()->addMinutes(5),
            'login_expires_at' => now()->addMinutes(15),
            'resend_available_at' => now()->addMinute(),
        ]));

        $request->session()->put(self::PENDING_CHALLENGE_KEY, $challenge->id);
        $this->hitChallengeStartLimits($request, $user);
        $this->audit('admin_mfa.challenge_created', $challenge, [
            'source' => $source,
        ]);
        $this->deliver($challenge, $code);

        return $challenge->fresh();
    }

    private function isEligible(User $user): bool
    {
        if (! $user->hasRole('admin')
            || $user->status !== User::STATUS_ACTIVE
            || ! $user->hasVerifiedEmail()) {
            return false;
        }

        return ! $user->userSuspensions()
            ->where('status', 'active')
            ->where(function ($query): void {
                $query->whereNull('ends_at')->orWhere('ends_at', '>', now());
            })
            ->exists();
    }

    private function deliver(AdminMfaChallenge $challenge, string $code): void
    {
        try {
            $this->notifications->sendNow($challenge->user, new AdminLoginCodeNotification($code));
            $challenge->forceFill([
                'delivered_at' => now(),
                'delivery_failed_at' => null,
            ])->save();
            $this->audit('admin_mfa.email_sent', $challenge);
        } catch (Throwable $exception) {
            $challenge->forceFill([
                'delivered_at' => null,
                'delivery_failed_at' => now(),
            ])->save();
            Log::warning('Admin MFA email delivery failed.', [
                'user_id' => $challenge->user_id,
                'challenge_id' => $challenge->id,
                'exception' => $exception::class,
            ]);
            $this->audit('admin_mfa.email_failed', $challenge, [
                'exception' => $exception::class,
            ]);
        }
    }

    private function cancelPendingChallenge(Request $request, string $reason): void
    {
        $id = $request->session()->get(self::PENDING_CHALLENGE_KEY);
        if (! is_string($id)) {
            return;
        }

        $challenge = AdminMfaChallenge::query()->find($id);
        if ($challenge !== null && $challenge->consumed_at === null && $challenge->cancelled_at === null) {
            $challenge->forceFill(['cancelled_at' => now()])->save();
            $this->audit('admin_mfa.cancelled', $challenge, ['reason' => $reason]);
        }

        $request->session()->forget(self::PENDING_CHALLENGE_KEY);
    }

    private function ensurePasswordIsNotLimited(Request $request): void
    {
        $key = $this->passwordRateKey($request);
        if (! RateLimiter::tooManyAttempts($key, self::MAX_PASSWORD_ATTEMPTS)) {
            return;
        }

        $seconds = RateLimiter::availableIn($key);
        throw ValidationException::withMessages([
            'email' => ["Too many login attempts. Please try again in {$seconds} seconds."],
        ]);
    }

    private function failCredentials(Request $request): never
    {
        RateLimiter::hit($this->passwordRateKey($request), 60);

        throw ValidationException::withMessages([
            'email' => ['The provided credentials do not match our records.'],
        ]);
    }

    private function ensureChallengeStartIsNotLimited(Request $request, User $user): void
    {
        foreach ($this->challengeStartKeys($request, $user) as $key) {
            if (RateLimiter::tooManyAttempts($key, self::MAX_CHALLENGE_STARTS)) {
                throw ValidationException::withMessages([
                    'email' => ['Too many verification requests. Please wait before trying again.'],
                ]);
            }
        }
    }

    private function hitChallengeStartLimits(Request $request, User $user): void
    {
        foreach ($this->challengeStartKeys($request, $user) as $key) {
            RateLimiter::hit($key, 15 * 60);
        }
    }

    private function challengeStartKeys(Request $request, User $user): array
    {
        return [
            'admin-mfa-start:user:'.$user->id,
            'admin-mfa-start:ip:'.hash('sha256', (string) $request->ip()),
        ];
    }

    private function passwordRateKey(Request $request): string
    {
        return 'admin-login:'.(string) $request->ip();
    }

    private function sessionFingerprint(Request $request): string
    {
        return hash_hmac('sha256', $request->session()->getId(), (string) config('app.key'));
    }

    private function credentialFingerprint(User $user): string
    {
        return hash_hmac('sha256', implode('|', [
            (string) $user->id,
            (string) $user->getAuthPassword(),
            AccountEmail::normalize((string) $user->email),
        ]), (string) config('app.key'));
    }

    private function audit(string $type, AdminMfaChallenge $challenge, array $metadata = []): void
    {
        ActivityLog::log(
            $type,
            str_replace(['admin_mfa.', '_'], ['Admin MFA ', ' '], $type),
            ['challenge_id' => $challenge->id, ...$metadata],
            (int) $challenge->user_id,
        );
    }
}
~~~

- [ ] **Step 6: Run issuance and domain tests**

Run:

~~~powershell
php artisan test --compact tests/Feature/Auth/AdminMfaChallengeTest.php tests/Feature/Auth/AdminMfaIssuanceTest.php
~~~

Expected: PASS. Inspect the failure test to confirm `delivery_failed_at` is set and no authenticated session exists.

- [ ] **Step 7: Commit issuance and email delivery**

~~~powershell
git add -- app/Services/Auth/AdminMfaService.php app/Notifications/AdminLoginCodeNotification.php resources/views/emails/admin-mfa-code.blade.php tests/Feature/Auth/AdminMfaIssuanceTest.php
git commit -m "feat(auth): issue admin MFA codes"
~~~

---

### Task 3: Add atomic verification, resend, cancellation, and verified-session state

**Files:**
- Modify: `app/Services/Auth/AdminMfaService.php`
- Create: `tests/Feature/Auth/AdminMfaLifecycleTest.php`

**Interfaces:**
- Consumes: issuance methods, constants, delivery, fingerprints, eligibility, and audit helper from Task 2.
- Produces: `currentChallenge(Request $request): ?AdminMfaChallenge`.
- Produces: `verify(Request $request, string $code): User`.
- Produces: `resend(Request $request): AdminMfaChallenge`.
- Produces: `cancel(Request $request): void`.
- Produces: `authenticate(Request $request, User $user): void`.
- Produces: `sessionIsVerified(Request $request): bool` and `verifiedSessionData(User $user): array`.
- Produces: `logout(Request $request): void` and `maskEmail(string $email): string`.

- [ ] **Step 1: Write failing lifecycle tests**

Create `tests/Feature/Auth/AdminMfaLifecycleTest.php` with tests for consumption, five-attempt locking, resend replacement/cooldown/limit, expiry, account changes, session isolation, cancellation, session marking, and secret-free audit metadata:

~~~php
<?php

namespace Tests\Feature\Auth;

use App\Models\ActivityLog;
use App\Models\AdminMfaChallenge;
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
~~~

- [ ] **Step 2: Run the lifecycle test to verify missing methods fail**

Run:

~~~powershell
php artisan test --compact tests/Feature/Auth/AdminMfaLifecycleTest.php
~~~

Expected: FAIL with undefined `verify`, `resend`, `cancel`, `authenticate`, or session-state methods.

- [ ] **Step 3: Add current-challenge lookup and atomic verification**

Add these public methods to `AdminMfaService`:

~~~php
public function currentChallenge(Request $request): ?AdminMfaChallenge
{
    $id = $request->session()->get(self::PENDING_CHALLENGE_KEY);
    if (! is_string($id)) {
        return null;
    }

    $challenge = AdminMfaChallenge::query()->with('user')->find($id);
    if ($challenge === null || ! hash_equals(
        $challenge->session_fingerprint,
        $this->sessionFingerprint($request),
    )) {
        $request->session()->forget(self::PENDING_CHALLENGE_KEY);

        return null;
    }

    return $challenge;
}

public function verify(Request $request, string $code): User
{
    $result = DB::transaction(function () use ($request, $code): array {
        $challenge = $this->lockedCurrentChallenge($request);
        if ($challenge === null) {
            return ['user' => null, 'error' => 'This verification request is no longer valid. Sign in again.', 'clear' => true];
        }

        if ($error = $this->baseChallengeError($challenge)) {
            return ['user' => null, 'error' => $error, 'clear' => true];
        }

        if ($challenge->delivered_at === null || $challenge->delivery_failed_at !== null) {
            return ['user' => null, 'error' => 'The verification email was not delivered. Request a new code.', 'clear' => false];
        }

        if ($challenge->code_expires_at->isPast()) {
            $this->audit('admin_mfa.expired', $challenge, ['scope' => 'code']);

            return ['user' => null, 'error' => 'This code has expired. Request a new code.', 'clear' => false];
        }

        if (! Hash::check($code, $challenge->code_hash)) {
            $challenge->attempt_count++;
            $remaining = max(0, 5 - $challenge->attempt_count);
            if ($remaining === 0) {
                $challenge->locked_at = now();
            }
            $challenge->save();
            $this->audit('admin_mfa.verification_failed', $challenge, [
                'attempt_count' => $challenge->attempt_count,
                'remaining_attempts' => $remaining,
            ]);

            return [
                'user' => null,
                'error' => $remaining === 0
                    ? 'Too many incorrect codes. Sign in again.'
                    : "The verification code is incorrect. {$remaining} attempts remaining.",
                'clear' => $remaining === 0,
            ];
        }

        $challenge->forceFill(['consumed_at' => now()])->save();
        $this->audit('admin_mfa.verification_succeeded', $challenge);

        return ['user' => $challenge->user, 'error' => null, 'clear' => false];
    });

    if (is_string($result['error'])) {
        if ($result['clear']) {
            $request->session()->forget(self::PENDING_CHALLENGE_KEY);
        }

        throw ValidationException::withMessages(['code' => [$result['error']]]);
    }

    return $result['user'];
}
~~~

Add these private helpers to the same service:

~~~php
private function lockedCurrentChallenge(Request $request): ?AdminMfaChallenge
{
    $id = $request->session()->get(self::PENDING_CHALLENGE_KEY);
    if (! is_string($id)) {
        return null;
    }

    $challenge = AdminMfaChallenge::query()
        ->with('user')
        ->lockForUpdate()
        ->find($id);

    if ($challenge === null || ! hash_equals(
        $challenge->session_fingerprint,
        $this->sessionFingerprint($request),
    )) {
        return null;
    }

    return $challenge;
}

private function baseChallengeError(AdminMfaChallenge $challenge): ?string
{
    if ($challenge->consumed_at !== null) {
        return 'This verification code has already been used. Sign in again.';
    }

    if ($challenge->cancelled_at !== null) {
        return 'This verification request was cancelled. Sign in again.';
    }

    if ($challenge->locked_at !== null) {
        return 'Too many incorrect codes. Sign in again.';
    }

    if ($challenge->login_expires_at->isPast()) {
        $challenge->forceFill(['cancelled_at' => now()])->save();
        $this->audit('admin_mfa.expired', $challenge, ['scope' => 'login']);

        return 'This login request has expired. Sign in again.';
    }

    if (! $this->isEligible($challenge->user) || ! hash_equals(
        $challenge->credential_fingerprint,
        $this->credentialFingerprint($challenge->user),
    )) {
        $challenge->forceFill(['cancelled_at' => now()])->save();

        return 'This verification request is no longer valid. Sign in again.';
    }

    return null;
}
~~~

Returning an error from the transaction and throwing afterward is required: throwing inside the transaction would roll back the incremented attempt counter and `locked_at` timestamp.

- [ ] **Step 4: Add atomic resend and cancellation**

Add to `AdminMfaService`:

~~~php
public function resend(Request $request): AdminMfaChallenge
{
    $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    $result = DB::transaction(function () use ($request, $code): array {
        $challenge = $this->lockedCurrentChallenge($request);
        if ($challenge === null) {
            return ['challenge' => null, 'error' => 'This verification request is no longer valid. Sign in again.', 'clear' => true];
        }

        if ($error = $this->baseChallengeError($challenge)) {
            return ['challenge' => null, 'error' => $error, 'clear' => true];
        }

        if ($challenge->resend_count >= 3) {
            return ['challenge' => null, 'error' => 'The resend limit has been reached. Use the most recent code or sign in again.', 'clear' => false];
        }

        if ($challenge->resend_available_at->isFuture()) {
            $seconds = now()->diffInSeconds($challenge->resend_available_at);

            return ['challenge' => null, 'error' => "Please wait {$seconds} seconds before requesting another code.", 'clear' => false];
        }

        $codeExpiry = now()->addMinutes(5);
        if ($codeExpiry->greaterThan($challenge->login_expires_at)) {
            $codeExpiry = $challenge->login_expires_at->copy();
        }

        $challenge->forceFill([
            'code_hash' => Hash::make($code),
            'code_expires_at' => $codeExpiry,
            'resend_available_at' => now()->addMinute(),
            'resend_count' => $challenge->resend_count + 1,
            'delivered_at' => null,
            'delivery_failed_at' => null,
        ])->save();
        $this->audit('admin_mfa.resend_requested', $challenge, [
            'resend_count' => $challenge->resend_count,
        ]);

        return ['challenge' => $challenge, 'error' => null, 'clear' => false];
    });

    if (is_string($result['error'])) {
        if ($result['clear']) {
            $request->session()->forget(self::PENDING_CHALLENGE_KEY);
        }

        throw ValidationException::withMessages(['resend' => [$result['error']]]);
    }

    $this->deliver($result['challenge'], $code);

    return $result['challenge']->fresh();
}

public function cancel(Request $request): void
{
    DB::transaction(function () use ($request): void {
        $challenge = $this->lockedCurrentChallenge($request);
        if ($challenge !== null && $challenge->consumed_at === null && $challenge->cancelled_at === null) {
            $challenge->forceFill(['cancelled_at' => now()])->save();
            $this->audit('admin_mfa.cancelled', $challenge, ['reason' => 'user_cancelled']);
        }
    });

    $request->session()->forget([self::PENDING_CHALLENGE_KEY, 'url.intended']);
    $request->session()->regenerate();
    $request->session()->regenerateToken();
}
~~~

- [ ] **Step 5: Add authenticated-session marking, logout, and email masking**

Add to `AdminMfaService`:

~~~php
public function authenticate(Request $request, User $user): void
{
    Auth::guard('web')->login($user);
    $request->session()->regenerate();
    $request->session()->forget(self::PENDING_CHALLENGE_KEY);
    $request->session()->put($this->verifiedSessionData($user));
}

public function verifiedSessionData(User $user): array
{
    return [
        self::VERIFIED_USER_KEY => (int) $user->id,
        self::VERIFIED_AT_KEY => now()->toIso8601String(),
        self::VERIFIED_FINGERPRINT_KEY => $this->credentialFingerprint($user),
    ];
}

public function sessionIsVerified(Request $request): bool
{
    $user = $request->user();
    $fingerprint = $request->session()->get(self::VERIFIED_FINGERPRINT_KEY);
    $verifiedAt = $request->session()->get(self::VERIFIED_AT_KEY);

    return $user instanceof User
        && $this->isEligible($user)
        && (int) $request->session()->get(self::VERIFIED_USER_KEY) === (int) $user->id
        && is_string($verifiedAt)
        && is_string($fingerprint)
        && hash_equals($this->credentialFingerprint($user), $fingerprint);
}

public function logout(Request $request): void
{
    $user = $request->user();
    if ($user instanceof User && $user->hasRole('admin')) {
        ActivityLog::log(
            'admin_mfa.logout',
            'Admin MFA logout',
            null,
            (int) $user->id,
        );
    }

    Auth::guard('web')->logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();
}

public function maskEmail(string $email): string
{
    [$local, $domain] = array_pad(explode('@', AccountEmail::normalize($email), 2), 2, '');
    if ($local === '' || $domain === '') {
        return '***';
    }

    $domainParts = explode('.', $domain);
    $domainParts[0] = substr($domainParts[0], 0, 1).'***';

    return substr($local, 0, 1).'***@'.implode('.', $domainParts);
}
~~~

- [ ] **Step 6: Run lifecycle and issuance tests**

Run:

~~~powershell
php artisan test --compact tests/Feature/Auth/AdminMfaChallengeTest.php tests/Feature/Auth/AdminMfaIssuanceTest.php tests/Feature/Auth/AdminMfaLifecycleTest.php
~~~

Expected: PASS. Confirm the duplicate-verification test proves one consumption and the failed-attempt assertions persist after validation exceptions.

- [ ] **Step 7: Commit the atomic lifecycle**

~~~powershell
git add -- app/Services/Auth/AdminMfaService.php tests/Feature/Auth/AdminMfaLifecycleTest.php
git commit -m "feat(auth): verify admin MFA challenges"
~~~

---

### Task 4: Wire both login entry points and the complete MFA HTTP flow

**Files:**
- Create: `app/Http/Controllers/Auth/AdminMfaController.php`
- Create: `app/Http/Requests/Auth/VerifyAdminMfaRequest.php`
- Modify: `app/Http/Controllers/Auth/AdminAuthController.php:25`
- Modify: `app/Http/Controllers/Auth/AuthenticatedSessionController.php:26`
- Modify: `routes/auth.php:98`
- Create: `resources/views/auth/admin-mfa.blade.php`
- Create: `tests/Feature/Auth/AdminMfaAuthenticationTest.php`
- Test: `tests/Feature/Auth/AdminLoginPageUiTest.php`

**Interfaces:**
- Consumes: every public lifecycle/session method from `AdminMfaService` Tasks 2–3.
- Produces routes: `admin.mfa.show`, `admin.mfa.verify`, `admin.mfa.resend`, and `admin.mfa.cancel`.
- Produces: `AdminMfaController::show()`, `verify()`, `resend()`, and `cancel()`.
- Produces: six `digits[]` values normalized to one validated `code` by `VerifyAdminMfaRequest`.
- Preserves: ordinary `LoginRequest::authenticate()`, learner username login, role-based redirect behavior, and the existing permission-only Admin-portal rejection.

- [ ] **Step 1: Write failing end-to-end authentication tests**

Create `tests/Feature/Auth/AdminMfaAuthenticationTest.php`:

~~~php
<?php

namespace Tests\Feature\Auth;

use App\Models\ActivityLog;
use App\Models\AdminMfaChallenge;
use App\Models\User;
use App\Notifications\AdminLoginCodeNotification;
use App\Services\Auth\AdminMfaService;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AdminMfaAuthenticationTest extends TestCase
{
    public function test_admin_portal_validates_password_without_authenticating_and_redirects_to_mfa(): void
    {
        Notification::fake();
        $admin = $this->admin();

        $this->post(route('admin.login.submit'), [
            'email' => $admin->email,
            'password' => 'password',
        ])->assertRedirect(route('admin.mfa.show'));

        $this->assertGuest();
        $this->assertDatabaseHas('admin_mfa_challenges', ['user_id' => $admin->id]);
        Notification::assertSentTo($admin, AdminLoginCodeNotification::class);
    }

    public function test_general_login_intercepts_an_email_based_admin_before_normal_authentication(): void
    {
        Notification::fake();
        $admin = $this->admin();

        $this->post('/login', [
            'email' => $admin->email,
            'password' => 'password',
        ])->assertRedirect(route('admin.mfa.show'));

        $this->assertGuest();
        Notification::assertSentTo($admin, AdminLoginCodeNotification::class);
    }

    public function test_non_admin_login_retains_the_existing_authentication_flow(): void
    {
        $learner = User::factory()->create([
            'role' => 'learner',
            'status' => User::STATUS_ACTIVE,
        ]);
        $learner->assignRole('learner');

        $this->post('/login', [
            'email' => $learner->email,
            'password' => 'password',
        ])->assertRedirect();

        $this->assertAuthenticatedAs($learner);
        $this->assertDatabaseCount('admin_mfa_challenges', 0);
    }

    public function test_verification_page_masks_email_and_success_authenticates_after_consumption(): void
    {
        [$admin, $code, $challenge] = $this->start();

        $this->get(route('admin.mfa.show'))
            ->assertOk()
            ->assertSee('Two-Step Verification', false)
            ->assertSee('a***@e***.com', false)
            ->assertDontSee($admin->email, false)
            ->assertSee('name="digits[]"', false)
            ->assertSee(route('admin.mfa.resend'), false)
            ->assertSee(route('admin.mfa.cancel'), false);

        $this->post(route('admin.mfa.verify'), [
            'digits' => str_split($code),
        ])->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($admin);
        $this->assertNotNull($challenge->fresh()->consumed_at);
        $this->assertSame(
            $admin->id,
            session(AdminMfaService::VERIFIED_USER_KEY),
        );
    }

    public function test_invalid_code_returns_to_the_prompt_and_cancel_returns_to_login(): void
    {
        [$admin] = $this->start();

        $this->from(route('admin.mfa.show'))
            ->post(route('admin.mfa.verify'), ['digits' => str_split('999999')])
            ->assertRedirect(route('admin.mfa.show'))
            ->assertSessionHasErrors('code');

        $this->post(route('admin.mfa.cancel'))
            ->assertRedirect(route('admin.login'));

        $this->assertGuest();
        $this->assertNull(session(AdminMfaService::PENDING_CHALLENGE_KEY));
    }

    public function test_admin_logout_clears_session_and_records_audit_event(): void
    {
        [$admin, $code] = $this->start();
        $this->post(route('admin.mfa.verify'), ['digits' => str_split($code)]);

        $this->post(route('admin.logout'))
            ->assertRedirect(route('admin.login'));

        $this->assertGuest();
        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $admin->id,
            'activity_type' => 'admin_mfa.logout',
        ]);
    }

    public function test_mfa_routes_without_a_pending_challenge_return_to_admin_login(): void
    {
        $this->get(route('admin.mfa.show'))
            ->assertRedirect(route('admin.login'));

        $this->post(route('admin.mfa.verify'), ['digits' => str_split('123456')])
            ->assertRedirect(route('admin.login'));
    }

    private function start(): array
    {
        Notification::fake();
        $admin = $this->admin(['email' => 'admin@example.com']);
        $this->post(route('admin.login.submit'), [
            'email' => $admin->email,
            'password' => 'password',
        ]);
        $notification = Notification::sent($admin, AdminLoginCodeNotification::class)->last();
        $challenge = AdminMfaChallenge::query()->where('user_id', $admin->id)->latest()->firstOrFail();

        return [$admin, $notification->code, $challenge];
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
}
~~~

- [ ] **Step 2: Run the HTTP test to verify routes and controllers are missing**

Run:

~~~powershell
php artisan test --compact tests/Feature/Auth/AdminMfaAuthenticationTest.php tests/Feature/Auth/AdminLoginPageUiTest.php
~~~

Expected: the existing login-page test passes; the new test fails because MFA routes and controllers do not exist.

- [ ] **Step 3: Add six-digit request normalization**

Create `app/Http/Requests/Auth/VerifyAdminMfaRequest.php`:

~~~php
<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class VerifyAdminMfaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $digits = $this->input('digits', []);
        $this->merge([
            'code' => is_array($digits) ? implode('', $digits) : '',
        ]);
    }

    public function rules(): array
    {
        return [
            'digits' => ['required', 'array', 'size:6'],
            'digits.*' => ['required', 'regex:/^\d$/'],
            'code' => ['required', 'digits:6'],
        ];
    }

    public function messages(): array
    {
        return [
            'digits.required' => 'Enter the six-digit verification code.',
            'digits.size' => 'Enter all six digits.',
            'digits.*.regex' => 'The verification code may contain numbers only.',
            'code.digits' => 'Enter a valid six-digit verification code.',
        ];
    }
}
~~~

- [ ] **Step 4: Add the MFA controller with a same-origin Admin intended-URL boundary**

Create `app/Http/Controllers/Auth/AdminMfaController.php`:

~~~php
<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\VerifyAdminMfaRequest;
use App\Services\Auth\AdminMfaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AdminMfaController extends Controller
{
    public function show(Request $request, AdminMfaService $mfa): View|RedirectResponse
    {
        $challenge = $mfa->currentChallenge($request);
        if ($challenge === null) {
            return redirect()->route('admin.login');
        }

        return view('auth.admin-mfa', [
            'maskedEmail' => $mfa->maskEmail((string) $challenge->user->email),
            'codeExpiresAt' => $challenge->code_expires_at->getTimestamp(),
            'resendAvailableAt' => $challenge->resend_available_at->getTimestamp(),
            'deliveryFailed' => $challenge->delivery_failed_at !== null,
        ]);
    }

    public function verify(
        VerifyAdminMfaRequest $request,
        AdminMfaService $mfa,
    ): RedirectResponse {
        if ($mfa->currentChallenge($request) === null) {
            return redirect()->route('admin.login');
        }

        try {
            $user = $mfa->verify($request, (string) $request->validated('code'));
        } catch (ValidationException $exception) {
            if (! $request->session()->has(AdminMfaService::PENDING_CHALLENGE_KEY)) {
                return redirect()->route('admin.login')->withErrors($exception->errors());
            }

            throw $exception;
        }
        $mfa->authenticate($request, $user);

        return redirect()->to($this->intendedAdminUrl($request))
            ->with('success', 'Two-step verification complete.');
    }

    public function resend(Request $request, AdminMfaService $mfa): RedirectResponse
    {
        if ($mfa->currentChallenge($request) === null) {
            return redirect()->route('admin.login');
        }

        try {
            $challenge = $mfa->resend($request);
        } catch (ValidationException $exception) {
            if (! $request->session()->has(AdminMfaService::PENDING_CHALLENGE_KEY)) {
                return redirect()->route('admin.login')->withErrors($exception->errors());
            }

            throw $exception;
        }

        return back()->with(
            $challenge->delivery_failed_at === null ? 'status' : 'warning',
            $challenge->delivery_failed_at === null
                ? 'A new verification code was sent.'
                : 'We could not send the verification code. Try again when resend is available.',
        );
    }

    public function cancel(Request $request, AdminMfaService $mfa): RedirectResponse
    {
        $mfa->cancel($request);

        return redirect()->route('admin.login');
    }

    private function intendedAdminUrl(Request $request): string
    {
        $intended = (string) $request->session()->pull('url.intended', '');
        $adminRoot = url('/admin');

        if ($intended === $adminRoot || str_starts_with($intended, $adminRoot.'/')) {
            return $intended;
        }

        if ($intended === '/admin' || str_starts_with($intended, '/admin/')) {
            return url($intended);
        }

        return route('admin.dashboard');
    }
}
~~~

- [ ] **Step 5: Register guest MFA routes**

Import `AdminMfaController` in `routes/auth.php`. Inside the existing `Route::middleware('guest')->group(...)`, immediately after the `secure-panel-access` POST route, add:

~~~php
Route::get('secure-panel-access/verify', [AdminMfaController::class, 'show'])
    ->name('admin.mfa.show');
Route::post('secure-panel-access/verify', [AdminMfaController::class, 'verify'])
    ->middleware('throttle:10,1')
    ->name('admin.mfa.verify');
Route::post('secure-panel-access/resend', [AdminMfaController::class, 'resend'])
    ->middleware('throttle:6,1')
    ->name('admin.mfa.resend');
Route::post('secure-panel-access/cancel', [AdminMfaController::class, 'cancel'])
    ->name('admin.mfa.cancel');
~~~

- [ ] **Step 6: Replace immediate Admin authentication at both entry points**

Replace `AdminAuthController::login()` with:

~~~php
public function login(Request $request, AdminMfaService $mfa): RedirectResponse
{
    if (is_string($request->input('email'))) {
        $request->merge(['email' => AccountEmail::normalize($request->input('email'))]);
    }

    $validated = $request->validate([
        'email' => ['required', 'email:rfc'],
        'password' => ['required', 'string'],
    ]);

    $mfa->beginFromAdminPortal(
        $request,
        (string) $validated['email'],
        (string) $validated['password'],
    );

    return redirect()->route('admin.mfa.show');
}
~~~

Replace `AdminAuthController::logout()` with:

~~~php
public function logout(Request $request, AdminMfaService $mfa): RedirectResponse
{
    $mfa->logout($request);

    return redirect()->route('admin.login');
}
~~~

Remove unused `Auth`, `RateLimiter`, and `ValidationException` imports; add `AdminMfaService` and `RedirectResponse` imports.

At the start of `AuthenticatedSessionController::store()`, before `$request->authenticate()`, add:

~~~php
$challenge = $mfa->beginIfAdmin(
    $request,
    (string) $request->input('email'),
    (string) $request->input('password'),
);

if ($challenge !== null) {
    return redirect()->route('admin.mfa.show');
}
~~~

Add `AdminMfaService $mfa` to the method signature. Keep the existing post-authentication `access admin panel` rejection so permission-only non-Admins retain their existing behavior.

Replace `AuthenticatedSessionController::destroy()` with:

~~~php
public function destroy(Request $request, AdminMfaService $mfa): RedirectResponse
{
    $mfa->logout($request);

    return redirect('/');
}
~~~

- [ ] **Step 7: Add the responsive, accessible verification view**

Create `resources/views/auth/admin-mfa.blade.php`:

~~~blade
<x-auth-split-layout :showTabs="false" logo="/media/Logo.png">
    <x-slot name="panel">
        <div class="h-full flex flex-col items-center justify-center p-12 text-center text-white">
            <img src="{{ asset('/media/Logo.png') }}" alt="Conscious Connections" class="h-36 w-auto mb-6 drop-shadow-lg">
            <h2 class="text-4xl font-extrabold tracking-tight">Admin Authentication</h2>
            <p class="mt-3 text-white/80">A second step protects platform operations.</p>
        </div>
    </x-slot>

    <div
        x-data="{
            digits: @js(old('digits', array_fill(0, 6, ''))),
            expiresAt: {{ $codeExpiresAt * 1000 }},
            resendAt: {{ $resendAvailableAt * 1000 }},
            now: Date.now(),
            loading: false,
            resending: false,
            start() { this.tick = setInterval(() => this.now = Date.now(), 1000) },
            remaining(target) { return Math.max(0, Math.ceil((target - this.now) / 1000)) },
            clock(target) { const value = this.remaining(target); return `${Math.floor(value / 60)}:${String(value % 60).padStart(2, '0')}` },
            inputs() { return [...this.$root.querySelectorAll('[data-otp-digit]')] },
            input(index, event) {
                event.target.value = event.target.value.replace(/\D/g, '').slice(-1)
                this.digits[index] = event.target.value
                if (event.target.value && index < 5) this.inputs()[index + 1].focus()
            },
            back(index, event) {
                if (!event.target.value && index > 0) this.inputs()[index - 1].focus()
            },
            paste(event) {
                const value = event.clipboardData.getData('text').replace(/\D/g, '').slice(0, 6)
                value.split('').forEach((digit, index) => { this.digits[index] = digit; this.inputs()[index].value = digit })
                this.inputs()[Math.min(value.length, 6) - 1]?.focus()
            }
        }"
        x-init="start()"
    >
        <div class="mb-8">
            <h1 class="text-4xl font-bold text-purple-900">Two-Step Verification</h1>
            <p class="mt-2 text-sm text-gray-600">Enter the code sent to <strong>{{ $maskedEmail }}</strong>.</p>
        </div>

        @if($deliveryFailed)
            <div role="alert" class="mb-4 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
                We could not deliver the current code. Request another code when resend becomes available.
            </div>
        @endif

        <form method="POST" action="{{ route('admin.mfa.verify') }}" @submit="loading = true" class="space-y-5">
            @csrf
            <fieldset>
                <legend class="block text-sm font-semibold text-gray-700 mb-3">Six-digit verification code</legend>
                <div class="grid grid-cols-6 gap-2" @paste.prevent="paste($event)">
                    @foreach(range(0, 5) as $index)
                        <input
                            data-otp-digit
                            name="digits[]"
                            value="{{ old('digits.'.$index) }}"
                            x-model="digits[{{ $index }}]"
                            @input="input({{ $index }}, $event)"
                            @keydown.backspace="back({{ $index }}, $event)"
                            type="text"
                            inputmode="numeric"
                            pattern="[0-9]*"
                            maxlength="1"
                            autocomplete="{{ $index === 0 ? 'one-time-code' : 'off' }}"
                            aria-label="Digit {{ $index + 1 }} of 6"
                            class="h-14 w-full rounded-xl border border-gray-200 bg-gray-50 text-center text-xl font-bold text-gray-900 focus:border-transparent focus:outline-none focus:ring-2 focus:ring-brand-purple-primary"
                            {{ $index === 0 ? 'autofocus' : '' }}
                        >
                    @endforeach
                </div>
                <x-input-error :messages="$errors->get('code')" class="mt-2" />
            </fieldset>

            <p class="text-center text-sm text-gray-500" aria-live="polite">
                Code expires in <strong x-text="clock(expiresAt)"></strong>
            </p>

            <button
                type="submit"
                :disabled="loading || remaining(expiresAt) === 0"
                style="background: linear-gradient(135deg, #A30EB2, #730DB1, #3B0CB1);"
                class="w-full rounded-xl px-4 py-3 text-sm font-semibold text-white shadow-md disabled:cursor-not-allowed disabled:opacity-60"
            >
                <span x-text="loading ? 'Verifying...' : 'Verify and Continue'"></span>
            </button>
        </form>

        <form method="POST" action="{{ route('admin.mfa.resend') }}" @submit="resending = true" class="mt-4 text-center">
            @csrf
            <button type="submit" :disabled="resending || remaining(resendAt) > 0" class="text-sm font-semibold text-purple-700 disabled:text-gray-400">
                <span x-show="remaining(resendAt) > 0">Resend available in <span x-text="clock(resendAt)"></span></span>
                <span x-show="remaining(resendAt) === 0" x-text="resending ? 'Sending...' : 'Resend code'"></span>
            </button>
            <x-input-error :messages="$errors->get('resend')" class="mt-2" />
        </form>

        <form method="POST" action="{{ route('admin.mfa.cancel') }}" class="mt-3 text-center">
            @csrf
            <button type="submit" class="text-sm text-gray-500 hover:text-gray-700">Cancel and return to login</button>
        </form>
    </div>
</x-auth-split-layout>
~~~

- [ ] **Step 8: Run the HTTP, lifecycle, email, and existing login UI tests**

Run:

~~~powershell
php artisan test --compact tests/Feature/Auth/AdminMfaAuthenticationTest.php tests/Feature/Auth/AdminMfaLifecycleTest.php tests/Feature/Auth/AdminMfaIssuanceTest.php tests/Feature/Auth/AdminLoginPageUiTest.php
php artisan view:cache
~~~

Expected: all tests pass and Blade views compile successfully.

- [ ] **Step 9: Commit the end-to-end HTTP flow**

~~~powershell
git add -- app/Http/Controllers/Auth/AdminMfaController.php app/Http/Requests/Auth/VerifyAdminMfaRequest.php app/Http/Controllers/Auth/AdminAuthController.php app/Http/Controllers/Auth/AuthenticatedSessionController.php routes/auth.php resources/views/auth/admin-mfa.blade.php tests/Feature/Auth/AdminMfaAuthenticationTest.php
git commit -m "feat(auth): require admin email MFA"
~~~

---

### Task 5: Enforce MFA centrally without rewriting existing Admin tests

**Files:**
- Create: `app/Http/Middleware/EnsureAdminMfaVerified.php`
- Modify: `bootstrap/app.php:34`
- Modify: `routes/admin.php:19`
- Create: `tests/Concerns/InteractsWithAdminMfa.php`
- Modify: `tests/TestCase.php`
- Modify: `tests/DatabaseTestCase.php`
- Create: `tests/Feature/Auth/AdminMfaMiddlewareTest.php`

**Interfaces:**
- Consumes: `AdminMfaService::sessionIsVerified()`, `logout()`, session constants, and `verifiedSessionData()`.
- Produces: middleware alias `admin.mfa`.
- Produces: `InteractsWithAdminMfa::actingAs()` for existing tests and `actingAsWithoutAdminMfa()` for explicit pre-MFA security tests.
- Protects: every route already inside `routes/admin.php`.
- Preserves: the meaning of existing test calls—`actingAs($admin)` represents a completely authenticated Admin, while production code receives no test-only bypass.

- [ ] **Step 1: Write failing HTML, JSON, legacy-session, stale-fingerprint, and valid-session tests**

Create `tests/Feature/Auth/AdminMfaMiddlewareTest.php`:

~~~php
<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Services\Auth\AdminMfaService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AdminMfaMiddlewareTest extends TestCase
{
    public function test_legacy_admin_session_without_mfa_is_logged_out_and_redirected(): void
    {
        $admin = $this->admin();

        $this->actingAsWithoutAdminMfa($admin)
            ->get(route('admin.dashboard'))
            ->assertRedirect(route('admin.login'));

        $this->assertGuest();
    }

    public function test_json_admin_request_without_mfa_returns_unauthenticated_json(): void
    {
        Route::middleware(['web', 'auth', 'role:admin', 'admin.mfa'])
            ->get('/_test/admin-mfa-json', fn () => response()->json(['ok' => true]));

        $this->actingAsWithoutAdminMfa($this->admin())
            ->getJson('/_test/admin-mfa-json')
            ->assertUnauthorized()
            ->assertJson([
                'message' => 'Admin multi-factor authentication is required.',
            ]);

        $this->assertGuest();
    }

    public function test_stale_credential_fingerprint_is_rejected(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin);
        $admin->forceFill(['password' => Hash::make('changed-password')])->save();

        $this->get(route('admin.dashboard'))
            ->assertRedirect(route('admin.login'));

        $this->assertGuest();
    }

    public function test_admin_who_becomes_inactive_is_rejected_even_with_a_valid_marker(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin);
        $admin->forceFill(['status' => User::STATUS_INACTIVE])->save();

        $this->get(route('admin.dashboard'))
            ->assertRedirect(route('admin.login'));

        $this->assertGuest();
    }

    public function test_normal_acting_as_admin_helper_carries_valid_mfa_state(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.dashboard'))
            ->assertOk();
    }

    public function test_marker_for_another_user_is_rejected(): void
    {
        $admin = $this->admin();
        $other = $this->admin();

        $this->actingAsWithoutAdminMfa($admin)
            ->withSession(app(AdminMfaService::class)->verifiedSessionData($other))
            ->get(route('admin.dashboard'))
            ->assertRedirect(route('admin.login'));
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
}
~~~

This test intentionally calls `actingAsWithoutAdminMfa()`, which will be introduced with the test concern in Step 4.

- [ ] **Step 2: Run the middleware test to verify the alias/helper is missing**

Run:

~~~powershell
php artisan test --compact tests/Feature/Auth/AdminMfaMiddlewareTest.php
~~~

Expected: FAIL because the helper and `admin.mfa` alias do not exist.

- [ ] **Step 3: Add central middleware and register it**

Create `app/Http/Middleware/EnsureAdminMfaVerified.php`:

~~~php
<?php

namespace App\Http\Middleware;

use App\Services\Auth\AdminMfaService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminMfaVerified
{
    public function __construct(private readonly AdminMfaService $mfa)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        if ($this->mfa->sessionIsVerified($request)) {
            return $next($request);
        }

        $expectsJson = $request->expectsJson();
        $this->mfa->logout($request);

        if ($expectsJson) {
            return response()->json([
                'message' => 'Admin multi-factor authentication is required.',
            ], 401);
        }

        return redirect()->route('admin.login')->withErrors([
            'email' => 'Complete two-step verification to access the Admin panel.',
        ]);
    }
}
~~~

Add the alias in `bootstrap/app.php`:

~~~php
'admin.mfa' => \App\Http\Middleware\EnsureAdminMfaVerified::class,
~~~

Change the route group declaration in `routes/admin.php` to:

~~~php
Route::prefix('admin')->name('admin.')->middleware([
    'auth',
    'verified',
    'role:admin',
    'admin.mfa',
])->group(function () {
~~~

Do not modify `routes/api.php`; it currently contains public location endpoints and a signed Persona webhook only. The middleware's JSON branch is verified with the temporary test route.

- [ ] **Step 4: Add one shared test concern instead of editing hundreds of Admin tests**

Create `tests/Concerns/InteractsWithAdminMfa.php`:

~~~php
<?php

namespace Tests\Concerns;

use App\Models\User;
use App\Services\Auth\AdminMfaService;
use Illuminate\Contracts\Auth\Authenticatable as UserContract;

trait InteractsWithAdminMfa
{
    public function actingAs(UserContract $user, $guard = null)
    {
        parent::actingAs($user, $guard);

        if ($user instanceof User && $user->hasRole('admin')) {
            $this->withSession(app(AdminMfaService::class)->verifiedSessionData($user));
        }

        return $this;
    }

    protected function actingAsWithoutAdminMfa(User $user)
    {
        parent::actingAs($user, 'web');

        return $this->withSession([
            AdminMfaService::VERIFIED_USER_KEY => null,
            AdminMfaService::VERIFIED_AT_KEY => null,
            AdminMfaService::VERIFIED_FINGERPRINT_KEY => null,
        ]);
    }
}
~~~

Import and use the concern in both `tests/TestCase.php` and `tests/DatabaseTestCase.php`:

~~~php
use Tests\Concerns\InteractsWithAdminMfa;

abstract class TestCase extends BaseTestCase
{
    use InteractsWithAdminMfa, RefreshDatabase;
~~~

and:

~~~php
use Tests\Concerns\InteractsWithAdminMfa;

abstract class DatabaseTestCase extends BaseTestCase
{
    use InteractsWithAdminMfa;
~~~

Do not add an `app()->environment('testing')` exception to production middleware. Existing tests receive explicit valid session state through the test base classes instead.

- [ ] **Step 5: Run middleware, Admin route, and RBAC regressions**

Run:

~~~powershell
php artisan test --compact tests/Feature/Auth/AdminMfaMiddlewareTest.php tests/Feature/Auth/AdminMfaAuthenticationTest.php tests/Feature/Admin/AdminAllModulesPageTest.php tests/Feature/Admin/AdminRolePermissionManagementAuthorizationTest.php tests/Feature/Rbac/RbacResidualRoleCheckRegressionTest.php
~~~

Expected: PASS. The non-Admin RBAC test must remain forbidden, ordinary Admin tests must receive a valid marker, and explicit pre-MFA requests must be logged out.

- [ ] **Step 6: Commit central enforcement and test compatibility**

~~~powershell
git add -- app/Http/Middleware/EnsureAdminMfaVerified.php bootstrap/app.php routes/admin.php tests/Concerns/InteractsWithAdminMfa.php tests/TestCase.php tests/DatabaseTestCase.php tests/Feature/Auth/AdminMfaMiddlewareTest.php
git commit -m "feat(auth): enforce admin MFA sessions"
~~~

---

### Task 6: Prevent Admin email-change lockout with a signed verification-only route

**Files:**
- Create: `app/Http/Controllers/Auth/AdminEmailVerificationController.php`
- Modify: `app/Notifications/CustomVerifyEmail.php`
- Modify: `routes/auth.php:121`
- Create: `tests/Feature/Auth/AdminEmailVerificationTest.php`
- Test: `tests/Feature/Auth/EmailVerificationTest.php`
- Test: `tests/Feature/ProfileTest.php`
- Test: `tests/Feature/Admin/AdminProfileControllerTest.php`

**Interfaces:**
- Produces route: `admin.email-verification.verify`, protected by `signed` and `throttle:6,1`, but not `auth` or `guest`.
- Produces: `AdminEmailVerificationController::__invoke(Request $request, User $user): RedirectResponse`.
- Modifies: `CustomVerifyEmail::verificationUrl()` to choose the Admin-only route only for a current `admin` role recipient.
- Preserves: the current `verification.verify` URL and authenticated verification controller for every non-Admin.

- [ ] **Step 1: Write failing signed-link isolation tests**

Create `tests/Feature/Auth/AdminEmailVerificationTest.php`:

~~~php
<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Notifications\CustomVerifyEmail;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class AdminEmailVerificationTest extends TestCase
{
    public function test_admin_notification_uses_signed_guest_safe_url_that_verifies_without_login(): void
    {
        Event::fake();
        $admin = $this->admin();
        $url = (new CustomVerifyEmail())->toMail($admin)->viewData['verificationUrl'];

        $this->assertStringContainsString('secure-panel-access/verify-email', $url);
        $this->assertTrue(URL::hasValidSignature(Request::create($url)));

        $this->get($url)
            ->assertRedirect(route('admin.login'));

        Event::assertDispatched(Verified::class);
        $this->assertTrue($admin->fresh()->hasVerifiedEmail());
        $this->assertGuest();
    }

    public function test_signed_admin_url_rejects_a_wrong_current_email_hash(): void
    {
        $admin = $this->admin();
        $url = URL::temporarySignedRoute(
            'admin.email-verification.verify',
            now()->addHour(),
            ['user' => $admin->id, 'hash' => sha1('wrong@example.com')],
        );

        $this->get($url)->assertForbidden();
        $this->assertFalse($admin->fresh()->hasVerifiedEmail());
        $this->assertGuest();
    }

    public function test_tampered_admin_url_is_rejected_by_signature_middleware(): void
    {
        $admin = $this->admin();
        $url = (new CustomVerifyEmail())->toMail($admin)->viewData['verificationUrl'];

        $this->get($url.'&tampered=1')->assertForbidden();
        $this->assertFalse($admin->fresh()->hasVerifiedEmail());
    }

    public function test_non_admin_notification_keeps_the_existing_verification_route(): void
    {
        $learner = User::factory()->unverified()->create([
            'role' => 'learner',
            'status' => User::STATUS_ACTIVE,
        ]);
        $learner->assignRole('learner');

        $url = (new CustomVerifyEmail())->toMail($learner)->viewData['verificationUrl'];

        $this->assertStringNotContainsString('secure-panel-access/verify-email', $url);
        $this->assertStringContainsString('/verify-email/', $url);
    }

    private function admin(): User
    {
        $admin = User::factory()->unverified()->create([
            'role' => 'admin',
            'status' => User::STATUS_ACTIVE,
        ]);
        $admin->assignRole('admin');

        return $admin;
    }
}
~~~

- [ ] **Step 2: Run the signed-link test to verify the route is missing**

Run:

~~~powershell
php artisan test --compact tests/Feature/Auth/AdminEmailVerificationTest.php
~~~

Expected: FAIL because `admin.email-verification.verify` does not exist and Admin notifications still use the authenticated route.

- [ ] **Step 3: Add the verification-only controller**

Create `app/Http/Controllers/Auth/AdminEmailVerificationController.php`:

~~~php
<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AdminEmailVerificationController extends Controller
{
    public function __invoke(Request $request, User $user): RedirectResponse
    {
        abort_unless($user->hasRole('admin'), 403);
        abort_unless(hash_equals(
            (string) $request->route('hash'),
            sha1($user->getEmailForVerification()),
        ), 403);

        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
            event(new Verified($user));
        }

        return redirect()->route('admin.login')->with(
            'status',
            'Email verified. Sign in to continue with two-step verification.',
        );
    }
}
~~~

The signed URL proves possession of the current registered mailbox. This controller deliberately does not call `Auth::login()`, write an MFA marker, or redirect into the Admin panel.

- [ ] **Step 4: Generate the Admin-specific signed URL in the existing notification**

Add these imports to `CustomVerifyEmail`:

~~~php
use App\Models\User;
use Illuminate\Support\Facades\URL;
~~~

Add this protected override to the class:

~~~php
protected function verificationUrl($notifiable)
{
    if (! $notifiable instanceof User || ! $notifiable->hasRole('admin')) {
        return parent::verificationUrl($notifiable);
    }

    return URL::temporarySignedRoute(
        'admin.email-verification.verify',
        now()->addMinutes((int) config('auth.verification.expire', 60)),
        [
            'user' => $notifiable->getKey(),
            'hash' => sha1($notifiable->getEmailForVerification()),
        ],
    );
}
~~~

- [ ] **Step 5: Register the route outside both guest and authenticated groups**

Import `AdminEmailVerificationController` in `routes/auth.php`. Place this route beside the existing signed verification routes, but outside `Route::middleware('guest')` and `Route::middleware('auth')` groups:

~~~php
Route::get(
    'secure-panel-access/verify-email/{user}/{hash}',
    AdminEmailVerificationController::class,
)->middleware(['signed', 'throttle:6,1'])
    ->name('admin.email-verification.verify');
~~~

- [ ] **Step 6: Run Admin and existing email-verification regressions**

Run:

~~~powershell
php artisan test --compact tests/Feature/Auth/AdminEmailVerificationTest.php tests/Feature/Auth/EmailVerificationTest.php tests/Feature/ProfileTest.php tests/Feature/Admin/AdminProfileControllerTest.php
~~~

Expected: PASS. The Admin link works while logged out but leaves the browser guest; existing learner/profile verification tests remain unchanged.

- [ ] **Step 7: Commit the Admin email-verification seam**

~~~powershell
git add -- app/Http/Controllers/Auth/AdminEmailVerificationController.php app/Notifications/CustomVerifyEmail.php routes/auth.php tests/Feature/Auth/AdminEmailVerificationTest.php
git commit -m "fix(auth): keep admin email verification reachable"
~~~

---

### Task 7: Close the security matrix and collect honest verification evidence

**Files:**
- Modify: `tests/Feature/Auth/AdminMfaIssuanceTest.php`
- Modify: `tests/Feature/Auth/AdminMfaLifecycleTest.php`
- Modify: `tests/Feature/Auth/AdminMfaAuthenticationTest.php`
- Verify: every production and test path listed in the Planned File Map.

**Interfaces:**
- Adds no production abstraction.
- Proves: challenge-start limits, code-only expiration recovery, role/status/email/suspension invalidation, request-role manipulation resistance, duplicate consumption, secret-free audit, HTML/JSON route enforcement, and non-Admin regressions.
- Produces: exact source, PHPUnit, formatting, browser, mail-provider, deployment, and production evidence for the final report.

- [ ] **Step 1: Add the missing account and IP challenge-start limit tests**

Add to `AdminMfaIssuanceTest`:

~~~php
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
~~~

- [ ] **Step 2: Add code-expiry recovery and account-change invalidation tests**

Add to `AdminMfaLifecycleTest`:

~~~php
public function test_expired_code_can_be_replaced_while_overall_login_is_active(): void
{
    Carbon::setTestNow('2026-11-11 10:00:00');
    [$service, $request, $challenge, $code] = $this->issued();
    $challenge->forceFill([
        'code_expires_at' => now()->subSecond(),
        'resend_available_at' => now()->subSecond(),
    ])->save();

    try {
        $service->verify($request, $code);
        $this->fail('Expected the expired code to fail.');
    } catch (ValidationException $exception) {
        $this->assertStringContainsString('expired', $exception->errors()['code'][0]);
    }

    $replacement = $service->resend($request);

    $this->assertSame(1, $replacement->resend_count);
    $this->assertNull($replacement->cancelled_at);
}

public function test_role_status_email_and_suspension_changes_invalidate_pending_challenges(): void
{
    [$service, $request, $challenge, $code, $admin] = $this->issued();
    $admin->syncRoles(['learner']);
    $this->assertPendingChallengeIsRejected($service, $request, $code);

    $admin = $this->admin();
    [$service, $request, $challenge, $code] = $this->issued($admin);
    $admin->forceFill(['status' => User::STATUS_INACTIVE])->save();
    $this->assertPendingChallengeIsRejected($service, $request, $code);

    $admin = $this->admin();
    [$service, $request, $challenge, $code] = $this->issued($admin);
    $admin->forceFill(['email' => 'changed@example.com'])->save();
    $this->assertPendingChallengeIsRejected($service, $request, $code);

    $admin = $this->admin();
    [$service, $request, $challenge, $code] = $this->issued($admin);
    \App\Models\UserSuspension::query()->create([
        'user_id' => $admin->id,
        'status' => 'active',
        'starts_at' => now()->subMinute(),
        'ends_at' => now()->addHour(),
    ]);
    $this->assertPendingChallengeIsRejected($service, $request, $code);
}

private function assertPendingChallengeIsRejected(
    AdminMfaService $service,
    Request $request,
    string $code,
): void {
    try {
        $service->verify($request, $code);
        $this->fail('Expected the pending challenge to be invalidated.');
    } catch (ValidationException $exception) {
        $this->assertArrayHasKey('code', $exception->errors());
    }
}
~~~

- [ ] **Step 3: Add a request-role manipulation regression**

Add to `AdminMfaAuthenticationTest`:

~~~php
public function test_request_role_parameter_cannot_turn_a_learner_into_an_admin_challenge(): void
{
    Notification::fake();
    $learner = User::factory()->create([
        'role' => 'learner',
        'status' => User::STATUS_ACTIVE,
    ]);
    $learner->assignRole('learner');

    $this->post(route('admin.login.submit'), [
        'email' => $learner->email,
        'password' => 'password',
        'role' => 'admin',
    ])->assertSessionHasErrors('email');

    $this->assertGuest();
    $this->assertDatabaseCount('admin_mfa_challenges', 0);
    Notification::assertNothingSent();
}
~~~

- [ ] **Step 4: Run the complete focused security suite**

Run:

~~~powershell
php artisan test --compact tests/Feature/Auth/AdminMfaChallengeTest.php tests/Feature/Auth/AdminMfaIssuanceTest.php tests/Feature/Auth/AdminMfaLifecycleTest.php tests/Feature/Auth/AdminMfaAuthenticationTest.php tests/Feature/Auth/AdminMfaMiddlewareTest.php tests/Feature/Auth/AdminEmailVerificationTest.php tests/Feature/Auth/AdminLoginPageUiTest.php tests/Feature/Auth/EmailVerificationTest.php tests/Feature/ProfileTest.php tests/Feature/Admin/AdminProfileControllerTest.php tests/Feature/Rbac/RbacResidualRoleCheckRegressionTest.php
~~~

Expected: PASS with zero failures. Record the actual test and assertion counts; do not copy expected counts from this plan.

- [ ] **Step 5: Inspect registered routes, middleware placement, and pruning schedule**

Run:

~~~powershell
php artisan route:list --name=admin.mfa
php artisan route:list --name=admin.email-verification
php artisan route:list --path=admin
php artisan schedule:list
~~~

Expected:

- Four guest MFA routes are listed.
- One signed Admin email-verification route is listed without `auth`.
- Every protected `/admin` route inherits `auth`, `verified`, `role:admin`, and `admin.mfa`.
- `model:prune` is scheduled daily.

- [ ] **Step 6: Run source, Blade, and formatting checks**

Run:

~~~powershell
rg -n "Auth::attempt|Auth::login" app/Http/Controllers/Auth/AdminAuthController.php app/Http/Controllers/Auth/AuthenticatedSessionController.php
rg -n "admin\.mfa|role:admin" routes/admin.php bootstrap/app.php
rg -n "code_hash|AdminLoginCodeNotification|admin_mfa" app tests resources/views
php artisan view:cache
vendor\bin\pint --test app/Models/AdminMfaChallenge.php app/Services/Auth/AdminMfaService.php app/Notifications/AdminLoginCodeNotification.php app/Http/Controllers/Auth/AdminMfaController.php app/Http/Controllers/Auth/AdminEmailVerificationController.php app/Http/Requests/Auth/VerifyAdminMfaRequest.php app/Http/Middleware/EnsureAdminMfaVerified.php
~~~

Expected:

- No Admin controller performs password authentication with `Auth::attempt()` or authenticates before `AdminMfaService::authenticate()`.
- Central Admin routes show the role and MFA middleware.
- Blade compilation succeeds.
- Pint reports no style changes required for task-owned PHP files.

- [ ] **Step 7: Run the full PHPUnit suite against the isolated test database**

Reconfirm the database contract, then run:

~~~powershell
Select-String -Path phpunit.xml -Pattern 'APP_ENV.*testing','DB_DATABASE.*cc_db_test'
php artisan test --compact
~~~

Expected: exit code 0. Report the actual tests, assertions, skipped tests, deprecations, and duration. If an unrelated pre-existing failure remains, report it separately with its exact test name and output; do not call the suite passing.

- [ ] **Step 8: Perform browser and configured-mail delivery checks**

Start the normal development stack in a separate terminal without running `composer setup`:

~~~powershell
composer run dev
~~~

Using a disposable Admin test account and the configured SMTP sandbox or approved provider, verify:

1. `/secure-panel-access` accepts valid Admin credentials but does not expose `/admin/dashboard` before OTP.
2. The prompt masks the address, supports digit-by-digit entry and full-code paste, announces errors, and works at mobile and desktop widths.
3. The expiry and resend countdowns display correctly while server rejection still wins after manual client-time manipulation.
4. The received email matches the current purple Conscious Connections email UI, contains selectable code text, and contains no OTP URL.
5. Wrong, expired, resent-old, consumed, and five-times-wrong codes fail with the designed messages.
6. Resend invalidates the old code and respects the 60-second/three-resend limits.
7. Cancel and logout clear pending or authenticated MFA state.
8. `/login` sends an email-based Admin into MFA while a Learner, Instructor, and Parent retain their existing flow.
9. An Admin email-change link verifies the email while logged out, leaves the browser unauthenticated, and still requires password plus MFA.
10. An existing Admin session created before the feature is forced back through login.

Record browser evidence separately from SMTP/provider evidence. If provider credentials are unavailable, mark delivery verification as unrun rather than substituting `MAIL_MAILER=array` evidence.

- [ ] **Step 9: Commit only test hardening changes**

~~~powershell
git add -- tests/Feature/Auth/AdminMfaIssuanceTest.php tests/Feature/Auth/AdminMfaLifecycleTest.php tests/Feature/Auth/AdminMfaAuthenticationTest.php
git commit -m "test(auth): cover admin MFA security edges"
~~~

- [ ] **Step 10: Prepare the deployment and final technical report without applying production changes**

The report must contain:

1. Authentication-flow changes.
2. New and modified files.
3. Migration schema, relationships, pruning schedule, and confirmation that no reset/reseed was used.
4. Middleware and route-protection changes, including the fact that no Admin API routes currently exist.
5. Existing mailer configuration and whether a real sandbox/provider delivery was run.
6. OTP hashing, binding, attempt, resend, expiration, single-use, and concurrency behavior.
7. Verification-page and email UI changes.
8. Exact focused and full-suite commands with actual results.
9. Browser, provider, staging, deployment, and production checks clearly labelled as run or unrun.
10. Remaining limitations: no trusted devices, backup codes, TOTP, SMS, or WebAuthn.

For a later approved deployment, take the normal database backup, review:

~~~powershell
php artisan migrate --pretend --path=database/migrations/2026_11_11_000001_create_admin_mfa_challenges_table.php
~~~

Then apply the normal incremental migration only in the explicitly approved target environment. Never use `composer setup`, `migrate:fresh`, or a database reset as a release shortcut.

---

## Completion Definition

Implementation is complete only when all seven tasks are committed, focused tests pass, the full PHPUnit result is reported accurately, task-owned PHP passes Pint, Blade compiles, central route inspection proves Admin MFA enforcement, and any browser/provider/deployment/production checks are explicitly separated by evidence level. A valid Admin password alone must never reach protected Admin functionality.
