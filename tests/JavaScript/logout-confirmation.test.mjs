import test from 'node:test';
import assert from 'node:assert/strict';
import { initializeLogoutConfirmation } from '../../resources/js/logout-confirmation.js';

function createHarness() {
    const listeners = {};
    const dispatched = [];
    const trigger = { focus() {} };
    const form = {
        dataset: {},
        closest(selector) {
            return selector === 'form[data-logout-form]' ? this : null;
        },
    };
    const documentRef = {
        activeElement: trigger,
        addEventListener(type, listener) {
            listeners[type] = listener;
        },
    };
    const windowRef = {
        CustomEvent: class CustomEvent {
            constructor(type, options) {
                this.type = type;
                this.detail = options.detail;
            }
        },
        dispatchEvent(event) {
            dispatched.push(event);
        },
    };

    initializeLogoutConfirmation(documentRef, windowRef);

    return { dispatched, documentRef, form, listeners };
}

test('intercepts an unconfirmed logout submit and dispatches the modal request', () => {
    const { dispatched, documentRef, form, listeners } = createHarness();
    let prevented = false;

    listeners.submit({
        target: form,
        preventDefault() {
            prevented = true;
        },
    });

    assert.equal(prevented, true);
    assert.equal(dispatched.length, 1);
    assert.equal(dispatched[0].type, 'logout-confirmation-request');
    assert.equal(dispatched[0].detail.form, form);
    assert.equal(dispatched[0].detail.trigger, documentRef.activeElement);
});

test('allows the form through once the confirmation marker is present', () => {
    const { form, listeners } = createHarness();
    form.dataset.logoutConfirmed = 'true';
    let prevented = false;

    listeners.submit({
        target: form,
        preventDefault() {
            prevented = true;
        },
    });

    assert.equal(prevented, false);
    assert.equal(form.dataset.logoutConfirmed, undefined);
});
