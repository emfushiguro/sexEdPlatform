<?php

namespace Tests\Unit\Services;

use App\Models\PersonaInquiry;
use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Illuminate\Foundation\Application;
use PHPUnit\Framework\TestCase;

class PersonaDemoEnvironmentTest extends TestCase
{
    public function test_demo_guard_requires_the_matching_opt_in_and_sandbox(): void
    {
        $previous = Container::getInstance();
        try {
            $app = new Application;
            foreach ([
                ['production', true, false, true, 'sandbox', true],
                ['production', false, true, true, 'sandbox', false],
                ['production', true, true, false, 'sandbox', false],
                ['production', true, true, true, 'production', false],
                ['staging', true, false, true, 'sandbox', true],
                ['local', true, false, true, 'sandbox', false],
                ['local', false, true, true, 'sandbox', true],
                ['testing', true, true, true, 'sandbox', false],
            ] as [$environment, $demo, $local, $enabled, $personaEnvironment, $expected]) {
                $app['env'] = $environment;
                $app->instance('config', new Repository(['persona' => [
                    'enabled' => $enabled,
                    'environment' => $personaEnvironment,
                    'local_manual_review' => $local,
                    'demo_manual_review' => $demo,
                ]]));
                $this->assertSame($expected, PersonaInquiry::localManualReviewEnabled(), $environment);
            }
        } finally {
            Container::setInstance($previous);
        }
    }
}
