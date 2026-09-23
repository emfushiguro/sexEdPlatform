# Learning Path

## Problem

Conscious Connections already provides modules, lessons, topics, interactive
activities, checkpoints, quizzes, enrollment, payment, learner-category
eligibility, certificates, and progress tracking. Learners currently discover
and resume modules through the dashboard and module browser, but those pages do
not communicate an intentional curriculum sequence across several modules.

Learning Path adds an administrator-curated sequence over existing modules. It
must answer where the learner is, what is complete, what is in progress, and
what should come next without becoming a second source of truth for content,
access, enrollment, or progress.

The visual concept may evoke a guided journey, but it must use the existing
Conscious Connections design system. It must not reproduce Duolingo branding,
layout, shapes, assets, mascot, or gamification.

## Goals

- Let authorized administrators create, edit, publish, archive, restore, and
  preview Learning Paths.
- Let administrators assign existing learner-visible modules and arrange them
  in a stable order.
- Provide learner-facing path discovery and an individual journey view.
- Show completed, in-progress, recommended, available, and unavailable module
  states using existing learner data.
- Show module-based overall path progress and topic-based individual module
  progress with the existing lesson fallback.
- Reuse the existing module details, enrollment, purchase, lesson, quiz, and
  completion flows.
- Provide the same pointer, touch, keyboard, and screen-reader reorder
  interaction used by the learner Sequencing activity.
- Keep queries bounded and avoid database access from Blade loops.
- Preserve existing module data and all learner history when a module is
  removed from or reordered within a path.

## Non-goals

- A second enrollment, completion, progress, resume, or authorization system.
- Per-learner path assignment.
- Optional or weighted modules in V1.
- Prerequisite dependency graphs or arbitrary locking.
- Branching paths, AI recommendations, new gamification, leaderboards, or a
  mascot.
- Replacing the learner dashboard, module browser, or module details page.
- Instructor-authored Learning Paths.
- Scheduled path publication or a path review workflow.
- React, Vue, a graph editor, or a new drag-and-drop dependency.

## Existing Architecture

- `Module::learnerVisible()` is the canonical publication/governance boundary.
- `Module::forAge()` and `module_learner_categories` implement learner-category
  eligibility for `kids`, `teens`, and `adults`.
- `ModuleEnrollment` records approval, pending/rejected states, completion time,
  and completion percentage.
- `UserProgress`, `LessonTopicProgress`, `InteractiveCheckpointProgress`,
  `InteractiveActivityProgress`, and `QuizAttempt` remain the learner-history
  sources of truth.
- `LearnerModuleCompletionService` already normalizes completed topic state
  across ordinary topics, checkpoints, and interactive activities.
- The learner dashboard already resolves the first incomplete published lesson
  for its Continue Learning action.
- The learner `ModuleController` preserves module visibility for approved
  learners when a module is later deactivated and delegates payment and
  enrollment decisions to existing services.
- Admin content management uses `auth`, `role:admin`, module permissions,
  Blade, Alpine.js, Tailwind CSS, inline SVG icons, session flashes, and
  Toastify.
- Learner Sequencing uses `pointer-reorder.js` for pointer/touch drag, keyboard
  movement, edge scrolling, and reorder-session state.

Learning Path extends these paths. It does not replace them.

## Accepted Product Decisions

- V1 supports multiple published paths.
- Learners enter through a separate Learning Paths sidebar item and path index.
- Paths target one or more existing learner categories.
- Admins may assign learner-visible platform- or instructor-owned modules.
- Every assigned module is required; V1 has no `is_required` field.
- Paths use `draft`, `published`, and `archived` statuses.
- Archive replaces hard deletion in the management UI; an archived path can be
  restored to draft.
- Module nodes link to the existing Module Details page rather than opening a
  new preview modal.
- The visual path is itself a semantic ordered list; there is no duplicated
  Path/List renderer.
- Admin preview uses the learner components with neutral progress and no
  learner impersonation.

## Data Model and Migration

Create all three normalized tables in one incremental migration.

### `learning_paths`

- `id`
- `title`, up to 255 characters
- `description`, text
- nullable `thumbnail`
- `status`, up to 20 characters, defaulting to `draft`
- nullable `created_by`, foreign keyed to `users` with `nullOnDelete`
- timestamps
- index on `status`

The UI never hard-deletes a path. A nullable creator preserves curated paths if
an administrator account is later removed.

### `learning_path_modules`

