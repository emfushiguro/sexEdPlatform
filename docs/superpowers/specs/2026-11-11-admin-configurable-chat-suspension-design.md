# Admin-Configured Chat Suspension Design

## Goal

Let an administrator set a finite chat-report suspension duration in hours or days, communicate the exact end time to the affected account, and restore account access when the suspension expires.

## Scope

- Configure duration only when recording a temporary suspension from a chat-message report.
- Start the suspension immediately when the admin records the decision.
- Accept a positive integer duration in hours or days, capped at 365 days. Keep permanent suspension and standalone dashboard issuance out of scope.
- Persist the calculated end timestamp in the existing enforcement action and suspension `ends_at` fields; no schema migration.
- Keep the report's moderation notes private. User-facing messages communicate action, start time, and exact expected end time, not internal notes.
- On the suspended user's next authenticated web request after expiry, mark elapsed active suspension records expired and restore active account status only when no other unexpired suspension remains.
- Do not change learner module/instructor report actions, community moderation, module-publication review, or instructor-application review.

## Flow

1. Admin selects temporary suspension on a chat-report decision and supplies duration plus unit.
2. The server validates duration and unit and computes `ends_at` from server time.
3. The enforcement action and user-suspension record store the same start/end window. Existing email and database notifications, plus the suspension-status page, show that window and clarify when access resumes.
4. When a suspended user returns after the end time, request middleware expires elapsed records and synchronizes account status before deciding whether to redirect or continue.

## Validation and Access

- Duration fields are required and validated only for the temporary-suspension decision; other report actions remain unchanged.
- Duration is an integer from 1 through 8760 hours or 1 through 365 days.
- All existing web access remains blocked during any other active suspension. Chat restriction remains a messaging-only restriction.
- Expiry is evaluated server-side using the application's current clock; browser time is display-only. Display the end timestamp with the configured application timezone abbreviation.

## Verification

- Feature tests cover duration validation, exact persisted end time, email/database and status-page communication, expiry restoration, overlapping suspensions, and unchanged non-suspension report actions.
- Existing report-review, suspension middleware/lifecycle, and appeal tests remain green.
- Browser QA checks conditional duration controls in the admin report form and exact start/end copy in the affected user's suspension status page where a local authenticated browser session is available.

## Explicitly Deferred

- Direct suspension issuance from the central dashboard.
- Permanent suspension controls, scheduled expiry jobs, and unifying the separate instructor restriction policy.
- Community and publication-review case routing into the suspension dashboard.
