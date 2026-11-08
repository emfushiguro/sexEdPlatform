import test from 'node:test';
import assert from 'node:assert/strict';
import { shiftCivil, seminarSchedule } from '../../resources/js/seminar-schedule.js';

test('civil arithmetic handles leap days, year boundaries and seconds', () => {
    assert.equal(shiftCivil('2028-02-28T23:30', 60), '2028-02-29T00:30');
    assert.equal(shiftCivil('2026-12-31T23:30:17', 90), '2027-01-01T01:00:17');
    assert.equal(shiftCivil('2026-01-01T00:10', -30), '2025-12-31T23:40');
    for (const timezone of ['America/New_York', 'Asia/Manila', 'Pacific/Auckland']) {
        process.env.TZ = timezone;
        assert.equal(shiftCivil('2026-03-08T01:30', 120), '2026-03-08T03:30');
    }
});
test('suggestions follow start until edited or cleared and can be restored', () => {
    const state = seminarSchedule({ starts_at: '2026-10-15T10:00', suggest: true });
    state.init();
    assert.equal(state.ends_at, '2026-10-15T11:00');
    assert.equal(state.registration_deadline_at, '2026-10-15T09:30');
    assert.equal(state.external_link_visible_at, '2026-10-15T09:45');
    state.inputPart('external_link_visible_at', 1, '');
    state.inputPart('starts_at', 1, '12:00');
    assert.equal(state.external_link_visible_at, '');
    assert.equal(state.ends_at, '2026-10-15T13:00');
    state.suggest('external_link_visible_at');
    assert.equal(state.external_link_visible_at, '2026-10-15T11:45');
    state.duration(120);
    state.inputPart('starts_at', 1, '14:00');
    assert.equal(state.ends_at, '2026-10-15T14:00');
    assert.equal(state.feedback, 'End must be after start.');
});
test('saved and failed validation values including blank remain untouched', () => {
    const state = seminarSchedule({ starts_at: '2026-10-15T10:00:17', ends_at: '2026-10-16T11:00:23', registration_deadline_at: '' });
    state.init(); state.inputPart('starts_at', 0, '2026-10-16');
    assert.equal(state.starts_at, '2026-10-16T10:00:17');
    assert.equal(state.ends_at, '2026-10-16T11:00:23');
    assert.equal(state.registration_deadline_at, '');
});
test('split controls support entering date and time separately', () => {
    const state = seminarSchedule({ suggest: true });
    state.inputPart('starts_at', 0, '2026-10-15');
    state.inputPart('starts_at', 1, '23:30');
    assert.equal(state.starts_at, '2026-10-15T23:30');
    assert.equal(state.ends_at, '2026-10-16T00:30');
});

test('summary and registration deadline feedback use civil PHT times', () => {
    const state = seminarSchedule({ starts_at: '2026-12-31T23:30', ends_at: '2027-01-01T00:30', registration_deadline_at: '2027-01-01T00:00' });
    assert.match(state.dateSummary, /Dec 31, 2026.*Jan 1, 2027.*PHT/);
    assert.equal(state.deadlineFeedback, 'Registration deadline must be before event start.');
    state.registration_deadline_at = state.starts_at;
    assert.equal(state.deadlineFeedback, 'Registration deadline must be before event start.');
    state.registration_deadline_at = '2026-12-31T23:00';
    assert.equal(state.deadlineFeedback, '');
    state.registration_deadline_at = '';
    assert.equal(state.deadlineFeedback, '');
    assert.equal(state.timeStep('starts_at'), 60);
    state.starts_at = '2026-12-31T23:30:17';
    assert.equal(state.timeStep('starts_at'), 1);
    state.starts_at = '2026-12-31T23:30:00';
    assert.equal(state.timeStep('starts_at'), 60);
});
