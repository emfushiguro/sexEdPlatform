@extends('layouts.instructor')
@section('title', 'Quiz: ' . $quiz->title)
@section('page-title', 'Quiz: ' . $quiz->title)
@section('content')

<div class="mb-5 flex items-center justify-between">
    <a href="{{ route('instructor.quizzes.index') }}" class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-brand-500 dark:text-gray-400 dark:hover:text-brand-400 transition-colors">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        Back to Quizzes
    </a>
    <a href="{{ route('instructor.quizzes.add-question', $quiz) }}" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg bg-brand-500 hover:bg-brand-600 text-white text-sm font-medium shadow-theme-xs transition-colors">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
        Add Question
    </a>
</div>

<!-- Quiz Details Card -->
<div class="rounded-2xl bg-white dark:bg-white/[0.03] border border-gray-200 dark:border-gray-800 shadow-theme-xs overflow-hidden mb-6">
    <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-800">
        <h3 class="text-base font-semibold text-gray-900 dark:text-white">Quiz Details</h3>
    </div>
    <div class="p-6">
        <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">{{ $quiz->description }}</p>
        <div class="grid grid-cols-2 gap-4">
            <div class="rounded-xl bg-brand-50 dark:bg-brand-500/10 p-3.5">
                <div class="text-xs font-medium text-brand-600 dark:text-brand-400">Passing Score</div>
                <div class="text-lg font-semibold text-brand-900 dark:text-brand-300 mt-0.5">{{ $quiz->passing_score }}%</div>
            </div>
            <div class="rounded-xl bg-success-50 dark:bg-success-500/10 p-3.5">
                <div class="text-xs font-medium text-success-600 dark:text-success-400">Total Questions</div>
                <div class="text-lg font-semibold text-success-900 dark:text-success-300 mt-0.5">{{ $quiz->questions->count() }}</div>
            </div>
        </div>
    </div>
</div>

