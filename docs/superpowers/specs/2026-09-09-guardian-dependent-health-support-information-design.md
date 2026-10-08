# Guardian-Dependent Health & Support Information Design

Date: 2026-09-09

## Goal

Add an optional, privacy-restricted Relevant Health & Support Information feature for dependent learners. The feature gives a dependent and specifically authorized guardians a minimal place to record information relevant to learning participation, accessibility, support, or safety without creating a medical-record system or influencing Guardian-Dependent relationship verification.

Phase 3 extends the current dependent-registration, `ParentChildAccount`, learner visibility, notification, authorization, and audit architecture. It does not create a second dependent, relationship, verification, invitation, or messaging system.

## Phase Boundary

Phase 3 includes:

- an optional Health & Support Information step after dependent account and relationship submission;
- relevant health considerations, accessibility or learning-support needs, and additional relevant participation or safety information;
- application-level encrypted storage in a dedicated dependent-owned record;
- dependent self-service viewing, editing, and removal;
- independently controlled access for each eligible Guardian-Dependent relationship;
- a clear purpose notice and acknowledgement before collection;
- metadata-only audit history;
- privacy-safe notifications;
- authorization, privacy, integration, and regression tests.

Phase 3 does not include:

- medical, disability, or mental-health diagnosis;
- screening, scoring, treatment recommendations, or medical assessment;
- medical certificates, prescriptions, laboratory results, or document uploads;
- emergency-response functionality;
- diagnosis codes or structured medical histories;
- automatic instructor access to detailed information;
- relationship-review administrator access to detailed information;
- health-based identity, invitation, or relationship decisions;
- automatic sharing with every guardian;
- a separate health messaging, notification, or verification system.

Phase 2 invitation and messaging work may still be under development while this specification is written. Phase 3 implementation should begin only after Phase 2 database and authorization changes are stable enough to serve as its integration baseline.

## Existing Architecture

The current dependent-registration wizard is implemented by `ParentRegistrationController`. It collects dependent personal information, location, credentials, dependent identity evidence, and relationship evidence. The final relationship-evidence submission creates the dependent, learner profile, and pending `ParentChildAccount` relationship inside a database transaction.

`ParentChildAccount` is the authoritative relationship aggregate. It stores one row per Guardian-Dependent connection, independent verification and lifecycle state, and relationship-specific learning permissions. `scopeAccessEligible()` already requires an active, verified relationship, an active guardian with approved identity verification, an active dependent, and any required approved dependent verification.

`ParentVisibilityController` provides the dependent-facing list of active linked guardians. This is the appropriate place to add dependent-controlled support-information access because permissions must belong to the exact relationship row rather than to a general guardian role.

The application uses Laravel authorization and scoped Eloquent queries rather than database-native row-level security. It also has a global administrator Gate bypass. Phase 3 must explicitly prevent that bypass from granting access to sensitive support information.

## Privacy and Philippine Context

Information about a person's health is sensitive personal information under the Philippine Data Privacy Act of 2012. Collection and processing must use a declared, specific, and legitimate purpose with appropriate safeguards. The National Privacy Commission also describes data-subject rights to access, correction, and erasure or blocking and has issued guidance requiring child-oriented transparency in clear, age-appropriate language.

Relevant official references:

- [Republic Act No. 10173 - Data Privacy Act of 2012](https://privacy.gov.ph/data-privacy-act/)
- [National Privacy Commission - Data Subject Rights](https://privacy.gov.ph/data-subject-rights/)
- [National Privacy Commission - Right to Erasure or Blocking](https://privacy.gov.ph/right-to-erasure-or-blocking/)
- [National Privacy Commission - Child-Oriented Transparency FAQs](https://privacy.gov.ph/wp-content/uploads/2024/12/FAQs-Advisory-on-Guidelines-on-Child-Oriented-Transparency.pdf)

The collection notice will use plain language similar to:

> This optional information helps you and an authorized guardian record learning, accessibility, participation, or relevant safety support needs. It is not used to diagnose a condition, determine medical treatment, or approve a guardian relationship.

The final lawful basis, privacy notice, retention schedule, backup policy, and operational procedures should be reviewed by the platform's Data Protection Officer or qualified Philippine privacy counsel before production deployment. This feature design is an engineering control, not a legal determination.

## Chosen Architecture

Create one dedicated encrypted support-information record per dependent. Keep access on each independently verified `ParentChildAccount` relationship.

```text
Dependent
|-- Health & Support Information - one optional encrypted record
|-- Guardian A relationship - support access enabled
|-- Guardian B relationship - support access disabled
`-- Guardian C relationship - pending; no ongoing access
```

This architecture is preferred over adding fields to `learner_profiles`, because learner profiles are broadly reused and ordinary profile queries should never retrieve sensitive text accidentally. It is also preferred over storing support information on `ParentChildAccount`, because relationship-owned storage would duplicate potentially conflicting information across multiple guardians and incorrectly couple support data to relationship verification.

The support record belongs to the dependent account:

- removing one guardian does not delete the record;
- granting another guardian access does not copy the record;
- a pending, rejected, revoked, or inactive guardian has no ongoing access;
- rejecting or revoking every relationship does not delete the dependent's record;
- relationship-review services never read the record;
- deleting the record does not alter any relationship state.

## Data Model

### `dependent_support_profiles`

Create a table with at most one row per dependent:

- `id`;
- `dependent_user_id`, a unique foreign key to `users`;
- `relevant_health_considerations`, nullable encrypted text;
- `accessibility_support_needs`, nullable encrypted text;
- `additional_relevant_information`, nullable encrypted text;
- `privacy_notice_version`;
- `purpose_acknowledged_at`;
- `purpose_acknowledged_by_user_id`;
- `created_by_user_id`;
- `updated_by_user_id`;
- timestamps.

The three content fields use Laravel encrypted casts backed by the application key. They are text columns because encrypted values are longer than their plaintext input. The application does not search, filter, sort, aggregate, or index their encrypted contents.

The UI field `has_relevant_support_information` is a form control rather than a persisted health assertion:

- no row means no information has been voluntarily provided;
- choosing Yes reveals the content fields;
- at least one content field is required before a row can be created;
- choosing to remove all information hard-deletes the active row.

This avoids storing empty or negative health records.

No health documents, document paths, diagnoses, classification codes, searchable tags, inferred conditions, assessment results, or derived medical fields are added.

### `dependent_support_information_audits`

Create a metadata-only audit table containing:

- `id`;
- `dependent_user_id`;
- nullable `actor_user_id`;
- nullable `parent_child_account_id` when a guardian performed the action;
- `action`, restricted to supported action values;
- nullable JSON `changed_fields` containing field names only;
- `occurred_at`.

Supported actions include:

- `created`;
- `updated`;
- `removed`;
- `permission_granted`;
- `permission_revoked`.

Audit rows never contain previous or new support text, diagnoses, form payloads, uploaded files, notification bodies, IP addresses, or user-agent values. Removing the support record preserves only this non-content accountability history. Deleting the dependent account removes its support record and associated audit metadata according to the application's account-deletion policy.

## Relationship Permission

Add one permission to `ParentChildAccount`:

```text
can_manage_support_information
```

Manage access includes viewing, creating, editing, and removing the optional record. A separate view-only permission is not needed for the accepted Phase 3 requirements.

Permission behavior:

- existing relationships receive `false` through the migration default;
- a relationship created as part of new dependent registration receives `true`, but the permission remains dormant until the relationship is active and verified;
- a relationship produced by an existing-learner invitation receives `false`;
- the dependent may grant or revoke the permission independently for each active verified guardian;
- pending, under-review, rejected, revoked, inactive, or otherwise access-ineligible relationships cannot exercise the permission;
- relationship rejection, revocation, or deactivation resets the permission to `false`;
- restoring or reverifying a relationship does not silently restore sensitive access;
- changing one relationship does not alter another relationship's permission;
- administrative relationship approval never enables this permission for an invited guardian.

`ParentChildAccount::scopeAccessEligible()` remains the common lifecycle prerequisite. `scopeWithPermission()` will recognize the new permission while preserving the behavior of progress, quiz-answer, and content-approval permissions.

Only the dependent controls this permission in Phase 3. The centralized relationship-review interface can continue managing its existing learning permissions, but it does not expose or modify support-information contents and does not automatically grant sensitive access.

## Authorization Model

| Actor | Initial registration submission | Ongoing view, edit, or removal |
|---|---:|---:|
| Dependent | Not applicable | Allowed for their own information |
| Creating guardian | Allowed through the exact registration relationship | Allowed only after the relationship is active, verified, access-eligible, and permission-enabled |
| Other verified guardian with permission | No | Allowed |
| Verified guardian without permission | No | Denied |
| Pending, rejected, revoked, or inactive guardian | One initial submission only when they are the registration creator | Denied |
| Instructor | Denied | Denied |
| Relationship-verification administrator | Denied | Denied |
| Invitation participant before relationship approval | Denied | Denied |
| Unrelated user | Denied | Denied |

Every guardian authorization resolves the exact `ParentChildAccount` row. A general check that the actor is a guardian, or that the dependent has a guardian, is insufficient.

Create a dedicated support-information policy and authorization query that:

1. permits the dependent when the authenticated user ID equals `dependent_user_id`;
2. otherwise requires an exact `ParentChildAccount::accessEligible()` row;
3. requires `can_manage_support_information = true` on that row;
4. fails closed when the relationship or dependent cannot be resolved.

The current global administrator Gate bypass must explicitly exclude support-information abilities so the dedicated policy decides them. Tests must prove that an administrator does not gain support-information access merely by having the administrator role.

## Dependent-Creation Integration

The existing relationship-evidence submission remains the transaction that creates the dependent and pending relationship. Sensitive support text will not be stored in the multi-step registration session before the dependent exists.

The revised flow is:

1. dependent personal information;
2. location;
3. credentials;
4. dependent identity validation;
5. relationship evidence submission;
6. optional Health & Support Information;
7. completion page.

After step 5 succeeds:

- the dependent, learner profile, pending relationship, and relationship evidence are already committed;
- the controller stores only the new dependent ID, relationship ID, and an expiry timestamp in a short-lived registration marker;
- no support text is stored in the wizard session;
- the guardian is redirected to the optional support step;
- the guardian may submit information or choose Skip for now;
- skipping does not create a support record;
- abandoning the optional page leaves a valid dependent and relationship with no support record;
- a support-step failure never rolls back the dependent or submitted relationship evidence.

The one-time registration path verifies that:

- the authenticated guardian owns the relationship;
- the dependent ID and relationship ID match one another;
- the marker has not expired;
- the relationship has not been rejected, revoked, or deleted.

The initial write is a narrowly scoped registration action. It does not grant ongoing read access while the relationship remains pending. The completion response confirms whether information was saved without redisplaying sensitive content.

## Collection Form and Validation

The optional page begins with the child-friendly purpose notice before showing any data-entry controls.

Fields:

- `has_relevant_support_information`;
- `relevant_health_considerations`;
- `accessibility_support_needs`;
- `additional_relevant_information`;
- an acknowledgement checkbox required only when information is submitted.

Behavior and server-side validation:

- the section remains optional;
- No or Skip for now creates no record;
- Yes reveals the three optional plain-text fields;
- at least one trimmed content field is required when Yes is selected;
- each content field has a conservative server-side character limit;
- arrays, objects, HTML-specific payloads, and file uploads are rejected;
- Blade escapes all output;
- no content is interpreted, scored, diagnosed, or automatically categorized;
- the acknowledgement records the notice version, actor, and timestamp;
- creating or updating a record requires a fresh acknowledgement.

The interface should encourage users to provide only information relevant to platform support and avoid unnecessary medical history. It should also state that the page is not an emergency channel.

Sensitive inputs must not be included in URLs, analytics, logs, exception context, notification payloads, verification data, or ordinary session flashing. Validation failure should render the form and errors without persisting sensitive plaintext in the wizard session.

## Ongoing Management

Provide two interfaces backed by the same service layer:

- a dependent-facing My Health & Support Information settings page;
- a guardian-facing Health & Support Information action for an eligible dependent.

Supported operations:

- view the current information;
- create information when no record exists;
- edit one or more fields;
- clear selected fields;
- remove the complete record;
- view when the record was last updated;
- see who last updated it using privacy-safe language.

Removal uses a confirmation screen and an active-database hard delete. Soft deletion is inappropriate because it would keep sensitive text readily recoverable in the primary table. The UI must explain that backups follow the platform's established retention and privacy policy and must not promise instantaneous removal from every historical backup.

Sensitive responses use private, no-store cache headers. All writes use CSRF protection and fresh server-side authorization.

## Dependent-Controlled Guardian Access

Extend the existing `/my-parent` interface with a control for each active verified relationship:

> Allow this guardian to manage my Health & Support Information

The control displays only the context needed for an informed decision:

- guardian name and avatar;
- relationship label;
- verified relationship status;
- current support-information access status;
- an explanation that access permits viewing, editing, and removal.

Grant and revoke requests authorize the authenticated dependent against the exact relationship row. The relationship must belong to the dependent and remain active, verified, and access-eligible. Permission changes are audited independently and do not modify relationship status or other permissions.

## Notifications and Transparency

Reuse existing database notifications.

- Notify the dependent when an authorized guardian creates, updates, or removes support information.
- Notify a guardian when the dependent grants or revokes that guardian's permission.
- Do not notify instructors or relationship-verification reviewers.
- Do not send a notification when the dependent changes their own record.
- Notification text contains no health or support content and no changed-field names.
- Notification links perform fresh authorization when opened.
- Old notification links do not restore access after permission or relationship changes.

## Concurrency and Failure Handling

The unique `dependent_user_id` constraint is the final defense against duplicate support profiles.

Edit forms include the record version represented by `updated_at`. During update:

1. authorize the actor again;
2. lock the current row;
3. compare the submitted version with the current version;
4. reject a stale write and ask the user to review the latest record;
5. update the encrypted fields and metadata in one transaction;
6. add the metadata-only audit row;
7. dispatch any privacy-safe notification only after commit.

Concurrent create attempts rely on the unique constraint and return the existing record for a safe retry. Concurrent update and delete operations fail closed rather than recreating a record from stale data.

Storage, audit creation, and notification coordination must not leave a successful-looking audit or notification for a failed content change.

## Exposure Controls

Support information must not appear in:

- `LearnerProfile` serialization or ordinary profile relations;
- public or invitation-scoped guardian profiles;
- invitation detail pages or evidence metadata;
- chat conversations or message notifications;
- guardian progress or quiz pages;
- instructor enrollment, module, lesson, or learner views;
- centralized relationship-verification pages;
- relationship evidence previews or downloads;
- administrator dashboards or moderation queues;
- search indexes, exports, analytics, or reports not explicitly designed for this record;
- URLs, logs, email bodies, or notification payloads.

Controllers load the support relation only for a route that has already identified the target dependent and then apply the dedicated authorization policy. The relation is not added to default eager-loading lists, broad user resources, or shared view composers.

The application does not use Supabase or PostgreSQL row-level security for this area. Equivalent record-level restrictions are provided through exact relationship scopes, a dedicated Laravel policy, database constraints, controlled relation loading, and denial-path feature tests.

## Relationship-Verification Independence

```text
Support information exists or is absent
                |
                `-- has no effect on
                    |-- guardian identity verification
                    |-- dependent identity verification
                    |-- relationship evidence requirements
                    |-- invitation acceptance
                    |-- administrative approval or rejection
                    `-- relationship activation
```

Relationship-review queries, controllers, views, resources, notification payloads, and services do not load `DependentSupportProfile`. Approval and rejection services do not accept support-information fields or model instances. Tests compare relationship decisions with and without a support record to prevent accidental coupling.

## Multiple-Guardian Behavior

Expected behavior:

- Guardian A and Guardian B can have different support permissions for the same dependent.
- Granting Guardian A access does not grant Guardian B access.
- Guardian A and the dependent see the same dependent-owned record rather than separate copies.
- A pending Guardian C receives no ongoing access even if an invalid request attempts to set the permission.
- Revoking Guardian A immediately blocks Guardian A and resets only Guardian A's support permission.
- Guardian B remains authorized when Guardian A is revoked.
- Removing the support record affects no relationship state.
- Removing every guardian does not delete the dependent's support record.
- Adding a later guardian through the existing-learner invitation flow defaults to no support access.

## Testing Strategy

### Schema and encryption tests

- the support table has the unique dependent foreign key and expected actor foreign keys;
- relationship permission defaults to false for existing and invitation-created relationships;
- encrypted casts return original values through the model;
- raw database content does not contain submitted plaintext;
- audit rows never contain submitted content.

### Registration tests

- the optional step appears only after successful dependent and relationship creation;
- Skip for now creates no record and reaches completion;
- abandoning the step leaves the account and pending relationship intact;
- each field can be submitted independently;
- multiple fields can be submitted together;
- the purpose acknowledgement is required for submission;
- no support text is stored in the registration session;
- an expired, mismatched, or unrelated registration marker is rejected;
- registration submission does not grant ongoing access while the relationship is pending.

### Validation and privacy tests

- server-side character limits and value shapes are enforced;
- a Yes submission with no content is rejected;
- file uploads are rejected;
- rendered content is escaped;
- logs, exceptions, sessions, notifications, and URLs contain no support text;
- sensitive pages return private, no-store cache headers.

### Authorization tests

- a dependent can view, create, update, and remove their own record;
- an active verified guardian with permission can manage the record;
- a verified guardian without permission is denied;
- pending, rejected, revoked, inactive, suspended, or otherwise ineligible guardians are denied;
- an unrelated guardian or learner is denied;
- an instructor is denied;
- a relationship-review administrator is denied despite the global administrator Gate bypass;
- invitation participation alone grants no access;
- an old notification URL is denied after permission loss.

### Permission and multiple-guardian tests

- the dependent can grant and revoke access independently;
- one relationship's change does not affect another relationship;
- relationship deactivation or revocation resets only that relationship's support permission;
- reactivation does not silently restore the permission;
- invitation-created relationships default to false;
- the originating new-dependent relationship uses the documented dormant-true behavior.

### CRUD, audit, and concurrency tests

- create, view, edit, clear-one-field, and hard-delete paths work;
- clearing every content field follows the explicit removal path;
- successful actions produce metadata-only audits;
- failed actions do not create audits or notifications;
- dependent notifications are sent only for guardian changes;
- permission notifications contain no sensitive content;
- stale updates are rejected without overwriting newer information;
- concurrent creates cannot produce duplicate rows;
- stale update-after-delete does not recreate removed information.

### Verification-separation tests

- relationship approval is identical when no support record exists;
- relationship approval is identical when a support record exists;
- relationship rejection does not inspect or modify support content;
- health fields cannot be submitted to relationship-review endpoints;
- centralized verification pages do not query or render support information.

### Regression tests

- Phase 1 multiple-guardian, evidence, review, revocation, and learning permissions remain operational;
- Phase 2 invitation context, evidence transfer, chat authorization, moderation, and notifications remain operational;
- existing dependent registration still creates valid accounts and pending relationships;
- learner profiles, instructor views, progress, quiz answers, content approval, chat, and notifications do not gain accidental support-information exposure.

## Rollout and Compatibility

- Existing dependent accounts receive no support record.
- Existing relationships receive the new permission as false.
- No existing learner-profile data is migrated or reinterpreted as health information.
- No relationship is reverified, reapproved, or reactivated by this migration.
- New-dependent relationships use the documented dormant permission; existing-learner invitation relationships remain false.
- Phase 3 routes and navigation appear only when their authorization prerequisites are met.
- A rollback removes Phase 3 application paths before schema removal so encrypted content is never exposed through fallback serialization.

## Completion Criteria

Phase 3 is complete when:

- dependent creation offers a genuinely optional support-information step;
- no information is required to create a dependent or submit a relationship;
- sensitive text is stored in one dependent-owned encrypted record;
- dependents can view, update, and remove their information;
- every guardian's ongoing access requires their own active verified relationship and explicit permission;
- instructors, unrelated users, and relationship-verification administrators cannot access detailed information;
- the global administrator Gate bypass does not override the sensitive policy;
- multiple guardians remain independent;
- no medical documents, diagnosis, assessment, or legal determination is introduced;
- relationship verification never reads or depends on support information;
- sensitive values do not enter ordinary sessions, logs, notifications, verification evidence, or broad profile queries;
- metadata-only audits, concurrency protection, and hard deletion work as designed;
- Phase 1, Phase 2, and broader Guardian, Dependent, Learner, Chat, Notification, Authorization, and Moderation regression tests pass.

## Delivery Sequence

After this written specification is approved:

1. Write the dedicated Phase 3 implementation plan.
2. Implement Phase 3 with test-first checkpoints in a separate coding session.
3. Run phase-specific and regression verification.
4. Perform browser QA for registration, dependent controls, guardian access, multiple guardians, and denial cases.
5. Write a Phase 3 verification report containing the tested commit, environment, commands, results, and limitations.
