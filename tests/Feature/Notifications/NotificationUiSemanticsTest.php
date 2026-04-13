<?php

namespace Tests\Feature\Notifications;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class NotificationUiSemanticsTest extends TestCase
{
    public function test_role_headers_use_exact_badge_count_with_nine_plus_cap(): void
    {
        $learnerHeader = File::get(resource_path('views/layouts/learner-header.blade.php'));
        $instructorHeader = File::get(resource_path('views/layouts/instructor-header.blade.php'));
        $adminLayout = File::get(resource_path('views/layouts/admin.blade.php'));

        $this->assertStringContainsString("{{ \$unreadCount > 9 ? '9+' : \$unreadCount }}", $learnerHeader);
        $this->assertStringContainsString("{{ \$notificationBadgeCount > 9 ? '9+' : \$notificationBadgeCount }}", $instructorHeader);
        $this->assertStringContainsString("{{ \$adminNotifications['unread_count'] > 9 ? '9+' : \$adminNotifications['unread_count'] }}", $adminLayout);
    }

    public function test_unread_indicators_use_red_semantics_across_role_headers(): void
    {
        $learnerHeader = File::get(resource_path('views/layouts/learner-header.blade.php'));
        $instructorHeader = File::get(resource_path('views/layouts/instructor-header.blade.php'));
        $adminLayout = File::get(resource_path('views/layouts/admin.blade.php'));

        $this->assertStringContainsString('bg-red-500', $learnerHeader);
        $this->assertStringContainsString('bg-red-500', $instructorHeader);
        $this->assertStringContainsString('bg-red-500', $adminLayout);
    }

    public function test_success_and_failure_severity_classes_exist_for_notification_items(): void
    {
        $learnerHeader = File::get(resource_path('views/layouts/learner-header.blade.php'));
        $instructorHeader = File::get(resource_path('views/layouts/instructor-header.blade.php'));
        $adminLayout = File::get(resource_path('views/layouts/admin.blade.php'));

        $this->assertStringContainsString("'success' => 'bg-emerald-100 text-emerald-700'", $learnerHeader);
        $this->assertStringContainsString("'error' => 'bg-rose-100 text-rose-700'", $learnerHeader);

        $this->assertStringContainsString("'success' => 'border-l-4 border-emerald-500'", $instructorHeader);
        $this->assertStringContainsString("'error' => 'border-l-4 border-rose-500'", $instructorHeader);

        $this->assertStringContainsString("'success' => 'border-l-4 border-emerald-500'", $adminLayout);
        $this->assertStringContainsString("'error' => 'border-l-4 border-rose-500'", $adminLayout);
    }
}
