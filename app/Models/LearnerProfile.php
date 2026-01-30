<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class LearnerProfile extends Model
{
    protected $fillable = [
        'user_id',
        'username',
        'username_changed_at',
        'birthdate',
        'gender',
        'city_code',
        'barangay',
        'barangay_code',
        'school',
        'bio',
        'avatar_path',
        'about',
    ];

    protected $casts = [
        'birthdate' => 'date',
        'gender' => 'string',
        'username_changed_at' => 'datetime',
    ];

    protected $hidden = [];

    /**
     * Get the user that owns the learner profile.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get city/municipality location.
     */
    public function city(): BelongsTo
    {
        return $this->belongsTo(\Schoolees\Psgc\Models\City::class, 'city_code', 'code');
    }

    /**
     * Get barangay location.
     */
    public function barangayLocation(): BelongsTo
    {
        return $this->belongsTo(\Schoolees\Psgc\Models\Barangay::class, 'barangay_code', 'code');
    }

    /**
     * Calculate age from birthdate.
     */
    public function getAge(): int
    {
        return Carbon::parse($this->birthdate)->age;
    }

    /**
     * Get age bracket: kids, teens, or adults.
     */
    public function getAgeBracket(): ?string
    {
        if (!$this->birthdate) {
            return null;
        }

        $age = $this->getAge();

        return match (true) {
            $age >= 5 && $age <= 12 => 'kids',
            $age >= 13 && $age <= 17 => 'teens',
            $age >= 18 => 'adults',
            default => null, // Under 5 not allowed
        };
    }

    /**
     * Check if user is a kid (5-12).
     */
    public function isKid(): bool
    {
        return $this->getAgeBracket() === 'kids';
    }

    /**
     * Check if user is a teen (13-17).
     */
    public function isTeen(): bool
    {
        return $this->getAgeBracket() === 'teens';
    }

    /**
     * Check if user is an adult (18+).
     */
    public function isAdult(): bool
    {
        return $this->getAgeBracket() === 'adults';
    }

    /**
     * Check if profile is completed (all required fields filled).
     */
    public function isCompleted(): bool
    {
        return !empty($this->username) 
            && !empty($this->birthdate) 
            && !empty($this->city_code)
            && !empty($this->barangay_code);
    }

    /**
     * Check if account is ready to access platform.
     */
    public function canAccessPlatform(): bool
    {
        return $this->isCompleted();
    }
}
