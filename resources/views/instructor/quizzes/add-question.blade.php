@extends('layouts.instructor-app')

@php
$richTextTypes  = ['multiple_choice', 'true_false', 'multiple_select', 'identification'];
$blankTypes     = ['fill_blank_text', 'fill_blank_select'];
$choiceTypes    = ['multiple_choice', 'true_false', 'multiple_select'];
$isRichText     = in_array($selectedType, $richTextTypes);
$isBlankType    = in_array($selectedType, $blankTypes);
$isChoiceType   = in_array($selectedType, $choiceTypes);

// Rebuild options from old() on validation-error return
$oldOpts    = old('options', []);
$oldCorrect = array_map('intval', (array) old('correct_options', []));
$initOptions = [];
if ($oldOpts) {
    foreach ($oldOpts as $i => $text) {
        $initOptions[] = ['text' => $text, 'isCorrect' => in_array($i, $oldCorrect), 'readonly' => false];
    }
}
if (empty($initOptions)) {
    if ($selectedType === 'true_false') {
        $initOptions = [
            ['text' => 'True',  'isCorrect' => false, 'readonly' => true],
            ['text' => 'False', 'isCorrect' => false, 'readonly' => true],
        ];
    } elseif (in_array($selectedType, ['multiple_choice', 'multiple_select'])) {
        $initOptions = [
            ['text' => '', 'isCorrect' => false, 'readonly' => false],
            ['text' => '', 'isCorrect' => false, 'readonly' => false],
        ];
    }
}

$oldAnswers  = old('acceptable_answers', ['']);
if (empty($oldAnswers)) $oldAnswers = [''];

// Initial blank count from old question_text
$oldQText     = old('question_text', '');
$initBlanks   = substr_count($oldQText, '_____');

$typeLabels = [
    'multiple_choice'  => 'Multiple Choice',
    'true_false'       => 'True or False',
    'multiple_select'  => 'Multiple Select',
    'fill_blank_text'  => 'Fill in the Blank (Text)',
    'fill_blank_select'=> 'Fill in the Blank (Word Bank)',
    'identification'   => 'Identification',
];
$typeBadgeClasses = [
    'multiple_choice'  => 'bg-brand-50 text-brand-700 border-brand-200',
    'true_false'       => 'bg-green-50 text-green-700 border-green-200',
    'multiple_select'  => 'bg-purple-50 text-purple-700 border-purple-200',
    'fill_blank_text'  => 'bg-yellow-50 text-yellow-700 border-yellow-200',
    'fill_blank_select'=> 'bg-orange-50 text-orange-700 border-orange-200',
    'identification'   => 'bg-pink-50 text-pink-700 border-pink-200',
];
@endphp

@push('head')
@if($isRichText && $selectedType)
<script src="{{ asset('build/tinymce/tinymce.min.js') }}"></script>
@endif
@endpush

