<?php

namespace Database\Seeders;

use App\Models\Clinic;
use App\Models\User;
use App\Enums\ApprovalStatus;
use App\Enums\ClinicService;
use App\Enums\ClinicType;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class ClinicSeeder extends Seeder
{
    public function run(): void
    {
        // Always create or update the default admin user
        $user = User::updateOrCreate(
            ['email' => 'admin@sexed.platform'],
            [
                'first_name' => 'Admin',
                'last_name' => 'User',
                'name' => 'Admin User',
                'email' => 'admin@sexed.platform',
                'password' => bcrypt('admin123'),
                'role' => 'admin',
                'status' => 'active',
                'verified' => true,
            ]
        );

        // Ensure admin has proper role
        if (!$user->hasRole('admin')) {
            $user->assignRole('admin');
        }

        // Clear existing clinics safely using model (triggers events)
        Clinic::query()->forceDelete();

        // Sample clinics with EXACT data structure matching UI creation
        $clinics = [
            [
                'name' => 'awdaw',
                'type' => ClinicType::CLINIC, // Use enum instance
                'city' => 'Mendez (Mendez-Nuñez)',
                'barangay' => 'Labac',
                'address' => 'awdawd',
                'latitude' => 14.21346880,
                'longitude' => 120.85657400,
                'contact' => '21312321',
                'email' => '21312awdaw@gmail.com',
                'services' => ['hiv_testing', 'std_screening', 'contraception', 'health_education'], // Array - will be auto-casted
                'operating_hours' => 'Mon to Fri 9:00 AM - 6:00 PM',
                'notes' => null,
                'is_active' => true,
                'verified' => false, // Match UI default
                'is_premium' => false, // Explicit default
                'approval_status' => ApprovalStatus::APPROVED, // Use enum instance
                'approved_by' => $user->id,
                'approved_at' => Carbon::now(),
                'user_id' => $user->id,
            ],
            [
                'name' => 'Dasmarinas Health Center',
                'type' => ClinicType::CLINIC,
                'city' => 'Dasmariñas',
                'barangay' => 'Zone 1', 
                'address' => '123 Main Street, Dasmariñas, Cavite',
                'latitude' => 14.3294,
                'longitude' => 120.9367,
                'contact' => '+63 912 345 6789',
                'email' => 'dasmarinas.health@cavite.gov.ph',
                'services' => ['hiv_testing', 'std_screening', 'counseling', 'contraception'],
                'operating_hours' => 'Mon to Fri 8:00 AM - 5:00 PM',
                'notes' => 'Main health center serving Dasmariñas City. Walk-ins welcome.',
                'is_active' => true,
                'verified' => true,
                'is_premium' => false,
                'approval_status' => ApprovalStatus::APPROVED,
                'approved_by' => $user->id,
                'approved_at' => Carbon::now(),
                'user_id' => $user->id,
            ],
            [
                'name' => 'Imus Community Hospital',
                'type' => ClinicType::HOSPITAL,
                'city' => 'Imus',
                'barangay' => 'Poblacion',
                'address' => '456 Hospital Road, Imus, Cavite',
                'latitude' => 14.4269,
                'longitude' => 120.9396,
                'contact' => '+63 912 765 4321',
                'email' => 'info@imuscommunityhospital.ph',
                'services' => ['hiv_testing', 'std_screening', 'counseling', 'contraception', 'pregnancy_test', 'family_planning'],
                'operating_hours' => '24/7',
                'notes' => 'Full-service community hospital with 24/7 emergency services.',
                'is_active' => true,
                'verified' => true,
                'is_premium' => false,
                'approval_status' => ApprovalStatus::APPROVED,
                'approved_by' => $user->id,
                'approved_at' => Carbon::now(),
                'user_id' => $user->id,
            ],
            [
                'name' => 'Bacoor Testing Center',
                'type' => ClinicType::TESTING_CENTER, // Different type for variety
                'city' => 'Bacoor',
                'barangay' => 'Talaba',
                'address' => '789 Wellness Ave, Bacoor, Cavite',
                'latitude' => 14.4578,
                'longitude' => 120.9421,
                'contact' => '+63 917 111 2233',
                'email' => 'bacoor.testing@clinic.ph',
                'services' => ['hiv_testing', 'std_screening'],
                'operating_hours' => 'Mon to Fri 9:00 AM - 6:00 PM',
                'notes' => 'Specialized HIV/STD testing center.',
                'is_active' => true,
                'verified' => true,
                'is_premium' => false,
                'approval_status' => ApprovalStatus::APPROVED,
                'approved_by' => $user->id,
                'approved_at' => Carbon::now(),
                'user_id' => $user->id,
            ],
            [
                'name' => 'Tanza Barangay Health Station',
                'type' => ClinicType::BARANGAY_HEALTH_STATION, // Use valid enum
                'city' => 'Tanza',
                'barangay' => 'Amaya 1',
                'address' => '101 Health St, Tanza, Cavite',
                'latitude' => 14.3890,
                'longitude' => 120.8530,                                                                            
                'contact' => '+63 917 222 3344',
                'email' => 'tanza.bhs@cavite.gov.ph',
                'services' => ['health_education', 'vaccination'],
                'operating_hours' => 'Mon to Fri 8:00 AM - 5:00 PM',
                'notes' => 'Barangay health station serving the Tanza area.',
                'is_active' => true,
                'verified' => true,
                'is_premium' => false,
                'approval_status' => ApprovalStatus::APPROVED,
                'approved_by' => $user->id,
                'approved_at' => Carbon::now(),
                'user_id' => $user->id,
            ],
        ];

        // Create clinics using proper model flow (triggers events, observers, etc.)
        foreach ($clinics as $clinicData) {
            Clinic::create($clinicData);
        }

        // Clear cache after seeding
        app(\App\Services\ClinicCacheService::class)->clearAllCache();

        $this->command->info('Clinics seeded successfully with UI-compatible data!');
        $this->command->info('Total clinics created: ' . count($clinics));

            // Add 100 randomized clinics
            $cities = [
                'Imus', 'Dasmariñas', 'Bacoor', 'Tanza', 'Mendez', 'General Trias', 'Kawit', 'Rosario', 'Noveleta', 'Naic',
                'Silang', 'Tagaytay', 'Trece Martires', 'Alfonso', 'Amadeo', 'Carmona', 'Gen. Mariano Alvarez', 'Magallanes',
                'Maragondon', 'Indang', 'Ternate', 'Gen. Aguinaldo', 'Imus', 'Bacoor', 'Dasmariñas', 'Tanza', 'Naic', 'Silang',
            ];
            $services = [
                'hiv_testing', 'std_screening', 'contraception', 'health_education', 'counseling', 'pregnancy_test', 'family_planning', 'vaccination'
            ];
            for ($i = 1; $i <= 100; $i++) {
                Clinic::create([
                    'user_id' => $user->id,
                    'name' => 'Clinic ' . $i,
                    'type' => ClinicType::CLINIC,
                    'city' => $cities[array_rand($cities)],
                    'barangay' => 'Barangay ' . rand(1, 50),
                    'address' => 'Address ' . $i,
                    'latitude' => 14.4 + mt_rand(-1000, 1000) / 10000,
                    'longitude' => 120.9 + mt_rand(-1000, 1000) / 10000,
                    'contact' => '+63 912 000 ' . str_pad($i, 4, '0', STR_PAD_LEFT),
                    'email' => 'clinic' . $i . '@example.com',
                    'services' => array_rand(array_flip($services), rand(2, 5)),
                    'operating_hours' => ($i % 10 === 0) ? '24/7' : 'Mon to Fri 8:00 AM - 5:00 PM',
                    'notes' => 'Auto-generated clinic for seeding.',
                    'approval_status' => ApprovalStatus::APPROVED,
                    'approved_by' => $user->id,
                    'approved_at' => now(),
                    'verified' => true,
                    'is_premium' => false,
                    'is_active' => true,
                ]);
            }
            $this->command->info('100 randomized clinics seeded!');
    }
}