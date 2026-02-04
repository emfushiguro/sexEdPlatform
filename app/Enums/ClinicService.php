<?php

namespace App\Enums;

enum ClinicService: string
{
    case HIV_TESTING = 'hiv_testing';
    case STD_SCREENING = 'std_screening';
    case COUNSELING = 'counseling';
    case CONTRACEPTION = 'contraception';
    case PREGNANCY_TEST = 'pregnancy_test';
    case FAMILY_PLANNING = 'family_planning';
    case HEALTH_EDUCATION = 'health_education';
    case VACCINATION = 'vaccination';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function options(): array
    {
        $options = [];
        foreach (self::cases() as $service) {
            $options[$service->value] = $service->getDisplayName();
        }
        return $options;
    }

    public function getDisplayName(): string
    {
        return match($this) {
            self::HIV_TESTING => 'HIV Testing',
            self::STD_SCREENING => 'STD Screening',
            self::COUNSELING => 'Counseling Services',
            self::CONTRACEPTION => 'Contraception',
            self::PREGNANCY_TEST => 'Pregnancy Testing',
            self::FAMILY_PLANNING => 'Family Planning',
            self::HEALTH_EDUCATION => 'Health Education',
            self::VACCINATION => 'Vaccination Services',
        };
    }

    public function getDescription(): string
    {
        return match($this) {
            self::HIV_TESTING => 'Confidential HIV testing and consultation',
            self::STD_SCREENING => 'Sexually transmitted disease screening',
            self::COUNSELING => 'Professional health and wellness counseling',
            self::CONTRACEPTION => 'Birth control and contraceptive services',
            self::PREGNANCY_TEST => 'Pregnancy testing and early care',
            self::FAMILY_PLANNING => 'Family planning and reproductive health',
            self::HEALTH_EDUCATION => 'Health education and awareness programs',
            self::VACCINATION => 'Immunization and vaccination services',
        };
    }

    public function getCategory(): string
    {
        return match($this) {
            self::HIV_TESTING, self::STD_SCREENING => 'Sexual Health',
            self::COUNSELING, self::HEALTH_EDUCATION => 'Mental Health & Education',
            self::CONTRACEPTION, self::FAMILY_PLANNING, self::PREGNANCY_TEST => 'Reproductive Health',
            self::VACCINATION => 'Preventive Care',
        };
    }
}