# Support Workflow Refinement Design

## Status

Approved in chat on 2026-09-14. This specification refines the existing Help
Center, private Platform Feedback ticket flow, and consent-controlled
Testimonials. It does not merge those features with safety reports, Community
Hub moderation, module reviews, instructor reviews, content reports, or chat.

## Objective

Deliver a cleaner and more reliable support experience for learners,
instructors, administrators, connectors, and public visitors by:

- simplifying ticket submission;
- replacing ambiguous ticket statuses with a clear lifecycle;
- adding an authorized two-way ticket conversation;
- making Help Center images render without depending on a filesystem symlink;
- reusing the platform's established toast and confirmation patterns;
- deriving testimonial public names safely from existing profiles; and
- presenting published testimonials appropriately on the landing page.

## Approved Approach

Use a coordinated workflow refinement rather than a UI-only patch or a support
portal rewrite. Existing controllers, policies, layouts, route contexts,
storage services, and components remain the foundation. New data is introduced
only where the requested behavior cannot be represented safely by the current
model, principally the ticket conversation.

Implementation will occur in the existing checkout without creating a Git
worktree. The checkout is currently on `community-feed-v1`, not the Git branch
named `main`. Existing unrelated dirty changes must be preserved, so no branch
switch will be attempted automatically. The instruction to execute "to main"
is interpreted as using this primary checkout unless the user explicitly asks
for a Git branch change after safeguarding the current work. No broad
refactoring, cleanup, commit, or push is part of this design.

## Domain Boundaries

### Help Center

Help Center articles are navigation and usage guidance. Their screenshots may
be visible publicly or to a role-restricted audience according to the parent
article. They are not private ticket attachments.

### Platform Feedback tickets

Platform Feedback is a private support-ticket workflow between the submitter
and authorized platform administrators. It remains separate from urgent safety
reporting and moderation workflows. Ticket descriptions, attachments, status
history, and messages must never appear on the public website.

### Testimonials

Testimonials are independent, explicitly consented public quotations from
eligible adult learners and instructors. They are not generated from support
tickets. Publication requires active consent and admin approval. The author
may revoke publication consent at any time.

## Shared UI and Notification Contract

- Continue using the existing role layouts and purple platform visual system.
- Use `window.toast.success`, `window.toast.error`, and related existing toast
  helpers for redirect flash messages.
- Ordinary Save actions do not require a confirmation dialog.
- Destructive or public-visibility actions continue to use the existing
  `data-confirm-submit` / `window.ccSweetAlert` convention.
- Remove page-local success banners when the containing layout already emits
  the same success flash as a toast, preventing duplicate feedback.
- Preserve visible field labels, keyboard focus, error associations, minimum
  touch targets, and screen-reader text for icon-only controls.
- Loading states disable the submitted control and prevent duplicate requests.

## Feedback Submission

### Category dropdown

Replace the six radio cards with one labeled select control. Retain the current
enum values and public labels:

1. General Inquiry
2. Report a Problem
3. Suggestion
4. Technical Issue
5. Content Concern
6. Account or Payment Issue

Query-string prefilling remains supported only for recognized enum values.

### Text limits

- Subject remains required with a 180-character maximum.
- Initial ticket description becomes required with a 500-character maximum.
- The client counter displays `current/500` and the server enforces the same
  limit.
- Ticket conversation messages use a separate 1,000-character maximum.
- Testimonial quotations retain their existing 1,000-character maximum.

### Heart rating

The experience rating remains optional and stores the existing integer value
from 1 through 5. The form displays five heart controls. Selecting a value
fills every heart up to that value. Each radio remains keyboard reachable and
has a spoken label such as `3 out of 5 hearts`. No rating is selected by
default.

### Attachment

Retain the existing private optional JPG, PNG, or WebP attachment with its
5 MB maximum, selected filename, removable preview, decoded-image validation,
and duplicate-submission protection.

## Ticket Lifecycle

### Canonical statuses

The canonical persisted statuses become:

- `new` — submitted and not yet opened by an administrator;
- `reviewed` — displayed to users as **In Review**;
- `resolved` — a resolution was supplied, but the author may reply and reopen;
- `closed` — terminal; no new conversation messages or withdrawal;
- `withdrawn` — terminal; withdrawn by the author before review.

`planned` and `archived` are removed from the enum and application controls.
A forward migration maps existing `planned` rows and history values to
`reviewed`, and maps existing `archived` rows and history values to `closed`.

### Allowed transitions

- New -> In Review, Resolved through a recorded intermediate review, Closed,
  or Withdrawn.
- In Review -> Resolved or Closed.
- Resolved -> In Review when the owner replies, or Closed by an administrator.
- Closed and Withdrawn are terminal.

