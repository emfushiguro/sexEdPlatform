<?php

namespace App\Support;

use Illuminate\Database\QueryException;

final class AccountEmail
{
    public static function normalize(string $email): string
    {
        return mb_strtolower(trim($email));
    }

    public static function fingerprint(string $email): string
    {
        return hash_hmac('sha256', self::normalize($email), self::fingerprintKey());
    }

    public static function isUsersEmailUniqueViolation(QueryException $exception): bool
    {
        $previous = $exception->getPrevious();
        $errorInfo = $exception->errorInfo ?? $previous?->errorInfo ?? [];
        $details = strtolower(implode(' ', array_map('strval', $errorInfo)));
        $message = strtolower($exception->getMessage().' '.($previous?->getMessage() ?? '').' '.$details);
        $code = (int) ($errorInfo[1] ?? $exception->getCode() ?? $previous?->getCode() ?? 0);

        return $code === 1062 && str_contains($message, 'users_email_unique');
    }

    private static function fingerprintKey(): string
    {
        if (function_exists('app') && app()->bound('config')) {
            return (string) config('app.key', 'account-email-fingerprint');
        }

        return (string) ($_ENV['APP_KEY'] ?? 'account-email-fingerprint');
    }
}
