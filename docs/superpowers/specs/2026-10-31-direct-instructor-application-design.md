# Direct Instructor Application with Two-Stage Verification Design

**Status:** Approved for implementation planning

**Date:** 2026-10-31

**Scope:** Direct instructor recruitment, applicant accounts, identity verification, administrator identity review, reuse of the existing instructor application, final instructor activation, and full-stack verification

## Summary

Conscious Connections will allow an adult prospective instructor to apply without first registering as a learner. A direct applicant receives an applicant account with no learner profile, learner access, or instructor permissions. The applicant completes personal information, Persona Government ID plus Selfie verification, and an administrator identity review before the existing instructor application becomes available.

Existing adult learners continue applying through their dashboard. A current approved learner identity decision satisfies Stage One because an administrator has already approved that learner's identity. It is not necessary to start a duplicate Persona inquiry. Existing learners retain learner access while their instructor application is pending.

Persona remains evidence for a local administrator decision. A successful provider result never approves Stage One, submits Stage Two, assigns a role, or unlocks instructor functionality by itself.

The complete flow is:

~~~text
Be Our Partner
    |
    v
Create applicant account or use an existing learner account
    |
    v
Verified email + personal information + age 18 or older
    |
    v
Stage One identity verification
    |-- current approved learner identity -> Stage One satisfied
    +-- direct applicant -> Persona GovID + Selfie -> administrator identity review
    |
    v
Stage Two existing instructor application
    |
    v
Final administrator application review
    |
    v
Instructor role and profile activation
~~~

## Approved Product Decisions

| Concern | Approved decision |
| --- | --- |
| Minimum age | Every instructor applicant must be at least 18. |
| Direct account classification | Use an applicant role through the existing role system. |
| Direct applicant privileges | No learner profile, learner role, learner permissions, instructor role, or instructor permissions. |
| Existing learner path | Existing learners apply from their dashboard and retain learner access while pending. |
| Existing learner identity | A current approved learner identity decision satisfies Stage One. |
| Email verification | Required before Persona or Stage One submission. |
| Address scope | Preserve the current Cavite PSGC scope. |
| Persona template | Use the configured Government ID plus Selfie template selected on the server. |
| Persona authority | Persona supplies evidence; an administrator decides Stage One. |
| Identity comparison | Compare only corresponding documented attributes and treat missing data explicitly. |
| Sandbox behavior | Match the current learner-registration Sandbox safeguards and manual-review implications. |
| Administrator presentation | Follow the existing learner-verification review-page format. |
| Stage Two | Reuse the existing instructor application, validation, review history, and activation service. |
| Government ID duplication | Do not require a second government-ID upload after approved Stage One evidence. |
| Documents | Store new instructor documents privately and migrate legacy public files through recoverable tooling. |
| Final activation | Assign instructor access only after final Stage Two approval. |
| Browser verification | Add automated Playwright tests and a supervised full-stack Playwright QA agent. |
| Implementation location | Work in the current checkout because the design depends on the current Persona implementation; preserve unrelated changes. |

## Goals

- Add a factual public recruitment entry point without unsupported promises.
- Allow an adult direct applicant to create an account without becoming a learner.
- Reuse the existing authentication, email-verification, role, profile, PSGC, Persona, notification, and administrator patterns.
- Give direct applicants a resumable application workspace.
- Preserve current approved learner identity decisions as valid Stage One evidence.
- Use Persona GovID plus Selfie for direct-applicant identity evidence.
- Compare declared personal information with corresponding Persona attributes without persisting raw provider payloads.
- Require an authorized administrator to approve Stage One.
- Keep identity state, instructor application state, account status, and role separate.
- Reuse the existing Stage Two credibility application and final approval behavior.
- Store sensitive instructor documents privately.
- Preserve existing learner, guardian, dependent, relationship, Persona, and instructor workflows.
- Add focused PHP, JavaScript, Playwright, browser, and Sandbox verification.

## Non-goals

- Persona production activation.
- Treating a Sandbox result as genuine identity proof.
- Automatic Stage One approval from Persona.
- Automatic Stage Two approval.
- Assigning a learner role or learner profile to a direct applicant.
- Granting instructor permissions before final approval.
- Replacing learner, guardian, dependent, or relationship verification.
- Converting applicant accounts into learner accounts.
- Supporting guardian-registration accounts as instructor applicants in this release.
- Refactoring all identity workflows into a generic polymorphic framework.
- Expanding PSGC coverage beyond the existing Cavite scope.
- Adding new instructor qualifications, compensation claims, employment promises, or benefit promises.
- Inventing a retention policy.
- Claiming legal, privacy, security, accessibility, or regulatory certification.

