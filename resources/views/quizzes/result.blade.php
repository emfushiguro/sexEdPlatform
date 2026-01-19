<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Quiz Results
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                    {{ session('success') }}
                </div>
            @endif

            <!-- Results Card -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-8">
                    <!-- Score Display -->
                    <div class="text-center mb-8">
                        <div class="inline-flex items-center justify-center w-32 h-32 rounded-full mb-4
                            {{ $attempt->passed ? 'bg-gradient-to-br from-green-400 to-green-600' : 'bg-gradient-to-br from-red-400 to-red-600' }}">
                            <span class="text-5xl font-bold text-white">{{ $attempt->score }}%</span>
                        </div>
                        <h3 class="text-3xl font-bold {{ $attempt->passed ? 'text-green-600' : 'text-red-600' }} mb-2">
                            {{ $attempt->passed ? '🎉 You Passed!' : '💪 Keep Trying!' }}
                        </h3>
                        <p class="text-gray-600">
                            You got {{ collect($attempt->answers)->where('is_correct', true)->count() }} out of {{ count($attempt->answers) }} questions correct
                        </p>
                        <p class="text-sm text-gray-500 mt-2">
                            Passing score: {{ $attempt->quiz->passing_score }}%
                        </p>
                    </div>

                    <!-- Stats -->
                    <div class="grid grid-cols-3 gap-4 mb-8">
                        <div class="text-center p-4 bg-blue-50 rounded-lg">
                            <div class="text-2xl font-bold text-blue-600">{{ count($attempt->answers) }}</div>
                            <div class="text-sm text-gray-600">Total Questions</div>
                        </div>
                        <div class="text-center p-4 bg-green-50 rounded-lg">
                            <div class="text-2xl font-bold text-green-600">{{ collect($attempt->answers)->where('is_correct', true)->count() }}</div>
                            <div class="text-sm text-gray-600">Correct</div>
                        </div>
                        <div class="text-center p-4 bg-red-50 rounded-lg">
                            <div class="text-2xl font-bold text-red-600">{{ collect($attempt->answers)->where('is_correct', false)->count() }}</div>
                            <div class="text-sm text-gray-600">Incorrect</div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex gap-4">
                        <a href="{{ route('learner.modules.index') }}" 
                           class="flex-1 text-center px-6 py-3 bg-gray-600 hover:bg-gray-700 text-white font-semibold rounded-lg transition">
                            Back to Modules
                        </a>
                        @if(!$attempt->passed)
                            <a href="{{ route('quizzes.start', $attempt->quiz) }}" 
                               class="flex-1 text-center px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition">
                                Try Again
                            </a>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Question Review -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h4 class="text-xl font-bold text-gray-900 mb-6">Question Review</h4>
                    
                    <div class="space-y-6">
                        @foreach($attempt->quiz->questions as $index => $question)
                            @php
                                $answer = $attempt->answers[$question->id] ?? null;
                                $isCorrect = $answer['is_correct'] ?? false;
                            @endphp
                            
                            <div class="p-6 rounded-lg border-2 {{ $isCorrect ? 'border-green-300 bg-green-50' : 'border-red-300 bg-red-50' }}">
                                <!-- Question Header -->
                                <div class="flex items-start gap-3 mb-4">
                                    <span class="flex-shrink-0 w-8 h-8 {{ $isCorrect ? 'bg-green-600' : 'bg-red-600' }} text-white rounded-full flex items-center justify-center font-semibold">
                                        @if($isCorrect)
                                            ✓
                                        @else
                                            ✗
                                        @endif
                                    </span>
                                    <div class="flex-1">
                                        <h5 class="text-lg font-semibold text-gray-900">{{ $question->question_text }}</h5>
                                        <div class="flex gap-2 mt-1">
                                            @if($question->question_type === 'multiple_choice')
                                                <span class="px-2 py-1 text-xs font-medium bg-blue-100 text-blue-700 rounded">Multiple Choice</span>
                                            @elseif($question->question_type === 'true_false')
                                                <span class="px-2 py-1 text-xs font-medium bg-green-100 text-green-700 rounded">True/False</span>
                                            @elseif($question->question_type === 'multiple_select')
                                                <span class="px-2 py-1 text-xs font-medium bg-purple-100 text-purple-700 rounded">Multiple Select</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <!-- Options Review -->
                                <div class="space-y-2 ml-11">
                                    @foreach($question->options as $option)
                                        @php
                                            $isSelected = false;
                                            if ($question->question_type === 'multiple_select') {
                                                $isSelected = in_array($option->id, $answer['selected'] ?? []);
                                            } else {
                                                $isSelected = ($answer['selected'] ?? null) == $option->id;
                                            }
                                        @endphp
                                        
                                        <div class="p-3 rounded-lg flex items-center gap-3
                                            {{ $option->is_correct ? 'bg-green-100 border-2 border-green-400' : 'bg-white border border-gray-300' }}">
                                            
                                            @if($question->question_type === 'multiple_select')
                                                <input type="checkbox" 
                                                       disabled 
                                                       {{ $isSelected ? 'checked' : '' }}
                                                       class="w-5 h-5 {{ $isSelected ? 'text-blue-600' : 'text-gray-400' }}">
                                            @else
                                                <input type="radio" 
                                                       disabled 
                                                       {{ $isSelected ? 'checked' : '' }}
                                                       class="w-5 h-5 {{ $isSelected ? 'text-blue-600' : 'text-gray-400' }}">
                                            @endif
                                            
                                            <span class="flex-1 {{ $option->is_correct ? 'font-semibold text-green-900' : 'text-gray-700' }}">
                                                {{ $option->option_text }}
                                            </span>
                                            
                                            @if($option->is_correct)
                                                <span class="px-2 py-1 text-xs font-semibold bg-green-600 text-white rounded">
                                                    ✓ Correct Answer
                                                </span>
                                            @elseif($isSelected && !$option->is_correct)
                                                <span class="px-2 py-1 text-xs font-semibold bg-red-600 text-white rounded">
                                                    ✗ Your Answer
                                                </span>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
