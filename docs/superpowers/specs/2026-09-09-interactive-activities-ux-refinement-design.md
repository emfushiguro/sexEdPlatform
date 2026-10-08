# Interactive Activities UX Refinement Design

**Date:** 2026-09-09
**Status:** Approved
**Scope:** Learner and authoring UX refinement for the existing Matching and Sequencing Interactive Activities

## 1. Purpose

Refine the existing Interactive Activities implementation so Matching feels like connecting concepts and Sequencing feels like direct reordering. Preserve the existing activity data model, canonical answer formats, server-side handlers, optional learning status, progress isolation, Topic placement, and Lesson navigation rules.

This design supersedes only the interaction decisions in `docs/superpowers/specs/2026-09-02-interactive-activities-design.md` that specified tap-to-match and visible Move Up/Move Down controls. All unrelated decisions in that specification remain authoritative.

## 2. Approved Decisions

1. Matching evaluates a proposed pair immediately on drop through the existing server handler.
2. Pointer and touch users connect visible dots; keyboard users select the same source and target cards.
3. Matching can begin from either column. The client normalizes the proposal to `left_id` and `right_id`.
4. Correct Matching connections persist and lock. Incorrect connections remain locally visible until removed or replaced and are never persisted as progress.
5. Matching retains compact left and right columns at narrow widths with a protected connection gutter and wrapping text.
6. Sequencing removes visible Up/Down controls in learner and authoring views.
7. Sequencing uses a drag handle, floating dragged item, stable placeholder gap, and explicit insertion bar. The order commits only on drop.
8. Keyboard Sequencing uses Space or Enter to pick up and drop, Arrow keys/Home/End to choose a position, and Escape to cancel.
9. Touch dragging starts only from the handle. The rest of each card remains available for ordinary page scrolling, and long lists support edge auto-scroll.
10. Learner and authoring Sequencing share one small reorder engine. Matching authoring row order uses the same primitive.
11. Matching authoring shows paired left/right fields with a visible connection relationship.
12. Preview renders the learner components and evaluates through the same PHP handlers using a short-lived encrypted preview token. It does not expose an answer key or save learner progress.
13. Matching and Sequencing use one feedback vocabulary and shared feedback presentation.
14. Practice rehydrates child components from the newly returned shuffled payload.
15. Animations use CSS and native browser APIs, add no runtime dependency, and respect reduced-motion preferences.
16. The complete learner, authoring, preview, accessibility, and regression scope ships through one staged implementation plan.

## 3. Scope and Preserved Learning Rules

The refinement changes learner interaction, authoring interaction, preview evaluation, feedback presentation, responsive layout, and accessibility behavior.

It does not change:

- the `interactive_activities` or `interactive_activity_progress` schemas;
- Matching or Sequencing configuration schema version `1`;
- canonical answer storage;
- activity revision and answer-fingerprint behavior;
- optional activity status;
- Topic, Lesson, or Module completion requirements;
- Quiz attempts, daily limits, shields, scoring, gamification, or certification;
- inside-topic and between-topic placement rules;
- Lesson/module publication and moderation workflows; or
- instructor/admin authorization.

Interactive Activities remain optional formative work and continue to use the existing Lesson Topic architecture.

## 4. Architecture

The existing PHP activity handlers remain the sole source of truth for configuration normalization and correctness. The browser coordinates gestures and presentation but does not introduce a second answer-validation implementation.

```text
Interactive activity shell
|-- shared lifecycle and feedback presentation
|-- Matching renderer
|   |-- pointer/touch connection controller
|   |-- keyboard selection controller
|   `-- responsive SVG connection layer
`-- Sequencing renderer
    |-- shared pointer reorder controller
    |-- shared keyboard reorder controller
    `-- drag overlay, placeholder, and insertion presentation

Authoring
|-- Matching pair editor
|-- Sequencing canonical-order editor
`-- actual learner shell used by Preview
```

Two focused JavaScript modules are permitted:

- `activity-feedback.js` maps evaluation and lifecycle results to shared, accessible presentation state.
- `pointer-reorder.js` owns framework-neutral reorder state and operations used by learner Sequencing, authoring Sequencing, and Matching authoring row order.

Do not introduce a canvas renderer, a general activity framework, or a third-party drag-and-drop dependency.

## 5. Shared Feedback Contract

The common activity shell owns the visible result region. Type-specific components publish structured events rather than maintaining unrelated user-facing strings.

The feedback object contains:

