import test from 'node:test';
import assert from 'node:assert/strict';
import { emptyActivityFeedback, feedbackForEvaluation, feedbackForLifecycle } from '../../resources/js/activity-feedback.js';

test('matching feedback distinguishes progress, incorrect, and completion', () => {
    assert.deepEqual(feedbackForEvaluation('matching', { is_correct: true, is_complete: false }, { completed: 2, total: 4 }), {
        type: 'correct', message: 'Correct match. 2 of 4 pairs complete.',
    });
    assert.equal(feedbackForEvaluation('matching', { is_correct: false }).message,
        'Review the highlighted connections and try again.');
    assert.equal(feedbackForEvaluation('matching', { is_correct: false }).type, 'incorrect');
    assert.deepEqual(feedbackForEvaluation('matching', { is_correct: true, is_complete: true }), {
        type: 'activity_completed', message: 'You completed this learning activity.',
    });
});

test('sequencing and lifecycle feedback use the shared language', () => {
    assert.equal(feedbackForEvaluation('sequencing', { is_correct: false }).message,
        'Review the highlighted positions and try again.');
    assert.deepEqual(feedbackForLifecycle('skipped'), {
        type: 'informational', presentation: 'micro', message: 'Activity skipped. You can resume when ready.',
    });
    assert.equal(feedbackForLifecycle('practice_completed').type, 'activity_completed');
    assert.deepEqual(emptyActivityFeedback(), { kind: 'idle', message: '', icon: null });
});
