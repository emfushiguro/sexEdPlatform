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
            'livestream_status' => 'live',
            'livestream_started_at' => now(),
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

    public function test_real_agora_credentials_generate_access_token2(): void
    {
        config()->set('services.agora.app_id', str_repeat('a', 32));
        config()->set('services.agora.app_certificate', str_repeat('b', 32));
        config()->set('services.agora.token_ttl_seconds', 900);

        $service = new class(Mockery::mock(ConnectorAccessService::class), Mockery::mock(SeminarRegistrationService::class), Mockery::mock(SeminarSpeakerService::class)) extends AgoraTokenService
        {
            public function token(string $channel, int $uid, string $role): string
            {
                return $this->buildRtcToken($channel, $uid, $role, now()->addMinutes(15));
            }
        };

        $token = $service->token('seminar-channel', 55, 'host');
        $this->assertStringStartsWith('007', $token);

        $payload = zlib_decode(base64_decode(substr($token, 3)));
        $offset = 0;
        $readUint16 = function () use (&$payload, &$offset): int {
            $value = unpack('v', substr($payload, $offset, 2))[1];
            $offset += 2;

            return $value;
        };
        $readUint32 = function () use (&$payload, &$offset): int {
            $value = unpack('V', substr($payload, $offset, 4))[1];
            $offset += 4;

            return $value;
        };
        $skipString = function () use (&$payload, &$offset, $readUint16): void {
            $offset += $readUint16();
        };

        $skipString(); // signature
        $skipString(); // app id
        $readUint32(); // issued at
        $this->assertLessThanOrEqual(900, $readUint32());
        $readUint32(); // salt
        $this->assertSame(1, $readUint16()); // services
        $this->assertSame(1, $readUint16()); // RTC service
        $privilegeCount = $readUint16();
        $this->assertSame(4, $privilegeCount);
        for ($i = 0; $i < $privilegeCount; $i++) {
            $readUint16();
            $this->assertLessThanOrEqual(900, $readUint32());
        }
    }
}
