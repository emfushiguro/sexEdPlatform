@php
    $progress = ($checkpointProgress ?? collect())->get($question->id);
    $blankCount = substr_count($question->question_text, '_____');
    $parts = $blankCount > 0 ? explode('_____', $question->question_text) : [];
    $isPerspectiveFeedback = $question->question_type === 'perspective_feedback';
    $initialResult = $progress?->latest_answer;
    $initialFeedback = $progress?->status === 'completed'
        ? ($initialResult['feedback'] ?? null)
        : null;
    $isStandaloneCheckpoint = $question->checkpoint_block_uuid === null;
    $ownsFooterOnLoad = $isStandaloneCheckpoint || in_array($progress?->status, ['correct', 'completed', 'skipped'], true);
    $checkpointContinueUrl = null;

    if ($isStandaloneCheckpoint && isset($currentTopic, $currentTopicIndex, $lessonTopics) && $currentTopic->id === $question->checkpoint_topic_id) {
        if ($currentTopicIndex < $lessonTopics->count() - 1) {
            $checkpointContinueUrl = route('learner.lessons.show', ['lesson' => $lesson->id, 'topic' => $currentTopicIndex + 1]);
        } elseif ($lessonQuiz) {
            $checkpointContinueUrl = route('learner.lessons.show', ['lesson' => $lesson->id, 'quiz' => 1]);
        } elseif ($nextLesson) {
            $checkpointContinueUrl = route('learner.lessons.show', $nextLesson);
        } else {
            $checkpointContinueUrl = route('learner.modules.show', $module);
        }
    }
@endphp

