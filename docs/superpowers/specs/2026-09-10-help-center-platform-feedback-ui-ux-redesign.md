# Help Center and Platform Feedback UI/UX Redesign

## Status

Approved in chat on 2026-09-10. This document is the implementation-level
design for the user-facing Help Center and Platform Feedback experience, plus a
second-phase visual refinement of the related platform-admin work surfaces.

This specification extends, but does not replace, the domain and authorization
contracts in
`docs/superpowers/specs/2026-09-07-help-center-feedback-testimonials-design.md`.
If the documents differ, the earlier document remains authoritative for data,
privacy, access, and publication rules; this document is authoritative for the
new UI structure and interaction behavior.

Implementation remains local to the existing `community-feed-v1` checkout. Do
not create a worktree, change branches, pull, merge, commit, or push unless the
user separately requests it.

## Product Outcome

Turn the currently separate Help Center, feedback form, feedback history, and
feedback detail pages into one coherent Support experience. A person should be
able to find an answer, recognize when they need a safety-reporting tool instead,
submit product feedback, and understand the state of a previous submission
without learning a new navigation model on each page.

The redesign must preserve the platform's role-specific shells while making the
support navigation, icon meanings, information hierarchy, form behavior, and
status language consistent for admins, instructors, learners, parents, and
connector users.

## Success Criteria

- Help Center, Send Feedback, and My Feedback feel like parts of one workflow.
- Help Center and Platform Feedback use the same icons in every role sidebar and
  within support-page headings and actions.
- A user can distinguish product feedback from an unsafe-content report before
  submitting anything.
- Every feedback form control has a visible label, helper text where needed, an
  inline validation message, and preserved input after a validation failure.
- Feedback types and statuses are displayed with human-readable enum labels,
  never raw database values.
- Role personalization changes the surrounding shell and recommended content,
  not the basic page structure or icon vocabulary.
- Learner pages remain usable in light and dark modes; admin and instructor pages
  remain light-mode operational workspaces.
- The existing support authorization, connector context, validation, attachment,
  testimonial-consent, and route contracts continue to pass.

## Non-goals

- No live chat, ticket conversation thread, staff assignment, SLA, or external
  help-desk integration.
- No AI search, AI feedback classification, or external search service.
- No new safety-reporting or emergency-response system.
- No changes to Community Hub, seminar, message, or learning-content report
  ownership.
- No feedback editing after submission.
- No feedback visibility for instructors, parents, or connector staff beyond
  their own submissions.
- No automatic collection of a full URL, query string, browser screenshot, or
  sensitive page contents.
- No database-schema change is required for the core redesign.

## Chosen Approach

Use a Unified Support Workspace inside the role shell already selected by
`SupportLayoutResolver`.

The workspace adds a shared support sub-navigation with three destinations:

1. Help Center
2. Send Feedback
3. My Feedback

Guests see Help Center only. Authenticated users see all three destinations.
Platform admins retain a separate Support Management sidebar group for Help
Center administration, the feedback inbox, and testimonials. Personal Platform
Feedback and administrative Feedback Inbox must remain visibly distinct.

This approach is preferred over isolated page polish because navigation and
status conventions stay consistent throughout the workflow. It is preferred
over a guided support wizard because the current domain does not need extra
steps, conditional routing, or conversational state.

## Visual and Interaction Thesis

### Visual thesis

Present a calm, trustworthy support desk: a warm lilac page wash, white working
surfaces, restrained borders and shadows, generous reading space, and the
existing purple-to-indigo brand gradient used only for identity and primary
actions. Avoid a dense dashboard-card mosaic.

Use the existing `font-sans` stack, brand color scale, `rounded-xl` and
`rounded-2xl` radii, and outline SVG icon language. Cards are reserved for
objects that are actually clickable or conceptually grouped.

### Content plan

- Orient the user with the shared Support navigation.
- Put search or the current task first.
- Provide the most relevant next choices with minimal supporting copy.
- End each page with one clear continuation: read another guide, send feedback,
  review a submission, or return to Help Center.

### Interaction thesis

