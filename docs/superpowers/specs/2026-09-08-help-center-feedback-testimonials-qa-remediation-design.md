# Help Center, Feedback, and Testimonials QA Remediation Design

**Date:** 2026-09-08

**Status:** Approved on 2026-09-08

**Branch:** `community-feed-v1`

**Source:** Approved 2026-09-07 feature design plus the 2026-09-08 QA report

**Approach:** Root-cause remediation in dependency order (Approach A)

## 1. Purpose and scope

This design corrects all 28 grouped defects identified in the Help Center, private Platform Feedback, and Testimonials QA review. The objective is to make the approved feature safe, complete, accessible, and verifiable without replacing its intended architecture or weakening existing Community Hub protections.

Work stays in the existing checkout and directly on `community-feed-v1`. No worktree, branch switch, commit, push, pull, merge, or pull request is part of the remediation. Existing unrelated dirty files remain untouched.

The implementation includes:

- Security and seeded-identity remediation.
- Help Center audience, search, category, voting, authoring, ordering, and content corrections.
- Canonical and connector-context feedback routing.
- Private attachment hardening.
- Idempotent and rate-limited feedback submission.
- Explicit feedback lifecycle and audit semantics.
- Testimonial eligibility, curation, ordering, publication, withdrawal, and public visibility.
- Admin and role-shell navigation.
- Accessibility and responsive-layout corrections.
- Migration, test-environment, automated-test, and browser verification.

The existing Dompdf memory exhaustion is outside the production-code scope. The full suite will still be attempted, and that failure will be reported separately if unchanged. Only the isolated `cc_db_test` database may be refreshed automatically.

## 2. Selected architecture

The remediation will reuse Laravel controllers, requests, policies, models, services, Blade views, database notifications, and native validation. It will add focused domain services only where a multi-step invariant must be shared:

- `HelpAudienceResolver`: resolves guest, learner, parent, instructor, connector, and admin Help audiences.
- `HelpArticlePersistenceService`: synchronizes sections and owns screenshot compensation/cleanup.
- `PlatformFeedbackLifecycleService`: validates transitions, maintains audit fields, and dispatches user-visible notifications after persistence.
- `TestimonialPublicationService`: owns candidate creation, eligibility, curation, publication, withdrawal, and ordering.

`PlatformFeedbackSubmissionService` remains responsible for identity-derived submission data, private attachment persistence, idempotency, and draft creation.

Controllers remain thin: authorize, validate, call one focused service, and return a context-correct response. Query behavior remains in model scopes or focused aggregate methods; no repository abstraction or unrelated refactor is added.

## 3. Seed security and attribution

`HelpCenterSeeder` must not create a login-capable user. The predictable `help-center@consciousconnections.local` identity and fixed password are removed.

Because the feature is approved as unreleased, the original Help Center migration will define nullable `created_by` and `updated_by` foreign keys with `nullOnDelete`. Seeded articles use null system attribution. An administrator-created article receives both creator and updater IDs. An edited article keeps its original `created_by` value and updates only `updated_by`.

No operational deletion of an existing non-test user is authorized. Tests use a clean database and prove that running the seeder creates no predictable identity. If later evidence shows this seeder ran in a shared environment, account cleanup becomes a separate operational action requiring explicit approval.

## 4. Help audience and visibility

`HelpAudienceResolver` produces one effective audience from the current request context:

| Context | Effective audience |
| --- | --- |
| Unauthenticated | `guest` |
| Standard learner | `learner` |
| `role=learner`, `account_type=parent` | `parent` |
| Instructor | `instructor` |
| Admin | `admin` |
| Authorized connector workspace route | `connector` |

The resolver depends on the authenticated user and, when present, an already-authorized connector context. A user's ordinary global role does not become `connector` merely because they own or belong to a connector; the connector audience applies while using that connector's scoped Help route.

Every public Help query requires:

- Article status is published.
- Article publication timestamp is present.
- Category is active.
- Article audience includes `all` or the effective audience.
- Category audience includes `all` or the effective audience.

The index, direct detail lookup, related guides, category links, and connector aliases use the same rules. Ineligible, draft, archived, and inactive-category articles return 404 on direct public access.

## 5. Help discovery and reader actions

Category navigation uses an explicit validated category slug parameter. It no longer submits the category name as free-text search.

Text search:

