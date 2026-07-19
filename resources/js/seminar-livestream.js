const roots = () => document.querySelectorAll('[data-agora-livestream]');
const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content ?? '';

const deviceMessage = (error, kind = 'camera or microphone') => {
    if (error?.code === 'NOT_ALLOWED' || error?.name === 'NotAllowedError') return `Allow ${kind} access in browser settings, then try again.`;
    if (error?.code === 'DEVICE_NOT_FOUND' || error?.name === 'NotFoundError') return `No ${kind} was detected.`;
    if (error?.code === 'NOT_READABLE' || error?.name === 'NotReadableError') return `${kind} is unavailable or already used by another app.`;
    if (error?.code === 'TOKEN_EXPIRED') return 'Your livestream access expired. Reconnect to continue.';
    return error?.message ?? `Could not access ${kind}.`;
};

function createTile(root, uid) {
    const grid = root.querySelector('[data-agora-remotes]');
    if (!grid) return null;
    let tile = grid.querySelector(`[data-agora-user="${uid}"]`);
    if (tile) return tile.querySelector('[data-agora-video]');
    grid.querySelector('[data-agora-empty]')?.remove();
    tile = document.createElement('div');
    tile.className = 'overflow-hidden rounded-xl border border-white/10 bg-black ring-purple-500 transition';
    tile.dataset.agoraUser = uid;
    tile.innerHTML = `<div class="flex items-center justify-between px-3 py-2 text-xs font-semibold text-gray-300"><span>Participant ${uid}</span><span class="text-emerald-400">Connected</span></div><div class="aspect-video" data-agora-video></div>`;
    grid.appendChild(tile);
    return tile.querySelector('[data-agora-video]');
}

