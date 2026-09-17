# Learning Feedback Audio System Design

**Date:** 2026-09-17

**Status:** Approved

**Scope:** Learner-facing platform audio feedback using Laravel, Blade, Alpine.js, Vite, Howler.js, local MP3 assets, and browser-local preferences

## Purpose

Add a centralized, optional audio feedback layer to the learner experience. Audio reinforces existing visual states but never evaluates an answer, controls progress, or blocks learning.

The system uses exactly five semantic sounds:

- `selection` for lightweight intentional choices;
- `correct` for a graded response confirmed as correct;
- `incorrect` for a graded response confirmed as incorrect;
- `success` for a successfully recorded quiz or completed activity, checkpoint, or module;
- `complete` for newly completed learning content such as a topic or lesson.

## Existing System Findings

The learner experience uses two primary layouts:

- `resources/views/layouts/learner-app.blade.php` for standard learner pages, formal quizzes, quiz results, module pages, and module completion;
- `resources/views/layouts/learner-fullscreen.blade.php` for the lesson viewer and its module-aware top bar.

The common Vite entry point is `resources/js/app.js`. It registers Alpine components and global Alpine stores before calling `Alpine.start()`. Existing JavaScript learning interactions are split into focused modules:

- `resources/js/interactive-checkpoint.js` owns checkpoint request and response state;
- `resources/js/interactive-activity.js` coordinates shared activity lifecycle and feedback;
- `resources/js/matching-activity.js` owns Matching selection and evaluation behavior;
- `resources/js/sequencing-activity.js` owns Sequencing reorder and evaluation behavior;
- the formal quiz wizard currently lives in `resources/views/quizzes/take.blade.php` and deliberately does not grade individual questions before final submission.

Checkpoint and activity responses already expose authoritative semantic states such as `is_correct`, `is_complete`, and `status`. The audio layer can react to those states without duplicating evaluation logic.

Topic completion and automatic lesson completion occur in `Learner\LessonController`. Formal quiz attempts and the final-quiz module-completion transition occur in `Learner\QuizController`. A secondary guarded module-completion transition exists in `Learner\ModuleController::completion()`.

The existing learner profile editor is `resources/views/profile/learner-edit.blade.php`. It already uses tabbed controls and is the appropriate location for browser-local sound preferences.

## Goals

- Use Howler.js as the only browser audio abstraction.
- Use five individual local MP3 files under `public/audio/feedback/`.
- Give all learner components a small semantic playback API.
- Keep preferences synchronized between settings and quick toggles.
- Handle browser autoplay restrictions without a disruptive modal.
- Trigger completion sounds only after authoritative state changes succeed.
- Prevent noisy overlap with a small deterministic priority policy.
- Preserve visual feedback, grading, scoring, progress, and navigation behavior.
- Degrade silently when audio is unavailable.

## Non-Goals

- No database table or user-profile columns for audio preferences.
- No cross-device preference synchronization.
- No external audio service or API.
- No audio sprites.
- No additional semantic sound categories.
- No immediate formal-quiz grading endpoint or altered quiz behavior.
- No changes to scoring, answer evaluation, progress calculations, completion rules, quiz limits, permissions, enrollment, or content access.
- No sound for passive state restoration, scrolling, navigation, typing, hover, modal opening, skipping, or retry controls.

## Considered Approaches

### 1. Central service with explicit semantic calls — selected

A single JavaScript service owns Howler, source paths, preference persistence, initialization, playback priority, and errors. Components explicitly request semantic sounds such as `learningAudio.play('correct')` after their existing state logic produces a result.

This approach is traceable, testable, and makes it impossible for file paths or `Howl` instances to spread through learner components.

### 2. Global browser event bus

Components could dispatch custom audio events to a layout-level listener. This reduces imports but replaces normal method calls with string-based runtime wiring and makes unit tests and call tracing less direct.

### 3. DOM delegation and data attributes

A global listener could infer sounds from clicks and markup. This has the smallest apparent component diff but cannot reliably distinguish an attempted interaction from a server-confirmed result. It would be especially unsafe for completion actions.

## Central Service

Create `resources/js/learning-audio.js`. It is the only application module allowed to import Howler or instantiate `Howl`.

The public interface is:

```text
initialize()
unlock()
play(soundKey)
setEnabled(enabled)
isEnabled()
setVolume(volume)
getVolume()
```

The implementation exposes one singleton through both:

