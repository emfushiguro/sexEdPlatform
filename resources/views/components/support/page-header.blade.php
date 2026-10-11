@props([
    'icon' => 'help',
    'eyebrow' => 'Support',
    'title',
    'description' => null,
])

<header {{ $attributes->class(['flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between']) }}>
    <div class="flex min-w-0 gap-4">
        <span class="grid h-12 w-12 shrink-0 place-items-center rounded-2xl bg-purple-100 text-brand-700 dark:bg-purple-950 dark:text-purple-300">
            <x-ui.support-icon :name="$icon" class="h-6 w-6" />
        </span>
        <div class="min-w-0">
            @if($eyebrow)
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-purple-700 dark:text-purple-300">{{ $eyebrow }}</p>
            @endif
            <h1 class="mt-1 text-2xl font-bold tracking-tight text-gray-950 sm:text-3xl dark:text-white">{{ $title }}</h1>
            @if($description)
                <p class="mt-2 max-w-2xl text-sm leading-6 text-gray-600 dark:text-gray-400">{{ $description }}</p>
            @endif
        </div>
    </div>
    @isset($action)
        <div class="shrink-0">{{ $action }}</div>
    @endisset
</header>
