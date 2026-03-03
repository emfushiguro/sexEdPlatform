<x-app-layout>
    <x-slot name="header">
        <x-breadcrumb :items="[
            ['label' => 'Dashboard', 'url' => route('instructor.dashboard')],
            ['label' => 'Quizzes', 'url' => route('instructor.quizzes.index')],
            ['label' => 'Create']
        ]" />
        
        <div class="flex items-center space-x-3 mt-4">
            <a href="{{ route('instructor.quizzes.index') }}" class="text-gray-600 hover:text-gray-900">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
            </a>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Create New Quiz</h2>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6" x-data="{
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
                    <form method="POST" action="{{ route('instructor.quizzes.store') }}">
                        @csrf

                        <!-- Helper Text -->
                        <div class="mb-6 p-4 bg-blue-50 border-l-4 border-blue-500 rounded">
                            <p class="text-sm text-blue-800">
                                ℹ️ You can attach this quiz to either a <strong>module</strong> (for module-level assessment) 
                                or a <strong>lesson</strong> (for topic-specific quiz), but not both.
                            </p>
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700">Title</label>
                            <input type="text" name="title" value="{{ old('title') }}" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700">Description</label>
                            <textarea name="description" rows="3"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">{{ old('description') }}</textarea>
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700">Attach to Module</label>
                            <select name="module_id" 
                                x-model="selectedModule"
                                :disabled="selectedLesson != ''"
                                @change="if($event.target.value) selectedLesson = ''"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                :class="{ 'opacity-50 cursor-not-allowed': selectedLesson != '' }">
                                <option value="">Select Module (Optional)</option>
                                @foreach($modules as $module)
                                <option value="{{ $module->id }}">{{ $module->title }}</option>
                                @endforeach
                            </select>
                            <p x-show="selectedModule" class="mt-1 text-sm text-blue-600">
                                📘 This quiz will be attached to the selected module
                            </p>
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700">Or Attach to Lesson</label>
                            <select name="lesson_id" 
                                x-model="selectedLesson"
                                :disabled="selectedModule != ''"
                                @change="if($event.target.value) selectedModule = ''"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                :class="{ 'opacity-50 cursor-not-allowed': selectedModule != '' }">
                                <option value="">Select Lesson (Optional)</option>
                                <template x-for="lesson in filteredLessons" :key="lesson.id">
                                    <option :value="lesson.id" :selected="selectedLesson == lesson.id"
                                            x-text="selectedModule ? lesson.title : (lesson.module_title + ' - ' + lesson.title)"></option>
                                </template>
                            </select>
                            <p x-show="selectedLesson" class="mt-1 text-sm text-green-600">
                                📗 This quiz will be attached to the selected lesson
                            </p>
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700">Passing Score (%)</label>
                            <input type="number" name="passing_score" value="{{ old('passing_score', 70) }}" required min="0" max="100"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        </div>

                        <div class="flex items-center justify-end gap-4 mt-6">
                            @if($lessonId)
                                @php
                                    $lesson = \App\Models\Lesson::find($lessonId);
                                @endphp
                                <a href="{{ $lesson ? route('instructor.lessons.show', $lesson) : route('instructor.quizzes.index') }}" 
                                   class="text-gray-600 hover:text-gray-900">Cancel</a>
                            @else
                                <a href="{{ route('instructor.quizzes.index') }}" class="text-gray-600 hover:text-gray-900">Cancel</a>
                            @endif
                            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">Create Quiz</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
