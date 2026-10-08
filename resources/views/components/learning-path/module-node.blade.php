@props([
    'node',
    'preview' => false,
    'showConnector' => false,
])

@php
    $state = (string) ($node['state'] ?? 'available');
    $stateIcon = match ($state) {
        'completed' => 'M5 12.5 9.25 17 19 7',
        'in_progress' => 'M12 7v5l3 2',
        'recommended' => 'M12 3.75 14.55 9l5.7.83-4.12 4.02.97 5.68L12 16.85l-5.1 2.68.97-5.68-4.12-4.02L9.45 9 12 3.75Z',
        'unavailable' => 'M6.75 6.75 17.25 17.25M17.25 6.75 6.75 17.25',
        default => 'M6 12h12',
    };
@endphp

<li class="learning-path-node learning-path-node--{{ $state }}{{ ($node['is_current'] ?? false) ? ' learning-path-node--current' : '' }}"
    aria-current="{{ ($node['is_current'] ?? false) ? 'step' : 'false' }}">
    <article class="learning-path-node__card">
        <div class="flex min-w-0 items-start gap-4">
            <span class="learning-path-node__icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <path d="{{ $stateIcon }}" />
                </svg>
            </span>

            <div class="min-w-0 flex-1">
                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-gray-500 dark:text-gray-400">Module {{ $node['position'] }}</p>
                <h2 class="mt-1 overflow-wrap-anywhere text-xl font-bold leading-tight text-gray-900 dark:text-white">{{ $node['module']->title }}</h2>
                <p class="mt-2 text-sm font-semibold text-gray-700 dark:text-gray-200">{{ $node['state_label'] }}</p>
            </div>
        </div>

        @if($node['reason'])
            <p class="mt-4 rounded-xl border border-gray-200 bg-gray-50 px-3 py-2 text-sm leading-6 text-gray-700 dark:border-gray-700 dark:bg-gray-900/60 dark:text-gray-300">{{ $node['reason'] }}</p>
        @endif

        <div class="mt-5">
            <div class="flex items-center justify-between gap-3 text-xs font-semibold text-gray-600 dark:text-gray-300">
                <span>{{ $node['completed_lessons'] }} of {{ $node['total_lessons'] }} lessons</span>
                <span>{{ $node['progress_percentage'] }}%</span>
            </div>
            <div class="mt-2 h-2.5 overflow-hidden rounded-full bg-gray-100 dark:bg-gray-700"
                 role="progressbar"
                 aria-label="Progress for {{ $node['module']->title }}"
                 aria-valuemin="0"
                 aria-valuemax="100"
                 aria-valuenow="{{ $node['progress_percentage'] }}">
                <span class="learning-path-node__progress" style="width: {{ $node['progress_percentage'] }}%"></span>
            </div>
        </div>

        <div class="mt-5 flex flex-wrap items-center justify-between gap-3">
            @if(! $preview && $node['action_url'])
                <a href="{{ $node['action_url'] }}"
                   class="inline-flex min-h-11 items-center justify-center rounded-xl bg-purple-700 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-purple-800 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-purple-500 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-gray-800"
                   aria-label="{{ $node['action_label'] }}: {{ $node['module']->title }}">
                    {{ $node['action_label'] }}
                </a>
            @elseif($preview)
                <span class="inline-flex min-h-11 items-center rounded-xl border border-dashed border-gray-300 px-4 py-2.5 text-sm font-semibold text-gray-600 dark:border-gray-600 dark:text-gray-300">Preview only</span>
            @else
                <span class="text-sm font-semibold text-gray-500 dark:text-gray-400">No action available</span>
            @endif
        </div>
    </article>

    @if($showConnector)
        <div class="learning-path-node__connector" aria-hidden="true">
            <svg viewBox="0 0 24 40" fill="none" focusable="false" aria-hidden="true">
                <path d="M12 1v38" />
            </svg>
        </div>
    @endif
</li>
