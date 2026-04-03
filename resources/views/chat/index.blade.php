@php
    $user = auth()->user();

    $startContext = array_filter([
        'target_user_id' => request()->query('target_user_id'),
        'conversation_type' => request()->query('conversation_type'),
        'module_id' => request()->query('module_id'),
        'lesson_id' => request()->query('lesson_id'),
        'quiz_id' => request()->query('quiz_id'),
        'initial_message' => request()->query('initial_message'),
    ], static fn ($value) => $value !== null && $value !== '');

    $chatBootstrap = [
        'currentUserId' => $user?->id,
        'currentUserRole' => $user?->role,
        'notificationsEnabled' => false,
        'startContext' => $startContext,
    ];
@endphp

<div
    class="rounded-2xl border border-gray-200 bg-white shadow-theme-xs overflow-hidden"
    data-chat-root
    x-data
    x-init="$store.chat.bootstrap({{ \Illuminate\Support\Js::from($chatBootstrap) }})"
>
    <div class="border-b border-gray-100 px-4 py-3 flex items-center justify-between gap-3">
        <p class="text-sm font-semibold text-gray-900">Real-time chat</p>
        <button
            type="button"
            data-chat-notification-toggle
            @click="$store.chat.toggleNotificationsEnabled()"
            class="inline-flex items-center rounded-full border border-gray-200 px-3 py-1 text-xs font-medium text-gray-700 hover:bg-gray-50 transition-colors"
            x-text="$store.chat.notificationsEnabled ? 'Browser alerts on' : 'Enable browser alerts'"
        ></button>
    </div>

    <div class="flex flex-col lg:flex-row min-h-[640px]">
        <div class="w-full lg:w-auto lg:border-r lg:border-gray-200">
            @include('chat.partials.request-list')
            @include('chat.partials.conversation-list')
        </div>

        @include('chat.partials.conversation-panel')
    </div>
</div>
