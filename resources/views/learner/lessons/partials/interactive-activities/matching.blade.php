@props(['activity', 'preview' => false])

<div class="interactive-match-container relative mt-6" x-data="matchingActivity(@js([
    'activityId' => $activity['id'] ?? null,
    'revision' => $activity['revision'] ?? 1,
    'matchUrl' => $activity['match_url'] ?? null,
    'previewToken' => $activity['preview_token'] ?? null,
    'previewEvaluateUrl' => $activity['preview_evaluate_url'] ?? null,
    'preview' => $preview,
    'csrf' => csrf_token(),
    'initialStatus' => $activity['status'] ?? 'in_progress',
    'initialMatchedPairs' => $activity['payload']['completed_matches'] ?? [],
    'leftItems' => $activity['payload']['left_items'] ?? [],
    'rightItems' => $activity['payload']['right_items'] ?? [],
]))"
    x-init="$nextTick(() => setupConnectors($el)); return () => teardownConnectors()"
    @pointermove.window="moveConnection($event)"
    @pointercancel.window="cancelConnection()"
    @keydown.escape.window="cancelConnection()"
    @interactive-activity-state.window="if ($event.detail.activityId === activityId) status = $event.detail.status"
    @interactive-activity-payload.window="if ($event.detail.activityId === activityId) loadPayload($event.detail.payload, $event.detail.status, $event.detail.previewToken)"
    @interactive-activity-practice.window="if ($event.detail.activityId === activityId) ($event.detail.payload ? loadPayload($event.detail.payload, status, $event.detail.previewToken) : resetPractice())">
    <svg aria-hidden="true" class="interactive-match-svg pointer-events-none absolute inset-0 h-full w-full overflow-visible" preserveAspectRatio="none">
        <defs>
            <marker id="interactive-match-arrow-correct" markerWidth="8" markerHeight="8" refX="6" refY="3" orient="auto"><path d="M0,0 L0,6 L6,3 z" class="fill-emerald-500" /></marker>
            <marker id="interactive-match-arrow-incorrect" markerWidth="8" markerHeight="8" refX="6" refY="3" orient="auto"><path d="M0,0 L0,6 L6,3 z" class="fill-rose-500" /></marker>
            <marker id="interactive-match-arrow-pending" markerWidth="8" markerHeight="8" refX="6" refY="3" orient="auto"><path d="M0,0 L0,6 L6,3 z" class="fill-violet-500" /></marker>
        </defs>
        <template x-for="line in connectorLines" :key="line.key">
            <line :x1="line.x1" :y1="line.y1" :x2="line.x2" :y2="line.y2" vector-effect="non-scaling-stroke" class="interactive-match-line"
                :class="{
                    'interactive-match-line--correct': line.state === 'correct',
                    'interactive-match-line--incorrect': line.state === 'incorrect',
                    'interactive-match-line--pending': line.state === 'pending'
                }"
                :marker-end="`url(#interactive-match-arrow-${line.state})`"></line>
        </template>
    </svg>

    <p id="matching-dot-instructions-{{ $activity['id'] ?? 'unknown' }}" class="sr-only" aria-live="polite" x-text="activeEndpoint ? 'Connection started. Select a dot on the opposite side.' : ''">Select a connection dot on either side, then select the related dot.</p>

    <div class="interactive-match-grid relative z-10 gap-y-3">
        <div class="space-y-2">
            <h4 class="text-sm font-semibold text-gray-700">Match each item</h4>
            <div class="space-y-2">
                @foreach(($activity['payload']['left_items'] ?? []) as $item)
                    <div class="interactive-match-card" :class="{
                        'interactive-match-card--selected': endpointState('left', @js($item['id'])) === 'selected',
                        'interactive-match-card--pending': endpointState('left', @js($item['id'])) === 'pending',
                        'interactive-match-card--unanswered': endpointState('left', @js($item['id'])) === 'unanswered',
                        'interactive-match-card--correct': endpointState('left', @js($item['id'])) === 'correct',
                        'interactive-match-card--incorrect': endpointState('left', @js($item['id'])) === 'incorrect'
                    }">
                        <span class="min-w-0 flex-1 text-sm text-gray-900">{{ $item['value'] }}</span>
                        <span x-cloak x-show="endpointState('left', @js($item['id'])) === 'selected'" class="text-xs font-semibold text-violet-700">Selected</span>
                        <span x-cloak x-show="endpointState('left', @js($item['id'])) === 'pending'" class="text-xs font-semibold text-violet-700">Connected</span>
                        <span x-cloak x-show="endpointState('left', @js($item['id'])) === 'unanswered'" class="text-xs font-semibold text-gray-600">Unanswered</span>
                        <span x-cloak x-show="endpointState('left', @js($item['id'])) === 'correct'" class="text-xs font-semibold text-emerald-700">Correct</span>
                        <span x-cloak x-show="endpointState('left', @js($item['id'])) === 'incorrect'" class="text-xs font-semibold text-rose-700">Incorrect</span>
                        <span class="interactive-match-badge interactive-match-badge--correct" x-cloak x-show="endpointState('left', @js($item['id'])) === 'correct'" aria-hidden="true">✓</span>
                        <span class="interactive-match-badge interactive-match-badge--incorrect" x-cloak x-show="endpointState('left', @js($item['id'])) === 'incorrect'" aria-hidden="true">×</span>
                        <button type="button" data-match-dot-side="left" data-match-id="{{ $item['id'] }}"
                            @click.stop="activateEndpoint('left', @js($item['id']), $event)"
                            @keydown.escape.stop.prevent="cancelConnection()"
                            :aria-pressed="ariaPressed('left', @js($item['id']))"
                            :aria-label="endpointLabel('left', @js($item['id']), @js($item['value']))"
                            :aria-describedby="'matching-dot-instructions-{{ $activity['id'] ?? 'unknown' }}'"
                            :aria-disabled="String(!isEndpointAvailable('left', @js($item['id'])))"
                            :disabled="isLocked() || endpointState('left', @js($item['id'])) === 'correct'"
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
                    <div class="interactive-match-card" :class="{
                        'interactive-match-card--selected': endpointState('right', @js($item['id'])) === 'selected',
                        'interactive-match-card--pending': endpointState('right', @js($item['id'])) === 'pending',
                        'interactive-match-card--unanswered': endpointState('right', @js($item['id'])) === 'unanswered',
                        'interactive-match-card--correct': endpointState('right', @js($item['id'])) === 'correct',
                        'interactive-match-card--incorrect': endpointState('right', @js($item['id'])) === 'incorrect'
                    }">
                        <button type="button" data-match-dot-side="right" data-match-id="{{ $item['id'] }}"
                            @click.stop="activateEndpoint('right', @js($item['id']), $event)"
                            @keydown.escape.stop.prevent="cancelConnection()"
                            :aria-pressed="ariaPressed('right', @js($item['id']))"
                            :aria-label="endpointLabel('right', @js($item['id']), @js($item['value']))"
                            :aria-describedby="'matching-dot-instructions-{{ $activity['id'] ?? 'unknown' }}'"
                            :aria-disabled="String(!isEndpointAvailable('right', @js($item['id'])))"
                            :disabled="isLocked() || endpointState('right', @js($item['id'])) === 'correct'"
                            class="interactive-match-dot interactive-match-dot--right min-h-11 min-w-11 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-purple-700">
                            <span aria-hidden="true"></span>
                        </button>
                        <span class="min-w-0 flex-1 text-sm text-gray-900">{{ $item['value'] }}</span>
                        <span x-cloak x-show="endpointState('right', @js($item['id'])) === 'selected'" class="text-xs font-semibold text-violet-700">Selected</span>
                        <span x-cloak x-show="endpointState('right', @js($item['id'])) === 'pending'" class="text-xs font-semibold text-violet-700">Connected</span>
                        <span x-cloak x-show="endpointState('right', @js($item['id'])) === 'unanswered'" class="text-xs font-semibold text-gray-600">Unanswered</span>
                        <span x-cloak x-show="endpointState('right', @js($item['id'])) === 'correct'" class="text-xs font-semibold text-emerald-700">Correct</span>
                        <span x-cloak x-show="endpointState('right', @js($item['id'])) === 'incorrect'" class="text-xs font-semibold text-rose-700">Incorrect</span>
                        <span class="interactive-match-badge interactive-match-badge--correct" x-cloak x-show="endpointState('right', @js($item['id'])) === 'correct'" aria-hidden="true">✓</span>
                        <span class="interactive-match-badge interactive-match-badge--incorrect" x-cloak x-show="endpointState('right', @js($item['id'])) === 'incorrect'" aria-hidden="true">×</span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="mt-4 flex flex-wrap gap-3">
        <button type="button" x-show="!isLocked()" @click="checkAnswer()" :disabled="isLocked()" class="min-h-11 rounded-xl bg-purple-700 px-4 py-2 text-sm font-semibold text-white focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-purple-700 disabled:opacity-50">Check answer</button>
        <button type="button" x-cloak x-show="hasIncorrectResults() && !isLocked()" @click="retryAnswer()" :disabled="isLocked()" class="min-h-11 rounded-xl border border-purple-300 px-4 py-2 text-sm font-semibold text-purple-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-purple-700 disabled:opacity-50">Retry</button>
    </div>
    <p x-cloak x-show="requestState === 'pending'" class="mt-3 text-sm text-gray-600" role="status" aria-live="polite">Checking your connections...</p>
</div>