## Existing Architecture and Constraints

The implementation extends the current application instead of building a parallel system:

- Registration currently creates learner accounts and learner identity cases. The direct route must deliberately create an applicant instead.
- The users table and Spatie roles form a synchronized transitional role system. Applicant classification must keep both mechanisms consistent.
- User names and birthdate already live on users. Generic profile information lives in user_profiles, while learner-specific information and PSGC codes live in learner_profiles.
- The current instructor application Form Request and controller allow only learners.
- InstructorApplicationService creates the application, records reviews, changes the primary role, creates the instructor profile, writes a role transition, and sends notifications.
- Persona already has a server-side client, template selector, attempt ledger, signed webhook, delivery ledger, queued reconciliation, policy evaluator, protected evidence proxy, and learner/guardian administrator review integration.
- Current Persona orchestration assumes learner or guardian context and must gain an explicit instructor-identity context.
- Existing instructor application documents are stored on the public disk. New evidence must not continue that behavior.
- Existing approved, pending, and rejected instructor application records must remain valid.
- Existing Persona and relationship work in the checkout is user-owned and must not be overwritten or reformatted incidentally.

## Account Eligibility and Classification

### Direct applicants

A direct applicant must:

- be at least 18 based on the saved birthdate;
- have a verified email address before starting Persona;
- have active account status;
- have no active instructor application;
- not already be an instructor or administrator; and
- not be a guardian-registration account in this release.

The account is assigned the applicant primary role through the existing role synchronization service. The role grants no learner or instructor permissions. Applicant routes use explicit policies and ownership checks rather than relying only on a broad role name.

The application will add an instructor-applicant account-type constant where account classification requires one. Account status remains independent: an applicant may have an active account while still having no platform learning or instructor privileges.

### Existing learners

An existing learner:

- uses the current account and dashboard entry point;
- must be at least 18;
- must have a verified email address;
- retains learner access while pending;
- does not receive an applicant role;
- reuses saved personal information;
- must have a current approved learner identity case before Stage Two; and
- remains subject to the existing account, suspension, and identity access rules.

An existing learner without a current approved identity decision is directed to the existing learner identity workflow. The application does not create a second instructor-specific Persona attempt for that learner.

### Login and duplicate email handling

The current login remains the single account entry point. After authentication:

- an applicant is directed to the instructor application hub;
- an instructor is directed to the instructor dashboard;
- a learner follows the existing learner redirect; and
- an administrator continues using the administrator portal.

If a registration email already belongs to an account, the direct registration flow asks the user to sign in and resume with that account. It never creates or merges a duplicate identity automatically.

## Personal Information and PSGC Validation

### Required fields

- first name;
- last name;
- birthdate;
- verified account email;
- Cavite city or municipality;
- barangay; and
- supported government-ID choice for a direct Persona attempt.

### Optional fields

- middle name or initial;
- suffix;
- gender;
- contact number; and
- address line.

Direct-applicant personal information uses users plus the generic user profile. Existing learners use users plus learner_profiles. A single resolver presents a consistent value object to the Stage One service without copying an existing learner into user_profiles solely for this feature.

The generic user profile receives additive nullable PSGC fields and an optional address line. Legacy location text remains untouched.

### Server-side location rules

The request must:

- resolve the city from the cities table;
- confirm its province_code is the existing Cavite code;
- resolve the barangay from the barangays table;
- confirm its city_code matches the selected city;
- derive all display labels from those records; and
- reject missing, mismatched, foreign, or fabricated codes.

The browser may filter choices for usability, but browser labels and relationships are never authoritative.

### Snapshot rules

Before Persona begins, the user may correct personal information. Starting the current attempt freezes a declared-information snapshot on the Stage One case. Material identity changes after that point require an explicit replacement attempt. Administrators cannot silently rewrite declared identity information.

## Stage One Data Model

### instructor_identity_verifications

One row represents one local Stage One submission round. Proposed fields are:

- id;
- user_id;
- source, distinguishing direct Persona evidence from an existing learner identity approval;
- nullable learner_identity_verification_id;
- internal status;
- submission_round;
- declared_snapshot;
- nullable comparison_summary;
- sandbox_manual_review;
- resubmission_allowed;
- nullable reviewed_by;
- nullable submitted_at;
- nullable reviewed_at;
- nullable approved_at;
- nullable rejected_at;
- nullable action_required_at;
- nullable reason_code;
- nullable reason_note;
- nullable requested_actions;
- nullable superseded_at; and
- Laravel timestamps.

