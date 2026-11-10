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