- Use fast focus, hover, selection, and disclosure transitions, approximately
  150-200 milliseconds.
- Give search results and form validation a subtle entrance/update transition
  where Alpine is already present, without delaying server navigation.
- Animate attachment preview and testimonial disclosure only enough to preserve
  spatial continuity.
- Respect `prefers-reduced-motion` and never make motion necessary for
  understanding or task completion.

## Information Architecture

### Authenticated support workspace

```text
Support
|-- Help Center
|   |-- Search results
|   |-- Category results
|   `-- Guide detail
|-- Send Feedback
`-- My Feedback
    `-- Submission detail
```

### Administrative support workspace

```text
Support Management
|-- Help Articles
|   `-- Categories
|-- Feedback Inbox
`-- Testimonials
```

The administrative pages are a second delivery phase. They reuse the visual
tokens and status components introduced for user-facing pages but do not share
the user-facing three-tab sub-navigation.

## Shared Support Components

Prefer existing shared UI components before creating new ones. Introduce only
small support-specific Blade components or partials with one clear purpose.

### Support icon

Continue using `resources/views/components/ui/support-icon.blade.php` as the
single source for support icon geometry:

- `help`: question mark inside a circle.
- `feedback`: message bubble with pencil.

The component is used in every role sidebar, support page heading, feedback
entry point, and support empty state. Feedback-type tiles use a separate small
type-icon mapping so Bug Report, Feature Suggestion, and the other choices remain
visually distinct. Icons remain decorative when adjacent text already supplies
the accessible name.

### Support navigation

Add a focused `SupportRouteContext` service that accepts an optional connector
and returns the contextual Help Center index, feedback create, feedback history,
feedback detail, attachment, and consent-withdrawal route definitions. Both
support controllers use this service instead of maintaining separate private
route maps. Each support view receives a `supportRoutes` array with the same
shape.

A shared Blade partial or component renders the authenticated destinations from
`supportRoutes`. It must:

- Preserve connector-scoped route parameters.
- Use `aria-current="page"` for the active destination.
- Keep Platform Feedback active for create, history, and submission-detail
  routes.
- Collapse into a horizontally scrollable tab row on narrow screens without
  hiding destinations in a menu.
- Use a visible keyboard-focus ring and a minimum 44-pixel touch target.

Guests do not receive a redundant one-item tab row. The public Help Center
header provides the page identity and a sign-in-gated feedback action instead.

### Page header

Use a consistent header rhythm: icon, short eyebrow where helpful, page title,
one-sentence explanation, and at most one primary action. The Help Center search
banner is the only richer branded header.

### Status badge

Map `PlatformFeedbackStatus::label()` to a reusable text-and-color treatment:

- New: sky or neutral blue, label `New`.
- Reviewed: amber, label `In Review`.
- Planned: purple, label `Planned`.
- Resolved: emerald, label `Resolved`.
- Archived: gray, label `Archived`.

Every badge contains the text label; color is never the only status indicator.

### Feedback-type presentation

Map `PlatformFeedbackType::label()` to a stable outline icon and human-readable
label. The enum remains the source of value and label truth. Do not reproduce
labels through `str()->headline()` or show raw values such as `bug_report`.

## Help Center Landing Page

### Header and search

Retain the existing brand gradient as a compact, search-focused banner within
the content canvas. It contains:

- `Conscious Connections Support` eyebrow.
- `How can we help?` heading.
- One concise explanation.
- A large search field with search icon, submit action, and clear action when a
  term is present.

The header should feel useful inside an application shell, not like a marketing
landing-page hero. Its content must fit comfortably in the first viewport below
the role header.

### Role-aware recommendations

Authenticated viewers receive a `Recommended for you` group based on the
existing audience resolver. The underlying publication and audience query stays
authoritative. Recommendation copy may name the audience, such as
`Recommended for learners`, but must not expose authorization language.

Guests receive general getting-started guides. If no audience-specific guides
exist, omit the group instead of showing an empty panel.

### Browse by topic

Display active, visible categories in a responsive two-column or three-column
grid. Each category object includes:

- Validated category icon.
- Category name.
- One-line description.
- Count of published guides visible to the current audience.
- Clear forward affordance.

The whole category surface is clickable. Category links must use the contextual
Help Center route and preserve connector workspace parameters rather than
hard-coding the public `help.index` route.

### Popular guides

Render popular or manually ordered guides as a divided list, not another large
card grid. Each row includes category, title, short summary or reading time, and
a forward arrow. Existing `sort_order` remains the ranking source; view-count
tracking is not introduced.

### Safety and feedback actions

Place a calm amber safety notice after the core help content. It explains that
unsafe or inappropriate content must be reported from the relevant content
surface so the safety team receives the right context.

End the page with a compact `Could not find what you need?` Platform Feedback
action. Guests are sent to sign in before feedback submission.

## Search and Category Results

Search remains server-rendered and query-string driven. No JavaScript search
framework is added.

Results include:

- The submitted search term or selected category.
- Result count.
- A clear-search or browse-all action.
- Category, title, and concise summary for each matching guide.
- Pagination that preserves the active query and connector context.

When no result exists, show one intentional empty state with these options:

1. Try a shorter or different search.
2. Browse all topics.
3. Send Platform Feedback if a guide appears to be missing.

Search matching and audience restrictions remain unchanged. Search-term
highlighting is optional and must be escaped; it may be omitted if safe
highlighting would add disproportionate complexity.

## Help Article Page

### Layout

Use a two-column layout on wide screens:

- Main article column with a readable maximum line length.
- Sticky `On this page` navigation generated from section headings.

On small screens, `On this page` becomes an accessible disclosure above the
article body. If the article has fewer than two headed sections, omit it.

### Header metadata

Show breadcrumb, category, title, summary, computed reading time, and
`Last updated` using the existing article timestamp. Reading time is computed
from the escaped section text and does not require persistence.

### Article content

- Maintain semantic `h1`, `h2`, and subsequent heading order.
- Generate stable section anchors from the section position plus a slugged
  heading, preventing duplicate-heading collisions.
- Use visual section numbers only as orientation; do not label every article as
  a required step-by-step procedure.
- Preserve whitespace in section bodies and continue escaping stored content.
- Render screenshots with their required alt text, restrained border, and
  optional visual caption derived from the section heading.
- Offer `Back to top` only for sufficiently long articles.

### Helpfulness and continuation

The helpfulness control displays Yes and No buttons with a visible selected
state matching `aria-pressed`. It retains the existing one-vote-per-user update
behavior.

`Still need help? Send feedback` opens the contextual feedback form with:

- Type preselected as `help_content_issue`.
- The article path supplied as the affected page.
- No automatic copy of article contents, search terms, query parameters, or
  other sensitive data.

Related guides render as a divided list and preserve connector route context.

## Platform Feedback Form

### Form model

Use one server-submitted form with progressive disclosure. Do not introduce a
multi-route wizard. The visual sections are:

1. What would you like to share?
2. What happened?
3. Supporting information
4. Contact permission
5. Optional testimonial consent for eligible adults
6. Submit action

The existing idempotent submission token, throttling, request validation, image
validation, and submission service remain authoritative.

### Feedback type

Render the five enum cases as an accessible radio group of selectable icon
tiles. Each tile has a label and one short explanation:

- General Feedback
- Bug Report
- Feature Suggestion
- Accessibility Issue
- Help Content Issue

Keyboard users can move through and select the choices. Validation is attached
to the group legend and first control.

### Main fields

- `Subject`: visible label, short helper, 180-character maximum.
- `Description`: visible label, task-appropriate prompt, 20,000-character
  maximum, and an Alpine character counter.
- `Rating`: optional accessible 1-5 radio scale labelled from `Very poor` to
  `Excellent`; the group explains that it rates the overall platform experience.
- `Affected page`: user-facing label `Where did this happen?`; editable, with an
  example path instead of backend terminology.

When launched from a Help Center article or another approved internal action,
the form may accept an `affected_path` query value. The controller must normalize
it to an internal path, reject external URLs, remove any query string or
fragment, limit it to the existing validation length, and allow the user to edit
or clear it.

### Attachment

Provide a labelled image upload area with:

- JPG, PNG, or WebP and 5 MB guidance before selection.
- Selected filename and local image preview.
- Replace and remove actions.
- Reminder to hide personal or sensitive information.
- Server validation as the final authority.

The preview remains browser-local and is not uploaded until the user submits.

### Contact permission

Use a checkbox with direct copy explaining that platform staff may contact the
user about this submission. The default remains unchecked.

### Testimonial consent

Render this section only when `TestimonialEligibility` identifies an adult. It
starts collapsed under `Optional: allow this feedback to be considered as a
testimonial`.

Opening the disclosure does not grant consent. The publication-consent checkbox
must be explicitly selected before display-name, role-sharing, and profile-image
sharing controls become available. Role and profile-image sharing default to
unchecked and preserve their old values after validation failures.

Copy must explain that:

- Feedback stays private unless selected and approved for a testimonial.
- An admin may curate the final excerpt.
- Consent can be withdrawn later.

The entire section remains absent for minors and otherwise ineligible users.

### Validation and submission

- Render a focusable error summary at the top when validation fails.
- Link each summary entry to the invalid field.
- Render a specific inline message below each invalid field.
- Apply `aria-invalid` and `aria-describedby` to affected controls.
- Preserve every submitted value, including type, rating, checkboxes, display
  name, and testimonial-sharing choices.
- Disable duplicate submission after submit, show `Submitting...`, and expose
  `aria-busy` without hiding the button label from assistive technology.

## Product Feedback Versus Safety Reports

Every feedback entry point and the form itself must reinforce this distinction:

- Platform Feedback covers bugs, accessibility barriers, missing or unclear Help
  Center content, suggestions, and general product experience.
- Unsafe content or conduct is reported using the report action on the relevant
  Community Hub, seminar, message, or learning-content surface.
- The notice must not promise emergency monitoring or immediate response.

The UI provides a link to the existing safety-reporting Help Center guide. It
does not create a generic report route or convert feedback into moderation data.

## Submission Success and Feedback Detail

After successful submission, retain the current redirect to the owned feedback
detail route. Display a success banner with:

- Confirmation.
- Reference number.
- Copy-reference action.
- Current human-readable status.
- Link to My Feedback.
- Link back to Help Center.

The detail page then becomes the durable submission record.

### Detail composition

- Subject and reference number.
- Feedback-type label and icon.
- Submitted date and current status badge.
- A status progression indicator showing New, In Review, Planned, and Resolved.
- Original description.
- Affected page when supplied.
- Rating when supplied.
- Authorized attachment link and preview where the file can be safely rendered.
- Contact-permission state.
- Staff response in a visually distinct response panel.
- Testimonial-consent state and withdrawal action where applicable.

The progression indicator represents possible lifecycle positions, not an audit
timeline. Emphasize only the current state; do not mark earlier positions as
completed, invent dates, or claim they were visited. Archived is shown as a
separate terminal state rather than as a fifth progress milestone.

## My Feedback

Use a page header with `Send feedback` as the single primary action. Render
submissions as divided, clickable rows instead of plain list items or a large
card mosaic.

Each row includes:

- Feedback-type icon.
- Subject.
- Reference number.
- Submitted date.
- Human-readable status badge.
- Short, escaped description preview.
- Forward affordance.

Add query-string filters for:

- All
- Active: New, Reviewed, or Planned
- Closed: Resolved or Archived

Filters remain owned-user scoped. Pagination preserves the chosen filter and
connector context. An empty account history has one action: `Send feedback`.
A filtered empty state offers `Show all feedback`.

## Role and Route Behavior

The common support content stays shared. The existing layout resolver remains
responsible for the outer shell:

- Guest: landing/public layout; Help Center only.
- Learner and parent: learner layout with light and dark variants.
- Instructor: instructor application layout in light mode.
- Admin: admin layout in light mode.
- Connector context: connector application layout and connector-scoped route
  parameters.

The same Help Center and Platform Feedback icon SVGs appear in every shell.
Role-specific changes are limited to shell chrome, dark-mode classes,
recommendations, contextual route parameters, and authorization.

Connector membership or moderation permission never grants access to another
person's feedback. Connector workspace context changes return URLs and layout;
it does not change platform ownership of the submission.

## Admin Support Management Refinement

This is delivered after the user-facing workspace and reuses its status, type,
form, empty-state, and focus conventions.

### Help administration

- Provide clear Help Articles and Categories tabs.
- Use a searchable, horizontally scrollable article table with category,
  audience, status, updated date, and stable actions.
- Keep Draft, Preview, Publish, and Archive actions explicit.
- Improve the article form with grouped metadata and repeatable article sections.
- Keep public preview separate from public route binding.
- Preserve the current escaped-text and required-image-alt-text contract.

### Feedback inbox

- Use one filter row for search, status, type, rating, and date range.
- Label every filter and preserve active values.
- Render compact insight totals above the table without decorative charting.
- Use human-readable type and status labels in every row.
- Provide a clear-filter action and intentional empty states.
- Keep pagination and filter query parameters together.

### Feedback review

- Place submission identity and metadata above the description.
- Separate user-visible response from the private internal note.
- Label both textareas and describe who can see each value.
- Keep status selection and Save action in a distinct review panel.
- Use the authorized admin attachment route.
- Show testimonial eligibility and consent without implying automatic publication.

### Testimonials

- Make consent, publication status, excerpt, display name, role/image visibility,
  ordering, and withdrawal state scannable.
- Keep curation and publication as explicit admin actions.
- Never republish after consent withdrawal.

## Data and Controller Changes

No schema migration is planned. The implementation may add query preparation
and presentation data to existing controllers:

- Visible category article counts for the active audience.
- A bounded set of role-recommended articles using existing audience and order
  rules.
- Computed article reading time and section anchors.
- Normalized feedback prefill values.
- Owned feedback history filter handling.
- Enum cases or presentation maps for shared type/status components.
- Central contextual routes from `SupportRouteContext`, replacing duplicated
  controller-private route maps.

Controller queries must remain audience scoped and avoid per-row query loading.
Use eager loading and aggregate counts where required.

## Error, Empty, and Loading States

Every page must define these states explicitly:

- Help search with no results.
- Audience with no recommended articles.
- Category with no visible articles.
- User with no feedback history.
- Active feedback filter with no matching submissions.
- Feedback validation failure.
- Rejected or oversized attachment.
- Duplicate or throttled submission.
- Missing or unauthorized feedback attachment.
- Feedback detail with no staff response.
- Article with no related guides.

Empty states contain one primary next action. Errors use plain language and do
not expose exception text, storage paths, enum values, or policy internals.

## Accessibility Requirements

- One `h1` per page and correctly nested section headings.
- Visible labels for all form and filter controls.
- Error summaries and inline errors programmatically associated with controls.
- `aria-current` for support navigation and `aria-pressed` for helpfulness votes.
- Native radio, checkbox, button, details/disclosure, and file input behavior
  retained underneath custom styling.
- Visible brand-colored focus treatment on every interactive element.
- Minimum 44-pixel target for primary navigation and form choices.
- Status never communicated by color alone.
- Decorative SVGs use `aria-hidden`; meaningful icon-only controls receive an
  accessible name.
- Article images retain required alternative text.
- Keyboard access does not depend on hover or drag and drop.
- Motion respects reduced-motion preferences.
- Learner dark mode maintains readable contrast for backgrounds, text, borders,
  inputs, badges, and alerts.

## Responsive Behavior

- Main content uses the role shell's existing mobile spacing and desktop width.
- Support navigation scrolls horizontally on narrow screens with the active item
  visible.
- The Help Center search action remains easy to tap and may stack below the input
  on very narrow screens.
- Category grids collapse to one column.
- Article side navigation becomes a disclosure.
- Feedback-type tiles collapse to one column or a compact two-column grid without
  truncating their descriptions.
- Form actions remain in document flow; do not add a persistent mobile bar that
  could cover validation messages or learner navigation.
- Administrative tables use horizontal overflow and retain stable action
  columns.

## Security and Privacy Requirements

- Continue escaping all article, feedback, staff-response, and testimonial text.
- Do not expose storage paths directly.
- Preserve owner-or-admin feedback and attachment authorization.
- Normalize affected-page prefill to an internal path and discard query strings
  and fragments.
- Attachment preview uses a temporary browser object URL and revokes it when
  replaced or removed.
- Testimonial consent remains explicit, adult-only, revocable, and separate from
  basic feedback submission.
- Do not weaken throttling, idempotency, MIME validation, or image-content
  validation.

## Delivery Phases

### Phase 1: Shared support foundation

- Shared icon, support navigation, headers, status badges, and type presentation.
- Route-context preservation across public and connector paths.
- Component-level and navigation tests.

### Phase 2: Help Center

- Landing/search/category redesign.
- Article layout, article metadata, section navigation, helpfulness state, and
  contextual feedback entry.

### Phase 3: Platform Feedback

- Accessible progressive-disclosure form.
- Attachment preview and form-state preservation.
- Submission success, My Feedback filters/list, and feedback detail tracker.

### Phase 4: Admin Support Management

- Help administration, Feedback Inbox, feedback review, and testimonial visual
  refinement.

### Phase 5: Cross-role verification

- Guest, learner, parent, instructor, admin, and connector routes.
- Learner light/dark themes and desktop/mobile layouts.
- Focused automated tests, Blade compilation, asset build, and browser review.

## Likely Files and Boundaries

The implementation plan should refine the exact list, but expected work is
limited to the existing support domain:

- `resources/views/help/*`
- `resources/views/feedback/*`
- `resources/views/admin/help/*`
- `resources/views/admin/feedback/*`
- `resources/views/admin/testimonials/*`
- `resources/views/components/ui/support-icon.blade.php`
- New focused support components or partials under `resources/views/components`
  or the relevant view directories
- `app/Http/Controllers/HelpCenterController.php`
- `app/Http/Controllers/PlatformFeedbackController.php`
- A focused support route-context service under `app/Services/Support`
- Narrow request or presentation helpers only where validation or normalization
  belongs outside a view
- Existing support feature and QA tests, plus new focused UI contract tests

Unrelated layouts, domain models, routes, migrations, and services should not be
refactored unless a failing support contract requires a targeted correction.

## Verification and Acceptance

### Automated verification

- Run all tests under `tests/Feature/Support` and `tests/Unit/Support`.
- Run the support UI QA tests, including visible labels, old-input preservation,
  route presence, sidebar consistency, admin filters, and admin form labels.
- Add focused tests for support sub-navigation, contextual category links,
  human-readable enum labels, feedback prefill normalization, owned-history
  filters, empty states, and testimonial-control preservation.
- Compile Blade views or clear/rebuild the Blade view cache as appropriate.
- Run scoped Pint on changed PHP files.
- Run `npm.cmd run build` for Tailwind and Alpine changes.
- Run `git diff --check` and inspect only task-owned diffs because the checkout
  already contains unrelated changes.

### Browser verification

Review the following at desktop and mobile widths:

- Guest Help Center landing, search result, empty search, and article.
- Learner Help Center and feedback flow in light and dark mode.
- Parent, instructor, and admin support navigation and active states.
- Connector-scoped Help Center, feedback form, history, and detail URLs.
- Form validation, attachment preview/removal, testimonial disclosure, loading
  state, and success confirmation.
- My Feedback empty, filtered-empty, active, resolved, and archived states.
- Admin Help Articles, Feedback Inbox, feedback review, and testimonials.

### Final acceptance statements

- The same support icons and semantic labels appear for every role.
- Users can move between Help Center, Send Feedback, and My Feedback without
  returning to the sidebar.
- Safety reporting and product feedback remain visibly and technically separate.
- Feedback controls are labelled, errors are actionable, and failed submissions
  preserve every user choice.
- No role receives access to another person's private submission.
- No raw enum values are visible in the redesigned support pages.
- The redesign works with connector-scoped routes and learner dark mode.
- All focused support tests, build checks, and agreed browser checks pass before
  completion is claimed.