<section data-optional-interaction="checkpoint:{{ $question->id }}"
    x-data="interactiveCheckpoint(@js([
        'type' => $question->question_type,
        'questionId' => $question->id,
        'blankCount' => max(1, $blankCount),
        'wordBank' => $question->question_type === 'fill_blank_select' ? $question->word_bank : null,
        'submitUrl' => route('learner.checkpoints.submit', $question),
        'skipUrl' => route('learner.checkpoints.skip', $question),
        'csrf' => csrf_token(),
        'initialStatus' => $progress?->status,
        'initialResult' => $initialResult,
        'initialFeedback' => $initialFeedback,
        'initialExplanation' => in_array($progress?->status, ['correct', 'completed'], true)
            ? $question->explanation
            : null,
        'perspectiveCharacterLimit' => $question->perspective_character_limit,
        'continueUrl' => $checkpointContinueUrl,
    ]))"
    x-init="if (@js($ownsFooterOnLoad)) $dispatch('checkpoint-active', { questionId: {{ $question->id }}, token: 'checkpoint:{{ $question->id }}', initial: true })"
    @focusin="$dispatch('checkpoint-active', { questionId: {{ $question->id }}, token: 'checkpoint:{{ $question->id }}' })"
    class="my-6 rounded-2xl border border-purple-200 bg-purple-50/50 dark:border-purple-800 dark:bg-purple-900/10 p-5">
    <p class="text-xs font-bold uppercase tracking-widest text-purple-700 dark:text-purple-300">{{ $isPerspectiveFeedback ? 'Perspective Feedback' : 'Quick Check' }}</p>
    <h3 class="mt-2 text-base font-semibold text-gray-900 dark:text-white">
        @if(in_array($question->question_type, ['fill_blank_text', 'fill_blank_select']) && $blankCount > 0)
            @foreach($parts as $index => $part)
                {!! $part !!}
                @if($index < $blankCount)
                    <button type="button" x-show="wordBank" @click="if (wordBank?.selectedIndices[{{ $index }}] !== null) { wordBank.removeWord({{ $index }}); $store.learningAudio.play('selection') }" class="inline-flex min-w-28 border-b-2 border-purple-300 align-baseline" x-text="wordBank?.answers()[{{ $index }}] || '_____'"></button>
                @endif
            @endforeach
        @else
            {!! $question->question_text !!}
        @endif
    </h3>

    @if($question->image_url)
        <img src="{{ $question->image_url }}" alt="Question image" class="mt-4 max-h-56 rounded-xl border object-contain">
    @endif

    @if($isPerspectiveFeedback && $question->context_description)
        <p class="mt-3 whitespace-pre-line text-sm text-gray-600 dark:text-gray-300">{{ $question->context_description }}</p>
    @endif

    @if($isPerspectiveFeedback)
        <div x-show="state !== 'completed'" class="mt-4">
            @if($question->allow_own_perspective)
                <fieldset class="mt-5">
                    <legend class="text-sm font-semibold text-gray-900 dark:text-white">How would you like to respond?</legend>
                    <div class="mt-3 grid gap-3 sm:grid-cols-2">
                        <label class="min-h-11 rounded-xl border bg-white p-4 dark:bg-gray-900" :class="answer.pathway === 'guided' ? 'border-purple-600 ring-2 ring-purple-200' : 'border-gray-200 dark:border-gray-700'">
                            <input type="radio" name="perspective_pathway_{{ $question->id }}" value="guided" :checked="answer.pathway === 'guided'" @change="choosePerspectivePathway('guided'); $store.learningAudio.play('selection')">
                            <span class="ml-2 font-semibold">Choose a Response</span>
                        </label>
                        <label class="min-h-11 rounded-xl border bg-white p-4 dark:bg-gray-900" :class="answer.pathway === 'own' ? 'border-purple-600 ring-2 ring-purple-200' : 'border-gray-200 dark:border-gray-700'">
                            <input type="radio" name="perspective_pathway_{{ $question->id }}" value="own" :checked="answer.pathway === 'own'" @change="choosePerspectivePathway('own'); $store.learningAudio.play('selection')">
                            <span class="ml-2 font-semibold">Share Your Perspective</span>
                        </label>
                    </div>
                </fieldset>
            @else
                <span x-init="choosePerspectivePathway('guided')"></span>
            @endif

            <fieldset x-show="answer.pathway === 'guided'" class="mt-5">
                <legend class="text-sm font-semibold text-gray-900 dark:text-white">Choose a Response</legend>
                <div class="mt-3 space-y-3">
                    @foreach($question->options as $option)
                        <label class="flex min-h-11 items-center gap-3 rounded-xl border bg-white p-3 dark:bg-gray-900" :class="Number(answer.option_id) === {{ $option->id }} ? 'border-purple-600 ring-2 ring-purple-200' : 'border-gray-200 dark:border-gray-700'">
                            <input type="radio" name="perspective_option_{{ $question->id }}" value="{{ $option->id }}" x-model.number="answer.option_id" @change="$store.learningAudio.play('selection')">
                            <span>{{ $option->option_text }}</span>
                        </label>
                    @endforeach
                </div>
            </fieldset>

            @if($question->allow_own_perspective)
                <section x-show="answer.pathway === 'own'" class="mt-5" aria-labelledby="perspective_prompt_{{ $question->id }}">
                    <h4 id="perspective_prompt_{{ $question->id }}" class="text-sm font-semibold text-gray-900 dark:text-white">{{ $question->perspective_prompt }}</h4>
                    @if($question->reflection_guide)
                        <aside class="mt-3 rounded-xl border border-sky-200 bg-sky-50 p-4 text-sm text-sky-900" aria-label="Reflection Guide">
                            <p class="font-semibold">Reflection Guide</p>
                            <p class="mt-1 whitespace-pre-line">{{ $question->reflection_guide }}</p>
                        </aside>
                    @endif
                    <label for="perspective_text_{{ $question->id }}" class="mt-4 block text-sm font-semibold text-gray-700 dark:text-gray-200">Your Perspective</label>
                    <textarea id="perspective_text_{{ $question->id }}" x-model="answer.perspective_text" rows="5" :aria-describedby="`perspective_count_{{ $question->id }}`" class="mt-2 w-full rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900"></textarea>
                    <p id="perspective_count_{{ $question->id }}" class="mt-1 text-xs text-gray-500" aria-live="polite"><span x-text="remainingPerspectiveCharacters()"></span> characters remaining</p>
                </section>
            @endif
        </div>

        <section x-show="state === 'completed'" class="mt-5 rounded-xl border border-sky-200 bg-sky-50 p-4 text-sky-950" aria-live="polite">
            <p class="font-semibold" x-text="result?.pathway === 'guided' ? 'Your Response' : 'Your Perspective'"></p>
            <p class="mt-2 whitespace-pre-line text-sm" x-text="result?.pathway === 'guided' ? result?.option_text : result?.perspective_text"></p>
            <div x-show="result?.pathway === 'guided' && feedback" class="mt-4">
                <p class="font-semibold">Feedback</p>
                <p class="mt-1 whitespace-pre-line text-sm" x-text="feedback"></p>
            </div>
            <div x-show="explanation" class="mt-4">
                <p class="font-semibold">Why This Matters</p>
                <p class="mt-1 whitespace-pre-line text-sm" x-text="explanation"></p>
            </div>
        </section>
    @endif

    <div class="mt-4 space-y-3">
        @if(! $isPerspectiveFeedback && in_array($question->question_type, ['multiple_choice', 'true_false']))
            @foreach($question->options as $option)
                <label class="flex items-center gap-3 rounded-xl border border-gray-200 bg-white p-3 dark:border-gray-700 dark:bg-gray-900">
                    <input type="radio" name="checkpoint_{{ $question->id }}" value="{{ $option->id }}" x-model="answer" @change="$store.learningAudio.play('selection')">
                    <span>{{ $option->option_text }}</span>
                </label>
            @endforeach
        @elseif(! $isPerspectiveFeedback && $question->question_type === 'multiple_select')
            @foreach($question->options as $option)
                <label class="flex items-center gap-3 rounded-xl border border-gray-200 bg-white p-3 dark:border-gray-700 dark:bg-gray-900">
                    <input type="checkbox" value="{{ $option->id }}" x-model="answer" @change="$store.learningAudio.play('selection')">
                    <span>{{ $option->option_text }}</span>
                </label>
            @endforeach
        @elseif(! $isPerspectiveFeedback && $question->question_type === 'fill_blank_text')
            @for($i = 0; $i < max(1, $blankCount); $i++)
                <input type="text" x-model="answer[{{ $i }}]" class="w-full rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900" placeholder="Blank {{ $i + 1 }}">
            @endfor
        @elseif(! $isPerspectiveFeedback && $question->question_type === 'identification')
            <input type="text" x-model="answer" class="w-full rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900" placeholder="Your answer">
        @elseif(! $isPerspectiveFeedback && $question->question_type === 'fill_blank_select')
            <div class="flex flex-wrap gap-2">
                <template x-for="(word, wordIndex) in wordBank?.words || []" :key="wordIndex">
                    <button type="button" @click="if (!wordBank.isUsed(wordIndex) && wordBank.selectedIndices.includes(null)) { wordBank.selectWord(wordIndex); $store.learningAudio.play('selection') }" :disabled="wordBank.isUsed(wordIndex)" x-text="word" class="rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm font-medium hover:bg-purple-50 disabled:opacity-40 dark:border-gray-700 dark:bg-gray-900"></button>
                </template>
            </div>
        @endif
    </div>

    @if(! $isPerspectiveFeedback)
        <div x-show="state !== 'ready' && state !== 'submitting'" class="mt-4 rounded-xl p-4" :class="state === 'correct' ? 'bg-green-50 text-green-800' : state === 'incorrect' ? 'bg-red-50 text-red-800' : 'bg-gray-100 text-gray-700'">
        <p class="font-semibold" x-text="state === 'correct' ? 'Correct' : state === 'incorrect' ? 'Not quite; try again.' : state === 'skipped' ? 'Skipped for now.' : error"></p>
        <p x-show="state === 'correct' && explanation" class="mt-2 text-sm" x-text="explanation"></p>
        </div>
    @endif

    <div class="mt-5 flex flex-wrap items-center gap-3">
        @if($isPerspectiveFeedback)
            <button type="button" x-show="state === 'ready'" :disabled="state === 'submitting'" @click="submit()" class="rounded-xl px-4 py-2 text-sm font-semibold text-white disabled:opacity-50" style="background: linear-gradient(135deg, #A30EB2, #3B0CB1);" x-text="answer.pathway === 'own' ? 'Submit Perspective' : 'Submit Response'"></button>
        @else
            <button type="button" x-show="state === 'ready'" :disabled="state === 'submitting'" @click="submit()" class="rounded-xl px-4 py-2 text-sm font-semibold text-white disabled:opacity-50" style="background: linear-gradient(135deg, #A30EB2, #3B0CB1);">
                Check Answer
            </button>
            <button type="button" x-show="state === 'incorrect' || state === 'error'" @click="retry()" class="rounded-xl border border-gray-200 px-4 py-2 text-sm font-semibold dark:border-gray-700">
                Retry
            </button>
        @endif
        <button type="button" x-show="showSkip()" @click="skip()" class="text-sm font-semibold text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
            Skip for now
        </button>
        <button type="button" x-show="showContinue()" @click="continueLearning()" class="rounded-xl border border-gray-200 px-4 py-2 text-sm font-semibold dark:border-gray-700">
            Continue
        </button>
    </div>

    @if($progress?->status)
        <p class="mt-3 text-xs text-gray-500">Last status: {{ str_replace('_', ' ', $progress->status) }}</p>
    @endif
</section>
