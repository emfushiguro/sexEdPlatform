@props(['activity', 'continueUrl' => null, 'inside' => false, 'preview' => false])

@php($activityToken = 'activity:'.($activity['id'] ?? 'unknown'))

<section data-optional-interaction="{{ $activityToken }}" data-preview="{{ $preview ? 'true' : 'false' }}" class="rounded-2xl border border-purple-100 bg-white p-5 shadow-sm" x-data="interactiveActivity(@js([
    'activityId' => $activity['id'] ?? null,
    'revision' => $activity['revision'] ?? 1,
    'initialStatus' => $activity['status'] ?? 'in_progress',
    'initialExplanation' => $activity['explanation'] ?? null,
    'continueUrl' => $continueUrl,
    'skipUrl' => $activity['skip_url'] ?? null,
    'resumeUrl' => $activity['resume_url'] ?? null,
    'practiceUrl' => $activity['practice_url'] ?? null,
    'preview' => $preview,
    'csrf' => csrf_token(),
]))" x-init="$dispatch('optional-interaction-active', { token: @js($activityToken), initial: true })" @focusin="$dispatch('optional-interaction-active', { token: @js($activityToken) })" @interactive-activity-result.window="if ($event.detail.activityId === activityId) handleActivityResult($event.detail)" @interactive-activity-error.window="if ($event.detail.activityId === activityId) handleActivityError($event.detail)" @interactive-activity-recovered.window="if ($event.detail.activityId === activityId) handleActivityRecovered($event.detail)">
    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-purple-600">INTERACTIVE ACTIVITY · Optional</p>
    <h3 class="mt-2 text-lg font-semibold text-gray-900">{{ $activity['title'] ?? 'Interactive Activity' }}</h3>
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

    <div x-show="feedback.message" aria-live="polite" role="status" class="mt-4 rounded-xl border px-4 py-3">
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
