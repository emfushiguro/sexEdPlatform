<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ModuleMarketplaceListing extends Model
{
    use HasFactory;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_PUBLISHED = 'published';

    public const STATUS_UNPUBLISHED = 'unpublished';

    public const STATUS_SUSPENDED = 'suspended';

    protected $fillable = [
        'module_id',
        'seller_type',
        'seller_user_id',
        'status',
        'price_amount',
        'currency',
        'seat_capacity',
        'seat_packages',
        'published_by',
        'published_at',
        'unpublished_by',
        'unpublished_at',
        'suspended_by',
        'suspended_at',
        'suspension_reason',
    ];

    protected function casts(): array
    {
        return [
            'price_amount' => 'decimal:2',
            'seat_capacity' => 'integer',
            'seat_packages' => 'array',
            'published_at' => 'datetime',
            'unpublished_at' => 'datetime',
            'suspended_at' => 'datetime',
        ];
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_user_id');
    }

    public function publishedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'published_by');
    }

    public function unpublishedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'unpublished_by');
    }

    public function suspendedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'suspended_by');
    }

    public function purchases(): HasMany
    {
        return $this->hasMany(ConnectorModulePurchase::class);
    }

    public function entitlements(): HasMany
    {
        return $this->hasMany(ConnectorModuleEntitlement::class);
    }

    public function isPublished(): bool
    {
        return $this->status === self::STATUS_PUBLISHED;
    }

    public function packages(): \Illuminate\Support\Collection
    {
        return collect([
            ['seat_capacity' => $this->seat_capacity, 'price_amount' => $this->price_amount],
            ...($this->seat_packages ?? []),
        ])->sortBy('seat_capacity')->values();
    }

    public function packageForSeats(?int $seats = null): array
    {
        $package = $this->packages()->firstWhere('seat_capacity', $seats ?? $this->seat_capacity);
        if (! $package) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'seat_capacity' => 'Choose an available seat package for this listing.',
            ]);
        }

        return $package;
    }
}