The lifecycle service remains the only status-transition authority. Each real
transition creates one status-history entry. The first review records
`reviewed_by` and `reviewed_at`; entering Resolved records `resolved_at`;
reopening clears `resolved_at`.

### Admin eye action

Opening a ticket from the admin inbox is a state-changing action only when the
ticket is New. The eye is therefore a CSRF-protected POST form targeting a
dedicated `admin.feedback.open` endpoint. That endpoint transitions New to In
Review, records the history, and redirects to the review screen. For tickets
already beyond New, the same endpoint only redirects and does not create a
duplicate history record.

Direct GET access to the review page remains read-only and does not mutate
state.

### Owner withdrawal

The author may withdraw a ticket only while its status is New. The Withdraw
button is absent for In Review, Resolved, Closed, and Withdrawn records. The
controller and policy enforce the same rule so a crafted request cannot bypass
the UI.

### Personal history filters

My Tickets provides four views:

- All — every ticket owned by the authenticated user;
- Active — New and In Review;
- Resolved — Resolved only;
- Closed — Closed and Withdrawn.

The same filter meanings apply within learner, instructor, and connector route
contexts. Empty states use the selected view's plain-language label.

Connector ticket routes remain a navigation and shell context, matching the
current data model. A ticket is owned by a user and is not scoped to or
persisted against a connector. Entering My Tickets from a connector therefore
shows that user's same ticket collection after workspace-membership
verification. Message links preserve the current route context when possible;
database notifications may use the canonical non-connector ticket URL.

## Ticket Conversation

### Data model

Add a `platform_feedback_messages` table containing:

- primary key;
- `platform_feedback_id`, cascading on ticket deletion;
- nullable `sender_id`, preserving an admin message if that admin is later
  deleted;
- a `sender_role` snapshot derived by the server from the authenticated user
  and limited to `learner`, `parent`, `instructor`, `connector`, or `admin`;
- message `body` with a 1,000-character server limit; and
- timestamps plus an index on ticket and creation order.

Read receipts and reply attachments are intentionally out of scope. Database
notifications are sufficient for this iteration.

### Legacy response migration

For each existing ticket with a non-empty `staff_response`, create one
admin-authored conversation message. Use `reviewed_by` as sender where
available and use `reviewed_at`, falling back to the ticket's `updated_at`, as
the message timestamp. A missing legacy sender displays as **Platform team**.
Keep the original `staff_response` column for non-destructive compatibility,
but stop using it for new writes after migration. Refined views render only
the migrated message and never render the legacy column separately.

The existing owner foreign key still cascades ticket deletion, so deleting the
ticket owner deletes the ticket and its messages. Nullable `sender_id` only
preserves messages when a non-owner sender, such as an administrator, is later
deleted.

### Message authorization

- Only the ticket owner and authorized platform administrators may read its
  messages.
- Administrators may reply to New, In Review, or Resolved tickets. Creating a
  first admin reply to New must first move it to In Review.
- The owner may reply only after at least one admin-authored message exists.
- An owner reply to Resolved atomically transitions the ticket back to In
  Review and adds the message.
- A ticket may enter Resolved only after it has at least one admin-authored
  conversation message. A crafted status request cannot bypass this invariant.
- Closed and Withdrawn tickets reject all new messages server-side.
- Ticket submission grants in-app ticket-response access. The separate
  `may_contact` flag continues to govern optional contact outside the ticket.

### Notifications

An admin message creates one private database notification for the owner. An
owner reply notifies the ticket's active admin reviewer when one exists;
otherwise it uses the existing support-admin resolver to select one active
authorized admin. Compound operations do not duplicate notifications: the
first admin reply to New produces one owner notification, and an owner reply
that reopens Resolved produces one admin notification. Status-only changes
continue using the existing ticket update notification.

### Conversation UI

Display messages chronologically with clear sender identity, role, timestamp,
and visually distinct owner/admin bubbles. Keep the immutable original ticket
above the conversation. Show a reply composer only when the current user is
allowed to reply. The composer includes a live `current/1,000` counter,
validation errors, a submitting state, and a success toast.

## Admin Ticket Review UX

The review page uses a responsive two-column layout:

- Main column: sender profile, ticket metadata, heart rating, description,
  private attachment preview, conversation, and concise status history.
- Action column: current status, allowed next status, response composer, and
  Save/Send controls.

Remove Planned from progress and controls. Present Resolved and Closed as
distinct outcomes: Resolved allows the owner to respond; Closed is final.

Remove the Private Internal Note field, its validation, model mass-assignment
entry, new persistence, and its display in history. Preserve historical
`internal_note` database columns and values silently to avoid destructive data
loss. Status history shows only the transition, actor, and timestamp.

