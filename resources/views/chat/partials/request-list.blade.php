<section
    class="border-b border-gray-100 bg-gradient-to-r from-amber-50 via-white to-orange-50"
    data-chat-request-list
    x-show="$store.chat.currentUserRole === 'instructor'"
>
    <div class="px-4 py-3 flex items-center justify-between">
        <h2 class="text-sm font-semibold text-amber-800">Message Requests</h2>
        <span
            class="inline-flex items-center rounded-full bg-amber-100 px-2 py-0.5 text-xs font-semibold text-amber-700"
            x-text="($store.chat.requests || []).length"
        ></span>
    </div>

    <div class="max-h-72 overflow-y-auto space-y-2 px-3 pb-3">
        <template x-if="($store.chat.requests || []).length < 1">
            <div class="rounded-xl border border-amber-100 bg-white px-4 py-3 text-xs text-amber-700">
                No pending requests.
            </div>
        </template>

        <template x-for="request in $store.chat.requests" :key="request.id">
            <div class="rounded-xl border border-amber-100 bg-white p-3 shadow-sm">
                <div class="flex items-start gap-3">
                    <div class="relative h-10 w-10 shrink-0">
                        <template x-if="request.requester_avatar_url">
                            <img :src="request.requester_avatar_url" alt="Avatar" class="h-10 w-10 rounded-full border border-gray-200 object-cover">
                        </template>
                        <template x-if="!request.requester_avatar_url">
                            <div class="h-10 w-10 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center text-sm font-semibold">
                                <span x-text="(request.requester?.name || 'L').slice(0, 1).toUpperCase()"></span>
                            </div>
                        </template>
                        <span
                            class="absolute -bottom-0.5 -right-0.5 inline-flex h-3 w-3 rounded-full border-2 border-white"
                            :class="$store.chat.statusToneClass($store.chat.normalizeUserStatus(request.requester_status))"
                        ></span>
                    </div>

                    <div class="min-w-0 flex-1">
                        <div class="flex items-center justify-between gap-2">
                            <p class="truncate text-sm font-semibold text-gray-900" x-text="request.requester?.name || 'Learner'"></p>
                            <span class="text-[11px] text-gray-400" x-text="$store.chat.formatConversationTime(request.created_at)"></span>
                        </div>
                        <p class="mt-0.5 truncate text-[11px] font-medium text-amber-700" x-text="request.context_label || 'Direct Conversation'"></p>
                        <p class="mt-1 line-clamp-2 text-xs text-gray-600" x-text="request.initial_message || 'No preview available.'"></p>

                        <button
                            type="button"
                            class="mt-2 inline-flex items-center rounded-lg border border-amber-300 bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-800 hover:bg-amber-200"
                            x-show="request.accepted_conversation_id"
                            @click="$store.chat.selectConversation(request.accepted_conversation_id)"
                        >
                            Open Request
                        </button>
                    </div>
                </div>
            </div>
        </template>

        <p class="px-4 pb-3 text-xs font-medium text-rose-700" x-show="$store.chat.requestActionError" x-text="$store.chat.requestActionError"></p>
    </div>
</section>
