@extends('layouts.instructor-app')

@section('content')
@php
    $prefillLessonId = (string) old('lesson_id', $lessonId ?? '');
    $prefillModuleId = (string) old('module_id', '');

    if ($prefillModuleId === '' && $prefillLessonId !== '') {
        $prefillLesson = $modules->flatMap(function ($module) {
            return $module->lessons;
        })->firstWhere('id', (int) $prefillLessonId);

        if ($prefillLesson) {
            $prefillModuleId = (string) $prefillLesson->module_id;
        }
    }
@endphp

<div class="max-w-4xl mx-auto sm:px-6 lg:px-8"
     x-data="{
        currentStep: 1,
        stepErrors: {},
        title: @js(old('title', '')),
        description: @js(old('description', '')),
        selectedModule: @js($prefillModuleId),
        selectedLesson: @js($prefillLessonId),
        passingScore: {{ (int) old('passing_score', 70) }},
        attemptLimit: @js(old('attempt_limit', '')),
        timeLimitMinutesTotal: {{ (int) old('time_limit_hours', 0) * 60 + (int) old('time_limit_minutes', 0) }},
        isActive: {{ old('is_active', 1) ? 'true' : 'false' }},
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

    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-gray-100">
        <div class="px-6 py-5 border-b bg-white">
            <h1 class="text-xl font-bold text-gray-900">Create Quiz</h1>
            <p class="text-sm text-gray-500 mt-1">Complete each step to configure your quiz with less friction.</p>
        </div>

        <div class="p-6">
            <div class="rounded-xl border border-gray-200 bg-gray-50/80 p-3 sm:p-4 mb-6">
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

            <form method="POST" action="{{ route('instructor.quizzes.store') }}" @submit="submitWizard">
                @csrf

                <input type="hidden" name="module_id" :value="moduleIdForSubmit">
                <input type="hidden" name="time_limit_hours" :value="Math.floor((Number(timeLimitMinutesTotal) || 0) / 60)">
                <input type="hidden" name="time_limit_minutes" :value="(Number(timeLimitMinutesTotal) || 0) % 60">
                <input type="hidden" name="time_limit_seconds" value="0">
                <input type="hidden" name="is_active" :value="isActive ? 1 : 0">

                <section x-show="currentStep === 1" x-cloak class="space-y-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Quiz Title *</label>
                        <input type="text"
                               name="title"
                               x-model="title"
                               required
                               class="block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500"
                               placeholder="e.g., Module 1 Quiz: Growing Up">
                        @error('title')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        <p x-show="stepErrors.title" x-text="stepErrors.title" class="mt-1 text-sm text-red-600"></p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Quiz Description</label>
                        <textarea name="description"
                                  x-model="description"
                                  rows="4"
                                  class="block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500"
                                  placeholder="Optional description for the quiz..."></textarea>
                        @error('description')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="rounded-xl border border-purple-200 bg-purple-50 p-4">
                        <h3 class="text-sm font-semibold text-purple-900 mb-1">Quiz Attachment</h3>
                        <p class="text-xs text-purple-700 mb-4">Attach to a module or to a specific lesson.</p>

                        <div class="mb-3">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Module</label>
                            <select x-model="selectedModule"
                                    @change="onModuleChange()"
                                    class="block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500">
                                <option value="">- Select a Module -</option>
                                @foreach($modules as $module)
                                <option value="{{ $module->id }}">{{ $module->title }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div x-show="selectedModule !== ''" x-cloak>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Specific Lesson (optional)</label>
                            <select name="lesson_id"
                                    x-model="selectedLesson"
                                    class="block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500">
                                <option value="">None - attach to module level</option>
                                <template x-for="lesson in filteredLessons" :key="lesson.id">
                                    <option :value="lesson.id" x-text="lesson.title"></option>
                                </template>
                            </select>
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

                <section x-show="currentStep === 2" x-cloak class="space-y-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Passing Score (%) *</label>
                        <input type="number"
                               name="passing_score"
                               x-model.number="passingScore"
                               min="0"
                               max="100"
                               required
                               class="block w-full rounded-md border-gray-300 shadow-sm focus:border-purple-500 focus:ring-purple-500">
                        @error('passing_score')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        <p x-show="stepErrors.passingScore" x-text="stepErrors.passingScore" class="mt-1 text-sm text-red-600"></p>
                    </div>

                    <div class="rounded-xl border border-gray-200 p-4 bg-gray-50/70 space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Quiz Attempt Limit</label>
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
                                    <p class="text-sm font-semibold text-gray-900">Active Quiz Toggle</p>
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

                <section x-show="currentStep === 3" x-cloak class="space-y-4">
                    <div class="rounded-xl border border-gray-200 p-4 bg-gray-50/70">
                        <h3 class="text-sm font-semibold text-gray-900 mb-3">Review Quiz Information</h3>
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
                        <h3 class="text-sm font-semibold text-gray-900 mb-3">Review Configuration</h3>
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

                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pt-6 mt-6 border-t">
                    <div class="flex items-center gap-2">
                        @if($lessonId)
                            @php
                                $lesson = \App\Models\Lesson::find($lessonId);
                            @endphp
                            <a href="{{ $lesson ? route('instructor.lessons.show', $lesson) : route('instructor.quizzes.index') }}"
                               class="px-5 py-2.5 text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg font-medium transition">Cancel</a>
                        @else
                            <a href="{{ route('instructor.quizzes.index') }}"
                               class="px-5 py-2.5 text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg font-medium transition">Cancel</a>
                        @endif

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
    </div>
</div>
@endsection
