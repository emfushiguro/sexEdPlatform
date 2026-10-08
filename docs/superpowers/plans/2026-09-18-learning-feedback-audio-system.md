# Learning Feedback Audio System Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add optional, centralized, semantic audio feedback across learner quizzes, checkpoints, activities, and successful learning-progress transitions without changing any grading, scoring, or completion rules.

**Architecture:** A single testable `LearningAudioService` dynamically loads Howler.js, owns the five local MP3 mappings, persists one enabled flag and one volume value in `localStorage`, and exposes the same reactive object through `window.learningAudio` and `Alpine.store('learningAudio')`. Existing client components call semantic keys only. Server-confirmed redirect transitions publish one `learning_audio_event` flash value that learner layouts consume after navigation; direct completion views use same-response session data so events play once.

**Tech Stack:** Laravel 12, PHP 8.2+, Blade, Alpine.js 3, Tailwind CSS, Vite 7, Howler.js, Node's built-in test runner, PHPUnit 11, localStorage, local MP3 assets.

**Approved design:** `docs/superpowers/specs/2026-09-17-learning-feedback-audio-system-design.md`

## Global Constraints

- Use exactly five semantic keys: `selection`, `correct`, `incorrect`, `success`, and `complete`.
- Keep exactly five individual files under `public/audio/feedback/`; do not add sprites or a sixth sound.
- Only `resources/js/learning-audio.js` may import Howler, instantiate `Howl`, or contain feedback MP3 paths.
- Components call `learningAudio.play('<semantic-key>')`; they never reference filenames or determine correctness.
- Audio is fire-and-forget. No grading, saving, progress, completion, redirect, or navigation path may await successful playback.
- Existing visual feedback remains authoritative and complete when sound is disabled or unavailable.
- Formal quizzes keep delayed server-side grading. Answer choices emit `selection`; a persisted attempt emits one `success`, whether passed or failed.
- A graded checkpoint emits exactly one result sound: `correct` or `incorrect`. Perspective Feedback emits `success`, never `correct` or `incorrect`.
- An Interactive Activity result emits exactly one sound: `success` when complete, otherwise `correct` or `incorrect` from the existing response.
- Topic and lesson completion emit `complete` only after a new successful transition. A topic that auto-completes its lesson still emits one `complete`.
- Module and quiz completion use `success`. If a final quiz also completes the module, the redirect carries one `success`, not two sounds.
- Revisited/restored completed state, skip, retry, typing, navigation, scrolling, hover, modal open, drag start, and failed requests stay silent.
- Preferences are browser/device-local only. Do not add migrations, model fields, requests, controllers, or profile columns for them.
- Never reset, wipe, truncate, recreate, or destructively reseed any database. Use the isolated test configuration from `phpunit.xml`.
- Preserve all unrelated worktree changes. In particular, do not stage or replace pre-existing `public/build/` or `storage/framework/` changes.
- The active settings surface is `resources/views/learner/partials/edit-profile-modal.blade.php`. Do not revive `resources/views/profile/learner-edit.blade.php`, because its route redirects to the dashboard modal.
- Exact Mixkit asset titles, creator names, and per-asset URLs were not retained. Record that fact honestly; do not invent provenance.

## Existing Ownership Map

| Responsibility | Existing authority | Audio integration point |
|---|---|---|
| JavaScript/Alpine bootstrap | `resources/js/app.js` | Register one service/store before `Alpine.start()` |
| Standard learner shell | `resources/views/layouts/learner-app.blade.php` | Page marker, flashed event, standard-header toggle |
| Lesson shell | `resources/views/layouts/learner-fullscreen.blade.php` | Page marker, flashed event, fullscreen toggle |
| Active learner preferences UI | `resources/views/learner/partials/edit-profile-modal.blade.php` | Sound Effects tab backed only by the shared store |
| Checkpoint evaluation | `resources/js/interactive-checkpoint.js` | React to returned `status`/`is_correct` |
| Checkpoint choices | `resources/views/learner/lessons/partials/interactive-checkpoint.blade.php` | Intentional selection calls; text remains silent |
| Activity feedback orchestration | `resources/js/interactive-activity.js` | One authoritative result sound |
| Matching input | `resources/js/matching-activity.js` | Valid endpoint selection and created connection |
| Sequencing input | `resources/js/sequencing-activity.js` | Actual order changes only |
| Formal quiz choices | `resources/views/quizzes/take.blade.php` | Radio, checkbox, and word-bank selection only |
| Formal quiz persistence | `app/Http/Controllers/Learner/QuizController.php` | `success` after the attempt transaction and follow-up work succeed |
| Topic/lesson transitions | `app/Http/Controllers/Learner/LessonController.php` | `complete` on new completion only |
| Guarded module transition | `app/Http/Controllers/Learner/ModuleController.php` | Same-response `success` on new completion only |

## Cross-Task Contracts

### Service interface

```javascript
initialize(): Promise<boolean>
unlock(): Promise<boolean>
play(soundKey): boolean
setEnabled(enabled): void
isEnabled(): boolean
setVolume(volume): void
getVolume(): number
```

For testability, also export:

```javascript
createLearningAudioService(options = {}): LearningAudioService
initializeLearningAudioPage(service, root = document): void
```

`options` accepts injected `loadHowler`, `storage`, `eventTarget`, `now`, and `warn` collaborators. Application code uses the exported singleton; tests use the factory.

### Stable storage and source configuration

```javascript
export const LEARNING_AUDIO_STORAGE_KEYS = Object.freeze({
    enabled: 'cc_learning_audio_enabled',
    volume: 'cc_learning_audio_volume',
});

export const LEARNING_AUDIO_SOURCES = Object.freeze({
    selection: '/audio/feedback/selection.mp3',
    correct: '/audio/feedback/correct.mp3',
    incorrect: '/audio/feedback/incorrect.mp3',
    success: '/audio/feedback/success.mp3',
    complete: '/audio/feedback/complete.mp3',
});
```

Defaults are `enabled = true` and `volume = 0.7`. Only stored strings `true` and `false` are valid enabled values. Volume must be finite and within `0..1`; malformed or inaccessible storage falls back to defaults.

### Server-to-client event contract

The only server key is:

```php
'learning_audio_event' => 'selection|correct|incorrect|success|complete'
```

