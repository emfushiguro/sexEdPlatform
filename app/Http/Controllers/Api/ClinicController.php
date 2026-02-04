<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Clinic;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ClinicController extends Controller
{
    /**
     * Display a listing of clinics for public consumption.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Clinic::query()
            ->approved()
            ->active()
            ->select([
                'id', 'name', 'type', 'city', 'barangay', 'address',
                'latitude', 'longitude', 'contact', 'email', 'services',
                'operating_hours', 'notes', 'is_active'
            ]);

        // Apply filters
        if ($request->filled('city')) {
            $query->byCity($request->city);
        }

        if ($request->filled('type')) {
            $query->byType($request->type);
        }

        if ($request->filled('service')) {
            $query->withService($request->service);
        }

        if ($request->filled('services') && is_array($request->services)) {
            foreach ($request->services as $service) {
                $query->withService($service);
            }
        }

        // Search by name or address
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('address', 'like', "%{$search}%")
                  ->orWhere('city', 'like', "%{$search}%");
            });
        }

        // Location-based filtering
        if ($request->filled(['latitude', 'longitude'])) {
            $radius = $request->get('radius', 10); // Default 10km
            $query->nearby($request->latitude, $request->longitude, $radius);
        }

        $clinics = $query->get()->map(function ($clinic) {
            return [
                'id' => $clinic->id,
                'name' => $clinic->name,
                'type' => $clinic->type instanceof \App\Enums\ClinicType ? $clinic->type->value : $clinic->type,
                'city' => $clinic->city,
                'barangay' => $clinic->barangay,
                'address' => $clinic->address,
                'full_address' => $clinic->full_address,
                'latitude' => $clinic->latitude ? (float) $clinic->latitude : null,
                'longitude' => $clinic->longitude ? (float) $clinic->longitude : null,
                'contact' => $clinic->contact,
                'email' => $clinic->email,
                'services' => $clinic->services,
                'services_display' => $clinic->services_display,
                'operating_hours' => $clinic->operating_hours,
                'notes' => $clinic->notes,
                'google_maps_link' => $clinic->google_maps_link,
                'is_active' => (bool) $clinic->is_active,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $clinics,
            'count' => $clinics->count(),
            'statistics' => [
                'total' => $clinics->count(),
                'active' => $clinics->where('is_active', true)->count(),
                'cities' => $clinics->pluck('city')->unique()->count(),
                'emergency' => $this->count24HourClinics(),
            ]
        ]);
    }

    /**
     * Search for clinics (alias for index with parameters).
     */
    public function search(Request $request): JsonResponse
    {
        return $this->index($request);
    }

    /**
     * Display the specified clinic.
     */
    public function show(string $id): JsonResponse
    {
        $clinic = Clinic::approved()
            ->active()
            ->select([
                'id', 'name', 'type', 'city', 'barangay', 'address',
                'latitude', 'longitude', 'contact', 'email', 'services',
                'operating_hours', 'notes'
            ])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $clinic->id,
                'name' => $clinic->name,
                'type' => $clinic->type,
                'city' => $clinic->city,
                'barangay' => $clinic->barangay,
                'address' => $clinic->address,
                'full_address' => $clinic->full_address,
                'latitude' => $clinic->latitude,
                'longitude' => $clinic->longitude,
                'contact' => $clinic->contact,
                'email' => $clinic->email,
                'services' => $clinic->services,
                'operating_hours' => $clinic->operating_hours,
                'notes' => $clinic->notes,
                'google_maps_link' => $clinic->google_maps_link,
            ]
        ]);
    }

    /**
     * Get available filter options.
     */
    public function filters(): JsonResponse
    {
        $cities = Clinic::approved()->active()
            ->distinct()
            ->pluck('city')
            ->filter()
            ->sort()
            ->values();

        $types = Clinic::approved()->active()
            ->distinct()
            ->pluck('type')
            ->filter()
            ->sort()
            ->values();

        // Get all available services from all clinics
        $allServices = Clinic::approved()->active()
            ->whereNotNull('services')
            ->get()
            ->pluck('services')
            ->flatten()
            ->unique()
            ->sort()
            ->values();

        $clinic = new Clinic();
        $availableServices = $clinic->available_services;

        return response()->json([
            'success' => true,
            'data' => [
                'cities' => $cities,
                'types' => $types,
                'services' => $allServices,
                'available_services' => $availableServices,
            ]
        ]);
    }

    /**
     * Get clinic statistics.
     */
    public function statistics(): JsonResponse
    {
        return $this->stats();
    }

    /**
     * Get clinic statistics.
     */
    public function stats(): JsonResponse
    {
        $totalClinics = Clinic::approved()->active()->count();
        
        $clinicsByCity = Clinic::approved()->active()
            ->selectRaw('city, COUNT(*) as count')
            ->whereNotNull('city')
            ->groupBy('city')
            ->pluck('count', 'city');

        $clinicsByType = Clinic::approved()->active()
            ->selectRaw('type, COUNT(*) as count')
            ->groupBy('type')
            ->pluck('count', 'type');

        // Most common services
        $allServices = Clinic::approved()->active()
            ->whereNotNull('services')
            ->get()
            ->pluck('services')
            ->flatten(); 

        $serviceStats = $allServices->countBy()->sortDesc()->take(10);

        return response()->json([
            'success' => true,
            'data' => [
                'total_clinics' => $totalClinics,
                'clinics_by_city' => $clinicsByCity,
                'clinics_by_type' => $clinicsByType,
                'most_common_services' => $serviceStats,
            ]
        ]);
    }

    /**
     * Count clinics with 24/7 operating hours.
     */
    private function count24HourClinics(): int
    {
        return Clinic::where('approval_status', 'approved')
            ->where('is_active', true)
            ->where(function($query) {
                $query->where('operating_hours', 'like', '%24/7%')
                      ->orWhere('operating_hours', 'like', '%24-7%')
                      ->orWhere('operating_hours', 'like', '%24 hours%')
                      ->orWhereRaw("LOWER(operating_hours) LIKE '%24%7%'");
            })
            ->count();
    }
}
