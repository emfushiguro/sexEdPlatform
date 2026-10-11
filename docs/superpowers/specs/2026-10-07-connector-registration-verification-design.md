# Connector Registration and Type-Specific Organization Verification Design

- **Date:** 2026-10-07
- **Status:** Approved design
- **Scope:** Connector registration, organization evidence, representative authority, admin review, resubmission, and re-verification

## 1. Purpose and Success Criteria

This feature strengthens the existing Connector application without replacing the Connector domain. Applicants may add an optional organization logo, but approval depends on private documentary evidence selected for the organization type.

The system verifies four distinct facts:

1. The organization exists as claimed.
2. Any applicable regulator or public authority recognizes the organization or activity.
3. The applicant's identity is sufficiently established.
4. The applicant is authorized to represent the organization.

Success means:

- existing Connector categories and downstream behavior remain compatible;
- a separate organization type selects proportionate evidence;
- requirements are centralized and enforced by Laravel;
- verification documents remain private;
- administrators review evidence by purpose and document;
- rejected evidence can be replaced without restarting the application;
- existing verified Connectors are preserved;
- workspace, seminar, community, membership, and planned marketplace access continue using the existing operational status boundary; and
- the product describes its own review accurately without claiming government certification.

V1 remains limited to Cavite and the current Laravel, Blade, Alpine, Tailwind, Vite, notification, and storage stack.

## 2. Selected Architecture and Alternatives

The selected approach adds a dedicated Connector verification extension:

    Category + Organization Type
                |
                v
    Versioned PHP requirement registry
                |
                v
    Requirement snapshot for this Connector
                |
                v
    Private evidence submission
                |
                v
    Document-level admin review
                |
                v
    Overall verification checklist
                |
                v
    Verified Connector
                |
                v
    Existing workspace and feature access

The existing connectors row remains the application and operational access boundary. New requirement, document, and audit records supply the missing evidence lifecycle.

Two alternatives were rejected:

1. A polymorphic verification platform would require a broad refactor of functioning learner and guardian verification flows.
2. Connector columns or JSON alone would not provide durable document-level decisions, immutable replacement history, expiration, or safe custom evidence requests.

The selected design reuses proven patterns without coupling unrelated verification tables.

## 3. Existing Systems Reused

The implementation extends:

- Connector, including its current profile, representative, status, review, membership, role, invitation, seminar, and community relationships.
- ConnectorRegistrationService and the current registration routes.
- ConnectorAccessService, which remains the workspace permission boundary.
- ConnectorReview for overall lifecycle decisions.
- Existing application, withdrawal, and moderation notifications.
- Existing admin Connector queue and detail pages.
- Private evidence patterns from learner and guardian verification:
  - generated private paths;
  - detected MIME validation;
  - content hashes;
  - submission rounds;
  - authorization-controlled streaming;
  - private and no-store response headers;
  - row locking;
  - audit history; and
  - after-commit notifications.
- The admin Payment Management visual baseline and learner identity review patterns in design.md.

The learner and guardian verification tables are not generalized or reused as Connector storage.

Minimal new domain units are:

- ConnectorVerificationRequirements, which resolves the PHP registry and snapshots applicable requirements.
- ConnectorVerificationService, which owns submission, document review, resubmission, overall decisions, and expiration transitions.
- Thin applicant and administrator controllers.
- A dedicated controller for secure evidence streaming.

No interfaces, factories, new framework, or database-driven requirement builder are introduced.

## 4. Taxonomy

The current category remains the service or community domain:

- government;
- ngo;
- community_based_organization;
- school_educational_institution;
- health_organization;
- advocacy_group; and
- other.

The new organization type describes the entity or regulated institution:

- barangay_lgu;
- government_agency;
- public_educational_institution;
- private_basic_education_institution;
- higher_education_institution;
- technical_vocational_institution;
- ngo_nonprofit_foundation;
- corporation_private_company;
- partnership;
- cooperative;
- social_welfare_agency;
- registered_community_association;
- informal_community_organization; and
- other.