```text
kind: idle | pending | correct | incorrect | completed | skipped | error
message: user-facing text
icon: semantic icon name
```

Approved messages are:

- Matching incomplete success: `Correct match. {completed} of {total} pairs complete.`
- Matching incorrect: `Incorrect match. Remove or replace this connection and try again.`
- Sequencing incorrect: `Incorrect sequence. Reorder the items and try again.`
- Completion: `Correct. Activity complete.`
- Skipped: `Activity skipped. You can resume when ready.`
- Pending Matching request: `Checking match.`

Safe server errors remain authoritative for request failures. Results use a polite live region. Request failures use `role="alert"`. Icons, labels, line styles, and borders supplement color.

Starting a new relevant interaction clears stale general feedback. Existing correct connection status remains visible. Reordering after an incorrect sequence clears the previous incorrect message.

The shell renders the optional sanitized explanation only when the server returns it after completion. Learner and Preview instructions and explanations use the same sanitized rich-text presentation.

## 6. Matching Learner Interaction

### 6.1 Connection points

Every card has a visible dot with a minimum 44-by-44-pixel interactive hit area. The visual dot may be smaller, but the hit target and focus indicator meet the touch-target requirement.

Pointer and touch users drag from either column. Keyboard users focus a card or dot, activate it with Space or Enter, move to an available target, and activate the target with Space or Enter. Escape cancels a pending keyboard selection.

Starting a connection:

- highlights the source card;
- applies an active ring to the source dot;
- marks available opposite-side endpoints as valid targets;
- marks occupied or same-side endpoints as unavailable; and
- draws a temporary SVG line to the current pointer position.

### 6.2 Proposal and evaluation

Dropping on an available opposite-side target immediately submits the existing `left_id` and `right_id` proposal. The pending line remains visible while the request is active, both endpoints are temporarily locked, and the shared shell announces `Checking match.`

Dropping outside a valid target cancels only the provisional connection and makes no request.

A correct response creates a persistent solid line, check marker, correct card labels, progress announcement, and short settle animation. Correct endpoints are locked.

An incorrect response creates one local dashed line with an x marker and incorrect labels. The learner can remove it through an accessible remove action or replace it by beginning another connection from either endpoint. Beginning a replacement clears the rejected line first. Only one rejected proposal remains visible at a time.

A failed request keeps the proposal in a neutral retryable state and shows the safe request error. It must not label the proposal incorrect.

### 6.3 Conflict prevention

The client prevents same-side connections, duplicate proposals, and reuse of confirmed endpoints. The existing handler remains authoritative for unknown, duplicate, stale, or manipulated IDs.

## 7. Matching Payload and Persistence

The current persisted working state already stores confirmed pairs, but the learner payload exposes completed left and right IDs as independent sets. That cannot reconstruct connection lines after reload.

`MatchingActivityHandler::learnerPayload()` will therefore return `completed_matches`, containing only solved `left_id` and `right_id` relationships. It will not expose unresolved mappings, enclosing pair IDs, or the answer key.

Matching restores:

- the persisted shuffled right-side order;
- confirmed connections and line geometry;
- completed read-only state; and
- a fresh shuffled state returned by Practice.

Rejected proposals remain client-only and disappear after reload.

## 8. Matching Geometry and Responsive Behavior

The activity root establishes the SVG coordinate space. Lines connect dot centers, not card centers. Each point is calculated from its `getBoundingClientRect()` relative to the activity root rectangle.

Geometry updates are batched through `requestAnimationFrame` after connection changes, payload replacement, text wrapping, container resize, viewport resize, orientation change, font readiness, or ancestor scrolling. A `ResizeObserver` watches the activity region. Event listeners and observers are released when the component is destroyed.

The SVG is decorative and `aria-hidden`. Card text, icons, and state labels preserve meaning without it.

At narrow widths, Matching remains a two-column layout with flexible text cards, wrapping content, large endpoint targets, and a protected central gutter. It does not horizontally scroll or switch to dropdowns.

The shell is a CSS size container, so component breakpoints respond to the activity's actual width. The same container behavior makes the author Preview width controls meaningful inside a desktop browser.

## 9. Sequencing Learner Interaction

Visible Up/Down controls are removed. Each row contains its current position, item text, and a minimum 44-by-44-pixel drag handle.

Pointer dragging begins only on the handle. During dragging:

