import Echo from 'laravel-echo';

import Pusher from 'pusher-js';
window.Pusher = Pusher;

const reverbKey = String(import.meta.env.VITE_REVERB_APP_KEY ?? '').trim();
const reverbHost = String(import.meta.env.VITE_REVERB_HOST ?? '').trim();
const reverbPort = Number(import.meta.env.VITE_REVERB_PORT ?? 0);
const configuredScheme = String(import.meta.env.VITE_REVERB_SCHEME ?? '').trim().toLowerCase();
const resolvedScheme = configuredScheme === 'http' || configuredScheme === 'https'
    ? configuredScheme
    : (window.location.protocol === 'https:' ? 'https' : 'http');
const forceTLS = resolvedScheme === 'https';
const realtimeEnabled = reverbKey !== '' && reverbHost !== '' && reverbPort > 0;

window.Echo = null;

if (realtimeEnabled) {
    const echo = new Echo({
        broadcaster: 'reverb',
        key: reverbKey,
        wsHost: reverbHost,
        wsPort: reverbPort,
        wssPort: reverbPort,
        forceTLS,
        enabledTransports: forceTLS ? ['wss'] : ['ws'],
    });

    const isLocalReverb = reverbHost === 'localhost' || reverbHost === '127.0.0.1';
    const pusherConnection = echo.connector?.pusher?.connection;

    if (isLocalReverb && pusherConnection && typeof pusherConnection.bind === 'function') {
        pusherConnection.bind('error', () => {
            // Prevent repeated reconnect attempts and console spam when local Reverb is down.
            echo.disconnect();
        });
    }

    window.Echo = echo;
}

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

        channel.listen('.chat.message.updated', (payload) => {
            if (typeof handlers.onMessageUpdated === 'function') {
                handlers.onMessageUpdated(payload);
            }
        });

        channel.listenForWhisper('typing', (payload) => {
            if (typeof handlers.onTyping === 'function') {
                handlers.onTyping(payload);
            }
        });

        return channel;
    },

    whisperTyping(conversationId, payload = {}) {
        if (!window.Echo || !conversationId) {
            return;
        }

        const channel = window.Echo.private(`chat.conversation.${conversationId}`);
        channel.whisper('typing', payload);
    },

    subscribePresence(handlers = {}) {
        if (!window.Echo) {
            return null;
        }

        const channel = window.Echo.join('chat.presence');

        channel.here((users) => {
            if (typeof handlers.onHere === 'function') {
                handlers.onHere(users || []);
            }
        });

        channel.joining((user) => {
            if (typeof handlers.onJoining === 'function') {
                handlers.onJoining(user);
            }
        });

        channel.leaving((user) => {
            if (typeof handlers.onLeaving === 'function') {
                handlers.onLeaving(user);
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
