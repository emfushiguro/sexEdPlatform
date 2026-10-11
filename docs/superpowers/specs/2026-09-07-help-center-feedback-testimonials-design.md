# Help Center, Platform Feedback, and Testimonials Design

## Status

Approved section-phase design for implementation on the existing
`community-feed-v1` branch.

This document defines three connected but independently authorized features:

1. A public, role-aware Help Center maintained by platform administrators.
2. A private Platform Feedback workflow for authenticated users and admins.
3. An adult-only, explicitly consented testimonial publication workflow.

Implementation remains local to `community-feed-v1`. Do not create a worktree,
switch branches, merge, pull, commit, or push unless the user separately requests
that operation.

## Product Direction

Build a native Laravel support area that helps people navigate Conscious
Connections, provides the platform team with structured product feedback, and
allows a small set of adult feedback submissions to become public testimonials
after explicit consent and administrator review.

The result must feel like part of the existing sexual-health learning platform,
not a separate generic support product. Help content should be warm and easy to
scan. Feedback administration should be operational and privacy-conscious.

The implementation uses existing Laravel, Blade, Tailwind, Alpine, database,
notification, authorization, and storage patterns. It does not introduce a
third-party help desk, external search service, AI classification, or a general
content-management framework.

## Chosen Approach

Use database-backed Laravel modules delivered in phases:

1. Help Center data and public experience.
2. Help Center administration.
3. Platform Feedback submission and user history.
4. Feedback administration and basic insights.
5. Consent-controlled testimonials and landing-page presentation.
6. Cross-role navigation, accessibility, and regression verification.

This approach is preferred over static help pages and email-only feedback
because administrators can maintain content, users receive a clear record of
their submissions, and the platform can produce useful aggregate insights. It
is preferred over a full customer-support system because staff assignment,
service-level agreements, and conversation threads are not required.

## Domain Boundaries

### Help Center

Publishes navigation and troubleshooting guidance for guests and authenticated
roles. It owns help categories, articles, article sections, public search, and
helpfulness votes.

### Platform Feedback

Collects private feedback about the application itself. It owns feedback types,
attachments, lifecycle status, an internal note, one optional staff response,
and user submission history.

### Testimonials

Publishes a reviewed excerpt from an eligible feedback submission. It owns the
public display name, approved quotation, display preferences, ordering, and
publication lifecycle. It cannot exist without a source feedback submission.

### Existing features that remain separate

- Module reviews and ratings.
- Instructor reviews and ratings.
- Community Hub post and comment reports.
- Content reports and moderation cases.
- Chat and message reports.
- Direct support conversations.

No data is migrated from those features. Platform Feedback must not create or
resolve moderation cases.

## Roles and Access

| Surface | Access contract |
| --- | --- |
| Help Center index and published guides | Public |
| Role-specific guide recommendations | Derived from the authenticated role |
| Article helpfulness vote | Authenticated users |
| Feedback form and personal history | Authenticated users |
| Feedback detail and attachment | Owning user or platform admin |
| Help administration | Platform admin only |
| Feedback inbox and insights | Platform admin only |
| Testimonial administration | Platform admin only |
| Published testimonials | Public |

Connector membership or connector-local moderation permissions do not grant
access to platform feedback. Instructors and connectors cannot read submissions
from their learners or members.

## Route Contract

Use explicit route names so navigation and tests do not depend on raw paths.

### Public Help Center

- `GET /help` -> `help.index`
- `GET /help/{helpArticle:slug}` -> `help.show`

Only published articles are publicly route-bindable. Admin preview uses a
separate admin route and must not relax public binding.

### Authenticated Help and Feedback

- `PUT /help/{helpArticle}/helpfulness` -> `help.helpfulness.update`
- `GET /feedback` -> `feedback.create`
- `POST /feedback` -> `feedback.store`
- `GET /feedback/submissions` -> `feedback.index`
- `GET /feedback/submissions/{platformFeedback}` -> `feedback.show`
- `DELETE /feedback/submissions/{platformFeedback}/testimonial-consent` ->
  `feedback.testimonial-consent.destroy`
