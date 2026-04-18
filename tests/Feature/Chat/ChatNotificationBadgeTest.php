<?php

namespace Tests\Feature\Chat;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class ChatNotificationBadgeTest extends TestCase
{
    public function test_unread_badge_hooks_are_rendered_in_role_headers_and_layouts(): void
    {
        $adminLayout = File::get(resource_path('views/layouts/admin.blade.php'));
        $instructorHeader = File::get(resource_path('views/layouts/instructor-header.blade.php'));
        $learnerHeader = File::get(resource_path('views/layouts/learner-header.blade.php'));

        $this->assertStringContainsString('data-chat-unread-badge-role="admin"', $adminLayout);
        $this->assertStringContainsString('data-chat-unread-badge-role="instructor"', $instructorHeader);
        $this->assertStringContainsString('data-chat-unread-badge-role="learner"', $learnerHeader);
    }

    public function test_admin_chat_badge_hides_when_unread_count_is_zero(): void
    {
        $adminLayout = File::get(resource_path('views/layouts/admin.blade.php'));
        $storeContents = File::get(resource_path('js/chat/store.js'));

        $this->assertStringContainsString('data-chat-unread-badge', $adminLayout);
        $this->assertStringContainsString('badge.hidden = !hasUnread;', $storeContents);
        $this->assertStringContainsString("badge.textContent = hasUnread ? (totalUnread > 99 ? '99+' : String(totalUnread)) : '';", $storeContents);
    }

    public function test_notification_preference_toggle_contract_is_wired_and_persisted(): void
    {
        $storeContents = File::get(resource_path('js/chat/store.js'));
        $chatViewContents = File::get(resource_path('views/chat/index.blade.php'));

        $this->assertStringNotContainsString('chat.notifications.enabled', $storeContents);
        $this->assertStringNotContainsString('toggleNotificationsEnabled', $storeContents);
        $this->assertStringNotContainsString('data-chat-notification-toggle', $chatViewContents);
    }

    public function test_active_focused_thread_suppresses_browser_notification_popup(): void
    {
        $storeContents = File::get(resource_path('js/chat/store.js'));

        $this->assertStringNotContainsString('shouldSuppressBrowserNotification', $storeContents);
        $this->assertStringNotContainsString('maybeShowBrowserNotification', $storeContents);
        $this->assertStringNotContainsString('new Notification(', $storeContents);
    }

    public function test_browser_notification_popup_flow_is_removed(): void
    {
        $storeContents = File::get(resource_path('js/chat/store.js'));
        $chatViewContents = File::get(resource_path('views/chat/index.blade.php'));

        $this->assertStringNotContainsString('new Notification(', $storeContents);
        $this->assertStringNotContainsString('toggleNotificationsEnabled', $storeContents);
        $this->assertStringNotContainsString('data-chat-notification-toggle', $chatViewContents);
    }
}
