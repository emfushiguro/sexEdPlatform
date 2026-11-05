# Direct Instructor Application Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Let an adult prospective instructor create an applicant-only account, complete Persona Government ID plus Selfie evidence and an administrator identity review, then submit the existing instructor application; only the final application approval grants instructor access.

**Architecture:** Extend the current user/Spatie role bridge, generic profile, Persona attempt ledger, signed webhook reconciliation, learner identity review patterns, and instructor application service. Add one local instructor-identity aggregate with an append-only audit trail, keep provider state/local identity review/application state/account state/role separate, and route every transition through transaction-locked services. Existing adult learners reuse their approved learner identity decision; direct applicants never receive a learner profile or learner permissions.

**Tech Stack:** PHP 8.2+, Laravel 12, Eloquent/MySQL, Blade, Alpine.js, Tailwind CSS, Vite, Spatie Laravel Permission, PHPUnit 11, Persona API `2026-09-29`, Playwright Test, and axe-core for browser accessibility checks.

**Approved design:** [`docs/superpowers/specs/2026-10-31-direct-instructor-application-design.md`](../specs/2026-10-31-direct-instructor-application-design.md)

## Global Constraints

- Work in the current checkout. Do not create a worktree, overwrite current Persona/relationship work, or stage unrelated files.
- Before editing any currently modified file, reread its current on-disk contents and inspect the focused diff. CodeGraph may lag current uncommitted changes.
- Never run `migrate:fresh`, `db:wipe`, destructive seeders, broad deletes, table truncation, database recreation, or rollback of history-bearing migrations.
- Use additive forward migrations. Their `down()` methods must refuse destructive rollback with `LogicException` once identity, audit, or document history can exist.
- Run database tests serially against the configured isolated `cc_db_test` database with `--do-not-cache-result`. Never point PHPUnit or Playwright at the development database.
- Keep direct entry points behind `DIRECT_INSTRUCTOR_APPLICATIONS_ENABLED=false` by default. Apply identity enforcement only from the explicit `INSTRUCTOR_IDENTITY_ENFORCED_FROM` cutover.
- Treat Persona Sandbox as simulated workflow evidence. A provider success never writes local identity `approved`, unlocks Stage Two, or grants a role.
- Do not persist raw Persona responses, raw webhook bodies, document numbers, biometric material, signed evidence URLs, provider image bytes, or raw comparison values.
- Keep API keys, webhook secrets, template IDs, session tokens, storage paths, and authenticated browser state out of views, JavaScript, logs, screenshots, traces, fixtures, commits, and reports.
- Use server-derived city/barangay labels and relationships. The only allowed province remains Cavite (`402100000`).
- Store every new instructor document on the private `local` disk. Legacy public files remain readable only through an authorized controller while migration is completed.
- Use thin controllers, Form Requests, policies, explicit route ownership, service-layer transitions, `DB::transaction()`, row locks, and `DB::afterCommit()` notifications.
- Use test-first red/green cycles. Each task starts with the narrow failing test and ends with a focused regression run and a small commit.
- Do not claim WCAG, privacy, security, biometric, or production Persona compliance. Accessibility work targets the requested behavior and is reported as tested behavior only.

## Non-Negotiable State Invariants

| Concern | Invariant |
| --- | --- |
| Direct account | Primary role and legacy role are `applicant`; there is no learner profile, learner role, or learner permission. |
| Existing learner | Keeps the learner account while pending and reuses the current locally approved learner identity case. |
| Persona inquiry | Uses the server-selected GovID plus Selfie template and belongs to exactly one local identity context. |
| Persona completion | May produce `pending_review` or `action_required`; it can never produce local `approved`. |
| Stage One | Only an authorized administrator can approve, reject, or request action. |
| Stage Two | Requires an access-eligible local Stage One approval for post-cutover submissions. |
| Application | `draft`, `pending`, `approved`, and `rejected` remain separate from identity and account state. |
| Final activation | Only approval of a locked pending application assigns `instructor` and creates/updates the instructor profile. |
| Sandbox | Sandbox-derived approval is labelled and remains fail-closed when the applicable manual-review flag is disabled. |
| Legacy data | Existing instructors and pre-cutover pending/reviewed applications remain operable and are not rewritten by migration. |

## Provider Field and Outcome Rules

- Implementation must be checked against the matching official Persona references before changing provider parsing or hosted-flow behavior:

  - Hosted Flow resume: <https://docs.withpersona.com/2026-09-29/resuming-inquiries>
  - Webhook security/idempotency guidance: <https://docs.withpersona.com/webhooks-best-practices>
  - Retrieve Government ID Verification: <https://docs.withpersona.com/2026-09-29/api-reference/verifications/retrieve-a-verification>
  - Documented inquiry fields: <https://docs.withpersona.com/inquiry-fields>

- Continue using the current Persona API version and the current documented provider statuses/policy mapping. Do not add browser-supplied provider statuses.
- Allowlist only documented Government ID verification attributes needed for review: `name-first`, `name-middle`, `name-last`, `birthdate`, documented city/subdivision address fields, `country-code`, and `id-class`.
- Treat suffix as `not_comparable` unless the configured Persona response supplies a documented corresponding attribute. Do not infer a suffix from another name field.
- Never read, persist, display, or log `document-number` for this feature.
- Store only field name, comparison outcome, and normalization version. Valid outcomes are `match`, `mismatch`, `missing_from_applicant`, `missing_from_persona`, `unavailable`, and `not_comparable`.
- Normalize trim/case/repeated whitespace/harmless name punctuation and exact parsed dates only. Do not use fuzzy matching, phonetic matching, edit distance, or silent date correction.
- Map provider evidence to internal workflow states without calling mismatch, missing data, failure, or inconclusive evidence fraud.
- Use this exact internal mapping for the instructor context:

  | Existing evaluated outcome | Required local effect |
  | --- | --- |
  | `not_ready` while the attempt is current | Remain `verification_in_progress`. |
  | `eligible` with provider completion and both required typed resources | Move to `pending_review`; never approve. |
  | Existing `sandbox_review` outcome with the current manual-review flag and required resources | Move to `pending_review` with durable Sandbox marker; never approve automatically. |
  | `inconclusive` with authoritative typed resources available for human review | Move to `pending_review` with missing/unavailable comparison outcomes; never approve automatically. |
  | `retryable`, expired/failed, missing required typed resources, or ordinary `ineligible`/declined evidence | Move to `action_required` with a neutral reason and controlled replacement path. |

- Administrator approval must still refuse unsupported country/document policy unless the existing Sandbox manual-review pathway explicitly marks the attempt reviewable and all current reviewer confirmations are present.
- Identity enforcement is exact: every direct applicant requires approved Stage One regardless of cutover; an existing learner submission requires Stage One when its `submitted_at` is on/after `INSTRUCTOR_IDENTITY_ENFORCED_FROM`. Existing applications already pending/approved/rejected before cutover remain grandfathered.

## File Map

### New production files

