# Educational event QA refinements

Approved in chat on 2026-10-15, including both code-sharing methods and suggested registration/link offsets.

## Schedule

Group Start and End together, separate date and time controls with PHT labels, suggest an initial one-hour duration, and provide 30/60/90/120 minute shortcuts. Suggest registration closing 30 minutes before start and external-link release 15 minutes before start. Suggestions follow start changes until the user edits or clears them. Restore automatic behavior through Use suggested time. Preserve persisted values, failed-validation input, second precision in existing records, overnight/multiday events, and the existing prohibition on rescheduling after any registration exists. Show duration and invalid-order feedback.

## Event and speaker presentation

Show confirmed speakers with a green check badge; participant-facing lists contain only accepted speakers. Keep pending, declined, and cancelled states visible to managers. Admin moderation displays description, objectives, delivery format/platform or venue, instructions, schedule/duration, registration deadline and approval/audience, release/expiry, organizer and submission/moderation information. Admin attendance oversight remains read-only.

Published/completed external events expose Manage event access near the event heading; existing authorized delivery form opens in a right-side drawer. Reuse current focus/keyboard behavior, restore trigger focus, close on Escape/backdrop, retain scroll containment, show errors and reopen on delivery validation failures.

Instructor invitation pages separate invitation from event lifecycle state and show appropriate external/native/in-person access and guidance. Accepted external speakers use the existing authorization-rechecking redirect, including early preparation access; no raw external URL in speaker HTML. Completed/cancelled/expired/unpublished states use accurate guidance.

Participant and attendance tables show profile photos with initials fallback and keep updates with their row. Reasons expand for corrections/removals. Preserve authorization, auditing, registration checks, CSV structure, and pagination.

## Attendance sharing

Connector hosts can copy/present the once-generated code and explicitly post/unpost it for confirmed eligible registrants. Posting requires possession of the current code, verifies its hash under the event lock, and stores only ciphertext plus posting timestamp. Hash remains the submission verifier. Encrypt only after explicit posting; never store code plaintext or include it in audit/notification payloads, generic model JSON, admin pages, or unrelated learner HTML. A posted code is available only for published/completed non-native events during the inclusive effective attendance window, with active eligible registration. Posting itself never records attendance. Regeneration/closing clears the published code. Unposting hides it without disabling known-code entry. Hosts can see posted state and copy/present their posted value; lost unposted codes require regeneration.

Use an incremental nullable-field migration; inspect schema first and apply normally without resets. Tests use existing isolated cc_db_test and rollback transactions; no concurrent database suites. Preserve existing checkout work. Validate schedule state in Node, access/security/lifecycle via PHPUnit, Blade compilation, focused style, build and browser checks where available. Report unrun gates explicitly.
