<?php

namespace Tests\Unit\Services\Seminars;

use App\Models\Seminar;
use App\Models\User;
use App\Services\Connectors\ConnectorAccessService;
use App\Services\Seminars\AgoraTokenService;
use App\Services\Seminars\SeminarRegistrationService;
use App\Services\Seminars\SeminarSpeakerService;
use Mockery;
use Tests\UnitTestCase;

class AgoraTokenServiceTest extends UnitTestCase
{
    public function test_agora_uid_is_deterministic_from_user_id(): void
    {
        $service = new AgoraTokenService(
            Mockery::mock(ConnectorAccessService::class),
            Mockery::mock(SeminarRegistrationService::class),
            Mockery::mock(SeminarSpeakerService::class),
        );

        $user = new User(['name' => 'Learner']);
        $user->id = 12345;

        $this->assertSame(12345, $service->agoraUidFor($user));
    }

    public function test_token_payload_uses_mocked_builder_and_denies_publish_for_audience(): void
    {
        config()->set('services.agora.app_id', 'agora-app');
        config()->set('services.agora.app_certificate', 'agora-secret');
        config()->set('services.agora.token_ttl_seconds', 900);

        $user = new User(['name' => 'Learner']);
        $user->id = 55;
        $seminar = new Seminar([
            'type' => 'webinar',
            'status' => 'published',
            'starts_at' => now()->subMinutes(5),
            'ends_at' => now()->addMinutes(30),
            'livestream_channel' => 'seminar-channel',
        ]);

        $service = Mockery::mock(AgoraTokenService::class, [
            Mockery::mock(ConnectorAccessService::class),
            Mockery::mock(SeminarRegistrationService::class),
            Mockery::mock(SeminarSpeakerService::class),
        ])->makePartial()->shouldAllowMockingProtectedMethods();

        $service->shouldReceive('canJoinAsAudience')->once()->with($user, $seminar)->andReturn(true);
        $service->shouldReceive('buildRtcToken')->once()->andReturn('mocked-token');

        $payload = $service->tokenFor($user, $seminar, 'audience');

        $this->assertSame('agora-app', $payload['app_id']);
        $this->assertSame('seminar-channel', $payload['channel']);
        $this->assertSame(55, $payload['uid']);
        $this->assertSame('audience', $payload['role']);
        $this->assertFalse($payload['can_publish']);
        $this->assertSame('mocked-token', $payload['token']);
    }
}