- `config/instructor_application.php` — feature flag, cutover, age, Cavite code, private disk, and safe upload limits.
- `database/migrations/2026_10_31_000000_add_direct_instructor_account_support.php` — applicant legacy-role support and nullable generic-profile PSGC/address fields.
- `database/migrations/2026_10_31_000100_create_instructor_identity_verification_tables.php` — local Stage One cases and append-only audits.
- `database/migrations/2026_10_31_000200_link_instructor_identity_and_private_documents.php` — Persona/application links, draft support, submission timestamp, nullable legacy ID path, and document disk.
- `app/Models/InstructorIdentityVerification.php`
- `app/Models/InstructorIdentityAudit.php`
- `app/Policies/InstructorIdentityVerificationPolicy.php`
- `app/Policies/InstructorApplicationPolicy.php`
- `app/Support/AuthenticatedHomeRouteResolver.php`
- `app/Services/Location/CaviteAddressResolver.php`
- `app/Services/InstructorApplication/InstructorApplicantPersonalInformation.php`
- `app/Services/InstructorApplication/InstructorApplicantEligibility.php`
- `app/Services/InstructorApplication/InstructorIdentityComparison.php`
- `app/Services/InstructorApplication/InstructorIdentityService.php`
- `app/Services/InstructorApplication/InstructorIdentityReviewEvidence.php`
- `app/Services/InstructorApplication/InstructorApplicationDocumentStorage.php`
- `app/Services/Persona/PersonaGovernmentIdAttributes.php`
- `app/Http/Middleware/EnsureDirectInstructorApplicationsEnabled.php`
- `app/Http/Controllers/Auth/InstructorApplicantRegistrationController.php`
- `app/Http/Controllers/InstructorApplication/DashboardController.php`
- `app/Http/Controllers/InstructorApplication/PersonalInformationController.php`
- `app/Http/Controllers/InstructorApplication/PersonaIdentityController.php`
- `app/Http/Controllers/InstructorApplication/ApplicationController.php`
- `app/Http/Controllers/InstructorApplication/DocumentController.php`
- `app/Http/Controllers/Admin/InstructorIdentityVerificationController.php`
- `app/Http/Requests/Auth/StoreInstructorApplicantPersonalInformationRequest.php`
- `app/Http/Requests/Auth/StoreInstructorApplicantAccountRequest.php`
- `app/Http/Requests/InstructorApplication/UpdatePersonalInformationRequest.php`
- `app/Http/Requests/InstructorApplication/StartPersonaInquiryRequest.php`
- `app/Http/Requests/InstructorApplication/SaveInstructorApplicationDraftRequest.php`
- `app/Http/Requests/Admin/ApproveInstructorIdentityRequest.php`
- `app/Http/Requests/Admin/RejectInstructorIdentityRequest.php`
- `app/Http/Requests/Admin/RequestInstructorIdentityActionRequest.php`
- `app/Notifications/Admin/InstructorIdentitySubmittedNotification.php`
- `app/Notifications/InstructorIdentityStatusNotification.php`
- `app/Console/Commands/MigrateInstructorApplicationDocuments.php`
- `resources/views/landing/instructor-partner.blade.php`
- `resources/views/auth/instructor-applicant-register-personal.blade.php`
- `resources/views/auth/instructor-applicant-register-account.blade.php`
- `resources/views/instructor-application/dashboard.blade.php`
- `resources/views/instructor-application/personal-information.blade.php`
- `resources/views/instructor-application/form.blade.php`
- `resources/views/instructor-application/submitted.blade.php`
- `resources/views/instructor-application/partials/stage-tracker.blade.php`
- `resources/views/admin/instructor-applications/identities/index.blade.php`
- `resources/views/admin/instructor-applications/identities/show.blade.php`
- `resources/js/instructor-application.js`
- `docs/direct-instructor-application-operations.md`
- `docs/superpowers/verification/2026-10-31-direct-instructor-application.md`

### Existing production files to modify

- `app/Models/User.php`, `app/Models/UserProfile.php`, `app/Models/PersonaInquiry.php`, and `app/Models/InstructorApplication.php`
- `app/Services/Admin/RoleSyncService.php`, `app/Services/InstructorApplicationService.php`, and `app/Services/AdminDashboardService.php`
- `app/Services/Persona/PersonaTemplateSelector.php`, `PersonaInquiryService.php`, `PersonaInquirySynchronizer.php`, `PersonaPolicyEvaluator.php`, and `PersonaReviewSubmission.php`
- `app/Http/Controllers/Admin/InstructorApplicationController.php` and `app/Http/Controllers/Admin/PersonaEvidenceController.php`
- `app/Http/Controllers/Auth/AuthenticatedSessionController.php`, `EmailVerificationPromptController.php`, `EmailVerificationNotificationController.php`, and `ConfirmablePasswordController.php`
- `app/Http/Requests/SubmitInstructorApplicationRequest.php`, `ApproveInstructorApplicationRequest.php`, and `RejectInstructorApplicationRequest.php`
- `app/Support/NotificationDeepLinkResolver.php`
- `app/Notifications/InstructorApplicationSubmitted.php` and `InstructorApplicationStatusUpdate.php`
- `app/Providers/AppServiceProvider.php`, `bootstrap/app.php`, `routes/web.php`, and `routes/admin.php`
- `database/seeders/PermissionSeeder.php`, `RoleSeeder.php`, and `RolePermissionSeeder.php`
- `resources/views/landing/index.blade.php`, `resources/views/layouts/landing.blade.php`, `resources/views/layouts/admin.blade.php`, and `resources/views/layouts/learner-header.blade.php`
- `resources/views/admin/instructor-applications/index.blade.php`, `show.blade.php`, `_approve-modal.blade.php`, and `_reject-modal.blade.php`
- `resources/views/admin/parent-verifications/partials/persona-summary.blade.php`
- `resources/views/emails/instructor-application-status.blade.php`
- `resources/js/app.js`, `.gitignore`, `package.json`, `package-lock.json`, and `phpunit.xml`
- Move the reusable content from `resources/views/learner/instructor-application/form.blade.php` and `submitted.blade.php` to the role-neutral views above; keep legacy routes as compatibility redirects/aliases.
- `docs/persona-identity-verification-sandbox-runbook.md`

### New and updated tests

- `tests/Feature/InstructorApplication/DirectInstructorSchemaTest.php`
- `tests/Feature/InstructorApplication/ApplicantAccountClassificationTest.php`
- `tests/Feature/InstructorApplication/ApplicantRegistrationTest.php`
- `tests/Feature/InstructorApplication/ApplicantRoutingAuthorizationTest.php`
- `tests/Feature/InstructorApplication/InstructorIdentityWorkflowTest.php`
- `tests/Feature/InstructorApplication/InstructorPersonaIntegrationTest.php`
- `tests/Feature/InstructorApplication/InstructorApplicationDraftTest.php`
- `tests/Feature/InstructorApplication/InstructorApplicationDocumentAccessTest.php`
- `tests/Feature/InstructorApplication/InstructorApplicationActivationTest.php`
- `tests/Feature/Admin/InstructorIdentityVerificationAdminTest.php`
- `tests/Unit/Services/InstructorIdentityComparisonTest.php`
- `tests/Unit/Services/CaviteAddressResolverTest.php`
- `tests/Unit/Services/InstructorApplicationDocumentStorageTest.php`
- Update the existing Persona, webhook, instructor submission/approval, admin UI, notification-navigation, learner registration, guardian, dependent, and relationship tests named in the tasks below.
- `tests/JavaScript/instructor-application.test.mjs`
- `playwright.config.mjs`
- `tests/Browser/Support/global-setup.mjs`
- `tests/Browser/Support/prepare-direct-instructor-fixtures.php`
- `tests/Browser/Support/auth.setup.mjs`
- `tests/Browser/fixtures/instructor-application.mjs`
- `tests/Browser/direct-instructor-public.spec.mjs`
- `tests/Browser/direct-instructor-applicant.spec.mjs`
- `tests/Browser/direct-instructor-admin.spec.mjs`
- `tests/Browser/direct-instructor-authorization.spec.mjs`
- `tests/Browser/direct-instructor-accessibility.spec.mjs`

