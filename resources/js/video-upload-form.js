const MEBIBYTE = 1024 * 1024;
const SUPPORTED_VIDEO_TYPES = new Set([
    'video/mp4',
    'video/mpeg',
    'video/quicktime',
    'video/x-msvideo',
    'video/webm',
]);

export const VIDEO_UPLOAD_MAX_BYTES = 100 * MEBIBYTE;

export function formatMiB(bytes) {
    return `${(Number(bytes || 0) / MEBIBYTE).toFixed(1)} MB`;
}

export function uploadPercent(loaded, total) {
    if (!Number.isFinite(total) || total <= 0) {
        return 0;
    }

    return Math.min(100, Math.max(0, Math.round((loaded / total) * 100)));
}

export function allowedVideoFileError(file, maxBytes = VIDEO_UPLOAD_MAX_BYTES) {
    if (!file) {
        return null;
    }

    if (file.size > maxBytes) {
        return `${file.name} is ${formatMiB(file.size)}. Videos must be 100 MB or smaller.`;
    }

    if (file.type && !SUPPORTED_VIDEO_TYPES.has(file.type)) {
        return 'Video must use one of these formats: MP4, MPEG, MOV, AVI, and WebM.';
    }

    return null;
}

export function syncVideoSourceFields(form) {
    const source = form.querySelector('[data-video-source]:checked')
        || form.querySelector('select[data-video-source]');
    const urlField = form.querySelector('[data-video-url-field]');
    const fileField = form.querySelector('[data-video-file-field]');

    if (!source || !urlField || !fileField) {
        return;
    }

    urlField.classList.toggle('hidden', source.value !== 'url');
    fileField.classList.toggle('hidden', source.value !== 'upload');
}

export function bindVideoFileTrigger(form) {
    const fileInput = form.querySelector('[data-video-file-input]');
    const trigger = form.querySelector('button[data-video-file-trigger]');

    trigger?.addEventListener('click', () => fileInput?.click());
}

export function initializeVideoFilePreview(form) {
    const input = form.querySelector('[data-video-file-input]');
    const panel = form.querySelector('[data-video-preview]');
    const video = form.querySelector('[data-video-preview-player]');
    const status = form.querySelector('[data-video-preview-status]');
    if (!input || !panel || !video) return;

    let objectUrl;
    const clear = () => {
        video.pause();
        video.removeAttribute('src');
        video.load();
        if (objectUrl) URL.revokeObjectURL(objectUrl);
        objectUrl = undefined;
        panel.hidden = true;
    };
    const update = () => {
        clear();
        const file = input.files?.[0];
        if (!file || allowedVideoFileError(file, Number(form.dataset.videoMaxBytes || VIDEO_UPLOAD_MAX_BYTES))) return;

        objectUrl = URL.createObjectURL(file);
        video.src = objectUrl;
        video.hidden = false;
        video.setAttribute('aria-label', `Preview of ${file.name}`);
        panel.hidden = false;
        setText(status, 'Loading video preview…');
    };
    video.addEventListener('loadedmetadata', () => {
        // Seek just past the first frame so mobile browsers also paint a thumbnail.
        if (video.duration > 0) {
            video.currentTime = Math.min(0.1, video.duration / 2);
        }
    });
    video.addEventListener('loadeddata', () => setText(status, 'Preview only. The file will upload when you save the topic.'));
    video.addEventListener('error', () => {
        if (!objectUrl) return;
        video.hidden = true;
        setText(status, 'This browser cannot preview this video format. You can still save the selected file.');
    });
    input.addEventListener('change', update);
    form.addEventListener('reset', clear);
    form.addEventListener('change', event => {
        if (['video_source', 'type'].includes(event.target.name)) video.pause();
    });
    window.addEventListener('pagehide', clear);
    window.addEventListener('pageshow', event => { if (event.persisted) update(); });
}

function toggle(element, hidden) {
    element?.classList.toggle('hidden', hidden);
}

function setText(element, value) {
    if (element) {
        element.textContent = value;
    }
}

function uploadCopy(form) {
    return {
        saving: form.dataset.videoSavingLabel || 'Saving topic...',
        finalizing: form.dataset.videoFinalizingLabel
            || 'Upload complete. Finalizing the topic...',
        failure: form.dataset.videoFailureLabel
            || 'The topic could not be saved. Please try again.',
    };
}

function parsePayload(xhr) {
    if (xhr.response && typeof xhr.response === 'object') {
        return xhr.response;
    }

    try {
        return JSON.parse(xhr.responseText || '{}');
    } catch {
        return {};
    }
}

