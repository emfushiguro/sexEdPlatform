document.addEventListener('alpine:init', () => {
    const POPUP_STATE_STORAGE_PREFIX = 'chat.popup.windows';

    window.globalPopupChat = (payload = {}) => ({
        openWindows: [],
        popupStateKey: null,

        async init() {
            await this.bootstrapStore();
            this.popupStateKey = this.resolvePopupStateKey();
            await this.restoreWindowsFromStorage();

            window.addEventListener('open-global-chat', async (event) => {
                await this.openChat(event.detail || {});
            });

            window.addEventListener('beforeunload', () => {
                this.persistWindowsState();
            });

            this._readSyncInterval = setInterval(() => {
                this.syncOpenWindowReadState();
            }, 1500);
        },

        resolvePopupStateKey() {
            const userId = Number(payload.currentUserId || 0);

            if (!userId) {
                return null;
            }

            return `${POPUP_STATE_STORAGE_PREFIX}.${userId}`;
        },

        async bootstrapStore() {
            await this.$store.chat.bootstrapGlobal({
                currentUserId: payload.currentUserId,
                currentUserName: payload.currentUserName,
                currentUserRole: payload.currentUserRole,
                messageMutationWindowMinutes: payload.messageMutationWindowMinutes,
                notificationsEnabled: false,
            });
        },

        async openChat(detail = {}) {
            await this.bootstrapStore();

            const conversationId = await this.resolveConversationId(detail);

            if (!conversationId) {
                return;
            }

            await this.openWindowByConversationId(conversationId, detail, {
                markRead: true,
                persist: true,
            });
        },

        async openWindowByConversationId(conversationId, detail = {}, options = {}) {
            const config = {
                markRead: options.markRead !== false,
                persist: options.persist !== false,
            };

            await this.$store.chat.ensureConversationLoaded(conversationId);
            this.$store.chat.subscribeConversationChannel(conversationId);
            await this.$store.chat.loadMessages(conversationId, true);

            const existing = this.openWindows.find((windowItem) => Number(windowItem.id) === Number(conversationId));
            if (existing) {
                existing.isMinimized = false;

                if (config.markRead) {
                    await this.markConversationRead(conversationId);
                }

                this.scrollToBottom(conversationId);

                if (config.persist) {
                    this.persistWindowsState();
                }

                return;
            }

            if (this.openWindows.length >= 3) {
                this.openWindows.shift();
            }

            this.openWindows.push({
                id: Number(conversationId),
                composer: '',
                queuedAttachments: [],
                isMinimized: false,
                contextLabel: detail.context_label || '',
                fallbackName: detail.name || 'Conversation',
                fallbackAvatar: detail.avatar || null,
                fallbackConversationType: detail.conversation_type || 'direct',
                sending: false,
            });

            if (config.markRead) {
                await this.markConversationRead(conversationId);
            }

            this.scrollToBottom(conversationId);

            if (config.persist) {
                this.persistWindowsState();
            }
        },

        restoreWindowsPayload() {
            if (!this.popupStateKey) {
                return [];
            }

            try {
                const serialized = window.localStorage.getItem(this.popupStateKey);

                if (!serialized) {
                    return [];
                }

                const payload = JSON.parse(serialized);

                if (!Array.isArray(payload?.windows)) {
                    return [];
                }

                return payload.windows
                    .map((entry, index) => ({
                        conversation_id: Number(entry.conversation_id || 0),
                        is_minimized: Boolean(entry.is_minimized),
                        context_label: entry.context_label || '',
                        name: entry.name || 'Conversation',
                        avatar: entry.avatar || null,
                        conversation_type: entry.conversation_type || 'direct',
                        position: Number(entry.position ?? index),
                    }))
                    .filter((entry) => entry.conversation_id > 0)
                    .sort((a, b) => a.position - b.position)
                    .slice(0, 3);
            } catch (error) {
                return [];
            }
        },

        async restoreWindowsFromStorage() {
            const restoredWindows = this.restoreWindowsPayload();

            if (restoredWindows.length < 1) {
                return;
            }

            for (const windowConfig of restoredWindows) {
                await this.openWindowByConversationId(windowConfig.conversation_id, {
                    context_label: windowConfig.context_label,
                    name: windowConfig.name,
                    avatar: windowConfig.avatar,
                    conversation_type: windowConfig.conversation_type,
                }, {
                    markRead: false,
                    persist: false,
                });

                const restored = this.openWindows.find((entry) => Number(entry.id) === Number(windowConfig.conversation_id));

                if (restored) {
                    restored.isMinimized = Boolean(windowConfig.is_minimized);
                }
            }

            this.persistWindowsState();
        },

        persistWindowsState() {
            if (!this.popupStateKey) {
                return;
            }

            const windows = this.openWindows.slice(0, 3).map((windowItem, index) => ({
                conversation_id: Number(windowItem.id),
                is_minimized: Boolean(windowItem.isMinimized),
                context_label: windowItem.contextLabel || '',
                name: windowItem.fallbackName || '',
                avatar: windowItem.fallbackAvatar || null,
                conversation_type: windowItem.fallbackConversationType || 'direct',
                position: index,
            }));

            try {
                if (windows.length < 1) {
                    window.localStorage.removeItem(this.popupStateKey);
                    return;
                }

                window.localStorage.setItem(this.popupStateKey, JSON.stringify({ windows }));
            } catch (error) {
                // Ignore storage write failures.
            }
        },

        async resolveConversationId(detail = {}) {
            const explicitConversationId = Number(detail.conversation_id || 0);

            if (explicitConversationId > 0) {
                return explicitConversationId;
            }

            const startPayload = {
                target_user_id: Number(detail.target_user_id || 0),
                conversation_type: detail.conversation_type || 'direct',
                module_id: detail.module_id,
                lesson_id: detail.lesson_id,
                lesson_topic_id: detail.lesson_topic_id,
                quiz_id: detail.quiz_id,
                initial_message: detail.initial_message,
            };

            if (!startPayload.target_user_id) {
                return null;
            }

            const startResult = await this.$store.chat.startConversation(startPayload, false);
            return Number(startResult?.conversation?.id || 0) || null;
        },

        conversationFor(windowItem) {
            return this.$store.chat.findConversationById(windowItem.id);
        },

        windowTitle(windowItem) {
            const conversation = this.conversationFor(windowItem);

            if (!conversation) {
                return windowItem.fallbackName || 'Conversation';
            }

            return this.$store.chat.conversationParticipantName(conversation);
        },

        windowAvatar(windowItem) {
            const conversation = this.conversationFor(windowItem);

            if (!conversation) {
                return windowItem.fallbackAvatar;
            }

            return this.$store.chat.conversationParticipantAvatar(conversation) || windowItem.fallbackAvatar;
        },

        windowStatus(windowItem) {
            const conversation = this.conversationFor(windowItem);

            if (!conversation) {
                return 'offline';
            }

            return this.$store.chat.conversationParticipantStatus(conversation);
        },

        windowContext(windowItem) {
            const conversation = this.conversationFor(windowItem);

            if (!conversation) {
                return windowItem.contextLabel || '';
            }

            return conversation.context_label || windowItem.contextLabel || '';
        },

        messagesFor(windowItem) {
            return this.$store.chat.messagesByConversation[windowItem.id] || [];
        },

        messageWindowState(windowItem) {
            return this.$store.chat.messageWindow(windowItem.id);
        },

        typingLabel(windowItem) {
            return this.$store.chat.typingLabelForConversation(windowItem.id);
        },

        unreadCount(windowItem) {
            return Number(this.$store.chat.unreadByConversation[windowItem.id] || 0);
        },

        canSend(windowItem) {
            return this.$store.chat.canSendToConversation(windowItem.id);
        },

        isPending(windowItem) {
            const conversation = this.conversationFor(windowItem);
            return conversation?.status === 'pending_request';
        },

        isDeclined(windowItem) {
            const conversation = this.conversationFor(windowItem);
            return conversation?.status === 'declined';
        },

        pendingRequest(windowItem) {
            const conversation = this.conversationFor(windowItem);

            if (!conversation || conversation.status !== 'pending_request') {
                return null;
            }

            return conversation.pending_request || null;
        },

        shouldShowPendingRequestActions(windowItem) {
            const request = this.pendingRequest(windowItem);

            return this.$store.chat.currentUserRole === 'instructor'
                && !!request?.id;
        },

        async acceptRequest(windowItem) {
            const request = this.pendingRequest(windowItem);

            if (!request?.id) {
                return;
            }

            await this.$store.chat.acceptRequest(request.id);
            await this.$store.chat.ensureConversationLoaded(windowItem.id);
            await this.markConversationRead(windowItem.id);
        },

        async declineRequest(windowItem) {
            const request = this.pendingRequest(windowItem);

            if (!request?.id) {
                return;
            }

            await this.$store.chat.declineRequest(request.id);
            await this.$store.chat.ensureConversationLoaded(windowItem.id);
            await this.markConversationRead(windowItem.id);
        },

        queueAttachments(windowItem, event) {
            const files = Array.from(event?.target?.files || []);

            files.forEach((file) => {
                windowItem.queuedAttachments.push({
                    id: `${Date.now()}-${Math.random().toString(16).slice(2)}`,
                    file,
                    name: file?.name || 'Attachment',
                    size: Number(file?.size || 0),
                });
            });

            if (event?.target) {
                event.target.value = null;
            }
        },

        removeAttachment(windowItem, attachmentId) {
            windowItem.queuedAttachments = (windowItem.queuedAttachments || []).filter((item) => item.id !== attachmentId);
        },

        openAttachmentPicker(windowItem) {
            const input = document.getElementById(`popup-attachment-${windowItem.id}`);

            if (input) {
                input.click();
            }
        },

        formatSize(bytes) {
            const value = Number(bytes || 0);

            if (value < 1024) {
                return `${value} B`;
            }

            if (value < 1024 * 1024) {
                return `${(value / 1024).toFixed(1)} KB`;
            }

            return `${(value / (1024 * 1024)).toFixed(1)} MB`;
        },

        async sendMessage(windowItem) {
            const body = String(windowItem.composer || '').trim();
            const files = (windowItem.queuedAttachments || []).map((item) => item.file).filter(Boolean);

            if ((!body && files.length < 1) || windowItem.sending) {
                return;
            }

            windowItem.sending = true;

            try {
                await this.$store.chat.sendMessageToConversation(windowItem.id, body, files);
                windowItem.composer = '';
                windowItem.queuedAttachments = [];
                this.scrollToBottom(windowItem.id);
                await this.markConversationRead(windowItem.id);
            } finally {
                windowItem.sending = false;
            }
        },

        closeWindow(windowId) {
            this.openWindows = this.openWindows.filter((windowItem) => Number(windowItem.id) !== Number(windowId));
            this.persistWindowsState();
        },

        async toggleMinimize(windowItem) {
            windowItem.isMinimized = !windowItem.isMinimized;

            if (!windowItem.isMinimized) {
                await this.markConversationRead(windowItem.id);
                this.scrollToBottom(windowItem.id);
            }

            this.persistWindowsState();
        },

        async openFullChat(windowItem) {
            window.location.href = `/chat/conversation/${windowItem.id}`;
        },

        async loadOlderMessages(windowItem) {
            await this.$store.chat.loadMessages(windowItem.id, false);
        },

        async handleMessageScroll(windowItem, event) {
            const target = event?.target;

            if (!target) {
                return;
            }

            if (target.scrollTop <= 72) {
                await this.loadOlderMessages(windowItem);
            }

            const distanceFromBottom = target.scrollHeight - target.scrollTop - target.clientHeight;
            if (distanceFromBottom <= 120) {
                await this.markConversationRead(windowItem.id);
            }
        },

        async markConversationRead(conversationId) {
            try {
                await this.$store.chat.markConversationRead(conversationId);
            } catch (error) {
                // Ignore read sync errors in popup context.
            }

            if (this.$store.chat.unreadByConversation[conversationId] !== undefined) {
                this.$store.chat.unreadByConversation[conversationId] = 0;
                this.$store.chat.syncUnreadBadges();
            }
        },

        scrollToBottom(conversationId) {
            requestAnimationFrame(() => {
                const element = this.$el.querySelector(`[data-popup-messages='${conversationId}']`);

                if (element) {
                    element.scrollTop = element.scrollHeight;
                }
            });
        },

        syncOpenWindowReadState() {
            this.openWindows.forEach((windowItem) => {
                if (!windowItem.isMinimized && this.unreadCount(windowItem) > 0) {
                    this.markConversationRead(windowItem.id);
                }
            });
        },
    });
});
