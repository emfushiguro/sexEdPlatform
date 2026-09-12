async function readResponse(response) {
    const data = await response.json();
    if (!response.ok) throw new Error(data.errors?.preview_token?.[0] || data.message || 'Unable to check the matches.');
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
    const initialMatchedPairs = Array.isArray(config.initialMatchedPairs) ? copy(config.initialMatchedPairs) : [];
    const activity = {
        activityId: config.activityId,
        activeEndpoint: null,
        hoveredEndpoint: null,
        pointerPosition: null,
        pendingConnection: null,
        rejectedConnection: null,
        matchedPairs: initialMatchedPairs,
        pairResults: initialMatchedPairs.map((pair) => ({ ...pair, is_correct: true, state: 'correct' })),
        answerChecked: false,
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
        pointerFrame: null,
        connectorRefreshHandler: null,

        isLocked() {
            return ['completed', 'practice_completed'].includes(this.status) || this.submitting;
        },

        isLeftMatched(id) {
            return this.matchedPairs.some((pair) => pair.left_id === id);
        },

        isRightMatched(id) {
            return this.matchedPairs.some((pair) => pair.right_id === id);
        },

        connectionForEndpoint(side, id) {
            return this.matchedPairs.find((pair) => side === 'left'
                ? pair.left_id === id
                : pair.right_id === id) ?? null;
        },

        samePair(first, second) {
            return Boolean(first && second
                && first.left_id === second.left_id
                && first.right_id === second.right_id);
        },

        pairResultFor(pair) {
            if (!pair) return null;
            return this.pairResults.find((result) => this.samePair(result, pair)) ?? null;
        },

        pairState(pair) {
            const result = this.pairResultFor(pair);
            if (result) return result.state ?? (result.is_correct ? 'correct' : 'incorrect');
            if (this.pendingConnection && this.samePair(this.pendingConnection, pair)) return 'pending';
            return pair ? 'pending' : 'idle';
        },

        clearPairResult(pair) {
            this.pairResults = this.pairResults.filter((result) => !this.samePair(result, pair));
            return this;
        },

        detachEndpoint(side, id) {
            const pair = this.connectionForEndpoint(side, id);
            if (!pair || this.pairState(pair) === 'correct') return this;

            this.matchedPairs = this.matchedPairs.filter((candidate) => !this.samePair(candidate, pair));
            this.clearPairResult(pair);
            if (this.samePair(this.rejectedConnection, pair)) this.rejectedConnection = null;
            return this;
        },

        correctPairCount() {
            return this.matchedPairs.filter((pair) => this.pairState(pair) === 'correct').length;
        },

        hasIncorrectResults() {
            return this.pairResults.some((result) => (result.state ?? (result.is_correct ? 'correct' : 'incorrect')) === 'incorrect');
        },

        isEndpointAvailable(side, id) {
            if (this.isLocked()) return false;
            const pair = this.connectionForEndpoint(side, id);
            return !pair || this.pairState(pair) !== 'correct';
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
            if (this.activeEndpoint?.side === side && this.activeEndpoint.id === id) return 'selected';
            if (this.hoveredEndpoint?.side === side && this.hoveredEndpoint.id === id) return 'selected';
            const pair = this.connectionForEndpoint(side, id);
            if (pair) return this.pairState(pair);
            if (this.rejectedConnection && ((side === 'left' && this.rejectedConnection.left_id === id) || (side === 'right' && this.rejectedConnection.right_id === id))) return 'incorrect';
            if (this.pendingConnection && ((side === 'left' && this.pendingConnection.left_id === id) || (side === 'right' && this.pendingConnection.right_id === id))) return 'pending';
            return this.answerChecked ? 'unanswered' : 'idle';
        },

        endpointLabel(side, id, value) {
            const state = this.endpointState(side, id);
            const pair = this.connectionForEndpoint(side, id);
            const targetId = side === 'left' ? pair?.right_id : pair?.left_id;
            const targetItems = side === 'left' ? this.rightItems : this.leftItems;
            const targetLabel = targetId ? this.labelFor(targetItems, targetId) : null;
            const connection = targetLabel ? ` connected to ${targetLabel}` : '';
            const stateLabel = state === 'correct' ? 'correct' : state === 'selected' ? 'selected' : state;

            return `${value} - ${stateLabel}${connection}`;
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
            this.connectorFrame = typeof requestAnimationFrame === 'function'
                ? requestAnimationFrame(refresh)
                : setTimeout(refresh, 0);
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
            return {
                x1: source.x,
                y1: source.y,
                x2: target.x,
                y2: target.y,
                state,
                key: `${state}-${pair.left_id}-${pair.right_id}`,
            };
        },

        selectionPreviewPoint(side, id) {
            if (!this.connectorContainer) return null;
            const endpoint = this.findEndpoint(side, id);
            if (!endpoint) return null;
            const containerRect = this.connectorContainer.getBoundingClientRect();
            const source = connectorPoint(endpoint.getBoundingClientRect(), containerRect);
            return { x: containerRect.width / 2, y: source.y };
        },

        refreshConnectors() {
            if (!this.connectorContainer) return this;
            const lines = this.matchedPairs
                .map((pair) => this.connectionLine(pair, this.pairState(pair)))
                .filter(Boolean);
            const pending = this.connectionLine(this.pendingConnection, 'pending');
            const rejected = this.connectionLine(this.rejectedConnection, 'incorrect');
            if (pending && !this.matchedPairs.some((pair) => this.samePair(pair, this.pendingConnection))) lines.push(pending);
            if (rejected && !this.matchedPairs.some((pair) => this.samePair(pair, this.rejectedConnection))) lines.push(rejected);
            if (this.activeEndpoint) {
                const source = this.findEndpoint(this.activeEndpoint.side, this.activeEndpoint.id);
                const target = this.pointerPosition ?? this.selectionPreviewPoint(this.activeEndpoint.side, this.activeEndpoint.id);
                if (source && target) {
                    const point = connectorPoint(source.getBoundingClientRect(), this.connectorContainer.getBoundingClientRect());
                    lines.push({
                        x1: point.x,
                        y1: point.y,
                        x2: target.x,
                        y2: target.y,
                        state: 'pending',
                        key: 'active-connection',
                    });
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
            if (typeof window !== 'undefined') {
                window.addEventListener('resize', this.connectorRefreshHandler);
                window.addEventListener('scroll', this.connectorRefreshHandler, true);
                window.addEventListener('orientationchange', this.connectorRefreshHandler);
            }
            if (typeof document !== 'undefined') document.fonts?.ready?.then(this.connectorRefreshHandler);
            return this;
        },

        teardownConnectors() {
            this.connectorObserver?.disconnect();
            if (this.connectorRefreshHandler && typeof window !== 'undefined') {
                window.removeEventListener('resize', this.connectorRefreshHandler);
                window.removeEventListener('scroll', this.connectorRefreshHandler, true);
                window.removeEventListener('orientationchange', this.connectorRefreshHandler);
            }
            if (this.connectorFrame !== null) {
                if (typeof cancelAnimationFrame === 'function') cancelAnimationFrame(this.connectorFrame);
                else clearTimeout(this.connectorFrame);
            }
            if (this.pointerFrame !== null) {
                if (typeof cancelAnimationFrame === 'function') cancelAnimationFrame(this.pointerFrame);
                else clearTimeout(this.pointerFrame);
            }
            this.connectorObserver = null;
            this.connectorRefreshHandler = null;
            this.connectorFrame = null;
            this.pointerFrame = null;
            return this;
        },

        startConnection(side, id, event) {
            if (!this.isEndpointAvailable(side, id)) return this;
            this.detachEndpoint(side, id);
            this.rejectedConnection = null;
            this.requestState = 'idle';
            this.feedback = '';
            this.error = '';
            this.activeEndpoint = { side, id };
            this.hoveredEndpoint = null;
            this.pointerPosition = null;
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
            if (this.pointerFrame !== null) return this;
            const callback = () => {
                this.pointerFrame = null;
                pointer();
            };
            this.pointerFrame = typeof requestAnimationFrame === 'function'
                ? requestAnimationFrame(callback)
                : setTimeout(callback, 0);
            return this;
        },

        cancelConnection() {
            this.activeEndpoint = null;
            this.hoveredEndpoint = null;
            this.pointerPosition = null;
            this.scheduleConnectorRefresh();
            return this;
        },

        activateEndpoint(side, id, event = null) {
            const key = event?.key;
            if (key && ![' ', 'Enter', 'Escape'].includes(key)) return this;
            event?.preventDefault?.();
            if (key === 'Escape') return this.cancelConnection();
            if (!this.activeEndpoint) return this.startConnection(side, id, event);
            if (this.activeEndpoint.side === side && this.activeEndpoint.id === id) return this.cancelConnection();
            if (this.activeEndpoint.side === side) return this.startConnection(side, id, event);
            return this.finishConnection(side, id);
        },

        removeRejectedConnection() {
            if (this.rejectedConnection) this.detachEndpoint('left', this.rejectedConnection.left_id);
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
                meta: { completed: this.correctPairCount(), total: this.leftItems.length },
            });
        },

        finishConnection(side, id) {
            if (this.activeEndpoint?.side === side && this.activeEndpoint.id === id) return this;
            if (!this.isValidTarget(side, id)) return this.cancelConnection();
            const proposal = normalizeProposal(this.activeEndpoint, { side, id });
            this.cancelConnection();
            this.detachEndpoint('left', proposal.left_id);
            this.detachEndpoint('right', proposal.right_id);
            this.matchedPairs.push(proposal);
            this.clearPairResult(proposal);
            this.pendingConnection = null;
            this.requestState = 'idle';
            this.feedback = '';
            this.error = '';
            this.scheduleConnectorRefresh();

            return proposal;
        },

        async checkAnswer() {
            if (this.isLocked()) return null;
            if (this.activeEndpoint) this.cancelConnection();
            if (typeof request !== 'function') return null;

            const preview = config.preview === true;
            const url = preview ? config.previewEvaluateUrl : config.matchUrl;
            if (!url || (preview && !this.previewToken)) {
                this.error = preview ? 'Generate a new preview before checking this activity.' : 'Unable to check the matches.';
                this.requestState = 'error';
                this.$dispatch?.('interactive-activity-error', { activityId: config.activityId, message: this.error });
                return null;
            }

            this.submitting = true;
            this.requestState = 'pending';
            this.pendingConnection = null;
            this.feedback = '';
            this.error = '';
            const connections = copy(this.matchedPairs);
            try {
                const response = await request(url, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': config.csrf, Accept: 'application/json' },
                    body: JSON.stringify(preview
                        ? { preview_token: this.previewToken, action: 'match', connections }
                        : {
                            revision: this.revision,
                            connections,
                            practice: config.practice === true,
                            working_state: {
                                right_order: this.rightItems.map((item) => item.id),
                                matched: this.matchedPairs.filter((pair) => this.pairState(pair) === 'correct'),
                            },
                        }),
                });
                const data = await readResponse(response);
                if (data.preview_token !== undefined) this.previewToken = data.preview_token;
                if (data.is_complete && Array.isArray(data.payload?.left_items) && Array.isArray(data.payload?.right_items)) {
                    this.loadPayload(data.payload, data.status, data.preview_token);
                } else {
                    this.status = data.status ?? this.status;
                    this.answerChecked = true;
                    if (Array.isArray(data.pair_results)) {
                        this.pairResults = copy(data.pair_results);
                    } else if (data.is_correct !== undefined && connections.length === 1) {
                        this.pairResults = [{
                            ...connections[0],
                            is_correct: data.is_correct,
                            state: data.is_correct ? 'correct' : 'incorrect',
                        }];
                    }
                }
                this.requestState = 'idle';
                if (!data.is_complete && data.is_correct !== true) this.feedback = 'Review the highlighted connections and try again.';
                this.$dispatch?.('interactive-activity-state', { activityId: config.activityId, status: this.status, data });
                this.publishResult(data);
                this.scheduleConnectorRefresh();
                return data;
            } catch (error) {
                this.error = error.message || 'Unable to check the matches.';
                this.requestState = 'error';
                this.$dispatch?.('interactive-activity-error', { activityId: config.activityId, message: this.error });
                this.scheduleConnectorRefresh();
                return null;
            } finally {
                this.submitting = false;
            }
        },

        retryAnswer() {
            if (this.isLocked()) return this;
            this.feedback = '';
            this.error = '';
            this.requestState = 'idle';
            this.$dispatch?.('interactive-activity-retry', { activityId: config.activityId, type: 'matching' });
            this.scheduleConnectorRefresh();
            return this;
        },

        loadPayload(payload = {}, status = this.status, previewToken = undefined) {
            this.leftItems = Array.isArray(payload.left_items) ? payload.left_items : [];
            this.rightItems = Array.isArray(payload.right_items) ? payload.right_items : [];
            this.matchedPairs = Array.isArray(payload.completed_matches) ? copy(payload.completed_matches) : [];
            this.pairResults = this.matchedPairs.map((pair) => ({ ...pair, is_correct: true, state: 'correct' }));
            this.answerChecked = false;
            this.status = status ?? this.status;
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
            this.pairResults = [];
            this.answerChecked = false;
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