---

## Task 0: Protect the Dirty Checkout and Establish the Baseline

**Files:**

- Inspect: `git status`, the approved design, current migrations, and focused existing tests.
- Create during execution: `docs/superpowers/verification/2026-10-31-direct-instructor-application.md` with an initial baseline section.

- [ ] Record `git status --short` and identify every pre-existing modified/untracked path. Mark those paths as user-owned; do not stage them as part of this feature unless a later task intentionally edits the same file.
- [ ] Confirm the test connection before any database test:

  ```powershell
  Select-String -Path phpunit.xml -Pattern 'DB_DATABASE.*cc_db_test'
  ```

  Expected: exactly the isolated `cc_db_test` entry.

- [ ] Run the current focused baseline without changing code:

  ```powershell
  php vendor/bin/phpunit --do-not-cache-result tests/Feature/InstructorApplicationSubmissionTest.php tests/Feature/InstructorApplicationApprovalTest.php tests/Feature/Admin/AdminInstructorApplicationsUiTest.php tests/Feature/Identity/PersonaLearnerIntegrationTest.php tests/Feature/Auth/PersonaGuardianIntegrationTest.php tests/Feature/DependentSupport/DependentSupportRegistrationTest.php
  ```

  Expected: record actual pass/fail results. Existing failures are baseline findings, not silently attributed to this feature.

- [ ] Run `php artisan migrate:status` only as a read-only inventory. Record all pending migrations; do not run broad `php artisan migrate` while unrelated user-owned migrations are pending.
- [ ] Add the commands, timestamp, database name, and actual results to the verification document.

## Task 1: Add Forward-Only Schema, Configuration, and Model Relationships

**Files:**

- Create: the three `2026_10_31_*` migrations, `config/instructor_application.php`, `InstructorIdentityVerification.php`, `InstructorIdentityAudit.php`, and `DirectInstructorSchemaTest.php`.
- Modify: `User.php`, `UserProfile.php`, `PersonaInquiry.php`, `InstructorApplication.php`, and `phpunit.xml` only as needed for stable test defaults.

- [ ] Write `DirectInstructorSchemaTest` first. Assert the new tables/columns/index-backed relationships exist, existing application rows can remain pending without identity links, encrypted casts round-trip, and the default feature flag is off.
- [ ] Add a model test that creates two rounds for one user, rejects a duplicate `(user_id, submission_round)`, and confirms audit rows are append-only through model events (`updating` and `deleting` throw `LogicException`).
- [ ] Run the new test and confirm it fails because the schema/models do not exist:

  ```powershell
  php vendor/bin/phpunit --do-not-cache-result tests/Feature/InstructorApplication/DirectInstructorSchemaTest.php
  ```

- [ ] Implement `2026_10_31_000000_add_direct_instructor_account_support.php`:

  - expand the MySQL `users.role` enum to include `applicant` without changing existing values/defaults;
  - add nullable `province_code`, `city_code`, `barangay_code`, and `address_line` to `user_profiles`;
  - add explicit foreign keys to PSGC tables and bounded index/constraint names; and
  - use a non-destructive `down(): never` that explains why applicant/profile history cannot be silently removed.

- [ ] Implement `2026_10_31_000100_create_instructor_identity_verification_tables.php` with:

  - `instructor_identity_verifications`: user/source/learner source link/status/round/encrypted declared snapshot/encrypted comparison summary/Sandbox marker/resubmission flag/reviewer/timestamps/reason/requested actions/supersession;
  - unique `(user_id, submission_round)` plus review-queue and current-case lookup indexes;
  - `instructor_identity_audits`: case/actor/action/from/to/round/Persona attempt/reason/encrypted privacy-minimized metadata/created timestamp; and
  - a refusal-only `down()`.

- [ ] Implement `2026_10_31_000200_link_instructor_identity_and_private_documents.php`:

  - add nullable `instructor_identity_verification_id` to `persona_inquiries` and `instructor_applications` using short explicit constraint names;
  - allow `draft` in the application enum without changing existing row values;
  - add nullable `submitted_at` and `document_disk` defaulting to `public` for legacy compatibility;
  - make `government_id_path` nullable while leaving every existing value untouched; and
  - refuse destructive rollback.

- [ ] Add model constants and typed relationships. Use `encrypted:array` on declared/comparison snapshots, `array` on requested action codes, booleans/datetimes on workflow fields, and named query scopes (`current`, `pendingReview`, `accessApproved`).
- [ ] Add `User::ACCOUNT_TYPE_APPLICANT`, `isInstructorApplicant()`, identity relationships, and generic-profile PSGC relationships. Ensure `isLearner()` remains false for an applicant.
- [ ] Add `config/instructor_application.php` with these server-owned values:

  ```text
  enabled = DIRECT_INSTRUCTOR_APPLICATIONS_ENABLED (default false)
  identity_enforced_from = INSTRUCTOR_IDENTITY_ENFORCED_FROM (default null)
  minimum_age = 18
  province_code = 402100000
  document_disk = local
  max_upload_kilobytes = 5120
  ```

- [ ] Run the schema test again; expected: pass.
- [ ] Run legacy schema/Persona tests; expected: pass with old records still valid:

  ```powershell
  php vendor/bin/phpunit --do-not-cache-result tests/Feature/Admin/InstructorApplicationRejectionReasonTest.php tests/Feature/Identity/PersonaSchemaTest.php
  ```

- [ ] Commit only owned Task 1 paths. Stage new files normally, but stage only feature-owned hunks from any path that was already dirty before this task; never whole-file stage `User.php`, `PersonaInquiry.php`, or another overlapping user-owned file:

  ```powershell
  git add config/instructor_application.php database/migrations/2026_10_31_000000_add_direct_instructor_account_support.php database/migrations/2026_10_31_000100_create_instructor_identity_verification_tables.php database/migrations/2026_10_31_000200_link_instructor_identity_and_private_documents.php app/Models/InstructorIdentityVerification.php app/Models/InstructorIdentityAudit.php tests/Feature/InstructorApplication/DirectInstructorSchemaTest.php
  # Review overlapping diffs, then stage only the feature hunks with an index-only patch.
  git diff -- app/Models/User.php app/Models/UserProfile.php app/Models/PersonaInquiry.php app/Models/InstructorApplication.php
  git commit -m "feat: add instructor identity schema"
  ```

## Task 2: Add Applicant RBAC and Account-Aware Redirects

**Files:**

- Create: `app/Support/AuthenticatedHomeRouteResolver.php`, `ApplicantAccountClassificationTest.php`, and `ApplicantRoutingAuthorizationTest.php`.
- Modify: `RoleSyncService.php`, the three RBAC seeders, `User.php`, the four auth redirect controllers, and the authenticated `/` branch in `routes/web.php`.

- [ ] Write failing tests proving:

  - `RoleSyncService::assignPrimaryRole($user, 'applicant')` writes both the Spatie role and legacy column as applicant;
  - the applicant role has no permissions;
  - an applicant is not a learner/instructor/admin and cannot open learner/instructor/admin routes;
  - applicant login, verified-email continuation, password confirmation, and `/` resolve to `instructor-application.dashboard`; and
  - current learner/instructor/admin redirects are unchanged.