The table has indexes for the current user case, review queues, and submission history. A unique user and submission-round constraint plus transaction locking prevents duplicate rounds. Service rules ensure there is only one current non-superseded case.

Internal statuses are:

- awaiting_verification;
- verification_in_progress;
- pending_review;
- approved;
- action_required; and
- rejected.

These are application statuses, not Persona status values.

### instructor_identity_audits

The append-only audit table records:

- instructor identity case;
- nullable actor;
- action;
- previous status;
- next status;
- submission round;
- nullable Persona inquiry;
- reason;
- privacy-minimized metadata; and
- creation time.

It does not store raw Persona bodies, document numbers, image URLs, image bytes, scores, or biometric data.

### persona_inquiries

Add a nullable instructor_identity_verification_id and an instructor-government flow type. A Persona inquiry belongs to one supported local context. Application validation prevents a single attempt from being treated as both learner and instructor evidence.

The existing attempt number, target round, idempotency, provider identifiers, policy outcomes, timestamps, and supersession rules remain authoritative for provider lifecycle.

### instructor_applications

Additive Stage Two fields are:

- nullable instructor_identity_verification_id for legacy compatibility;
- draft as an application status;
- nullable submitted_at;
- nullable government_id_path for identity-linked applications; and
- document_disk or equivalent storage metadata.

Existing rows default to their legacy storage behavior and remain reviewable. Identity enforcement applies only to new submissions at or after an explicit cutover.

## State Separation

The following axes stay independent:

1. Persona inquiry lifecycle.
2. Persona policy outcome.
3. Declared-versus-provider comparison result.
4. Local instructor identity-review status.
5. Instructor application status.
6. Account status.
7. Current user role and permissions.

Persona completion may move a direct case to pending_review when the required evidence is authoritative and reviewable. It cannot write approved to the local identity case.

Only local Stage One approved status unlocks Stage Two. Only local Stage Two approved status triggers instructor activation.

## Stage One State Transitions

Allowed transitions are:

~~~text
awaiting_verification
    -> verification_in_progress

verification_in_progress
    -> pending_review
    -> action_required

pending_review
    -> approved
    -> action_required
    -> rejected

action_required
    -> verification_in_progress
    -> rejected

approved
    -> action_required only through an authorized revocation or material-change workflow

rejected
    -> action_required only when an authorized administrator reopens the case
~~~

Every transition is implemented in one service, validated under transaction and row lock, and written to the audit ledger.

## Existing Learner Identity Reuse

For an adult existing learner, the Stage One service:

1. locks the learner and current identity case;
2. confirms the identity case is current and locally approved;
3. creates or resolves the current instructor identity case;
4. records source as learner identity approval;
5. links the learner identity record;
6. sets Stage One approved;
7. copies no Persona payload or local evidence;
8. records an audit entry; and
9. unlocks Stage Two.

The authority is the previous administrator decision. A provider result by itself is insufficient.

If the source learner identity is superseded, revoked, or no longer access-eligible, it cannot unlock a new Stage Two submission. A Sandbox-manual learner approval carries its existing Sandbox restriction into the instructor case.

## Direct Applicant Persona Flow

### Template selection

The direct flow always selects the configured GovID plus Selfie template on the server. The browser submits only a validated supported ID choice. A template ID is never accepted from request data.

Government-ID selection reuses the current Philippine mappings and deny-by-default policy. A choice is available only when it has an exact configured application-to-Persona mapping.

### Inquiry lifecycle

The application:

- locks the applicant and current instructor identity case;
- reuses a current resumable attempt;
- creates at most one current attempt for the submission round;
- uses stable reference and idempotency keys;
- opens the hosted flow in the current application experience;
- uses the return route only to trigger authoritative synchronization;
- supports explicit resume, refresh, expiration, and replacement;
- retains superseded attempts for history; and
- routes the applicant back to the instructor application hub.

The existing Persona client remains the only component that holds credentials or makes authenticated provider calls.

### Webhook processing

The existing signed webhook continues to:

- validate the raw body;
- accept active secret rotation sets;
- reject invalid or stale signatures;
- deduplicate event IDs;
- persist only the small event ledger;
- queue reconciliation;
- refetch the authoritative inquiry and typed verification resources; and
- reject older provider timestamps from regressing newer state.

No browser return, client callback, request parameter, or webhook outcome field can declare success.

## Personal-Information Comparison

A dedicated instructor comparison service receives the frozen declared snapshot and the authoritative Government ID verification resource already fetched by the synchronizer.

