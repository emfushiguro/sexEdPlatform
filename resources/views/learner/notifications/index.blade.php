@extends('layouts.learner-app')

@section('title', 'Notifications')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-900 border-b-2 border-purple-600 pb-1 inline-block">Notifications</h1>
        
        @if($notifications->count() > 0 && auth()->user()->unreadNotifications->count() > 0)
        <form method="POST" action="{{ route('learner.notifications.mark-all-read') }}">
            @csrf
            <button type="submit" class="text-sm text-purple-600 font-medium hover:text-purple-800 transition-colors">
                Mark all as read
            </button>
        </form>
        @endif
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        @forelse($notifications as $notification)
            @php
                $isUnread = is_null($notification->read_at);
                $title = $notification->data['title'] ?? 'New Notification';
                $message = $notification->data['message'] ?? '';
            @endphp
            <div class="p-5 border-b border-gray-100 last:border-0 hover:bg-gray-50 transition-colors {{ $isUnread ? 'bg-purple-50/30' : '' }}">
                <div class="flex gap-4 items-start">
                    <div class="w-10 h-10 rounded-full flex-shrink-0 flex items-center justify-center {{ $isUnread ? 'bg-purple-100 text-purple-600' : 'bg-gray-100 text-gray-500' }}">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                        </svg>
                    </div>
                    <div class="flex-1">
                        <div class="flex justify-between items-start">
                            <h3 class="text-sm font-semibold {{ $isUnread ? 'text-gray-900' : 'text-gray-700' }}">{{ $title }}</h3>
                            <span class="text-xs text-gray-500 whitespace-nowrap ml-4">{{ $notification->created_at->diffForHumans() }}</span>
                        </div>
                        <p class="text-sm text-gray-600 mt-1 mb-2">{{ $message }}</p>
                    </div>
                </div>
            </div>
        @empty
            <div class="p-10 text-center text-gray-500">
                <svg class="w-12 h-12 mx-auto text-gray-300 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                </svg>
                <p>You have no notifications yet.</p>
            </div>
        @endforelse
    </div>
    
    <div class="mt-6">
        {{ $notifications->links() }}
    </div>
</div>
@endsection