- [ ] Run the tests and confirm applicant currently falls through to learner behavior:

  ```powershell
  php vendor/bin/phpunit --do-not-cache-result tests/Feature/InstructorApplication/ApplicantAccountClassificationTest.php tests/Feature/InstructorApplication/ApplicantRoutingAuthorizationTest.php
  ```

- [ ] Add applicant to `RoleSyncService::LEGACY_ROLE_VALUES`. Do not map it to learner.
- [ ] Add `review instructor identities` and `review instructor applications` permissions. Create an applicant role with an empty permission set; keep admin synchronization to all canonical permissions.
- [ ] Implement `AuthenticatedHomeRouteResolver::routeName(User $user): string` with order: admin, instructor, applicant, parent/learner. Reuse it in all four auth controllers and the root route so no controller keeps an applicant-to-learner fallback.
- [ ] Preserve the administrator-login separation already enforced by `AuthenticatedSessionController`.
- [ ] Run the two new tests and the existing auth registration/login suite; expected: pass.
- [ ] Commit owned paths with `feat: add instructor applicant account role`.

## Task 3: Implement Adult Direct Registration and Cavite Personal Information

**Files:**

- Create: `CaviteAddressResolver.php`, registration controller/requests/views, personal-information controller/request/view, feature middleware, and registration/location tests.
- Modify: `bootstrap/app.php`, `routes/web.php`, `UserProfile.php`, and `app/Http/Controllers/Api/LocationController.php` only if a response reuse is necessary.

- [ ] Write `CaviteAddressResolverTest` for a valid Cavite pair, non-Cavite city, unknown city, unknown barangay, and barangay belonging to a different city.
- [ ] Write `ApplicantRegistrationTest` for:

  - age 17 rejected and exact age 18 accepted using birthdate, not a client age value;
  - duplicate email rejected with sign-in guidance;
  - city/barangay labels ignored and canonical labels derived from PSGC rows;
  - invalid city/barangay pair rejected server-side;
  - successful creation uses explicit active applicant classification, sends normal email verification, creates one generic profile, and creates no learner profile/learner identity case/instructor profile/application; and
  - requests fail closed while the feature flag is false.

- [ ] Run the two tests and confirm the new routes/services are missing.
- [ ] Implement `CaviteAddressResolver::resolve(string $cityCode, string $barangayCode): array` to query `cities` and `barangays`, enforce province `402100000`, enforce the parent-child relation, and return canonical codes/names. Convert domain failures to field-specific Form Request errors.
- [ ] Implement the two-step direct registration by reusing the current registration/session style:

  1. personal identity and Cavite address data are validated and held in the session;
  2. email/password are validated with the current account rules;
  3. one transaction creates the user and generic profile, assigns applicant through `RoleSyncService`, refreshes classification, dispatches Laravel's `Registered` event, authenticates the user, and clears the session data.

- [ ] Require first/last name, birthdate, Cavite city, and barangay; keep middle name, suffix, gender, contact, and address line optional. Do not silently rewrite declared names.
- [ ] Add applicant personal-information edit/update routes for corrections before the current Persona attempt starts. After the snapshot is frozen, material identity changes must direct the user to a replacement-attempt action rather than mutate the snapshot.
- [ ] Register `direct.instructor.enabled` in `bootstrap/app.php`; use it on direct registration and direct-applicant workflow routes. Return 404/disabled guidance without leaking partial routes when off.
- [ ] Render explicit labels, required/optional hints, `autocomplete` attributes, linked field errors, an error summary with `role="alert"`, and focus the first invalid field.
- [ ] Run the new tests and `tests/Feature/Auth/RegistrationTest.php`; expected: all pass and learner registration behavior remains unchanged.
- [ ] Commit owned paths with `feat: add direct instructor registration`.

## Task 4: Implement the Local Stage One Domain and Learner Identity Reuse

**Files:**

- Create: applicant personal-information resolver, eligibility service, comparison service, identity service, identity policy, unit/feature tests, dashboard controller/view, and stage tracker partial.
- Modify: `User.php`, `LearnerIdentityVerification.php` only if an existing access-eligibility method must be exposed, and `routes/web.php`.

- [ ] Write `InstructorIdentityComparisonTest` first. Cover exact and normalized matches, materially different names, exact date mismatch, missing applicant/provider values, unavailable values, documented address fields, suffix `not_comparable`, and proof that raw values/document numbers are absent from the returned summary.
- [ ] Write `InstructorIdentityWorkflowTest` for every allowed transition and forbidden transition, duplicate-current-case convergence, stale round rejection, auditable actor/status changes, and no role changes.
- [ ] Add learner-reuse cases:

  - approved current adult learner identity creates/resolves an approved instructor identity source without a new Persona attempt;
  - under-18 learner is denied;
  - pending/rejected/superseded learner identity does not unlock Stage Two and redirects to the existing learner identity flow;
  - learner Sandbox marker is propagated; and
  - repeated entry returns the same local case/audit outcome instead of duplicating it.

- [ ] Run the tests and confirm they fail before services exist.
- [ ] Implement `InstructorApplicantPersonalInformation::for(User $user): array`:

  - direct applicant reads names/birthdate from `users` and address/contact from `user_profiles`;
  - learner reads the same identity fields plus `learner_profiles` PSGC values; and
  - no copying between profile tables is performed.

- [ ] Implement `InstructorApplicantEligibility` to enforce feature state, active account, verified email where required, age 18+, supported account kind, no existing instructor/admin access, and no duplicate active application. Keep read checks here, but repeat critical checks under locks in transition services.
- [ ] Implement `InstructorIdentityComparison` as a pure service returning only allowlisted outcome metadata and normalization version `v1`.
- [ ] Implement `InstructorIdentityService` methods with transactions and user/case row locks:

  ```php
  current(User $user): ?InstructorIdentityVerification
  ensureAwaitingVerification(User $user): InstructorIdentityVerification
  satisfyFromApprovedLearnerIdentity(User $user): InstructorIdentityVerification
  startAttempt(User $user, array $declaredSnapshot): InstructorIdentityVerification
  markPendingReview(InstructorIdentityVerification $case, PersonaInquiry $attempt, array $comparison): void
  markActionRequired(InstructorIdentityVerification $case, PersonaInquiry $attempt, string $reasonCode): void
  approve(InstructorIdentityVerification $case, User $reviewer, array $confirmations): void
  reject(InstructorIdentityVerification $case, User $reviewer, string $reasonCode, ?string $note): void
  requestAction(InstructorIdentityVerification $case, User $reviewer, array $actions, ?string $note): void
  ```

- [ ] Enforce the approved transition matrix, append one audit row per transition, store privacy-minimized metadata only, and make repeated provider/admin deliveries idempotent.
- [ ] Implement the applicant dashboard as a status hub. It may call learner reuse, but it must never auto-approve a direct Persona case.
- [ ] Run the new unit/feature tests and existing learner identity requirement tests; expected: pass.
- [ ] Commit owned paths with `feat: add instructor identity workflow`.

## Task 5: Extend Persona with an Explicit Instructor Context

**Files:**

- Create: `PersonaGovernmentIdAttributes.php`, instructor Persona controller/request, and `InstructorPersonaIntegrationTest.php`.
- Modify: `PersonaInquiry.php`, the five Persona services, `PersonaEvidenceController.php`, `routes/web.php`, `PersonaWebhookTest.php`, `PersonaSingleSubmissionTest.php`, `PersonaPolicyTest.php`, and `PersonaClientTest.php` only where coverage requires it.

