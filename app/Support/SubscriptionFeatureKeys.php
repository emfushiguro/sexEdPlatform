<?php

namespace App\Support;

final class SubscriptionFeatureKeys
{
    public const UNLIMITED_USERNAME_CHANGE = 'unlimited_username_change';
    public const UNLIMITED_QUIZ_SHIELDS = 'unlimited_quiz_shields';
    public const DOWNLOADABLE_CERTIFICATES = 'downloadable_certificates';

    // Backward-compatible aliases used across older views/tests.
    public const UNLIMITED_SHIELDS = self::UNLIMITED_QUIZ_SHIELDS;
    public const CERTIFICATE_PDF_DOWNLOAD_ACCESS = self::DOWNLOADABLE_CERTIFICATES;

    private function __construct()
    {
    }
}
