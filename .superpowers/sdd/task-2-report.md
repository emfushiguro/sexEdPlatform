# Task 2 report: isolate native seminar delivery

## Implementation

- Added `Seminar::isNativeDelivery()` checks before Agora credential validation and to the join-window decision. External webinars return HTTP 403 from token issuance even when Agora secrets are unset.
- Guarded native livestream preparation, start, end, status, connector host access, and participant join. Kept the published webinar requirement for starting a native stream.
- Guarded native attendance join, heartbeat, and leave, plus participant comments and questions. The native attendance service marks newly created rows with `attendance_method = native`.
- Restricted attendance finalization to native events. It still records duration and leave time on native rows while preserving manual `status`, `attended_at`, and `attendance_method`. Join, heartbeat, and leave also preserve an existing manual status.
- Hid Host Livestream and Join Livestream links for external delivery.

## TDD and verification

1. Added an external webinar regression with registered learner and connector owner access, while setting Agora credentials to null. Explicitly marked the existing native fixtures as `event_format = native`.
2. Ran the four focused test files before implementation: **15 tests, 85 assertions, 1 expected failure**. The external webinar participant join route returned 200 instead of the expected 403.
3. Added attendance finalization and interaction regressions before implementation. Both failed as expected: finalization replaced a manual status with `attended`, and an external webinar comment request returned 302 instead of 403.
4. Applied the shared guards and reran the four focused files. One test assertion needed timestamp precision adjustment because persisted database timestamps omit subsecond precision; application behavior had passed the other assertions.
5. Final command: `php vendor/bin/phpunit --do-not-cache-result tests/Feature/Seminars/SeminarLivestreamAccessTest.php tests/Feature/Seminars/SeminarAttendanceTest.php tests/Feature/Seminars/SeminarInteractionTest.php tests/Unit/Services/Seminars/AgoraTokenServiceTest.php` — **17 tests, 115 assertions, 0 failures**. `phpunit.xml` targets isolated `cc_db_test`.
6. `git diff --check` and `php -l` on the six changed PHP application files passed.

## Self-review

- Checked every Task 2 route: learner join/token/attendance, owner livestream page/token/prepare/start/end/status, and participant comment/question creation. The external webinar test confirms 403 and unchanged stream state. Existing native tests still cover token issuance, participant join, stream lifecycle, interactions, and attendance duration.
- Confirmed no development migration, database reset, destructive seeder, or full test suite was run. The unrelated untracked plan remains outside this task's commit.
- Task 2 does not add external access or authoring; those are separate tasks in the approved plan.
