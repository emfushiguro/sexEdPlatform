@props(['activity', 'continueUrl' => null, 'inside' => false, 'preview' => false])

@php($activityToken = 'activity:'.($activity['id'] ?? 'unknown'))

<section data-optional-interaction="{{ $activityToken }}" data-preview="{{ $preview ? 'true' : 'false' }}" class="interactive-activity-container relative rounded-2xl border border-purple-100 bg-white p-5 shadow-sm" x-data="interactiveActivity(@js([
    'activityId' => $activity['id'] ?? null,
    'revision' => $activity['revision'] ?? 1,
    'initialStatus' => $activity['status'] ?? 'in_progress',
    'initialExplanation' => $activity['explanation'] ?? null,
    'previewToken' => $activity['preview_token'] ?? null,
    'previewEvaluateUrl' => $activity['preview_evaluate_url'] ?? null,
    'continueUrl' => $continueUrl,
    'skipUrl' => $activity['skip_url'] ?? null,
    'resumeUrl' => $activity['resume_url'] ?? null,
    'practiceUrl' => $activity['practice_url'] ?? null,
    'preview' => $preview,
    'csrf' => csrf_token(),
]))" x-init="$dispatch('optional-interaction-active', { token: @js($activityToken), initial: true })" @focusin="$dispatch('optional-interaction-active', { token: @js($activityToken) })" @keydown.escape.window="if (helpOpen) closeHelp()" @interactive-activity-result.window="if ($event.detail.activityId === activityId) handleActivityResult($event.detail)" @interactive-activity-error.window="if ($event.detail.activityId === activityId) handleActivityError($event.detail)" @interactive-activity-recovered.window="if ($event.detail.activityId === activityId) handleActivityRecovered($event.detail)" @interactive-activity-retry.window="if ($event.detail.activityId === activityId) handleActivityRetry($event.detail)">
    <button type="button" x-ref="helpTrigger" @click="openHelp($event.currentTarget)" :aria-expanded="String(helpOpen)" aria-controls="interactive-activity-help-{{ $activity['id'] ?? 'unknown' }}" aria-label="How to complete this activity" title="How to complete this activity" class="absolute right-5 top-5 inline-flex min-h-11 min-w-11 items-center justify-center rounded-full border border-gray-200 bg-white text-gray-500 shadow-sm transition hover:border-purple-300 hover:bg-purple-50 hover:text-purple-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-purple-700">
        <svg aria-hidden="true" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
            <circle cx="12" cy="12" r="9"></circle>
            <path stroke-linecap="round" d="M9.75 9a2.25 2.25 0 1 1 3.82 1.62c-.88.84-1.57 1.28-1.57 2.63"></path>
            <path stroke-linecap="round" d="M12 16.5h.01"></path>
        </svg>
    </button>
    <h3 class="mt-2 pr-14 text-lg font-semibold text-gray-900">{{ $activity['title'] ?? 'Interactive Activity' }}</h3>
    @if(!empty($activity['instructions']))
        <div class="mt-2 prose prose-sm max-w-none text-gray-600">{!! $activity['instructions'] !!}</div>
    @endif

    @if(($activity['available'] ?? false) && ($activity['type'] ?? null) === 'matching')
        @include('learner.lessons.partials.interactive-activities.matching', ['activity' => $activity, 'preview' => $preview])
    @elseif(($activity['available'] ?? false) && ($activity['type'] ?? null) === 'sequencing')
        @include('learner.lessons.partials.interactive-activities.sequencing', ['activity' => $activity, 'preview' => $preview])
    @else
        @include('learner.lessons.partials.interactive-activities.unavailable', ['activity' => $activity, 'continueUrl' => $continueUrl])
    @endif

    <div id="interactive-activity-help-{{ $activity['id'] ?? 'unknown' }}" x-cloak x-show="helpOpen" x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/50 p-4" role="presentation" @click.self="closeHelp()">
        <div x-ref="helpDialog" role="dialog" aria-modal="true" aria-labelledby="interactive-activity-help-title-{{ $activity['id'] ?? 'unknown' }}" tabindex="-1" class="max-h-[calc(100vh-2rem)] w-full max-w-lg overflow-y-auto rounded-2xl bg-white p-5 shadow-2xl focus:outline-none sm:p-6" @click.stop @keydown="handleHelpKeydown($event)">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-purple-600">Activity guide</p>
                    <h4 id="interactive-activity-help-title-{{ $activity['id'] ?? 'unknown' }}" class="mt-1 text-lg font-semibold text-gray-900">How this activity works</h4>
                </div>
                <button type="button" @click="closeHelp()" aria-label="Close activity guide" title="Close activity guide" class="inline-flex min-h-11 min-w-11 items-center justify-center rounded-full text-gray-500 hover:bg-gray-100 hover:text-gray-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-purple-700">
                    <svg aria-hidden="true" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" d="m6 6 12 12M18 6 6 18"></path></svg>
                </button>
            </div>
            @if(($activity['type'] ?? null) === 'matching')
                <div class="mt-4 space-y-3 text-sm leading-6 text-gray-600">
                    <p>Select a connection dot on either side, then select the related dot. A temporary line follows your pointer while you choose, and a completed connection stays visible between the cards.</p>
                    <p>Connect all required pairs, then choose <strong class="font-semibold text-gray-900">Check answer</strong>. Incorrect connections stay in place so you can reconnect them before checking again.</p>
                </div>
            @elseif(($activity['type'] ?? null) === 'sequencing')
                <div class="mt-4 space-y-3 text-sm leading-6 text-gray-600">
                    <p>Use the grip on a card to pick it up and drag it to a new position. On a keyboard, press Space or Enter to pick it up, use the arrow keys, Home, or End to choose a position, then press Space or Enter to drop it.</p>
                    <p>Choose <strong class="font-semibold text-gray-900">Check answer</strong> when the steps are in order. If a position is incorrect, adjust the existing arrangement and try again.</p>
                </div>
            @else
                <p class="mt-4 text-sm leading-6 text-gray-600">Follow the activity instructions, then use the controls below the interaction to continue.</p>
            @endif
            <div class="mt-6 flex justify-end">
                <button type="button" @click="closeHelp()" class="min-h-11 rounded-xl bg-purple-700 px-4 py-2 text-sm font-semibold text-white focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-purple-700">Close</button>
            </div>
        </div>
    </div>

    <div x-show="feedback.message" aria-label="Activity feedback" aria-live="polite" role="status" class="mt-4 rounded-xl border px-4 py-3">
        <span aria-hidden="true" x-text="feedback.icon === 'check' ? '✓' : feedback.icon === 'x' ? '×' : '•'"></span>
        <span x-text="feedback.message"></span>
    </div>
    <div x-show="['completed', 'practice_completed'].includes(status) && explanation" x-html="explanation" class="mt-4 prose prose-sm max-w-none text-gray-600"></div>

    <div class="mt-5 flex flex-wrap gap-3">
        <button type="button" x-show="showSkip()" @click="skip()" :disabled="submitting" class="min-h-11 rounded-xl border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-purple-700 disabled:opacity-50">Skip</button>
        <button type="button" x-show="showResume()" @click="resume()" :disabled="submitting" class="min-h-11 rounded-xl border border-purple-300 px-4 py-2 text-sm font-semibold text-purple-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-purple-700 disabled:opacity-50">Resume</button>
        <button type="button" x-show="showContinue()" @click="continueLearning()" class="min-h-11 rounded-xl bg-purple-700 px-4 py-2 text-sm font-semibold text-white focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-purple-700">Continue</button>
        <button type="button" x-show="showPracticeAgain()" @click="practice()" :disabled="submitting" class="min-h-11 rounded-xl border border-purple-300 px-4 py-2 text-sm font-semibold text-purple-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-purple-700 disabled:opacity-50">Practice Again</button>
    </div>
    <div x-show="error" role="alert" class="mt-3 text-sm text-red-700" x-text="error"></div>
</section>
