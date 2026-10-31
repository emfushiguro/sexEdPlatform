<?php

namespace App\Services\Location;

use DomainException;
use Schoolees\Psgc\Models\Barangay;
use Schoolees\Psgc\Models\City;
use Schoolees\Psgc\Models\Province;

final class CaviteAddressResolver
{
    /**
     * @return array{province_code:string, province_name:string, city_code:string, city_name:string, barangay_code:string, barangay_name:string}
     */
    public function resolve(string $cityCode, string $barangayCode): array
    {
        $provinceCode = (string) config('instructor_application.province_code', '402100000');
        $city = City::query()
            ->where('code', $cityCode)
            ->where('province_code', $provinceCode)
            ->first();

        if (! $city) {
            throw new DomainException('The selected city or municipality is not in Cavite.', 1);
        }

        $barangay = Barangay::query()
            ->where('code', $barangayCode)
            ->where('city_code', $city->code)
            ->first();

        if (! $barangay) {
            throw new DomainException('The selected barangay does not belong to that city or municipality.', 2);
        }

        $province = Province::query()->where('code', $provinceCode)->first();

        return [
            'province_code' => $provinceCode,
            'province_name' => (string) ($province?->name ?: 'Cavite'),
            'city_code' => (string) $city->code,
            'city_name' => (string) $city->name,
            'barangay_code' => (string) $barangay->code,
            'barangay_name' => (string) $barangay->name,
        ];
    }
}