- `id`
- `learning_path_id`, foreign keyed with cascade delete
- `module_id`, foreign keyed with cascade delete
- unsigned `position`
- timestamps
- unique `(learning_path_id, module_id)`
- unique `(learning_path_id, position)`
- index `(module_id, learning_path_id)`

Cascade behavior affects only membership rows. Module removal from a path never
deletes the module. Module soft deletion retains membership; only an explicit
permanent module deletion removes orphaned membership.

### `learning_path_learner_categories`

- `id`
- `learning_path_id`, foreign keyed with cascade delete
- `category`, up to 16 characters
- timestamps
- unique `(learning_path_id, category)`
- index `(category, learning_path_id)`

Category values reuse `kids`, `teens`, and `adults`. No parallel category
vocabulary is introduced.

The migration neither backfills a generated default path nor changes existing
module order. No database reset, destructive reseed, or development-data
replacement is part of this feature.

## Models and Relationships

`LearningPath` owns ordered `LearningPathModule` rows, related modules,
learner-category rows, and its creator. It exposes status constants and focused
`published()` and `forLearnerCategory()` scopes.

`LearningPathModule` belongs to one path and one existing `Module`.
`LearningPathLearnerCategory` belongs to one path. `Module` gains only the
inverse path-membership relationship.

Path ordering is independent from `modules.order`. A module may appear once in
each path and in any number of different paths.

## Authorization

Admin routes remain inside the existing `auth` and `role:admin` group. A
`LearningPathPolicy` reuses content permissions:

- list and preview: `view modules`;
- create: `create modules`;
- update, archive, and restore: `edit modules`;
- publish: `publish modules`.

The existing super-admin Gate behavior continues to apply. Instructors receive
no Learning Path authoring routes.

Learner routes remain inside the authenticated, profile-completed learner
group. Learner queries resolve only published paths that contain the learner's
existing category. Direct access to a draft, archived, or category-ineligible
path returns 404 instead of revealing its metadata.

Every module node is filtered through existing module access behavior. A path
never grants module access.

## Validation and Publication Rules

All paths require a title, description, and at least one valid learner
category. A draft may contain no modules so administrators can save work in
progress. Publishing requires at least one module.

Submitted module IDs must be distinct and must resolve to modules that:

- pass `Module::learnerVisible()`;
- support every selected path category;
- have not been soft-deleted.

The server repeats these checks even when the builder already filtered its
candidate list. Unknown, duplicate, unpublished, deleted, or category-mismatched
module IDs are rejected. Positions are derived from submitted array order, not
trusted numeric positions from the browser.

Thumbnail validation and storage follow the existing module-image convention,
using the public disk under `learning-paths/`. A thumbnail is optional.

## Admin Management Interface

The admin Learning Contents navigation gains a Learning Paths entry. A
dedicated index follows existing admin page, card, filter, badge, and pagination
patterns. It shows title, category badges, status, module count, creator, edit,
preview, archive, and restore actions.

Create and edit use one shared builder. Fields are title, description,
thumbnail, categories, status, a searchable eligible-module catalog, and an
ordered selected-module list. Candidate data contains only ID, title,
thumbnail URL, owner label, and category keys; it does not load lesson or topic
content.

Changing categories never silently removes a selected module. The builder
marks a mismatch and server validation prevents saving invalid membership.
Validation failure reconstructs the selected IDs and order from old input.

Publishing, archiving, restoring, adding, removing, and reordering use the
existing session flash and Toastify conventions.

## Sequencing-style Module Ordering

`learning-path-builder.js` imports `createReorderSession`, `moveAt`,
`keyboardDestination`, and `edgeScrollDelta` from `pointer-reorder.js`. It uses
a path-specific Alpine controller rather than coupling path authoring to
`createSequencingActivity()`, whose answer checking, practice, audio, revision,
and learner-progress behavior do not apply.

The selected-module list matches the Sequencing interaction contract:

- a 44-by-44-pixel drag handle;
- pointer and touch dragging;
- source-row emphasis, insertion marker, and floating overlay;
- viewport-edge auto-scroll;
- cancellation when dropped outside a valid target;
- Space or Enter to pick up and drop;
- Arrow Up/Down, Home, and End to choose a position;
- Escape to cancel;
- hidden instructions and polite live position announcements.

The controller maintains the ordered module-ID array and renders hidden form
inputs from that order. Reordering does not autosave. The administrator must
submit Save Path, so path fields and membership change atomically.

