# Task 10: Manual attendance corrections and native decision preservation

## Scope

- Added manager and admin manual attendance actions for active, confirmed seminar registrants. Connector actions enforce `connector.manage_seminars` and event ownership; admin actions enforce the admin role. Walk-ins and registrants from another event are rejected.
- Added a transactional attendance service that locks the registrant and attendance row, records a manual `attended` or `not_present` decision, mirrors `attended_at` to the registrant, and writes `seminar_attendance_corrected` ActivityLog metadata with actor, event, participant, action, reason, and before/after snapshots.
- A reason is required for removal and any change to an existing decision, including transfer of an attendance-code decision to manual control. Repeating the same manual state is an idempotent no-op without a new timestamp or audit record. Existing native role and duration fields are retained.
- Native join, heartbeat, leave, and finalization update timing fields without writing manual decision fields. Connector completion calls finalization only for native delivery. Attendance-code submission continues to reject manually overridden rows.

## TDD and verification

- Initial RED: the direct PHPUnit run of the two new manual test files yielded seven expected `RouteNotFoundException` errors for the absent named actions; the existing external completion guard test passed.
- A later RED exposed a repeated `not_present` action that returned a validation redirect because the request required a reason for every false value. A success-flash assertion caught it; the service now decides whether the action is a removal, correction, or no-op under its row locks.
- Final focused run: `php vendor/bin/phpunit --do-not-cache-result tests/Feature/Connectors/SeminarManualAttendanceTest.php tests/Feature/Admin/AdminSeminarAttendanceTest.php tests/Feature/Seminars/SeminarCodeAttendanceTest.php tests/Feature/Seminars/SeminarAttendanceTest.php tests/Unit/Services/Seminars/SeminarAttendanceServiceTest.php` passed: 24 tests, 256 assertions.
- Scoped `php vendor/bin/pint --test` passed for the ten Task 10 PHP files. `git diff --check` passed. PHP syntax checks passed for the new request and service.
- PHPUnit used isolated `cc_db_test` through `phpunit.xml`. No development database reset or migration was run.

## Boundary

- This task adds server actions and audit behavior. Task 11 supplies the complete attendance roster, export, and learner wording.
