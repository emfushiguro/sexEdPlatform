@props(['type'])

@php
    $case = $type instanceof \App\Enums\PlatformFeedbackType
        ? $type
        : (\App\Enums\PlatformFeedbackType::tryFrom((string) $type) ?? \App\Enums\PlatformFeedbackType::General);
@endphp

<svg {{ $attributes->class(['h-5 w-5'])->merge(['viewBox' => '0 0 24 24', 'fill' => 'none', 'stroke' => 'currentColor', 'stroke-width' => '1.8', 'aria-hidden' => 'true']) }}>
    @switch($case)
        @case(\App\Enums\PlatformFeedbackType::BugReport)
            <path d="M9 6.5h6M8 9H5m14 0h-3M8 14H4m16 0h-4M9 19h6M8 12a4 4 0 0 1 8 0v3a4 4 0 0 1-8 0v-3Z" />
            @break
        @case(\App\Enums\PlatformFeedbackType::FeatureSuggestion)
            <path d="M9 18h6M10 21h4M8.5 14.5C7.6 13.7 7 12.5 7 11a5 5 0 1 1 10 0c0 1.5-.6 2.7-1.5 3.5-.7.6-1.1 1.1-1.2 1.5h-4.6c-.1-.4-.5-.9-1.2-1.5Z" />
            @break
        @case(\App\Enums\PlatformFeedbackType::AccessibilityIssue)
            <circle cx="12" cy="5" r="2" /><path d="M5 9.5c4.7 1.4 9.3 1.4 14 0M12 11v10M8 21l4-6 4 6" />
            @break
        @case(\App\Enums\PlatformFeedbackType::HelpContentIssue)
            <path d="M5 4.5h10a3 3 0 0 1 3 3V20H8a3 3 0 0 1-3-3V4.5Z" /><path d="M8 20a3 3 0 0 1 0-6h10M10 8h4" />
            @break
        @case(\App\Enums\PlatformFeedbackType::AccountPaymentIssue)
            <rect x="3" y="5" width="18" height="14" rx="2" /><path d="M3 10h18M7 15h3" />
            @break
        @default
            <path d="M4 5.5h16v11H9l-5 4v-15Z" /><path d="M8 10h8M8 13h5" />
    @endswitch
</svg>
