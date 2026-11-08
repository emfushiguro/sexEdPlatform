# Educational event refinements QA

Date: 2026-10-15. Approved refinements implemented in the existing checkout.

## Delivered behavior

- Schedule: aligned Start/End with separate PHT date/time inputs, duration shortcuts, summary and ordering feedback. New events suggest end +60 minutes, deadline -30 minutes and link release -15 minutes. Suggestions follow Start until edited/cleared; Use suggested time restores following. Existing/failed-validation values and stored seconds are preserved; registrations still lock rescheduling.
- Published external-event access settings open from Manage event access in a keyboard-accessible drawer near the event heading. Delivery errors reopen that drawer without opening Admin rejection. It overlays navigation and restores focus/scroll.
- Confirmed-speaker indicators distinguish accepted invitations. Participant speaker lists show accepted speakers only. Instructor invitation pages distinguish invitation/event status and provide appropriate external, native or in-person guidance. External preparation access uses the protected redirect, including before participant release.
- Admin review includes description/objectives, format-specific delivery, dates/duration, registration deadline/audience, instructions, organizer and submission/publication metadata. Attendance remains read-only for Admins.
- Registration/attendance rows display photos or initials. Decision and Save remain inline for each participant, with reasons for corrections/removals. Eager profile loading, CSV structure and registration-first pagination are retained.
- Connector hosts can copy/present generated codes and explicitly post/unpost them for confirmed eligible registrants. Posting never marks attendance. Regeneration/closing clear the posted value; unposting hides it while allowing known-code submission.

## Data and privacy

Migration `2026_10_15_000002_add_posted_attendance_code_to_seminars.php` adds nullable ciphertext and posting timestamp fields. Its SQL was inspected and applied incrementally to development `cc_db` and isolated `cc_db_test`; no reset, destructive seed or unrelated pending migration ran.

Only explicit posting stores an encrypted display copy; submission verification still uses the hash. The ciphertext is hidden from model JSON, codes are excluded from flashed validation input, and audit entries contain no secrets. Host/learner secret-bearing HTML uses `no-store, private`. Confirmed eligible registrants can view posts only within the inclusive window on published/completed non-native events. Admins cannot reveal codes or write attendance.

## End-to-end QA sequence

Use dedicated QA accounts/events, with a future start in Philippine Time.

1. Create an event starting tomorrow 2 PM: expect End 3 PM, deadline 1:30 PM and release 1:45 PM. Move Start; automatic fields follow. Edit deadline and clear release, then move Start: both choices remain. Restore with Use suggested time. Test duration shortcuts, overnight dates, reversed End and deadline at/after Start. After another validation error, verify input survives. Existing registered events retain their schedule on unrelated edits.
2. Invite an instructor and accept in that account. Verify Confirmed speaker on host/Admin/participant pages; pending/declined names must not appear as confirmed speakers. Instructor colleagues exclude self and unaccepted invitations.
3. Review as Admin: description/objectives, platform or venue/address/room, PHT dates/duration/deadline, audience and submission metadata must match the Connector submission. Approve, then publish through the Connector workflow. Attendance controls remain read-only for Admins.
4. On published external events open Manage event access as host and Admin. Check saved values, Close above navigation, Tab cycling, Escape/backdrop dismissal, focus return and mobile scrolling. Invalid access data should reopen only the access drawer with field errors.
5. Before participant release, an accepted external speaker should receive Open external event using the protected application URL. The provider URL must be absent from speaker HTML. Cancel the invitation and verify direct join denial. Verify expired/unavailable-link messages, completed ongoing vs event-end links, in-person arrival details and native readiness.
6. Register confirmed, pending and cancelled participants. Verify photos/fallback initials and each attendance row's inline actions. Corrections/removals require a reason; non-confirmed rows cannot be manually marked. Export CSV and verify statuses/methods/timestamps/native durations.
7. Generate a code with an open attendance window. Test Copy code and Present code. An unposted code is shown only after generation; save it or regenerate. A posted value remains available to the authorized host for sharing.
8. Post for registrants, then reload as a confirmed eligible learner. Verify Posted attendance code and the submission form. Opening the page alone must not record attendance; submitting should update only that learner and the host count.
9. Pending/cancelled/ineligible participants, Admins and speakers without participant registrations must not receive the post. Automated tests cover before opening, inclusive boundaries and after closing.
10. Unpost: hide the code but allow known-code entry. Regenerate: old code fails and post clears. Close attendance: hide post and deny submission. Verify audit entries never contain codes/hashes.

