# Interactive Activities Learner Validation and Matching Fixes Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Make learner Sequencing validate and display every position accurately, and make Matching deliver a reliable Select, Connect, Check, Retry, and Continue experience.

**Architecture:** Keep the registered PHP handlers authoritative and return explicit per-item evaluation details. Repair only legacy zero-attempt initial state, rehydrate saved completion, and refine the existing Alpine/SVG interaction instead of creating a second renderer.

**Tech Stack:** PHP 8.2, Laravel 12, Blade, Alpine.js 3, Tailwind CSS 3, native Pointer Events, SVG, ResizeObserver, Node test runner, PHPUnit 11, Vite 7.

## Global Constraints

- Work directly on `main`; do not create a worktree.
- Preserve configuration schema version `1` and all existing completion/progression rules.
- A completed activity remains locked and displays its saved correct result.
- Practice Again starts a fresh randomized non-mutating attempt.
- PHP handlers remain the sole answer authority; never expose an answer key to JavaScript.
- Retry preserves current order/connections and correct Matching pairs remain locked.
- Add no dependency, migration, parallel renderer, or unrelated refactor.
- Preserve unrelated dirty files and use the existing Blade, Alpine, Tailwind, and SVG patterns.
- Keep 44-by-44-pixel touch targets, visible focus, non-color state labels/icons, keyboard alternatives, and reduced-motion behavior.

## File Structure

- Modify `app/Services/Learning/InteractiveActivities/InteractiveActivityProgressService.php` to repair only legacy zero-attempt canonical Sequencing state.
- Modify `app/Services/Learning/InteractiveActivities/MatchingActivityHandler.php` so batch results cover correct, incorrect, and unanswered left items and only a complete correct set reports overall correctness.
- Modify `resources/js/sequencing-activity.js` to rehydrate persisted completed payloads and preserve accurate per-card results.
- Modify `resources/js/matching-activity.js` to use tap/click endpoint activation, visible temporary and persistent lines, complete practice state, independent pair results, and completed-payload rehydration.
- Modify learner Matching/Sequencing Blade partials and `resources/css/components.css` only where semantic controls/states need adjustment.
- Extend focused PHP and JavaScript tests; add verification evidence under `docs/superpowers/verification/`.

---

### Task 1: Repair Fresh Sequencing State Without Resetting Real Progress

**Files:**
- Modify: `app/Services/Learning/InteractiveActivities/InteractiveActivityProgressService.php`
- Test: `tests/Feature/Learner/SequencingActivityFlowTest.php`

**Interfaces:**
- Consumes: normalized Sequencing configuration and `InteractiveActivityProgress`.
- Produces: a repaired non-canonical `working_state.item_order` only for legacy zero-attempt in-progress records.

- [ ] **Step 1: Add failing regressions**

Add feature tests that create canonical `working_state` records for the same activity in these states:

```php
// Repair this stale pre-fix state.
['status' => 'in_progress', 'attempt_count' => 0, 'working_state' => ['item_order' => ['item-1', 'item-2', 'item-3']]]

// Preserve these states exactly.
['status' => 'in_progress', 'attempt_count' => 1, 'working_state' => ['item_order' => ['item-1', 'item-2', 'item-3']]]
['status' => 'completed', 'attempt_count' => 2, 'working_state' => ['item_order' => ['item-1', 'item-2', 'item-3']]]
```

Assert the first GET payload is not canonical and the latter records are unchanged. Add a completed re-submission assertion that the response is `accepted: false`, remains `completed`, and returns the persisted canonical payload.

- [ ] **Step 2: Run the focused test and observe failure**

Run:

```powershell
php vendor/bin/phpunit tests/Feature/Learner/SequencingActivityFlowTest.php
```

Expected: the zero-attempt canonical record remains canonical before the fix.

- [ ] **Step 3: Implement the narrow repair**

After loading an existing progress record in `lockedCurrentProgress()`, repair it only when all conditions are true:

```php
$isLegacyCanonicalSequence = $lockedActivity->activity_type === InteractiveActivityType::SEQUENCING
    && $progress->status === 'in_progress'
    && (int) $progress->attempt_count === 0
    && ($progress->working_state['item_order'] ?? null)
        === array_column($configuration['items'] ?? [], 'id');

if ($isLegacyCanonicalSequence) {
    $progress->working_state = $handler->initialWorkingState($configuration, new Randomizer);
    $progress->save();
}
```

Do not alter attempted, skipped, practice, or completed records.

- [ ] **Step 4: Run the focused test**

Run the command from Step 2. Expected: PASS.

- [ ] **Step 5: Commit only Task 1 files**

