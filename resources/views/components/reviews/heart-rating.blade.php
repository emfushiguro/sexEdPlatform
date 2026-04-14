@props([
    'rating' => 0,
    'outOf' => 5,
    'sizeClass' => 'h-4 w-4',
    'textClass' => 'text-xs font-semibold text-gray-600 dark:text-gray-300',
    'showNumeric' => true,
])

@php
    $normalizedOutOf = max(1, (int) $outOf);
    $normalizedRating = max(0, min((float) $rating, (float) $normalizedOutOf));
    $roundedForHearts = (int) round($normalizedRating);
    $ratingForLabel = fmod($normalizedRating, 1.0) === 0.0
        ? (string) ((int) $normalizedRating)
        : number_format($normalizedRating, 1);
@endphp

<span class="inline-flex items-center gap-1.5" role="img" aria-label="{{ $ratingForLabel }} out of {{ $normalizedOutOf }} hearts">
    <span class="inline-flex items-center gap-0.5 text-rose-500" aria-hidden="true">
        @foreach(range(1, $normalizedOutOf) as $heartIndex)
            <svg class="{{ $sizeClass }} {{ $heartIndex <= $roundedForHearts ? 'opacity-100' : 'opacity-30' }}" viewBox="0 0 20 20" fill="currentColor">
                <path d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z" />
            </svg>
        @endforeach
    </span>

    @if($showNumeric)
        <span class="{{ $textClass }}">{{ number_format($normalizedRating, 1) }}</span>
    @endif
</span>
