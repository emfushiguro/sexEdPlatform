<?php

namespace Tests\Feature\Auth;

use App\Models\AdminMfaChallenge;
use App\Models\User;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdminMfaChallengeTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_challenge_schema_uses_uuid_and_datetime_casts(): void
    {
        $this->assertTrue(Schema::hasColumns('admin_mfa_challenges', [
            'id',
            'user_id',
            'code_hash',
            'session_fingerprint',
            'credential_fingerprint',
            'attempt_count',
            'resend_count',
            'code_expires_at',
            'login_expires_at',
            'resend_available_at',
            'delivered_at',
            'delivery_failed_at',
            'consumed_at',
            'cancelled_at',
            'locked_at',
        ]));

        $challenge = AdminMfaChallenge::query()->create($this->attributes(User::factory()->create()));

        $this->assertIsString($challenge->id);
        $this->assertSame(36, strlen($challenge->id));
        $this->assertTrue($challenge->code_expires_at->isFuture());
        $this->assertTrue($challenge->user->is($challenge->user()->firstOrFail()));
    }

    public function test_required_challenge_times_use_datetime_columns_without_implicit_defaults(): void
    {
        foreach (['code_expires_at', 'login_expires_at', 'resend_available_at'] as $column) {
            $this->assertSame('datetime', Schema::getColumnType('admin_mfa_challenges', $column));
        }
    }

    public function test_prunable_query_keeps_terminal_rows_for_twenty_four_hours(): void
    {
        Carbon::setTestNow('2026-11-11 10:00:00');
        $user = User::factory()->create();

        $old = AdminMfaChallenge::query()->create($this->attributes($user, [
            'login_expires_at' => now()->subHours(25),
            'consumed_at' => now()->subHours(25),
        ]));
        $recent = AdminMfaChallenge::query()->create($this->attributes($user, [
            'login_expires_at' => now()->subHours(23),
            'consumed_at' => now()->subHours(23),
        ]));
        $active = AdminMfaChallenge::query()->create($this->attributes($user));

        $ids = (new AdminMfaChallenge)->prunable()->pluck('id')->all();

        $this->assertContains($old->id, $ids);
        $this->assertNotContains($recent->id, $ids);
        $this->assertNotContains($active->id, $ids);
    }

    public function test_model_pruning_is_scheduled(): void
    {
        $commands = collect(app(Schedule::class)->events())
            ->map(fn ($event): string => (string) ($event->command ?? ''));

        $this->assertTrue(
            $commands->contains(fn (string $command): bool => str_contains($command, 'model:prune')),
            'Expected model:prune to be registered in scheduler.',
        );
    }

    private function attributes(User $user, array $overrides = []): array
    {
        return array_replace([
            'user_id' => $user->id,
            'code_hash' => 'hashed-code',
            'session_fingerprint' => str_repeat('a', 64),
            'credential_fingerprint' => str_repeat('b', 64),
            'attempt_count' => 0,
            'resend_count' => 0,
            'code_expires_at' => now()->addMinutes(5),
            'login_expires_at' => now()->addMinutes(15),
            'resend_available_at' => now()->addMinute(),
            'delivered_at' => now(),
        ], $overrides);
    }
}
