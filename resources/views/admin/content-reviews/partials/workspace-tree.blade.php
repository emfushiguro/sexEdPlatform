<div class="bg-white shadow-sm rounded-xl border border-gray-200 p-5"
    x-data="{
        previewOpen: false,
        previewLoading: false,
        previewError: '',
        previewNodeType: null,
        previewTopic: null,
        previewQuiz: null,
        async previewNode(nodeType, nodeId) {
            this.previewOpen = true;
            this.previewLoading = true;
            this.previewError = '';
            this.previewNodeType = nodeType;
            this.previewTopic = null;
            this.previewQuiz = null;

            try {
                const response = await fetch('{{ route('admin.content-reviews.preview', $reviewRequest) }}?node_type=' + encodeURIComponent(nodeType) + '&node_id=' + nodeId, {
                    headers: { 'Accept': 'application/json' }
                });

                if (!response.ok) {
                    throw new Error('Unable to load preview content.');
                }

                const payload = await response.json();

                if (nodeType === 'topic') {
                    this.previewTopic = payload.node;
                } else {
                    this.previewQuiz = payload.node;
                }
            } catch (error) {
                this.previewError = error.message || 'Unable to load preview content.';
            } finally {
                this.previewLoading = false;
            }
        },
        previewTopicContent(topicId) {
            this.previewNode('topic', topicId);
        },
        previewQuizContent(quizId) {
            this.previewNode('quiz', quizId);
        },
        formatLabel(value) {
            if (!value) {
                return 'N/A';
            }

            return String(value)
                .replace(/[_-]+/g, ' ')
                .replace(/\s+/g, ' ')
                .trim()
                .replace(/\b\w/g, (char) => char.toUpperCase());
        },
        formatLimit(value) {
            if (value === null || value === undefined || value === '') {
                return 'No limit';
            }

            return value;
        },
        questionTypeLabel(value) {
            const map = {
                multiple_choice: 'Multiple Choice',
                true_false: 'True / False',
                multiple_select: 'Multiple Select',
                fill_blank_text: 'Fill Blank (Text)',
                fill_blank_select: 'Fill Blank (Word Bank)',
                identification: 'Identification',
            };

            if (!value) {
                return 'Unknown';
            }

            return map[value] || this.formatLabel(value);
        },
        questionTypeClasses(value) {
            const map = {
                multiple_choice: 'bg-blue-50 text-blue-700 border-blue-100',
                true_false: 'bg-green-50 text-green-700 border-green-100',
                multiple_select: 'bg-purple-50 text-purple-700 border-purple-100',
                fill_blank_text: 'bg-yellow-50 text-yellow-700 border-yellow-100',
                fill_blank_select: 'bg-orange-50 text-orange-700 border-orange-100',
                identification: 'bg-pink-50 text-pink-700 border-pink-100',
            };

            return map[value] || 'bg-gray-100 text-gray-600 border-gray-200';
        },
        optionLabel(index) {
            return String.fromCharCode(65 + index);
        },
        quizQuestionCount() {
            return Array.isArray(this.previewQuiz?.questions) ? this.previewQuiz.questions.length : 0;
        },
        quizTotalPoints() {
            if (!Array.isArray(this.previewQuiz?.questions)) {
                return 0;
            }

            return this.previewQuiz.questions.reduce((total, question) => {
                const points = Number(question?.attributes?.points || 0);
                return total + (Number.isNaN(points) ? 0 : points);
            }, 0);
        },
        normalizeImageUrl(path) {
            if (!path) {
                return null;
            }

            const value = String(path).trim();
            if (!value) {
                return null;
            }

            if (value.startsWith('http://') || value.startsWith('https://') || value.startsWith('//')) {
                return value;
            }

            if (value.startsWith('/storage/')) {
                return value;
            }

            return '/storage/' + value.replace(/^\/+/, '');
        }
    }">
    <div class="flex items-center justify-between">
        <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-500">Content Structure</h2>
        <span class="text-xs text-gray-500">{{ data_get($workspace, 'hierarchy.lesson_count', 0) }} lessons · {{ data_get($workspace, 'hierarchy.quiz_count', 0) }} quizzes</span>
    </div>

    <div class="mt-4 rounded-xl border border-gray-100 bg-gray-50/70">
        <div class="flex items-center justify-between border-b border-gray-100 px-4 py-3">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Learner Module Progression</p>
                <p class="text-xs text-gray-400 mt-0.5">Mirrors learner-side lesson flow and unlock structure</p>
            </div>
            <span class="rounded-full bg-white px-2.5 py-1 text-[11px] font-semibold text-gray-600 border border-gray-200">
                {{ data_get($workspace, 'hierarchy.lesson_count', 0) }} {{ \Illuminate\Support\Str::plural('lesson', (int) data_get($workspace, 'hierarchy.lesson_count', 0)) }}
            </span>
        </div>

        <div class="divide-y divide-gray-100">
            @forelse(data_get($workspace, 'hierarchy.lessons', []) as $lesson)
                @php
                    $lessonId = (int) data_get($lesson, 'attributes.id', 0);
                    $topicItems = (array) data_get($lesson, 'topics', []);
                    $lessonQuizzes = (array) data_get($workspace, 'hierarchy.quizzes_by_lesson.' . $lessonId, []);
                    $topicCount = count($topicItems);
                    $quizCount = count($lessonQuizzes);
                    $requirementLabel = data_get($lesson, 'requirement_label', 'Optional Lesson');
                    $isRequired = data_get($lesson, 'requirement_type') === 'required';
                @endphp
                <div data-testid="review-tree-lesson-node" class="px-4 py-4">
                    <div class="flex items-start gap-3">
                        <span class="inline-flex mt-0.5 h-7 w-7 flex-shrink-0 items-center justify-center rounded-full text-xs font-bold {{ $isRequired ? 'bg-purple-100 text-purple-700' : 'bg-gray-100 text-gray-500' }}">
                            {{ $loop->iteration }}
                        </span>

                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2 flex-wrap">
                                <p class="truncate text-sm font-semibold text-gray-900">{{ data_get($lesson, 'attributes.title', 'Untitled Lesson') }}</p>
                                <span class="inline-flex rounded-full px-2 py-0.5 text-[10px] font-semibold {{ $isRequired ? 'bg-purple-50 text-purple-700 border border-purple-100' : 'bg-gray-100 text-gray-600 border border-gray-200' }}">
                                    {{ $requirementLabel }}
                                </span>
                            </div>
                            <p class="mt-1 text-xs text-gray-500">{{ $topicCount }} topics · {{ $quizCount }} quizzes</p>

                            <div class="mt-3 space-y-0">
                                @foreach($topicItems as $topic)
                                    @php
                                        $topicId = (int) data_get($topic, 'id', 0);
                                        $typeLabel = \Illuminate\Support\Str::upper(str_replace(['_', '-'], ' ', (string) data_get($topic, 'type', 'unknown')));
                                        $isPrerequisite = (bool) data_get($topic, 'is_prerequisite', false);
                                        $showConnector = !$loop->last || $quizCount > 0;
                                    @endphp

                                    <div class="flex gap-3">
                                        <div class="flex flex-col items-center flex-shrink-0" style="width:16px;">
                                            <div class="mt-2 h-3.5 w-3.5 rounded-full border-2 {{ $isPrerequisite ? 'border-amber-400 bg-amber-50' : 'border-gray-300 bg-white' }}"></div>
                                            @if($showConnector)
                                                <div class="mt-1 min-h-[16px] flex-1 rounded-full bg-gray-200" style="width:2px;"></div>
                                            @endif
                                        </div>

                                        <div class="flex-1 {{ $showConnector ? 'pb-2.5' : 'pb-1' }}">
                                            <div class="rounded-lg border border-gray-100 bg-white px-3 py-2.5">
                                                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                                    <div class="min-w-0">
                                                        <p class="text-sm font-medium text-gray-900 truncate">{{ data_get($topic, 'title', 'Untitled Topic') }}</p>
                                                        <p class="mt-1 text-xs text-gray-500">{{ $typeLabel }} · Prerequisite: {{ $isPrerequisite ? 'Yes' : 'No' }}</p>
                                                    </div>
                                                    @if($topicId > 0)
                                                        <button type="button"
                                                            class="inline-flex items-center gap-1 rounded-lg border border-purple-200 bg-purple-50 px-2.5 py-1 text-xs font-semibold text-purple-700 hover:bg-purple-100"
                                                            @click="previewTopicContent({{ $topicId }})">
                                                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                            </svg>
                                                            Preview Topic
                                                        </button>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach

                                @foreach($lessonQuizzes as $quiz)
                                    @php
                                        $quizId = (int) data_get($quiz, 'attributes.id', 0);
                                        $showConnector = !$loop->last;
                                    @endphp

                                    <div class="flex gap-3">
                                        <div class="flex flex-col items-center flex-shrink-0" style="width:16px;">
                                            <div class="mt-2 h-3.5 w-3.5 rounded-full border-2 border-purple-300 bg-purple-50"></div>
                                            @if($showConnector)
                                                <div class="mt-1 min-h-[16px] flex-1 rounded-full bg-gray-200" style="width:2px;"></div>
                                            @endif
                                        </div>

                                        <div class="flex-1 {{ $showConnector ? 'pb-2.5' : 'pb-1' }}">
                                            <div class="rounded-lg border border-purple-100 bg-purple-50/40 px-3 py-2.5">
                                                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                                    <div class="min-w-0">
                                                        <p class="text-sm font-semibold text-gray-900 truncate">Lesson Quiz: {{ data_get($quiz, 'attributes.title', 'Untitled Quiz') }}</p>
                                                        <p class="mt-1 text-xs text-gray-500">Passing score: {{ data_get($quiz, 'attributes.passing_score', '-') }}% · Attempt limit: {{ data_get($quiz, 'attributes.attempt_limit', 'No limit') }}</p>
                                                    </div>
                                                    @if($quizId > 0)
                                                        <button type="button"
                                                            class="inline-flex items-center gap-1 rounded-lg border border-purple-200 bg-white px-2.5 py-1 text-xs font-semibold text-purple-700 hover:bg-purple-100"
                                                            @click="previewQuizContent({{ $quizId }})">
                                                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                            </svg>
                                                            Preview Quiz
                                                        </button>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach

                                @if($topicCount === 0 && $quizCount === 0)
                                    <div class="rounded-lg border border-dashed border-gray-200 bg-gray-50 px-3 py-2.5 text-xs text-gray-500">
                                        No topics or quizzes were included for this lesson in the submitted revision.
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="px-4 py-6 text-sm text-gray-500">No lesson progression available for this submission.</div>
            @endforelse
        </div>
    </div>

    <div class="mt-4 space-y-3">
        @foreach((array) data_get($workspace, 'hierarchy.final_quizzes', []) as $quiz)
            @php
                $quizId = (int) data_get($quiz, 'attributes.id', 0);
            @endphp
            <div class="rounded-lg border border-purple-100 bg-purple-50/50 px-3 py-3">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <p class="text-sm font-semibold text-gray-900">Final Quiz: {{ data_get($quiz, 'attributes.title', 'Untitled Quiz') }}</p>
                        <p class="text-xs text-gray-500 mt-1">Passing score: {{ data_get($quiz, 'attributes.passing_score', '-') }}% · Time limit: {{ data_get($quiz, 'attributes.time_limit', 'No limit') }} min</p>
                    </div>
                    @if($quizId > 0)
                        <button type="button"
                            class="inline-flex items-center gap-1 rounded-lg border border-purple-200 bg-white px-2.5 py-1 text-xs font-semibold text-purple-700 hover:bg-purple-100"
                            @click="previewQuizContent({{ $quizId }})">
                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                            Preview Quiz
                        </button>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    <div x-show="previewOpen"
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/50 px-4"
         @click.self="previewOpen = false">
        <div class="w-full max-w-5xl overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-2xl">
            <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
                <h3 class="text-base font-semibold text-gray-900" x-text="previewNodeType === 'quiz' ? 'Quiz Preview' : 'Lesson Topic Preview'"></h3>
                <button type="button" class="rounded-lg p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-600" @click="previewOpen = false">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div class="max-h-[70vh] overflow-y-auto px-5 py-4">
                <template x-if="previewLoading">
                    <p class="text-sm text-gray-500">Loading preview...</p>
                </template>

                <template x-if="previewError && !previewLoading">
                    <p class="text-sm text-rose-600" x-text="previewError"></p>
                </template>

                <template x-if="previewTopic && !previewLoading">
                    <div class="space-y-4">
                        <div class="rounded-xl border border-gray-100 bg-gray-50 p-4">
                            <p class="text-sm font-semibold text-gray-900" x-text="previewTopic.title"></p>
                            <p class="mt-1 text-xs text-gray-500" x-text="previewTopic.topic_type_label"></p>
                        </div>

                        <div class="prose max-w-none" x-html="previewTopic.text_content"></div>

                        <template x-if="previewTopic.video_url">
                            <div class="overflow-hidden rounded-xl border border-gray-200">
                                <iframe class="h-72 w-full" :src="previewTopic.video_url" allowfullscreen></iframe>
                            </div>
                        </template>

                        <template x-if="previewTopic.video_file_url">
                            <video controls class="w-full rounded-xl border border-gray-200" :src="previewTopic.video_file_url"></video>
                        </template>

                        <template x-if="previewTopic.file_url">
                            <a class="inline-flex items-center rounded-lg border border-sky-200 bg-sky-50 px-3 py-2 text-sm font-semibold text-sky-700" :href="previewTopic.file_url" target="_blank" rel="noopener">Open Attachment</a>
                        </template>

                        <template x-if="Array.isArray(previewTopic.image_attachment_urls) && previewTopic.image_attachment_urls.length">
                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                <template x-for="(imageUrl, imageIndex) in previewTopic.image_attachment_urls" :key="'image-' + imageIndex">
                                    <img :src="imageUrl" alt="Topic attachment" class="h-44 w-full rounded-xl border border-gray-200 object-cover">
                                </template>
                            </div>
                        </template>
                    </div>
                </template>

                <template x-if="previewQuiz && !previewLoading">
                    <div class="space-y-5">
                        <div class="rounded-2xl border border-gray-100 bg-gray-50 px-5 py-4">
                            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">Quiz Information</p>
                            <p class="mt-1 text-lg font-bold text-gray-900" x-text="previewQuiz.title || 'Untitled Quiz'"></p>
                            <p class="mt-1 text-sm text-gray-600" x-text="previewQuiz.description || 'No description provided.'"></p>
                        </div>

                        <dl class="grid grid-cols-1 gap-3 text-sm sm:grid-cols-2 lg:grid-cols-5">
                            <div class="rounded-xl border border-purple-100 bg-purple-50/40 px-3 py-2.5">
                                <dt class="text-xs font-semibold uppercase tracking-widest text-gray-400">Questions</dt>
                                <dd class="text-2xl font-bold text-gray-900" x-text="quizQuestionCount()"></dd>
                            </div>
                            <div class="rounded-xl border border-purple-100 bg-purple-50/40 px-3 py-2.5">
                                <dt class="text-xs font-semibold uppercase tracking-widest text-gray-400">Total Points</dt>
                                <dd class="text-2xl font-bold text-gray-900" x-text="quizTotalPoints()"></dd>
                            </div>
                            <div class="rounded-xl border border-purple-100 bg-purple-50/40 px-3 py-2.5">
                                <dt class="text-xs font-semibold uppercase tracking-widest text-gray-400">Attempt Limit</dt>
                                <dd class="text-2xl font-bold text-gray-900" x-text="formatLimit(previewQuiz.attempt_limit)"></dd>
                            </div>
                            <div class="rounded-xl border border-purple-100 bg-purple-50/40 px-3 py-2.5">
                                <dt class="text-xs font-semibold uppercase tracking-widest text-gray-400">Quiz Timer</dt>
                                <dd class="text-2xl font-bold text-gray-900" x-text="formatLimit(previewQuiz.time_limit)"></dd>
                            </div>
                            <div class="rounded-xl border border-purple-100 bg-purple-50/40 px-3 py-2.5">
                                <dt class="text-xs font-semibold uppercase tracking-widest text-gray-400">Passing Score</dt>
                                <dd class="text-2xl font-bold text-gray-900" x-text="(previewQuiz.passing_score ?? '-') + '%'">-</dd>
                            </div>
                        </dl>

                        <div class="rounded-2xl border border-gray-100 bg-white">
                            <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
                                <div class="border-l-4 pl-3" style="border-color: #730DB1;">
                                    <h4 class="text-sm font-bold text-gray-900">Questions</h4>
                                    <p class="text-xs text-gray-400 mt-0.5" x-text="quizQuestionCount() + ' questions total'"></p>
                                </div>
                            </div>

                            <div class="divide-y divide-gray-50" x-show="Array.isArray(previewQuiz.questions) && previewQuiz.questions.length">
                            <template x-for="(question, questionIndex) in previewQuiz.questions" :key="'quiz-question-' + questionIndex">
                                <div class="px-5 py-4">
                                    <div class="flex items-start gap-3">
                                        <div class="flex h-7 w-7 flex-shrink-0 items-center justify-center rounded-full bg-gray-100">
                                            <span class="text-xs font-bold text-gray-500" x-text="questionIndex + 1"></span>
                                        </div>

                                        <div class="min-w-0 flex-1">
                                            <p class="text-sm font-medium text-gray-900" x-text="question?.attributes?.question_text || 'Untitled question'"></p>

                                            <div class="mt-2 flex flex-wrap items-center gap-1.5">
                                                <span class="inline-flex rounded-lg border px-2 py-0.5 text-xs font-medium"
                                                    :class="questionTypeClasses(question?.attributes?.question_type)"
                                                    x-text="questionTypeLabel(question?.attributes?.question_type)"></span>
                                                <span class="inline-flex rounded-lg border border-gray-200 bg-gray-50 px-2 py-0.5 text-xs font-medium text-gray-600"
                                                    x-text="String(question?.attributes?.points || 0) + ' ' + ((Number(question?.attributes?.points || 0) === 1) ? 'pt' : 'pts')"></span>
                                                <span x-show="Boolean(question?.attributes?.case_sensitive)"
                                                    class="inline-flex rounded-lg border border-red-100 bg-red-50 px-2 py-0.5 text-xs font-medium text-red-600">
                                                    Case Sensitive
                                                </span>
                                            </div>

                                            <template x-if="Array.isArray(question?.options) && question.options.length">
                                                <div class="mt-3 space-y-1">
                                                    <template x-for="(option, optionIndex) in question.options" :key="'quiz-option-' + questionIndex + '-' + optionIndex">
                                                        <div class="flex items-center gap-2 text-xs">
                                                            <span class="flex h-4 w-4 flex-shrink-0 items-center justify-center rounded-full"
                                                                :class="Boolean(option?.is_correct) ? 'bg-green-100' : 'bg-gray-100'">
                                                                <template x-if="Boolean(option?.is_correct)">
                                                                    <svg class="h-2.5 w-2.5 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                                                                    </svg>
                                                                </template>
                                                                <template x-if="!Boolean(option?.is_correct)">
                                                                    <span class="h-1 w-1 rounded-full bg-gray-400"></span>
                                                                </template>
                                                            </span>

                                                            <span :class="Boolean(option?.is_correct) ? 'font-semibold text-green-700' : 'text-gray-600'"
                                                                x-text="optionLabel(optionIndex) + '. ' + (option?.option_text || 'Option')"></span>
                                                        </div>
                                                    </template>
                                                </div>
                                            </template>

                                            <p x-show="Boolean(question?.attributes?.acceptable_answers)" class="mt-2 text-xs text-gray-500">
                                                <span class="font-medium text-gray-600">Acceptable answers: </span>
                                                <span x-text="String(question?.attributes?.acceptable_answers || '').replaceAll('|', ' / ')"></span>
                                            </p>

                                            <p x-show="Boolean(question?.attributes?.word_bank)" class="mt-2 text-xs text-gray-500">
                                                <span class="font-medium text-gray-600">Word bank: </span>
                                                <span x-text="Array.isArray(question?.attributes?.word_bank) ? question.attributes.word_bank.join(', ') : String(question?.attributes?.word_bank || '')"></span>
                                            </p>

                                            <img x-show="normalizeImageUrl(question?.attributes?.image_path)"
                                                :src="normalizeImageUrl(question?.attributes?.image_path)"
                                                alt="Question image"
                                                class="mt-2 h-16 w-auto rounded-xl border border-gray-100 object-cover">
                                        </div>
                                    </div>
                                </div>
                            </template>
                            </div>

                            <div x-show="!Array.isArray(previewQuiz.questions) || !previewQuiz.questions.length" class="px-5 py-8 text-center">
                                <p class="text-sm font-semibold text-gray-700">No questions available</p>
                                <p class="text-xs text-gray-400 mt-1">This quiz has no question data in the submitted revision.</p>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>
</div>
