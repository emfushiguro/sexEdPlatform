<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Create instructor role
        $instructorRole = Role::firstOrCreate(['name' => 'instructor', 'guard_name' => 'web']);
        
        // Create admin role if it doesn't exist
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        
        // Define instructor permissions (content management)
        $instructorPermissions = [
            // Module Management
            'view-modules',
            'create-modules',
            'edit-modules',
            'delete-modules',
            'publish-modules',
            
            // Lesson Management
            'view-lessons',
            'create-lessons',
            'edit-lessons',
            'delete-lessons',
            'publish-lessons',
            
            // Topic Management
            'view-topics',
            'create-topics',
            'edit-topics',
            'delete-topics',
            
            // Quiz Management
            'view-quizzes',
            'create-quizzes',
            'edit-quizzes',
            'delete-quizzes',
            
            // Learner Management
            'view-learners',
            'view-learner-progress',
            'grade-assessments',
            'approve-module-access',
            
            // Content Analytics
            'view-content-analytics',
            'export-learner-data',
        ];
        
        // Define admin permissions (system management)
        $adminPermissions = [
            // All instructor permissions plus:
            'manage-subscriptions',
            'create-subscription-plans',
            'edit-subscription-plans',
            'delete-subscription-plans',
            
            'manage-payments',
            'view-all-payments',
            'process-refunds',
            
            'manage-users',
            'create-instructors',
            'edit-users',
            'delete-users',
            'assign-roles',
            
            'manage-platform-settings',
            'view-system-logs',
            'manage-counselors',
            'manage-organizations',
            
            'view-system-analytics',
            'moderate-content',
        ];
        
        // Create permissions
        foreach ($instructorPermissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }
        
        foreach ($adminPermissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }
        
        // Assign permissions to roles
        $instructorRole->syncPermissions($instructorPermissions);
        $adminRole->syncPermissions(array_merge($instructorPermissions, $adminPermissions));
        
        // Migrate existing admin users to instructor role
        // EXCEPT the first user (super admin) - keep them as admin
        $existingAdminRole = Role::where('name', 'admin')->first();
        if ($existingAdminRole) {
            $adminUsers = User::role('admin')->get();
            
            foreach ($adminUsers as $user) {
                if ($user->id === 1) {
                    // Keep first user as admin (super admin)
                    continue;
                } else {
                    // Migrate other admins to instructor
                    $user->removeRole('admin');
                    $user->assignRole('instructor');
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Migrate instructors back to admin
        $instructors = User::role('instructor')->get();
        foreach ($instructors as $instructor) {
            $instructor->removeRole('instructor');
            $instructor->assignRole('admin');
        }
        
        // Delete instructor role and permissions
        $instructorRole = Role::where('name', 'instructor')->first();
        if ($instructorRole) {
            $instructorRole->delete();
        }
        
        // Delete instructor-specific permissions
        $permissionsToDelete = [
            'view-modules', 'create-modules', 'edit-modules', 'delete-modules', 'publish-modules',
            'view-lessons', 'create-lessons', 'edit-lessons', 'delete-lessons', 'publish-lessons',
            'view-topics', 'create-topics', 'edit-topics', 'delete-topics',
            'view-quizzes', 'create-quizzes', 'edit-quizzes', 'delete-quizzes',
            'view-learners', 'view-learner-progress', 'grade-assessments',
            'view-content-analytics', 'export-learner-data',
        ];
        
        Permission::whereIn('name', $permissionsToDelete)->delete();
    }
};
