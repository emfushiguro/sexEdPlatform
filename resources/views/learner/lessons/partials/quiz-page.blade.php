<div class="bg-white rounded-lg shadow-md p-8">
    <div class="mb-6">
        <div class="flex items-center gap-3 mb-4">
            <div class="flex-shrink-0 w-12 h-12 bg-purple-500 rounded-lg flex items-center justify-center">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
            </div>
            <div>
                <h3 class="text-2xl font-bold text-gray-900">{{ $lessonQuiz->title }}</h3>
                <p class="text-sm text-gray-500">Complete this quiz to test your knowledge</p>
            </div>
        </div>

        @if($lessonQuiz->description)
            <div class="bg-purple-50 border-l-4 border-purple-500 p-4 rounded-lg mb-6">
                <p class="text-purple-900">{!! nl2br(e($lessonQuiz->description)) !!}</p>
            </div>
        @endif
    </div>

    <!-- Quiz Stats -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8">
        <div class="bg-gray-50 rounded-lg p-4">
            <div class="flex items-center gap-3">
                <div class="flex-shrink-0 w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-900">{{ $lessonQuiz->questions->count() }}</p>
                    <p class="text-xs text-gray-500">Questions</p>
                </div>
            </div>
        </div>

        <div class="bg-gray-50 rounded-lg p-4">
            <div class="flex items-center gap-3">
                <div class="flex-shrink-0 w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-900">{{ $lessonQuiz->time_limit }}</p>
                    <p class="text-xs text-gray-500">Minutes</p>
                </div>
            </div>
        </div>

        <div class="bg-gray-50 rounded-lg p-4">
            <div class="flex items-center gap-3">
                <div class="flex-shrink-0 w-10 h-10 bg-yellow-100 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-900">{{ $lessonQuiz->passing_score }}%</p>
                    <p class="text-xs text-gray-500">Pass Score</p>
                </div>
            </div>
        </div>
    </div>

    @if($quizAttempt)
        <!-- Previous Attempt Results -->
        <div class="mb-8 bg-gradient-to-br from-{{ $quizAttempt->passed ? 'green' : 'red' }}-50 to-{{ $quizAttempt->passed ? 'green' : 'red' }}-100 border-2 border-{{ $quizAttempt->passed ? 'green' : 'red' }}-400 rounded-xl p-6">
            <div class="flex items-center gap-4 mb-4">
                @if($quizAttempt->passed)
                    <div class="flex-shrink-0 w-16 h-16 bg-green-500 rounded-full flex items-center justify-center">
                        <svg class="w-10 h-10 text-white" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                @else
                    <div class="flex-shrink-0 w-16 h-16 bg-red-500 rounded-full flex items-center justify-center">
                        <svg class="w-10 h-10 text-white" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                @endif
                <div>
                    <h4 class="text-xl font-bold text-{{ $quizAttempt->passed ? 'green' : 'red' }}-900">
                        @if($quizAttempt->passed)
                            Congratulations! You Passed!
                        @else
                            Keep Trying!
                        @endif
                    </h4>
                    <p class="text-{{ $quizAttempt->passed ? 'green' : 'red' }}-700">
                        Your Score: <span class="font-bold text-2xl">{{ number_format($quizAttempt->score, 1) }}%</span>
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4 mb-4">
                <div class="bg-white bg-opacity-60 rounded-lg p-3">
                    <p class="text-sm text-gray-600">Questions Answered</p>
                    <p class="text-xl font-bold text-gray-900">
                        {{ $quizAttempt->correct_answers + ($quizAttempt->total_questions - $quizAttempt->correct_answers) }}/{{ $quizAttempt->total_questions }}
                    </p>
                </div>
                <div class="bg-white bg-opacity-60 rounded-lg p-3">
                    <p class="text-sm text-gray-600">Correct Answers</p>
                    <p class="text-xl font-bold text-green-600">{{ $quizAttempt->correct_answers }}/{{ $quizAttempt->total_questions }}</p>
                </div>
            </div>

            @if($quizAttempt->passed)
                <div class="bg-white bg-opacity-60 rounded-lg p-4">
                    <div class="flex items-start gap-3">
                        <svg class="w-6 h-6 text-green-600 flex-shrink-0 mt-1" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                        </svg>
                        <div>
                            <p class="font-semibold text-green-900 mb-1">Great job on completing this quiz!</p>
                            <p class="text-sm text-green-800">
                                You can retake the quiz to improve your score, or continue to the next lesson.
                            </p>
                        </div>
                    </div>
                </div>
            @else
                <div class="bg-white bg-opacity-60 rounded-lg p-4">
                    <div class="flex items-start gap-3">
                        <svg class="w-6 h-6 text-red-600 flex-shrink-0 mt-1" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                        </svg>
                        <div>
                            <p class="font-semibold text-red-900 mb-1">You need {{ $lessonQuiz->passing_score }}% to pass</p>
                            <p class="text-sm text-red-800">
                                Review the lesson content and try again. You can do it!
                            </p>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    @else
        <!-- First Time Taking Quiz -->
        <div class="bg-blue-50 border-2 border-blue-300 rounded-xl p-6 mb-8">
            <div class="flex items-start gap-4">
                <div class="flex-shrink-0 w-12 h-12 bg-blue-500 rounded-full flex items-center justify-center">
                    <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <div>
                    <h4 class="font-bold text-blue-900 mb-2">Quiz Instructions:</h4>
                    <ul class="text-blue-800 space-y-1 text-sm list-disc list-inside">
                        <li>Answer all {{ $lessonQuiz->questions->count() }} questions</li>
                        <li>You have {{ $lessonQuiz->time_limit }} minutes to complete the quiz</li>
                        <li>You need {{ $lessonQuiz->passing_score }}% to pass</li>
                        <li>You can retake the quiz if you don't pass</li>
                        <li>Your highest score will be recorded</li>
                    </ul>
                </div>
            </div>
        </div>
    @endif

    <!-- Action Buttons -->
    <div class="flex gap-4">
        <a href="{{ route('quizzes.start', $lessonQuiz) }}" class="flex-1">
            <button type="button" class="w-full px-6 py-4 bg-purple-600 hover:bg-purple-700 text-white font-semibold rounded-lg transition shadow-lg hover:shadow-xl flex items-center justify-center gap-2">
                @if($quizAttempt)
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    Retake Quiz
                @else
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                    </svg>
                    Start Quiz
                @endif
            </button>
        </a>
    </div>

    <!-- Help Section -->
    <div class="mt-6 bg-gray-50 rounded-lg p-4">
        <div class="flex items-start gap-3">
            <svg class="w-5 h-5 text-gray-500 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 00-.867.5 1 1 0 11-1.731-1A3 3 0 0113 8a3.001 3.001 0 01-2 2.83V11a1 1 0 11-2 0v-1a1 1 0 011-1 1 1 0 100-2zm0 8a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/>
            </svg>
            <div class="text-sm text-gray-600">
                <p class="font-medium text-gray-900 mb-1">Need help?</p>
                <p>Review the lesson topics before taking the quiz. Make sure you understand all the concepts covered.</p>
            </div>
        </div>
    </div>
</div>
