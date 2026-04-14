<?php

namespace Tests\Feature\Rbac;

use Tests\TestCase;

class RbacResidualRoleCheckRegressionTest extends TestCase
{
    public function test_critical_auth_and_profile_flows_no_longer_use_role_string_authorization_checks(): void
    {
        $targets = [
            app_path('Http/Controllers/Auth/AuthenticatedSessionController.php'),
            app_path('Http/Controllers/Auth/AdminAuthController.php'),
            app_path('Http/Controllers/Auth/InstructorAuthController.php'),
            app_path('Http/Controllers/Learner/ProfileCompletionController.php'),
            app_path('Http/Controllers/Learner/InstructorProfileController.php'),
        ];

        foreach ($targets as $filePath) {
            $contents = file_get_contents($filePath);
            $this->assertIsString($contents);

            $this->assertStringNotContainsString("->role === 'instructor'", $contents);
            $this->assertStringNotContainsString('->role === \"instructor\"', $contents);
            $this->assertStringNotContainsString("->role === 'admin'", $contents);
            $this->assertStringNotContainsString('->role === \"admin\"', $contents);
        }

        $authenticatedSessionController = file_get_contents(app_path('Http/Controllers/Auth/AuthenticatedSessionController.php'));
        $this->assertStringContainsString("can('access admin panel')", (string) $authenticatedSessionController);
        $this->assertStringContainsString("can('access instructor panel')", (string) $authenticatedSessionController);

        $instructorProfileController = file_get_contents(app_path('Http/Controllers/Learner/InstructorProfileController.php'));
        $this->assertStringContainsString("can('access instructor panel')", (string) $instructorProfileController);
    }
}
