# Connector Marketplace and Registration Verification — Overall E2E Runbook

Status: implementation and release-test guide for the `ModuleMarketplace` branch.

This document combines the two implemented Connector workflows:

1. Connector registration, organization-type verification, evidence review, and reverification.
2. One-time Connector module marketplace purchase, seat assignment, learning access, and correction.

It is a testing guide, not legal or regulatory certification. A passing automated suite does not by itself approve staging or production release.

Companion documents:

- [Connector Verification Operations Guide](connector-verification-operations.md)
- [Connector Verification End-to-End Runbook](connector-verification-end-to-end-test-runbook.md)
- [Connector Module Marketplace Operations](connector-module-marketplace.md)
- [Marketplace design](superpowers/specs/2026-10-05-connector-module-marketplace-design.md)
- [Marketplace implementation plan](superpowers/plans/2026-10-05-connector-module-marketplace.md)
- [Registration verification design](superpowers/specs/2026-10-07-connector-registration-verification-design.md)
- [Registration verification plan](superpowers/plans/2026-10-07-connector-registration-verification.md)

## 1. What was implemented

### A. Connector registration and verification

The registration-verification plan contains 12 completed task areas:

| Area | Implemented behavior |
| --- | --- |
| Registry and schema | Versioned organization-type/category registry, stable requirement keys, evidence/requirement/review/audit records, and forward-only schema changes. |
| Initial submission | Type-specific validation, all/any/minimum requirement rules, file and metadata validation, private evidence storage, logo separation, snapshots, hashes, identity-case reuse, and atomic cleanup. |
| Registration UX | Accessible five-step registration flow covering organization, representative, evidence, and review/submit stages. |
| Applicant status | Applicant-only status presentation, safe reasons, submission rounds, stale-round protection, and partial resubmission. |
| Private evidence | Applicant/admin-only preview and download routes with ownership checks, MIME allowlists, safe headers, sanitized filenames, and access audits. |
| Document review | Admin accept/reject actions, applicant-facing reasons separate from internal notes, configurable N/A, supplemental requirements, locks, and round checks. |
| Decision gate | Centralized approval, resubmission, permanent rejection, and suspension decisions with checklist enforcement, audit history, and membership boundaries. |
| Admin workspace | Status/round summaries, grouped current and superseded evidence, safe source metadata, secure review controls, checklists, and history. |
| Notifications | Safe in-app/email verification events delivered after commit; transport failures do not create a second state transition. |
| Expiration | Daily 30-day/7-day warnings, idempotent expiry, alternative-evidence recomputation, reverification/resubmission transitions, and no automatic suspension. |
| Public compatibility | Truthful verified/legacy-verified presentation without exposing private evidence or representative identity data. |
| Operations and release gates | Verification operations guide, end-to-end runbook, retention gate, migration/staging checklist, and sign-off template. |

### B. Connector module marketplace

The marketplace plan contains 15 completed task areas:

| Task | Implemented behavior |
| --- | --- |
| Schema and domain foundation | Connector listings, purchases, entitlements, access rules, assignments, audit/correction records, and sale-ledger extensions. |
| Shared access grants | Connector assignments synchronize shared module grants and approved learning enrollment. |
| Capacity accounting | Module-level Connector reservations, assignment-level seats, individual holds, locking, and capacity checks share existing learner/instructor accounting. |
| Listing management | Approved learner-visible modules can be listed with price, currency, capacity, draft/published/unpublished/suspended states, and instructor/platform authorization. |
| Browse and details | Verified Connector members can browse published listings through shared module-detail presentation. |
| Checkout | Authorized Connector managers create historical purchases/payments and a 30-minute capacity hold with price and terms snapshots. |
| Payment finalization | Provider confirmation atomically and idempotently completes payment, purchase, entitlement, and commission/sale-ledger history. |
| Webhooks and history | Webhook, status polling, receipts, payment history, and non-refundable correction boundary preserve historical payment attempts. |
| Access rules and seats | All-member, role, and individual rules reconcile to stable assignments with direct revoke tombstones and released seats. |
| Membership reconciliation | Membership, role, guardian approval, suspension, removal, and reassignment state changes release or restore access safely. |
| Organization library | Purchased entitlements, access management, confirmation, and library views expose the Connector’s usable modules. |
| Learner enforcement | Lessons, quizzes, progress, interactive activities, translation, feedback/completion, chat entitlement, and certificate actions use shared access checks. |
| Notifications and finance | Connector purchase/access notifications, finance visibility, admin activity logs, and exceptional corrections are recorded. |
| Revision protection | Approved module revisions preserve existing progress and issued learning history. |
| Lifecycle QA and operations | Full lifecycle test, migration safety checks, route/build checks, and the marketplace operations guide were added. |

