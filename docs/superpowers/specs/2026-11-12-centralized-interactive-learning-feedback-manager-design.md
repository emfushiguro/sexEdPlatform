# Centralized Interactive Learning Feedback Manager Design

**Date:** 2026-11-12

**Status:** Design approved; written specification awaiting user review

**Scope:** Learner-facing contextual feedback for existing learning interactions using Laravel Blade, Alpine.js, local reviewed unDraw SVG assets, and the existing learning-audio service

## 1. Executive Summary

Conscious Connections will gain one reusable Interactive Learning Feedback Manager for contextual learning outcomes. It will replace the current small inline outcome messages used by interactive checkpoints and Matching/Sequencing activities with polished micro feedback, feedback cards, and completion modals. It will also present authoritative lesson and module completion events after the existing server-side completion logic succeeds.

The manager is presentation only. Existing controllers, evaluators, progress services, activity components, and quiz submission flows remain the source of truth. It will not grade answers, alter progress, award points, complete content, or decide whether a retry is allowed.

The existing Toastify system remains responsible for ordinary application and gamification notifications. The existing `learningAudio` service remains the only sound system. The feedback manager asks that service to play an existing semantic sound only when an accepted feedback event becomes visible.

## 2. Existing-System Findings

### 2.1 Ordinary notifications

`resources/js/toast.js` is the application-wide Toastify wrapper exposed through `window.toast`. Learner layouts use it for session success, error, warning, information, validation, shield, points, streak, achievement, and related application notifications. This system is not limited to learning interactions and must remain available.

### 2.2 Interactive activities

Matching and Sequencing activities already have a sound event boundary and a shared feedback coordinator:

- child activities publish `interactive-activity-result`, `interactive-activity-error`, `interactive-activity-recovered`, and `interactive-activity-retry` events;
- `resources/js/interactive-activity.js` filters events by activity ID, updates local status, retains explanations, and currently maps results to inline feedback and sounds;
- `resources/js/activity-feedback.js` owns the current outcome wording;
- `resources/views/learner/lessons/partials/interactive-activities/shell.blade.php` renders the shared inline status and lifecycle actions;
- Matching and Sequencing keep authoritative answer state, highlighting, retry, practice, and persistence behavior in their existing modules.

The manager will consume these established outcomes rather than introduce a parallel activity implementation.

### 2.3 Interactive checkpoints

`resources/js/interactive-checkpoint.js` owns checkpoint submission, retry, skip, continuation, error, and authoritative response state. Correctness comes from the existing server response. Perspective Feedback returns a neutral completed state rather than correctness. The current Blade partial renders state-specific inline messages and actions.

The manager will present accepted checkpoint outcomes while the checkpoint component retains its answer and lifecycle state.

### 2.4 Formal quizzes

Formal quizzes intentionally do not reveal correctness per question. `Learner\QuizController` grades only after the full attempt is submitted, and `resources/views/quizzes/result.blade.php` provides the comprehensive score, pass/fail status, attempt availability, question scorecard, and next action.

The manager must not introduce per-question formal-quiz feedback or replace the result page. It is used there only if the existing final-quiz flow performs an authoritative module-completion transition.

### 2.5 Completion events

Topic and lesson completion are confirmed by `Learner\LessonController`. Formal quiz attempts and the final-quiz module transition are handled by `Learner\QuizController`. A guarded secondary module transition exists in `Learner\ModuleController::completion()`.

These paths already decide whether completion is new and already emit one-time session audio events. The manager will reuse those transition points and will not duplicate their eligibility calculations.

### 2.6 Existing sound system

`resources/js/learning-audio.js` is the sole feedback-audio service. It owns local MP3 paths, Howler instances, enabled and volume preferences, browser unlock behavior, playback priority, overlap prevention, and failure containment. It exposes the semantic keys:

- `selection`;
- `correct`;
- `incorrect`;
- `success`;
- `complete`.

It is exposed through `window.learningAudio` and `Alpine.store('learningAudio')`. The manager will call this service; it will not create audio objects, duplicate preferences, or add sound files.

## 3. Approved Design Decisions

The following decisions were explicitly approved:

