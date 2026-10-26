# Persona Identity Verification Design

**Status:** Approved

**Date:** 2026-10-26

**Scope:** Persona Sandbox integration for learner and guardian identity verification

## Summary

Conscious Connections will add Persona as an external identity-evidence step inside the existing learner and guardian verification workflows. Persona does not replace the application's local evidence, administrator review, approval statuses, access restrictions, guardian onboarding, or dependent relationship checks.

The approved approach preserves both evidence sources:

- Persona runs first.
- Once the authoritative Persona result satisfies the applicable policy, the user continues to the existing local evidence step.
- Authorized administrators review the local evidence and a minimal Persona result summary before making the application's final approve or reject decision.
- Persona-hosted images are fetched only when an authorized administrator requests them. They are never copied into application storage.

This first release is Sandbox-only. Every Persona result shown in the application must be labelled as simulated. Production activation remains blocked until privacy, consent, retention, minors, biometric-processing, and operational reviews are completed.

## Goals

- Use the Persona Selfie template for teen learners aged 13–17 who submit a school or institution ID.
- Use the Persona GovID + Selfie template for adult learners, guardians, and eligible teen learners using a supported Philippine government ID.
- Select templates and enforce ID policy on the server.
- Create, resume, synchronize, expire, and replace inquiries without duplicate active attempts.
- Treat Persona's API as authoritative; never trust a browser return or webhook body as proof of success.
- Preserve existing private local evidence and administrator decision authority.
- Keep Persona lifecycle, Persona policy, local application review, account access, profile completion, and guardian-dependent relationship states separate.
- Add durable, privacy-minimized audit history for Persona attempts and guardian identity decisions.
- Reuse existing Laravel, private-storage, authorization, notification, and review patterns with no new package.

## Non-goals

- Automatically approving a learner or guardian from a Persona result.
- Replacing local school, institution, government-ID, selfie, birth-certificate, or relationship evidence.
- Adding Persona to instructor, Connector, Connector-member, or other unrelated verification flows.
- Verifying a dependent's identity or relationship through the guardian's Persona inquiry.
- Supporting arbitrary foreign IDs, arbitrary client-selected templates, or the application's `other` government-ID choice.
- Persisting Persona-hosted identity images, raw webhook bodies, raw API responses, document numbers, confidence scores, or biometric templates.
- Creating a second administrator dashboard.
- Enabling Persona in production as part of the Sandbox implementation.

## Existing Application Boundaries

The implementation extends these existing boundaries instead of replacing them:

- `LearnerIdentityVerificationController` and `LearnerIdentitySubmission` own the learner's local document and selfie submission.
- `LearnerIdentityDocumentDraft` and `RegistrationTempUploadService` stage local learner documents privately between the existing document and selfie pages.
- `LearnerIdentityReview` owns learner administrator decisions and append-only learner audit entries.
- `GuardianIdentityVerificationController` and `ParentChildVerificationService` own guardian identity submission and decisions.
- `EnsureLearnerIdentityVerified` and the existing guardian middleware keep unapproved users out of protected areas.
- `show-learner.blade.php` and `show-parent.blade.php` are the existing administrator review surfaces.
- Dependent birth-certificate, relationship, invitation, and guardian-authority workflows remain independent.

Existing pending and approved submissions continue unchanged. Persona becomes mandatory only for:

- a new local identity submission that has not yet been made after this feature is enabled; and
- a new submission round following rejection.

No existing approved decision is revoked, and no existing pending submission is invalidated or forced back through Persona.

## Approved Decisions