<!-- CSV Import Section -->
<div class="rounded-2xl bg-white dark:bg-white/[0.03] border border-gray-200 dark:border-gray-800 shadow-theme-xs overflow-hidden mb-6" x-data="{ showHelp: false }">
    <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between">
        <div>
            <h3 class="text-base font-semibold text-gray-900 dark:text-white">Import Questions from CSV</h3>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Upload a CSV file to add multiple questions at once</p>
        </div>
        <button @click="showHelp = !showHelp" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium text-brand-600 dark:text-brand-400 bg-brand-50 dark:bg-brand-500/10 hover:bg-brand-100 dark:hover:bg-brand-500/20 transition-colors">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            Need Help?
        </button>
    </div>

    <!-- Help Panel -->
    <div x-show="showHelp" x-collapse x-cloak class="px-6 py-4 bg-brand-50/50 dark:bg-brand-500/5 border-b border-gray-100 dark:border-gray-800">
        <h4 class="text-sm font-semibold text-brand-900 dark:text-brand-300 mb-3">Quick Guide</h4>
        <div class="space-y-2 text-xs text-brand-700 dark:text-brand-400">
            <div class="flex items-start gap-2"><span class="font-bold">1.</span><div><strong>Download template</strong> — Contains examples for all 6 question types</div></div>
            <div class="flex items-start gap-2"><span class="font-bold">2.</span><div><strong>Upload images first</strong> — Go to Image Library for identification questions</div></div>
            <div class="flex items-start gap-2"><span class="font-bold">3.</span><div><strong>Fill your CSV</strong> — Use <code class="px-1 py-0.5 rounded bg-brand-100 dark:bg-brand-500/20 text-xs">|</code> for alternatives, <code class="px-1 py-0.5 rounded bg-brand-100 dark:bg-brand-500/20 text-xs">;</code> for multiple blanks, <code class="px-1 py-0.5 rounded bg-brand-100 dark:bg-brand-500/20 text-xs">_____</code> for blanks</div></div>
            <div class="flex items-start gap-2"><span class="font-bold">4.</span><div><strong>Preview &amp; fix</strong> — System shows validation errors before import</div></div>
        </div>
        <a href="{{ asset('CSV_IMPORT_GUIDE.md') }}" target="_blank" class="inline-flex items-center gap-1.5 mt-3 px-3 py-1.5 rounded-lg bg-brand-500 hover:bg-brand-600 text-white text-xs font-medium transition-colors">
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
            View Full Documentation
        </a>
    </div>

    <div class="p-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <!-- Download Template -->
            <div class="rounded-xl border-2 border-dashed border-gray-200 dark:border-gray-700 p-4 hover:border-brand-300 dark:hover:border-brand-600 transition-colors">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-10 h-10 rounded-lg bg-brand-50 dark:bg-brand-500/10 flex items-center justify-center">
                        <svg class="w-5 h-5 text-brand-600 dark:text-brand-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    </div>
                    <div>
                        <h4 class="text-sm font-semibold text-gray-900 dark:text-white">Step 1: Template</h4>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Download CSV with examples</p>
                    </div>
                </div>
                <a href="{{ route('instructor.quizzes.import.template', $quiz) }}" class="block w-full text-center px-4 py-2 rounded-lg bg-brand-500 hover:bg-brand-600 text-white text-sm font-medium shadow-theme-xs transition-colors">Download</a>
            </div>

            <!-- Upload CSV -->
            <div class="rounded-xl border-2 border-dashed border-gray-200 dark:border-gray-700 p-4 hover:border-success-300 dark:hover:border-success-600 transition-colors">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-10 h-10 rounded-lg bg-success-50 dark:bg-success-500/10 flex items-center justify-center">
                        <svg class="w-5 h-5 text-success-600 dark:text-success-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                    </div>
                    <div>
                        <h4 class="text-sm font-semibold text-gray-900 dark:text-white">Step 2: Upload</h4>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Preview before importing</p>
                    </div>
                </div>
                <form method="POST" action="{{ route('instructor.quizzes.import.preview', $quiz) }}" enctype="multipart/form-data">
                    @csrf
                    <input type="file" name="csv_file" accept=".csv" required class="hidden" id="csv_file" onchange="this.form.submit()">
                    <label for="csv_file" class="block w-full text-center px-4 py-2 rounded-lg bg-success-500 hover:bg-success-600 text-white text-sm font-medium shadow-theme-xs transition-colors cursor-pointer">Choose CSV</label>
                </form>
            </div>

            <!-- Image Library -->
            <div class="rounded-xl border-2 border-dashed border-gray-200 dark:border-gray-700 p-4 hover:border-warning-300 dark:hover:border-warning-600 transition-colors">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-10 h-10 rounded-lg bg-warning-50 dark:bg-warning-500/10 flex items-center justify-center">
                        <svg class="w-5 h-5 text-warning-600 dark:text-warning-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    </div>
                    <div>
                        <h4 class="text-sm font-semibold text-gray-900 dark:text-white">Manage Images</h4>
                        <p class="text-xs text-gray-500 dark:text-gray-400">For identification questions</p>
                    </div>
                </div>
                <a href="{{ route('instructor.image-library.index') }}" class="block w-full text-center px-4 py-2 rounded-lg bg-warning-500 hover:bg-warning-600 text-white text-sm font-medium shadow-theme-xs transition-colors">Image Library</a>
            </div>
        </div>
    </div>
</div>

