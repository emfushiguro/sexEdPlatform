# Interactive Activities UX Refinement Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Turn existing Matching and Sequencing activities into polished, responsive direct-manipulation experiences while preserving their data model, server-authoritative correctness, optional progress rules, and Topic/Lesson integration.

**Architecture:** Keep `MatchingActivityHandler` and `SequencingActivityHandler` as the canonical evaluators. Add two focused dependency-free JavaScript primitives for feedback and reordering, rebuild the existing Alpine learner/authoring adapters around them, and make unsaved Preview call the same handlers through an encrypted short-lived state token.

**Tech Stack:** PHP 8.2, Laravel 12, Blade, Alpine.js 3, Tailwind CSS 3, native Pointer Events, SVG, ResizeObserver, Web Crypto-independent Laravel `Crypt`, Node test runner, PHPUnit 11, Vite 7.

## Global Constraints

- Follow `docs/superpowers/specs/2026-09-09-interactive-activities-ux-refinement-design.md`.
- Preserve configuration schema version `1`; do not add a migration.
- Preserve Matching and Sequencing canonical answer formats and stable item UUIDs.
- Keep answer evaluation in the registered PHP handlers; browser code only coordinates gestures and presentation.
- Interactive Activities remain optional and must not affect Topic/Lesson completion, quizzes, daily limits, shields, scoring, gamification, or certification.
- Preserve inside-topic and between-topic placement, revisions, publishing, ownership, and permissions.
- Do not add a drag, animation, canvas, or preview dependency.
- Use Pointer Events, SVG, ResizeObserver, `requestAnimationFrame`, CSS container queries, and existing Alpine/Tailwind infrastructure.
- Matching correct connections persist and lock; one rejected connection may remain client-side until removed or replaced.
- Sequencing exposes no visible Up/Down controls; its keyboard drag contract is Space/Enter, Arrow keys/Home/End, and Escape.
- Preview must not expose an answer key or write `interactive_activity_progress`.
- Every gesture-dependent control needs visible focus, non-color semantics, and a minimum 44-by-44-pixel hit target.
- Respect `prefers-reduced-motion: reduce`.
- Preserve unrelated worktree changes, especially the existing changes in `resources/views/instructor/topics/create.blade.php`; do not stage generated `public/build` files.
- Use TDD for each task: add a focused failing test, observe the expected failure, implement the smallest change, rerun the focused test, and commit only task files.
- Use `php vendor/bin/phpunit` on Windows rather than `php artisan test`.

---

## File Structure

### New files

- `resources/js/activity-feedback.js` — pure mapping from activity results/lifecycle states to shared semantic feedback.
- `resources/js/pointer-reorder.js` — pure reorder session, keyboard destination, and edge-scroll calculations shared by learner and authoring adapters.
- `tests/JavaScript/activity-feedback.test.mjs` — feedback copy and state mapping.
- `tests/JavaScript/pointer-reorder.test.mjs` — reorder, keyboard, cancellation, and edge-scroll primitives.
- `docs/superpowers/verification/2026-09-09-interactive-activities-ux-refinement-e2e.md` — exact automated and browser verification evidence.

### Existing files to modify

- `app/Services/Learning/InteractiveActivities/MatchingActivityHandler.php` — expose solved relationships only as `completed_matches`.
- `app/Services/Learning/InteractiveActivities/InteractiveActivityAuthoringService.php` — issue, decode, rotate, and evaluate stateless encrypted Preview contexts.
- `app/Http/Controllers/Instructor/InteractiveActivityController.php` — authorize Preview evaluation and return renderer/evaluation responses.
- `routes/instructor.php` — instructor Preview evaluation route.
- `routes/admin.php` — admin Preview evaluation route.
- `resources/js/interactive-activity.js` — shared lifecycle, scoped payload rehydration, feedback, and Preview Practice requests.
- `resources/js/matching-activity.js` — pointer/touch/keyboard connection state, safe evaluation adapter, rejected connection, and responsive SVG geometry.
- `resources/js/sequencing-activity.js` — shared reorder session, drag overlay, keyboard drag, edge auto-scroll, persistence, and payload rehydration.
- `resources/js/interactive-activity-authoring.js` — shared pair/item reorder sessions, removal focus, and Preview response handling.
- `resources/css/components.css` — activity container, line, drag, insertion, non-color state, and reduced-motion styles.
- `resources/views/learner/lessons/partials/interactive-activities/shell.blade.php` — shared feedback, explanation, scoped events, and container root.
- `resources/views/learner/lessons/partials/interactive-activities/matching.blade.php` — connection dots, SVG layers, card states, and keyboard controls.
- `resources/views/learner/lessons/partials/interactive-activities/sequencing.blade.php` — drag handle, overlay, placeholder, insertion bar, and announcements.
- `resources/views/instructor/topics/partials/matching-builder.blade.php` — visually connected reorderable pair rows.
- `resources/views/instructor/topics/partials/sequencing-builder.blade.php` — canonical drag order without Up/Down buttons.
- `resources/views/instructor/topics/partials/interactive-activity-fields.blade.php` — shared authoring pointer/keyboard events and Preview evaluation URL.
- `resources/views/instructor/topics/partials/interactive-activity-preview-modal.blade.php` — container-width Preview mount and token-expiry recovery.
- `tests/Unit/Services/Learning/InteractiveActivityHandlerTest.php` — solved Matching payload secrecy contract.
- `tests/JavaScript/interactive-activity.test.mjs` — lifecycle feedback and scoped payload events.
- `tests/JavaScript/matching-activity.test.mjs` — direct connection state and geometry.
- `tests/JavaScript/sequencing-activity.test.mjs` — pointer/touch/keyboard reordering and persistence.
- `tests/JavaScript/interactive-activity-authoring.test.mjs` — shared authoring reorder, focus, and Preview behavior.
- `tests/Feature/Learner/InteractiveActivityRenderingTest.php` — activity markup, feedback, explanation, and answer secrecy.
- `tests/Feature/Learner/MatchingActivityFlowTest.php` — restored solved relationships and canonical evaluation.
- `tests/Feature/Learner/SequencingActivityFlowTest.php` — preserved order, retry, Practice, and completion regressions.
- `tests/Feature/Instructor/InteractiveActivityAuthoringTest.php` — encrypted Preview evaluation, authorization, expiry, tampering, and no-progress contract.

---

### Task 1: Restore safe Matching relationships and rehydrate lifecycle payloads

**Files:**

- Modify: `app/Services/Learning/InteractiveActivities/MatchingActivityHandler.php`
- Modify: `resources/js/interactive-activity.js`
- Modify: `tests/Unit/Services/Learning/InteractiveActivityHandlerTest.php`
- Modify: `tests/JavaScript/interactive-activity.test.mjs`
- Modify: `tests/Feature/Learner/MatchingActivityFlowTest.php`

**Interfaces:**

- Consumes: `MatchingActivityHandler::learnerPayload(array $configuration, array $workingState): array` and `createInteractiveActivity(config, request)`.
- Produces: learner payload key `completed_matches: Array<{left_id: string, right_id: string}>`; scoped `interactive-activity-state`, `interactive-activity-payload`, and `interactive-activity-practice` events containing `activityId`.

- [ ] **Step 1: Add failing handler and flow assertions for solved relationships**

Extend the existing Matching handler test with an accepted solved pair and assert the safe payload shape:

