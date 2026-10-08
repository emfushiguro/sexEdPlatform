<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use PHPUnit\Framework\TestCase;

class LogoutConfirmationMarkupTest extends TestCase
{
    public function test_end_user_logout_controls_use_the_shared_confirmation_modal(): void
    {
        $views = [
            'resources/views/layouts/navigation.blade.php',
            'resources/views/layouts/learner-navigation.blade.php',
            'resources/views/layouts/learner-sidebar.blade.php',
            'resources/views/layouts/learner-header.blade.php',
            'resources/views/layouts/instructor-app.blade.php',
            'resources/views/layouts/instructor-header.blade.php',
            'resources/views/layouts/admin.blade.php',
            'resources/views/layouts/connector-app.blade.php',
            'resources/views/auth/parent-verification-status.blade.php',
            'resources/views/auth/child-verification-status.blade.php',
            'resources/views/moderation/suspension-status.blade.php',
        ];

        $projectRoot = dirname(__DIR__, 3);

        foreach ($views as $view) {
            $contents = file_get_contents($projectRoot.DIRECTORY_SEPARATOR.$view);

            $this->assertStringContainsString(
                'data-logout-form',
                $contents,
                $view.' must opt into the shared logout confirmation modal.',
            );
            $this->assertStringNotContainsString(
                'window.confirm',
                $contents,
                $view.' must not use the browser-native confirmation prompt.',
            );
        }

        $modal = file_get_contents(
            $projectRoot.DIRECTORY_SEPARATOR.'resources/views/components/logout-confirmation-modal.blade.php',
        );

        $this->assertStringContainsString('data-logout-confirmation-modal', $modal);
        $this->assertStringContainsString('role="dialog"', $modal);
        $this->assertStringContainsString('Cancel', $modal);
        $this->assertStringContainsString('Log out', $modal);
        $this->assertMatchesRegularExpression(
            '/x-data="(?:[^"]*)"\s+x-show="isOpen"/s',
            $modal,
            'The Alpine data attribute must not be truncated by an inner HTML quote.',
        );
    }
}
