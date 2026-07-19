# Seminar Governance Upgrade Design

## Goal

Upgrade the existing connector-owned Seminar/Webinar system so every public seminar goes through moderation before learner discovery. The work also removes duplicated form fields, supports custom categories, improves lifecycle action placement, replaces manual speaker ID entry with instructor selection, and creates a dedicated learner discovery experience for approved educational events.

This design extends the seminar v1 foundation in `docs/superpowers/specs/2026-06-02-connector-seminars-design.md`. It does not create a parallel event domain.

## Approved Choices

- Admin approval changes a seminar to `approved`; connector managers publish it afterward.
- Only connector seminar managers can publish approved seminars.
- Rejected seminars never have registrations because they were never visible publicly.
- Custom "Others" category is stored as `category = other` plus `custom_category`.
- Speaker selection lists approved and active instructors only.
- Existing `published` seminars remain published during migration to avoid breaking live content.

## Scope

In scope:

- Remove the seminar `description` field from connector create/edit validation, payloads, and forms.
- Keep `purpose` as the single long-form explanation field.
- Add custom category support when category is `other`.
- Display the custom category on connector, admin, and learner seminar listings/details.
- Remove the connector detail "Channel Details" section.
- Group `Edit`, `Archive`, `Complete`, and `Cancel` as action buttons on the connector seminar detail page.
- Add a confirmation modal before completion.
- Replace manual platform user ID speaker assignment with a searchable instructor modal.
- Remove speaker title input from the connector speaker workflow.
- Add moderation-first statuses and review history.
- Replace direct connector publishing with `Submit for Review -> Admin Approve -> Connector Publish`.
- Add admin moderation table filters, pagination, and icon actions.
- Add dedicated admin review page with approve/reject flow.
- Add learner seminar discovery page for approved/published webinars and physical seminars.

Out of scope:

- Paid seminar access.
- Automated publish scheduling.
- Public guest access.
- Physical attendance automation beyond the existing future-ready attendance model.
- Certificate generation.
- Waitlist implementation. Registration counts and capacity display are included; waitlist remains future work.

## Data Model

Extend `seminars`:

- `custom_category`: nullable string, required only when `category = other`.
- `submitted_for_review_at`: nullable datetime.
- `submitted_for_review_by`: nullable user foreign key.
- `approved_at`: nullable datetime.
- `approved_by`: nullable user foreign key.
- `rejected_at`: nullable datetime.
- `rejected_by`: nullable user foreign key.
- `rejection_reason`: nullable string.
- `moderator_note`: nullable text.
- `published_at`: nullable datetime.
- `published_by`: nullable user foreign key.
- `archived_at`: nullable datetime.
- `archived_by`: nullable user foreign key.

Create `seminar_moderation_reviews`:

- `id`
- `seminar_id`
- `moderator_id`
- `from_status`
- `to_status`
- `reason`
- `note`
- `reviewed_at`
- timestamps

Status values become:

- `draft`
- `pending_review`
- `approved`
- `rejected`
- `published`
- `completed`
- `cancelled`
- `archived`

Existing `published` seminars stay `published`. Existing drafts stay `draft`.

## Connector Workflow

Connectors create seminars as drafts. Draft and rejected seminars are editable. A connector manager submits a seminar for review when required fields are complete. Submission changes status to `pending_review`, records submitter metadata, and locks public visibility.

Rejected seminars show the rejection reason and moderator note. Connector managers can edit and resubmit them. Rejection creates moderation history but does not create learner-facing records or registrations.

Approved seminars are not public yet. Connector managers publish approved seminars when they are ready. Publishing records `published_at` and `published_by`.

Completed, cancelled, and archived seminars are terminal for public registration and joining. Archive is a connector management action for non-active records and removes the seminar from ordinary connector lists unless archived filters are selected.

## Admin Moderation

Admins get a dedicated seminar moderation index with:

- Search by title, host connector, and speaker name.
- Status filter.
- Seminar type filter.
- Category filter.
- Pagination.
- Icon-based actions for view, approve, and reject.

The review page shows:

