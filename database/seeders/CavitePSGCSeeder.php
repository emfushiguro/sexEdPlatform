<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class CavitePSGCSeeder extends Seeder
{
    /**
     * Seed PSGC data for Cavite province only.
     * This includes Region IV-A (CALABARZON), Cavite province, 
     * all Cavite cities/municipalities, and their barangays.
     */
    public function run(): void
    {
        $this->command->info('🌏 Seeding PSGC data for Cavite...');

        DB::transaction(function () {
            $now = now();

            // 1. Seed Region IV-A (CALABARZON)
            $this->seedRegion($now);

            // 2. Seed Cavite Province
            $this->seedProvince($now);

            // 3. Seed Cavite Cities and Municipalities
            $this->seedCities($now);

            // 4. Seed Barangays for Cavite
            $this->seedBarangays($now);
        });

        $this->command->info(' Cavite PSGC data seeded successfully!');
        $this->showSummary();
    }

    protected function seedRegion($now): void
    {
        $regionsPath = base_path('vendor/schoolees/laravel-psgc/resources/psgc/regions.json');
        $regions = json_decode(File::get($regionsPath), true);

        // Find Region IV-A (CALABARZON) - code: 400000000
        $calabarzon = collect($regions)->firstWhere('code', '400000000');

        if ($calabarzon) {
            DB::table('regions')->updateOrInsert(
                ['code' => $calabarzon['code']],
                array_merge($calabarzon, ['created_at' => $now, 'updated_at' => $now])
            );
            $this->command->info("  ✓ Region: {$calabarzon['name']}");
        }
    }

    protected function seedProvince($now): void
    {
        $provincesPath = base_path('vendor/schoolees/laravel-psgc/resources/psgc/provinces.json');
        $provinces = json_decode(File::get($provincesPath), true);

        // Find Cavite Province - code: 402100000
        $cavite = collect($provinces)->firstWhere('code', '402100000');

        if ($cavite) {
            DB::table('provinces')->updateOrInsert(
                ['code' => $cavite['code']],
                array_merge($cavite, ['created_at' => $now, 'updated_at' => $now])
            );
            $this->command->info("  ✓ Province: {$cavite['name']}");
        }
    }

    protected function seedCities($now): void
    {
        $citiesPath = base_path('vendor/schoolees/laravel-psgc/resources/psgc/cities.json');
        $cities = json_decode(File::get($citiesPath), true);

        // Filter cities/municipalities for Cavite (province_code: 402100000)
        $caviteCities = collect($cities)->filter(function ($city) {
            return $city['province_code'] === '402100000';
        });

        foreach ($caviteCities as $city) {
            DB::table('cities')->updateOrInsert(
                ['code' => $city['code']],
                array_merge($city, ['created_at' => $now, 'updated_at' => $now])
            );
        }

        $this->command->info("  ✓ Cities/Municipalities: {$caviteCities->count()}");
    }

    protected function seedBarangays($now): void
    {
        $barangaysPath = base_path('vendor/schoolees/laravel-psgc/resources/psgc/barangays.json');
        $barangays = json_decode(File::get($barangaysPath), true);

        // Get all Cavite city codes
        $cavityCityCodes = DB::table('cities')
            ->where('province_code', '402100000')
            ->pluck('code')
            ->toArray();

        // Filter barangays that belong to Cavite cities
        $caviteBarangays = collect($barangays)->filter(function ($barangay) use ($cavityCityCodes) {
            return in_array($barangay['city_code'], $cavityCityCodes);
        });

        // Insert in chunks for performance
        $chunks = $caviteBarangays->chunk(500);
        
        foreach ($chunks as $chunk) {
            $data = $chunk->map(function ($barangay) use ($now) {
                return array_merge($barangay, ['created_at' => $now, 'updated_at' => $now]);
            })->toArray();

            DB::table('barangays')->upsert(
                $data,
                ['code'],
                ['name', 'city_code', 'updated_at']
            );
        }

        $this->command->info("  ✓ Barangays: {$caviteBarangays->count()}");
    }

    protected function showSummary(): void
    {
        $this->command->newLine();
        $this->command->info('📊 PSGC Data Summary for Cavite:');
        $this->command->table(
            ['Category', 'Count'],
            [
                ['Regions', DB::table('regions')->count()],
                ['Provinces', DB::table('provinces')->count()],
                ['Cities/Municipalities', DB::table('cities')->count()],
                ['Barangays', DB::table('barangays')->count()],
            ]
        );
    }
}
