@extends('layouts.instructor-app')

@section('content')
<div
    x-data="questionEntryModal({{ $openModal ? 'true' : 'false' }})"
    class="space-y-5"
>

    {{-- ── PAGE HEADER ── --}}
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <a
                    href="{{ route('instructor.quizzes.index') }}"
                    class="text-sm text-gray-400 hover:text-purple-600 transition flex items-center gap-1"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                    Quizzes
                </a>
                <span class="text-gray-300">/</span>
                <span class="text-sm text-gray-500 truncate max-w-xs">{{ $quiz->title }}</span>
            </div>
            <h1 class="text-xl font-bold text-gray-900">{{ $quiz->title }}</h1>
            <div class="flex flex-wrap items-center gap-2 mt-2">
                @if($quiz->module)
                    <span class="inline-flex items-center gap-1 text-xs font-medium bg-purple-50 text-purple-700 border border-purple-100 rounded-lg px-2.5 py-1">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                        </svg>
                        Module Quiz
                    </span>
                @elseif($quiz->lesson)
                    <span class="inline-flex items-center gap-1 text-xs font-medium bg-blue-50 text-blue-700 border border-blue-100 rounded-lg px-2.5 py-1">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                        </svg>
                        Lesson Quiz · {{ $quiz->lesson->title }}
                    </span>
                @endif

                @if($quiz->is_active)
                    <span class="inline-flex items-center gap-1 text-xs font-medium bg-green-50 text-green-700 border border-green-100 rounded-lg px-2.5 py-1">
                        <span class="w-1.5 h-1.5 rounded-full bg-green-500 inline-block"></span>
                        Active
                    </span>
                @else
                    <span class="inline-flex items-center gap-1 text-xs font-medium bg-gray-100 text-gray-600 border border-gray-200 rounded-lg px-2.5 py-1">
                        <span class="w-1.5 h-1.5 rounded-full bg-gray-400 inline-block"></span>
                        Inactive
                    </span>
                @endif
            </div>
        </div>

        <div class="flex items-center gap-2 flex-shrink-0">
            <a
                href="{{ route('instructor.quizzes.edit', $quiz) }}"
                class="flex items-center gap-2 px-4 py-2 text-sm font-semibold text-gray-700 bg-white border border-gray-200 rounded-xl hover:border-gray-300 hover:bg-gray-50 transition"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
                Edit Quiz
            </a>
            <button
                @click="open = true"
                type="button"
                class="flex items-center gap-2 px-4 py-2 text-sm font-semibold text-white rounded-xl transition hover:opacity-90 active:scale-95"
                style="background: linear-gradient(135deg, #A30EB2, #730DB1, #3B0CB1);"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Add Question
            </button>
        </div>
    </div>

    {{-- ── FLASH MESSAGES ── --}}
    @if(session('success'))
    <div class="flex items-center gap-3 px-4 py-3 bg-green-50 border border-green-200 rounded-2xl text-sm text-green-800">
        <svg class="w-5 h-5 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        {{ session('success') }}
    </div>
    @endif
    @if(session('error'))
    <div class="flex items-center gap-3 px-4 py-3 bg-red-50 border border-red-200 rounded-2xl text-sm text-red-800">
        <svg class="w-5 h-5 text-red-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        {{ session('error') }}
    </div>
    @endif

    {{-- ── QUIZ DETAILS CARD ── --}}
    <div class="rounded-2xl bg-white shadow-sm border border-gray-100 p-6">
        @if($quiz->description)
        <p class="text-sm text-gray-600 mb-5 leading-relaxed">{{ $quiz->description }}</p>
        @endif

        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
            <div class="bg-purple-50/40 rounded-xl p-3 border border-purple-100/60">
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-widest mb-0.5">Questions</p>
                <p class="text-xl font-bold text-gray-900">{{ $quiz->questions->count() }}</p>
            </div>
            <div class="bg-purple-50/40 rounded-xl p-3 border border-purple-100/60">
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-widest mb-0.5">Total Points</p>
                <p class="text-xl font-bold text-gray-900">{{ $quiz->getTotalPoints() }}</p>
            </div>
            <div class="bg-purple-50/40 rounded-xl p-3 border border-purple-100/60">
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-widest mb-0.5">Passing Score</p>
                <p class="text-xl font-bold text-gray-900">{{ $quiz->passing_score }}%</p>
            </div>
            <div class="bg-purple-50/40 rounded-xl p-3 border border-purple-100/60">
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-widest mb-0.5">Time Limit</p>
                <p class="text-xl font-bold text-gray-900">
                    {{ $quiz->time_limit ? $quiz->time_limit . ' min' : '—' }}
                </p>
            </div>
        </div>
    </div>

    {{-- ── QUESTIONS LIST ── --}}
    <div class="rounded-2xl bg-white shadow-sm border border-gray-100">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
            <div class="border-l-4 pl-3" style="border-color: #730DB1;">
                <h2 class="text-sm font-bold text-gray-900">Questions</h2>
                <p class="text-xs text-gray-400 mt-0.5">{{ $quiz->questions->count() }} question{{ $quiz->questions->count() !== 1 ? 's' : '' }} total</p>
            </div>
            <button
                @click="open = true"
                type="button"
                class="flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-purple-700 bg-purple-50 hover:bg-purple-100 border border-purple-200 rounded-xl transition"
            >
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Add Question
            </button>
        </div>

        <div class="divide-y divide-gray-50">
            @forelse($quiz->questions as $question)
            <div class="px-6 py-5">
                <div class="flex items-start justify-between gap-4">
                    <div class="flex items-start gap-3 flex-1 min-w-0">
                        {{-- Index badge --}}
                        <div class="flex-shrink-0 w-7 h-7 rounded-full bg-gray-100 flex items-center justify-center">
                            <span class="text-xs font-bold text-gray-500">{{ $loop->iteration }}</span>
                        </div>

                        <div class="flex-1 min-w-0">
                            {{-- Question text --}}
                            <p class="text-sm font-medium text-gray-900 leading-snug">
                                {!! nl2br(e(strip_tags($question->question_text))) !!}
                            </p>

                            {{-- Type + points badges --}}
                            <div class="flex flex-wrap items-center gap-1.5 mt-2">
                                @php
                                $typeBadges = [
                                    'multiple_choice'  => ['bg-blue-50 text-blue-700 border-blue-100',   'Multiple Choice'],
                                    'true_false'       => ['bg-green-50 text-green-700 border-green-100', 'True / False'],
                                    'multiple_select'  => ['bg-purple-50 text-purple-700 border-purple-100', 'Multiple Select'],
                                    'fill_blank_text'  => ['bg-yellow-50 text-yellow-700 border-yellow-100', 'Fill Blank (Text)'],
                                    'fill_blank_select'=> ['bg-orange-50 text-orange-700 border-orange-100', 'Fill Blank (Word Bank)'],
                                    'identification'   => ['bg-pink-50 text-pink-700 border-pink-100',    'Identification'],
                                ];
                                [$badgeClass, $badgeLabel] = $typeBadges[$question->question_type] ?? ['bg-gray-100 text-gray-600 border-gray-200', ucfirst($question->question_type)];
                                @endphp
                                <span class="inline-flex text-xs font-medium border rounded-lg px-2 py-0.5 {{ $badgeClass }}">{{ $badgeLabel }}</span>
                                <span class="inline-flex text-xs font-medium bg-gray-50 text-gray-600 border border-gray-200 rounded-lg px-2 py-0.5">
                                    {{ $question->points }} {{ $question->points === 1 ? 'pt' : 'pts' }}
                                </span>
                                @if($question->case_sensitive)
                                    <span class="inline-flex text-xs font-medium bg-red-50 text-red-600 border border-red-100 rounded-lg px-2 py-0.5">Case Sensitive</span>
                                @endif
                            </div>

                            {{-- Options (choice-based) --}}
                            @if($question->options->count() > 0)
                            <div class="mt-3 space-y-1">
                                @foreach($question->options as $option)
                                <div class="flex items-center gap-2 text-xs">
                                    <span class="w-4 h-4 rounded-full flex items-center justify-center flex-shrink-0 {{ $option->is_correct ? 'bg-green-100' : 'bg-gray-100' }}">
                                        @if($option->is_correct)
                                            <svg class="w-2.5 h-2.5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                                            </svg>
                                        @else
                                            <span class="w-1 h-1 rounded-full bg-gray-400"></span>
                                        @endif
                                    </span>
                                    <span class="{{ $option->is_correct ? 'font-semibold text-green-700' : 'text-gray-600' }}">
                                        {{ chr(65 + $loop->index) }}. {{ $option->option_text }}
                                    </span>
                                </div>
                                @endforeach
                            </div>
                            @endif

                            {{-- Acceptable answers --}}
                            @if($question->acceptable_answers)
                            <div class="mt-2 text-xs text-gray-500">
                                <span class="font-medium text-gray-600">Acceptable answers: </span>
                                {{ str_replace('|', ' / ', $question->acceptable_answers) }}
                            </div>
                            @endif

                            {{-- Word bank --}}
                            @if($question->word_bank)
                            <div class="mt-2 text-xs text-gray-500">
                                <span class="font-medium text-gray-600">Word bank: </span>
                                {{ implode(', ', $question->word_bank) }}
                            </div>
                            @endif

                            {{-- Image preview --}}
                            @if($question->image_path)
                            <div class="mt-2">
                                <img
                                    src="{{ asset('storage/' . $question->image_path) }}"
                                    alt="Question image"
                                    class="h-16 w-auto rounded-xl border border-gray-100 object-cover"
                                >
                            </div>
                            @endif
                        </div>
                    </div>

                    {{-- Action buttons --}}
                    <div class="flex items-center gap-1.5 flex-shrink-0">
                        <a
                            href="{{ route('instructor.quizzes.edit-question', ['quiz' => $quiz, 'question' => $question]) }}"
                            class="p-2 rounded-xl text-gray-400 hover:text-blue-600 hover:bg-blue-50 transition"
                            title="Edit question"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                        </a>
                        <form
                            method="POST"
                            action="{{ route('instructor.quizzes.delete-question', ['quiz' => $quiz, 'question' => $question]) }}"
                            onsubmit="return confirm('Delete this question? This cannot be undone.')"
                            class="inline"
                        >
                            @csrf
                            @method('DELETE')
                            <button
                                type="submit"
                                class="p-2 rounded-xl text-gray-400 hover:text-red-600 hover:bg-red-50 transition"
                                title="Delete question"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            @empty
            <div class="px-6 py-12 text-center">
                <div class="w-12 h-12 bg-gray-100 rounded-2xl flex items-center justify-center mx-auto mb-3">
                    <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <p class="text-sm font-semibold text-gray-700">No questions yet</p>
                <p class="text-xs text-gray-400 mt-1 mb-4">Add questions to make this quiz available to learners.</p>
                <button
                    @click="open = true"
                    type="button"
                    class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold text-white rounded-xl transition hover:opacity-90"
                    style="background: linear-gradient(135deg, #A30EB2, #730DB1, #3B0CB1);"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Add Your First Question
                </button>
            </div>
            @endforelse
        </div>
    </div>

    {{-- ── QUESTION ENTRY MODAL ── --}}
    @include('instructor.quizzes.partials.question-entry-modal')

