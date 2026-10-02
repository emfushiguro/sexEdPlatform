# Task 8: Scheduled educational event notices

## Scope

- Added `seminars:send-notices`, scheduled every minute with overlap protection.
- Due published events receive one reminder for each start time. Due published or completed external events receive one link availability notice for each explicit release time while their protected link remains valid and unexpired.
- Both notices use the Task 7 recipient set: active eligible registrants and accepted speakers, deduplicated by user ID. Organizer copying remains specific to the Task 7 admin change notice.
- Conditional database updates claim schedule timestamp markers. The claim includes the current schedule value and event status, so stale scheduler reads cannot mark a changed slot. Failed dispatch logs a warning and clears only the still-current claimed marker for a later retry.
- Reminder mail and database headings now say Educational Event. Both notification payloads point to the protected event page and omit the raw external URL.

## TDD evidence

- RED: `php vendor/bin/phpunit --do-not-cache-result tests/Feature/Seminars/SeminarScheduledNoticesTest.php` exited 1 with four expected `CommandNotFoundException` errors for `seminars:send-notices` (`Tests: 4, Assertions: 1, Errors: 4`).
- The first GREEN attempt exposed a test expectation error: a published event in the reminder window correctly sent reminders while the test expected no notifications of any kind. The assertion was narrowed to link notices.
- Final focused suites: `php vendor/bin/phpunit --do-not-cache-result tests/Feature/Seminars/SeminarScheduledNoticesTest.php tests/Feature/Connectors/SeminarDeliveryManagementTest.php` passed with `OK (12 tests, 105 assertions)`.

## Checks

- `phpunit.xml` points to the isolated MySQL database `cc_db_test`; no development database migration or reset was run.
- `php -l` passed for all new and modified PHP classes.
- Scoped `php vendor/bin/pint --test` passed on all five changed PHP files.
- `php artisan schedule:list` listed `seminars:send-notices` at `* * * * *`.
- Scoped `git diff --check` passed. Unrelated pre-existing working tree changes were not staged.

## Delivery boundary

The marker is per event and schedule timestamp. If a dispatch fails after some recipients have already been queued, retry can queue those recipients again. A per-recipient outbox would be needed for stronger guarantees across partial dispatch failure; it is outside this task's approved marker design.

## Review follow-up: release edited after claim

- Regression: a one-shot database query listener invokes the Task 7 delivery update immediately after the scheduler claims release A. It moves the release to future B and resets the marker before the scheduler can dispatch.
- RED: the focused regression sent an `available` notification for future B (`Tests: 1, Assertions: 3, Failures: 1`).
- Fix: after claiming, the command reloads the row with `lockForUpdate` and checks the claimed schedule and marker, current status and delivery format, due window, and link expiry. It dispatches while holding that row lock, serializing with the Task 7 delivery update. A stale claim clears only its old marker when still present. Dispatch failures keep the same conditional marker cleanup.
- GREEN: the focused regression passed (`OK (1 test, 5 assertions)`). The scheduled notice and Task 7 delivery suites passed together (`OK (13 tests, 110 assertions)`).

## Review follow-up: atomic claim and dispatch

- A timestamp comparison could not distinguish an old release A claim from A being restored after A-to-B-to-A edits. The earlier claim update occurred before the row-lock transaction.
- Regression: a query listener records the database transaction level at the availability-marker write. RED found it at level 1, equal to PHPUnit's outer test transaction (`Tests: 1, Assertions: 4, Failures: 1`), proving the claim was outside the dispatch transaction.
- Fix: the command locks the event row first, verifies the current schedule, marker, status, due time, and link validity, then writes the marker and dispatches within the same transaction. Dispatch failure is logged and the marker is cleared under that lock before commit. The existing post-claim release-change regression remains covered by a recheck after the marker write.
- GREEN: the focused transaction regression passed (`OK (1 test, 4 assertions)`). Scheduled notice and Task 7 delivery suites passed together (`OK (14 tests, 114 assertions)`).
