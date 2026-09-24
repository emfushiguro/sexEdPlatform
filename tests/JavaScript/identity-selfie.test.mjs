import test from 'node:test';
import assert from 'node:assert/strict';
import { createIdentitySelfie } from '../../resources/js/identity-selfie.js';

function fixture(overrides = {}) {
    const stopped = [];
    const revoked = [];
    const tracks = [1, 2].map((id) => ({ stop: () => stopped.push(id) }));
    const stream = { getTracks: () => tracks };
    const input = { files: [], set value(value) { if (value === '') this.files = []; } };
    const video = { srcObject: null, videoWidth: 640, videoHeight: 480, play: async () => {} };
    const canvas = { width: 0, height: 0, getContext: () => ({ drawImage() {} }), toBlob: (done) => done(new Blob(['photo'], { type: 'image/jpeg' })) };
    const dependencies = {
        mediaDevices: { getUserMedia: async () => stream },
        createCanvas: () => canvas,
        url: { createObjectURL: () => 'blob:photo', revokeObjectURL: (url) => revoked.push(url) },
        DataTransfer: class { items = { add: (file) => { this.files = [file]; } }; files = []; },
        File,
        ...overrides,
    };
    const component = createIdentitySelfie(dependencies);
    component.$refs = { video, selfieInput: input };
    return { component, input, video, stopped, revoked };
}

test('camera starts on request and capture remains local until Use Photo', async () => {
    const { component, input, video } = fixture();
    await component.startCamera();
    assert.equal(component.cameraActive, true);
    assert.ok(video.srcObject);
    await component.capturePhoto();
    assert.equal(component.cameraActive, false);
    assert.equal(component.previewUrl, 'blob:photo');
    assert.equal(input.files.length, 0);
    component.usePhoto();
    assert.equal(input.files.length, 1);
    assert.equal(input.files[0].type, 'image/jpeg');
});

test('permission denial leaves upload available', async () => {
    const { component } = fixture({ mediaDevices: { getUserMedia: async () => { throw new Error('NotAllowedError'); } } });
    await component.startCamera();
    assert.match(component.cameraError, /upload/i);
    assert.equal(component.cameraActive, false);
    assert.equal(component.uploadAvailable, true);
});

test('missing camera API and canvas keep native upload available', async () => {
    const { component } = fixture({ mediaDevices: null, createCanvas: null });
    await component.startCamera();
    assert.equal(component.cameraActive, false);
    assert.equal(component.uploadAvailable, true);
});

test('disconnected stream and failed capture stop every track', async () => {
    const { component, stopped } = fixture();
    await component.startCamera();
    component.$refs.video.videoWidth = 0;
    await component.capturePhoto();
    assert.equal(component.cameraActive, false);
    assert.match(component.cameraError, /upload/i);
    assert.deepEqual(stopped, [1, 2]);
});

test('retake clears candidate and revokes preview without selecting a file', async () => {
    const { component, input, revoked, stopped } = fixture();
    await component.startCamera();
    await component.capturePhoto();
    await component.retakePhoto();
    assert.equal(component.previewUrl, null);
    assert.deepEqual(revoked, ['blob:photo']);
    assert.equal(input.files.length, 0);
    assert.equal(component.cameraActive, true);
    assert.deepEqual(stopped, [1, 2]);
});

test('replacement and cancel clear camera resources and file selection', async () => {
    const { component, input, stopped, revoked } = fixture();
    await component.startCamera();
    await component.capturePhoto();
    component.usePhoto();
    component.replacePhoto();
    assert.equal(input.files.length, 0);
    component.uploadSelected({ target: { files: [new File(['new'], 'upload.jpg', { type: 'image/jpeg' })] } });
    assert.equal(component.photoReady, true);
    component.cancel();
    assert.equal(component.photoReady, false);
    assert.equal(input.files.length, 0);
    assert.deepEqual(stopped, [1, 2]);
    assert.deepEqual(revoked, ['blob:photo', 'blob:photo']);
});

test('destroy stops all tracks and revokes preview URL', async () => {
    const { component, stopped, revoked } = fixture();
    await component.startCamera();
    await component.capturePhoto();
    component.destroy();
    assert.deepEqual(stopped, [1, 2]);
    assert.deepEqual(revoked, ['blob:photo']);
});

test('cancel during camera permission request stops late stream', async () => {
    let resolveCamera;
    const stream = { getTracks: () => [{ stop: () => { stream.stopped = true; } }] };
    const { component } = fixture({ mediaDevices: { getUserMedia: () => new Promise((resolve) => { resolveCamera = resolve; }) } });
    const starting = component.startCamera();
    component.cancel();
    resolveCamera(stream);
    await starting;
    assert.equal(stream.stopped, true);
    assert.equal(component.cameraActive, false);
});

test('cancel during image encoding discards late candidate', async () => {
    let finishBlob;
    const canvas = { getContext: () => ({ drawImage() {} }), toBlob: (done) => { finishBlob = done; } };
    const { component, input } = fixture({ createCanvas: () => canvas });
    await component.startCamera();
    const capturing = component.capturePhoto();
    component.cancel();
    finishBlob(new Blob(['photo'], { type: 'image/jpeg' }));
    await capturing;
    assert.equal(component.previewUrl, null);
    assert.equal(input.files.length, 0);
});
