const defaultState = {
    currentUserId: null,
    currentUserRole: null,
    conversations: [],
    messagesByConversation: {},
    activeConversationId: null,
    unreadByConversation: {},
    requests: [],
    discovery: {
        role: null,
        supportAdmin: null,
        contacts: {
            learners: [],
            instructors: [],
        },
    },
    discoveryQuery: '',
    adminDiscoveryTab: 'learners',
    pendingOptimistic: {},
    notificationsEnabled: false,
    loading: {
        conversations: false,
        discovery: false,
        messages: false,
        send: false,
        requests: false,
    },
    subscribedConversationIds: {},
    requestChannelSubscribed: false,
};

const NOTIFICATION_PREFERENCE_KEY = 'chat.notifications.enabled';

function normalizeConversation(conversation) {
    return {
        id: conversation.id,
        participant_one_id: conversation.participant_one_id,
        participant_two_id: conversation.participant_two_id,
        conversation_type: conversation.conversation_type,
        status: conversation.status,
        context_key: conversation.context_key,
        context_label: conversation.context_label || 'Direct Conversation',
        last_message_at: conversation.last_message_at,
        latest_message_preview: conversation.latest_message_preview || '',
        participantOne: conversation.participant_one ?? null,
        participantTwo: conversation.participant_two ?? null,
        other_participant: conversation.other_participant ?? null,
        can_send: conversation.can_send !== false,
        unread_count: Number(conversation.unread_count || 0),
    };
}

function insertOrReplaceById(collection, item) {
    const index = collection.findIndex((entry) => entry.id === item.id);

    if (index === -1) {
        collection.push(item);
        return;
    }

    collection[index] = {
        ...collection[index],
        ...item,
    };
}

function buildStartPayload(input = {}) {
    const payload = {
        target_user_id: Number(input.target_user_id || 0),
        conversation_type: input.conversation_type,
    };

    if (!payload.target_user_id || !payload.conversation_type) {
        return null;
    }

    if (input.module_id) {
        payload.module_id = Number(input.module_id);
    }

    if (input.lesson_id) {
        payload.lesson_id = Number(input.lesson_id);
    }

    if (input.quiz_id) {
        payload.quiz_id = Number(input.quiz_id);
    }

    if (input.initial_message) {
        payload.initial_message = String(input.initial_message);
    }

    return payload;
}

