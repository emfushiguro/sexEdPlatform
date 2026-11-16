# Moderation suspension defects: investigation and implemented design

Status: implemented locally on `main`, uncommitted and not deployed. Focused moderation verification passes; the complete project suite still has existing failures. See [verification and manual test guide](../../qa/2026-11-16-moderation-suspension-fixes.md) for results and limitations.

## User report and scope

The user observed these issues both locally and on deployment:

1. Suspension status and appeal pages retain the default Laravel appearance.
2. Moderation notifications display `Notification` with an empty message.
3. The eye icon in the suspension dashboard leads to HTTP 500.
4. Report Review Queue does not retain the selected temporary suspension duration.

The user explicitly selected an additional action for changing the duration of an already-issued suspension. Ordinary report review must not silently change its end time.

Investigated checkout: `main` at `dcb078e8`. The existing remote-tracking ref is three commits ahead; its diff does not change the moderation files examined here. No branch update was performed during this investigation. Deployment symptoms are user-reported; the deployed admin session and server logs were not accessed in this pass.

## Evidence and root causes

### Suspension details: reproducible SQL failure

`ModerationSuspensionDashboardService::buildShowPayload()` eager-loads appeal thread messages using `sender_id` and `message`. Both the migration and the connected MySQL schema define `sender_user_id` and `message_body` instead.

Executing that same SELECT with `LIMIT 0` against the local schema, without reading or changing any message records, produced SQLSTATE `42S22`, MySQL error `1054`: `Unknown column 'sender_id' in 'field list'`.

The failing eager-load is reached when a suspension has an appeal. It can fail even if that appeal has no thread messages because MySQL validates the selected column names. A suspension without appeals may open successfully. Existing dashboard tests visit the index/report pages but do not cover this suspension-details path with appeals.

### Blank notifications: payload/display contract mismatch

`EnforcementIssuedNotification`, `SuspensionIssuedNotification`, `AppealSubmittedNotification`, and `AppealDecisionNotification` serialize IDs, statuses and timestamps but omit display title, message and action URL. Running all four payloads through the real `NotificationPayloadNormalizer` produced exactly:

```text
title: Notification
message: ""
action_url: null
```

The learner/admin notification views render those normalized fields. Existing lifecycle tests check notification delivery and IDs, not rendered text or navigation. Older stored payloads would stay blank if only new notification producers were fixed.

Recipient distinction: suspension issuance currently notifies the affected user only; administrators receive appeal-submission notifications. The working chat-report and learner-report notifications already include title/message/action_url. A distinct admin suspension receipt is proposed below rather than assuming admins already receive suspension issuance events.

### Duration: initial enforcement saves; form selection does not persist

The real report-review controller was executed against a separate in-memory database, with notifications faked and schema column names copied from the local schema:

- First submission of `8 hours` created one enforcement action and one suspension with an eight-hour window.
- The report payload does not include duration fields; the selection is absent from report attributes and case metadata.
- `report-show.blade.php` therefore uses `old('duration_amount', 3)` and `old('duration_unit', 'days')` after redirect/reopen.
- Resubmitting `12 hours` while the report action remains `temporary_suspension` leaves the original end time unchanged. The controller only issues enforcement when the action string changes.

This confirms a display/persistence problem and a silently ignored edit. It does not show that the first submitted end time is always wrong.

### Laravel appearance: legacy page shell and logo

Both learner-facing moderation views extend `layouts.app`. That shell uses Figtree and the older `layouts.learner-navigation` / `layouts.navigation`; the latter includes `x-application-logo`, whose SVG is the Laravel mark. The current branded pages use Poppins, the Conscious Connections logo and purple brand styles. The admin appeal workspace already uses the admin shell.

## Proposed changes and acceptance criteria

