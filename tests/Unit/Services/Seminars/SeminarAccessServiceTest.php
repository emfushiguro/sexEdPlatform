<?php

namespace Tests\Unit\Services\Seminars;

use App\Enums\SeminarStatus;
use App\Enums\SeminarType;
use Tests\UnitTestCase;

class SeminarAccessServiceTest extends UnitTestCase
{
    public function test_seminar_config_exposes_expected_values(): void
    {
        $this->assertSame(['kids', 'teen', 'adult'], array_keys(config('seminars.learner_age_categories')));
        $this->assertSame(15, config('seminars.join_window_before_minutes'));
        $this->assertSame(5, config('seminars.attendance.minimum_minutes'));
        $this->assertSame('webinar', SeminarType::Webinar->value);
        $this->assertSame('published', SeminarStatus::Published->value);
        $this->assertTrue(array_key_exists('app_id', config('services.agora')));
        $this->assertTrue(array_key_exists('app_certificate', config('services.agora')));
        $this->assertTrue(array_key_exists('token_ttl_seconds', config('services.agora')));
    }
}
