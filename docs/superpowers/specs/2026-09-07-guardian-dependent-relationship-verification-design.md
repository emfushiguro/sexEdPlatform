# Guardian–Dependent Relationship Verification and Multiple Guardians Design

**Date:** 2026-09-07
**Status:** Pending written-spec review
**Phase:** 1 of 3

## Goal

Strengthen the existing Guardian–Dependent relationship system so every new relationship is independently verified, evidence-based, administrator-reviewed, and permission-scoped. A dependent may have multiple guardians, and decisions about one relationship must not affect any other relationship.

This phase extends the existing Laravel architecture. It does not create a parallel relationship or verification system, and it does not represent administrative review as legal adjudication.

## Scope and Phase Boundaries

Phase 1 covers:

- independent Guardian–Dependent relationships;
- multiple guardians per dependent;
- relationship-type verification pathways;
- biological, adoptive, legal-guardian, relative, and caregiver verification;
- multiple categorized evidence documents;
- draft submission, resubmission, and evidence history;
- centralized administrative review;
- relationship-specific permissions;
- revocation, voluntary deactivation, and controlled reactivation;
- legacy-data compatibility;
- authorization, auditing, notifications, and testing.

Phase 1 does not add Health & Support Information or redesign invitation profile and messaging features. Those are isolated in Phases 2 and 3.

The existing invitation flow must remain operational during Phase 1. Its primary and supporting document inputs will be normalized internally to the new evidence collection contract until Phase 2 upgrades that interface.

## Current Architecture and Chosen Approach

The implementation will incrementally strengthen the existing architecture:

- `ParentChildAccount` remains the authoritative relationship model.
- `GuardianRelationshipVerificationDocument` remains the relationship-evidence model.
- `GuardianRelationshipVerificationAudit` remains the immutable relationship audit history.
- `GuardianRelationshipVerificationService` remains the central lifecycle service.
- `GuardianRelationshipTypes` and `config/guardian_relationships.php` remain the verification policy source.
- Existing administration routes and interfaces will be extended.
- Existing notifications, private storage, Laravel policies, and authorization patterns will be reused.

No parallel Guardian–Dependent system will be created. Internal `ParentChild*` names remain temporarily for compatibility. New user-facing language and new domain methods will consistently use “Guardian,” “Dependent,” “Relationship,” and “Administrative Verification.”

Alternatives rejected:

1. Replacing the current tables and workflows with a new `GuardianDependentRelationship` subsystem would provide cleaner names but create duplicate behavior and a high-risk migration.
2. Maintaining an extended dual-system compatibility façade would prolong two sources of truth without enough benefit over directly hardening the existing relationship aggregate.

## Relationship Data Model

Each `parent_child_accounts` row represents exactly one Guardian ↔ Dependent relationship. The existing unique constraint on `parent_user_id` and `child_user_id` permits multiple different guardians for one dependent while preventing duplicate relationships between the same two accounts.

Each relationship independently maintains:

- guardian and dependent identifiers;
- claimed relationship type;
- optional custom relationship description;
- applicant circumstances statement, stored through the existing purpose-limited relationship notes field when the pathway requires it;
- verification pathway captured at claim time;
- operational relationship status;
- verification status;
- current evidence-submission round;
- submission, review, approval, rejection, revocation, and deactivation timestamps;
- reviewing administrator;
- rejection reason and administrative note;
- legacy compatibility marker;
- relationship permissions;
- created, updated, and soft-deletion timestamps.

Capturing the verification pathway on the relationship preserves the basis under which it was reviewed even if pathway configuration changes later.

No guardian is automatically designated as the dependent’s primary or sole guardian.

## Relationship and Verification States

Operational status and verification status remain separate.

| Event | Relationship status | Verification status | Access |
|---|---|---|---|
| Relationship claimed | `pending` | `pending` | None |
| Evidence being prepared | `pending` | `pending` | None |
| Evidence submitted | `pending` | `under_review` | None |
| More evidence requested | `pending` | `resubmission_required` | None |
| Approved | `active` | `verified` | Permission-based |
| Final rejection | `rejected` | `rejected` | None |
| Voluntary deactivation | `inactive` | Retain previous verification result | None |
| Reactivation requested | `pending` | `under_review` | None |
| Administratively revoked | `revoked` | `revoked` | None |

