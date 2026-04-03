<section
    class="border-b border-gray-100 bg-amber-50/60"
    data-chat-request-list
    x-show="['instructor', 'learner'].includes($store.chat.currentUserRole)"
>
    <div class="px-4 py-3 flex items-center justify-between">
        <h2 class="text-sm font-semibold text-amber-800">Message Requests</h2>
        <span
            class="inline-flex items-center rounded-full bg-amber-100 px-2 py-0.5 text-xs font-semibold text-amber-700"
            x-text="($store.chat.requests || []).length"
        ></span>
    </div>

    <div class="max-h-56 overflow-y-auto divide-y divide-amber-100">
        <template x-if="($store.chat.requests || []).length < 1">
            <div class="px-4 pb-3 text-xs text-amber-700">
                No pending requests.
            </div>
        </template>

        <template x-for="request in $store.chat.requests" :key="request.id">
            <div class="px-4 py-3">
                <p
                    class="text-xs font-semibold text-amber-900"
                    x-text="$store.chat.currentUserRole === 'instructor'
                        ? `${request.requester?.name || 'Learner'} wants to message you`
                        : `Waiting for ${request.instructor?.name || 'instructor'} to approve`"
                ></p>
                <p
                    class="mt-1 text-xs text-amber-700"
                    x-show="request.initial_message"
                    x-text="request.initial_message"
                ></p>

                <div class="mt-2 flex items-center gap-2" x-show="$store.chat.currentUserRole === 'instructor'">
                    <button
                        type="button"
                        class="inline-flex items-center rounded-lg bg-emerald-600 px-2.5 py-1 text-xs font-semibold text-white hover:bg-emerald-700"
                        @click="$store.chat.acceptRequest(request.id)"
                    >
                        Accept
                    </button>
                    <button
                        type="button"
                        class="inline-flex items-center rounded-lg bg-rose-600 px-2.5 py-1 text-xs font-semibold text-white hover:bg-rose-700"
                        @click="$store.chat.declineRequest(request.id)"
                    >
                        Decline
                    </button>
                </div>
            </div>
        </template>
    </div>
</section>
