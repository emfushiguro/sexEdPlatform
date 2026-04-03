<section class="flex-1 flex flex-col bg-white" data-chat-conversation-panel x-data="{ composer: '' }">
    <header class="border-b border-gray-100 px-5 py-4">
        <h2
            class="text-sm font-semibold text-gray-900"
            x-text="$store.chat.activeConversation() ? $store.chat.conversationParticipantName($store.chat.activeConversation()) : 'Chat'"
        ></h2>
        <p
            class="text-xs text-gray-500"
            x-text="$store.chat.activeConversation() ? $store.chat.activeConversation().context_label : 'Select a conversation to begin.'"
        ></p>
    </header>

    <div class="flex-1 overflow-y-auto px-5 py-4 space-y-3 bg-gray-50/50" data-chat-message-stream>
        <template x-if="!$store.chat.activeConversationId">
            <div class="h-full min-h-[280px] flex items-center justify-center text-center px-6">
                <div>
                    <p class="text-sm font-semibold text-gray-700">No active conversation</p>
                    <p class="mt-1 text-xs text-gray-500">Select one from the left panel or start a new conversation.</p>
                </div>
            </div>
        </template>

        <template x-if="$store.chat.activeConversationId">
            <div class="space-y-3">
                <template x-if="$store.chat.activeMessages().length < 1">
                    <div class="rounded-xl border border-dashed border-gray-300 bg-white px-4 py-3 text-xs text-gray-500">
                        No messages yet. Start the conversation.
                    </div>
                </template>

                <template x-for="message in $store.chat.activeMessages()" :key="message.id">
                    <div class="flex" :class="$store.chat.isOwnMessage(message) ? 'justify-end' : 'justify-start'">
                        <div
                            class="max-w-[80%] rounded-2xl px-3 py-2 shadow-sm"
                            :class="$store.chat.isOwnMessage(message)
                                ? 'bg-blue-600 text-white rounded-br-md'
                                : 'bg-white text-gray-900 border border-gray-200 rounded-bl-md'"
                        >
                            <p class="text-xs leading-relaxed" x-text="message.message_body"></p>
                            <div class="mt-1 flex items-center justify-end gap-2">
                                <p
                                    class="text-[10px]"
                                    :class="$store.chat.isOwnMessage(message) ? 'text-blue-100' : 'text-gray-400'"
                                    x-text="$store.chat.formatMessageTime(message.created_at)"
                                ></p>
                                <button
                                    type="button"
                                    class="text-[10px] font-semibold underline"
                                    x-show="message.failed"
                                    @click="$store.chat.retryFailedMessage($store.chat.activeConversationId, message.id)"
                                >
                                    Retry
                                </button>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </template>
    </div>

    <footer class="border-t border-gray-100 px-5 py-4">
        <template x-if="$store.chat.activeConversationId">
            <form
                class="flex items-center gap-3"
                data-chat-composer
                @submit.prevent="if (!composer.trim()) { return; } await $store.chat.sendActiveMessage(composer); composer = '';"
            >
                <input
                    type="text"
                    x-model="composer"
                    :disabled="$store.chat.loading.send || !$store.chat.activeConversation()?.can_send"
                    :placeholder="$store.chat.activeConversation()?.can_send
                        ? 'Type your message...'
                        : 'Messaging is disabled in this conversation'"
                    class="flex-1 rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm text-gray-900 placeholder-gray-400 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20 disabled:cursor-not-allowed disabled:bg-gray-100"
                >
                <button
                    type="submit"
                    :disabled="$store.chat.loading.send || !$store.chat.activeConversation()?.can_send"
                    class="rounded-xl bg-brand-500 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-600 disabled:cursor-not-allowed disabled:bg-gray-400"
                >
                    Send
                </button>
            </form>
        </template>

        <template x-if="!$store.chat.activeConversationId">
            <div class="text-xs text-gray-500">Start a conversation to enable the message composer.</div>
        </template>
    </footer>
</section>
