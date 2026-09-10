<fieldset class="space-y-4" aria-describedby="activity-configuration-error matching-drag-instructions">
    <legend class="text-sm font-semibold text-gray-900">Matching pairs</legend>
    <p id="matching-drag-instructions" class="text-xs text-gray-500">Add 2–12 unique pairs. Drag the handle to reorder a complete pair. The server validates the final configuration.</p>
    <div role="list" aria-label="Matching pairs">
        <template x-for="(pair, index) in pairs" :key="pair.id || `pair-${index}`">
            <div class="interactive-authoring-pair relative grid items-center gap-3 rounded-xl border border-gray-200 bg-gray-50 p-4 md:grid-cols-[auto_1fr_auto_1fr_auto]"
                 role="listitem"
                 :data-pairs-index="index"
                 @pointerenter="if (authoringCollection === 'pairs') targetAuthoringDrag(index)"
                 @pointermove="if (authoringCollection === 'pairs') targetAuthoringDrag(index)"
                 :class="authoringCollection === 'pairs' && authoringReorder.from === index ? 'interactive-authoring-row--dragged' : ''">
                <div x-show="authoringCollection === 'pairs' && dragOverIndex === index && dragIndex !== index" class="interactive-authoring-insertion-bar absolute -top-2 left-2 right-2" aria-hidden="true"></div>
                <button type="button"
                    :data-pairs-handle="index"
                    :aria-label="`Drag pair ${index + 1}. Position ${index + 1} of ${pairs.length}.`"
                    aria-describedby="matching-drag-instructions"
                    :aria-pressed="authoringCollection === 'pairs' && authoringReorder.from === index"
                    @pointerdown.prevent.stop="beginAuthoringDrag('pairs', index)"
                    @keydown="handleAuthoringDragKey('pairs', index, $event)"
                    class="interactive-authoring-handle inline-flex min-h-11 min-w-11 cursor-grab items-center justify-center rounded-lg border border-gray-300 bg-white text-lg text-gray-600 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-purple-700 active:cursor-grabbing">
                    <span aria-hidden="true">⠿</span><span class="sr-only">Drag pair</span>
                </button>
                <label class="text-xs font-semibold text-gray-700">
                    <span x-text="`Pair ${index + 1} left item`"></span>
                    <input type="hidden" :name="`configuration[pairs][${index}][id]`" :value="pair.id || ''" :disabled="activityType !== 'matching'">
                    <input type="hidden" :name="`configuration[pairs][${index}][left][id]`" :value="pair.left?.id || ''" :disabled="activityType !== 'matching'">
                    <input type="hidden" :name="`configuration[pairs][${index}][left][kind]`" value="text" :disabled="activityType !== 'matching'">
                    <input type="text" :id="`activity-pair-${index}-left`" :name="`configuration[pairs][${index}][left][value]`" x-model="pair.left.value" maxlength="500" required :disabled="activityType !== 'matching'" :aria-describedby="`activity-configuration-error activity-pair-${index}-left-error`" :aria-invalid="errorFor(`configuration.pairs.${index}.left.value`) ? 'true' : 'false'" class="mt-1 w-full rounded-lg border-gray-300 text-sm">
                    <span class="sr-only" role="alert" :id="`activity-pair-${index}-left-error`" x-show="errorFor(`configuration.pairs.${index}.left.value`)" x-text="errorFor(`configuration.pairs.${index}.left.value`)"></span>
                </label>
                <span class="interactive-authoring-relationship" aria-hidden="true"><span class="interactive-authoring-dot"></span><span class="interactive-authoring-line"></span><span class="interactive-authoring-dot"></span></span>
                <label class="text-xs font-semibold text-gray-700">
                    <span x-text="`Pair ${index + 1} right item`"></span>
                    <input type="hidden" :name="`configuration[pairs][${index}][right][id]`" :value="pair.right?.id || ''" :disabled="activityType !== 'matching'">
                    <input type="hidden" :name="`configuration[pairs][${index}][right][kind]`" value="text" :disabled="activityType !== 'matching'">
                    <input type="text" :id="`activity-pair-${index}-right`" :name="`configuration[pairs][${index}][right][value]`" x-model="pair.right.value" maxlength="500" required :disabled="activityType !== 'matching'" :aria-describedby="`activity-configuration-error activity-pair-${index}-right-error`" :aria-invalid="errorFor(`configuration.pairs.${index}.right.value`) ? 'true' : 'false'" class="mt-1 w-full rounded-lg border-gray-300 text-sm">
                    <span class="sr-only" role="alert" :id="`activity-pair-${index}-right-error`" x-show="errorFor(`configuration.pairs.${index}.right.value`)" x-text="errorFor(`configuration.pairs.${index}.right.value`)"></span>
                </label>
                <button type="button" @click="removePair(index)" :disabled="pairs.length <= 2" :aria-label="`Remove pair ${index + 1}`" class="min-h-11 rounded-lg border border-red-200 px-3 py-1.5 text-xs text-red-600 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-purple-700 disabled:cursor-not-allowed disabled:opacity-40">Remove</button>
            </div>
        </template>
    </div>
    <div class="sr-only" aria-live="polite" x-text="authoringDragAnnouncement"></div>
    <button type="button" data-add-pairs @click="addPair()" :disabled="pairs.length >= 12" class="min-h-11 rounded-lg border border-purple-200 px-3 py-2 text-xs font-semibold text-purple-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-purple-700 disabled:cursor-not-allowed disabled:opacity-40">Add pair</button>
</fieldset>