| Concern | Approved decision |
|---|---|
| Evidence model | Preserve local evidence alongside Persona. |
| Evidence order | Complete Persona first, then collect the existing local evidence. |
| Teen school/institution path | Persona Selfie, followed by local school/institution ID and a manual portrait comparison. |
| Teen government-ID path | Persona GovID + Selfie, followed by the existing local government ID and local selfie. |
| Adult learner path | Persona GovID + Selfie, followed by the existing local government ID and local selfie. |
| Guardian path | Persona GovID + Selfie, followed by the existing local government ID. No new local guardian selfie is added. |
| Administrator authority | Persona supplies evidence; an authorized administrator makes the final application decision. |
| Inquiry ownership | One current inquiry per user and target submission round, with append-only replacement attempts. |
| Hosted experience | API-created Persona Hosted Flow in the same tab, with a safe application return route. |
| Synchronization | Signed webhook triggers queued reconciliation; the server refetches authoritative Persona state. Return and manual refresh may also reconcile. |
| Country policy | Philippine government IDs only, enforced both in Persona configuration and in application policy. |
| ID-class policy | Deny by default. Only explicit, verified application-to-Persona mappings are eligible. |
| Persona media | Authorized, on-demand server proxy only; never persist Persona files or reveal upstream URLs. |
| Rollout | Sandbox-only, with simulated labels and explicit production gates. |

## End-to-end Flows

| User and selected evidence | Persona template | Local evidence retained | Administrator responsibility |
|---|---|---|---|
| Teen, school ID | Selfie | School ID front/back as currently required | Review school ID and manually compare its portrait with the Persona selfie. Do not claim document authentication or automated face matching. |
| Teen, institution ID | Selfie | Institution ID front/back as currently required | Review institution ID and manually compare its portrait with the Persona selfie. |
| Teen, supported Philippine government ID | GovID + Selfie | Existing local government ID and local selfie | Review the local evidence and Persona's eligible, simulated Sandbox summary. |
| Adult learner, supported Philippine government ID | GovID + Selfie | Existing local government ID and local selfie | Preserve the existing manual learner decision. |
| Guardian, supported Philippine government ID | GovID + Selfie | Existing local government ID only | Preserve the existing guardian decision, onboarding, and dependent-creation gates. |
| Dependent | None | Existing birth-certificate and relationship evidence | Preserve the current dependent and relationship review. |

The user sequence is:

~~~text
validated local choice
        |
        v
create or resume server-owned Persona inquiry
        |
        v
Persona Hosted Flow in the same tab
        |
        v
safe return -> authoritative server reconciliation
        |
        +-- not eligible / incomplete -> retry or resume guidance
        |
        +-- policy eligible -> existing local evidence form
                                      |
                                      v
                            existing administrator review
                                      |
                                      v
                            application approve or reject
~~~

Creating an inquiry, opening Hosted Flow, returning to the application, receiving a webhook, or seeing a Sandbox `completed` state never changes the application's review status by itself.

## Integration Architecture

The integration is a focused ledger around the existing identity system:

- `PersonaClient` is the only class that sends authenticated Persona API requests. It uses Laravel's HTTP client, the pinned API version, short timeouts, bounded retries for safe transient failures, and sanitized exceptions.
- `PersonaTemplateSelector` derives the flow and configured template from the authenticated user, current verification case, date of birth, registration role, and validated local ID choice. Browser input cannot contain a template ID.
- `PersonaPolicyEvaluator` converts authoritative inquiry and verification resources into a small application policy result without storing raw responses.
- `PersonaInquiryService` creates, resumes, replaces, and owns attempts under a database transaction and row lock.
- `PersonaInquirySynchronizer` refetches the inquiry and referenced verification resources, applies monotonic updates, and records only normalized fields.
- `SyncPersonaInquiry` performs webhook-triggered reconciliation on the existing queue.
- A narrow authenticated controller owns start, resume, return, refresh, and replace actions.
- A signed API webhook endpoint records deduplicated event metadata and queues reconciliation.
- A narrow administrator-only evidence controller proxies a requested Persona image after authorization and a fresh server-side lookup.

No generic third-party-verification framework, repository layer, new SDK, or separate review application is introduced.

## Configuration and Persona Contract

`config/persona.php` reads the existing variables:

- `PERSONA_API_KEY`
- `PERSONA_SELFIE_TEMPLATE_ID`
- `PERSONA_GOVID_SELFIE_TEMPLATE_ID`

The implementation adds:

- `PERSONA_WEBHOOK_SECRET`
- `PERSONA_ENABLED`, defaulting to false
- `PERSONA_ENVIRONMENT`, which must remain `sandbox` in this release
- `PERSONA_ENFORCED_FROM`, an explicit timestamp separating grandfathered submissions from Persona-required rounds