- [ ] Write failing tests proving:

  - inquiry creation uses `PERSONA_GOVID_SELFIE_TEMPLATE_ID`, an instructor flow constant, the authenticated applicant reference, local identity case, stable idempotency key, and the selected supported Philippine ID mapping;
  - template IDs and workflow status cannot be submitted by the browser;
  - one resumable current attempt is reused and concurrent/repeated starts converge;
  - return/refresh always refetch authoritative server data and a forged inquiry ID/redirect parameter cannot unlock anything;
  - provider eligible/completed evidence moves the local case only to `pending_review`;
  - retryable/expired/missing required resources move to `action_required` without a fraud label;
  - inconclusive/ineligible evidence remains explicitly reviewable or action-required according to the existing policy result and cannot be approved implicitly;
  - invalid signatures are rejected, duplicate event IDs are acknowledged idempotently, and older event timestamps cannot regress state; and
  - no raw provider values are persisted in the comparison summary.

- [ ] Run the focused test and confirm the instructor context is unsupported:

  ```powershell
  php vendor/bin/phpunit --do-not-cache-result tests/Feature/InstructorApplication/InstructorPersonaIntegrationTest.php tests/Feature/Api/PersonaWebhookTest.php
  ```

- [ ] Add `PersonaInquiry::FLOW_INSTRUCTOR_GOVERNMENT` and an `instructorIdentityVerification()` relationship. Preserve every current learner/guardian constant and behavior.
- [ ] Add explicit instructor entry methods to `PersonaInquiryService` (`startForInstructor`, `resumeForInstructor`, `returnForInstructor`, `refreshForInstructor`, and `replaceForInstructor`) that delegate to shared locked internals. Do not guess context from role alone.
- [ ] Extend `PersonaTemplateSelector` with an explicit instructor selection path that always resolves the configured GovID plus Selfie template on the server.
- [ ] Implement `PersonaGovernmentIdAttributes` to extract only the documented allowlist from the typed Government ID resource. Never include document number, raw image URLs, scores, or full resources.
- [ ] Extend `PersonaInquirySynchronizer` lock order to user → instructor identity case → Persona attempt, pass the in-memory typed verification resource into review submission/comparison, and retain current monotonic provider timestamp handling.
- [ ] Add an explicit instructor branch in `PersonaReviewSubmission`. It must call `InstructorIdentityService`; it must not share the learner status column, set approved, or assign a role.
- [ ] Keep current webhook raw-body signature validation, rotating secret support, event ledger, queued authoritative refetch, dedupe, and out-of-order protection unchanged except for recognizing the new associated context.
- [ ] Add same-tab hosted flow, resume, return, refresh, and replace routes under `instructor-application.identity.persona.*`. All require auth, verified email, feature middleware, policy ownership, and current-case checks.
- [ ] Extend the evidence controller only enough to authorize the new admin review context; continue server proxying with private/no-store responses.
- [ ] Run the new test plus all existing Persona unit/feature tests; expected: pass with learner and guardian flows unchanged.
- [ ] Commit owned paths with `feat: add instructor Persona verification context`.

## Task 6: Add Administrator Stage One Review, Audit, and Notifications

**Files:**

- Create: admin identity controller/requests/views, review-evidence service, identity notifications, policy, and admin test.
- Modify: `routes/admin.php`, `PermissionSeeder.php`, `RoleSeeder.php`, `AppServiceProvider.php`, `AdminDashboardService.php`, admin layout, Persona summary partial, and notification deep-link resolver.

- [ ] Write `InstructorIdentityVerificationAdminTest` for:

  - permission-protected queue/detail access;
  - declared data, provider policy outcome, per-field match state, source, Sandbox label, timestamps, and audit history;
  - on-demand allowlisted provider values with no persistence;
  - provider evidence proxy authorization/no-store behavior;
  - successful provider outcome still pending until explicit local approval;
  - approval, rejection with reason, and requested action/re-verification;
  - stale submission round/version conflict returning a reload message;
  - non-admin and admin-without-permission denial;
  - owner cannot approve self; and
  - each decision creates exactly one audit and one after-commit notification set.

- [ ] Run the test and confirm routes/controllers are absent.
- [ ] Build Identity Review as a tab inside the existing Instructor Applications workspace. Reuse the learner verification page's card, Persona summary, evidence proxy, moderation controls, status badge, history, confirmation, and responsive patterns.
- [ ] Use route-model binding plus policy checks. Scope index queries to pending/current cases and eager-load users/reviewers/attempts to avoid N+1 queries.
- [ ] Implement `InstructorIdentityReviewEvidence` so provider attributes exist only in the request lifecycle, are allowlisted, and are represented as unavailable if Persona cannot be reached. Do not serialize them into session flashes, cache, logs, notifications, or the database.
- [ ] Require reviewer confirmations for declared/provider review and the existing Sandbox acknowledgements. Deny approval when the current case/round is stale, account is inactive, age is below 18, or Sandbox eligibility is fail-closed.
- [ ] Add safe notification payloads containing only local case ID/type/status/reason label and a reconstructed internal destination. Extend `NotificationDeepLinkResolver` to verify recipient role/ownership from the local ID rather than trusting a persisted URL.
- [ ] Add pending identity count next to pending application count in the existing admin navigation/dashboard.
- [ ] Run the new admin test plus `PersonaReviewTest.php`, `PersonaEvidenceTest.php`, `VerificationNotificationNavigationTest.php`, and `AdminInstructorApplicationsUiTest.php`; expected: pass.
- [ ] Commit owned paths with `feat: add instructor identity admin review`.

## Task 7: Gate and Reuse Stage Two with Draft/Resume Support

**Files:**

- Create: shared application controller, save-draft request, application policy, role-neutral form/submitted views, and draft tests.
- Modify: `SubmitInstructorApplicationRequest.php`, `InstructorApplicationService.php`, `InstructorApplication.php`, learner header, email view, and `routes/web.php`.
- Remove after references migrate: `app/Http/Controllers/Learner/InstructorApplicationController.php` and the two learner-namespaced application views.

- [ ] Write `InstructorApplicationDraftTest` proving:

  - direct applicant and existing learner cannot GET, save, or submit Stage Two before access-eligible Stage One approval;
  - URL parameters, hidden fields, or client status values cannot bypass the gate;
  - an approved direct case and an approved learner-reuse case can open the same form with personal information prefilled/read-only instead of re-entered;
  - Save Draft accepts incomplete non-dangerous fields, resumes the same draft, and never notifies admins;
  - final submit applies the current qualification/document/declaration rules, requires at least one Tier 2 credential, and changes the locked draft to pending;
  - no second government-ID upload is required for an identity-linked post-cutover application;
  - pre-cutover legacy behavior remains valid and existing pending/rejected rows remain reviewable; and
  - duplicate draft/pending creation converges to one active application.

- [ ] Run the new test and confirm the current learner-only authorization fails direct applicants.
- [ ] Implement a shared `ApplicationController` with `edit`, `saveDraft`, `submit`, `submitted`, and `withdraw`. Keep existing `learner.instructor.*` route names as compatibility aliases/redirects while changing all project links to canonical `instructor-application.application.*` names.
- [ ] Refactor `SubmitInstructorApplicationRequest` to authorize through `InstructorApplicationPolicy` and `InstructorApplicantEligibility`; never trust application/user/identity IDs from form data.
- [ ] Implement separate draft and final validation. At final submission, merge already stored valid draft files with newly uploaded files before checking required clearance/CV/Tier 2 evidence.
- [ ] Refactor `InstructorApplicationService` into locked `saveDraft()` and `submit()` paths. Link the current approved identity, set `submitted_at` only on submit, prevent duplicate active rows, and send admin notifications only after commit.
- [ ] Preserve existing withdrawal/rejection/resubmission behavior. A withdrawal archives/soft-deletes the draft or pending application but does not delete audit history or assign/revoke roles.
- [ ] Move the existing form markup into role-neutral views and remove the duplicate government-ID field only for identity-linked submissions. Do not invent new mandatory qualifications.
- [ ] Run the new test and update/run `InstructorApplicationSubmissionTest.php`; expected: both direct and legacy paths pass.
- [ ] Commit owned paths with `feat: gate and resume instructor applications`.