1. Reuse and adapt the six supplied learning SVGs, then obtain one missing completion illustration.
2. Cover all currently supported learning feedback flows in one bounded implementation.
3. Preserve the formal quiz result page and use the manager only for a resulting module-completion event.
4. Suppress a learning success Toast only when the manager presents that same event; preserve all other Toasts.
5. Use one Alpine store/controller and one reusable Blade host mounted in both learner layouts.
6. Use micro feedback for minor lifecycle messages, cards for contextual outcomes, and modals for lesson/module completion.
7. Preserve answer highlighting and authoritative local state while making the manager the primary outcome presentation.
8. Show one feedback presentation at a time, coalesce duplicates, and let completion supersede routine feedback.
9. Let the manager invoke the existing sound service once when each accepted event becomes visible.
10. Reuse existing navigation and completion actions without modifying progress.
11. Reuse `lesson-completed.svg` for both lesson and module milestones.
12. Recolor appropriate unDraw accents with the established Conscious Connections purple family while preserving neutral and human colors.
13. Allow contextual plain-text copy, existing explanations, and only whitelisted actions.
14. Present recoverable activity failures as persistent warnings without sound while preserving learner state.
15. Record asset titles, official URLs, dates, license information, filenames, and color changes internally.
16. Use restrained motion, respect reduced-motion preferences, and omit confetti.

## 4. Goals

- Give learning outcomes a consistent, engaging, mature visual presentation.
- Centralize feedback defaults, illustration mapping, sound mapping, presentation rules, and dismissal behavior.
- Reuse existing authoritative learning events and actions.
- Reuse the current sound service and browser-local sound preferences.
- Preserve ordinary application and gamification Toasts.
- Use locally stored, reviewed, customized unDraw SVG files.
- Prevent duplicate presentation, duplicate actions, and duplicate sound playback.
- Work across learner desktop, tablet, and mobile layouts.
- Meet keyboard, focus, screen-reader, contrast, and reduced-motion requirements.
- Degrade without breaking learning if feedback UI, SVG, or sound playback is unavailable.

## 5. Non-Goals

- No new grading, answer validation, hints, achievements, points, or progress calculations.
- No changes to checkpoint, activity, lesson, quiz, or module completion rules.
- No per-question feedback in formal quizzes.
- No replacement of the formal quiz result, module completion, or certificate pages.
- No new frontend framework, modal library, notification framework, sound manager, or package.
- No new sound assets or semantic sound categories.
- No database migration or persistent feedback history.
- No cross-device feedback preferences.
- No arbitrary caller-provided HTML, SVG path, JavaScript callback, event name, or external URL.
- No site-wide Laravel error-page implementation in this workstream. The approved branded error pages using `error-page.svg` and `its-not-you-its-us.svg` require a separate specification and implementation plan.

## 6. Functional Scope

The manager supports only events already represented by application behavior:

- correct checkpoint answers;
- incorrect checkpoint answers;
- retry encouragement when the checkpoint or activity already permits another attempt;
- neutral completed Perspective Feedback;
- correct intermediate Matching results;
- incorrect Matching and Sequencing results;
- Matching and Sequencing completion, including practice completion;
- activity skip and resume information;
- recoverable activity request failures and recovery;
- newly confirmed lesson completion;
- newly confirmed module completion;
- learning-related informational and warning messages emitted by integrated learning components.

Topic completion, certificate issuance, formal quiz attempt recording without module completion, shields, points, streaks, achievements, validation, and ordinary application notices remain in their existing presentation paths.

## 7. Responsibility Boundaries

### 7.1 Toastify

Toastify continues to present:

- ordinary success, error, warning, information, and validation messages;
- shield changes;
- points and XP;
- streak milestones and streak saves;
- achievements and level changes;
- certificate and topic notices that are not replaced by a manager event;
- non-learning application feedback.

### 7.2 Learning Feedback Manager

The manager owns:

- normalization of learning-feedback events;
- default copy and visual treatment;
- local illustration selection;
- presentation variant selection;
- event priority, deduplication, and pending replacement;
- focus and dismissal behavior;
- fixed action dispatch;
- exactly-once requests to the existing sound service.

### 7.3 Learning components and controllers

Existing components and controllers continue to own:

- answer and activity state;
- server requests and validation;
- correctness and explanations;
- retry eligibility;
- progress and completion persistence;
- points and achievements;
- navigation destinations;
- authorization and access control.

### 7.4 Learning audio

`learningAudio` continues to own:

- sound files and Howler instances;
- enabled and volume preferences;
- browser unlock and deferred playback;
- active-sound priority and overlap prevention;
- unavailable-asset and playback failure handling.

## 8. Architecture Overview

The smallest complete architecture has four parts:

1. `resources/js/learning-feedback.js` exports a testable manager factory and the shared feedback configuration.
2. `resources/js/app.js` registers one `Alpine.store('learningFeedback')` backed by that manager.
3. `resources/views/components/learner/learning-feedback.blade.php` renders the single feedback host.
4. Existing learning producers call the store or dispatch one fixed `learning-feedback:show` DOM event with a normalized payload.