1. Correct appeal thread column names in the details query. Cover no appeal, empty appeal, populated thread, missing/deleted related users and admin access restrictions.
2. Add title/message/severity/internal action URL to all four lifecycle payloads. Add type-specific normalization for existing stored payloads without rewriting notification rows. Keep recipient routing authorized and safe. Never expose internal report/enforcement notes in user-facing messages.
3. Provide an in-app, database-only suspension receipt to the issuing admin, and a corresponding update receipt to the admin who explicitly changes a duration. This is a proposed recipient choice: do not broadcast issuance/change receipts to every admin. Existing appeal-submission delivery to admins remains unchanged.
4. Store the exact original and current amount/unit plus the linked suspension/action IDs in `moderation_cases.metadata.chat_temporary_suspension`. Existing source adapter metadata merges preserve this key. Avoid a schema migration. Timestamps in `user_suspensions` and `enforcement_actions` remain authoritative for enforcement.
5. On reopen, display the saved duration and effective start/end window. Use three days only for a genuinely new temporary suspension. Preserve submitted values on validation errors. For older cases with no metadata, show their actual issued window and derive whole hours when exact; do not invent a new three-day duration.
6. Add an explicit Change duration action for an active temporary suspension linked to a chat report. Proposed time interpretation: the entered amount/unit is the total duration from the original `starts_at`, not an extra extension from the button click. Show the calculated proposed end time before confirmation. Require a reason of 10–2,000 characters, retain the existing 1–8,760 hours / 1–365 days bounds, and reject a resulting end time that is not in the future. Permanent, extended, expired and revoked suspensions are outside this editor.
7. Apply the duration edit atomically to the matching suspension and enforcement action; preserve `starts_at`; retain initial selection; update current selection; record old/new values, reason and actor through the existing admin audit service. Notify the affected user of the updated end time and send the editor their admin receipt. Identical retries must not duplicate audit entries or notifications. Report-note saves do not restart or extend a suspension.
8. Move suspension status and appeal pages into one branded restricted-access shell with the platform logo, Poppins, brand colors, accessible forms and mobile layout. Provide status, appeal/back and logout controls; omit normal dashboard menus and chat popups that lead into blocked areas. Preserve appeal validation, ownership, history and eligibility rules.

These are bounded changes to the existing moderation workflow. Keep report actions, suspension expiry, overlapping-suspension handling, and separate instructor penalties intact.

## Approaches considered

- Reuse full dashboard shells: visually consistent but brings blocked navigation and popup dependencies into the suspended-user flow. Not recommended.
- Use a small restricted shell with existing brand assets: recommended; fits both status and appeal history without changing global layouts or normal dashboards.
- Change only the new notification payloads: insufficient for already-stored blank rows. Use producer fixes plus type-specific read-time fallback instead of destructive backfill.
- Store duration in new database columns: possible, but existing central case metadata already supports this exact review context. Use metadata plus existing authoritative timestamps to avoid a migration.
- Treat every report save as a fresh suspension: rejected because it can restart penalties and duplicate notifications. Use the explicit duration-change action selected by the user.

## Original investigation evidence and limitations

- Diagnostic script: `C:/Users/tvace/AppData/Local/Temp/moderation-debug-20261116/probe.php`
- Captured results: `C:/Users/tvace/AppData/Local/Temp/moderation-debug-20261116/probe-results.json`
- Read-only schema preflight confirmed effective environment `local`, configured/connected MySQL database `sexedplat`.
- All controller mutations in the probe used SQLite `:memory:` and fake notifications. Development users, suspensions, messages and notifications were not changed.
- The SQL error was verified with MySQL. SQLite alone must not be used to validate this column-name regression because double-quoted unknown identifiers may be treated as string literals.
- During diagnosis, no database-backed feature suite was run because the default PHPUnit database is `cc_db_test`. Implementation subsequently allocated and verified the separate `registration_qa_20261116183313_root_test` schema before any feature tests. Development data was preserved.
- The implementation has local captured-response browser evidence at 320px, 390px and desktop widths. Functional HTTP requests were tested independently against the isolated schema; this does not constitute deployed-site verification.
- Execution uses total duration from the original start and receipts to the issuing/editing admin only. The user authorized execution of this plan directly on `main` without a worktree.

## Review findings resolved during implementation

- MySQL marks the first `starts_at` timestamp as `ON UPDATE CURRENT_TIMESTAMP`. Duration edits and appeal-state writes explicitly preserve the original start; inconsistent legacy suspension/action starts are rejected for manual review.
- Issued report forms display persisted duration without submitting stale duration inputs. Changing the duration requires the explicit action.
- Appeal-decision notifications state approved/rejected/clarification outcomes and link to owned appeal history, including after the last active suspension is revoked.
- Admin receipts identify the case when available, suspension, action, affected user and full start/end window. They omit internal notes and private edit reasons.
