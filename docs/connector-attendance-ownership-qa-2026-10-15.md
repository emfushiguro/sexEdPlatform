# Connector-managed attendance: implementation and verification

Date: 2026-10-15

## Root cause

Admin attendance controllers explicitly authorized ordinary Admins to generate and close codes and make manual attendance decisions. Both Admin event pages included the shared code-management component, which decrypted the host's flashed code without checking the generating actor. The shared roster also generated Admin correction forms. Code management services accepted no actor and manual correction relied on controller authorization.

An additional native attendance path allowed an Admin with Connector seminar permissions to create/update their own livestream attendance. ActivityLog also attributed direct service operations to the ambient authenticated account rather than the authorized actor.

## Result and permissions

- Connector hosts need an active membership with `connector.manage_seminars` in a verified organization. The route organization must own the event. Code and manual services independently reload the event and verify the actor against its actual organization.
- Admin attendance write routes and controller methods were removed. Legacy POST requests return 404. Admin actors are rejected by shared code, manual, and native attendance services, including Admins with Connector membership. Connector code/roster access is also denied to Admins.
- Admins retain roster and CSV access. `/admin/seminars/attendance` adds read-only platform monitoring with event-title, organization, local event-date, and attendance-status filters. Each event roster includes available attendance audit history. Existing unrelated event moderation and delivery permissions are preserved.
- Learner submissions continue to use the authenticated participant, active eligible registration, event status, server clock, and event-specific hash. Client-provided user/event/status/timestamp fields cannot select another participant or override attendance.
- Existing controlled Connector corrections remain: active confirmed registrations only; reasons required for removal or correction; actor, timestamp, previous/new values and reason recorded. No separate super-admin correction workflow was found or introduced.

## Lifecycle and secrets

1. A permitted Connector host generates an unpredictable eight-digit code with `random_int`. The existing default window opens 15 minutes before event start and closes 30 minutes after event end; hosts can configure local PHT overrides. Missing/reversed effective windows are rejected.
2. Only a hash is stored in the seminar row. The generated code is delivered once in an encrypted session flash, bound to event and generating actor. The host must copy/share it; a lost code must be regenerated. Stale flashed values are checked against the current hash before display.
3. Server-side checks enforce registration eligibility and inclusive opening/closing boundaries. Wrong-code attempts retain the existing limits of five per participant/event and twenty per IP over ten minutes. Successful attendance uses the authenticated user and server-generated timestamp.
4. Regeneration replaces the hash and guarantees a different value from the currently active code. Closing clears the hash and disables submission. Transactions, locks, and the existing unique `(seminar_id, user_id)` constraint prevent duplicate records.
5. Generation, regeneration, closing, successful submission, and manual correction use ActivityLog. Attendance services explicitly supply the authorized audit actor. Automatic metadata includes no code or hash. Admin pages do not render code controls or secret values, including when host flash data is present.

The Connector page retains its existing roster/correction interface and adds submission count plus scheduled/open/expired/closed window feedback. The learner interface and existing failure/success feedback remain intact.

## Database safety

Read-only inspection confirmed the existing attendance fields and unique constraint in development. No schema migration was needed or added. No development rows, registration records, learner progress, quiz results, or certificates were modified by this work.

Tests used the existing MySQL `cc_db_test` schema and rollback transactions through `tests/existing-database-bootstrap.php`. It requires the testing environment/database and rejects cached test configuration, then bypasses RefreshDatabase's schema rebuild. Existing schema and role seed data must be present. No reset, destructive migration, or destructive seeder was run.

## Files changed

Backend:

- `app/Http/Controllers/Admin/SeminarAttendanceController.php`: read-only monitoring, roster, audit history and CSV.
- `app/Http/Controllers/Connector/SeminarAttendanceController.php`: authorized code actor, scoped secret flash, submission count.
- `app/Models/ActivityLog.php`: backward-compatible optional explicit audit actor.
- `app/Services/Seminars/SeminarAccessService.php`: shared attendance-management authorization.
- `app/Services/Seminars/SeminarCodeAttendanceService.php`: actor authorization and lifecycle/submission audit.
- `app/Services/Seminars/SeminarManualAttendanceService.php`: service authorization and correct audit actor.
- `app/Services/Seminars/SeminarAttendanceService.php`: deny Admin native attendance writes.
- `routes/admin.php`: remove attendance write routes; add monitoring GET route.

Views:

- `resources/views/admin/seminars/attendance-monitor.blade.php` (new)
- `resources/views/admin/seminars/attendance.blade.php`
- `resources/views/admin/seminars/index.blade.php`
- `resources/views/admin/seminars/show.blade.php`
- `resources/views/connectors/seminars/attendance.blade.php`
- `resources/views/seminars/_attendance-code-management.blade.php`
- `resources/views/seminars/_attendance-roster.blade.php`
- `resources/views/seminars/_attendance-audit.blade.php` (new)

Tests/documentation:

- `tests/Feature/Admin/AdminSeminarAttendanceTest.php`
- `tests/Feature/Connectors/ConnectorAttendanceOwnershipTest.php` (new)
- `tests/Feature/Connectors/SeminarManualAttendanceTest.php`
- `tests/Feature/Seminars/SeminarCodeAttendanceTest.php`
- `tests/Feature/Seminars/SeminarAttendanceRosterTest.php`
- `tests/existing-database-bootstrap.php` (new)
- `docs/superpowers/plans/2026-10-15-connector-attendance-ownership.md` (new)
- This report.

Unrelated existing checkout edits, including Connector routes and compiled production assets, were preserved.

## Automated evidence

- **133 seminar/educational-event regressions passed, 1,033 assertions**, covering code lifecycle, eligibility, throttling, duplicates, roster/export, registration, delivery, ownership, Admin restrictions, and native attendance.
- **3 additional Connector ownership/audit tests passed, 29 assertions**, including direct unauthorized service calls, mismatched organizations, membership permissions, server identity/time, secret-free audit metadata, and explicit audit actor attribution.
- Total: **136 passing tests, 1,062 assertions**. Three pre-existing PHPUnit doc-comment metadata deprecations were reported in unrelated test classes.
- PHP style passed for all 13 scoped application/test PHP files. `routes/admin.php` still fails existing import-order/indentation rules, reproduced on its HEAD version; unrelated formatting was left alone.
- `php artisan view:cache` passed. Live route inspection confirms Admin attendance routes are GET/HEAD only.
- Vite production build passed into `storage/framework/attendance-build-20261015`; its application CSS hash matched the current `public/build` CSS hash. Production build files were not changed. Temporary build output was removed after verification.
- A separate read-only code review found the audit attribution issue; the fix and regression were reviewed with no remaining blockers identified.

Reproduce the full targeted selection without rebuilding either database:

```powershell
php vendor/phpunit/phpunit/phpunit --do-not-cache-result --bootstrap tests/existing-database-bootstrap.php --filter 'Seminar|EducationalEvent|ConnectorAttendanceOwnership'
```

Saved run evidence: `storage/framework/attendance-regressions.xml`, `storage/framework/attendance-ownership.xml`, and `storage/framework/attendance-regressions.txt`.

## Unrun release gates and limits

Browser/mobile/keyboard/screen-reader QA, staging verification, full-project PHPUnit/Pint, and concurrent multi-process load testing were not run. The passing transaction and authorization tests do not substitute for those release gates. No deployment was performed; rebuild route caches as part of the normal deployment process.

Code display remains intentionally one-time to retain hash-only persistence. The host must save the displayed value or regenerate it. New audit entries begin with this implementation; historical entries were preserved and no history was synthesized.
