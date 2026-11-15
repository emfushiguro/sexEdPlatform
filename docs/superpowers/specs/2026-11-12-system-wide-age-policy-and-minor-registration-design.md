# System-Wide Age Policy and Guardian-Assisted Teenager Registration Design

**Date:** 2026-11-12
**Status:** Product design approved; written specification awaiting review

## Goal

Retire Kids as an active learner category and establish exactly two active
learner categories throughout Conscious Connections:

- **Teenager:** ages 10-17, inclusive;
- **Adult:** ages 18-100, inclusive.

Learners below 10 or above 100 are ineligible for new registration and active
learner participation. Every Teenager requires an approved guardian
relationship and guardian authorization. A Teenager does not need a personal
email address; username login and guardian-backed recovery provide the
no-email pathway.

This change preserves historical data and legacy database values. It does not
delete Kids records, silently suspend accounts, or reinterpret unknown dates
of birth.

## Accepted Product Decisions

- Date of birth is the authoritative source for learner age, eligibility, and
  category. A submitted age, category, account type, or eligibility flag is
  never trusted.
- Exactly age 10 is Teenager, exactly age 18 is Adult, and exactly age 100 is
  still eligible.
- Every learner aged 10-17 needs a verified adult guardian, an approved
  Guardian-Dependent relationship, and recorded guardian authorization before
  the learner can activate and verify their account.
- Teenager identity verification remains separate from guardian identity and
  relationship verification. The Teenager submits an accepted school,
  institution, or government ID plus a selfie and awaits explicit
  administrator approval.
- Persona or another provider may collect evidence, but provider completion
  never automatically approves an identity, guardian, or relationship.
- A Teenager's personal email is optional. Ages 10-12 are not required to
  obtain Gmail or any other third-party mailbox. Ages 13-17 are treated the
  same way so one minor flow works consistently.
- A genuine personal email may be added and verified, but a fabricated Gmail
  plus-alias or internal placeholder must not be represented as the
  Teenager's verified email.
- Teenagers sign in with a unique learner username and their own password. The
  guardian never receives the final password.
- The guardian's verified email is the guardian contact and, when the
  Teenager has no verified personal email, the approval channel for account
  recovery. It is not copied into the Teenager's email field.
- Kids remains readable as a legacy internal value where historical integrity
  requires it, but no active form, filter, badge, card, label, navigation
  item, content-authoring option, or ordinary report exposes it.
- Existing accounts, progress, enrollments, attempts, certificates,
  relationships, evidence, and audit history are preserved.
- The policy primarily governs learners. Parent, Admin, Instructor, and
  applicant workflows are not assigned a learner category merely because the
  user has a date of birth.

The guardian requirement is an approved platform safety and eligibility
policy. The implementation and final consent/privacy wording still require
the application's normal product, privacy, and legal review; the platform
does not claim to adjudicate legal parenthood or guardianship.

## Current Architecture and Problems to Correct

The current code already has useful building blocks:

- `User::calculateAge()` derives age from `users.birthdate`.
- `User::deriveAgeBracketCache()` and `User::deriveAccountType()` maintain
  cached learner classification.
- `LearnerProfile::getAgeBracket()` derives a second classification from the
  profile birthdate.
- `Module` owns module learner-category normalization and age ranges.
- `LoginRequest` already accepts either an email address or a learner
  username.
- `ParentRegistrationController` already creates a learner profile with a
  unique username and a pending Guardian-Dependent relationship.
- Guardian identity review, relationship evidence review, learner identity
  cases, private evidence, manual administration, and access middleware
  already exist.

The current behavior is inconsistent with the approved policy:

- age ranges are duplicated and currently treat roughly ages 5-12 as Kids and
  13-17 as Teens;
- some content subsystems use `kids`, `teens`, and `adults`, while seminars
  also use singular `teen` and `adult` values;
- under-13 registration branches to a guardian, but ages 13-17 may register
  independently;
