<x-app-layout>
    <x-slot name="header">
        <x-breadcrumb :items="[
            ['label' => 'Dashboard', 'url' => route('instructor.dashboard')],
            ['label' => 'Modules', 'url' => route('instructor.modules.index')],
            ['label' => $lesson->module->title, 'url' => route('instructor.modules.show', $lesson->module)],
            ['label' => $lesson->title]
        ]" />
        
        <div class="flex items-center justify-between mt-4">
            <div class="flex items-center space-x-3">
                <a href="{{ route('instructor.modules.show', $lesson->module) }}" class="text-gray-600 hover:text-gray-900">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                </a>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $lesson->title }}</h2>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('instructor.lessons.edit', $lesson) }}" 
                    class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition">
                    Edit Lesson
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            
            <!-- Lesson Details Card -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Lesson Details</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div>
                            <p class="text-sm text-gray-500">Module</p>
                            <p class="font-medium text-gray-900">{{ $lesson->module->title }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Duration</p>
                            <p class="font-medium text-gray-900">
                                {{ $lesson->topics()->sum('duration') ?? 0 }} minutes
                                <span class="text-xs text-gray-500">(auto-calculated)</span>
                            </p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Order</p>
                            <p class="font-medium text-gray-900">Lesson {{ $lesson->order }}</p>
                        </div>
                    </div>

                    <div class="mt-4">
                        <p class="text-sm text-gray-500">Description</p>
                        <p class="text-gray-900 mt-1">{{ $lesson->description ?? 'No description provided' }}</p>
                    </div>
                </div>
            </div>

            <!-- Topics Management Card -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">Lesson Topics</h3>
                            <p class="text-sm text-gray-500 mt-1">Manage the content sections for this lesson</p>
                        </div>
                        <div class="flex gap-3">
                            <a href="{{ route('instructor.quizzes.create', ['lesson_id' => $lesson->id]) }}" 
                                class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg text-sm font-medium flex items-center gap-2 transition">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                                </svg>
                                Create Quiz
                            </a>
                            <a href="{{ route('instructor.topics.create', ['lesson' => $lesson->id]) }}" 
                                class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm font-medium flex items-center gap-2 transition">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                                </svg>
                                Add Topic
                            </a>
                        </div>
                    </div>

                    @if($lesson->topics->isEmpty())
                        <div class="text-center py-12 bg-gray-50 rounded-lg border-2 border-dashed border-gray-300">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            <p class="mt-4 text-gray-500">No topics added yet</p>
                            <p class="text-sm text-gray-400 mt-1">Click "Add Topic" to create content sections</p>
                        </div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Order</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Title</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Duration</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Prerequisite</th>
                                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($lesson->topics()->ordered()->get() as $topic)
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                {{ $topic->order }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm font-medium text-gray-900">{{ $topic->title }}</div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                    {{ $topic->type === 'video' ? 'bg-red-100 text-red-800' : '' }}
                                                    {{ $topic->type === 'text' ? 'bg-blue-100 text-blue-800' : '' }}
                                                    {{ $topic->type === 'worksheet' ? 'bg-green-100 text-green-800' : '' }}
                                                    {{ $topic->type === 'quiz' ? 'bg-purple-100 text-purple-800' : '' }}
                                                    {{ $topic->type === 'interactive' ? 'bg-orange-100 text-orange-800' : '' }}">
                                                    {{ ucfirst($topic->type) }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                {{ $topic->duration }} min
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                @if($topic->is_prerequisite)
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                                        Required
                                                    </span>
                                                @else
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                                                        Optional
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                <div class="flex items-center justify-end gap-2">
                                                    <a href="{{ route('instructor.topics.edit', $topic) }}" 
                                                        class="text-blue-600 hover:text-blue-900">Edit</a>
                                                    <form action="{{ route('instructor.topics.destroy', $topic) }}" method="POST" 
                                                        onsubmit="return confirm('Are you sure you want to delete this topic?');"
                                                        class="inline">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="text-red-600 hover:text-red-900">Delete</button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Quiz Section -->
            @php
                $lessonQuiz = $lesson->quizzes()->where('is_active', true)->first();
            @endphp
            @if($lessonQuiz)
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-4">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900">Lesson Quiz</h3>
                                <p class="text-sm text-gray-500 mt-1">Quiz linked to this lesson</p>
                            </div>
                            <a href="{{ route('instructor.quizzes.edit', $lessonQuiz) }}" 
                                class="text-blue-600 hover:text-blue-900 text-sm font-medium">
                                Edit Quiz
                            </a>
                        </div>
                        
                        <div class="bg-purple-50 border-l-4 border-purple-500 p-4 rounded">
                            <div class="flex items-start gap-4">
                                <div class="flex-shrink-0">
                                    <svg class="w-8 h-8 text-purple-600" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"/>
                                        <path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd"/>
                                    </svg>
                                </div>
                                <div class="flex-1">
                                    <h4 class="font-semibold text-purple-900 mb-1">{{ $lessonQuiz->title }}</h4>
                                    <p class="text-sm text-purple-700 mb-3">{{ $lessonQuiz->description }}</p>
                                    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 text-sm">
                                        <div class="bg-white rounded p-2">
                                            <span class="text-gray-500">Questions:</span>
                                            <span class="font-semibold text-gray-900 ml-1">{{ $lessonQuiz->questions->count() }}</span>
                                        </div>
                                        <div class="bg-white rounded p-2">
                                            <span class="text-gray-500">Time Limit:</span>
                                            <span class="font-semibold text-gray-900 ml-1">{{ $lessonQuiz->time_limit ?? 'None' }} min</span>
                                        </div>
                                        <div class="bg-white rounded p-2">
                                            <span class="text-gray-500">Passing Score:</span>
                                            <span class="font-semibold text-gray-900 ml-1">{{ $lessonQuiz->passing_score }}%</span>
                                        </div>
                                        <div class="bg-white rounded p-2">
                                            <span class="text-gray-500">Status:</span>
                                            <span class="font-semibold {{ $lessonQuiz->is_active ? 'text-green-600' : 'text-red-600' }} ml-1">
                                                {{ $lessonQuiz->is_active ? 'Active' : 'Inactive' }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

        </div>
    </div>
</x-app-layout>