The host is mounted once in both `layouts.learner-app` and `layouts.learner-fullscreen`. It listens for the fixed show event, renders the active event from the store, and consumes an optional server-flashed payload on initialization.

No per-activity popup component, popup HTML factory, or second event bus is introduced.

## 9. Component Responsibilities

### 9.1 Manager factory

The manager factory owns pure or injected behavior wherever practical so Node tests can cover it without a browser. Its public methods are:

```text
show(payload)
dismiss(reason = 'dismissed')
act(actionId)
handleKeydown(event)
```

Its observable state is:

```text
active
pending
open
acting
announcement
```

### 9.2 Alpine store

The Alpine store exposes manager state and methods to Blade. It delegates audio to the existing `learningAudio` store/service and DOM action dispatch to the fixed learning-feedback action event.

### 9.3 Blade host

The host owns markup only:

- overlay and panel structure;
- headings, messages, explanations, illustrations, and buttons;
- live-region and dialog attributes;
- fixed status iconography;
- static Tailwind class branches;
- focusable element references.

It does not map feedback types, determine correctness, or calculate actions.

### 9.4 Producers

Checkpoint, activity, and server bootstrap adapters translate existing outcomes into the public contract. They do not choose arbitrary illustration paths or sound names.

## 10. Public Event Contract

Integrated code sends the following conceptual payload:

```javascript
{
    id: 'activity:42:evaluation:7',
    sourceId: 'activity:42',
    type: 'incorrect',
    title: 'Not quite yet',
    message: 'Review the highlighted connections and try again.',
    explanation: null,
    presentation: 'card',
    actions: [
        { id: 'retry', intent: 'retry', label: 'Try again' },
        { id: 'dismiss', intent: 'dismiss', label: 'Close' },
    ],
}
```

Allowed feedback types are:

```text
correct
incorrect
try_again
activity_completed
lesson_completed
module_completed
informational
warning
```

Allowed presentations are:

```text
micro
card
completion_modal
```

Allowed action intents are:

```text
retry
continue
navigate
dismiss
```

`id`, `sourceId`, `type`, and `message` are required after normalization. Missing optional values use centralized defaults.

## 11. Input Normalization and Security

The manager normalizes every event before accepting it:

- unknown types are rejected;
- illustration and sound values from callers are ignored;
- presentation is clamped to the variants allowed for the selected type;
- title, message, explanation, and labels are converted to bounded plain strings;
- actions with unknown intents are removed;
- duplicate action IDs are removed;
- only the first safe primary and secondary actions are retained;
- navigation is accepted only when it resolves to the current origin and an application-relative destination;
- arbitrary HTML is never rendered from the public contract;
- arbitrary JavaScript callbacks and caller-selected DOM events are unsupported.

Existing rich activity explanations are converted to readable plain text for the feedback card. The existing sanitized rich explanation may remain available in the activity after the primary feedback closes so formatting is not lost. The manager itself renders copy with `x-text`, not `x-html`.

## 12. Central Feedback Configuration

One immutable configuration maps types to fixed assets and defaults:

| Type | Default presentation | Illustration | Sound | Priority | Default dismissal |
|---|---|---|---|---:|---|
| `correct` | Card | `correct-answer.svg` | `correct` | 40 | Timed unless actionable |
| `incorrect` | Card | `incorrect-answer.svg` | `incorrect` | 50 | Persistent |
| `try_again` | Card | `try-again.svg` | None | 30 | Persistent when actionable |
| `activity_completed` | Card | `activity-completed.svg` | `success` | 80 | Persistent |
| `lesson_completed` | Completion modal | `lesson-completed.svg` | `complete` | 100 | Persistent |
| `module_completed` | Completion modal | `lesson-completed.svg` | `success` | 100 | Persistent |
| `informational` | Micro or card | `informational.svg` | None | 20 | Timed unless actionable |
| `warning` | Card | `warning.svg` | None | 60 | Persistent |

Only `lesson_completed` and `module_completed` may use `completion_modal`. This prevents callers from turning routine outcomes into blocking modals.

## 13. Feedback-Type Defaults

Default language remains encouraging, concise, and appropriate for sensitive education:

| Type | Default title | Default message |
|---|---|---|
| Correct | Excellent work | That answer is correct. |
| Incorrect | Not quite yet | Review your response and try again when you are ready. |
| Try again | Keep going | You can adjust your response and try again. |
| Activity completed | Activity complete | You completed this learning activity. |
| Lesson completed | Lesson complete | You completed this lesson. |
| Module completed | Module complete | You completed this module. |
| Informational | Good to know | Here is some information for this activity. |
| Warning | Let us pause here | Review this message before continuing. |

