# Connector attendance ownership implementation plan

**Goal:** Apply the requested Connector-managed attendance lifecycle and read-only Admin oversight to the existing seminar system.

**Architecture:** Reuse SeminarAccessService, code/manual attendance services, registration roster, ActivityLog, and current Blade components. Codes remain hashed with one-time encrypted session delivery to the authorized host. No schema changes or database resets.

**Scope:** Attendance services and their callers, Admin attendance routes/controller/views, shared roster and Connector code panel, focused existing and new authorization tests. Preserve unrelated edits in this checkout.

- [x] Add failing HTTP and service authorization tests: Admin writes/reveal, cross-organization routes, role permissions, learner identity, audit metadata, and monitoring filters.
- [x] Remove Admin write routes/methods/controls. Require an authorized Connector actor in code generation, closing, and manual correction services; block Admin actors even with Connector membership.
- [x] Audit code generation/regeneration/closing and successful learner submissions without secrets. Retain row locks, unique constraints, rate limits, registration eligibility, window validation, and correction history.
- [x] Reuse the roster as read-only for Admins; add platform monitoring filters and event audit history. Show host submission counts and scheduled/open/expired/closed feedback.
- [x] Run attendance, registration, delivery, ownership, and native attendance regressions using existing cc_db_test schema with rollback transactions. Run focused Pint and Blade compilation; report browser and release gates separately.

## Verification

Use `php vendor/phpunit/phpunit/phpunit --do-not-cache-result --bootstrap tests/existing-database-bootstrap.php --filter 'Seminar|EducationalEvent|ConnectorAttendanceOwnership'`.
The bootstrap requires APP_ENV=testing, MySQL database=cc_db_test, and no testing config cache. It sets RefreshDatabaseState::$migrated before tests so RefreshDatabase uses transactions without a rebuild. The existing schema and role seed data must already be present. Never run schema resets or seed development data.
