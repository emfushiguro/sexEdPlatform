@php
    $eventState = match (true) {
        $seminar?->status === 'cancelled' => 'Cancelled',
        $seminar?->status === 'completed' => 'Completed',
        ! in_array($seminar?->status, ['published', 'completed'], true) => 'Awaiting publication',
        $seminar?->ends_at && now()->greaterThanOrEqualTo($seminar->ends_at) => 'Completed',
        ($seminar?->starts_at ?? $seminar?->schedule) && now()->greaterThanOrEqualTo($seminar->starts_at ?? $seminar->schedule) => 'Happening now',
        default => 'Upcoming',
    };
@endphp
<span class="text-sm font-semibold text-gray-600">Event: {{ $eventState }}</span>
