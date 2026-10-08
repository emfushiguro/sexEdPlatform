# Guardian–Dependent Existing-Learner Invitation and Messaging Design

Date: 2026-09-09

## Goal

Enhance the existing Guardian → Existing Learner invitation flow with privacy-safe guardian context, a multi-document evidence uploader, and learner-controlled direct messaging while preserving Phase 1 relationship verification as the only path to guardian privileges.

This phase reuses the current `ParentChildInvitation`, `ParentChildAccount`, guardian evidence, chat, notification, authorization, and moderation architecture. It does not create a second invitation, verification, profile, or messaging system.

## Phase Boundary

Phase 2 includes:

- richer guardian context on an existing learner's invitation;
- an invitation-scoped Guardian Information view;
- one-to-ten categorized evidence files on invitation creation;
- image and PDF previews plus remove and replace controls before submission;
- an invitation-scoped chat entry point controlled by the invited learner;
- lifecycle-aware chat authorization;
- invitation, chat, privacy, and regression tests.

Phase 2 does not include:

- Health & Support Information, which remains Phase 3;
- medical or disability information;
- public guardian profiles;
- learner access to relationship evidence;
- automatic or legal relationship determination;
- guardian discovery of unrelated learners;
- replacement of the existing chat or moderation systems.

## Existing Architecture

`ParentInvitationController` and `ParentChildInvitationService` currently implement invitation creation, acceptance, rejection, cancellation, expiration, private staged evidence, and transfer into a Phase 1 relationship evidence round. Acceptance creates or restores a `ParentChildAccount` and submits it for administrative verification. It does not activate relationship privileges.

`Conversation`, `ChatService`, `ChatAuthorizationService`, `ConversationController`, and `MessageController` implement participant conversations, messages, notifications, unread state, reporting, moderation, and real-time authorization. Existing instructor message requests are specifically modeled around a requester and an instructor and will not be generalized for this phase.

The current invitation interface has three gaps addressed here:

1. It exposes email addresses and the dependent's age on the invitation detail page even though those fields are unnecessary for reviewing the claim.
2. It exposes only one required and one optional evidence input even though the Phase 1 backend accepts a normalized evidence collection.
3. It has no invitation-authorized path for the learner to contact the requesting guardian.

## Chosen Architecture

Use an invitation-scoped conversation that reuses the existing chat tables and services.

Add `guardian_invitation` as a supported conversation type. Each such conversation references exactly one `ParentChildInvitation` and uses `guardian_invitation:{invitation_id}` as its `context_key`. The existing `pair_key` continues identifying the two participants. A unique invitation foreign key prevents duplicate conversations when requests race.

The learner initiates the conversation from the invitation. The guardian cannot independently initiate contact while the invitation or relationship is unverified; the guardian's existing optional invitation message remains the initial guardian-provided context. Once the learner opens the conversation, both parties may exchange text messages while the invitation or resulting relationship is in an allowed state.

This is preferred over an ordinary direct conversation because it records why the otherwise unverified pair may communicate. It is preferred over generalizing `MessageRequest` because that system is coupled to learner–instructor requests and broader polymorphism is not needed for the requested behavior.

## Data Model

### Parent-child invitations

Add nullable `parent_child_account_id` to `parent_child_invitations`:

- foreign key to `parent_child_accounts`;
- populated inside the existing acceptance transaction;
- remains null before acceptance and after rejection, cancellation, or expiration;
- lets later authorization resolve the exact Phase 1 relationship without relying on a pair-only lookup;
- uses `nullOnDelete` so historical invitations remain readable if a relationship row is removed.

The invitation retains its current staged evidence JSON. That JSON remains a temporary pre-acceptance transport format and is cleared only after successful transfer or after rejection, cancellation, or expiration.

### Conversations

Add nullable `parent_child_invitation_id` to `conversations`:

- foreign key to `parent_child_invitations`;
- unique when present, allowing no more than one conversation per invitation;
- null for every existing conversation;
- uses `nullOnDelete`; a conversation without its invitation becomes closed and cannot authorize new messages.

Extend `Conversation` with:

- `TYPE_GUARDIAN_INVITATION = 'guardian_invitation'`;
- `parent_child_invitation_id` fillable field;
- `parentChildInvitation()` relationship;
- `makeContextKey()` support for the invitation ID;
- inclusion in `supportedConversationTypes()`.

No evidence, identity document, email, address, birthdate, or health information is copied into a conversation.

