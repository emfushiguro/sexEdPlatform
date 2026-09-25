# Learner Identity Verification Enhancement

## Problem and scope

Conscious Connections already has two-step learner registration, DOB-based age
classification, email verification, guardian identity review, dependent
verification, guardian relationship review, and a single admin verification
workspace. New learner registration currently moves from email verification
directly to profile completion. It does not collect the learner's own identity
evidence. Guardian and child documents use a mixture of private and publicly
served storage.

This change adds manual, age-appropriate identity review for newly registered
teens and adults. The existing Guardian/Dependent process remains the child
pathway. The selfie is supplementary visual evidence for an administrator; no
facial recognition, biometric profile, liveness detection, or automated
identity decision is included.

## Accepted product decisions

- A teen may create an account without a guardian link. Existing guardian
  checks apply when a relationship is present; learner identity approval never
  verifies guardian authority.
- The new learner identity requirement applies to registrations made after
  this feature is introduced. Existing learners remain exempt.
- Every new teen submission includes a selfie.
- Teen identity documents are a school ID, institution-issued ID, or
  government ID. One qualifying document and a selfie are required.
- Adult identity documents use the current government-ID choices and
  front/back requirements in config/guardian_identity.php. The Other choice
  requires a description and an administrator to confirm that the document is
  government-issued. Every adult submission includes a selfie.
- A learner registered under the new policy who reaches 18 must obtain adult
  identity approval before continued learner access. There is no grace period.
- No retention duration is invented. Current evidence remains under the
  application's retention policy if one exists; a future period can be
  configured without changing the evidence schema.

## Existing integration points

- RegisteredUserController branches children under 13 into guardian
  registration and creates teen/adult learners through the same two-step flow.
- User::calculateAge and User::deriveAgeBracketCache derive the age category
  from birthdate. Verification routing uses these server-side methods, not an
  age or category sent by the browser or a stale cached category.
- EmailVerificationPromptController sends verified users to guardian
  verification or profile completion. It will route new teen/adult learners
  through learner identity verification first.
- VerificationStatus supplies pending, approved, and rejected values.
- ParentChildVerificationController and the admin.parent-verifications views
  provide the existing review workspace.
- GuardianRelationshipVerificationService demonstrates private evidence,
  submission rounds, audit entries, and manual decisions.
- RegistrationTempUploadService currently stages guardian/child documents on
  the public disk. Final child documents are also on that disk and the admin
  child queue constructs public URLs. Guardian identity submissions already
  use the private local disk. The public sensitive-document paths require a
  targeted migration.
- The repository contains no implemented image-resizer module; a guideline
  mentions one. Existing Laravel upload, storage, and image-validation
  conventions will be used without a new media dependency.

## Verification cases, evidence, and history

Create one learner identity case per learner and pathway, teen or adult.
Registration creates the initial case with a null status, meaning evidence
has not been submitted. A missing case means the account is outside the new
policy; it does not mean approved. Cases retain the pathway, selected document
type and government-ID subtype, submission round, status, submission and
review timestamps, reviewer, rejection reason, and superseded timestamp.
There is a unique user/pathway key.

Current evidence belongs to a case and occupies one of three slots:
identity_front, identity_back, or selfie. Each item has its purpose, private
relative path, MIME type, byte size, pixel dimensions, and submission time.
The case document type makes school, institution, and government evidence
distinct. A selfie cannot satisfy an identity-document slot. The back slot is
required only when the selected government-ID rule requires it; otherwise it
is optional and cleared when no longer relevant.

An audit entry records actor, action, submission round, old/new status,
timestamp, and any review reason; its case supplies the pathway. Audit entries
never contain raw images, public URLs, or uploaded file names. Current evidence
paths may be replaced on resubmission; superseded raw files are removed only
after the new files and database state are committed. Audit history remains.

When a covered teen reaches 18, the service marks the teen case superseded
and creates an unsubmitted adult case. The teen case and its decisions remain
historical, while its raw evidence is removed after the transition commits.
A superseded pending teen case leaves the active review queue and cannot be
approved. The adult case requires a new government ID and selfie.
An age-changing DOB correction for a covered account follows the same
server-side pathway check; an approval for the wrong age category cannot
grant access.

## Account and access flow

The normal child path remains guardian registration and dependent review.
For new teens and adults the flow is:

1. Validate DOB and register the account. Create the matching learner identity
   case with account creation so a failed second write cannot leave a new
   learner outside the requirement.
2. Require email verification. Unverified users cannot submit identity
   evidence or reach learner features.
3. Route the verified user to an accessible identity form, then to a pending,
   approved, or rejected status page as appropriate.
4. Permit profile completion and learner features only after current-pathway
   identity approval. Pending/rejected users retain access to verification,
   resubmission, account help and deletion, password actions, privacy pages,
   and logout.
5. Preserve existing guardian relationship checks for linked teens. A
   pending relationship grants no guardian-specific permissions and does not
   replace the learner's identity decision.

The learner registration stepper shows Identity Verification after Verify
Email and before Profile for new registrations. Existing learner profile
completion retains its previous stepper, and guardian/dependent steppers
retain their own flows.

A central server-side learner gate applies across authenticated learner
entry points, including profile completion, learning, seminars, subscriptions,
and chat. It exempts existing accounts, guardians on the existing guardian
flow, children on the existing dependent flow, and non-learner roles. A small
allowlist prevents redirects from looping. Direct URLs and crafted requests
receive the same gate as navigation links.

## Evidence submission and selfie experience

