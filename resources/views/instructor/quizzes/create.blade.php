@extends('layouts.instructor-app')

@section('content')
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
                                 You can attach this quiz to either a <strong>module</strong> (for module-level assessment) 
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
                                ðŸ“˜ This quiz will be attached to the selected module
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
                                ðŸ“— This quiz will be attached to the selected lesson
                            </p>
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700">Passing Score (%)</label>
                            <input type="number" name="passing_score" value="{{ old('passing_score', 70) }}" required min="0" max="100"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        </div>

                        <div class="mb-4 grid gap-4 sm:grid-cols-3">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Hours</label>
                                <input type="number" name="time_limit_hours" min="0" value="{{ old('time_limit_hours', 0) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Minutes</label>
                                <input type="number" name="time_limit_minutes" min="0" max="59" value="{{ old('time_limit_minutes', 0) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Seconds</label>
                                <input type="number" name="time_limit_seconds" min="0" max="59" value="{{ old('time_limit_seconds', 0) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700">Attempt Limit</label>
                            <input type="number" name="attempt_limit" min="1" value="{{ old('attempt_limit') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" placeholder="Leave empty for unlimited">
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
@endsection