Integrated activities may supply contextual title, message, and explanation text. Defaults prevent every producer from recreating presentation copy.

## 14. Presentation Variants

### 14.1 Micro feedback

Micro feedback is non-modal, compact, and does not steal focus. It is appropriate for brief lifecycle information such as skip or resume confirmation. It uses a status icon and concise text; the illustration may appear as a small decorative accent on larger screens and be omitted visually at narrow widths.

### 14.2 Interactive feedback card

The card is used for correct, incorrect, retry, activity completion, informational, and warning outcomes. It contains:

- a status label and icon;
- one local illustration;
- a heading and message;
- optional plain-text explanation;
- up to two safe actions;
- a clearly labeled close control where dismissal is allowed.

Cards are non-modal and do not trap focus. Actionable cards remain until the learner acts or dismisses them.

### 14.3 Learning completion modal

Lesson and module completion use a modal with:

- `role="dialog"` and `aria-modal="true"`;
- a milestone heading and message;
- the larger `lesson-completed.svg` illustration;
- existing next-step destinations;
- a Close or Review option;
- focus placement, containment, and restoration.

The modal is a presentation of an already-persisted transition. Closing it cannot undo or repeat completion.

## 15. Visual Design System

The UI uses the established learner visual language:

- primary brand family `#A30EB2`, `#730DB1`, and `#3B0CB1`;
- existing Tailwind spacing, rounded corners, shadows, borders, focus rings, and dark-mode conventions;
- restrained semantic accent colors paired with icons and text so color is never the only cue;
- minimum 44-pixel interactive targets;
- clear title, message, explanation, and action hierarchy;
- no childish imagery, shame-oriented language, aggressive red treatment, or exaggerated celebration.

Static class branches are present in Blade so Tailwind can discover them. The implementation does not construct arbitrary Tailwind class names from event data.

## 16. Placement and Responsive Behavior

- Desktop micro feedback and cards appear in a fixed top-right region below the application header with a safe viewport inset.
- Mobile micro feedback and cards use a full-width inset panel near the top, never beneath the sticky lesson footer.
- Cards have a viewport-bounded height and internal scrolling only when content requires it.
- Completion modals center on desktop and use a near-full-screen, safe-area-aware panel on small screens.
- Illustrations scale with `object-contain` and fixed maximum heights; their SVG `viewBox` remains authoritative.
- Essential lesson controls remain reachable while non-modal feedback is open.

## 17. Event Lifecycle

For each event, the manager:

1. normalizes and validates the payload;
2. rejects a recently accepted duplicate ID;
3. assigns fixed configuration for its type;
4. compares it with the active event by priority;
5. displays it immediately, replaces routine feedback with a major completion, or stores it as the single pending event;
6. updates the screen-reader announcement;
7. requests one mapped sound from `learningAudio` only if the event became active and visible;
8. applies focus behavior appropriate to the presentation;
9. executes only a whitelisted action intent;
10. dismisses and promotes the pending event when appropriate.

## 18. Deduplication, Priority, and Pending Events

The manager keeps a bounded page-local set of recently accepted IDs. An ID is marked accepted before any later activation, so duplicate events cannot replay sound. Audio is requested only when the event becomes active and visible; a pending event that is replaced before display remains silent.

Producer components maintain a local feedback sequence that increments only for an accepted authoritative result. This permits legitimate repeated intermediate results while making accidental reprocessing of the same result idempotent.

Only one event is visible. If an event arrives while another is open:

- lesson or module completion replaces routine feedback and clears a lower-priority pending event;
- an identical ID is ignored;
- a lower-priority event cannot replace a completion modal;
- routine events keep only the newest eligible pending event;
- no visible stack or unbounded queue is created.

The recent-ID collection is capped to prevent unbounded page memory growth.

## 19. Dismissal Rules

- Non-actionable micro feedback dismisses after approximately three seconds.
- Non-actionable correct feedback dismisses after approximately four seconds.
- Any event with a retry, continue, or navigation action remains persistent.
- Incorrect, warning, activity-completed, lesson-completed, and module-completed feedback remains persistent.
- Escape dismisses a card or completion modal only when a safe close path is available.
- Clicking the modal backdrop may dismiss only when the event configuration permits Close.
- Dismissal never invokes a learning action or changes progress.
- Timers pause while the pointer is over the card or keyboard focus is inside it.

## 20. Action Contract

### 20.1 Retry

The manager dispatches one fixed, cancelable `learning-feedback:action` event with `{ feedbackId, sourceId, intent: 'retry' }`. The matching checkpoint or activity source calls its existing retry/reset behavior and marks the event handled with `preventDefault()`. Retry is omitted when the source does not support another attempt.