```php
$payload = $handler->learnerPayload($configuration, [
    'right_order' => [$configuration['pairs'][1]['right']['id'], $configuration['pairs'][0]['right']['id']],
    'matched' => [[
        'left_id' => $configuration['pairs'][0]['left']['id'],
        'right_id' => $configuration['pairs'][0]['right']['id'],
    ]],
]);

$this->assertSame([[
    'left_id' => $configuration['pairs'][0]['left']['id'],
    'right_id' => $configuration['pairs'][0]['right']['id'],
]], $payload['completed_matches']);
$this->assertArrayNotHasKey('pairs', $payload);
$this->assertArrayNotHasKey('correct_position', $payload);
```

In `MatchingActivityFlowTest`, reload an activity after one correct proposal and assert that the JSON payload contains exactly that solved relationship but not the unresolved mapping.

- [ ] **Step 2: Run the focused PHP tests and confirm the missing key failure**

Run:

```powershell
php vendor/bin/phpunit --do-not-cache-result tests/Unit/Services/Learning/InteractiveActivityHandlerTest.php tests/Feature/Learner/MatchingActivityFlowTest.php --testdox
```

Expected: FAIL because `completed_matches` does not exist.

- [ ] **Step 3: Return only normalized solved relationships**

Add this helper to `MatchingActivityHandler`:

```php
/** @return list<array{left_id: string, right_id: string}> */
private function completedMatches(array $matches): array
{
    return array_values(array_filter(array_map(
        static fn ($match): ?array => is_array($match)
            && is_string($match['left_id'] ?? null)
            && is_string($match['right_id'] ?? null)
                ? ['left_id' => $match['left_id'], 'right_id' => $match['right_id']]
                : null,
        $matches,
    )));
}
```

Return it beside the existing independent ID sets:

```php
'completed_matches' => $this->completedMatches($matches),
```

Do not return configuration pairs or unresolved mappings.

- [ ] **Step 4: Add a failing JavaScript test for activity-scoped payload events**

Add:

```javascript
test('practice publishes the returned payload only to its activity instance', async () => {
    const events = [];
    const activity = createInteractiveActivity({
        activityId: 41,
        practiceUrl: '/practice',
    }, async () => response({
        status: 'practice',
        payload: { items: [{ id: 'fresh' }] },
    }));
    activity.$dispatch = (name, detail) => events.push([name, detail]);

    await activity.practice();

    assert.deepEqual(events.find(([name]) => name === 'interactive-activity-payload')[1], {
        activityId: 41,
        status: 'practice',
        payload: { items: [{ id: 'fresh' }] },
    });
});
```

- [ ] **Step 5: Run the JavaScript test and confirm the payload event is missing**

Run:

```powershell
node --test tests/JavaScript/interactive-activity.test.mjs
```

Expected: FAIL because `applyResponse()` does not dispatch a payload event.

- [ ] **Step 6: Scope lifecycle events and publish returned payloads**

Add `activityId: config.activityId` to the returned Alpine object. Update `applyResponse()` to dispatch activity identity and payload:

```javascript
applyResponse(data) {
    this.status = data.status ?? this.status;
    this.payload = data.payload ?? this.payload;
    this.explanation = data.explanation ?? null;
    this.practiceMode = this.status.startsWith('practice');
    this.error = '';
    const detail = { activityId: config.activityId, status: this.status, data };
    this.$dispatch?.('interactive-activity-state', detail);
    if (data.payload) {
        this.$dispatch?.('interactive-activity-payload', {
            activityId: config.activityId,
            status: this.status,
            payload: data.payload,
            previewToken: data.preview_token ?? null,
        });
    }
    return data;
},
```

Include `activityId` in local Preview skip/resume/practice/continue events. Dispatch `interactive-activity-practice` with the returned payload only after `send()` succeeds.

- [ ] **Step 7: Run focused tests and commit the safe state contract**

Run:

```powershell
php vendor/bin/phpunit --do-not-cache-result tests/Unit/Services/Learning/InteractiveActivityHandlerTest.php tests/Feature/Learner/MatchingActivityFlowTest.php --testdox
node --test tests/JavaScript/interactive-activity.test.mjs
```

Expected: PASS.

Commit:

```powershell
git add app/Services/Learning/InteractiveActivities/MatchingActivityHandler.php resources/js/interactive-activity.js tests/Unit/Services/Learning/InteractiveActivityHandlerTest.php tests/JavaScript/interactive-activity.test.mjs tests/Feature/Learner/MatchingActivityFlowTest.php
git commit -m "fix: restore interactive activity state"
```

---

### Task 2: Centralize accessible activity feedback

**Files:**

- Create: `resources/js/activity-feedback.js`
- Create: `tests/JavaScript/activity-feedback.test.mjs`
- Modify: `resources/js/interactive-activity.js`
- Modify: `resources/views/learner/lessons/partials/interactive-activities/shell.blade.php`
- Modify: `tests/JavaScript/interactive-activity.test.mjs`
- Modify: `tests/Feature/Learner/InteractiveActivityRenderingTest.php`

**Interfaces:**

- Consumes: evaluation objects with `is_correct`, `is_complete`, `status`, and optional progress metadata.
- Produces: `emptyActivityFeedback()`, `feedbackForEvaluation(type, data, meta)`, and `feedbackForLifecycle(status)` returning `{kind, message, icon}`; `createInteractiveActivity.handleActivityResult(detail)`.

- [ ] **Step 1: Write failing pure feedback tests**

Create `activity-feedback.test.mjs`:

```javascript
import test from 'node:test';
import assert from 'node:assert/strict';
import {
    emptyActivityFeedback,
    feedbackForEvaluation,
    feedbackForLifecycle,
} from '../../resources/js/activity-feedback.js';

test('matching feedback distinguishes progress, incorrect, and completion', () => {
    assert.deepEqual(feedbackForEvaluation('matching', { is_correct: true, is_complete: false }, { completed: 2, total: 4 }), {
        kind: 'correct', message: 'Correct match. 2 of 4 pairs complete.', icon: 'check',
    });
    assert.equal(feedbackForEvaluation('matching', { is_correct: false }).message,
        'Incorrect match. Remove or replace this connection and try again.');
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
```

- [ ] **Step 2: Run the feedback test and confirm the module is missing**

Run:

```powershell
node --test tests/JavaScript/activity-feedback.test.mjs
```

Expected: FAIL with module-not-found.

- [ ] **Step 3: Implement the pure feedback mapper**

Create `activity-feedback.js` with these exports:

```javascript
export const emptyActivityFeedback = () => ({ kind: 'idle', message: '', icon: null });

export function feedbackForEvaluation(type, data = {}, meta = {}) {
    if (data.is_complete === true) return { kind: 'completed', message: 'Correct. Activity complete.', icon: 'check' };
    if (data.is_correct === true && type === 'matching') {
        return {
            kind: 'correct',
            message: `Correct match. ${meta.completed ?? 0} of ${meta.total ?? 0} pairs complete.`,
            icon: 'check',
        };
    }
    if (type === 'matching') {
        return { kind: 'incorrect', message: 'Incorrect match. Remove or replace this connection and try again.', icon: 'x' };
    }
    return { kind: 'incorrect', message: 'Incorrect sequence. Reorder the items and try again.', icon: 'x' };
}

export function feedbackForLifecycle(status) {
    if (status === 'skipped') return { kind: 'skipped', message: 'Activity skipped. You can resume when ready.', icon: 'skip' };
    if (status === 'completed' || status === 'practice_completed') {
        return { kind: 'completed', message: 'Correct. Activity complete.', icon: 'check' };
    }
    return emptyActivityFeedback();
}
```

- [ ] **Step 4: Add failing common-shell integration tests**

