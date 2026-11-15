<?php

namespace Tests\Unit;

use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Facades\Blade;

class ActivationExpiryTest extends TestCase
{
    public function test_remaining_minutes_round_up_from_the_actual_expiration(): void
    {
        $this->travelTo(now()->startOfSecond());
        $html = Blade::render('<x-activation-expiry :expires-at="$expires" />', ['expires' => now()->addSeconds(61)]);
        $this->assertStringContainsString('Expires in 2 minutes.', $html);
        $html = Blade::render('<x-activation-expiry :expires-at="$expires" />', ['expires' => now()->addSeconds(20)]);
        $this->assertStringContainsString('Expires in 1 minute.', $html);
    }

    public function test_expired_or_missing_expiry_does_not_promise_a_valid_code(): void
    {
        foreach ([now()->subSecond(), null] as $expires) {
            $html = Blade::render('<x-activation-expiry :expires-at="$expires" />', compact('expires'));
            $this->assertStringContainsString('This code has expired. Ask for a new code.', $html);
        }
    }

    public function test_email_expiry_uses_readable_minutes_without_javascript(): void
    {
        $html = Blade::render('<x-activation-expiry :expires-at="$expires" :live="false" />', ['expires' => now()->addMinutes(15)]);
        $this->assertStringContainsString('Expires in 15 minutes.', $html);
        $this->assertStringNotContainsString('x-data', $html);
    }
}