The API key and webhook secret are server-only. Template IDs are selected from configuration and never accepted from request data. The API contract is pinned to Persona version `2026-09-29`. Enabling the integration requires all settings and a valid enforcement timestamp; a non-Sandbox environment fails closed.

The client uses:

- `POST /api/v1/inquiries` to create an inquiry with `data.attributes.inquiry-template-id`, a stable local reference, and an `Idempotency-Key` header;
- `GET /api/v1/inquiries/{inquiry-id}` to retrieve authoritative inquiry state;
- the documented resume operation when a resumable session token is needed;
- `GET /api/v1/verification/government-ids/{verification-id}` for government-ID policy fields and authorized file locations; and
- `GET /api/v1/verification/selfies/{verification-id}` for the selfie verification result and authorized center image.

The Hosted Flow URL is built server-side from the returned inquiry ID, documented resume token when required, and an application-owned return URL. Resume tokens are short-lived secrets and are never stored.

Persona POST requests use a stable per-attempt idempotency key. Repeating the application's start action returns the existing current attempt rather than issuing a second create request.

## Data Model

### `persona_inquiries`

One row represents one immutable attempt. A replacement creates another row and marks the previous attempt superseded.

Required fields:

- local primary key;
- `user_id`;
- nullable `learner_identity_verification_id` for learner attempts;
- `flow_type`: teen school, teen government, adult government, or guardian government;
- `target_submission_round` and monotonic `attempt_number`;
- frozen `local_document_type` and nullable `local_government_id_type`;
- `template_key` and the actual configured `template_id` used;
- unique stable `reference_id` and `idempotency_key`;
- nullable unique `persona_inquiry_id`;
- nullable Persona government-ID and selfie verification identifiers;
- exact bounded `persona_status` from the authoritative API;
- normalized `verification_outcome`;
- normalized `country_code` and `id_class` when supplied;
- separate `country_policy_status` and `document_policy_status`;
- `is_sandbox`;
- Persona event time, last synchronized time, completion/expiration/supersession times, and a sanitized failure category;
- Laravel timestamps.

The row contains no raw payload, document number, name extracted from an ID, birth date extracted from an ID, address, image URL, image bytes, score, or biometric data.

The service serializes start and replacement actions by locking the owning user and, for learners, the current verification case. Database uniqueness protects Persona inquiry IDs, references, idempotency keys, and attempt numbers. Application locking enforces one non-superseded attempt for the target submission round.

### `persona_webhook_events`

This table is a small delivery ledger:

- unique Persona event ID;
- bounded event type;
- nullable Persona inquiry ID;
- authoritative event-created timestamp;
- received, processed, and failed timestamps;
- processing state and sanitized failure category.

The raw body is used only long enough to validate the signature and extract identifiers. It is not persisted. The queued job uses the inquiry ID to retrieve the authoritative resource.

### `guardian_identity_audits`

The existing guardian status fields represent only the latest state, so a small append-only table records:

- guardian user;
- nullable actor;
- action;
- previous and next application statuses;
- related local Persona attempt;
- attempt number and reason;
- creation time.

Existing learner audit rows remain the authority for learner decisions. This table does not duplicate learner history or dependent relationship events.

All schema work is additive. The migration does not rewrite identity data. Its rollback method must refuse destructive reversal because routine rollback must not erase identity or audit history.

## State Separation

The following axes remain independent:

1. Persona inquiry lifecycle — the exact authoritative provider status.
2. Persona verification outcome — not ready, eligible evidence, ineligible, retryable, or inconclusive.
3. Application policy — country and selected-document compatibility.
4. Local evidence submission — unsubmitted, pending review, approved, or rejected in the existing model.
5. Account and feature access — controlled by existing middleware.
6. Profile completion and guardian onboarding.
7. Guardian-dependent relationship verification.

Only a current, non-superseded, policy-eligible Persona attempt for the user's target submission round unlocks the local evidence form. It does not unlock protected learner or guardian features and does not write `approved` to an application status.

## Server-side Template and Document Policy

Template selection uses current server-owned data:

- age is derived from the saved date of birth;
- the learner's current pathway comes from `LearnerIdentityRequirement`;
- guardian eligibility comes from the existing registration and onboarding state;
- the local ID choice is validated against `config/guardian_identity.php` and frozen on the attempt.