### C. Important boundaries

- Connector subscriptions are not used for marketplace eligibility, capacity, checkout, or access.
- Instructors can save and publish Connector marketplace listings without an instructor subscription or baseline plan. Instructor monetization permissions do not gate these listings; ownership, module visibility, pricing validation, and shared capacity limits still apply.
- Verification decisions do not silently rewrite memberships, roles, seminars, community data, subscriptions, marketplace purchases, entitlements, or learning history.
- Marketplace purchases are not automatically refunded. Administrators use append-only `void`, `restore`, or `retry_finalization` corrections with a reason.
- Revoking access blocks new learning actions but preserves progress, quiz attempts, completion state, and issued certificates.
- Raw verification evidence is private. Public pages and notifications must not expose files, hashes, document numbers, expiry metadata, storage paths, or internal notes.

## 2. Branch and evidence context

The current branch history includes the following implementation evidence:

| Commit | Evidence |
| --- | --- |
| `547456dd` | Initial connector verification evidence submission. |
| `f69cdfd7` | Five-step verification registration experience. |
| `1832d334` | Partial verification resubmission. |
| `d5fa7fe2` | Secure private verification evidence delivery. |
| `82a55d7a` | Document review and evidence decisions. |
| `84485d24` | Central verification decision gate. |
| `594b39b2` | Verification notifications. |
| `c83d9df9` | Evidence expiration and reverification transitions. |
| `dcda8531` | Administrator verification workspace. |
| `41137a16` | Public verified-identity presentation and compatibility. |
| `d8f5a765` | Verification operations guide. |
| `7799550a` | Marketplace lifecycle test and marketplace operations guide. |

The verification implementation was already present on the shared branch when the marketplace release checks were continued. Unrelated working-tree changes, including generated `public/build` assets, must remain untouched during this runbook.

## 3. Safety and test setup

Use synthetic accounts and synthetic PDF/PNG/JPEG/WebP files only. Do not upload real identity documents.

Use an isolated MySQL test database. The recent marketplace evidence used `cc_db_connector_marketplace_test`; use the database provisioned for the current test environment, but never point destructive test setup at development data.

PowerShell setup for the isolated database:

```powershell
$env:DB_DATABASE = 'cc_db_connector_marketplace_test'
```

Do not run any of the following against a development database:

- `php artisan migrate:fresh`
- `php artisan db:wipe`
- `TRUNCATE`
- `DROP DATABASE` or `DROP TABLE`
- destructive seeders or unscoped deletes

Before testing, prepare these personas:

| Persona | Required capability |
| --- | --- |
| Applicant / primary representative | Register a Connector, upload evidence, view status, and resubmit requested evidence. |
| Admin reviewer | Review evidence, request changes, approve, reject, suspend, and inspect audits. |
| Approved instructor without a subscription | Start as an adult learner, apply for instructor, receive admin approval, then save and publish a Connector module listing without buying a plan. |
| Verified Connector manager | Browse listings, start checkout, manage access rules, and assign seats. |
| Ordinary Connector member | Browse where permitted but cannot buy or access private evidence/admin controls. |
| Learner | Receive an assignment and exercise module learning actions. |
| Unrelated authenticated user | Prove cross-Connector authorization boundaries. |
| Guest/public visitor | Verify public identity presentation and denial of private routes. |

Record only safe identifiers: connector ID, purchase ID, entitlement ID, assignment ID, verification round, requirement key, audit action, notification type, and result. Do not record raw files, hashes, document numbers, private paths, or real personal data.

## 4. Automated verification

In this Windows checkout, direct PHPUnit is the reliable path when the Laravel `artisan test` wrapper hits its Symfony Process working-directory issue. Run all database-backed commands with the isolated database variable above.

### 4.1 Marketplace lifecycle

Run the instructor publishing regression first:

```powershell
php vendor/bin/phpunit --do-not-cache-result tests/Feature/Instructor/InstructorConnectorMarketplaceListingTest.php
```

