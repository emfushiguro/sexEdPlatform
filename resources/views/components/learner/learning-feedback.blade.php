@props(['initialFeedback' => null])

@php
    $bootstrapFeedback = is_array($initialFeedback) ? $initialFeedback : null;
    $certificateModuleId = null;
    if (($bootstrapFeedback['type'] ?? null) === 'module_completed'
        && preg_match('/^module:([1-9][0-9]*)$/', $bootstrapFeedback['sourceId'] ?? '', $moduleMatch)) {
        $certificateModuleId = (int) $moduleMatch[1];
    }
@endphp

<div
    data-learning-feedback-host
    x-data="{ announcement: '', hovered: false, imageFailed: false, certificateSubmitting: false }"
    x-init="
        $watch('$store.learningFeedback.active', () => {
            imageFailed = false;
            $nextTick(() => {
                $store.learningFeedback.opened($refs.panel, $refs.heading);
                if (hovered || $refs.panel.contains(document.activeElement)) $store.learningFeedback.pauseTimer();
            });
        });
        $watch('[$store.learningFeedback.active?.id, $store.learningFeedback.announcement, $store.learningFeedback.announcementRevision]', () => {
            announcement = '';
            $nextTick(() => { announcement = $store.learningFeedback.announcement; });
        });
        @if($bootstrapFeedback !== null)
            $store.learningFeedback.show({{ Illuminate\Support\Js::from($bootstrapFeedback) }});
        @endif
    "
    @learning-feedback:show.window="$store.learningFeedback.show($event.detail)"
    @keydown.window="$store.learningFeedback.handleKeydown($event)"
    @if($certificateModuleId)
        @learning-feedback:action.window="if ($event.detail.intent === 'continue' && $event.detail.sourceId === 'module:{{ $certificateModuleId }}' && $event.detail.feedbackId === {{ Illuminate\Support\Js::from($bootstrapFeedback['id']) }}) { $event.preventDefault(); if (!certificateSubmitting) { certificateSubmitting = true; $refs.certificateForm.requestSubmit(); } }"
    @endif
