<!-- Quiz Creation/Edit Wizard Modal -->
<!-- Modal Backdrop -->
<div x-show="$store.modals.quizModal"
     x-cloak
     class="fixed inset-0 bg-gray-900 bg-opacity-50 z-40 transition-opacity"
     @click="$store.modals.closeQuizModal()"
     @keydown.escape.window="$store.modals.closeQuizModal()"></div>

<!-- Modal Dialog -->
<div x-show="$store.modals.quizModal"
     x-cloak
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
     x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
     x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
     class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4"
     @keydown.escape.window="$store.modals.closeQuizModal()">

    <div class="relative transform overflow-hidden rounded-2xl bg-white shadow-2xl transition-all w-full max-w-3xl max-h-[90vh] flex flex-col"
         @click.stop
         x-data="{
            mode: 'create',
            editQuizId: null,
            currentStep: 1,
            stepErrors: {},
            title: '',
            description: '',
            passingScore: 70,
            attemptLimit: '',
            timeLimitMinutesTotal: 0,
            isActive: true,
            selectedModule: '',
            selectedLesson: '',
            actionBase: '{{ url($contentRoutePrefix . '/quizzes') }}',
            moduleOptions: {{ json_encode($modules->map(function ($module) {
                return [
                    'id' => $module->id,
                    'title' => $module->title,
                ];
            })->values()) }},
            allLessons: {{ json_encode($modules->flatMap(function ($module) {
                return $module->lessons->map(function ($lesson) use ($module) {
                    return [
                        'id' => $lesson->id,
                        'title' => $lesson->title,
                        'module_id' => $module->id,
                        'module_title' => $module->title,
                    ];
                });
            })->values()) }},
            get filteredLessons() {
                if (!this.selectedModule) {
                    return [];
                }

                return this.allLessons.filter((lesson) => String(lesson.module_id) === String(this.selectedModule));
            },
            get isEdit() {
                return this.mode === 'edit' && this.editQuizId !== null;
            },
            get formAction() {
                return this.isEdit ? `${this.actionBase}/${this.editQuizId}` : '{{ route($contentRoutePrefix . '.quizzes.store') }}';
            },
            get moduleIdForSubmit() {
                return this.selectedLesson ? '' : this.selectedModule;
            },
            get attachmentSummary() {
                if (this.selectedLesson) {
                    const lesson = this.allLessons.find((item) => String(item.id) === String(this.selectedLesson));
                    if (lesson) {
                        return `${lesson.module_title} > ${lesson.title} (lesson)`;
                    }

                    return 'Lesson selected';
                }

                if (this.selectedModule) {
                    const module = this.moduleOptions.find((item) => String(item.id) === String(this.selectedModule));
                    if (module) {
                        return `${module.title} (module)`;
                    }
                }

                return 'Not selected';
            },
            get timerSummary() {
                const minutes = this.normalizeMinutes(this.timeLimitMinutesTotal);
                if (minutes <= 0) {
                    return 'No timer';
                }

                return `${minutes} minute${minutes === 1 ? '' : 's'}`;
            },
            normalizeMinutes(value) {
                const parsed = Number(value);
                if (Number.isNaN(parsed) || parsed < 0) {
                    return 0;
                }

                return Math.floor(parsed);
            },
            resetStepErrors() {
                this.stepErrors = {};
            },
            validateStepOne() {
                const errors = {};

                if (!String(this.title || '').trim()) {
                    errors.title = 'Quiz title is required before continuing.';
                }

                if (!this.selectedModule && !this.selectedLesson) {
                    errors.attachment = 'Select a module or a lesson to attach this quiz.';
                }

                this.stepErrors = errors;
                return Object.keys(errors).length === 0;
            },
            validateStepTwo() {
                const errors = {};
                const passing = Number(this.passingScore);

                if (Number.isNaN(passing) || passing < 0 || passing > 100) {
                    errors.passingScore = 'Passing score must be between 0 and 100.';
                }

                if (this.attemptLimit !== '') {
                    const parsedAttempt = Number(this.attemptLimit);
                    if (Number.isNaN(parsedAttempt) || parsedAttempt < 1) {
                        errors.attemptLimit = 'Attempt limit must be at least 1 or left blank.';
                    }
                }

                if (Number(this.timeLimitMinutesTotal) < 0) {
                    errors.timeLimit = 'Timer cannot be a negative value.';
                }

                this.stepErrors = errors;
                return Object.keys(errors).length === 0;
            },
            goToStep(step) {
                if (step < this.currentStep) {
                    this.currentStep = step;
                    this.resetStepErrors();
                    return;
                }

                if (step === 2 && this.validateStepOne()) {
                    this.currentStep = 2;
                    return;
                }

                if (step === 3 && this.validateStepOne() && this.validateStepTwo()) {
                    this.currentStep = 3;
                }
            },
            nextStep() {
                if (this.currentStep === 1 && this.validateStepOne()) {
                    this.currentStep = 2;
                    return;
                }

                if (this.currentStep === 2 && this.validateStepTwo()) {
                    this.currentStep = 3;
                }
            },
            previousStep() {
                if (this.currentStep > 1) {
                    this.currentStep -= 1;
                    this.resetStepErrors();
                }
            },
            onModuleChange() {
                if (!this.selectedLesson) {
                    return;
                }

                const lesson = this.allLessons.find((item) => String(item.id) === String(this.selectedLesson));
                if (!lesson || String(lesson.module_id) !== String(this.selectedModule)) {
                    this.selectedLesson = '';
                }
            },
            syncFromStore() {
                const draft = $store.modals.quizModalDraft;
                this.currentStep = 1;
                this.resetStepErrors();

                if (!draft) {
                    this.mode = 'create';
                    this.editQuizId = null;
                    this.title = '';
                    this.description = '';
                    this.passingScore = 70;
                    this.attemptLimit = '';
                    this.timeLimitMinutesTotal = 0;
                    this.isActive = true;
                    this.selectedModule = '';
                    this.selectedLesson = '';
                    return;
                }

                this.mode = 'edit';
                this.editQuizId = draft.id;
                this.title = draft.title || '';
                this.description = draft.description || '';
                this.passingScore = draft.passing_score ?? 70;
                this.attemptLimit = draft.attempt_limit ?? '';
                this.isActive = !!draft.is_active;
                this.selectedModule = draft.module_id ? String(draft.module_id) : '';
                this.selectedLesson = draft.lesson_id ? String(draft.lesson_id) : '';

                if (!this.selectedModule && this.selectedLesson) {
                    const lesson = this.allLessons.find((item) => String(item.id) === String(this.selectedLesson));
                    if (lesson) {
                        this.selectedModule = String(lesson.module_id);
                    }
                }

                const seconds = Number(draft.time_limit ?? 0) || 0;
                if (seconds > 0) {
                    this.timeLimitMinutesTotal = Math.floor(seconds / 60);
                } else {
                    const hours = Number(draft.time_limit_hours) || 0;
                    const minutes = Number(draft.time_limit_minutes) || 0;
                    this.timeLimitMinutesTotal = (hours * 60) + minutes;
                }
            },
            submitWizard(event) {
                const stepOneValid = this.validateStepOne();
                const stepTwoValid = this.validateStepTwo();

                if (!stepOneValid) {
                    this.currentStep = 1;
                    event.preventDefault();
                    return;
                }

                if (!stepTwoValid) {
                    this.currentStep = 2;
                    event.preventDefault();
                }
            },
            init() {
                this.syncFromStore();
                this.$watch('$store.modals.quizModalDraft', () => { this.syncFromStore(); });
                this.$watch('$store.modals.quizModal', (open) => {
                    if (open) {
                        this.syncFromStore();
                    }
                });
            },
        }">

        <!-- Modal Header with Stepper -->
        <div class="px-6 pt-6 pb-4 border-b border-gray-100 bg-gray-50/50 flex-shrink-0">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h3 class="text-xl font-bold text-gray-900" x-text="isEdit ? 'Edit Quiz' : 'Create New Quiz'"></h3>
                    <p class="text-sm text-gray-500 mt-1">Follow the guided steps to configure this assessment.</p>
                </div>
                <button @click="$store.modals.closeQuizModal()"
                        class="rounded-full p-2 text-gray-400 hover:bg-gray-100 hover:text-gray-500 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <div class="max-w-xl mx-auto">
                <div class="flex items-center justify-between relative">
                    <template x-for="s in [1,2,3]" :key="s">
                        <button type="button" @click="goToStep(s)" class="flex flex-col items-center relative z-10 w-24">
                            <div class="w-10 h-10 rounded-full flex items-center justify-center transition-all duration-300 shadow-sm"
                                 :class="{
                                     'bg-gradient-to-br from-purple-600 to-indigo-700 text-white ring-4 ring-purple-100': currentStep === s,
                                     'bg-purple-600 text-white': currentStep > s,
                                     'bg-white border-2 border-gray-200 text-gray-400': currentStep < s
                                 }">
                                <template x-if="currentStep > s">
                                    <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                    </svg>
                                </template>
                                <template x-if="currentStep <= s">
                                    <span class="text-sm font-bold" x-text="s"></span>
                                </template>
                            </div>
                            <span class="mt-2 text-xs font-semibold uppercase tracking-wider text-center"
                                  :class="currentStep >= s ? 'text-purple-700' : 'text-gray-400'"
                                  x-text="s === 1 ? 'Basics' : (s === 2 ? 'Rules' : 'Review')"></span>
                        </button>
                    </template>

                    <div class="absolute top-5 left-12 right-12 h-0.5 bg-gray-200 -z-10">
                        <div class="h-full bg-purple-600 transition-all duration-500 ease-in-out"
                             :style="'width: ' + ((currentStep - 1) / 2 * 100) + '%'">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Body -->
        <form method="POST" :action="formAction" class="flex flex-col h-full overflow-hidden" @submit="submitWizard">
            @csrf
            <template x-if="isEdit">
                <input type="hidden" name="_method" value="PUT">
            </template>

            <input type="hidden" name="module_id" :value="moduleIdForSubmit">
            <input type="hidden" name="time_limit_hours" :value="Math.floor((Number(timeLimitMinutesTotal) || 0) / 60)">
            <input type="hidden" name="time_limit_minutes" :value="(Number(timeLimitMinutesTotal) || 0) % 60">
            <input type="hidden" name="time_limit_seconds" value="0">

            <div class="p-6 overflow-y-auto flex-1 bg-white">
            <!-- Step 1 -->
            <section x-show="currentStep === 1" x-cloak x-transition.opacity class="space-y-6 max-w-2xl mx-auto">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Quiz Title *</label>
                    <input type="text"
                           name="title"
                           x-model="title"
                           required
                           placeholder="e.g., Module 1 Quiz: Growing Up"
                           class="block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-400 focus:ring-purple-300">
                    @error('title')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <p x-show="stepErrors.title" x-text="stepErrors.title" class="mt-1 text-sm text-red-600"></p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                    <textarea name="description"
                              x-model="description"
                              rows="4"
                              placeholder="Optional description for the quiz..."
                              class="block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-400 focus:ring-purple-300"></textarea>
                </div>

                <div class="rounded-xl border border-purple-200 bg-purple-50 p-4">
                    <h4 class="text-sm font-semibold text-purple-900 mb-1">Quiz Attachment</h4>
                    <p class="text-xs text-purple-700 mb-4">Attach this quiz to a module or to a specific lesson.</p>

                    <div class="mb-3">
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Module
                            <span class="text-xs font-normal text-gray-500">(required for module-level quizzes)</span>
                        </label>
                        <select x-model="selectedModule"
                                @change="onModuleChange()"
                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-400 focus:ring-purple-300">
                            <option value="">- Select a Module -</option>
                            @foreach($modules as $module)
                            <option value="{{ $module->id }}">{{ $module->title }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div x-show="selectedModule !== ''" x-cloak>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Specific Lesson
                            <span class="text-xs font-normal text-gray-500">(optional)</span>
                        </label>
                        <select name="lesson_id"
                                x-model="selectedLesson"
                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-400 focus:ring-purple-300">
                            <option value="">None - attach to module level</option>
                            <template x-for="lesson in filteredLessons" :key="lesson.id">
                                <option :value="lesson.id" x-text="lesson.title"></option>
                            </template>
                        </select>
                        <p class="mt-1.5 text-xs text-purple-700" x-show="selectedLesson !== ''" x-cloak>
                            Quiz will appear after completing the selected lesson.
                        </p>
                        <p class="mt-1.5 text-xs text-purple-700" x-show="selectedLesson === ''" x-cloak>
                            Quiz will appear after completing the full module.
                        </p>
                    </div>

                    @error('module_id')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    @error('lesson_id')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <p x-show="stepErrors.attachment" x-text="stepErrors.attachment" class="mt-2 text-sm text-red-600"></p>
                </div>
            </section>

            <!-- Step 2 -->
            <section x-show="currentStep === 2" x-cloak x-transition.opacity class="space-y-6 max-w-2xl mx-auto">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Passing Score (%) *</label>
                    <input type="number"
                           name="passing_score"
                           x-model.number="passingScore"
                           required
                           min="0"
                           max="100"
                           class="block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-400 focus:ring-purple-300">
                    @error('passing_score')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <p x-show="stepErrors.passingScore" x-text="stepErrors.passingScore" class="mt-1 text-sm text-red-600"></p>
                </div>

                <div class="rounded-xl border border-gray-200 p-4 bg-gray-50/70 space-y-4">
                    <div>
                        <p class="text-sm font-semibold text-gray-900">Quiz Rules</p>
                        <p class="text-xs text-gray-500 mt-0.5">Set attempt limits, timer, and active status.</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Attempt Limit</label>
                        <input type="number"
                               name="attempt_limit"
                               x-model="attemptLimit"
                               min="1"
                               placeholder="Leave blank for unlimited attempts"
                               class="block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-400 focus:ring-purple-300">
                        <p class="mt-1 text-xs text-gray-500">Leave empty to allow unlimited attempts.</p>
                        @error('attempt_limit')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        <p x-show="stepErrors.attemptLimit" x-text="stepErrors.attemptLimit" class="mt-2 text-sm text-red-600"></p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Quiz Timer (minutes)</label>
                        <input type="number"
                               x-model.number="timeLimitMinutesTotal"
                               min="0"
                               placeholder="Leave empty or 0 for no timer"
                               class="block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-400 focus:ring-purple-300">
                        <p class="mt-1 text-xs text-gray-500">Set how many minutes learners have to finish this quiz.</p>
                        @error('time_limit_hours')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        @error('time_limit_minutes')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        @error('time_limit_seconds')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        <p x-show="stepErrors.timeLimit" x-text="stepErrors.timeLimit" class="mt-2 text-sm text-red-600"></p>
                    </div>

                    <div class="rounded-xl border border-gray-200 p-4 bg-white">
                        <div class="flex items-center justify-between gap-4">
                            <div>
                                <p class="text-sm font-semibold text-gray-900">Active quiz</p>
                                <p class="text-xs text-gray-500 mt-0.5">Inactive quizzes are hidden from learners.</p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer flex-shrink-0">
                                <input type="hidden" name="is_active" :value="isActive ? 1 : 0">
                                <input type="checkbox" class="sr-only peer" x-model="isActive">
                                <div class="w-11 h-6 bg-gray-200 peer-focus:ring-2 peer-focus:ring-purple-400/50 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all"
                                     :style="isActive ? 'background: linear-gradient(135deg, #A30EB2, #3B0CB1);' : ''"></div>
                            </label>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Step 3 -->
            <section x-show="currentStep === 3" x-cloak x-transition.opacity class="space-y-4 max-w-2xl mx-auto">
                <div class="rounded-xl border border-gray-200 p-4 bg-gray-50/70">
                    <h4 class="text-sm font-semibold text-gray-900 mb-3">Review Basic Information</h4>
                    <dl class="space-y-3">
                        <div>
                            <dt class="text-xs uppercase tracking-wide text-gray-500 font-semibold">Title</dt>
                            <dd class="text-sm text-gray-900 mt-1" x-text="title || 'Untitled quiz'"></dd>
                        </div>
                        <div>
                            <dt class="text-xs uppercase tracking-wide text-gray-500 font-semibold">Description</dt>
                            <dd class="text-sm text-gray-900 mt-1" x-text="description || 'No description provided'">No description provided</dd>
                        </div>
                        <div>
                            <dt class="text-xs uppercase tracking-wide text-gray-500 font-semibold">Attachment</dt>
                            <dd class="text-sm text-gray-900 mt-1" x-text="attachmentSummary"></dd>
                        </div>
                    </dl>
                </div>

                <div class="rounded-xl border border-gray-200 p-4 bg-gray-50/70">
                    <h4 class="text-sm font-semibold text-gray-900 mb-3">Review Configuration</h4>
                    <dl class="space-y-3">
                        <div>
                            <dt class="text-xs uppercase tracking-wide text-gray-500 font-semibold">Passing Score</dt>
                            <dd class="text-sm text-gray-900 mt-1"><span x-text="passingScore"></span>%</dd>
                        </div>
                        <div>
                            <dt class="text-xs uppercase tracking-wide text-gray-500 font-semibold">Attempt Limit</dt>
                            <dd class="text-sm text-gray-900 mt-1" x-text="attemptLimit === '' ? 'Unlimited attempts' : `${attemptLimit} attempt(s)`"></dd>
                        </div>
                        <div>
                            <dt class="text-xs uppercase tracking-wide text-gray-500 font-semibold">Timer</dt>
                            <dd class="text-sm text-gray-900 mt-1" x-text="timerSummary"></dd>
                        </div>
                        <div>
                            <dt class="text-xs uppercase tracking-wide text-gray-500 font-semibold">Quiz Status</dt>
                            <dd class="text-sm text-gray-900 mt-1" x-text="isActive ? 'Active' : 'Inactive'"></dd>
                        </div>
                    </dl>
                </div>
            </section>
            </div>

            <!-- Form Actions -->
            <div class="p-6 border-t border-gray-100 bg-gray-50 flex items-center justify-between flex-shrink-0 rounded-b-2xl">
                <button type="button"
                        x-show="currentStep > 1"
                        x-cloak
                        @click="previousStep()"
                        class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg border border-gray-300 bg-white text-sm font-semibold text-gray-700 hover:bg-gray-50 hover:text-gray-900 transition-all shadow-sm">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                    Back
                </button>
                <div x-show="currentStep === 1" class="w-20"></div>

                <div class="flex items-center gap-3">
                    <button type="button"
                            @click="$store.modals.closeQuizModal()"
                            class="text-sm font-semibold text-gray-500 hover:text-gray-800 transition-colors px-4">
                        Cancel
                    </button>

                    <button type="button"
                            x-show="currentStep < 3"
                            @click="nextStep()"
                            class="inline-flex items-center gap-2 px-6 py-2.5 rounded-lg bg-gradient-to-r from-purple-600 to-indigo-700 text-sm font-semibold text-white hover:shadow-lg hover:shadow-purple-500/30 transition-all">
                        Continue
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </button>

                    <button type="submit"
                            x-show="currentStep === 3"
                            x-cloak
                            class="inline-flex items-center gap-2 px-6 py-2.5 rounded-lg bg-gradient-to-r from-purple-600 to-indigo-700 text-sm font-semibold text-white hover:shadow-lg hover:shadow-purple-500/30 transition-all">
                        <span x-text="isEdit ? 'Save Quiz Changes' : 'Create Quiz'"></span>
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                    </button>
                </div>
            </div>
        </form>
    </div>

<style>
    [x-cloak] {
        display: none !important;
    }
</style>