- `window.learningAudio` for existing inline Blade/Alpine handlers;
- `Alpine.store('learningAudio')` for settings and navigation controls.

The Alpine store delegates playback and persistence to the service rather than maintaining a second preference system.

### Source mapping

The service owns the complete mapping:

```text
selection -> /audio/feedback/selection.mp3
correct   -> /audio/feedback/correct.mp3
incorrect -> /audio/feedback/incorrect.mp3
success   -> /audio/feedback/success.mp3
complete  -> /audio/feedback/complete.mp3
```

Components know only the five keys. Moving assets later requires changing only this mapping.

### Howler loading and preloading

Howler is installed with npm because `package-lock.json` is the repository's current JavaScript lockfile. It is a runtime dependency.

The service dynamically imports Howler through Vite when a learner layout initializes audio. This keeps Howler out of non-learner startup work and lets initialization failures be caught without breaking the main application. The five sounds preload non-blockingly after the import resolves.

If stored preferences have audio disabled, Howler and the assets may remain unloaded until the learner enables sound. Test Sound and enabling audio both initialize the service when necessary.

Initialization is idempotent. Concurrent calls share the same initialization work and never construct duplicate sound banks.

## Preferences

The defaults are:

```text
enabled = true
volume = 0.7
```

Use these stable local-storage keys:

```text
cc_learning_audio_enabled
cc_learning_audio_volume
```

Only literal `true` and `false` values are accepted for enabled state. Volume must parse as a finite number from `0` through `1`. Missing, malformed, inaccessible, or out-of-range values fall back safely to defaults; volume input is clamped before persistence.

Enabled state and volume remain independent. Setting volume to zero does not change the enabled preference. The service changes only its own Howler sounds and never affects device or system volume.

All `localStorage` access is guarded because storage can be unavailable in restricted browsing contexts.

## Playback and Priority

Audio playback is fire-and-forget. Educational state changes happen first; sound is attempted afterward and is never awaited by grading, saving, completion, or navigation logic.

The priority order is:

```text
complete > success > correct/incorrect > selection
```

The lightweight policy is:

- keep at most one feedback sound active;
- let a higher-priority event stop and replace a lower-priority sound;
- ignore a lower-priority event while a higher-priority sound is active;
- replace an equal-priority sound with the newer authoritative event;
- apply a short cooldown to repeated `selection` events;
- never queue a sequence of routine interaction sounds.

The component mapping intentionally avoids emitting both `correct` and `success` for the same state transition. Priority remains a safeguard for events from different components that occur close together.

## Browser Unlock and Deferred Playback

Learner layouts initialize audio without blocking rendering. The service listens for the first meaningful pointer or keyboard gesture and explicitly resumes Howler's Web Audio context when required. Test Sound also calls `unlock()` within its user gesture.

Server-confirmed completion events are delivered to the destination page as one-time Laravel flash data. The learner layout passes the semantic key to the service. The service attempts playback after initialization; if the browser rejects playback, it retains only that highest-priority pending event and retries once after the next meaningful gesture.

No Enable Audio modal is introduced. If playback remains unavailable, the event is discarded without affecting the learner experience.

Disabling sound clears pending feedback so an old completion sound cannot play after the learner has opted out.

## Semantic Event Mapping

| Existing learner event | Sound | Notes |
|---|---|---|
| Formal quiz radio, checkbox, or word-bank choice | `selection` | Text typing stays silent. |
| Quiz attempt committed successfully | `success` | Applies regardless of score; the visual result remains authoritative. |
| Graded checkpoint choice or word-bank choice | `selection` | No sound for text entry. |
| Graded checkpoint response confirmed correct | `correct` | Do not also emit `success`. |
| Graded checkpoint response confirmed incorrect | `incorrect` | Existing retry behavior is unchanged. |
| Perspective Feedback pathway or guided option selected | `selection` | Neutral interaction only. |
| Guided or written Perspective Feedback submission confirmed completed | `success` | Never emit `correct` or `incorrect`. |
| Matching source/target selection or completed connection | `selection` | Subject to the selection cooldown. |
| Matching intermediate response confirmed correct | `correct` | Final completion uses `success` instead. |
| Matching validation confirmed incorrect | `incorrect` | Existing highlighted feedback remains primary. |
| Matching activity confirmed complete | `success` | Do not also emit `correct`. |
| Sequencing order actually changes after pointer drop or keyboard move | `selection` | No drag-start sound. |
| Sequencing validation confirmed incorrect | `incorrect` | Existing visual results remain primary. |
| Sequencing activity confirmed complete | `success` | Do not also emit `correct`. |
| New topic completion confirmed | `complete` | Emitted only after persistence succeeds. |
| New lesson completion confirmed | `complete` | A topic that also completes its lesson emits one `complete`. |
| New module completion confirmed | `success` | Emitted once for the transition, not page revisits. |
| Skip, retry, resume, navigation, hover, typing, restored state, or request failure | none | These actions do not communicate a new graded/completed result. |