`not_required` and `reserved` will no longer be assigned to newly claimed relationships. They remain readable during the legacy migration period.

A relationship grants access only when all of these conditions hold:

```text
guardian account is active
dependent account is active
guardian identity verification is approved
relationship_status = active
relationship_verified_status = verified
requested relationship permission = true
```

Selecting a relationship type, accepting an invitation, or uploading evidence never activates access by itself.

## Controlled State Transitions

All state changes pass through `GuardianRelationshipVerificationService`. Controllers, jobs, seeders, and administrative actions may not directly update verification fields.

The service will expose focused operations for:

- creating or initializing a claim;
- submitting evidence;
- requesting resubmission;
- approving;
- rejecting;
- revoking;
- voluntarily deactivating;
- requesting reactivation.

Every transition will:

1. authorize the actor;
2. lock the relationship row;
3. confirm the current state permits the requested transition;
4. validate the current evidence round where applicable;
5. update operational and verification states atomically;
6. write an audit entry;
7. dispatch notifications after the transaction commits.

Approval atomically changes the relationship to `active` and `verified`. A failure may not leave a relationship verified but inactive or active but unverified.

## Relationship Types and Verification Pathways

The claimed family or caregiving relationship remains distinct from the verification pathway.

| Claimed relationship | Verification pathway |
|---|---|
| Biological mother/father | Biological parent |
| Adoptive parent | Adoptive parent |
| Foster parent | Non-parental care/placement |
| Legal guardian | Guardianship/authority |
| Court-appointed guardian | Court-appointed guardianship |
| Grandmother/grandfather | Non-parental care/custody |
| Aunt/uncle | Non-parental care/custody |
| Older sister/brother | Non-parental care/custody |
| Other relative | Non-parental care/custody |
| Family friend/caregiver | Non-parental care/custody |
| Other | Custom evidence-based review |
| Legacy `parent` | Legacy compatibility only |

The legacy `parent` value will not be selectable for new claims.

Pathways are configuration-driven. Each pathway defines:

- accepted evidence categories;
- core evidence-category groups;
- optional supporting categories;
- whether a circumstances statement is required;
- user-facing instructions;
- administrative review guidance;
- pathway-specific rejection reasons.

Changing a relationship label never establishes authority automatically.

## Evidence Categories

Evidence categories describe what an applicant submitted without claiming that it conclusively determines legal status. Categories may include:

- civil-registry or parentage evidence;
- adoption-related order or record;
- court order;
- guardianship documentation;
- government or agency placement documentation;
- official appointment documentation;
- custody or caregiving-arrangement evidence;
- other official supporting evidence;
- other contextual supporting evidence.

The system will not require every aunt, grandparent, sibling, or caregiver to possess one particular document. Non-parent applicants may submit a relevant combination of official evidence and a circumstances statement.

The adoptive-parent pathway requires qualifying adoption-related or court evidence but does not rely on a fixed number of files. Multiple documents are supported and encouraged, and administrators may request additional evidence. Passing file validation never results in automatic approval.

## Multiple-Document Evidence Model

Existing evidence records will be extended with:

- submission round;
- evidence category;
- document side: `front`, `back`, or `not_applicable`;
- optional front/back pairing identifier;
- display order;
- SHA-256 content hash;
- submitted timestamp;
- superseded timestamp where applicable.

Existing relationship ID, uploader, private disk and path, original filename, MIME type, file size, and timestamps remain.

The database continues storing one row per file. Evidence will not be consolidated into an opaque JSON payload. The invitation’s staged JSON remains only a pre-acceptance transport format and will adopt the same evidence metadata contract in Phase 2.

## Evidence Submission Rounds

Evidence is editable while it is a draft and immutable after submission.