## Guardian Information Contract

Invitation views receive an explicitly whitelisted guardian summary rather than selecting or rendering unrestricted guardian fields.

The learner may see:

- guardian avatar with an initial fallback;
- guardian full name;
- the label “Guardian identity administratively verified”;
- claimed relationship type;
- invitation date and expiration date;
- optional invitation message;
- account member-since month and year.

The learner must not see:

- guardian email address;
- phone number;
- birthdate or exact age;
- address or location details;
- government identifiers;
- identity-document images;
- relationship-evidence files or metadata;
- administrative notes or rejection details not addressed to the learner;
- the guardian's other dependents;
- health or support information.

The guardian-facing invitation view similarly avoids exposing the learner's email, birthdate, or age. It may show the learner's name, avatar, and username because those are sufficient to identify the intended account.

The identity badge refers only to the guardian account's completed identity review. The relationship is displayed as “Claimed relationship” until the exact `ParentChildAccount` is approved and active. Invitation acceptance must not change that wording to verified.

The “View Guardian Information” action opens an accessible disclosure panel or modal inside the existing invitation page. A public profile route and a separate guardian profile table are unnecessary.

## Privacy Principles

The interface and data flow apply transparency, legitimate purpose, and proportionality from the Philippine Data Privacy Act and its implementing rules:

- explain that guardian information is shown so the learner can evaluate the requester;
- show only fields necessary for that decision;
- do not expose evidence or identity records to the learner;
- do not snapshot extra personal information into invitations or conversations;
- retain existing access controls, audit behavior, and private storage;
- keep relationship verification and messaging decisions separate.

References:

- National Privacy Commission, Data Privacy Act of 2012: https://privacy.gov.ph/data-privacy-act/
- National Privacy Commission, Implementing Rules and Regulations: https://privacy.gov.ph/implementing-rules-regulations-data-privacy-act-2012/

This platform performs administrative verification of submitted identity and relationship evidence. It does not adjudicate parenthood, adoption, custody, or legal guardianship.

## Multi-Document Invitation Evidence

Replace the fixed primary/supporting fields in `SendParentChildInvitationRequest`, `ParentInvitationController`, and `resources/views/parent/invitations/index.blade.php` with the Phase 1 collection shape:

```text
documents[0][document_type]
documents[0][document_side]
documents[0][pairing_key]
documents[0][file]
```

The form supports one through ten document rows. Each row contains:

- document category filtered by the selected relationship pathway;
- file input;
- side: front, back, or not applicable;
- a pairing key when front and back belong to one document;
- preview for supported image or PDF files;
- replace and remove controls;
- required or optional guidance.

Alpine.js manages browser-local rows and previews. No new frontend dependency or temporary-upload subsystem is added. Object URLs are revoked when a file is replaced, removed, or the component is destroyed.

Client-side duplicate warnings may compare name, size, and last-modified time for immediate feedback. Server validation remains authoritative and recomputes SHA-256 through the existing staging service.

Server rules enforce:

- `documents` is an array with one to ten items;
- every item contains an accepted category and uploaded file;
- file types remain PDF, JPG, JPEG, PNG, and WebP;
- maximum size remains 5 MB per file;
- side and pairing metadata satisfy `GuardianRelationshipEvidenceRules`;
- selected categories belong to the relationship pathway;
- duplicate content hashes are rejected;
- every selectable relationship continues requiring evidence;
- the confirmation checkbox remains required.

On a validation response, non-file inputs and document-row metadata are restored. Browser security prevents repopulating file inputs, so the interface clearly asks the guardian to reselect files.

The service continues storing staged files on the private `local` disk and recording only the existing normalized metadata. Learners cannot preview or download this evidence.

## Invitation Presentation

### Guardian invitation center

The send form gains the multi-document collection. Outgoing cards and history continue using current layouts but remove unnecessary raw email display. Each card shows the learner's avatar, name, username when available, claimed relationship, status, and date.

### Learner invitation detail

The detail page shows:

- guardian avatar and name;
- identity-verification badge;
- invitation date and expiry;
- optional message;
- a clearly labeled claimed relationship;
- a short explanation that acceptance submits the claim for administrative review;
- View Guardian Information;
- Message Guardian when permitted;
- Accept and Reject while pending.

The layout remains responsive and keyboard accessible. The information disclosure uses a real button, exposes expanded state, has descriptive image alternatives, and does not rely on color alone for status.

## Messaging Lifecycle

