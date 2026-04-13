@extends('layouts.admin')

@section('title', 'Notifications')
@section('page-title', 'Notifications')

@php
    /** @var \Illuminate\Pagination\LengthAwarePaginator $notifications */
@endphp

@section('content')
<div class="mx-auto max-w-5xl space-y-6 px-4 py-6">
    <section class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Admin Notifications</h1>
            <p class="mt-1 text-sm text-gray-500">Database-backed alerts with operational signals surfaced in the header dropdown.</p>
        </div>

        @if($notifications->total() > 0 && auth()->user()->unreadNotifications()->count() > 0)
            <form method="POST" action="{{ route('admin.notifications.mark-all-read') }}">
                @csrf
                <button type="submit" class="rounded-lg border border-gray-200 px-3 py-2 text-xs font-semibold text-gray-700 transition-colors hover:bg-gray-50">
                    Mark all as read
                </button>
            </form>
        @endif
    </section>

    <section class="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm">
        @forelse($notifications as $notification)
            @php
                $isUnread = is_null($notification->read_at);
                $payload = $notification->normalized_data ?? app(\App\Support\NotificationPayloadNormalizer::class)->normalize((array) $notification->data);
                $severity = $payload['severity'] ?? 'info';

                $toneClass = match($severity) {
                    'success' => 'border-l-4 border-emerald-500',
                    'error' => 'border-l-4 border-rose-500',
                    default => 'border-l-4 border-slate-300',
                };
            @endphp
            <a
                href="{{ route('admin.notifications.read', $notification->id) }}"
                class="block border-b border-gray-100 p-5 transition-colors hover:bg-gray-50 last:border-0 {{ $toneClass }} {{ $isUnread ? 'bg-rose-50/30' : '' }}"
            >
                <div class="flex items-start justify-between gap-4">
                    <div class="min-w-0">
                        <h2 class="text-sm font-semibold text-gray-900">{{ $payload['title'] }}</h2>
                        <p class="mt-1 text-sm text-gray-600">{{ $payload['message'] }}</p>
                    </div>
                    <div class="text-right">
                        <p class="whitespace-nowrap text-xs text-gray-500">{{ $notification->created_at->diffForHumans() }}</p>
                        @if($isUnread)
                            <span class="mt-2 inline-flex h-2 w-2 rounded-full bg-red-500"></span>
                        @endif
                    </div>
                </div>
            </a>
        @empty
            <div class="px-6 py-10 text-center text-sm text-gray-500">
                No notifications right now.
            </div>
        @endforelse
    </section>

    <div>
        {{ $notifications->links() }}
    </div>
</div>
@endsection