Assert that `createInteractiveActivity` clears feedback on Resume/Practice, applies child result details through `handleActivityResult()`, and converts request failures to `{kind: 'error'}`. In `InteractiveActivityRenderingTest`, assert the shell has one `aria-live="polite"` feedback region, one conditional `role="alert"` error region, textual feedback, and completed explanation markup.

- [ ] **Step 5: Run tests and confirm shared shell behavior is absent**

Run:

```powershell
node --test tests/JavaScript/activity-feedback.test.mjs tests/JavaScript/interactive-activity.test.mjs
php vendor/bin/phpunit --do-not-cache-result tests/Feature/Learner/InteractiveActivityRenderingTest.php --testdox
```

Expected: FAIL on `handleActivityResult` and shared feedback markup.

- [ ] **Step 6: Integrate feedback into the common Alpine component and shell**

Import the feedback functions in `interactive-activity.js`, initialize `feedback`, and add:

```javascript
clearFeedback() {
    this.feedback = emptyActivityFeedback();
    return this;
},

handleActivityResult(detail = {}) {
    if (detail.activityId !== config.activityId) return this;
    this.status = detail.data?.status ?? this.status;
    this.explanation = detail.data?.explanation ?? null;
    this.feedback = feedbackForEvaluation(detail.type, detail.data, detail.meta);
    return this;
},
```

Set lifecycle feedback after Skip and clear it after Resume or Practice. Set `{kind: 'error', message, icon: 'warning'}` in `send()` failures.

Treat `practice_completed` as complete for display helpers without changing persisted progress:

```javascript
showContinue() {
    return ['completed', 'practice_completed', 'skipped'].includes(this.status);
},

showPracticeAgain() {
    return ['completed', 'practice_completed'].includes(this.status);
},
```

Use `showPracticeAgain()` in the shell instead of checking only `status === 'completed'`.

In `shell.blade.php`, handle scoped `interactive-activity-result`, render one icon-plus-text result region, render request errors separately, and render sanitized explanation only when non-empty:

```blade
<div x-show="feedback.message" aria-live="polite" role="status" class="mt-4 rounded-xl border px-4 py-3">
    <span aria-hidden="true" x-text="feedback.icon === 'check' ? '✓' : feedback.icon === 'x' ? '×' : '•'"></span>
    <span x-text="feedback.message"></span>
</div>
<div x-show="error" role="alert" class="mt-3 text-sm text-red-700" x-text="error"></div>
@if(!empty($activity['explanation']))
    <div x-show="['completed', 'practice_completed'].includes(status)" class="mt-4 rounded-xl border border-purple-100 bg-purple-50 p-4">
        <p class="text-xs font-semibold uppercase tracking-wide text-purple-700">Explanation</p>
        <div class="prose prose-sm mt-2 max-w-none">{!! $activity['explanation'] !!}</div>
    </div>
@endif
```

Render sanitized instructions as rich text in both learner and Preview contexts.

- [ ] **Step 7: Run focused tests and commit feedback centralization**

Run:

```powershell
node --test tests/JavaScript/activity-feedback.test.mjs tests/JavaScript/interactive-activity.test.mjs
php vendor/bin/phpunit --do-not-cache-result tests/Feature/Learner/InteractiveActivityRenderingTest.php --testdox
```

Expected: PASS.

Commit:

```powershell
git add resources/js/activity-feedback.js resources/js/interactive-activity.js resources/views/learner/lessons/partials/interactive-activities/shell.blade.php tests/JavaScript/activity-feedback.test.mjs tests/JavaScript/interactive-activity.test.mjs tests/Feature/Learner/InteractiveActivityRenderingTest.php
git commit -m "feat: unify interactive activity feedback"
```

---

### Task 3: Build the Matching connection interaction

**Files:**

- Modify: `resources/js/matching-activity.js`
- Modify: `resources/css/components.css`
- Modify: `resources/views/learner/lessons/partials/interactive-activities/matching.blade.php`
- Modify: `tests/JavaScript/matching-activity.test.mjs`
- Modify: `tests/Feature/Learner/InteractiveActivityRenderingTest.php`

**Interfaces:**

- Consumes: `completed_matches`, existing `matchUrl`, `revision`, `practice`, and scoped payload events.
- Produces: `normalizeProposal(source, target)`, `connectorPoint(rect, containerRect)`, `createMatchingActivity.startConnection()`, `moveConnection()`, `finishConnection()`, `activateEndpoint()`, `removeRejectedConnection()`, `loadPayload()`, and `refreshConnectors()`.

- [ ] **Step 1: Replace click-selection tests with failing connection-state tests**

Cover these pure and component behaviors:

```javascript
test('a connection can begin from either side and normalizes to the server shape', () => {
    assert.deepEqual(normalizeProposal(
        { side: 'right', id: 'right-1' },
        { side: 'left', id: 'left-1' },
    ), { left_id: 'left-1', right_id: 'right-1' });
});

test('incorrect connections remain removable and are replaced from either endpoint', async () => {
    const activity = createMatchingActivity({ matchUrl: '/match' }, async () => response({
        status: 'in_progress', accepted: true, is_correct: false, is_complete: false,
    }));
    activity.startConnection('left', 'left-1');
    await activity.finishConnection('right', 'right-2');
    assert.deepEqual(activity.rejectedConnection, { left_id: 'left-1', right_id: 'right-2' });
    activity.removeRejectedConnection();
    assert.equal(activity.rejectedConnection, null);
});
```

Also test valid-target rules, invalid drop cancellation, pending request state, failed-request neutral state, confirmed endpoint locking, keyboard activation/Escape, payload rehydration, and dot-centered line coordinates.

- [ ] **Step 2: Run Matching tests and confirm the direct-manipulation API is absent**

Run:

```powershell
node --test tests/JavaScript/matching-activity.test.mjs
```

Expected: FAIL on missing exports and methods.

- [ ] **Step 3: Add geometry and proposal primitives**

Replace card-center geometry with:

```javascript
export function normalizeProposal(source, target) {
    if (!source || !target || source.side === target.side) return null;
    return source.side === 'left'
        ? { left_id: source.id, right_id: target.id }
        : { left_id: target.id, right_id: source.id };
}

export function connectorPoint(rect, containerRect) {
    return {
        x: rect.left + rect.width / 2 - containerRect.left,
        y: rect.top + rect.height / 2 - containerRect.top,
    };
}
```

Build persistent lines from `[data-match-dot-side="left|right"][data-match-id]` elements. Preserve the existing `calculateConnectorLines` export only as a compatibility wrapper if an existing caller still requires it.

- [ ] **Step 4: Implement the Matching gesture state machine**

Add `const copy = (value) => JSON.parse(JSON.stringify(value));` beside `readResponse()`, add `activityId: config.activityId` to the returned Alpine object, then replace `leftId/rightId` selection state with:

```javascript
activeEndpoint: null,
hoveredEndpoint: null,
pointerPosition: null,
pendingConnection: null,
rejectedConnection: null,
matchedPairs: copy(config.initialMatchedPairs ?? []),
```

Add methods with these exact responsibilities:

```javascript
startConnection(side, id, event = null)       // validate availability, clear replaceable rejection, capture source and pointer
moveConnection(event)                         // rAF-throttled pointer coordinates and elementFromPoint target lookup
finishConnection(side = null, id = null)      // normalize target; cancel invalid; call submitMatch for valid proposal
cancelConnection()                            // clear only provisional gesture state
activateEndpoint(side, id, event = null)      // Space/Enter source/target toggle; Escape cancellation
removeRejectedConnection()                    // clear rejected line and feedback
isEndpointAvailable(side, id)                 // false for confirmed endpoint or locked activity
isValidTarget(side, id)                       // opposite side, available, and active source exists
loadPayload(payload, status)                  // replace items and completed_matches, clear transient state, refresh geometry
```

