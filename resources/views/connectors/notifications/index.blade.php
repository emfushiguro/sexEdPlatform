@extends('layouts.connector-app')

@section('title', 'Notifications')
@section('page-title', 'Notifications')

@php
    $categories = [
        '' => 'All',
        'connector' => 'Connector',
        'members' => 'Members',
        'invitations' => 'Invitations',
        'seminars' => 'Seminars',
        'speakers' => 'Speakers',
        'livestream' => 'Livestream',
        'platform' => 'Platform',
    ];
    $normalizer = app(\App\Support\NotificationPayloadNormalizer::class);
@endphp

@section('content')
<div class="mx-auto max-w-4xl">
    <div class="mb-6 flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Notifications</h1>
            <p class="mt-1 text-sm text-gray-500">Connector updates, member activity, seminars, and platform alerts.</p>
        </div>

        @if($notifications->total() > 0 && $connectorNotificationUnreadCount > 0)
            <form method="POST" action="{{ route('connector.notifications.mark-all-read', $connector) }}">
                @csrf
                <button type="submit" class="rounded-lg border border-gray-200 px-3 py-2 text-xs font-semibold text-gray-700 transition-colors hover:bg-gray-50">
                    Mark all as read
                </button>
            </form>
        @endif
    </div>

    <form method="GET" action="{{ route('connector.notifications.index', $connector) }}" class="mb-4 flex flex-col gap-3 rounded-xl border border-gray-100 bg-white p-3 shadow-sm md:flex-row">
        <input
            type="search"
            name="search"
            value="{{ $search }}"
            placeholder="Search notifications"
            class="min-h-10 flex-1 rounded-lg border-gray-200 text-sm focus:border-purple-500 focus:ring-purple-500"
        >
        <select name="category" class="min-h-10 rounded-lg border-gray-200 text-sm focus:border-purple-500 focus:ring-purple-500">
            @foreach($categories as $value => $label)
                <option value="{{ $value }}" @selected($category === $value)>{{ $label }}</option>
            @endforeach
        </select>
        <button type="submit" class="rounded-lg bg-purple-700 px-4 py-2 text-sm font-semibold text-white transition hover:bg-purple-800">
            Filter
        </button>
    </form>

    <div class="overflow-hidden rounded-xl border border-gray-100 bg-white shadow-sm">
        @forelse($notifications as $notification)
            @php
                $isUnread = is_null($notification->read_at);
                $payload = $normalizer->normalize((array) $notification->data);
                $severity = $payload['severity'] ?? 'info';
                $iconColor = match($severity) {
                    'success' => 'bg-emerald-100 text-emerald-700',
                    'error' => 'bg-rose-100 text-rose-700',
                    default => 'bg-slate-100 text-slate-600',
                };
            @endphp
            <a
                href="{{ route('connector.notifications.read', [$connector, $notification->id]) }}"
                class="block border-b border-gray-100 p-5 transition-colors hover:bg-gray-50 last:border-0 {{ $isUnread ? 'bg-rose-50/40' : '' }}"
            >
                <div class="flex items-start gap-4">
                    <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full {{ $iconColor }}">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0 1 18 14.158V11a6 6 0 1 0-12 0v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 1 1-6 0v-1m6 0H9" />
                        </svg>
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-start justify-between gap-3">
                            <h2 class="text-sm font-semibold text-gray-900">{{ $payload['title'] }}</h2>
                            <span class="whitespace-nowrap text-xs text-gray-500">{{ $notification->created_at->diffForHumans() }}</span>
                        </div>
                        <p class="mt-1 text-sm text-gray-600">{{ $payload['message'] }}</p>
                    </div>
                </div>
            </a>
        @empty
            <div class="px-6 py-10 text-center">
                <svg class="mx-auto mb-3 h-12 w-12 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M20 13V6a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v7m16 0v5a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-5m16 0h-2.586a1 1 0 0 0-.707.293l-2.414 2.414a1 1 0 0 1-.707.293h-3.172a1 1 0 0 1-.707-.293l-2.414-2.414A1 1 0 0 0 6.586 13H4" />
                </svg>
                <p class="text-sm text-gray-500">No notifications yet.</p>
            </div>
        @endforelse
    </div>

    <div class="mt-6">
        {{ $notifications->links() }}
    </div>
</div>
@endsection
