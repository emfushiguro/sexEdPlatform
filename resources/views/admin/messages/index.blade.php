@extends('layouts.admin')
@section('title', 'Messages')
@section('page-title', 'Messages')
@section('content')

<div class="mb-5 flex items-center gap-3 px-4 py-3 rounded-xl bg-brand-50 border border-brand-200 text-brand-700 dark:bg-brand-500/10 dark:border-brand-500/20 dark:text-brand-400 text-sm">
    <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
    Real-time messaging backend coming soon. This is a UI preview.
</div>

<div class="rounded-2xl bg-white dark:bg-white/[0.03] border border-gray-200 dark:border-gray-800 shadow-theme-xs overflow-hidden" style="height: calc(100vh - 220px); min-height: 500px;">
    <div class="flex h-full">

        {{-- Sidebar: Conversation List --}}
        <div class="w-80 flex-shrink-0 border-r border-gray-100 dark:border-gray-800 flex flex-col">
            <div class="px-4 py-3 border-b border-gray-100 dark:border-gray-800">
                <input type="text" placeholder="Search conversations..." class="w-full px-3 py-2 rounded-lg border border-gray-200 dark:border-gray-700 bg-transparent text-sm text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-brand-500/30 transition">
            </div>
            <div class="flex-1 overflow-y-auto divide-y divide-gray-100 dark:divide-gray-800">
                @foreach([
                    ['name'=>'Juan dela Cruz','role'=>'Learner','msg'=>'Hello! I have a question about...','time'=>'2m ago','unread'=>2,'active'=>true],
                    ['name'=>'Maria Reyes','role'=>'Instructor','msg'=>'Thanks for the update on the...','time'=>'1h ago','unread'=>0,'active'=>false],
                    ['name'=>'Ana Bautista','role'=>'Counselor','msg'=>'The session on Friday is...','time'=>'3h ago','unread'=>1,'active'=>false],
                    ['name'=>'Pedro Santos','role'=>'Learner','msg'=>'I need help accessing my...','time'=>'Yesterday','unread'=>0,'active'=>false],
                    ['name'=>'Rosa Gomez','role'=>'Parent','msg'=>'Thank you for the seminar...','time'=>'2d ago','unread'=>0,'active'=>false],
                ] as $conv)
                <div class="flex items-start gap-3 px-4 py-3 cursor-pointer {{ $conv['active'] ? 'bg-brand-50 dark:bg-brand-500/10' : 'hover:bg-gray-50 dark:hover:bg-white/[0.02]' }} transition-colors">
                    <div class="w-9 h-9 rounded-full bg-brand-100 dark:bg-brand-500/10 flex items-center justify-center text-brand-600 dark:text-brand-400 text-sm font-bold flex-shrink-0">{{ substr($conv['name'],0,1) }}</div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between">
                            <p class="text-sm font-semibold text-gray-900 dark:text-white truncate">{{ $conv['name'] }}</p>
                            <span class="text-xs text-gray-400 dark:text-gray-500 flex-shrink-0">{{ $conv['time'] }}</span>
                        </div>
                        <p class="text-xs text-gray-400 dark:text-gray-500">{{ $conv['role'] }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 truncate mt-0.5">{{ $conv['msg'] }}</p>
                    </div>
                    @if($conv['unread'] > 0)
                    <span class="flex-shrink-0 w-5 h-5 rounded-full bg-brand-500 text-white text-xs flex items-center justify-center font-bold">{{ $conv['unread'] }}</span>
                    @endif
                </div>
                @endforeach
            </div>
        </div>

        {{-- Main: Messages Panel --}}
        <div class="flex-1 flex flex-col">
            {{-- Conversation Header --}}
            <div class="flex items-center gap-3 px-5 py-3.5 border-b border-gray-100 dark:border-gray-800">
                <div class="w-9 h-9 rounded-full bg-brand-100 dark:bg-brand-500/10 flex items-center justify-center text-brand-600 dark:text-brand-400 font-bold flex-shrink-0">J</div>
                <div>
                    <p class="text-sm font-bold text-gray-900 dark:text-white">Juan dela Cruz</p>
                    <p class="text-xs text-success-500">Online</p>
                </div>
            </div>

            {{-- Messages --}}
            <div class="flex-1 overflow-y-auto px-5 py-4 space-y-4">
                @foreach([
                    ['text'=>'Hello! I have a question about the subscription plans.','sender'=>'user','time'=>'10:32 AM'],
                    ['text'=>'Of course! What would you like to know? I am happy to help.','sender'=>'admin','time'=>'10:33 AM'],
                    ['text'=>'Is the Premium plan different from the Standard plan in terms of video content access?','sender'=>'user','time'=>'10:35 AM'],
                    ['text'=>'Yes, the Premium plan gives you access to all video modules including advanced topics, while Standard covers the foundational content. You can see the full comparison on the Plans page.','sender'=>'admin','time'=>'10:36 AM'],
                ] as $msg)
                <div class="flex {{ $msg['sender'] === 'admin' ? 'justify-end' : 'justify-start' }} gap-2.5">
                    @if($msg['sender'] === 'user')
                    <div class="w-7 h-7 rounded-full bg-brand-100 dark:bg-brand-500/10 flex items-center justify-center text-brand-600 dark:text-brand-400 text-xs font-bold flex-shrink-0 mt-1">J</div>
                    @endif
                    <div class="max-w-sm">
                        <div class="px-4 py-2.5 rounded-2xl text-sm {{ $msg['sender'] === 'admin' ? 'bg-brand-500 text-white rounded-br-sm' : 'bg-gray-100 dark:bg-white/5 text-gray-800 dark:text-gray-200 rounded-bl-sm' }}">
                            {{ $msg['text'] }}
                        </div>
                        <p class="text-xs text-gray-400 mt-1 {{ $msg['sender'] === 'admin' ? 'text-right' : '' }}">{{ $msg['time'] }}</p>
                    </div>
                </div>
                @endforeach
            </div>

            {{-- Input --}}
            <div class="px-5 py-3 border-t border-gray-100 dark:border-gray-800">
                <div class="flex items-center gap-3">
                    <input type="text" placeholder="Type a message..." disabled
                           class="flex-1 px-4 py-2.5 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-white/5 text-sm text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none opacity-60">
                    <button disabled class="p-2.5 rounded-xl bg-brand-500 text-white hover:bg-brand-600 transition-colors opacity-50 cursor-not-allowed">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
