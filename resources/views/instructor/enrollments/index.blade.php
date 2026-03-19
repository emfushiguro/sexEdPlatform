@extends('layouts.instructor-app')

@php
    $allEnrollments = $pendingEnrollments ?? collect();
    $statuses = ['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected'];
    $statusCounts = [
        'all' => $allEnrollments->count(),
        'pending' => $allEnrollments->where('status', 'pending')->count(),
        'approved' => $allEnrollments->where('status', 'approved')->count(),
        'rejected' => $allEnrollments->where('status', 'rejected')->count(),
    ];
@endphp

@section('content')
<div x-data="{ currentStatus: 'pending' }" class="space-y-5" data-enrollment-list>

    {{-- Page Header --}}
    <div class="flex items-center justify-between mb-6">
        <div class="border-l-4 pl-3" style="border-color: #730DB1;">
            <h1 class="text-xl font-bold text-gray-900 dark:text-white">Enrollments</h1>
            <p class="text-xs text-gray-400 dark:text-gray-500">Manage learner enrollment requests</p>
        </div>
    </div>

    {{-- Status Filter Tabs --}}
    <div class="flex items-center gap-1 bg-gray-100 dark:bg-gray-800 rounded-xl p-1 flex-shrink-0 flex-wrap">
        @foreach(['all' => 'All', 'pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected'] as $tab => $label)
        <button
            @click="currentStatus = '{{ $tab }}'"
            :class="currentStatus === '{{ $tab }}'
                ? 'bg-white dark:bg-gray-700 text-gray-900 dark:text-white shadow-sm font-semibold'
                : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 font-medium'"
            class="px-3 py-1.5 rounded-lg text-sm transition-all">
            {{ $label }}
            <span class="ml-1 text-xs font-bold">{{ $statusCounts[$tab] ?? 0 }}</span>
        </button>
        @endforeach
    </div>

    {{-- Enrollments Container --}}
    @if($allEnrollments->isNotEmpty())
        <div class="grid grid-cols-1 gap-3">
            @foreach($allEnrollments as $enrollment)
                <div
                    x-show="currentStatus === 'all' || currentStatus === '{{ $enrollment->status }}'"
                    x-transition:enter="transition duration-150 ease-in"
                    x-transition:enter-start="opacity-0"
                    x-transition:enter-end="opacity-100"
                    x-transition:leave="transition duration-150 ease-out"
                    x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                    class="rounded-2xl bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 p-4 flex items-center justify-between gap-4 hover:border-purple-200 dark:hover:border-purple-900/40 transition-colors">

                    {{-- Learner Info --}}
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold text-gray-900 dark:text-white truncate">
                            {{ $enrollment->user->first_name ?? $enrollment->user->name }}
                            @if($enrollment->user->last_name)
                            <span class="font-normal">{{ $enrollment->user->last_name }}</span>
                            @endif
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ $enrollment->user->email }}</p>
                        <p class="text-xs text-purple-600 dark:text-purple-400 font-medium mt-1 truncate">
                            {{ $enrollment->module->title ?? 'Unknown Module' }}
                        </p>
                    </div>

                    {{-- Status Badge + Request Time --}}
                    <div class="flex items-center gap-3 flex-shrink-0">
                        {{-- Status Badge --}}
                        @if($enrollment->status === 'pending')
                            <span class="inline-flex items-center text-[10px] font-bold uppercase tracking-widest px-2.5 py-1 rounded-full bg-amber-100 text-amber-700 border border-amber-200 dark:bg-amber-900/30 dark:text-amber-400 dark:border-amber-900/60">Pending</span>
                        @elseif($enrollment->status === 'approved')
                            <span class="inline-flex items-center text-[10px] font-bold uppercase tracking-widest px-2.5 py-1 rounded-full bg-emerald-100 text-emerald-700 border border-emerald-200 dark:bg-emerald-900/30 dark:text-emerald-400 dark:border-emerald-900/60">Approved</span>
                        @else
                            <span class="inline-flex items-center text-[10px] font-bold uppercase tracking-widest px-2.5 py-1 rounded-full bg-red-100 text-red-600 border border-red-200 dark:bg-red-900/30 dark:text-red-400 dark:border-red-900/60">Rejected</span>
                        @endif

                        {{-- Requested Date --}}
                        <span class="text-xs text-gray-400 dark:text-gray-500 flex-shrink-0 whitespace-nowrap">{{ $enrollment->created_at->format('M d') }}</span>
                    </div>

                    {{-- Actions --}}
                    @if($enrollment->status === 'pending')
                        <div class="flex items-center gap-2 flex-shrink-0">
                            <a href="{{ route('instructor.enrollments.show', $enrollment) }}"
                               class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-semibold text-purple-600 dark:text-purple-400 bg-purple-50 dark:bg-purple-900/30 border border-purple-200 dark:border-purple-900/60 rounded-lg hover:bg-purple-100 dark:hover:bg-purple-900/50 transition-colors">
                                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                                View
                            </a>
                            <form method="POST" action="{{ route('instructor.enrollments.approve', $enrollment) }}" class="inline" onsubmit="return confirm('Approve this enrollment?')">
                                @csrf
                                @method('PATCH')
                                <button type="submit"
                                        class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-semibold text-emerald-700 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-900/30 border border-emerald-200 dark:border-emerald-900/60 rounded-lg hover:bg-emerald-100 dark:hover:bg-emerald-900/50 transition-colors">
                                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    Approve
                                </button>
                            </form>
                            <form method="POST" action="{{ route('instructor.enrollments.reject', $enrollment) }}" class="inline" onsubmit="return confirm('Reject this enrollment?')">
                                @csrf
                                @method('PATCH')
                                <button type="submit"
                                        class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-semibold text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-900/60 rounded-lg hover:bg-red-100 dark:hover:bg-red-900/50 transition-colors">
                                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                    Reject
                                </button>
                            </form>
                        </div>
                    @else
                        <div class="text-xs text-gray-400 dark:text-gray-500 flex-shrink-0">No actions</div>
                    @endif
                </div>
            @endforeach
        </div>
    @else
        {{-- Empty State --}}
        <div class="rounded-2xl bg-gray-50 dark:bg-gray-800/50 border border-dashed border-gray-200 dark:border-gray-700 p-12 text-center">
            <div class="flex justify-center mb-4">
                <div class="w-12 h-12 rounded-xl bg-gray-100 dark:bg-gray-700 flex items-center justify-center">
                    <svg class="w-6 h-6 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                    </svg>
                </div>
            </div>
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-1">No enrollment requests</h3>
            <p class="text-xs text-gray-500 dark:text-gray-400">Enrollments will appear here as learners request access to your modules.</p>
        </div>
    @endif

</div>
@endsection
