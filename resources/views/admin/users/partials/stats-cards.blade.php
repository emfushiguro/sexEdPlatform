@php
    $cards = [
        [
            'label' => 'Total Users',
            'value' => (int) ($stats['total'] ?? 0),
            'icon' => 'users',
            'cardClass' => 'border-brand-200 bg-gradient-to-br from-brand-50 via-white to-brand-100/70',
            'labelClass' => 'text-brand-700',
        ],
        [
            'label' => 'Active',
            'value' => (int) ($stats['active'] ?? 0),
            'icon' => 'active',
            'cardClass' => 'border-brand-100 bg-gradient-to-br from-white via-brand-50/70 to-brand-100/60',
            'labelClass' => 'text-brand-600',
        ],
        [
            'label' => 'Suspended',
            'value' => (int) ($stats['suspended'] ?? 0),
            'icon' => 'suspended',
            'cardClass' => 'border-brand-200 bg-gradient-to-br from-brand-100/60 via-white to-brand-50',
            'labelClass' => 'text-brand-800',
        ],
        [
            'label' => 'Archived',
            'value' => (int) ($stats['archived'] ?? 0),
            'icon' => 'archived',
            'cardClass' => 'border-brand-300 bg-gradient-to-br from-brand-100 via-white to-brand-200/70',
            'labelClass' => 'text-brand-900',
        ],
    ];
@endphp

<div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
    @foreach($cards as $card)
        <div class="rounded-[28px] border p-5 shadow-theme-xs min-h-[116px] {{ $card['cardClass'] }}">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.24em] {{ $card['labelClass'] }}">{{ $card['label'] }}</p>
                    <p class="mt-2 text-4xl leading-none font-bold text-gray-900">{{ number_format($card['value']) }}</p>
                </div>
                <span class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br text-white shadow-lg {{ $card['icon'] === 'users' ? 'from-brand-500 via-brand-700 to-brand-900 shadow-brand-200' : ($card['icon'] === 'active' ? 'from-brand-400 via-brand-600 to-brand-800 shadow-brand-200' : ($card['icon'] === 'suspended' ? 'from-brand-600 via-brand-700 to-brand-900 shadow-brand-300' : 'from-brand-700 via-brand-800 to-brand-900 shadow-brand-300')) }}">
                    @if($card['icon'] === 'users')
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 19a4 4 0 0 0-8 0m8 0h5m-5 0a4 4 0 0 1 8 0m-9-8a4 4 0 1 1-8 0 4 4 0 0 1 8 0Zm10 0a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg>
                    @elseif($card['icon'] === 'active')
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                    @elseif($card['icon'] === 'suspended')
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M18 6 6 18M6 6l12 12" /></svg>
                    @else
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M20.25 6.75v10.5A2.25 2.25 0 0 1 18 19.5H6a2.25 2.25 0 0 1-2.25-2.25V6.75M8.25 10.5h7.5" /></svg>
                    @endif
                </span>
            </div>
        </div>
    @endforeach
</div>
