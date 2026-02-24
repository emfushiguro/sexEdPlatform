@props([
    'value' => 0,
    'max' => 100,
    'color' => 'gradient', // gradient, purple, blue, green, red
    'showLabel' => false,
    'height' => 'h-2',
])

@php
$percentage = $max > 0 ? min(100, ($value / $max) * 100) : 0;

$colorClasses = [
    'gradient' => 'bg-gradient-to-r from-brand-purple-500 to-brand-blue-500',
    'purple' => 'bg-brand-purple-600',
    'blue' => 'bg-brand-blue-600',
    'green' => 'bg-green-600',
    'red' => 'bg-red-600',
    'yellow' => 'bg-yellow-500',
];

$fillClass = $colorClasses[$color] ?? $colorClasses['gradient'];
@endphp

<div {{ $attributes->merge(['class' => 'w-full']) }}>
    @if($showLabel)
        <div class="flex justify-between items-center mb-1">
            <span class="text-sm font-medium text-gray-700">{{ $slot }}</span>
            <span class="text-sm font-medium text-gray-700">{{ round($percentage) }}%</span>
        </div>
    @endif
    
    <div class="progress-bar {{ $height }}">
        <div class="{{ $fillClass }} h-full rounded-full transition-all duration-500 ease-out" style="width: {{ $percentage }}%"></div>
    </div>
</div>