## Task 8: Privatize Instructor Documents and Add Recoverable Legacy Migration Tooling

**Files:**

- Create: document storage service/controller, document policy coverage, migration command, storage tests, and document-access tests.
- Modify: `InstructorApplicationService.php`, `InstructorApplication.php`, Stage Two/admin views, and admin application controller.

- [ ] Write storage unit tests with `Storage::fake('local')` and `Storage::fake('public')` for unique generated names, MIME/size metadata, safe replacement, missing files, and legacy disk resolution.
- [ ] Write document feature tests proving:

  - new files are under `storage/app/private` through the `local` disk and no public URL is rendered;
  - owner and authorized reviewer can stream only permitted slots;
  - another applicant, pending/rejected applicant without ownership, and unauthenticated user receive denial;
  - route parameters cannot select arbitrary storage paths;
  - responses include `Cache-Control: private, no-store`, safe `Content-Disposition`, and `X-Content-Type-Options: nosniff`; and
  - invalid extensions/MIME/size are rejected before storage.

- [ ] Run the tests and confirm current public-disk URLs violate them.
- [ ] Implement `InstructorApplicationDocumentStorage` with an allowlisted slot-to-column map. Store new uploads to `local`; store generated names, disk, MIME, byte size, and original display name in application metadata; never use the original name as a path.
- [ ] Implement an authorized document controller using the application policy and slot allowlist. Update applicant/admin views to use this route for both private and legacy documents.
- [ ] Remove direct use of `Storage::disk('public')->url()` from the instructor review path. Keep legacy `document_disk=public` reads only through the controller until migration completes.
- [ ] Implement `instructor-applications:migrate-documents` as an operator command:

  - dry-run by default;
  - require `--commit` to copy;
  - support `--application-id=` and bounded `--chunk=`;
  - copy public → private, compare size/hash, then update `document_disk` in a transaction;
  - be idempotent and report missing/mismatched files without rewriting rows; and
  - expose source deletion only as a separate explicit `--delete-verified-source` phase that refuses unless the row points private and the verified copy matches. Never run deletion during implementation without current explicit user approval.

- [ ] Run command tests on fake disks and the focused document tests; expected: pass.
- [ ] Commit owned paths with `feat: protect instructor application documents`.

## Task 9: Harden Final Review and Atomic Instructor Activation

**Files:**

- Modify: `InstructorApplicationService.php`, admin controller/requests/modals, `RoleSyncService.php` if needed, application notifications, and existing approval/rejection tests.
- Create: `InstructorApplicationActivationTest.php`.

- [ ] Write activation tests proving:

  - Persona success and Stage One approval alone never grant instructor permissions;
  - only an authorized reviewer can approve a locked pending Stage Two application;
  - approval rechecks current identity access, age 18+, verified email, active account, and applicable Sandbox flag;
  - direct applicant transitions applicant → instructor, receives an instructor profile, and never receives a learner profile/role;
  - existing learner transitions through the current learner → instructor path while historical learner data remains;
  - rejected, withdrawn, abandoned, stale, duplicate, inactive, suspended, and Sandbox-ineligible cases receive no instructor access;
  - a pre-cutover legacy pending application can still be reviewed; and
  - any thrown activation error rolls back application/review/role-transition/profile changes together.

- [ ] Run the activation test plus existing approval test and confirm the preconditions/transaction behavior are not yet sufficient.
- [ ] Refactor `approve()` to accept the authenticated reviewer explicitly rather than reading hidden request state. Capture all required values in the transaction closure, lock application → user → linked identity, and reject non-pending/stale records.
- [ ] Inside one transaction: validate eligibility, create review and role-transition rows, assign instructor through `RoleSyncService`, update/create instructor profile, mark application approved, refresh account classification, and preserve learner history. Schedule notifications and subscription side effects after commit where failure must not corrupt approval.
- [ ] Keep application approval separate from account active state and role. Do not copy sensitive identity/application paths into instructor profile credentials.
- [ ] Put rejection under an equivalent application lock, preserve existing structured reason/resubmission behavior, create one review record, and notify after commit.
- [ ] Update `ApproveInstructorApplicationRequest` and `RejectInstructorApplicationRequest` to use `review instructor applications` plus model policy, current status, and stale-form tokens.
- [ ] Update confirmation copy to direct approved users to the current instructor onboarding/dashboard and clearly distinguish Sandbox workflow demonstrations.
- [ ] Run activation, submission, rejection-reason, notification, instructor route, and learner view-switch tests; expected: pass.
- [ ] Commit owned paths with `feat: activate instructors after final approval`.

## Task 10: Add Recruitment Entry Points and Finish the Accessible Workflow UI

**Files:**

- Create: recruitment page, UI JavaScript, and JavaScript test.
- Modify: landing page/layout, applicant dashboard/personal/application views, admin identity/application views, `resources/js/app.js`, and navigation headers.

- [ ] Before UI edits, invoke the frontend-design and accessibility skills. Reuse current Conscious Connections components/colors/type/spacing; do not introduce a parallel visual system.
- [ ] Write view/feature assertions for desktop/mobile CTA presence, feature-off hiding, factual two-stage copy, Start/Resume/Sign In actions, Sandbox disclosure, privacy summary, and the absence of unsupported compensation/employment/approval claims.
- [ ] Write `tests/JavaScript/instructor-application.test.mjs` for repeated-submit prevention, status/live-region updates, error-summary focus, dependent city/barangay reset, reduced-motion behavior, and draft-upload filename presentation.
- [ ] Run the PHP view assertions and JS test; confirm they fail before the page/script exists.
- [ ] Add restrained `Be Our Partner` links to desktop navigation, mobile navigation, one landing partner section, and footer. Hide or disable Start while the feature flag is off; direct authenticated users to their correct resume destination.
- [ ] Build the recruitment page around factual preparation and sequence: personal information, GovID plus Selfie evidence, administrator identity review, current instructor application, final administrator review. State that verification/application do not guarantee approval and Sandbox is simulated where applicable.
- [ ] Finish the applicant hub and stage tracker for awaiting verification, processing, pending review, approved, action required, rejected, Stage Two draft/pending/approved/rejected, and next actions.
- [ ] Apply WCAG-oriented behavior without claiming certification:

  - semantic headings/landmarks and associated labels;
  - skip link/main target if the chosen layout lacks one;
  - status text/icons in addition to color;
  - `aria-current="step"` or equivalent on the stage tracker;
  - `aria-invalid`/`aria-describedby` and a focusable error summary;
  - native buttons/links, visible focus, logical tab order, at least 24×24 CSS-pixel targets;
  - no keyboard traps; modal focus/Escape behavior where existing admin modals remain;
  - polite live regions for dynamic status and upload feedback;
  - no forced re-entry of valid existing personal information; and
  - `prefers-reduced-motion` support with no decorative autoplay.