This covers saving and publishing without any instructor plan, adult learner application approval followed by listing publication, and publication with restrictive instructor monetization permissions under strict rollout. It also checks denial for another instructor, unapproved/unpublished modules, and insufficient module capacity. All database-backed tests use the isolated test database; never remove development plans or subscriptions to reproduce the no-plan case.

```powershell
php vendor/bin/phpunit tests/Feature/Connectors/ConnectorModuleMarketplaceLifecycleTest.php
```

Expected current result: `1 test, 50 assertions`.

Run the marketplace-focused matrix:

```powershell
php vendor/bin/phpunit `
  tests/Feature/Connectors/ConnectorModuleMarketplaceSchemaTest.php `
  tests/Feature/Connectors/ConnectorModuleMarketplaceBrowseTest.php `
  tests/Feature/Connectors/ConnectorModuleCheckoutTest.php `
  tests/Feature/Connectors/ConnectorModulePaymentWebhookTest.php `
  tests/Feature/Connectors/ConnectorModulePaymentStatusTest.php `
  tests/Feature/Connectors/ConnectorModulePaymentReceiptTest.php `
  tests/Feature/Connectors/ConnectorModulePurchaseCompletionTest.php `
  tests/Feature/Connectors/ConnectorModulePurchaseConfirmationTest.php `
  tests/Feature/Connectors/ConnectorModulePurchaseIdempotencyTest.php `
  tests/Feature/Connectors/ConnectorModuleRefundBoundaryTest.php `
  tests/Feature/Connectors/ConnectorModuleAccessRuleTest.php `
  tests/Feature/Connectors/ConnectorModuleAccessManagementUiTest.php `
  tests/Feature/Connectors/ConnectorModuleSeatAssignmentTest.php `
  tests/Feature/Connectors/ConnectorModuleSeatConcurrencyTest.php `
  tests/Feature/Connectors/ConnectorModuleGuardianApprovalTest.php `
  tests/Feature/Connectors/ConnectorModuleMembershipLifecycleTest.php `
  tests/Feature/Connectors/ConnectorModuleRoleReconciliationTest.php `
  tests/Feature/Connectors/ConnectorModuleSuspensionTest.php `
  tests/Feature/Connectors/ConnectorModuleLibraryTest.php `
  tests/Feature/Connectors/ConnectorModuleNotificationTest.php `
  tests/Feature/Connectors/ConnectorModuleCommissionTest.php
```

Run the marketplace regression and Unit set used for the release check:

```powershell
php vendor/bin/phpunit `
  tests/Feature/Learner/LearnerPaidModulePurchaseFlowTest.php `
  tests/Feature/Learner/LearnerEnrollmentCapByInstructorPlanTest.php `
  tests/Feature/Learner/LearnerFinalQuizCompletionFlowTest.php `
  tests/Feature/Learner/LearnerCertificateDownloadFreeAccessTest.php `
  tests/Feature/Instructor/InstructorModulePlanEnforcementTest.php `
  tests/Feature/Admin/AdminCommissionSettingsTest.php `
  tests/Feature/Connectors/ConnectorInvitationTest.php `
  tests/Feature/Connectors/ConnectorDashboardAccessTest.php `
  tests/Unit
```

Expected current result: `232 tests, 1,082 assertions`.

### 4.2 Registration-verification matrix

The detailed verification runbook contains grouped commands and the full 15-scenario browser procedure. Run its focused matrix as follows:

```powershell
php vendor/bin/phpunit `
  tests/Unit/Support/ConnectorVerificationRequirementsTest.php `
  tests/Feature/Connectors/ConnectorVerificationSchemaTest.php `
  tests/Feature/Connectors/ConnectorRegistrationTest.php `
  tests/Feature/Connectors/ConnectorVerificationSubmissionTest.php `
  tests/Feature/Connectors/ConnectorRegistrationStepperTest.php `
  tests/Feature/Connectors/ConnectorVerificationResubmissionTest.php `
  tests/Feature/Connectors/ConnectorVerificationDocumentAccessTest.php `
  tests/Feature/Connectors/AdminConnectorDocumentReviewTest.php `
  tests/Feature/Connectors/AdminConnectorVerificationDecisionTest.php `
  tests/Feature/Connectors/AdminConnectorVerificationWorkspaceTest.php `
  tests/Feature/Connectors/ConnectorVerificationNotificationTest.php `
  tests/Feature/Connectors/ConnectorVerificationExpirationTest.php `
  tests/Feature/Connectors/ConnectorVerificationCompatibilityTest.php `
  tests/Feature/Connectors/ConnectorHomeTest.php
```

