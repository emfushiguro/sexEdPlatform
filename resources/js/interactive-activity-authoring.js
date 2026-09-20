import { createReorderSession, keyboardDestination, moveAt } from './pointer-reorder.js';

const copy = (value) => JSON.parse(JSON.stringify(value));

const defaultContentItem = () => ({
    value: '',
    image_path: null,
    image_url: null,
    image_alt: '',
    imageUploading: false,
    imageError: '',
    localPreviewUrl: null,
});

const contentItem = (item = {}) => ({
    ...defaultContentItem(),
    ...item,
    value: item?.value ?? '',
});

const defaultPair = () => ({ left: defaultContentItem(), right: defaultContentItem() });
const defaultItem = () => defaultContentItem();
const authoringPair = (pair = {}) => ({
    ...pair,
    left: contentItem(pair.left),
    right: contentItem(pair.right),
});

export function createInteractiveActivityPreview(options = {}, request = globalThis.fetch?.bind(globalThis)) {
    return {
        isOpen: false,
        isLoading: false,
        previewHtml: '',
        errors: {},
        previewError: '',
        previewViewport: 'desktop',
        previewTrigger: null,

        previewWidth() {
            return { mobile: 375, tablet: 768, desktop: 1440 }[this.previewViewport] ?? 1440;
        },

        selectViewport(viewport) {
            if (['mobile', 'tablet', 'desktop'].includes(viewport)) this.previewViewport = viewport;
            return this;
        },

        errorMessages() {
            return Object.values(this.errors).flatMap((messages) => Array.isArray(messages) ? messages : [messages]).filter(Boolean);
        },

        async open(form, trigger = null) {
            this.isLoading = true;
            this.isOpen = false;
            this.previewError = '';
            this.errors = {};
            this.previewTrigger = trigger;

            try {
                const formData = new FormData(form);
                formData.delete?.('_method');
                const response = await request(options.url, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': options.csrf, Accept: 'application/json' },
                    body: formData,
                });
                const data = await response.json();
                if (!response.ok) {
                    this.errors = data.errors ?? {};
                    this.previewError = data.message || 'Review the highlighted fields.';
                    return null;
                }

                this.previewHtml = data.html || '';
                this.isOpen = true;
                this.$nextTick?.(() => {
                    const mount = this.$refs?.previewMount;
                    if (mount && globalThis.Alpine?.initTree) globalThis.Alpine.initTree(mount);
                });
                return data;
            } catch (error) {
                this.previewError = error.message || 'Unable to load the activity preview.';
                return null;
            } finally {
                this.isLoading = false;
            }
        },

        close() {
            this.isOpen = false;
            this.previewTrigger?.focus?.();
            this.previewTrigger = null;
            return this;
        },
    };
}