On a correct response, append the normalized proposal once and dispatch:

```javascript
this.$dispatch?.('interactive-activity-result', {
    activityId: config.activityId,
    type: 'matching',
    data,
    meta: { completed: this.matchedPairs.length, total: this.leftItems.length },
});
```

On an incorrect response, assign `rejectedConnection`. On request failure, retain `pendingConnection` with `requestState: 'error'` and do not assign `rejectedConnection`.

- [ ] **Step 5: Replace Matching markup with dots, lines, and non-color states**

Update the partial so:

- the SVG is visible at every container width;
- one line template renders confirmed, pending, and rejected lines with distinct classes and markers;
- every endpoint is a real button with `data-match-dot-side`, `data-match-id`, a 44-pixel hit area, visible focus, `aria-pressed`, and state-aware label;
- cards display check/x text badges where applicable;
- the old `Check match` button is removed because drop evaluates immediately;
- an accessible Remove incorrect connection button appears for the rejected proposal; and
- window-scoped payload/practice events check `activityId` before calling `loadPayload()`.

Use scoped event conditions:

```blade
@interactive-activity-payload.window="if ($event.detail.activityId === activityId) loadPayload($event.detail.payload, $event.detail.status)"
@keydown.escape.window="if (activeEndpoint) cancelConnection()"
```

- [ ] **Step 6: Add focused activity CSS**

In `components.css`, add an `interactive-activity` component block with:

```css
.interactive-activity-container { container-type: inline-size; }
.interactive-match-grid { display: grid; grid-template-columns: minmax(0, 1fr) clamp(2rem, 9cqi, 5rem) minmax(0, 1fr); }
.interactive-match-dot { min-width: 2.75rem; min-height: 2.75rem; touch-action: none; }
.interactive-match-line--correct { stroke-width: 3; stroke-linecap: round; }
.interactive-match-line--incorrect { stroke-width: 3; stroke-dasharray: 8 6; }
.interactive-match-line--pending { stroke-width: 3; stroke-dasharray: 3 5; }
```

Add card state borders/icons and a narrow-container rule that reduces padding without removing the two-column relationship. Add reduced-motion overrides for line and card animation.

- [ ] **Step 7: Add rendering assertions and run focused tests**

Assert endpoint buttons, SVG markers, status labels, no `Check match` button, no mobile-only textual replacement, and no unresolved mapping in rendered output.

Run:

```powershell
node --test tests/JavaScript/matching-activity.test.mjs tests/JavaScript/activity-feedback.test.mjs
php vendor/bin/phpunit --do-not-cache-result tests/Feature/Learner/InteractiveActivityRenderingTest.php tests/Feature/Learner/MatchingActivityFlowTest.php --testdox
```

Expected: PASS.

- [ ] **Step 8: Commit the learner Matching interaction**

```powershell
git add resources/js/matching-activity.js resources/css/components.css resources/views/learner/lessons/partials/interactive-activities/matching.blade.php tests/JavaScript/matching-activity.test.mjs tests/Feature/Learner/InteractiveActivityRenderingTest.php
git commit -m "feat: connect matching activity pairs"
```

---

### Task 4: Add the shared reorder primitive

**Files:**

- Create: `resources/js/pointer-reorder.js`
- Create: `tests/JavaScript/pointer-reorder.test.mjs`
- Modify: `resources/js/sequencing-activity.js`
- Modify: `tests/JavaScript/sequencing-activity.test.mjs`

**Interfaces:**

- Produces: `moveAt(items, from, to)`, `keyboardDestination(key, current, length)`, `edgeScrollDelta(clientY, viewportHeight, threshold, maximum)`, and `createReorderSession()`.
- Consumers: learner Sequencing and both authoring builders.

- [ ] **Step 1: Write failing reorder primitive tests**

Create tests for bounded movement, unchanged input arrays, session cancellation, commit, keyboard keys, and edge scrolling:

```javascript
test('reorder session does not mutate order until commit', () => {
    const session = createReorderSession();
    const order = ['one', 'two', 'three'];
    session.begin(0).target(2);
    assert.deepEqual(order, ['one', 'two', 'three']);
    assert.deepEqual(session.commit(order), ['two', 'three', 'one']);
});

test('keyboard and edge destinations are bounded', () => {
    assert.equal(keyboardDestination('Home', 2, 4), 0);
    assert.equal(keyboardDestination('End', 1, 4), 3);
    assert.equal(keyboardDestination('ArrowUp', 0, 4), 0);
    assert.equal(edgeScrollDelta(10, 800, 72, 16), -16);
    assert.equal(edgeScrollDelta(790, 800, 72, 16), 16);
    assert.equal(edgeScrollDelta(400, 800, 72, 16), 0);
});
```

- [ ] **Step 2: Run and confirm the module is missing**

Run:

```powershell
node --test tests/JavaScript/pointer-reorder.test.mjs
```

Expected: FAIL with module-not-found.

- [ ] **Step 3: Implement the dependency-free primitives**

Create:

```javascript
const clamp = (value, minimum, maximum) => Math.min(maximum, Math.max(minimum, value));

export function moveAt(items, from, to) {
    const next = [...items];
    if (from < 0 || from >= next.length || to < 0 || to >= next.length || from === to) return next;
    const [item] = next.splice(from, 1);
    next.splice(to, 0, item);
    return next;
}

export function keyboardDestination(key, current, length) {
    if (length < 1) return 0;
    if (key === 'Home') return 0;
    if (key === 'End') return length - 1;
    if (key === 'ArrowUp') return clamp(current - 1, 0, length - 1);
    if (key === 'ArrowDown') return clamp(current + 1, 0, length - 1);
    return current;
}

export function edgeScrollDelta(clientY, viewportHeight, threshold = 72, maximum = 16) {
    if (clientY < threshold) return -maximum;
    if (clientY > viewportHeight - threshold) return maximum;
    return 0;
}

export function createReorderSession() {
    return {
        from: null,
        to: null,
        begin(index) { this.from = index; this.to = index; return this; },
        target(index) { if (this.from !== null) this.to = index; return this; },
        active() { return this.from !== null; },
        commit(items) {
            const next = this.active() ? moveAt(items, this.from, this.to) : [...items];
            this.cancel();
            return next;
        },
        cancel() { this.from = null; this.to = null; return this; },
    };
}
```

- [ ] **Step 4: Route the existing Sequencing array movement through `moveAt`**

Keep `moveItem` temporarily as a compatibility export:

```javascript
export function moveItem(order, index, delta) {
    return moveAt(order, index, index + delta);
}
```

No markup behavior changes in this task.

- [ ] **Step 5: Run focused tests and commit the primitive**

Run:

```powershell
node --test tests/JavaScript/pointer-reorder.test.mjs tests/JavaScript/sequencing-activity.test.mjs
```

Expected: PASS.

Commit:

```powershell
git add resources/js/pointer-reorder.js resources/js/sequencing-activity.js tests/JavaScript/pointer-reorder.test.mjs tests/JavaScript/sequencing-activity.test.mjs
git commit -m "refactor: share activity reorder state"
```

---

### Task 5: Replace learner Sequencing controls with pointer and keyboard dragging

**Files:**

- Modify: `resources/js/sequencing-activity.js`
- Modify: `resources/css/components.css`
- Modify: `resources/views/learner/lessons/partials/interactive-activities/sequencing.blade.php`
- Modify: `tests/JavaScript/sequencing-activity.test.mjs`
- Modify: `tests/Feature/Learner/InteractiveActivityRenderingTest.php`
- Modify: `tests/Feature/Learner/SequencingActivityFlowTest.php`