export function initializeVideoUploadForm(form, xhrFactory = () => new XMLHttpRequest()) {
    const fileInput = form.querySelector('[data-video-file-input]');
    const fileName = form.querySelector('[data-video-file-name]');
    const fileError = form.querySelector('[data-video-error]');
    const overlay = document.querySelector('[data-video-upload-overlay]');
    const spinner = overlay?.querySelector('[data-upload-spinner]');
    const progressPanel = overlay?.querySelector('[data-upload-progress-panel]');
    const progressBar = overlay?.querySelector('[data-upload-progress]');
    const percentage = overlay?.querySelector('[data-upload-percentage]');
    const status = overlay?.querySelector('[data-upload-status]');
    const detail = overlay?.querySelector('[data-upload-detail]');
    const formError = document.querySelector('[data-video-upload-form-error]');
    const submitButton = form.querySelector('[type="submit"]');
    const originalButtonHtml = submitButton?.innerHTML;
    const maxBytes = Number(form.dataset.videoMaxBytes || VIDEO_UPLOAD_MAX_BYTES);
    const copy = uploadCopy(form);
    let previouslyFocusedElement;
    let invalidFileSelection = false;

    bindVideoFileTrigger(form);
    initializeVideoFilePreview(form);
    form.querySelectorAll?.('[data-video-source]')?.forEach((control) => {
        control.addEventListener('change', () => syncVideoSourceFields(form));
    });
    syncVideoSourceFields(form);

    const setFileError = (message) => {
        setText(fileError, message || '');
        toggle(fileError, !message);
        fileInput?.setAttribute('aria-invalid', message ? 'true' : 'false');
    };

    const showOverlay = () => {
        previouslyFocusedElement = document.activeElement;
        toggle(overlay, false);
        overlay?.focus();
    };

    const trapOverlayFocus = (event) => {
        if (event.key === 'Tab' && overlay && !overlay.classList.contains('hidden')) {
            event.preventDefault();
            overlay.focus();
        }
    };

    document.addEventListener('keydown', trapOverlayFocus);

    const showError = (message) => {
        setText(formError, message);
        toggle(formError, false);
    };

    const restoreAfterFailure = (message) => {
        toggle(overlay, true);
        showError(message);
        if (submitButton) {
            submitButton.disabled = false;
            submitButton.innerHTML = originalButtonHtml;
        }
        previouslyFocusedElement?.focus();
        previouslyFocusedElement = undefined;
    };

    const updateProgress = (loaded, total) => {
        const percent = uploadPercent(loaded, total);
        if (progressBar) {
            progressBar.style.width = `${percent}%`;
            progressBar.setAttribute('aria-valuenow', String(percent));
        }
        setText(percentage, `${percent}%`);
        setText(detail, `${formatMiB(loaded)} of ${formatMiB(total)}`);
    };

    fileInput?.addEventListener('change', () => {
        const file = fileInput.files?.[0];
        const error = allowedVideoFileError(file, maxBytes);

        setFileError(error);

        if (error) {
            invalidFileSelection = true;
            fileInput.value = '';
            setText(fileName, 'MP4, MPEG, MOV, AVI, or WebM up to 100 MB');
            return;
        }

        if (file) {
            invalidFileSelection = false;
            setText(fileName, `${file.name} (${formatMiB(file.size)})`);
        }
    });

    form.addEventListener('reset', () => {
        invalidFileSelection = false;
        setFileError(null);
    });

    form.addEventListener('submit', (event) => {
        const file = fileInput?.files?.[0];
        const error = allowedVideoFileError(file, maxBytes);

        toggle(formError, true);

        if (error || invalidFileSelection) {
            event.preventDefault();
            if (error) {
                setFileError(error);
            }
            return;
        }

        showOverlay();
        setText(status, file ? 'Uploading video...' : copy.saving);
        toggle(spinner, Boolean(file));
        toggle(progressPanel, !file);

        if (submitButton) {
            submitButton.disabled = true;
        }

        if (!file) {
            return;
        }

        event.preventDefault();
        updateProgress(0, file.size);

        const xhr = xhrFactory();
        xhr.open(form.method || 'POST', form.action);
        xhr.responseType = 'json';
        xhr.setRequestHeader('Accept', 'application/json');
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

        const csrfToken = form.querySelector('input[name="_token"]')?.value;
        if (csrfToken) {
            xhr.setRequestHeader('X-CSRF-TOKEN', csrfToken);
        }

        xhr.upload.addEventListener('progress', (progressEvent) => {
            if (progressEvent.lengthComputable) {
                updateProgress(progressEvent.loaded, progressEvent.total);
            }
        });

        xhr.upload.addEventListener('load', () => {
            setText(status, copy.saving);
            setText(detail, copy.finalizing);
        });

        xhr.addEventListener('load', () => {
            const payload = parsePayload(xhr);
            if (xhr.status >= 200 && xhr.status < 300 && payload.redirect) {
                window.location.assign(payload.redirect);
                return;
            }

            const firstValidationError = Object.values(payload.errors || {})
                .flat()
                .find(Boolean);
            const message = xhr.status === 413
                ? 'The upload is larger than the server request limit.'
                : firstValidationError
                    || payload.message
                    || copy.failure;
            restoreAfterFailure(message);
        });

        xhr.addEventListener('error', () => {
            restoreAfterFailure('The network interrupted the upload. Check your connection and try again.');
        });

        xhr.addEventListener('abort', () => {
            restoreAfterFailure('The video upload was cancelled.');
        });

        xhr.send(new FormData(form));
    });
}

if (typeof document !== 'undefined') {
    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('[data-video-upload-form]')
            .forEach((form) => initializeVideoUploadForm(form));
    });
}