Redirect responses use flash data. `ModuleController::completion()` returns a view in the same request, so its direct transition uses `session()->now(...)` to avoid replay on the following request.

### Result mapping

```text
checkpoint status=correct             -> correct
checkpoint status=incorrect           -> incorrect
checkpoint status=completed           -> success
checkpoint status=skipped/error       -> no sound
activity is_complete=true             -> success
activity is_correct=true, incomplete  -> correct
activity is_correct=false             -> incorrect
persisted quiz attempt                -> success
new topic/lesson completion           -> complete
new module completion                 -> success
```

---

### Task 1: Install Howler and finalize the five-file asset set

**Files:**
- Modify: `package.json`
- Modify: `package-lock.json`
- Rename: `public/audio/feedback/select.mp3` -> `public/audio/feedback/selection.mp3`
- Retain: `public/audio/feedback/correct.mp3`
- Retain: `public/audio/feedback/incorrect.mp3`
- Retain: `public/audio/feedback/success.mp3`
- Retain: `public/audio/feedback/complete.mp3`
- Modify: `docs/audio-feedback-assets.md`

**Interfaces:**
- Consumes: The five MP3 files already converted by the project owner.
- Produces: The exact public asset paths consumed by `LEARNING_AUDIO_SOURCES`, plus an npm-managed Howler runtime dependency.

- [ ] **Step 1: Capture the dependency and asset baseline**

Run:

```powershell
npm ls howler --depth=0
Get-ChildItem -LiteralPath 'public\audio\feedback' -File | Select-Object Name, Length
```

Expected: `npm ls` reports Howler missing, and the directory contains `select.mp3` plus the other four semantic files.

- [ ] **Step 2: Install Howler through the existing npm pipeline**

Run:

```powershell
npm install howler
```

Expected: `howler` is under runtime `dependencies`, and `package-lock.json` is updated. Do not vendor Howler and do not add Tone.js.

- [ ] **Step 3: Safely rename the user-supplied selection asset**

Validate both exact paths are inside the workspace before moving the binary:

```powershell
$source = Join-Path (Get-Location) 'public\audio\feedback\select.mp3'
$target = Join-Path (Get-Location) 'public\audio\feedback\selection.mp3'
if (-not (Test-Path -LiteralPath $source -PathType Leaf)) { throw "Missing $source" }
if (Test-Path -LiteralPath $target) { throw "Target already exists: $target" }
Move-Item -LiteralPath $source -Destination $target
```

- [ ] **Step 4: Replace blank provenance fields with the approved relaxed record**

For every asset entry in `docs/audio-feedback-assets.md`, record these exact facts:

```text
Source: Mixkit (direct download)
Source page: https://mixkit.co/free-sound-effects/
Exact asset title: Not retained
Exact asset URL: Not retained
Creator/author: Not retained / not displayed in the retained download record
Applicable license: Mixkit Sound Effects Free License
License page: https://mixkit.co/license/#sfxFree
Attribution required: No
Download date: 2026-09-17
Modifications: Project owner manually converted the downloaded WAV to MP3.
Final filename: <the matching semantic filename>
```

Add a note that final relative-loudness, silence, click/pop, and duration review is performed in Task 10. Do not claim normalization values that were not measured.

- [ ] **Step 5: Verify exact scope and dependency ownership**

Run:

```powershell
npm ls howler --depth=0
Get-ChildItem -LiteralPath 'public\audio\feedback' -File | Sort-Object Name | Select-Object Name, Length
```

Expected filenames, and no others:

```text
complete.mp3
correct.mp3
incorrect.mp3
selection.mp3
success.mp3
```

- [ ] **Step 6: Commit only dependency, asset, and provenance files**

```powershell
git add package.json package-lock.json docs/audio-feedback-assets.md public/audio/feedback/selection.mp3 public/audio/feedback/correct.mp3 public/audio/feedback/incorrect.mp3 public/audio/feedback/success.mp3 public/audio/feedback/complete.mp3
git commit -m "build(audio): add howler and feedback assets"
```

Do not stage `public/build/` or `storage/framework/`.

---

### Task 2: Build the centralized LearningAudioService test-first

**Files:**
- Create: `resources/js/learning-audio.js`
- Create: `tests/JavaScript/learning-audio.test.mjs`

**Interfaces:**
- Consumes: Dynamic `import('howler')`, guarded `localStorage`, the five public asset URLs, pointer/keyboard gestures.
- Produces: The public service API and singleton used by every subsequent task.

- [ ] **Step 1: Write the failing service tests**

Create `tests/JavaScript/learning-audio.test.mjs` with a fake storage object, an event target that records listeners, and fake `Howl`/`Howler` objects. The fake `Howl` must record `src`, `preload`, `volume`, `play`, `stop`, and callback calls without accessing browser audio.

Cover these exact tests:

```javascript
test('defaults to enabled at seventy percent when storage is missing or malformed', async () => {});
test('persists enabled and clamped volume with stable namespaced keys', async () => {});
test('maps and preloads exactly five semantic files through one Howl bank', async () => {});
test('does not import howler until an enabled learner page initializes', async () => {});
test('disabled playback is silent and reenabling initializes once', async () => {});
test('propagates application volume to every loaded sound', async () => {});
test('higher priority replaces lower priority and lower priority is ignored', async () => {});
test('equal result priority replaces the active result while selection is cooled down', async () => {});
test('blocked playback retains one highest-priority event and retries once on the next gesture', async () => {});
test('load, construction, asset, storage, and playback failures stay contained', async () => {});
test('page initialization ignores non-learner pages and consumes a valid flashed event', async () => {});
```

Use the constants from the implementation in assertions rather than duplicating paths except where asserting the approved contract.

- [ ] **Step 2: Run the service test and verify failure**

Run:

```powershell
node --test tests/JavaScript/learning-audio.test.mjs
```

Expected: FAIL with `ERR_MODULE_NOT_FOUND` for `resources/js/learning-audio.js`.

- [ ] **Step 3: Implement constants, parsing, persistence, and factory state**

Create `resources/js/learning-audio.js` with these exported constants and factory shape:

