@extends('layouts.instructor-app')

@section('title', 'Manage Learners')

@php
    $ageGroupLabel = static function ($user) {
        if ($user->role === 'parent') {
            return 'Parent';
        }

        $age = $user->age;

        if (is_null($age) && $user->birthdate) {
            $age = now()->diffInYears($user->birthdate);
        }

        if (is_null($age)) {
            return 'N/A';
        }

        if ($age < 13) {
            return 'Child';
        }

        if ($age < 18) {
            return 'Teen';
        }

        return 'Adult';
    };
@endphp

@section('content')
<div class="space-y-5">
    <div class="flex items-end justify-between gap-4">
        <div>
            <h1 class="text-xl font-bold text-gray-900 dark:text-white">Manage Learners</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400">Learners enrolled in your modules (view-only).</p>
        </div>
    </div>

    <div class="rounded-2xl bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm table-standard-numbering">
                <thead>
                    <tr class="bg-gray-50 dark:bg-gray-700/40 border-b border-gray-100 dark:border-gray-700">
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-widest text-gray-500 dark:text-gray-400">No</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-widest text-gray-500 dark:text-gray-400">Learner Name</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-widest text-gray-500 dark:text-gray-400">Role</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-widest text-gray-500 dark:text-gray-400">Modules Enrolled</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-widest text-gray-500 dark:text-gray-400">Last Activity Page</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-widest text-gray-500 dark:text-gray-400">View</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse($users as $index => $user)
                        @php
                            $latestActivity = $user->activityLogs->first();
                            $lastActivityPage = $latestActivity?->metadata['page']
                                ?? $latestActivity?->metadata['url']
                                ?? $latestActivity?->description
                                ?? 'N/A';
                        @endphp
                        <tr class="hover:bg-gray-50/80 dark:hover:bg-gray-700/20 transition-colors">
                            <td class="px-4 py-3 text-gray-500 dark:text-gray-400">{{ $index + 1 }}</td>
                            <td class="px-4 py-3">
                                <p class="font-semibold text-gray-900 dark:text-white">{{ $user->full_name ?: $user->name }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $user->email }}</p>
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-300">
                                    {{ $ageGroupLabel($user) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-gray-700 dark:text-gray-300 font-medium">{{ $user->instructor_modules_enrolled_count }}</td>
                            <td class="px-4 py-3 text-gray-600 dark:text-gray-300 max-w-[340px] truncate" title="{{ $lastActivityPage }}">{{ $lastActivityPage }}</td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('instructor.users.show', $user) }}"
                                              class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-gray-500 hover:text-purple-700 hover:bg-purple-50 dark:hover:bg-purple-900/20 transition-colors action-icon-standard"
                                   title="View learner">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                        <circle cx="12" cy="12" r="3"/>
                                    </svg>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-10 text-center text-sm text-gray-500 dark:text-gray-400">
                                No enrolled learners found for your modules.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
