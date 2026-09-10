const clamp = (value, minimum, maximum) => Math.min(maximum, Math.max(minimum, value));

export function moveAt(items, from, to) {
    const next = [...items];
    if (from < 0 || from >= next.length || to < 0 || to >= next.length || from === to) return next;

    const [item] = next.splice(from, 1);
    next.splice(to, 0, item);
    return next;
}

export function keyboardDestination(key, current, length) {
    if (length < 1) return 0;
    if (key === 'Home') return 0;
    if (key === 'End') return length - 1;
    if (key === 'ArrowUp') return clamp(current - 1, 0, length - 1);
    if (key === 'ArrowDown') return clamp(current + 1, 0, length - 1);
    return current;
}

export function edgeScrollDelta(clientY, viewportHeight, threshold = 72, maximum = 16) {
    if (clientY < threshold) return -maximum;
    if (clientY > viewportHeight - threshold) return maximum;
    return 0;
}

export function createReorderSession() {
    return {
        from: null,
        to: null,

        begin(index) {
            this.from = index;
            this.to = index;
            return this;
        },

        target(index) {
            if (this.from !== null) this.to = index;
            return this;
        },

        active() {
            return this.from !== null;
        },

        commit(items) {
            const next = this.active() ? moveAt(items, this.from, this.to) : [...items];
            this.cancel();
            return next;
        },

        cancel() {
            this.from = null;
            this.to = null;
            return this;
        },
    };
}