- `User::requiresParentalConsent()` checks only under 13;
- guardian-created dependents receive either a derived Gmail plus-alias or an
  internal `@child.sexed-platform.local` address, which is then marked
  verified even though it is not the learner's own verified mailbox;
- password reset accepts only an email address;
- Kids is exposed in learner dashboards, admin filters and statistics,
  modules, learning paths, seminars, chat suggestions, connector workflows,
  public copy, instructor guidance, seeders, factories, and tests;
- cached classifications can be stale or can incorrectly default an
  unclassifiable learner to an adult account type.

The chosen design strengthens these existing seams instead of creating a
parallel age-category framework or replacing the Guardian-Dependent system.

## Authoritative Learner Age Policy

Create one focused domain policy, `LearnerAgePolicy`, used by registration,
models, services, validation, middleware, queries, reports, and UI presenters.
It accepts a date of birth and an as-of date in the application timezone and
returns one explicit result:

| Result | Age | Active category | Eligible |
|---|---:|---|---|
| `eligible_teenager` | 10-17 | `teens` | Yes |
| `eligible_adult` | 18-100 | `adults` | Yes |
| `ineligible_underage` | Below 10 | None | No |
| `ineligible_overage` | Above 100 | None | No |
| `unclassified` | Missing or invalid DOB | None | No decision |

The canonical internal active keys remain `teens` and `adults` to minimize
changes to existing module, learning-path, cache, and query structures. The
only public labels are the singular product labels **Teenager** and **Adult**.
Legacy singular `teen` and `adult` values are accepted only by compatibility
readers during transition and normalized to the canonical active keys on new
writes.

The legacy `kids` value is excluded from every active-category method. A
separate legacy reader may recognize it for historical records and migration
reports, but it can never be assigned by a new or edited active record.

Age calculation uses calendar dates in `config('app.timezone')`, not elapsed
hours or a hardcoded year. Tests pin the as-of date so birthdays and February
29 are deterministic. The browser may give immediate feedback, but the server
recalculates every decision.

`User`, `LearnerProfile`, `Module`, chat suggestions, connector access, and
other consumers delegate to this policy. Compatibility methods may remain,
but they cannot retain their own ranges. Add `User::requiresGuardian()` for
ages 10-17; the existing parental-consent method delegates to it until callers
are migrated.

## Classification Storage and Compatibility

`age_bracket_cached` remains a query optimization, not an authority. New
active values are only `teens`, `adults`, or `null`. Add
`age_eligibility_cached` with the five explicit policy results so reports can
distinguish eligible, underage, overage, and unclassified learners without
guessing from a null category.

For learner account types:

- ages 10-17 derive `learner-teen`;
- ages 18-100 derive `learner-adult`;
- ineligible or unclassified learners retain their existing account type for
  audit compatibility, but that value grants no learner access and cannot be
  recalculated to Adult by default;
- `learner-child` remains readable for legacy rows but is never assigned by a
  new registration or edit.

The live policy result, not the cache, controls access and registration.
Caches refresh when date of birth, learner role, or relevant relationship
state changes and through an idempotent scheduled reconciliation so birthdays
cannot leave stale classifications indefinitely.

The `users.birthdate` value is authoritative after account creation. Profile
birthdate displays and writes must synchronize with it or be removed as an
independent decision source. A date-of-birth correction uses the existing
authorized identity-review process, reruns the policy, refreshes caches, and
re-evaluates guardian and identity requirements inside one transaction.

## Registration Entry Points

Every learner entry point uses the same policy:

- public learner registration;
- Teenager-initiated guardian handoff;
- guardian-created dependent registration;
- administrator-created learner accounts;
- administrator date-of-birth edits;
- APIs or future integrations that create or modify learners.

Ages below 10 and above 100 receive a clear eligibility error before an active
learner account is created. Missing, future, or invalid dates are rejected for
new learner registrations. Requests that submit `kids`, alter an age field, or
claim a conflicting account type are ignored or rejected at the trust
boundary.

