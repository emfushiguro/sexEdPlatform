<x-app-layout>
    <x-slot name="header">
        <x-breadcrumb :items="[
            ['label' => 'Dashboard', 'url' => route('admin.dashboard')],
            ['label' => 'Quizzes', 'url' => route('admin.quizzes.index')],
            ['label' => $quiz->title]
        ]" />
        
        <div class="flex justify-between items-center mt-4">
            <div class="flex items-center space-x-3">
                <a href="{{ route('admin.quizzes.index') }}" class="text-gray-600 hover:text-gray-900">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                </a>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Quiz: {{ $quiz->title }}</h2>
            </div>
            <a href="{{ route('admin.quizzes.add-question', $quiz) }}" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">Add Question</a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">{{ session('success') }}</div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <h3 class="text-lg font-semibold mb-2">Quiz Details</h3>
                    <p class="text-gray-700">{{ $quiz->description }}</p>
                    <div class="mt-4 grid grid-cols-3 gap-4">
                        <div>
                            <span class="text-sm text-gray-500">Passing Score:</span>
                            <span class="font-semibold">{{ $quiz->passing_score }}%</span>
                        </div>
                        <div>
                            <span class="text-sm text-gray-500">Time Limit:</span>
                            <span class="font-semibold">{{ $quiz->time_limit ? $quiz->time_limit . ' min' : 'No limit' }}</span>
                        </div>
                        <div>
                            <span class="text-sm text-gray-500">Total Questions:</span>
                            <span class="font-semibold">{{ $quiz->questions->count() }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold mb-4">Questions</h3>
                    @forelse($quiz->questions as $question)
                    <div class="border-b border-gray-200 pb-4 mb-4 last:border-0">
                        <div class="flex justify-between items-start">
                            <div class="flex-1">
                                <p class="font-semibold text-gray-900">{{ $loop->iteration }}. {{ $question->question }}</p>
                                <p class="text-sm text-gray-500 mt-1">Type: {{ ucfirst(str_replace('_', ' ', $question->type)) }} | Points: {{ $question->points }}</p>
                                
                                @if($question->options->count() > 0)
                                <div class="mt-2 ml-4">
                                    @foreach($question->options as $option)
                                    <div class="flex items-center gap-2 mt-1">
                                        <span class="{{ $option->is_correct ? 'text-green-600 font-semibold' : 'text-gray-600' }}">
                                            {{ chr(65 + $loop->index) }}. {{ $option->option_text }}
                                            @if($option->is_correct) ✓ @endif
                                        </span>
                                    </div>
                                    @endforeach
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                    @empty
                    <p class="text-gray-500 text-center py-4">No questions yet. Add questions to make this quiz active.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
