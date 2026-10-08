# Interactive Activity Item Images Verification

**Date:** 2026-09-20
**Branch:** `enhancement`

## Automated checks

- `node --test tests/JavaScript/interactive-activity-authoring.test.mjs tests/JavaScript/matching-activity.test.mjs tests/JavaScript/sequencing-activity.test.mjs tests/JavaScript/interactive-activity.test.mjs tests/JavaScript/pointer-reorder.test.mjs` — exit 0; **82 tests passed**.
- `php vendor/bin/phpunit --do-not-cache-result tests/Unit/Services/Learning/InteractiveActivityHandlerTest.php tests/Feature/Instructor/InteractiveActivityAuthoringTest.php tests/Feature/Instructor/InteractiveActivityImageLibraryTest.php tests/Feature/Instructor/InstructorImageLibraryThemeTest.php tests/Feature/Learner/InteractiveActivityRenderingTest.php tests/Feature/Learner/InteractiveActivityProgressTest.php tests/Feature/Learner/InteractiveActivityProgressIsolationTest.php tests/Feature/Learner/InteractiveActivitySchemaTest.php` — exit 0; **87 tests, 577 assertions**.
- `vendor/bin/pint --test app/Http/Controllers/Instructor/ImageLibraryController.php tests/Feature/Instructor/InteractiveActivityAuthoringTest.php tests/Feature/Instructor/InteractiveActivityImageLibraryTest.php tests/Feature/Learner/InteractiveActivityRenderingTest.php` — exit 0; **4 files pass**.
- `npm.cmd run build` — exit 0 after the sandbox retry; Vite 7.3.0 transformed 97 modules and emitted the production bundle.
- `git diff --check` — exit 0.

## Full regression

`php vendor/bin/phpunit --do-not-cache-result` — exit 1 after **1471 tests and 7279 assertions**.

- 10 errors are caused by the environment missing the GD extension in unrelated Community image tests.
- 7 failures are unrelated auth/parent-page assertions.
- No unrelated production code was changed to mask these failures.

## Authoring QA

Automated coverage verifies:

- text-only, image-only, and mixed Matching and Sequencing items;
- upload and Image Library selection;
- WebP acceptance and scoped library paths;
- replacement, removal, alt-text validation, and text-or-image validation;
- upload failure restoration and pending-submit protection;
- Preview serialization without transient URLs;
- exact-path authorization and referenced-image deletion blocking;
- Create/Edit controls, accessible picker dialog, and responsive media markup.

Browser authoring QA was not run because no browser session was available in this environment.

## Learner QA

Automated coverage verifies:

- accessible image-alt labels for image-only items;
- Matching connector refresh after image load/failure;
- missing-image fallback state;
- Sequencing image labels, failed-media state, and drag-overlay media;
- preserved Matching and Sequencing scoring, Retry, Practice, progress, and ID-only request payloads;
- responsive media classes and unchanged endpoint/drag-handle targets.

Desktop pointer, mobile touch, keyboard-only, delayed-image, resize/orientation, Retry, Continue, fullscreen, and missing-image browser checks remain outstanding because no browser session was connected.

## Database safety

All automated database tests use the isolated `cc_db_test` configuration from `phpunit.xml`. No development database reset, wipe, truncate, drop, recreation, or destructive reseed was executed.

## Outstanding issues

- Browser/device QA remains outstanding.
- Full PHPUnit remains non-green only for the GD and unrelated auth/parent failures listed above.
- Existing unrelated worktree changes and `storage/framework/lsp-b7c5039063be9f4.php` were preserved unstaged.
