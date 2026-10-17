@php
    $ownershipDisplay = $ownershipDisplay ?? app(\App\Services\Content\AdminOwnershipDisplayService::class)->forModule($module);
    $displayOwnerName = $ownershipDisplay['display_owner_name'] ?? ($module->creator?->name ?? 'Instructor');
    $instructorPhoto = ($ownershipDisplay['owner_type'] ?? '') === 'admin'
        ? ($module->creator?->adminCreatorProfile?->avatar_path ? asset('storage/' . $module->creator->adminCreatorProfile->avatar_path) : asset('media/Logo.png'))
        : ($module->creator?->instructorProfile?->profile_photo_path ? asset('storage/' . $module->creator->instructorProfile->profile_photo_path) : null);
    $activeConnectorSources = $activeConnectorSources ?? collect();
@endphp

{{-- Module hero --}}
<div class="bg-white dark:bg-gray-800 rounded-2xl overflow-hidden shadow-sm border border-gray-100 dark:border-gray-700">
    <div class="relative h-40 sm:h-48 overflow-hidden" style="background: linear-gradient(135deg, #A30EB2, #730DB1, #3B0CB1);">
        @if($module->thumbnail)
            <img src="{{ asset('storage/' . $module->thumbnail) }}"
                 alt="{{ $module->title }}"
                 class="absolute inset-0 w-full h-full object-cover">
            <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-black/20 to-transparent"></div>
        @else
            <div class="absolute inset-0 bg-gradient-to-t from-black/50 via-transparent to-transparent"></div>
        @endif
        <div class="absolute bottom-0 left-0 right-0 p-5">
            <div class="flex flex-wrap gap-2 mb-2">
                @if($module->difficulty_level)
                    <span class="text-[11px] font-bold px-2.5 py-0.5 rounded-full
                        {{ $module->difficulty_level === 'beginner'
                            ? 'bg-green-400/90 text-green-900'
                            : ($module->difficulty_level === 'intermediate'
                                ? 'bg-amber-400/90 text-amber-900'
                                : 'bg-red-400/90 text-red-900') }}">
                        {{ ucfirst($module->difficulty_level) }}
                    </span>
                @endif
            </div>
            <h1 class="text-2xl font-bold text-white leading-tight">{{ $module->title }}</h1>
            <div class="flex flex-wrap items-center gap-4 mt-2 text-sm text-white/80">
                <span class="flex items-center gap-1.5">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                    </svg>
                    {{ $lessons->count() }} {{ Str::plural('lesson', $lessons->count()) }}
                </span>
                @if($module->duration_minutes)
                <span class="flex items-center gap-1.5">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l4 2m6-2a10 10 0 11-20 0 10 10 0 0120 0z"/>
                    </svg>
                    {{ $module->duration_minutes }} min
                </span>
                @endif
            </div>
        </div>
    </div>

    {{-- Module description --}}
    @if($module->description)
    <div class="px-5 py-4 border-t border-gray-100 dark:border-gray-700">
        <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-1.5">About this module</h3>
        <p class="text-sm text-gray-600 dark:text-gray-400 whitespace-pre-line leading-relaxed">{{ $module->description }}</p>
    </div>
    @endif

    <div class="px-5 py-4 border-t border-gray-100 dark:border-gray-700">
        <div class="flex items-center gap-2 rounded-xl border border-gray-200 bg-gray-50 px-3 py-2.5 dark:border-gray-700 dark:bg-gray-800">
            @if($instructorPhoto)
                <img src="{{ $instructorPhoto }}" alt="{{ $displayOwnerName }}" class="h-8 w-8 rounded-full border border-gray-200 object-cover dark:border-gray-600">
            @else
                <div class="h-8 w-8 rounded-full bg-purple-100 text-purple-700 flex items-center justify-center text-xs font-bold dark:bg-purple-900/40 dark:text-purple-300">
                    {{ strtoupper(substr($displayOwnerName, 0, 1)) }}
                </div>
            @endif
            <div>
                <p class="text-[11px] font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Created by</p>
                <p class="text-sm font-medium text-gray-800 dark:text-gray-100">{{ $displayOwnerName }}</p>
                @if(!empty($ownershipDisplay['individual_attribution_text']))
                    <p class="text-xs text-purple-600 dark:text-purple-300 mt-0.5">{{ $ownershipDisplay['individual_attribution_text'] }}</p>
                @endif
                @foreach($activeConnectorSources as $connectorSource)
                    @if($connectorSource->connector)
                        <p class="text-xs text-indigo-600 dark:text-indigo-300 mt-0.5">Provided by {{ $connectorSource->connector->name }}</p>
                    @endif
                @endforeach
            </div>
        </div>
    </div>
</div>