- `GET /feedback/submissions/{platformFeedback}/attachment` ->
  `feedback.attachment.show`

The specific authenticated shell may expose a contextual link, but there is one
canonical feedback resource and one authorization policy.

### Admin Help Center

- Resource-style category routes under `admin/help/categories`.
- Resource-style article routes under `admin/help/articles`.
- Explicit article preview, publish, and archive actions.
- Explicit category ordering and article ordering actions.

Route names use the `admin.help.categories.*` and `admin.help.articles.*`
families.

### Admin Feedback and Testimonials

- Feedback inbox, show, status-update, and response actions under
  `admin/feedback` using `admin.feedback.*` route names.
- Authorized feedback-attachment delivery through an admin feedback route.
- Testimonial index, store-from-feedback, update, publish, unpublish, and order
  actions under `admin/testimonials` using `admin.testimonials.*` route names.

## Help Center Information Architecture

Seed these initial categories in the following order:

1. Getting Started
2. Account and Profile
3. Learning and Modules
4. Quizzes and Certificates
5. Seminars
6. Community Hub
7. Parent and Guardian Support
8. Instructor Tools
9. Connector Tools
10. Payments and Subscriptions
11. Privacy and Safety
12. Accessibility
13. Troubleshooting

Categories may be inactive without being deleted. Articles support these
audiences:

- `all`
- `guest`
- `learner`
- `parent`
- `instructor`
- `connector`
- `admin`

An article may target multiple audiences. `all` makes it available to every
audience and should not be combined with narrower values.

## Help Center Data Model

### `help_categories`

- `id`
- `name`
- unique `slug`
- nullable `description`
- nullable validated `icon_key`
- JSON `audiences`
- integer `sort_order`
- boolean `is_active`
- timestamps

### `help_articles`

- `id`
- `help_category_id`
- `created_by`
- nullable `updated_by`
- `title`
- unique `slug`
- `summary`
- nullable JSON `keywords`
- JSON `audiences`
- enum-backed `status`: `draft`, `published`, or `archived`
- integer `sort_order`
- nullable `published_at`
- timestamps

### `help_article_sections`

- `id`
- `help_article_id`
- nullable `heading`
- long text `body`
- nullable `image_path`
- nullable `image_alt_text`
- integer `sort_order`
- timestamps

An image requires non-empty alternative text. Article section bodies are stored
as plain text and rendered escaped with preserved line breaks. Raw user-provided
or admin-provided HTML is not rendered.

### `help_article_votes`

- `id`
- `help_article_id`
- `user_id`
- boolean `is_helpful`
- timestamps
- unique constraint on `help_article_id` and `user_id`

Updating a vote replaces the user's previous choice instead of inserting a
duplicate.

## Help Center Public Experience

### Index

The Help Center index contains:

- A concise `How can we help?` heading.
- A prominent search field.
- Active categories the viewer may access.
- Recommended articles for the current role.
- Popular or manually ordered general guides.
- A troubleshooting area.
- A `Send Feedback` call to action.
- A concise safety note directing abusive-content and safeguarding concerns to
  existing reporting tools.

Search matches published, audience-appropriate articles by title, summary,
keywords, and section text. It uses database queries and pagination. Search
terms are trimmed, length-limited, and escaped. Empty results offer category
links and the feedback action.

### Article

An article contains:

- Breadcrumbs.
- Title and summary.
- Ordered instructional sections.
- Optional screenshots with meaningful alternative text.
- Automatic related articles from the same category.
- An authenticated `Was this helpful?` control.
- A `Still need help? Send feedback` action.

Guests can read articles but are invited to sign in before voting or submitting
feedback.

### Role shells

The core Help Center content is shared. Thin wrapper views place that content in
the existing guest, learner, instructor, connector, or admin shell. This avoids
duplicating business logic while preserving role-specific navigation and visual
language. Connector wrappers keep the currently selected connector context only
for navigation; Help Center authorization remains platform-wide.

## Help Center Administration

Add a `SUPPORT` group to the admin navigation containing:

- Help Articles
- User Feedback
- Testimonials

