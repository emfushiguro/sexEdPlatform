@extends('layouts.learner-app')

@section('title', 'Quiz Results')

@section('content')
<div class="max-w-4xl mx-auto">
            @if(session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                    {{ session('success') }}
                </div>
            @endif

            <!-- Gamification Status Bar -->
            <div class="bg-gradient-to-r from-purple-500 to-blue-500 rounded-lg p-4 mb-6 text-white shadow-lg">
                <div class="flex justify-between items-center">
                    <div>
                        <h3 class="text-sm font-semibold opacity-90">Your Progress</h3>
                        <div class="flex items-center gap-4 mt-1">
                            <div class="flex items-center gap-2">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                </svg>
                                <span class="font-bold text-lg">{{ auth()->user()->gamification->total_points ?? 0 }} Points</span>
                            </div>
                            <div class="h-6 w-px bg-white opacity-30"></div>
                            <div class="flex items-center gap-2">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                                </svg>
                                @php
                                    $shieldsLeft = \App\Models\UserDailyShield::getShields(auth()->user());
                                @endphp
                                <span class="font-bold">🛡 {{ $shieldsLeft }}/3 Shields Left Today</span>
                            </div>
                        </div>
                    </div>
                    @if(!auth()->user()->isPremium())
                        <a href="{{ route('subscription.upgrade') }}" class="bg-white text-purple-600 px-4 py-2 rounded-lg font-semibold hover:bg-gray-100 transition">
                            Upgrade to Premium
                        </a>
                    @endif
                </div>
            </div>

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
                            You got {{ $attempt->answers ? collect($attempt->answers)->where('is_correct', true)->count() : 0 }} out of {{ $attempt->answers ? count($attempt->answers) : 0 }} questions correct
                        </p>
                        <p class="text-sm text-gray-500 mt-2">
                            Passing score: {{ $attempt->quiz->passing_score }}%
                        </p>
                    </div>

                    <!-- Stats -->
                    <div class="grid grid-cols-3 gap-4 mb-8">
                        <div class="text-center p-4 bg-blue-50 rounded-lg">
                            <div class="text-2xl font-bold text-blue-600">{{ $attempt->answers ? count($attempt->answers) : 0 }}</div>
                            <div class="text-sm text-gray-600">Total Questions</div>
                        </div>
                        <div class="text-center p-4 bg-green-50 rounded-lg">
                            <div class="text-2xl font-bold text-green-600">{{ $attempt->answers ? collect($attempt->answers)->where('is_correct', true)->count() : 0 }}</div>
                            <div class="text-sm text-gray-600">Correct</div>
                        </div>
                        <div class="text-center p-4 bg-red-50 rounded-lg">
                            <div class="text-2xl font-bold text-red-600">{{ $attempt->answers ? collect($attempt->answers)->where('is_correct', false)->count() : 0 }}</div>
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
                            @php
                                $shieldsLeft = \App\Models\UserDailyShield::getShields(auth()->user());
                                $canRetry = auth()->user()->isPremium() || $shieldsLeft > 0;
                            @endphp
                            @if($canRetry)
                                <a href="{{ route('quizzes.show', $attempt->quiz) }}" 
                                   class="flex-1 text-center px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition">
                                    Try Again
                                </a>
                            @else
                                <button disabled
                                   class="flex-1 text-center px-6 py-3 bg-gray-400 text-white font-semibold rounded-lg cursor-not-allowed">
                                    Out of Shields
                                </button>
                            @endif
                        @endif
                    </div>

                    @if(!$attempt->passed && !$canRetry)
                        <div class="mt-4 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                            <p class="text-sm text-yellow-800 text-center">
                                <strong>Out of shields for today!</strong>
                                <a href="{{ route('learner.gamification') }}" class="underline font-semibold">Refill shields</a>
                                or
                                <a href="{{ route('subscription.upgrade') }}" class="underline font-semibold">upgrade to Premium</a>
                                for unlimited attempts.
                            </p>
                        </div>
                    @endif
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
                                            @elseif($question->question_type === 'fill_blank_text')
                                                <span class="px-2 py-1 text-xs font-medium bg-yellow-100 text-yellow-700 rounded">Fill in the Blanks (Text)</span>
                                            @elseif($question->question_type === 'fill_blank_select')
                                                <span class="px-2 py-1 text-xs font-medium bg-orange-100 text-orange-700 rounded">Fill in the Blanks (Word Selection)</span>
                                            @elseif($question->question_type === 'identification')
                                                <span class="px-2 py-1 text-xs font-medium bg-pink-100 text-pink-700 rounded">Identification</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <!-- Options Review -->
                                @if(in_array($question->question_type, ['multiple_choice', 'true_false', 'multiple_select']))
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
                                @elseif(in_array($question->question_type, ['fill_blank_text', 'fill_blank_select', 'identification']))
                                <div class="ml-11 space-y-3">
                                    @if($question->question_type === 'identification' && isset($answer['image_url']))
                                    <div class="mb-3">
                                        <img src="{{ $answer['image_url'] }}" alt="Question image" class="max-w-sm rounded-lg border shadow-sm">
                                    </div>
                                    @endif
                                    
                                    <div class="p-4 bg-white border-2 {{ $isCorrect ? 'border-green-400' : 'border-red-400' }} rounded-lg">
                                        <div class="mb-2">
                                            <span class="text-sm font-semibold text-gray-700">Your Answer:</span>
                                            <div class="mt-1">
                                                @if(is_array($answer['selected'] ?? null))
                                                    @foreach($answer['selected'] as $ans)
                                                        <span class="inline-block px-2 py-1 text-sm bg-blue-100 text-blue-800 rounded mr-1 mb-1">{{ $ans }}</span>
                                                    @endforeach
                                                @else
                                                    <span class="text-sm text-gray-900">{{ $answer['selected'] ?? 'No answer' }}</span>
                                                @endif
                                            </div>
                                        </div>
                                        
                                        <div>
                                            <span class="text-sm font-semibold text-green-700">Correct Answer{{ count($answer['correct'] ?? []) > 1 ? 's' : '' }}:</span>
                                            <div class="mt-1">
                                                @if(is_array($answer['correct'] ?? null))
                                                    @foreach($answer['correct'] as $correct)
                                                        <span class="inline-block px-2 py-1 text-sm bg-green-100 text-green-800 rounded mr-1 mb-1">{{ $correct }}</span>
                                                    @endforeach
                                                @else
                                                    <span class="text-sm text-green-900">{{ $answer['correct'] ?? 'N/A' }}</span>
                                                @endif
                                            </div>
                                        </div>
                                        
                                        @if(isset($answer['case_sensitive']) && $answer['case_sensitive'])
                                        <div class="mt-2 text-xs text-red-600">
                                            ⚠ This question was case-sensitive
                                        </div>
                                        @endif
                                    </div>
                                </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