Comparable fields are:

- first name;
- middle name when available on both sides;
- last name;
- suffix;
- birthdate; and
- city or other address components actually supplied by Persona.

Email, telephone number, PSGC codes, and barangay are not considered matches when Persona does not provide a corresponding documented value.

Normalization may:

- trim leading and trailing whitespace;
- collapse repeated whitespace;
- normalize letter case;
- ignore harmless name punctuation; and
- normalize documented date formats.

It does not use broad fuzzy matching, edit-distance guessing, phonetic equivalence, or silent date correction. Birthdate equality is exact after parsing.

Each field is classified as:

- match;
- mismatch;
- missing_from_applicant;
- missing_from_persona;
- unavailable; or
- not_comparable.

The comparison summary stores field names, outcomes, and a normalization version. It does not store the provider's raw identity values.

A mismatch or missing value:

- is never treated as proof of fraud;
- is never treated as a match;
- does not automatically reject the applicant;
- is clearly shown to the administrator; and
- requires an explicit administrator decision and rationale.

Provider identity values needed for human comparison are fetched on demand through an authorized server-side review service and are not copied into application storage.

## Sandbox Behavior

The instructor flow reuses the current learner-registration Sandbox implications:

- Persona environment must remain sandbox;
- the existing local and staging manual-review flags govern fallback behavior;
- Sandbox warnings and evidence-source labels remain visible;
- reported country, class, status, and policy outcomes are never rewritten;
- required evidence and reviewer confirmations remain enforced;
- Sandbox-derived approvals receive a durable marker;
- disabling the applicable environment flag invalidates access recognition for that marked approval; and
- production-sensitive activation is blocked.

Local or explicitly enabled staging environments may demonstrate the complete application and role-activation flow. That is application workflow evidence, not genuine identity verification.

Instructor access checks must recognize identity-linked Sandbox approvals in the same fail-closed manner used by current learner access. Legacy instructors and legacy applications are grandfathered rather than forced into the new gate.

## Administrator Identity Review

### Workspace

The existing Instructor Applications workspace gains:

- Identity Review;
- Application Review;
- Approved;
- Action Required; and
- Rejected queues.

The review detail presentation follows the current learner-verification page format without moving the case into the parent-verification domain.

### Review content

Administrators see:

- declared personal information;
- evidence source;
- Persona status and policy summary;
- visible Sandbox warning;
- protected Government ID and selfie previews;
- side-by-side declared and provider attributes;
- per-field match, mismatch, missing, and unavailable markers;
- current submission round and Persona attempt;
- relevant timestamps;
- requested actions;
- decision history; and
- the current stage and next valid action.

Persona evidence is retrieved through the existing protected media pattern. Upstream URLs and provider bytes are never exposed in HTML, JavaScript, logs, or stored metadata.

### Decisions

An authorized administrator may:

- approve Stage One;
- request re-verification or additional action; or
- reject Stage One with a structured reason.

A mismatch approval requires a rationale. Non-reviewable provider evidence cannot be silently overridden outside the existing controlled Sandbox fallback.

Forms bind the displayed submission round and Persona attempt. A stale decision returns a conflict and requires reload.

### Authorization

The workspace requires:

- administrator authentication;
- a dedicated instructor-application review permission;
- Form Request or policy authorization;
- object-level evidence authorization; and
- CSRF protection.

The permission is assigned idempotently to the existing administrator role. Applicants, learners, instructors, and unrelated users cannot decide or view another applicant's case.

## Applicant Application Hub

The authenticated hub displays:

1. Account and personal information.
2. Identity verification.
3. Administrator identity review.
4. Instructor application.
5. Final application review.

It includes:

- a responsive stage tracker;
- current status;
- next permitted action;
- completed-stage indicators;
- pending and action-required explanations;
- validation and review reasons;
- resume, refresh, or replacement actions when allowed;
- Stage Two only after Stage One approval;
- safe withdrawal actions; and
- privacy-minimized history.

Existing learners enter from the learner dashboard. If their identity is already approved, the hub shows Stage One satisfied and links Stage Two without requesting another inquiry.

## Stage Two Instructor Application

Stage Two continues using the current instructor application form, request validation, service, review model, notifications, role transition, and instructor profile creation.

It continues collecting only established requirements:

- educational background;
- NBI or police clearance;
- CV or resume;
- professional background or bio;
- at least one existing Tier Two expertise document; and
- existing declarations and acknowledgements.

The form does not request another government ID for an identity-linked application. The administrator review displays the linked Stage One evidence summary instead. Legacy applications continue displaying their stored government-ID evidence.

