<?php

namespace App\Models;

use App\Services\GamificationService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Throwable;

class UserDailyShield extends Model
{
    protected $fillable = ['user_id', 'shields_remaining', 'date'];

    protected $casts = ['date' => 'date'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Return the user's shield record for today, creating it with configured default shields if absent. */
    public static function todayForUser(User $user): self
    {
        return static::firstOrCreate(
            ['user_id' => $user->id, 'date' => today()->toDateString()],
            ['shields_remaining' => static::initialShieldCount()]
        );
    }

    /** Get the number of shields remaining for a user today. */
    public static function getShields(User $user): int
    {
        return static::todayForUser($user)->shields_remaining;
    }

    /** Drain one shield (floors at 0). Returns true if a shield was consumed. */
    public static function drainShield(User $user): bool
    {
        $record = static::todayForUser($user);

        if ($record->shields_remaining <= 0) {
            return false;
        }

        $record->decrement('shields_remaining');

        return true;
    }

    /** Refill one shield (caps at configured daily cap). */
    public static function refillOne(User $user): void
    {
        $record = static::todayForUser($user);
        $cap = static::shieldCap();

        if ($record->shields_remaining < $cap) {
            $record->increment('shields_remaining');
        }
    }

    /** Restore all shields to configured daily cap. */
    public static function refillFull(User $user): void
    {
        static::todayForUser($user)->update(['shields_remaining' => static::shieldCap()]);
    }

    private static function initialShieldCount(): int
    {
        return min(static::shieldDefault(), static::shieldCap());
    }

    private static function shieldDefault(): int
    {
        try {
            return max(0, app(GamificationService::class)->dailyShieldDefault());
        } catch (Throwable) {
            return 3;
        }
    }

    private static function shieldCap(): int
    {
        try {
            return max(0, app(GamificationService::class)->dailyShieldCap());
        } catch (Throwable) {
            return 3;
        }
    }
}
