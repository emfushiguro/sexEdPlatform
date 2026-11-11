<?php

namespace Tests\Feature\Parent\SubscriptionGifting;

use Tests\TestCase;

class GuardianSubscriptionGiftConcurrencyTest extends TestCase
{
    // Independent committed connections cannot run inside RefreshDatabase's outer transaction.
    // The helper owns its fixtures and removes those exact rows in a finally block.
    protected function connectionsToTransact(): array
    {
        return [];
    }

    public function test_concurrent_confirmations_apply_one_term_using_independent_database_connections(): void
    {
        $this->runScenario('confirm', 'CONCURRENCY OK');
    }

    public function test_competing_gift_and_self_purchases_reserve_only_one_checkout(): void
    {
        $this->runScenario('prepare', 'PREPARATION OK');
    }

    public function test_regular_cancellation_rechecks_after_concurrent_gift_fulfillment(): void
    {
        $this->runScenario('cancel', 'LIFECYCLE OK');
    }

    public function test_regular_expiration_rechecks_after_concurrent_gift_fulfillment(): void
    {
        $this->runScenario('reconcile', 'LIFECYCLE OK');
    }

    public function test_scheduled_expiration_rechecks_after_concurrent_gift_fulfillment(): void
    {
        $this->runScenario('expire', 'LIFECYCLE OK');
    }

    private function runScenario(string $scenario, string $expected): void
    {
        $process = proc_open([PHP_BINARY, base_path('tests/Support/GiftConcurrencyScenario.php'), $scenario],
            [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        $this->assertIsResource($process);
        fclose($pipes[0]);
        $output = stream_get_contents($pipes[1]);
        $error = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $this->assertSame(0, proc_close($process), $error."\n".$output);
        $this->assertStringContainsString($expected, $output);
    }
}
