@extends('layouts.instructor-app')

@section('content')
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6" x-data="{
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
                    <form method="POST" action="{{ route('instructor.quizzes.update', $quiz) }}">
                        @csrf
                        @method('PUT')

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700">Title</label>
                            <input type="text" name="title" value="{{ old('title', $quiz->title) }}" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700">Description</label>
                            <textarea name="description" rows="3"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ old('description', $quiz->description) }}</textarea>
                        </div>

                        <!-- Quiz Attachment -->
                        <div class="mb-6 p-4 bg-blue-50 rounded-lg border border-blue-200">
                            <h3 class="text-sm font-semibold text-blue-900 mb-3">ðŸ“Œ Quiz Attachment</h3>
                            <p class="text-xs text-blue-700 mb-4">Choose either a module OR a lesson - not both. This determines when learners see this quiz.</p>
                            
                            <div class="grid grid-cols-2 gap-4">
                                <!-- Module Selection -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        ðŸ“˜ Attach to Module
                                        <span class="text-xs font-normal text-gray-500">(after all lessons)</span>
                                    </label>
                                    <select name="module_id" 
                                            x-model="selectedModule"
                                            :disabled="selectedLesson != ''"
                                            @change="if($event.target.value) selectedLesson = ''"
                                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                            :class="selectedLesson != '' ? 'bg-gray-100 cursor-not-allowed' : ''">
                                        <option value="">None</option>
                                        @foreach($modules as $module)
                                        <option value="{{ $module->id }}" {{ old('module_id', $quiz->module_id) == $module->id ? 'selected' : '' }}>
                                            {{ $module->title }}
                                        </option>
                                        @endforeach
                                    </select>
                                    <p class="mt-1 text-xs text-gray-500" x-show="selectedModule != ''" style="display: none;">
                                        âœ… Quiz will appear after completing all lessons
                                    </p>
                                </div>

                                <!-- Lesson Selection -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        ðŸ“— Attach to Lesson
                                        <span class="text-xs font-normal text-gray-500">(after lesson topics)</span>
                                    </label>
                                    <select name="lesson_id"
                                            x-model="selectedLesson"
                                            :disabled="selectedModule != ''"
                                            @change="if($event.target.value) selectedModule = ''"
                                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                            :class="selectedModule != '' ? 'bg-gray-100 cursor-not-allowed' : ''">
                                        <option value="">None</option>
                                        <template x-for="lesson in filteredLessons" :key="lesson.id">
                                            <option :value="lesson.id" 
                                                    :selected="lesson.id == '{{ old('lesson_id', $quiz->lesson_id ?? '') }}'">
                                                <span x-text="selectedModule ? lesson.title : lesson.module_title + ' - ' + lesson.title"></span>
                                            </option>
                                        </template>
                                    </select>
                                    <p class="mt-1 text-xs text-gray-500" x-show="selectedLesson != ''" style="display: none;">
                                        âœ… Quiz will appear after completing lesson topics
                                    </p>
                                </div>
                            </div>

                            @error('module_id')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700">Passing Score (%)</label>
                            <input type="number" name="passing_score" value="{{ old('passing_score', $quiz->passing_score) }}" required min="0" max="100"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        </div>

                        <div class="flex items-center justify-end gap-4">
                            <a href="{{ route('instructor.quizzes.show', $quiz) }}" class="text-gray-600 hover:text-gray-900">Cancel</a>
                            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">Update Quiz</button>
                        </div>
                    </form>
                </div>
            </div>
@endsection
