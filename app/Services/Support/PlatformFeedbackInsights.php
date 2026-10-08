<?php

namespace App\Services\Support;

use App\Enums\PlatformFeedbackStatus;
use App\Enums\PlatformFeedbackType;
use App\Models\PlatformFeedback;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class PlatformFeedbackInsights
{
    public function summary(array $filters = []): array
    {
        $base = PlatformFeedback::query();
        $total = (clone $base)->count();
        $counts = (clone $base)->select('status', DB::raw('count(*) as aggregate'))->groupBy('status')->pluck('aggregate', 'status')->all();
        $types = (clone $base)->select('type', DB::raw('count(*) as aggregate'))->groupBy('type')->pluck('aggregate', 'type')->all();
        $average = (clone $base)->whereNotNull('rating')->avg('rating');
        $monthly = [];
        (clone $base)->where('created_at', '>=', now()->subMonths(12)->startOfMonth())->get(['created_at', 'rating'])->each(function (PlatformFeedback $feedback) use (&$monthly): void {
            $key = Carbon::parse($feedback->created_at)->format('Y-m');
            $monthly[$key] ??= ['total' => 0, 'ratings' => []];
            $monthly[$key]['total']++;
            if ($feedback->rating !== null) {
                $monthly[$key]['ratings'][] = (int) $feedback->rating;
            }
        });
        ksort($monthly);
        $monthly = array_map(static function (array $row): array {
            $ratings = $row['ratings'];

            return ['total' => $row['total'], 'average_rating' => $ratings === [] ? null : round(array_sum($ratings) / count($ratings), 2)];
        }, $monthly);

        return [
            'total' => $total,
            'new' => (int) ($counts['new'] ?? 0),
            'unresolved' => (int) (($counts['new'] ?? 0) + ($counts['reviewed'] ?? 0)),
            'resolved' => (int) ($counts['resolved'] ?? 0),
            'average_rating' => $average === null ? null : round((float) $average, 2),
            'by_type' => array_replace(array_fill_keys(PlatformFeedbackType::values(), 0), array_map('intval', $types)),
            'by_status' => array_replace(array_fill_keys(PlatformFeedbackStatus::values(), 0), array_map('intval', $counts)),
            'monthly' => $monthly,
        ];
    }
}
