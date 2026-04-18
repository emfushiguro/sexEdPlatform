<?php

namespace App\Support;

final class SubscriptionFeatureKeys
{
    public const UNLIMITED_USERNAME_CHANGE = 'unlimited_username_change';
    public const UNLIMITED_QUIZ_SHIELDS = 'unlimited_quiz_shields';
    public const DOWNLOADABLE_CERTIFICATES = 'downloadable_certificates';
    public const TEXT_TRANSLATOR = 'text_translator';
    public const VOICE_SPEECH_TRANSLATOR = 'voice_speech_translator';
    public const INSTRUCTOR_PUBLISHED_MODULES_LIMIT = 'instructor_published_modules_limit';
    public const INSTRUCTOR_MAX_LEARNERS_PER_FREE_MODULE = 'instructor_max_learners_per_free_module';
    public const INSTRUCTOR_MAX_LEARNERS_PER_PAID_MODULE = 'instructor_max_learners_per_paid_module';
    public const INSTRUCTOR_CAN_PUBLISH_PAID_MODULES = 'instructor_can_publish_paid_modules';
    public const INSTRUCTOR_CAN_RECEIVE_PAID_ENROLLMENTS = 'instructor_can_receive_paid_enrollments';
    public const INSTRUCTOR_CAN_VIEW_EARNINGS = 'instructor_can_view_earnings';

    // Backward-compatible aliases used across older views/tests.
    public const UNLIMITED_SHIELDS = self::UNLIMITED_QUIZ_SHIELDS;
    public const CERTIFICATE_PDF_DOWNLOAD_ACCESS = self::DOWNLOADABLE_CERTIFICATES;
    public const TEXT_TRANSLATION = self::TEXT_TRANSLATOR;
    public const VOICE_TRANSLATOR = self::VOICE_SPEECH_TRANSLATOR;
    public const MAX_PUBLISHED_MODULES = self::INSTRUCTOR_PUBLISHED_MODULES_LIMIT;
    public const MAX_LEARNERS_PER_FREE_MODULE = self::INSTRUCTOR_MAX_LEARNERS_PER_FREE_MODULE;
    public const PAID_MODULE_LEARNER_CAP = self::INSTRUCTOR_MAX_LEARNERS_PER_PAID_MODULE;
    public const CAN_PUBLISH_PAID_MODULE = self::INSTRUCTOR_CAN_PUBLISH_PAID_MODULES;
    public const CAN_RECEIVE_PAID_ENROLLMENTS = self::INSTRUCTOR_CAN_RECEIVE_PAID_ENROLLMENTS;
    public const CAN_VIEW_EARNINGS = self::INSTRUCTOR_CAN_VIEW_EARNINGS;

    private function __construct()
    {
    }
}
