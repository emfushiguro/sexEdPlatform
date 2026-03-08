@extends('layouts.instructor')
@section('title', 'Enrollment Requests')
@section('page-title', 'Enrollment Requests')
@section('content')

<div class="rounded-2xl bg-white dark:bg-white/[0.03] border border-gray-200 dark:border-gray-800 shadow-theme-xs overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-800">
        <h3 class="text-base font-semibold text-gray-900 dark:text-white">Pending Enrollment Requests</h3>
    </div>

    @if($pendingEnrollments->count() > 0)
    <div class="overflow-x-auto">
        <table class="min-w-full">
            <thead>
                <tr class="bg-gray-50 dark:bg-white/[0.02]">
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Learner</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Module</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Requested</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                @foreach($pendingEnrollments as $enrollment)
                <tr class="hover:bg-gray-50 dark:hover:bg-white/[0.02] transition-colors">
                    <td class="px-6 py-4">
                        <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $enrollment->user->name }}</div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">{{ $enrollment->user->email }}</div>
                        @if($enrollment->user->learnerProfile)
                            <div class="text-xs text-gray-400 dark:text-gray-500">{{ $enrollment->user->learnerProfile->username }}</div>
                        @endif
                    </td>
                    <td class="px-6 py-4">
                        <div class="text-sm text-gray-900 dark:text-white">{{ $enrollment->module->title }}</div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">Age: {{ $enrollment->module->min_age }}–{{ $enrollment->module->max_age }} yrs</div>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400 whitespace-nowrap">
                        {{ $enrollment->created_at->diffForHumans() }}
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-1.5">
                            <a href="{{ route('instructor.enrollments.show', $enrollment) }}"
                               class="p-1.5 rounded-lg text-gray-400 hover:bg-brand-50 hover:text-brand-600 dark:hover:bg-brand-500/10 dark:hover:text-brand-400 transition-colors" title="View Details">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            </a>
                            <form method="POST" action="{{ route('instructor.enrollments.approve', $enrollment) }}" class="inline">
                                @csrf
                                @method('PATCH')
                                <button type="submit" onclick="return confirm('Approve this enrollment request?')"
                                    class="p-1.5 rounded-lg text-gray-400 hover:bg-success-50 hover:text-success-600 dark:hover:bg-success-500/10 dark:hover:text-success-400 transition-colors" title="Approve">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                </button>
                            </form>
                            <form method="POST" action="{{ route('instructor.enrollments.reject', $enrollment) }}" class="inline">
                                @csrf
                                @method('PATCH')
                                <button type="submit" onclick="return confirm('Reject this enrollment request?')"
                                    class="p-1.5 rounded-lg text-gray-400 hover:bg-error-50 hover:text-error-600 dark:hover:bg-error-500/10 dark:hover:text-error-400 transition-colors" title="Reject">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="px-6 py-4 border-t border-gray-100 dark:border-gray-800">
        {{ $pendingEnrollments->links() }}
    </div>
    @else
    <div class="text-center py-14">
        <svg class="mx-auto h-10 w-10 text-gray-300 dark:text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
        <h3 class="mt-3 text-sm font-semibold text-gray-900 dark:text-white">No pending requests</h3>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">All enrollment requests have been processed.</p>
    </div>
    @endif
</div>
@endsection
