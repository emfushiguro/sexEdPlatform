<fieldset class="space-y-4" aria-describedby="activity-configuration-error sequencing-authoring-drag-instructions">
    <legend class="text-sm font-semibold text-gray-900">Sequence items</legend>
    <p id="sequencing-authoring-drag-instructions" class="text-xs text-gray-500">Add 3–12 unique items. Drag the handle to set the canonical order. Correct positions are derived from the displayed order.</p>
    <div role="list" aria-label="Sequence items">
        <template x-for="(item, index) in items" :key="item.id || `item-${index}`">
            <div class="interactive-authoring-sequence-row relative grid items-center gap-3 rounded-xl border border-gray-200 bg-gray-50 p-4 md:grid-cols-[auto_auto_minmax(0,1fr)_auto]"
                 role="listitem"
                 :data-items-index="index"
                 @pointerenter="if (authoringCollection === 'items') targetAuthoringDrag(index)"
                 @pointermove="if (authoringCollection === 'items') targetAuthoringDrag(index)"
                 :class="authoringCollection === 'items' && authoringReorder.from === index ? 'interactive-authoring-row--dragged' : ''">
                <div x-show="authoringCollection === 'items' && dragOverIndex === index && dragIndex !== index" class="interactive-authoring-insertion-bar absolute -top-2 left-2 right-2" aria-hidden="true"></div>
                <button type="button"
                    :data-items-handle="index"
                    :aria-label="`Drag ${itemLabel(item)}. Position ${index + 1} of ${items.length}.`"
                    aria-describedby="sequencing-authoring-drag-instructions"
                    :aria-pressed="authoringCollection === 'items' && authoringReorder.from === index"
                    @pointerdown.prevent.stop="beginAuthoringDrag('items', index)"
                    @keydown="handleAuthoringDragKey('items', index, $event)"
                    class="interactive-authoring-handle inline-flex min-h-11 min-w-11 cursor-grab items-center justify-center rounded-lg border border-gray-300 bg-white text-lg text-gray-600 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-purple-700 active:cursor-grabbing">
                    <span aria-hidden="true">⠿</span><span class="sr-only">Drag item</span>
                </button>
                <span class="text-xs font-semibold text-gray-500" x-text="`Correct position ${index + 1}`"></span>
                <div class="min-w-0 text-xs font-semibold text-gray-700">
                    <span x-text="`Item ${index + 1} text`"></span>
                    <input type="hidden" :name="`configuration[items][${index}][id]`" :value="item.id || ''" :disabled="activityType !== 'sequencing'">
                    <input type="hidden" :name="`configuration[items][${index}][kind]`" value="text" :disabled="activityType !== 'sequencing'">
                    <input type="hidden" :name="`configuration[items][${index}][image_path]`" :value="item.image_path || ''" :disabled="activityType !== 'sequencing'">
                    <input type="text" :id="`activity-item-${index}`" :name="`configuration[items][${index}][value]`" x-model="item.value" maxlength="500" :required="!item.image_path" :disabled="activityType !== 'sequencing'" :aria-describedby="`activity-configuration-error activity-item-${index}-error`" :aria-invalid="errorFor(`configuration.items.${index}.value`) ? 'true' : 'false'" class="mt-1 w-full rounded-lg border-gray-300 text-sm">
                    <span class="sr-only" role="alert" :id="`activity-item-${index}-error`" x-show="errorFor(`configuration.items.${index}.value`)" x-text="errorFor(`configuration.items.${index}.value`)"></span>
                    <div class="mt-2 space-y-2">
                        <img x-cloak x-show="item.image_url" :src="item.image_url" :alt="item.image_alt || ''" class="interactive-authoring-media-preview" draggable="false">
                        <div class="flex flex-wrap gap-2">
                            <label class="interactive-authoring-media-action">
                                <span x-text="item.image_path ? 'Replace image' : 'Upload image'"></span>
                                <input type="file" class="sr-only" accept="image/jpeg,image/png,image/webp" :disabled="activityType !== 'sequencing' || item.imageUploading" @change="uploadImage('items', index, null, $event.target.files[0]); $event.target.value = ''">
                            </label>
                            <button type="button" @click="openImageLibrary('items', index, null, $event.currentTarget)" :disabled="activityType !== 'sequencing'" class="interactive-authoring-media-action">Choose from Image Library</button>
                            <button type="button" x-cloak x-show="item.image_path" @click="removeImage('items', index, null)" class="interactive-authoring-media-remove">Remove image</button>
                        </div>
                        <label x-cloak x-show="item.image_path" class="block text-xs font-semibold text-gray-700">
                            Image alt text
                            <input type="text" :name="`configuration[items][${index}][image_alt]`" x-model="item.image_alt" maxlength="500" :required="Boolean(item.image_path)" :disabled="activityType !== 'sequencing'" class="mt-1 w-full rounded-lg border-gray-300 text-sm">
                        </label>
                        <p x-cloak x-show="item.imageUploading" role="status" class="text-xs text-gray-600">Uploading image...</p>
                        <p x-cloak x-show="item.imageError" x-text="item.imageError" role="alert" class="text-xs text-red-600"></p>
                    </div>
                </div>
                <button type="button" @click="removeItem(index)" :disabled="items.length <= 3" :aria-label="`Remove ${itemLabel(item)}`" class="min-h-11 rounded-lg border border-red-200 px-3 py-1.5 text-xs text-red-600 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-purple-700 disabled:cursor-not-allowed disabled:opacity-40">Remove</button>
            </div>
        </template>
    </div>
    <div class="sr-only" aria-live="polite" x-text="authoringDragAnnouncement"></div>
    <button type="button" data-add-items @click="addItem()" :disabled="items.length >= 12" class="min-h-11 rounded-lg border border-purple-200 px-3 py-2 text-xs font-semibold text-purple-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-purple-700 disabled:cursor-not-allowed disabled:opacity-40">Add item</button>
</fieldset>