The owner-only Withdraw endpoint rejects administrators as well as owners of
non-New tickets. Administrators use the normal Closed status transition rather
than the owner withdrawal operation.

Saving status changes and sending messages use the existing success toast.
Destructive Close actions use the shared confirmation behavior.

## Help Article Editing and Media

### Modern section image picker

Each section image control reuses the Submit a Ticket file-picker treatment:

- styled native file button;
- accepted JPG, PNG, and WebP formats;
- 5 MB guidance;
- selected filename;
- image thumbnail preview;
- Remove action; and
- preservation or explicit removal of an existing image.

Repeated sections maintain independent picker state. Replacing an image
removes the obsolete file only after the database transaction succeeds.

### Alternative text

Remove the manual Screenshot Alternative Text input. Whenever a section that
contains an image is created or saved, the server deterministically overwrites
its alternative text with `Screenshot for {section heading}` when a heading
exists, or `Screenshot for {article title}` otherwise. Existing records remain
unchanged until their section is next saved. Public and admin previews continue
rendering the resulting non-empty `alt` attribute.

### Reliable image delivery

The current runtime cannot depend on `/storage/...` because `public/storage`
may not be a valid link to `storage/app/public`. Add a Laravel endpoint for a
section image, resolved from the persisted database path.

The endpoint must:

- verify that the section belongs to the article in the route;
- allow an administrator to preview Draft, Published, or Archived articles;
- otherwise apply the same active-category, publication, role/audience, and
  connector visibility rules as the parent Help Center article;
- read only the stored path from the configured disk;
- return 404 for a missing record or missing file;
- stream the file inline with its detected MIME type;
- use public cache headers only for truly guest-visible articles, and
  `private, no-store` for role-restricted content and admin Draft/Archived
  previews; and
- never accept an arbitrary client-supplied filesystem path.

Public article pages, admin preview, and admin edit previews use this endpoint.
Private Platform Feedback attachments keep their separate authorized route.

### Save feedback

Help Article create/update continues redirecting with a success flash. The
admin layout displays `Help article updated.` through the established top-right
toast. Validation errors remain inline and in the existing error toast flow.

## Testimonial Submission and Identity

### Public display name

Remove the editable Public Display Name field from the owner submission form.
Resolve and snapshot the public identity at submission:

- adult learner: `learnerProfile.username`;
- instructor: existing account display/full name, falling back to `users.name`.

Display the resolved name as read-only before the consent checkbox so the
author knows what will become public. If no safe non-empty value is available,
reject submission with a plain-language profile-completion message.

The snapshot remains stable if the source profile changes later. Admin may
edit quotation, ordering, and allowed presentation settings, but the identity
snapshot is read-only so publication cannot silently change what the author
consented to.

### Consent and eligibility

Keep explicit publication consent, optional role visibility, optional profile
image visibility, adult eligibility, active-account checks, and independent
admin publication approval. Minor users remain unable to submit or publish a
testimonial.

For legacy compatibility, an author status of `null` or `active` is eligible.
Authors marked `inactive`, `suspended`, or `archived` cannot submit, publish, or
remain visible publicly. Admin update requests reject or ignore any attempted
mass assignment of the snapshotted `display_name`.

### Owner withdrawal

The author may withdraw active consent while the testimonial is Draft or
Published. Published withdrawal must immediately clear public visibility and
publication timestamps in the same transaction. The operation uses the shared
confirmation and success-toast patterns. Rejected and Withdrawn records do not
show the action.

An owner-withdrawn record cannot be republished. A new public submission
requires a new record and new explicit consent.

## Landing-Page Testimonials

Keep the section directly before the footer and rename its heading to
**What our community says**, since both learners and instructors may appear.

Render one through six eligible published records in a responsive editorial
grid:

- one column on small screens, two on medium screens, and up to three on large
  screens;
- one or two cards centered rather than stretched awkwardly;
- restrained quote icon and quotation-first hierarchy;
- consented profile image or initials fallback;
- snapshotted public name and optional role; and
- no autoplay carousel or placeholder testimonials.

The landing query remains limited to actively consented, published,
adult-eligible testimonials from active users. If no records qualify, render no
testimonial section. If a configured profile image is missing or fails to
load, the component reveals the initials fallback instead of a broken image.

## Security, Privacy, and Accessibility

- Keep tickets, ticket messages, and ticket attachments owner/admin only.
- Preserve connector membership and route-context checks.
- Keep all ticket content escaped as plain text.
- Validate authorization again on every message, transition, withdrawal, and
  media request.
- Keep CSRF protection and throttling on mutating endpoints.
- Keep upload extension, MIME, decoded-image, and size validation.
- Do not expose internal note history after the UI removal.
- Do not allow a public testimonial without current adult eligibility and
  active explicit consent.
