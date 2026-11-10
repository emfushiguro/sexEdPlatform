# Dynamic Role-Based Onboarding Navigation Guides

## Goal

Add administrator-managed onboarding videos for the Learner and Instructor dashboards. An eligible user sees the current guide once on the applicable dashboard unless they dismiss or complete it, and can always return to the role-specific **Guide** page while that guide remains published.

The feature must preserve published versions and user history, keep dismissal distinct from completion, and enforce role and publication access on the server. It must not hardcode guide content or identifiers into frontend code.

## Approved product decisions

- The first published guide applies to every currently eligible user and to users who become eligible later.
- Eligibility means the user can currently access the applicable dashboard, including the existing active-account, suspension, verification, profile, and role gates.
- The active dashboard context selects the guide. Instructor remains the default where the existing role classification gives Instructor precedence.
- The modal appears only on the applicable dashboard landing page.
- Multiple drafts may exist, but only one guide may be current and published for a role and guide key.
- Administration uses one new `manage onboarding guides` permission.
- A target role may change only before the guide has ever been published.
- Videos use private local storage and authorized inline delivery.
- Upload validation reuses the existing 100 MiB limit and supported video formats.
- Custom thumbnail upload and thumbnail generation are excluded from the first release.
- Published content is immutable. Changing its video, title, or description creates a draft revision.
- Informational revisions retain the effect of prior completion or dismissal without copying or fabricating records.
- Completion is always an explicit action and is not gated by playback events.
- Skip, Close, and an Escape-key dismissal have the same persisted meaning.
- Ordinary users see only the current published guide; version history is administrator-only.
- Analytics use the currently dashboard-eligible population and aggregate counts only.
- Archiving never deletes published media or user history.

## Architecture choice

Use a focused onboarding domain consisting of a guide aggregate, immutable versions, and one state row per user and version.

A single mutable guide table would be smaller but could not truthfully preserve the content a user completed. A generic tutorial or course engine would support hypothetical future sequencing but would add categories, ordering, prerequisites, and authoring behavior that this release does not need. The selected model is the smallest shape that provides version history, role-safe publication, and separate dismissal and completion.

The initial guide key is `dashboard_navigation`. Keeping the key on the guide allows later tutorial types to be added as additional guide records without changing the version or user-state model. This release does not expose tutorial sequencing or multiple simultaneous user-facing guides.

## Domain model

### `onboarding_guides`

This is the stable guide identity and publication aggregate.

- `id`
- `guide_key`, initially `dashboard_navigation`
- `target_role`, initially `learner` or `instructor`
- `status`: `draft`, `published`, or `archived`
- `current_version_id`, nullable until the first publication
- `created_by`, nullable administrator reference
- `archived_at`, nullable
- timestamps

Index `guide_key`, `target_role`, and `status` for current-guide lookup. `current_version_id` identifies the exact version ordinary users may access. A guide with published history may return to `draft` when unpublished; the interface labels that state as **Unpublished** rather than implying that its history disappeared.

The target role is editable while `current_version_id` is null. After the first publication it is immutable, even if the guide is later unpublished or archived.

### `onboarding_guide_versions`

This stores the exact content and video metadata for each revision.

- `id`
- `onboarding_guide_id`
- `version_number`, increasing within the guide
- `title`
- `description`
- `video_disk`
- `video_path`
- `video_original_name`
- `video_mime_type`
- `video_size`
- `requires_recompletion`
- `published_at`, nullable for the current draft revision
- `created_by`, nullable administrator reference
- timestamps

Enforce a unique `(onboarding_guide_id, version_number)` constraint. Each guide has at most one unpublished draft version; the lifecycle service reuses that draft instead of creating competing revisions. A published row is immutable. Editing a published guide clones its current content into the next draft version; subsequent draft edits update that draft until publication.

The first published version always establishes onboarding for that guide. For later revisions, the administrator must explicitly choose **Informational update** or **Require users to complete this new version**. The form defaults to informational. Unpublishing and republishing the same version does not create a version or alter `requires_recompletion`.

### `user_onboarding_guide_states`

One row records a user's interaction with one exact version.

- `id`
- `user_id`
- `onboarding_guide_version_id`
- `dismissed_at`, nullable
- `completed_at`, nullable
- timestamps

Enforce a unique `(user_id, onboarding_guide_version_id)` constraint. Skip or Close may create the row with only `dismissed_at`; this is not a completion record. Completing later updates the same row with `completed_at` and preserves the earlier dismissal timestamp.