### 20.2 Continue

The same fixed cancelable action event carries `intent: 'continue'`. The source invokes its existing continuation method and marks the event handled.

### 20.3 Navigate

The manager navigates only to a validated same-origin URL supplied by trusted server or integrated application code. It does not submit forms or perform completion mutations.

### 20.4 Dismiss

Dismiss closes the presentation and restores focus where appropriate without notifying the learning source.

While an action is being processed, all action buttons are disabled. Retry and Continue dismiss only after a matching source marks the fixed event handled; an unhandled action leaves the card open and announces that the learner should use the activity controls. A single activation cannot produce duplicate submissions or navigation.

## 21. Learning-State Ownership

The feedback payload is a description of an outcome, never the outcome itself. Specifically:

- showing `correct` does not mark an answer correct;
- showing `activity_completed` does not save activity completion;
- showing `lesson_completed` or `module_completed` does not create a progress record;
- Retry uses the source component's existing reset method;
- Continue uses the source component's existing navigation method;
- Close leaves all source state intact;
- the manager stores no durable educational state.

## 22. Sound Integration

The manager receives the already-created `learningAudio` service/store as a dependency and calls only `play(key)`.

When a producer is migrated:

- its direct authoritative-result sound call is removed;
- selection sounds remain at their existing interaction points;
- skip, resume, retry, warning, and request failure remain silent;
- the manager requests the fixed mapped sound only when a newly accepted event becomes active and visible;
- the existing service continues to respect enabled state, volume, priority, overlap, browser unlock, and unavailable assets.

Server completion paths that create manager payloads do not also flash `learning_audio_event` for the same transition. Other existing server audio events remain unchanged.

## 23. Interactive Checkpoint Integration

After a successful checkpoint response updates local state:

- `correct` produces a Correct card with the existing explanation converted to plain text and a Continue action;
- `incorrect` produces a persistent Incorrect card with Retry when retry is already supported and optional Close/Skip behavior represented by existing controls;
- Perspective Feedback `completed` produces `activity_completed` with neutral contextual copy and Continue;
- `skipped` produces informational micro feedback and leaves existing continuation behavior intact;
- request failure produces a persistent Warning card without sound and retains the answer;
- Retry clears the existing answer and state through `retry()` and dismisses the feedback;
- restored checkpoint state on page load does not replay feedback or sound.

The current inline state region remains as a compact fallback/state indicator. It no longer duplicates the full title, message, illustration, explanation, and actions while the manager is available.

## 24. Matching and Sequencing Integration

The existing child event contract remains intact.

For Matching:

- an accepted intermediate correct connection produces Correct feedback;
- an incorrect check produces Incorrect feedback while highlighted connections remain visible;
- final completion produces Activity Completed feedback rather than an additional Correct event;
- Retry invokes the existing reconnection/reset behavior;
- practice mode uses the same presentation but does not change stored completion semantics.

For Sequencing:

- an incorrect check produces Incorrect feedback while the current order remains visible;
- a completed order produces Activity Completed feedback;
- Retry returns control to the existing reorder interaction;
- practice mode retains existing persistence boundaries.

Selection and reorder sounds remain where the successful interaction occurs. The shared parent remains the only source of result feedback and result sound, preventing child/parent duplication.

## 25. Activity Lifecycle Integration

- Skip and resume retain existing requests and status updates.
- Skip confirmation uses Informational micro feedback.
- Resume clears stale feedback and returns focus to the activity.
- Practice Again clears completion feedback and uses the existing practice method.
- Continue invokes the existing `continueLearning()` behavior.
- Recoverable save errors use Warning feedback without clearing learner work.
- Recovery clears only the matching warning event.
- Activity completion uses a persistent card with Continue and Practice Again when already supported.

## 26. Formal Quiz Compatibility

The formal quiz remains unchanged until submission:

- choices may continue to play `selection`;
- text entry remains silent;
- no question is graded in the browser;
- no correct or incorrect feedback manager event is shown per question;
- the result page remains the complete pass/fail and score presentation;
- a persisted attempt without module completion continues its existing success audio behavior;
- if the final quiz newly completes a module, the destination page receives one `module_completed` manager payload and one `success` sound through the manager.

Attempt limits, shields, scoring, pass thresholds, result details, and retry availability remain unchanged.

## 27. Lesson Completion Integration

A `lesson_completed` payload is flashed only after the existing lesson-completion logic confirms a new transition. Repeated completion requests and failure branches do not create feedback.

The payload may provide only destinations already derived by the server, such as:

- next lesson;
- lesson review/current page;
- module overview when no next lesson exists.

