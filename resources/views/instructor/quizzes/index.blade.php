<x-app-layout>
    <x-slot name="header">
        <x-breadcrumb :items="[
            ['label' => 'Dashboard', 'url' => route('instructor.dashboard')],
            ['label' => 'Quizzes']
        ]" />
        
        <div class="flex justify-between items-center mt-4">
            <div class="flex items-center space-x-3">
                <a href="{{ route('instructor.dashboard') }}" class="text-gray-600 hover:text-gray-900">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                </a>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Quiz Management</h2>
            </div>
            <button @click="$store.modals.openQuizModal()" 
                    class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                Create New Quiz
            </button>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <form method="GET" class="flex gap-4">
                        <select name="module_id" class="rounded-md border-gray-300 shadow-sm">
                            <option value="">All Modules</option>
                            @foreach($modules as $mod)
                            <option value="{{ $mod->id }}" {{ request('module_id') == $mod->id ? 'selected' : '' }}>{{ $mod->title }}</option>
                            @endforeach
                        </select>
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">Filter</button>
                    </form>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Title</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Module/Lesson</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Passing Score</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($quizzes as $quiz)
                            <tr>
                                <td class="px-6 py-4">
                                    <div class="font-medium text-gray-900">{{ $quiz->title }}</div>
                                    <div class="text-sm text-gray-500">{{ Str::limit($quiz->description, 60) }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    @if($quiz->module)
                                        <span class="text-sm text-blue-600">Module: {{ $quiz->module->title }}</span>
                                    @elseif($quiz->lesson)
                                        <span class="text-sm text-purple-600">Lesson: {{ $quiz->lesson->title }}</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4">{{ $quiz->passing_score }}%</td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <a href="{{ route('instructor.quizzes.show', $quiz) }}" class="text-blue-600 hover:text-blue-900 mr-3">View</a>
                                    <a href="{{ route('instructor.quizzes.edit', $quiz) }}" class="text-indigo-600 hover:text-indigo-900 mr-3">Edit</a>
                                    <form action="{{ route('instructor.quizzes.destroy', $quiz) }}" method="POST" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-900" onclick="return confirm('Delete?')">Delete</button>
                                    </form>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="px-6 py-4 text-center text-gray-500">No quizzes found</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                    <div class="mt-4">{{ $quizzes->links() }}</div>
                </div>
            </div>
        </div>

        <!-- Quiz Creation Modal -->
        @include('instructor.quizzes.partials.quiz-modal')
    </div>
</x-app-layout>
