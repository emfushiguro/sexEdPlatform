<x-app-layout>
    <x-slot name="header">
        <x-breadcrumb :items="[
            ['label' => 'Dashboard', 'url' => route('admin.dashboard')],
            ['label' => 'Quizzes', 'url' => route('admin.quizzes.index')],
            ['label' => 'Create']
        ]" />
        
        <div class="flex items-center space-x-3 mt-4">
            <a href="{{ route('admin.quizzes.index') }}" class="text-gray-600 hover:text-gray-900">
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
                <div class="p-6">
                    <form method="POST" action="{{ route('admin.quizzes.store') }}">
                        @csrf

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
                            <select name="module_id" id="module_select"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                                <option value="">Select Module (Optional)</option>
                                @foreach($modules as $module)
                                <option value="{{ $module->id }}">{{ $module->title }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700">Or Attach to Lesson</label>
                            <select name="lesson_id" id="lesson_select"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                                <option value="">Select Lesson (Optional)</option>
                                @foreach($modules as $module)
                                    @foreach($module->lessons as $lesson)
                                    <option value="{{ $lesson->id }}" {{ (old('lesson_id') ?? $lessonId ?? '') == $lesson->id ? 'selected' : '' }}>
                                        {{ $module->title }} - {{ $lesson->title }}
                                    </option>
                                    @endforeach
                                @endforeach
                            </select>
                            <p class="mt-1 text-sm text-gray-500">Select either module or lesson, not both</p>
                        </div>

                        <div class="grid grid-cols-2 gap-4 mb-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Passing Score (%)</label>
                                <input type="number" name="passing_score" value="{{ old('passing_score', 70) }}" required min="0" max="100"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Time Limit (minutes, optional)</label>
                                <input type="number" name="time_limit" value="{{ old('time_limit') }}" min="1"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            </div>
                        </div>

                        <div class="flex items-center justify-end gap-4">
                            <a href="{{ route('admin.quizzes.index') }}" class="text-gray-600 hover:text-gray-900">Cancel</a>
                            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">Create Quiz</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