### Access gate

The create, draft, upload, submit, and document actions all require:

- the authenticated applicant owns the case;
- Stage One is approved and access-eligible;
- the applicant is at least 18;
- the applicant's email remains verified;
- the account is active;
- there is no other active Stage Two record; and
- the user is not already an instructor or administrator.

UI visibility is supplementary; these rules are enforced on the server.

### Draft and resume

Stage Two adds explicit draft support:

- validated text fields are saved;
- validated files are stored privately;
- existing draft files may be retained or replaced;
- replaced draft files are deleted only after the new file is safely stored;
- final submission revalidates every required field and file; and
- draft-to-pending transition is atomic.

Row locks and active-record checks prevent concurrent duplicate drafts or submissions.

### Rejection and resubmission

Stage Two rejection preserves the current structured reason, message, notification, and review history.

A permitted reapplication creates a new application attempt. It may link the still-current Stage One approval. Stage One repeats only when evidence is invalidated, personal information materially changes, or an authorized administrator requires re-verification.

## Document Storage and Delivery

All new instructor application documents use private storage.

An authenticated document controller:

- confirms applicant ownership or administrator review permission;
- resolves the recorded disk and path;
- validates expected content type;
- streams content without revealing the storage path;
- sets private, no-store, and nosniff headers; and
- returns a generic unavailable response for missing files.

Public Storage URLs, arbitrary remote URLs, and direct provider URLs are not emitted for sensitive evidence.

### Legacy migration tooling

A recoverable command migrates legacy instructor files:

- dry-run is the default or explicitly available;
- each source and resolved target are validated;
- files are copied to private storage;
- size and hash are checked before metadata changes;
- database updates are transactional per application or safe batch;
- missing or invalid files are reported with sanitized identifiers;
- source deletion is a later explicit step after verification; and
- no application row is deleted or rewritten merely because a file is missing.

The implementation will not run destructive storage cleanup without explicit authorization and an approved retention decision.

## Final Application Review and Activation

Final approval runs in one transaction and:

1. locks the user and application;
2. confirms the current application is pending;
3. confirms the linked Stage One case remains approved and access-eligible;
4. confirms age is still at least 18;
5. confirms the email remains verified;
6. confirms account status permits activation;
7. records the application review;
8. records the role transition;
9. assigns the instructor role through RoleSyncService;
10. creates or updates the instructor profile;
11. preserves historical learner data;
12. marks the application approved; and
13. sends notifications after commit.

A direct applicant transitions from applicant to instructor and never receives a learner profile. An existing learner follows the current learner-to-instructor primary-role transition while retaining historical learner records.

Pending, rejected, withdrawn, abandoned, inactive, suspended, or Sandbox-ineligible users cannot access instructor routes.

After approval, the next login goes to the instructor dashboard. Missing optional profile information is presented as a profile-completion prompt rather than another approval gate.

## Public Recruitment Experience

The landing page receives a restrained Be Our Partner action in:

- desktop navigation;
- mobile navigation;
- a dedicated partner section; and
- footer navigation.

The dedicated recruitment page explains:

- the Conscious Connections educational mission;
- the two-stage review;
- what an applicant should prepare;
- identity and document privacy expectations;
- that verification does not guarantee instructor approval;
- that Persona is a Sandbox integration where applicable;
- how an existing learner continues with the current account; and
- how a new applicant starts or resumes.

The page provides Start, Resume, and Sign In actions. It does not promise compensation, employment, approval, audience size, subscriptions, or unsupported benefits.

## UI and Accessibility

The implementation uses existing Blade, Tailwind, layout, card, button, badge, alert, progress, spinner, empty-state, and form patterns.

Requirements include:

- responsive desktop and mobile layouts;
- semantic headings and labels;
- keyboard-accessible actions;
- visible focus states;
- status text that does not rely only on color;
- actionable validation summaries;
- loading and repeated-submission prevention;
- empty, pending, action-required, rejected, and approved states;
- reduced-motion compatibility;
- restrained motion consistent with the current landing page; and
- clear Sandbox, evidence-source, and privacy language.

The stage tracker communicates state but never substitutes for server authorization.

## Notifications

Existing email and database-notification patterns cover:

- direct account creation and email verification;
- identity verification submitted;
- identity action required;
- identity approved;
- identity rejected;
- Stage Two submitted;
- Stage Two approved; and
- Stage Two rejected.

Notifications contain safe status language and internal links. They do not contain provider values, document images, storage paths, inquiry identifiers, signed URLs, or biometric details.

