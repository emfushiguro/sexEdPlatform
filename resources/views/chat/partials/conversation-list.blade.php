<section
    class="w-full lg:w-80 xl:w-96 border-r border-gray-200 bg-white"
    data-chat-conversation-list
    x-show="$store.chat.shouldShowSidebar()"
    x-transition.opacity
>
    <div class="p-4 border-b border-gray-100 space-y-3">
        <label for="chat-search" class="sr-only">Search conversations</label>
        <input
            id="chat-search"
            type="text"
            placeholder="Search people or conversations..."
            x-model="$store.chat.discoveryQuery"
            @input="$store.chat.debouncedDiscoveryRefresh()"
            class="w-full rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm text-gray-900 placeholder-gray-400 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/20"
        >

        <template x-if="$store.chat.currentUserRole !== 'admin' && $store.chat.discovery.supportAdmin">
            <button
                type="button"
                class="inline-flex w-full items-center justify-center rounded-lg bg-gradient-to-r from-purple-700 to-pink-500 px-4 py-2.5 text-xs font-semibold text-white shadow-md hover:opacity-90 transition-transform active:scale-[0.98]"
                @click="$store.chat.startConversation({
                    target_user_id: $store.chat.discovery.supportAdmin.id,
                    conversation_type: 'admin_support_chat'
                }, true)"
            >
                Contact Platform Support
            </button>
        </template>
    </div>

    <div class="max-h-[34vh] overflow-y-auto border-b border-gray-100">
        <template x-if="$store.chat.filteredConversations().length < 1">
            <div class="px-4 py-4 text-xs text-gray-500">No conversations yet. Start one from contacts below.</div>
        </template>

        <template x-for="group in $store.chat.conversationGroups()" :key="group.key">
            <div>
                <p class="px-4 pt-3 pb-2 text-[11px] font-semibold uppercase tracking-[0.14em] text-gray-500" x-text="group.label"></p>

                <template x-for="conversation in group.items" :key="conversation.id">
                    <button
                        type="button"
                        class="flex w-full items-start gap-3 px-4 py-3 text-left transition-all duration-200 border-l-4"
                        :class="$store.chat.activeConversationId === conversation.id ? 'bg-purple-50/50 border-purple-600' : 'border-transparent hover:bg-gray-50 hover:border-gray-300'"
                        @click="$store.chat.selectConversation(conversation.id)"
                    >
                        <div class="relative h-10 w-10 flex-shrink-0">
                            <template x-if="$store.chat.conversationParticipantAvatar(conversation)">
                                <img
                                    :src="$store.chat.conversationParticipantAvatar(conversation)"
                                    alt="Avatar"
                                    class="h-10 w-10 rounded-full object-cover border border-gray-200"
                                >
                            </template>
                            <template x-if="!$store.chat.conversationParticipantAvatar(conversation)">
                                <div class="h-10 w-10 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center font-semibold">
                                    <span x-text="$store.chat.conversationParticipantName(conversation).slice(0, 1).toUpperCase()"></span>
                                </div>
                            </template>
                            <span
                                class="absolute -bottom-0.5 -right-0.5 inline-flex h-3 w-3 rounded-full border-2 border-white"
                                :class="$store.chat.statusToneClass($store.chat.conversationParticipantStatus(conversation))"
                            ></span>
                        </div>

                        <div class="min-w-0 flex-1">
                            <div class="flex items-center justify-between gap-2">
                                <p class="text-sm font-semibold text-gray-900 truncate" x-text="$store.chat.conversationParticipantName(conversation)"></p>
                                <span class="text-[11px] text-gray-400" x-text="$store.chat.formatConversationTime(conversation.last_message_at)"></span>
                            </div>
                            <p
                                class="mt-0.5 text-[11px] text-gray-500 truncate"
                                x-text="`${(conversation.other_participant?.role || 'member').replace(/^./, (c) => c.toUpperCase())} • ${$store.chat.statusLabel($store.chat.conversationParticipantStatus(conversation))}`"
                            ></p>
                            <p class="mt-0.5 text-[11px] font-medium text-gray-500 truncate" x-text="conversation.context_label"></p>
                            <p class="mt-0.5 text-xs text-gray-500 truncate" x-text="conversation.latest_message_preview || 'No messages yet.'"></p>
                        </div>

                        <span
                            class="inline-flex min-w-5 items-center justify-center rounded-full bg-gradient-to-r from-purple-700 to-pink-500 px-1.5 py-0.5 text-[10px] font-bold text-white shadow-sm"
                            x-show="($store.chat.unreadByConversation[conversation.id] || 0) > 0"
                            x-text="$store.chat.unreadByConversation[conversation.id] > 99 ? '99+' : $store.chat.unreadByConversation[conversation.id]"
                        ></span>
                    </button>
                </template>
            </div>
        </template>

        <div class="px-4 py-3" x-show="$store.chat.conversationPagination.hasMore">
            <button
                type="button"
                class="inline-flex w-full items-center justify-center rounded-lg border border-gray-200 bg-white px-3 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-60"
                :disabled="$store.chat.conversationPagination.loadingMore"
                @click="$store.chat.loadMoreConversations()"
            >
                <span x-show="!$store.chat.conversationPagination.loadingMore">Load older conversations</span>
                <span x-show="$store.chat.conversationPagination.loadingMore">Loading...</span>
            </button>
        </div>
    </div>

    <div class="max-h-[30vh] overflow-y-auto">
        <div class="px-4 pt-3 pb-2 flex items-center justify-between gap-2">
            <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-gray-500">Start New Conversation</p>
            <div class="inline-flex rounded-lg border border-gray-200 bg-gray-50 p-0.5" x-show="$store.chat.currentUserRole === 'admin'">
                <button
                    type="button"
                    class="rounded-md px-2 py-1 text-[11px] font-semibold"
                    :class="$store.chat.adminDiscoveryTab === 'learners' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500'"
                    @click="$store.chat.adminDiscoveryTab = 'learners'"
                >
                    Learners
                </button>
                <button
                    type="button"
                    class="rounded-md px-2 py-1 text-[11px] font-semibold"
                    :class="$store.chat.adminDiscoveryTab === 'instructors' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500'"
                    @click="$store.chat.adminDiscoveryTab = 'instructors'"
                >
                    Instructors
                </button>
            </div>
        </div>

        <template x-if="$store.chat.filteredDiscoveryContacts().length < 1">
            <div class="px-4 pb-4 text-xs text-gray-500">No contacts matched your search.</div>
        </template>

        <template x-for="contact in $store.chat.filteredDiscoveryContacts()" :key="`contact-${contact.id}`">
            <button
                type="button"
                class="flex w-full items-start gap-3 px-4 py-3 text-left transition-colors hover:bg-gray-50"
                @click="$store.chat.startContactConversation(contact)"
            >
                <div class="relative h-9 w-9 flex-shrink-0">
                    <template x-if="contact.avatar_url">
                        <img :src="contact.avatar_url" alt="Avatar" class="h-9 w-9 rounded-full object-cover border border-gray-200">
                    </template>
                    <template x-if="!contact.avatar_url">
                        <div class="h-9 w-9 rounded-full bg-gray-100 text-gray-700 flex items-center justify-center text-xs font-semibold">
                            <span x-text="(contact.name || 'U').slice(0, 1).toUpperCase()"></span>
                        </div>
                    </template>
                    <span
                        class="absolute -bottom-0.5 -right-0.5 inline-flex h-3 w-3 rounded-full border-2 border-white"
                        :class="$store.chat.statusToneClass($store.chat.contactStatus(contact))"
                    ></span>
                </div>
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-semibold text-gray-900 truncate" x-text="contact.name"></p>
                    <p class="text-xs text-gray-500 truncate" x-text="contact.subtitle || contact.role"></p>
                </div>
            </button>
        </template>
    </div>
</section>