Guide and version deletion is not part of the application. Foreign keys protect version and state history. If the existing account-deletion policy removes a user, its normal privacy behavior may cascade that user's state rows; this feature does not weaken account-deletion handling to retain personal analytics.

## Version satisfaction rules

For a selected current version, the audience service finds the most recent version at or before it that required completion. That version is the current completion boundary.

- A completion on any version from that boundary through the current informational version is an effective completion.
- If there is no effective completion, a dismissal on any version in the same boundary is an effective dismissal.
- A required revision creates a new boundary, so older completion and dismissal no longer suppress its modal.
- Informational revisions do not create copied state rows. The original version and timestamp remain the evidence.
- Direct completion takes precedence over dismissal when both timestamps exist across the effective boundary.

This rule supports repeated informational and required revisions without a global user flag and without rewriting historical records.

## Server components and boundaries

### Lifecycle service

An `OnboardingGuideLifecycleService` owns draft creation, safe video replacement, publication, unpublication, and archival. Controllers remain thin and do not duplicate status rules.

Publication runs in a database transaction and locks guides for the same indexed `guide_key` and role. It verifies that `current_version_id` belongs to its guide, verifies the selected draft and private file, publishes that version, sets it as current, and supersedes any other published guide for that role/key. Superseded data and files remain intact. The administrator receives an explicit confirmation before another guide is superseded.

Publishing a separate guide aggregate is new onboarding and does not inherit state from the guide it supersedes. Administrators use a revision of the existing guide when prior completion or dismissal should remain effective.

Archival makes the guide unavailable without deleting versions, media, or states. Archived guides have no restore or permanent-delete action in this release. Unpublication returns the guide to a non-public state while keeping its current-version reference and all history.

### Audience service

An `OnboardingGuideAudienceService` is the single source for:

- resolving the current published guide for an explicit dashboard context;
- confirming the authenticated user can access that role context;
- deriving direct and effective completion or dismissal;
- deciding whether dashboard modal data is present;
- providing the Guide page status;
- calculating aggregate administrator statistics.

Learner and Instructor routes pass a fixed context to this service. No role, guide ID, or user ID supplied by browser form data is trusted. The two dashboard controllers add the resolved optional presentation to their existing view data; no global layout query or client-only detection is introduced.

The analytics eligibility query must match the existing dashboard gates. Focused parity tests protect this relationship so suspended, inactive, incomplete, or otherwise dashboard-ineligible accounts do not inflate the denominator.

### HTTP endpoints

Administrator routes live under the existing authenticated Admin route group and also require `manage onboarding guides`. They provide the index, create/edit draft forms, authorized preview, version history, publish, unpublish, and archive actions.

Learner and Instructor each receive a role-protected Guide page route in their existing route group. The current navigation links point only to those fixed-context routes.

Dismiss and complete endpoints accept a route-bound version but never a user ID. They authorize the authenticated user against the current published guide and its role before performing an idempotent `updateOrCreate` against the unique state row. A stale, unpublished, archived, historical, or wrong-role version cannot be updated.

The video endpoint accepts only a route-bound version. Authorized administrators may preview drafts and historical versions. Ordinary users may stream only the current version of their applicable published guide. Authorization precedes path lookup, and internal storage paths are never returned.

## Administrator experience

Add **Onboarding Guides** to the existing Admin navigation and show it only to users with the new permission. Backend middleware and FormRequest authorization enforce the same permission independently of navigation visibility.

The index follows the current Admin cards, filters, table, status badges, and row-action conventions. It shows title, target role, current version, status, last publication date, and current aggregate counts. Filters cover role and status. Actions are Create, Edit draft, Preview, History, Publish, Unpublish, and Archive as allowed by the guide's state.

The create/edit form contains title, description, target role, and video. Server validation remains authoritative. The browser mirrors the 100 MiB limit and accepted types and reuses the existing XHR upload-progress presentation: transferred bytes, percentage, saving state, disabled duplicate submission, and accessible status text.

Saving creates or updates a draft. Draft preview uses the authorized private-video route and normal `<video controls>`. Publication of a revision requires an explicit informational/required choice. Publication of the first version is necessarily required because no prior state exists.

History shows immutable versions, publication dates, the re-completion choice, file metadata, and direct aggregate event counts. It does not offer content edits or deletion. No named per-user roster or export is included.

## Dashboard modal

The shared Blade/Alpine modal component is rendered only when the audience service supplies a presentation on the Learner or Instructor dashboard landing page. No applicable guide produces no modal markup.