Notification delivery occurs after commit where possible. A transport failure is logged with sanitized operational metadata and does not undo a valid decision.

## Error Handling and Recovery

- Persona unavailable: retain the attempt and offer safe retry or resume.
- Webhook delayed: allow authenticated authoritative refresh.
- Webhook duplicated: acknowledge without duplicate transition or notification.
- Older event: do not regress newer state.
- Browser return manipulated: do not change local or provider status.
- Attempt expired: offer controlled replacement.
- Attribute missing: record missing or unavailable, never match.
- PSGC relation invalid: return a field-specific validation error.
- Administrator form stale: return conflict and require reload.
- Concurrent start or submit: converge on one current record.
- Draft upload fails: preserve the previous valid draft.
- Provider media unavailable: keep the review page usable with a generic message.
- Notification fails: retain the committed workflow state.

User-facing errors never expose secrets, provider payloads, stack traces, upstream URLs, or internal storage paths.

## Security and Privacy

The implementation enforces:

- authentication on every applicant route;
- email verification before provider processing;
- explicit role and object authorization;
- applicant ownership;
- administrator review permission;
- private storage;
- protected Persona media proxying;
- server-selected template and identity context;
- CSRF protection;
- file extension, MIME, and size validation;
- transaction and row-lock protection;
- append-only decision audits;
- no raw webhook-body persistence;
- no raw Persona-response persistence;
- no document number, confidence score, biometric template, or signed URL logging;
- no client-controlled workflow state; and
- no learner or instructor access for applicant-only accounts.

The design does not claim compliance with a privacy or security standard.

## Retention and Withdrawal

Temporary files reuse the project's existing temporary-file expiry conventions.

Submitted identity, application, and audit records remain available until a formally approved retention policy authorizes deletion. The feature documents this gap instead of inventing a period.

Draft or pending Stage Two applications may use the existing withdrawal concept:

- withdrawal prevents review and activation;
- the application is archived or soft-deleted consistently with current behavior;
- private documents remain inaccessible publicly;
- the decision and history remain auditable; and
- permanent deletion is not automatic.

## Configuration and Cutover

New server-side configuration includes:

- DIRECT_INSTRUCTOR_APPLICATIONS_ENABLED, default false; and
- INSTRUCTOR_IDENTITY_ENFORCED_FROM, an explicit cutover timestamp.

The feature reuses existing Persona configuration, including:

- Persona enabled state;
- Sandbox environment;
- API key;
- GovID plus Selfie template;
- webhook secrets;
- enforcement settings;
- local manual-review flag; and
- staging demonstration manual-review flag.

Template IDs, API keys, webhook secrets, session tokens, and evidence URLs remain server-only.

### Grandfathering

- Existing approved and rejected applications remain unchanged.
- Existing pending applications remain reviewable without a new Stage One record.
- Existing instructors remain authorized through legacy behavior.
- New applications after the configured cutover require approved Stage One.
- A new post-cutover reapplication requires current Stage One approval.
- No existing account, role, profile, or decision is rewritten by the migration.

## Migrations and Database Safety

Schema changes are additive and forward-only in operational effect:

- add applicant role compatibility;
- add nullable PSGC fields to generic profiles;
- create instructor identity cases;
- create instructor identity audits;
- associate Persona attempts with instructor identity cases;
- associate new instructor applications with identity cases;
- add draft and submission metadata;
- allow the legacy government-ID path to be nullable; and
- add document disk metadata and required indexes.

Migrations run normally against existing data. They do not reset, wipe, truncate, recreate, or destructively reseed the database.

Down methods must not erase identity, audit, review, or uploaded-evidence history. History-bearing migrations either refuse destructive rollback or use an explicitly retention-approved follow-up migration.

## Automated Testing Strategy

Automated tests use the existing test framework, isolated test database, transactions where appropriate, synthetic files, and mocked Persona responses. Ordinary test execution never depends on live Persona.

### Unit tests

- age eligibility;
- PSGC relationship validation;
- declared-information normalization;
- per-field comparison outcomes;
- Stage One transition rules;
- existing learner identity reuse;
- template selection;
- Sandbox access eligibility;
- duplicate-current-case protection;
- storage-disk resolution; and
- final activation preconditions.

### Feature tests