Adult learners aged 18-100 continue through independent registration,
verified personal email, Adult government-ID and selfie verification, manual
approval, and profile completion.

Teenagers aged 10-17 enter the guardian-assisted flow described below.

## Teenager Guardian-Assisted Registration

### Starting the request

The primary path is guardian-first. An approved guardian signs in and starts
registration for a Teenager.

A Teenager may also start a handoff request by entering basic personal details
and the guardian's email address. This does not create an active learner login.
It sends a single-use, expiring invitation to the guardian. The guardian must
register or sign in, and the flow then becomes the same guardian-first process.
Expired, declined, or abandoned handoffs do not grant access and can be
restarted without creating duplicate learner accounts.

The handoff stores only the minimum details needed to connect the request to
the guardian, protects its token in hashed form, and expires it under a
configured registration-retention policy. It does not collect identity or
relationship evidence before the guardian authenticates.

The guardian must have an active account, a verified genuine email, completed
guardian onboarding, and approved guardian identity before creating the
restricted Teenager account.

### Guardian submission and relationship review

The guardian supplies or confirms:

- the Teenager's legal name and date of birth;
- location and other currently required dependent information;
- the claimed relationship type;
- the required relationship evidence;
- the approved guardian-authorization and privacy-consent text and version;
- an optional proposed learner username;
- the Teenager's genuine personal email only when one exists.

Guardian identity, the Guardian-Dependent relationship, guardian
authorization, and Teenager identity are four separate decisions. None
automatically approves another.

After the guardian submits, the platform creates a restricted learner account
and pending relationship in one transaction. The technical account exists so
the existing relationship and evidence models can reference it, but the
Teenager cannot yet sign in or use learner features. Administrator relationship
approval activates only the relationship. It does not approve the Teenager's
identity.

Guardian authorization is versioned and records the guardian, relationship,
consent version, timestamp, and request context. If the relationship is
rejected, revoked, or becomes inactive before activation, the Teenager cannot
claim the account.

A rejected or abandoned restricted account never becomes a normal learner
account. It remains inaccessible for resubmission, audit, and the configured
privacy-retention process; it is not silently activated, suspended, or merged
with another account.

### Account activation without personal email

Once the relationship is approved and guardian authorization is valid, the
platform sends the verified guardian a notification and creates a one-time,
expiring activation handoff. The raw token is never stored, logged, or placed
in an audit record.

The guardian opens the activation page and hands the device to the Teenager,
or gives the Teenager the short activation code for another device. The
Teenager then:

1. confirms or selects the unique learner username;
2. creates and confirms their own password;
3. reads and accepts the age-appropriate privacy notice;
4. enters the Teenager identity-verification flow.

The guardian does not set, see, or receive the final password. Reuse, expiry,
or an invalid activation token produces a generic failure and requires a new
handoff from the guardian dashboard.

If the Teenager has a genuine email, it is optional and independently
verified before it is used for mail or recovery. If there is no email,
`users.email` remains null for that minor learner. `email_verified_at` means
only that the user's own genuine mailbox was verified; guardian authorization
must never be represented by marking a fabricated address as verified.

### Teenager identity and final access

After activation, the Teenager submits one accepted school, institution, or
government ID plus a selfie through the existing Teenager identity pathway.
A guardian may assist a younger Teenager with navigation, but the evidence is
the Teenager's evidence and remains subject to the existing owner and
administrator authorization rules.

Persona completion or successful upload leaves the identity case pending.
Only an authorized administrator can approve it. After approval, the Teenager
completes the learner profile.

Full learner access requires all of the following at request time:

- DOB currently classifies the user as an eligible Teenager;
- at least one active, approved Guardian-Dependent relationship exists whose
  same guardian has approved identity;
- current guardian authorization is recorded by that same guardian for that
  same relationship;
- the account activation handoff was completed;
- the current Teenager identity case is approved;
- the learner profile is complete;
- existing account-status and authorization checks pass.

