@props([
    'title' => '',
    'subtitle' => '',
    'tone' => 'purple',
    'actionHref' => null,
    'actionLabel' => null,
])

@php
    $toneKey = is_string($tone) ? $tone : 'purple';
    $tones = [
        'purple' => ['wrap' => 'bg-purple-50/40 border-purple-100/60', 'accent' => 'border-purple-400', 'action' => 'text-purple-600 bg-purple-100 hover:text-purple-800 hover:bg-purple-200'],
        'amber' => ['wrap' => 'bg-amber-50/40 border-amber-100/60', 'accent' => 'border-amber-400', 'action' => 'text-amber-700 bg-amber-100 hover:text-amber-900 hover:bg-amber-200'],
        'indigo' => ['wrap' => 'bg-indigo-50/30 border-indigo-100/50', 'accent' => 'border-indigo-400', 'action' => 'text-indigo-600 bg-indigo-100 hover:text-indigo-800 hover:bg-indigo-200'],
        'green' => ['wrap' => 'bg-green-50/30 border-green-100/50', 'accent' => 'border-green-400', 'action' => 'text-green-700 bg-green-100 hover:text-green-900 hover:bg-green-200'],
    ];
    $selectedTone = $tones[$toneKey] ?? $tones['purple'];
@endphp

<section {{ $attributes->class(['rounded-2xl p-5 border', $selectedTone['wrap']]) }}>
    <div class="flex items-center justify-between mb-4">
        <div class="border-l-4 pl-3 {{ $selectedTone['accent'] }}">
            <h2 class="text-base font-semibold text-gray-900">{{ $title }}</h2>
            @if($subtitle)
                <p class="text-xs text-gray-400">{{ $subtitle }}</p>
            @endif
        </div>

        @if($actionHref && $actionLabel)
            <a href="{{ $actionHref }}" class="text-xs font-medium px-3 py-1 rounded-full transition-colors {{ $selectedTone['action'] }}">
                {{ $actionLabel }}
            </a>
        @endif
    </div>

    {{ $slot }}
</section>