## Formal Quiz Compatibility

The formal quiz wizard continues to collect answers without exposing correctness. Choice interactions use `selection`; text input remains silent.

`Learner\QuizController::submit()` remains the sole grading path. After the attempt transaction commits, the redirect carries a one-time `success` audio event. This event means the attempt was successfully recorded, not that the learner passed. The result screen continues to communicate score and pass/fail visually.

A quiz submission that also completes the module emits one `success`, covering both closely related transitions. A persisted attempt emits `success` even when its score does not pass; a request that fails validation or does not persist carries no audio event. Reopening an existing result does not replay the sound.

## Interactive Checkpoint Compatibility

`resources/js/interactive-checkpoint.js` reacts only to the existing server response:

- `correct` status plays `correct`;
- `incorrect` status plays `incorrect`;
- Perspective Feedback `completed` status plays `success`;
- request errors and skips stay silent.

The existing `QuestionEvaluator` remains the source of correctness. Perspective Feedback remains outside that evaluator and retains `is_correct = null`.

Restoring a previously completed checkpoint on page load never produces audio.

## Interactive Activity Compatibility

Matching and Sequencing keep their existing request, validation, working-state, retry, and practice flows.

Selection sounds live at the actual successful selection/reorder action. Evaluation sounds are triggered from the shared activity result path so a child component and its parent cannot emit duplicate result sounds.

For Matching, an intermediate correct connection uses `correct`; completing the final connection uses `success`. For Sequencing, a changed placement uses `selection`, an incorrect check uses `incorrect`, and a completed sequence uses `success`.

Practice mode follows the same semantic mapping because it already returns the same correctness and completion fields, but it does not alter stored progress.

## Topic, Lesson, and Module Completion

Laravel uses one flash key:

```text
learning_audio_event
```

Controllers assign the key only after the existing authoritative state transition succeeds:

- `Learner\LessonController::completeTopic()` uses `complete` for a newly completed topic;
- when the same request automatically completes the lesson, no second sound is added;
- `Learner\LessonController::complete()` uses `complete` for a newly completed lesson;
- `Learner\QuizController::submit()` uses `success` after a quiz attempt commits, including the final-quiz module path;
- `Learner\ModuleController::completion()` uses `success` only if that request performs the guarded module transition itself.

Already-completed and error branches do not set the flash key. Audio introduces no new completion calculation and does not change existing transitions.

Both learner layouts consume the same flash key and pass it to the centralized service.

## Learner Controls

### Settings

Add a Sound Effects tab to `resources/views/profile/learner-edit.blade.php`, extending the existing tab system without rewriting it.

The tab contains:

- a labeled ON/OFF control with switch semantics and visible state;
- a labeled range input from 0 through 100 percent;
- a visible numeric percentage;
- a Test Sound button using `correct`;
- concise text explaining that preferences apply only to this browser/device.

Controls read and update `Alpine.store('learningAudio')`. Test Sound respects enabled state and configured volume.

### Quick toggles

Add a speaker toggle to:

- `resources/views/layouts/learner-header.blade.php`;
- `resources/views/layouts/learner-fullscreen.blade.php`.

The toggles use speaker and muted SVG states rather than relying on emoji. They expose an accessible name, `aria-pressed`, visible enabled/disabled state, keyboard activation, focus styling, and an adequate touch target.

Both controls delegate to the same Alpine store as the settings tab.

## Accessibility

- Sound is never the sole indicator of correctness, failure, progress, or completion.
- Existing messages, colors, icons, focus behavior, and live regions remain authoritative.
- Sound controls are keyboard accessible and labeled for assistive technology.
- Muted state is visually distinguishable.
- No sound is attached to hover, scrolling, typing, or passive DOM updates.
- Learners can disable audio from either navigation layout or the settings page.
- Browser autoplay rules and the learner's device volume are respected.