The modal uses a fixed product welcome message plus the database-backed title and description. It includes the authorized video player, a Start Watching control, Skip, a close control, and an explicit **I've completed the guide — Continue to Dashboard** action. Playback never starts with sound automatically and never marks completion.

Skip, Close, backdrop dismissal if enabled by the existing modal convention, and Escape all submit the same dismissal action. The modal closes immediately so persistence latency cannot gate dashboard access. If persistence fails, a non-blocking message explains that the guide may appear again; the client does not pretend the server saved it.

Completion closes the modal only after the server confirms the state. On failure, the modal shows a retryable inline error while Skip and Close remain available.

The component traps focus, labels the dialog and video controls, restores focus when closed, prevents background interaction while open, and keeps every action reachable on small screens. Mobile may use the available viewport but does not force browser video fullscreen.

## Role-specific Guide page

Learner and Instructor navigation each gain a permanent **Guide** item using their existing sidebar conventions. The page resolves only the current published guide for that fixed context.

The page shows the title, description, player, direct or inherited completion status, and the effective completion date when one exists. An informational revision may therefore show that the user's earlier completion remains valid. A dismissed but incomplete user can complete from this page. Replaying never clears or duplicates completion.

If there is no current published guide, the page shows a concise empty state. Historical, archived, unpublished, and wrong-role versions are not linked and remain inaccessible by manually entered URLs.

## Video storage and delivery

Use the existing Laravel `local` disk with UUID filenames under a dedicated `onboarding-guides/...` prefix. Store only references and trusted metadata in the database. The original name is sanitized for administrator display and is not used as a storage path.

Server validation accepts the repository's existing video set—MP4, MPEG, MOV, AVI, and WebM—through 102,400 KiB. Client checks improve feedback but do not replace Laravel validation. The deployed PHP request limits remain the existing 100M upload and 110M POST settings and require deployment verification.

Private playback follows the existing authorized `BinaryFileResponse` pattern with inline disposition, the stored MIME type, `X-Content-Type-Options: nosniff`, and private no-store caching. Symfony's file response supplies byte-range behavior needed for seeking; focused response tests and runtime browser verification must confirm that behavior in this deployment.

Draft replacement stores the new file first. If persistence fails, the new file is removed and the previous draft remains intact. After a successful update, only the superseded unpublished draft file is removed. Publishing never deletes an older published file, and archiving never performs storage cleanup.

## State changes and concurrency

- Publish, supersede, unpublish, and archive validate the current guide state and use transactions with row locking.
- Duplicate publish submissions return the already-established state or a clear conflict rather than creating another version.
- Dismiss and complete are idempotent under the database uniqueness constraint.
- Completing an already completed effective version does not create a duplicate record.
- A dismissal received after direct completion is a no-op.
- A stale browser submission for a version that is no longer current is rejected without changing history.
- Missing private media returns 404 and a user-facing playback error; it does not trigger data deletion or automatic publication changes.

## Analytics

For the current version, calculate against users who can presently access that role's dashboard:

- eligible users;
- effectively completed users;
- effectively dismissed but incomplete users;
- not completed users, including dismissed users;
- completion percentage, `completed / eligible`, with zero handled safely.

Effective counts honor the current completion boundary, so an informational revision does not make a previously valid completion appear lost. Direct per-version counts remain visible in administrator history. The calculation stays query-based and aggregate-only; no event warehouse, snapshot population, named roster, or export is introduced.

## Authorization and data protection

- Register `manage onboarding guides` in the canonical permission seeder.
- Add it to existing installations through the repository's forward-only permission-migration pattern and grant it incrementally to the Admin role. The rollback retains the permission and grant rather than unexpectedly revoking access.
- Keep Admin route middleware and action-level/FormRequest permission checks.
- Keep Learner and Instructor routes behind their current authentication, account, suspension, verification, profile, and role middleware.
- Resolve the acting user from the authenticated request for every state mutation.
- Return 403 or 404 for unauthorized guide, version, or media access without disclosing private paths.
- Retain CSRF protection and validate every state change on the server.
- Do not expose draft metadata or private file URLs in ordinary user responses.

## Failure handling

- Validation errors remain beside the corresponding Admin fields and preserve safe text inputs.
- Upload failure leaves the previous draft and file usable.
- Publication failure rolls back database state and leaves the previously published guide current.
- A missing or unreadable file prevents publication.
- Video playback failure leaves Skip, Close, and Guide-page navigation usable and provides a retry message.
- Completion failure never displays a completed state.
- Dismissal failure does not block dashboard access and warns that the modal may return.
- An empty applicable-guide result is a normal state, not an exception.

