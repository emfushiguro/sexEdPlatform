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
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;
use Throwable;

class AdminMfaService
{
    public const PENDING_CHALLENGE_KEY = 'admin_mfa.pending_challenge_id';

    public const PENDING_CODE_KEY = 'admin_mfa.pending_code';

    public const VERIFIED_USER_KEY = 'admin_mfa.verified_user_id';

    public const VERIFIED_AT_KEY = 'admin_mfa.verified_at';

    public const VERIFIED_FINGERPRINT_KEY = 'admin_mfa.credential_fingerprint';

    private const MAX_PASSWORD_ATTEMPTS = 5;

    private const MAX_CHALLENGE_STARTS = 5;

    public function __construct(private readonly Dispatcher $notifications) {}

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
            $request->session()->forget([self::PENDING_CHALLENGE_KEY, self::PENDING_CODE_KEY]);

            return null;
        }

        return $challenge;
    }

    public function pendingCode(Request $request, AdminMfaChallenge $challenge): ?string
    {
        if ((string) $request->session()->get(self::PENDING_CHALLENGE_KEY) !== (string) $challenge->getKey()
            || $challenge->delivered_at === null
            || $challenge->delivery_failed_at !== null
            || $challenge->consumed_at !== null
            || $challenge->cancelled_at !== null
            || $challenge->locked_at !== null
            || $challenge->code_expires_at->isPast()
            || $challenge->login_expires_at->isPast()) {
            return null;
        }

        $ciphertext = $request->session()->get(self::PENDING_CODE_KEY);
        if (! is_string($ciphertext)) {
            return null;
        }

        try {
            $code = Crypt::decryptString($ciphertext);
        } catch (Throwable) {
            return null;
        }

        return preg_match('/^\d{6}$/D', $code) === 1 && Hash::check($code, $challenge->code_hash)
            ? $code
            : null;
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
                $request->session()->forget([self::PENDING_CHALLENGE_KEY, self::PENDING_CODE_KEY]);
            }

            throw ValidationException::withMessages(['code' => [$result['error']]]);
        }

        return $result['user'];
    }

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
                $request->session()->forget([self::PENDING_CHALLENGE_KEY, self::PENDING_CODE_KEY]);
            }

            throw ValidationException::withMessages(['resend' => [$result['error']]]);
        }

        $this->deliver($request, $result['challenge'], $code);

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

        $request->session()->forget([
            self::PENDING_CHALLENGE_KEY,
            self::PENDING_CODE_KEY,
            'url.intended',
        ]);
        $request->session()->regenerate();
        $request->session()->regenerateToken();
    }

    public function authenticate(Request $request, User $user): void
    {
        Auth::guard('web')->login($user);
        $request->session()->regenerate();
        $request->session()->forget([self::PENDING_CHALLENGE_KEY, self::PENDING_CODE_KEY]);
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
        $this->deliver($request, $challenge, $code);

        return $challenge->fresh();
    }

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

    private function deliver(Request $request, AdminMfaChallenge $challenge, string $code): void
    {
        try {
            $request->session()->put(self::PENDING_CODE_KEY, Crypt::encryptString($code));
            $copyUrl = URL::temporarySignedRoute(
                'admin.mfa.copy',
                $challenge->code_expires_at,
                ['challenge' => $challenge->id],
            );
            $this->notifications->sendNow($challenge->user, new AdminLoginCodeNotification($code, $copyUrl));
            $challenge->forceFill([
                'delivered_at' => now(),
                'delivery_failed_at' => null,
            ])->save();
            $this->audit('admin_mfa.email_sent', $challenge);
        } catch (Throwable $exception) {
            $request->session()->forget(self::PENDING_CODE_KEY);
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
            $request->session()->forget(self::PENDING_CODE_KEY);

            return;
        }

        $challenge = AdminMfaChallenge::query()->find($id);
        if ($challenge !== null && $challenge->consumed_at === null && $challenge->cancelled_at === null) {
            $challenge->forceFill(['cancelled_at' => now()])->save();
            $this->audit('admin_mfa.cancelled', $challenge, ['reason' => $reason]);
        }

        $request->session()->forget([self::PENDING_CHALLENGE_KEY, self::PENDING_CODE_KEY]);
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
        $appKey = (string) config('app.key');

        return [
            'admin-mfa-start:user:'.hash_hmac('sha256', (string) $user->id, $appKey),
            'admin-mfa-start:ip:'.hash_hmac('sha256', (string) $request->ip(), $appKey),
        ];
    }

    private function passwordRateKey(Request $request): string
    {
        return 'admin-login:'.hash_hmac('sha256', (string) $request->ip(), (string) config('app.key'));
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
