@php
    $thumbnailUrl = $seminar?->thumbnailUrl();
    $compact = ($variant ?? 'cover') === 'compact';
    $decorative = $decorative ?? $compact;
@endphp
<div data-seminar-thumbnail class="relative overflow-hidden rounded-lg bg-purple-50 {{ $compact ? 'h-14 w-24 shrink-0' : 'aspect-video w-full' }}" x-data="{ failed: false }" x-init="$nextTick(() => { const image = $refs.image; if (image?.complete && !image.naturalWidth) failed = true; })">
    <div @if($thumbnailUrl) x-show="failed" @endif class="absolute inset-0 flex items-center justify-center text-purple-300" @if(!$decorative) role="img" aria-label="Event image unavailable" @else aria-hidden="true" @endif>
        <svg class="{{ $compact ? 'h-7 w-7' : 'h-12 w-12' }}" aria-hidden="true" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 2v4m8-4v4M3 10h18M5 4h14a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2Zm3 10h2m4 0h2m-8 4h2"/></svg>
    </div>
    @if($thumbnailUrl)
        <img x-ref="image" src="{{ $thumbnailUrl }}" alt="{{ $decorative ? '' : $seminar->title.' event cover' }}" width="640" height="360" loading="{{ $loading ?? 'lazy' }}" decoding="async" class="absolute inset-0 h-full w-full object-cover" x-show="!failed" x-on:error="failed = true">
    @endif
</div>
