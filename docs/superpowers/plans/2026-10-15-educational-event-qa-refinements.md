# Educational event QA refinements implementation plan

> Use executing-plans/subagent-driven-development in the current branch. User approved implementation; no extra worktree or approval checkpoint.

**Goal:** Deliver the approved schedule, speaker, moderation, drawer, roster, and attendance-sharing refinements.

**Architecture:** Reuse Laravel seminar/access/attendance services and Blade/Alpine. A small pure JS schedule state module supports native date/time inputs. Persist only an explicitly posted encrypted attendance code; reuse existing code verification and audit.

**Constraints:** Preserve preexisting edits, PHT/UTC semantics, registration schedule locks, connector-only attendance writes, secret-free admin/JSON/audit/notification outputs, and existing dev data. No new dependencies. Run test database suites serially with `--bootstrap tests/existing-database-bootstrap.php`.

## Task 1: Schedule, access drawer, participant rows

- [x] Implement `resources/js/seminar-schedule.js`, register in `resources/js/app.js`, update `resources/views/connectors/seminars/_form.blade.php`: separate native dates/times, hidden canonical payloads preserving seconds, automatic defaults until edited, reset suggestions, duration shortcuts, feedback; unit check with `node --test tests/JavaScript/seminar-schedule.test.mjs`.
- [x] Add `resources/views/seminars/_delivery-drawer.blade.php`, reuse delivery form and accessible drawer pattern; wire connector/admin show pages; preserve form error feedback.
- [x] Add photos and compact row actions to shared attendance roster and connector registrants. Eager load required profile relationships; preserve CSV/pagination/manual rules. Add focused rendering regressions.

## Task 2: Secure attendance posting and sharing

- [x] Inspect schema, add incremental nullable `attendance_code_posted_ciphertext` (TEXT) and `attendance_code_posted_at` (timestamp) fields. Hide ciphertext in Seminar serialization.
- [x] Extend SeminarCodeAttendanceService with `post(Seminar, User, string): void`, `unpost(Seminar, User): void`, `postedCodeFor(User, Seminar): ?string`. Lock/authenticate/verify current hash on posting; enforce window/status/registration/eligibility when reading; clear posted state on regeneration/disable; audit secret-free actor/event only.
- [x] Add connector-only POST endpoints and controller actions; exclude raw code input from flashed validation fields; wire copy/present/post/unpost controls and protected learner display. Reuse service in both learner controller entry paths. Set no-store responses for secret-bearing HTML.
- [x] Add serial isolated feature tests covering lifecycle, authorization, stale codes, inclusive windows, eligibility, non-native only, admin denial, serialization, audit, no automatic attendance. Retain existing attendance tests.

## Task 3: Speaker and moderation details

- [x] Update instructor speaker controller/list/detail for event lifecycle and external/native/in-person access using existing services. Cover pending/accepted/declined and unpublished/published/live/completed/cancelled/expired conditions. No external raw URL output.
- [x] Add confirmed-speaker badges and accepted-only participant lists; expand admin review metadata with clear format-specific details and submission context. Preserve admin read-only attendance policy.

## Task 4: Verification and handoff

- [x] Run schedule Node checks, serial seminar/educational-event/ownership regressions, focused Pint, Blade compilation, Vite build to isolated output, and diff whitespace checks.
- [x] Review all changed paths against approved spec and security/ownership constraints; fix findings and rerun covering checks.
- [x] Record migration application, automated evidence, browser QA, and outstanding limits in docs; update this checklist. Leave unrelated changes untouched.
