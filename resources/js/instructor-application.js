export function setStatus(root, message) {
    const status = root?.querySelector?.('[data-submit-status]');

    if (status) {
        status.textContent = message;
    }
}

export function draftUploadLabel(input) {
    return input?.files?.[0]?.name ?? '';
}

export function prefersReducedMotion(view = globalThis.window) {
    return Boolean(view?.matchMedia?.('(prefers-reduced-motion: reduce)')?.matches);
}

export function resetDependentOptions(_city, barangay) {
    if (barangay) {
        barangay.value = '';
    }
}

export function preventRepeatedSubmit(form) {
    if (!form || form.dataset.instructorSubmitBound === 'true') {
        return;
    }

    const controls = () => [...form.querySelectorAll('button[type="submit"], input[type="submit"]')];
    const restore = () => {
        delete form.dataset.processing;
        form.removeAttribute('aria-busy');
        controls().forEach((control) => { control.disabled = false; });
        setStatus(form, '');
    };

    form.dataset.instructorSubmitBound = 'true';
    form.addEventListener('submit', (event) => {
        if (form.dataset.processing === 'true') {
            event.preventDefault();
            return;
        }

        form.dataset.processing = 'true';
        form.setAttribute('aria-busy', 'true');
        controls().forEach((control) => { control.disabled = true; });
        setStatus(form, 'Submitting. Please wait.');
    });
    form.addEventListener('invalid', restore, true);
}

export function focusErrorSummary(root = globalThis.document) {
    const summary = root?.querySelector?.('[data-error-summary], [role="alert"][tabindex="-1"]');

    if (!summary) {
        return false;
    }

    summary.focus();
    return true;
}

function initializeInstructorApplicationUI(documentRef = globalThis.document) {
    if (!documentRef) {
        return;
    }

    documentRef.querySelectorAll('[data-instructor-form]').forEach(preventRepeatedSubmit);
    documentRef.querySelectorAll('[data-dependent-select]').forEach((city) => {
        const barangay = documentRef.getElementById(city.dataset.dependentTarget);
        city.addEventListener('change', () => resetDependentOptions(city, barangay));
    });
    documentRef.querySelectorAll('[data-draft-upload]').forEach((input) => {
        input.addEventListener('change', () => {
            const label = documentRef.querySelector(`[data-upload-name-for="${input.id}"]`);
            if (label) {
                label.textContent = draftUploadLabel(input) || 'No file selected';
            }
        });
    });

    focusErrorSummary(documentRef);
}

if (globalThis.document) {
    if (globalThis.document.readyState === 'loading') {
        globalThis.document.addEventListener('DOMContentLoaded', () => initializeInstructorApplicationUI());
    } else {
        initializeInstructorApplicationUI();
    }
}