- [ ] Disable submit controls only while the request is processing and restore them on client-side failure; server idempotency remains authoritative.
- [ ] Run the focused PHP/JS tests and `npm.cmd run build`; expected: pass without Vite errors.
- [ ] Commit owned paths with `feat: add instructor recruitment experience`.

## Task 11: Add Automated Playwright Infrastructure and Full-Stack Scenarios

**Files:**

- Create: Playwright config/support/fixtures/specs listed in the file map.
- Modify: `package.json`, `package-lock.json`, and `.gitignore`.

- [ ] Install test-only dependencies and commit the lockfile:

  ```powershell
  npm.cmd install --save-dev @playwright/test @axe-core/playwright
  npx.cmd playwright install chromium firefox
  ```

  Expected: package/lock updates and installed browser binaries. If installation is blocked by network/sandbox policy, request approval and report the gate rather than claiming browser coverage.

- [ ] Add scripts:

  ```json
  "test:browser": "playwright test",
  "test:browser:headed": "playwright test --headed",
  "test:browser:debug": "playwright test --debug=cli"
  ```

- [ ] Configure `playwright.config.mjs` with:

  - base URL default `http://127.0.0.1:8000` (never port 3000 for this project);
  - a non-reused Laravel `webServer` process that fails if port 8000 is already occupied, so tests cannot silently attach to a development server/database;
  - explicit server environment: `APP_ENV=testing`, the guarded `PLAYWRIGHT_DB_DATABASE`, `SESSION_DRIVER=file`, `CACHE_STORE=array`, `QUEUE_CONNECTION=sync`, `MAIL_MAILER=array`, `PERSONA_ENABLED=false`, direct feature enabled, and a fixed historical identity cutover;
  - an isolated Playwright config-cache path so a cached development configuration cannot override the guarded test environment;
  - HTML report that never auto-opens in automated runs;
  - trace on first retry, screenshot only on failure, video retained only on failure;
  - desktop Chromium and mobile Chromium projects;
  - Firefox public/critical-form smoke project; and
  - serial worker count for stateful workflow specs.

- [ ] Add a dedicated `cc_db_playwright_test` prerequisite. `global-setup.mjs` and the PHP fixture bootstrap must abort unless `APP_ENV=testing` and the resolved database name ends exactly in `_playwright_test`.
- [ ] Refuse to start if the browser-test database does not already exist. Document creating that isolated database as a one-time operator prerequisite; never create/drop/recreate a database from the test suite.
- [ ] In `prepare-direct-instructor-fixtures.php`:

  - run normal `artisan migrate --force` only against that guarded browser-test database, never `migrate:fresh`;
  - seed canonical roles/permissions in the isolated database;
  - create unique synthetic records keyed by `PW_RUN_ID` without deleting arbitrary rows;
  - prepare direct applicant, verified applicant states, approved learner identity, admin reviewer, unrelated owner, and approved instructor states;
  - prepare synthetic Persona/application states directly through factories/services, not public test-only endpoints; and
  - output only non-sensitive fixture aliases/credentials intended for the test process.

  Automated browser tests consume those prepared provider states and never call Persona. Inquiry creation, signature, dedupe, and authoritative synchronization remain covered by mocked PHPUnit feature tests; the real hosted flow is reserved for the separately reported Sandbox walkthrough.

- [ ] Add `.gitignore` entries for `playwright-report/`, `test-results/`, `.playwright/`, `tests/Browser/.auth/`, traces, videos, and storage-state files. Never commit session cookies.
- [ ] Implement public specs for desktop/mobile CTA, factual recruitment content, registration age boundary, duplicate email guidance, email-verification gate, and feature-off behavior.
- [ ] Implement applicant specs for login redirect, no learner/instructor access, learner identity reuse, PSGC UI plus manipulated server pair, all Stage One states, provider completion remaining locked, approved unlock, draft/resume, real file chooser upload, repeated-submit prevention, and Stage Two submission.
- [ ] Implement admin specs for identity queue/detail, comparison/missing-data presentation, Sandbox confirmations, approve/reject/action-required, application review, and final role activation.
- [ ] Implement authorization specs for cross-user identity/application/document URLs, non-admin decisions, stale forms, manipulated parameters, private headers, and rejected/abandoned privilege checks.
- [ ] Implement accessibility specs using role-based locators plus axe for critical pages; also assert keyboard-only navigation, visible focus, first-error focus, status text independent of color, mobile layout, and reduced-motion emulation. Treat axe as supplemental; retain manual keyboard checks.
- [ ] Run the focused automated suite with noninteractive reporting:

  ```powershell
  $env:PLAYWRIGHT_HTML_OPEN='never'
  $env:PLAYWRIGHT_DB_DATABASE='cc_db_playwright_test'
  npm.cmd run test:browser -- tests/Browser/direct-instructor-public.spec.mjs tests/Browser/direct-instructor-applicant.spec.mjs tests/Browser/direct-instructor-admin.spec.mjs tests/Browser/direct-instructor-authorization.spec.mjs tests/Browser/direct-instructor-accessibility.spec.mjs
  ```

  Expected: all scenarios pass or each failure is recorded with trace path. Do not fall back to live Persona.

- [ ] Debug failures with `npm.cmd run test:browser:debug -- <spec>`, attach using `playwright-cli`, and inspect console/requests/traces. Stop the paused debug process after diagnosis.
- [ ] Commit owned Playwright paths with `test: add direct instructor browser coverage`.

## Task 12: Assign the Supervised Playwright QA Agent

**Files the QA agent owns:**

- `tests/Browser/**`
- `playwright.config.mjs`
- browser-only fixture support
- `docs/superpowers/verification/2026-10-31-direct-instructor-application.md`
- generated ignored artifacts under `test-results/` and `playwright-report/`

- [ ] After Tasks 1–11 are green, spawn one dedicated QA agent with the playwright-cli skill. Give it the approved design, this plan, current verification report, base URL, guarded test DB name, synthetic accounts, and the exact owned path list.
- [ ] Explicitly prohibit the QA agent from editing production code, resetting any database, using real identity information, calling live Persona, exposing evidence/provider identifiers, weakening assertions, or writing outside its owned paths.
- [ ] Assign backend-facing browser checks: response status/redirects, auth/email/role/ownership gates, server age/address validation, Stage One/Stage Two transitions, duplicate requests, stale forms, private headers, and persisted final role.
- [ ] Assign frontend checks: navigation, actual uploads, stage/status copy, action-required recovery, admin moderation controls, keyboard/focus/reduced-motion behavior, desktop/mobile layout, and console/network errors.
- [ ] Require each failure report to include role/route, reproduction, expected/actual, sanitized console/network evidence, trace/snapshot location, suspected layer clearly marked as unconfirmed, and related rerun set.
- [ ] The implementation owner—not the QA agent—fixes production code. After each fix, return the exact failed scenario and its related regression set to the QA agent for rerun.
- [ ] Record the QA agent's exact commands and actual pass/fail counts in the verification document. Keep automated Playwright, exploratory CLI, and real Sandbox results separate.

## Task 13: Run Regression, Migration, and Operational Verification

**Files:**

- Modify: the operations guide, Persona Sandbox runbook, verification report, and any focused regression tests exposed by failures.

- [ ] Run formatting on owned PHP files only, then check all changed PHP files:

  ```powershell
  php vendor/bin/pint --test
  ```

  Expected: no formatting violations. Do not reformat unrelated dirty files wholesale.