<!-- Questions List -->
<div class="rounded-2xl bg-white dark:bg-white/[0.03] border border-gray-200 dark:border-gray-800 shadow-theme-xs overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-800">
        <h3 class="text-base font-semibold text-gray-900 dark:text-white">Questions ({{ $quiz->questions->count() }})</h3>
    </div>
    <div class="p-6">
        @forelse($quiz->questions as $question)
        <div class="pb-5 mb-5 border-b border-gray-100 dark:border-gray-800 last:border-0 last:pb-0 last:mb-0">
            <div class="flex items-start justify-between gap-4">
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $loop->iteration }}. {{ $question->question_text }}</p>
                    <div class="flex flex-wrap gap-1.5 mt-2">
                        @if($question->question_type === 'multiple_choice')
                            <span class="px-2 py-0.5 text-[10px] font-medium rounded-full bg-brand-50 text-brand-700 dark:bg-brand-500/10 dark:text-brand-400">Multiple Choice</span>
                        @elseif($question->question_type === 'true_false')
                            <span class="px-2 py-0.5 text-[10px] font-medium rounded-full bg-success-50 text-success-700 dark:bg-success-500/10 dark:text-success-400">True/False</span>
                        @elseif($question->question_type === 'multiple_select')
                            <span class="px-2 py-0.5 text-[10px] font-medium rounded-full bg-purple-50 text-purple-700 dark:bg-purple-500/10 dark:text-purple-400">Multiple Select</span>
                        @elseif($question->question_type === 'fill_blank_text')
                            <span class="px-2 py-0.5 text-[10px] font-medium rounded-full bg-warning-50 text-warning-700 dark:bg-warning-500/10 dark:text-warning-400">Fill in the Blanks (Text)</span>
                        @elseif($question->question_type === 'fill_blank_select')
                            <span class="px-2 py-0.5 text-[10px] font-medium rounded-full bg-orange-50 text-orange-700 dark:bg-orange-500/10 dark:text-orange-400">Fill in the Blanks (Word Selection)</span>
                        @elseif($question->question_type === 'identification')
                            <span class="px-2 py-0.5 text-[10px] font-medium rounded-full bg-pink-50 text-pink-700 dark:bg-pink-500/10 dark:text-pink-400">Identification</span>
                        @endif
                        <span class="px-2 py-0.5 text-[10px] font-medium rounded-full bg-gray-100 text-gray-600 dark:bg-white/[0.05] dark:text-gray-400">{{ $question->points }} {{ $question->points === 1 ? 'point' : 'points' }}</span>
                        @if($question->case_sensitive)
                            <span class="px-2 py-0.5 text-[10px] font-medium rounded-full bg-error-50 text-error-700 dark:bg-error-500/10 dark:text-error-400">Case Sensitive</span>
                        @endif
                    </div>
                </div>
                <div class="flex items-center gap-1 flex-shrink-0">
                    <a href="{{ route('instructor.quizzes.edit-question', ['quiz' => $quiz, 'question' => $question]) }}" class="p-1.5 rounded-lg text-gray-400 hover:bg-brand-50 hover:text-brand-600 dark:hover:bg-brand-500/10 dark:hover:text-brand-400 transition-colors" title="Edit">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    </a>
                    <form method="POST" action="{{ route('instructor.quizzes.delete-question', ['quiz' => $quiz, 'question' => $question]) }}" onsubmit="return confirm('Delete this question?');" class="inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="p-1.5 rounded-lg text-gray-400 hover:bg-error-50 hover:text-error-600 dark:hover:bg-error-500/10 dark:hover:text-error-400 transition-colors" title="Delete">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        </button>
                    </form>
                </div>
            </div>

            @if($question->options->count() > 0)
            <div class="mt-3 ml-4 space-y-1">
                @foreach($question->options as $option)
                <div class="flex items-center gap-2 text-sm {{ $option->is_correct ? 'text-success-600 dark:text-success-400 font-medium' : 'text-gray-600 dark:text-gray-400' }}">
                    {{ chr(65 + $loop->index) }}. {{ $option->option_text }}
                    @if($option->is_correct) <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> @endif
                </div>
                @endforeach
            </div>
            @endif

            @if($question->acceptable_answers)
            <div class="mt-3 ml-4">
                <span class="text-xs font-medium text-gray-500 dark:text-gray-400">Acceptable Answers:</span>
                <span class="text-xs text-gray-600 dark:text-gray-400 ml-1">{{ str_replace('|', ', ', $question->acceptable_answers) }}</span>
            </div>
            @endif

            @if($question->word_bank)
            <div class="mt-3 ml-4">
                <span class="text-xs font-medium text-gray-500 dark:text-gray-400">Word Bank:</span>
                <span class="text-xs text-gray-600 dark:text-gray-400 ml-1">{{ implode(', ', $question->word_bank) }}</span>
            </div>
            @endif

            @if($question->image_path)
            <div class="mt-3 ml-4">
                <span class="text-xs font-medium text-gray-500 dark:text-gray-400">Image:</span>
                <img src="{{ asset('storage/' . $question->image_path) }}" alt="Question image" class="mt-1.5 max-w-[150px] h-auto rounded-lg border border-gray-200 dark:border-gray-700 shadow-theme-xs">
            </div>
            @endif
        </div>
        @empty
        <div class="text-center py-8">
            <svg class="mx-auto h-10 w-10 text-gray-300 dark:text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">No questions yet. Add questions to make this quiz active.</p>
        </div>
        @endforelse
    </div>
</div>
@endsection