```javascript
export const LEARNING_AUDIO_STORAGE_KEYS = Object.freeze({
    enabled: 'cc_learning_audio_enabled',
    volume: 'cc_learning_audio_volume',
});

export const LEARNING_AUDIO_SOURCES = Object.freeze({
    selection: '/audio/feedback/selection.mp3',
    correct: '/audio/feedback/correct.mp3',
    incorrect: '/audio/feedback/incorrect.mp3',
    success: '/audio/feedback/success.mp3',
    complete: '/audio/feedback/complete.mp3',
});

const PRIORITY = Object.freeze({ selection: 1, correct: 2, incorrect: 2, success: 3, complete: 4 });
const DEFAULT_ENABLED = true;
const DEFAULT_VOLUME = 0.7;
const SELECTION_COOLDOWN_MS = 150;

export function createLearningAudioService({
    loadHowler = () => import('howler'),
    storage = undefined,
    eventTarget = globalThis.document,
    now = () => Date.now(),
    warn = (...args) => {
        if (import.meta.env?.DEV) console.warn(...args);
    },
} = {}) {
    // Return the service object defined by the interface below.
}
```

The returned object owns reactive public `enabled` and `volume` properties. If no storage double is injected, resolve `globalThis.localStorage` inside `try/catch`; property access itself can throw in restricted contexts. Read and write storage only through guarded helpers. Treat only literal `true`/`false` as valid enabled values; accept volume only when `Number.isFinite(value)` and `0 <= value <= 1`.

- [ ] **Step 4: Implement idempotent dynamic loading and non-blocking preloading**

Inside the factory:

- Keep one `initializationPromise`; concurrent `initialize()` calls return it.
- If disabled, `initialize()` resolves `false` without calling `loadHowler`.
- Dynamically resolve `{ Howl, Howler }` and construct one `Howl` per `LEARNING_AUDIO_SOURCES` entry with `src: [path]`, `preload: true`, and the current shared volume.
- Store failed asset keys in an `unavailable` set from `onloaderror`; `play()` never reconstructs or retries those assets on every interaction.
- Catch dynamic-import and constructor failures, warn only through the injected development logger, and resolve `false`.
- Never throw an audio failure into a caller.

- [ ] **Step 5: Implement deterministic priority, cooldown, and one-retry unlock**

Maintain only:

```javascript
let activePlayback = null; // { key, id, priority }
let pendingPlayback = null; // { key, priority }
let lastSelectionAt = Number.NEGATIVE_INFINITY;
```

Enforce:

- `complete > success > correct/incorrect > selection`.
- A higher priority stops/replaces the active sound.
- A lower priority is ignored while a higher priority remains active.
- Equal priority stops/replaces the active sound.
- Repeated `selection` inside 150 ms is ignored.
- There is no routine playback queue.
- `onend` and `onstop` clear `activePlayback` only when their key/id still match the active record, so a stale callback cannot clear a replacement sound.
- A first autoplay/play error keeps only the highest-priority pending key and installs one pointer/keyboard gesture listener pair.
- Whichever gesture fires removes both listeners, resumes `Howler.ctx` when suspended, and retries that pending key once.
- A retry failure is discarded rather than queued again.
- `setEnabled(false)` stops active playback, clears pending playback/listeners, and persists `false`.
- `setEnabled(true)` persists `true` and starts idempotent initialization without awaiting it.

`play(soundKey)` validates the key, returns immediately, and schedules initialization/playback without being awaited by application code.

- [ ] **Step 6: Implement shared volume and page bootstrap helper**

`setVolume(value)` converts and clamps to `0..1`, persists it, updates the public property, and calls `sound.volume(newVolume)` on every constructed Howl. Volume zero does not change `enabled`.

Export:

```javascript
export function initializeLearningAudioPage(service, root = globalThis.document) {
    const body = root?.body;
    if (!body?.hasAttribute('data-learning-audio')) return;

    service.initialize();
    const soundKey = body.dataset.learningAudioEvent;
    if (Object.hasOwn(LEARNING_AUDIO_SOURCES, soundKey)) service.play(soundKey);
}

export const learningAudio = createLearningAudioService();
```

- [ ] **Step 7: Run the focused tests and verify pass**

```powershell
node --test tests/JavaScript/learning-audio.test.mjs
```

Expected: PASS, with no unhandled rejection or real audio/network access.

- [ ] **Step 8: Commit the service**

```powershell
git add resources/js/learning-audio.js tests/JavaScript/learning-audio.test.mjs
git commit -m "feat(audio): add learning audio service"
```

---

### Task 3: Bootstrap audio only in learner layouts and consume server events

**Files:**
- Modify: `resources/js/app.js:1-18,80-90,140-147,343`
- Modify: `resources/views/layouts/learner-app.blade.php`
- Modify: `resources/views/layouts/learner-fullscreen.blade.php:80`
- Modify: `tests/JavaScript/learning-audio.test.mjs`
- Modify: `tests/Feature/Learner/LearnerGamificationDashboardDynamicViewTest.php`
- Modify: `tests/Feature/Learner/LessonPageTest.php`

**Interfaces:**
- Consumes: `data-learning-audio` and optional `data-learning-audio-event` on learner `<body>`.
- Produces: One shared `window.learningAudio` / Alpine store and one attempted destination-page event.

- [ ] **Step 1: Add failing page-bootstrap and rendered-layout assertions**

Extend the service test to prove that `initializeLearningAudioPage`:

- does nothing without `data-learning-audio`;
- initializes once with the marker;
- calls `play('complete')` once when `data-learning-audio-event="complete"` exists;
- ignores an unknown event key.

Add a dashboard test method using the class's existing learner fixture and assert:

```php
$response->assertOk()
    ->assertSee('data-learning-audio', false);
```

Add equivalent assertions to the existing successful lesson-view test. Use `withSession(['learning_audio_event' => 'complete'])` in one rendered request and assert:

```php
$response->assertSee('data-learning-audio-event="complete"', false);
```

- [ ] **Step 2: Run focused tests and verify failure**

```powershell
node --test tests/JavaScript/learning-audio.test.mjs
php artisan test tests/Feature/Learner/LearnerGamificationDashboardDynamicViewTest.php tests/Feature/Learner/LessonPageTest.php
```

Expected: FAIL because no layout marker or bootstrap registration exists.

- [ ] **Step 3: Register the shared singleton before Alpine starts**

In `resources/js/app.js`, import:

```javascript
import { initializeLearningAudioPage, learningAudio } from './learning-audio';
```

Before `Alpine.start()`:

```javascript
window.learningAudio = learningAudio;
Alpine.store('learningAudio', learningAudio);
initializeLearningAudioPage(learningAudio);
```

Do not initialize it from non-learner markup; the helper's body marker is the boundary.

- [ ] **Step 4: Publish a validated event on both learner body elements**

Immediately before each layout's `<body>`, normalize the internal session value:

```blade
@php
    $learningAudioEvent = in_array(session('learning_audio_event'), ['selection', 'correct', 'incorrect', 'success', 'complete'], true)
        ? session('learning_audio_event')
        : null;
@endphp
```

Add to each existing body tag:

```blade
data-learning-audio
@if($learningAudioEvent) data-learning-audio-event="{{ $learningAudioEvent }}" @endif
```

Do not add a polling script and do not wait for sound before rendering.

- [ ] **Step 5: Run focused tests and verify pass**

```powershell
node --test tests/JavaScript/learning-audio.test.mjs
php artisan test tests/Feature/Learner/LearnerGamificationDashboardDynamicViewTest.php tests/Feature/Learner/LessonPageTest.php
```

- [ ] **Step 6: Commit learner bootstrap wiring**

```powershell
git add resources/js/app.js resources/views/layouts/learner-app.blade.php resources/views/layouts/learner-fullscreen.blade.php tests/JavaScript/learning-audio.test.mjs tests/Feature/Learner/LearnerGamificationDashboardDynamicViewTest.php tests/Feature/Learner/LessonPageTest.php
git commit -m "feat(audio): bootstrap learner feedback audio"
```

---

### Task 4: Add synchronized accessible settings and quick toggles

**Files:**
- Modify: `resources/views/learner/partials/edit-profile-modal.blade.php:70-90,432+`
- Modify: `resources/views/layouts/learner-header.blade.php:89-121`
- Modify: `resources/views/layouts/learner-fullscreen.blade.php:132-160`
- Modify: `tests/Feature/Learner/LearnerGamificationDashboardDynamicViewTest.php`
- Modify: `tests/Feature/Learner/LessonPageTest.php`

**Interfaces:**
- Consumes: Reactive `Alpine.store('learningAudio')` state.
- Produces: One Sound Effects tab, two quick toggles, one volume slider, and one Test Sound action using the same service object.

- [ ] **Step 1: Write failing rendering assertions**

On the dashboard response assert these stable markers/text:

```php
$response->assertSee('data-learning-audio-settings', false)
    ->assertSee('data-learning-audio-toggle', false)
    ->assertSee('Sound Effects')
    ->assertSee('Test Sound')
    ->assertSee('role="switch"', false)
    ->assertSee('type="range"', false);
```

On the fullscreen lesson response assert `data-learning-audio-toggle` is present.

- [ ] **Step 2: Run the focused rendering tests and verify failure**

```powershell
php artisan test tests/Feature/Learner/LearnerGamificationDashboardDynamicViewTest.php tests/Feature/Learner/LessonPageTest.php
```

- [ ] **Step 3: Add the Sound Effects tab to the active dashboard modal**

Add `['key' => 'sound', 'label' => 'Sound Effects']` to the existing tab list. Add a panel with `x-show="activeTab === 'sound'"` and `data-learning-audio-settings` containing:

```blade
<button
    type="button"
    role="switch"
    aria-label="Toggle sound effects"
    :aria-checked="$store.learningAudio.enabled.toString()"
    @click="$store.learningAudio.setEnabled(!$store.learningAudio.enabled)"
>
    <span x-text="$store.learningAudio.enabled ? 'ON' : 'OFF'"></span>
</button>

<input
    type="range"
    min="0"
    max="100"
    step="1"
    aria-label="Sound effects volume"
    :value="Math.round($store.learningAudio.volume * 100)"
    @input="$store.learningAudio.setVolume(Number($event.target.value) / 100)"
>
<output x-text="`${Math.round($store.learningAudio.volume * 100)}%`"></output>

<button
    type="button"
    :disabled="!$store.learningAudio.enabled"
    @click="$store.learningAudio.unlock(); $store.learningAudio.play('correct')"
>
    Test Sound
</button>
```

Use the modal's existing purple/pink design tokens, visible focus styles, `min-h-11` controls, and explanatory text that preferences apply only to this browser/device. These are not form fields and must have no `name` attribute or backend submission.

- [ ] **Step 4: Add the same quick toggle to both learner top bars**

Place one button beside the existing theme toggle in each header:

```blade
<button
    type="button"
    data-learning-audio-toggle
    @click="$store.learningAudio.setEnabled(!$store.learningAudio.enabled)"
    :aria-pressed="$store.learningAudio.enabled.toString()"
    :aria-label="$store.learningAudio.enabled ? 'Mute sound effects' : 'Enable sound effects'"
    :title="$store.learningAudio.enabled ? 'Mute sound effects' : 'Enable sound effects'"
>
    <svg x-show="$store.learningAudio.enabled" x-cloak class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M11 5 6 9H3v6h3l5 4V5Zm4.5 4.5a3.5 3.5 0 0 1 0 5m2.5-7.5a7 7 0 0 1 0 10"/>
    </svg>
    <svg x-show="!$store.learningAudio.enabled" x-cloak class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M11 5 6 9H3v6h3l5 4V5Zm5 4 5 5m0-5-5 5"/>
    </svg>
</button>
```

Use inline SVGs, not emoji. Keep the standard control at `w-10 h-10`; keep the fullscreen control at least `w-10 h-10` and visible on mobile. Both buttons read and mutate the same store—no local `x-data` preference copy.

- [ ] **Step 5: Run rendering and service tests**

```powershell
php artisan test tests/Feature/Learner/LearnerGamificationDashboardDynamicViewTest.php tests/Feature/Learner/LessonPageTest.php
node --test tests/JavaScript/learning-audio.test.mjs
```

Expected: PASS. The rendered controls have labels, visible state, keyboard-native buttons/range input, and no database fields.

- [ ] **Step 6: Commit the learner controls**