The category workspace supports listing, creating, editing, ordering, activating,
and deactivating categories. Deactivating a category hides its articles from
public discovery without deleting their data.

The article workspace supports:

- Filtering by category, audience, and status.
- Creating and editing metadata.
- Adding, removing, and ordering instructional sections.
- Uploading one optional screenshot per section.
- Previewing drafts.
- Publishing drafts.
- Archiving published or draft articles.
- Seeing helpful and not-helpful totals.

Help screenshots are admin-authored public content. Store validated JPEG, PNG,
or WebP files in a Help Center area on the public disk and remove superseded
files only after the corresponding database update succeeds. Feedback
attachments follow a different private-storage contract.

## Platform Feedback Types and Statuses

### Types

- `general`
- `bug_report`
- `feature_suggestion`
- `accessibility_issue`
- `help_content_issue`

Safety reports do not appear as a feedback type. The form explains where to
report abuse, harassment, dangerous content, or safeguarding concerns using the
platform's existing report paths.

### Statuses

- `new`
- `reviewed`
- `planned`
- `resolved`
- `archived`

User-facing views convert these values into plain labels and brief explanations.
They do not expose raw enum names.

## Platform Feedback Data Model

### `platform_feedback`

- `id`
- unique public `reference_number`
- `user_id`
- `user_role` snapshot
- enum-backed `type`
- `subject`
- long text `description`
- nullable integer `rating` from 1 through 5
- nullable relative `affected_path`
- nullable length-limited `user_agent`
- boolean `may_contact`
- nullable private `attachment_path`
- enum-backed `status`
- nullable `internal_note`
- nullable `staff_response`
- nullable `reviewed_by`
- nullable `reviewed_at`
- nullable `resolved_at`
- boolean `testimonial_consent`
- nullable `testimonial_display_name`
- boolean `testimonial_show_role`
- boolean `testimonial_show_profile_image`
- nullable `testimonial_consented_at`
- nullable `testimonial_consent_withdrawn_at`
- timestamps

The server derives `user_id`, `user_role`, user-agent metadata, and adult
eligibility. It never trusts hidden form fields for these values. The affected
page is stored only as a validated relative application path; external URLs are
rejected.

## Feedback Submission Experience

The authenticated form contains:

- Feedback type.
- Subject.
- Detailed description.
- Optional 1-5 experience rating.
- Optional affected application page.
- Optional screenshot.
- Permission for staff to contact the user.
- Adult-only testimonial consent and display preferences.
- A warning not to include passwords, medical records, or unnecessary private
  health information.

The screenshot is optional and limited to one JPEG, PNG, or WebP image of at
most 5 MB. It is stored on a private disk and never exposed through a direct
public storage URL.

Successful submission shows a stable reference number and links to `My
Feedback`. Failed database writes remove any newly stored attachment. Failed
file storage does not create a feedback record.

## User Feedback History

`My Feedback` lists the authenticated user's submissions with:

- Reference number.
- Type.
- Subject.
- Submitted date.
- Plain-language status.
- Whether a staff response is available.
- Testimonial consent state.

The detail view shows the immutable original submission, authorized attachment,
current status, and one optional staff response. Users cannot edit the original
submission and cannot add a reply thread.

Users may withdraw testimonial consent. Withdrawal records a timestamp and
immediately withdraws any linked public testimonial in the same transaction.

## Feedback Administration

The feedback inbox includes:

- Status tabs and stable counts.
- Type, rating, and date filters.
- Search by reference number, subject, or submitter.
- Paginated results.
- A detail screen with authorized private attachment access.
- Status updates.
- One private internal note.
- One user-visible staff response.
- Testimonial eligibility and publication actions.

The original subject, description, rating, and attachment are immutable in the
admin interface. Internal notes and user-visible responses are visibly distinct.

An admin update that creates or changes a staff response, or meaningfully changes
the user-facing status, sends one database notification to the submitter. Saving
an unchanged value does not create a duplicate notification.

## Feedback Insights

The admin workspace calculates:

- Total feedback.
- New feedback.
- Unresolved feedback.
- Resolved feedback.
- Average optional rating.
- Count by type.
- Count by status.
- Monthly submission trend.
- Monthly average rating.
- Frequently referenced Help Center articles or application paths.

Use database aggregate queries. Do not add third-party analytics, AI sentiment
analysis, user tracking, or scheduled data exports.

## Testimonial Eligibility and Consent

A feedback submission is eligible for testimonial review only when all of these
conditions are true:

1. The server-side age/audience resolver identifies the user as an adult.
2. The user explicitly opted in.
3. Consent has not been withdrawn.
4. The user supplied a public display name.
5. A platform administrator reviewed the source feedback.

Minor users may submit private product feedback, bugs, accessibility issues, and
Help Center corrections. The server ignores or rejects testimonial consent from
a minor even if a crafted request includes the consent fields. No guardian
consent pathway is added in this version.

Role and profile-image display require separate affirmative choices. Not
selecting either option does not prevent publication with the approved display
name.

## Testimonial Data Model

### `testimonials`

- `id`
- unique `platform_feedback_id`
- `user_id`
- `approved_by`
- `display_name`
- `display_role`
- long text `quotation`
- boolean `show_profile_image`
- enum-backed `status`: `draft`, `published`, or `withdrawn`
- integer `sort_order`
- nullable `published_at`
- nullable `withdrawn_at`
- timestamps

`display_role` is nullable and may only be populated when the user opted to show
their role. The public profile image is resolved only while permission remains
valid. Missing, removed, or inaccessible profile images fall back to an
initial-based avatar.

An administrator may shorten a quotation for presentation but must not alter its
meaning. The private source feedback is never changed. Publishing re-checks age,
consent, and withdrawal state on the server.

## Public Testimonial Presentation

Add a `What Our Community Says` section to the public landing page. Display
between three and six administrator-selected published testimonials ordered by
`sort_order`, then publication date.

Each card may show:

- Approved display name.
- General role when permitted.
- Profile image when permitted and available.
- Approved quotation.
- Original optional rating.

Use a responsive grid, not an auto-playing carousel. Do not create a separate
public testimonial directory in this version.

Withdrawal of consent, loss of eligibility, or an admin withdrawal excludes the
testimonial from the public query immediately.

## Backend Components

Use focused controllers rather than one support controller:

- Public `HelpCenterController`.
- Authenticated `HelpArticleHelpfulnessController`.
- Authenticated `PlatformFeedbackController`.
- Authorized `PlatformFeedbackAttachmentController`.
- Admin `HelpCategoryController`.
- Admin `HelpArticleController`.
- Admin `PlatformFeedbackController`.
- Admin `TestimonialController`.

Form Request objects own input validation. Policies and route middleware own
authorization. Enum classes own types and states.

Use a focused submission service to coordinate feedback persistence and private
attachment storage. Use a testimonial publication service to centralize age,
consent, publication, and withdrawal checks. Help Center search and admin
aggregates remain query scopes or focused query methods; do not add repositories
or abstract query layers.

## Data Flow

### Read a guide

1. Visitor opens the Help Center or searches.
2. The controller resolves guest or authenticated audience.
3. The query returns only active-category, published, audience-appropriate
   articles.
4. The selected role wrapper renders shared Help Center content.

### Submit feedback

1. Authenticated user submits validated form data.
2. The server derives identity, role, age eligibility, and limited user agent.
3. An optional image is validated and written to private storage.
4. Feedback is created in a database transaction with status `new`.
5. On failure, newly stored files are removed.
6. The user receives a confirmation and reference number.

### Review feedback

1. Admin opens the private inbox and selects a submission.
2. Admin records status, internal note, and optional staff response.
3. User-visible changes trigger a database notification.
4. Eligible, consented adult feedback may be prepared as a testimonial draft.

### Publish or withdraw a testimonial

1. Admin prepares an accurate excerpt and display choices allowed by consent.
2. The service re-checks adulthood and active consent.
3. Admin publishes and orders the testimonial.
4. Landing-page queries return only published, still-eligible records.
5. User consent withdrawal atomically marks the testimonial withdrawn.

