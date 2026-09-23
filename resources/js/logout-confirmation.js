const LOGOUT_FORM_SELECTOR = 'form[data-logout-form]';

export function initializeLogoutConfirmation(
    documentRef = globalThis.document,
    windowRef = globalThis.window,
) {
    if (!documentRef || !windowRef || documentRef.__ccLogoutConfirmationInitialized) {
        return;
    }

    documentRef.__ccLogoutConfirmationInitialized = true;
    documentRef.addEventListener('submit', (event) => {
        const form = event.target?.closest?.(LOGOUT_FORM_SELECTOR);

        if (!form) {
            return;
        }

        if (form.dataset.logoutConfirmed === 'true') {
            delete form.dataset.logoutConfirmed;
            return;
        }

        event.preventDefault();
        windowRef.dispatchEvent(new windowRef.CustomEvent('logout-confirmation-request', {
            detail: {
                form,
                trigger: documentRef.activeElement,
            },
        }));
    }, true);
}

if (typeof document !== 'undefined') {
    initializeLogoutConfirmation();
}