```text
Guardian sends invitation with evidence
        ↓
Learner reviews privacy-safe guardian information
        ↓
Learner may open invitation-scoped text chat
        ↓
Learner accepts or rejects
        ↓
Acceptance creates an under-review ParentChildAccount
        ↓
Administrator approves or rejects the relationship
        ↓
Only a verified and active relationship grants guardian privileges
```

Messaging does not establish or verify the relationship.

### Allowed behavior

| State | New conversation | New text messages | Relationship privileges |
|---|---|---|---|
| Invitation pending | Learner may initiate | Both parties after learner initiation | None |
| Invitation accepted; relationship pending/under review | Learner may initiate if not started | Both parties | None |
| Relationship verified and active | Either participant may reopen the existing invitation conversation | Both parties | Per that relationship's permissions |
| Invitation rejected, cancelled, or expired | Denied | Denied; existing transcript is read-only | None |
| Relationship rejected, revoked, or inactive | Denied | Denied; existing transcript is read-only | None |
| Either participant suspended, inactive, or archived | Denied | Denied | None |

The guardian may reply only after the learner has created the invitation conversation. A verified relationship does not create a second conversation automatically.

Invitation-scoped messages are text-only in Phase 2. `SendMessageRequest` rejects attachments for `guardian_invitation` conversations, preventing chat from becoming an alternative evidence-upload channel. Existing direct, instructional, and support conversations keep their current attachment behavior.

## Authorization

`ChatAuthorizationService` remains the central policy service. Add explicit methods for invitation conversations instead of placing state checks only in controllers.

The authorization decision verifies on every conversation creation, list serialization, transcript read, send, mark-read, real-time subscription, and open-link request:

- the conversation references an invitation;
- conversation participants match the invitation guardian and learner;
- the actor is one of those participants;
- both accounts have an active platform status;
- existing suspension and moderation middleware permits access;
- the invitation is pending, or it is accepted and linked to its exact relationship;
- an accepted relationship is not finally rejected, revoked, inactive, or deleted;
- the conversation is active for sending;
- attachments are absent.

Participant membership alone is insufficient for sending or subscribing to a live invitation conversation. Authorization is recalculated at request time so a stale page cannot continue sending after cancellation, expiration, rejection, revocation, deactivation, or suspension.

The conversation list may retain a closed invitation conversation for transcript transparency, but `can_send` is false. Authorized moderators retain the existing report-review path. Unrelated administrators do not become conversation participants merely because they can review relationship evidence.

Phase 1 prerequisite: verify that existing ordinary guardian-dependent conversations also stop authorizing sends and subscriptions after relationship revocation or participant suspension. If the recorded Phase 1 follow-up test fails, fix that centralized authorization gap before enabling invitation messaging.

## Services and Controllers

### ParentChildInvitationService

Continue owning invitation creation and state transitions. Changes are limited to:

- accepting normalized one-to-ten document rows;
- setting `parent_child_account_id` during successful acceptance;
- leaving it null for non-accepted outcomes;
- exposing only the relations needed to build the whitelisted invitation view;
- closing invitation-conversation sending eligibility through state, with optional status synchronization after committed terminal transitions.

### GuardianInvitationConversationService

Add one focused service for invitation-conversation creation and state synchronization. It:

- locks and reloads the invitation;
- asks `ChatAuthorizationService` whether the actor may initiate;
- creates or retrieves the single conversation using `pair_key`, invitation context key, and unique invitation foreign key;
- records the guardian and learner as participants;
- returns the existing conversation when concurrent requests race;
- closes a stored conversation after a terminal invitation or relationship transition for accurate UI status.

Authorization remains dynamic even when the stored status has not yet been synchronized.

### ParentInvitationController

Keep the controller thin. It validates through form requests, calls the invitation or conversation service, builds whitelisted view data, and redirects the user to `chat.conversation.open` after successful conversation creation.

Add one authenticated route inside the existing parent invitation group:

```text
POST /parent/invitations/{invitation}/conversation
```

Despite the `parent` URL prefix, the current group is available to eligible authenticated learner-context accounts and the service performs party-specific authorization.

### Existing chat controllers

Reuse the current message, history, unread, report, notification, and real-time endpoints. Their shared authorization calls gain invitation-aware decisions. The generic `/chat/conversations/start` endpoint does not accept arbitrary guardian-invitation IDs; invitation conversations can start only through the scoped invitation route.

## Notifications and Moderation

