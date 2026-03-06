@extends('layouts.admin')
@section('title', 'Compose Email')
@section('page-title', 'Compose Email')
@section('content')

<div class="mb-5">
    <a href="{{ route('admin.emails.index') }}" class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-brand-500 dark:text-gray-400 dark:hover:text-brand-400 transition-colors">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        Back to Emails
    </a>
</div>

<div class="max-w-3xl">
    <div class="rounded-2xl bg-white dark:bg-white/[0.03] border border-gray-200 dark:border-gray-800 shadow-theme-xs overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-800">
            <h3 class="text-base font-semibold text-gray-900 dark:text-white">New Announcement Email</h3>
        </div>
        <form class="p-6 space-y-5" method="POST" action="#" onsubmit="alert('Email backend coming soon.'); return false;">
            @csrf
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Send To</label>
                <select name="audience" class="w-full px-3 py-2.5 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 text-sm text-gray-700 dark:text-gray-300 focus:outline-none focus:ring-2 focus:ring-brand-500/30">
                    <option value="all">All Users</option>
                    <option value="learners">Learners Only</option>
                    <option value="instructors">Instructors Only</option>
                    <option value="counselors">Counselors Only</option>
                    <option value="premium">Premium Subscribers</option>
                    <option value="active_subs">Active Subscribers</option>
                    <option value="expiring">Expiring Subscriptions (7 days)</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Subject</label>
                <input type="text" name="subject" placeholder="Email subject line..."
                       class="w-full px-3 py-2.5 rounded-lg border border-gray-200 dark:border-gray-700 bg-transparent text-sm text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-brand-500/30 focus:border-brand-500 transition">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Preview Text</label>
                <input type="text" name="preview_text" placeholder="Short preview shown in inbox..."
                       class="w-full px-3 py-2.5 rounded-lg border border-gray-200 dark:border-gray-700 bg-transparent text-sm text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-brand-500/30 focus:border-brand-500 transition">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Message Body</label>
                <div class="rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                    {{-- Toolbar --}}
                    <div class="flex items-center gap-1 px-3 py-2 border-b border-gray-100 dark:border-gray-800 bg-gray-50 dark:bg-white/[0.02]">
                        @foreach(['B','I','U'] as $fmt)
                        <button type="button" class="px-2.5 py-1 rounded text-xs font-bold text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-white/10 transition-colors">{{ $fmt }}</button>
                        @endforeach
                        <div class="w-px h-4 bg-gray-200 dark:bg-gray-700 mx-1"></div>
                        <button type="button" class="px-2 py-1 rounded text-xs text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-white/10 transition-colors">Link</button>
                        <button type="button" class="px-2 py-1 rounded text-xs text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-white/10 transition-colors">List</button>
                    </div>
                    <textarea name="body" rows="10" placeholder="Write your announcement here..."
                              class="w-full px-4 py-3 bg-transparent text-sm text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none resize-none"></textarea>
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Schedule Send</label>
                <div class="flex items-center gap-3">
                    <input type="datetime-local" name="scheduled_at"
                           class="flex-1 px-3 py-2.5 rounded-lg border border-gray-200 dark:border-gray-700 bg-transparent text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-brand-500/30 transition">
                    <p class="text-xs text-gray-400 dark:text-gray-500">Leave blank to send immediately</p>
                </div>
            </div>
            <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-100 dark:border-gray-800">
                <button type="button" class="px-4 py-2 rounded-lg text-sm border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-white/5 transition-colors">Save Draft</button>
                <button type="submit" class="inline-flex items-center gap-2 px-6 py-2 rounded-lg bg-brand-500 hover:bg-brand-600 text-white text-sm font-medium shadow-theme-xs transition-colors">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    Send Email
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
