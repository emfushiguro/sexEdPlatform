# Moderation Suspension Fixes Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Repair suspension details, notification text/navigation, saved duration and explicit duration editing, and the suspended-user/appeal appearance.

**Architecture:** Keep current controllers/services and authoritative suspension/enforcement timestamps. Store review input in existing case metadata; use an explicit transactional duration-change method and admin audit records. Reuse the existing notification normalizer/deep-link resolver and add one restricted branded Blade shell.

**Tech Stack:** Laravel 12, PHP, MySQL, Blade, existing Tailwind/Vite assets, PHPUnit and Playwright.

**Spec:** `docs/superpowers/specs/2026-11-16-moderation-suspension-debug-design.md` (execution authorized by the user; work performed directly on main).

**Execution:** implemented locally, uncommitted and not deployed. Checked items record work/checks performed, not a green complete project suite. Final focused suite: 98 tests / 455 assertions passed; JavaScript: 257 passed; browser: 8 checks passed. Full project run still has 178 errors / 737 failures, with baseline comparison documented in [verification and manual test guide](../../qa/2026-11-16-moderation-suspension-fixes.md). No commit, push, merge or deployment was performed. Local captured-response browser checks and isolated HTTP tests are documented separately; a live deployed browser test is still required after deployment.

## Global Constraints

- Preserve development data. Never reset, wipe, truncate, recreate or destructively reseed `sexedplat` or the existing `cc_db_test`.
- Use an independently verified disposable test schema for `RefreshDatabase` tests; fake mail/notifications in fixtures.
- Never expose internal report/enforcement notes in user-facing messages.
- Retain bounds of 1–8,760 hours or 1–365 days, ownership checks, admin route protections, current appeal eligibility and overlapping-suspension expiry behavior.
- Use existing case metadata and timestamp columns; no schema migration or new frontend dependency is planned.
- Keep registration stopped at document upload and avoid changes to deployed accounts/data while implementing locally.
- Preserve all existing Git stashes. Work directly on main without a separate worktree, as explicitly requested by the user; commit/push remain separate user-directed actions.
- User-confirmed: provide a separate explicit duration-change action. Executed plan interpretation: total duration from original start, and issuance/change receipts to the issuing/editing admin only.

## Review Focus

- Suspensions with appeals but no thread rows must render, because MySQL validates selected columns even for an empty result (Task 1).
- Notifications written before the fix must gain readable fallback text and safe role-appropriate links without editing their stored data (Task 2).
- Report saves and retried duration edits must not restart penalties or duplicate audit/notification records (Task 3).
- Expired/revoked/permanent suspensions, sub-hour legacy windows and overlapping penalties must not be accidentally extended or unsuspended (Task 3).
- Suspended users must be able to appeal and log out without depending on blocked dashboard/chat routes or another user's records (Task 4).

---

### Task 1: Restore suspension-details rendering

**Files:**
- Modify: `app/Services/Admin/ModerationSuspensionDashboardService.php`, `buildShowPayload()`.
- Test: `tests/Feature/Admin/Moderation/AdminSuspensionDashboardUiTest.php`.

**Interfaces:** Consume existing `buildShowPayload(UserSuspension $suspension): array`; preserve returned `suspension` and `appeals` keys and the existing show route/view.

- [x] Establish a new isolated MySQL test schema, inspect its effective configuration, and assert `SELECT DATABASE()` equals that exact schema before running any test with `RefreshDatabase`. Do not run the following commands against default PHPUnit settings without this guard.
- [x] Add `test_suspension_details_renders_with_no_appeals`, `test_suspension_details_renders_with_an_empty_appeal`, and `test_suspension_details_renders_with_thread_messages`. Each GETs `admin.moderation-suspensions.show` and asserts 200; the latter two create a `SuspensionAppeal`, with the final test also creating an `AppealThreadMessage` using `sender_user_id` and `message_body`. Assert the expected appeal status/reason and message count.
- [x] Add `test_suspension_details_handles_soft_deleted_reported_user` (200 with unavailable-user fallback) and `test_non_admin_cannot_open_suspension_details` (403 or the established authentication redirect).
- [x] Run on the guarded schema: `php artisan test tests/Feature/Admin/Moderation/AdminSuspensionDashboardUiTest.php`. Confirm the appeal-details cases fail with MySQL error 1054 before the fix.
- [x] Change the eager-load projection to `threadMessages:id,suspension_appeal_id,sender_user_id,sender_role,message_body,created_at`. Do not rename the database columns or create a migration.
- [x] Rerun the same tests; confirm all new cases pass, with existing index/report behavior unchanged. Review the diff for this task before continuing.

