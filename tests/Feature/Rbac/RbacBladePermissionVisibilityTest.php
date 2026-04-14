<?php

namespace Tests\Feature\Rbac;

use Tests\TestCase;

class RbacBladePermissionVisibilityTest extends TestCase
{
    public function test_layouts_use_permission_based_visibility_checks(): void
    {
        $appLayout = file_get_contents(resource_path('views/layouts/app.blade.php'));
        $navigationLayout = file_get_contents(resource_path('views/layouts/navigation.blade.php'));
        $learnerHeaderLayout = file_get_contents(resource_path('views/layouts/learner-header.blade.php'));

        $this->assertIsString($appLayout);
        $this->assertIsString($navigationLayout);
        $this->assertIsString($learnerHeaderLayout);

        $this->assertStringNotContainsString('hasRole(', $appLayout);
        $this->assertStringContainsString("can('access learner platform')", $appLayout);
        $this->assertStringContainsString("can('access parent dashboard')", $appLayout);

        $this->assertStringNotContainsString('hasRole(', $navigationLayout);
        $this->assertStringContainsString("can('create modules')", $navigationLayout);
        $this->assertStringContainsString("can('view enrollments')", $navigationLayout);

        $this->assertStringNotContainsString('hasRole(', $learnerHeaderLayout);
        $this->assertStringContainsString("can('access instructor panel')", $learnerHeaderLayout);
    }
}
