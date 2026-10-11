# Connector Verification Operations Guide

Status: implementation guide and release checklist for the connector registration verification workflow. The current registry is `config/connector_verification.php` version `2026-10-07`; review this guide whenever that registry changes.

## Scope and non-certification boundary

This workflow records an application review decision based on submitted evidence. It is not legal advice, government accreditation, a guarantee that a document is genuine, or a certification issued by any government or regulatory body. Reviewers must apply the configured requirements and escalate legal or policy questions to the product owner and appropriate counsel or authority.

The workflow is deliberately separate from connector memberships, roles, seminars, community data, subscriptions, module entitlements, and marketplace history. Verification decisions must not rewrite those systems unless a separately approved policy explicitly does so.

## Operating invariants

- The organization type is the verification input; category is a broader discovery and routing label. A category such as `government` can contain multiple organization types with different evidence requirements.
- Requirement keys are stable identifiers. Labels and guidance may be clarified, but a key must not be repurposed to mean a different document.
- Every submitted verification uses a versioned registry snapshot. Updating the registry version/config creates the definition for future submissions; it does not mutate existing requirement or evidence snapshots.
- Evidence is private by default. Public connector pages never expose raw files, document numbers, expiry dates, representative identity, internal review notes, or private storage paths.
- Administrative decisions must go through `ConnectorVerificationService` so round checks, ownership checks, audit rows, notifications, and status transitions remain consistent.

## Registry and requirement matrix

### Organization type versus category

Categories are used for discovery and broad routing. The organization type selects the exact stable requirement set below. Do not infer requirements from the category alone.

| Type key | Display label | Categories | Requirement keys |
| --- | --- | --- | --- |
| barangay_lgu | Barangay / LGU | government | barangay_identity<br>representative_identity<br>representative_authority |
| government_agency | Government Agency | government | government_agency_identity<br>activity_specific_authority<br>representative_identity<br>representative_authority |
| public_educational_institution | Public Educational Institution | school_educational_institution<br>government | public_school_identity<br>deped_authority<br>ched_authority<br>tesda_program_registration<br>representative_identity<br>representative_authority |
| private_basic_education_institution | Private Basic Education Institution | school_educational_institution | sec_registration<br>dti_registration<br>deped_authority<br>representative_identity<br>representative_authority |
| higher_education_institution | Higher Education Institution | school_educational_institution | sec_registration<br>institution_charter<br>government_institution_identity<br>ched_authority<br>representative_identity<br>representative_authority |
| technical_vocational_institution | Technical-Vocational Institution | school_educational_institution | technical_vocational_identity<br>tesda_program_registration<br>representative_identity<br>representative_authority |
| ngo_nonprofit_foundation | NGO / Non-profit / Foundation | ngo<br>advocacy_group | sec_nonstock_registration<br>activity_specific_authority<br>representative_identity<br>representative_authority |
| corporation_private_company | Corporation / Private Company | health_organization<br>advocacy_group<br>other | sec_registration<br>activity_specific_authority<br>representative_identity<br>representative_authority |
| partnership | Partnership | health_organization<br>advocacy_group<br>other | sec_partnership_registration<br>activity_specific_authority<br>representative_identity<br>representative_authority |
| cooperative | Cooperative | community_based_organization<br>other | cda_registration<br>cda_branch_or_activity_authority<br>representative_identity<br>representative_authority |
| social_welfare_agency | Social Welfare Agency | ngo<br>health_organization<br>advocacy_group | sec_nonstock_registration<br>dswd_crlto<br>dswd_program_accreditation<br>representative_identity<br>representative_authority |
| registered_community_association | Registered Community Association | community_based_organization<br>advocacy_group | sec_registration<br>cda_registration<br>other_registration<br>activity_specific_authority<br>representative_identity<br>representative_authority |
| informal_community_organization | Informal Community Organization | community_based_organization<br>advocacy_group<br>other | barangay_or_community_certification<br>organization_charter<br>officer_list<br>current_activity_evidence<br>representative_identity<br>representative_authority |
| other | Other | government<br>ngo<br>community_based_organization<br>school_educational_institution<br>health_organization<br>advocacy_group<br>other | other_organization_identity<br>representative_identity<br>representative_authority |

### Stable requirement keys

`all` requires every configured evidence item in the rule group; `any` accepts one qualifying item; `minimum (n)` requires at least `n` qualifying items. A required flag controls whether the requirement is part of the baseline; expiry controls whether accepted evidence is processed by the scheduled expiration command.

