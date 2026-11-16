<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use DateTimeInterface;
use Throwable;

final class LearnerAgePolicy
{
    public const ELIGIBLE_TEENAGER = 'eligible_teenager';

    public const ELIGIBLE_ADULT = 'eligible_adult';

    public const INELIGIBLE_UNDERAGE = 'ineligible_underage';

    public const INELIGIBLE_OVERAGE = 'ineligible_overage';

    public const UNCLASSIFIED = 'unclassified';

    public const CATEGORY_TEENAGERS = 'teens';

    public const CATEGORY_ADULTS = 'adults';

    public static function classify(DateTimeInterface|string|null $birthdate, DateTimeInterface|string|null $asOf = null): string
    {
        $age = self::age($birthdate, $asOf);

        return match (true) {
            $age === null => self::UNCLASSIFIED,
            $age < 10 => self::INELIGIBLE_UNDERAGE,
            $age <= 17 => self::ELIGIBLE_TEENAGER,
            default => self::ELIGIBLE_ADULT,
        };
    }

    public static function age(DateTimeInterface|string|null $birthdate, DateTimeInterface|string|null $asOf = null): ?int
    {
        $timezone = config('app.timezone');

        try {
            $dob = $birthdate instanceof DateTimeInterface
                ? CarbonImmutable::instance($birthdate)->setTimezone($timezone)->startOfDay()
                : CarbonImmutable::parse($birthdate, $timezone)->startOfDay();
            $today = $asOf instanceof DateTimeInterface
                ? CarbonImmutable::instance($asOf)->setTimezone($timezone)->startOfDay()
                : ($asOf === null
                    ? CarbonImmutable::now($timezone)->startOfDay()
                    : CarbonImmutable::parse($asOf, $timezone)->startOfDay());
        } catch (Throwable) {
            return null;
        }

        if ($birthdate === null || $dob->isAfter($today)) {
            return null;
        }

        return (int) $dob->diffInYears($today);
    }

    public static function category(DateTimeInterface|string|null $birthdate, DateTimeInterface|string|null $asOf = null): ?string
    {
        if (($age = self::age($birthdate, $asOf)) === null || $age < 10) {
            return null;
        }

        return match (self::classify($birthdate, $asOf)) {
            self::ELIGIBLE_TEENAGER => self::CATEGORY_TEENAGERS,
            self::ELIGIBLE_ADULT => self::CATEGORY_ADULTS,
            default => null,
        };
    }

    public static function requiresGuardian(DateTimeInterface|string|null $birthdate, DateTimeInterface|string|null $asOf = null): bool
    {
        return self::classify($birthdate, $asOf) === self::ELIGIBLE_TEENAGER;
    }

    public static function activeCategories(): array
    {
        return [self::CATEGORY_TEENAGERS, self::CATEGORY_ADULTS];
    }

    public static function normalizeActiveCategory(string $value): ?string
    {
        return match (strtolower(trim($value))) {
            'teen', 'teens' => self::CATEGORY_TEENAGERS,
            'adult', 'adults' => self::CATEGORY_ADULTS,
            default => null,
        };
    }
}
