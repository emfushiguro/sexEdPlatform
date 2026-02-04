<?php

namespace App\Services;

use App\Enums\ClinicService;
use App\Enums\ClinicType;
use App\Models\Clinic;
use Illuminate\Support\Facades\Cache;

class ClinicCacheService
{
    const CACHE_TTL = 3600; // 1 hour
    
    public function getClinicTypes(): array
    {
        return Cache::remember('clinic_types', self::CACHE_TTL, function () {
            return ClinicType::options();
        });
    }
    
    public function getClinicServices(): array
    {
        return Cache::remember('clinic_services', self::CACHE_TTL, function () {
            return ClinicService::options();
        });
    }
    
    public function getCitiesList(): array
    {
        return Cache::remember('clinic_cities', self::CACHE_TTL, function () {
            return Clinic::distinct()
                ->whereNotNull('city')
                ->pluck('city')
                ->sort()
                ->values()
                ->toArray();
        });
    }
    
    public function getPopularServices(): array
    {
        return Cache::remember('popular_clinic_services', self::CACHE_TTL, function () {
            $allServices = Clinic::whereNotNull('services')
                ->where('approval_status', 'approved')
                ->pluck('services')
                ->flatten()
                ->countBy()
                ->sortDesc()
                ->take(5);
            
            return $allServices->map(function ($count, $service) {
                $serviceEnum = ClinicService::from($service);
                return [
                    'service' => $service,
                    'name' => $serviceEnum->getDisplayName(),
                    'count' => $count,
                ];
            })->values()->toArray();
        });
    }
    
    public function getApprovalStats(): array
    {
        return Cache::remember('clinic_approval_stats', 300, function () { // 5 minutes
            return [
                'total' => Clinic::count(),
                'pending' => Clinic::where('approval_status', 'pending')->count(),
                'approved' => Clinic::where('approval_status', 'approved')->count(),
                'rejected' => Clinic::where('approval_status', 'rejected')->count(),
                'active' => Clinic::where('is_active', true)->count(),
                'this_month' => Clinic::whereYear('created_at', now()->year)
                    ->whereMonth('created_at', now()->month)
                    ->count(),
            ];
        });
    }
    
    public function getClinicsByType(): array
    {
        return Cache::remember('clinics_by_type', self::CACHE_TTL, function () {
            return Clinic::where('approval_status', 'approved')
                ->where('is_active', true)
                ->groupBy('type')
                ->selectRaw('type, count(*) as count')
                ->get()
                ->mapWithKeys(function ($item) {
                    $type = ClinicType::from($item->type);
                    return [$type->getDisplayName() => $item->count];
                })
                ->toArray();
        });
    }
    
    public function clearAllCache(): void
    {
        Cache::forget('clinic_types');
        Cache::forget('clinic_services');
        Cache::forget('clinic_cities');
        Cache::forget('popular_clinic_services');
        Cache::forget('clinic_approval_stats');
        Cache::forget('clinics_by_type');
    }
    
    public function clearStatsCache(): void
    {
        Cache::forget('clinic_approval_stats');
        Cache::forget('clinics_by_type');
        Cache::forget('popular_clinic_services');
    }
    
    public function warmCache(): void
    {
        $this->getClinicTypes();
        $this->getClinicServices();
        $this->getCitiesList();
        $this->getPopularServices();
        $this->getApprovalStats();
        $this->getClinicsByType();
    }
}