- Seminar information.
- Category and custom category.
- Host connector information.
- Speaker list.
- Registration settings.
- Webinar or physical event details.
- Eligible registrant settings.
- Moderation history.

Approval changes `pending_review` to `approved`.

Rejection changes `pending_review` to `rejected`, requires a predefined reason, accepts a custom moderator note, and records a moderation history entry.

Admins do not publish connector seminars in this design. Publishing remains a connector manager action after approval.

## Learner Discovery

Create a dedicated learner seminar discovery page for webinars and physical seminars. The page shows:

- Thumbnail or fallback visual.
- Title.
- Category display name, using custom category when present.
- Host connector.
- Speakers.
- Seminar type.
- Registration availability.
- Date and time.
- Capacity and active registration count.

Filters:

- Webinar.
- Physical seminar.
- Category.
- Upcoming events.

Eligibility:

- Only `published` seminars appear in normal learner discovery.
- Learners must match selected learner age categories.
- Instructors see instructor-eligible seminars.
- Cancelled, archived, pending, rejected, draft, and approved-but-unpublished seminars are hidden.

## Speaker Management

Connector detail pages show an `Add Speaker` button. It opens a modal with:

- Search input.
- Dropdown or searchable list of eligible instructors.
- Instructor avatar/profile image.
- Instructor name and email.

Only active approved instructors appear. The form posts a selected `user_id`; display name defaults to the instructor name. The speaker title field is removed from the connector workflow. Existing database `title` values can remain for backward compatibility, but the current UI no longer collects them.

## UI And UX

Connector seminar detail actions are grouped in the header/action bar:

```text
Edit | Archive | Complete | Cancel
```

Rules:

- `Edit` is primary or neutral.
- `Archive` is secondary.
- `Complete` requires a confirmation modal and is shown only for publishable/live/past seminars that are not terminal.
- `Cancel` is destructive and requires a reason.
- Destructive actions use existing warning/destructive button styles.

The completion modal includes:

- Seminar title.
- Warning that completion finalizes attendance and closes registration/joining.
- Confirm button.
- Cancel button.

The "Channel Details" section is removed from connector seminar details. Livestream channel values remain server-side operational data.

## Backend Boundaries

Connector seminar management continues to use `connector.manage_seminars` and `SeminarAccessService`.

Server-side services remain the source of truth for:

- Connector ownership.
- Status transitions.
- Submit/approve/reject/publish rules.
- Learner eligibility.
- Registration visibility.
- Speaker eligibility.

The UI only reflects allowed actions; it does not decide authorization.

## Testing Strategy

Feature tests should cover:

- Description is no longer accepted as a required seminar form field.
- `category = other` requires `custom_category`.
- Custom category displays on connector/admin/learner pages.
- Draft submission creates `pending_review`.
- Direct connector publishing from draft is blocked.
- Admin approval creates `approved` and moderation history.
- Connector publishing from approved creates `published`.
- Admin rejection creates `rejected`, reason, note, and history.
- Rejected seminars can be edited and resubmitted.
- Learner discovery hides draft, pending, approved, rejected, cancelled, and archived seminars.
- Learner discovery shows only eligible published seminars.
- Completion requires confirmation in the UI and finalizes via the existing controller action.
- Speaker modal endpoint returns only active approved instructors.
- Speaker assignment accepts selected instructor IDs and rejects ineligible users.

Unit tests should cover:

- Seminar status transition service.
- Custom category display helper.
- Speaker eligibility query.
- Discovery eligibility query.

## Acceptance Criteria

- Connector seminar creation no longer shows or stores a form description.
- Purpose remains available and required according to current seminar rules.
- Selecting `Others` shows and validates a custom category field.
- Seminar lists and details display custom categories correctly.
- Connector detail no longer shows Channel Details.
- Lifecycle actions are grouped as buttons with clear destructive styling.
- Complete Seminar opens a confirmation modal before posting.
- Add Speaker uses a searchable instructor modal with avatars and no speaker title field.
- New seminars cannot become public without admin approval.
- Admins can approve or reject seminars from a dedicated moderation workflow.
- Moderation history is preserved.
- Learners only discover published seminars that satisfy governance and eligibility rules.
