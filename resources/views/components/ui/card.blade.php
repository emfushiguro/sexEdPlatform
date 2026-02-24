@props([
    'variant' => 'default', // default, hover, gradient, glass
    'padding' => 'p-6',
])

@php
$variantClasses = [
    'default' => 'card',
    'hover' => 'card-hover',
    'gradient' => 'card-gradient',
    'glass' => 'card-glass',
];

$classes = $variantClasses[$variant] . ' ' . $padding;
@endphp

<div {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</div>