1. The applicant selects multiple files.
2. The browser validates basic count, type, and size.
3. Images and PDFs receive local previews.
4. Files can be reordered, categorized, removed, or replaced.
5. Server-side validation repeats every rule.
6. The server hashes files and detects exact duplicates.
7. Valid evidence is stored privately and assigned to the current round.
8. Submission locks the round and moves the relationship to `under_review`.
9. A resubmission request opens a new round.
10. Earlier rounds remain restricted but available to administrators as history.

Previous submitted evidence will not be overwritten or silently deleted.

Where a multi-step workflow must persist files temporarily, it will reuse the application’s private temporary-upload pattern with owner-bound tokens and expiration cleanup. A single-page submission may keep previews locally until final submission instead of persisting unnecessary drafts on the server.

## Upload Validation and Storage Security

Standard limits are:

- no more than 10 files per evidence round;
- no more than 5 MB per file;
- PDF, JPEG, PNG, and WebP files;
- valid MIME type and file signature;
- exact duplicate detection within the current submission;
- sanitized display filenames;
- randomized private storage paths.

Validation is enforced server-side even when JavaScript provides earlier feedback.

Documents never use public storage URLs. Preview and download operations pass through authorized controllers. Images may render inline, PDFs may use an embedded viewer, and other supported responses use controlled download headers.

A failed database operation triggers file compensation cleanup. A failed file move prevents the database transaction from finalizing the submission.

## Focused Service Boundaries

- `GuardianRelationshipTypes`: relationship labels and pathway mapping.
- Relationship configuration: accepted categories, instructions, and rejection reasons.
- `GuardianRelationshipVerificationService`: state transitions and administrative decisions.
- Evidence-management service: validation, hashing, storage, submission rounds, and cleanup.
- `ParentChildAccount`: relationships, casts, labels, status predicates, and query scopes.
- Laravel policies: relationship viewing, evidence submission, evidence access, and administration.
- Controllers: request orchestration only.
- Form requests: actor authorization and server-side input validation.
- Notifications: status communication without evidence contents.

New query scopes will centralize:

- verified and active relationships;
- relationships belonging to a guardian;
- relationships belonging to a dependent;
- relationships awaiting administrative review;
- relationships requiring resubmission.

## Dependent Account Creation

The existing dependent-registration workflow remains in place.

1. Confirm the creating guardian has an active account and approved guardian identity.
2. Create or continue creating the dependent account.
3. Select the claimed relationship type.
4. Resolve the verification pathway.
5. Explain the pathway and evidence purpose.
6. Collect required relationship evidence.
7. Create the relationship as pending.
8. Submit evidence for administrative review.
9. Keep relationship privileges disabled.
10. Activate the relationship only after approval.

Existing dependent account verification remains separate from relationship verification. A dependent’s account-verification decision and a guardian’s relationship-verification decision must not overwrite one another.

The later Health & Support Information step will not be added during Phase 1.

## Multiple-Guardian Behavior

All guardian queries will operate on relationship collections rather than a singular parent field.

```text
Dependent
├── Guardian A — active + verified
├── Guardian B — active + verified
└── Guardian C — pending + under review
```

Expected behavior:

- Guardian A and Guardian B receive only their configured permissions.
- Guardian C receives no relationship privileges.
- Approving Guardian C changes only Guardian C’s relationship.
- Revoking Guardian A does not affect Guardian B.
- Deleting or rejecting an invitation affects no existing relationships.
- Removing every guardian does not delete or disable the dependent account.
- No fixed maximum number of guardians is imposed.
- Only one current relationship or pending invitation is allowed for the same guardian–dependent pair.

## Relationship Permissions

Permissions remain relationship-specific. On approval, existing defaults are preserved:

- `can_view_progress = true`;
- `can_view_quiz_answers = true`;
- `can_approve_content = false`.

Administrators may alter these permissions independently for each verified relationship. Permission changes are audited.

Phase 3 may introduce separate health-support permissions. Those permissions will not be added or inferred during Phase 1.

Authorization checks must use the particular relationship row being exercised. They may not ask only whether a user “is a parent” or whether a dependent “has a parent.”

## Revocation, Deactivation, and Restoration

Administrators may revoke a relationship for verification, safety, or policy reasons.

A guardian or dependent may voluntarily deactivate their relationship. Deactivation:

