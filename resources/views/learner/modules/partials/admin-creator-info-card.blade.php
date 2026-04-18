@php
    $creatorProfile = $creator?->adminCreatorProfile;
    $adminDisplayName = $creatorProfile?->public_display_name ?: ($creator?->full_name ?: $creator?->name ?: 'Platform Developer');
    $adminBio = $creatorProfile?->bio ?: 'Platform creator information is being updated.';
    $adminAvatar = $creatorProfile?->avatar_path
        ? asset('storage/' . ltrim((string) $creatorProfile->avatar_path, '/'))
        : asset('media/Logo.png');
@endphp

<div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 p-5">
    <div class="flex items-start justify-between gap-3">
        <h4 class="text-sm font-semibold text-gray-900 dark:text-white">Admin Creator Information</h4>
        @if($creator)
            <div class="inline-flex items-center gap-2">
                <button
                    type="button"
                    aria-label="Report module"
                    @click="reportModalOpen = true; reportTarget = 'module'"
                    class="inline-flex h-8 w-8 items-center justify-center rounded-full border border-amber-200 bg-amber-50 text-amber-700 transition-colors hover:bg-amber-100"
                    title="Report module"
                >
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M5.2 19h13.6c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.468 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </button>

                <button
                    type="button"
                    aria-label="Message creator"
                    @click="$dispatch('open-global-chat', {
                        target_user_id: {{ $creator->id }},
                        name: '{{ addslashes($adminDisplayName) }}',
                        avatar: '{{ $adminAvatar ?: ('https://ui-avatars.com/api/?name=' . urlencode($adminDisplayName) . '&color=1D4ED8&background=EFF6FF') }}',
                        conversation_type: 'module_chat',
                        module_id: {{ $module->id }}
                    })"
                    class="inline-flex h-8 w-8 items-center justify-center rounded-full border border-blue-200 bg-blue-50 text-blue-700 transition-colors hover:bg-blue-100"
                    title="Message creator"
                >
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 10h8M8 14h5m-8 5l1.405-1.405A2.032 2.032 0 017.84 17H19a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v9a2 2 0 002 2h1.586a1 1 0 01.707.293L8 19z" />
                    </svg>
                </button>
            </div>
        @endif
    </div>

    <div class="mt-3 flex items-start gap-3">
        <img src="{{ $adminAvatar }}" alt="{{ $ownershipDisplay['display_owner_name'] }}" class="w-12 h-12 rounded-full object-cover border border-gray-200 dark:border-gray-600">
        <div class="min-w-0">
            <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $ownershipDisplay['display_owner_name'] }}</p>
            @if(!empty($ownershipDisplay['individual_attribution_text']))
                <p class="mt-1 text-xs font-medium text-purple-600 dark:text-purple-300">{{ $ownershipDisplay['individual_attribution_text'] }}</p>
            @endif
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 line-clamp-3">{{ $adminBio }}</p>
        </div>
    </div>

    @if($creator)
        <a href="{{ route('learner.admin-creators.show', $creator) }}"
           class="mt-4 inline-flex items-center gap-1.5 text-xs font-semibold text-purple-600 dark:text-purple-400 hover:underline">
            View Full Information page
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
            </svg>
        </a>
    @endif
</div>
