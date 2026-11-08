# Unrestricted Provider-Neutral Email Registration Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox syntax for tracking.

**Goal:** Accept all syntactically valid provider emails, including temporary addresses, while strengthening normalization, verification, reset privacy, uniqueness, and abuse limits.

**Architecture:** Laravel-native RFC validation, a small account-email normalizer/fingerprint helper, the existing MySQL unique index, signed verification, password broker, and named rate limiters. No ERAG, domain lists, DNS checks, mailbox probes, or vendor APIs.

**Tech Stack:** PHP 8.2, Laravel 12.44, MySQL, Blade, Tailwind, Alpine.js, PHPUnit.

**Spec:** `docs/superpowers/specs/2026-10-31-unrestricted-provider-neutral-email-registration-design.md`

## Global Constraints

- Preserve unrelated dirty files and do not switch branches automatically.
- No worktree, commit, push, stash, or `git add .`.
- Never reset, wipe, truncate, recreate, or destructively seed the development database.
- Database tests run only against confirmed `testing` / `cc_db_test`.
- Do not add ERAG, disposable-domain classification, DNS/MX validation, SMTP probing, or external validation APIs.
- Preserve generated child-account verification, Gmail literal identity behavior, password policy, roles, notifications, and existing authentication controls.

## Review Focus

- Temporary, privacy-relay, custom, and non-resolving domains pass syntax validation without network access.
- Normalization applies to every account-email write and lookup.
- Generated child accounts remain usable without a deliverable inbox.
- Database collation and duplicate-key races produce safe, friendly behavior.
- Rate limits use correct proxy IPs and shared production cache.

### Task 0: Safety and data preflight

**Files:** None.

- [ ] Record branch, status, HEAD, and scoped existing diffs.
- [ ] Inspect `users.email` column, collation, unique index, legacy normalization, and normalized collisions with read-only queries.
- [ ] Confirm test environment and connected database are exactly `testing` and `cc_db_test` before database tests.
- [ ] Stop for a separate remediation decision if collisions or incompatible collation are found.

### Task 1: Provider-neutral validation and regression coverage

**Modify:** Existing learner, guardian, and admin validation paths and their focused tests.

- [ ] Add passing cases for Gmail, Yahoo, Outlook, school, custom, privacy-relay, temporary, and syntactically valid `.invalid` addresses.
- [ ] Add malformed, duplicate, mixed-case, padded-input, and literal Gmail dot/tag/`googlemail.com` cases.
- [ ] Preserve the existing provisional removal of `ends_with:@gmail.com` and `email:rfc,dns`.
- [ ] Confirm no external email service is contacted by tests.

### Task 2: Central normalization and duplicate-race handling

**Create:** `app/Support/AccountEmail.php`, `tests/Unit/Support/AccountEmailTest.php`.

**Interface:** `normalize(string): string` and `fingerprint(string): string`.

**Modify:** `User`, registration/profile/admin requests, login, reset, and connector invitation lookup paths.

- [ ] Normalize before validation, lookup, write, and uniqueness checks.
- [ ] Add a write-time User email mutator as defense-in-depth.
- [ ] Use keyed HMAC fingerprints for email-based throttles; never persist raw fingerprints as email data.
- [ ] Catch only the MySQL `users.email` duplicate-key race and convert it to the existing friendly duplicate error; rethrow unrelated database errors.
- [ ] Add unit, registration, login, reset, invitation, and admin-management regression tests.

### Task 3: Email-change ownership lifecycle

**Modify:** Profile requests/controllers/views, admin profile controller/request, admin user-management service/controller, and admin/instructor route middleware.

- [ ] Require current password for self-service email changes.
- [ ] Null verification and send a new verification notification after ordinary email changes.
- [ ] Make admin-created ordinary accounts unverified and send verification after creation.
- [ ] Reverify admin-managed email changes.
- [ ] Preserve guardian-generated child accounts as guardian-attested/system-verified.
- [ ] Add verified middleware to protected admin and instructor groups and test unverified redirects.

### Task 4: Password-reset privacy

**Modify:** Password reset controllers and `PasswordResetTest`.

- [ ] Normalize reset input.
- [ ] Keep Laravel's 60-minute expiry and 60-second broker throttle.
- [ ] Return the same public response for existing and unknown accounts.
- [ ] Confirm valid reset delivery, invalid-token handling, and no notification for unknown addresses.

### Task 5: Native registration, resend, and reset throttles

**Modify:** `AppServiceProvider`, `routes/auth.php`, and new endpoint-throttle tests.

- [ ] Add registration-step `30/minute/IP`.
- [ ] Add final registration `10/minute/IP`, `50/hour/IP`, and `3/hour/email fingerprint`.
- [ ] Add verification resend `3/15 minutes/account` and `10/hour/IP`.
- [ ] Add reset `5/minute/IP`, `20/hour/IP`, and `3/hour/email fingerprint` while retaining the broker throttle.
- [ ] Verify proxy configuration and shared cache assumptions.

### Task 6: Provider-neutral UI and recovery warning

**Modify:** Existing registration, FAQ, verification, policy, and copy-regression files.

- [ ] Preserve the current UI structure and Alpine behavior.
- [ ] Remove Gmail eligibility wording and placeholders.
- [ ] Add: “Use an email you can keep accessing. Temporary inboxes may expire and prevent future password recovery.”
- [ ] State that temporary/disposable addresses are accepted.
- [ ] Show the Gmail shortcut only for an exact case-insensitive `@gmail.com` suffix.

### Task 7: Verification and handoff

- [ ] Run syntax checks, focused tests, broader authentication/profile/admin tests, and the full PHPUnit suite only against `cc_db_test`.
- [ ] Run a scoped Gmail/ERAG/DNS/domain-restriction sweep.
- [ ] Run `git diff --check` and review only owned paths.
- [ ] Report test results and any blocked database verification; do not commit or publish.
