@extends('layouts.instructor')
@section('title', $module->title)
@section('page-title', $module->title)
@section('content')

<div class="mb-5 flex items-center justify-between">
    <a href="{{ route('instructor.modules.index') }}" class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-brand-500 dark:text-gray-400 dark:hover:text-brand-400 transition-colors">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        Back to Modules
    </a>
    <div class="flex items-center gap-2">
        <a href="{{ route('instructor.lessons.create', ['module_id' => $module->id]) }}" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg bg-success-500 hover:bg-success-600 text-white text-sm font-medium shadow-theme-xs transition-colors">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
            Add Lesson
        </a>
        <a href="{{ route('instructor.modules.edit', $module) }}" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg border border-gray-200 dark:border-gray-700 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-white/5 transition-colors">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
            Edit Module
        </a>
    </div>
</div>

<!-- Module Info Card -->
<div class="rounded-2xl bg-white dark:bg-white/[0.03] border border-gray-200 dark:border-gray-800 shadow-theme-xs overflow-hidden mb-6">
    <div class="p-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Thumbnail -->
            <div>
                @if($module->thumbnail)
                    <img src="{{ asset('storage/' . $module->thumbnail) }}" alt="{{ $module->title }}" class="w-full rounded-xl shadow-theme-xs object-cover">
                @else
                    <div class="w-full h-48 bg-gray-100 dark:bg-white/[0.03] rounded-xl flex items-center justify-center border border-gray-200 dark:border-gray-700">
                        <svg class="w-12 h-12 text-gray-300 dark:text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    </div>
                @endif
            </div>

            <!-- Module Details -->
            <div class="md:col-span-2">
                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">{{ $module->title }}</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-5">{{ $module->description }}</p>

                <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                    <div class="rounded-xl bg-success-50 dark:bg-success-500/10 p-3.5">
                        <div class="text-xs font-medium text-success-600 dark:text-success-400">Duration</div>
                        <div class="text-lg font-semibold text-success-900 dark:text-success-300 mt-0.5">{{ $module->duration_minutes }} min</div>
                    </div>
                    <div class="rounded-xl bg-brand-50 dark:bg-brand-500/10 p-3.5">
                        <div class="text-xs font-medium text-brand-600 dark:text-brand-400">Lessons</div>
                        <div class="text-lg font-semibold text-brand-900 dark:text-brand-300 mt-0.5">{{ $module->lessons->count() }}</div>
                    </div>
                    <div class="rounded-xl {{ $module->is_published ? 'bg-success-50 dark:bg-success-500/10' : 'bg-warning-50 dark:bg-warning-500/10' }} p-3.5">
                        <div class="text-xs font-medium {{ $module->is_published ? 'text-success-600 dark:text-success-400' : 'text-warning-600 dark:text-warning-400' }}">Status</div>
                        <div class="text-lg font-semibold {{ $module->is_published ? 'text-success-900 dark:text-success-300' : 'text-warning-900 dark:text-warning-300' }} mt-0.5">{{ $module->is_published ? 'Published' : 'Draft' }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Lessons List -->
<div class="rounded-2xl bg-white dark:bg-white/[0.03] border border-gray-200 dark:border-gray-800 shadow-theme-xs overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between">
        <h3 class="text-base font-semibold text-gray-900 dark:text-white">Lessons ({{ $module->lessons->count() }})</h3>
        <a href="{{ route('instructor.lessons.create', ['module_id' => $module->id]) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-brand-500 hover:bg-brand-600 text-white text-xs font-medium shadow-theme-xs transition-colors">
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
            Add Lesson
        </a>
    </div>

    <div class="p-6">
        @if($module->lessons->count() > 0)
            <div class="space-y-2">
                @foreach($module->lessons->sortBy('order') as $lesson)
                    <div class="rounded-xl border border-gray-200 dark:border-gray-700 p-4 hover:bg-gray-50 dark:hover:bg-white/[0.02] transition-colors" draggable="true">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-4 flex-1 min-w-0">
                                <!-- Drag Handle -->
                                <div class="cursor-move text-gray-300 dark:text-gray-600 flex-shrink-0">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M7 2a2 2 0 10.001 4.001A2 2 0 007 2zm0 6a2 2 0 10.001 4.001A2 2 0 007 8zm0 6a2 2 0 10.001 4.001A2 2 0 007 14zm6-8a2 2 0 10-.001-4.001A2 2 0 0013 6zm0 2a2 2 0 10.001 4.001A2 2 0 0013 8zm0 6a2 2 0 10.001 4.001A2 2 0 0013 14z"/></svg>
                                </div>

                                <!-- Order Badge -->
                                <div class="bg-gray-100 dark:bg-white/[0.05] rounded-full w-8 h-8 flex items-center justify-center text-xs font-semibold text-gray-700 dark:text-gray-300 flex-shrink-0">
                                    {{ $lesson->order }}
                                </div>

                                <!-- Lesson Info -->
                                <div class="flex-1 min-w-0">
                                    <div class="text-sm font-semibold text-gray-900 dark:text-white">{{ $lesson->title }}</div>
                                    @if($lesson->description)
                                        <div class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ Str::limit($lesson->description, 100) }}</div>
                                    @endif
                                    <div class="flex items-center gap-2 mt-1.5">
                                        <span class="px-2 py-0.5 text-[10px] font-medium rounded-full bg-brand-50 text-brand-700 dark:bg-brand-500/10 dark:text-brand-400">{{ strtoupper($lesson->type) }}</span>
                                        @if($lesson->duration)
                                            <span class="text-xs text-gray-400 dark:text-gray-500">{{ $lesson->duration }} min</span>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="flex items-center gap-1 flex-shrink-0 ml-3">
                                @if(!$loop->first)
                                    <form action="{{ route('instructor.lessons.move', $lesson) }}" method="POST" class="inline">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="direction" value="up">
                                        <button type="submit" class="p-1.5 rounded-lg text-gray-400 hover:bg-gray-100 dark:hover:bg-white/[0.05] hover:text-gray-600 dark:hover:text-gray-300 transition-colors" title="Move Up">
                                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z" clip-rule="evenodd"/></svg>
                                        </button>
                                    </form>
                                @endif
                                @if(!$loop->last)
                                    <form action="{{ route('instructor.lessons.move', $lesson) }}" method="POST" class="inline">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="direction" value="down">
                                        <button type="submit" class="p-1.5 rounded-lg text-gray-400 hover:bg-gray-100 dark:hover:bg-white/[0.05] hover:text-gray-600 dark:hover:text-gray-300 transition-colors" title="Move Down">
                                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                                        </button>
                                    </form>
                                @endif
                                <a href="{{ route('instructor.lessons.show', $lesson) }}" class="p-1.5 rounded-lg text-gray-400 hover:bg-brand-50 hover:text-brand-600 dark:hover:bg-brand-500/10 dark:hover:text-brand-400 transition-colors" title="View">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                </a>
                                <a href="{{ route('instructor.lessons.edit', $lesson) }}" class="p-1.5 rounded-lg text-gray-400 hover:bg-brand-50 hover:text-brand-600 dark:hover:bg-brand-500/10 dark:hover:text-brand-400 transition-colors" title="Edit">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </a>
                                <form action="{{ route('instructor.lessons.destroy', $lesson) }}" method="POST" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="p-1.5 rounded-lg text-gray-400 hover:bg-error-50 hover:text-error-600 dark:hover:bg-error-500/10 dark:hover:text-error-400 transition-colors" title="Delete" onclick="return confirm('Delete this lesson?')">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-center py-12">
                <svg class="mx-auto h-12 w-12 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                <p class="mt-3 text-sm text-gray-500 dark:text-gray-400">No lessons yet. Start building your module!</p>
                <a href="{{ route('instructor.lessons.create', ['module_id' => $module->id]) }}" class="mt-4 inline-flex items-center gap-1.5 px-4 py-2 rounded-lg bg-brand-500 hover:bg-brand-600 text-white text-sm font-medium shadow-theme-xs transition-colors">
                    Create First Lesson
                </a>
            </div>
        @endif
    </div>
</div>
@endsection
