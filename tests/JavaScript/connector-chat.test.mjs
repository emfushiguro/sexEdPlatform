import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import vm from 'node:vm';

function chatStore() {
    let store;
    const window = { innerWidth: 1280, Alpine: { store: (_name, value) => { store = value; } } };
    const document = { addEventListener: (_name, callback) => callback() };
    vm.runInNewContext(readFileSync(new URL('../../resources/js/chat/store.js', import.meta.url), 'utf8'), { window, document });
    return { store, window, document };
}

test('Connector workspace scope survives global popup bootstrap and reaches conversation pagination', async () => {
    const { store, window } = chatStore();
    store.applyBootstrapPayload({ connectorId: 12 });
    store.applyBootstrapPayload({ currentUserId: 7 });
    const requests = [];
    window.axios = { get: async (_url, options) => {
        requests.push(options.params);
        const conversations = options.params.connector_id ? [{ id: 11, connector_id: 12 }] : [{ id: 99 }];
        return { data: { conversations, pagination: { has_more: false } } };
    } };
    store.subscribeConversationChannels = () => {};
    store.syncUnreadBadges = () => {};
    await store.fetchConversations();
    assert.equal(requests[0].connector_id, undefined);
    assert.equal(requests[1].connector_id, 12);
    assert.ok(store.findConversationById(99), 'personal popup conversation remains globally available');
    assert.deepEqual(Array.from(store.filteredConversations(), item => item.id), [11]);
});

test('Connector inbox skips learning contact discovery', async () => {
    const { store, window } = chatStore();
    store.applyBootstrapPayload({ connectorId: 12 });
    let requests = 0;
    window.axios = { get: async () => { requests++; return { data: {} }; } };
    await store.fetchDiscovery();
    assert.equal(requests, 0);
});

test('Connector inbox initially selects a Connector thread rather than a personal conversation', async () => {
    const { store } = chatStore();
    store.ensureBootstrapped = async payload => store.applyBootstrapPayload(payload);
    store.conversations = [{ id: 99 }, { id: 11, connector_id: 12 }];
    let selected;
    store.selectConversation = async id => { selected = id; };
    await store.bootstrap({ connectorId: 12 });
    assert.equal(selected, 11);
});

test('saved personal popup is restored through global pagination while viewing Connector messages', async () => {
    const { store, window, document } = chatStore();
    store.applyBootstrapPayload({ connectorId: 12 });
    window.axios = { get: async (_url, { params }) => {
        const conversations = params.connector_id
            ? (params.page === 1 ? [{ id: 11, connector_id: 12 }] : [])
            : (params.page === 1 ? [{ id: 21 }] : [{ id: 99, unread_count: 3 }]);
        return { data: { conversations, pagination: { current_page: params.page, has_more: !params.connector_id && params.page === 1 } } };
    } };
    store.subscribeConversationChannels = () => {};
    store.subscribeConversationChannel = () => {};
    store.loadMessages = async () => true;
    store.syncUnreadBadges = () => {};
    await store.fetchConversations();
    const saved = new Map([['chat.popup.windows.7', JSON.stringify({ windows: [{ conversation_id: 99 }] })]]);
    window.localStorage = {
        getItem: key => saved.get(key), setItem: (key, value) => saved.set(key, value), removeItem: key => saved.delete(key),
    };
    vm.runInNewContext(readFileSync(new URL('../../resources/js/chat/global-popup.js', import.meta.url), 'utf8'), { window, document });
    const popup = window.globalPopupChat({ currentUserId: 7 });
    popup.$store = { chat: store };
    popup.popupStateKey = 'chat.popup.windows.7';
    popup.scrollToBottom = () => {};
    await popup.restoreWindowsFromStorage();
    assert.equal(popup.openWindows[0]?.id, 99);
    assert.equal(JSON.parse(saved.get(popup.popupStateKey)).windows[0].conversation_id, 99);
    assert.equal(store.unreadByConversation[99], 3);
});

test('revoked Connector access does not prevent global popup conversations from loading', async () => {
    const { store, window } = chatStore();
    store.applyBootstrapPayload({ connectorId: 12 });
    window.axios = { get: async (_url, { params }) => {
        if (params.connector_id) throw { response: { status: 403 } };
        return { data: { conversations: [{ id: 99 }], pagination: { has_more: false } } };
    } };
    store.subscribeConversationChannels = () => {};
    store.syncUnreadBadges = () => {};
    await store.fetchConversations();
    assert.ok(store.findConversationById(99));
    assert.equal(store.filteredConversations().length, 0);
});
