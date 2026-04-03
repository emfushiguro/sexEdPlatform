<!-- Quiz Creation Wizard Modal -->
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

    <div class="bg-white rounded-lg shadow-xl max-w-3xl w-full max-h-[92vh] overflow-y-auto"
         @click.stop
         x-data="{
            currentStep: 1,
            stepErrors: {},
            title: '{{ old('title') }}',
            description: @js(old('description', '')),
            passingScore: {{ (int) old('passing_score', 75) }},
            attemptLimit: '{{ old('attempt_limit', '') }}',
            timeLimitMinutesTotal: {{ (int) old('time_limit_hours', 0) * 60 + (int) old('time_limit_minutes', 0) }},
            isActive: true,
            lessonTitle: @js($lesson->title),
            moduleTitle: @js($lesson->module->title ?? 'Module'),
            normalizeMinutes(value) {
                const parsed = Number(value);
                if (Number.isNaN(parsed) || parsed < 0) {
                    return 0;
                }

                return Math.floor(parsed);
            },
            get attachmentSummary() {
                return `${this.moduleTitle} > ${this.lessonTitle} (lesson)`;
            },
            get timerSummary() {
                const minutes = this.normalizeMinutes(this.timeLimitMinutesTotal);
                if (minutes <= 0) {
                    return 'No timer';
                }

                return `${minutes} minute${minutes === 1 ? '' : 's'}`;
            },
            resetStepErrors() {
                this.stepErrors = {};
            },
            validateStepOne() {
                const errors = {};

                if (!String(this.title || '').trim()) {
                    errors.title = 'Quiz title is required before continuing.';
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
        }">

        <!-- Modal Header -->
        <div class="flex items-center justify-between px-6 py-4 border-b sticky top-0 bg-white z-10">
            <div>
                <h3 class="text-xl font-semibold text-gray-900">Create Quiz for {{ $lesson->title }}</h3>
                <p class="text-xs text-gray-500 mt-1">Use the guided steps to configure this lesson quiz.</p>
            </div>
            <button @click="$store.modals.closeQuizModal()"
                    class="text-gray-400 hover:text-gray-600 transition">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <!-- Stepper -->
        <div class="px-6 pt-5">
            <div class="rounded-xl border border-gray-200 bg-gray-50/80 p-3 sm:p-4">
                <div class="grid grid-cols-3 gap-2">
                    <button type="button"
                            @click="goToStep(1)"
                            class="rounded-lg px-2 py-2 text-left transition"
                            :class="currentStep === 1 ? 'bg-white shadow-sm border border-purple-200' : 'bg-transparent'">
                        <p class="text-xs font-semibold tracking-wide uppercase"
                           :class="currentStep === 1 ? 'text-purple-700' : 'text-gray-500'">Step 1</p>
                        <p class="text-sm font-semibold"
                           :class="currentStep === 1 ? 'text-gray-900' : 'text-gray-700'">Basic Information</p>
                    </button>

                    <button type="button"
                            @click="goToStep(2)"
                            class="rounded-lg px-2 py-2 text-left transition"
                            :class="currentStep === 2 ? 'bg-white shadow-sm border border-purple-200' : 'bg-transparent'">
                        <p class="text-xs font-semibold tracking-wide uppercase"
                           :class="currentStep === 2 ? 'text-purple-700' : 'text-gray-500'">Step 2</p>
                        <p class="text-sm font-semibold"
                           :class="currentStep === 2 ? 'text-gray-900' : 'text-gray-700'">Configuration</p>
                    </button>

                    <button type="button"
                            @click="goToStep(3)"
                            class="rounded-lg px-2 py-2 text-left transition"
                            :class="currentStep === 3 ? 'bg-white shadow-sm border border-purple-200' : 'bg-transparent'">
                        <p class="text-xs font-semibold tracking-wide uppercase"
                           :class="currentStep === 3 ? 'text-purple-700' : 'text-gray-500'">Step 3</p>
                        <p class="text-sm font-semibold"
                           :class="currentStep === 3 ? 'text-gray-900' : 'text-gray-700'">Review and Finalize</p>
                    </button>
                </div>
            </div>
        </div>

        <!-- Modal Body -->
        <form method="POST" action="{{ route('instructor.quizzes.store') }}" class="p-6" @submit="submitWizard">
            @csrf

            <input type="hidden" name="lesson_id" value="{{ $lesson->id }}">
            <input type="hidden" name="time_limit_hours" :value="Math.floor((Number(timeLimitMinutesTotal) || 0) / 60)">
            <input type="hidden" name="time_limit_minutes" :value="(Number(timeLimitMinutesTotal) || 0) % 60">
            <input type="hidden" name="time_limit_seconds" value="0">
            <input type="hidden" name="is_active" :value="isActive ? 1 : 0">

            <!-- Step 1 -->
            <section x-show="currentStep === 1" x-cloak class="space-y-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Quiz Title *</label>
                    <input type="text"
                           name="title"
                           x-model="title"
                           required
                           placeholder="e.g., Lesson 1 Quiz: Understanding Topics"
                           class="block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500">
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
                              class="block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500"></textarea>
                    @error('description')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="rounded-xl border border-purple-200 bg-purple-50 p-4">
                    <h4 class="text-sm font-semibold text-purple-900 mb-1">Quiz Attachment</h4>
                    <p class="text-xs text-purple-700">This quiz will be attached to <strong>{{ $lesson->title }}</strong> in <strong>{{ $lesson->module->title ?? 'this module' }}</strong>.</p>
                </div>
            </section>

            <!-- Step 2 -->
            <section x-show="currentStep === 2" x-cloak class="space-y-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Passing Score (%) *</label>
                    <input type="number"
                           name="passing_score"
                           x-model.number="passingScore"
                           required
                           min="0"
                           max="100"
                           class="block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500">
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
                               class="block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500">
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
                               class="block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500">
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
                    </div>

                    <div class="rounded-xl border border-gray-200 p-4 bg-white">
                        <div class="flex items-center justify-between gap-4">
                            <div>
                                <p class="text-sm font-semibold text-gray-900">Active quiz</p>
                                <p class="text-xs text-gray-500 mt-0.5">Inactive quizzes are hidden from learners.</p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer flex-shrink-0">
                                <input type="checkbox" class="sr-only peer" x-model="isActive">
                                <div class="w-11 h-6 bg-gray-200 peer-focus:ring-2 peer-focus:ring-purple-400/50 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all"
                                     :style="isActive ? 'background: linear-gradient(135deg, #A30EB2, #3B0CB1);' : ''"></div>
                            </label>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Step 3 -->
            <section x-show="currentStep === 3" x-cloak class="space-y-4">
                <div class="rounded-xl border border-gray-200 p-4 bg-gray-50/70">
                    <h4 class="text-sm font-semibold text-gray-900 mb-3">Review Basic Information</h4>
                    <dl class="space-y-3">
                        <div>
                            <dt class="text-xs uppercase tracking-wide text-gray-500 font-semibold">Title</dt>
                            <dd class="text-sm text-gray-900 mt-1" x-text="title || 'Untitled quiz'"></dd>
                        </div>
                        <div>
                            <dt class="text-xs uppercase tracking-wide text-gray-500 font-semibold">Description</dt>
                            <dd class="text-sm text-gray-900 mt-1" x-text="description || 'No description provided'"></dd>
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

            <!-- Form Actions -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pt-6 mt-6 border-t">
                <div class="flex items-center gap-2">
                    <button type="button"
                            @click="$store.modals.closeQuizModal()"
                            class="px-5 py-2.5 text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg font-medium transition">
                        Cancel
                    </button>
                    <button type="button"
                            x-show="currentStep > 1"
                            x-cloak
                            @click="previousStep()"
                            class="px-5 py-2.5 text-gray-700 bg-white border border-gray-300 hover:bg-gray-50 rounded-lg font-medium transition">
                        Previous Step
                    </button>
                </div>

                <div class="flex items-center gap-2">
                    <button type="button"
                            x-show="currentStep < 3"
                            @click="nextStep()"
                            class="inline-flex items-center gap-2 px-5 py-2.5 text-sm font-semibold text-white rounded-xl hover:opacity-90 active:scale-[0.98] transition-all shadow-sm"
                            style="background: linear-gradient(135deg, #A30EB2, #730DB1, #3B0CB1);">
                        Next Step
                    </button>

                    <button type="submit"
                            x-show="currentStep === 3"
                            x-cloak
                            class="inline-flex items-center gap-2 px-5 py-2.5 text-sm font-semibold text-white rounded-xl hover:opacity-90 active:scale-[0.98] transition-all shadow-sm"
                            style="background: linear-gradient(135deg, #A30EB2, #730DB1, #3B0CB1);">
                        Create Quiz
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
