# Connector Registration Verification — End-to-End Test Runbook

Status: implementation runbook for the connector registration and verification work on branch `ModuleMarketplace`.

Companion guide: [Connector Verification Operations Guide](connector-verification-operations.md)

This runbook tests the implemented workflow. It does not certify that an organization, document, or government relationship is legally valid, and it does not replace the retention, staging, or production release gates.

## 1. What is implemented?

All 12 plan tasks are implemented and committed on the current branch. The latest documentation commit is `d8f5a765 docs: add connector verification operations guide`.

| Task | Implemented scope | Main verification evidence |
| --- | --- | --- |
| 1. Registry and schema | Versioned organization-type/category registry; stable requirement keys; evidence, requirement, audit, and review schema; legacy verified backfill; forward-only migration | `ConnectorVerificationRequirementsTest`, `ConnectorVerificationSchemaTest` |
| 2. Initial submission | Dynamic category/type validation; required/any/minimum rules; file and metadata validation; private evidence storage; public logo storage; snapshots; hashes; identity-case reuse; pending owner membership; atomic cleanup | `ConnectorRegistrationTest`, `ConnectorVerificationSubmissionTest` |
| 3. Five-step registration | Accessible five-step applicant stepper with category/type, organization data, representative data, evidence, and review/submit stages | `ConnectorRegistrationStepperTest` |
| 4. Applicant status and resubmission | Applicant-only status page; partial replacement; accepted evidence preservation; submission rounds; stale-round protection; safe applicant-facing reasons | `ConnectorVerificationResubmissionTest`, status-page checks |
| 5. Private evidence delivery | Applicant/admin-only streaming; connector/document binding; MIME allowlist; sanitized filename; no-store/nosniff headers; preview/download audits | `ConnectorVerificationDocumentAccessTest` |
| 6. Document review | Admin accept/reject; separate applicant reason and internal note; configurable N/A; `other` supplemental requirements; row locks and round checks | `AdminConnectorDocumentReviewTest` |
| 7. Overall decision gate | Six-item approval checklist; explicit approval, resubmission, permanent rejection, and suspension services; evidence preservation and membership boundary | `AdminConnectorVerificationDecisionTest`, moderation regression |
| 8. Admin workspace | Status/round summary, evidence grouping, safe source metadata, secure preview/download, review controls, checklist, audit/review history, status filters | `AdminConnectorVerificationWorkspaceTest` |
| 9. Notifications | Allowlisted database/email events, after-commit delivery, safe labels/routes only, transport-failure isolation | `ConnectorVerificationNotificationTest`, notification regression |
| 10. Expiration | Daily command, exact 30/7-day warnings, idempotent expiry, alternative-evidence recomputation, reverification/resubmission transitions, no automatic suspension | `ConnectorVerificationExpirationTest` |
| 11. Public compatibility | Verified and legacy-verified public claim/logo behavior; neutral reverification state; no public evidence or representative identity leakage | `ConnectorVerificationCompatibilityTest`, `ConnectorHomeTest` |
| 12. Operations and release gates | Operations guide, retention gate, manual QA checklist, migration/staging checklist, sign-off reporting | This runbook plus [operations guide](connector-verification-operations.md) |

## 2. Safety and test environment

- Use synthetic, non-sensitive PDFs/images only.
- Use the isolated testing database configured as `cc_db_test`.
- Do not use `migrate:fresh`, `db:wipe`, truncation, destructive seeders, or database recreation against development data.
- For browser testing, use an isolated staging environment or a disposable test tenant with a backup.
- Record connector ID, verification ID/round, requirement key, audit action, notification recipient/type, and result. Do not record raw storage paths, hashes, document numbers, or real personal data.
- A full pass requires both automated evidence and manual/staging evidence. Focused PHPUnit success alone is not release approval.

## 3. Test personas and fixtures

Create or use these isolated accounts:

| Persona | Purpose |
| --- | --- |
| Applicant / primary representative | Registers, uploads, resubmits, receives status notifications |
| Admin reviewer | Reviews evidence, requests changes, approves/rejects/suspends |
| Ordinary connector member | Proves private evidence is not broadly visible |
| Unrelated authenticated user | Proves cross-connector authorization boundaries |
| Guest/public visitor | Proves public compatibility presentation and no raw evidence access |