The companion verification runbook records a focused baseline of 46 tests and 611 assertions. Rerun it on the current branch and record the actual result; do not treat the recorded baseline as a new pass.

### 4.3 Migration, route, command, schedule, and build checks

Run the migration checks only with the isolated database selected:

```powershell
php artisan migrate --pretend
php artisan migrate
php artisan migrate:status
```

Expected result: no pending migrations after the normal forward migration; the connector marketplace and verification migrations show `Ran`; pretend output contains no destructive data operation.

Run route and operational checks:

```powershell
php artisan route:list --path=connector --except-vendor
php artisan route:list --name=connector.verification
php artisan route:list --name=admin.connectors.verification
php artisan list --raw | Select-String 'connectors:expire-verification-documents'
php artisan schedule:list
```

Run focused formatting and diff checks:

```powershell
php vendor/bin/pint --test tests/Feature/Connectors/ConnectorModuleMarketplaceLifecycleTest.php
git diff --check
```

Run the frontend build without overwriting the existing generated assets in `public/build`:

```powershell
$viteOut = Join-Path $env:TEMP 'cc-connector-overall-vite-build'
npm run build -- --outDir $viteOut
```

The current repository-wide Pint baseline is not green. Do not run an auto-fix across the repository as part of this feature test; record the count and separate unrelated style debt from feature evidence.

## 5. Manual end-to-end process

### Phase A — Register and verify a Connector

1. Sign in as the applicant and open Connector registration.
2. Select a supported category and organization type. Confirm the type, not the broad category alone, determines the requirement set.
3. Complete all five steps, including organization details, representative details, configured evidence, and review/submit.
4. Confirm server-side validation rejects incompatible types, missing required evidence, unsupported files, invalid metadata, excessive files, and duplicate/ineligible identity reuse.
5. Submit valid synthetic evidence and record the Connector ID and verification round.
6. Confirm the applicant receives a pending/under-review status, a primary representative membership, safe status text, and no public evidence URL.
7. Sign in as an admin and open the verification workspace. Confirm the current round, requirement snapshots, grouped evidence, review controls, checklist, audit history, and safe source metadata.
8. Reject one document with an applicant-facing reason and a separate internal note.
9. As the applicant, confirm the safe reason is visible but the internal note, path, hash, filename, document number, and expiry metadata are not.
10. Replace only the rejected/requested evidence. Confirm accepted evidence remains accepted, one submission round advances, duplicate hashes are rejected, and stale browser actions cannot overwrite the current round.
11. As admin, accept all valid evidence and submit all six approval checklist items:
    - organization identity established;
    - regulatory evidence resolved;
    - representative identity verified;
    - representative authority established;
    - required evidence complete;
    - application consistent.
12. Confirm the Connector becomes verified, the primary representative membership is activated, audit history is preserved, and approval notification delivery is recorded.
13. As a public visitor, confirm the verified public claim and eligible logo/badge. Confirm pending, rejected, suspended, and reverification-required states do not appear verified and expose no private evidence.

### Phase B — Publish and purchase a module

1. Start with an adult learner account without a subscription (for example, `adult@test.local`), submit an instructor application, and approve it as admin. If already approved, use the existing instructor account. No instructor subscription purchase or baseline-plan seed is required for listing eligibility.
2. As that instructor, use an approved, learner-visible module. Select **Manage connector listing** to open the drawer. Set the package price and **Seats per Connector purchase**; the 10/25/50-learner suggestions are optional. Check the shared module capacity summary and use **Edit module learner limit** to change the overall limit in the existing module editor. Save a draft, then publish the listing. Record the listing ID, module ID, price, currency, and seat capacity. Confirm both actions succeed without a subscription. A platform-owned listing can be tested separately as admin. Verify Escape/backdrop/close-button dismissal, focus returning to the trigger, and retained input with the drawer reopened after validation errors.
3. As an ordinary verified Connector member, confirm the listing is browseable but the purchase POST is forbidden.
4. As a verified Connector manager, open the listing detail and start checkout.
5. Confirm the pending purchase/payment history is created with listing and terms snapshots and a 30-minute capacity hold.
6. Simulate the provider boundary with the approved webhook/test mechanism. Confirm finalization creates exactly one completed payment, one completed purchase, one active entitlement, and one sale-ledger record.
7. Replay the same provider event and confirm no duplicate purchase, entitlement, or sale-ledger row is created.
8. Open the Connector library and purchase confirmation. Confirm the entitlement snapshot retains the original module, seller, price, currency, and capacity values.