### Task 2: Restore readable moderation notification text and destinations

**Files:**
- Modify: `app/Notifications/Moderation/EnforcementIssuedNotification.php`.
- Modify: `app/Notifications/Moderation/SuspensionIssuedNotification.php`.
- Modify: `app/Notifications/Moderation/AppealSubmittedNotification.php`.
- Modify: `app/Notifications/Moderation/AppealDecisionNotification.php`.
- Create: `app/Notifications/Moderation/AdminSuspensionRecordedNotification.php`.
- Modify: `app/Support/NotificationPayloadNormalizer.php`, `app/Support/NotificationDeepLinkResolver.php`.
- Modify: `app/Services/Moderation/SuspensionService.php`, issuance notification dispatch only.
- Test: `tests/Feature/Notifications/ModerationLifecycleNotificationTest.php`.
- Create: `tests/Unit/ModerationNotificationPresentationTest.php`.
- Create: `tests/Feature/Notifications/ModerationNotificationNavigationTest.php`.

**Interfaces:** Existing `toArray(object $notifiable): array` payloads gain `title`, `message`, `severity` and `action_url` without removing their existing keys. Preserve `normalize(array $data): array` and `resolve(array $payload, string $fallbackUrl, ?User $recipient = null): string`. New `AdminSuspensionRecordedNotification(UserSuspension $suspension, string $event = 'issued')` accepts `issued` / `duration_changed` and uses the database channel only.

- [x] Add unit data-provider cases for all four current lifecycle types. Normalize each new payload and an old ID/status/timestamp-only payload; assert `title !== 'Notification'`, nonempty message, and an internal action URL. Assert an unrelated unknown type retains the existing generic fallback. Valid explicit title/message fields must take precedence over inferred legacy values.
- [x] Add notification/navigation cases: admin appeal alert links to `admin.moderation-appeals.show`; affected-user suspension/decision links lead to their suspension status/appeal flow; admin issuance receipt links to `admin.moderation-suspensions.show`. Invalid IDs, mismatched recipients, external hosts and malformed legacy payloads must fall back safely. Approval resolving the last active suspension must lead to an accessible destination rather than a dead appeal link.
- [x] Extend lifecycle tests to assert saved database notification text and the issuing-admin receipt. Verify the receipt is not broadcast to unrelated admins, existing appeal-submission admin recipients remain unchanged, and internal moderation notes never enter affected-user display copy.
- [x] Run pure tests with `php vendor/phpunit/phpunit/phpunit tests/Unit/ModerationNotificationPresentationTest.php`; run the feature files only under the guarded schema. Confirm the current four payloads fail presentation assertions before changing producers/normalizers.
- [x] Add display fields to new payloads and type-specific old-payload fallbacks in the normalizer/resolver. Use actual stored timestamps and existing IDs; keep old data unchanged and do not perform a notification-row backfill. Use existing working report notifications as the shape reference.
- [x] Add the database-only receipt dispatch to the supplied issuing admin after successful issuance. Preserve user channels and current expiry/status semantics. Make the receipt show case/action and start/end window without private notes.
- [x] Rerun unit/feature presentation and navigation tests. Verify admin, learner, Guardian and instructor notification surfaces, including the dropdown and full list. Delivery-only assertions are insufficient.

### Task 3: Persist duration selection and implement an explicit duration-change action