Recommended connectors:

| Fixture | Organization type | Use |
| --- | --- | --- |
| A | `ngo_nonprofit_foundation` | Normal initial application; SEC evidence plus representative identity/authority; mark activity-specific authority N/A |
| B | `other` | Supplemental requirement flow and custom evidence |
| C | `social_welfare_agency` | Expiring DSWD evidence, 30/7-day warnings, expiry transition |
| D | Legacy verified connector | Existing `legacy_verified` public compatibility |
| E | Alternative-evidence connector | Any/minimum rule recomputation after one document expires or is rejected |

Use the current registry matrix in the [operations guide](connector-verification-operations.md) for all 14 organization types and 26 stable requirement keys.

## 4. Automated verification

Run from the repository root. In this Windows environment, the Laravel Artisan test wrapper has a Symfony Process working-directory problem, so direct PHPUnit is the reliable equivalent:

```
C:/xampp/php/php.exe vendor/bin/phpunit tests/Unit/Support/ConnectorVerificationRequirementsTest.php --no-coverage --do-not-cache-result
```

```
C:/xampp/php/php.exe vendor/bin/phpunit tests/Feature/Connectors/ConnectorVerificationSchemaTest.php tests/Feature/Connectors/ConnectorVerificationSubmissionTest.php tests/Feature/Connectors/ConnectorRegistrationStepperTest.php --no-coverage --do-not-cache-result
```

```
C:/xampp/php/php.exe vendor/bin/phpunit tests/Feature/Connectors/ConnectorVerificationResubmissionTest.php tests/Feature/Connectors/ConnectorVerificationDocumentAccessTest.php tests/Feature/Connectors/AdminConnectorDocumentReviewTest.php --no-coverage --do-not-cache-result
```

```
C:/xampp/php/php.exe vendor/bin/phpunit tests/Feature/Connectors/AdminConnectorVerificationDecisionTest.php tests/Feature/Connectors/AdminConnectorVerificationWorkspaceTest.php tests/Feature/Connectors/ConnectorVerificationNotificationTest.php --no-coverage --do-not-cache-result
```

```
C:/xampp/php/php.exe vendor/bin/phpunit tests/Feature/Connectors/ConnectorVerificationExpirationTest.php tests/Feature/Connectors/ConnectorVerificationCompatibilityTest.php --no-coverage --do-not-cache-result
```

Then run the whole suite:

```
C:/xampp/php/php.exe vendor/bin/phpunit --no-coverage --do-not-cache-result
```

Expected focused baseline from the current implementation: 46 tests and 611 assertions pass across the five groups. The current full-suite run is not green: it reports environment prerequisites (in-memory SQLite/GD), mixed-suite isolation failures, and unrelated failures. Repair the environment/test isolation and rerun before calling the release gate passed.

Static/build checks:

```
C:/xampp/php/php.exe vendor/bin/pint --test
npm.cmd run build
git diff --check
```

The current final connector scope passes Pint. Repository-wide Pint still needs its Windows path/style issues resolved before treating the full formatting gate as green.

Read-only route, command, schedule, and migration checks:

```
C:/xampp/php/php.exe artisan route:list --name=connector.verification
C:/xampp/php/php.exe artisan route:list --name=admin.connectors.verification
C:/xampp/php/php.exe artisan list --raw
C:/xampp/php/php.exe artisan schedule:list
C:/xampp/php/php.exe artisan migrate:status --env=testing
```

Expected release evidence:

- Applicant verification routes are present.
- Admin document/review/resubmission routes are present.
- `connectors:expire-verification-documents` is listed.
- The expiration command is scheduled daily at 01:15 with overlap protection.
- The verification migration is reported as ran in the testing database.
- No development database reset was used.

## 5. Manual end-to-end workflow

Run the following in an authenticated browser using the five personas. Capture only safe screenshots and IDs.

### E2E-01 — Registration taxonomy and address