## Asset Preparation and Provenance

The final application files are:

```text
public/audio/feedback/selection.mp3
public/audio/feedback/correct.mp3
public/audio/feedback/incorrect.mp3
public/audio/feedback/success.mp3
public/audio/feedback/complete.mp3
```

The supplied `select.mp3` is renamed to `selection.mp3` during implementation.

The original exact-title, creator, and asset-page requirements are intentionally relaxed because those details were not retained at download time. `docs/audio-feedback-assets.md` records, without inventing missing data:

- Mixkit as the direct source;
- the Mixkit Sound Effects Free License;
- the download date;
- the WAV-to-MP3 conversion and any subsequent preparation;
- the final filename;
- exact title, creator, and asset URL as not retained.

Mixkit currently states that its free sound effects can be used in personal and commercial projects and that attribution is not required: <https://mixkit.co/free-sound-effects/>.

Before acceptance, the files receive a manual trim, click/pop, duration, excessive-volume, and relative-loudness comparison. The source files, not per-component volume overrides, are corrected if the set is materially mismatched. If suitable editing tooling is unavailable, any required re-export is reported explicitly rather than silently compensating in code.

## Error Handling

The learner sees no technical audio error.

The service catches and contains:

- dynamic Howler import failure;
- Howler construction failure;
- asset load failure;
- play failure;
- blocked or suspended audio context;
- missing or unsupported assets;
- inaccessible or malformed local-storage values.

A sound that produces a load error is marked unavailable for the current page so normal interactions do not retry it continuously. Development-only logging includes the semantic key and failure category without exposing sensitive data.

No controller, Alpine component, or navigation flow awaits playback.

## Testing Strategy

### JavaScript unit tests

Add focused tests using injected Howler and storage doubles for:

- default enabled state and 70 percent volume;
- valid persistence and malformed-value fallback;
- disabling, reenabling, and zero volume;
- volume propagation to all sounds;
- all five paths and semantic keys;
- non-blocking initialization;
- constructor, load, and play failures;
- pending playback after unlock;
- priority replacement and lower-priority suppression;
- selection cooldown and repeated playback.

Extend the existing checkpoint and activity tests to assert semantic calls without loading real audio.

### Laravel feature and rendering tests

Cover:

- settings and both quick-toggle renderings;
- semantic flash data only on successful new topic, lesson, quiz, and module transitions;
- no completion audio on failure or already-completed branches;
- quiz grading and scoring regression behavior;
- neutral Perspective Feedback completion;
- preservation of current completion and access rules.

### Asset and build checks

- Assert that all five exact MP3 paths exist and are non-empty.
- Confirm there are no scattered MP3 path references or component-level `Howl`/`Audio` construction.
- Run the relevant Node test suite.
- Run focused Laravel tests, followed by the safe existing test suite where practical.
- Run the Vite production build and whitespace checks.

### Browser verification

Automate Chromium desktop and mobile-viewport checks for:

- first interaction and unlock;
- settings persistence after refresh;
- quick-toggle synchronization;
- Test Sound;
- answer, checkpoint, Matching, and Sequencing events;
- successful completion events;
- missing-asset graceful degradation;
- repeated and rapid playback behavior.

Provide a manual checklist for Edge/Chromium desktop, Android Chrome, and iOS Safari. Real-device combinations that are unavailable are reported as untested rather than claimed as verified.

## Expected File Areas

Implementation is expected to touch only the focused areas below, subject to the implementation plan and test discovery:

- `package.json` and `package-lock.json`;
- `resources/js/learning-audio.js` and `resources/js/app.js`;
- existing checkpoint and activity JavaScript modules;
- learner quiz, checkpoint, settings, and layout Blade views;
- the existing learner lesson, quiz, and module controllers only where they already confirm completion;
- `public/audio/feedback/*.mp3`;
- `docs/audio-feedback-assets.md`;
- focused JavaScript and Laravel tests.

No migration, model field, database reset, destructive seed, or unrelated learner refactor is part of this work.

## Acceptance Summary

The design is satisfied when the five local sounds are accessible only through the centralized semantic service; preferences and both navigation toggles share one browser-local state; authoritative learner results trigger the agreed sounds once; Perspective Feedback stays neutral; overlap and autoplay restrictions are handled gracefully; audio failure never affects learning; accessibility and visual feedback remain intact; and focused automated and browser checks pass without changing grading or progression behavior.
