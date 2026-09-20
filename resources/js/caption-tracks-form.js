export const CAPTION_MAX_BYTES = 2 * 1024 * 1024;
const CAPTION_MIMES = new Set(['text/vtt', 'text/plain']);

export function captionFileError(file) {
    if (!file) {
        return null;
    }
    if (file.size > CAPTION_MAX_BYTES) {
        return 'WebVTT caption files must be 2 MB or smaller.';
    }
    if (!file.name.toLowerCase().endsWith('.vtt')) {
        return 'Caption files must use the WebVTT (.vtt) format.';
    }
    if (file.type && !CAPTION_MIMES.has(file.type)) {
        return 'Caption files must contain WebVTT text.';
    }

    return null;
}

export function captionRowHtml(html, index) {
    return html.replaceAll('__INDEX__', String(index));
}

export function initializeCaptionTracksForm(root) {
    if (root.dataset.captionTracksInitialized === 'true') {
        return;
    }
    root.dataset.captionTracksInitialized = 'true';

    const form = root.closest('form');
    const rows = root.querySelector('[data-caption-rows]');
    const template = root.querySelector('[data-caption-template]');
    const noDefault = root.querySelector('[data-caption-no-default]');
    let nextIndex = Number(root.dataset.nextIndex || 0);

    const sourceIsLocal = () => {
        const select = form?.querySelector('select[name="video_source"]');
        const checked = form?.querySelector('input[name="video_source"]:checked');
        return (select?.value || checked?.value) === 'upload';
    };

    const syncAvailability = () => {
        const enabled = sourceIsLocal();
        root.classList.toggle('hidden', !enabled);
        root.querySelectorAll('[data-caption-row]').forEach((row) => {
            const removed = row.dataset.removed === 'true';
            row.querySelectorAll('input, button').forEach((control) => {
                control.disabled = !enabled || removed;
            });
            if (enabled && removed) {
                row.querySelector('[data-caption-id]')?.removeAttribute('disabled');
                row.querySelector('[data-caption-remove-value]')?.removeAttribute('disabled');
            }
        });
    };

    root.addEventListener('click', (event) => {
        if (event.target.closest('[data-add-caption]')) {
            rows.insertAdjacentHTML(
                'beforeend',
                captionRowHtml(template.innerHTML, nextIndex),
            );
            nextIndex += 1;
            root.dataset.nextIndex = String(nextIndex);
            return;
        }

        const removeButton = event.target.closest('[data-remove-caption]');
        if (!removeButton) {
            return;
        }

        const row = removeButton.closest('[data-caption-row]');
        const id = row.querySelector('[data-caption-id]')?.value;
        if (row.querySelector('input[name="caption_default"]:checked')) {
            noDefault.checked = true;
        }

        if (!id) {
            row.remove();
            return;
        }

        row.dataset.removed = 'true';
        row.hidden = true;
        row.querySelector('[data-caption-remove-value]').value = '1';
        syncAvailability();
    });

    root.addEventListener('change', (event) => {
        if (!event.target.matches('[data-caption-file]')) {
            return;
        }

        const row = event.target.closest('[data-caption-row]');
        const file = event.target.files?.[0];
        const error = captionFileError(file);
        const errorElement = row.querySelector('[data-caption-file-error]');
        const nameElement = row.querySelector('[data-caption-file-name]');

        errorElement.textContent = error || '';
        errorElement.classList.toggle('hidden', !error);
        event.target.setAttribute('aria-invalid', error ? 'true' : 'false');
        if (error) {
            event.target.value = '';
        } else if (file) {
            nameElement.textContent = file.name;
        }
    });

    form?.querySelectorAll('[name="video_source"]').forEach((control) => {
        control.addEventListener('change', syncAvailability);
    });
    syncAvailability();
}

if (typeof document !== 'undefined') {
    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('[data-caption-tracks-form]')
            .forEach((root) => initializeCaptionTracksForm(root));
    });
}
