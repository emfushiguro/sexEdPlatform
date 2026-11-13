export const emptyActivityFeedback = () => ({ kind: 'idle', message: '', icon: null });

export function feedbackForEvaluation(type, data = {}, meta = {}) {
    if (data.is_complete === true) return { type: 'activity_completed', message: 'You completed this learning activity.' };
    if (data.is_correct === true && type === 'matching') {
        return { type: 'correct', message: `Correct match. ${meta.completed ?? 0} of ${meta.total ?? 0} pairs complete.` };
    }
    if (type === 'matching') return { type: 'incorrect', message: 'Review the highlighted connections and try again.' };
    return { type: 'incorrect', message: 'Review the highlighted positions and try again.' };
}

export function feedbackForLifecycle(status) {
    if (status === 'skipped') return { type: 'informational', presentation: 'micro', message: 'Activity skipped. You can resume when ready.' };
    if (status === 'completed' || status === 'practice_completed') return { type: 'activity_completed', message: 'You completed this learning activity.' };
    return emptyActivityFeedback();
}
