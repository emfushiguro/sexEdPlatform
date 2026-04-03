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

    public function test_notification_preference_toggle_contract_is_wired_and_persisted(): void
    {
        $storeContents = File::get(resource_path('js/chat/store.js'));
        $chatViewContents = File::get(resource_path('views/chat/index.blade.php'));

        $this->assertStringContainsString('chat.notifications.enabled', $storeContents);
        $this->assertStringContainsString('toggleNotificationsEnabled', $storeContents);
        $this->assertStringContainsString('localStorage.setItem', $storeContents);
        $this->assertStringContainsString('data-chat-notification-toggle', $chatViewContents);
    }

    public function test_active_focused_thread_suppresses_browser_notification_popup(): void
    {
        $storeContents = File::get(resource_path('js/chat/store.js'));

        $this->assertStringContainsString('shouldSuppressBrowserNotification', $storeContents);
        $this->assertStringContainsString("document.visibilityState === 'visible'", $storeContents);
        $this->assertStringContainsString('this.activeConversationId !== payload.conversation_id', $storeContents);
    }
}
