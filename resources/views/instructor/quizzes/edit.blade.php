@extends('layouts.instructor')
@section('title', 'Edit Quiz')
@section('page-title', 'Edit Quiz')
@section('content')

<div class="mb-5">
    <a href="{{ route('instructor.quizzes.show', $quiz) }}" class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-brand-500 dark:text-gray-400 dark:hover:text-brand-400 transition-colors">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        Back to Quiz
    </a>
</div>

@if($errors->any())
<div class="mb-5 rounded-xl bg-error-50 border border-error-200 dark:bg-error-500/10 dark:border-error-500/20 px-4 py-3">
    <ul class="list-disc list-inside text-sm text-error-700 dark:text-error-400 space-y-1">
        @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
    </ul>
</div>
@endif

<div class="max-w-2xl" x-data="{
    selectedModule: '{{ old('module_id', $quiz->module_id ?? '') }}',
    selectedLesson: '{{ old('lesson_id', $quiz->lesson_id ?? '') }}',
    allLessons: {{ json_encode($modules->flatMap(function($module) {
        return $module->lessons->map(function($lesson) use ($module) {
            return [
                'id' => $lesson->id,
                'title' => $lesson->title,
                'module_id' => $module->id,
                'module_title' => $module->title
            ];
        });
    })) }},
    get filteredLessons() {
        if (!this.selectedModule) return this.allLessons;
        return this.allLessons.filter(lesson => lesson.module_id == this.selectedModule);
    }
}">
    <div class="rounded-2xl bg-white dark:bg-white/[0.03] border border-gray-200 dark:border-gray-800 shadow-theme-xs overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-800">
            <h3 class="text-base font-semibold text-gray-900 dark:text-white">Edit Quiz Details</h3>
        </div>
        <form method="POST" action="{{ route('instructor.quizzes.update', $quiz) }}" class="p-6 space-y-5">
            @csrf
            @method('PUT')

            <div>
                <label for="title" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Title</label>
                <input type="text" name="title" id="title" value="{{ old('title', $quiz->title) }}" required
                       class="w-full px-3 py-2.5 rounded-lg border border-gray-200 dark:border-gray-700 bg-transparent text-sm text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-brand-500/30 focus:border-brand-500 transition">
            </div>

            <div>
                <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Description</label>
                <textarea name="description" id="description" rows="3"
                          class="w-full px-3 py-2.5 rounded-lg border border-gray-200 dark:border-gray-700 bg-transparent text-sm text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-brand-500/30 focus:border-brand-500 transition">{{ old('description', $quiz->description) }}</textarea>
            </div>

            <!-- Quiz Attachment -->
            <div class="rounded-xl bg-brand-50 dark:bg-brand-500/10 border border-brand-200 dark:border-brand-500/20 p-5">
                <h4 class="text-sm font-semibold text-brand-900 dark:text-brand-300 mb-1">Quiz Attachment</h4>
                <p class="text-xs text-brand-700 dark:text-brand-400 mb-4">Choose either a module OR a lesson — not both. This determines when learners see this quiz.</p>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Attach to Module</label>
                        <select name="module_id" x-model="selectedModule"
                                :disabled="selectedLesson != ''"
                                @change="if($event.target.value) selectedLesson = ''"
                                class="w-full px-3 py-2.5 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 text-sm text-gray-700 dark:text-gray-300 focus:outline-none focus:ring-2 focus:ring-brand-500/30 transition"
                                :class="selectedLesson != '' ? 'opacity-50 cursor-not-allowed' : ''">
                            <option value="">None</option>
                            @foreach($modules as $module)
                            <option value="{{ $module->id }}" {{ old('module_id', $quiz->module_id) == $module->id ? 'selected' : '' }}>{{ $module->title }}</option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-success-600 dark:text-success-400" x-show="selectedModule != ''" x-cloak>Quiz will appear after completing all lessons</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Attach to Lesson</label>
                        <select name="lesson_id" x-model="selectedLesson"
                                :disabled="selectedModule != ''"
                                @change="if($event.target.value) selectedModule = ''"
                                class="w-full px-3 py-2.5 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 text-sm text-gray-700 dark:text-gray-300 focus:outline-none focus:ring-2 focus:ring-brand-500/30 transition"
                                :class="selectedModule != '' ? 'opacity-50 cursor-not-allowed' : ''">
                            <option value="">None</option>
                            <template x-for="lesson in filteredLessons" :key="lesson.id">
                                <option :value="lesson.id" :selected="lesson.id == '{{ old('lesson_id', $quiz->lesson_id ?? '') }}'">
                                    <span x-text="selectedModule ? lesson.title : lesson.module_title + ' - ' + lesson.title"></span>
                                </option>
                            </template>
                        </select>
                        <p class="mt-1 text-xs text-success-600 dark:text-success-400" x-show="selectedLesson != ''" x-cloak>Quiz will appear after completing lesson topics</p>
                    </div>
                </div>

                @error('module_id')<p class="mt-2 text-xs text-error-600 dark:text-error-400">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="passing_score" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Passing Score (%)</label>
                <input type="number" name="passing_score" id="passing_score" value="{{ old('passing_score', $quiz->passing_score) }}" required min="0" max="100"
                       class="w-full px-3 py-2.5 rounded-lg border border-gray-200 dark:border-gray-700 bg-transparent text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-brand-500/30 focus:border-brand-500 transition">
            </div>

            <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-100 dark:border-gray-800">
                <a href="{{ route('instructor.quizzes.show', $quiz) }}" class="px-4 py-2 rounded-lg text-sm text-gray-600 dark:text-gray-400 border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-white/5 transition-colors">Cancel</a>
                <button type="submit" class="px-6 py-2 rounded-lg bg-brand-500 hover:bg-brand-600 text-white text-sm font-medium shadow-theme-xs transition-colors">Update Quiz</button>
            </div>
        </form>
    </div>
</div>
@endsection