### Phase C — Assign seats and exercise learner access

1. As the Connector manager, create and reconcile an all-member, role, or individual rule as appropriate.
2. Confirm the rule creates active assignments only for eligible Connector members and does not exceed entitlement capacity.
3. Confirm a direct revoke remains a tombstone and is not immediately recreated by an inherited rule.
4. As the learner, verify access to the module overview, lesson, quiz, interactive activity/checkpoint, translation, feedback/completion, chat entitlement, and certificate actions covered by the learner matrix.
5. Confirm an unassigned, inactive, suspended, unverified, age-ineligible, or otherwise unauthorized learner cannot mutate learning state.
6. Remove a membership or revoke an assignment. Confirm new learning access is blocked and the seat is released without deleting progress, quiz attempts, completion state, or issued certificates.
7. Reconcile a replacement member and confirm the released seat can be reused without increasing module-level purchased capacity.

### Phase D — Corrections and independent state changes

1. As admin, void a test purchase with a reason. Confirm purchase/entitlement/assignment state changes and historical payment, ledger, learning, and audit rows remain.
2. Restore only when the module is safe, published, and capacity is available. Confirm assignments reconcile after restore.
3. For a recoverable incomplete finalization, use `retry_finalization`; do not create a second manual purchase.
4. Unpublish the listing. Confirm it disappears from browse/detail while existing historical purchase and entitlement records remain.
5. Expire a verification document using the scheduled command/test clock. Confirm warnings and reverification/resubmission transitions are idempotent.
6. Confirm verification expiration does not automatically suspend the Connector, remove memberships, alter seminars, change subscriptions, revoke marketplace entitlements, or delete learning history.

## 6. Authorization and privacy matrix

| Actor | Verification evidence | Marketplace browse | Marketplace checkout | Assign/access management |
| --- | --- | --- | --- | --- |
| Applicant/primary representative | Own Connector only | According to verified status | No, unless manager permission applies | Own Connector permissions only |
| Admin | Authorized review and correction | Administrative visibility | Administrative correction only | Administrative/support paths only |
| Verified manager | No private evidence or review controls | Yes | Yes | Yes, subject to entitlement/capacity |
| Ordinary Connector member | Denied | Browse where allowed | Denied | Denied |
| Assigned learner | Denied | Library/learning according to assignment | Denied | Denied |
| Unrelated authenticated user | Denied/generic not found | No cross-Connector data | Denied | Denied |
| Guest/public visitor | Public claim only | Public pages only | Denied | Denied |

For successful private evidence responses, verify private/no-store caching, `nosniff`, safe content disposition, an allowlisted MIME type, and an audit row. For denied or missing documents, verify a generic not-found response without storage-path disclosure.

## 7. Expected invariants and negative cases

Check these explicitly during the run:

- A category/type mismatch cannot be submitted by changing only browser fields.
- A failed registration upload leaves no partial application state and only cleans files written by that attempt.
- An old verification round cannot accept, reject, or resubmit against a newer round.
- Accepted evidence is preserved when only one rejected requirement is replaced.
- Internal review notes never appear in applicant/public responses or notifications.
- An ordinary member cannot POST checkout even if the form is manually submitted.
- An unverified Connector, non-manager, unpublished/invisible module, or capacity-exhausted checkout is rejected server-side. Learner age eligibility is checked for assignment and learning access; an adult manager may purchase a module intended for younger members.
- A duplicate webhook is harmless and idempotent.
- A pending hold expires without deleting the historical purchase/payment row.
- A payment received after capacity is lost becomes an attention case rather than granting unsafe access.
- A direct assignment revoke is not recreated by a broader rule until the tombstone is cleared.
- Revocation blocks new learning actions but never deletes learning history or issued certificates.
- Marketplace flows do not create, inspect, or modify Connector subscription state.
- Verification expiration does not silently change marketplace entitlement state.
- Public pages never expose private evidence or claim verification for a non-verified state.