```powershell
git add app/Services/Learning/InteractiveActivities/InteractiveActivityProgressService.php tests/Feature/Learner/SequencingActivityFlowTest.php
git commit -m "fix: repair initial sequence state"
```

### Task 2: Make Sequencing Completion and Per-Card Feedback Unambiguous

**Files:**
- Modify: `resources/js/sequencing-activity.js`
- Modify: `resources/views/learner/lessons/partials/interactive-activities/sequencing.blade.php`
- Modify: `resources/css/components.css`
- Test: `tests/JavaScript/sequencing-activity.test.mjs`
- Test: `tests/Feature/Learner/InteractiveActivityRenderingTest.php`

**Interfaces:**
- Consumes: `position_results[]`, `payload.items[]`, `status`, `accepted`, and `is_complete` from the evaluation endpoint.
- Produces: `itemState(itemId, index): 'idle'|'correct'|'incorrect'`, preserved Retry order, and completed-payload rehydration.

- [ ] **Step 1: Add failing JavaScript and rendering tests**

Cover a mixed result:

```javascript
position_results: [
  { item_id: 'two', position: 1, is_correct: false },
  { item_id: 'one', position: 2, is_correct: false },
  { item_id: 'three', position: 3, is_correct: true },
]
```

Assert `itemState()` returns two incorrect states and one correct state; `retryAnswer()` clears decorations without changing `order`; and a completed response with `accepted: false` plus canonical `payload.items` replaces a stale local order and locks it. Assert the Blade includes readable `Correct`/`Incorrect` labels and non-color icons.

- [ ] **Step 2: Run focused tests and observe failure**

```powershell
node --test tests/JavaScript/sequencing-activity.test.mjs
php vendor/bin/phpunit tests/Feature/Learner/InteractiveActivityRenderingTest.php
```

Expected: at least the completed-payload rehydration assertion fails.

- [ ] **Step 3: Rehydrate authoritative completed state**

In `checkAnswer()`, always retain `data.position_results` when supplied. If the response is complete and contains `data.payload.items`, call the existing payload loader before publishing feedback:

```javascript
if (data.is_complete && Array.isArray(data.payload?.items)) {
    this.loadPayload(data.payload, data.status, data.preview_token);
}
this.positionResults = Array.isArray(data.position_results) ? [...data.position_results] : this.positionResults;
```

Keep the submitted order untouched on incorrect responses. Keep Retry as a local feedback reset only.

- [ ] **Step 4: Align card semantics with Quiz feedback**

Render exactly one readable state label and one hidden-from-AT icon per evaluated card. Keep the existing emerald/rose card classes and single-number position label. Ensure the Check action is unavailable only when completed/submitting, while Retry appears after an incorrect evaluation.

- [ ] **Step 5: Run focused tests**

Run both commands from Step 2. Expected: PASS.

- [ ] **Step 6: Commit only Task 2 files**

```powershell
git add resources/js/sequencing-activity.js resources/views/learner/lessons/partials/interactive-activities/sequencing.blade.php resources/css/components.css tests/JavaScript/sequencing-activity.test.mjs tests/Feature/Learner/InteractiveActivityRenderingTest.php
git commit -m "fix: show sequence position results"
```

### Task 3: Return Complete Independent Matching Results

**Files:**
- Modify: `app/Services/Learning/InteractiveActivities/MatchingActivityHandler.php`
- Test: `tests/Unit/Services/Learning/InteractiveActivityHandlerTest.php`
- Test: `tests/Feature/Learner/MatchingActivityFlowTest.php`

**Interfaces:**
- Consumes: `connections: list<{left_id:string,right_id:string}>` and stored correct matches.
- Produces: `pair_results: list<{left_id:string,right_id:?string,is_correct:?bool,state:'correct'|'incorrect'|'unanswered'}>` with one entry per configured left item.

- [ ] **Step 1: Add failing handler and endpoint tests**

Submit one correct connection for a three-pair activity and assert:

```php
$this->assertFalse($result['is_correct']);
$this->assertFalse($result['is_complete']);
$this->assertSame(['correct', 'unanswered', 'unanswered'], array_column($result['pair_results'], 'state'));
```

Submit a complete mixed set and assert each left ID has only its own correct/incorrect result. Submit a complete correct set and assert both `is_correct` and `is_complete` are true.

- [ ] **Step 2: Run focused tests and observe failure**

```powershell
php vendor/bin/phpunit tests/Unit/Services/Learning/InteractiveActivityHandlerTest.php tests/Feature/Learner/MatchingActivityFlowTest.php
```