async function initLivestream(root) {
    const { default: AgoraRTC } = await import('agora-rtc-sdk-ng');
    const canPublish = root.dataset.agoraCanPublish === '1';
    const isHost = root.dataset.agoraHostControls === '1';
    const localSlot = root.querySelector('[data-agora-local]');
    const buttons = Object.fromEntries([...root.querySelectorAll('[data-agora-action]')].map((el) => [el.dataset.agoraAction, el]));
    const dialog = root.querySelector('[data-agora-leave-dialog]');
    let client;
    let audio;
    let video;
    let session;
    let joined = false;
    let published = false;
    let publishing = false;
    let leaving = false;
    let timer;
    let heartbeat;
    let statusPoll;
    let startedAt;

    const text = (selector, value) => root.querySelectorAll(selector).forEach((el) => { el.textContent = value; });
    const status = (value, tone = 'waiting') => {
        text('[data-agora-status]', value);
        root.querySelectorAll('[data-agora-badge]').forEach((badge) => {
            badge.textContent = value;
            badge.dataset.tone = tone;
        });
    };
    const errorFrom = async (response) => {
        const payload = await response.json().catch(() => ({}));
        return new Error(payload.message ?? Object.values(payload.errors ?? {}).flat()[0] ?? 'Livestream request failed.');
    };
    const request = async (url, { method = 'POST', keepalive = false } = {}) => {
        if (!url) return null;
        const response = await fetch(url, { method, keepalive, headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf() } });
        if (!response.ok) throw await errorFrom(response);
        return response.json();
    };
    const syncButtons = () => {
        if (buttons.join) buttons.join.disabled = leaving || publishing || published || (isHost && !(joined && audio && video));
        if (buttons.camera) buttons.camera.disabled = leaving || publishing;
        if (buttons.mic) buttons.mic.disabled = leaving || publishing;
        if (buttons.leave) buttons.leave.disabled = leaving;
    };
    const applyCounts = (payload = {}) => {
        if (payload.viewer_count !== undefined) text('[data-agora-viewers]', String(payload.viewer_count));
        if (payload.speaker_count !== undefined) text('[data-agora-speakers]', String(payload.speaker_count));
        if (payload.participant_count !== undefined) text('[data-agora-participants]', String(payload.participant_count));
    };
    const startTimer = (value = null) => {
        startedAt = value ? new Date(value).getTime() : (startedAt ?? Date.now());
        clearInterval(timer);
        const tick = () => {
            const seconds = Math.max(0, Math.floor((Date.now() - startedAt) / 1000));
            text('[data-agora-duration]', new Date(seconds * 1000).toISOString().slice(11, 19));
        };
        tick();
        timer = setInterval(tick, 1000);
    };
    const setTrackButton = (track, button, label) => {
        if (!button) return;
        button.setAttribute('aria-pressed', String(track?.enabled ?? false));
        text(`[data-agora-${label}-label]`, track?.enabled ? (label === 'mic' ? 'Mute' : 'Camera off') : (label === 'mic' ? 'Unmute' : 'Camera on'));
    };
    const pollStatus = () => {
        if (!root.dataset.agoraStatusUrl) return;
        clearInterval(statusPoll);
        statusPoll = setInterval(async () => {
            try {
                const payload = await request(root.dataset.agoraStatusUrl, { method: 'GET' });
                applyCounts(payload);
                if (payload.status === 'completed') status('Completed', 'ended');
            } catch (_) {}
        }, 10000);
    };
    const bindClient = () => {
        client.on('user-published', async (user, mediaType) => {
            await client.subscribe(user, mediaType);
            if (mediaType === 'video') user.videoTrack?.play(createTile(root, user.uid));
            if (mediaType === 'audio') user.audioTrack?.play();
        });
        client.on('user-unpublished', (user, mediaType) => {
            if (mediaType === 'video') root.querySelector(`[data-agora-user="${user.uid}"]`)?.remove();
        });
        client.on('user-left', (user) => root.querySelector(`[data-agora-user="${user.uid}"]`)?.remove());
        client.on('connection-state-change', (current, previous, reason) => {
            text('[data-agora-quality]', current === 'CONNECTED' ? 'Connected' : current.toLowerCase().replaceAll('_', ' '));
            if (current === 'RECONNECTING') status('Reconnecting…', 'preparing');
            if (current === 'CONNECTED' && previous === 'RECONNECTING') status(published ? 'LIVE' : 'Connected', published ? 'live' : 'waiting');
            if (current === 'DISCONNECTED' && reason !== 'LEAVE') status('Connection lost', 'ended');
        });
        client.on('token-privilege-will-expire', async () => {
            session = await request(root.dataset.agoraTokenUrl);
            await client.renewToken(session.token);
        });
    };
    const ensureClient = async () => {
        if (joined) return;
        session = await request(root.dataset.agoraTokenUrl);
        client = AgoraRTC.createClient({ mode: 'live', codec: 'vp8' });
        await client.setClientRole(canPublish ? 'host' : 'audience');
        bindClient();
        await client.join(session.app_id, session.channel, session.token, session.uid);
        joined = true;
        await request(root.dataset.agoraAttendanceJoinUrl);
        clearInterval(heartbeat);
        heartbeat = setInterval(() => request(root.dataset.agoraAttendanceHeartbeatUrl).catch(() => {}), 60000);
        text('[data-agora-quality]', 'Connected');
    };
    const ensureTracks = async () => {
        if (!canPublish) return;
        if (!audio) audio = await AgoraRTC.createMicrophoneAudioTrack();
        if (!video) {
            video = await AgoraRTC.createCameraVideoTrack();
            video.play(localSlot);
        }
        setTrackButton(audio, buttons.mic, 'mic');
        setTrackButton(video, buttons.camera, 'camera');
    };
    const prepareHost = async () => {
        status('Preparing livestream', 'preparing');
        syncButtons();
        try {
            await ensureClient();
            await ensureTracks();
            const payload = await request(root.dataset.agoraPrepareUrl);
            applyCounts(payload);
            status('Waiting room ready', 'waiting');
        } catch (error) {
            status(deviceMessage(error), 'ended');
        } finally {
            syncButtons();
        }
    };
    const goLive = async () => {
        if (published || publishing || leaving) return;
        publishing = true;
        status(canPublish ? 'Publishing audio and video…' : 'Joining livestream…', 'preparing');
        syncButtons();
        try {
            await ensureClient();
            await ensureTracks();
            if (canPublish) await client.publish([audio, video]);
            let live = session;
            try {
                live = await request(root.dataset.agoraStartUrl) ?? session;
            } catch (error) {
                if (canPublish) await client.unpublish([audio, video]);
                throw error;
            }
            published = canPublish;
            if (buttons.join) buttons.join.textContent = canPublish ? (isHost ? 'LIVE' : 'On stage') : 'Joined';
            startTimer(live?.started_at ?? live?.livestream_started_at);
            applyCounts(live);
            status('LIVE', 'live');
            pollStatus();
        } catch (error) {
            status(deviceMessage(error), 'ended');
        } finally {
            publishing = false;
            syncButtons();
        }
    };
    const toggle = async (kind) => {
        try {
            if (kind === 'camera' && !video) { video = await AgoraRTC.createCameraVideoTrack(); video.play(localSlot); if (published) await client.publish(video); }
            if (kind === 'mic' && !audio) { audio = await AgoraRTC.createMicrophoneAudioTrack(); if (published) await client.publish(audio); }
            const track = kind === 'camera' ? video : audio;
            await track.setEnabled(!track.enabled);
            setTrackButton(track, buttons[kind], kind);
        } catch (error) { status(deviceMessage(error, kind === 'mic' ? 'microphone' : 'camera'), 'ended'); }
        syncButtons();
    };
    const leave = async (redirect = true) => {
        if (leaving) return;
        leaving = true;
        syncButtons();
        clearInterval(timer); clearInterval(heartbeat); clearInterval(statusPoll);
        try {
            if (client && published) await client.unpublish([audio, video].filter(Boolean));
            audio?.stop(); audio?.close(); video?.stop(); video?.close();
            await request(root.dataset.agoraAttendanceLeaveUrl, { keepalive: !redirect }).catch(() => {});
            if (client) { client.removeAllListeners(); if (joined) await client.leave(); }
            if (redirect && isHost) await request(root.dataset.agoraEndUrl);
            audio = video = client = null; joined = published = false;
            localSlot?.replaceChildren();
            status(isHost ? 'Completed' : 'Left session', 'ended');
            if (redirect && root.dataset.agoraReturnUrl) window.location.assign(root.dataset.agoraReturnUrl);
        } catch (error) {
            status(`Session closed locally. ${error.message}`, 'ended');
            leaving = false;
            syncButtons();
        }
    };

    buttons.join?.addEventListener('click', goLive);
    buttons.camera?.addEventListener('click', () => toggle('camera'));
    buttons.mic?.addEventListener('click', () => toggle('mic'));
    buttons.leave?.addEventListener('click', () => dialog?.showModal());
    root.querySelector('[data-agora-leave-cancel]')?.addEventListener('click', () => dialog?.close());
    root.querySelector('[data-agora-leave-confirm]')?.addEventListener('click', () => { dialog?.close(); leave(); });
    window.addEventListener('pagehide', () => leave(false), { once: true });

    syncButtons();
    if (isHost) await prepareHost();
}

document.addEventListener('DOMContentLoaded', () => roots().forEach((root) => initLivestream(root).catch((error) => {
    root.querySelectorAll('[data-agora-status]').forEach((status) => { status.textContent = error?.message ?? 'Livestream could not start.'; });
})));
