import test from 'node:test';
import assert from 'node:assert/strict';
import { emptyActivityFeedback, feedbackForEvaluation, feedbackForLifecycle } from '../../resources/js/activity-feedback.js';

test('matching feedback distinguishes progress, incorrect, and completion', () => {
    assert.deepEqual(feedbackForEvaluation('matching', { is_correct: true, is_complete: false }, { completed: 2, total: 4 }), {
        kind: 'correct', message: 'Correct match. 2 of 4 pairs complete.', icon: 'check',
    });
    assert.equal(feedbackForEvaluation('matching', { is_correct: false }).message,
        'Incorrect match. Review the highlighted connections and try again.');
    assert.equal(feedbackForEvaluation('matching', { is_correct: true, is_complete: true }).message,
        'Correct. Activity complete.');
});

test('sequencing and lifecycle feedback use the shared language', () => {
    assert.equal(feedbackForEvaluation('sequencing', { is_correct: false }).message,
        'Incorrect sequence. Reorder the items and try again.');
    assert.equal(feedbackForLifecycle('skipped').message,
        'Activity skipped. You can resume when ready.');
    assert.deepEqual(emptyActivityFeedback(), { kind: 'idle', message: '', icon: null });
});