</div>
@endsection

@push('scripts')
<script>
function questionEntryModal(autoOpen) {
    return {
        open: autoOpen,
        tab: 'manual',
        images: [],
        imageLoading: false,
        uploadLoading: false,
        showCopied: false,
        _imagesLoaded: false,

        async loadImages() {
            if (this._imagesLoaded) return;
            this.imageLoading = true;
            try {
                const res = await fetch('{{ route("instructor.image-library.json") }}', {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    }
                });
                const data = await res.json();
                this.images = data.images || [];
                this._imagesLoaded = true;
            } catch (e) {
                console.error('Failed to load images:', e);
            } finally {
                this.imageLoading = false;
            }
        },

        async uploadImage(event) {
            const file = event.target.files[0];
            if (!file) return;
            this.uploadLoading = true;
            const formData = new FormData();
            formData.append('image', file);
            formData.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));
            try {
                const res = await fetch('{{ route("instructor.image-library.upload") }}', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: formData,
                });
                const data = await res.json();
                if (data.success) {
                    this.images.unshift({
                        filename: data.filename,
                        url: data.url,
                        size_kb: data.size_kb,
                    });
                }
            } catch (e) {
                console.error('Image upload failed:', e);
            } finally {
                this.uploadLoading = false;
                event.target.value = '';
            }
        },

        copyFilename(filename) {
            navigator.clipboard.writeText(filename).then(() => {
                this.showCopied = true;
                setTimeout(() => { this.showCopied = false; }, 2000);
            }).catch(() => {
                // Fallback for non-secure contexts
                const el = document.createElement('textarea');
                el.value = filename;
                document.body.appendChild(el);
                el.select();
                document.execCommand('copy');
                document.body.removeChild(el);
                this.showCopied = true;
                setTimeout(() => { this.showCopied = false; }, 2000);
            });
        },
    };
}
</script>
@endpush
