@extends('layouts.admin')
@section('title', 'User Profile')
@section('page-title', 'User Profile')
@section('content')

<div class="mb-5">
    <a href="{{ route('admin.users.index') }}" class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-brand-500 dark:text-gray-400 dark:hover:text-brand-400 transition-colors">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        Back to Users
    </a>
</div>

@php
    $roleMap = ['learner'=>'bg-brand-50 text-brand-700 dark:bg-brand-500/10 dark:text-brand-400','instructor'=>'bg-purple-50 text-purple-700 dark:bg-purple-500/10 dark:text-purple-400','counselor'=>'bg-success-50 text-success-700 dark:bg-success-500/10 dark:text-success-400','clinic'=>'bg-teal-50 text-teal-700 dark:bg-teal-500/10 dark:text-teal-400','organization'=>'bg-indigo-50 text-indigo-700 dark:bg-indigo-500/10 dark:text-indigo-400','admin'=>'bg-error-50 text-error-700 dark:bg-error-500/10 dark:text-error-400'];
    $statusMap = ['active'=>'bg-success-50 text-success-700 dark:bg-success-500/10 dark:text-success-400','suspended'=>'bg-error-50 text-error-700 dark:bg-error-500/10 dark:text-error-400','inactive'=>'bg-gray-100 text-gray-500 dark:bg-white/5 dark:text-gray-400'];
@endphp

