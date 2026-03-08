@extends('layouts.instructor')
@section('title', 'Module Management')
@section('page-title', 'Module Management')
@section('content')

{{-- Stat Cards --}}
@php
    $pendingCount = \App\Models\ModuleEnrollment::pending()->count();
    $totalModules = $modules->total();
    $publishedCount = $modules->filter(fn($m) => $m->is_published)->count();
@endphp
<div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
    @php
        $cards = [
            ['label'=>'Total Modules',    'value'=>$totalModules,   'icon'=>'M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10', 'ring'=>'ring-brand-200 dark:ring-brand-800', 'bg'=>'bg-brand-50 dark:bg-brand-500/10', 'color'=>'text-brand-600 dark:text-brand-400'],
            ['label'=>'Published',        'value'=>$publishedCount, 'icon'=>'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z', 'ring'=>'ring-success-200 dark:ring-success-800', 'bg'=>'bg-success-50 dark:bg-success-500/10', 'color'=>'text-success-600 dark:text-success-400'],
            ['label'=>'Total Lessons',    'value'=>$modules->sum('lessons_count'), 'icon'=>'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253', 'ring'=>'ring-purple-200 dark:ring-purple-800', 'bg'=>'bg-purple-50 dark:bg-purple-500/10', 'color'=>'text-purple-600 dark:text-purple-400'],
            ['label'=>'Pending Enrollments','value'=>$pendingCount, 'icon'=>'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z', 'ring'=>'ring-warning-200 dark:ring-warning-800', 'bg'=>'bg-warning-50 dark:bg-warning-500/10', 'color'=>'text-warning-600 dark:text-warning-400'],
        ];
    @endphp
    @foreach($cards as $c)
    <div class="rounded-2xl bg-white dark:bg-white/[0.03] border border-gray-200 dark:border-gray-800 shadow-theme-xs p-5 ring-1 {{ $c['ring'] }}">
        <div class="w-10 h-10 rounded-xl {{ $c['bg'] }} flex items-center justify-center mb-3">
            <svg class="w-5 h-5 {{ $c['color'] }}" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $c['icon'] }}"/></svg>
        </div>
        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $c['value'] }}</p>
        <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">{{ $c['label'] }}</p>
    </div>
    @endforeach
</div>

{{-- Pending Enrollment Alert --}}
@if($pendingCount > 0)
<div class="mb-5 rounded-xl bg-warning-50 dark:bg-warning-500/10 border border-warning-200 dark:border-warning-500/20 px-4 py-3 flex items-center justify-between">
    <div class="flex items-center gap-2">
        <svg class="w-4 h-4 text-warning-500" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
        </svg>
        <p class="text-sm text-warning-700 dark:text-warning-400"><span class="font-medium">{{ $pendingCount }}</span> enrollment {{ $pendingCount === 1 ? 'request' : 'requests' }} waiting for review</p>
    </div>
    <a href="{{ route('instructor.enrollments.index') }}" class="text-sm font-medium text-warning-700 dark:text-warning-400 hover:underline">Review Now →</a>
</div>
@endif

{{-- Table Card --}}
<div class="rounded-2xl bg-white dark:bg-white/[0.03] border border-gray-200 dark:border-gray-800 shadow-theme-xs overflow-hidden">
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 px-6 py-4 border-b border-gray-100 dark:border-gray-800">
        <h3 class="text-base font-semibold text-gray-900 dark:text-white">All Modules</h3>
        <a href="{{ route('instructor.modules.create') }}"
           class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-brand-500 hover:bg-brand-600 text-white text-sm font-medium shadow-theme-xs transition-colors">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Create Module
        </a>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-800">
            <thead class="bg-gray-50 dark:bg-white/[0.02]">
                <tr>
                    <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Module</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Duration</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Lessons</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Quizzes</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                    <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                @forelse($modules as $module)
                <tr class="hover:bg-gray-50 dark:hover:bg-white/[0.02] transition-colors">
                    <td class="px-5 py-3">
                        <div class="flex items-center gap-3">
                            @if($module->thumbnail)
                                <img src="{{ asset('storage/' . $module->thumbnail) }}" alt="" class="w-10 h-10 rounded-lg object-cover flex-shrink-0">
                            @else
                                <div class="w-10 h-10 rounded-lg bg-brand-50 dark:bg-brand-500/10 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-5 h-5 text-brand-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                                </div>
                            @endif
                            <div>
                                <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $module->title }}</p>
                                <p class="text-xs text-gray-400 dark:text-gray-500">{{ Str::limit($module->description, 50) }}</p>
                            </div>
                        </div>
                    </td>
                    <td class="px-5 py-3 text-sm text-gray-500 dark:text-gray-400">{{ $module->duration_minutes }} min</td>
                    <td class="px-5 py-3 text-sm text-gray-500 dark:text-gray-400">{{ $module->lessons_count }}</td>
                    <td class="px-5 py-3 text-sm text-gray-500 dark:text-gray-400">{{ $module->quizzes_count }}</td>
                    <td class="px-5 py-3">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $module->is_published ? 'bg-success-50 text-success-700 dark:bg-success-500/10 dark:text-success-400' : 'bg-gray-100 text-gray-500 dark:bg-white/5 dark:text-gray-400' }}">
                            {{ $module->is_published ? 'Published' : 'Draft' }}
                        </span>
                    </td>
                    <td class="px-5 py-3">
                        <div class="flex items-center justify-end gap-1">
                            <a href="{{ route('instructor.modules.show', $module) }}" title="View" class="p-1.5 rounded-lg text-gray-400 hover:bg-brand-50 hover:text-brand-600 dark:hover:bg-brand-500/10 dark:hover:text-brand-400 transition-colors">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            </a>
                            <a href="{{ route('instructor.modules.edit', $module) }}" title="Edit" class="p-1.5 rounded-lg text-gray-400 hover:bg-warning-50 hover:text-warning-600 dark:hover:bg-warning-500/10 dark:hover:text-warning-400 transition-colors">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            </a>
                            <form action="{{ route('instructor.modules.destroy', $module) }}" method="POST" class="inline" onsubmit="return confirm('Delete this module and all its lessons?')">
                                @csrf @method('DELETE')
                                <button type="submit" title="Delete" class="p-1.5 rounded-lg text-gray-400 hover:bg-error-50 hover:text-error-600 dark:hover:bg-error-500/10 dark:hover:text-error-400 transition-colors">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="px-5 py-12 text-center text-sm text-gray-400 dark:text-gray-500">No modules found. Create your first module to get started.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($modules->hasPages())
    <div class="px-6 py-4 border-t border-gray-100 dark:border-gray-800">{{ $modules->links() }}</div>
    @endif
</div>
@endsection
