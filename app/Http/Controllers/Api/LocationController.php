<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Barangay;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    /**
     * Get barangays for a specific city/municipality.
     *
     * @param string $cityCode
     * @return \Illuminate\Http\JsonResponse
     */
    public function getBarangays($cityCode)
    {
        $barangays = Barangay::forCityCode($cityCode);
        
        return response()->json($barangays->map(function ($barangay) {
            return [
                'code' => $barangay->code,
                'name' => $barangay->name,
                'id' => $barangay->id
            ];
        }));
    }
}