**Interfaces:**

- Consumes: `createReorderSession`, `keyboardDestination`, `edgeScrollDelta`, existing state/check endpoints, and scoped payload events.
- Produces: `beginPointerDrag`, `movePointerDrag`, `dropPointerDrag`, `cancelDrag`, `handleDragKey`, `loadPayload`, `candidateOrder`, and `dragAnnouncement`.

- [ ] **Step 1: Write failing pointer and keyboard drag tests**

Replace button-oriented expectations with tests asserting:

```javascript
test('pointer drag keeps the committed order stable until drop', () => {
    const activity = createSequencingActivity({ initialOrder: ['one', 'two', 'three'] });
    activity.beginPointerDrag(0, { clientX: 20, clientY: 40 });
    activity.setDragTarget(2);
    assert.deepEqual(activity.order, ['one', 'two', 'three']);
    activity.dropPointerDrag();
    assert.deepEqual(activity.order, ['two', 'three', 'one']);
});

test('keyboard drag supports pickup move drop and cancellation', () => {
    const activity = createSequencingActivity({ initialOrder: ['one', 'two', 'three'] });
    activity.handleDragKey(1, { key: ' ', preventDefault() {} });
    activity.handleDragKey(1, { key: 'Home', preventDefault() {} });
    activity.handleDragKey(1, { key: 'Enter', preventDefault() {} });
    assert.deepEqual(activity.order, ['two', 'one', 'three']);
    assert.match(activity.dragAnnouncement, /Dropped/);
});
```

Also cover Escape, outside drop, pointer cancellation, one scheduled save after commit, item labels, lock state, incorrect-feedback clearing, and Practice payload replacement.

- [ ] **Step 2: Run Sequencing tests and confirm the new API fails**

Run:

```powershell
node --test tests/JavaScript/pointer-reorder.test.mjs tests/JavaScript/sequencing-activity.test.mjs
```

Expected: FAIL on missing pointer/keyboard drag methods.

- [ ] **Step 3: Implement committed-versus-candidate drag state**

Initialize:

```javascript
activityId: config.activityId,
reorder: createReorderSession(),
draggedId: null,
dragPoint: null,
dragRect: null,
dragAnnouncement: '',
autoScrollFrame: null,
lastPointerY: null,
```

Implement methods so `order` changes only inside `commitDrag()`. `setDragTarget(index)` updates the session destination and insertion UI. `handleDragKey()` uses the same session and calls `scheduleSave()` only after a successful drop. `cancelDrag()` stops auto-scroll and clears transient state without changing `order`.

Use this announcement format:

```javascript
announcement(action, index) {
    const label = this.itemFor(this.draggedId)?.value ?? 'Item';
    return `${action} ${label}, position ${index + 1} of ${this.order.length}.`;
},
```

Use `edgeScrollDelta()` in one animation-frame loop while a pointer drag is active. Disable smooth scrolling when reduced motion is requested.

- [ ] **Step 4: Replace Sequencing markup**

Remove both arrow buttons and their click handlers. Add:

- a focusable drag-handle button with `aria-describedby` instructions;
- `aria-pressed` while picked up;
- pointerdown/move/up/cancel handlers;
- a subdued source placeholder;
- an insertion bar before or after the candidate row;
- a fixed-position drag overlay using captured row dimensions; and
- one visually hidden live region bound to `dragAnnouncement`.

The handle uses `touch-action: none`; the list and cards do not.

- [ ] **Step 5: Add sequencing drag CSS and reduced-motion rules**

Add component classes for active row elevation, floating overlay, source opacity, insertion bar, animated placeholder height, grab/grabbing cursors, and immediate reduced-motion transitions.

- [ ] **Step 6: Add rendering and server-flow regressions**

Assert that learner markup contains the drag instructions and handle but does not contain visible `Move ... up`, `Move ... down`, `↑`, or `↓` controls. Preserve existing feature assertions that moving does not complete, incorrect checking preserves order, and correct checking alone completes.

- [ ] **Step 7: Run focused tests and commit learner Sequencing**

Run:

```powershell
node --test tests/JavaScript/pointer-reorder.test.mjs tests/JavaScript/sequencing-activity.test.mjs tests/JavaScript/activity-feedback.test.mjs
php vendor/bin/phpunit --do-not-cache-result tests/Feature/Learner/InteractiveActivityRenderingTest.php tests/Feature/Learner/SequencingActivityFlowTest.php --testdox
```

Expected: PASS.

Commit:

```powershell
git add resources/js/sequencing-activity.js resources/css/components.css resources/views/learner/lessons/partials/interactive-activities/sequencing.blade.php tests/JavaScript/sequencing-activity.test.mjs tests/Feature/Learner/InteractiveActivityRenderingTest.php tests/Feature/Learner/SequencingActivityFlowTest.php
git commit -m "feat: drag sequence activity items"
```

---

### Task 6: Refine Matching and Sequencing authoring

**Files:**

- Modify: `resources/js/interactive-activity-authoring.js`
- Modify: `resources/views/instructor/topics/partials/interactive-activity-fields.blade.php`
- Modify: `resources/views/instructor/topics/partials/matching-builder.blade.php`
- Modify: `resources/views/instructor/topics/partials/sequencing-builder.blade.php`
- Modify: `resources/css/components.css`
- Modify: `tests/JavaScript/interactive-activity-authoring.test.mjs`
- Modify: `tests/Feature/Instructor/InteractiveActivityAuthoringTest.php`

**Interfaces:**

- Consumes: `createReorderSession()` and `keyboardDestination()`.
- Produces: `beginAuthoringDrag(kind, index)`, `targetAuthoringDrag(index)`, `dropAuthoringDrag()`, `handleAuthoringDragKey(kind, index, event)`, and `focusAfterRemoval(kind, index)`.

- [ ] **Step 1: Write failing authoring interaction tests**

Add tests that pair rows stay atomic through pointer and keyboard reorder, Sequence item positions derive from committed array order, Escape cancels, removal returns a focus selector, and no button-only move method is required:

```javascript
test('matching and sequencing use the shared authoring reorder session', () => {
    const authoring = createInteractiveActivityAuthoring({
        pairs: [pair('one'), pair('two')],
        items: [item('one'), item('two'), item('three')],
    });
    authoring.beginAuthoringDrag('pairs', 0).targetAuthoringDrag(1).dropAuthoringDrag();
    assert.deepEqual(authoring.pairs.map(({ id }) => id), ['two', 'one']);
    authoring.beginAuthoringDrag('items', 2).targetAuthoringDrag(0).dropAuthoringDrag();
    assert.deepEqual(authoring.configuration().items.map(({ id }) => id), ['three', 'one', 'two']);
    assert.deepEqual(authoring.configuration().items.map(({ correct_position }) => correct_position), [1, 2, 3]);
});
```

- [ ] **Step 2: Run authoring tests and confirm shared methods are absent**

Run:

```powershell
node --test tests/JavaScript/interactive-activity-authoring.test.mjs tests/JavaScript/pointer-reorder.test.mjs
```

Expected: FAIL on missing authoring drag methods.

- [ ] **Step 3: Replace duplicate movement state with the shared session**

Import the reorder primitives. Keep one `authoringReorder` session and `authoringCollection` discriminator. `dropAuthoringDrag()` commits to `pairs` or `items`; `handleAuthoringDragKey()` uses the same pickup/move/drop/cancel contract as learner Sequencing.

Return focus selectors after removal:

```javascript
focusTargetAfterRemoval(kind, removedIndex) {
    const collection = kind === 'pairs' ? this.pairs : this.items;
    if (collection.length === 0) return `[data-add-${kind}]`;
    return `[data-${kind}-handle="${Math.min(removedIndex, collection.length - 1)}"]`;
},
```

Use Alpine `$nextTick` in the partial to focus the returned target.

- [ ] **Step 4: Redesign Matching authoring rows**

Render each pair as one grid row with a drag handle, left field, decorative dot/line/dot relationship, right field, and Remove action. Preserve every existing hidden ID/kind input and indexed error key. Add pickup/drop instructions and a live reorder announcement.

- [ ] **Step 5: Redesign Sequencing authoring rows**

Remove visible Up/Down controls. Render `Correct position ${index + 1}`, one focusable drag handle, editable item text, Remove action, insertion indicator, and shared pointer/keyboard events. Preserve hidden IDs, `kind=text`, indexed names, min/max rules, and derived positions.

- [ ] **Step 6: Add feature rendering and save/reopen assertions**

Assert both create and edit pages render the new handles and relationship markup, contain no authoring Up/Down controls, preserve submitted IDs/order, reject incomplete configurations, and reopen saved order correctly. Do not modify `resources/views/instructor/topics/create.blade.php`; exercise its existing partial include.

- [ ] **Step 7: Run focused tests and commit authoring UX**

Run:

```powershell
node --test tests/JavaScript/interactive-activity-authoring.test.mjs tests/JavaScript/pointer-reorder.test.mjs
php vendor/bin/phpunit --do-not-cache-result tests/Feature/Instructor/InteractiveActivityAuthoringTest.php --testdox
```

Expected: PASS.

Commit:

```powershell
git add resources/js/interactive-activity-authoring.js resources/views/instructor/topics/partials/interactive-activity-fields.blade.php resources/views/instructor/topics/partials/matching-builder.blade.php resources/views/instructor/topics/partials/sequencing-builder.blade.php resources/css/components.css tests/JavaScript/interactive-activity-authoring.test.mjs tests/Feature/Instructor/InteractiveActivityAuthoringTest.php
git commit -m "feat: refine interactive activity authoring"
```

---

### Task 7: Route unsaved Preview evaluation through the canonical handlers

**Files:**

- Modify: `app/Services/Learning/InteractiveActivities/InteractiveActivityAuthoringService.php`
- Modify: `app/Http/Controllers/Instructor/InteractiveActivityController.php`
- Modify: `routes/instructor.php`
- Modify: `routes/admin.php`
- Modify: `resources/js/interactive-activity.js`
- Modify: `resources/js/matching-activity.js`
- Modify: `resources/js/sequencing-activity.js`
- Modify: `resources/js/interactive-activity-authoring.js`
- Modify: `resources/views/learner/lessons/partials/interactive-activities/shell.blade.php`
- Modify: `resources/views/learner/lessons/partials/interactive-activities/matching.blade.php`
- Modify: `resources/views/learner/lessons/partials/interactive-activities/sequencing.blade.php`
- Modify: `resources/views/instructor/topics/partials/interactive-activity-fields.blade.php`
- Modify: `resources/views/instructor/topics/partials/interactive-activity-preview-modal.blade.php`
- Modify: `tests/Feature/Instructor/InteractiveActivityAuthoringTest.php`
- Modify: `tests/JavaScript/interactive-activity-authoring.test.mjs`
- Modify: `tests/JavaScript/matching-activity.test.mjs`
- Modify: `tests/JavaScript/sequencing-activity.test.mjs`

**Interfaces:**

- Consumes: registered `InteractiveActivityHandler::initialWorkingState()`, `evaluate()`, and `learnerPayload()`.
- Produces: `InteractiveActivityAuthoringService::preview(Lesson $lesson, array $data, User $author): array`, `decodePreviewToken(string $token, User $author): array`, `evaluatePreview(array $context, array $answer): array`, and route names `instructor.interactive-activities.preview-evaluate` / `admin.interactive-activities.preview-evaluate`.

- [ ] **Step 1: Add failing Preview security and canonical-evaluation tests**

Extend `InteractiveActivityAuthoringTest` to assert:

1. Initial Preview HTML contains `preview_token` and the panel-specific evaluation URL.
2. It does not contain `preview_answer_key`, unresolved Matching mappings, or Sequencing `correct_position`.
3. Posting a correct and incorrect Matching answer to the Preview evaluation route returns the handler result and rotated token.
4. Posting a Sequence order returns the handler result and preserves incorrect order.
5. `action=practice` returns a fresh non-correct shuffled payload.
6. Preview evaluation does not create `InteractiveActivityProgress`.
7. Tampered, expired, cross-user, wrong-Lesson, and wrong-action/type tokens return 422 or 403 as appropriate.
8. Admin Preview uses the admin route and authorization context.

Use `Carbon::setTestNow()` to test the exact 15-minute expiry.

- [ ] **Step 2: Run the Preview feature tests and confirm route/token failures**

Run:

```powershell
php vendor/bin/phpunit --do-not-cache-result tests/Feature/Instructor/InteractiveActivityAuthoringTest.php --testdox
```

Expected: FAIL because Preview HTML exposes a client answer key and no evaluation route exists.

- [ ] **Step 3: Add panel-specific Preview evaluation routes**

In both route files, place the route beside the existing Preview route and before parameterized activity routes:

```php
Route::post('interactive-activities/preview/evaluate', [Instructor\InteractiveActivityController::class, 'evaluatePreview'])
    ->name('interactive-activities.preview-evaluate');
```

- [ ] **Step 4: Add encrypted Preview context methods**

Import `App\Models\User`, `Illuminate\Contracts\Encryption\DecryptException`, `Illuminate\Support\Facades\Crypt`, and `Illuminate\Validation\ValidationException`. Add a 900-second TTL.

Resolve `$type = InteractiveActivityType::from($data['activity_type'])`, then issue tokens with this exact envelope:

```php
[
    'version' => 1,
    'author_id' => $author->id,
    'lesson_id' => $lesson->id,
    'activity_type' => $type->value,
    'configuration' => $configuration,
    'working_state' => $workingState,
    'explanation' => $data['explanation'],
    'expires_at' => now()->addSeconds(self::PREVIEW_TTL_SECONDS)->timestamp,
]
```

Serialize with `json_encode(..., JSON_THROW_ON_ERROR)` and encrypt with `Crypt::encryptString()`.

`decodePreviewToken()` must catch `DecryptException`/JSON exceptions and raise:

```php
throw ValidationException::withMessages([
    'preview_token' => 'The activity preview expired or is invalid. Generate a new preview.',
]);
```

It must also reject wrong version, missing arrays, mismatched author ID, and `expires_at < now()->timestamp`. Resolve and normalize configuration through the registry after decryption.

Change the Preview activity array to use `status => 'practice'`, include the encrypted value as `preview_token`, and remove `preview_answer_key` entirely.

- [ ] **Step 5: Implement handler-backed Preview evaluation**

`evaluatePreview()` accepts one decoded context and an answer. It resolves the handler, uses the token working state, and supports:

```text
match           -> left_id + right_id
check_sequence  -> item_order
practice        -> new handler initialWorkingState
```

Reject an action that does not match the token activity type. For evaluation, call the existing handler and return:

```php
[
    ...$result,
    'status' => $result['is_complete'] ? 'practice_completed' : 'practice',
    'payload' => $handler->learnerPayload($configuration, $result['working_state']),
    'explanation' => $result['is_complete'] ? $context['explanation'] : null,
    'preview_token' => $this->issuePreviewTokenFromContext($context, $result['working_state']),
]
```