## Authoring Persistence

`LearningPathAuthoringService` owns the database transaction. It saves path
fields, synchronizes categories, removes only memberships no longer submitted,
retains existing membership rows, inserts new memberships, and applies final
contiguous positions.

To satisfy the unique position constraint while swapping existing rows, the
service first offsets retained positions beyond the final range, then applies
the submitted positions. Reordering therefore changes positions only; it does
not delete and recreate retained memberships.

Removing or reordering membership never writes to modules, lessons, topics,
activities, quizzes, enrollments, purchases, progress, attempts, or
certificates. Any failure rolls back the entire database mutation.

## Learner Path Discovery

Add these routes inside the learner group:

- `GET /learn/learning-paths`
- `GET /learn/learning-paths/{learningPath}`

The path index displays eligible published paths with thumbnail, title,
description, completed/total module count, overall percentage, and Continue or
View action. Results are paginated. An empty state links to the existing module
browser.

The learner sidebar gains Learning Paths as a separate destination. My Modules
and the current dashboard remain intact.

## Progress Presentation Service

`LearningPathPresentationService` is the single read-only path presenter. It
provides batched index summaries, a learner detail payload, and a neutral admin
preview payload. Controllers pass prepared arrays to Blade; views perform no
queries.

Canonical module completion is an approved enrollment with either
`completed_at` set or `completion_percentage >= 100`. The service does not mark
a module complete itself.

For an incomplete approved module, individual percentage is:

1. completed topics divided by published instructional topics when topics
   exist, using one batched `LearnerModuleCompletionService::completedTopicIds()`
   call for ordinary topics, checkpoints, and interactive activities;
2. completed lessons divided by published lessons when no topics exist.

A canonical completed module always displays 100 percent. A not-started or
unapproved module displays 0 percent.

Overall progress is completed actionable modules divided by actionable
modules. Completed historical modules remain counted. Currently unavailable
incomplete nodes remain visible when existing access rules require historical
visibility, but are excluded from the actionable denominator. A zero
denominator returns 0 percent.

## Module State and Recommendation Rules

State priority is:

1. completed;
2. unavailable;
3. in progress;
4. recommended;
5. available.

Completed means canonical enrollment completion. In progress means an approved
enrollment with calculated progress greater than zero. Recommended is the
first actionable incomplete module when no in-progress module exists. Available
means learner-visible and accessible but not started.

The first in-progress module by path order is the current module. Other
in-progress modules remain visibly in progress. If no module is in progress,
the first actionable incomplete module becomes Recommended Next. Completed
modules may appear later in the path if a learner explored out of order.

No node is marked locked because the current platform has no legitimate
module-prerequisite system.

Paid modules without a completed purchase remain available and link to the
existing details/payment flow. Pending or rejected enrollment and an incomplete
deactivated module use unavailable labels and reasons rather than bypassing
access controls.

## Continue Learning

The path detail header includes one primary Continue Learning card. It targets
the current module or, when none is in progress, the recommended module.

- Approved enrollment plus an incomplete published lesson links to the first
  incomplete lesson.
- Completed lessons with remaining quiz or module-completion work link to
  Module Details.
- A learner not yet enrolled links to Module Details and the existing
  enrollment flow.
- A paid, unpurchased module links to Module Details and the existing purchase
  flow.
- A completed path displays completion copy and a review action instead of a
  false next recommendation.

No new resume pointer or auto-enrollment behavior is introduced.

## Learner Path Interface

The path page contains title, description, overall progress, completed/total
count, Continue Learning card, and a semantic ordered list of module nodes.

Each node displays ordinal position, title, explicit state label, state icon,
progress percentage, completed/total lessons, and a context-appropriate action.
Selecting a node opens the existing Module Details page. The feature does not
duplicate full module descriptions, objectives, enrollment, or payment UI in a
modal.

Visual treatment uses existing purple and indigo gradients, emerald completion,
amber recommendation emphasis, neutral available states, explicit unavailable
copy, existing typography, rounded cards, shadows, spacing, and dark mode.

Desktop uses a centered alternating route with gentle horizontal offsets.
Mobile uses a compact vertical route with smaller controlled offsets and no
horizontal scrolling. Long titles wrap inside bounded cards.

Normal HTML contains every interactive and informational element. Small native
SVG segments connect adjacent nodes and remain `aria-hidden`; SVG never handles
navigation or essential state.

