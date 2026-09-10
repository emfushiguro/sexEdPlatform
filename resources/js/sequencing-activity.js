import { createReorderSession, edgeScrollDelta, keyboardDestination, moveAt } from './pointer-reorder.js';

export function moveItem(order, index, delta) {
    return moveAt(order, index, index + delta);
}

async function readResponse(response) {
    const data = await response.json();
    if (!response.ok) throw new Error(data.errors?.preview_token?.[0] || data.message || 'Unable to save the sequence.');
    return data;
}

export function createSequencingActivity(config = {}, request = globalThis.fetch?.bind(globalThis)) {
    const initialOrder = Array.isArray(config.initialOrder) ? [...config.initialOrder] : [];
    const activity = {
        order: initialOrder,
        initialOrder: [...initialOrder],
        candidateOrder: [...initialOrder],
        items: Array.isArray(config.items) ? [...config.items] : [],
        activityId: config.activityId,
        previewToken: config.previewToken ?? null,
        previewEvaluateUrl: config.previewEvaluateUrl ?? null,
        status: config.initialStatus || 'in_progress',
        revision: config.revision ?? 1,
        error: '',
        feedback: '',
        submitting: false,
        saveTimer: null,
        pendingSave: null,
        dragIndex: null,
        dragOverIndex: null,
        reorder: createReorderSession(),
        draggedId: null,
        dragPoint: null,
        dragRect: null,
        dragAnnouncement: '',
        autoScrollFrame: null,
        lastPointerY: null,

        isLocked() {
            return this.status === 'completed' || this.submitting;
        },

        positionLabel(index) {
            return `${index + 1} of ${this.order.length}`;
        },

        itemFor(id) {
            return this.items.find((item) => item.id === id) ?? { id, value: id };
        },

        publishResult(data) {
            this.$dispatch?.('interactive-activity-result', {
                activityId: config.activityId,
                type: 'sequencing',
                data,
                meta: {},
            });
        },

        move(index, delta) {
            if (!this.isLocked()) {
                this.order = moveItem(this.order, index, delta);
                this.candidateOrder = [...this.order];
            }
            this.scheduleSave();
            return this;
        },

        keyboardMove(index, delta, event = null) {
            if (event?.key && !['ArrowUp', 'ArrowDown'].includes(event.key)) return this;
            event?.preventDefault?.();
            return this.move(index, delta);
        },

        isDragging() {
            return this.reorder.active();
        },

        announcement(action, index) {
            const label = this.itemFor(this.draggedId)?.value ?? 'Item';
            return `${action} ${label}, position ${index + 1} of ${this.order.length}.`;
        },

        dragOverlayStyle() {
            if (!this.dragPoint) return {};
            return {
                left: `${this.dragPoint.x + 12}px`,
                top: `${this.dragPoint.y + 12}px`,
                width: this.dragRect?.width ? `${this.dragRect.width}px` : 'auto',
                minHeight: this.dragRect?.height ? `${this.dragRect.height}px` : '2.75rem',
            };
        },

        beginPointerDrag(index, event = null) {
            if (this.isLocked() || !Number.isInteger(index) || index < 0 || index >= this.order.length) return this;
            this.feedback = '';
            this.error = '';
            this.reorder.cancel().begin(index);
            this.draggedId = this.order[index];
            this.dragPoint = Number.isFinite(event?.clientX) && Number.isFinite(event?.clientY)
                ? { x: event.clientX, y: event.clientY }
                : null;
            const row = event?.currentTarget?.closest?.('[data-sequence-index]') ?? event?.currentTarget;
            this.dragRect = row?.getBoundingClientRect?.() ?? null;
            this.lastPointerY = Number.isFinite(event?.clientY) ? event.clientY : null;
            this.candidateOrder = [...this.order];
            this.dragIndex = index;
            this.dragOverIndex = index;
            this.dragAnnouncement = this.announcement('Picked up', index);
            this.startAutoScroll();
            return this;
        },

        setDragTarget(index) {
            if (!this.isDragging() || !Number.isInteger(index) || this.order.length === 0) return this;
            const target = Math.min(this.order.length - 1, Math.max(0, index));
            this.reorder.target(target);
            this.candidateOrder = moveAt(this.order, this.reorder.from, target);
            this.dragOverIndex = target;
            this.dragAnnouncement = this.announcement('Moved', target);
            return this;
        },

        resolveDragIndex(event) {
            const pointTarget = typeof document !== 'undefined' && typeof document.elementFromPoint === 'function'
                && Number.isFinite(event?.clientX) && Number.isFinite(event?.clientY)
                ? document.elementFromPoint(event.clientX, event.clientY)
                : event?.target;
            const row = pointTarget?.closest?.('[data-sequence-index]');
            const index = Number(row?.dataset?.sequenceIndex);
            return Number.isInteger(index) ? index : null;
        },

        movePointerDrag(event) {
            if (!this.isDragging()) return this;
            if (Number.isFinite(event?.clientX) && Number.isFinite(event?.clientY)) {
                this.dragPoint = { x: event.clientX, y: event.clientY };
                this.lastPointerY = event.clientY;
            }
            const target = this.resolveDragIndex(event);
            if (target !== null) this.setDragTarget(target);
            this.startAutoScroll();
            return this;
        },

        dropPointerDrag(event = null) {
            if (!this.isDragging()) return this;
            const eventTarget = event && typeof event === 'object' ? this.resolveDragIndex(event) : null;
            if (event && typeof event === 'object' && eventTarget === null) return this.cancelDrag();
            if (eventTarget !== null) this.setDragTarget(eventTarget);
            const to = this.reorder.to ?? this.reorder.from;
            const label = this.itemFor(this.draggedId)?.value ?? 'Item';
            const next = this.reorder.commit(this.order);
            const changed = JSON.stringify(next) !== JSON.stringify(this.order);
            this.order = next;
            this.candidateOrder = [...next];
            this.stopAutoScroll();
            this.draggedId = null;
            this.dragPoint = null;
            this.dragRect = null;
            this.dragIndex = null;
            this.dragOverIndex = null;
            this.lastPointerY = null;
            this.dragAnnouncement = `Dropped ${label}, position ${to + 1} of ${this.order.length}.`;
            if (changed) this.scheduleSave();
            return this;
        },

        cancelDrag() {
            if (!this.isDragging()) return this;
            const index = this.reorder.from ?? this.dragIndex ?? 0;
            const label = this.itemFor(this.draggedId)?.value ?? 'Item';
            this.reorder.cancel();
            this.candidateOrder = [...this.order];
            this.stopAutoScroll();
            this.draggedId = null;
            this.dragPoint = null;
            this.dragRect = null;
            this.dragIndex = null;
            this.dragOverIndex = null;
            this.lastPointerY = null;
            this.dragAnnouncement = `Cancelled ${label}, position ${index + 1} of ${this.order.length}.`;
            return this;
        },

        handleDragKey(index, event = null) {
            const key = event?.key;
            if (![' ', 'Enter', 'Escape', 'ArrowUp', 'ArrowDown', 'Home', 'End'].includes(key)) return this;
            event?.preventDefault?.();
            if (key === 'Escape') return this.cancelDrag();
            if (key === ' ' || key === 'Enter') {
                if (this.isDragging()) return this.dropPointerDrag();
                return this.beginPointerDrag(index, event);
            }
            if (!this.isDragging()) return this;
            return this.setDragTarget(keyboardDestination(key, this.reorder.to ?? index, this.order.length));
        },

        startAutoScroll() {
            if (!this.isDragging() || this.autoScrollFrame !== null || typeof window === 'undefined') return this;
            const requestFrame = window.requestAnimationFrame?.bind(window);
            if (!requestFrame) return this;
            const tick = () => {
                this.autoScrollFrame = null;
                if (!this.isDragging()) return;
                const delta = Number.isFinite(this.lastPointerY)
                    ? edgeScrollDelta(this.lastPointerY, window.innerHeight)
                    : 0;
                if (delta && typeof window.scrollBy === 'function') window.scrollBy({ top: delta, left: 0, behavior: 'auto' });
                this.autoScrollFrame = requestFrame(tick);
            };
            this.autoScrollFrame = requestFrame(tick);
            return this;
        },

        stopAutoScroll() {
            if (this.autoScrollFrame !== null) {
                if (typeof cancelAnimationFrame === 'function') cancelAnimationFrame(this.autoScrollFrame);
                else clearTimeout(this.autoScrollFrame);
            }
            this.autoScrollFrame = null;
            return this;
        },

        teardown() {
            if (this.isDragging()) this.cancelDrag();
            else this.stopAutoScroll();
            return this;
        },

        startItemDrag(index, event = null) {
            return this.beginPointerDrag(index, event);
        },

        dropItem(index) {
            if (Number.isInteger(index)) this.setDragTarget(index);
            return this.dropPointerDrag();
        },

        cancelItemDrag() {
            return this.cancelDrag();
        },

        scheduleSave() {
            if (config.preview || !config.stateUrl || typeof request !== 'function' || this.status === 'completed') return this;
            clearTimeout(this.saveTimer);
            this.saveTimer = setTimeout(() => this.persistState(), config.saveDebounceMs ?? 300);
            return this;
        },

        async persistState(force = false) {
            if (config.preview || (!force && this.submitting) || typeof request !== 'function' || !config.stateUrl) return null;
            const body = { revision: this.revision, state: { item_order: [...this.order] } };
            this.pendingSave = request(config.stateUrl, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': config.csrf, Accept: 'application/json' },
                body: JSON.stringify(body),
            }).then(readResponse).then((data) => {
                this.error = '';
                this.$dispatch?.('interactive-activity-recovered', { activityId: config.activityId });
                return data;
            }).catch((error) => {
                this.error = error.message || 'Unable to save the sequence.';
                this.$dispatch?.('interactive-activity-error', { activityId: config.activityId, message: this.error });
                return null;
            });
            const result = await this.pendingSave;
            this.pendingSave = null;
            return result;
        },

        async checkAnswer() {
            if (this.isLocked()) return null;
            if (this.isDragging()) this.dropPointerDrag();
            clearTimeout(this.saveTimer);
            this.submitting = true;
            this.feedback = '';
            this.error = '';
            try {
                if (config.preview && this.previewEvaluateUrl && this.previewToken) {
                    const response = await request(this.previewEvaluateUrl, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': config.csrf, Accept: 'application/json' },
                        body: JSON.stringify({ preview_token: this.previewToken, action: 'check_sequence', item_order: [...this.order] }),
                    });
                    const data = await readResponse(response);
                    if (data.preview_token !== undefined) this.previewToken = data.preview_token;
                    this.status = data.status ?? this.status;
                    if (!data.is_correct) this.feedback = 'Not quite, try again';
                    this.$dispatch?.('interactive-activity-state', { activityId: config.activityId, status: this.status, data });
                    this.publishResult(data);
                    return data;
                }
                if (config.preview) throw new Error('Generate a new preview before checking this activity.');
                if (typeof request !== 'function' || !config.checkUrl) return null;
                if (this.pendingSave) await this.pendingSave;
                else if (config.stateUrl) await this.persistState(true);
                const response = await request(config.checkUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': config.csrf, Accept: 'application/json' },
                    body: JSON.stringify({ revision: this.revision, item_order: [...this.order] }),
                });
                const data = await readResponse(response);
                this.status = data.status ?? this.status;
                if (!data.is_correct) this.feedback = 'Not quite, try again';
                this.$dispatch?.('interactive-activity-state', { activityId: config.activityId, status: this.status, data });
                this.publishResult(data);
                return data;
            } catch (error) {
                this.error = error.message || 'Unable to check the sequence.';
                this.$dispatch?.('interactive-activity-error', { activityId: config.activityId, message: this.error });
                return null;
            } finally {
                this.submitting = false;
            }
        },

        loadPayload(payload = {}, status = this.status, previewToken = undefined) {
            this.items = Array.isArray(payload.items) ? [...payload.items] : [];
            this.order = this.items.map((item) => item.id);
            this.initialOrder = [...this.order];
            this.candidateOrder = [...this.order];
            this.status = status ?? this.status;
            if (previewToken !== undefined) this.previewToken = previewToken;
            this.feedback = '';
            this.error = '';
            this.reorder.cancel();
            this.stopAutoScroll();
            this.draggedId = null;
            this.dragPoint = null;
            this.dragRect = null;
            this.dragAnnouncement = '';
            this.lastPointerY = null;
            this.dragIndex = null;
            this.dragOverIndex = null;
            return this;
        },

        resetPractice() {
            this.order = [...this.initialOrder];
            this.candidateOrder = [...this.order];
            this.status = 'practice';
            this.feedback = '';
            this.error = '';
            this.reorder.cancel();
            this.stopAutoScroll();
            this.draggedId = null;
            this.dragPoint = null;
            this.dragRect = null;
            this.dragAnnouncement = '';
            this.lastPointerY = null;
            this.dragIndex = null;
            this.dragOverIndex = null;
            return this;
        },
    };

    return activity;
}