- a floating representation follows the pointer;
- the source becomes a subdued placeholder;
- the dragged card gains elevation, slight scale, and an active outline;
- an insertion bar and animated gap identify the target; and
- the committed `order` array remains unchanged.

Dropping commits one reorder operation and schedules the existing debounced state save. Dropping outside the list, pointer cancellation, or Escape restores the original order.

When the pointer approaches the top or bottom viewport edge, animation-frame-driven edge auto-scroll supports long lists. Auto-scroll stops on drop, cancel, pointer loss, or component teardown. Only the handle suppresses touch scrolling; the rest of the card remains normally scrollable.

Keyboard behavior on the focused handle is:

- Space or Enter: pick up or drop;
- Arrow Up/Arrow Down: move the proposed insertion position;
- Home/End: move to the first or last position; and
- Escape: cancel.

A polite live region announces pickup, proposed position, drop, and cancellation.

## 10. Sequencing Evaluation and Persistence

Reordering never marks an activity correct. `Check answer` submits the complete opaque ID order to the existing endpoint and handler.

An incorrect result preserves the order, reveals no correct position, hides the explanation, and remains retryable. Starting another reorder clears the stale incorrect message.

A correct result preserves and locks the final order, adds non-color completion indicators, publishes the shared completion message, displays the optional explanation, and retains existing Continue and Practice Again behavior.

A failed background save preserves local order and exposes a retryable error. Checking the answer still flushes or retries the current complete order before evaluation.

## 11. Practice, Reset, Skip, and Resume

Child components rehydrate from the payload returned by shared lifecycle responses. Practice must not reset to the original page-load payload.

Practice Again:

- requests a new server-generated non-correct shuffle;
- replaces Matching item order and connections or Sequencing item order;
- clears transient feedback and errors;
- preserves recorded completion; and
- remains non-mutating.

Retry means replacing an incorrect connection or reordering an incorrect sequence. No new endpoint erases confirmed progress. Skip and Resume preserve unfinished working state and remain optional lifecycle actions.

## 12. Matching Authoring

Each pair appears as one connected authoring row:

```text
[drag handle] [Left item] dot---dot [Right item] [Remove]
```

Authors can add, edit, remove, pointer-reorder, keyboard-reorder, and preview 2-12 pairs. The relationship line makes the eventual learner mapping understandable while preserving the existing JSON structure and stable IDs.

Removing a row moves focus to the next row, previous row, or Add Pair action. Existing inline and summary validation remain associated with the relevant fields.

## 13. Sequencing Authoring

The authoring list directly represents canonical order. Each row shows a drag handle, derived `Correct position {n}`, editable item text, and Remove action.

The shared reorder module powers pointer, touch, and keyboard operation. Visible Up/Down buttons are removed. After a committed drop, array order changes, displayed positions update, and submitted `correct_position` values remain derived from array indexes. Stable item IDs remain attached to their content.

The server continues to normalize positions and reject blank, duplicate, or out-of-range configurations.

## 14. Shared Authoring Fields

Existing title, type, placement, containing Topic, insertion location, instructions, optional explanation, Preview, and Save controls remain. Activity type stays immutable after creation. Placement and Topic composition behavior do not change.

Matching and Sequencing use consistent cards, spacing, typography, drag handles, focus states, errors, add/remove controls, and motion principles.

## 15. Author Preview

Preview continues to render the actual learner shell and type-specific partials. The current client-side answer-key comparisons are removed.

Initial Preview creation:

1. Authorizes the Lesson and validates unsaved fields.
2. Normalizes configuration through the registered handler.
3. Creates the non-correct shuffled working state through that handler.
4. Creates a short-lived encrypted token bound to activity type, Lesson, author, normalized configuration, authoritative preview working state, and expiration.
5. Renders the learner shell without an answer key.

Preview evaluation:

1. Requires authenticated author access and CSRF.
2. Decrypts and validates the token.
3. Reauthorizes the bound Lesson and user.
4. Resolves the registered handler.
5. Evaluates the submitted pair or complete order through that handler using the token's authoritative working state.
6. Returns the normal result, safe learner payload, and a rotated token containing the resulting working state.
7. Creates no learner progress and never trusts client-supplied completed Matching state.

Tampered, expired, cross-user, and wrong-Lesson tokens are rejected. Preview Continue never navigates, and Preview Practice creates another temporary server shuffle.

The existing mobile, tablet, and desktop width controls remain. CSS container breakpoints make the constrained Preview width match learner component behavior.

## 16. Accessibility