```powershell
git add resources/views/learner/partials/edit-profile-modal.blade.php resources/views/layouts/learner-header.blade.php resources/views/layouts/learner-fullscreen.blade.php tests/Feature/Learner/LearnerGamificationDashboardDynamicViewTest.php tests/Feature/Learner/LessonPageTest.php
git commit -m "feat(audio): add learner sound controls"
```

---

### Task 5: Integrate objectively graded checkpoints and neutral Perspective Feedback

**Files:**
- Modify: `resources/js/interactive-checkpoint.js:16-114`
- Modify: `resources/views/learner/lessons/partials/interactive-checkpoint.blade.php:27-160`
- Modify: `tests/JavaScript/interactive-checkpoint.test.mjs`

**Interfaces:**
- Consumes: Existing checkpoint response fields `status` and `is_correct`.
- Produces: One result sound after state assignment, plus intentional choice sounds; it does not evaluate answers.

- [ ] **Step 1: Write failing checkpoint sound tests**

Pass an injected spy as `config.audio` and add tests asserting:

```text
response status=correct   -> ['correct']
response status=incorrect -> ['incorrect']
response status=completed for perspective_feedback -> ['success']
response status=skipped   -> []
request error             -> []
restored initial state    -> []
retry                     -> []
```

Also assert a completed written Perspective Feedback response never calls `correct` or `incorrect`.

- [ ] **Step 2: Run the focused test and verify failure**

```powershell
node --test tests/JavaScript/interactive-checkpoint.test.mjs
```

- [ ] **Step 3: React to the existing authoritative response**

At factory creation:

```javascript
const audio = config.audio ?? globalThis.learningAudio;
```

After `submit()` assigns `state`, `isCorrect`, result, feedback, and explanation, map only the returned status:

```javascript
const soundKey = data.status === 'correct'
    ? 'correct'
    : data.status === 'incorrect'
        ? 'incorrect'
        : data.status === 'completed'
            ? 'success'
            : null;

if (soundKey) audio?.play?.(soundKey);
```

Keep this after the response succeeds and before/after `claimForward()` without awaiting it. Do not add audio to `skip()`, `retry()`, `continueLearning()`, or initial-state restoration.

- [ ] **Step 4: Add intentional checkpoint selection calls in Blade**

Call `$store.learningAudio.play('selection')` from:

- Perspective pathway radio `@change` handlers.
- Guided perspective option radio `@change`.
- Multiple Choice and True/False radio `@change`.
- Multiple Select checkbox `@change`.
- A word-bank button only when it actually fills a blank.
- A filled blank only when it actually removes a word.

Keep Identification, Fill in the Blanks text inputs, and own-perspective textarea typing silent. The automatic hidden `x-init="choosePerspectivePathway('guided')"` must not play a sound.

For word-bank guards, use the existing state before calling the mutation:

```blade
@click="if (!wordBank.isUsed(wordIndex) && wordBank.selectedIndices.includes(null)) { wordBank.selectWord(wordIndex); $store.learningAudio.play('selection') }"
```

and play removal only when the selected blank currently has a value.

- [ ] **Step 5: Run checkpoint tests**

```powershell
node --test tests/JavaScript/interactive-checkpoint.test.mjs tests/JavaScript/word-bank.test.mjs
php artisan test tests/Feature/Learner/InteractiveCheckpointQuizRegressionTest.php tests/Feature/Learner/ModuleInteractionCompletionTest.php
```

Expected: PASS with unchanged checkpoint grading, progression, and Perspective Feedback neutrality.

- [ ] **Step 6: Commit checkpoint integration**

```powershell
git add resources/js/interactive-checkpoint.js resources/views/learner/lessons/partials/interactive-checkpoint.blade.php tests/JavaScript/interactive-checkpoint.test.mjs
git commit -m "feat(audio): add checkpoint feedback sounds"
```

---

### Task 6: Emit one authoritative result sound from the activity coordinator

**Files:**
- Modify: `resources/js/interactive-activity.js:9-97`
- Modify: `tests/JavaScript/interactive-activity.test.mjs`

**Interfaces:**
- Consumes: Existing `interactive-activity-result` detail fields `data.is_complete` and `data.is_correct`.
- Produces: Exactly one `success`, `correct`, or `incorrect` request per accepted activity result.

- [ ] **Step 1: Write failing coordinator tests**

Inject `config.audio` and test:

```javascript
{ is_complete: true, is_correct: true }  // success only
{ is_complete: false, is_correct: true } // correct only
{ is_complete: false, is_correct: false }// incorrect only
{}                                       // silent
```

Also prove an event for another `activityId`, `handleActivityError`, `handleActivityRecovered`, `handleActivityRetry`, skip, resume, and practice setup do not emit result audio.

- [ ] **Step 2: Run the test and verify failure**

```powershell
node --test tests/JavaScript/interactive-activity.test.mjs
```

- [ ] **Step 3: Add result mapping after the coordinator accepts the event**

At factory creation:

```javascript
const audio = config.audio ?? globalThis.learningAudio;
```

At the end of `handleActivityResult`, after current status/explanation/feedback assignment:

```javascript
const soundKey = detail.data?.is_complete === true
    ? 'success'
    : detail.data?.is_correct === true
        ? 'correct'
        : detail.data?.is_correct === false
            ? 'incorrect'
            : null;

if (soundKey) audio?.play?.(soundKey);
```

Do not add equivalent result playback inside Matching or Sequencing; their existing `publishResult()` dispatch remains the single route to this coordinator.

- [ ] **Step 4: Run activity tests**

```powershell
node --test tests/JavaScript/interactive-activity.test.mjs tests/JavaScript/matching-activity.test.mjs tests/JavaScript/sequencing-activity.test.mjs
```

- [ ] **Step 5: Commit coordinator integration**

```powershell
git add resources/js/interactive-activity.js tests/JavaScript/interactive-activity.test.mjs
git commit -m "feat(audio): map activity result sounds"
```

---

### Task 7: Add meaningful Matching and Sequencing selection sounds

**Files:**
- Modify: `resources/js/matching-activity.js:35-381`
- Modify: `resources/js/sequencing-activity.js:13-205`
- Modify: `tests/JavaScript/matching-activity.test.mjs`
- Modify: `tests/JavaScript/sequencing-activity.test.mjs`

**Interfaces:**
- Consumes: Existing valid endpoint/connection and changed-order decisions.
- Produces: `selection` only for state-changing pointer, touch, or keyboard interactions.

