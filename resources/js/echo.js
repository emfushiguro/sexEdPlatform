import Echo from 'laravel-echo';

import Pusher from 'pusher-js';
window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: import.meta.env.VITE_REVERB_PORT ?? 80,
    wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
    forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
    enabledTransports: ['ws', 'wss'],
});

window.chatEcho = {
    subscribeConversation(conversationId, handlers = {}) {
        if (!window.Echo || !conversationId) {
            return null;
        }

        const channel = window.Echo.private(`chat.conversation.${conversationId}`);

        channel.listen('.chat.message.sent', (payload) => {
            if (typeof handlers.onMessageSent === 'function') {
                handlers.onMessageSent(payload);
            }
        });

        return channel;
    },

    subscribeRequests(userId, handlers = {}) {
        if (!window.Echo || !userId) {
            return null;
        }

        const channel = window.Echo.private(`chat.requests.user.${userId}`);

        channel.listen('.chat.request.created', (payload) => {
            if (typeof handlers.onRequestCreated === 'function') {
                handlers.onRequestCreated(payload);
            }
        });

        channel.listen('.chat.request.resolved', (payload) => {
            if (typeof handlers.onRequestResolved === 'function') {
                handlers.onRequestResolved(payload);
            }
        });

        return channel;
    },
};
