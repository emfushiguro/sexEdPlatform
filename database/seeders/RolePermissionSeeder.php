<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions for each module
        $permissions = [
            // Module Management
            'view modules',
            'create modules',
            'edit modules',
            'delete modules',
            'publish modules',

            // Lesson Management
            'view lessons',
            'create lessons',
            'edit lessons',
            'delete lessons',

            // Quiz Management
            'view quizzes',
            'create quizzes',
            'edit quizzes',
            'delete quizzes',
            'take quizzes',
            'view quiz results',

            // User Management
            'view users',
            'create users',
            'edit users',
            'delete users',
            'manage roles',

            // Seminar Management
            'view seminars',
            'create seminars',
            'edit seminars',
            'delete seminars',
            'register seminars',

            // Consultation Management
            'request consultation',
            'manage consultations',
            'approve consultations',

            // Clinic Management
            'manage clinics',
            'approve clinics',

            // Counselor Management
            'manage counselors',
            'approve counselors',

            // Certificate Management
            'view certificates',
            'generate certificates',
            'download certificates',

            // Premium Features
            'download modules',
            'unlimited quiz attempts',
            'access premium content',

            // Analytics & Reports
            'view analytics',
            'view activity logs',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // Create Roles and assign permissions

        // Admin Role - Full access
        $adminRole = Role::create(['name' => 'admin']);
        $adminRole->givePermissionTo(Permission::all());

        // Learner Role
        $learnerRole = Role::create(['name' => 'learner']);
        $learnerRole->givePermissionTo([
            'view modules',
            'view lessons',
            'view quizzes',
            'take quizzes',
            'view quiz results',
            'view seminars',
            'register seminars',
            'request consultation',
            'view certificates',
        ]);

        // Counselor Role
        $counselorRole = Role::create(['name' => 'counselor']);
        $counselorRole->givePermissionTo([
            'view modules',
            'view lessons',
            'manage consultations',
            'approve consultations',
        ]);

        // Clinic Role
        $clinicRole = Role::create(['name' => 'clinic']);
        $clinicRole->givePermissionTo([
            'manage clinics',
        ]);

        // Organization Role
        $organizationRole = Role::create(['name' => 'organization']);
        $organizationRole->givePermissionTo([
            'view seminars',
            'create seminars',
            'edit seminars',
            'delete seminars',
        ]);

        $this->command->info('Roles and permissions created successfully!');
    }
}