- immediately removes relationship privileges;
- does not affect other guardian relationships;
- preserves audit and verification history;
- notifies the other party;
- does not delete either user account.

Reactivation does not simply flip the relationship back to active. It creates a fresh administrative review event and may require updated evidence.

A revoked relationship cannot be self-reactivated. It requires an administrator-authorized new review.

## Centralized Administrative Review

The existing Guardian/Dependent verification interface will be extended rather than replaced.

The list interface will provide filters for guardian or dependent, relationship type, verification pathway, operational status, verification status, submission date, and legacy status.

The review interface will show:

- guardian avatar and name;
- guardian identity-verification status;
- restricted identity evidence where needed for comparison;
- dependent avatar and basic identity context;
- claimed relationship;
- verification pathway;
- applicant circumstances statement;
- current evidence round;
- previous evidence rounds;
- categorized front/back documents;
- submission and decision timeline;
- existing relationship permissions and status.

Available actions remain minimal: approve, request resubmission, reject, and revoke.

Actions use confirmation modals and configured rejection reasons. Technical storage paths, internal hashes, and unnecessary database details are not shown.

The interface describes its function as administrative verification of submitted identity and relationship evidence, not legal adjudication.

## Evidence Preview and Download

Each evidence item provides:

- a friendly category label;
- original filename;
- file type and size;
- front/back indicator;
- upload date and uploader;
- inline image or PDF preview;
- zoom or open action where supported;
- authorized download action.

Only the submitting guardian for their own relationship and authorized administrators may access evidence. Dependent learners, instructors, other guardians, and unrelated administrators without the required permission do not receive document access.

Document routes confirm both relationship ownership and document membership to prevent cross-relationship enumeration.

## Notifications

Guardian and dependent receive appropriate notifications for relationship claim or invitation acceptance, evidence submission, resubmission request, approval, final rejection, revocation, voluntary deactivation, and reactivation submission.

Notifications contain status and navigation context but not evidence images, government identifiers, document contents, private administrative notes, or sensitive identity details. Administrative submission notifications continue using the existing notification infrastructure.

## Audit History

Every material event records:

- relationship;
- actor;
- action;
- previous and new states;
- evidence round where applicable;
- standard reason code;
- optional administrative note;
- timestamp.

Audited events include claim creation, submission, resubmission requests, resubmission, approval, rejection, revocation, deactivation, reactivation requests, and permission changes.

Submitted evidence history remains immutable. Audit entries do not copy raw evidence contents or sensitive identifiers.

## Authorization and Privacy