The manager does not independently identify the next lesson. The existing `complete` sound is requested once by the manager.

If the controller retains a legacy `success` message for no-JavaScript fallback, the learner layout suppresses its Toast only when the validated manager payload explicitly declares that it replaces the same success notice. The feedback host supplies an escaped `<noscript>` summary so completion remains understandable without JavaScript.

## 28. Module Completion Integration

A `module_completed` payload is produced only by an existing path that performs or confirms a new module-completion transition:

- final-quiz submission that newly completes the module;
- the guarded module completion page transition when it performs completion itself.

The modal may navigate to existing destinations such as:

- module completion page;
- certificate page when a certificate already exists;
- module overview;
- close/review.

The modal does not claim a certificate or submit a completion form. Existing POST actions remain on their existing pages. Reopening a completed module does not replay the modal or sound.

## 29. Server Bootstrap Contract

Controllers flash one `learning_feedback` array with the same normalized fields used by client events. Server payloads use fixed application-generated IDs, types, copy, and route URLs.

The layout passes the payload through Blade's safe JSON serialization to the feedback host. The manager validates it again before display. Session content cannot select an asset path, sound key, CSS class, HTML fragment, or arbitrary event name.

When a manager payload replaces an equivalent success Toast, it contains a fixed boolean such as `replacesSuccessToast: true`. The learner layouts consult only that validated condition; other session Toasts continue normally.

## 30. Ordinary Toast Preservation

The implementation must demonstrate that:

- `window.toast` remains loaded and unchanged for ordinary use;
- learner layout session errors and validation messages still render;
- shield, points, streak, achievement, and level notifications still render;
- topic completion and certificate notices remain on their current path unless a separately approved migration occurs;
- a manager event suppresses only the one equivalent learning success Toast;
- non-learner layouts are not modified.

## 31. Illustration Inventory and Mapping

The supplied untracked files currently include:

```text
completed.svg
correct-answer.svg
incorrect-answer.svg
informational.svg
try-again.svg
warning.svg
```

Implementation will review and adapt them as follows:

```text
completed.svg        -> activity-completed.svg
correct-answer.svg   -> correct-answer.svg
incorrect-answer.svg -> incorrect-answer.svg
informational.svg    -> informational.svg
try-again.svg        -> try-again.svg
warning.svg          -> warning.svg
```

One suitable milestone illustration must be obtained from the official unDraw gallery and stored as `lesson-completed.svg`. No placeholder or unrelated artwork may substitute for it.

The separately supplied `error-page.svg` and `itsnotyouitsus.svg` are not learning-feedback assets and are excluded from this directory and workstream.

## 32. Final Asset Directory

```text
public/
└── assets/
    └── illustrations/
        └── learning-feedback/
            ├── activity-completed.svg
            ├── correct-answer.svg
            ├── incorrect-answer.svg
            ├── informational.svg
            ├── lesson-completed.svg
            ├── try-again.svg
            └── warning.svg
```

No unrelated illustration is placed in this directory. The configuration refers only to these fixed filenames.

## 33. Illustration Selection Rules

Every illustration must be visually inspected before integration. Selection favors:

- mature, friendly, inclusive subjects;
- imagery that supports learning, reflection, progress, or recovery;
- calm expressions and non-shaming composition;
- suitability for a sensitive sex-education context;
- consistent unDraw visual style;
- legibility at card and modal sizes.

Illustrations that trivialize sensitive content, appear mocking, rely on stereotypes, or do not match the feedback meaning are rejected.

## 34. SVG Color Customization

The current supplied files use the standard unDraw purple accent. Customization is deliberate rather than global search-and-replace:

- primary accent shapes may use `#730DB1`;
- selected decorative accent shapes may use `#A30EB2` or `#3B0CB1` when visual balance benefits;
- neutral grays, white, black, skin colors, shadows, and structural details remain unchanged unless contrast requires a targeted correction;
- viewBox, geometry, transforms, and visual detail remain intact;
- external `<img>` rendering is used, so final colors are written into the SVG files rather than relying on page CSS variables.

Each changed color is recorded in the asset provenance document.

## 35. SVG Security and Integrity Review

Before integration, each SVG is checked for:

- `<script>` elements;
- inline event handlers;
- `javascript:` or data-script URLs;
- external image, font, stylesheet, or document references;
- unsafe `foreignObject` content;
- unnecessary metadata or editor payloads;
- missing or damaged `viewBox` values;
- broken dimensions or clipping;
- duplicate IDs that matter when used as external images.

SVGs are served as local external images. Untrusted SVG markup is never injected into the page.

## 36. Asset Provenance and License Record

