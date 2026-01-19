<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Edit Quiz</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <form method="POST" action="{{ route('admin.quizzes.update', $quiz) }}">
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
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">{{ old('description', $quiz->description) }}</textarea>
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700">Module</label>
                            <select name="module_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                                <option value="">Select Module (Optional)</option>
                                @foreach($modules as $module)
                                <option value="{{ $module->id }}" {{ $quiz->module_id == $module->id ? 'selected' : '' }}>{{ $module->title }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700">Or Lesson</label>
                            <select name="lesson_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                                <option value="">Select Lesson (Optional)</option>
                                @foreach($modules as $module)
                                    @foreach($module->lessons as $lesson)
                                    <option value="{{ $lesson->id }}" {{ $quiz->lesson_id == $lesson->id ? 'selected' : '' }}>{{ $module->title }} - {{ $lesson->title }}</option>
                                    @endforeach
                                @endforeach
                            </select>
                        </div>

                        <div class="grid grid-cols-2 gap-4 mb-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Passing Score (%)</label>
                                <input type="number" name="passing_score" value="{{ old('passing_score', $quiz->passing_score) }}" required min="0" max="100"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Time Limit (minutes)</label>
                                <input type="number" name="time_limit" value="{{ old('time_limit', $quiz->time_limit) }}" min="1"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            </div>
                        </div>

                        <div class="flex items-center justify-end gap-4">
                            <a href="{{ route('admin.quizzes.index') }}" class="text-gray-600 hover:text-gray-900">Cancel</a>
                            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">Update Quiz</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
