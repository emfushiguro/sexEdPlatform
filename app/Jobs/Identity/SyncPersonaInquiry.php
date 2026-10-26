<?php

namespace App\Jobs\Identity;

use App\Models\PersonaInquiry;
use App\Models\PersonaWebhookEvent;
use App\Services\Persona\PersonaInquirySynchronizer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Throwable;

class SyncPersonaInquiry implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;

    public function __construct(public int $eventId) {}

    /** @return array<int, int> */
    public function backoff(): array
    {
        return [30, 120, 600, 1800];
    }

    public function handle(PersonaInquirySynchronizer $synchronizer): void
    {
        $event = DB::transaction(function (): ?PersonaWebhookEvent {
            $event = PersonaWebhookEvent::query()->whereKey($this->eventId)->lockForUpdate()->first();
            if (! $event || $event->processing_status === PersonaWebhookEvent::STATUS_PROCESSED) {
                return null;
            }

            return $event;
        });

        if (! $event) {
            return;
        }

        $attempt = PersonaInquiry::query()
            ->where('persona_inquiry_id', $event->persona_inquiry_id)
            ->first();
        if (! $attempt) {
            $this->markFailed('local_association_missing');

            return;
        }

        try {
            $synchronizer->sync($attempt, $event->event_created_at?->toImmutable());
        } catch (Throwable $exception) {
            $this->markFailed($exception->getCode() === 503 ? 'provider_unavailable' : 'provider_error');
            throw $exception;
        }

        DB::transaction(function (): void {
            $locked = PersonaWebhookEvent::query()->whereKey($this->eventId)->lockForUpdate()->first();
            if ($locked && $locked->processing_status !== PersonaWebhookEvent::STATUS_PROCESSED) {
                $locked->forceFill([
                    'processing_status' => PersonaWebhookEvent::STATUS_PROCESSED,
                    'processed_at' => now(),
                    'failed_at' => null,
                    'failure_category' => null,
                ])->save();
            }
        });
    }

    private function markFailed(string $category): void
    {
        PersonaWebhookEvent::query()->whereKey($this->eventId)->update([
            'processing_status' => PersonaWebhookEvent::STATUS_FAILED,
            'failed_at' => now(),
            'failure_category' => $category,
        ]);
    }
}
