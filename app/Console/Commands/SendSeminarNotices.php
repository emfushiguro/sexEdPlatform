<?php

namespace App\Console\Commands;

use App\Models\Seminar;
use App\Services\Seminars\SeminarNoticeService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendSeminarNotices extends Command
{
    protected $signature = 'seminars:send-notices';

    protected $description = 'Send due educational event reminders and link availability notices';

    public function __construct(private readonly SeminarNoticeService $notices)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $now = now();

        Seminar::query()
            ->where('status', 'published')
            ->whereNotNull('starts_at')
            ->whereBetween('starts_at', [$now, $now->copy()->addHour()])
            ->where(function ($query): void {
                $query->whereNull('reminder_sent_for_starts_at')
                    ->orWhereColumn('reminder_sent_for_starts_at', '!=', 'starts_at');
            })
            ->chunkById(100, function ($seminars): void {
                foreach ($seminars as $seminar) {
                    $this->send($seminar, 'starts_at', 'reminder_sent_for_starts_at', 'sendReminder');
                }
            });

        Seminar::query()
            ->whereIn('status', ['published', 'completed'])
            ->where('event_format', 'external')
            ->whereNotNull('external_link_visible_at')
            ->where('external_link_visible_at', '<=', $now)
            ->where(function ($query): void {
                $query->whereNull('link_available_sent_for_visible_at')
                    ->orWhereColumn('link_available_sent_for_visible_at', '!=', 'external_link_visible_at');
            })
            ->chunkById(100, function ($seminars) use ($now): void {
                foreach ($seminars as $seminar) {
                    if ($this->linkIsAvailable($seminar, $now)) {
                        $this->send($seminar, 'external_link_visible_at', 'link_available_sent_for_visible_at', 'sendLinkAvailable');
                    }
                }
            });

        return self::SUCCESS;
    }

    private function send(Seminar $seminar, string $schedule, string $marker, string $method): void
    {
        $slot = $seminar->{$schedule};
        $claimed = Seminar::query()->whereKey($seminar->id)
            ->where($schedule, $slot)
            ->whereIn('status', $schedule === 'starts_at' ? ['published'] : ['published', 'completed'])
            ->where(function ($query) use ($marker, $schedule): void {
                $query->whereNull($marker)->orWhereColumn($marker, '!=', $schedule);
            })
            ->update([$marker => $slot]);

        if ($claimed !== 1) {
            return;
        }

        try {
            $this->notices->{$method}($seminar->fresh());
        } catch (\Throwable $exception) {
            Log::warning('Scheduled educational event notice dispatch failed', [
                'seminar_id' => $seminar->id,
                'notice' => $method,
                'exception' => $exception,
            ]);
            Seminar::query()->whereKey($seminar->id)->where($schedule, $slot)->where($marker, $slot)
                ->update([$marker => null]);
        }
    }

    private function linkIsAvailable(Seminar $seminar, \Illuminate\Support\Carbon $now): bool
    {
        $url = $seminar->external_url;
        if (! is_string($url) || filter_var($url, FILTER_VALIDATE_URL) === false
            || ! in_array(strtolower((string) parse_url($url, PHP_URL_SCHEME)), ['http', 'https'], true)) {
            return false;
        }

        $expiry = match ($seminar->external_link_expiry_mode) {
            'ongoing' => null,
            'event_end' => $seminar->ends_at,
            'custom' => $seminar->external_link_expires_at,
            default => $now,
        };

        return ($seminar->external_link_expiry_mode === 'ongoing' || $expiry !== null)
            && ($expiry === null || $now->lt($expiry));
    }
}
