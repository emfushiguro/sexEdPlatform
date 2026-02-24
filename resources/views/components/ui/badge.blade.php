@props([
    'variant' => 'primary', // primary, success, warning, danger, info
    'size' => 'md', // sm, md, lg
    'outline' => false,
])

@php
$variantClasses = [
    'primary' => 'badge-primary',
    'success' => 'badge-success',
    'warning' => 'badge-warning',
    'danger' => 'badge-danger',
    'info' => 'badge-info',
];

$sizeClasses = [
    'sm' => 'text-xs px-2 py-0.5',
    'md' => 'text-sm px-2.5 py-0.5',
    'lg' => 'text-base px-3 py-1',
];

$classes = ($outline ? 'badge-outline ' : '') . $variantClasses[$variant] . ' ' . $sizeClasses[$size];
@endphp

<span {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</span>