Add sanitized `explanation` to the encrypted context. For `practice`, return a new non-correct state, null correctness, null explanation, and a rotated token.

- [ ] **Step 6: Authorize and expose Preview evaluation in the controller**

Change `preview()` to pass `$request->user()` into Preview generation and append:

```php
$activity['preview_evaluate_url'] = route($this->routeName('interactive-activities.preview-evaluate'));
```

Add `evaluatePreview(Request $request): JsonResponse`. Validate `preview_token`, `action`, optional IDs, and optional order; decode the token; fetch the bound Lesson; call the same `authorize('update', $lesson)` and `ensureAdminCanMutateLesson()` checks; then return the authoring service result.

Use explicit action-dependent validation after token decoding so Matching cannot accept a Sequence order and Sequencing cannot accept pair IDs.

- [ ] **Step 7: Remove client-side Preview answer keys**

Remove `preview_answer_key` from the Preview activity array and all Blade configs. Store `previewToken` and `previewEvaluateUrl` in the shared parent and type-specific Alpine components. For Preview Match, Sequence check, and Practice, POST:

```javascript
{
    preview_token: this.previewToken,
    action: 'match' | 'check_sequence' | 'practice',
    left_id,
    right_id,
    item_order,
}
```

Apply `data.preview_token` after every successful request. A child evaluation updates its local token and includes the result in `interactive-activity-result`; `handleActivityResult()` copies `data.preview_token` into the parent. Parent Practice includes the latest parent token, then publishes the rotated token beside the activity-scoped payload. `loadPayload(payload, status, previewToken)` updates each child token. Remove local `answerKey` comparisons from `matching-activity.js` and `sequencing-activity.js`.

- [ ] **Step 8: Update Preview modal recovery behavior and JavaScript tests**

If evaluation reports `preview_token` invalid/expired, show `The activity preview expired or is invalid. Generate a new preview.` and leave the author's unsaved form untouched. Closing and reopening regenerates the token.

Update JavaScript tests so Preview stubs assert network calls and rotated tokens rather than local answer-key comparisons.

- [ ] **Step 9: Run focused Preview tests and commit canonical Preview evaluation**

Run:

```powershell
php vendor/bin/phpunit --do-not-cache-result tests/Feature/Instructor/InteractiveActivityAuthoringTest.php tests/Unit/Services/Learning/InteractiveActivityHandlerTest.php --testdox
node --test tests/JavaScript/interactive-activity-authoring.test.mjs tests/JavaScript/interactive-activity.test.mjs tests/JavaScript/matching-activity.test.mjs tests/JavaScript/sequencing-activity.test.mjs
```

Expected: PASS.

Commit:

```powershell
git add app/Services/Learning/InteractiveActivities/InteractiveActivityAuthoringService.php app/Http/Controllers/Instructor/InteractiveActivityController.php routes/instructor.php routes/admin.php resources/js/interactive-activity.js resources/js/matching-activity.js resources/js/sequencing-activity.js resources/js/interactive-activity-authoring.js resources/views/learner/lessons/partials/interactive-activities/shell.blade.php resources/views/learner/lessons/partials/interactive-activities/matching.blade.php resources/views/learner/lessons/partials/interactive-activities/sequencing.blade.php resources/views/instructor/topics/partials/interactive-activity-fields.blade.php resources/views/instructor/topics/partials/interactive-activity-preview-modal.blade.php tests/Feature/Instructor/InteractiveActivityAuthoringTest.php tests/JavaScript/interactive-activity-authoring.test.mjs tests/JavaScript/matching-activity.test.mjs tests/JavaScript/sequencing-activity.test.mjs
git commit -m "feat: evaluate activity previews on server"
```

---

### Task 8: Complete accessibility, responsive, and reduced-motion contracts

**Files:**

- Modify: `resources/css/components.css`
- Modify: `resources/views/learner/lessons/partials/interactive-activities/shell.blade.php`
- Modify: `resources/views/learner/lessons/partials/interactive-activities/matching.blade.php`
- Modify: `resources/views/learner/lessons/partials/interactive-activities/sequencing.blade.php`
- Modify: `resources/views/instructor/topics/partials/matching-builder.blade.php`
- Modify: `resources/views/instructor/topics/partials/sequencing-builder.blade.php`
- Modify: `resources/views/instructor/topics/partials/interactive-activity-preview-modal.blade.php`
- Modify: `tests/Feature/Learner/InteractiveActivityRenderingTest.php`
- Modify: `tests/Feature/Instructor/InteractiveActivityAuthoringTest.php`

**Interfaces:**

- Consumes: final interaction APIs from Tasks 3, 5, 6, and 7.
- Produces: complete semantic markup and CSS contracts for focus, touch targets, container responsiveness, non-color feedback, and reduced motion.

- [ ] **Step 1: Add failing semantic rendering assertions**

Assert:

- the shell carries `interactive-activity-container` and one labelled feedback region;
- Matching dots are buttons with explicit side/item labels and at least one textual state badge;
- rejected connections have an accessible Remove action;
- Sequence handles include pickup/move/drop instructions and `aria-pressed` state;
- authoring handles expose equivalent instructions;
- Preview dialog retains `aria-modal`, label, Escape close, and trigger focus restoration;
- completed items remain readable; and
- no state relies on SVG or color alone.

- [ ] **Step 2: Run rendering tests and confirm missing semantics**

Run:

```powershell
php vendor/bin/phpunit --do-not-cache-result tests/Feature/Learner/InteractiveActivityRenderingTest.php tests/Feature/Instructor/InteractiveActivityAuthoringTest.php --testdox
```

Expected: FAIL on one or more new semantic assertions.

- [ ] **Step 3: Finish semantic markup and focus behavior**

Add concise visible instructions above each interaction, stable IDs for `aria-describedby`, status text next to icons, `aria-disabled` for completed discoverable controls, and logical DOM order. Do not disable completed text content or remove it from the accessibility tree.

Ensure Preview close restores `previewTrigger`, and authoring removal focuses the calculated surviving handle/Add action.

- [ ] **Step 4: Finish container and reduced-motion CSS**

Add:

```css
@container (max-width: 30rem) {
    .interactive-match-card { padding-inline: .625rem; }
    .interactive-match-grid { grid-template-columns: minmax(0, 1fr) 2rem minmax(0, 1fr); }
}

@media (prefers-reduced-motion: reduce) {
    .interactive-activity-container *,
    .interactive-activity-container *::before,
    .interactive-activity-container *::after {
        scroll-behavior: auto !important;
        animation-duration: .01ms !important;
        animation-iteration-count: 1 !important;
        transition-duration: .01ms !important;
    }
}
```

- [ ] **Step 5: Verify markup and commit the accessibility contract**

Run:

```powershell
php vendor/bin/phpunit --do-not-cache-result tests/Feature/Learner/InteractiveActivityRenderingTest.php tests/Feature/Instructor/InteractiveActivityAuthoringTest.php --testdox
node --test tests/JavaScript/activity-feedback.test.mjs tests/JavaScript/matching-activity.test.mjs tests/JavaScript/sequencing-activity.test.mjs tests/JavaScript/interactive-activity-authoring.test.mjs
```

Expected: PASS.

Commit:

```powershell
git add resources/css/components.css resources/views/learner/lessons/partials/interactive-activities/shell.blade.php resources/views/learner/lessons/partials/interactive-activities/matching.blade.php resources/views/learner/lessons/partials/interactive-activities/sequencing.blade.php resources/views/instructor/topics/partials/matching-builder.blade.php resources/views/instructor/topics/partials/sequencing-builder.blade.php resources/views/instructor/topics/partials/interactive-activity-preview-modal.blade.php tests/Feature/Learner/InteractiveActivityRenderingTest.php tests/Feature/Instructor/InteractiveActivityAuthoringTest.php
git commit -m "fix: make activity gestures accessible"
```

