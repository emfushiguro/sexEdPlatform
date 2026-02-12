<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Schoolees\Psgc\Models\City;
use Schoolees\Psgc\Models\Barangay;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    /**
     * Get cities/municipalities for a specific province.
     *
     * @param string $provinceCode
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCities($provinceCode)
    {
        $cities = City::where('province_code', $provinceCode)
            ->orderBy('name')
            ->get(['code', 'name']);

        return response()->json($cities);
    }

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
