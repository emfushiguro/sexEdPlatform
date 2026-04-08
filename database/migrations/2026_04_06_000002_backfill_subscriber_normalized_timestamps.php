<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $planPrices = DB::table('plan_prices')
            ->select('id', 'duration_unit', 'duration_count')
            ->get()
            ->keyBy('id');

        DB::table('subscribers')
            ->select([
                'id',
                'plan_price_id',
                'start_date',
                'end_date',
                'starts_at',
                'ends_at',
                'next_billing_at',
                'created_at',
            ])
            ->orderBy('id')
            ->chunkById(200, function ($subscriptions) use ($planPrices) {
                foreach ($subscriptions as $subscription) {
                    $updates = [];

                    $completedPaymentAt = DB::table('payments')
                        ->where('subscription_id', $subscription->id)
                        ->where('status', 'completed')
                        ->whereNotNull('paid_at')
                        ->orderByDesc('paid_at')
                        ->value('paid_at');

                    $resolvedStart = $subscription->starts_at
                        ?? $completedPaymentAt
                        ?? $subscription->start_date
                        ?? $subscription->created_at;

                    if ($subscription->starts_at === null && $resolvedStart !== null) {
                        $updates['starts_at'] = $resolvedStart;
                    }

                    if ($subscription->ends_at === null) {
                        $resolvedEnd = $subscription->end_date;

                        if ($resolvedEnd === null && $subscription->next_billing_at !== null) {
                            $resolvedEnd = $subscription->next_billing_at;
                        }

                        if ($resolvedEnd === null && $resolvedStart !== null && $subscription->plan_price_id !== null) {
                            $price = $planPrices->get($subscription->plan_price_id);

                            if ($price) {
                                $startAt = Carbon::parse((string) $resolvedStart);
                                $durationCount = max(1, (int) ($price->duration_count ?? 1));
                                $durationUnit = strtolower(trim((string) ($price->duration_unit ?? 'month')));

                                $resolvedEnd = match ($durationUnit) {
                                    'minute', 'minutes' => $startAt->copy()->addMinutes($durationCount),
                                    'hour', 'hours' => $startAt->copy()->addHours($durationCount),
                                    'day', 'days' => $startAt->copy()->addDays($durationCount),
                                    'week', 'weeks' => $startAt->copy()->addWeeks($durationCount),
                                    'year', 'years' => $startAt->copy()->addYears($durationCount),
                                    default => $startAt->copy()->addMonths($durationCount),
                                };
                            }
                        }

                        if ($resolvedEnd === null && $resolvedStart !== null) {
                            $resolvedEnd = Carbon::parse((string) $resolvedStart)->addMonth();
                        }

                        if ($resolvedEnd !== null) {
                            $updates['ends_at'] = $resolvedEnd;
                        }
                    }

                    if (!empty($updates)) {
                        DB::table('subscribers')
                            ->where('id', $subscription->id)
                            ->update($updates);
                    }
                }
            });
    }

    public function down(): void
    {
        // Data backfill only; no-op by design.
    }
};