The learner submits identity evidence in two pages. The first page has one
grouped ID selector for school, institution, and government choices as allowed
by the learner's age pathway. It reveals the Other description and back-side
upload only when the selected ID needs them, and previews chosen ID images.
ID images are staged on the private local disk for the current learner, case,
and submission round. Staging does not create review evidence, mark a case
pending, or notify reviewers. Returning from the selfie page retains the
staged ID until final submission; an expired or mismatched draft requires a
new ID upload.

The second page handles selfie capture and upload on phone, tablet, laptop,
and desktop. Camera access starts only on an
explicit action. It prefers the front camera when available, previews the
image, and offers Retake, Use Photo, Replace, and Cancel. Camera streams stop
on completion, cancellation, or component teardown. File upload remains
visible and usable when a camera is absent, denied, disconnected, or fails.
Captures and replacements stay in browser memory until final submission, so
retakes create no server duplicates.
The final consent and submission occur on the selfie page. The server combines
the staged ID with the selfie and revalidates the complete evidence set before
changing the case status.

The form asks for one person, a recent clear photo, good lighting, a visible
face, no sunglasses or face covering, and no heavy filters. It explains that
authorized administrators compare the submitted image manually with identity
evidence.
It does not claim automated matching or liveness. Teen copy is age
appropriate. Controls have labels, keyboard focus, status text, and at least
44-by-44-pixel touch targets; error and fallback messages do not depend on
color alone.

Server validation requires the correct slots for the current DOB-derived
pathway, accepts JPEG/PNG/WebP images with verified image content, limits
each upload to the existing 5 MB ID limit, and requires width and height of
at least 320 pixels and at most 6000 pixels.
Selected ID values must be in the approved choices; Other requires a bounded
description. Only the administrator judges document authenticity and visual
correspondence. Storage failure leaves no pending case and cleans up any
newly written files.

## Manual review, resubmission, and notifications

Add teen and adult learner lists and filters within the existing admin
verification workspace. A focused learner-review controller and service
handle learner actions so the existing parent/child controller does not
absorb unrelated case logic. The same status enum, route authorization
pattern, Blade components, and notification conventions are reused.

The admin detail view places the account and DOB beside the document and
selfie, with authorized inline previews and zoom. It shows status, submitted
time, reviewer, decision, rejection reason, and audit history. The adult
guidance covers account/ID/DOB consistency, document readability and apparent
validity, selfie clarity, and reasonable visual correspondence. The teen
guidance covers document readability, submitted information and age
consistency, selfie clarity, and linked guardian requirements when present.
The checklists guide the reviewer; individual ticks are not persisted. For an
adult Other ID, the approval action requires an explicit confirmation that the
reviewer determined the document is government-issued. Only an authorized
administrator may approve or reject a pending, current-pathway case. A reviewer
must supply a reason to reject. No upload or checklist state automatically
decides a case.

The learner status page shows a rejection reason and allows selective
replacement of corrected ID sides or selfie. The service validates the
resulting complete set, returns the case to pending, increments the round,
clears the current review decision, appends audit history, and notifies the
learner and admins through existing notification conventions. Approved cases
cannot be silently replaced.

## Privacy, authorization, and existing public files

All new learner evidence uses the private local disk with random storage
names. Owner-only submission/status operations and admin-only evidence
preview/decision operations check identity and case ownership server-side.
Responses use private, no-store caching and content-type protection. Request
validation rejects spoofed, corrupt, oversized, and wrong-purpose files.
No evidence path is accepted from client input; no raw evidence is logged or
sent to an external service.

Before rollout, switch RegistrationTempUploadService to private staging.
Session-bound preview endpoints replace public temporary URLs, including
the pre-account guardian form. Final child document previews use an admin
authorized route. An idempotent storage migration inventories existing public
registration-temp and referenced child/guardian identity paths, copies each
file to the same private relative path, verifies the copy, then removes the
public original. Conflicts or missing sources are reported and left untouched
for investigation. Existing sessions can resolve migrated temporary paths.
This migration does not reset or reseed any database table.

The submission page identifies the required evidence, why it is collected,
that authorized administrators alone review it for identity verification,
that selfies are supplementary manual-review evidence, and how the privacy
page describes storage and retention. It links to that page and uses
age-appropriate teen language. The privacy page gains the matching evidence,
review, and retention explanation. No arbitrary retention deadline is added;
policy configuration can be added without changing case or evidence
relationships.

## Failure handling and verification

Camera errors preserve the upload fallback. Invalid files return field-level
errors without changing case status. Storage and database errors clean up
new files and leave earlier good evidence available. Concurrent submissions
and reviews serialize on the case so only one current decision or submission
round wins. Notifications run after committed state and failure to deliver a
notification does not corrupt the decision.

Feature tests cover the 12/13/17/18 boundaries; new versus existing cohorts;
email-first routing; teen/adult evidence requirements; ID purpose isolation;
guardian independence; pending/rejected access; birthday transition;
authorization and private previews; invalid files; review, notifications,
and selective resubmission. JavaScript tests cover camera permission,
unavailable APIs, capture, preview, retake, cancel, and upload fallback.
Storage tests cover private temp and child evidence, migration idempotence,
copy verification, and failure safety. Existing registration, email,
guardian/dependent, admin, onboarding, and notification tests run against
the isolated test database. Manual phone, tablet, and desktop checks cover
camera behavior, responsive layout, keyboard access, and screen readers.

## Out of scope

No third-party identity provider, OCR, facial recognition, similarity score,
liveness detector, age-estimation AI, React/Vue rewrite, new guardian
authority workflow, or retroactive identity lockout for existing learners.