- direct account creation assigns applicant and no learner role;
- no learner profile or learner identity case is created;
- under-18 direct and existing applicants are rejected;
- email verification gates Stage One;
- duplicate email resolves to sign-in guidance;
- applicant login redirects to the application hub;
- existing learner identity approval satisfies Stage One;
- an unapproved learner is sent to the current learner identity flow;
- direct Persona creation uses the GovID plus Selfie template;
- inquiry ownership and application association are correct;
- browser returns cannot forge success;
- valid signed webhooks are deduplicated and queued;
- invalid signatures are rejected;
- older events cannot regress state;
- missing and mismatched attributes are explicit;
- provider completion does not approve Stage One;
- only authorized administrators decide Stage One;
- stale review forms fail safely;
- Stage Two is inaccessible before local identity approval;
- draft and resume work;
- duplicate active applications are blocked;
- files are private and ownership-protected;
- rejection grants no role;
- final approval activates instructor access atomically;
- abandoned applicants retain no unintended permissions;
- Sandbox access matches current learner behavior; and
- learner, guardian, dependent, relationship, and legacy instructor regressions remain passing.

### JavaScript tests

Focused JavaScript tests cover:

- repeated-submit prevention;
- dependent city and barangay selectors;
- stage tracker behavior;
- draft upload interactions;
- error summary focus;
- reduced-motion behavior; and
- any client-side behavior added to the recruitment or application pages.

## Automated Playwright Coverage

Playwright is a required verification layer.

Automated browser specifications cover:

- desktop and mobile Be Our Partner links;
- recruitment page content and start or resume actions;
- direct applicant registration;
- under-18 rejection and age-18 acceptance;
- email-verification gating;
- applicant login and application-hub redirect;
- absence of learner and instructor access for applicants;
- existing learner dashboard entry;
- approved learner identity satisfying Stage One;
- personal-information prefill;
- PSGC city and barangay interaction;
- server rejection of a manipulated PSGC pair;
- processing, action-required, mismatch, and pending-review states;
- provider completion not unlocking Stage Two;
- refresh, resume, and replacement actions;
- administrator Identity Review queue and detail page;
- declared-versus-provider comparison presentation;
- Sandbox warnings and confirmations;
- Stage One approval unlocking Stage Two;
- rejection and action-required remaining locked;
- Stage Two Save Draft and resume;
- visible file controls and real file-chooser interaction;
- repeated-submit prevention;
- Stage Two submission;
- unauthorized cross-user records and document URLs;
- final administrator approval;
- applicant-to-instructor transition;
- approved instructor dashboard redirect;
- rejected and abandoned applicants retaining no instructor access;
- keyboard navigation;
- visible focus;
- reduced-motion mode; and
- responsive layouts.

The required browser coverage is desktop Chromium and mobile Chromium emulation. Public recruitment and critical form smoke tests also run in Firefox or WebKit where the existing environment supports them.

Authenticated storage states or deterministic fixtures represent:

- direct applicant;
- existing learner;
- administrator;
- approved instructor; and
- unrelated user for ownership testing.

Playwright tests use synthetic records and evidence. They do not call live Persona. Backend-only provider states are created through test fixtures or mocked server-side integrations, not insecure public test endpoints.

## Supervised Playwright QA Agent

After each implementation checkpoint becomes testable, a dedicated supervised QA agent is assigned.

The agent may:

- add or update Playwright specifications and approved test fixtures;
- run focused PHPUnit and JavaScript tests;
- run Playwright request and browser checks;
- inspect browser console, network traffic, snapshots, and traces;
- use Playwright CLI for exploratory checks; and
- write the approved QA report.

The agent may not:

- change production code merely to make a test pass;
- reset or destructively modify the development database;
- use real identity information;
- expose provider secrets, identifiers, evidence, or URLs;
- weaken authorization, validation, or Sandbox safeguards; or
- write outside owned test, fixture, artifact, and QA-document paths.

### Agent verification responsibilities

Backend-facing browser verification includes:

- status codes and redirects;
- authentication, email, role, and ownership gates;
- server-side age and address validation;
- Stage One and Stage Two transitions;
- manipulated URL and parameter attempts;
- duplicate submission behavior;
- administrator authorization;
- private-document headers; and
- persisted final role behavior.

Frontend verification includes:

- user-visible navigation and forms;
- actual upload-control interaction;
- stage and status presentation;
- action-required and error recovery;
- administrator moderation controls;
- desktop, mobile, keyboard, focus, and reduced-motion behavior; and
- safe loading and repeated-submission states.

The QA agent reports each failure with:

- exact reproduction steps;
- route and role;
- expected result;
- actual result;
- relevant console or network evidence;
- trace, snapshot, or synthetic screenshot location;
- suspected layer without asserting an unproven cause; and
- related scenarios that should be rerun.

The implementation owner fixes production code. The QA agent then reruns the failure and its related regression set.

