# Task 2: Centralize accessible activity feedback

## Implementation

- Added the dependency-free `activity-feedback.js` mapper with the specified idle, evaluation, and lifecycle messages.
- Added parent-level Alpine feedback state, scoped result handling, lifecycle clearing, request-error feedback, and `practice_completed` controls.
- Matching and sequencing now publish scoped evaluation details to the shared shell. Their duplicate local feedback regions were removed so each activity has one central status region and one central request-error alert.
- Learner and Preview instructions render the stored sanitized rich text. Completed and practice-completed explanations render with `x-html` only after the matching status is active; activity authoring sanitizes both fields before storage.

## Files

Created:

- `resources/js/activity-feedback.js`
- `tests/JavaScript/activity-feedback.test.mjs`

Modified:

- `resources/js/interactive-activity.js`
- `resources/js/matching-activity.js`
- `resources/js/sequencing-activity.js`
- `resources/views/learner/lessons/partials/interactive-activities/shell.blade.php`
- `resources/views/learner/lessons/partials/interactive-activities/matching.blade.php`
- `resources/views/learner/lessons/partials/interactive-activities/sequencing.blade.php`
- `tests/JavaScript/interactive-activity.test.mjs`
- `tests/JavaScript/matching-activity.test.mjs`
- `tests/JavaScript/sequencing-activity.test.mjs`
- `tests/Feature/Learner/InteractiveActivityRenderingTest.php`

The child component files are required to publish the scoped result event and eliminate their duplicate live/error regions.

## RED evidence

1. `node --test tests/JavaScript/activity-feedback.test.mjs` failed before the mapper existed with `ERR_MODULE_NOT_FOUND` for `resources/js/activity-feedback.js`.
2. The required combined JavaScript RED run had four intended failures: missing skipped feedback, missing request-error feedback, feedback not clearing, and missing `handleActivityResult`.
3. The PHP rendering RED run completed with 10 passing tests and one failure because the shell did not contain the scoped `interactive-activity-result` listener.
4. Child result-event tests then failed because only lifecycle state was dispatched. The expected `interactive-activity-result` events were absent.

## GREEN evidence

Required JavaScript command:

```text
node --test tests/JavaScript/activity-feedback.test.mjs tests/JavaScript/interactive-activity.test.mjs
7 tests passed, 0 failed.
```

Extended JavaScript coverage:

```text
node --test tests/JavaScript/activity-feedback.test.mjs tests/JavaScript/interactive-activity.test.mjs tests/JavaScript/matching-activity.test.mjs tests/JavaScript/sequencing-activity.test.mjs
20 tests passed, 0 failed.
```

Required PHP command:

```text
php vendor/bin/phpunit --do-not-cache-result tests/Feature/Learner/InteractiveActivityRenderingTest.php --testdox
OK (11 tests, 54 assertions)
```

PHP took about 32 seconds, exceeding the foreground terminal window, so it was captured from a hidden background PHP process. `git diff --check` was clean for all Task 2 code and test files.

## Self-review

- Confirmed all result and lifecycle actions are scoped by `activityId`.
- Confirmed no new dependencies, schema changes, scoring, completion rules, or activity-required behavior were added.
- Confirmed only the common shell owns the feedback status and request-error alert; child controls retain their existing focus and target sizing.
- Confirmed stored instructions and explanations are sanitized by `InteractiveActivityAuthoringService` before their rich-text rendering.
- Preserved all unrelated dirty files, including `resources/views/instructor/topics/create.blade.php` and `public/build` output.

## Concerns

None. The task brief's listed files did not include the two child activity components, but the scoped result event and one-region requirement require these directly coupled changes.

## Commit

`b941252d9791cb12651b41cf3f6e5cc242d1af67 feat: unify interactive activity feedback`