@section('content')
<div class="max-w-3xl mx-auto space-y-5">

    {{-- ── BREADCRUMB ── --}}
    <div class="flex items-center gap-2 text-sm">
        <a href="{{ route('instructor.quizzes.index') }}" class="text-gray-400 hover:text-purple-600 transition">Quizzes</a>
        <span class="text-gray-300">/</span>
        <a href="{{ route('instructor.quizzes.show', ['quiz' => $quiz, 'open_modal' => 1]) }}" class="text-gray-400 hover:text-purple-600 transition">{{ $quiz->title }}</a>
        <span class="text-gray-300">/</span>
        <span class="text-gray-600 font-medium">Add Question</span>
    </div>

    {{-- ── NO TYPE SELECTED ── --}}
    @if(!$selectedType)
    <div class="rounded-2xl bg-white shadow-sm border border-gray-100 p-12 text-center">
        <div class="w-12 h-12 bg-gray-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
            <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </div>
        <p class="text-sm font-semibold text-gray-700">No question type selected</p>
        <p class="text-xs text-gray-400 mt-1 mb-4">Please go back and select a question type first.</p>
        <a
            href="{{ route('instructor.quizzes.show', ['quiz' => $quiz, 'open_modal' => 1]) }}"
            class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold text-white rounded-xl transition hover:opacity-90"
            style="background: linear-gradient(135deg, #A30EB2, #730DB1, #3B0CB1);"
        >
            Select Question Type
        </a>
    </div>
    @else

    {{-- ── TYPE CHIP + CHANGE LINK ── --}}
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
            <span class="text-sm font-semibold text-gray-700">Question type:</span>
            <span class="inline-flex items-center text-sm font-semibold border rounded-xl px-3 py-1 {{ $typeBadgeClasses[$selectedType] }}">
                {{ $typeLabels[$selectedType] }}
            </span>
        </div>
        <a
            href="{{ route('instructor.quizzes.show', ['quiz' => $quiz, 'open_modal' => 1]) }}"
            class="text-xs text-gray-400 hover:text-purple-600 underline underline-offset-2 transition"
        >
            Change type
        </a>
    </div>

    {{-- ── VALIDATION ERRORS ── --}}
    @if($errors->any())
    <div class="rounded-2xl border border-red-200 bg-red-50 px-5 py-4">
        <p class="text-sm font-semibold text-red-800 mb-1">Please fix the following errors:</p>
        <ul class="text-xs text-red-700 list-disc list-inside space-y-0.5">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    {{-- ── QUESTION FORM ── --}}
    <div
        x-data="questionForm(
            {{ json_encode($selectedType) }},
            {{ json_encode($initOptions) }},
            {{ json_encode($oldAnswers) }}
        )"
        class="rounded-2xl bg-white shadow-sm border border-gray-100"
    >
        <form
            method="POST"
            action="{{ route('instructor.quizzes.store-question', $quiz) }}"
            id="questionForm"
            enctype="multipart/form-data"
            @submit="handleSubmit($event)"
        >
            @csrf
            <input type="hidden" name="question_type" value="{{ $selectedType }}">
            <input type="hidden" name="after_save" id="afterSaveInput" value="return">

            <div class="p-6 space-y-6">

                {{-- ── QUESTION TEXT ── --}}
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <label for="question_text" class="text-sm font-semibold text-gray-700">
                            Question Text <span class="text-red-500">*</span>
                        </label>
                        @if($isBlankType)
                        <button
                            type="button"
                            @click="insertBlank()"
                            class="flex items-center gap-1.5 px-3 py-1 text-xs font-semibold text-purple-700 bg-purple-50 hover:bg-purple-100 border border-purple-200 rounded-xl transition"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            Insert Blank (_____)</button>
                        @endif
                    </div>

                    @if($isRichText)
                    {{-- TinyMCE textarea --}}
                    <textarea
                        id="question_text"
                        name="question_text"
                        rows="4"
                        class="w-full rounded-xl border-gray-200 shadow-sm focus:border-purple-400 focus:ring-purple-300 text-sm"
                    >{{ old('question_text') }}</textarea>
                    @else
                    {{-- Plain textarea for fill-blank types --}}
                    <textarea
                        id="question_text"
                        name="question_text"
                        rows="4"
                        required
                        @input="updateBlankCount()"
                        class="w-full rounded-xl border-gray-200 shadow-sm focus:border-purple-400 focus:ring-purple-300 text-sm font-mono"
                        placeholder="Type your question here. Use _____ (5 underscores) for each blank."
                    >{{ old('question_text') }}</textarea>
                    <div x-show="blankCount > 0" class="mt-1.5 flex items-center gap-2 text-xs">
                        <span class="font-semibold text-purple-600" x-text="blankCount + ' blank' + (blankCount !== 1 ? 's' : '') + ' detected'"></span>
                        <span class="text-gray-400">— add one acceptable answer per blank below</span>
                    </div>
                    @endif

                    @error('question_text')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                    <p x-show="questionTextError" x-text="questionTextError" class="mt-1 text-xs text-red-600"></p>
                </div>

                {{-- ── POINTS ── --}}
                <div>
                    <label for="points" class="block text-sm font-semibold text-gray-700 mb-2">
                        Points <span class="text-red-500">*</span>
                    </label>
                    <input
                        type="number"
                        id="points"
                        name="points"
                        min="1"
                        value="{{ old('points', 1) }}"
                        required
                        class="w-32 rounded-xl border-gray-200 shadow-sm focus:border-purple-400 focus:ring-purple-300 text-sm"
                    >
                    @error('points')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- ══════════════════════════════════════════════════════════
                     TYPE-SPECIFIC FIELDS
                ══════════════════════════════════════════════════════════ --}}

                {{-- ── CHOICE-BASED OPTIONS (multiple_choice / true_false / multiple_select) ── --}}
                @if($isChoiceType)
                <div>
                    <div class="flex items-center justify-between mb-3">
                        <div class="border-l-4 pl-3" style="border-color: #730DB1;">
                            <p class="text-sm font-semibold text-gray-900">Answer Options</p>
                            @if($selectedType === 'multiple_choice')
                                <p class="text-xs text-gray-400">Select the ONE correct answer using the radio button.</p>
                            @elseif($selectedType === 'multiple_select')
                                <p class="text-xs text-gray-400">Check ALL options that are correct answers.</p>
                            @else
                                <p class="text-xs text-gray-400">Select the correct answer — True or False.</p>
                            @endif
                        </div>
                        @if($selectedType !== 'true_false')
                        <button
                            type="button"
                            @click="addOption()"
                            class="flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-purple-700 bg-purple-50 hover:bg-purple-100 border border-purple-200 rounded-xl transition"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            Add Option
                        </button>
                        @endif
                    </div>

                    <div class="space-y-2">
                        <template x-for="(option, i) in options" :key="i">
                            <div class="flex items-center gap-3 p-3 rounded-xl border bg-gray-50/60"
                                 :class="option.isCorrect ? 'border-green-200 bg-green-50/50' : 'border-gray-100'">
                                {{-- Correct toggle --}}
                                @if($selectedType === 'multiple_select')
                                <input
                                    type="checkbox"
                                    name="correct_options[]"
                                    :value="i"
                                    :checked="option.isCorrect"
                                    @change="option.isCorrect = $event.target.checked"
                                    class="w-4 h-4 text-purple-600 rounded border-gray-300 focus:ring-purple-400"
                                >
                                @else
                                <input
                                    type="radio"
                                    name="correct_options[]"
                                    :value="i"
                                    :checked="option.isCorrect"
                                    @change="setOnlyCorrect(i)"
                                    class="w-4 h-4 text-purple-600 border-gray-300 focus:ring-purple-400"
                                >
                                @endif

                                {{-- Option label --}}
                                <span class="text-xs font-bold text-gray-400 w-5 text-center flex-shrink-0"
                                      x-text="String.fromCharCode(65 + i)">
                                </span>

                                {{-- Option text input --}}
                                <input
                                    type="text"
                                    name="options[]"
                                    :value="option.text"
                                    @input="option.text = $event.target.value"
                                    :readonly="option.readonly"
                                    :placeholder="'Option ' + String.fromCharCode(65 + i)"
                                    required
                                    class="flex-1 rounded-lg border-gray-200 shadow-sm focus:border-purple-400 focus:ring-purple-300 text-sm"
                                    :class="option.readonly ? 'bg-gray-100 cursor-not-allowed' : ''"
                                >

                                {{-- Correct badge --}}
                                <span
                                    x-show="option.isCorrect"
                                    class="text-xs font-semibold text-green-600 flex items-center gap-1 flex-shrink-0"
                                >
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    Correct
                                </span>

                                {{-- Remove button (not for true_false) --}}
                                @if($selectedType !== 'true_false')
                                <button
                                    type="button"
                                    @click="removeOption(i)"
                                    x-show="options.length > 2 && !option.readonly"
                                    class="p-1.5 rounded-lg text-gray-300 hover:text-red-500 hover:bg-red-50 transition flex-shrink-0"
                                >
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                                @endif
                            </div>
                        </template>
                    </div>

                    @error('options')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                    @error('correct_options')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                @endif

                {{-- ── FILL BLANK TEXT: Acceptable Answers ── --}}
                @if($selectedType === 'fill_blank_text')
                <div>
                    <div class="flex items-center justify-between mb-3">
                        <div class="border-l-4 pl-3" style="border-color: #730DB1;">
                            <p class="text-sm font-semibold text-gray-900">Acceptable Answers</p>
                            <p class="text-xs text-gray-400">Add every spelling or form you'll accept. If multiple blanks, add one answer per blank (in order).</p>
                        </div>
                        <button
                            type="button"
                            @click="addAnswer()"
                            class="flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-purple-700 bg-purple-50 hover:bg-purple-100 border border-purple-200 rounded-xl transition"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            Add Answer
                        </button>
                    </div>

                    <div class="space-y-2">
                        <template x-for="(answer, i) in answers" :key="i">
                            <div class="flex items-center gap-3">
                                <span class="text-xs text-gray-400 w-5 text-right flex-shrink-0" x-text="i + 1 + '.'"></span>
                                <input
                                    type="text"
                                    name="acceptable_answers[]"
                                    :value="answer"
                                    @input="answers[i] = $event.target.value"
                                    :placeholder="'Acceptable answer ' + (i + 1)"
                                    required
                                    class="flex-1 rounded-xl border-gray-200 shadow-sm focus:border-purple-400 focus:ring-purple-300 text-sm"
                                >
                                <button
                                    type="button"
                                    @click="removeAnswer(i)"
                                    x-show="answers.length > 1"
                                    class="p-1.5 rounded-lg text-gray-300 hover:text-red-500 hover:bg-red-50 transition"
                                >
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                            </div>
                        </template>
                    </div>

                    {{-- Case sensitive toggle --}}
                    <div class="mt-4 flex items-start gap-3 p-3 bg-yellow-50 border border-yellow-200 rounded-xl">
                        <input
                            type="checkbox"
                            id="case_sensitive"
                            name="case_sensitive"
                            value="1"
                            {{ old('case_sensitive') ? 'checked' : '' }}
                            class="mt-0.5 w-4 h-4 text-purple-600 border-gray-300 rounded focus:ring-purple-400"
                        >
                        <div>
                            <label for="case_sensitive" class="text-sm font-medium text-gray-700 cursor-pointer">Case Sensitive</label>
                            <p class="text-xs text-gray-500 mt-0.5">If checked, learners must match capitalization exactly (e.g., "Paris" is not equal to "paris").</p>
                        </div>
                    </div>

                    @error('acceptable_answers')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                @endif

                {{-- ── FILL BLANK SELECT: Word Bank + Correct Answers ── --}}
                @if($selectedType === 'fill_blank_select')
                <div class="space-y-5">
                    {{-- Word bank --}}
                    <div>
                        <div class="border-l-4 pl-3 mb-3" style="border-color: #730DB1;">
                            <p class="text-sm font-semibold text-gray-900">Word Bank</p>
                            <p class="text-xs text-gray-400">Enter all the words learners can choose from, separated by commas. Max 10 words.</p>
                        </div>
                        <input
                            type="text"
                            id="word_bank"
                            name="word_bank"
                            value="{{ old('word_bank') }}"
                            placeholder="e.g. hydrogen, oxygen, nitrogen, carbon, helium"
                            class="w-full rounded-xl border-gray-200 shadow-sm focus:border-purple-400 focus:ring-purple-300 text-sm"
                        >
                        @error('word_bank')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Correct answers (in order) --}}
                    <div>
                        <div class="flex items-center justify-between mb-3">
                            <div class="border-l-4 pl-3" style="border-color: #730DB1;">
                                <p class="text-sm font-semibold text-gray-900">Correct Answers (in order)</p>
                                <p class="text-xs text-gray-400">Add one correct word per blank, in the order the blanks appear in the question.</p>
                            </div>
                            <button
                                type="button"
                                @click="addAnswer()"
                                class="flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-purple-700 bg-purple-50 hover:bg-purple-100 border border-purple-200 rounded-xl transition"
                            >
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                </svg>
                                Add Blank Answer
                            </button>
                        </div>

                        <div class="space-y-2">
                            <template x-for="(answer, i) in answers" :key="i">
                                <div class="flex items-center gap-3">
                                    <span class="text-xs font-medium text-gray-400 flex-shrink-0">Blank <span x-text="i + 1"></span>:</span>
                                    <input
                                        type="text"
                                        name="acceptable_answers[]"
                                        :value="answer"
                                        @input="answers[i] = $event.target.value"
                                        :placeholder="'Correct word for blank ' + (i + 1)"
                                        required
                                        class="flex-1 rounded-xl border-gray-200 shadow-sm focus:border-purple-400 focus:ring-purple-300 text-sm"
                                    >
                                    <button
                                        type="button"
                                        @click="removeAnswer(i)"
                                        x-show="answers.length > 1"
                                        class="p-1.5 rounded-lg text-gray-300 hover:text-red-500 hover:bg-red-50 transition"
                                    >
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                    </button>
                                </div>
                            </template>
                        </div>

                        @error('acceptable_answers')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
                @endif

                {{-- ── IDENTIFICATION: Acceptable Answers + Image Upload ── --}}
                @if($selectedType === 'identification')
                <div class="space-y-5">
                    {{-- Acceptable answers --}}
                    <div>
                        <div class="flex items-center justify-between mb-3">
                            <div class="border-l-4 pl-3" style="border-color: #730DB1;">
                                <p class="text-sm font-semibold text-gray-900">Acceptable Answers</p>
                                <p class="text-xs text-gray-400">Add every acceptable short-answer response. Case sensitivity option below.</p>
                            </div>
                            <button
                                type="button"
                                @click="addAnswer()"
                                class="flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-purple-700 bg-purple-50 hover:bg-purple-100 border border-purple-200 rounded-xl transition"
                            >
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                </svg>
                                Add Answer
                            </button>
                        </div>

                        <div class="space-y-2">
                            <template x-for="(answer, i) in answers" :key="i">
                                <div class="flex items-center gap-3">
                                    <span class="text-xs text-gray-400 w-5 text-right flex-shrink-0" x-text="i + 1 + '.'"></span>
                                    <input
                                        type="text"
                                        name="acceptable_answers[]"
                                        :value="answer"
                                        @input="answers[i] = $event.target.value"
                                        :placeholder="'Acceptable answer ' + (i + 1)"
                                        required
                                        class="flex-1 rounded-xl border-gray-200 shadow-sm focus:border-purple-400 focus:ring-purple-300 text-sm"
                                    >
                                    <button
                                        type="button"
                                        @click="removeAnswer(i)"
                                        x-show="answers.length > 1"
                                        class="p-1.5 rounded-lg text-gray-300 hover:text-red-500 hover:bg-red-50 transition"
                                    >
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                    </button>
                                </div>
                            </template>
                        </div>

                        {{-- Case sensitive --}}
                        <div class="mt-4 flex items-start gap-3 p-3 bg-yellow-50 border border-yellow-200 rounded-xl">
                            <input
                                type="checkbox"
                                id="case_sensitive"
                                name="case_sensitive"
                                value="1"
                                {{ old('case_sensitive') ? 'checked' : '' }}
                                class="mt-0.5 w-4 h-4 text-purple-600 border-gray-300 rounded focus:ring-purple-400"
                            >
                            <div>
                                <label for="case_sensitive" class="text-sm font-medium text-gray-700 cursor-pointer">Case Sensitive</label>
                                <p class="text-xs text-gray-500 mt-0.5">If checked, learners must match capitalization exactly.</p>
                            </div>
                        </div>

                        @error('acceptable_answers')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Image upload --}}
                    <div>
                        <div class="border-l-4 pl-3 mb-3" style="border-color: #730DB1;">
                            <p class="text-sm font-semibold text-gray-900">Question Image <span class="text-gray-400 font-normal">(optional)</span></p>
                            <p class="text-xs text-gray-400">Attach an image as a visual context clue for the learner.</p>
                        </div>
                        <input
                            type="file"
                            id="image"
                            name="image"
                            accept="image/*"
                            class="block w-full text-sm text-gray-500 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-semibold file:bg-purple-50 file:text-purple-700 hover:file:bg-purple-100 transition"
                        >
                        <p class="text-xs text-gray-400 mt-1">JPG or PNG, max 2 MB.</p>
                        @error('image')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
                @endif

            </div>{{-- end fields --}}

            {{-- ── SAVE BUTTONS ── --}}
            <div class="px-6 py-5 border-t border-gray-100 flex flex-col sm:flex-row gap-3">
                {{-- Save & Return (default) --}}
                <button
                    type="submit"
                    class="flex-1 flex items-center justify-center gap-2 px-5 py-3 text-sm font-semibold text-white rounded-xl transition hover:opacity-90 active:scale-[0.98]"
                    style="background: linear-gradient(135deg, #A30EB2, #730DB1, #3B0CB1);"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
                    </svg>
                    Save & Return to Question Bank
                </button>

                {{-- Save & Add Another --}}
                <button
                    type="button"
                    @click="document.getElementById('afterSaveInput').value = 'another'; document.getElementById('questionForm').requestSubmit();"
                    class="flex-1 flex items-center justify-center gap-2 px-5 py-3 text-sm font-semibold text-purple-700 bg-white border-2 border-purple-200 rounded-xl hover:border-purple-400 hover:bg-purple-50 transition active:scale-[0.98]"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Save & Add Another ({{ $typeLabels[$selectedType] }})
                </button>

                {{-- Cancel --}}
                <a
                    href="{{ route('instructor.quizzes.show', $quiz) }}"
                    class="flex items-center justify-center px-4 py-3 text-sm font-semibold text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-xl transition"
                >
                    Cancel
                </a>
            </div>
        </form>
    </div>
    @endif