- [ ] **Step 1: Write failing Matching selection tests**

Inject `config.audio` and assert:

- `startConnection()` on an available endpoint emits one `selection`.
- `finishConnection()` with a valid opposite endpoint emits one additional `selection` after the pair is created.
- Keyboard `Enter`/Space follows the same methods and behavior.
- Locked/unavailable endpoints, invalid same-side targets, Escape/cancel, hover/move, rejected removal, retry, and `checkAnswer()` itself do not add selection sounds.
- Result sounds remain absent from the child factory and are handled by Task 6.

- [ ] **Step 2: Write failing Sequencing selection tests**

Inject `config.audio` and assert:

- `move(index, delta)` emits once only when the committed array order changes.
- Boundary/no-op movement emits nothing.
- `dropPointerDrag()` emits once only when `changed === true`.
- Pointer drag start/move/cancel emits nothing.
- Keyboard reorder emits on the actual committed move/drop, not every key or drag target preview.
- `checkAnswer()` itself emits no child result sound.

- [ ] **Step 3: Run both tests and verify failure**

```powershell
node --test tests/JavaScript/matching-activity.test.mjs tests/JavaScript/sequencing-activity.test.mjs
```

- [ ] **Step 4: Integrate Matching at existing validity boundaries**

At factory creation:

```javascript
const audio = config.audio ?? globalThis.learningAudio;
```

In `startConnection()`, call `audio?.play?.('selection')` only after availability is confirmed and `activeEndpoint` is assigned. In `finishConnection()`, call it only after a valid proposal is pushed to `matchedPairs`. Do not call it from pointer-move, hover, cancel, validation, or response handling.

- [ ] **Step 5: Integrate Sequencing only when order changes**

Refactor `move()` without changing ordering rules:

```javascript
move(index, delta) {
    if (this.isLocked()) return this;

    const next = moveItem(this.order, index, delta);
    const changed = JSON.stringify(next) !== JSON.stringify(this.order);
    if (!changed) return this;

    this.positionResults = [];
    this.feedback = '';
    this.order = next;
    this.candidateOrder = [...next];
    audio?.play?.('selection');
    this.scheduleSave();
    return this;
},
```

In `dropPointerDrag()`, call `selection` inside the existing `if (changed)` branch alongside `scheduleSave()`. Keep drag start, target preview, and auto-scroll silent.

- [ ] **Step 6: Run focused and shared activity tests**

```powershell
node --test tests/JavaScript/matching-activity.test.mjs tests/JavaScript/sequencing-activity.test.mjs tests/JavaScript/interactive-activity.test.mjs tests/JavaScript/pointer-reorder.test.mjs
```

- [ ] **Step 7: Commit interaction selection integration**

```powershell
git add resources/js/matching-activity.js resources/js/sequencing-activity.js tests/JavaScript/matching-activity.test.mjs tests/JavaScript/sequencing-activity.test.mjs
git commit -m "feat(audio): add activity selection sounds"
```

---

### Task 8: Add formal quiz selections and post-persistence success

**Files:**
- Modify: `resources/views/quizzes/take.blade.php:179-329,494+`
- Modify: `app/Http/Controllers/Learner/QuizController.php:103-289`
- Modify: `tests/Feature/Learner/QuizProgressionUxTest.php`
- Modify: `tests/Feature/Learner/LearnerFinalQuizCompletionFlowTest.php`
- Modify: `tests/Feature/Learner/LearnerQuizAttemptLimitTest.php` if its failed-attempt fixture is reused

**Interfaces:**
- Consumes: Existing quiz choice changes and successfully persisted `QuizAttempt` flow.
- Produces: Choice `selection` sounds and one redirect-delivered `success` after successful submission.

- [ ] **Step 1: Write failing quiz rendering and completion tests**

Add stable `data-learning-audio-selection` markers to the planned choice controls, then first write a quiz-start rendering assertion that the response contains the marker but text inputs do not carry it.

In `LearnerFinalQuizCompletionFlowTest`, extend the passing final-quiz submission assertion:

```php
->assertSessionHas('learning_audio_event', 'success');
```

Add or extend a failed-but-persisted quiz attempt test and assert the same `success` event. Extend an attempt-limit early redirect (where no new `QuizAttempt` is created) with:

```php
->assertSessionMissing('learning_audio_event');
```

- [ ] **Step 2: Run focused quiz tests and verify failure**

```powershell
php artisan test tests/Feature/Learner/QuizProgressionUxTest.php tests/Feature/Learner/LearnerFinalQuizCompletionFlowTest.php tests/Feature/Learner/LearnerQuizAttemptLimitTest.php
```

- [ ] **Step 3: Add only intentional quiz choice audio**

In `take.blade.php`, add `$store.learningAudio.play('selection')` to:

- Multiple Select checkbox `@change`, after `updateMultiSelect`.
- Multiple Choice and True/False radio `@change`, after `markAnswered`.
- Word-bank selection only when an unused word can fill a blank.
- Word-bank removal only when that blank is filled.

Add `data-learning-audio-selection` to those controls for rendering tests. Do not add sound to Identification or Fill in the Blanks text `@input`, question navigation, review, submit click, timer, or auto-submit.

- [ ] **Step 4: Flash success only after the existing submission work succeeds**

In `QuizController::submit()`, do not flash before `DB::commit()`. After the commit and the existing optional module-completion helper have returned successfully, set:

```php
session()->flash('learning_audio_event', 'success');
```

Place it before the three successful redirect branches so final-module completion, lesson-quiz return, and result-page return all inherit it. Keep all pre-attempt redirects, validation failures, transaction exceptions, and attempt-limit rejections silent. Do not branch on `$passed`; both passed and failed persisted attempts use `success`.

- [ ] **Step 5: Run quiz regression tests**

```powershell
php artisan test tests/Feature/Learner/QuizProgressionUxTest.php tests/Feature/Learner/LearnerFinalQuizCompletionFlowTest.php tests/Feature/Learner/LearnerQuizAttemptLimitTest.php tests/Feature/Learner/LearnerQuizTimerAutoSubmitTest.php tests/Feature/Learner/InteractiveCheckpointQuizRegressionTest.php tests/Feature/Learner/InteractiveActivityQuizRegressionTest.php
```

