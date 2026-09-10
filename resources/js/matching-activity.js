async function readResponse(response) {
    const data = await response.json();
    if (!response.ok) throw new Error(data.errors?.preview_token?.[0] || data.message || 'Unable to check the match.');
    return data;
}

function copy(value) {
    return JSON.parse(JSON.stringify(value));
}

export function normalizeProposal(source, target) {
    if (!source || !target || source.side === target.side) return null;
    return source.side === 'left'
        ? { left_id: source.id, right_id: target.id }
        : { left_id: target.id, right_id: source.id };
}

export function connectorPoint(rect, containerRect) {
    return {
        x: rect.left + rect.width / 2 - containerRect.left,
        y: rect.top + rect.height / 2 - containerRect.top,
    };
}

export function calculateConnectorLines(leftRects, rightRects, containerRect) {
    return leftRects.flatMap((left, index) => {
        const right = rightRects[index];
        if (!left || !right) return [];
        const source = connectorPoint(left, containerRect);
        const target = connectorPoint(right, containerRect);
        return [{ x1: source.x, y1: source.y, x2: target.x, y2: target.y }];
    });
}

export function createMatchingActivity(config = {}, request = globalThis.fetch?.bind(globalThis)) {
    const activity = {
        activityId: config.activityId,
        activeEndpoint: null,
        hoveredEndpoint: null,
        pointerPosition: null,
        pendingConnection: null,
        rejectedConnection: null,
        matchedPairs: Array.isArray(config.initialMatchedPairs) ? copy(config.initialMatchedPairs) : [],
        status: config.initialStatus || 'in_progress',
        feedback: '',
        error: '',
        requestState: 'idle',
        submitting: false,
        revision: config.revision ?? 1,
        leftItems: config.leftItems ?? [],
        rightItems: config.rightItems ?? [],
        previewToken: config.previewToken ?? null,
        previewEvaluateUrl: config.previewEvaluateUrl ?? null,
        connectorLines: [],
        connectorContainer: null,
        connectorObserver: null,
        connectorFrame: null,
        connectorRefreshHandler: null,

        isLeftMatched(id) {
            return this.matchedPairs.some((pair) => pair.left_id === id);
        },

        isRightMatched(id) {
            return this.matchedPairs.some((pair) => pair.right_id === id);
        },

        isEndpointAvailable(side, id) {
            if (this.submitting || this.status === 'completed') return false;
            return side === 'left' ? !this.isLeftMatched(id) : !this.isRightMatched(id);
        },

        isValidTarget(side, id) {
            return Boolean(this.activeEndpoint
                && this.activeEndpoint.side !== side
                && this.isEndpointAvailable(this.activeEndpoint.side, this.activeEndpoint.id)
                && this.isEndpointAvailable(side, id));
        },

        ariaPressed(side, id) {
            return String(this.activeEndpoint?.side === side && this.activeEndpoint.id === id);
        },

        endpointState(side, id) {
            if (!this.isEndpointAvailable(side, id)) return 'correct';
            if (this.rejectedConnection && (this.rejectedConnection.left_id === id || this.rejectedConnection.right_id === id)) return 'incorrect';
            if (this.pendingConnection && (this.pendingConnection.left_id === id || this.pendingConnection.right_id === id)) return 'pending';
            if (this.activeEndpoint?.side === side && this.activeEndpoint.id === id) return 'selected';
            if (this.hoveredEndpoint?.side === side && this.hoveredEndpoint.id === id) return 'selected';
            return 'idle';
        },

        endpointLabel(side, id, value) {
            const state = this.endpointState(side, id);
            return `${value} — ${state === 'correct' ? 'connected' : state === 'selected' ? 'selected' : state}`;
        },

        labelFor(items, id) {
            return items.find((item) => item.id === id)?.value ?? id;
        },

        scheduleConnectorRefresh() {
            if (this.connectorFrame !== null) return this;
            const refresh = () => {
                this.connectorFrame = null;
                this.refreshConnectors();
            };
            this.connectorFrame = typeof requestAnimationFrame === 'function' ? requestAnimationFrame(refresh) : setTimeout(refresh, 0);
            return this;
        },

        findEndpoint(side, id) {
            return Array.from(this.connectorContainer?.querySelectorAll('[data-match-dot-side][data-match-id]') ?? [])
                .find((endpoint) => endpoint.dataset.matchDotSide === side && endpoint.dataset.matchId === id);
        },

        connectionLine(pair, state) {
            if (!pair || !this.connectorContainer) return null;
            const left = this.findEndpoint('left', pair.left_id);
            const right = this.findEndpoint('right', pair.right_id);
            if (!left || !right) return null;
            const containerRect = this.connectorContainer.getBoundingClientRect();
            const source = connectorPoint(left.getBoundingClientRect(), containerRect);
            const target = connectorPoint(right.getBoundingClientRect(), containerRect);
            return { x1: source.x, y1: source.y, x2: target.x, y2: target.y, state, key: `${state}-${pair.left_id}-${pair.right_id}` };
        },

        refreshConnectors() {
            if (!this.connectorContainer) return this;
            const lines = this.matchedPairs.map((pair) => this.connectionLine(pair, 'correct')).filter(Boolean);
            const pending = this.connectionLine(this.pendingConnection, 'pending');
            const rejected = this.connectionLine(this.rejectedConnection, 'incorrect');
            if (pending) lines.push(pending);
            if (rejected) lines.push(rejected);
            if (this.activeEndpoint && this.pointerPosition) {
                const source = this.findEndpoint(this.activeEndpoint.side, this.activeEndpoint.id);
                if (source) {
                    const point = connectorPoint(source.getBoundingClientRect(), this.connectorContainer.getBoundingClientRect());
                    lines.push({ x1: point.x, y1: point.y, x2: this.pointerPosition.x, y2: this.pointerPosition.y, state: 'pending', key: 'active-connection' });
                }
            }
            this.connectorLines = lines;
            return this;
        },

        setupConnectors(container) {
            this.teardownConnectors();
            this.connectorContainer = container;
            this.connectorRefreshHandler = () => this.scheduleConnectorRefresh();
            this.scheduleConnectorRefresh();
            if (typeof ResizeObserver === 'function') {
                this.connectorObserver = new ResizeObserver(this.connectorRefreshHandler);
                this.connectorObserver.observe(container);
            }
            window.addEventListener('resize', this.connectorRefreshHandler);
            window.addEventListener('scroll', this.connectorRefreshHandler, true);
            window.addEventListener('orientationchange', this.connectorRefreshHandler);
            if (typeof document !== 'undefined') document.fonts?.ready?.then(this.connectorRefreshHandler);
            return this;
        },

        teardownConnectors() {
            this.connectorObserver?.disconnect();
            if (this.connectorRefreshHandler) {
                window.removeEventListener('resize', this.connectorRefreshHandler);
                window.removeEventListener('scroll', this.connectorRefreshHandler, true);
                window.removeEventListener('orientationchange', this.connectorRefreshHandler);
            }
            if (this.connectorFrame !== null) {
                if (typeof cancelAnimationFrame === 'function') cancelAnimationFrame(this.connectorFrame);
                else clearTimeout(this.connectorFrame);
            }
            this.connectorObserver = null;
            this.connectorRefreshHandler = null;
            this.connectorFrame = null;
            return this;
        },

        startConnection(side, id, event) {
            if (!this.isEndpointAvailable(side, id)) return this;
            this.rejectedConnection = null;
            if (this.requestState === 'error') this.pendingConnection = null;
            this.requestState = 'idle';
            this.feedback = '';
            this.error = '';
            this.activeEndpoint = { side, id };
            this.hoveredEndpoint = null;
            this.moveConnection(event);
            this.scheduleConnectorRefresh();
            return this;
        },

        moveConnection(event) {
            if (!this.activeEndpoint || !this.connectorContainer) return this;
            const pointer = () => {
                const rect = this.connectorContainer.getBoundingClientRect();
                if (!Number.isFinite(event?.clientX) || !Number.isFinite(event?.clientY)) {
                    this.pointerPosition = null;
                    this.hoveredEndpoint = null;
                    return;
                }
                this.pointerPosition = { x: event.clientX - rect.left, y: event.clientY - rect.top };
                const pointTarget = typeof document !== 'undefined' && typeof document.elementFromPoint === 'function'
                    ? document.elementFromPoint(event.clientX, event.clientY)
                    : event?.target;
                const endpoint = pointTarget?.closest?.('[data-match-dot-side][data-match-id]');
                this.hoveredEndpoint = endpoint && this.isValidTarget(endpoint.dataset.matchDotSide, endpoint.dataset.matchId)
                    ? { side: endpoint.dataset.matchDotSide, id: endpoint.dataset.matchId }
                    : null;
                this.scheduleConnectorRefresh();
            };
            if (this.connectorFrame !== null) return this;
            this.connectorFrame = typeof requestAnimationFrame === 'function' ? requestAnimationFrame(pointer) : setTimeout(pointer, 0);
            return this;
        },

        cancelConnection() {
            this.activeEndpoint = null;
            this.hoveredEndpoint = null;
            this.pointerPosition = null;
            this.scheduleConnectorRefresh();
            return this;
        },

        async activateEndpoint(side, id, event) {
            if (![' ', 'Enter', 'Escape'].includes(event?.key)) return this;
            event.preventDefault();
            if (event.key === 'Escape') return this.cancelConnection();
            if (!this.activeEndpoint || this.activeEndpoint.side === side && this.activeEndpoint.id === id) return this.startConnection(side, id, event);
            return this.finishConnection(side, id);
        },

        removeRejectedConnection() {
            this.rejectedConnection = null;
            this.feedback = '';
            this.scheduleConnectorRefresh();
            return this;
        },

        publishResult(data) {
            this.$dispatch?.('interactive-activity-result', {
                activityId: config.activityId,
                type: 'matching',
                data,
                meta: { completed: this.matchedPairs.length, total: this.leftItems.length },
            });
        },

        async finishConnection(side, id) {
            if (this.activeEndpoint?.side === side && this.activeEndpoint.id === id) return this;
            if (!this.isValidTarget(side, id)) return this.cancelConnection();
            const proposal = normalizeProposal(this.activeEndpoint, { side, id });
            this.cancelConnection();
            this.pendingConnection = proposal;
            this.requestState = 'pending';
            this.submitting = true;
            this.feedback = '';
            this.error = '';
            this.scheduleConnectorRefresh();
            try {
                let data;
                if (typeof request === 'function' && (config.matchUrl || (config.preview && config.previewEvaluateUrl && this.previewToken))) {
                    const preview = config.preview === true;
                    const response = await request(preview ? config.previewEvaluateUrl : config.matchUrl, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': config.csrf, Accept: 'application/json' },
                        body: JSON.stringify(preview
                            ? { preview_token: this.previewToken, action: 'match', ...proposal }
                            : { revision: this.revision, ...proposal, practice: config.practice === true, working_state: { matched: this.matchedPairs } }),
                    });
                    data = await readResponse(response);
                } else {
                    throw new Error('Unable to check the match.');
                }

                if (data.preview_token !== undefined) this.previewToken = data.preview_token;
                this.status = data.status ?? this.status;
                this.pendingConnection = null;
                this.requestState = 'idle';
                if (data.is_correct) {
                    if (!this.matchedPairs.some((pair) => pair.left_id === proposal.left_id || pair.right_id === proposal.right_id)) this.matchedPairs.push(proposal);
                    this.rejectedConnection = null;
                } else {
                    this.rejectedConnection = proposal;
                    this.feedback = 'Not quite—try another match';
                }
                this.$dispatch?.('interactive-activity-state', { activityId: config.activityId, status: this.status, data });
                this.publishResult(data);
                this.scheduleConnectorRefresh();
                return data;
            } catch (error) {
                this.error = error.message || 'Unable to check the match.';
                this.requestState = 'error';
                this.$dispatch?.('interactive-activity-error', { activityId: config.activityId, message: this.error });
                this.scheduleConnectorRefresh();
                return null;
            } finally {
                this.submitting = false;
            }
        },

        loadPayload(payload = {}, status = this.status, previewToken = undefined) {
            this.leftItems = Array.isArray(payload.left_items) ? payload.left_items : [];
            this.rightItems = Array.isArray(payload.right_items) ? payload.right_items : [];
            this.matchedPairs = Array.isArray(payload.completed_matches) ? copy(payload.completed_matches) : [];
            this.status = status;
            if (previewToken !== undefined) this.previewToken = previewToken;
            this.activeEndpoint = null;
            this.hoveredEndpoint = null;
            this.pointerPosition = null;
            this.pendingConnection = null;
            this.rejectedConnection = null;
            this.requestState = 'idle';
            this.feedback = '';
            this.error = '';
            queueMicrotask(() => this.refreshConnectors());
            return this;
        },

        resetPractice() {
            this.matchedPairs = [];
            this.status = 'practice';
            this.activeEndpoint = null;
            this.hoveredEndpoint = null;
            this.pointerPosition = null;
            this.pendingConnection = null;
            this.rejectedConnection = null;
            this.requestState = 'idle';
            this.feedback = '';
            this.error = '';
            this.connectorLines = [];
            return this;
        },
    };

    return activity;
}
