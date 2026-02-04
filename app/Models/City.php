<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class City extends Model
{
    use HasFactory;

    protected $table = 'cities';
    
    // Use auto-incrementing ID as primary key
    protected $primaryKey = 'id';
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'code',
        'name', 
        'region_code',
        'province_code',
        'is_city',
        'city_class'
    ];

    protected $casts = [
        'is_city' => 'boolean',
    ];

    /**
     * Get all barangays for this city.
     */
    public function barangays(): HasMany
    {
        return $this->hasMany(Barangay::class, 'city_code', 'code');
    }

    /**
     * Find city by PSGC code.
     */
    public static function findByCode(string $code): ?self
    {
        return static::where('code', $code)->first();
    }

    /**
     * Get Cavite cities only (for your project).
     */
    public static function caviteCities()
    {
        return static::where('province_code', '0402100000')
                    ->orderBy('name')
                    ->get();
    }

    /**
     * Search cities by name or code.
     */
    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'LIKE', "%{$search}%")
              ->orWhere('code', 'LIKE', "%{$search}%");
        });
    }
}