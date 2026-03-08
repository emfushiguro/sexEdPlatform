@extends('layouts.instructor')
@section('title', 'Create Module')
@section('page-title', 'Create Module')
@section('content')

<div class="mb-5">
    <a href="{{ route('instructor.modules.index') }}" class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-brand-500 dark:text-gray-400 dark:hover:text-brand-400 transition-colors">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        Back to Modules
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
            <h3 class="text-base font-semibold text-gray-900 dark:text-white">New Module Details</h3>
        </div>
        <form method="POST" action="{{ route('instructor.modules.store') }}" enctype="multipart/form-data" class="p-6 space-y-5">
            @csrf

            <div>
                <label for="title" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Title</label>
                <input type="text" name="title" id="title" value="{{ old('title') }}" required
                       class="w-full px-3 py-2.5 rounded-lg border border-gray-200 dark:border-gray-700 bg-transparent text-sm text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-brand-500/30 focus:border-brand-500 transition">
                @error('title')<p class="mt-1 text-xs text-error-600 dark:text-error-400">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Description</label>
                <textarea name="description" id="description" rows="3" required
                          class="w-full px-3 py-2.5 rounded-lg border border-gray-200 dark:border-gray-700 bg-transparent text-sm text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-brand-500/30 focus:border-brand-500 transition">{{ old('description') }}</textarea>
                @error('description')<p class="mt-1 text-xs text-error-600 dark:text-error-400">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Thumbnail Image</label>
                <input type="file" name="thumbnail" accept="image/*"
                       class="w-full text-sm text-gray-500 dark:text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-brand-50 file:text-brand-700 hover:file:bg-brand-100 dark:file:bg-brand-500/10 dark:file:text-brand-400">
                <p class="mt-1 text-xs text-gray-400">Recommended: 800x450px, max 2MB</p>
                @error('thumbnail')<p class="mt-1 text-xs text-error-600 dark:text-error-400">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="age_bracket" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Age Bracket <span class="text-error-500">*</span></label>
                <select name="age_bracket" id="age_bracket" required
                        class="w-full px-3 py-2.5 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 text-sm text-gray-700 dark:text-gray-300 focus:outline-none focus:ring-2 focus:ring-brand-500/30">
                    <option value="">Select target age group</option>
                    <option value="kids" {{ old('age_bracket') === 'kids' ? 'selected' : '' }}>Kids (5-12 years)</option>
                    <option value="teens" {{ old('age_bracket') === 'teens' ? 'selected' : '' }}>Teens (13-17 years)</option>
                    <option value="adults" {{ old('age_bracket') === 'adults' ? 'selected' : '' }}>Adults (18+ years)</option>
                </select>
                <p class="mt-1 text-xs text-gray-400">Select the age group this module is designed for</p>
                @error('age_bracket')<p class="mt-1 text-xs text-error-600 dark:text-error-400">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Enrollment Mode <span class="text-error-500">*</span></label>
                <div class="space-y-3">
                    <label class="flex items-start p-4 rounded-xl border border-gray-200 dark:border-gray-700 cursor-pointer hover:bg-gray-50 dark:hover:bg-white/[0.02] transition-colors">
                        <input type="radio" name="enrollment_mode" value="auto"
                            {{ old('enrollment_mode', 'auto') === 'auto' ? 'checked' : '' }}
                            class="mt-1 h-4 w-4 text-brand-500 focus:ring-brand-500 border-gray-300 dark:border-gray-600">
                        <div class="ml-3">
                            <span class="text-sm font-medium text-gray-900 dark:text-white">Open Enrollment (Auto-approve)</span>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Learners can enroll immediately and access module content right away</p>
                        </div>
                    </label>
                    <label class="flex items-start p-4 rounded-xl border border-gray-200 dark:border-gray-700 cursor-pointer hover:bg-gray-50 dark:hover:bg-white/[0.02] transition-colors">
                        <input type="radio" name="enrollment_mode" value="manual"
                            {{ old('enrollment_mode') === 'manual' ? 'checked' : '' }}
                            class="mt-1 h-4 w-4 text-brand-500 focus:ring-brand-500 border-gray-300 dark:border-gray-600">
                        <div class="ml-3">
                            <span class="text-sm font-medium text-gray-900 dark:text-white">Manual Approval (Gated Access)</span>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">You must review and approve each enrollment request before learners can access content</p>
                        </div>
                    </label>
                </div>
                @error('enrollment_mode')<p class="mt-1 text-xs text-error-600 dark:text-error-400">{{ $message }}</p>@enderror
            </div>

            <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-100 dark:border-gray-800">
                <a href="{{ route('instructor.modules.index') }}" class="px-4 py-2 rounded-lg text-sm text-gray-600 dark:text-gray-400 border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-white/5 transition-colors">Cancel</a>
                <button type="submit" name="action" value="draft" class="px-4 py-2 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-white/5 transition-colors">Save as Draft</button>
                <button type="submit" name="action" value="publish" class="px-6 py-2 rounded-lg bg-brand-500 hover:bg-brand-600 text-white text-sm font-medium shadow-theme-xs transition-colors">Create & Publish</button>
            </div>
        </form>
    </div>
</div>
@endsection
