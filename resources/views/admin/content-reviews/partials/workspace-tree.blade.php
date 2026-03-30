<div class="bg-white shadow-sm rounded-xl border border-gray-200 p-5" x-data="{ openLessons: [] }">
    <div class="flex items-center justify-between">
        <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-500">Content Structure</h2>
        <span class="text-xs text-gray-500">{{ data_get($workspace, 'hierarchy.lesson_count', 0) }} lessons · {{ data_get($workspace, 'hierarchy.quiz_count', 0) }} quizzes</span>
    </div>

    <div class="mt-4 space-y-3">
        @foreach(data_get($workspace, 'hierarchy.lessons', []) as $lesson)
            @php
                $lessonId = data_get($lesson, 'attributes.id');
                $topicItems = data_get($lesson, 'topics', []);
            @endphp
            <div class="rounded-lg border border-gray-100">
                <button type="button"
                    data-testid="review-tree-lesson-node"
                    class="w-full flex items-center justify-between px-3 py-2 text-left"
                    @click="openLessons.includes({{ (int) $lessonId }}) ? openLessons = openLessons.filter(id => id !== {{ (int) $lessonId }}) : openLessons.push({{ (int) $lessonId }})">
                    <span class="text-sm font-semibold text-gray-900">{{ data_get($lesson, 'attributes.title', 'Untitled Lesson') }}</span>
                    <span class="text-xs text-gray-500">{{ count($topicItems) }} topics</span>
                </button>

                <div x-show="openLessons.includes({{ (int) $lessonId }})" class="border-t border-gray-100 px-4 py-3 space-y-2" style="display:none;">
                    @foreach($topicItems as $topic)
                        <div class="rounded-md bg-gray-50 px-3 py-2 text-sm">
                            <p class="font-medium text-gray-900">{{ data_get($topic, 'title', 'Untitled Topic') }}</p>
                            <p class="text-xs text-gray-500 mt-1">Type: {{ data_get($topic, 'type', 'unknown') }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach

        @foreach(data_get($workspace, 'hierarchy.quizzes', []) as $quiz)
            <div class="rounded-lg border border-gray-100 px-3 py-3">
                <p class="text-sm font-semibold text-gray-900">Quiz: {{ data_get($quiz, 'attributes.title', 'Untitled Quiz') }}</p>
                <p class="text-xs text-gray-500 mt-1">Passing score: {{ data_get($quiz, 'attributes.passing_score', '-') }}%</p>

                <div class="mt-3 space-y-3">
                    @foreach((array) data_get($quiz, 'questions', []) as $question)
                        <div class="rounded-md border border-gray-200 bg-gray-50 px-3 py-2">
                            <div class="flex items-center justify-between gap-3">
                                <p class="text-sm font-medium text-gray-900">{{ data_get($question, 'attributes.question_text', 'Untitled question') }}</p>
                                <span class="text-[11px] font-semibold uppercase tracking-wide text-gray-500">{{ str_replace('_', ' ', data_get($question, 'attributes.question_type', 'unknown')) }}</span>
                            </div>

                            @if(!empty(data_get($question, 'options', [])))
                                <ul class="mt-2 space-y-1">
                                    @foreach((array) data_get($question, 'options', []) as $option)
                                        <li class="text-xs text-gray-700 flex items-center gap-2">
                                            <span>{{ data_get($option, 'option_text', 'Option') }}</span>
                                            @if((bool) data_get($option, 'is_correct', false))
                                                <span class="inline-flex items-center rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-semibold text-emerald-700">Correct Answer</span>
                                            @endif
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
</div>