Expected: partial all-correct input reports `is_correct: true` and omits unanswered results before the fix.

- [ ] **Step 3: Build results from every configured left item**

Validate supplied connections first, index them by `left_id`, then map configuration order:

```php
$pairResults = array_map(static function (array $pair) use ($connectionsByLeft, $mapping): array {
    $leftId = $pair['left']['id'];
    $rightId = $connectionsByLeft[$leftId] ?? null;
    if ($rightId === null) {
        return ['left_id' => $leftId, 'right_id' => null, 'is_correct' => null, 'state' => 'unanswered'];
    }
    $isCorrect = $mapping[$leftId] === $rightId;
    return ['left_id' => $leftId, 'right_id' => $rightId, 'is_correct' => $isCorrect, 'state' => $isCorrect ? 'correct' : 'incorrect'];
}, $configuration['pairs']);
```

Persist only correct non-conflicting matches. Set overall correctness only when every configured pair is connected correctly:

```php
$complete = count($matched) === count($mapping);
$correct = $complete && ! collect($pairResults)->contains(fn (array $result): bool => $result['state'] !== 'correct');
```

- [ ] **Step 4: Run focused tests**

Run the command from Step 2. Expected: PASS.

- [ ] **Step 5: Commit only Task 3 files**

```powershell
git add app/Services/Learning/InteractiveActivities/MatchingActivityHandler.php tests/Unit/Services/Learning/InteractiveActivityHandlerTest.php tests/Feature/Learner/MatchingActivityFlowTest.php
git commit -m "fix: validate every matching pair"
```

### Task 4: Implement Reliable Matching Select and Connect Interaction

**Files:**
- Modify: `resources/js/matching-activity.js`
- Modify: `resources/views/learner/lessons/partials/interactive-activities/matching.blade.php`
- Modify: `resources/css/components.css`
- Test: `tests/JavaScript/matching-activity.test.mjs`
- Test: `tests/Feature/Learner/InteractiveActivityRenderingTest.php`

**Interfaces:**
- Produces: `activateEndpoint(side, id, event?)`, `selectionPreviewPoint(side, id)`, persistent `matchedPairs`, result-aware `endpointState()`, and responsive `connectorLines`.
- Consumes: the Task 3 `pair_results` contract and existing `completed_matches` payload.

- [ ] **Step 1: Add failing interaction tests**

Test click/tap semantics directly:

```javascript
activity.activateEndpoint('left', 'left-1');
assert.deepEqual(activity.activeEndpoint, { side: 'left', id: 'left-1' });
assert.equal(activity.connectorLines.at(-1).state, 'pending');

activity.activateEndpoint('right', 'right-2');
assert.deepEqual(activity.matchedPairs, [{ left_id: 'left-1', right_id: 'right-2' }]);
assert.equal(activity.activeEndpoint, null);
```

Also assert Retry preserves all connections, correct endpoints cannot be replaced, incorrect endpoints can be replaced, practice requests include `right_order`, and a completed response rehydrates `completed_matches` from the authoritative payload.

- [ ] **Step 2: Run tests and observe failure**

```powershell
node --test tests/JavaScript/matching-activity.test.mjs
```

Expected: pointerdown/pointerup semantics cancel a normal tap or produce a zero-length temporary line.

- [ ] **Step 3: Separate endpoint activation from pointer preview**

Use one activation method for click, tap, and the button's native Space/Enter click behavior:

```javascript
activateEndpoint(side, id, event = null) {
    event?.preventDefault?.();
    if (!this.activeEndpoint) return this.startConnection(side, id, event);
    if (this.activeEndpoint.side === side && this.activeEndpoint.id === id) return this.cancelConnection();
    if (this.activeEndpoint.side === side) return this.startConnection(side, id, event);
    return this.finishConnection(side, id);
}
```

Bind the endpoint button to `@click.stop="activateEndpoint(...)"` and `@keydown.escape.stop.prevent="cancelConnection()"` without pairing `pointerdown` and `pointerup` on the same tap. Native button semantics provide Space/Enter activation. Preserve pointermove only to update the temporary line.

- [ ] **Step 4: Make selection immediately visible**

When no meaningful pointer coordinate exists, draw the temporary line from the selected dot toward the center gutter at the same Y coordinate:

```javascript
selectionPreviewPoint(side, id) {
    if (!this.connectorContainer) return null;
    const endpoint = this.findEndpoint(side, id);
    if (!endpoint) return null;
    const containerRect = this.connectorContainer.getBoundingClientRect();
    const source = connectorPoint(endpoint.getBoundingClientRect(), containerRect);
    return { x: containerRect.width / 2, y: source.y };
}
```