## 8. Evidence record

Copy this template into a separate test report. Do not replace this runbook with environment-specific secrets or personal data.

```text
Tester:
Date/time and timezone:
Branch/commit:
Application URL/build:
Isolated database name:

Applicant account:
Admin account:
Manager account:
Ordinary member account:
Learner account:

Connector ID:
Verification round(s):
Requirement keys exercised:
Listing ID:
Module ID:
Purchase/payment ID:
Entitlement ID:
Assignment ID(s):

Automated verification result:
Marketplace lifecycle result:
Marketplace regression/Unit result:
Verification matrix result:
Migration/route/command/schedule result:
Build and diff result:

Manual scenarios passed:
Manual scenarios failed:
Authorization/privacy findings:
Accessibility findings:
Audit/notification evidence:
Data-preservation evidence:
Remaining blockers:
Release decision: GO / NO-GO
```

## 9. Current recorded release status

This is the latest recorded evidence from the implementation session, not a substitute for rerunning the commands after another branch change:

| Gate | Status |
| --- | --- |
| Marketplace lifecycle | PASS — 1 test, 50 assertions. |
| Marketplace regression and Unit set | PASS — 232 tests, 1,082 assertions. |
| Isolated migration pretend/apply/status | PASS — no pending migrations; development data untouched. |
| Connector route listing | PASS — 117 connector routes listed. |
| Isolated Vite production build | PASS — built to a temporary output directory; existing `public/build` changes preserved. |
| Focused marketplace Pint | PASS. |
| Repository-wide Pint | OPEN — existing baseline reported 1,456 files and 698 style issues; no auto-fix applied. |
| Verification focused matrix | Recorded companion baseline: 46 tests, 611 assertions; rerun and record current result before release. |
| Full PHPUnit suite | OPEN — do not infer a clean full-suite result from focused evidence. |
| Manual browser/accessibility QA | UNRUN — a browser backend was unavailable in the implementation environment. |
| Staging backup/forward-migration rehearsal | UNRUN. |
| Evidence retention/disposal approval | BLOCKED — owner and approval date are still required. |

The implementation can be tested as a feature set, but production release should remain `NO-GO` until the open manual, staging, formatting, full-suite, and retention gates have owners and evidence.

## 10. Marketplace drawer and restored route validation — 2026-10-14

The [dated QA report](connector-marketplace-qa-2026-10-14.md) records the restored Connector marketplace routes, drawer refinement, local module/listing identifiers, Phase B–D test mapping, browser screenshots, and remaining limits. The final regression matrix passed 165 tests and 1,464 assertions, with three existing PHPUnit deprecation notices. Desktop/mobile drawer checks and the production frontend build also passed. These results supplement the older recorded baselines above; they do not close the real-provider, complete browser journey, staging, or retention gates.

## 11. Shared learner presentation and multiple fixed packages — 2026-10-15

The [October 15 report](connector-marketplace-qa-2026-10-15.md) documents learner-style Connector cards/details without personal progress, fixed package selection, separate personal/organization ownership, payment-binding hardening, pending-checkout recovery and the new automated/browser evidence. For Phase B2, configure additional fixed packages in the existing listing drawer; for B4–B8, record and verify the **selected** package's seats and price, not only the listing's default. Reopening a pending checkout must resume its saved package even when its hold exhausts available capacity.

The October 15 regression matrix passed **185 tests and 1,579 assertions**, with three existing deprecation notices. Desktop/mobile Connector presentation and instructor package-drawer checks passed without submitting a purchase or changing existing offers. Real-provider and staging/release gates remain separate.

## 12. Administrator module access management — 2026-10-15

Use **Admin → Connectors → Manage module access**. Choose the organization and an existing module license; this workflow cannot create a license or charge an individual learner. See the [admin access QA report](connector-module-access-qa-2026-10-15.md) for files, authorization, commands, evidence and remaining limits.

