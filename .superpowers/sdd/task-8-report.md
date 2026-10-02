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
