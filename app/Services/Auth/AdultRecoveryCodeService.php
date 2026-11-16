<?php

namespace App\Services\Auth;

use App\Models\User;
use App\Support\LearnerAgePolicy;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

final class AdultRecoveryCodeService
{
    public function eligible(User $user): bool
    {
        return LearnerAgePolicy::classify($user->birthdate) === LearnerAgePolicy::ELIGIBLE_ADULT
            && $user->minor_account_activated_at !== null;
    }

    public function generate(User $user): array
    {
        if (! $this->eligible($user)) {
            throw new InvalidArgumentException('Recovery codes are unavailable.');
        }
        return DB::transaction(function () use ($user): array {
            User::query()->lockForUpdate()->findOrFail($user->id);
            DB::table('dependent_adult_recovery_codes')->where('user_id', $user->id)->delete();
            $codes = [];
            for ($i = 0; $i < 8; $i++) {
                $raw = strtoupper(bin2hex(random_bytes(10)));
                $codes[] = implode('-', str_split($raw, 5));
                DB::table('dependent_adult_recovery_codes')->insert(['user_id' => $user->id, 'code_hash' => $this->digest($user->id, $raw), 'created_at' => now(), 'updated_at' => now()]);
            }
            return $codes;
        });
    }

    public function reset(User $user, string $code, string $password): void
    {
        if (config('session.driver') !== 'database' || (config('session.connection') !== null && config('session.connection') !== config('database.default'))) {
            throw new RuntimeException('Recovery is unavailable with this session configuration.');
        }
        DB::transaction(function () use ($user, $code, $password): void {
            $locked = User::query()->lockForUpdate()->find($user->id);
            $normalized = strtoupper(str_replace(['-', ' '], '', trim($code)));
            if (! $locked || ! $this->eligible($locked) || ! preg_match('/^[A-F0-9]{20}$/', $normalized)) {
                throw new InvalidArgumentException('Invalid recovery details.');
            }
            $record = DB::table('dependent_adult_recovery_codes')->where('user_id', $locked->id)->where('code_hash', $this->digest($locked->id, $normalized))->whereNull('used_at')->lockForUpdate()->first();
            if (! $record) {
                throw new InvalidArgumentException('Invalid recovery details.');
            }
            DB::table('dependent_adult_recovery_codes')->where('id', $record->id)->update(['used_at' => now(), 'updated_at' => now()]);
            $locked->forceFill(['password' => Hash::make($password), 'remember_token' => Str::random(60)])->save();
            DB::table(config('session.table', 'sessions'))->where('user_id', $locked->id)->delete();
        });
    }

    private function digest(int $userId, string $code): string
    {
        return hash_hmac('sha256', $userId.'|'.$code, (string) config('app.key'));
    }
}
