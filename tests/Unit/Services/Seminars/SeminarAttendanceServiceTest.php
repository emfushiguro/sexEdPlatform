<?php

namespace Tests\Unit\Services\Seminars;

use App\Services\Seminars\AgoraTokenService;
use App\Services\Seminars\SeminarAttendanceService;
use Mockery;
use Tests\UnitTestCase;

class SeminarAttendanceServiceTest extends UnitTestCase
{
    public function test_status_for_seconds_uses_configured_minimum_minutes(): void
    {
        config()->set('seminars.attendance.minimum_minutes', 5);

        $service = new SeminarAttendanceService(Mockery::mock(AgoraTokenService::class));

        $this->assertSame('joined', $service->statusForSeconds(120, true));
        $this->assertSame('left', $service->statusForSeconds(120, false));
        $this->assertSame('attended', $service->statusForSeconds(300, false));
    }
}