## Security and Privacy Requirements

- Authenticate all feedback write and history routes.
- Authorize feedback detail and attachments by owner or platform admin.
- Keep feedback screenshots on private storage.
- Deliver private files only through authorized controller responses.
- Validate extension, MIME type, decoded image validity, and size.
- Escape all article, feedback, note, response, and testimonial text.
- Derive user, role, age, and consent eligibility server-side.
- Do not store submitter IP addresses.
- Do not accept external affected-page URLs.
- Rate-limit feedback submission and helpfulness updates.
- Do not publish minor feedback.
- Do not publish without explicit active consent.
- Do not expose feedback to instructors, parents, or connector moderators merely
  because they have a relationship with the submitter.
- Preserve existing Community Hub access, connector ownership, visibility,
  moderation, reporting, audit, and minor-exclusion behavior.

## Validation and Error Handling

- Show validation messages beside the corresponding controls.
- Preserve non-file form values after validation failure.
- Explain that file inputs must be selected again after a failed submission.
- Reject unsupported or oversized images with a plain-language message.
- Return the application's normal 403 or 404 response without confirming the
  existence of another user's feedback.
- Return 404 for unpublished public articles while allowing authorized admin
  preview.
- Clean up newly stored files when persistence fails.
- Update an existing helpfulness vote instead of creating duplicates.
- Show categories and a feedback action when search returns no results.
- Use database constraints to protect unique slugs, reference numbers, votes,
  and one-testimonial-per-feedback rules.

## UI and Accessibility Contract

Follow the project-local Conscious Connections UI skill:

- Use the configured Poppins/Figtree `font-sans` stack.
- Use the existing purple/indigo brand gradient for primary identity.
- Use white primary surfaces and softly tinted support sections.
- Prefer `rounded-xl` and `rounded-2xl` with restrained shadows.
- Preserve learner dark-mode behavior where the learner shell supports it.
- Keep instructor and admin surfaces light, calm, and operational.
- Use existing shared UI components before creating new ones.
- Use literal Tailwind class names that Vite can detect.
- Preserve visible keyboard focus, semantic headings, labels, alternative text,
  and mobile-friendly control sizes.
- Wrap administrative tables for horizontal scrolling on small screens.
- Avoid auto-playing motion and decorative support widgets.

Status colors use emerald for resolved, amber for reviewed/planned, rose for
actual blocking errors, and neutral or brand tones for ordinary new records.

## Navigation Contract

- Add `Help Center` to the public landing footer.
- Add a Help Center entry to learner, parent, instructor, connector, and admin
  navigation using the pattern of each existing shell.
- Add `My Feedback` in the authenticated account/support area.
- Put the main `Send Feedback` action inside the Help Center instead of adding a
  second primary sidebar item to every role.
- Add the admin `SUPPORT` navigation group for Help Articles, User Feedback, and
  Testimonials.

Route names, not hard-coded URLs, drive every navigation link.

## Notifications

Use the existing database notification mechanism. A submitter receives a
notification only when:

- The user-visible status meaningfully changes; or
- The staff response is created or changed.

Initial submission confirmation is rendered immediately and does not require a
separate notification. Admin discovery uses the inbox and a stable new-feedback
count rather than notifying every administrator individually.

## Implementation Phases

### Phase 1: Domain foundation

Add enums, migrations, models, relationships, factories, policies, validation,
and foundational tests.

### Phase 2: Public Help Center

Add public queries, search, role filtering, index/article pages, helpfulness
voting, and initial seeded categories and guides.

### Phase 3: Help administration

Add category/article workflows, ordered sections, screenshot handling, preview,
publish/archive actions, and admin navigation.

### Phase 4: User feedback

Add the submission service, private attachment delivery, feedback form,
confirmation, personal history, detail, and consent withdrawal.

### Phase 5: Feedback administration and insights

Add the inbox, filters, status workflow, notes, response notification, and native
aggregate insights.

### Phase 6: Testimonials

Add eligibility enforcement, testimonial drafting/publication/withdrawal,
ordering, and the landing-page testimonial section.

