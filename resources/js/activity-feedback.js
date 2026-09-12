export const emptyActivityFeedback = () => ({ kind: 'idle', message: '', icon: null });

export function feedbackForEvaluation(type, data = {}, meta = {}) {
    if (data.is_complete === true) return { kind: 'completed', message: 'Correct. Activity complete.', icon: 'check' };
    if (data.is_correct === true && type === 'matching') {
        return { kind: 'correct', message: `Correct match. ${meta.completed ?? 0} of ${meta.total ?? 0} pairs complete.`, icon: 'check' };
    }
    if (type === 'matching') return { kind: 'incorrect', message: 'Incorrect match. Review the highlighted connections and try again.', icon: 'x' };
    return { kind: 'incorrect', message: 'Incorrect sequence. Reorder the items and try again.', icon: 'x' };
}

export function feedbackForLifecycle(status) {
    if (status === 'skipped') return { kind: 'skipped', message: 'Activity skipped. You can resume when ready.', icon: 'skip' };
    if (status === 'completed' || status === 'practice_completed') return { kind: 'completed', message: 'Correct. Activity complete.', icon: 'check' };
    return emptyActivityFeedback();
}
