<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $admin = Role::findOrCreate('admin', 'web');
        $instructor = Role::findOrCreate('instructor', 'web');
        $learner = Role::findOrCreate('learner', 'web');
        $parent = Role::findOrCreate('parent', 'web');

        $admin->syncPermissions(Permission::query()->pluck('name')->all());

        $instructor->syncPermissions([
            'access instructor panel',
            'access chat',
            'start conversations',
            'send messages',
            'edit own messages',
            'delete own messages',
            'report messages',
            'manage message requests',
            'view modules',
            'create modules',
            'edit modules',
            'delete modules',
            'submit modules',
            'resubmit modules',
            'withdraw module submissions',
            'view lessons',
            'create lessons',
            'edit lessons',
            'delete lessons',
            'reorder lessons',
            'move lessons',
            'create lesson topics',
            'edit lesson topics',
            'delete lesson topics',
            'reorder lesson topics',
            'view quizzes',
            'create quizzes',
            'edit quizzes',
            'delete quizzes',
            'manage quiz questions',
            'import quiz questions',
            'view assessment logs',
            'view quiz results',
            'view users',
            'view learners',
            'view enrollments',
            'manage enrollments',
            'approve enrollments',
            'reject enrollments',
            'view own financial reports',
            'export own financial reports',
            'view analytics',
            'view activity logs',
        ]);

        $learner->syncPermissions([
            'access learner platform',
            'access chat',
            'start conversations',
            'send messages',
            'edit own messages',
            'delete own messages',
            'report messages',
            'view modules',
            'view lessons',
            'view quizzes',
            'take quizzes',
            'view quiz results',
            'view certificates',
            'generate certificates',
            'download certificates',
            'enroll modules',
            'purchase modules',
        ]);

        $parent->syncPermissions([
            'access parent dashboard',
            'view learner progress',
            'view learner enrollments',
        ]);

        // Compatibility roles still used by legacy account types.
        $counselor = Role::findOrCreate('counselor', 'web');
        $counselor->syncPermissions([
            'request consultation',
            'manage consultations',
            'approve consultations',
        ]);

        $clinic = Role::findOrCreate('clinic', 'web');
        $clinic->syncPermissions([
            'manage clinics',
            'approve clinics',
        ]);

        $organization = Role::findOrCreate('organization', 'web');
        $organization->syncPermissions([
            'view seminars',
            'create seminars',
            'edit seminars',
            'delete seminars',
            'register seminars',
        ]);
    }
}
