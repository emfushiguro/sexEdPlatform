@props(['status'])

@php
    $case = $status instanceof \App\Enums\PlatformFeedbackStatus
        ? $status
        : \App\Enums\PlatformFeedbackStatus::tryFrom((string) $status);
    [$label, $classes] = match ($case) {
        \App\Enums\PlatformFeedbackStatus::New => ['New', 'bg-sky-50 text-sky-700 ring-sky-200 dark:bg-sky-950 dark:text-sky-300 dark:ring-sky-800'],
        \App\Enums\PlatformFeedbackStatus::Reviewed => ['In Review', 'bg-amber-50 text-amber-800 ring-amber-200 dark:bg-amber-950 dark:text-amber-300 dark:ring-amber-800'],
        \App\Enums\PlatformFeedbackStatus::Resolved => ['Resolved', 'bg-emerald-50 text-emerald-700 ring-emerald-200 dark:bg-emerald-950 dark:text-emerald-300 dark:ring-emerald-800'],
        \App\Enums\PlatformFeedbackStatus::Closed => ['Closed', 'bg-gray-100 text-gray-700 ring-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:ring-gray-700'],
        \App\Enums\PlatformFeedbackStatus::Withdrawn => ['Withdrawn', 'bg-rose-50 text-rose-700 ring-rose-200 dark:bg-rose-950 dark:text-rose-300 dark:ring-rose-800'],
        default => ['Unknown', 'bg-gray-100 text-gray-700 ring-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:ring-gray-700'],
    };
@endphp

<span {{ $attributes->class(['inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset', $classes]) }}>{{ $label }}</span>