- Trims and caps input.
- Matches title, summary, normalized individual keywords, and section body.
- Supports partial and case-insensitive matching according to the configured database behavior.
- Escapes wildcard characters.
- Never broadens audience or publication constraints.
- Retains category/search parameters through pagination.
- Produces a useful no-results state with visible categories and Send feedback.

The admin keyword textarea is parsed on line endings, trimmed, lowercased for storage/search consistency, deduplicated, and stripped of blank values before validation. A blank optional textarea saves as an empty keyword array.

Published guides display accessible Helpful and Not helpful controls. The existing one-user/one-article database constraint remains authoritative. Repeated selection updates the existing row rather than increasing totals. The view displays the current user's choice and totals and includes a context-aware Send feedback link.

## 6. Help administration and screenshots

Article persistence synchronizes sections by server-validated section ID:

1. Load existing sections owned by the article.
2. Reject or ignore a submitted section ID that belongs to another article.
3. Retain an existing image when no replacement is submitted.
4. Store new images before the database transaction and track every new path.
5. Create or update submitted sections in submitted order.
6. Delete database rows omitted from the submitted section set.
7. Commit the database transaction.
8. After commit, delete files replaced by new uploads or belonging to removed sections.
9. On any failure before commit, delete only newly written files and retain all prior files/data.

The browser never supplies a trusted existing storage path. It submits an owned section ID.

Admin functionality includes:

- Article search and validated category/status/audience filters.
- Category and article ordering.
- Section add, remove, move up, and move down controls.
- Category activation/deactivation with an explicit hidden false value.
- Helpful/not-helpful totals.
- Preview that cannot change publication state.
- Publish/archive actions that preserve valid timestamps.
- Labels and field-level errors for dynamic section controls.
- Preservation of non-file values after validation failures.

## 7. Seeded Help content

The 13 approved categories and 13 initial guides remain stable by slug. The seeder remains repeatable without duplicate categories, articles, or sections.

Generic generated paragraphs are replaced by content grounded in actual named routes and role navigation. Each guide states:

- Where to begin.
- Which roles can access the action.
- Concrete steps.
- The expected status or result.
- What to do when the action is unavailable.
- The relevant Help or feedback escalation path.

Seeded content is maintained as application-owned starter content before release. Rerunning the seeder deterministically restores those specific seeded guides. This behavior is documented so administrators do not assume pre-release seed records preserve later manual edits.

## 8. Canonical feedback and connector routing

The canonical route contract is:

- `GET /feedback` → `feedback.create`
- `POST /feedback` → `feedback.store`
- `GET /feedback/submissions` → `feedback.index`
- `GET /feedback/submissions/{platformFeedback}` → `feedback.show`
- `GET /feedback/submissions/{platformFeedback}/attachment` → `feedback.attachment.show`
- `DELETE /feedback/submissions/{platformFeedback}/testimonial-consent` → `feedback.testimonial-consent.destroy`

Connector routes remain aliases around the same platform-wide records. No `connector_id` is added to Platform Feedback.

Every connector Help or Feedback entry point authorizes the connector before controller behavior. The existing connector access service defines member and global-admin access. An unrelated authenticated user receives 403 and no submission is created.

Controller method signatures match route parameter order explicitly. Connector form actions, search, pagination, detail links, attachments, breadcrumbs, consent withdrawal, and post-submit redirects preserve connector context. Feedback ownership remains owner-or-platform-admin; connector membership never grants access to another user's record.

## 9. Submission idempotency and throttling

The feedback form receives a server-generated UUID `submission_token`, retained through validation errors. `platform_feedback` stores the token with a unique constraint. The token is server-validated but is not an authorization credential.

Submission behavior is idempotent:

1. If the current user already has the token, return that existing submission without storing another file.
2. Validate and store an optional attachment.
3. Create feedback and its optional private testimonial draft in a transaction.
4. If a unique-token race occurs, delete the racing request's newly stored file and return the winning record.
5. If storage or persistence fails, show a safe retry message and create no partial record.

A token belongs to the authenticated submitter. A token collision with another user does not expose or redirect to the other user's record.
Instead, the conflicting request receives a safe conflict response and a fresh form token.

Named rate limiters apply to:

- Global and connector feedback submission: five accepted attempts per minute per authenticated user.
- Helpfulness updates: thirty attempts per minute per authenticated user.

Limit responses use the normal 429 behavior and an understandable UI message. Client forms also set a processing/disabled state, but database idempotency remains the correctness boundary.

## 10. Private attachment security

Allowed feedback attachments are one JPEG, PNG, or WebP image, no larger than 5 MB. Validation checks:

- Successful PHP upload state.
- Allowed client extension.
- Detected MIME type.
- Successful decoded image metadata inspection.
- Width, height, and total-pixel limits.
- Rejection of truncated or malformed image content.

A maximum dimension of 8,000 pixels per side and 40 megapixels prevents disproportionate decoding/storage cost while remaining above normal screenshots.

`store()` returning `false` is treated as a failed operation. No record is created. A database failure deletes the newly written file.

Files remain on the private disk under generated paths. Owner-or-admin policy authorization is required for delivery. Responses include `X-Content-Type-Options: nosniff`, a safe content type, and no direct public storage URL. Failed authorization does not reveal attachment existence.

## 11. Feedback lifecycle and notifications

Allowed transitions are explicit:

| Current | Allowed next states |
| --- | --- |
| `new` | `reviewed`, `archived` |
| `reviewed` | `planned`, `resolved`, `archived` |
| `planned` | `reviewed`, `resolved`, `archived` |
| `resolved` | `reviewed`, `planned`, `archived` |
| `archived` | `reviewed` |

Saving the current status again is a valid no-op.

Audit rules:

- `reviewed_by` and `reviewed_at` are assigned on the first valid transition out of `new`.
- Archiving a new item counts as an administrative review and receives review attribution.
- A private-note-only update while status remains `new` does not mark it reviewed.
- Entering `resolved` assigns `resolved_at`.
- Leaving `resolved` clears `resolved_at`.
- Original user content and attachment identity are immutable.

After a successful update, one database notification is sent only when the user-facing status changes or the staff response changes. Internal notes do not trigger notifications and are never serialized. Notification URLs use the corrected canonical detail route.

## 12. Testimonial lifecycle and public eligibility

An explicitly consenting adult submission may create one private draft testimonial. Draft creation does not imply administrator review and never makes content public.

`TestimonialPublicationService` is the only publication-state writer. Publishing requires:

- Source feedback and author still exist.
- Author account is active.
- Current server-derived age is adult.
- Consent is true and the withdrawal timestamp is null.
- Public display name is present.
- Source feedback has `reviewed_at`.
- Source status is `reviewed`, `planned`, or `resolved`.
- Role/profile-image choices do not exceed the user's separate consent flags.

An administrator may curate display name, an accurate shortened quotation, permitted role text, permitted profile-image display, and sort order. The private source appears beside the curation form and is immutable. The quotation has a 1,000-character public limit independent of the original 20,000-character private description.

The public scope independently rechecks published state, timestamp, active consent, current adulthood, active author, and non-deleted author. Invalid persisted rows therefore fail closed even if a controller defect or later account change occurs.

Consent withdrawal atomically:

- Sets consent false.
- Records the withdrawal timestamp.
- Marks any linked testimonial withdrawn.
- Clears its publication timestamp.

Admin withdrawal performs the testimonial state change without changing the user's underlying consent. Republishing must pass the complete eligibility guard again.

## 13. Admin feedback and testimonial interfaces

The feedback inbox exposes existing validated backend filters and aggregates:

- Search by reference, subject, or submitter.
- Status tabs with stable unfiltered counts.
- Type, rating, from-date, and to-date filters.
- Total, new, unresolved, resolved, and average rating.
- Counts by type and status.
- Monthly submissions and average rating.
- Frequently affected paths.
- Query-preserving pagination and useful empty states.

Feedback detail shows the immutable original, safe attachment link, lifecycle controls, and two visually separated fields:

- **Private internal note — administrators only**
- **Response visible to the submitter**

Both fields receive permanent labels, help text, associated errors, and distinct visual treatment.

The testimonial workspace provides candidate discovery, editing, preview, publication, withdrawal, and ordering. It shows source eligibility and explains why an item cannot publish. Public fields never expose email, attachment path, internal note, user-agent metadata, or other account details.

## 14. Navigation, accessibility, and responsive layout

The implementation will follow the available `.codex/conscious-connections-ui` system because this checkout has no `.agents` directory.

Navigation:

- Landing footer: Help Center.
- Learner/guardian: Help Center plus discoverable My Feedback in the support/account area.
- Instructor: Help Center and My Feedback.
- Connector: connector-scoped Help Center and My Feedback.
- Admin: SUPPORT group with Help Articles, User Feedback, and Testimonials.

The primary Send feedback action lives in the Help Center; navigation remains discoverable without unnecessarily duplicating prominent sidebar items.

Accessibility requirements:

- Visible labels for every input, select, textarea, and file control.
- Field-level errors associated through `aria-describedby` and invalid state.
- Preserved select/checkbox values after validation failure.
- Notice that file inputs must be selected again.
- Semantic headings, fieldsets, legends, tables, status text, and button names.
- Visible keyboard focus and usable tab order.
- Status meaning conveyed by text, not color alone.
- No unsafe raw rendering of user/admin content.

Responsive requirements:

- Long titles, subjects, filenames, and quotation excerpts wrap safely.
- Flexible children use `min-w-0`.
- Arbitrary identifiers use targeted `overflow-wrap:anywhere`.
- Page-level horizontal overflow is prevented.
- Admin tables keep intentional local horizontal scrolling.
- Desktop and 390-pixel mobile layouts remain usable.

## 15. Migration and failure boundaries

Since the feature is unreleased, original support migrations may be corrected for fresh installations. The schema retains:

- Unique article/category slugs.
- Unique feedback reference numbers.
- One vote per user/article.
- One testimonial per feedback.
- New feedback submission-token uniqueness.
- Required ownership and cascade/null behavior.
- Indexes for visibility, filters, ordering, and eligibility.

No normal development database is refreshed automatically. Migration fresh/rollback verification runs only against an explicitly identified isolated test database.

Multi-step mutations use database transactions plus filesystem compensation. New files are deleted after failed persistence. Old files are deleted only after a successful commit. Expected validation and authorization failures use normal 422/session errors, 403, 404, 409/idempotent success, or 429 responses without exposing internal paths or stack traces.

## 16. TDD execution and acceptance

Implementation follows a root-cause sequence:

1. Add or promote focused failing tests for one defect group.
2. Run them and confirm the expected failure.
3. Implement the smallest coherent domain correction.
4. Rerun the focused tests.
5. Run the related Support group.
6. Inspect the scoped diff.
7. Continue to the next dependency group.

Execution batches:

1. Seed security, attribution, and schema.
2. Audience resolver and Help visibility.
3. Search, categories, keywords, voting, and reader actions.
4. Admin Help persistence, screenshots, ordering, and seeded content.
5. Canonical/connector routing and feedback detail.
6. Idempotency, throttling, and attachment validation.
7. Lifecycle, audit fields, notifications, and admin feedback UI.
8. Testimonial service, curation, ordering, and public scope.
9. Navigation, accessibility, and responsive behavior.
10. Comprehensive verification.

Durable QA cases move into normal `tests/Feature/Support` and `tests/Unit/Support` suites. The review-only `tests/QA` suite remains executable until its coverage is incorporated and all expected failures are resolved.

Completion requires fresh evidence for:

- Existing and expanded Support suites with zero feature failures.
- All eight previously blocked UI tests executing assertions.
- Adjacent Community, authentication, connector, notification, upload, landing, and APK regressions.
- Route and middleware inventory.
- PHP lint, scoped formatting, Blade rendering, and Vite build.
- Real browser workflows at desktop and mobile widths.
- Long-content, keyboard, focus, form-error, privacy, and accessibility checks.
- Migration/constraint/seeder verification on an isolated database.
- `git diff --check` and task-owned diff review.

The full repository suite is attempted after focused verification. An unchanged Dompdf exhaustion is reported separately and does not erase feature results. No success claim is made without current command output.

## 17. Defect coverage map

| Defects | Design section |
| --- | --- |
| D01, D09 | Seed security and attribution |
| D10, D12 | Help audience and visibility |
| D11, D13, D16 | Help discovery and reader actions |
| D08, D14, D15 | Help administration and screenshots |
| D27 | Seeded Help content |
| D05, D06, D07 | Canonical and connector routing |
| D17, D18 | Idempotency and throttling |
| D19, D20 | Private attachment security |
| D04, D21 | Feedback lifecycle/detail/notifications |
| D02, D03, D26 | Testimonial lifecycle/public eligibility |
| D22, D25 | Admin interfaces |
| D23, D24, D28 | Navigation, accessibility, responsive layout |

## 18. Explicit non-goals

This remediation does not:

- Add connector ownership to Platform Feedback.
- Add a public testimonial directory.
- Add guardian consent for minor testimonials.
- Add feedback reply threads.
- Add AI sentiment analysis or external analytics.
- Change Community Hub moderation or visibility rules.
- Fix the unrelated Dompdf implementation/memory behavior.
- Modify live users or shared databases.
- Commit, push, merge, switch branches, or create a worktree.