## Persona Sandbox Browser Verification

A separate Playwright-assisted Sandbox walkthrough verifies:

- application-created inquiry;
- same-tab return;
- resume;
- webhook reconciliation;
- authenticated refresh;
- simulated Philippine country and exact document class;
- protected evidence previews;
- administrator review;
- re-verification;
- duplicate delivery;
- out-of-order delivery; and
- Stage Two unlocking only after explicit local approval.

This is reported separately from automated Playwright. It uses synthetic provider data and cannot demonstrate document authenticity, genuine extraction, liveness accuracy, or real face matching.

Traces, screenshots, and videos must not contain identity evidence, provider URLs, inquiry identifiers, or secrets.

## Verification Reporting

The final report separates:

1. focused PHP unit and feature tests;
2. JavaScript tests;
3. automated Playwright results;
4. exploratory Playwright CLI checks;
5. real Persona Sandbox checks;
6. unrun browser or provider scenarios;
7. staging prerequisites; and
8. production release gates.

No category is described as passing unless it was executed successfully.

## External Configuration

The following cannot be completed solely in application code:

- configure the current public Persona webhook URL;
- enable the relevant inquiry lifecycle events in Persona Dashboard;
- confirm the GovID plus Selfie template remains Philippine-restricted;
- verify each configured ID choice produces the expected authoritative class;
- configure supported Sandbox simulated extraction;
- keep the queue worker running;
- run the scheduler when temporary-file pruning requires it;
- enable the direct-application feature flag after verification; and
- complete separate production privacy, consent, biometric, minors, retention, security, operations, monitoring, and incident-response reviews.

The Dashboard event list and actual configured template remain the source of truth. The application does not invent unsupported provider event names, fields, or status values.

## Implementation Sequence

1. Add failing domain and schema tests.
2. Add forward-only migrations and model relationships.
3. Add applicant role compatibility and account routing.
4. Add reusable personal-information and Cavite PSGC validation services.
5. Add recruitment entry points and the applicant hub.
6. Add instructor identity cases and existing learner identity reuse.
7. Extend Persona orchestration for the direct applicant context.
8. Add comparison and Stage One transition services.
9. Add administrator identity-review queues and detail pages.
10. Add Stage Two gate, drafts, private storage, and authorized document delivery.
11. Add final activation, notifications, and Sandbox access safeguards.
12. Add legacy private-document migration tooling.
13. Assign the Playwright QA agent at testable checkpoints.
14. Run focused PHP, JavaScript, and Playwright suites.
15. Perform exploratory browser and Persona Sandbox verification.
16. Review the complete workflow and publish a separated verification report.

Implementation remains in the current checkout. Unrelated edits are preserved. Only owned paths are staged or committed.

## Acceptance Criteria

The implementation is acceptable when it demonstrates all of the following:

- A new adult can start from Be Our Partner without receiving learner status.
- A direct applicant can securely leave and resume.
- An applicant account cannot access learner or instructor functionality.
- An existing adult learner can start from the dashboard.
- A current approved learner identity decision satisfies Stage One.
- A direct applicant uses the configured GovID plus Selfie template.
- Persona completion alone never approves Stage One.
- Declared information is compared only with corresponding provider attributes.
- Missing and mismatched values are explicit and are not treated as fraud or success.
- Only an authorized administrator can approve Stage One.
- Stage Two is inaccessible before local Stage One approval.
- Stage Two reuses the current application requirements without a duplicate government-ID upload.
- New instructor documents are private and ownership-protected.
- Duplicate current identity cases, provider attempts, drafts, and pending applications are prevented.
- Final approval is the only application action that grants instructor access.
- Direct applicants never receive a learner profile.
- Rejected and abandoned users receive no unintended role or permission.
- Sandbox approvals retain current learner-style restrictions and labels.
- Existing pending and reviewed applications remain operable.
- Existing learner, guardian, dependent, relationship, and instructor workflows continue working.
- PHP, JavaScript, automated Playwright, QA-agent, and Sandbox evidence are reported separately and accurately.

## Known Release Gates

- The project remains on Persona Sandbox.
- Provider Dashboard configuration must be verified externally.
- The queue worker is required for webhook-triggered reconciliation.
- Legacy public instructor documents remain a privacy gate until the migration command is run and verified.
- A formal submitted-evidence retention and deletion policy is still required.
- Production use requires separate legal, privacy, biometric, security, operational, monitoring, and incident-response approval.
- Browser and Sandbox evidence must be collected after implementation; this document is a design, not verification that the feature exists.