**Files:**
- Modify: `app/Http/Controllers/Admin/ModerationSuspensionController.php`.
- Modify: `app/Services/Admin/ModerationSuspensionDashboardService.php`.
- Modify: `app/Services/Moderation/SuspensionService.php`.
- Create: `app/Http/Requests/Admin/ChangeSuspensionDurationRequest.php`.
- Create: `app/Notifications/Moderation/SuspensionDurationChangedNotification.php`.
- Modify: `routes/admin.php`.
- Modify: `resources/views/admin/moderation/suspensions/report-show.blade.php` and `show.blade.php`.
- Test: `tests/Feature/Admin/Moderation/AdminMessageReportReviewTest.php`.
- Create: `tests/Feature/Admin/Moderation/SuspensionDurationChangeTest.php`.
- Reference: `app/Services/Moderation/ModerationCaseIntakeService.php` recursive metadata merge and `app/Services/AdminActivityLogService.php` existing audit API.

**Interfaces:**
- Existing `buildReportPayload(ModerationCase $moderationCase): array` adds `temporarySuspensionDuration` (`amount: ?int`, `unit: ?string`, `suspension_id: ?int`, `issued: bool`, `can_change: bool`).
- Persist `metadata.chat_temporary_suspension` as `{initial: {amount, unit}, current: {amount, unit}, user_suspension_id, enforcement_action_id}` on initial issuance; preserve other metadata keys.
- Add `SuspensionService::changeDuration(UserSuspension $suspension, User $admin, int $durationAmount, string $durationUnit, string $reason): UserSuspension`.
- Add `SuspensionDurationChangedNotification(UserSuspension $suspension)` using existing mail/database channels; its display payload contains title, message, severity, internal status URL, suspension ID and updated window, with no private audit reason.
- Add controller `changeDuration(ChangeSuspensionDurationRequest $request, UserSuspension $userSuspension): RedirectResponse` and admin-protected `PATCH /moderation-suspensions/{userSuspension}/duration`, named `admin.moderation-suspensions.duration.update`, following the existing route prefix.
- Request: positive integer `duration_amount`, `duration_unit` in `hours|days`, maximum 8,760 hours / 365 days, and required string `reason` of 10–2,000 characters.

- [x] Add `test_temporary_duration_survives_redirect_and_reopen` with `8 hours` and `2 days`: assert both records have the correct end time, metadata preserves exact amount/unit, and a fresh GET shows that selection rather than three days. Add validation-error redisplay assertions for status/action/notes/duration.
- [x] Add `test_legacy_case_uses_issued_window_instead_of_default`: no metadata, known eight-hour end/start window; show eight hours and actual dates. For a sub-hour legacy window, show actual timestamps and no fabricated exact amount. No GET should update the stored record.
- [x] Add `test_report_note_save_does_not_change_issued_duration`: subsequent ordinary report saves retain original ends and counts. A tampered different duration on the ordinary report endpoint must return a validation error directing the admin to Change duration, rather than silently ignore it.
- [x] Add `test_explicit_duration_change_updates_both_records_and_audit`: freeze server time; issue an eight-hour suspension starting two hours ago; explicitly set total duration to twelve hours. Assert original start is unchanged, end is original start + twelve hours in both records, initial metadata remains eight hours, current becomes twelve, and audit records actor/reason/old/new end. Assert one affected-user update and one editor receipt with correct end time and no private reason in the user payload.
- [x] Add negative cases for zero/negative/fractional values, 8,761 hours, 366 days, invalid units, reason outside bounds, non-admins, permanent/extended/expired/revoked records, missing/wrongly linked enforcement actions, and a proposed end already in the past. Assert no partial writes, notifications or extra suspension rows.
- [x] Add duplicate submission/no-op assertions: the same requested end time produces no extra audit/notification. Add overlapping suspensions and confirm editing one does not restore account access or alter the other. A simulated audit/save failure must roll back both timestamp changes and metadata, with no delivered notice. After-commit tests must use non-transaction-wrapped fixtures on the disposable schema, or explicitly exercise the test framework's transaction callbacks; an outer `RefreshDatabase` transaction must not hide notification dispatch from assertions.
- [x] Run the two test files on the guarded MySQL schema; confirm persistence/edit cases fail before implementation.
- [x] Make initial report decision + enforcement + suspension + review metadata updates atomic. Lock/reload the report and case before comparing the saved action so duplicate submissions do not create two suspensions; add a duplicate-initial-submission regression asserting one action/suspension. Preserve status/action/notes on validation errors. Issue notifications after a successful outer commit so a failed report save does not emit a notice for a missing suspension. Defer existing issuance notices to commit without sending duplicate lifecycle events.
- [x] Load saved selection and linked window in the dashboard payload; use 3 days only for a new decision. Preserve metadata through adapter resync. Render issued selection as read-only on the ordinary report form with a link to Change duration.
- [x] Implement `changeDuration(...)` with row locks in a transaction. Restrict it to an active finite temporary suspension from a chat report with a valid matching enforcement action. Compute the total duration from immutable original `starts_at`, reject past/nonfuture ends, update both ends and current metadata, and audit through the existing service using action `moderation.suspension.duration_changed`. Keep private reasons in the admin audit only. Defer user/editor notifications until commit; identical requested end is an intentional no-op.
- [x] Render Change duration on the details page, linked from the report page, with current end time, amount/unit, a required reason, and a confirmation showing the proposed end time. Label the amount as total duration from the original start so the action does not silently mean an additional extension. Use the existing confirmation-dialog pattern; browser calculation is advisory and the final PATCH independently validates/calculates the authoritative end time. Do not introduce a second preview endpoint.
- [x] Rerun focused tests, existing `UserSuspensionLifecycleTest`, `SuspensionMiddlewareEnforcementTest`, `SuspensionAppealReviewTest` and notification tests. Verify exact dates, counts and audit entries, not just redirect success.