## Accessibility and Motion

The visual path is one semantic `<ol>` of `<li>` module cards, so visual and
assistive representations cannot drift. Node status never relies on color.
Links and drag handles expose visible focus, meaningful labels, and at least
44-by-44-pixel targets. Progress elements expose labels, minimum, maximum, and
current values.

Admin drag ordering supplies screen-reader instructions, `aria-pressed`, item
position metadata, and live pickup/move/drop/cancel announcements. Learner
nodes use headings and explicit status text.

Hover, focus, current-node emphasis, connector entry, and progress changes use
small CSS transitions. `prefers-reduced-motion: reduce` disables nonessential
movement and transforms.

## Unavailable and Changing Content

If an assigned module later becomes unpublished, deleted, or category
incompatible, the admin edit page shows a warning.

For learners without approved historical access, that node is omitted and does
not enter the progress denominator. For approved learners, current module rules
continue to decide whether the module can be reviewed. Completed history remains
completed. An incomplete deactivated module appears unavailable and cannot
become the primary continuation target.

Archiving a path changes only path status. It does not delete membership or any
learner data. Restoring an archived path returns it to draft so an administrator
must explicitly republish it.

## Admin Preview

Preview reuses the learner path components and presentation shape without
impersonating a learner. It shows an admin-only preview banner, zero overall
progress, the first module as Recommended Next, later modules as Available, and
no enrollment, payment, or learning mutation actions.

## Query and Performance Design

The presenter batches path memberships, modules, published lesson counts and
IDs, minimal topic IDs/types, learner enrollments, completed lesson progress,
topic/activity/checkpoint completion, and completed purchases only when action
copy needs purchase state.

It does not load lesson bodies, topic media, quiz questions, activity
configuration, or complete learner history. Blade components receive prepared
data and issue no queries. Path indexes paginate; path detail remains linear in
the number of summary nodes.

No user-specific cache is introduced in V1. Correct set-based queries are
sufficient and avoid invalidation complexity.

## Error Handling

Validation errors use the existing admin form and toast patterns. Invalid
module IDs, duplicates, incomplete published paths, and category mismatches are
rejected before mutation. A database failure rolls back path, category, and
membership changes together.

Learner access to a non-visible path returns 404. A later-unavailable module is
omitted or labelled according to existing approved-access rules. Missing media
uses the existing thumbnail fallback. No Learning Path-specific notification
system is added.

## Testing and Verification

Automated coverage will include:

- schema constraints and model relationships;
- admin create, edit, publish, archive, restore, and preview;
- title, description, category, status, module, uniqueness, publication, and
  category-compatibility validation;
- platform- and instructor-owned module selection;
- pointer/touch reorder state, keyboard pickup/move/drop/cancel, edge-scroll
  calculation reuse, announcements, and submitted ordering;
- persisted order after reopening edit;
- module removal preserving module, enrollment, purchase, progress, quiz
  attempt, activity, and certificate records;
- reorder changing membership positions only;
- admin permission failures and learner authoring denial;
- no path, one path, multiple paths, category filtering, and direct-access
  denial;
- not-started, in-progress, recommended, completed, all-complete, unavailable,
  pending/rejected, paid, and deactivated states;
- existing learner progress and no-progress cases;
- overall progress, module progress, current selection, recommendation, and
  Continue Learning destinations;
- semantic list, state labels, progress accessibility, drag instructions,
  focus hooks, and reduced-motion rules;
- existing module browsing, details, enrollment, payment, lesson, topic,
  activity, checkpoint, quiz, certificate, progress, governance, and RBAC
  regressions;
- JavaScript tests, targeted PHPUnit tests, production Vite build, formatting,
  and whitespace verification.

Manual browser QA will cover mobile, tablet, and desktop widths; long titles;
long paths; small viewport heights; large displays; pointer, touch, and keyboard
reordering; dark mode; focus visibility; and reduced motion. Testing will use
isolated test data and normal application flows. It will never reset or treat
the development database as disposable.

## Expected Change Surface

The implementation adds one migration, three models, one policy, admin and
learner controllers, one validation request, focused authoring and presentation
services, admin and learner Blade views/components, one path-builder JavaScript
module, and focused PHP/JavaScript tests. Existing app bootstrap, routes,
navigation, component CSS, and `Module` receive small integrations only.

Unrelated learner dashboard, module, progress, authorization, instructor, and
content-authoring code remains unchanged.