<div class="grid grid-cols-1 xl:grid-cols-3 gap-5">
    <div class="xl:col-span-2 space-y-5">
        {{-- Profile Card --}}
        <div class="rounded-2xl bg-white dark:bg-white/[0.03] border border-gray-200 dark:border-gray-800 shadow-theme-xs p-6">
            <div class="flex items-start justify-between mb-6">
                <div class="flex items-center gap-4">
                    <div class="w-16 h-16 rounded-2xl bg-brand-100 dark:bg-brand-500/10 flex items-center justify-center text-brand-600 dark:text-brand-400 text-2xl font-bold">
                        {{ strtoupper(substr($user->name, 0, 1)) }}
                    </div>
                    <div>
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white">{{ $user->name }}</h2>
                        <p class="text-sm text-gray-400">{{ $user->email }}</p>
                        <div class="flex items-center gap-2 mt-2">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $roleMap[$user->role] ?? 'bg-gray-100 text-gray-500 dark:bg-white/5 dark:text-gray-400' }}">{{ ucfirst($user->role) }}</span>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $statusMap[$user->status] ?? 'bg-gray-100 text-gray-500 dark:bg-white/5 dark:text-gray-400' }}">{{ ucfirst($user->status) }}</span>
                        </div>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <a href="{{ route('admin.users.edit', $user) }}" class="inline-flex items-center gap-2 px-3 py-2 rounded-lg border border-warning-200 dark:border-warning-800 text-warning-600 dark:text-warning-400 hover:bg-warning-50 dark:hover:bg-warning-500/10 text-sm font-medium transition-colors">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                        Edit
                    </a>
                    @if($user->id !== auth()->id())
                    <form action="{{ route('admin.users.destroy', $user) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this user?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="inline-flex items-center gap-2 px-3 py-2 rounded-lg border border-error-200 dark:border-error-800 text-error-600 dark:text-error-400 hover:bg-error-50 dark:hover:bg-error-500/10 text-sm font-medium transition-colors">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            Delete
                        </button>
                    </form>
                    @endif
                </div>
            </div>
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-4 pt-4 border-t border-gray-100 dark:border-gray-800">
                <div><p class="text-xs text-gray-400 dark:text-gray-500 mb-0.5">Member Since</p><p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $user->created_at->format('M d, Y') }}</p></div>
                <div><p class="text-xs text-gray-400 dark:text-gray-500 mb-0.5">Last Updated</p><p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $user->updated_at->format('M d, Y') }}</p></div>
                <div><p class="text-xs text-gray-400 dark:text-gray-500 mb-0.5">Email Verified</p><p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $user->email_verified_at ? 'Yes' : 'No' }}</p></div>
            </div>
        </div>

        {{-- Subscription History --}}
        @if(isset($user->subscriptions) && $user->subscriptions->count() > 0)
        <div class="rounded-2xl bg-white dark:bg-white/[0.03] border border-gray-200 dark:border-gray-800 shadow-theme-xs overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-800">
                <h3 class="text-base font-semibold text-gray-900 dark:text-white">Subscription History</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-800">
                    <thead class="bg-gray-50 dark:bg-white/[0.02]">
                        <tr>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Plan</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Period</th>
                            <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @foreach($user->subscriptions as $sub)
                        @php $sv = is_object($sub->status) ? $sub->status->value : $sub->status; @endphp
                        <tr class="hover:bg-gray-50 dark:hover:bg-white/[0.02]">
                            <td class="px-5 py-3 text-sm font-semibold text-gray-900 dark:text-white capitalize">{{ $sub->plan }}</td>
                            <td class="px-5 py-3">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $sv === 'active' ? 'bg-success-50 text-success-700 dark:bg-success-500/10 dark:text-success-400' : 'bg-gray-100 text-gray-500 dark:bg-white/5 dark:text-gray-400' }}">{{ ucfirst($sv) }}</span>
                            </td>
                            <td class="px-5 py-3 text-sm text-gray-500 dark:text-gray-400">{{ $sub->start_date }} &rarr; {{ $sub->end_date }}</td>
                            <td class="px-5 py-3 text-right">
                                <a href="{{ route('admin.subscribers.show', $sub) }}" class="p-1.5 rounded-lg text-gray-400 hover:bg-brand-50 hover:text-brand-600 dark:hover:bg-brand-500/10 dark:hover:text-brand-400 transition-colors inline-flex">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        {{-- Certificates --}}
        @if($user->role === 'learner')
        <div class="rounded-2xl bg-white dark:bg-white/[0.03] border border-gray-200 dark:border-gray-800 shadow-theme-xs overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-800">
                <h3 class="text-base font-semibold text-gray-900 dark:text-white">Certificates</h3>
            </div>

            @if($user->certificates->isEmpty())
                <div class="px-6 py-5 text-sm text-gray-500 dark:text-gray-400">No certificates earned yet.</div>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-800">
                        <thead class="bg-gray-50 dark:bg-white/[0.02]">
                            <tr>
                                <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Certificate #</th>
                                <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Module</th>
                                <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Issued</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @foreach($user->certificates->sortByDesc('issued_at') as $certificate)
                            <tr class="hover:bg-gray-50 dark:hover:bg-white/[0.02]">
                                <td class="px-5 py-3 text-sm font-mono text-gray-900 dark:text-white">{{ $certificate->certificate_number }}</td>
                                <td class="px-5 py-3 text-sm text-gray-700 dark:text-gray-300">{{ $certificate->module_title }}</td>
                                <td class="px-5 py-3 text-sm text-gray-500 dark:text-gray-400">{{ optional($certificate->issued_at)->format('M d, Y') }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
        @endif
    </div>

    {{-- Quick Actions Sidebar --}}
    <div>
        <div class="rounded-2xl bg-white dark:bg-white/[0.03] border border-gray-200 dark:border-gray-800 shadow-theme-xs p-5">
            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-4">Quick Actions</h3>
            <div class="space-y-2">
                <a href="{{ route('admin.users.edit', $user) }}" class="flex items-center gap-3 w-full px-4 py-2.5 rounded-lg border border-gray-200 dark:border-gray-700 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-white/5 transition-colors">
                    <svg class="w-4 h-4 text-warning-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    Edit Profile
                </a>
                <a href="{{ route('admin.payments.index') }}?search={{ $user->email }}" class="flex items-center gap-3 w-full px-4 py-2.5 rounded-lg border border-gray-200 dark:border-gray-700 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-white/5 transition-colors">
                    <svg class="w-4 h-4 text-success-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                    View Payments
                </a>
                <a href="{{ route('admin.subscribers.index') }}?search={{ $user->email }}" class="flex items-center gap-3 w-full px-4 py-2.5 rounded-lg border border-gray-200 dark:border-gray-700 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-white/5 transition-colors">
                    <svg class="w-4 h-4 text-brand-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                    View Subscriptions
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
