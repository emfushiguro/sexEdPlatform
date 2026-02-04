<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Barangay extends Model
{
    use HasFactory;

    protected $table = 'barangays';
    
    // Use auto-incrementing ID as primary key
    protected $primaryKey = 'id';
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'code',
        'name',
        'city_code'
    ];

    /**
     * Get the city that this barangay belongs to.
     */
    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class, 'city_code', 'code');
    }

    /**
     * Find barangay by PSGC code.
     */
    public static function findByCode(string $code): ?self
    {
        return static::where('code', $code)->first();
    }

    /**
     * Get barangays for a specific city code.
     */
    public static function forCityCode(string $cityCode)
    {
        return static::where('city_code', $cityCode)
                    ->orderBy('name')
                    ->get();
    }

    /**
     * Search barangays by name or code.
     */
    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'LIKE', "%{$search}%")
              ->orWhere('code', 'LIKE', "%{$search}%");
        });
    }

    /**
     * Get full address (barangay, city).
     */
    public function getFullAddressAttribute(): string
    {
        return $this->name . ', ' . $this->city->name;
    }
}