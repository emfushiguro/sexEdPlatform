@props([
    'hero' => [],
])

<div class="relative rounded-2xl overflow-hidden border border-purple-200/60 shadow-sm mb-6" data-testid="instructor-hero">
    <div class="absolute inset-0" style="background: linear-gradient(135deg, #A30EB2 0%, #730DB1 50%, #3B0CB1 100%);"></div>
    <div class="absolute inset-0 opacity-10" style="background-image: radial-gradient(circle, #fff 1px, transparent 1px); background-size: 20px 20px;"></div>
    <div class="absolute -top-6 -right-6 w-40 h-40 rounded-full opacity-20" style="background: radial-gradient(circle, #fff, transparent);"></div>
    <div class="absolute -bottom-8 -left-4 w-32 h-32 rounded-full opacity-10" style="background: radial-gradient(circle, #fff, transparent);"></div>

    <div class="relative z-10 flex items-center justify-between gap-4 px-6 py-5">
        <div>
            <p class="text-purple-200 text-xs font-medium uppercase tracking-widest mb-1">Instructor Space</p>
            <h1 class="text-2xl font-bold tracking-tight text-white">{{ $hero['title'] ?? 'Instructor Dashboard' }}</h1>
            <p class="text-purple-200 text-sm mt-1">{{ $hero['subtitle'] ?? '' }}</p>
        </div>

        @if(!empty($hero['cta_label']) && !empty($hero['cta_route']))
            <a href="{{ $hero['cta_route'] }}"
               class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-white/15 border border-white/20 text-white text-sm font-semibold hover:bg-white/25 transition-colors">
                {{ $hero['cta_label'] }}
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                </svg>
            </a>
        @endif
    </div>
</div>
