<?php

namespace Tests\Feature\Chat;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class RealtimeBootstrapTest extends TestCase
{
    public function test_channel_routes_file_loads_without_runtime_error(): void
    {
        $this->assertFileExists(base_path('routes/channels.php'));

        $loaded = (static function (string $channelsPath): bool {
            require $channelsPath;

            return true;
        })(base_path('routes/channels.php'));

        $this->assertTrue($loaded);
    }

    public function test_broadcasting_config_contains_reverb_connection(): void
    {
        $this->assertFileExists(config_path('broadcasting.php'));

        $config = require config_path('broadcasting.php');

        $this->assertIsArray($config);
        $this->assertArrayHasKey('connections', $config);
        $this->assertArrayHasKey('reverb', $config['connections']);
    }

    public function test_bootstrap_app_registers_channels_route_file(): void
    {
        $this->assertFileExists(base_path('bootstrap/app.php'));

        $bootstrapContents = File::get(base_path('bootstrap/app.php'));

        $this->assertStringContainsString('channels: __DIR__.\'/../routes/channels.php\'', $bootstrapContents);
    }
}
