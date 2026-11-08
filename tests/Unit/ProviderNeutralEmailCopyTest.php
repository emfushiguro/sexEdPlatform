<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ProviderNeutralEmailCopyTest extends TestCase
{
    #[DataProvider('activeEmailCopyFiles')]
    public function test_active_email_copy_does_not_claim_gmail_is_required(string $relativePath): void
    {
        $contents = strtolower((string) file_get_contents(dirname(__DIR__, 2).'/'.$relativePath));

        foreach ([
            'only gmail',
            'only accept gmail',
            'must be a gmail',
            'valid gmail address',
            'registered gmail address',
            'your gmail address',
            'gmail requirement',
            'yourname@gmail.com',
            'guardian@gmail.com',
            'your@gmail.com',
        ] as $forbiddenFragment) {
            self::assertStringNotContainsString($forbiddenFragment, $contents, $relativePath);
        }
    }

    public static function activeEmailCopyFiles(): array
    {
        return [
            'learner registration' => ['resources/views/auth/register-account.blade.php'],
            'guardian registration' => ['resources/views/auth/parent-register-account.blade.php'],
            'forgot password' => ['resources/views/auth/forgot-password.blade.php'],
            'reset password' => ['resources/views/auth/reset-password.blade.php'],
            'admin profile' => ['resources/views/admin/profile/show.blade.php'],
            'verification prompt' => ['resources/views/auth/verify-email.blade.php'],
            'guardian verification status' => ['resources/views/auth/parent-verification-status.blade.php'],
            'legal modal' => ['resources/views/components/legal-modals.blade.php'],
            'privacy policy' => ['resources/views/legal/privacy.blade.php'],
            'terms of service' => ['resources/views/legal/terms.blade.php'],
        ];
    }

    public function test_active_email_forms_use_provider_neutral_examples(): void
    {
        foreach ([
            'resources/views/auth/register-account.blade.php',
            'resources/views/auth/parent-register-account.blade.php',
            'resources/views/auth/forgot-password.blade.php',
            'resources/views/auth/reset-password.blade.php',
            'resources/views/admin/profile/show.blade.php',
        ] as $relativePath) {
            $contents = (string) file_get_contents(dirname(__DIR__, 2).'/'.$relativePath);

            self::assertStringContainsString('name@example.com', $contents, $relativePath);
        }
    }

    public function test_active_policy_copy_describes_valid_email_addresses(): void
    {
        foreach ([
            'resources/views/components/legal-modals.blade.php',
            'resources/views/legal/privacy.blade.php',
            'resources/views/legal/terms.blade.php',
        ] as $relativePath) {
            $contents = strtolower((string) file_get_contents(dirname(__DIR__, 2).'/'.$relativePath));

            self::assertStringContainsString('valid email address', $contents, $relativePath);
        }
    }

    public function test_registration_forms_warn_about_temporary_inbox_recovery_risk(): void
    {
        foreach ([
            'resources/views/auth/register-account.blade.php',
            'resources/views/auth/parent-register-account.blade.php',
        ] as $relativePath) {
            $contents = (string) file_get_contents(dirname(__DIR__, 2).'/'.$relativePath);

            self::assertStringContainsString(
                'Temporary inboxes may expire and prevent future password recovery.',
                $contents,
                $relativePath,
            );
        }
    }

    public function test_email_faq_explicitly_accepts_temporary_addresses(): void
    {
        $contents = strtolower((string) file_get_contents(
            dirname(__DIR__, 2).'/resources/views/components/legal-modals.blade.php'
        ));

        self::assertStringContainsString('temporary', $contents);
        self::assertStringContainsString('disposable', $contents);
        self::assertStringContainsString('password recovery', $contents);
    }
}
