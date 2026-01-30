<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Schoolees\Psgc\Models\Barangay;
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
        $barangays = Barangay::where('city_code', $cityCode)
            ->orderBy('name')
            ->get(['code', 'name']);

        return response()->json($barangays);
    }
}
