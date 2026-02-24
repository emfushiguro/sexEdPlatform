@props([
    'width' => 'w-full',
    'height' => 'h-4',
    'rounded' => 'rounded',
])

@php
$classes = "skeleton {$width} {$height} {$rounded}";
@endphp

<div {{ $attributes->merge(['class' => $classes]) }}></div>