</div>
@endsection

@push('scripts')
<script>
function questionForm(type, initOptions, initAnswers) {
    return {
        questionType: type,
        options: initOptions || [],
        answers: initAnswers || [''],
        blankCount: {{ $initBlanks }},
        questionTextError: '',

        addOption() {
            this.options.push({ text: '', isCorrect: false, readonly: false });
        },
        removeOption(i) {
            if (this.options.length > 2) this.options.splice(i, 1);
        },
        setOnlyCorrect(i) {
            this.options.forEach((o, j) => { o.isCorrect = (j === i); });
        },

        addAnswer() {
            this.answers.push('');
        },
        removeAnswer(i) {
            if (this.answers.length > 1) this.answers.splice(i, 1);
        },

        updateBlankCount() {
            const ta = document.getElementById('question_text');
            if (!ta) return;
            this.blankCount = (ta.value.match(/_____/g) || []).length;
        },
        insertBlank() {
            const ta = document.getElementById('question_text');
            if (!ta) return;
            const start = ta.selectionStart;
            const end   = ta.selectionEnd;
            const blank = '_____';
            ta.value = ta.value.substring(0, start) + blank + ta.value.substring(end);
            ta.selectionStart = ta.selectionEnd = start + blank.length;
            ta.focus();
            this.updateBlankCount();
        },

        syncTinyMce() {
            if (typeof tinymce !== 'undefined') {
                const ed = tinymce.get('question_text');
                if (ed) ed.save();
            }
        },

        handleSubmit(event) {
            this.syncTinyMce();

            // TinyMCE hides the textarea, so native `required` cannot focus it.
            // Validate rich-text content manually before allowing submit.
            if (this.questionType === 'multiple_choice' || this.questionType === 'true_false' || this.questionType === 'multiple_select' || this.questionType === 'identification') {
                const ta = document.getElementById('question_text');
                const plainText = (ta?.value || '')
                    .replace(/<[^>]*>/g, ' ')
                    .replace(/&nbsp;/gi, ' ')
                    .trim();

                if (!plainText) {
                    event.preventDefault();
                    this.questionTextError = 'Question text is required.';
                    const ed = typeof tinymce !== 'undefined' ? tinymce.get('question_text') : null;
                    if (ed) {
                        ed.focus();
                    }
                    return;
                }
            }

            this.questionTextError = '';
        },
    };
}

@if($isRichText && $selectedType)
document.addEventListener('DOMContentLoaded', function () {
    tinymce.init({
        selector: '#question_text',
        license_key: 'gpl',
        promotion: false,
        height: 220,
        menubar: false,
        plugins: [
            'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview',
            'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
            'insertdatetime', 'media', 'table', 'help', 'wordcount',
        ],
        toolbar: 'undo redo | blocks | bold italic underline | alignleft aligncenter alignright | bullist numlist | image link | removeformat',
        images_upload_url: '{{ route("instructor.upload.image") }}',
        automatic_uploads: true,
        images_reuse_filename: true,
        file_picker_types: 'image',
        file_picker_callback: function (callback, value, meta) {
            if (meta.filetype === 'image') {
                const input = document.createElement('input');
                input.setAttribute('type', 'file');
                input.setAttribute('accept', 'image/*');
                input.onchange = function () {
                    const file = this.files[0];
                    const reader = new FileReader();
                    reader.onload = function () {
                        callback(reader.result, { alt: file.name });
                    };
                    reader.readAsDataURL(file);
                };
                input.click();
            }
        },
        content_style: 'body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; font-size: 14px; }',
    });
});
@endif
</script>
@endpush