The refined interfaces provide:

- visible focus indicators;
- pointer, touch, and keyboard operation;
- minimum 44-by-44-pixel dots and drag handles;
- text and icons in addition to color;
- programmatically associated instructions;
- polite interaction and result announcements;
- assertive request-error announcements;
- keyboard drag position announcements;
- accessible removal of rejected connections;
- focus restoration when Preview closes;
- predictable focus after authoring removal;
- readable completed content using `aria-disabled` or read-only semantics where appropriate; and
- reduced-motion behavior.

No separate gesture-only or accessibility-only implementation is introduced.

## 17. Animation and Performance

Matching uses an active endpoint ring, pointer-following line, short correct-line draw, dashed rejected-line appearance, and gentle invalid-drop return. Sequencing uses drag lift, shadow, placeholder expansion, insertion movement, and short drop settle.

With `prefers-reduced-motion: reduce`, durations become immediate and pulsing, shaking, sliding, and smooth auto-scroll are disabled. State remains clear through text, icons, borders, and labels.

Pointer-move work and geometry refreshes are animation-frame throttled. No new animation or drag library is added.

## 18. Error and Recovery Behavior

Matching invalid targets cancel locally. Incorrect answers retain a removable rejected connection. Request failures retain a neutral proposal. Revision conflicts retain the existing reload-required behavior.

Sequencing outside drops and pointer cancellation restore the original order. Failed saves preserve the local order. Failed checks preserve the current arrangement. Revision conflicts require reload rather than silently applying stale changes.

Invalid authoring configuration keeps Preview closed and displays field errors. Invalid or expired Preview tokens ask the author to regenerate Preview from the current form. Preview request failures preserve the current display and allow retry.

## 19. Testing and Regression Strategy

JavaScript unit tests cover Matching gesture state, geometry, correct/incorrect/error states, replacement, duplicate prevention, restored connections, and Practice rehydration. Sequencing tests cover pointer and keyboard pickup, insertion, drop, cancellation, state saving, failed saves, feedback reset, and Practice rehydration. Authoring tests cover shared reordering, pair integrity, canonical positions, add/remove focus, stable IDs, validation, and Preview tokens.

PHP and feature tests cover canonical handler evaluation, solved Matching payloads without unresolved answers, absence of answer keys in HTML, stateless Preview evaluation, token security, no Preview progress, explanation timing, revision isolation, and removal of visible Up/Down controls.

Browser QA covers desktop mouse, mobile touch, keyboard-only operation, scrolling, resizing, orientation, edge auto-scroll, invalid drops, authoring CRUD and reorder flows, Preview widths, and reduced motion.

Regression verification includes existing Topics, Lessons, checkpoints, quizzes, progress, completion, permissions, authoring, JavaScript tests, PHP tests, formatting, and the Vite build.

## 20. Implementation Boundaries

Implementation is staged as:

1. Characterize current payload, feedback, and Practice behavior.
2. Add shared feedback and payload rehydration.
3. Build Matching connection interaction and responsive geometry.
4. Add the shared reorder primitive.
5. Refine learner Sequencing.
6. Refine Matching and Sequencing authoring.
7. Centralize Preview evaluation through encrypted tokens and handlers.
8. Complete accessibility, reduced-motion, regression, and browser verification.

No migration or new frontend dependency is expected. Unrelated worktree changes must remain untouched and excluded from activity-specific commits.

## 21. Acceptance Criteria

The refinement is accepted when:

- feedback accurately and consistently reports correct, incorrect, pending, completed, skipped, and request-failed states;
- Matching uses responsive interactive dots and lines on pointer and touch devices;
- temporary, pending, correct, incorrect, restored, and removed Matching connections are understandable without color alone;
- confirmed Matching pairs persist after reload without exposing unresolved answers;
- Sequencing has no visible Up/Down controls and supports pointer, touch, and keyboard drag semantics;
- the Sequencing insertion location remains obvious and stable;
- incorrect sequences preserve learner order and correct sequences complete only after server evaluation;
- learner and authoring Sequencing share the reorder primitive;
- Matching authoring visually represents pairs;
- Preview renders the learner components and evaluates through existing handlers without exposing an answer key or saving progress;
- Practice uses a fresh shuffled response payload;
- reduced motion, focus, live-region, and touch-target requirements are met;
- existing learning, progress, navigation, permissions, and authoring rules remain intact; and
- focused tests, regression tests, build verification, and browser QA have recorded results.