---

### Task 9: Run regression and browser QA

**Files:**

- Create: `docs/superpowers/verification/2026-09-09-interactive-activities-ux-refinement-e2e.md`
- Modify only if an observed defect requires a focused correction: files already listed in Tasks 1-8 and their focused tests.

**Interfaces:**

- Consumes: all refined learner, authoring, and Preview behavior.
- Produces: reproducible verification evidence with exact revision, commands, exit codes, test counts, environment, browser widths, and observed results.

- [ ] **Step 1: Run the complete JavaScript activity suite**

Run:

```powershell
node --test tests/JavaScript/activity-feedback.test.mjs tests/JavaScript/pointer-reorder.test.mjs tests/JavaScript/interactive-activity.test.mjs tests/JavaScript/matching-activity.test.mjs tests/JavaScript/sequencing-activity.test.mjs tests/JavaScript/interactive-activity-authoring.test.mjs tests/JavaScript/interactive-checkpoint.test.mjs
```

Expected: PASS with zero failures.

- [ ] **Step 2: Run the focused PHP activity matrix**

Run:

```powershell
php vendor/bin/phpunit --do-not-cache-result tests/Unit/Services/Learning/InteractiveActivityHandlerTest.php tests/Feature/Learner/InteractiveActivitySchemaTest.php tests/Feature/Learner/InteractiveActivityRenderingTest.php tests/Feature/Learner/MatchingActivityFlowTest.php tests/Feature/Learner/SequencingActivityFlowTest.php tests/Feature/Learner/InteractiveActivityProgressTest.php tests/Feature/Learner/InteractiveActivityProgressIsolationTest.php tests/Feature/Learner/InteractiveActivityQuizRegressionTest.php tests/Feature/Instructor/InteractiveActivityAuthoringTest.php tests/Feature/Instructor/LegacyInteractiveTopicRemovalTest.php --testdox
```

Expected: PASS with zero failures and errors.

- [ ] **Step 3: Run checkpoint, Topic, Lesson, and quiz regressions**

Run:

```powershell
php vendor/bin/phpunit --do-not-cache-result tests/Feature/Learner/InteractiveCheckpointFlowTest.php tests/Feature/Learner/InteractiveCheckpointRenderingTest.php tests/Feature/Learner/InteractiveCheckpointProgressIsolationTest.php tests/Feature/Learner/InteractiveCheckpointQuizRegressionTest.php tests/Feature/Instructor/InteractiveCheckpointAuthoringTest.php --testdox
```

Expected: PASS. Record any pre-existing failure separately and do not attribute it to this feature without reproducing it against the pre-change revision.

- [ ] **Step 4: Run formatting and production build checks**

Run:

```powershell
vendor\bin\pint --test app/Services/Learning/InteractiveActivities/MatchingActivityHandler.php app/Services/Learning/InteractiveActivities/InteractiveActivityAuthoringService.php app/Http/Controllers/Instructor/InteractiveActivityController.php routes/instructor.php routes/admin.php
pnpm.cmd build
```

Expected: Pint exits 0 and Vite build exits 0. Do not stage generated `public/build` output.

- [ ] **Step 5: Run the full PHPUnit suite**

Run:

```powershell
php vendor/bin/phpunit --do-not-cache-result
```

Expected: no new failures relative to the repository baseline. List every pre-existing failure with its test name and compare it with a clean pre-change run when feasible.

- [ ] **Step 6: Perform the learner browser matrix**

Using non-sensitive fixtures and the existing local app, record observed results for:

1. Desktop mouse Matching: start from left and right, pointer-following line, valid target, invalid target, correct lock, rejected dashed line, remove, replace, completion, explanation, Practice.
2. Mobile touch Matching at 375 CSS pixels: readable dual columns, 44-pixel dots, page scroll outside a gesture, connection during scroll, orientation/width change, restored lines after reload.
3. Tablet Matching at 768 CSS pixels: wrapping content, resized container, and accurate line endpoints.
4. Desktop Sequencing: floating row, stable placeholder, insertion bar, outside drop cancellation, committed drop, failed check, retry, completion.
5. Mobile touch Sequencing: handle-only drag, ordinary card scrolling, edge auto-scroll, visible insertion position, drop and cancellation.
6. Keyboard Matching: select, cancel, target, incorrect removal, retry, correct completion.
7. Keyboard Sequencing: Space/Enter pickup/drop, Arrow/Home/End movement, Escape cancellation, live announcements.
8. Reduced-motion Matching and Sequencing: immediate state changes with all labels/icons intact.

- [ ] **Step 7: Perform the authoring and Preview browser matrix**

Record:

1. Create and edit Matching; add, edit, remove, pointer-reorder, keyboard-reorder, Preview, save, and reopen.
2. Create and edit Sequencing; add, edit, remove, pointer-reorder, keyboard-reorder, Preview, save, and reopen.
3. Incomplete, duplicate, minimum, maximum, and blank configuration validation.
4. Preview at 375, 768, and desktop widths using the actual activity container layout.
5. Correct and incorrect Preview interactions without learner progress changes.
6. Preview token expiry/tamper recovery without losing unsaved form values.
7. Preview Escape close and trigger focus restoration.
8. Instructor and admin authorization behavior.

- [ ] **Step 8: Write the verification report**

Record:

- Git revision;
- PHP, Laravel, PHPUnit, Node, pnpm, and browser versions;
- exact commands and exit codes;
- JavaScript and PHP test counts;
- Pint and Vite outcomes;
- full-suite baseline comparison;
- every browser scenario and observed result;
- mobile/tablet/desktop widths;
- reduced-motion result;
- confirmation that Preview exposed no answer key and created no progress;
- confirmation that no migration or dependency was added; and
- confirmation that Topic/Lesson/checkpoint/quiz rules remained unchanged.

- [ ] **Step 9: Commit the verification evidence and any focused fixes**

Commit each observed fix with its reproducing test before the report. Then:

```powershell
git add docs/superpowers/verification/2026-09-09-interactive-activities-ux-refinement-e2e.md
git commit -m "docs: verify interactive activity ux"
```

Do not stage unrelated worktree changes or generated build assets.

---

## Plan Self-Review

- Tasks 1-2 cover restored state, fresh Practice payloads, explanations, and consistent feedback.
- Task 3 covers Matching dots, bidirectional pointer/touch/keyboard connection, temporary/pending/correct/incorrect lines, conflict prevention, responsive geometry, scrolling, and resizing.
- Tasks 4-5 cover one shared reorder primitive, learner drag overlay, stable insertion, keyboard drag, touch scrolling, edge auto-scroll, persistence, retry, and removal of visible Up/Down controls.
- Task 6 covers visual Matching authoring, shared Sequencing authoring drag behavior, dynamic add/edit/remove, focus, validation, stable IDs, save, and reopen.
- Task 7 removes client correctness duplication and makes Preview use the actual renderer and registered handlers without progress writes or answer-key exposure.
- Task 8 covers semantic instructions, focus, non-color states, touch targets, container behavior, and reduced motion.
- Task 9 covers the requested learner, authoring, Preview, Topic, Lesson, checkpoint, quiz, progress, permission, formatting, build, and browser regressions.
- Function and route names are consistent across producer and consumer tasks.
- The plan contains no migration, dependency, second renderer, or unrelated refactor.