### Phase 7: Integration and verification

Add navigation links across shells, finish accessibility/responsive behavior,
run focused and regression tests, build frontend assets, perform browser checks,
and review only task-owned changes.

## Testing Strategy

### Help Center feature tests

- Guests can view the index and published general guides.
- Drafts, archived articles, and inactive categories are hidden publicly.
- Role-targeted guides are filtered correctly.
- Search returns only published, eligible articles.
- Admin preview does not expose drafts publicly.
- Admin CRUD, ordering, publish, and archive actions require admin access.
- Section images require valid image types and alternative text.
- A user has at most one helpfulness vote per article.

### Feedback feature tests

- Guests cannot submit or view feedback history.
- Every authenticated supported role can submit ordinary platform feedback.
- Validation rejects invalid types, ratings, paths, and files.
- Attachments are private and require owner/admin authorization.
- A user cannot enumerate or open another user's feedback.
- Original submissions remain immutable.
- Admin status, note, and response updates follow authorization rules.
- Meaningful user-visible changes notify once.
- Insight aggregates match seeded data.

### Testimonial safety tests

- Minor testimonial consent is rejected or ignored server-side.
- Minor feedback never enters testimonial candidate queries.
- Adult feedback requires explicit active consent and a display name.
- Admin publication re-checks eligibility.
- Role and profile image appear only with their separate permissions.
- Consent withdrawal immediately removes a public testimonial.
- Draft, withdrawn, and otherwise ineligible testimonials stay off the landing
  page.

### Integration and regression checks

- Help navigation renders in guest, learner, parent, instructor, connector, and
  admin surfaces.
- Admin support navigation exposes only authorized routes.
- Existing module and instructor feedback tests continue to pass.
- Existing Community Hub safety, visibility, and moderation tests continue to
  pass.
- Run focused PHP tests and the relevant broader feature groups.
- Run `npm.cmd run build` for Blade/Tailwind changes.
- Inspect the route list for the new named-route families.
- Browser-check guest, learner, minor learner, instructor, connector, and admin
  workflows at desktop and mobile widths.

## Acceptance Criteria

- Anyone can find and read published Help Center navigation guides.
- Signed-in users receive role-relevant guide recommendations without losing
  access to general help.
- Admins can maintain categories and structured articles without code edits.
- Draft or archived content never leaks through public routes or search.
- Authenticated users can submit structured feedback with one optional private
  screenshot and receive a reference number.
- Users can view only their own submissions and any staff response.
- Admins can triage feedback through the approved five-state lifecycle.
- Basic feedback counts and trends are calculated without external services.
- Minors can submit private product feedback but cannot become testimonials.
- No testimonial is public without active adult consent and admin approval.
- Consent withdrawal removes the testimonial from public results immediately.
- Landing testimonials are curated, accessible, and never auto-published.
- Feedback remains separate from reviews, Community Hub reports, and moderation
  cases.
- Existing Community Hub authorization and safety behavior continues to pass.
- The UI matches existing role shells and the Conscious Connections visual
  system.

## Non-Goals

- Live support chat.
- Threaded support tickets.
- Staff assignment, queues by assignee, or SLAs.
- Guest or anonymous feedback submissions.
- Multiple attachments or video attachments.
- AI classification, sentiment analysis, or automatic prioritization.
- External help-search or analytics services.
- Public testimonial directory.
- Automatic testimonial publication.
- Guardian consent for minor testimonials.
- Migration or replacement of module and instructor reviews.
- Changes to Community Hub reporting or moderation.
- General-purpose CMS abstractions unrelated to Help Center content.

## Delivery Constraints

- Implement on the existing `community-feed-v1` branch.
- Do not create a separate worktree.
- Do not switch to `main`.
- Do not pull, merge, commit, or push without a separate user instruction.
- Preserve the existing untracked `.claude/` directory.
- Use a direct, YAGNI implementation style and avoid speculative abstraction.
- Reinspect current routes, schema, layouts, and tests before each affected phase.
- Keep generated `public/build` changes distinct from source changes during
  final review.
