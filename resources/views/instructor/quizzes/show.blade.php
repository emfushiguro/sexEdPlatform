<x-app-layout>
    <x-slot name="header">
        <x-breadcrumb :items="[
            ['label' => 'Dashboard', 'url' => route('instructor.dashboard')],
            ['label' => 'Quizzes', 'url' => route('instructor.quizzes.index')],
            ['label' => $quiz->title]
        ]" />
        
        <div class="flex justify-between items-center mt-4">
            <div class="flex items-center space-x-3">
                <a href="{{ route('instructor.quizzes.index') }}" class="text-gray-600 hover:text-gray-900">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                </a>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Quiz: {{ $quiz->title }}</h2>
            </div>
            <a href="{{ route('instructor.quizzes.add-question', $quiz) }}" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">Add Question</a>
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

            <!-- CSV Import Section -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">📥 Import Questions from CSV</h3>
                            <p class="text-sm text-gray-600">
                                Upload a CSV file to add multiple questions at once. Supports all question types.
                            </p>
                        </div>
                        <button 
                            onclick="toggleHelp()"
                            class="flex items-center gap-2 px-4 py-2 bg-indigo-100 hover:bg-indigo-200 text-indigo-700 rounded-lg transition">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span class="font-semibold">Need Help?</span>
                        </button>
                    </div>

                    <!-- Help Panel (Initially Hidden) -->
                    <div id="csvHelpPanel" class="hidden mb-4 p-4 bg-indigo-50 border-l-4 border-indigo-500 rounded">
                        <h4 class="font-semibold text-indigo-900 mb-3">📖 Quick Guide</h4>
                        <div class="space-y-2 text-sm text-indigo-800">
                            <div class="flex items-start gap-2">
                                <span class="font-bold">1.</span>
                                <div>
                                    <strong>Download template</strong> - Contains examples for all 6 question types
                                </div>
                            </div>
                            <div class="flex items-start gap-2">
                                <span class="font-bold">2.</span>
                                <div>
                                    <strong>Upload images first</strong> - Go to Image Library for identification questions
                                </div>
                            </div>
                            <div class="flex items-start gap-2">
                                <span class="font-bold">3.</span>
                                <div>
                                    <strong>Fill your CSV</strong> - Follow format rules:
                                    <ul class="ml-4 mt-1 list-disc">
                                        <li>Use <code class="bg-indigo-100 px-1">|</code> for alternative answers (same blank)</li>
                                        <li>Use <code class="bg-indigo-100 px-1">;</code> for multiple blanks</li>
                                        <li>Use <code class="bg-indigo-100 px-1">_____</code> (5 underscores) for blanks</li>
                                        <li>True/False auto-generates options - leave empty!</li>
                                    </ul>
                                </div>
                            </div>
                            <div class="flex items-start gap-2">
                                <span class="font-bold">4.</span>
                                <div>
                                    <strong>Preview & fix</strong> - System shows validation errors before import
                                </div>
                            </div>
                        </div>
                        <a href="{{ asset('CSV_IMPORT_GUIDE.md') }}" 
                           target="_blank"
                           class="inline-flex items-center gap-2 mt-4 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                            </svg>
                            View Full Documentation
                        </a>
                    </div>
                    
                    <div class="flex flex-col md:flex-row gap-4">
                        <!-- Download Template -->
                        <div class="flex-1 p-4 border-2 border-dashed border-gray-300 rounded-lg">
                            <div class="flex items-center gap-3 mb-2">
                                <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                <div>
                                    <h4 class="font-semibold text-gray-900">Step 1: Download Template</h4>
                                    <p class="text-xs text-gray-500">Get the CSV template with examples</p>
                                </div>
                            </div>
                            <a href="{{ route('instructor.quizzes.import.template', $quiz) }}" 
                               class="block w-full text-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded transition">
                                Download Template
                            </a>
                        </div>
                        
                        <!-- Upload CSV -->
                        <div class="flex-1 p-4 border-2 border-dashed border-gray-300 rounded-lg">
                            <div class="flex items-center gap-3 mb-2">
                                <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                                </svg>
                                <div>
                                    <h4 class="font-semibold text-gray-900">Step 2: Upload CSV</h4>
                                    <p class="text-xs text-gray-500">Preview before importing</p>
                                </div>
                            </div>
                            <form method="POST" action="{{ route('instructor.quizzes.import.preview', $quiz) }}" enctype="multipart/form-data">
                                @csrf
                                <input 
                                    type="file" 
                                    name="csv_file" 
                                    accept=".csv"
                                    required
                                    class="hidden" 
                                    id="csv_file"
                                    onchange="this.form.submit()">
                                <label for="csv_file" class="block w-full text-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-semibold rounded transition cursor-pointer">
                                    Choose CSV File
                                </label>
                            </form>
                        </div>
                        
                        <!-- Image Library Link -->
                        <div class="flex-1 p-4 border-2 border-dashed border-gray-300 rounded-lg">
                            <div class="flex items-center gap-3 mb-2">
                                <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                <div>
                                    <h4 class="font-semibold text-gray-900">Manage Images</h4>
                                    <p class="text-xs text-gray-500">For identification questions</p>
                                </div>
                            </div>
                            <a href="{{ route('instructor.image-library.index') }}" 
                               class="block w-full text-center px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white font-semibold rounded transition">
                                Image Library
                            </a>
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
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <p class="font-semibold text-gray-900">{{ $loop->iteration }}. {{ $question->question_text }}</p>
                                        <div class="flex gap-2 mt-2">
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
                                            <span class="px-2 py-1 text-xs font-medium bg-gray-200 text-gray-700 rounded">{{ $question->points }} {{ $question->points === 1 ? 'point' : 'points' }}</span>
                                            @if($question->case_sensitive)
                                                <span class="px-2 py-1 text-xs font-medium bg-red-100 text-red-700 rounded">Case Sensitive</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="flex gap-2 ml-4">
                                        <a href="{{ route('instructor.quizzes.edit-question', ['quiz' => $quiz, 'question' => $question]) }}" 
                                           class="px-3 py-1 text-sm bg-blue-600 hover:bg-blue-700 text-white rounded transition">
                                            Edit
                                        </a>
                                        <form method="POST" action="{{ route('instructor.quizzes.delete-question', ['quiz' => $quiz, 'question' => $question]) }}" 
                                              onsubmit="return confirm('Are you sure you want to delete this question?');" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="px-3 py-1 text-sm bg-red-600 hover:bg-red-700 text-white rounded transition">
                                                Delete
                                            </button>
                                        </form>
                                    </div>
                                </div>
                                
                                @if($question->options->count() > 0)
                                <div class="mt-3 ml-4 space-y-1">
                                    @foreach($question->options as $option)
                                    <div class="flex items-center gap-2">
                                        <span class="{{ $option->is_correct ? 'text-green-600 font-semibold' : 'text-gray-600' }}">
                                            {{ chr(65 + $loop->index) }}. {{ $option->option_text }}
                                            @if($option->is_correct) <span class="text-green-600">✓</span> @endif
                                        </span>
                                    </div>
                                    @endforeach
                                </div>
                                @endif

                                @if($question->acceptable_answers)
                                <div class="mt-3 ml-4">
                                    <span class="text-sm font-medium text-gray-700">Acceptable Answers:</span>
                                    <span class="text-sm text-gray-600">{{ str_replace('|', ', ', $question->acceptable_answers) }}</span>
                                </div>
                                @endif

                                @if($question->word_bank)
                                <div class="mt-3 ml-4">
                                    <span class="text-sm font-medium text-gray-700">Word Bank:</span>
                                    <span class="text-sm text-gray-600">{{ implode(', ', $question->word_bank) }}</span>
                                </div>
                                @endif

                                @if($question->image_path)
                                <div class="mt-3 ml-4">
                                    <span class="text-sm font-medium text-gray-700">Image:</span>
                                    <img src="{{ asset('storage/' . $question->image_path) }}" alt="Question image" class="mt-2 max-w-[150px] h-auto rounded border shadow-sm">
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

    <script>
        function toggleHelp() {
            const panel = document.getElementById('csvHelpPanel');
            if (panel.classList.contains('hidden')) {
                panel.classList.remove('hidden');
                panel.classList.add('animate-fadeIn');
            } else {
                panel.classList.add('hidden');
            }
        }
    </script>
</x-app-layout>