>
    @if($certificateModuleId)
        <form x-ref="certificateForm" method="POST" action="{{ route('learner.certificates.check', $certificateModuleId) }}" hidden>
            @csrf
        </form>
    @endif
    <p class="sr-only" :aria-live="['incorrect', 'warning'].includes($store.learningFeedback.active?.type) ? 'assertive' : 'polite'" aria-atomic="true" x-text="announcement"></p>

    <div
        x-cloak
        x-show="$store.learningFeedback.open && $store.learningFeedback.active?.presentation === 'completion_modal'"
        class="fixed inset-0 z-[10001] bg-black/60"
        aria-hidden="true"
        @click="if (!$store.learningFeedback.acting && $store.learningFeedback.active?.actions.some(action => action.intent === 'dismiss')) $store.learningFeedback.act($store.learningFeedback.active.actions.find(action => action.intent === 'dismiss').id)"
    ></div>

    <div
        x-cloak
        x-show="$store.learningFeedback.open"
        class="pointer-events-none fixed inset-0 z-[10002] flex items-center justify-center p-4 pt-[max(1rem,env(safe-area-inset-top))] pb-[max(1rem,env(safe-area-inset-bottom))]"
    >
        <section
            x-ref="panel"
            x-show="$store.learningFeedback.open"
            x-transition:enter="motion-safe:transition motion-safe:duration-200 motion-safe:ease-out motion-reduce:transition-none motion-reduce:transform-none"
            x-transition:enter-start="opacity-0 motion-safe:translate-y-2"
            x-transition:enter-end="opacity-100 motion-safe:translate-y-0"
            x-transition:leave="motion-safe:transition motion-safe:duration-150 motion-reduce:transition-none motion-reduce:transform-none"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            :role="$store.learningFeedback.active?.presentation === 'completion_modal' ? 'dialog' : null"
            :aria-modal="$store.learningFeedback.active?.presentation === 'completion_modal' ? 'true' : null"
            :aria-labelledby="$store.learningFeedback.active?.presentation === 'completion_modal' ? 'learning-feedback-title' : null"
            :aria-describedby="$store.learningFeedback.active?.presentation === 'completion_modal' ? 'learning-feedback-message learning-feedback-explanation' : null"
            :aria-busy="$store.learningFeedback.acting.toString()"
            tabindex="-1"
            @mouseenter="hovered = true; $store.learningFeedback.pauseTimer()"
            @mouseleave="hovered = false; if (!$el.contains(document.activeElement)) $store.learningFeedback.resumeTimer()"
            @focusin="$store.learningFeedback.pauseTimer()"
            @focusout="$nextTick(() => { if (!hovered && !$el.contains(document.activeElement)) $store.learningFeedback.resumeTimer(); })"
            class="pointer-events-auto relative max-h-[calc(100dvh-2rem-env(safe-area-inset-top)-env(safe-area-inset-bottom))] w-full max-w-md overflow-y-auto overscroll-contain rounded-2xl border-2 border-brand-700 bg-white p-4 text-gray-900 shadow-xl focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-700 dark:border-brand-300 dark:bg-gray-900 dark:text-gray-100 dark:focus-visible:outline-brand-300 sm:p-6 forced-colors:border-[CanvasText] forced-colors:bg-[Canvas] forced-colors:text-[CanvasText]"
        >
            <div class="flex items-start justify-between gap-3">
                <p class="flex min-h-11 items-center gap-2 text-sm font-semibold text-brand-800 dark:text-brand-200 forced-colors:text-[CanvasText]">
                    <span x-show="$store.learningFeedback.active?.type === 'correct'"><span aria-hidden="true">✓</span> Correct</span>
                    <span x-show="$store.learningFeedback.active?.type === 'incorrect'"><span aria-hidden="true">✕</span> Incorrect</span>
                    <span x-show="$store.learningFeedback.active?.type === 'try_again'"><span aria-hidden="true">↻</span> Try again</span>
                    <span x-show="$store.learningFeedback.active?.type === 'activity_completed'"><span aria-hidden="true">✓</span> Activity complete</span>
                    <span x-show="$store.learningFeedback.active?.type === 'lesson_completed'"><span aria-hidden="true">✓</span> Lesson complete</span>
                    <span x-show="$store.learningFeedback.active?.type === 'module_completed'"><span aria-hidden="true">✓</span> Module complete</span>
                    <span x-show="$store.learningFeedback.active?.type === 'informational'"><span aria-hidden="true">ⓘ</span> Information</span>
                    <span x-show="$store.learningFeedback.active?.type === 'warning'"><span aria-hidden="true">!</span> Warning</span>
                </p>
                <button
                    type="button"
                    x-show="$store.learningFeedback.active?.type === 'module_completed' || $store.learningFeedback.active?.actions.some(action => action.intent === 'dismiss')"
                    :disabled="$store.learningFeedback.acting"
                    @click="$store.learningFeedback.dismiss()"
                    aria-label="Dismiss learning feedback"
                    class="inline-flex min-h-11 min-w-11 shrink-0 items-center justify-center rounded-lg border border-gray-500 text-xl hover:bg-gray-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-700 focus-visible:ring-offset-2 disabled:cursor-wait dark:border-gray-400 dark:hover:bg-gray-800 dark:focus-visible:ring-brand-300 dark:focus-visible:ring-offset-gray-900 forced-colors:border-[ButtonText] forced-colors:focus-visible:outline forced-colors:focus-visible:outline-2 forced-colors:focus-visible:outline-[Highlight]"
                ><span aria-hidden="true">×</span></button>
            </div>

            <img
                x-show="!imageFailed"
                :src="$store.learningFeedback.active?.illustration"
                x-on:error="imageFailed = true"
                alt=""
                class="mx-auto mb-4 h-24 max-h-[20vh] w-full object-contain sm:h-32"
            >
            <h2 id="learning-feedback-title" x-ref="heading" tabindex="-1" class="rounded text-xl font-bold focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-700 dark:focus-visible:outline-brand-300 forced-colors:focus-visible:outline-[Highlight]" x-text="$store.learningFeedback.active?.title"></h2>
            <p id="learning-feedback-message" class="mt-2 whitespace-pre-line break-words text-sm leading-relaxed" x-text="$store.learningFeedback.active?.message"></p>
            <p id="learning-feedback-explanation" x-show="$store.learningFeedback.active?.explanation" class="mt-3 whitespace-pre-line break-words text-sm leading-relaxed" x-text="$store.learningFeedback.active?.explanation"></p>

            <div x-show="$store.learningFeedback.active?.actions.length" class="mt-5 flex flex-col gap-3 sm:flex-row sm:flex-wrap">
                <template x-for="action in ($store.learningFeedback.active?.actions ?? [])" :key="action.id">
                    <button
                        type="button"
                        :disabled="$store.learningFeedback.acting"
                        @click="$store.learningFeedback.act(action.id)"
                        class="min-h-11 min-w-11 flex-1 rounded-lg border-2 border-brand-700 bg-brand-700 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-800 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-700 focus-visible:ring-offset-2 disabled:cursor-wait dark:border-brand-200 dark:bg-brand-200 dark:text-brand-950 dark:hover:bg-brand-100 dark:focus-visible:ring-brand-300 dark:focus-visible:ring-offset-gray-900 forced-colors:border-[ButtonText] forced-colors:bg-[ButtonFace] forced-colors:text-[ButtonText] forced-colors:focus-visible:outline forced-colors:focus-visible:outline-2 forced-colors:focus-visible:outline-[Highlight]"
                        x-text="$store.learningFeedback.acting ? 'Working…' : action.label"
                    ></button>
                </template>
            </div>
        </section>
    </div>
</div>

@if($bootstrapFeedback !== null && in_array($bootstrapFeedback['type'] ?? null, ['lesson_completed', 'module_completed'], true))
    <noscript>
        <section class="m-4 rounded-lg border-2 border-brand-700 bg-white p-4 text-gray-900 dark:border-brand-300 dark:bg-gray-900 dark:text-gray-100">
            <h2>{{ is_string($bootstrapFeedback['title'] ?? null) ? $bootstrapFeedback['title'] : 'Learning complete' }}</h2>
            <p>{{ is_string($bootstrapFeedback['message'] ?? null) ? $bootstrapFeedback['message'] : 'You completed this learning step.' }}</p>
        </section>
    </noscript>
@endif