Relationship evidence can contain sensitive identity and family information. Handling will follow purpose limitation, data minimization, transparency, restricted access, appropriate security, and retention no longer than necessary. These principles align with the Philippine National Privacy Commission’s [Implementing Rules and Regulations of the Data Privacy Act of 2012](https://privacy.gov.ph/implementing-rules-regulations-data-privacy-act-2012/).

The implementation will:

- explain why evidence is collected;
- collect only pathway-relevant evidence;
- avoid claiming that a document automatically proves legal authority;
- keep documents in private storage;
- require server-side authorization for every access;
- exclude evidence contents from logs and notifications;
- use organization-approved configurable retention rules;
- permit controlled correction or resubmission;
- require product/privacy-owner review of final wording.

The platform performs administrative verification of submitted evidence. It does not determine legal parenthood, guardianship, adoption, or custody.

## Error Handling and Concurrency

Controls include:

- database transactions for relationship and audit updates;
- row locking during submission and administrative decisions;
- unique Guardian–Dependent constraints;
- status revalidation inside transactions;
- idempotent handling of repeated requests;
- file cleanup compensation on transaction failure;
- rejection of missing, corrupted, oversized, or mismatched files;
- prevention of approval without a valid current evidence round;
- conflict responses for stale administrative pages;
- denial when either account is suspended, archived, or otherwise ineligible.

Two administrators attempting conflicting decisions cannot both succeed. The second request receives a clear stale-state response.

## Legacy Migration and Compatibility

The rollout uses additive migrations:

1. Add pathway and evidence-round metadata.
2. Add supporting indexes and constraints.
3. Backfill normalized states.
4. Mark existing declaration-based relationships as legacy-compatible.
5. Introduce verified-active query scopes.
6. Update callers to use relationship collections.
7. Stop treating `learner_profiles.parent_user_id` as authoritative.
8. Retain temporary compatibility reads.
9. Remove obsolete singular behavior in a later release.

Existing active relationships will not suddenly lock users out. Records activated through `not_required` or legacy declaration logic remain identifiable for optional administrator-triggered reverification. New claims cannot use the legacy bypass.

The migration will not modify unrelated account, lesson, enrollment, chat, or community data.

## Existing Invitation Compatibility

Phase 1 does not redesign the invitation page.

- Existing invitations retain their consent lifecycle.
- Current primary and supporting files are normalized into the new evidence collection contract.
- Acceptance continues creating a pending relationship.
- Evidence transfers into relationship-scoped document rows.
- Administrative review remains mandatory.
- Acceptance never activates the relationship.
- An existing learner’s dashboard and ordinary eligible chat access remain unaffected by a pending claim.

Phase 2 will add richer guardian context, an invitation-scoped profile, a multi-file invitation uploader, and the permitted message-request entry point.

## Testing Strategy

### Unit Tests

- relationship type-to-pathway mapping;
- accepted evidence categories;
- new relationships never receiving `not_required`;
- state-transition guards;
- verified-active query scopes;
- permission predicates;
- duplicate hashing and side pairing;
- evidence-round grouping.

### Feature Tests

- biological-parent claim, submission, approval, and activation;
- adoptive-parent multi-document submission;
- document categorization and front/back metadata;
- relative/caregiver flexible evidence submission;
- resubmission preserving prior evidence;
- unauthorized evidence-access denial;
- administrative approval, rejection, resubmission, and revocation;
- voluntary deactivation and controlled reactivation;
- suspended-account restrictions;
- server-side count, type, MIME, and size validation;
- notification recipient correctness;
- audit-history correctness;
- legacy-record compatibility.

### Multiple-Guardian Tests

- add and approve Guardian A;
- add and approve Guardian B;
- confirm both relationships coexist;
- assign different permissions;
- revoke Guardian A;
- confirm Guardian B remains active;
- confirm Guardian A cannot access protected dependent resources;
- confirm Guardian B retains access.

### Browser End-to-End Tests

- add, preview, categorize, reorder, replace, and remove files;
- submit multiple adoptive-parent documents;
- review the complete evidence set as an administrator;
- use confirmation and rejection modals;
- verify evidence zoom and authorized download;
- confirm status and notification updates.

### Regression Tests

- existing dependent creation;
- existing learner invitations;
- learner dashboards;
- guardian progress views;
- chat and moderation;
- notifications;
- child account verification;
- administrative verification queues;
- enrollment and content-approval behavior.

## Completion Criteria

Phase 1 is complete when:

- multiple guardians can coexist for one dependent;
- each relationship has independent states, evidence, history, and permissions;
- every new relationship requires administrative approval;
- relationship types resolve to appropriate configurable pathways;
- biological relationships no longer activate through declaration alone;
- adoptive applicants can submit and manage multiple documents;
- non-parent relatives use flexible care/custody evidence;
- evidence is private, categorized, versioned by round, and reviewable;
- rejecting or revoking one relationship does not affect another;
- legacy relationships continue working without enabling new bypasses;
- server-side validation and authorization cover every critical operation;
- phase-specific and regression tests pass.

## Non-Goals

Phase 1 will not:

- determine legal parenthood or guardianship;
- automatically approve documents;
- create a new legal-document registry;
- require medical information;
- add Health & Support Information fields;
- expose evidence to instructors;
- redesign invitation profiles or messaging;
- rename every legacy table and route;
- replace working chat, notification, or administration systems.

## Delivery Sequence

After this written specification is approved:

1. Write a dedicated Phase 1 implementation plan.
2. Implement Phase 1 with test-first checkpoints.
3. Run phase-specific and regression verification.
4. Write the Phase 1 verification report.
5. Begin the separate Phase 2 invitation and messaging design.