Government-ID support is deny-by-default. The initial one-to-one candidates from Persona's documented class list are:

| Application type | Persona class |
|---|---|
| Passport | `pp` |
| Driver's license | `dl` |
| UMID | `umid` |
| Postal ID | `pid` |
| Voter's ID | `vid` |

Each mapping must also be enabled by the configured GovID + Selfie template and proven in Sandbox before activation. Generic Persona class `id` is not accepted as proof of any specific application ID type. PhilHealth, senior-citizen, PWD, PRC, national-ID, `other`, and any unmapped choice stay unavailable for the Persona government route until an exact documented mapping and configured-template result are verified.

An eligible government attempt requires all of the following from an authoritative fetch:

- the inquiry reached the applicable completed state;
- the required government-ID and selfie verifications are present and passed;
- the reported issuing country is `PH`;
- the reported ID class exactly matches the frozen application choice;
- the attempt is current, belongs to the user and target round, and is not expired or superseded.

A foreign country, missing country, missing class, ambiguous class, mismatched class, absent required verification, failed check, or inconclusive state is never treated as eligible. The UI offers the safe resume, retry, or replacement action supported by the actual state.

The teen school/institution route does not run government-ID country or class policy. It requires the configured Selfie inquiry's authoritative successful result, then sends the learner to the existing local ID upload. The administrator is explicitly told that Persona did not authenticate the school ID or perform an ID-to-selfie match.

## Inquiry Lifecycle and Concurrency

- `start` returns the current resumable attempt when one exists.
- A create call first commits a local attempt with a unique reference and idempotency key, then associates the returned Persona inquiry under a transaction-safe update.
- A retry of a timed-out create reuses the same idempotency key.
- `resume` requests a fresh documented session token and immediately redirects; it does not persist that token.
- `replace` is explicit and allowed only for a current failed or expired attempt. It locks ownership, supersedes the old row, and creates the next attempt.
- A new rejected local submission round cannot reuse the prior round's Persona attempt.
- An older event may enrich its own historical row but cannot become current, unlock evidence, or alter a newer local submission.
- Concurrent start requests converge on one current attempt through row locking and idempotency.

API failure leaves the existing local review state untouched. A failed create remains safely retryable with the same idempotency key or is marked with a sanitized operational category. Secrets and provider response bodies are excluded from logs and user messages.

## Webhook and Reconciliation Design

The webhook endpoint is outside the web/CSRF group and uses a dedicated signature middleware.

The middleware:

- reads the exact raw request body;
- parses all documented `Persona-Signature` values needed for secret rotation;
- rejects stale timestamps using a bounded tolerance;
- computes HMAC over `timestamp.body` with the configured webhook secret;
- compares signatures with constant-time equality; and
- fails closed when the secret or valid signature is absent.

After signature validation, the controller validates the minimum event envelope, inserts the event ID once, and returns promptly. Duplicate event IDs return success without duplicate work. Unknown inquiry IDs are recorded as ignored and cannot touch a user.

The queued job never trusts outcome fields in the event body. It loads the associated local attempt and calls `PersonaInquirySynchronizer`, which refetches the inquiry and required verification resources. Updates are monotonic by Persona's documented event-created time and local synchronization time. Stale events cannot regress or reactivate an attempt.

The safe return route and an authenticated manual-refresh action may invoke the same synchronizer. They are reconciliation opportunities, not proof of success.

## User Experience

Existing learner and guardian pages gain a compact Persona step:

- explain that Persona is an identity-evidence provider and application approval is still manual;
- label every result and preview `Simulated Sandbox result`;
- show Begin, Continue, Refresh, or Start a new attempt only when valid for the current state;
- show accurate non-sensitive created, in progress, completed, failed, expired, unavailable, and retry guidance;
- preserve the existing rejection reason and resubmission instructions;
- keep the selected ID type fixed through Persona and local upload;
- return to the correct learner or guardian page after Hosted Flow;
- never show an application-approved badge based only on Persona.

Persona runs before local file collection. This avoids creating a second long-lived staging scheme across an external redirect. For a rejected application case, starting a new submission round requires a new eligible Persona attempt before replacement local evidence is accepted.

## Administrator Review