### Task 4: Brand suspended-user and appeal pages without blocked navigation

**Files:**
- Create: `resources/views/layouts/moderation.blade.php`.
- Modify: `resources/views/moderation/suspension-status.blade.php` and `resources/views/moderation/appeals/create.blade.php`.
- Test: `tests/Feature/Moderation/SuspensionAppealUiFlowTest.php`.
- Reference: existing branded Poppins/font and Vite setup, `public/media/Logo.png`, current moderation route allowlist in `CheckUserSuspensionStatus`.

**Interfaces:** Preserve view names, controllers, ownership checks, form actions, CSRF, validation field names and appeal-history relations. The shell uses ordinary Blade title/content sections, existing brand assets and responsive styles; it does not depend on learner/instructor dashboard navigation or chat.

- [x] Add `test_moderation_status_and_appeal_use_platform_branding`: both routes respond 200 for the owner, contain the platform logo and Poppins/brand markers, and omit the Laravel application-logo SVG and dashboard navigation links. Assert status/window, reason form, validation errors, appeal history and logout remain accessible.
- [x] Add cross-account and guest tests, unchanged invalid-reason form behavior, XSS-escaped reason/thread content, and both finite/permanent status copy. Preserve existing policy tests rather than expanding eligibility in this presentation fix.
- [x] Run the focused file on the guarded schema and record the branding assertions failing on the legacy layout.
- [x] Implement the restricted branded shell and move both views to it. Keep accurate active/permanent/expiry and appeal feedback; do not use a generic temporary heading for a permanent suspension. The admin appeal workspace remains in its existing admin layout.
- [x] Rerun focused tests, then browser-check 320px, 390px and desktop widths with long appeal history/error text and keyboard-only navigation. Verify logo, no horizontal overflow, readable timestamps, reachable actions and successful logout without a redirect loop. Do not submit production suspensions or documents for visual QA.

## Final verification and handoff

- [x] Confirm one regression test from each task failed before its fix and passes after it; save actual output.
- [x] Run the bounded moderation, notification, appeal and expiry suite once on the verified isolated schema. Report any unrelated failures separately instead of broadening this patch.
- [x] Run PHP syntax checks on changed PHP files, view rendering tests, `npm run build` for the new Tailwind classes, manifest-reference validation and `git diff --check`.
- [x] Manual scenario on dedicated local test users: report -> issue eight hours -> reopen -> explicit change to twelve total hours -> inspect user/admin notification text -> open eye/details -> view status -> submit/review appeal -> verify expiry/overlap behavior. Record effective start/end times and expected messages.
- [x] Review authorization, notification privacy, old-row fallback, concurrency/no-op behavior and transaction failure handling against the spec. Do not claim deployed verification until the reviewed changes are deployed and retested with authorized access.
- [x] Hand off the concrete diff and test evidence. Do not commit, push, merge or deploy without the user's instruction.
