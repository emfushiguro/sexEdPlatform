@php
    $currentChatStatus = strtolower((string) (auth()->user()?->chat_status ?? 'active'));

    $currentUiStatus = match ($currentChatStatus) {
        'active', 'online' => 'online',
        'inactive', 'do_not_disturb', 'dnd' => 'do_not_disturb',
        'busy' => 'busy',
        default => 'offline',
    };

    $statusOptions = [
        'online' => ['label' => 'Online', 'dot' => 'bg-emerald-500'],
        'busy' => ['label' => 'Busy', 'dot' => 'bg-amber-500'],
        'do_not_disturb' => ['label' => 'Do Not Disturb', 'dot' => 'bg-red-500'],
        'offline' => ['label' => 'Offline', 'dot' => 'bg-gray-400'],
    ];
@endphp

<div class="border-t border-gray-100 px-4 py-3">
    <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-500">Connection Status</p>
    <div class="mt-2 grid grid-cols-2 gap-1.5">
        @foreach($statusOptions as $value => $meta)
            <form method="POST" action="{{ route('chat.status.update') }}">
                @csrf
                @method('PATCH')
                <input type="hidden" name="status" value="{{ $value }}">
                <button
                    type="submit"
                    class="inline-flex w-full items-center justify-start gap-2 rounded-md border px-2 py-1.5 text-[11px] font-medium transition-colors {{ $currentUiStatus === $value ? 'border-blue-300 bg-blue-50 text-blue-700' : 'border-gray-200 bg-white text-gray-700 hover:bg-gray-50' }}"
                >
                    <span class="inline-flex h-2.5 w-2.5 rounded-full {{ $meta['dot'] }}"></span>
                    <span>{{ $meta['label'] }}</span>
                </button>
            </form>
        @endforeach
    </div>
</div>
