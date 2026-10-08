<fieldset class="space-y-4" aria-describedby="activity-configuration-error matching-drag-instructions">
    <legend class="text-sm font-semibold text-gray-900">Matching pairs</legend>
    <p id="matching-drag-instructions" class="text-xs text-gray-500">Add 2–12 unique pairs. Drag the handle to reorder a complete pair. The server validates the final configuration.</p>
    <div role="list" aria-label="Matching pairs">
        <template x-for="(pair, index) in pairs" :key="pair.id || `pair-${index}`">
            <div class="interactive-authoring-pair relative grid items-center gap-3 rounded-xl border border-gray-200 bg-gray-50 p-4 md:grid-cols-[auto_minmax(0,1fr)_auto_minmax(0,1fr)_auto]"
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
                <div class="min-w-0 text-xs font-semibold text-gray-700">
                    <span x-text="`Pair ${index + 1} left item`"></span>
                    <input type="hidden" :name="`configuration[pairs][${index}][id]`" :value="pair.id || ''" :disabled="activityType !== 'matching'">
                    <input type="hidden" :name="`configuration[pairs][${index}][left][id]`" :value="pair.left?.id || ''" :disabled="activityType !== 'matching'">
                    <input type="hidden" :name="`configuration[pairs][${index}][left][kind]`" value="text" :disabled="activityType !== 'matching'">
                    <input type="hidden" :name="`configuration[pairs][${index}][left][image_path]`" :value="pair.left.image_path || ''" :disabled="activityType !== 'matching'">
                    <input type="text" :id="`activity-pair-${index}-left`" :name="`configuration[pairs][${index}][left][value]`" x-model="pair.left.value" maxlength="500" :required="!pair.left.image_path" :disabled="activityType !== 'matching'" :aria-describedby="`activity-configuration-error activity-pair-${index}-left-error`" :aria-invalid="errorFor(`configuration.pairs.${index}.left.value`) ? 'true' : 'false'" class="mt-1 w-full rounded-lg border-gray-300 text-sm">
                    <span class="sr-only" role="alert" :id="`activity-pair-${index}-left-error`" x-show="errorFor(`configuration.pairs.${index}.left.value`)" x-text="errorFor(`configuration.pairs.${index}.left.value`)"></span>
                    <div class="mt-2 space-y-2">
                        <img x-cloak x-show="pair.left.image_url" :src="pair.left.image_url" :alt="pair.left.image_alt || ''" class="interactive-authoring-media-preview" draggable="false">
                        <div class="flex flex-wrap gap-2">
                            <label class="interactive-authoring-media-action">
                                <span x-text="pair.left.image_path ? 'Replace image' : 'Upload image'"></span>
                                <input type="file" class="sr-only" accept="image/jpeg,image/png,image/webp" :disabled="activityType !== 'matching' || pair.left.imageUploading" @change="uploadImage('pairs', index, 'left', $event.target.files[0]); $event.target.value = ''">
                            </label>
                            <button type="button" @click="openImageLibrary('pairs', index, 'left', $event.currentTarget)" :disabled="activityType !== 'matching'" class="interactive-authoring-media-action">Choose from Image Library</button>
                            <button type="button" x-cloak x-show="pair.left.image_path" @click="removeImage('pairs', index, 'left')" class="interactive-authoring-media-remove">Remove image</button>
                        </div>
                        <label x-cloak x-show="pair.left.image_path" class="block text-xs font-semibold text-gray-700">
                            Image alt text
                            <input type="text" :name="`configuration[pairs][${index}][left][image_alt]`" x-model="pair.left.image_alt" maxlength="500" :required="Boolean(pair.left.image_path)" :disabled="activityType !== 'matching'" class="mt-1 w-full rounded-lg border-gray-300 text-sm">
                        </label>
                        <p x-cloak x-show="pair.left.imageUploading" role="status" class="text-xs text-gray-600">Uploading image...</p>
                        <p x-cloak x-show="pair.left.imageError" x-text="pair.left.imageError" role="alert" class="text-xs text-red-600"></p>
                    </div>
                </div>
                <span class="interactive-authoring-relationship" aria-hidden="true"><span class="interactive-authoring-dot"></span><span class="interactive-authoring-line"></span><span class="interactive-authoring-dot"></span></span>
                <div class="min-w-0 text-xs font-semibold text-gray-700">
                    <span x-text="`Pair ${index + 1} right item`"></span>
                    <input type="hidden" :name="`configuration[pairs][${index}][right][id]`" :value="pair.right?.id || ''" :disabled="activityType !== 'matching'">
                    <input type="hidden" :name="`configuration[pairs][${index}][right][kind]`" value="text" :disabled="activityType !== 'matching'">
                    <input type="hidden" :name="`configuration[pairs][${index}][right][image_path]`" :value="pair.right.image_path || ''" :disabled="activityType !== 'matching'">
                    <input type="text" :id="`activity-pair-${index}-right`" :name="`configuration[pairs][${index}][right][value]`" x-model="pair.right.value" maxlength="500" :required="!pair.right.image_path" :disabled="activityType !== 'matching'" :aria-describedby="`activity-configuration-error activity-pair-${index}-right-error`" :aria-invalid="errorFor(`configuration.pairs.${index}.right.value`) ? 'true' : 'false'" class="mt-1 w-full rounded-lg border-gray-300 text-sm">
                    <span class="sr-only" role="alert" :id="`activity-pair-${index}-right-error`" x-show="errorFor(`configuration.pairs.${index}.right.value`)" x-text="errorFor(`configuration.pairs.${index}.right.value`)"></span>
                    <div class="mt-2 space-y-2">
                        <img x-cloak x-show="pair.right.image_url" :src="pair.right.image_url" :alt="pair.right.image_alt || ''" class="interactive-authoring-media-preview" draggable="false">
                        <div class="flex flex-wrap gap-2">
                            <label class="interactive-authoring-media-action">
                                <span x-text="pair.right.image_path ? 'Replace image' : 'Upload image'"></span>
                                <input type="file" class="sr-only" accept="image/jpeg,image/png,image/webp" :disabled="activityType !== 'matching' || pair.right.imageUploading" @change="uploadImage('pairs', index, 'right', $event.target.files[0]); $event.target.value = ''">
                            </label>
                            <button type="button" @click="openImageLibrary('pairs', index, 'right', $event.currentTarget)" :disabled="activityType !== 'matching'" class="interactive-authoring-media-action">Choose from Image Library</button>
                            <button type="button" x-cloak x-show="pair.right.image_path" @click="removeImage('pairs', index, 'right')" class="interactive-authoring-media-remove">Remove image</button>
                        </div>
                        <label x-cloak x-show="pair.right.image_path" class="block text-xs font-semibold text-gray-700">
                            Image alt text
                            <input type="text" :name="`configuration[pairs][${index}][right][image_alt]`" x-model="pair.right.image_alt" maxlength="500" :required="Boolean(pair.right.image_path)" :disabled="activityType !== 'matching'" class="mt-1 w-full rounded-lg border-gray-300 text-sm">
                        </label>
                        <p x-cloak x-show="pair.right.imageUploading" role="status" class="text-xs text-gray-600">Uploading image...</p>
                        <p x-cloak x-show="pair.right.imageError" x-text="pair.right.imageError" role="alert" class="text-xs text-red-600"></p>
                    </div>
                </div>
                <button type="button" @click="removePair(index)" :disabled="pairs.length <= 2" :aria-label="`Remove pair ${index + 1}`" class="min-h-11 rounded-lg border border-red-200 px-3 py-1.5 text-xs text-red-600 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-purple-700 disabled:cursor-not-allowed disabled:opacity-40">Remove</button>
            </div>
        </template>
    </div>
    <div class="sr-only" aria-live="polite" x-text="authoringDragAnnouncement"></div>
    <button type="button" data-add-pairs @click="addPair()" :disabled="pairs.length >= 12" class="min-h-11 rounded-lg border border-purple-200 px-3 py-2 text-xs font-semibold text-purple-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-purple-700 disabled:cursor-not-allowed disabled:opacity-40">Add pair</button>
</fieldset>
