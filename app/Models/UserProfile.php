<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserProfile extends Model
{
    protected $fillable = [
        'user_id',
        'bio',
        'birthdate',
        'gender',
        'location',
        'avatar',
        'contact',
        'province_code',
        'city_code',
        'barangay_code',
        'address_line',
    ];

    protected function casts(): array
    {
        return [
            'birthdate' => 'date',
        ];
    }

    // Relationships

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function province(): BelongsTo
    {
        return $this->belongsTo(\Schoolees\Psgc\Models\Province::class, 'province_code', 'code');
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(\Schoolees\Psgc\Models\City::class, 'city_code', 'code');
    }

    public function barangay(): BelongsTo
    {
        return $this->belongsTo(\Schoolees\Psgc\Models\Barangay::class, 'barangay_code', 'code');
    }
}
