<?php

namespace Tests\Unit;

use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;

class LegalBackLinkTest extends TestCase
{
    public function test_explicit_local_return_destination_and_unsafe_destination_fallback(): void
    {
        foreach (['/register?step=2' => '/register?step=2', 'https://outside.test' => '/', '//outside.test' => '/', '/%5coutside.test' => '/'] as $return => $expected) {
            $this->app->instance('request', Request::create('/privacy-policy', 'GET', ['return' => $return]));
            $html = Blade::render('<x-legal-back-link />');
            $this->assertStringContainsString('href="'.$expected.'"', $html);
            $this->assertStringNotContainsString('history.back', $html);
        }
    }
}
