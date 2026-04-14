# Learner Module Overview, Review UX, Reporting, and Instructor Background Design

## Goal
Improve the learner module overview experience by clarifying page hierarchy, reducing UI weight, strengthening review trust signals, integrating reporting and messaging shortcuts, and aligning learner-facing instructor background details with the instructor profile structure.

## Approved Decisions
- 1A: Keep Enrollment and Progress first in the right rail.
- 2A: Keep Instructor Information and Module Information as separate cards.
- 3A: Add a message icon button in the instructor card that opens existing global popup chat with module context.
- 4C: Use icon hearts plus numeric value for rating clarity.
- 5C: Apply icon-heart rating treatment consistently across module review surfaces.
- 6A: Show learner display name and learner avatar in review entries.
- 7A: Respect current platform privacy and visibility behavior as-is.
- 8A: Replace inline write-review form with button-triggered modal.
- 9B: Use two report actions (Report Module and Report Instructor) in a modal, triggered by a report icon button.
- 10A: Remove standalone Module Assessment block and place quizzes inside curriculum hierarchy only, without extra detail panels.
- 11A: Keep learner instructor background route/view and upgrade layout to match instructor profile structure.
- 12A: Show learner-safe instructor sections (certifications, education, professional details).
- 13B: Add and update targeted learner tests plus existing feedback/report flow coverage.

## Architecture
Approach 2 (hybrid refactor with targeted partial extraction) is adopted.

This keeps the existing Laravel server-rendered architecture and current route ownership while reducing template complexity in the learner module page. The implementation favors additive Blade partials, reuse of existing controllers/services, and minimal backend query updates for review identity rendering and instructor profile section parity.

## Scope
In scope:
- Right-rail card reordering and UI hierarchy updates on learner module overview.
- Message icon shortcut in instructor card.
- Icon-based heart rating rendering across module review surfaces.
- Learner avatar and display name in review entries.
- Modal-based write review flow.
- Modal-based report flow with report icon trigger and dual action target shortcuts.
- Removal of standalone Module Assessment section.
- Quiz visibility in curriculum hierarchy only.
- Learner-facing instructor background structure parity with instructor profile page.
- Focused feature test updates.

Out of scope:
- New privacy preference model.
- New reporting statuses or moderation workflow changes.
- Database schema changes for this UI and structure rollout.
- API-first or SPA conversion.

## UX and Layout Design
### Learner Module Overview
- Keep current left-primary and right-rail layout.
- Right-rail card order:
  1. Enrollment and Progress card.
  2. Instructor Information card.
  3. Module Information card.
  4. Learner Reviews card.

### Instructor Information Card
- Keep avatar, display name, and short background summary.
- Keep View Full Background action.
- Add icon-only message button with:
  - visible tooltip
  - aria-label
  - existing open-global-chat event dispatch
  - module context payload (conversation_type: module_chat, module_id)

### Report Action UX
- Add icon-only report button in right-rail action area (near instructor/review actions).
- On click, open report modal.
- Inside modal top actions:
  - Report Module (preselect target_type=module, target_id=module id)
  - Report Instructor (preselect target_type=instructor, target_id=creator id)
- Keep reason and details fields, validation, and submission endpoint unchanged.
- Display active-report state warning blocks in modal context before submit.

### Learner Reviews Card
- Keep average rating and total count.
- Show latest reviews with:
  - learner avatar (photo fallback to initials)
  - learner display name
  - icon-heart + numeric rating
  - review text excerpt
- Replace inline form with Write Review or Update Review button.
- Button opens review modal with rating and rich-text review fields.

### Full Reviews Page
- Keep existing filters and pagination.
- Replace text heart labels with icon-heart rendering.
- Show learner avatar with display name for each review entry.

### Curriculum and Quizzes
- Remove standalone Module Assessment section.
- Render quizzes in curriculum hierarchy:
  - lesson quiz markers/items under lesson rows
  - final quiz marker/item at module level if applicable
- Show only hierarchy-level quiz indicators/status placement, no extra quiz details panel.

### Instructor Background Page (Learner View)
- Keep learner route/controller contract.
- Upgrade section structure to mirror instructor profile display style:
  - hero identity block
  - professional background
  - certifications
  - educational background
  - selected profile metadata relevant for learners
- Respect learner-safe boundaries and omit sensitive/private internals.

## View and File Strategy
Primary modified views:
- resources/views/learner/modules/show.blade.php
- resources/views/learner/modules/reviews.blade.php
- resources/views/learner/instructors/show.blade.php

Targeted partial extraction for maintainability:
- resources/views/learner/modules/partials/instructor-info-card.blade.php
- resources/views/learner/modules/partials/module-info-card.blade.php
- resources/views/learner/modules/partials/reviews-card.blade.php
- resources/views/learner/modules/partials/review-modal.blade.php
- resources/views/learner/modules/partials/report-modal.blade.php
- resources/views/components/reviews/heart-rating.blade.php

Partial extraction remains limited to keep delivery low-risk and avoid over-componentization.

## Data and Controller Adjustments
### ModuleController
- Keep existing orchestration responsibility.
- Update recent review loading to include learner profile data needed for avatar rendering.
- Keep feedback/review eligibility and report status logic unchanged.

### ModuleReviewPageController
- Update review query eager loading to include learner profile data for avatar rendering.
- Keep filters, sorting, and pagination behavior unchanged.

### InstructorProfileController (Learner)
- Keep role guard and route ownership unchanged.
- Expand read model passed to view for certifications and educational sections with learner-safe fields.
- Align rendering shape with instructor profile structure while avoiding privileged-only metadata.

## Safety, Privacy, and Accessibility
- Safety and privacy:
  - Follow existing role and visibility policies.
  - No new identity exposure beyond currently visible learner display data.
  - Report workflow remains explicit and intentional.
- Accessibility:
  - icon buttons include aria-label/title
  - keyboard and focus support for modals
  - heart ratings include numeric text for non-visual clarity
  - empty states and fallback text remain explicit

## Error Handling
- Preserve server-side validation and sanitized rich text behavior.
- Reopen relevant modal after validation errors by using old input and modal state flags.
- Keep flash success/error messaging behavior from existing controllers.
- Ensure report modal safely handles missing instructor creator references.

## Testing Strategy
Add/update focused feature tests:
- Module overview right-rail hierarchy and section presence/absence.
- Standalone Module Assessment section is removed.
- Curriculum displays quiz hierarchy indicators.
- Reviews show learner avatar/display name and icon-heart rating output.
- Review modal trigger and server-post flow continue to work.
- Report icon trigger and dual target preselection continue to submit to existing endpoint.
- Learner instructor background page renders certifications, education, and professional sections.

Retain and rerun existing core flows:
- Module feedback submission and edit behavior.
- Content report creation and active-report handling.

## Rollout Notes
- No schema migration is required.
- Route ownership remains in existing learner web routes.
- Chat integration reuses current global popup event contract.
- Implementation is additive and reversible at view/controller level.
