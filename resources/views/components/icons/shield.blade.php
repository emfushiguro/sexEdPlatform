{{-- Shield SVG icon. Props: state (full|empty|broken), size (default 24) --}}
@props(['state' => 'full', 'size' => 24])

@if($state === 'full')
<svg width="{{ $size }}" height="{{ $size }}" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" {{ $attributes }}>
    <defs>
        <linearGradient id="shield-full-grad" x1="0%" y1="0%" x2="100%" y2="100%">
            <stop offset="0%" style="stop-color:#A30EB2;stop-opacity:1" />
            <stop offset="100%" style="stop-color:#3B0CB1;stop-opacity:1" />
        </linearGradient>
    </defs>
    <path d="M12 2L4 6V12C4 16.4 7.4 20.5 12 22C16.6 20.5 20 16.4 20 12V6L12 2Z"
          fill="url(#shield-full-grad)" stroke="none"/>
    <path d="M9 12L11 14L15 10" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
</svg>

@elseif($state === 'empty')
<svg width="{{ $size }}" height="{{ $size }}" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" {{ $attributes }}>
    <path d="M12 2L4 6V12C4 16.4 7.4 20.5 12 22C16.6 20.5 20 16.4 20 12V6L12 2Z"
          fill="#D1D5DB" opacity="0.7" stroke="none"/>
</svg>

@else {{-- broken --}}
<svg width="{{ $size }}" height="{{ $size }}" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" {{ $attributes }}>
    <path d="M12 2L4 6V12C4 16.4 7.4 20.5 12 22C16.6 20.5 20 16.4 20 12V6L12 2Z"
          fill="#9CA3AF" opacity="0.5" stroke="none"/>
    <line x1="10" y1="8" x2="14" y2="16" stroke="#6B7280" stroke-width="1.5" stroke-linecap="round"/>
    <line x1="14" y1="8" x2="10" y2="16" stroke="#6B7280" stroke-width="1.5" stroke-linecap="round"/>
</svg>
@endif
