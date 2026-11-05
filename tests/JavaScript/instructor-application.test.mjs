import test from 'node:test';
import assert from 'node:assert/strict';
import {
    draftUploadLabel,
    focusErrorSummary,
    preventRepeatedSubmit,
    prefersReducedMotion,
    resetDependentOptions,
    setStatus,
} from '../../resources/js/instructor-application.js';

test('dependent selector reset clears the previous barangay selection', () => {
    const barangay = { value: 'old-barangay' };

    resetDependentOptions({}, barangay);

    assert.equal(barangay.value, '');
});

test('status updates use the live region and upload labels expose only the file name', () => {
    const status = { textContent: '' };
    const root = { querySelector: (selector) => selector === '[data-submit-status]' ? status : null };
    const input = { files: [{ name: 'clearance.pdf' }] };

    setStatus(root, 'Draft saved.');

    assert.equal(status.textContent, 'Draft saved.');
    assert.equal(draftUploadLabel(input), 'clearance.pdf');
});

test('reduced motion follows the user media preference', () => {
    assert.equal(prefersReducedMotion({ matchMedia: () => ({ matches: true }) }), true);
    assert.equal(prefersReducedMotion({ matchMedia: () => ({ matches: false }) }), false);
});

test('repeated submits are blocked while the first request is processing', () => {
    const controls = [{ disabled: false }];
    const listeners = {};
    const form = {
        dataset: {},
        addEventListener: (type, listener) => { listeners[type] = listener; },
        querySelectorAll: () => controls,
        querySelector: () => ({ textContent: '' }),
        setAttribute: () => {},
        removeAttribute: () => {},
    };

    preventRepeatedSubmit(form);
    listeners.submit({ preventDefault: () => { form.prevented = true; } });
    listeners.submit({ preventDefault: () => { form.prevented = true; } });

    assert.equal(controls[0].disabled, true);
    assert.equal(form.prevented, true);
});

test('error summary receives focus when present', () => {
    let focused = false;
    const summary = { focus: () => { focused = true; } };

    assert.equal(focusErrorSummary({ querySelector: () => summary }), true);
    assert.equal(focused, true);
});
