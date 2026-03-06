@extends('layouts.admin')
@section('title', 'Email Announcements')
@section('page-title', 'Email Announcements')
@section('content')

<div class="mb-5 flex items-center gap-3 px-4 py-3 rounded-xl bg-brand-50 border border-brand-200 text-brand-700 dark:bg-brand-500/10 dark:border-brand-500/20 dark:text-brand-400 text-sm">
    <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
    Email broadcast backend coming soon. This is a UI preview.
</div>

{{-- Stats --}}
<div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
    @foreach([['label'=>'Sent','value'=>'128','bg'=>'bg-success-50 dark:bg-success-500/10','color'=>'text-success-600 dark:text-success-400'],['label'=>'Opened','value'=>'89%','bg'=>'bg-brand-50 dark:bg-brand-500/10','color'=>'text-brand-600 dark:text-brand-400'],['label'=>'Subscribers','value'=>'1,204','bg'=>'bg-purple-50 dark:bg-purple-500/10','color'=>'text-purple-600 dark:text-purple-400'],['label'=>'Drafts','value'=>'3','bg'=>'bg-warning-50 dark:bg-warning-500/10','color'=>'text-warning-600 dark:text-warning-400']] as $c)
    <div class="rounded-2xl bg-white dark:bg-white/[0.03] border border-gray-200 dark:border-gray-800 shadow-theme-xs p-5">
        <div class="w-10 h-10 rounded-xl {{ $c['bg'] }} flex items-center justify-center mb-3">
            <span class="text-sm font-bold {{ $c['color'] }}">{{ $c['value'] }}</span>
        </div>
        <p class="text-xs text-gray-400 dark:text-gray-500">{{ $c['label'] }}</p>
    </div>
    @endforeach
</div>

<div class="rounded-2xl bg-white dark:bg-white/[0.03] border border-gray-200 dark:border-gray-800 shadow-theme-xs overflow-hidden">
    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 dark:border-gray-800">
        <h3 class="text-base font-semibold text-gray-900 dark:text-white">Email History</h3>
        <a href="{{ route('admin.emails.compose') }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-brand-500 hover:bg-brand-600 text-white text-sm font-medium shadow-theme-xs transition-colors">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Compose
        </a>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-800">
            <thead class="bg-gray-50 dark:bg-white/[0.02]">
                <tr>
                    <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Subject</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Recipients</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Sent At</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Open Rate</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                @foreach([
                    ['subject'=>'Welcome to SexEd Learning Platform','to'=>'All Users (1,204)','sent'=>'Jul 1, 2025 8:00 AM','rate'=>'92%','status'=>'sent'],
                    ['subject'=>'July Seminar: Reproductive Health Awareness','to'=>'Learners (842)','sent'=>'Jun 28, 2025 2:00 PM','rate'=>'87%','status'=>'sent'],
                    ['subject'=>'New Modules Available: Consent & Boundaries','to'=>'Premium (320)','sent'=>'Jun 20, 2025 9:00 AM','rate'=>'94%','status'=>'sent'],
                    ['subject'=>'Platform Maintenance Notice','to'=>'All Users (1,204)','sent'=>'-','rate'=>'-','status'=>'draft'],
                ] as $email)
                <tr class="hover:bg-gray-50 dark:hover:bg-white/[0.02] transition-colors">
                    <td class="px-5 py-3 text-sm font-medium text-gray-900 dark:text-white">{{ $email['subject'] }}</td>
                    <td class="px-5 py-3 text-sm text-gray-500 dark:text-gray-400">{{ $email['to'] }}</td>
                    <td class="px-5 py-3 text-sm text-gray-500 dark:text-gray-400">{{ $email['sent'] }}</td>
                    <td class="px-5 py-3 text-sm font-semibold {{ $email['rate'] !== '-' ? 'text-success-600 dark:text-success-400' : 'text-gray-400' }}">{{ $email['rate'] }}</td>
                    <td class="px-5 py-3"><span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $email['status'] === 'sent' ? 'bg-success-50 text-success-700 dark:bg-success-500/10 dark:text-success-400' : 'bg-warning-50 text-warning-700 dark:bg-warning-500/10 dark:text-warning-400' }}">{{ ucfirst($email['status']) }}</span></td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