| Scenario | Expected result |
| --- | --- |
| Select a verified Connector and usable license for a published module | Module details, seats, full-roster counts, members/staff, roles and membership/access states appear. Search and role/membership/access filters work. |
| Select eligible users, review, then confirm grant | Only explicit member rules are added; existing seats and guardian requirements apply. No individual purchase or charge is created. |
| Repeat a grant/revoke, then regrant | Existing assignment/grant rows are reused. Real transitions are audited; no-op retries do not duplicate events. |
| Bulk selection includes an outsider, removed/ineligible member, or unavailable seat | Entire operation is rejected with feedback; no partial assignments or audit events persist. |
| Direct HTTP request uses a non-admin actor or another Connector's entitlement | Denied server-side. Client roles/status fields cannot bypass eligibility. |
| Membership, module publication, license or Connector status changes after review | Confirmation revalidates current state and rejects unsafe grants. |
| Revoke an assignment with progress, quiz results, completion, certificate or individual purchase | Only that Connector's source ends. Learning records and separate valid entitlements remain; issued certificates remain viewable. |
| Manage a suspended license, removed member, or archived module | Grants are unavailable when ineligible; historical assignments remain visible and revocable. |
| Inspect admin history | Acting admin, affected user, action/result and timestamp appear for the selected Connector/module license. |

Automated regression evidence: **98 tests / 633 assertions** passed, with three existing metadata deprecations. Fixture-based desktop/mobile selection, keyboard and confirmation checks passed; live staging, full-suite, screen-reader and simultaneous race gates remain separate.

### Member details and row actions

On the Connector **Access Management** page, use **Grant Access** to select a specific eligible member. The roster now includes unassigned members, profile photos/initials, **View** learner information, age eligibility with explanations, and per-row grant/revoke actions. Admin rows offer the same learner/eligibility details and route individual actions through existing admin confirmation.

Verify that a teen is eligible for a teen module and an adult is ineligible; also verify a Kids + Adults module excludes teens despite its broad minimum/maximum age range. Missing ages show a neutral warning under existing policy. Check active, revoked, inactive and guardian-pending rows, confirmation, Escape/focus restoration, photo fallback, and that an admin row action submits only that row even when bulk selection is active.

