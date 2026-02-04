<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Schoolees\Psgc\Models\City;
use Schoolees\Psgc\Models\Barangay;

class CaviteCityBarangaySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Cavite Province Code: 402100000
        $caviteCities = [
            ['code' => '402101000', 'name' => 'Bacoor City', 'province_code' => '402100000'],
            ['code' => '402102000', 'name' => 'Cavite City', 'province_code' => '402100000'],
            ['code' => '402103000', 'name' => 'Dasmariñas City', 'province_code' => '402100000'],
            ['code' => '402104000', 'name' => 'Imus City', 'province_code' => '402100000'],
            ['code' => '402105000', 'name' => 'Tagaytay City', 'province_code' => '402100000'],
            ['code' => '402106000', 'name' => 'Trece Martires City', 'province_code' => '402100000'],
            ['code' => '402107000', 'name' => 'General Trias', 'province_code' => '402100000'],
            ['code' => '402108000', 'name' => 'Kawit', 'province_code' => '402100000'],
            ['code' => '402109000', 'name' => 'Noveleta', 'province_code' => '402100000'],
            ['code' => '402110000', 'name' => 'Rosario', 'province_code' => '402100000'],
            ['code' => '402111000', 'name' => 'Silang', 'province_code' => '402100000'],
            ['code' => '402112000', 'name' => 'Tanza', 'province_code' => '402100000'],
            ['code' => '402113000', 'name' => 'Ternate', 'province_code' => '402100000'],
            ['code' => '402114000', 'name' => 'Maragondon', 'province_code' => '402100000'],
            ['code' => '402115000', 'name' => 'Naic', 'province_code' => '402100000'],
            ['code' => '402116000', 'name' => 'Indang', 'province_code' => '402100000'],
            ['code' => '402117000', 'name' => 'Mendez', 'province_code' => '402100000'],
            ['code' => '402118000', 'name' => 'Alfonso', 'province_code' => '402100000'],
            ['code' => '402119000', 'name' => 'Amadeo', 'province_code' => '402100000'],
            ['code' => '402120000', 'name' => 'General Emilio Aguinaldo', 'province_code' => '402100000'],
            ['code' => '402121000', 'name' => 'Magallanes', 'province_code' => '402100000'],
            ['code' => '402122000', 'name' => 'Carmona', 'province_code' => '402100000'],
            ['code' => '402123000', 'name' => 'General Mariano Alvarez', 'province_code' => '402100000'],
        ];

        // Sample barangays for major cities (you can expand this)
        $barangays = [
            // Bacoor City (402101000)
            ['code' => '402101001', 'name' => 'Alima', 'city_code' => '402101000'],
            ['code' => '402101002', 'name' => 'Annalyn', 'city_code' => '402101000'],
            ['code' => '402101003', 'name' => 'Banalo', 'city_code' => '402101000'],
            ['code' => '402101004', 'name' => 'Bayanan', 'city_code' => '402101000'],
            ['code' => '402101005', 'name' => 'Campo Santo', 'city_code' => '402101000'],
            ['code' => '402101006', 'name' => 'City Heights', 'city_code' => '402101000'],
            ['code' => '402101007', 'name' => 'Dulong Bayan', 'city_code' => '402101000'],
            ['code' => '402101008', 'name' => 'Ginintuang Silangan', 'city_code' => '402101000'],
            ['code' => '402101009', 'name' => 'Habay', 'city_code' => '402101000'],
            ['code' => '402101010', 'name' => 'Ligas', 'city_code' => '402101000'],
            ['code' => '402101011', 'name' => 'Mabolo', 'city_code' => '402101000'],
            ['code' => '402101012', 'name' => 'Maliksi', 'city_code' => '402101000'],
            ['code' => '402101013', 'name' => 'Molino', 'city_code' => '402101000'],
            ['code' => '402101014', 'name' => 'Niog', 'city_code' => '402101000'],
            ['code' => '402101015', 'name' => 'Panapaan', 'city_code' => '402101000'],
            ['code' => '402101016', 'name' => 'Queens Row Central', 'city_code' => '402101000'],
            ['code' => '402101017', 'name' => 'Queens Row East', 'city_code' => '402101000'],
            ['code' => '402101018', 'name' => 'Queens Row West', 'city_code' => '402101000'],
            ['code' => '402101019', 'name' => 'Real', 'city_code' => '402101000'],
            ['code' => '402101020', 'name' => 'Salinas', 'city_code' => '402101000'],
            ['code' => '402101021', 'name' => 'San Nicolas', 'city_code' => '402101000'],
            ['code' => '402101022', 'name' => 'Springville', 'city_code' => '402101000'],
            ['code' => '402101023', 'name' => 'Talaba', 'city_code' => '402101000'],
            ['code' => '402101024', 'name' => 'Tabing Dagat', 'city_code' => '402101000'],
            ['code' => '402101025', 'name' => 'Zapote', 'city_code' => '402101000'],

            // Dasmariñas City (402103000)
            ['code' => '402103001', 'name' => 'Bagong Bayan', 'city_code' => '402103000'],
            ['code' => '402103002', 'name' => 'Burol', 'city_code' => '402103000'],
            ['code' => '402103003', 'name' => 'Fatima', 'city_code' => '402103000'],
            ['code' => '402103004', 'name' => 'Langkaan', 'city_code' => '402103000'],
            ['code' => '402103005', 'name' => 'Luzviminda', 'city_code' => '402103000'],
            ['code' => '402103006', 'name' => 'Paliparan', 'city_code' => '402103000'],
            ['code' => '402103007', 'name' => 'Sabang', 'city_code' => '402103000'],
            ['code' => '402103008', 'name' => 'Salawag', 'city_code' => '402103000'],
            ['code' => '402103009', 'name' => 'San Agustin', 'city_code' => '402103000'],
            ['code' => '402103010', 'name' => 'San Jose', 'city_code' => '402103000'],
            ['code' => '402103011', 'name' => 'San Luis', 'city_code' => '402103000'],
            ['code' => '402103012', 'name' => 'Sampaloc', 'city_code' => '402103000'],
            ['code' => '402103013', 'name' => 'Zone 1 Poblacion', 'city_code' => '402103000'],
            ['code' => '402103014', 'name' => 'Zone 2 Poblacion', 'city_code' => '402103000'],
            ['code' => '402103015', 'name' => 'Zone 3 Poblacion', 'city_code' => '402103000'],
            ['code' => '402103016', 'name' => 'Zone 4 Poblacion', 'city_code' => '402103000'],

            // Imus City (402104000)
            ['code' => '402104001', 'name' => 'Alapan I-A', 'city_code' => '402104000'],
            ['code' => '402104002', 'name' => 'Alapan I-B', 'city_code' => '402104000'],
            ['code' => '402104003', 'name' => 'Alapan II-A', 'city_code' => '402104000'],
            ['code' => '402104004', 'name' => 'Alapan II-B', 'city_code' => '402104000'],
            ['code' => '402104005', 'name' => 'Anabu I-A', 'city_code' => '402104000'],
            ['code' => '402104006', 'name' => 'Anabu I-B', 'city_code' => '402104000'],
            ['code' => '402104007', 'name' => 'Anabu I-C', 'city_code' => '402104000'],
            ['code' => '402104008', 'name' => 'Anabu I-D', 'city_code' => '402104000'],
            ['code' => '402104009', 'name' => 'Anabu I-E', 'city_code' => '402104000'],
            ['code' => '402104010', 'name' => 'Anabu I-F', 'city_code' => '402104000'],
            ['code' => '402104011', 'name' => 'Anabu I-G', 'city_code' => '402104000'],
            ['code' => '402104012', 'name' => 'Anabu II-A', 'city_code' => '402104000'],
            ['code' => '402104013', 'name' => 'Anabu II-B', 'city_code' => '402104000'],
            ['code' => '402104014', 'name' => 'Anabu II-C', 'city_code' => '402104000'],
            ['code' => '402104015', 'name' => 'Anabu II-D', 'city_code' => '402104000'],
            ['code' => '402104016', 'name' => 'Buhay na Tubig', 'city_code' => '402104000'],
            ['code' => '402104017', 'name' => 'Bucandala I', 'city_code' => '402104000'],
            ['code' => '402104018', 'name' => 'Bucandala II', 'city_code' => '402104000'],
            ['code' => '402104019', 'name' => 'Bucandala III', 'city_code' => '402104000'],
            ['code' => '402104020', 'name' => 'Bucandala IV', 'city_code' => '402104000'],
            ['code' => '402104021', 'name' => 'Bucandala V', 'city_code' => '402104000'],

            // General Trias (402107000) - sample barangays
            ['code' => '402107001', 'name' => 'Alingaro', 'city_code' => '402107000'],
            ['code' => '402107002', 'name' => 'Arnaldo Poblacion', 'city_code' => '402107000'],
            ['code' => '402107003', 'name' => 'Bacao I', 'city_code' => '402107000'],
            ['code' => '402107004', 'name' => 'Bacao II', 'city_code' => '402107000'],
            ['code' => '402107005', 'name' => 'Bagumbayan Poblacion', 'city_code' => '402107000'],
            ['code' => '402107006', 'name' => 'Biclatan', 'city_code' => '402107000'],
            ['code' => '402107007', 'name' => 'Corregidor Poblacion', 'city_code' => '402107000'],
            ['code' => '402107008', 'name' => 'Dulong Bayan Poblacion', 'city_code' => '402107000'],
            ['code' => '402107009', 'name' => 'Gov. Ferrer Poblacion', 'city_code' => '402107000'],
            ['code' => '402107010', 'name' => 'Javalera', 'city_code' => '402107000'],
            ['code' => '402107011', 'name' => 'Manggahan', 'city_code' => '402107000'],
            ['code' => '402107012', 'name' => 'Navarro', 'city_code' => '402107000'],
            ['code' => '402107013', 'name' => 'Ninety Six Poblacion', 'city_code' => '402107000'],
            ['code' => '402107014', 'name' => 'Panungyanan', 'city_code' => '402107000'],
            ['code' => '402107015', 'name' => 'Pasong Camachile I', 'city_code' => '402107000'],
            ['code' => '402107016', 'name' => 'Pasong Camachile II', 'city_code' => '402107000'],
            ['code' => '402107017', 'name' => 'Pasong Kawayan I', 'city_code' => '402107000'],
            ['code' => '402107018', 'name' => 'Pasong Kawayan II', 'city_code' => '402107000'],
            ['code' => '402107019', 'name' => 'Pinagtipunan', 'city_code' => '402107000'],
            ['code' => '402107020', 'name' => 'Prinza Poblacion', 'city_code' => '402107000'],
        ];

        // Insert cities
        foreach ($caviteCities as $city) {
            City::updateOrCreate(['code' => $city['code']], $city);
        }

        // Insert barangays
        foreach ($barangays as $barangay) {
            Barangay::updateOrCreate(['code' => $barangay['code']], $barangay);
        }

        $this->command->info('Seeded Cavite cities and barangays successfully!');
    }
}