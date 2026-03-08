<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserDailyShield extends Model
{
    protected $fillable = ['user_id', 'shields_remaining', 'date'];

    protected $casts = ['date' => 'date'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Return the user's shield record for today, creating it with 3 shields if absent. */
    public static function todayForUser(User $user): self
    {
        return static::firstOrCreate(
            ['user_id' => $user->id, 'date' => today()->toDateString()],
            ['shields_remaining' => 3]
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

    /** Refill one shield (caps at 3). */
    public static function refillOne(User $user): void
    {
        $record = static::todayForUser($user);

        if ($record->shields_remaining < 3) {
            $record->increment('shields_remaining');
        }
    }

    /** Restore all shields to 3. */
    public static function refillFull(User $user): void
    {
        static::todayForUser($user)->update(['shields_remaining' => 3]);
    }
}