Expected: PASS with unchanged score, pass/fail, shield, limit, timer, and redirect behavior.

- [ ] **Step 6: Commit quiz integration**

```powershell
git add resources/views/quizzes/take.blade.php app/Http/Controllers/Learner/QuizController.php tests/Feature/Learner/QuizProgressionUxTest.php tests/Feature/Learner/LearnerFinalQuizCompletionFlowTest.php tests/Feature/Learner/LearnerQuizAttemptLimitTest.php
git commit -m "feat(audio): add quiz feedback events"
```

If `LearnerQuizAttemptLimitTest.php` did not need changes, omit it from `git add` rather than touching it mechanically.

---

### Task 9: Emit transition-only topic, lesson, and module completion events

**Files:**
- Modify: `app/Http/Controllers/Learner/LessonController.php:427-553`
- Modify: `app/Http/Controllers/Learner/ModuleController.php:912-965`
- Create: `tests/Feature/Learner/LearningAudioCompletionTest.php`
- Modify: `tests/Feature/Learner/LearnerFinalQuizCompletionFlowTest.php`

**Interfaces:**
- Consumes: Existing successful progress records and module enrollment transition.
- Produces: `complete` for a new topic/lesson and `success` for a newly completed module, never for a revisit or error.

- [ ] **Step 1: Write failing transition tests**

Create `LearningAudioCompletionTest` with existing learner/module/lesson/topic factories and isolated `RefreshDatabase`. Cover:

```php
public function test_new_topic_completion_flashes_complete_once(): void {}
public function test_repeated_topic_completion_does_not_flash_complete(): void {}
public function test_topic_that_auto_completes_lesson_still_flashes_one_complete(): void {}
public function test_new_manual_lesson_completion_flashes_complete(): void {}
public function test_revisited_lesson_completion_does_not_flash_complete(): void {}
public function test_failed_or_unauthorized_completion_does_not_flash_audio(): void {}
```

Use the named routes `learner.topics.complete` and `learner.lessons.complete`. Assertions are:

```php
->assertSessionHas('learning_audio_event', 'complete');
// or
->assertSessionMissing('learning_audio_event');
```

In `LearnerFinalQuizCompletionFlowTest`, add a direct module-completion transition setup with a passed final attempt and all existing prerequisites, then assert the first `GET learner.modules.completion` has `success` and a second GET does not.

- [ ] **Step 2: Run tests and verify failure**

```powershell
php artisan test tests/Feature/Learner/LearningAudioCompletionTest.php tests/Feature/Learner/LearnerFinalQuizCompletionFlowTest.php
```

- [ ] **Step 3: Add manual lesson completion audio after creation**

In `LessonController::complete()`, leave the existing already-completed early return unchanged and silent. After `UserProgress::create`, point/streak work, and current point flash succeed, add:

```php
session()->flash('learning_audio_event', 'complete');
```

Then return the existing success response unchanged.

- [ ] **Step 4: Distinguish a new topic transition without changing progression logic**

Before `$topic->markCompleted($user->id)`, read only the pre-existing transition state:

```php
$wasAlreadyCompleted = LessonTopicProgress::query()
    ->where('user_id', $user->id)
    ->where('lesson_topic_id', $topic->id)
    ->where('completed', true)
    ->exists();
```

Keep `markCompleted`, point awards, streak updates, automatic lesson completion, and redirects unchanged. After all existing completion work succeeds and before either redirect:

```php
if (! $wasAlreadyCompleted) {
    session()->flash('learning_audio_event', 'complete');
}
```

This intentionally emits one `complete` even when the same topic transition also auto-completes the lesson.

- [ ] **Step 5: Add same-response module success only on the guarded transition**

In `ModuleController::completion()`:

```php
$newlyCompleted = false;

if ($enrollment->completed_at === null && $this->completionService->isFullyCompleted($user, $module)) {
    // existing enrollment update and point award
    $newlyCompleted = true;
}

if ($newlyCompleted) {
    session()->now('learning_audio_event', 'success');
}
```

Use `now`, not `flash`, because this method returns the completion view in the same request. If `QuizController` already completed the enrollment and redirected here with a flashed `success`, `$newlyCompleted` is false and the existing redirect event remains the single sound.

- [ ] **Step 6: Run completion and progress regressions**

```powershell
php artisan test tests/Feature/Learner/LearningAudioCompletionTest.php tests/Feature/Learner/LearnerFinalQuizCompletionFlowTest.php tests/Feature/Learner/LessonPageTest.php tests/Feature/Learner/ModuleInteractionCompletionTest.php
```

Expected: PASS with unchanged point awards, progress calculations, certificate conditions, and completion rules.

- [ ] **Step 7: Commit learning-progress integration**

```powershell
git add app/Http/Controllers/Learner/LessonController.php app/Http/Controllers/Learner/ModuleController.php tests/Feature/Learner/LearningAudioCompletionTest.php tests/Feature/Learner/LearnerFinalQuizCompletionFlowTest.php
git commit -m "feat(audio): add completion feedback events"
```

---

### Task 10: Complete automated regression, asset QA, and browser verification

**Files:**
- Modify: `docs/audio-feedback-assets.md`
- Create: `docs/superpowers/verification/2026-09-18-learning-feedback-audio-system.md`

**Interfaces:**
- Consumes: The complete feature, local assets, automated tests, and available browser environment.
- Produces: Reproducible verification evidence and an honest platform coverage report.

- [ ] **Step 1: Run the complete JavaScript suite**

```powershell
node --test tests/JavaScript
```

Expected: all JavaScript tests pass, including repeated playback, priority, unlock, checkpoint, Matching, Sequencing, and shared activity tests.

- [ ] **Step 2: Run the focused Laravel learner suite**

```powershell
php artisan test tests/Feature/Learner/LearningAudioCompletionTest.php tests/Feature/Learner/LearnerFinalQuizCompletionFlowTest.php tests/Feature/Learner/LearnerQuizAttemptLimitTest.php tests/Feature/Learner/LearnerQuizTimerAutoSubmitTest.php tests/Feature/Learner/QuizProgressionUxTest.php tests/Feature/Learner/InteractiveCheckpointQuizRegressionTest.php tests/Feature/Learner/InteractiveActivityQuizRegressionTest.php tests/Feature/Learner/ModuleInteractionCompletionTest.php tests/Feature/Learner/LessonPageTest.php tests/Feature/Learner/LearnerGamificationDashboardDynamicViewTest.php tests/Feature/Learner/LearnerProfileEditRedirectTest.php
```

