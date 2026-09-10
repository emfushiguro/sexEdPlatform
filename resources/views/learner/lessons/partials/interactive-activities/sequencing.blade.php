@props(['activity', 'preview' => false])

<div class="interactive-sequencing-container relative mt-6" x-data="sequencingActivity(@js([
    'activityId' => $activity['id'] ?? null,
    'revision' => $activity['revision'] ?? 1,
    'checkUrl' => $activity['check_sequence_url'] ?? null,
    'stateUrl' => $activity['state_url'] ?? null,
    'previewToken' => $activity['preview_token'] ?? null,
    'previewEvaluateUrl' => $activity['preview_evaluate_url'] ?? null,
    'preview' => $preview,
    'csrf' => csrf_token(),
    'initialStatus' => $activity['status'] ?? 'in_progress',
    'items' => $activity['payload']['items'] ?? [],
    'initialOrder' => collect($activity['payload']['items'] ?? [])->pluck('id')->values()->all(),
]))"
    x-init="return () => teardown()"
    @interactive-activity-state.window="if ($event.detail.activityId === activityId) status = $event.detail.status"
    @interactive-activity-payload.window="if ($event.detail.activityId === activityId) loadPayload($event.detail.payload, $event.detail.status, $event.detail.previewToken)"
    @interactive-activity-practice.window="if ($event.detail.activityId === activityId) ($event.detail.payload ? loadPayload($event.detail.payload, status, $event.detail.previewToken) : resetPractice())"
    @pointermove.window="movePointerDrag($event)"
    @pointerup.window="dropPointerDrag($event)"
    @pointercancel.window="cancelDrag()"
    @keydown.escape.window="if (isDragging()) cancelDrag()">
    <p id="sequencing-drag-instructions" class="mb-3 text-sm text-gray-600">Use Space or Enter to pick up an item. Use the arrow keys, Home, or End to choose a position, then Space or Enter to drop it. Press Escape to cancel.</p>

    <ol class="interactive-sequence-list space-y-2" aria-label="Sequence items">
        <template x-for="(itemId, index) in order" :key="itemId">
            <li class="interactive-sequence-row relative flex items-center gap-3 rounded-xl border border-gray-200 bg-gray-50 p-3"
                :data-sequence-index="index"
                :class="isDragging() && draggedId === itemId ? 'interactive-sequence-row--dragged' : ''"
                :aria-posinset="index + 1"
                :aria-setsize="order.length">
                <div x-show="isDragging() && dragOverIndex === index && draggedId !== itemId" class="interactive-sequence-insertion-bar absolute -top-2 left-2 right-2" aria-hidden="true"></div>
                <span class="interactive-sequence-position min-w-12 text-xs font-semibold text-gray-500" x-text="positionLabel(index)"></span>
                <span class="flex-1 text-sm text-gray-800" :class="isDragging() && draggedId === itemId ? 'interactive-sequence-source' : ''" x-text="itemFor(itemId).value"></span>
                <button type="button"
                    :aria-label="`Drag ${itemFor(itemId).value}. Position ${index + 1} of ${order.length}.`"
                    aria-describedby="sequencing-drag-instructions"
                    :aria-pressed="isDragging() && draggedId === itemId"
                    @pointerdown.prevent.stop="beginPointerDrag(index, $event)"
                    @keydown="handleDragKey(index, $event)"
                    class="interactive-sequence-handle inline-flex min-h-11 min-w-11 cursor-grab items-center justify-center rounded-lg border border-gray-300 bg-white text-lg text-gray-600 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-purple-700 active:cursor-grabbing"
                    :class="isDragging() && draggedId === itemId ? 'cursor-grabbing' : ''">
                    <span aria-hidden="true">â ¿</span>
                    <span class="sr-only">Drag item</span>
                </button>
            </li>
        </template>
    </ol>

    <div x-cloak x-show="isDragging()" :style="dragOverlayStyle()" class="interactive-sequence-overlay fixed z-50 pointer-events-none rounded-xl border border-purple-300 bg-white px-3 py-3 shadow-xl" aria-hidden="true">
        <span class="text-sm font-semibold text-gray-900" x-text="itemFor(draggedId).value"></span>
    </div>
    <div id="sequencing-drag-announcement" class="sr-only" aria-live="polite" x-text="dragAnnouncement"></div>

    <button type="button" @click="checkAnswer()" :disabled="isLocked()" class="mt-4 min-h-11 rounded-xl bg-purple-700 px-4 py-2 text-sm font-semibold text-white focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-purple-700 disabled:opacity-50">Check answer</button>
</div>
