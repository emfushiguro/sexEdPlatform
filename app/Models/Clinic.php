<?php

namespace App\Models;

use App\Enums\ApprovalStatus;
use App\Enums\ClinicService;
use App\Enums\ClinicType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;
use Illuminate\Http\Request as HttpRequest;

use function request;

class Clinic extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'name',
        'type',
        'city',
        'barangay',
        'address',
        'latitude',
        'longitude',
        'contact',
        'email',
        'services',
        'operating_hours',
        'notes',
        'approval_status',
        'approved_by',
        'approved_at',
        'rejection_reason',
        'verified',
        'is_premium',
        'is_active',
    ];

    // ...existing code...

    protected function casts(): array
    {
        return [
            'type' => ClinicType::class,
            'approval_status' => ApprovalStatus::class,
            'verified' => 'boolean',
            'is_premium' => 'boolean',
            'is_active' => 'boolean',
            'approved_at' => 'datetime',
            'services' => 'array',
            'operating_hours' => 'string',
            'latitude' => 'decimal:8',
            'longitude' => 'decimal:8',
        ];
    }

    // Relationships
    public function getServicesDisplayAttribute(): array
{
    if (!is_array($this->services)) return [];
    $map = [
        'hiv_testing' => 'HIV Testing',
        'std_screening' => 'STD Screening',
        'contraception' => 'Contraception',
        'health_education' => 'Health Education',
        'counseling' => 'Counseling',
        'pregnancy_test' => 'Pregnancy Test',
        'family_planning' => 'Family Planning',
        'vaccination' => 'Vaccination',
    ];
    return array_map(fn($s) => $map[$s] ?? ucfirst(str_replace('_', ' ', $s)), $this->services);
}


    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // Scopes

    public function scopeApproved($query)
    {
        return $query->where('approval_status', 'approved');
    }

    public function scopePending($query)
    {
        return $query->where('approval_status', 'pending');
    }

    public function scopeVerified($query)
    {
        return $query->where('verified', true);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByCity($query, $city)
    {
        return $query->where('city', $city);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeWithService($query, $service)
    {
        return $query->whereJsonContains('services', $service);
    }

    public function scopeNearby($query, $latitude, $longitude, $radiusKm = 10)
    {
        return $query->selectRaw(
            "*, (6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)))) AS distance",
            [$latitude, $longitude, $latitude]
        )->having('distance', '<', $radiusKm);
    }

    // Helper Methods

    public function isApproved(): bool
    {
        return $this->approval_status === 'approved';
    }

    public function isActive(): bool
    {
        return $this->is_active;
    }

    public function getFullAddressAttribute(): string
    {
        $parts = array_filter([
            $this->address,
            $this->barangay,
            $this->city
        ]);
        return implode(', ', $parts);
    }

    public function getGoogleMapsLinkAttribute(): string
    {
        if ($this->latitude && $this->longitude) {
            return "https://www.google.com/maps/dir/?api=1&destination={$this->latitude},{$this->longitude}";
        }
        return "https://www.google.com/maps/search/?api=1&query=" . urlencode($this->full_address);
    }

    public function hasService(string $service): bool
    {
        return in_array($service, $this->services ?? []);
    }

    public function getAvailableServicesAttribute(): array
    {
        return ClinicService::options();
    }

    public function getServicesWithDetailsAttribute(): array
    {
        $servicesDetails = [];
        foreach ($this->services ?? [] as $serviceValue) {
            $service = ClinicService::from($serviceValue);
            $servicesDetails[] = [
                'value' => $service->value,
                'name' => $service->getDisplayName(),
                'description' => $service->getDescription(),
                'category' => $service->getCategory(),
            ];
        }
        return $servicesDetails;
    }

    public function getTypeDisplayNameAttribute(): string
    {
        return $this->type?->getDisplayName() ?? $this->type;
    }

    public function getApprovalStatusBadgeAttribute(): string
    {
        $status = $this->approval_status;
        if ($status instanceof \App\Enums\ApprovalStatus) {
            $statusEnum = $status;
        } else {
            $statusEnum = \App\Enums\ApprovalStatus::tryFrom($status);
        }
        return $statusEnum ? $statusEnum->getBadgeClass() : 'bg-gray-100 text-gray-800';
    }

    // Audit logging methods
    protected static function booted()
    {
        static::updating(function ($clinic) {
            if ($clinic->isDirty('approval_status')) {
                $clinic->logApprovalStatusChange();
            }
        });
    }

    protected function logApprovalStatusChange(): void
    {
        $original = $this->getOriginal('approval_status');
        $new = $this->approval_status;
        if ($new instanceof \App\Enums\ApprovalStatus) {
            $newEnum = $new;
        } else {
            $newEnum = \App\Enums\ApprovalStatus::tryFrom($new);
        }
        \Log::info('Clinic approval status changed', [
            'clinic_id' => $this->id,
            'clinic_name' => $this->name,
            'old_status' => $original,
            'new_status' => $newEnum ? $newEnum->value : $new,
            'changed_by' => Auth::id(),
            'changed_at' => Carbon::now(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