Before all gates pass, the account may access only the routes needed to finish
guardian handoff, activation, identity verification, privacy and consent,
password and recovery, support, account deletion/data rights, and logout. It
cannot enter learning, community, chat, seminars, subscriptions, marketplace,
or other protected learner areas.

## Sign-In, Notifications, and Recovery Without Email

Teenagers sign in using their learner username and password. Existing email
login remains available only when a genuine email has been added.

Mail routing is explicit:

- guardian approval, consent, relationship, activation, recovery, and
  security notices go to the verified guardian;
- Teenager identity status can appear in-app and may notify the guardian when
  the notice is appropriate for completing the minor workflow;
- raw identity or relationship evidence, passwords, tokens, private messages,
  and sensitive identifiers are never included in email;
- ordinary private learner notifications are not silently redirected to the
  guardian.

For a Teenager without a verified personal email, password recovery works as
follows:

1. The Teenager enters the username.
2. The platform always returns the same generic response and applies existing
   rate limits to prevent account enumeration.
3. A recovery approval request goes to the current verified guardian.
4. The guardian authenticates to their own account and approves the reset.
5. The Teenager receives a single-use, expiring handoff and privately chooses
   a new password.

The guardian never receives the new password. If no approved guardian is
available, the relationship is disputed, or recovery looks unsafe, the flow
stops and requires support-assisted identity re-verification. A revoked or
inactive relationship cannot approve recovery.

Multiple Teenagers may share one guardian email because their accounts are
identified by unique usernames and relationships, not duplicated email
values.

## Turning 18 and Other Age Transitions

On the eighteenth birthday, the live policy classifies the learner as Adult.
The Guardian-Dependent row and audit history remain intact, but guardian
authorization and guardian permissions no longer control Adult access or
recovery. The relationship is historical unless the Adult explicitly uses an
existing non-guardian relationship feature in the future.

The existing identity policy opens a fresh Adult government-ID and selfie case
and requires Adult approval before protected Adult learner access. Prior
Teenager approval and evidence remain historical and are handled by the
existing private-evidence retention rules.

A no-email user keeps username login. The transition asks the Adult to add and
verify a personal recovery email. The former guardian is not retained as the
Adult's recovery contact; until the Adult establishes their own recovery
method, recovery requires support-assisted identity verification rather than
guardian approval.

A verified Adult whose corrected DOB makes them 10-17 moves immediately into
the guardian-required flow. An Adult identity approval does not satisfy the
guardian relationship or Teenager identity case. A correction making a learner
ineligible restricts participation without deleting the account or history.

## Retiring Kids From Active Content and Interfaces

All active category providers return only Teenager and Adult. This includes:

- registration and onboarding;
- learner and guardian dashboards;
- profiles, badges, status copy, and navigation;
- admin user creation, editing, filters, cards, statistics, and exports;
- module and learning-path authoring and eligibility;
- seminar and educational-event authoring and registration;
- connector membership and module assignment;
- chat suggestion audiences;
- public landing and informational pages;
- instructor guidance;
- emails, notifications, validation messages, and empty states;
- JavaScript configuration, factories, seeders, fixtures, and tests.

The UI must not show disabled or retired Kids options. It simply communicates
the two-category policy.

Existing content that contains a legacy Kids assignment is not automatically
retargeted because content intended for ages 5-12 cannot safely be assumed
appropriate for ages 10-17:

- Kids-only modules, paths, seminars, events, and suggestion entries are
  excluded from active discovery, assignment, and authoring until an
  authorized owner explicitly retargets them;
- mixed-category content continues for its remaining active Teenager or Adult
  assignments, while the legacy Kids association remains available to the
  migration/audit report;
- titles or descriptions containing user-facing Kids wording are flagged for
  owner review and hidden from active delivery until corrected;
- no migration silently changes the pedagogical audience of content.

A restricted administrative migration report may show raw legacy values for
audit and remediation. Ordinary admin management and reporting do not expose
Kids as an active category.