document.addEventListener('alpine:init', () => {
    const Alpine = window.Alpine;

    Alpine.store('chat', {
        ...defaultState,

        async bootstrap(payload = {}) {
            this.currentUserId = Number(payload.currentUserId || 0);
            this.currentUserRole = payload.currentUserRole || null;
            this.adminDiscoveryTab = 'learners';

            this.hydrateNotificationPreference(payload.notificationsEnabled);

            await Promise.all([
                this.fetchConversations(),
                this.fetchDiscovery(),
                this.fetchRequests(),
            ]);

            this.subscribeRequestChannel();
            this.subscribeConversationChannels();

            const startPayload = buildStartPayload(payload.startContext || {});

            if (startPayload) {
                await this.startConversation(startPayload, true);
                return;
            }

            if (this.activeConversationId) {
                await this.selectConversation(this.activeConversationId);
                return;
            }

            if (this.conversations.length > 0) {
                await this.selectConversation(this.conversations[0].id);
            }
        },

        hydrateNotificationPreference(payloadValue = false) {
            let storedValue = null;

            try {
                storedValue = window.localStorage.getItem(NOTIFICATION_PREFERENCE_KEY);
            } catch (error) {
                storedValue = null;
            }

            if (storedValue === '1' || storedValue === '0') {
                this.notificationsEnabled = storedValue === '1';
                return;
            }

            this.notificationsEnabled = Boolean(payloadValue);
            this.persistNotificationPreference();
        },

        persistNotificationPreference() {
            try {
                window.localStorage.setItem(
                    NOTIFICATION_PREFERENCE_KEY,
                    this.notificationsEnabled ? '1' : '0'
                );
            } catch (error) {
                // Ignore persistence failures (private mode / blocked storage).
            }
        },

        setNotificationsEnabled(enabled) {
            this.notificationsEnabled = Boolean(enabled);
            this.persistNotificationPreference();

            if (
                this.notificationsEnabled &&
                typeof window !== 'undefined' &&
                'Notification' in window &&
                Notification.permission === 'default'
            ) {
                Notification.requestPermission();
            }
        },

        toggleNotificationsEnabled() {
            this.setNotificationsEnabled(!this.notificationsEnabled);
        },

        async fetchConversations() {
            this.loading.conversations = true;

            try {
                const response = await window.axios.get('/chat/conversations');
                this.setConversations(response.data.conversations || []);
            } finally {
                this.loading.conversations = false;
            }
        },

        async fetchDiscovery() {
            this.loading.discovery = true;

            try {
                const response = await window.axios.get('/chat/discovery', {
                    params: {
                        q: this.discoveryQuery || undefined,
                    },
                });

                this.discovery.role = response.data.role || this.currentUserRole;
                this.discovery.supportAdmin = response.data.support_admin || null;
                this.discovery.contacts = {
                    learners: response.data.contacts?.learners || [],
                    instructors: response.data.contacts?.instructors || [],
                };
            } finally {
                this.loading.discovery = false;
            }
        },

        async fetchRequests() {
            if (!['instructor', 'learner'].includes(String(this.currentUserRole || ''))) {
                this.requests = [];
                return;
            }

            this.loading.requests = true;

            try {
                const response = await window.axios.get('/chat/requests');
                this.requests = response.data.requests || [];
            } finally {
                this.loading.requests = false;
            }
        },

        setConversations(conversations) {
            this.conversations = conversations.map(normalizeConversation);

            const nextUnread = {};
            this.conversations.forEach((conversation) => {
                nextUnread[conversation.id] = Number(conversation.unread_count || 0);
            });

            this.unreadByConversation = nextUnread;

            if (this.activeConversationId && !this.findConversationById(this.activeConversationId)) {
                this.activeConversationId = null;
            }

            this.sortConversationsByLatest();
            this.syncUnreadBadges();
        },

        sortConversationsByLatest() {
            this.conversations.sort((a, b) => {
                const aTime = a.last_message_at ? new Date(a.last_message_at).getTime() : 0;
                const bTime = b.last_message_at ? new Date(b.last_message_at).getTime() : 0;

                if (aTime === bTime) {
                    return Number(b.id) - Number(a.id);
                }

                return bTime - aTime;
            });
        },

        findConversationById(conversationId) {
            return this.conversations.find((entry) => Number(entry.id) === Number(conversationId)) || null;
        },

        activeConversation() {
            return this.findConversationById(this.activeConversationId);
        },

        activeMessages() {
            if (!this.activeConversationId) {
                return [];
            }

            return this.messagesByConversation[this.activeConversationId] || [];
        },

        filteredConversations() {
            const query = String(this.discoveryQuery || '').toLowerCase().trim();

            if (!query) {
                return this.conversations;
            }

            return this.conversations.filter((conversation) => {
                const participant = this.conversationParticipantName(conversation).toLowerCase();
                const label = String(conversation.context_label || '').toLowerCase();
                const preview = String(conversation.latest_message_preview || '').toLowerCase();

                return participant.includes(query) || label.includes(query) || preview.includes(query);
            });
        },

        filteredDiscoveryContacts() {
            if (this.currentUserRole === 'admin') {
                if (this.adminDiscoveryTab === 'instructors') {
                    return this.discovery.contacts.instructors || [];
                }

                return this.discovery.contacts.learners || [];
            }

            if (this.currentUserRole === 'instructor') {
                return this.discovery.contacts.learners || [];
            }

            return this.discovery.contacts.instructors || [];
        },

        conversationParticipantName(conversation) {
            if (conversation.conversation_type === 'admin_support_chat') {
                return 'Platform Support';
            }

            return conversation.other_participant?.name || 'Conversation';
        },

        formatConversationTime(timestamp) {
            if (!timestamp) {
                return 'No activity';
            }

            const date = new Date(timestamp);
            const now = new Date();
            const sameDay = date.toDateString() === now.toDateString();

            if (sameDay) {
                return date.toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' });
            }

            return date.toLocaleDateString([], { month: 'short', day: 'numeric' });
        },

        formatMessageTime(timestamp) {
            if (!timestamp) {
                return '';
            }

            const date = new Date(timestamp);
            return date.toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' });
        },

        isOwnMessage(message) {
            return Number(message.sender_id) === Number(this.currentUserId);
        },

        async selectConversation(conversationId) {
            this.activeConversationId = Number(conversationId);

            if (this.unreadByConversation[this.activeConversationId] !== undefined) {
                this.unreadByConversation[this.activeConversationId] = 0;
            }

            this.syncUnreadBadges();
            this.subscribeConversationChannel(this.activeConversationId);

            await Promise.all([
                this.loadMessages(this.activeConversationId),
                this.markConversationRead(this.activeConversationId),
            ]);
        },

        async loadMessages(conversationId) {
            this.loading.messages = true;

            try {
                const response = await window.axios.get(`/chat/conversations/${conversationId}/messages`);
                this.messagesByConversation[conversationId] = response.data.messages || [];
            } finally {
                this.loading.messages = false;
            }
        },

        async markConversationRead(conversationId) {
            try {
                await window.axios.post(`/chat/conversations/${conversationId}/read`);
            } catch (error) {
                // Read-state sync failures should not block UX actions.
            }
        },

        async startConversation(payload, autoSelect = true) {
            const normalizedPayload = buildStartPayload(payload);
            if (!normalizedPayload) {
                return null;
            }

            const response = await window.axios.post('/chat/conversations/start', normalizedPayload);

            if (response.status === 202) {
                this.upsertRequest(response.data.message_request);
                await this.fetchRequests();
                return { requires_request: true, message_request: response.data.message_request };
            }

            await this.fetchConversations();

            const conversation = response.data.conversation || null;
            if (autoSelect && conversation?.id) {
                await this.selectConversation(conversation.id);
            }

            return { requires_request: false, conversation };
        },

        async startContactConversation(contact) {
            if (!contact?.id) {
                return;
            }

            const isSupport = Number(contact.id) === Number(this.discovery.supportAdmin?.id);
            const conversationType = isSupport ? 'admin_support_chat' : 'direct';

            await this.startConversation({
                target_user_id: Number(contact.id),
                conversation_type: conversationType,
            }, true);
        },

        appendMessage(message) {
            const conversationId = Number(message.conversation_id);
            if (!this.messagesByConversation[conversationId]) {
                this.messagesByConversation[conversationId] = [];
            }

            const existingIndex = this.messagesByConversation[conversationId]
                .findIndex((entry) => entry.id === message.id);

            if (existingIndex !== -1) {
                this.messagesByConversation[conversationId][existingIndex] = message;
                return;
            }

            this.messagesByConversation[conversationId].push(message);
        },

        mergeBackfillMessages(conversationId, messages = []) {
            if (!this.messagesByConversation[conversationId]) {
                this.messagesByConversation[conversationId] = [];
            }

            messages.forEach((message) => {
                this.appendMessage(message);
            });
        },

        updateConversationFromMessage(payload) {
            const conversation = this.findConversationById(payload.conversation_id);

            if (!conversation) {
                this.fetchConversations();
                return;
            }

            conversation.last_message_at = payload.created_at || new Date().toISOString();
            conversation.latest_message_preview = payload.message_body || '';

            this.sortConversationsByLatest();
        },

        shouldSuppressBrowserNotification(payload) {
            if (this.activeConversationId !== payload.conversation_id) {
                return false;
            }

            return document.visibilityState === 'visible' && document.hasFocus();
        },

        maybeShowBrowserNotification(payload) {
            if (!this.notificationsEnabled) {
                return;
            }

            if (this.shouldSuppressBrowserNotification(payload)) {
                return;
            }

            if (typeof window === 'undefined' || !('Notification' in window)) {
                return;
            }

            if (Notification.permission !== 'granted') {
                return;
            }

            const body = (payload.message_body || 'New message').slice(0, 140);
            new Notification('New chat message', {
                body,
                tag: `chat-conversation-${payload.conversation_id}`,
            });
        },

        applyMessageSentEvent(payload) {
            this.appendMessage(payload);
            this.updateConversationFromMessage(payload);
            this.maybeShowBrowserNotification(payload);

            if (this.activeConversationId !== payload.conversation_id) {
                this.unreadByConversation[payload.conversation_id] =
                    (this.unreadByConversation[payload.conversation_id] || 0) + 1;
            }

            this.syncUnreadBadges();
        },

        async sendActiveMessage(messageBody) {
            const body = String(messageBody || '').trim();
            if (!body || !this.activeConversationId) {
                return null;
            }

            this.loading.send = true;

            const optimisticId = this.optimisticSend(this.activeConversationId, body);

            try {
                const response = await window.axios.post(
                    `/chat/conversations/${this.activeConversationId}/messages`,
                    { message_body: body }
                );

                this.reconcileOptimistic(optimisticId, response.data.message);
                this.updateConversationFromMessage(response.data.message);
                await this.markConversationRead(this.activeConversationId);

                return response.data.message;
            } catch (error) {
                this.markOptimisticFailed(optimisticId);
                throw error;
            } finally {
                this.loading.send = false;
            }
        },

        upsertRequest(requestPayload) {
            insertOrReplaceById(this.requests, requestPayload);
        },

        resolveRequest(payload) {
            const match = this.requests.find((entry) => entry.id === payload.id);

            if (!match) {
                return;
            }

            match.status = payload.status;
            match.accepted_conversation_id = payload.accepted_conversation_id || null;
        },

        async acceptRequest(requestId) {
            const response = await window.axios.post(`/chat/requests/${requestId}/accept`);
            this.resolveRequest(response.data.message_request);
            await this.fetchConversations();

            const acceptedConversationId = response.data.conversation?.id;
            if (acceptedConversationId) {
                await this.selectConversation(acceptedConversationId);
            }
        },

        async declineRequest(requestId) {
            const response = await window.axios.post(`/chat/requests/${requestId}/decline`);
            this.resolveRequest(response.data.message_request);
            await this.fetchRequests();
        },

        optimisticSend(conversationId, messageBody) {
            const optimisticId = `optimistic-${Date.now()}`;
            const optimisticMessage = {
                id: optimisticId,
                conversation_id: Number(conversationId),
                message_body: messageBody,
                sender_id: this.currentUserId,
                sender_name: 'You',
                created_at: new Date().toISOString(),
                optimistic: true,
                failed: false,
            };

            this.pendingOptimistic[optimisticId] = optimisticMessage;
            this.appendMessage(optimisticMessage);

            return optimisticId;
        },

        reconcileOptimistic(optimisticId, authoritativeMessage) {
            const pending = this.pendingOptimistic[optimisticId];
            if (!pending) {
                return;
            }

            const list = this.messagesByConversation[pending.conversation_id] || [];
            const index = list.findIndex((entry) => entry.id === optimisticId);

            if (index !== -1) {
                list[index] = authoritativeMessage;
            } else {
                this.appendMessage(authoritativeMessage);
            }

            delete this.pendingOptimistic[optimisticId];
        },

        markOptimisticFailed(optimisticId) {
            const pending = this.pendingOptimistic[optimisticId];
            if (!pending) {
                return;
            }

            pending.failed = true;
        },

        async retryFailedMessage(conversationId, optimisticId) {
            const pending = this.pendingOptimistic[optimisticId];

            if (!pending) {
                return null;
            }

            const response = await window.axios.post(`/chat/conversations/${conversationId}/messages`, {
                message_body: pending.message_body,
                retry_of: optimisticId,
            });

            this.reconcileOptimistic(optimisticId, response.data.message);

            return response.data.message;
        },

        subscribeConversationChannels() {
            this.conversations.forEach((conversation) => {
                this.subscribeConversationChannel(conversation.id);
            });
        },

        subscribeConversationChannel(conversationId) {
            const id = Number(conversationId);
            if (!id || this.subscribedConversationIds[id]) {
                return;
            }

            const channel = window.chatEcho?.subscribeConversation(id, {
                onMessageSent: (payload) => this.applyMessageSentEvent(payload),
            });

            if (channel) {
                this.subscribedConversationIds[id] = true;
            }
        },

        subscribeRequestChannel() {
            if (this.requestChannelSubscribed || !this.currentUserId) {
                return;
            }

            const channel = window.chatEcho?.subscribeRequests(this.currentUserId, {
                onRequestCreated: (payload) => {
                    this.upsertRequest(payload);
                    this.fetchRequests();
                },
                onRequestResolved: (payload) => {
                    this.resolveRequest(payload);

                    if (payload.accepted_conversation_id) {
                        this.fetchConversations();
                    }
                },
            });

            if (channel) {
                this.requestChannelSubscribed = true;
            }
        },

        totalUnreadCount() {
            return Object.values(this.unreadByConversation).reduce((sum, value) => {
                const count = Number(value) || 0;
                return sum + Math.max(0, count);
            }, 0);
        },

        syncUnreadBadges() {
            const totalUnread = this.totalUnreadCount();

            document.querySelectorAll('[data-chat-unread-badge]').forEach((badge) => {
                badge.textContent = totalUnread > 99 ? '99+' : String(totalUnread);
                badge.hidden = totalUnread < 1;
            });

            window.dispatchEvent(
                new CustomEvent('chat:unread-updated', {
                    detail: { totalUnread },
                })
            );
        },

        async debouncedDiscoveryRefresh() {
            clearTimeout(this._discoveryTimer);
            this._discoveryTimer = setTimeout(() => {
                this.fetchDiscovery();
            }, 250);
        },
    });
});