Expected: all pass against the isolated testing database. Do not run a database reset command.

- [ ] **Step 3: Verify a production Vite build without touching the dirty tracked build directory**

Use a dedicated new output directory and validate it before cleanup:

```powershell
$buildCheck = Join-Path (Get-Location) 'storage\framework\learning-audio-build-check'
if (Test-Path -LiteralPath $buildCheck) { throw "Refusing to overwrite existing path: $buildCheck" }
npm exec -- vite build --outDir $buildCheck
$resolvedBuildCheck = (Resolve-Path -LiteralPath $buildCheck).Path
$expectedBuildCheck = [System.IO.Path]::GetFullPath($buildCheck)
if ($resolvedBuildCheck -ne $expectedBuildCheck) { throw 'Unexpected build output path.' }
Remove-Item -LiteralPath $resolvedBuildCheck -Recurse -Force
```

Expected: build succeeds. Do not run the normal output command over pre-existing `public/build/` changes during this task.

- [ ] **Step 4: Verify centralization statically**

Run:

```powershell
rg -n "new Howl|audio/feedback/.*\.mp3" resources/js resources/views
rg -n "correct\.mp3|incorrect\.mp3|selection\.mp3|success\.mp3|complete\.mp3" resources/js resources/views
```

Expected: feedback paths and `new Howl` occur only in `resources/js/learning-audio.js`. No Blade component or other JavaScript component instantiates Howler or raw feedback audio.

- [ ] **Step 5: Perform in-app asset preparation review**

At 70 percent application volume, play all five assets individually and then these sequences: `selection -> correct`, `selection -> incorrect`, and `correct -> success`. Verify:

- no obvious leading/trailing silence;
- no clicks or pops;
- each file is short and appropriate for frequent learning feedback;
- `incorrect` is not substantially louder or harsher than `correct`;
- `complete` is not excessively loud;
- the five files have acceptable perceived loudness as a set.

If a file fails, stop and ask the project owner to re-export that specific MP3; do not install an unapproved audio editor or fabricate a normalization claim. After the set passes, append to each provenance record: `QA: Reviewed in application at the shared 70% setting; passed silence, click/pop, duration, and relative-loudness review on 2026-09-18.`

- [ ] **Step 6: Run available Chromium desktop and mobile checks**

Use the in-app browser against a signed-in learner session at desktop (approximately 1440x900) and mobile (approximately 390x844). Record pass/fail evidence for:

1. Default ON and 70%; refresh persistence.
2. Settings toggle and both quick toggles stay synchronized.
3. Volume 0, intermediate volume, and 100%; volume zero does not switch OFF.
4. Test Sound; first-gesture unlock; refresh; background-tab return.
5. All five sounds and a deliberately missing asset without learner-flow breakage. Simulate the missing file with browser request interception returning 404; never rename or delete the retained asset. If interception is unavailable, cite the automated `onloaderror` test and mark the browser-specific check not run.
6. Quiz selection and post-submit success on passed and failed persisted attempts.
7. Checkpoint correct, incorrect, skip, retry, Perspective guided, and own written response.
8. Matching pointer/keyboard source and connection; correct/incorrect/completion.
9. Sequencing pointer/keyboard placement; correct/incorrect/completion.
10. New topic, automatic lesson, manual lesson, quiz, and module completion; revisit stays silent.
11. Keyboard access, focus indicators, switch/range labels, touch targets, and visual feedback with sound disabled.
12. Rapid selection/result transitions do not overlap unpleasantly.

- [ ] **Step 7: Record unavailable real-device/browser checks honestly**

In the verification document, include a matrix for Chrome desktop, Edge desktop, Android Chrome, iOS Safari, desktop viewport, and mobile viewport. Mark only environments actually exercised as `Passed`. Mark unavailable environments as `Not run - unavailable in current environment`, and include the exact manual checklist needed for a later device run. Do not report emulation as a real Android or iOS device test.

- [ ] **Step 8: Review final diff and worktree scope**

Run:

```powershell
git diff --check
git status --short
git diff -- resources/js resources/views app/Http/Controllers/Learner tests/JavaScript tests/Feature/Learner docs/audio-feedback-assets.md docs/superpowers/verification package.json package-lock.json
```

Confirm no migration, model, scoring, evaluation, permission, enrollment, or generated-build change entered the feature. Preserve unrelated dirty files.

- [ ] **Step 9: Commit verification records only**

```powershell
git add docs/audio-feedback-assets.md docs/superpowers/verification/2026-09-18-learning-feedback-audio-system.md
git commit -m "docs(audio): record feedback verification"
```

---

## Final Acceptance Checklist

- [ ] Howler is npm-managed and dynamically loaded only for learner pages when enabled.
- [ ] Exactly five local MP3 files exist with exact semantic filenames.
- [ ] Relaxed, accurate Mixkit provenance and preparation history are documented.
- [ ] One centralized service owns Howler, paths, preferences, priority, preload, unlock, and errors.
- [ ] Enabled and volume defaults/persistence are safe under missing, malformed, or inaccessible storage.
- [ ] Settings, standard header, and fullscreen header share one reactive state.
- [ ] Test Sound uses `correct`; no sixth asset exists.
- [ ] Autoplay refusal defers at most one highest-priority event for one retry.
- [ ] Audio failure never blocks or changes learning state.
- [ ] Checkpoints, Perspective Feedback, Matching, Sequencing, quizzes, topics, lessons, and modules follow the approved semantic mapping.
- [ ] Own-perspective text is never classified correct/incorrect.
- [ ] Result sounds are not doubled with completion sounds for one transition.
- [ ] Completion sounds occur only after new successful transitions, never on click or revisit.
- [ ] Visual feedback and keyboard/touch behavior remain intact with sound disabled.
- [ ] Automated tests and temporary-output Vite build pass.
- [ ] Browser/device coverage is recorded without overstating unavailable platforms.
- [ ] No database or destructive operation was introduced.
- [ ] Unrelated worktree changes remain untouched.