Implementation adds `docs/learning-feedback-illustrations.md` containing, for each final file:

- official unDraw illustration title;
- official asset/gallery URL when it can be verified;
- creator/source;
- download or receipt date;
- final filename;
- original and final accent colors;
- security review result;
- visual suitability decision;
- the official unDraw license URL and the license terms relevant to application use.

The current supplied SVGs identify Katerina Limpitsouni and `https://undraw.co/`, but their exact titles and individual asset pages have not yet been verified. The implementation must verify them or record the missing source detail honestly; it must not invent provenance.

## 37. Accessibility

### 37.1 Announcements

- Correct, informational, and completion messages use polite announcements.
- Incorrect and warning messages use appropriately assertive announcements without repeating the same text in multiple live regions.
- Status icons and visible labels ensure correctness is not communicated by color alone.

### 37.2 Cards

- Cards do not steal focus on open.
- Close and action controls are keyboard reachable with visible focus.
- Timed cards pause while focused.
- Decorative illustrations use empty alternative text because the adjacent heading and message convey the outcome.

### 37.3 Modals

- Completion modals use dialog semantics, labelled headings, and descriptions.
- Focus moves to the modal heading or first meaningful action after render.
- Tab and Shift+Tab remain within the modal using the project's existing focus-management pattern.
- Escape behavior follows the event's Close permission.
- Focus returns to the prior active element when it still exists.

### 37.4 Content

- Copy is constructive and never shaming.
- Buttons use explicit labels rather than relying on icons.
- Contrast meets the existing accessible learner theme.
- Feedback remains understandable with sound muted or unavailable.

## 38. Reduced Motion

Default motion is limited to:

- opacity plus a slight upward transition for micro feedback and cards;
- a subtle scale and opacity transition for completion modals;
- optional slow, small illustration movement only when it remains calm.

`prefers-reduced-motion: reduce` removes translations, scaling, and illustration movement and uses immediate or opacity-only state changes. No confetti, flashing, bouncing, or continuous decorative animation is introduced.

## 39. Failure and Degraded Behavior

- Invalid payloads are ignored and may emit development-only diagnostics without exposing learner data.
- Missing illustration files show the full text, status icon, and actions; they never block learning.
- Sound failures stay inside `learningAudio` and never affect display or actions.
- If the manager cannot initialize, existing local activity state and inline fallback remain usable.
- Request failures preserve learner answers and use the existing activity error state in addition to any warning card.
- If JavaScript is disabled for a server completion event, the host's escaped `<noscript>` summary provides the message and existing page actions remain available.
- An unhandled Retry or Continue event leaves the feedback open, re-enables its actions, and directs the learner to the existing activity controls; navigation failures leave existing browser behavior intact.

## 40. Automated Testing Strategy

### 40.1 JavaScript manager tests

Add focused tests for:

- every supported type and default;
- fixed illustration and sound mapping;
- invalid type rejection;
- copy normalization and arbitrary HTML rejection;
- presentation clamping;
- action allowlisting and unsafe URL rejection;
- unique acceptance and duplicate rejection;
- completion priority and one-pending-event behavior;
- timed versus persistent dismissal;
- exactly-once audio calls;
- action button locking and fixed action dispatch;
- focus restoration hooks and Escape behavior;
- failure containment.

### 40.2 Producer tests

Extend existing checkpoint and activity tests to cover:

- correct, incorrect, neutral completion, and warning payloads;
- unique event sequencing;
- removal of direct result sound calls;
- retry and continue action routing;
- restored state remaining silent;
- unrelated activity IDs remaining isolated;
- Matching/Sequencing final completion replacing intermediate correctness.

### 40.3 Laravel feature/rendering tests

Cover:

- feedback host presence in both learner layouts;
- absence from unrelated layouts;
- newly completed lesson payloads;
- repeated/failure completion branches producing no payload;
- final-quiz module completion payloads;
- ordinary quiz attempts retaining existing result behavior;
- ordinary Toast and gamification markup preservation;
- duplicate success Toast suppression only for the equivalent manager event;
- existing progress, quiz, certificate, and access rules.

### 40.4 Asset tests

Assert:

- all seven final filenames exist and are non-empty;
- configuration paths resolve to those files;
- no unsafe SVG constructs or external references are present;
- each file retains a valid SVG root and `viewBox`;
- no learning-feedback asset is loaded from a runtime external URL.

## 41. Browser Verification Strategy

Use the installed browser automation tooling against the local learner experience when implementation is ready. Verify desktop and mobile viewport behavior for:

