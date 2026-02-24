@props([
    'icon' => '📭',
    'title' => 'No data found',
    'description' => 'There is no data to display at the moment.',
    'actionText' => null,
    'actionUrl' => null,
])

<div {{ $attributes->merge(['class' => 'empty-state']) }}>
    <div class="empty-state-icon text-6xl">
        {{ $icon }}
    </div>
    <h3 class="empty-state-title">{{ $title }}</h3>
    <p class="empty-state-description">{{ $description }}</p>
    
    @if($actionText && $actionUrl)
        <div class="mt-6">
            <a href="{{ $actionUrl }}" class="btn-primary">
                {{ $actionText }}
            </a>
        </div>
    @endif
    
    @if($slot->isNotEmpty())
        <div class="mt-4">
            {{ $slot }}
        </div>
    @endif
</div>