- [ ] Run the full focused PHP regression serially:

  ```powershell
  php vendor/bin/phpunit --do-not-cache-result tests/Feature/InstructorApplication tests/Feature/InstructorApplicationSubmissionTest.php tests/Feature/InstructorApplicationApprovalTest.php tests/Feature/Admin/InstructorIdentityVerificationAdminTest.php tests/Feature/Admin/AdminInstructorApplicationsUiTest.php tests/Feature/Admin/InstructorApplicationRejectionReasonTest.php tests/Feature/Api/PersonaWebhookTest.php tests/Feature/Identity tests/Feature/Auth/RegistrationTest.php tests/Feature/Auth/PersonaGuardianIntegrationTest.php tests/Feature/DependentSupport/DependentSupportRegistrationTest.php tests/Feature/Parent tests/Feature/GuardianRelationshipEvidenceSubmissionTest.php tests/Feature/Notifications/InstructorApplicationStatusNotificationTest.php tests/Feature/VerificationNotificationNavigationTest.php tests/Unit/Services/InstructorIdentityComparisonTest.php tests/Unit/Services/CaviteAddressResolverTest.php tests/Unit/Services/InstructorApplicationDocumentStorageTest.php tests/Unit/Services/PersonaPolicyTest.php tests/Unit/Services/PersonaClientTest.php
  ```

- [ ] Run all JavaScript tests and the production build:

  ```powershell
  node --test tests/JavaScript/*.test.mjs tests/Unit/JavaScript/*.test.js
  npm.cmd run build
  ```

- [ ] Run the complete PHPUnit suite serially only after the focused set is green:

  ```powershell
  php vendor/bin/phpunit --do-not-cache-result
  ```

- [ ] Rerun the complete Playwright matrix with `PLAYWRIGHT_HTML_OPEN=never`; record exact project/scenario counts and artifact locations for failures only.
- [ ] Verify routes and configuration without displaying secrets:

  ```powershell
  php artisan route:list --name=instructor-application
  php artisan route:list --name=admin.instructor-applications
  php artisan config:show instructor_application
  ```

- [ ] Inspect each owned migration with `php artisan migrate --pretend --path=<exact-file>`. If the development database has unrelated pending migrations or a prerequisite mismatch, stop and report it.
- [ ] Apply owned migrations to the development database only after confirming the exact database and path, one normal incremental migration at a time:

  ```powershell
  php artisan migrate --path=database/migrations/2026_10_31_000000_add_direct_instructor_account_support.php
  php artisan migrate --path=database/migrations/2026_10_31_000100_create_instructor_identity_verification_tables.php
  php artisan migrate --path=database/migrations/2026_10_31_000200_link_instructor_identity_and_private_documents.php
  ```

  Expected: three applied migrations, no reset/reseed, existing row counts/roles/applications unchanged. If current uncommitted Persona migrations are prerequisites, do not bypass them; stop for user direction.

- [ ] After reviewing the seed diff and confirming the target database, run only the project's canonical RBAC bridge so the applicant role and two reviewer permissions exist. Record role/permission counts before and after; do not run application/demo seeders:

  ```powershell
  php artisan db:seed --class='Database\Seeders\RolePermissionSeeder'
  ```

  Expected: applicant has zero permissions, admin receives the two reviewer permissions, and existing learner/instructor permission sets match the reviewed canonical seeder.

- [ ] Run the legacy document migration command in dry-run mode only. Record counts/missing files. Do not pass `--commit` or source deletion flags without a separately reviewed operator step.
- [ ] Update `docs/direct-instructor-application-operations.md` with deployment order, queue worker, feature flag/cutover, browser-test DB, private storage permissions, document-migration phases, rollback refusal, monitoring, and recovery.
- [ ] Update the Persona Sandbox runbook with the instructor flow, warnings, protected evidence review, and exact separation between automated mocks and real Sandbox walkthrough.
- [ ] Document external actions still required:

  - public HTTPS webhook URL and active secret rotation in Persona Dashboard;
  - actual lifecycle events selected in the Dashboard (record configured names; do not invent them in code/docs);
  - GovID plus Selfie template Philippine restriction and each ID-class mapping;
  - queue worker and scheduler operation;
  - legacy public-document copy/verification/authorized source cleanup;
  - enable `DIRECT_INSTRUCTOR_APPLICATIONS_ENABLED` only after verification;
  - set `INSTRUCTOR_IDENTITY_ENFORCED_FROM` deliberately; and
  - production legal/privacy/biometric/retention/security/monitoring/incident-response review.

- [ ] Perform a separate synthetic Persona Sandbox walkthrough only if credentials/dashboard/webhook/queue are available. Report it separately and do not describe simulated outcomes as genuine identity verification.
- [ ] Fill the verification report with four explicit sections: passed, failed, blocked/unrun, and manual/external gates. Include commands and actual results; never infer success.
- [ ] Commit owned documentation/test fixes with `docs: add direct instructor operations and verification`.

## Task 14: Final Diff and Acceptance Review

**Files:**

- Inspect: all owned diffs, the approved design, this plan, and the final verification report.

- [ ] Run `git status --short` and `git diff --check`. Confirm unrelated user-owned edits remain untouched and un-staged.
- [ ] Review the complete route-to-service-to-model path for every state-changing endpoint. Confirm each has auth, email/role/permission, ownership/policy, CSRF, server validation, transaction locks, and idempotency where applicable.
- [ ] Search owned code for accidental public storage/secret/raw evidence exposure:

  ```powershell
  rg -n "Storage::disk\('public'\)->url|PERSONA_API_KEY|PERSONA_WEBHOOK_SECRET|document-number|raw_body|persona_inquiry_id" app resources tests docs
  ```

  Expected: only intentional configuration/client/ledger/test references; no Blade/JS/log output or direct instructor public URL.

- [ ] Verify the end-to-end acceptance sequence manually from code and executed evidence:

  ```text
  Be Our Partner
  → applicant account with no learner access
  → verified email + Cavite personal information
  → Persona GovID + Selfie evidence
  → administrator Stage One decision
  → Stage Two draft/submit
  → administrator final decision
  → instructor role/profile/dashboard
  ```

- [ ] Confirm the negative sequence: Persona completion, Stage One approval alone, Stage Two pending/rejected/withdrawn, abandoned registration, inactive account, and Sandbox-ineligible approval never grant instructor access.
- [ ] Confirm existing adult learner reuse, learner/guardian/dependent/relationship verification, legacy pending applications, rejection/resubmission, and current instructors remain covered by passing tests or are explicitly listed as unrun.
- [ ] Use the verification-before-completion skill before any completion claim. Report exact test results, Playwright results, Sandbox status, migration status, external configuration, limitations, and unresolved failures.

## Release Gate Checklist

- [ ] Feature flag remains off until schema, focused/full tests, Playwright, authorization review, and storage checks are green.
- [ ] Cutover timestamp is explicit and reviewed; it is not silently set to deployment time.
- [ ] Persona Dashboard webhook/template/ID-class configuration is verified externally.
- [ ] Queue worker is live and failed jobs are monitored.
- [ ] New documents are private and authorized document responses are no-store.
- [ ] Legacy public document migration has been copied and verified; any source deletion has separate explicit approval.
- [ ] Sandbox warnings and fail-closed activation behavior are confirmed.
- [ ] No genuine identity, biometric, provider URL, signed URL, or secret exists in test/report artifacts.
- [ ] Retention remains an explicit unresolved policy unless separately approved.
- [ ] Production activation remains blocked pending production credentials and the required legal/privacy/security/operations reviews.

