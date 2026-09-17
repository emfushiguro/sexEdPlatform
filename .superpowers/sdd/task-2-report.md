# Task 2 TDD Report: Make video AJAX updates safe and redirectable

## Scope

- Modified `app/Http/Controllers/Instructor/TopicController.php`.
- Modified `tests/Feature/Instructor/VideoUploadTest.php`.
- Did not modify, stage, or commit `docs/FRESH_SERVER_SETUP.md`.
- Used the isolated `cc_db_test` test database only; no development database was reset, wiped, seeded, or otherwise changed.

## RED evidence

Added the three prescribed feature tests before any controller edits:

1. AJAX video creation returns its lesson redirect (existing behavior characterization).
2. AJAX video replacement returns its lesson redirect and removes the old file.
3. Failed replacement storage leaves the existing file/path untouched.

First required filtered invocation:

```powershell
php vendor/bin/phpunit tests/Feature/Instructor/VideoUploadTest.php --filter="video_create_ajax|video_update_ajax|failed_replacement" --do-not-cache-result
```

The initial run encountered a test-environment bootstrap error because `cc_db_test.migrations` did not yet exist. It also showed the two expected application failures: update returned HTTP 302 rather than HTTP 200 JSON, and failed replacement storage had already invoked deletion.

The exact command was rerun after the isolated schema initialization. Result: `Tests: 3, Assertions: 8, Failures: 2`.

- `test_video_create_ajax_response_contains_the_lesson_redirect`: passed.
- `test_video_update_ajax_response_contains_the_lesson_redirect`: failed: expected 200, received 302.
- `test_failed_replacement_storage_does_not_delete_the_existing_video`: failed: deletion flag was `true`.

This confirms the requested RED characterization without production-code changes.

## GREEN implementation

In `TopicController::update()`:

- Initialized `$oldVideoPathToDelete` immediately before video handling.
- Stored a replacement upload before recording the old path for deletion.
- Deferred removal of a local old path for both upload and URL video replacements.
- Deleted the deferred old path only immediately after `$topic->update($validated)` succeeds.
- Returned `{success: true, message: "Topic updated successfully!", redirect: ...}` for JSON/AJAX updates immediately before the existing redirect.
- Kept validation, public disk usage, and the non-AJAX redirect unchanged.

## Verification

Focused suite command:

```powershell
php vendor/bin/phpunit tests/Feature/Instructor/VideoUploadTest.php --do-not-cache-result
```

Result: `OK (6 tests, 24 assertions)` in 44.751 seconds.

`git diff --check` produced no whitespace errors.

## Self-review

- The old file is not scheduled for deletion until after the new upload is stored.
- If `store()` throws, execution stops before database update or deletion; the old database path and file remain intact.
- If `$topic->update($validated)` throws, deletion is also skipped.
- AJAX detection uses both `wantsJson()` and `ajax()`, matching the create method.
- No migrations, dependencies, logging changes, refactors, database reset/reseed, or unrelated files were introduced.

## Concerns

The initial focused RED command exposed an existing isolated-test initialization issue (`cc_db_test.migrations` absent). Rerunning the same non-cache PHPUnit command initialized the isolated schema and yielded the intended RED results. The final six-test focused suite passes.

## Fix review

The review fix was verified against the persistence-veto regression: `TopicController::update()` now captures the boolean result of `$topic->update($validated)` and deletes the deferred old local video only when that result is truthy. The regression test stores the replacement first, vetoes model persistence through `LessonTopic::saving`, and confirms the original database path and file remain while both files exist on the fake public disk.

The create AJAX assertion now covers `success`, `message` (`Topic created successfully!`), and the lesson redirect. The update AJAX assertion covers `success`, `message` (`Topic updated successfully!`), and the lesson redirect, plus replacement of the old file. The focused scope was inspected and remains limited to the controller's update behavior and `VideoUploadTest`; `docs/FRESH_SERVER_SETUP.md` was not touched, staged, or committed.

Exact verification commands and results:

```powershell
php vendor/bin/phpunit tests/Feature/Instructor/VideoUploadTest.php --do-not-cache-result
```

Result: `OK (7 tests, 31 assertions)` in `44.990` seconds.

```powershell
php -l app/Http/Controllers/Instructor/TopicController.php
php -l tests/Feature/Instructor/VideoUploadTest.php
git diff --check
```

Results: both PHP files reported no syntax errors; `git diff --check` reported no whitespace errors.