## Existing Learners and Data Transition

Use additive, forward-only migrations and an idempotent classification command
with dry-run and apply modes. Never reset, truncate, destructively reseed, or
replace the development database.

The dry run reports counts and identifiers without changing data. Apply mode
processes bounded batches, locks each affected learner while updating derived
classification, records failures, and can be rerun safely.

Existing learners are handled as follows:

### Ages 10-17

- set active category cache to `teens` and account type to `learner-teen`;
- preserve credentials, learner profile, progress, enrollments, attempts,
  certificates, evidence, notifications, and relationships;
- preserve a current approved Teenager identity case when it remains valid;
- require an approved guardian relationship and authorization before continued
  protected learner access;
- learners who previously registered independently are routed to the guardian
  handoff rather than deleted or suspended;
- existing personal email and password remain usable.

### Ages 18-100

- set active category cache to `adults` and account type to `learner-adult`;
- preserve all account and learning data;
- stop applying Teenager guardian gates;
- retain previous Guardian-Dependent rows and audits as history rather than
  deleting them.

### Below 10 or above 100

- preserve the account, relationships, and historical activity;
- set no active category and record the specific ineligible result;
- block active learner participation through the central learner gate;
- retain access to support, privacy, account/data-rights actions, and logout;
- place the record in an administrative review report without silently
  changing the general account status.

### Missing or invalid DOB

- preserve the account and do not guess a category;
- set the classification to unclassified;
- restrict protected learner participation;
- place the record in the administrative review report for authorized DOB
  correction.

The transition does not delete the legacy `kids` database value or rewrite
immutable historical events. It stops new assignment and updates only current,
derived classification where DOB is reliable.

## Reports and Analytics

Active reports, filters, exports, and dashboard statistics contain Teenager
and Adult only. Ineligible and unclassified learners may be shown as separate
administrative review counts, never folded into either active category.

Historical reports preserve their original facts. A restricted audit export
may include legacy category values with clear historical labeling. Active
category totals use the authoritative policy and must not silently omit
ineligible or unclassified records from reconciliation totals.

## Authorization, Privacy, and Failure Handling

- All age, guardian, consent, activation, recovery, and identity decisions are
  enforced server-side.
- Relationship and identity evidence stays in private storage behind
  owner/admin authorization and no-store responses.
- Consent and audit records contain status metadata, not raw evidence,
  passwords, or tokens.
- Activation and recovery tokens are hashed, single-use, expiring, rate
  limited, and invalidated after use or relationship revocation.
- Database state changes use transactions and row locking where concurrent
  guardian, Teenager, or administrator actions could conflict.
- Notification failure does not roll back an already committed review
  decision; it is logged without raw email, token, or evidence contents and
  can be retried safely.
- Storage failure leaves no partially submitted identity or relationship
  decision and compensates newly written files.
- Direct URLs and crafted requests receive the same gates as navigation.
- Existing suspension, archival, role, permission, and moderation checks
  remain in force.

## Testing and Verification

### Policy unit tests

- age 9 is underage and ineligible;
- age 10 and age 17 are Teenager;
- age 18 and age 100 are Adult;
- age 101 is overage and ineligible;
- birthday boundaries use the application timezone;
- February 29 birthdays are deterministic;
- missing, invalid, and future DOB values are not guessed;
- legacy aliases are readable but cannot be written as active categories.

### Registration and recovery feature tests

- guardian-first Teenager registration;
- Teenager-started guardian handoff;
- guardian identity and relationship review remain separate;
- consent version and actor are recorded;
- no activation before relationship approval;
- no personal email is required for ages 10-17;
- a genuine optional email follows normal verification;
- no Gmail alias or internal placeholder is created or marked verified;
- username activation, login, expiry, reuse protection, and rate limiting;
- guardian-backed password recovery without exposing the new password;
- recovery denial for revoked, inactive, or missing relationships;
- support fallback does not bypass identity checks;
- Teenager ID plus selfie submission and explicit admin approval;
- full learner access only after every gate succeeds.

