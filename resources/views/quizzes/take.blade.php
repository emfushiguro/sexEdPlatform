<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ $quiz->title }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <!-- Quiz Header -->
                    <div class="mb-6 pb-6 border-b">
                        <h3 class="text-2xl font-bold text-gray-900 mb-2">{{ $quiz->title }}</h3>
                        @if($quiz->description)
                            <p class="text-gray-600 mb-4">{{ $quiz->description }}</p>
                        @endif
                        <div class="flex gap-6 text-sm text-gray-700">
                            <span class="flex items-center gap-2">
                                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                </svg>
                                {{ $quiz->questions->count() }} Questions
                            </span>
                            <span class="flex items-center gap-2">
                                <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"></path>
                                </svg>
                                Passing Score: {{ $quiz->passing_score }}%
                            </span>
                            @if($quiz->time_limit)
                                <span class="flex items-center gap-2">
                                    <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    {{ $quiz->time_limit }} minutes
                                </span>
                            @endif
                        </div>
                    </div>

                    <!-- Quiz Form -->
                    <form method="POST" action="{{ route('quizzes.submit', $quiz) }}" id="quizForm">
                        @csrf

                        <div class="space-y-8">
                            @foreach($quiz->questions as $index => $question)
                                <div class="p-6 bg-gray-50 rounded-lg border border-gray-200">
                                    <!-- Question Header -->
                                    <div class="flex items-start justify-between mb-4">
                                        <div class="flex-1">
                                            <div class="flex items-center gap-3 mb-2">
                                                <span class="flex-shrink-0 w-8 h-8 bg-blue-600 text-white rounded-full flex items-center justify-center font-semibold">
                                                    {{ $index + 1 }}
                                                </span>
                                                <h4 class="text-lg font-semibold text-gray-900">{{ $question->question_text }}</h4>
                                            </div>
                                            <div class="flex gap-2 ml-11">
                                                @if($question->question_type === 'multiple_choice')
                                                    <span class="px-2 py-1 text-xs font-medium bg-blue-100 text-blue-700 rounded">Multiple Choice</span>
                                                @elseif($question->question_type === 'true_false')
                                                    <span class="px-2 py-1 text-xs font-medium bg-green-100 text-green-700 rounded">True/False</span>
                                                @elseif($question->question_type === 'multiple_select')
                                                    <span class="px-2 py-1 text-xs font-medium bg-purple-100 text-purple-700 rounded">Multiple Select</span>
                                                @endif
                                                <span class="px-2 py-1 text-xs font-medium bg-gray-200 text-gray-700 rounded">{{ $question->points }} {{ $question->points === 1 ? 'point' : 'points' }}</span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Options -->
                                    <div class="space-y-3 ml-11">
                                        @if($question->question_type === 'multiple_select')
                                            <!-- Multiple Select - Checkboxes -->
                                            <p class="text-sm text-gray-600 italic mb-3">Select all that apply:</p>
                                            @foreach($question->options as $option)
                                                <label class="flex items-center p-3 bg-white border-2 border-gray-300 rounded-lg cursor-pointer hover:bg-blue-50 hover:border-blue-400 transition">
                                                    <input type="checkbox" 
                                                           name="answers[{{ $question->id }}][]" 
                                                           value="{{ $option->id }}"
                                                           class="w-5 h-5 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                                                    <span class="ml-3 text-gray-800">{{ $option->option_text }}</span>
                                                </label>
                                            @endforeach
                                        @else
                                            <!-- Single Select - Radio Buttons -->
                                            @foreach($question->options as $option)
                                                <label class="flex items-center p-3 bg-white border-2 border-gray-300 rounded-lg cursor-pointer hover:bg-blue-50 hover:border-blue-400 transition">
                                                    <input type="radio" 
                                                           name="answers[{{ $question->id }}]" 
                                                           value="{{ $option->id }}"
                                                           required
                                                           class="w-5 h-5 text-blue-600 border-gray-300 focus:ring-blue-500">
                                                    <span class="ml-3 text-gray-800">{{ $option->option_text }}</span>
                                                </label>
                                            @endforeach
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <!-- Submit Button -->
                        <div class="mt-8 flex gap-4">
                            <a href="{{ route('learner.modules.index') }}" 
                               class="px-6 py-3 bg-gray-600 hover:bg-gray-700 text-white font-semibold rounded-lg transition">
                                Cancel
                            </a>
                            <button type="submit" 
                                    class="flex-1 px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition"
                                    onclick="return confirm('Are you sure you want to submit your answers? You cannot change them after submission.')">
                                Submit Quiz
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @if($quiz->time_limit)
    <script>
        // Simple timer (optional - can be enhanced)
        let timeLimit = {{ $quiz->time_limit }} * 60; // Convert to seconds
        let timerDisplay = document.createElement('div');
        timerDisplay.className = 'fixed bottom-4 right-4 bg-white shadow-lg rounded-lg p-4 border-2 border-orange-500';
        timerDisplay.innerHTML = '<div class="text-sm font-semibold text-gray-700">Time Remaining:</div><div id="timer" class="text-2xl font-bold text-orange-600"></div>';
        document.body.appendChild(timerDisplay);

        let timerElement = document.getElementById('timer');
        let interval = setInterval(function() {
            let minutes = Math.floor(timeLimit / 60);
            let seconds = timeLimit % 60;
            timerElement.textContent = `${minutes}:${seconds.toString().padStart(2, '0')}`;
            
            if (timeLimit <= 0) {
                clearInterval(interval);
                alert('Time is up! Submitting your quiz...');
                document.getElementById('quizForm').submit();
            }
            timeLimit--;
        }, 1000);
    </script>
    @endif
</x-app-layout>
