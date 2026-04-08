@extends('layouts.instructor-app')

@section('title', 'Notifications')

@php
    /** @var \Illuminate\Pagination\LengthAwarePaginator $notifications */
@endphp

@section('content')
<div class="mx-auto max-w-4xl">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Notifications</h1>
            <p class="mt-1 text-sm text-gray-500">Instructor updates and learner activity alerts.</p>
        </div>

        @if($notifications->total() > 0 && auth()->user()->unreadNotifications()->count() > 0)
            <form method="POST" action="{{ route('instructor.notifications.mark-all-read') }}">
                @csrf
                <button type="submit" class="rounded-lg border border-gray-200 px-3 py-2 text-xs font-semibold text-gray-700 transition-colors hover:bg-gray-50">
                    Mark all as read
                </button>
            </form>
        @endif
    </div>

    <div class="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm">
        @forelse($notifications as $notification)
            @php
                $isUnread = is_null($notification->read_at);
                $payload = $notification->normalized_data ?? app(\App\Support\NotificationPayloadNormalizer::class)->normalize((array) $notification->data);
                $severity = $payload['severity'] ?? 'info';

                $iconColor = match($severity) {
                    'success' => 'bg-emerald-100 text-emerald-700',
                    'error' => 'bg-rose-100 text-rose-700',
                    default => 'bg-slate-100 text-slate-600',
                };
            @endphp
            <a
                href="{{ route('instructor.notifications.read', $notification->id) }}"
                class="block border-b border-gray-100 p-5 transition-colors hover:bg-gray-50 last:border-0 {{ $isUnread ? 'bg-rose-50/40' : '' }}"
            >
                <div class="flex items-start gap-4">
                    <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full {{ $iconColor }}">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6 6 0 10-12 0v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
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
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
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
