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

function toggle(element, hidden) {
    element?.classList.toggle('hidden', hidden);
}

function setText(element, value) {
    if (element) {
        element.textContent = value;
    }
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
    let previouslyFocusedElement;
    let invalidFileSelection = false;

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
        setText(status, file ? 'Uploading video...' : 'Saving topic...');
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
            setText(status, 'Saving topic...');
            setText(detail, 'Upload complete. Finalizing the topic...');
        });

        xhr.addEventListener('load', () => {
            const payload = parsePayload(xhr);
            if (xhr.status >= 200 && xhr.status < 300 && payload.redirect) {
                window.location.assign(payload.redirect);
                return;
            }

            const message = xhr.status === 413
                ? 'The video is larger than the server request limit. Choose a video of 100 MB or less.'
                : payload.errors?.video_file?.[0]
                    || payload.message
                    || 'The video could not be uploaded. Please try again.';
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