Follow-up regression evidence: **104 tests / 680 assertions** passed, with three existing metadata deprecations. Desktop/mobile fixture browser checks, scoped Pint, Blade compilation, route listing, and the Vite stylesheet build passed. See the [follow-up QA details](connector-module-access-qa-2026-10-15.md#follow-up-member-photos-learner-details-and-row-actions) for the full-build disk limitation and remaining live QA gates.

### Row Actions modal — 2026-10-16

Open **Actions** in a member row to view learner information or choose an allowed grant/revoke action. View is now in this modal rather than the Member column. Check that ineligible members still have View, that switching to details/confirmation leaves only one dialog open, and that Escape returns focus to Actions. **Remove grant** must open a confirmation dialog. Admin row actions must still submit only that member regardless of bulk selection.

Presentation refinement checks passed: **24 tests / 199 assertions**, scoped Pint, Blade compilation and intercepted desktop/mobile Chromium fixtures. No backend, database or asset changes were required. See the [October 16 QA entry](connector-module-access-qa-2026-10-15.md#action-modal-refinement--october-16-2026); live staging/full-suite gates remain separate.

## 13. Connector organizer and instructor messaging — 2026-10-17

Use **Connector → Educators → Message instructor** (chat icon). The adjacent **View profile** (eye icon) opens the instructor's saved public profile inside the Connector workspace. Seminar/webinar hosts with `connector.manage_seminars` can manage educators by default; `connector.manage_educators` also grants this independently. Both require active membership in a verified Connector. Owners retain access through their existing permissions. No subscription or learning enrollment is required.

Threads are private to the acting organizer and instructor, labeled with the Connector name, and separate from personal/learning chats. Other organizers cannot view them. Instructors reply through **Chat**; organizers use **Connector → Messages**. Negotiation attachments are stored privately and downloaded through an authenticated participant-only route. Payment discussions do not change marketplace prices, checkout or financial history.

Apply only the forward migration for this feature after inspecting the target schema and backing up the target environment:

```powershell
php artisan migrate --path=database/migrations/2026_10_17_000001_add_connector_id_to_conversations.php
```

Never reset development data. Run automated database tests serially against `cc_db_test`, using the existing PHPUnit configuration:

```powershell
php artisan test tests/Feature/Connectors/ConnectorInstructorMessagingTest.php tests/Feature/Chat tests/Unit/Chat tests/Feature/Connectors/ConnectorSeminarManagementTest.php tests/Feature/Connectors/ConnectorModuleRoleReconciliationTest.php
node --test tests/JavaScript/connector-chat.test.mjs
```

| Manual scenario | Expected result |
| --- | --- |
| Sign in as a host with only `connector.manage_seminars`; open Educators | Directory and Messages appear. Search by instructor name and pagination work; ordinary members cannot open these pages or forge a start request. |
| Message an instructor, return to Educators, and message them again | The same private thread opens. Messages can be sent without enrollment or subscription; duplicates do not create another thread. |
| Reply as the instructor | Existing Chat shows the Connector name, unread count and notification. The organizer receives the reply in Connector Messages. |
| Start threads for two Connectors, or as two different organizers | Conversations stay separate. Another organizer or unrelated admin cannot read the thread through ordinary chat URLs. |
| Open Connector Messages with personal/other-Connector popup chats saved | Those popup windows and their unread state remain available; only the full-page sidebar is scoped to the current Connector. The initial full-page selection belongs to that Connector. |
| Upload an agreement as a message attachment | Both participants can download it. An outsider, another organizer, or a signed-out browser cannot fetch it. The URL is not a public storage URL. Deleted message attachments become inaccessible. |
| Remove organizer membership/permission, suspend the Connector or either participant, or remove the instructor role | Thread contents disappear from inbox results. History, send/edit/delete/report/read actions, private downloads, and new realtime subscriptions are denied. Queued broadcasts publish nothing to that thread. Stored history remains. |
| Both participants are instructors and hosts; original organizer leaves, remaining host starts a negotiation with them | A distinct thread uses the remaining host's organizing permission. It does not reopen the revoked thread. |
| Check desktop/mobile, keyboard navigation, screen reader labels and an interrupted send | Directory and inbox remain usable; failures show existing chat feedback without claiming success. |

Record results in [the messaging QA report](connector-instructor-messaging-qa-2026-10-17.md). Automated checks do not close interactive browser, live realtime, staging migration or full-suite release gates.

## 14. Shared chat access compatibility — 2026-10-17

The [chat access QA report](chat-access-qa-2026-10-17.md) records the instructor `/chat` 403 fix and cross-role checks. Existing admin/instructor/learner accounts can access chat even when legacy permission entries are missing; registered parents still require verification and onboarding. Inactive, suspended, archived and deleted accounts are rejected by the shared route gate, including the admin messages alias. Unrelated roles require an explicit chat grant or eligible Connector organizer access.

Verification passed **139 tests / 955 assertions** and **41 server-side HTTP checks** using the existing development accounts. The checks cover instructor requests/replies, learner/admin access, guardian gates, denied accounts, private Connector threads and the instructor message buttons. Development data and role assignments were preserved. Browser interaction and live realtime delivery remain separate manual checks.

## 15. Educator profile viewing and icon actions — 2026-10-17

The Educators directory provides two labeled icon actions: **View profile** (eye) and **Message instructor** (chat). The profile displays the instructor's saved photo, biography, expertise, experience, public credentials, professional background, certifications with public proofs, and education. Later edits through instructor profile management appear automatically. Missing fields retain the existing empty states; viewing a profile creates no profile or conversation record.

The Connector profile uses the same public presentation and credential filtering as the learner instructor-background page. Private verification documents and account details remain excluded. Viewing requires educator-management permission in a verified Connector and an available instructor target; ordinary members and outsiders cannot open a profile through a forged URL. **Back to Educators** returns to the directory; the profile's chat icon opens the existing negotiation flow.

Manual checks:

| Action | Expected result |
| --- | --- |
| Open Educators as a seminar/webinar host or delegated educator manager | Both icons appear for each instructor; hover titles and keyboard focus identify their actions. |
| Open the eye icon, including at a narrow viewport | Connector navigation remains visible; saved public profile fields and empty states render without overflow. |
| Edit the instructor's profile, then reopen the organizer view | Updated public information appears without copying or synchronizing a second profile. |
| Use the chat icon from the directory and profile | The existing private conversation opens; repeated use reuses the thread. |
| Try the profile URL as an ordinary member/outsider, or target an unavailable account | Access is denied; private account and verification data are absent. |

Verification passed **24 tests / 358 assertions**, scoped Pint, route registration and the production build. See [educator profile QA evidence](connector-educator-profile-qa-2026-10-17.md) for executed checks and remaining browser gates.
