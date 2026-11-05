<?php

namespace Tests\Unit\Services;

use App\Services\Location\CaviteAddressResolver;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use DomainException;

class CaviteAddressResolverTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        DB::table('cities')->updateOrInsert(
            ['code' => '402101000'],
            [
                'name' => 'Cavite City',
                'region_code' => '400000000',
                'province_code' => '402100000',
                'is_city' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );
        DB::table('cities')->updateOrInsert(
            ['code' => '402201000'],
            [
                'name' => 'Other City',
                'region_code' => '400000000',
                'province_code' => '402200000',
                'is_city' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );
        DB::table('barangays')->updateOrInsert(
            ['code' => '402101001'],
            [
                'name' => 'Barangay Test',
                'city_code' => '402101000',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );
        DB::table('barangays')->updateOrInsert(
            ['code' => '402201001'],
            [
                'name' => 'Other Barangay',
                'city_code' => '402201000',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );
    }

    public function test_resolves_a_cavite_city_and_child_barangay_to_canonical_values(): void
    {
        $this->assertSame([
            'province_code' => '402100000',
            'province_name' => 'Cavite',
            'city_code' => '402101000',
            'city_name' => 'Cavite City',
            'barangay_code' => '402101001',
            'barangay_name' => 'Barangay Test',
        ], app(CaviteAddressResolver::class)->resolve('402101000', '402101001'));
    }

    public function test_rejects_a_city_outside_cavite(): void
    {
        $this->expectException(DomainException::class);

        app(CaviteAddressResolver::class)->resolve('402201000', '402201001');
    }

    public function test_rejects_an_unknown_city(): void
    {
        $this->expectException(DomainException::class);

        app(CaviteAddressResolver::class)->resolve('missing-city', '402101001');
    }

    public function test_rejects_an_unknown_barangay(): void
    {
        $this->expectException(DomainException::class);

        app(CaviteAddressResolver::class)->resolve('402101000', 'missing-barangay');
    }

    public function test_rejects_a_barangay_from_a_different_city(): void
    {
        $this->expectException(DomainException::class);

        app(CaviteAddressResolver::class)->resolve('402101000', '402201001');
    }
}
