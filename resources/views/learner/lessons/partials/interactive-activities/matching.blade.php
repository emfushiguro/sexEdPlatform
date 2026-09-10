@props(['activity', 'preview' => false])

<div class="interactive-activity-container relative mt-6" x-data="matchingActivity(@js([
    'activityId' => $activity['id'] ?? null,
    'revision' => $activity['revision'] ?? 1,
    'matchUrl' => $activity['match_url'] ?? null,
    'preview' => $preview,
    'answerKey' => $preview ? ($activity['preview_answer_key'] ?? []) : null,
    'csrf' => csrf_token(),
    'initialStatus' => $activity['status'] ?? 'in_progress',
    'initialMatchedPairs' => $activity['payload']['completed_matches'] ?? [],
    'leftItems' => $activity['payload']['left_items'] ?? [],
    'rightItems' => $activity['payload']['right_items'] ?? [],
]))"
    x-init="setupConnectors($el); return () => teardownConnectors()"
    @pointermove="moveConnection($event)"
    @keydown.escape.window="cancelConnection()"
    @interactive-activity-state.window="if ($event.detail.activityId === activityId) status = $event.detail.status"
    @interactive-activity-payload.window="if ($event.detail.activityId === activityId) loadPayload($event.detail.payload, $event.detail.status)"
    @interactive-activity-practice.window="if ($event.detail.activityId === activityId) ($event.detail.payload ? loadPayload($event.detail.payload, status) : resetPractice())">
    <svg aria-hidden="true" class="pointer-events-none absolute inset-0 z-0 h-full w-full overflow-visible">
        <defs>
            <marker id="interactive-match-arrow-correct" markerWidth="8" markerHeight="8" refX="6" refY="3" orient="auto"><path d="M0,0 L0,6 L6,3 z" class="fill-emerald-500" /></marker>
            <marker id="interactive-match-arrow-incorrect" markerWidth="8" markerHeight="8" refX="6" refY="3" orient="auto"><path d="M0,0 L0,6 L6,3 z" class="fill-rose-500" /></marker>
            <marker id="interactive-match-arrow-pending" markerWidth="8" markerHeight="8" refX="6" refY="3" orient="auto"><path d="M0,0 L0,6 L6,3 z" class="fill-violet-500" /></marker>
        </defs>
        <template x-for="line in connectorLines" :key="line.key">
            <line :x1="line.x1" :y1="line.y1" :x2="line.x2" :y2="line.y2" :class="`interactive-match-line interactive-match-line--${line.state}`" :marker-end="`url(#interactive-match-arrow-${line.state})`"></line>
        </template>
    </svg>

    <div class="interactive-match-grid relative z-10 gap-y-3">
        <div class="space-y-2">
            <h4 class="text-sm font-semibold text-gray-700">Match each item</h4>
            <div class="space-y-2">
                @foreach(($activity['payload']['left_items'] ?? []) as $item)
                    <div class="interactive-match-card" :class="`interactive-match-card--${endpointState('left', @js($item['id']))}`">
                        <span class="min-w-0 flex-1 text-sm text-gray-900">{{ $item['value'] }}</span>
                        <span class="interactive-match-badge interactive-match-badge--correct" x-show="endpointState('left', @js($item['id'])) === 'correct'" aria-hidden="true">✓</span>
                        <span class="interactive-match-badge interactive-match-badge--incorrect" x-show="endpointState('left', @js($item['id'])) === 'incorrect'" aria-hidden="true">×</span>
                        <button type="button" data-match-dot-side="left" data-match-id="{{ $item['id'] }}"
                            @pointerdown.prevent="startConnection('left', @js($item['id']), $event)"
                            @pointerup.prevent="finishConnection('left', @js($item['id']))"
                            @keydown="activateEndpoint('left', @js($item['id']), $event)"
                            :aria-pressed="ariaPressed('left', @js($item['id']))"
                            :aria-label="endpointLabel('left', @js($item['id']), @js($item['value']))"
                            :disabled="!isEndpointAvailable('left', @js($item['id']))"
                            class="interactive-match-dot interactive-match-dot--left min-h-11 min-w-11 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-purple-700">
                            <span aria-hidden="true"></span>
                        </button>
                    </div>
                @endforeach
            </div>
        </div>

        <div aria-hidden="true"></div>

        <div class="space-y-2">
            <h4 class="text-sm font-semibold text-gray-700">Related item</h4>
            <div class="space-y-2">
                @foreach(($activity['payload']['right_items'] ?? []) as $item)
                    <div class="interactive-match-card" :class="`interactive-match-card--${endpointState('right', @js($item['id']))}`">
                        <button type="button" data-match-dot-side="right" data-match-id="{{ $item['id'] }}"
                            @pointerdown.prevent="startConnection('right', @js($item['id']), $event)"
                            @pointerup.prevent="finishConnection('right', @js($item['id']))"
                            @keydown="activateEndpoint('right', @js($item['id']), $event)"
                            :aria-pressed="ariaPressed('right', @js($item['id']))"
                            :aria-label="endpointLabel('right', @js($item['id']), @js($item['value']))"
                            :disabled="!isEndpointAvailable('right', @js($item['id']))"
                            class="interactive-match-dot interactive-match-dot--right min-h-11 min-w-11 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-purple-700">
                            <span aria-hidden="true"></span>
                        </button>
                        <span class="min-w-0 flex-1 text-sm text-gray-900">{{ $item['value'] }}</span>
                        <span class="interactive-match-badge interactive-match-badge--correct" x-show="endpointState('right', @js($item['id'])) === 'correct'" aria-hidden="true">✓</span>
                        <span class="interactive-match-badge interactive-match-badge--incorrect" x-show="endpointState('right', @js($item['id'])) === 'incorrect'" aria-hidden="true">×</span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <button type="button" x-cloak x-show="rejectedConnection" @click="removeRejectedConnection()" class="mt-4 min-h-11 rounded-xl border border-rose-300 bg-rose-50 px-4 py-2 text-sm font-semibold text-rose-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-purple-700">Remove incorrect connection</button>
</div>
