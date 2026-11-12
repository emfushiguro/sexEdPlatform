<?php

namespace App\Models;

use App\Support\LearnerAgePolicy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
        'is_parent_account',
        'requires_parental_consent',
    ];

    protected $casts = [
        'birthdate' => 'date',
        'gender' => 'string',
        'username_changed_at' => 'datetime',
        'is_parent_account' => 'boolean',
        'requires_parental_consent' => 'boolean',
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
    public function barangay(): BelongsTo
    {
        return $this->belongsTo(\Schoolees\Psgc\Models\Barangay::class, 'barangay_code', 'code');
    }

    /**
     * Alias for barangay relationship (backward compatibility).
     */
    public function barangayLocation(): BelongsTo
    {
        return $this->barangay();
    }

    /**
     * Calculate age from birthdate.
     */
    public function getAge(): ?int
    {
        return LearnerAgePolicy::age($this->user?->birthdate ?? $this->birthdate);
    }

    /**
     * Get active age category: teens or adults.
     */
    public function getAgeBracket(): ?string
    {
        return LearnerAgePolicy::category($this->user?->birthdate ?? $this->birthdate);
    }

    /**
     * Retain the legacy kid predicate for source compatibility.
     */
    public function isKid(): bool
    {
        return false;
    }

    /**
     * Check if user is an eligible teenager (10-17).
     */
    public function isTeen(): bool
    {
        return $this->getAgeBracket() === LearnerAgePolicy::CATEGORY_TEENAGERS;
    }

    /**
     * Check if user is an eligible adult (18-100).
     */
    public function isAdult(): bool
    {
        return $this->getAgeBracket() === LearnerAgePolicy::CATEGORY_ADULTS;
    }

    /**
     * Check if profile is completed (all required fields filled).
     */
    public function isCompleted(): bool
    {
        return ! empty($this->username)
            && ! empty($this->birthdate)
            && ! empty($this->city_code)
            && ! empty($this->barangay_code);
    }

    /**
     * Check if account is ready to access platform.
     */
    public function canAccessPlatform(): bool
    {
        return $this->isCompleted();
    }
}