The backend registry defines compatible category/type combinations. Legal identity primarily follows organization type. Category may add a conditional regulatory requirement when the declared activity genuinely needs it.

A health category does not by itself trigger a generic health license. An applicable authorization may be requested only when the declared activity requires it.

## 5. Regulatory Evidence Matrix

This is platform eligibility verification, not legal certification. Every configured regulatory requirement records its issuing authority, official source URL, and last-reviewed date.

| Organization type | Identity evidence | Regulatory evidence | Representative authority |
| --- | --- | --- | --- |
| Barangay / LGU | Official LGU document, authoritative certification, or creation/identity document identifying the barangay and jurisdiction | Normally not applicable | Election, appointment, designation, certification, or official authorization |
| Government agency | Enabling law, charter, executive issuance, or official agency certification | Activity-specific authority when applicable | Official appointment or designation |
| Public educational institution | Official establishment, endorsement, or institutional recognition | Applicable DepEd, CHED, or TESDA authority | School-head or administrator designation |
| Private basic education institution | SEC or DTI institutional identity where applicable | DepEd permit or government recognition | School-head designation or authorization |
| Higher education institution | SEC registration, charter, or government identity | CHED permit, recognition, or applicable program authority | Governing-board or institutional designation |
| Technical-vocational institution | Official institutional registration | TESDA Certificate of Program Registration where applicable | Authorized administrator designation |
| NGO / non-profit / foundation | SEC non-stock Certificate of Incorporation or Registration | Only applicable sector-specific authority | Board resolution, secretary's certificate, or authorization |
| Corporation / private company | SEC Certificate of Incorporation or Registration | Only applicable sector-specific authority | Officer evidence plus board or secretary authorization |
| Partnership | Appropriate SEC registration or recording | Only applicable sector-specific authority | Partner resolution or formal authorization |
| Cooperative | CDA Certificate of Registration | Branch authority or other CDA evidence only when applicable | Board resolution or official designation |
| Social welfare agency | SEC non-stock registration | DSWD CRLTO; accreditation only when the organization claims an accredited program | Board or executive authorization |
| Registered community association | SEC, CDA, or other applicable registration | Only when regulated | Officer designation or organizational authorization |
| Informal community organization | Barangay or community certification, charter, officer list, and evidence of current activity | Normally not applicable | Officer designation, endorsement, and representative identity |
| Other | Minimum identity and authority evidence | Admin-requested evidence based on declared activity | Formal authorization |

Regulatory basis:

- The Local Government Code recognizes barangays as political subdivisions and bodies politic: https://officialgazette.gov.ph/1991/10/10/republic-act-no-7160/
- SEC distinguishes stock corporations, non-stock corporations, and partnerships: https://appointment.sec.gov.ph/forms-and-fees/primary-registration/
- CDA states that a cooperative acquires juridical personality through a Certificate of Registration: https://cda.gov.ph/services/regulatory-services/registration/
- DepEd uses permits and government recognition for applicable private basic education institutions: https://www.deped.gov.ph/wp-content/uploads/DO_s2026_012r.pdf
- CHED issues applicable permits, recognition, and Certificates of Program Compliance: https://ched.gov.ph/wp-content/uploads/2017/10/CHED_MOP.pdf
- TESDA issues a Certificate of Program Registration under UTPRAS: https://tesda.gov.ph/AboutL/TESDA/26
- DSWD distinguishes registration/licensing through a CRLTO from program accreditation: https://helps.dswd.gov.ph/Home/RLAFAQ/

A barangay clearance alone does not establish that the applicant represents the barangay government. Likewise, an organization certificate does not establish the applicant's authority to act for that organization.

## 6. Requirement Registry

One version-controlled PHP configuration defines:

- stable requirement key;
- label and applicant guidance;
- evidence category;
- required or optional status;
- all, any-of, and minimum-count rules;
- maximum upload count;
- accepted MIME types and file-size limit;
- required metadata fields;
- whether expiration applies;
- official issuing authority;
- official source URL and last-reviewed date;
- compatible category/type combinations; and
- expiration effect.

Evidence categories are organization_identity, regulatory_eligibility, representative_identity, representative_authority, and supporting_evidence.

The resolved definition is snapshotted into database rows at submission. Later configuration changes do not silently change an application already under review.

An administrator may request a clearly explained supplemental requirement for an other application. That request is application-specific, audited, and does not modify global configuration.

## 7. Data Model

### connectors extensions

Add backward-compatible fields:

- organization_type, nullable and indexed;
- logo_path, nullable;
- verification_status, nullable and indexed;
- current_submission_round, default zero; and
- verification_submitted_at, nullable.

The existing status remains the operational state used by current access checks. verification_status represents the evidence lifecycle.

### connector_verification_requirements

Store the application-specific requirement snapshot:

- connector;
- stable requirement key;
- evidence category;
- label and guidance;
- required flag;
- minimum and maximum evidence counts;
- definition/source snapshot;
- current requirement status;
- optional reference to an existing approved identity case;
- administrator who requested a custom requirement; and
- supersession metadata.

### connector_verification_documents

Store:

- connector, requirement, uploader, and submission round;
- generated private disk and path;
- original filename, detected MIME type, byte size, and content hash;
- optional issuer, document number, issue date, and expiry date;
- pending, accepted, rejected, expired, or superseded status;
- reviewer and review time;
- applicant-facing rejection reason;
- internal review note; and
- supersession time.

### connector_verification_audits

Record:

- connector, requirement, and optional document;
- actor and action;
- previous and new states;
- submission round;
- applicant-facing reason;
- internal notes; and
- upload, replacement, review, access, and expiration events.

### connector_reviews extensions

Continue storing overall Connector decisions. Add nullable reason_code, internal_notes, and submission_round fields if required by the review UI.

All schema changes use incremental migrations. Normal application flows do not delete evidence or audit history.

## 8. Registration Experience

The existing Connector registration page becomes a five-step Blade and Alpine workflow:

1. Organization type
   - category;
   - organization type; and
   - explanation of why both are required.
2. Organization information
   - existing name, contact, website, description, Cavite city, barangay, and address fields.
3. Organization logo
   - optional PNG, JPEG, or WebP;
   - preview, replace, and remove;
   - maximum 2 MB; and
   - explicit notice that the logo is not verification evidence.
4. Verification evidence
   - requirements grouped by identity, regulatory eligibility, and authority;
   - required and optional labels;
   - issuing-authority and purpose guidance;
   - PDF, JPEG, PNG, or WebP;
   - maximum 10 MB per file; and
   - metadata fields only where configured.
5. Representative and confirmation
   - current account identity;
   - representative identity requirement;
   - representative authority evidence;
   - accuracy confirmation; and
   - final submission summary.

The page follows design.md:

- calm white surface and restrained brand accents;
- visible Step X of 5 progress;
- one primary action per step;
- clear Back and Continue controls;
- visible labels and inline errors;
- status words rather than color-only meaning;
- minimum 40 to 44 pixel action targets;
- responsive single-column mobile layout; and
- no long modal form.

There is no persisted draft in V1. Changing organization type preserves compatible values and warns before clearing incompatible evidence.

The optional logo uses the existing public profile-media convention. It is rendered publicly only for verified Connectors and never participates in evidence calculations.

## 9. Server-Side Submission

The frontend receives requirement data for presentation, but Laravel independently:

- validates category/type compatibility;
- resolves the authoritative requirements again;
- rejects missing evidence;
- rejects unrecognized requirement keys;
- checks MIME type, size, and configured count;
- validates only configured metadata;
- prevents duplicate active applications for the same organization identity;
- stores evidence privately;
- cleans up new files if the transaction fails;
- creates the Connector, pending owner membership, requirement snapshots, documents, audit rows, and initial Connector review atomically; and
- dispatches notifications after commit.