`startConnection()` assigns this point before scheduling connector refresh. Subsequent pointermove events replace it with the live pointer coordinate. Keep persistent lines based on actual endpoint centers. Continue recalculating through the existing ResizeObserver, capturing scroll, resize, orientation, font-ready, and animation-frame batching.

- [ ] **Step 5: Apply complete result state and preserve retry data**

On Check Answer:

```javascript
working_state: {
  right_order: this.rightItems.map((item) => item.id),
  matched: this.matchedPairs.filter((pair) => this.pairState(pair) === 'correct'),
}
```

Store all returned `pair_results`. Keep submitted incorrect connections locally, merge persisted correct `completed_matches`, and mark missing left items unanswered. On complete responses, load the authoritative payload and lock the result. Retry clears overall feedback but keeps lines and result distinctions; beginning from an incorrect endpoint replaces only that pair.

- [ ] **Step 6: Align learner markup with authoring visual language**

Keep independent left and shuffled-right columns, central 44-pixel dots, a protected line gutter, purple selected/connected borders, and explicit Selected/Connected/Correct/Incorrect/Unanswered text. Do not draw default row lines or otherwise reveal canonical pairs.

- [ ] **Step 7: Run focused tests**

```powershell
node --test tests/JavaScript/matching-activity.test.mjs
php vendor/bin/phpunit tests/Feature/Learner/InteractiveActivityRenderingTest.php
```

Expected: PASS.

- [ ] **Step 8: Commit only Task 4 files**

```powershell
git add resources/js/matching-activity.js resources/views/learner/lessons/partials/interactive-activities/matching.blade.php resources/css/components.css tests/JavaScript/matching-activity.test.mjs tests/Feature/Learner/InteractiveActivityRenderingTest.php
git commit -m "fix: complete learner matching flow"
```

### Task 5: Regression Verification and Runtime Asset Delivery

**Files:**
- Modify generated runtime assets under `public/build/` through the existing Vite build.
- Create: `docs/superpowers/verification/2026-09-12-interactive-activities-learner-fixes-e2e.md`

**Interfaces:**
- Consumes: all Task 1-4 behavior.
- Produces: a synchronized production bundle and reproducible verification evidence.

- [ ] **Step 1: Run all focused JavaScript tests**

```powershell
node --test tests/JavaScript/activity-feedback.test.mjs tests/JavaScript/interactive-activity.test.mjs tests/JavaScript/matching-activity.test.mjs tests/JavaScript/pointer-reorder.test.mjs tests/JavaScript/sequencing-activity.test.mjs tests/JavaScript/interactive-activity-authoring.test.mjs
```

Expected: all tests PASS.

- [ ] **Step 2: Run all focused PHP tests**

```powershell
php vendor/bin/phpunit tests/Unit/Services/Learning/InteractiveActivityHandlerTest.php tests/Feature/Learner/InteractiveActivityProgressTest.php tests/Feature/Learner/InteractiveActivityRenderingTest.php tests/Feature/Learner/MatchingActivityFlowTest.php tests/Feature/Learner/SequencingActivityFlowTest.php tests/Feature/Instructor/InteractiveActivityAuthoringTest.php
```

Expected: all tests PASS.

- [ ] **Step 3: Build the active Vite assets**

```powershell
pnpm.cmd build
```

Expected: Vite exits 0 and `public/build/manifest.json` references newly emitted app JS/CSS assets containing the Matching and Sequencing changes.

- [ ] **Step 4: Verify production-source markers**

Resolve the app JS path from `public/build/manifest.json` and assert the bundle contains the new per-item result handling and Matching activation behavior. Confirm `git diff --check` reports no whitespace errors in task files.

- [ ] **Step 5: Record learner-flow evidence**

Create the verification document with commands/results and manual checks for:

- wrong Sequencing order returns mixed card states and Retry without reset;
- correct Sequencing order locks and exposes Continue;
- fresh/practice sequence is non-canonical;
- Matching tap selects without immediate cancellation;
- temporary and persistent lines remain aligned after resize/scroll;
- partial Matching input reports unanswered items and cannot report overall correct;
- Retry keeps lines, locks correct pairs, and permits incorrect replacement;
- complete Matching result exposes Continue.

If no authenticated browser session is available, mark only browser checks as not executed and retain the automated evidence; do not claim they passed.

- [ ] **Step 6: Commit verification and generated app assets only**

Stage the verification document, manifest, and emitted app JS/CSS assets required by the current manifest. Do not stage unrelated TinyMCE files or unrelated worktree changes.

```powershell
git commit -m "test: verify learner activity fixes"
```