### Entry-point and security tests

- public, guardian, admin, and API learner creation use the same policy;
- crafted age, category, account type, and Kids values cannot bypass it;
- DOB edits re-evaluate category, guardian, identity, and eligibility state;
- non-learner roles are not incorrectly subjected to learner categories;
- direct protected routes are denied while completion routes remain available;
- guardian notifications never include raw evidence or private learner data.

### Legacy transition tests

- existing 10-17 Kids rows become Teenager without losing related data;
- existing 18-100 rows become Adult without losing related data;
- independently registered minors are preserved and routed to guardian setup;
- below-10, above-100, and unclassified accounts are preserved and flagged;
- historical relationships and category records remain intact;
- dry-run performs no writes;
- apply mode is batched, transactional, and idempotent;
- no ineligible or unclassified learner defaults to Adult.

### Content and UI tests

- Kids is absent from every active category provider and ordinary UI;
- module, learning-path, seminar, connector, and chat behavior uses the new
  10-17 and 18-100 boundaries;
- Kids-only content is hidden and flagged rather than auto-retargeted;
- mixed legacy content retains only its active audiences in ordinary delivery;
- reports reconcile Teenager, Adult, ineligible, and unclassified counts;
- responsive and accessible forms retain labels, focus, errors, and touch
  targets.

### Regression verification

Run focused and relevant broader suites for authentication, email
verification, password reset, guardian identity, relationship verification,
learner identity, profile completion, admin user management, modules, learning
paths, seminars, connectors, chat, notifications, reports, and exports.
Perform role-based browser checks for guardian, Teenager with email, Teenager
without email, Adult, and administrator. Report automated, browser, provider,
database, and production evidence separately; an unrun gate is not passed.

## Rollout Sequence

1. Add the authoritative policy and boundary tests without changing data.
2. Add compatibility schema for explicit eligibility and nullable minor email.
3. Move every age/category caller to the policy and active category provider.
4. Implement guardian-required activation and username/recovery paths.
5. Remove Kids from active UI and stop all new Kids writes.
6. Generate the legacy account and content dry-run reports.
7. Review anomalies and back up the target database.
8. Apply approved, idempotent reclassification batches.
9. Run focused suites, broader regression tests, and role-based browser checks.
10. Deploy with monitoring for failed activation, recovery, classification,
    relationship, identity, and notification transitions.

No production data transition is implied by code deployment alone. The dry
run, backup, anomaly review, and explicit application of the approved
reclassification remain separate operational gates.

## Completion Criteria

The design is implemented when:

- only Teenager and Adult are active learner categories;
- every learner entry point enforces ages 10-100 from DOB;
- every 10-17 learner requires a verified guardian relationship and consent;
- a Teenager can activate, sign in, verify identity, and recover an account
  without owning an email address;
- fabricated child email addresses are no longer created or marked verified;
- Teenager identity approval remains explicit and separate from guardian and
  relationship approval;
- Kids is absent from ordinary user and admin experiences and cannot be newly
  assigned;
- legacy records and Kids values remain safely readable for history/audit;
- existing learner data and relationships survive reclassification;
- ineligible and unclassified accounts are preserved, restricted, and
  reviewable rather than deleted or guessed;
- active content with legacy Kids targeting is hidden or explicitly retargeted,
  never silently reinterpreted;
- focused and regression verification results are reported honestly.

## Non-Goals

- deleting the Kids enum/value or historical records;
- automatically rewriting Kids content as Teenager content;
- SMS or phone-number authentication;
- requiring a Google, Gmail, or school-managed account;
- facial recognition, age-estimation AI, automatic document approval, or
  legal adjudication of guardianship;
- redesigning unrelated pages, permissions, or role systems;
- applying learner age categories to unrelated Parent, Instructor, Admin, or
  applicant accounts;
- destructive database resets or reseeding.
