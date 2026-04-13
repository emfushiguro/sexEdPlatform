<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::updateOrCreate(
            ['email' => 'admin@sexed.platform'],
            [
                'name' => 'System Administrator',
                'password' => Hash::make('admin123'), // Change this in production
                'email_verified_at' => now(),
                'role' => 'admin',
                'status' => 'active',
                'verified' => true,
            ]
        );

        if (!$admin->hasRole('admin')) {
            $admin->assignRole('admin');
        }

        UserProfile::updateOrCreate(
            ['user_id' => $admin->id],
            [
                'bio' => 'System Administrator Account',
                'birthdate' => '1990-01-01',
                'gender' => 'prefer_not_to_say',
                'location' => 'Cavite, Philippines',
                'contact' => '09123456789',
            ]
        );

        $this->command->info('Admin user seeded successfully!');
        $this->command->info('Email: admin@sexed.platform');
        $this->command->info('Password: admin123');
    }
}
