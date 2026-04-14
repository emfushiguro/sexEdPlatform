<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class InstructorUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $instructor = User::updateOrCreate(
            ['email' => 'instructor@sexed.platform'],
            [
                'name' => 'Default Instructor',
                'first_name' => 'Default',
                'last_name' => 'Instructor',
                'email' => 'instructor@sexed.platform',
                'password' => Hash::make('instructor123'),
                'email_verified_at' => now(),
                'role' => 'instructor',
                'status' => 'active',
                'verified' => true,
            ]
        );

        if (!$instructor->hasRole('instructor')) {
            $instructor->assignRole('instructor');
        }

        UserProfile::updateOrCreate(
            ['user_id' => $instructor->id],
            [
                'bio' => 'Default Instructor Account',
                'birthdate' => '1992-01-01',
                'gender' => 'prefer_not_to_say',
                'location' => 'Cavite, Philippines',
                'contact' => '09123456780',
            ]
        );

        if ($this->command) {
            $this->command->info('Instructor user seeded successfully!');
            $this->command->info('Email: instructor@sexed.platform');
            $this->command->info('Password: instructor123');
        }
    }
}