1. Sign in as the applicant and open the connector registration page.
2. Exercise each of the 14 organization types at least once across compatible categories.
3. Confirm incompatible category/type combinations are rejected server-side.
4. Confirm the Cavite city/barangay fields load and validate.
5. Confirm organization type, not category alone, determines the requirement set.
6. Confirm all visible required/optional labels, help text, and field errors are understandable.

Expected: no organization can submit a category/type pair outside the registry; the resolved requirements include representative identity and representative authority.

### E2E-02 — Five-step registration UX

1. Confirm the page is one multipart form with visible `Step 1 of 5` progress.
2. Move through organization, representative, evidence, and review/submit steps.
3. Verify Back/Continue controls, current-step indicator, focus movement, labels, required/optional text, and no full-form modal.
4. Remove and reselect a logo; verify the logo is separate from private evidence.
5. Disable JavaScript and confirm the server form remains usable enough to submit and display named validation errors.

Expected: the browser presentation can guide the applicant, but the server remains authoritative.

### E2E-03 — Valid initial submission

1. Use Fixture A with valid synthetic evidence.
2. Submit the required registration, representative identity, representative authority, and configured organization evidence.
3. Confirm the applicant is redirected to the connector status page.
4. Record connector ID, current submission round, and verification status.
5. Confirm the connector is operationally pending and verification is under review.
6. Confirm only the primary representative has the pending owner membership.
7. Confirm the public logo is stored separately from evidence.
8. Confirm no evidence file is reachable through a public storage URL.

Expected audit evidence includes `uploaded` and `submitted`; requirement and source definitions are snapshotted; applicant/admin notifications are safe and occur after commit.

### E2E-04 — Validation and cleanup boundaries

Attempt each invalid submission in isolation:

- Missing mandatory evidence.
- Unsatisfied any-of or minimum group.
- Unknown requirement key.
- Too many files.
- Unsupported MIME type.
- Oversized file.
- Metadata supplied for a requirement that does not configure that metadata.
- Missing issuer/issue date where configured.
- Duplicate active organization identity.
- Reuse of a rejected, superseded, or another applicant’s identity case.

Expected: validation names the relevant field/requirement; no partial connector/application state is created; only files written by the failed attempt are cleaned up.

### E2E-05 — Identity reuse

1. Give the applicant an approved, non-superseded learner identity verification.
2. Submit a connector with representative identity reuse enabled and no copied representative identity file.
3. Confirm the requirement references the approved identity case.
4. Confirm no connector evidence document is copied from the learner identity storage.
5. Repeat with an ineligible identity case and confirm rejection.

Expected: reuse is a reference, not a file copy.

### E2E-06 — Applicant status and partial resubmission

1. As admin, reject one document with an applicant-facing reason and a separate internal note.
2. Confirm the applicant sees the safe reason but not the internal note, path, hash, filename, or document number.
3. As applicant, replace only the rejected/requested evidence.
4. Confirm accepted evidence remains accepted and is not replaced.
5. Confirm exactly one submission round increments.
6. Confirm the connector returns to under review.
7. Submit duplicate content and verify duplicate-hash protection.
8. Open the old browser tab and attempt its stale review/resubmission action.

Expected: stale actions receive a conflict response; the current round remains authoritative.

### E2E-07 — Secure evidence access

Test each actor against a current and superseded document:

| Actor | Preview/download expected result |
| --- | --- |
| Applicant for own connector | Allowed |
| Admin reviewer | Allowed |
| Ordinary member | Denied |
| Unrelated authenticated user | Denied or generic not found according to route boundary |
| Guest | Denied |
| Any actor using another connector’s document ID | Generic not found |

For successful responses verify:

- MIME is limited to PDF/JPEG/PNG/WebP.
- `Content-Disposition` is inline for preview and attachment for download.
- Filename is sanitized.
- `Cache-Control: private, no-store, max-age=0`.
- `Pragma: no-cache`.
- `X-Content-Type-Options: nosniff`.
- No public storage URL resolves the evidence file.
- Preview creates `document_previewed`; download creates `document_downloaded`.

Delete or quarantine a test file in the isolated environment, then retry. Expected: generic 404 without storage path disclosure.