The current learner and guardian detail pages gain one compact Persona section and attempt history. No new dashboard is created.

The current attempt summary shows only:

- masked/local inquiry reference;
- template purpose;
- exact lifecycle status and normalized outcome;
- country and document policy states where applicable;
- supported issuing country and document class when present;
- required-check state;
- synchronized time;
- a prominent Sandbox/simulated label.

The history lists attempts, current/superseded state, and safe timestamps. It does not show raw payloads, scores, document numbers, extracted identity fields, or upstream URLs.

For school/institution routes, the review page presents the local ID and authorized Persona selfie together with precise guidance: visually compare the portrait, but do not infer that Persona authenticated the document or performed a face match.

For government routes, the page keeps local evidence and Persona evidence visually separated. Classification is not represented as proof of authenticity or ownership.

Approval services enforce the Persona gate under a database lock. Hiding an approval button is not sufficient. Direct approval requests fail when the attempt is missing, stale, superseded, failed, foreign, mismatched, or otherwise ineligible.

## Persona Media Proxy

An administrator requests a semantic artifact such as `government-id-front`, `government-id-back`, or `selfie` against a local `PersonaInquiry`. The browser never supplies a Persona verification ID or remote URL.

The controller:

- authenticates the administrator and authorizes the related learner or guardian review;
- verifies that the attempt belongs to the reviewed subject;
- derives the stored verification ID;
- retrieves the current Persona resource server-side;
- selects only the documented URL for the requested artifact;
- requires HTTPS and an allowlisted Persona-controlled host;
- does not forward cookies or authorization to redirected non-allowlisted hosts;
- permits only expected image content types and a bounded response size;
- streams the bytes with `Cache-Control: private, no-store` and `X-Content-Type-Options: nosniff`; and
- never returns, logs, or persists the upstream URL or bytes.

Errors become a generic unavailable-preview response. They do not leak inquiry ownership or provider details.

## Authorization, Privacy, and Logging

- All user actions require authentication, verified email, current ownership, valid registration context, and CSRF protection.
- The learner identity middleware explicitly allows only the required identity routes while the user is blocked elsewhere.
- Webhook requests use signature validation and rate limiting, not browser authentication.
- Administrator summaries, histories, and media require the existing administrator role boundary.
- Local evidence remains on the existing private disk and continues through existing authorized download/preview endpoints.
- Persona images remain with Persona and are viewed only through the transient proxy.
- Logs contain local attempt IDs and sanitized categories, never secrets, raw payloads, identity images, document numbers, remote media URLs, or biometric results beyond the normalized operational state.
- Inquiry IDs are treated as sensitive metadata and are not exposed to unrelated users.
- Existing records and retention behavior are unchanged.

## Notifications

Notifications are emitted only for meaningful transitions:

- Persona action is required after a retryable failure or expiration;
- authoritative Persona evidence becomes eligible and the user may continue local submission; and
- the existing local submission, approval, and rejection notifications continue unchanged.

Duplicate webhooks and repeated refreshes do not create repeated notifications. Notifications are dispatched after commit and contain no Persona inquiry ID or identity data.

## Failure Handling

| Failure | Safe behavior |
|---|---|
| Persona unavailable or times out | Keep local application status unchanged; show a retryable generic message; reuse the attempt idempotency key. |
| Invalid or missing webhook signature | Reject without processing. |
| Duplicate webhook | Return success after the unique event lookup; do not duplicate work or notifications. |
| Out-of-order webhook | Refetch authoritative state and refuse regression. |
| Unknown inquiry | Record an ignored event category; change no user state. |
| Foreign, missing, or ambiguous government-ID country | Mark policy ineligible/inconclusive; do not unlock local submission or approval. |
| Unsupported or mismatched ID class | Mark document policy ineligible; require a supported selection/new attempt. |
| Failed or expired inquiry | Offer documented resume or explicit replacement; retain the old row. |
| Browser return without completion | Reconcile and show the current state; do not infer success. |
| Persona preview unavailable | Keep the review page usable, disable the final decision if required evidence cannot be reviewed, and expose no upstream detail. |
| Queue delay | Manual refresh can use the same authoritative synchronizer; application approval remains blocked until current state is known. |

## Testing Strategy

