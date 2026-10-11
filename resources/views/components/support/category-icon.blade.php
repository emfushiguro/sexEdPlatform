@props(['name' => 'tools'])

@php
    $icons = ['rocket', 'account', 'book', 'quiz', 'seminar', 'community', 'guardian', 'instructor', 'connector', 'payment', 'shield', 'accessibility', 'tools'];
    $icon = in_array($name, $icons, true) ? $name : 'tools';
@endphp

<svg
    {{ $attributes->class(['h-5 w-5']) }}
    data-help-category-icon="{{ $icon }}"
    aria-hidden="true"
    focusable="false"
    fill="none"
    viewBox="0 0 24 24"
    stroke="currentColor"
    stroke-width="1.8"
>
    @switch($icon)
        @case('rocket')
            <path stroke-linecap="round" stroke-linejoin="round" d="M14.5 5.5c2.8-2.2 5-1.5 5-1.5s.7 2.2-1.5 5l-4.5 4.5-3-3 4-5Z" />
            <path stroke-linecap="round" stroke-linejoin="round" d="m10.5 10.5-4 .5-2 2 4 1.5L10 18l1.5-4m-5 3.5L4 20" />
            @break
        @case('account')
            <circle cx="12" cy="8" r="3.25" />
            <path stroke-linecap="round" d="M5.5 20c.7-4.1 2.8-6 6.5-6s5.8 1.9 6.5 6" />
            @break
        @case('book')
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 5.5A2.5 2.5 0 0 1 6.5 3H11v16H6.5A2.5 2.5 0 0 0 4 21V5.5Zm16 0A2.5 2.5 0 0 0 17.5 3H13v16h4.5A2.5 2.5 0 0 1 20 21V5.5Z" />
            @break
        @case('quiz')
            <path stroke-linecap="round" stroke-linejoin="round" d="M7 3.5h10A2.5 2.5 0 0 1 19.5 6v14.5h-15V6A2.5 2.5 0 0 1 7 3.5Z" />
            <path stroke-linecap="round" d="M8 9h8m-8 4h5m-5 4h7" />
            @break
        @case('seminar')
            <rect x="3.5" y="5.5" width="17" height="14" rx="2" />
            <path stroke-linecap="round" d="M7 3v5m10-5v5M3.5 10h17" />
            @break
        @case('community')
            <circle cx="8" cy="9" r="3" />
            <circle cx="16.5" cy="10" r="2.5" />
            <path stroke-linecap="round" d="M2.5 20c.5-4 2.3-6 5.5-6s5 2 5.5 6m0-5.2c3.8-.9 6.7 1.1 7.2 5.2" />
            @break
        @case('guardian')
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3 4.5 6v5.5c0 4.5 3 7.6 7.5 9.5 4.5-1.9 7.5-5 7.5-9.5V6L12 3Z" />
            <path stroke-linecap="round" d="m9 12 2 2 4-4" />
            @break
        @case('instructor')
            <path stroke-linecap="round" stroke-linejoin="round" d="m3 8.5 9-5 9 5-9 5-9-5Z" />
            <path stroke-linecap="round" d="M6.5 11v5c3.2 2.4 7.8 2.4 11 0v-5M21 9v6" />
            @break
        @case('connector')
            <circle cx="6" cy="12" r="2.5" />
            <circle cx="18" cy="6" r="2.5" />
            <circle cx="18" cy="18" r="2.5" />
            <path stroke-linecap="round" d="m8.2 10.9 7.6-3.8m-7.6 6 7.6 3.8" />
            @break
        @case('payment')
            <rect x="3" y="5" width="18" height="14" rx="2.5" />
            <path stroke-linecap="round" d="M3 9h18m-14 6h4" />
            @break
        @case('shield')
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3 4.5 6v5.5c0 4.5 3 7.6 7.5 9.5 4.5-1.9 7.5-5 7.5-9.5V6L12 3Z" />
            @break
        @case('accessibility')
            <circle cx="12" cy="4.5" r="1.75" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M5 8.5c4.4 1.3 9.6 1.3 14 0M12 10v5m0 0-4 6m4-6 4 6" />
            @break
        @default
            <path stroke-linecap="round" stroke-linejoin="round" d="M14.7 6.3a4 4 0 0 0-5 5L4 17l3 3 5.7-5.7a4 4 0 0 0 5-5l-2.4 2.4-3-3 2.4-2.4Z" />
    @endswitch
</svg>