Reuse current database notifications and chat events.

- The invitation notification links to the invitation detail.
- Creating an invitation conversation does not send a duplicate relationship notification.
- Normal message notifications begin only after the learner initiates the conversation.
- Terminal invitation and relationship decisions set `can_send` false immediately.
- Message reports continue entering the existing moderation workflow.
- Notification payloads do not contain evidence metadata, emails, addresses, identity details, private notes, or health information.

## Failure and Concurrency Handling

- Invitation creation remains transactional around invitation state and staged metadata; staged files are compensated on failure.
- Acceptance keeps relationship creation, evidence transfer, invitation acceptance, and relationship linkage in the existing locked transaction.
- Conversation creation locks the invitation and uses database uniqueness as the final duplicate defense.
- A unique-key race reloads the existing invitation conversation instead of returning an error.
- Every send rechecks lifecycle authorization; closing the conversation row is a UI optimization, not the security boundary.
- Expiration, rejection, cancellation, relationship rejection, revocation, and deactivation make the conversation read-only.
- Missing or deleted invitation context fails closed.
- Errors and logs include record IDs and paths only when already allowed by the existing logging policy; they do not include evidence contents or message bodies.

## Testing Strategy

### Schema and model tests

- migration columns, foreign keys, and uniqueness;
- model relationships and supported conversation type;
- context-key generation;
- existing conversations remain unchanged.

### Invitation evidence tests

- one and ten documents accepted;
- zero and eleven rejected;
- category, side, pairing, MIME, size, and duplicate validation;
- staged metadata order and hash preservation;
- removal of staged files after rejection, cancellation, and expiration;
- transactional evidence transfer on acceptance.

### Privacy and UI tests

- guardian avatar, name, identity badge, claimed relationship, date, and optional message render;
- guardian and learner emails, birthdates, ages, addresses, evidence, and identifiers do not render;
- avatar fallback works;
- information disclosure and action buttons have accessible labels and state;
- multiple upload rows expose preview, replace, and remove behavior;
- server validation errors preserve safe non-file form values.

### Messaging tests

- invited learner can initiate while pending;
- guardian cannot initiate before the learner;
- guardian may reply after learner initiation;
- unrelated accounts receive 403;
- duplicate creation returns one conversation;
- messages are text-only;
- acceptance does not approve the relationship;
- pending relationship permits invitation conversation but no guardian privileges;
- terminal invitation and relationship states prevent send and real-time subscription;
- suspended, inactive, and archived accounts cannot initiate or send;
- closed transcripts remain readable only by parties and authorized moderation paths;
- multiple guardian invitations create independent conversations.

### Regression tests

- ordinary learner–instructor message requests;
- verified guardian-dependent direct chat;
- module, lesson, topic, quiz, and support conversations;
- message notifications, unread state, reconnect backfill, reports, and moderation;
- dependent dashboard and relationship permissions;
- Phase 1 evidence and centralized admin review.

## Rollout and Compatibility

- Existing invitations with the old one- or two-file staged payload remain acceptable because the normalized stored metadata already matches the Phase 1 evidence contract.
- Existing accepted invitations may have a null `parent_child_account_id`. A migration does not guess the relationship. Runtime compatibility may resolve the unique matching pair only for historical accepted invitations, then persist the link under a lock; ambiguous matches fail closed.
- Existing conversations keep null `parent_child_invitation_id` and unchanged behavior.
- No new dependency is introduced.
- Phase 2 can be rolled back without deleting Phase 1 relationship or evidence records.

## Acceptance Criteria

Phase 2 is complete when:

- invitation creation accepts one-to-ten categorized evidence files;
- previews, replacement, and removal work before submission;
- all evidence validation is enforced server-side;
- the learner sees sufficient, privacy-safe guardian context;
- invitation pages no longer expose unnecessary email, birthdate, age, identity, or evidence data;
- the learner can initiate a text-only invitation conversation;
- the guardian cannot initiate before learner action;
- the conversation reuses existing chat, notification, unread, reporting, moderation, and real-time systems;
- invitation acceptance still produces an administratively reviewed relationship and no immediate guardian privileges;
- rejection, cancellation, expiration, relationship rejection, revocation, deactivation, or account suspension stops new messages;
- multiple guardians and invitations remain independent;
- focused Phase 2 and relevant Phase 1 regression tests pass;
- browser QA confirms responsive and accessible invitation, upload, profile, and messaging behavior.
