@extends('layouts.instructor')
@section('title', 'Edit Lesson')
@section('page-title', 'Edit Lesson')
@section('content')

<div class="mb-5">
    <a href="{{ route('instructor.lessons.show', $lesson) }}" class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-brand-500 dark:text-gray-400 dark:hover:text-brand-400 transition-colors">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        Back to Lesson
    </a>
</div>

@if($errors->any())
<div class="mb-5 rounded-xl bg-error-50 border border-error-200 dark:bg-error-500/10 dark:border-error-500/20 px-4 py-3">
    <ul class="list-disc list-inside text-sm text-error-700 dark:text-error-400 space-y-1">
        @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
    </ul>
</div>
@endif

<div class="max-w-2xl">
    <div class="rounded-2xl bg-white dark:bg-white/[0.03] border border-gray-200 dark:border-gray-800 shadow-theme-xs overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-800">
            <h3 class="text-base font-semibold text-gray-900 dark:text-white">Edit Lesson Details</h3>
        </div>
        <form method="POST" action="{{ route('instructor.lessons.update', $lesson) }}" class="p-6 space-y-5">
            @csrf
            @method('PUT')

            <div>
                <label for="module_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Module <span class="text-error-500">*</span></label>
                <select name="module_id" id="module_id" required
                        class="w-full px-3 py-2.5 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 text-sm text-gray-700 dark:text-gray-300 focus:outline-none focus:ring-2 focus:ring-brand-500/30">
                    <option value="">Select Module</option>
                    @foreach($modules as $module)
                        <option value="{{ $module->id }}" {{ old('module_id', $lesson->module_id) == $module->id ? 'selected' : '' }}>
                            {{ $module->title }}
                        </option>
                    @endforeach
                </select>
                @error('module_id')<p class="mt-1 text-xs text-error-600 dark:text-error-400">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="title" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Lesson Title <span class="text-error-500">*</span></label>
                <input type="text" name="title" id="title" value="{{ old('title', $lesson->title) }}" required
                       placeholder="e.g., Understanding Your Body: Reproductive Anatomy"
                       class="w-full px-3 py-2.5 rounded-lg border border-gray-200 dark:border-gray-700 bg-transparent text-sm text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-brand-500/30 focus:border-brand-500 transition">
                @error('title')<p class="mt-1 text-xs text-error-600 dark:text-error-400">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Description <span class="text-error-500">*</span></label>
                <textarea name="description" id="description" rows="4" required
                          placeholder="Brief overview of what this lesson covers..."
                          class="w-full px-3 py-2.5 rounded-lg border border-gray-200 dark:border-gray-700 bg-transparent text-sm text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-brand-500/30 focus:border-brand-500 transition">{{ old('description', $lesson->description) }}</textarea>
                <p class="mt-1 text-xs text-gray-400">This will appear as the lesson summary for learners</p>
                @error('description')<p class="mt-1 text-xs text-error-600 dark:text-error-400">{{ $message }}</p>@enderror
            </div>

            <!-- Info Box -->
            <div class="rounded-xl bg-brand-50 dark:bg-brand-500/10 border border-brand-200 dark:border-brand-500/20 p-4">
                <div class="flex items-start gap-3">
                    <svg class="w-5 h-5 text-brand-500 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <p class="text-sm text-brand-700 dark:text-brand-400"><strong>Note:</strong> To manage topics (videos, texts, quizzes, etc.), go back to the lesson overview page.</p>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-100 dark:border-gray-800">
                <a href="{{ route('instructor.lessons.show', $lesson) }}" class="px-4 py-2 rounded-lg text-sm text-gray-600 dark:text-gray-400 border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-white/5 transition-colors">Cancel</a>
                <button type="submit" class="px-6 py-2 rounded-lg bg-brand-500 hover:bg-brand-600 text-white text-sm font-medium shadow-theme-xs transition-colors">Update Lesson</button>
            </div>
        </form>
    </div>
</div>
@endsection