| Key | Label | Rule | Required | Expires | Manual N/A | Issuing authority / official source |
| --- | --- | --- | --- | --- | --- | --- |
| representative_identity | Representative identity | all | yes | no | no | —<br>Reviewed: — |
| representative_authority | Representative authority | all | yes | no | no | —<br>Reviewed: — |
| barangay_identity | Barangay identity document | all | yes | no | no | [Department of the Interior and Local Government](https://officialgazette.gov.ph/1991/10/10/republic-act-no-7160/)<br>Reviewed: 2026-10-07 |
| government_agency_identity | Government agency identity | all | yes | no | no | [Official Gazette of the Republic of the Philippines](https://officialgazette.gov.ph/)<br>Reviewed: 2026-10-07 |
| public_school_identity | Public school identity | all | yes | no | no | [Department of Education](https://www.deped.gov.ph/)<br>Reviewed: 2026-10-07 |
| sec_registration | SEC registration | any | no | no | no | [Securities and Exchange Commission](https://appointment.sec.gov.ph/forms-and-fees/primary-registration/)<br>Reviewed: 2026-10-07 |
| dti_registration | DTI registration | any | no | no | no | [Department of Trade and Industry](https://www.dti.gov.ph/)<br>Reviewed: 2026-10-07 |
| institution_charter | Institution charter | any | no | no | no | [Official Gazette of the Republic of the Philippines](https://officialgazette.gov.ph/)<br>Reviewed: 2026-10-07 |
| government_institution_identity | Government institution identity | any | no | no | no | [Official Gazette of the Republic of the Philippines](https://officialgazette.gov.ph/)<br>Reviewed: 2026-10-07 |
| deped_authority | DepEd authority | any | no | no | no | [Department of Education](https://www.deped.gov.ph/wp-content/uploads/DO_s2026_012r.pdf)<br>Reviewed: 2026-10-07 |
| ched_authority | CHED authority | any | no | no | no | [Commission on Higher Education](https://ched.gov.ph/wp-content/uploads/2017/10/CHED_MOP.pdf)<br>Reviewed: 2026-10-07 |
| tesda_program_registration | TESDA program registration | any | no | no | no | [Technical Education and Skills Development Authority](https://tesda.gov.ph/AboutL/TESDA/26)<br>Reviewed: 2026-10-07 |
| technical_vocational_identity | Technical-vocational institutional identity | all | yes | no | no | [Technical Education and Skills Development Authority](https://tesda.gov.ph/AboutL/TESDA/26)<br>Reviewed: 2026-10-07 |
| sec_nonstock_registration | SEC non-stock registration | all | yes | no | no | [Securities and Exchange Commission](https://appointment.sec.gov.ph/forms-and-fees/primary-registration/)<br>Reviewed: 2026-10-07 |
| activity_specific_authority | Activity-specific authority | all | no | no | yes | —<br>Reviewed: — |
| sec_partnership_registration | SEC partnership registration | all | yes | no | no | [Securities and Exchange Commission](https://appointment.sec.gov.ph/forms-and-fees/primary-registration/)<br>Reviewed: 2026-10-07 |
| cda_registration | CDA registration | any | no | no | no | [Cooperative Development Authority](https://cda.gov.ph/services/regulatory-services/registration/)<br>Reviewed: 2026-10-07 |
| cda_branch_or_activity_authority | CDA branch or activity authority | all | no | no | yes | [Cooperative Development Authority](https://cda.gov.ph/services/regulatory-services/registration/)<br>Reviewed: 2026-10-07 |
| dswd_crlto | DSWD CRLTO | all | yes | yes | no | [Department of Social Welfare and Development](https://helps.dswd.gov.ph/Home/RLAFAQ/)<br>Reviewed: 2026-10-07 |
| dswd_program_accreditation | DSWD program accreditation | all | no | yes | no | [Department of Social Welfare and Development](https://helps.dswd.gov.ph/Home/RLAFAQ/)<br>Reviewed: 2026-10-07 |
| other_registration | Other registration | any | no | no | no | —<br>Reviewed: — |
| barangay_or_community_certification | Barangay or community certification | minimum (2) | no | no | no | —<br>Reviewed: — |
| organization_charter | Organization charter | minimum (2) | no | no | no | —<br>Reviewed: — |
| officer_list | Current officer list | minimum (2) | no | no | no | —<br>Reviewed: — |
| current_activity_evidence | Current activity evidence | minimum (2) | no | no | no | —<br>Reviewed: — |
| other_organization_identity | Organization identity evidence | all | yes | no | no | —<br>Reviewed: — |

Only the configured requirements with `manual_not_applicable_allowed` may be marked N/A. Representative identity and representative authority are not N/A-capable in this registry.

### Registry change control

1. Review the proposed requirement key, evidence category, rule group/mode, evidence count, expiry behavior, issuing authority, official source URL, and `source_reviewed_on`.
2. Bump the registry version in `config/connector_verification.php` for a material definition change.
3. Keep stable keys stable. Add a new key for a new meaning; do not silently repurpose an existing key.
4. Confirm the submission service snapshots the current definitions and that older verification snapshots remain unchanged.
5. Run the schema and submission tests plus a focused review of existing production rows before release.
6. Use forward-only migrations for schema changes. A rollback must never delete evidence-bearing rows; a migration down path must refuse or be separately approved when those rows exist. Never use `migrate:fresh`, `db:wipe`, truncation, or destructive reseeding against development data.

## Submission and review procedure

### Applicant

1. Select the organization type and complete the registration stepper. Category compatibility is checked against the selected type.
2. Submit each configured requirement through the verification service. The service creates a submission round, requirement snapshots, document metadata, and audit rows atomically.
3. Use the applicant status page for `under_review`, `resubmission_required`, `verified`, `reverification_required`, and `rejected` outcomes.
4. When resubmission is requested, replace only the rejected or requested evidence. Accepted evidence remains valid unless the requirement is re-evaluated or the document expires.

### Reviewer

- Open the connector verification workspace and review the current round, requirement snapshot, evidence state, and safe preview/download controls.
- Accept, reject, or mark N/A only through the server-side review actions. Rejection requires an applicant-facing reason; internal notes are not applicant or public payloads.
- Request supplemental evidence only through the configured supplemental path. The current path is scoped to `other` organization applications; do not add an ad hoc requirement directly in the database.
- Before every action, use the round shown by the workspace. If a newer round exists, the stale action must be rejected with a conflict response; reload the current workspace rather than applying the old decision.
- Approval and permanent rejection are explicit decisions. Do not treat notification delivery failure as a reason to roll back an already committed review decision.

### N/A and supplemental evidence

N/A is an explicit reviewer decision, not an omitted upload. It is available only for requirements that opt into manual N/A, requires a reason, and is audited as `requirement_not_applicable`. The applicant sees the safe status/reason without private file metadata.

Supplemental requirements are created through the verification service, are tied to the connector and current verification context, and create a new resubmission path. Their label and safe identifier may be notified; raw filenames, document numbers, internal notes, storage paths, and hashes must not be included.

## Evidence access and privacy

### Access matrix

| Actor | Allowed | Not allowed |
| --- | --- | --- |
| Applicant (creator or primary representative) | Submit/resubmit their connector evidence; view status and requests; preview/download private evidence for their connector | Admin decisions; another connector’s evidence; public raw-file access |
| Admin reviewer | Review, accept, reject, mark configured N/A, request supplemental evidence, approve/reject, preview/download authorized private evidence, inspect audit history | Bypassing round/ownership checks or exposing raw evidence through public URLs |
| Ordinary connector member | Ordinary connector features allowed by their role | Verification evidence, reviewer controls, internal notes, document numbers, private routes |
| Anonymous/public visitor | Public connector profile and safe verification presentation | Raw evidence, representative identity documents, filenames, expiry dates, internal notes, storage paths |

The private evidence endpoints are `connector.verification.documents.show` and `admin.connectors.verification.documents.show`. They enforce connector/document ownership, applicant-or-admin authorization, the configured `local` evidence disk, an allowlist of PDF/JPEG/PNG/WebP MIME types, and server-side file existence. Cross-connector and missing-file requests return a generic not-found response.

Private evidence responses must retain these controls:

- `Content-Type` from the allowed stored MIME type.
- `Content-Disposition: inline` for preview or `attachment` for download, using a sanitized basename.
- `Cache-Control: private, no-store, max-age=0` and `Pragma: no-cache`.
- `X-Content-Type-Options: nosniff`.
- No public storage URL, path, hash, or document metadata in errors, logs intended for applicants, notifications, or public profile responses.

Connector logos use the configured `public` logo disk, but the public profile renders a logo only when the connector is currently verified or legacy-verified. A logo must not be used to imply current verification for any other status.

### Missing-file investigation

Investigate with connector ID, verification document ID, requirement ID, current round, audit actions, configured disk, and a server-side existence check. Do not paste or emit the storage path, hash, original filename, document number, or internal note into a user-visible error or notification. If the file is absent or the stored disk is not the configured private disk, return the generic not-found response and record only the safe operational facts needed for support.

## Public identity presentation

A `verified` or `legacy_verified` connector may show the exact public claim:

> Verified by Conscious Connections based on submitted organizational and authorization documents.

The public discovery/profile surfaces may show the safe verification badge and eligible logo. `under_review`, `resubmission_required`, `reverification_required`, rejected, suspended, draft, or null verification states use neutral status language and do not expose representative or evidence identity details. Reverification is not presented as a current verified claim.

## Expiration and re-verification

The scheduled command is `connectors:expire-verification-documents`, scheduled daily at `01:15` with overlap protection. The configured warning offsets are exactly `30` and `7` days before expiry. Each warning is emitted at most once per document/offset through its audit action (`document_expiration_warning_30` or `document_expiration_warning_7`).

At or after the expiry date, an accepted, non-superseded, configured-expiring document becomes expired. Requirement rules are recomputed so an alternative accepted document can continue satisfying the requirement. If the requirement set is no longer satisfied, a verified connector moves to `reverification_required` and a pending connector moves to `resubmission_required`. The transition is audited and sends only safe status/action notifications.

Expiration does not automatically suspend a connector, remove memberships, alter roles, cancel seminars, revoke community access, change subscriptions, or remove module entitlements. Any such policy requires a separate approved workflow and release gate.

## Future identity edits

Any future edit that changes a critical organizational identity field—organization name, organization type, primary representative, or equivalent identity used by the public claim—must call the verification service, preserve the current snapshot/audit history, and re-evaluate whether verification is still valid. Do not add a direct model update that leaves a verified claim attached to changed identity data. Review this invariant before enabling any new connector identity-edit route.

## Audit and notifications

Audit entries are the operational source of truth for evidence activity and decisions. Current actions include `uploaded`, `superseded`, `submitted`, `resubmitted`, `document_accepted`, `document_rejected`, `requirement_not_applicable`, `supplemental_requested`, `resubmission_requested`, `connector_approved`, `connector_permanently_rejected`, `connector_suspended`, `document_previewed`, `document_downloaded`, `document_expiration_warning_30`, `document_expiration_warning_7`, `document_expired`, `connector_reverification_required`, and `connector_resubmission_required`.

Notifications are emitted after the relevant transaction commits. Payloads may contain safe event labels, connector/requirement identifiers, public status text, and named application routes. Payloads must not contain raw evidence, document numbers, original filenames, storage paths, hashes, expiry metadata, or internal moderation notes. Notification transport failures are logged/handled as delivery failures and must not create a second state transition.

## Retention and disposal release gate

Production enablement remains blocked until the owner approves an organizational retention schedule covering raw evidence files, connector logos, verification audits, legal holds, backups, and secure disposal. This guide intentionally does not invent a duration or purge policy. Record the approving owner and approval date here before production release:

| Decision | Owner | Approved date | Status |
| --- | --- | --- | --- |
| Organizational retention/disposal schedule for evidence, logos, audits, legal holds, backups, and secure disposal | TBD | TBD | BLOCKED — owner decision required |

Do not implement automated purge or destructive cleanup until that decision is documented and a separately reviewed, recoverable operational procedure exists.

## Release checklist

### Automated checks

Run the exact checks in the implementation plan from the repository root. On Windows, if the Laravel Artisan test wrapper cannot launch a child process because of its working-directory handling, run the equivalent PHPUnit files directly with `vendor/bin/phpunit` and record the wrapper limitation; do not substitute a smaller suite for the full suite.

- Focused requirements, schema, submission, stepper, resubmission, private-access, admin-review, admin-decision, workspace, notification, expiration, and compatibility tests.
- Full PHPUnit suite with coverage disabled.
- `vendor/bin/pint --test`.
- `npm run build`.
- `git diff --check`.
- Named applicant/admin verification route lists, raw Artisan command list, schedule list, and `migrate:status --env=testing`.

### Manual browser and accessibility QA

Before release, manually test desktop and mobile flows for every organization type and category compatibility; logo preview/removal; Cavite address handling; all/any/minimum rules; identity reuse; server-side validation/errors; applicant status and partial resubmission; admin preview/download; accept/reject/N/A/custom supplemental request; approval/rejection/expiration/reverification/legacy-verified presentation; and notification links. Also check keyboard order/focus, Escape behavior, labels, error association, status announcements, target size, PDF/image preview, fallback rendering, responsive layout, zoom, and an ordinary member’s denial of private evidence.

This manual pass must use a real authenticated applicant, admin, ordinary member, and anonymous/public session. Passing automated tests does not replace it.

### Migration and staging gates

- Back up staging and rehearse only the forward migration against an isolated recent-data copy. Do not reset development data.
- Compare connector verification status counts before/after and verify preservation of memberships, roles, seminars, community data, subscriptions, module entitlements, reviews, and legacy-verified connectors.
- Verify the scheduled expiration command, warnings, transitions, idempotency, and audit rows.
- Smoke-test registration through approval, notifications, private evidence authorization, existing connector dashboard/membership/seminar/community paths, subscriptions, and marketplace access.
- Obtain the retention schedule decision above before production enablement.

## Sign-off record

Keep the release report separate from this operating guide and distinguish:

1. Implemented application/documentation scope.
2. Exact automated commands and pass/fail evidence.
3. Manual browser/accessibility QA and its tester/date.
4. Migration rehearsal and staging evidence.
5. Blocked or unrun gates, especially retention, browser QA, backup/rehearsal, and production smoke tests.

The feature is not “complete” from static checks alone while any required manual, staging, retention, or production gate remains unresolved.