### E2E-08 — Admin verification workspace

1. Open the admin verification index and verify status counts and filters.
2. Open Fixture A’s detail workspace.
3. Confirm status, current round, organization/logo, representative identity state, grouped current/superseded evidence, safe source metadata, review controls, checklist, and audit history.
4. Confirm private paths, hashes, document numbers, and internal notes are not rendered in the page source.
5. Confirm preview/download links use the secure controller routes.
6. Check responsive layout and keyboard behavior around review controls.

Expected: no N+1-visible behavior or cross-connector evidence leakage; no bulk evidence action is presented.

### E2E-09 — Document decisions, N/A, and supplemental evidence

Test each action with the correct round:

- Accept a pending document.
- Reject a pending document without an applicant reason: validation must fail.
- Reject with applicant reason and optional internal note.
- Decide a finalized/superseded document: conflict or validation failure.
- Mark `activity_specific_authority` N/A with a reason.
- Attempt N/A for representative identity: reject.
- Use Fixture B to request custom supplemental evidence.
- Attempt the custom supplemental path for a non-`other` organization: reject.
- Submit the newly requested supplemental evidence.

Expected audit actions include `document_accepted`, `document_rejected`, `requirement_not_applicable`, `supplemental_requested`, and `resubmission_requested`. Applicant notifications contain safe labels and routes only.

### E2E-10 — Overall approval gate

Before approval, confirm approval is rejected when any of these is true:

- Required evidence is missing or pending.
- A required document is rejected or expired.
- An any-of or minimum rule is unsatisfied.
- Any checklist item is false.
- The submitted round is stale.
- The connector is not in an eligible pending review state.

For a valid approval, submit all six checklist items:

1. Organization identity established.
2. Regulatory evidence resolved.
3. Representative identity verified.
4. Representative authority established.
5. Required evidence complete.
6. Application consistent.

Expected:

- Verification status becomes verified.
- Operational status becomes verified/active according to the existing connector lifecycle.
- Only the primary representative’s pending membership is activated.
- Evidence and audit history are preserved.
- `connector_approved` audit exists.
- Approval notification is delivered after commit.
- Existing membership, seminar, community, subscription, marketplace, and entitlement records are not rewritten.

### E2E-11 — Rejection, resubmission, and suspension boundaries

1. Use the admin permanent-rejection action with an allowed reason code and applicant guidance.
2. Confirm the connector becomes rejected and evidence remains preserved.
3. Confirm an invalid reason code is rejected.
4. Request resubmission only when a requirement is rejected, expired, or newly requested.
5. Suspend a connector and confirm verification evidence/audits remain available to authorized reviewers.
6. Confirm suspension does not silently delete evidence, memberships, seminars, community data, subscriptions, or module entitlements.

### E2E-12 — Notifications

For each event, verify the intended recipient, channel, safe type/title/message, route, and absence of private metadata:

- Initial submission.
- Resubmission.
- Supplemental requirement.
- Explicit document-rejection/resubmission request.
- Approval.
- Permanent rejection.
- Reverification required.
- Expiration warning.

Force or simulate a transport failure in the isolated environment. Expected: the persisted decision remains committed; failure is observable in logs/monitoring; no second state transition is created.

### E2E-13 — Expiration and re-verification

Use Fixture C or controlled test dates:

1. Set an expiring accepted document exactly 30 days from the test date.
2. Run `connectors:expire-verification-documents`.
3. Confirm one `document_expiration_warning_30` audit and safe warning notification.
4. Run the command again; confirm no duplicate warning.
5. Repeat at exactly 7 days for `document_expiration_warning_7`.
6. Move the date to/past expiry and run the command.
7. Confirm `document_expired`.
8. For an any-of requirement, confirm another accepted document still satisfies the requirement.
9. For a verified connector whose requirement is no longer satisfied, confirm `reverification_required`.
10. For a pending connector, confirm `resubmission_required`.
11. Confirm no automatic suspension, membership removal, access revocation, seminar change, subscription change, or marketplace entitlement change.
12. Run again and confirm idempotency.

### E2E-14 — Public compatibility and truthfulness