A compatible existing approved identity case may satisfy representative identity through a reference. Its private files are not copied. Representative authority remains a separate requirement.

## 10. Applicant Status and Resubmission

The existing status page displays:

- organization identity and optional logo;
- operational status;
- verification status;
- evidence progress grouped by purpose;
- each requirement's state and next action;
- safe applicant-facing rejection guidance;
- review history; and
- withdrawal while eligible.

Only rejected or newly requested requirements need replacement. Previously accepted evidence stays accepted. Replacement increments current_submission_round and supersedes only the replaced document.

Every review mutation includes the submission round. A stale administrator page receives a conflict response and cannot decide older evidence.

Applicants never receive internal review notes.

## 11. Admin Verification Workspace

The existing admin Connector detail page adopts the Payment Management baseline and learner identity review interaction patterns.

Page order:

1. Back link, Connector identity, submission date, and status.
2. Summary cards for organization identity, regulatory eligibility, representative authority, and completion.
3. Organization details and separate optional logo.
4. Representative account and identity state.
5. Evidence grouped by purpose.
6. Secure image or PDF preview and download.
7. Review checklist.
8. Decision controls.
9. Audit history.

Each requirement displays:

- required, optional, or not applicable;
- submitted, pending, accepted, rejected, expired, or superseded;
- issuer and relevant dates;
- applicant guidance;
- Accept and Reject actions; and
- separate applicant-facing and internal notes.

The approval checklist confirms:

- organization identity established;
- applicable regulatory evidence verified or legitimately not applicable;
- representative identity verified;
- representative authority established;
- required evidence complete; and
- evidence appears consistent with the application.

The backend independently enforces these conditions. Overall actions are Approve, Request resubmission, Permanently reject, and the existing post-approval suspension flow.

No bulk evidence decisions are added.

## 12. Status and Access Lifecycle

| Operational status | Verification status | Effect |
| --- | --- | --- |
| pending | under_review | Applicant status access only |
| pending | resubmission_required | Applicant replaces rejected evidence |
| verified | verified | Full workspace access and public verified state |
| verified | legacy_verified | Existing verified Connector remains operational |
| verified | reverification_required | Public verification is flagged; access remains temporarily available |
| rejected | rejected | No workspace access |
| suspended | Existing evidence state retained | Existing suspension behavior |
| withdrawn | Submission closed | No workspace access |

A daily expiration check marks applicable documents and requirements expired. It does not automatically suspend a Connector. It sets reverification_required, notifies the representative and administrators, and waits for replacement or an explicit moderation decision.

Foundational documents do not require expiry dates. Requirement configuration decides whether expiration applies.

Organization name, organization type, or primary representative changes trigger re-verification. Logo, description, website, and ordinary contact edits do not.

## 13. Security, Privacy, and Retention

Verification evidence:

- uses the private local disk;
- receives generated, non-guessable paths;
- has no public URL;
- is accessible only to the primary applicant and authorized platform administrators;
- is not available to ordinary Connector members;
- is streamed only through authorized controllers;
- uses detected MIME types, safe filenames, nosniff, and private/no-store caching;
- records administrator previews and downloads; and
- never appears in notification bodies, public profiles, or search indexes.

SVG, Office documents, archives, and arbitrary executable formats are not accepted.

Only metadata enabled for a requirement is collected. This follows the Data Privacy Act principles of legitimate purpose, proportionality, security, and limited retention:

- https://privacy.gov.ph/data-privacy-act-/
- https://privacy.gov.ph/implementing-rules-regulations-data-privacy-act-2012/

An approved organizational retention schedule is a production release gate. V1 does not invent a legal retention period. Secure disposal must remove raw evidence while preserving the minimum non-sensitive audit history required for accountability.

## 14. Notifications and Error Handling

In-app notifications cover:

- new application;
- resubmission;
- new administrator-requested requirement;
- document rejection;
- approval;
- permanent rejection;
- re-verification requirement; and
- configured expiration warnings.

Applicant-critical decisions also use email. Notifications are sent after commit. Notification failure is logged and does not roll back a successful decision.

Failure behavior:

- validation failures appear beside the relevant field;
- unauthorized evidence requests return 403;
- missing private files return 404 without exposing storage paths;
- stale submission rounds return 409;
- incomplete approval returns 422 with a clear reason;
- failed storage or database operations remove only newly written files; and
- duplicate evidence hashes are rejected without changing accepted evidence.

## 15. Compatibility and Migration

- Existing verified Connectors receive legacy_verified without losing operational access.
- Existing pending Connectors remain intact but must complete current requirements before a new approval.
- Existing rejected, suspended, and withdrawn records are preserved.
- Existing categories, slugs, memberships, roles, permissions, seminars, community content, subscriptions, and reviews remain compatible.
- ConnectorAccessService continues using operational status equal to verified.
- The planned Connector marketplace continues relying on the same verified operational boundary.
- No existing media or evidence is overwritten.
- No destructive database command or destructive seeder is used.

## 16. Testing and Release Gates

Automated coverage includes:

- every organization type and category/type compatibility;
- required, optional, conditional, and any-of requirements;
- logo validation and separation from evidence;
- backend rejection of missing or incorrect evidence;
- private storage and unauthorized-access denial;
- identity reuse without file copying;
- document acceptance and rejection;
- applicant and internal note separation;
- partial resubmission and immutable history;
- stale-review protection and row locking;
- approval gating;
- legacy Connector preservation;
- expiration and re-verification;
- Connector workspace, membership, seminar, and profile regressions; and
- notification timing and safe payloads.

Release evidence requires:

- focused PHPUnit tests against the isolated cc_db_test database;
- the full PHPUnit suite;
- Pint;
- Vite production build;
- forward migration against an existing-data copy;
- manual responsive registration and admin-review walkthrough;
- keyboard, focus, dialog, labeling, and status accessibility checks;
- private-document authorization and response-header verification; and
- staging migration and browser smoke testing.

Static checks alone do not qualify the feature as complete.

## 17. Delivery Sequence

1. Requirement registry, incremental schema, models, and focused tests.
2. Registration validation and private evidence submission.
3. Registration stepper and logo handling.
4. Applicant status and partial resubmission.
5. Admin document review and approval gating.
6. Expiration and re-verification.
7. Security, accessibility, regression, and staging verification.
8. Regulatory-source and retention documentation.

## 18. Excluded from V1

- OCR or document extraction.
- AI authenticity decisions.
- Facial recognition.
- Government API integrations.
- Automated SEC, CDA, DSWD, DepEd, CHED, or TESDA verification.
- Blockchain certificates.
- A generic verification framework.
- An administrator requirement-builder UI.
- Bulk review decisions.
- Persisted registration drafts.
- Automatic suspension solely because a document expires.
- Claims that Conscious Connections provides legal or government certification.

The public wording is:

> Verified by Conscious Connections based on submitted organizational and authorization documents.

## 19. Acceptance Criteria

The design is implemented when:

- an applicant can optionally upload a validated organization logo;
- the logo is visibly and technically separate from verification evidence;
- category and organization type are preserved as distinct concepts;
- the registry supplies proportionate type-specific requirements;
- the backend enforces the same requirements independently of Alpine;
- private evidence is authorized, auditable, and inaccessible through public URLs;
- individual requirements and documents can be accepted or rejected;
- the applicant can replace only rejected evidence;
- accepted evidence and audit history remain immutable;
- approval is impossible while required evidence is unresolved;
- representative identity and authority remain separate;
- expiry applies only where configured;
- existing verified Connectors remain operational;
- existing Connector permissions and features continue to work; and
- all automated, migration, privacy, browser, and staging release gates are explicitly reported.