Automated tests use Laravel HTTP fakes and synthetic signed webhook bodies. No test calls Persona or uses real identity data.

Coverage includes:

- server-side template selection for teen school/institution, teen government, adult, and guardian flows;
- rejection of unsupported roles, client template manipulation, `other`, unmapped types, foreign country, missing country, and mismatched class;
- idempotent create, resume, explicit replacement, ownership, concurrency, and stale-attempt isolation;
- API timeout and error safety;
- valid, invalid, rotated-secret, duplicate, out-of-order, unknown, and cross-user webhooks;
- authoritative reconciliation on webhook, return, and manual refresh;
- learner and guardian local-submission gates;
- server-side administrator approval gates;
- authorized media proxy and denial of arbitrary URLs, non-admin access, unexpected hosts, redirects, types, and oversize bodies;
- attempt and guardian audit history;
- existing approval, rejection, resubmission, account restriction, guardian onboarding, and dependent relationship regressions;
- accurate Sandbox labels and school-ID manual-comparison language.

Database suites run serially against the isolated `cc_db_test` database. Development data is never reset, wiped, truncated, reseeded, or used as disposable test data.

## Rollout and Release Gates

### Sandbox implementation

- Add the webhook secret to local configuration without committing its value.
- Configure the Persona Sandbox webhook URL and subscribed inquiry events using the event names available in the Persona Dashboard.
- Confirm each enabled local government-ID mapping against the configured template and record the Sandbox evidence.
- Run focused and regression tests.
- Exercise every user path and administrator decision in Sandbox.
- Verify duplicate/out-of-order delivery, queue retry, expired inquiry, safe return, media authorization, and no-secret logging.

### Production remains blocked until all are complete

- legal and privacy review of Persona processing;
- explicit consent and age-appropriate/minor language review;
- guardian/parental-consent analysis where required;
- documented retention and deletion responsibilities across Conscious Connections and Persona;
- data-processing agreements, subprocessor, residency, and cross-border transfer review;
- production Persona templates, API credentials, webhook secret rotation, and event subscriptions;
- production supported-ID matrix and Philippine country-policy validation;
- incident response, access review, operational monitoring, queue capacity, and failure runbook;
- accessibility and device/browser review;
- a separate production-enablement change with approval.

The Sandbox feature must fail closed if production configuration is attempted before that separate enablement.

## Official Persona References

Contract details were checked against Persona's versioned documentation for `2026-09-29`:

- Create an inquiry: https://docs.withpersona.com/api-reference/inquiries/create-an-inquiry
- Hosted Flow: https://docs.withpersona.com/hosted-flow
- Idempotency: https://docs.withpersona.com/2026-09-29/idempotence
- Webhook best practices and signatures: https://docs.withpersona.com/2026-09-29/webhooks-best-practices
- Retrieve an inquiry: https://docs.withpersona.com/api-reference/inquiries/retrieve-an-inquiry
- Government ID verification: https://docs.withpersona.com/api-reference/verifications/government-id-verifications/retrieve-a-government-id-verification
- Selfie verification: https://docs.withpersona.com/api-reference/verifications/selfie-verifications/retrieve-a-selfie-verification
- Environments and Sandbox limitations: https://docs.withpersona.com/2026-09-29/environments
- Government ID classes: https://docs.withpersona.com/2026-09-29/government-id-verifications

## Acceptance Criteria

- Every eligible user receives only the server-selected template.
- One current attempt exists per user and target submission round; replacements preserve history.
- Local evidence cannot be submitted until the current authoritative Persona result satisfies the applicable policy.
- Persona alone never approves an application or unlocks protected access.
- Existing pending and approved cases continue unchanged.
- Teen school/institution review accurately combines a local ID with a proxied Persona selfie without making unsupported claims.
- Government routes fail closed for non-Philippine, missing, ambiguous, unsupported, or mismatched classifications.
- Webhook processing is signed, deduplicated, queued, authoritative, and stale-safe.
- Persona media is administrator-only, transient, private, bounded, and never persisted.
- Existing learner, guardian, and dependent review boundaries remain intact.
- Every Sandbox result is visibly identified as simulated.
- Production cannot be enabled without the documented release gates and a separate approved change.