- Hearts, icon actions, upload controls, dialogs, filters, and conversation
  controls require visible focus states and accessible names.
- Generated Help image alternative text remains non-empty.

## Error and Empty States

- Reject over-limit ticket descriptions and messages without losing entered
  text.
- A failed message write must not change ticket status or send a notification.
- A failed status transition must not create a history entry.
- Missing Help images return a normal 404 and do not expose storage paths.
- Missing profile images display initials.
- Empty ticket filters and empty testimonial results use contextual copy and
  preserve a clear next action.

## Testing Strategy

### Unit tests

- Canonical feedback enum values and labels.
- Allowed lifecycle transitions and terminal states.
- Testimonial public-name resolution for learner and instructor profiles.
- Generated Help image alternative text.

### Feature tests

- Six-option feedback dropdown and 500-character description boundary.
- Optional 1–5 heart-rating semantics and persistence.
- All, Active, Resolved, and Closed owner filters.
- New-only withdrawal in both UI and controller.
- Admin requests to the owner-only withdrawal endpoint are rejected.
- CSRF-protected admin eye transition with no duplicate history.
- Planned/Archived data migration and canonical status rendering.
- Admin/owner ticket messages, authorization, 1,000-character validation,
  notification delivery, resolved reopening, and closed rejection.
- Resolved requires an existing admin message, including for crafted requests.
- Compound message/status operations create exactly one recipient notification.
- Connector routes remain navigation-only and never leak another user's ticket.
- Internal-note UI and request removal without destructive historical deletion.
- Help Article picker preview/replacement/removal and generated alt text.
- Authorized Help image response for guest, learner, instructor, connector, and
  admin Draft preview; forbidden or missing images return 404.
- Public Help images permit public caching while restricted or Draft images use
  `private, no-store`.
- Existing custom image alt text remains until that section is saved, after
  which the deterministic generated text replaces it.
- Article and admin ticket redirect flashes render through the shared toast.
- Testimonial name snapshot, admin read-only identity, publication eligibility,
  and owner withdrawal after publication.
- Suspended, inactive, and archived testimonial authors cannot publish or
  appear publicly, and crafted admin updates cannot change the name snapshot.
- Landing cards include only eligible records and handle one through six cards
  plus missing avatar fallbacks.

### End-to-end role matrix

- Learner: submit, validate, filter, withdraw while New, receive admin reply,
  reply, reopen Resolved, submit testimonial, and revoke publication consent.
- Instructor: the same ticket flow, instructor-name fallback, testimonial
  submission, and withdrawal.
- Admin: eye auto-review, sender review, attachment access, conversation,
  status transitions, closure, Help article media editing, and toast feedback.
- Connector member: context-preserving ticket routes, visibility, messages,
  and Help image access.
- Guest: published Help article image access and eligible landing testimonials
  only.

### Verification commands

Run focused red/green tests throughout implementation, then the complete
Support feature/unit/QA groups, Blade compilation, route serialization/cache,
frontend production build, formatting/lint checks applicable to touched files,
and `git diff --check`. Perform browser verification at desktop and mobile
widths for learner, instructor, admin, connector, and guest views.

## Delivery Phases

1. Add failing tests for canonical statuses, filters, 500-character input,
   heart semantics, messages, media delivery, testimonial identity, and public
   cards.
2. Normalize ticket statuses and introduce the conversation schema/services.
3. Refine feedback submission, personal history, ticket detail, and admin
   review.
4. Implement Help Article media delivery, generated alt text, and modern file
   picker.
5. Implement testimonial identity, withdrawal reliability, and landing-page
   presentation.
6. Run the complete role/security regression matrix and browser QA, addressing
   only failures caused by or materially blocking this feature.

## Acceptance Criteria

- Feedback category is a six-option dropdown.
- Initial description rejects the 501st character and preserves valid input.
- Rating is an optional, accessible 1–5 heart selector.
- Planned and Archived no longer appear or persist after migration.
- Admin eye opening changes a New ticket to In Review exactly once.
- In Review tickets cannot be withdrawn.
- Owner and admin can converse according to the approved status rules.
- Resolved cannot be selected until an admin response exists.
- Resolved appears as its own My Tickets filter.
- Private Internal Note is absent from the active product.
- Article and ticket saves generate the established top-right success toast.
- Help Article images render without a public-storage symlink.
- Manual screenshot alternative-text input is gone while rendered images retain
  generated accessible text.
- Help Article uploads match the existing ticket-picker experience.
- Testimonial names are resolved automatically and displayed read-only before
  consent.
- Published testimonials remain immediately withdrawable by their owner.
- The landing testimonial section is responsive, eligibility-safe, and located
  immediately before the footer.
- Learner, instructor, admin, connector, guest, authorization, privacy, and
  migration tests pass for the affected flows.
