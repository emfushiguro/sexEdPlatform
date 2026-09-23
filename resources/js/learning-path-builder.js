import {
    createReorderSession,
    edgeScrollDelta,
    keyboardDestination,
    moveAt,
} from './pointer-reorder.js';

const asId = (value) => Number(value);

const isIndex = (value) => Number.isInteger(value) && value >= 0;

export function createLearningPathBuilder(config = {}) {
    const modules = (Array.isArray(config.modules) ? config.modules : [])
        .map((module) => ({
            ...module,
            id: asId(module.id),
            categories: Array.isArray(module.categories) ? [...module.categories] : [],
        }))
        .filter((module) => Number.isInteger(module.id));
    const selectedIds = (Array.isArray(config.selectedIds) ? config.selectedIds : [])
        .map(asId)
        .filter((id, index, values) => Number.isInteger(id) && values.indexOf(id) === index);

    const builder = {
        modules,
        moduleIds: [...selectedIds],
        categories: Array.isArray(config.categories) ? [...config.categories] : [],
        query: '',
        candidateOrder: [...selectedIds],
        reorder: createReorderSession(),
        draggedId: null,
        dragPoint: null,
        dragRect: null,
        dragIndex: null,
        dragOverIndex: null,
        dragAnnouncement: '',
        autoScrollFrame: null,
        lastPointerY: null,

        moduleFor(id) {
            return this.modules.find((module) => module.id === asId(id)) ?? null;
        },

        moduleLabel(module) {
            return module?.title?.trim?.() || 'Module';
        },

        moduleOptionLabel(module) {
            const creator = module?.creator || 'Platform';
            const categories = Array.isArray(module?.categories) && module.categories.length > 0
                ? ` (${module.categories.join(', ')})`
                : '';

            return `${this.moduleLabel(module)} — ${creator}${categories}`;
        },

        selected(id) {
            return this.moduleIds.includes(asId(id));
        },

        eligible(module) {
            return Boolean(module)
                && module.learnerVisible !== false
                && this.categories.every((category) => module.categories.includes(category));
        },

        get eligibleModules() {
            const needle = this.query.trim().toLocaleLowerCase();

            return this.modules.filter((module) => this.eligible(module)
                && !this.selected(module.id)
                && (!needle || this.moduleLabel(module).toLocaleLowerCase().includes(needle)));
        },

        get mismatchedModuleIds() {
            return this.moduleIds.filter((id) => {
                const module = this.moduleFor(id);
                return Boolean(module) && module.learnerVisible !== false && !this.eligible(module);
            });
        },

        get unavailableModuleIds() {
            return this.moduleIds.filter((id) => {
                const module = this.moduleFor(id);
                return !module || module.learnerVisible === false;
            });
        },

        setCategories(categories) {
            this.categories = Array.isArray(categories) ? [...categories] : [];
            return this;
        },

        syncCategories(root) {
            const values = root?.querySelectorAll?.('input[name="categories[]"]:checked') ?? [];
            this.setCategories(Array.from(values).map((input) => input.value));
            return this;
        },

        indexFor(id, order = this.candidateOrder) {
            return order.findIndex((moduleId) => moduleId === asId(id));
        },

        add(id) {
            if (this.isDragging()) this.cancelDrag();
            const module = this.moduleFor(id);
            const moduleId = asId(id);
            if (!module || this.selected(moduleId) || !this.eligible(module)) return this;

            this.moduleIds.push(moduleId);
            this.candidateOrder = [...this.moduleIds];
            this.appendRow(moduleId);
            this.syncRows();
            return this;
        },

        remove(id) {
            const moduleId = asId(id);
            if (this.isDragging()) this.cancelDrag();
            this.moduleIds = this.moduleIds.filter((selectedId) => selectedId !== moduleId);
            this.candidateOrder = this.candidateOrder.filter((selectedId) => selectedId !== moduleId);
            this.removeRow(moduleId);
            this.syncRows();
            return this;
        },

        moveUp(index) {
            if (this.isDragging()) this.cancelDrag();
            if (!isIndex(index) || index <= 0 || index >= this.moduleIds.length) return this;
            this.moduleIds = moveAt(this.moduleIds, index, index - 1);
            this.candidateOrder = [...this.moduleIds];
            this.syncRows();
            return this;
        },

        moveDown(index) {
            if (this.isDragging()) this.cancelDrag();
            if (!isIndex(index) || index >= this.moduleIds.length - 1) return this;
            this.moduleIds = moveAt(this.moduleIds, index, index + 1);
            this.candidateOrder = [...this.moduleIds];
            this.syncRows();
            return this;
        },

        moveUpById(id) {
            if (this.isDragging()) this.cancelDrag();
            return this.moveUp(this.indexFor(id, this.moduleIds));
        },

        moveDownById(id) {
            if (this.isDragging()) this.cancelDrag();
            return this.moveDown(this.indexFor(id, this.moduleIds));
        },

        initializeRows() {
            this.candidateOrder = [...this.moduleIds];
            this.syncRows();
            return this;
        },

        rows() {
            return Array.from(this.$refs?.selected?.querySelectorAll?.('[data-learning-path-row]') ?? []);
        },

        rowFor(id) {
            const moduleId = String(asId(id));
            return this.rows().find((row) => row.dataset.moduleId === moduleId) ?? null;
        },

        syncRows(order = this.candidateOrder) {
            const selected = this.$refs?.selected;
            if (!selected) return this;

            const rows = this.rows();
            const byId = new Map(rows.map((row) => [row.dataset.moduleId, row]));
            order.forEach((moduleId, index) => {
                const row = byId.get(String(moduleId));
                if (!row) return;

                selected.append(row);
                row.dataset.learningPathIndex = String(index);
                row.setAttribute('aria-posinset', String(index + 1));
                row.setAttribute('aria-setsize', String(order.length));
                row.classList.toggle('learning-path-order-row--dragged', this.isDragging() && this.draggedId === asId(moduleId));
                const insertion = row.querySelector('[data-learning-path-insertion]');
                if (insertion) {
                    insertion.hidden = !(this.isDragging() && this.dragOverIndex === index);
                }
                const handle = row.querySelector('[data-learning-path-handle]');
                handle?.setAttribute('aria-label', `Reorder ${this.moduleLabel(this.moduleFor(moduleId))}, position ${index + 1} of ${order.length}`);
                handle?.setAttribute('aria-pressed', String(this.isDragging() && this.draggedId === asId(moduleId)));
                row.querySelector('[data-learning-path-up]')?.toggleAttribute('disabled', index === 0);
                row.querySelector('[data-learning-path-down]')?.toggleAttribute('disabled', index === order.length - 1);
            });

            return this;
        },

        appendRow(id) {
            const selected = this.$refs?.selected;
            const module = this.moduleFor(id);
            if (!selected || !module || this.rowFor(id) || typeof document === 'undefined') return this;

            const row = document.createElement('li');
            row.dataset.learningPathRow = 'true';
            row.dataset.moduleId = String(module.id);
            row.className = 'learning-path-order-row relative flex items-center gap-3 rounded-xl border border-gray-200 bg-gray-50 px-3 py-2 text-sm';

            const insertion = document.createElement('div');
            insertion.dataset.learningPathInsertion = 'true';
            insertion.hidden = true;
            insertion.className = 'learning-path-order-insertion-line absolute -top-1 left-3 right-3 h-1 rounded-full bg-purple-600';

            const handle = document.createElement('button');
            handle.type = 'button';
            handle.dataset.learningPathHandle = 'true';
            handle.className = 'learning-path-drag-handle inline-flex h-11 w-11 shrink-0 cursor-grab items-center justify-center rounded-lg border border-gray-200 text-gray-600 hover:border-purple-300 hover:text-purple-700';
            handle.setAttribute('aria-pressed', 'false');
            handle.setAttribute('aria-label', `Reorder ${this.moduleLabel(module)}`);
            handle.textContent = '↕';
            handle.addEventListener('pointerdown', (event) => this.beginPointerDrag(this.indexFor(module.id), event));
            handle.addEventListener('keydown', (event) => this.handleDragKey(this.indexFor(module.id), event));

            const hidden = document.createElement('input');
            hidden.type = 'hidden';
            hidden.name = 'module_ids[]';
            hidden.value = String(module.id);

            const label = document.createElement('span');
            label.className = 'min-w-0 flex-1';
            label.textContent = this.moduleOptionLabel(module);

            const moduleLabel = this.moduleLabel(module);
            const up = this.actionButton(`Move up ${moduleLabel}`, 'data-learning-path-up', '↑');
            const down = this.actionButton(`Move down ${moduleLabel}`, 'data-learning-path-down', '↓');
            const remove = this.actionButton(`Remove ${moduleLabel}`, 'data-learning-path-remove', '×');
            up.addEventListener('click', () => this.moveUpById(module.id));
            down.addEventListener('click', () => this.moveDownById(module.id));
            remove.addEventListener('click', () => this.remove(module.id));

            row.append(insertion, handle, hidden, label, up, down, remove);
            selected.append(row);
            return this;
        },

        actionButton(label, dataAttribute, icon) {
            const button = document.createElement('button');
            button.type = 'button';
            button.setAttribute(dataAttribute, '');
            button.setAttribute('aria-label', `${label} module`);
            button.className = 'inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-lg border border-purple-200 text-xs font-semibold text-purple-700 hover:bg-purple-50 disabled:cursor-not-allowed disabled:opacity-40';
            button.textContent = icon;
            return button;
        },

        removeRow(id) {
            this.rowFor(id)?.remove();
            return this;
        },

        isDragging() {
            return this.reorder.active();
        },

        announcement(action, index) {
            const label = this.moduleLabel(this.moduleFor(this.draggedId));
            return `${action} ${label}, position ${index + 1} of ${this.moduleIds.length}.`;
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
            if (!isIndex(index) || index >= this.moduleIds.length) return this;

            this.reorder.cancel().begin(index);
            this.draggedId = this.moduleIds[index];
            this.dragPoint = Number.isFinite(event?.clientX) && Number.isFinite(event?.clientY)
                ? { x: event.clientX, y: event.clientY }
                : null;
            const row = event?.currentTarget?.closest?.('[data-learning-path-row]') ?? event?.currentTarget;
            this.dragRect = row?.getBoundingClientRect?.() ?? null;
            this.lastPointerY = Number.isFinite(event?.clientY) ? event.clientY : null;
            this.candidateOrder = [...this.moduleIds];
            this.dragIndex = index;
            this.dragOverIndex = index;
            this.dragAnnouncement = this.announcement('Picked up', index);
            this.syncRows();
            this.startAutoScroll();
            return this;
        },

        setDragTarget(index) {
            if (!this.isDragging() || !isIndex(index) || this.moduleIds.length === 0) return this;
            const target = Math.min(this.moduleIds.length - 1, Math.max(0, index));
            this.reorder.target(target);
            this.candidateOrder = moveAt(this.moduleIds, this.reorder.from, target);
            this.dragOverIndex = target;
            this.dragAnnouncement = this.announcement('Moved', target);
            this.syncRows();
            return this;
        },

        resolveDragIndex(event) {
            const pointTarget = typeof document !== 'undefined' && typeof document.elementFromPoint === 'function'
                && Number.isFinite(event?.clientX) && Number.isFinite(event?.clientY)
                ? document.elementFromPoint(event.clientX, event.clientY)
                : event?.target;
            const row = pointTarget?.closest?.('[data-learning-path-row]');
            const index = Number(row?.dataset?.learningPathIndex);
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
            const label = this.moduleLabel(this.moduleFor(this.draggedId));
            const next = this.reorder.commit(this.moduleIds);
            this.moduleIds = next;
            this.candidateOrder = [...next];
            this.stopAutoScroll();
            this.clearDragState();
            this.syncRows();
            this.dragAnnouncement = `Dropped ${label}, position ${to + 1} of ${this.moduleIds.length}.`;
            return this;
        },

        cancelDrag() {
            if (!this.isDragging()) return this;
            const index = this.reorder.from ?? this.dragIndex ?? 0;
            const label = this.moduleLabel(this.moduleFor(this.draggedId));
            this.reorder.cancel();
            this.candidateOrder = [...this.moduleIds];
            this.stopAutoScroll();
            this.clearDragState();
            this.syncRows();
            this.dragAnnouncement = `Cancelled ${label}, position ${index + 1} of ${this.moduleIds.length}.`;
            return this;
        },

        clearDragState() {
            this.draggedId = null;
            this.dragPoint = null;
            this.dragRect = null;
            this.dragIndex = null;
            this.dragOverIndex = null;
            this.lastPointerY = null;
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
            return this.setDragTarget(keyboardDestination(key, this.reorder.to ?? index, this.moduleIds.length));
        },

        handleDragKeyById(id, event = null) {
            return this.handleDragKey(this.indexFor(id), event);
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

        destroy() {
            if (this.isDragging()) this.cancelDrag();
            else this.stopAutoScroll();
            return this;
        },
    };

    return builder;
}