- correct checkpoint feedback;
- incorrect checkpoint feedback and functional Retry;
- Perspective Feedback completion;
- Matching progress, incorrect result, retry, and completion;
- Sequencing incorrect result, retry, and completion;
- activity skip, resume, continue, and practice again;
- lesson completion modal and next destination;
- module completion modal and completion-page destination;
- muted audio and synchronized existing sound controls;
- rapid and duplicate events without overlapping cards or sound;
- keyboard focus, Escape, focus restoration, and live announcements;
- reduced-motion emulation;
- ordinary session, shield, points, and streak Toast regression;
- missing-illustration and unavailable-audio graceful degradation.

Real sound quality and real-device results are reported only when actually exercised.

## 42. Expected File Impact

The implementation plan is expected to consider these focused areas and confirm exact paths before edits:

```text
resources/js/learning-feedback.js                         new manager/config
resources/js/app.js                                       Alpine store registration
resources/js/activity-feedback.js                         normalized activity outcomes
resources/js/interactive-activity.js                      manager emission/action handling
resources/js/interactive-checkpoint.js                    manager emission/action handling
resources/js/matching-activity.js                         only if existing retry wiring requires it
resources/js/sequencing-activity.js                       only if existing retry wiring requires it
resources/views/components/learner/learning-feedback.blade.php
resources/views/layouts/learner-app.blade.php
resources/views/layouts/learner-fullscreen.blade.php
resources/views/learner/lessons/partials/interactive-checkpoint.blade.php
resources/views/learner/lessons/partials/interactive-activities/shell.blade.php
resources/views/quizzes/result.blade.php                  only for existing completion integration
resources/views/learner/modules/completion.blade.php      only if bootstrap/action placement requires it
app/Http/Controllers/Learner/LessonController.php
app/Http/Controllers/Learner/QuizController.php
app/Http/Controllers/Learner/ModuleController.php
public/assets/illustrations/learning-feedback/*.svg
docs/learning-feedback-illustrations.md
tests/JavaScript/*feedback*.test.mjs
tests/JavaScript/interactive-checkpoint.test.mjs
tests/JavaScript/interactive-activity.test.mjs
focused learner feature tests
```

No listed file is changed merely because it appears here. The implementation plan must remove any path that inspection shows is unnecessary.

## 43. Implementation Sequence

1. Add failing manager/config tests and the smallest manager implementation.
2. Add the accessible Blade host and mount it in both learner layouts.
3. Integrate checkpoints and preserve their state/action behavior.
4. Integrate the shared activity coordinator, then Matching and Sequencing only where required.
5. Integrate authoritative lesson and module completion payloads and duplicate-Toast suppression.
6. Review, rename, recolor, secure, document, and install the seven SVG assets.
7. Run focused JavaScript, Laravel, asset, view, and build checks.
8. Run desktop/mobile browser verification and record actual evidence and limitations.

Each stage retains a usable learning flow and has a focused verification checkpoint.

## 44. Acceptance Criteria

The design is satisfied when:

- one manager and one shared host present every supported learning-feedback type;
- callers no longer construct independent popup markup;
- ordinary Toastify notifications continue to work;
- the existing learning-audio service is the only audio system;
- each accepted result produces at most one mapped sound;
- duplicate or rapid events cannot overlap or create an unbounded queue;
- retry, continue, navigate, and dismiss actions invoke only existing safe behavior;
- closing feedback never modifies answers or progress;
- lesson and module completion appear only after authoritative new completion;
- the formal quiz result and grading flows remain unchanged;
- all seven local SVGs are genuine reviewed unDraw illustrations with the required filenames;
- SVG colors, provenance, safety, and rendering are documented and verified;
- keyboard, focus, announcements, responsive behavior, and reduced motion meet the specified behavior;
- focused automated checks, the production build, and available browser checks are actually run and reported without overstating coverage.

## 45. Known Limitations

- Feedback history is page-local and not persisted.
- Sound preferences remain browser/device local by existing design.
- Formal quizzes still reveal correctness only after final submission.
- The manager does not replace full quiz results, module completion pages, or certificate workflows.
- Exact source-page provenance for the six supplied SVGs remains to be verified during asset integration.
- A seventh lesson-completion illustration still must be selected, downloaded from the official unDraw gallery, reviewed, and documented.
- Real-device and perceived-audio-quality coverage depends on available devices and must be reported honestly.
- Site-wide branded Laravel error pages are a separate approved workstream and are intentionally excluded from this implementation plan.

## 46. Planning Handoff

After the user reviews and approves this written specification, the next step is a dedicated implementation plan created with the writing-plans workflow. That plan will inspect the then-current `FeedbackManager` checkout, convert the implementation sequence into small test-first tasks, name exact commands, and preserve unrelated worktree changes and development data.
