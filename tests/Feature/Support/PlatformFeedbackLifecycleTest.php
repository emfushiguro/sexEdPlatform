<?php

namespace Tests\Feature\Support;

use App\Enums\PlatformFeedbackStatus;
use App\Models\PlatformFeedback;
use App\Models\User;
use App\Services\Support\PlatformFeedbackLifecycleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class PlatformFeedbackLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_ticket_cannot_skip_directly_to_resolved(): void
    {
        $ticket = PlatformFeedback::factory()->create(['status' => PlatformFeedbackStatus::New]);
        $reviewer = User::factory()->create(['role' => 'admin']);

        $this->expectException(HttpException::class);

        app(PlatformFeedbackLifecycleService::class)->transition(
            $ticket,
            PlatformFeedbackStatus::Resolved,
            $reviewer,
        );
    }

    public function test_closed_ticket_is_terminal(): void
    {
        $ticket = PlatformFeedback::factory()->create(['status' => PlatformFeedbackStatus::Closed]);
        $reviewer = User::factory()->create(['role' => 'admin']);

        $this->expectException(HttpException::class);

        app(PlatformFeedbackLifecycleService::class)->transition(
            $ticket,
            PlatformFeedbackStatus::Reviewed,
            $reviewer,
        );
    }

    public function test_withdrawn_ticket_is_terminal(): void
    {
        $ticket = PlatformFeedback::factory()->create(['status' => PlatformFeedbackStatus::Withdrawn]);
        $reviewer = User::factory()->create(['role' => 'admin']);

        $this->expectException(HttpException::class);

        app(PlatformFeedbackLifecycleService::class)->transition(
            $ticket,
            PlatformFeedbackStatus::Reviewed,
            $reviewer,
        );
    }

    public function test_resolved_ticket_can_reopen_to_reviewed_and_clears_resolution_time(): void
    {
        $ticket = PlatformFeedback::factory()->create([
            'status' => PlatformFeedbackStatus::Resolved,
            'resolved_at' => now(),
        ]);
        $reviewer = User::factory()->create(['role' => 'admin']);

        $updated = app(PlatformFeedbackLifecycleService::class)->transition(
            $ticket,
            PlatformFeedbackStatus::Reviewed,
            $reviewer,
        );

        $this->assertSame(PlatformFeedbackStatus::Reviewed, $updated->status);
        $this->assertNull($updated->resolved_at);
        $this->assertDatabaseHas('platform_feedback_histories', [
            'platform_feedback_id' => $ticket->id,
            'from_status' => PlatformFeedbackStatus::Resolved->value,
            'to_status' => PlatformFeedbackStatus::Reviewed->value,
        ]);
    }
}
