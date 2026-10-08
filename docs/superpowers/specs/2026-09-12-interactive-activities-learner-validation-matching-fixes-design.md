# Interactive Activities Learner Validation and Matching Fixes Design

**Date:** 2026-09-12
**Status:** Approved for implementation planning
**Scope:** Correct the remaining learner-side Sequencing and Matching defects without rewriting Interactive Activities

## 1. Purpose

Finish the learner experience promised by the existing Interactive Activities UX refinement. Sequencing must start from a non-canonical order, validate the submitted order accurately, and identify each correct and incorrect position. Matching must use an authoring-inspired connection vocabulary and support a reliable Select, Connect, Check, Retry, and Continue flow.

This design refines the learner interaction described in `2026-09-09-interactive-activities-ux-refinement-design.md`. It preserves the current activity schemas, optional completion rules, revision handling, PHP handler architecture, and authoring behavior.

## 2. Confirmed Product Decisions

1. A completed activity remains locked and displays its saved correct result.
2. Practice Again starts a fresh, randomized, non-mutating attempt.
3. PHP activity handlers remain the sole correctness authority; the browser receives per-item results but no answer key.
4. Retry preserves the learner's current order or connections.
5. Correct Matching connections remain locked while incorrect connections can be replaced.
6. The learner Matching layout borrows the authoring interface's cards, central dots, relationship lines, and selected states without exposing canonical pairings.
7. No new framework, package, schema version, or parallel activity implementation is introduced.

## 3. Architecture and Data Flow

The existing Sequencing and Matching handlers continue to normalize configuration and evaluate answers. The learner Alpine components own only gesture state, responsive geometry, and result presentation.

```text
Author configuration
    -> SequencingActivityHandler / MatchingActivityHandler
    -> randomized safe learner payload
    -> learner Alpine component
    -> complete opaque-ID submission
    -> handler evaluation
    -> per-position or per-pair result contract
    -> card, icon, label, line, and overall feedback states
```

Fresh Sequencing progress and Practice Again must use a non-canonical shuffle. When a pre-fix progress record is still `in_progress`, has zero attempts, and contains the canonical order, it is repaired once with a new shuffle. Attempted and completed progress is never silently reset.

If a stale client submits against completed progress, the server does not evaluate the displayed arrangement. It returns the persisted completed payload, and the client rehydrates and locks that saved state. This prevents a visibly incorrect local arrangement from being paired with a misleading new `Correct` result.

Production Vite assets are rebuilt after source changes so Blade, JavaScript, and CSS execute as one version.

## 4. Sequencing Behavior

The canonical answer remains the normalized authoring item order. `Check answer` submits the complete ordered list of item IDs. The handler validates that the submission is a complete permutation before comparing every submitted position with the canonical position.

The response contains one `position_results` entry per submitted card:

```text
item_id
position
is_correct
```

After checking, each card displays a semantic state:

- correct position: emerald quiz-style surface/border, check icon, and `Correct` label;
- incorrect position: rose quiz-style surface/border, x icon, and `Incorrect` label.

The overall feedback remains visible. An incorrect attempt exposes Retry and preserves the current order. Retry clears stale result decoration but does not rearrange cards. A correct result locks the saved canonical order and exposes Continue through the existing shell. Position labels show only the current number and update after every committed reorder.

Pointer/touch dragging remains handle-only. Keyboard pickup/drop and arrow, Home, End, and Escape behavior remains available.

## 5. Matching Behavior

The layout keeps independent left and shuffled-right collections in compact columns. Cards and endpoint dots use the same central relationship vocabulary as Matching authoring, but no default row line implies an answer.

The interaction state machine is:

1. Click, tap, Space, or Enter on an available endpoint to select it.
2. Highlight the selected card and dot. Draw a temporary SVG line from the selected dot toward the pointer, or into the connection gutter when no pointer movement exists.
3. Activate an available endpoint on the opposite side to create a persistent purple connection.
4. Repeat until ready, then submit all current connections with Check Answer.
5. Render each pair independently as correct, incorrect, or unanswered.
6. On Retry, preserve all connections, lock correct pairs, and permit incorrect pairs to be removed or replaced.
7. On complete success, retain the lines and expose Continue through the existing shell.

A tap must not start and cancel a connection during the same pointer gesture. Endpoint activation therefore uses a click/tap selection contract; pointer movement supplements it only for temporary-line positioning.

The batch response contains one `pair_results` entry for every left-side item, including unanswered items. A correct pair cannot mark unrelated cards correct.

## 6. Matching Geometry

One existing SVG overlay system draws temporary and persistent lines. Coordinates come from each endpoint's `getBoundingClientRect()` relative to the activity root; no hard-coded screen coordinates are permitted.

Geometry recalculation is animation-frame batched after:

- selection or connection changes;
- payload replacement or reload;
- activity/container resize;
- viewport resize and orientation change;
- font readiness;
- text wrapping or card reflow; and
- relevant page or ancestor scrolling.

A `ResizeObserver` watches the activity region, and all observers/listeners are released during component teardown. The two-column mobile layout retains wrapped text, a protected connection gutter, and at least 44-by-44-pixel endpoint targets.

## 7. Feedback, Accessibility, and Errors

Color is never the only state signal. Correct, incorrect, unanswered, selected, and connected states include readable labels and/or icons. Focus remains visible. SVG lines are decorative and hidden from assistive technology; card labels and live regions communicate equivalent state.

Invalid, missing, duplicate, same-side, and unknown IDs are rejected safely. Network failures preserve local order/connections and show a retryable error. A stale revision asks the learner to reload. Temporary Matching selection cancels on Escape or an invalid target. Reduced-motion preferences disable nonessential movement.

## 8. Testing and Delivery

Regression coverage includes:

- fresh and Practice Again Sequencing states are never canonical;
- legacy zero-attempt canonical progress is repaired without resetting attempted/completed progress;
- incorrect Sequencing submissions cannot complete and return accurate per-position results;
- completed-state rehydration cannot show a new incorrect arrangement as newly correct;
- Sequencing Retry preserves order and card feedback is non-color-dependent;
- Matching tap selection does not immediately cancel;
- temporary and persistent lines use endpoint-center geometry;
- every Matching pair is validated independently, including unanswered pairs;
- Retry preserves connections, locks correct pairs, and permits incorrect replacement;
- line geometry recalculates on scroll, resize, reflow, and orientation events;
- responsive and keyboard interaction contracts remain intact.

Verification runs the focused JavaScript tests, focused Laravel/PHPUnit tests, production Vite build, and learner-flow browser checks where the local authenticated environment is available. Generated build assets are updated only as required by this repository's runtime delivery and unrelated worktree changes remain untouched.

## 9. Out of Scope

- Activity schema or database migrations
- Changes to lesson, topic, module, quiz, scoring, or certification rules
- New drag-and-drop or drawing dependencies
- Rewriting authoring or the Interactive Activities framework
- Resetting attempted or completed learner progress