Posting allows a confirmed registrant to submit without joining the session. Attendance proves code submission, not continuous participation.

## Automated checks

Use existing `cc_db_test`, rollback transactions and serial database suites:

```powershell
node --test tests/JavaScript/seminar-schedule.test.mjs
php vendor/phpunit/phpunit/phpunit --do-not-cache-result --bootstrap tests/existing-database-bootstrap.php --filter 'Seminar|EducationalEvent|ConnectorAttendanceOwnership' --log-junit storage/framework/event-refinements-regressions.xml
php artisan view:cache
npm.cmd run build -- --configLoader native --outDir storage/framework/event-refinements-build
node tests/Browser/educational-event-refinements.mjs
```

Build after database tests finish, because Tailwind scans compiled views that tests refresh. Native config loading avoids the sandbox's esbuild config-resolution restriction. New assets were copied into local public/build without deleting preexisting assets; its previous manifest was backed up. The isolated build was removed after matching manifest hashes.

Browser smoke uses existing seeded accounts and only GETs event pages; it never saves events or attendance. Override `CC_QA_ORIGIN`, `CC_CONNECTOR_QA_EMAIL`, `CC_CONNECTOR_QA_ID`, `CC_EVENT_QA_ID`, `CC_QA_PASSWORD` as needed. Defaults are Connector 2/event 1 and seeded test accounts. Chromium may need execution outside the restricted sandbox.

## Verification evidence

- Five schedule Node checks passed: leap/midnight/year boundaries, timezone independence, manual clears/restoration, saved seconds and deadline feedback.
- Combined seminar/attendance selection: 150 tests, 1,228 assertions passed; three PHPUnit metadata deprecations. JUnit: `storage/framework/event-refinements-regressions.xml`.
- After final Admin label corrections: 13 sharing/speaker tests, 143 assertions passed. JUnit: `storage/framework/event-refinements-final-check.xml`.
- After final mobile form adjustments: 26 presentation/form/management tests, 146 assertions passed. JUnit: `storage/framework/event-refinements-rendering.xml`.
- All 16 scoped PHP files passed Pint; Blade compilation and isolated Vite production build passed. Independent UI, sharing and final source reviews have no remaining actionable findings after fixes.
- Browser smoke passed against the local app using existing Connector and Admin QA accounts. Checked schedule suggestions/manual clearing, midnight/year rollover, mobile form width/full-date readability, participant identity, host/Admin drawer focus cycling, Escape, scroll restoration and navigation layering. No event or attendance mutations were submitted. The smoke uses the separate `/secure-panel-access` Admin portal. Saved screenshots: `storage/framework/event-schedule-desktop.png`, `event-schedule-mobile.png`, `event-attendance-mobile.png`, and `event-admin-review-desktop.png`.
- Browser checks found and fixed mobile grid/fieldset intrinsic overflow and narrow date controls. Mobile date/time inputs now stack, while larger screens retain aligned controls. An initial sandbox Chromium startup timeout required running this local check outside the sandbox. Temporary build output was removed after verifying matching manifest hashes.

Full-project PHPUnit/Pint, staging/production deployment, multi-process load, screen-reader QA and real external-provider media sessions were not run. Browser smoke did not mutate an attendance code or exercise clipboard/presentation with a live code; PHP tests cover those controls and posting/submission lifecycle. Application redirect/authorization tests do not verify third-party meeting behavior.

Changes remain uncommitted in the requested current checkout. Existing unrelated working-tree changes were preserved; no merge, push or deployment was performed.