export function createInteractiveActivityAuthoring(options = {}) {
    const initialType = options.activityType === 'sequencing' ? 'sequencing' : 'matching';
    const api = {
        ...createInteractiveActivityPreview({ url: options.previewUrl, csrf: options.csrf }, options.request),
        activityType: initialType,
        placement: options.placement === 'inside_topic' ? 'inside_topic' : 'between_topics',
        parentTopicId: options.parentTopicId ?? '',
        insertAfterBlock: Number.isInteger(options.insertAfterBlock) ? options.insertAfterBlock : 0,
        blockOptions: Array.isArray(options.blockOptions) ? copy(options.blockOptions) : [],
        validationErrors: options.validationErrors && typeof options.validationErrors === 'object'
            ? copy(options.validationErrors)
            : {},
        pairs: Array.isArray(options.pairs) && options.pairs.length > 0
            ? options.pairs.map(authoringPair)
            : [defaultPair(), defaultPair()],
        items: Array.isArray(options.items) && options.items.length > 0
            ? options.items.map(contentItem)
            : [defaultItem(), defaultItem(), defaultItem()],
        imageUploadUrl: options.imageUploadUrl ?? '',
        imageLibraryUrl: options.imageLibraryUrl ?? '',
        csrf: options.csrf ?? '',
        mediaRequest: options.request ?? globalThis.fetch?.bind(globalThis),
        mediaUploadCount: 0,
        imageLibraryOpen: false,
        imageLibraryLoading: false,
        imageLibraryLoaded: false,
        imageLibraryImages: [],
        imageLibraryError: '',
        mediaTarget: null,
        mediaTrigger: null,
        dragIndex: null,
        dragOverIndex: null,
        authoringReorder: createReorderSession(),
        authoringCollection: null,
        authoringDragAnnouncement: '',

        setActivityType(type) {
            this.activityType = type === 'sequencing' ? 'sequencing' : 'matching';
            return this;
        },

        mediaItem(kind, index, side = null) {
            if (kind === 'pairs') return this.pairs[index]?.[side] ?? null;
            if (kind === 'items') return this.items[index] ?? null;
            return null;
        },

        itemLabel(item) {
            return item?.value?.trim?.() || item?.image_alt?.trim?.() || 'Item';
        },

        attachImage(kind, index, side, image) {
            const item = this.mediaItem(kind, index, side);
            if (!item || !image?.path || !image?.url) return this;
            item.image_path = image.path;
            item.image_url = image.url;
            item.imageError = '';
            return this;
        },

        removeImage(kind, index, side = null) {
            const item = this.mediaItem(kind, index, side);
            if (!item) return this;
            if (item.localPreviewUrl) globalThis.URL?.revokeObjectURL?.(item.localPreviewUrl);
            item.image_path = null;
            item.image_url = null;
            item.image_alt = '';
            item.imageError = '';
            item.localPreviewUrl = null;
            return this;
        },

        serializedItem(item, extra = {}) {
            return {
                ...extra,
                kind: 'text',
                value: item?.value ?? '',
                image_path: item?.image_path || null,
                image_alt: item?.image_path ? (item?.image_alt ?? '') : null,
            };
        },

        hasPendingMedia() {
            return this.mediaUploadCount > 0;
        },

        syncMediaControls() {
            const form = this.$root?.closest?.('form');
            form?.querySelectorAll?.('[data-interactive-activity-submit]').forEach((button) => {
                button.disabled = this.hasPendingMedia();
            });
            return this;
        },

        async uploadImage(kind, index, side, file) {
            const item = this.mediaItem(kind, index, side);
            if (!item || !file || typeof this.mediaRequest !== 'function' || !this.imageUploadUrl) return this;

            const previous = { ...item };
            const previewUrl = globalThis.URL?.createObjectURL?.(file) ?? null;
            item.imageUploading = true;
            item.imageError = '';
            item.localPreviewUrl = previewUrl;
            if (previewUrl) item.image_url = previewUrl;
            this.mediaUploadCount += 1;
            this.syncMediaControls();

            try {
                const formData = new FormData();
                formData.append('image', file);
                const response = await this.mediaRequest(this.imageUploadUrl, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': this.csrf, Accept: 'application/json' },
                    body: formData,
                });
                const data = await response.json();
                if (!response.ok || !data.path || !data.url) {
                    throw new Error(data.message || 'Unable to upload image.');
                }
                this.attachImage(kind, index, side, data);
            } catch (error) {
                Object.assign(item, previous);
                item.imageError = error.message || 'Unable to upload image.';
            } finally {
                if (previewUrl) globalThis.URL?.revokeObjectURL?.(previewUrl);
                item.localPreviewUrl = null;
                item.imageUploading = false;
                this.mediaUploadCount = Math.max(0, this.mediaUploadCount - 1);
                this.syncMediaControls();
            }

            return this;
        },

        async openImageLibrary(kind, index, side, trigger = null) {
            this.mediaTarget = { kind, index, side };
            this.mediaTrigger = trigger;
            this.imageLibraryOpen = true;
            this.imageLibraryError = '';

            if (this.imageLibraryLoaded) return this;
            if (this.imageLibraryLoading || typeof this.mediaRequest !== 'function' || !this.imageLibraryUrl) return this;

            this.imageLibraryLoading = true;
            try {
                const response = await this.mediaRequest(this.imageLibraryUrl, {
                    headers: { Accept: 'application/json' },
                });
                const data = await response.json();
                if (!response.ok) throw new Error(data.message || 'Unable to load the Image Library.');
                this.imageLibraryImages = Array.isArray(data.images) ? data.images : [];
                this.imageLibraryLoaded = true;
            } catch (error) {
                this.imageLibraryError = error.message || 'Unable to load the Image Library.';
            } finally {
                this.imageLibraryLoading = false;
            }

            return this;
        },

        selectLibraryImage(image) {
            if (this.mediaTarget) {
                this.attachImage(
                    this.mediaTarget.kind,
                    this.mediaTarget.index,
                    this.mediaTarget.side,
                    image,
                );
            }
            return this.closeImageLibrary();
        },

        closeImageLibrary() {
            this.imageLibraryOpen = false;
            this.mediaTarget = null;
            this.mediaTrigger?.focus?.();
            this.mediaTrigger = null;
            return this;
        },

        cleanupMedia() {
            [...this.pairs.flatMap((pair) => [pair.left, pair.right]), ...this.items]
                .forEach((item) => {
                    if (item?.localPreviewUrl) globalThis.URL?.revokeObjectURL?.(item.localPreviewUrl);
                });
            return this;
        },

        errorFor(key) {
            const messages = this.validationErrors[key];
            return Array.isArray(messages) ? (messages[0] ?? '') : '';
        },

        addPair() {
            if (this.pairs.length < 12) this.pairs.push(defaultPair());
            return this;
        },

        removePair(index) {
            if (this.pairs.length > 2) {
                this.pairs.splice(index, 1);
                this.focusAfterRemoval('pairs', index);
            }
            return this;
        },

        movePair(index, offset) {
            this.pairs = moveAt(this.pairs, index, index + offset);
            return this;
        },

        addItem() {
            if (this.items.length < 12) this.items.push(defaultItem());
            return this;
        },

        removeItem(index) {
            if (this.items.length > 3) {
                this.items.splice(index, 1);
                this.focusAfterRemoval('items', index);
            }
            return this;
        },

        moveItem(index, offset) {
            this.items = moveAt(this.items, index, index + offset);
            return this;
        },

        authoringItems(kind) {
            return kind === 'pairs' ? this.pairs : this.items;
        },

        authoringLabel(kind, index) {
            const value = this.authoringItems(kind)[index];
            return kind === 'pairs'
                ? `${this.itemLabel(value?.left) || 'Pair'} / ${this.itemLabel(value?.right) || 'pair'}`
                : this.itemLabel(value);
        },

        authoringAnnouncement(action, kind, index) {
            return `${action} ${this.authoringLabel(kind, index)}, position ${index + 1} of ${this.authoringItems(kind).length}.`;
        },

        beginAuthoringDrag(kind, index) {
            const collection = this.authoringItems(kind);
            if (!['pairs', 'items'].includes(kind) || !Number.isInteger(index) || index < 0 || index >= collection.length) return this;
            this.authoringReorder.cancel().begin(index);
            this.authoringCollection = kind;
            this.dragIndex = index;
            this.dragOverIndex = index;
            this.authoringDragAnnouncement = this.authoringAnnouncement('Picked up', kind, index);
            return this;
        },

        targetAuthoringDrag(index) {
            if (!this.authoringReorder.active() || !Number.isInteger(index)) return this;
            const collection = this.authoringItems(this.authoringCollection);
            const target = Math.min(collection.length - 1, Math.max(0, index));
            this.authoringReorder.target(target);
            this.dragOverIndex = target;
            this.authoringDragAnnouncement = this.authoringAnnouncement('Moved', this.authoringCollection, target);
            return this;
        },

        dropAuthoringDrag() {
            if (!this.authoringReorder.active()) return this;
            const kind = this.authoringCollection;
            const collection = this.authoringItems(kind);
            const target = this.authoringReorder.to ?? this.authoringReorder.from;
            const next = this.authoringReorder.commit(collection);
            if (kind === 'pairs') this.pairs = next;
            else this.items = next;
            this.authoringDragAnnouncement = this.authoringAnnouncement('Dropped', kind, target);
            this.authoringCollection = null;
            this.dragIndex = null;
            this.dragOverIndex = null;
            return this;
        },

        cancelAuthoringDrag() {
            if (this.authoringReorder.active()) {
                const kind = this.authoringCollection;
                const index = this.authoringReorder.from;
                this.authoringReorder.cancel();
                this.authoringDragAnnouncement = this.authoringAnnouncement('Cancelled', kind, index);
            }
            this.authoringCollection = null;
            this.dragIndex = null;
            this.dragOverIndex = null;
            return this;
        },

        handleAuthoringDragKey(kind, index, event = null) {
            const key = event?.key;
            if (![' ', 'Enter', 'Escape', 'ArrowUp', 'ArrowDown', 'Home', 'End'].includes(key)) return this;
            event?.preventDefault?.();
            if (key === 'Escape') return this.cancelAuthoringDrag();
            if (key === ' ' || key === 'Enter') {
                if (this.authoringReorder.active()) return this.dropAuthoringDrag();
                return this.beginAuthoringDrag(kind, index);
            }
            if (this.authoringCollection !== kind || !this.authoringReorder.active()) return this;
            return this.targetAuthoringDrag(keyboardDestination(key, this.authoringReorder.to ?? index, this.authoringItems(kind).length));
        },

        focusTargetAfterRemoval(kind, removedIndex) {
            const collection = this.authoringItems(kind);
            if (collection.length === 0) return `[data-add-${kind}]`;
            return `[data-${kind}-handle="${Math.min(removedIndex, collection.length - 1)}"]`;
        },

        focusAfterRemoval(kind, removedIndex) {
            const selector = this.focusTargetAfterRemoval(kind, removedIndex);
            this.$nextTick?.(() => this.$root?.querySelector?.(selector)?.focus?.());
            return selector;
        },

        startItemDrag(index, event = null) {
            return this.beginAuthoringDrag('items', index, event);
        },

        dropItem(index) {
            if (Number.isInteger(index)) this.targetAuthoringDrag(index);
            return this.dropAuthoringDrag();
        },

        cancelItemDrag() {
            return this.cancelAuthoringDrag();
        },

        configuration() {
            if (this.activityType === 'sequencing') {
                return {
                    schema_version: 1,
                    items: this.items.map((item, index) => this.serializedItem(item, {
                        ...(item.id ? { id: item.id } : {}),
                        correct_position: index + 1,
                    })),
                };
            }

            return {
                schema_version: 1,
                pairs: this.pairs.map((pair) => ({
                    ...(pair.id ? { id: pair.id } : {}),
                    left: this.serializedItem(pair.left, pair.left?.id ? { id: pair.left.id } : {}),
                    right: this.serializedItem(pair.right, pair.right?.id ? { id: pair.right.id } : {}),
                })),
            };
        },

        serializedConfiguration() {
            return JSON.stringify(this.configuration());
        },

        async openPreview(trigger = null) {
            if (this.hasPendingMedia()) {
                this.previewError = 'Wait for image uploads to finish.';
                return null;
            }
            const form = trigger?.closest?.('form') ?? this.$root?.closest?.('form');
            const result = await this.open(form, trigger);
            if (result === null) this.validationErrors = copy(this.errors);
            return result;
        },

        closePreview() {
            return this.close();
        },
    };

    return api;
}