## Migration and data preservation

Create normal incremental migrations for the three new tables, indexes, foreign keys, and the permission grant. Inspect the active schema before implementation and use the project's configured database types and naming conventions.

No existing user, role, profile, video, or progress row is rewritten. No backfill is required: until an administrator publishes a guide, the feature is inert; after publication, absence of a state row means the eligible user may receive the modal.

These are forward-only preservation migrations. Their `down()` methods do not drop the new history tables, delete state, or revoke permission grants. Development and production databases must never be reset, wiped, truncated, or reseeded destructively for this feature.

## Implementation scope

The eventual change must reach:

- incremental schema and permission migration;
- onboarding guide, version, and state models and relationships;
- canonical permission and Admin-role seeders;
- Admin routes, navigation, controllers, FormRequests, lifecycle service, and views;
- the audience service and aggregate analytics queries;
- Learner and Instructor dashboard controllers and landing views;
- Learner and Instructor sidebars and role-specific Guide page wrappers;
- shared modal, guide content, upload-progress, and video-player presentation;
- authenticated video, dismissal, and completion endpoints;
- factories or focused fixtures and feature tests for every boundary below.

The existing public lesson-video pipeline remains unchanged. The open Admin Connector page and unrelated generated build assets are outside this feature.

## Tests and verification

### Domain and administration

- Schema constraints, relationships, casts, target-role immutability, and unique version numbering.
- Dedicated permission catalog/grant and hidden navigation for unauthorized administrators.
- Authorized draft creation/editing; unauthorized create, edit, publish, unpublish, archive, preview, and history denial.
- File type and 100 MiB boundaries, storage failure rollback, and safe unpublished-draft replacement.
- Immutable published versions, explicit informational/required revision choice, and no new version on unpublish/republish.
- Exactly one current published guide per role/key under repeated or conflicting publication attempts.
- Archive and supersede preservation of versions, media, and user states.

### Audience and state

- Existing and newly eligible Learners and Instructors resolve only their active-context guide.
- Instructor precedence and legitimate learner-context switching use the expected fixed route context.
- Inactive, suspended, incomplete, wrong-role, and otherwise dashboard-ineligible users are excluded.
- No guide, draft, unpublished, archived, or wrong-role guide produces no modal.
- Skip, Close, and duplicate dismissal remain incomplete and suppress only the effective boundary.
- Explicit completion is idempotent and tied to the authenticated user and exact version.
- Informational revisions inherit effective state without copied rows.
- Required revisions ignore earlier completion and dismissal while preserving history.
- Stale-version and forged-user submissions cannot alter state.

### Pages, media, and analytics

- Learner and Instructor navigation and Guide pages expose only the current applicable guide.
- Replay does not change or duplicate completion.
- Empty state, inherited completion date, and retryable media/error states render correctly.
- Ordinary users cannot stream drafts, historical versions, archived versions, or another role's video.
- Authorized Admin previews work without exposing storage paths.
- Inline security headers, MIME handling, and byte-range seeking are covered where the test runtime supports them.
- Eligible, completed, dismissed, incomplete, and percentage aggregates remain correct across informational and required revisions.

Run focused tests only against the configured isolated test database or transactional test infrastructure, never the development database as disposable state. Also run Blade view caching, relevant JavaScript tests, PHP formatting/static checks used by the repository, and `git diff --check`.

Browser verification remains a separate gate for focus trapping, Escape/close behavior, upload progress, playback and seeking, desktop/mobile layout, and real modal timing. Hostinger's effective PHP limits and production streaming behavior require deployment verification and must not be claimed from source or PHPUnit results.

## Explicit non-goals

- No tutorial sequencing, courses, prerequisites, quizzes, or certificates.
- No simultaneous multiple user-facing guides for one role in this release.
- No Connector, Guardian, Parent, Applicant, or Admin onboarding guides.
- No public video URLs, external video provider, transcoding, compression, adaptive streaming, or resumable upload.
- No custom or generated thumbnails.
- No automatic completion based on playback percentage or video end.
- No per-user analytics roster, export, notification campaign, or eligible-population snapshot.
- No hard delete, retention purge, or archive cleanup of published media/history.
- No unrelated dashboard, role, storage, or visual redesign.