As an anonymous/public visitor:

1. Open a verified connector.
2. Confirm the exact public claim: “Verified by Conscious Connections based on submitted organizational and authorization documents.”
3. Confirm the eligible logo and verified badge render.
4. Open a legacy-verified connector and confirm the same public claim.
5. Open under-review, resubmission-required, rejected, suspended, draft, and reverification-required connectors.
6. Confirm those states use neutral language and do not expose representative identity, filename, document number, expiry, internal note, private route, or raw evidence.
7. Confirm discovery distinguishes verified from reverification and does not present pending connectors as verified.

### E2E-15 — Ordinary-member and accessibility pass

As an ordinary connector member:

- Confirm ordinary connector features still work according to the member role.
- Confirm private evidence and admin controls are unavailable.
- Confirm public profile content does not leak evidence metadata.

Across desktop, mobile, 200% zoom, keyboard-only navigation, and a screen reader where available:

- Focus order follows the stepper and review hierarchy.
- Step changes move focus to a meaningful heading or invalid field.
- Dialogs have names, initial focus, Escape/Cancel behavior, and focus return.
- Labels and required/optional state are programmatically associated.
- Errors are visible and associated with controls.
- Status changes are understandable without color alone.
- Buttons and important targets meet the project’s minimum touch size.
- PDF/image preview and fallback rendering work at narrow widths.
- Reduced-motion preferences are respected.

## 6. Audit and database evidence checklist

Use read-only inspection in the isolated test/staging database. For each scenario, record:

| Scenario | Expected audit evidence |
| --- | --- |
| Initial upload/submit | `uploaded`, `submitted` |
| Partial replacement | `superseded`, `uploaded`, `resubmitted` |
| Document review | `document_accepted` or `document_rejected` |
| N/A | `requirement_not_applicable` |
| Supplemental request | `supplemental_requested` |
| Resubmission request | `resubmission_requested` |
| Approval/rejection/suspension | `connector_approved`, `connector_permanently_rejected`, or `connector_suspended` |
| Secure access | `document_previewed` or `document_downloaded` |
| Expiration | `document_expiration_warning_30`, `document_expiration_warning_7`, `document_expired` |
| Automatic state response | `connector_reverification_required` or `connector_resubmission_required` |

Never paste private evidence content or metadata into the test report.

## 7. Future identity-edit gate

There is no approved shortcut for editing critical verified identity fields. Before any future route changes organization name, organization type, primary representative, or equivalent identity data:

1. Route the mutation through the verification service.
2. Preserve the current snapshot and audit history.
3. Re-evaluate the public claim and verification state.
4. Add a focused test for the mutation.
5. Do not leave a verified claim attached to changed identity data.

This is a code-review/release gate until a dedicated identity-edit workflow exists.

## 8. Release sign-off

Do not mark the release ready until every row has evidence:

| Gate | Current evidence/status |
| --- | --- |
| Focused connector PHPUnit | PASS: 46 tests, 611 assertions |
| Full PHPUnit | OPEN: current run has environment/mixed-suite failures |
| Final connector Pint scope | PASS |
| Repository-wide Pint | OPEN: Windows path/style issues |
| Vite production build | PASS |
| Named routes/command/schedule/testing migration status | PASS |
| Manual browser/accessibility QA | UNRUN |
| Isolated staging backup and forward migration rehearsal | UNRUN |
| Staging smoke flows and existing-system regression | UNRUN |
| Retention schedule for evidence, logos, audits, legal holds, backups, disposal | BLOCKED: owner/date not approved |

The implementation can be called complete as code/documentation, but production release cannot be called complete until the open gates are closed and recorded.

## 9. Test result template

Copy this section into the release report:

- Tester:
- Date/time:
- Environment/build:
- Applicant account:
- Admin account:
- Ordinary member account:
- Connector IDs and rounds:
- Automated command results:
- Manual scenarios passed:
- Manual scenarios failed:
- Accessibility findings:
- Migration/staging evidence:
- Notification evidence:
- Retention decision owner/date:
- Remaining blockers:
- Release decision: `GO` / `NO-GO`

