<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\City;
use App\Models\Barangay;
use Illuminate\Support\Facades\DB;

class PSGCSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Disable foreign key checks for bulk insert
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        
        // Truncate tables to avoid conflicts
        DB::table('barangays')->truncate();
        DB::table('cities')->truncate();
        
        // Insert Cavite cities
        $cities = [
            ['code' => '0402101000', 'name' => 'Alfonso', 'province_code' => '0402100000', 'region_code' => '0400000000', 'is_city' => false, 'city_class' => '5th class'],
            ['code' => '0402102000', 'name' => 'Amadeo', 'province_code' => '0402100000', 'region_code' => '0400000000', 'is_city' => false, 'city_class' => '4th class'],
            ['code' => '0402103000', 'name' => 'Bacoor City', 'province_code' => '0402100000', 'region_code' => '0400000000', 'is_city' => true, 'city_class' => '1st class'],
            ['code' => '0402104000', 'name' => 'Carmona City', 'province_code' => '0402100000', 'region_code' => '0400000000', 'is_city' => true, 'city_class' => '1st class'],
            ['code' => '0402105000', 'name' => 'Cavite City', 'province_code' => '0402100000', 'region_code' => '0400000000', 'is_city' => true, 'city_class' => '3rd class'],
            ['code' => '0402106000', 'name' => 'Dasmariñas City', 'province_code' => '0402100000', 'region_code' => '0400000000', 'is_city' => true, 'city_class' => '1st class'],
            ['code' => '0402107000', 'name' => 'General Emilio Aguinaldo', 'province_code' => '0402100000', 'region_code' => '0400000000', 'is_city' => false, 'city_class' => '5th class'],
            ['code' => '0402108000', 'name' => 'General Mariano Alvarez', 'province_code' => '0402100000', 'region_code' => '0400000000', 'is_city' => false, 'city_class' => '1st class'],
            ['code' => '0402109000', 'name' => 'General Trias City', 'province_code' => '0402100000', 'region_code' => '0400000000', 'is_city' => true, 'city_class' => '1st class'],
            ['code' => '0402110000', 'name' => 'Imus City', 'province_code' => '0402100000', 'region_code' => '0400000000', 'is_city' => true, 'city_class' => '1st class'],
            ['code' => '0402111000', 'name' => 'Indang', 'province_code' => '0402100000', 'region_code' => '0400000000', 'is_city' => false, 'city_class' => '1st class'],
            ['code' => '0402112000', 'name' => 'Kawit', 'province_code' => '0402100000', 'region_code' => '0400000000', 'is_city' => false, 'city_class' => '1st class'],
            ['code' => '0402113000', 'name' => 'Magallanes', 'province_code' => '0402100000', 'region_code' => '0400000000', 'is_city' => false, 'city_class' => '4th class'],
            ['code' => '0402114000', 'name' => 'Maragondon', 'province_code' => '0402100000', 'region_code' => '0400000000', 'is_city' => false, 'city_class' => '2nd class'],
            ['code' => '0402115000', 'name' => 'Mendez (Mendez-Nuñez)', 'province_code' => '0402100000', 'region_code' => '0400000000', 'is_city' => false, 'city_class' => '4th class'],
            ['code' => '0402116000', 'name' => 'Naic', 'province_code' => '0402100000', 'region_code' => '0400000000', 'is_city' => false, 'city_class' => '1st class'],
            ['code' => '0402117000', 'name' => 'Noveleta', 'province_code' => '0402100000', 'region_code' => '0400000000', 'is_city' => false, 'city_class' => '1st class'],
            ['code' => '0402118000', 'name' => 'Rosario', 'province_code' => '0402100000', 'region_code' => '0400000000', 'is_city' => false, 'city_class' => '1st class'],
            ['code' => '0402119000', 'name' => 'Silang', 'province_code' => '0402100000', 'region_code' => '0400000000', 'is_city' => false, 'city_class' => '1st class'],
            ['code' => '0402120000', 'name' => 'Tagaytay City', 'province_code' => '0402100000', 'region_code' => '0400000000', 'is_city' => true, 'city_class' => '3rd class'],
            ['code' => '0402121000', 'name' => 'Tanza', 'province_code' => '0402100000', 'region_code' => '0400000000', 'is_city' => false, 'city_class' => '1st class'],
            ['code' => '0402122000', 'name' => 'Ternate', 'province_code' => '0402100000', 'region_code' => '0400000000', 'is_city' => false, 'city_class' => '3rd class'],
            ['code' => '0402123000', 'name' => 'Trece Martires City', 'province_code' => '0402100000', 'region_code' => '0400000000', 'is_city' => true, 'city_class' => '4th class'],
        ];

        // Add timestamps to each city record
        $now = now();
        foreach ($cities as &$city) {
            $city['created_at'] = $now;
            $city['updated_at'] = $now;
        }

        // Bulk insert cities
        DB::table('cities')->insert($cities);
        $this->command->info('Cities inserted successfully.');

        // Import barangays from the generated SQL file
        $this->importBarangaysFromSQL();

        // Re-enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        
        $this->command->info('PSGC data seeded successfully!');
    }

    /**
     * Import barangays from the generated SQL file.
     */
    private function importBarangaysFromSQL(): void
    {
        $sqlFile = database_path('../cavite_barangays.sql');
        
        if (!file_exists($sqlFile)) {
            $this->command->warn('Barangays SQL file not found. Please ensure cavite_barangays.sql exists in the project root.');
            return;
        }

        // Read and parse the SQL file
        $sql = file_get_contents($sqlFile);
        $lines = explode("\n", $sql);
        
        $barangays = [];
        $now = now();
        
        foreach ($lines as $line) {
            $line = trim($line);
            if (strpos($line, 'INSERT INTO barangays') === 0) {
                // Extract values from INSERT statement
                preg_match("/VALUES \('([^']+)', '([^']+)', '([^']+)'\);/", $line, $matches);
                
                if (count($matches) === 4) {
                    $barangays[] = [
                        'code' => $matches[1],
                        'name' => str_replace("''", "'", $matches[2]), // Unescape single quotes
                        'city_code' => $matches[3],
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }
        }
        
        if (!empty($barangays)) {
            // Insert in chunks to avoid memory issues
            $chunks = array_chunk($barangays, 500);
            foreach ($chunks as $chunk) {
                DB::table('barangays')->insert($chunk);
            }
            $this->command->info(count($barangays) . ' barangays inserted successfully.');
        } else {
            $this->command->warn('No barangay data found in SQL file.');
        }
    }
}