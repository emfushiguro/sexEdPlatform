@extends('layouts.instructor')
@section('title', 'Create Quiz')
@section('page-title', 'Create Quiz')
@section('content')

<div class="mb-5">
    <a href="{{ route('instructor.quizzes.index') }}" class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-brand-500 dark:text-gray-400 dark:hover:text-brand-400 transition-colors">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        Back to Quizzes
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
    selectedModule: '{{ old('module_id') }}',
    selectedLesson: '{{ old('lesson_id', $lessonId ?? '') }}',
    allLessons: {{ json_encode($modules->flatMap(function($module) {
        return $module->lessons->map(function($lesson) use ($module) {
            return [
                'id' => $lesson->id,
                'module_id' => $module->id,
                'title' => $lesson->title,
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
            <h3 class="text-base font-semibold text-gray-900 dark:text-white">New Quiz Details</h3>
        </div>
        <form method="POST" action="{{ route('instructor.quizzes.store') }}" class="p-6 space-y-5">
            @csrf

            <!-- Info Banner -->
            <div class="rounded-xl bg-brand-50 dark:bg-brand-500/10 border border-brand-200 dark:border-brand-500/20 p-4">
                <div class="flex items-start gap-3">
                    <svg class="w-5 h-5 text-brand-500 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <p class="text-sm text-brand-700 dark:text-brand-400">
                        You can attach this quiz to either a <strong>module</strong> (for module-level assessment) 
                        or a <strong>lesson</strong> (for topic-specific quiz), but not both.
                    </p>
                </div>
            </div>

            <div>
                <label for="title" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Title</label>
                <input type="text" name="title" id="title" value="{{ old('title') }}" required
                       class="w-full px-3 py-2.5 rounded-lg border border-gray-200 dark:border-gray-700 bg-transparent text-sm text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-brand-500/30 focus:border-brand-500 transition">
            </div>

            <div>
                <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Description</label>
                <textarea name="description" id="description" rows="3"
                          class="w-full px-3 py-2.5 rounded-lg border border-gray-200 dark:border-gray-700 bg-transparent text-sm text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-brand-500/30 focus:border-brand-500 transition">{{ old('description') }}</textarea>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Attach to Module</label>
                <select name="module_id" x-model="selectedModule"
                        :disabled="selectedLesson != ''"
                        @change="if($event.target.value) selectedLesson = ''"
                        class="w-full px-3 py-2.5 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 text-sm text-gray-700 dark:text-gray-300 focus:outline-none focus:ring-2 focus:ring-brand-500/30 transition"
                        :class="{ 'opacity-50 cursor-not-allowed': selectedLesson != '' }">
                    <option value="">Select Module (Optional)</option>
                    @foreach($modules as $module)
                    <option value="{{ $module->id }}">{{ $module->title }}</option>
                    @endforeach
                </select>
                <p x-show="selectedModule" class="mt-1 text-xs text-brand-600 dark:text-brand-400">This quiz will be attached to the selected module</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Or Attach to Lesson</label>
                <select name="lesson_id" x-model="selectedLesson"
                        :disabled="selectedModule != ''"
                        @change="if($event.target.value) selectedModule = ''"
                        class="w-full px-3 py-2.5 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 text-sm text-gray-700 dark:text-gray-300 focus:outline-none focus:ring-2 focus:ring-brand-500/30 transition"
                        :class="{ 'opacity-50 cursor-not-allowed': selectedModule != '' }">
                    <option value="">Select Lesson (Optional)</option>
                    <template x-for="lesson in filteredLessons" :key="lesson.id">
                        <option :value="lesson.id" :selected="selectedLesson == lesson.id"
                                x-text="selectedModule ? lesson.title : (lesson.module_title + ' - ' + lesson.title)"></option>
                    </template>
                </select>
                <p x-show="selectedLesson" class="mt-1 text-xs text-success-600 dark:text-success-400">This quiz will be attached to the selected lesson</p>
            </div>

            <div>
                <label for="passing_score" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Passing Score (%)</label>
                <input type="number" name="passing_score" id="passing_score" value="{{ old('passing_score', 70) }}" required min="0" max="100"
                       class="w-full px-3 py-2.5 rounded-lg border border-gray-200 dark:border-gray-700 bg-transparent text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-brand-500/30 focus:border-brand-500 transition">
            </div>

            <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-100 dark:border-gray-800">
                @if($lessonId)
                    @php $lesson = \App\Models\Lesson::find($lessonId); @endphp
                    <a href="{{ $lesson ? route('instructor.lessons.show', $lesson) : route('instructor.quizzes.index') }}" class="px-4 py-2 rounded-lg text-sm text-gray-600 dark:text-gray-400 border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-white/5 transition-colors">Cancel</a>
                @else
                    <a href="{{ route('instructor.quizzes.index') }}" class="px-4 py-2 rounded-lg text-sm text-gray-600 dark:text-gray-400 border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-white/5 transition-colors">Cancel</a>
                @endif
                <button type="submit" class="px-6 py-2 rounded-lg bg-brand-500 hover:bg-brand-600 text-white text-sm font-medium shadow-theme-xs transition-colors">Create Quiz</button>
            </div>
        </form>
    </div>
</div>
@endsection
