# Admin Content Governance Design

**Date:** 2026-03-27  
**Status:** Implemented  
**Approach:** Admin-governed content lifecycle built on top of existing instructor authoring flows

## Goal

Expand the admin side into the platform's content governance center so admins can:
- review instructor-created module packages before publication
- approve or reject submissions with feedback
- keep learner-visible content limited to approved versions only
- create and publish admin-owned modules directly
- prepare the system for future admin-side role-based access control

## Problem Statement

The current platform has a mature instructor-side content system, but publication control is not yet centered in the admin panel. The target operating model is:

- instructors create and maintain educational content
- admins review the full content package before it becomes visible on the platform
- rejected submissions return to the instructor for revision and resubmission
- admins can also author platform-owned content that is credited to admins

This means the admin panel should not just duplicate instructor CRUD. It should become the publication authority for platform content.

## Locked Decisions

### 1. Governance Model
- Instructors remain content authors for instructor-owned content.
- Admins become the publication gatekeepers for instructor-owned content.
- Admins can also create and publish admin-owned content directly.

### 2. Review Unit
- Review happens at the full package level:
  - module
  - lessons
  - lesson topics
  - quiz content and related assessment structure
- Partial approvals are out of scope for this phase.

### 3. Revision Rule
- If an already approved module is edited by an instructor, the updated version must go back into admin review before learners can see the revision.
- The currently approved version remains learner-visible until the new revision is approved.

### 4. Rejection Rule
- Rejected instructor submissions return to a `needs_revision` state.
- The instructor edits the same content package and resubmits it.
- Rejection must include admin feedback.

### 5. Attribution Rule
- Instructor-authored modules remain credited to the instructor.
- Admin-authored modules remain credited to the admin author.
- Admin approval does not transfer ownership of instructor-created content.

### 6. Publication Authority
- Learners only see approved published versions.
- Drafts, pending submissions, and rejected revisions are never learner-visible.

### 7. Future RBAC Direction
- This phase assumes a broad admin role.
- The workflow must be structured so future RBAC can later separate permissions such as:
  - review submissions
  - approve or reject publication
  - create admin-owned content
  - edit platform-owned content
  - view audit history

## Recommended Approach

Build an admin-governed content lifecycle on top of the existing instructor content domain instead of replacing the instructor panel or merging both sides into a new shared system.

This approach is preferred because it:
- matches the requested workflow exactly
- preserves the mature instructor authoring experience
- keeps admin control over learner-facing publication
- avoids a risky rewrite of instructor CRUD
- creates clean authorization seams for future admin RBAC

## Alternatives Considered

### Option 1. Admin-governed content lifecycle on existing instructor domain
- **Chosen**
- Instructors author content.
- Admins review, approve, reject, and publish.
- Admins can also author admin-owned content directly.

**Pros**
- Best fit for requested workflow
- Lowest disruption to current instructor-side maturity
- Easy to reason about attribution and publication authority
- Future-ready for granular admin permissions

**Cons**
- Requires new review and revision-state handling
- Needs careful protection against unapproved live overwrites

### Option 2. Shared content backend used by both admin and instructor panels
- Both roles use mostly the same content management flows with permission-gated actions.

**Pros**
- Lower duplication in the long term
- Potentially cleaner if content governance expands a lot later

**Cons**
- Bigger refactor now
- Higher regression risk across routes, controllers, and views
- Not necessary for the current admin-control requirement

### Option 3. Lightweight approval overlay only
- Keep instructor tools mostly untouched and add a minimal admin approval queue.

**Pros**
- Fastest initial delivery
- Smaller short-term change set

**Cons**
- Tends to create a split system
- Weak fit for future full admin control
- Harder to extend into admin-owned publishing and RBAC later

## Architecture

Use the existing instructor content domain as the authoring foundation, then add an admin-owned publication workflow above it.

The system should separate:
- **authoring state**
- **publication state**

This allows an instructor to keep working on a new revision without replacing the currently approved learner-facing version until admin approval happens.

### Architectural Principles
- Controllers stay thin.
- Business logic belongs in services.
- Learner-facing content queries should resolve only approved published versions.
- Admin review actions should be transactional and auditable.
- Existing route ownership remains respected:
  - instructor routes in `routes/instructor.php`
  - admin routes in `routes/admin.php`
  - learner visibility remains in learner/public route flows

## Core Components

### Admin Content Review Queue
An admin-facing queue that lists instructor submissions awaiting review, grouped by lifecycle status.

Expected statuses:
- `draft`
- `in_review`
- `needs_revision`
- `approved`
- `archived` or equivalent non-active lifecycle state if needed later

### Submission Detail View
An admin review screen that displays the entire package:
- module metadata
- lessons
- topics
- quiz structure
- author attribution
- submission timestamps
- latest revision notes

### Review Actions
Admins can:
- approve a submission
- reject a submission with required feedback
- inspect prior review history

### Admin Authoring Flow
Admins can create platform-owned modules directly from the admin panel. These modules are credited to the admin author and do not require the instructor review loop, though publication events should still be logged.

### Instructor Submission Status
Instructors need visibility into:
- current submission state
- rejection feedback
- resubmission availability
- whether a newer revision is pending while an older approved version remains live

### Audit and History Tracking
The system should store:
- actor
- action
- timestamp
- notes or rejection reason
- affected content package or revision

This supports governance today and future RBAC/compliance needs later.

## Data Model Direction

This design does not lock the exact schema yet, but it does lock the domain responsibilities the schema must support.

The model needs to distinguish:
- original author
- current learner-visible published version
- pending revision under review
- admin approver or rejecting admin

### Required Data Responsibilities
- `created_by` or equivalent author reference remains the original content owner
- `published_by_admin_id` or equivalent stores the approving admin for instructor-owned publication
- a submission or revision record tracks lifecycle state and feedback
- learner-facing queries must resolve only approved published content

### Attribution Rules
- Instructor-created modules remain instructor-owned after approval
- Admin-created modules remain admin-owned
- Admin approval should never silently transfer authorship

## Content Lifecycle

### Instructor-Owned Content
1. Instructor creates or edits a module package.
2. The package remains in authoring state until submission.
3. Instructor submits the package for review.
4. Admin reviews the full package.
5. Admin either:
   - approves it for platform publication, or
   - rejects it with feedback
6. If rejected:
   - the package returns to `needs_revision`
   - the instructor edits and resubmits
7. If the content was already live before the revision:
   - the currently approved version stays visible
   - the new revision remains hidden until approved

### Admin-Owned Content
1. Admin creates content directly in the admin panel.
2. The content is credited to the admin author.
3. Publication can happen from the admin side without the instructor review loop.
4. Publish actions are still logged for traceability.

## Learner Visibility Rules

Learners must only see approved published content.

Learners must never see:
- drafts
- pending reviews
- rejected submissions
- unapproved revisions

This rule should be enforced in the service/query layer, not only in views.

## Data Flow

### Instructor Flow
- Create or edit full module package in the instructor panel
- Submit package for review
- Receive approval or revision feedback
- Revise and resubmit if rejected

### Admin Flow
- Browse pending submissions
- Inspect full content package
- Approve or reject with notes
- Create and publish admin-owned content
- Review history and audit events

### Learner Flow
- Browse and consume only approved published versions
- Never encounter moderation-state content

## Error Handling and Risk Controls

### Risk 1. Unapproved edits overwrite live content
**Mitigation**
- Separate active published version from pending revision
- Do not let instructor edits directly replace learner-visible approved content

### Risk 2. Partial approval creates broken learner experiences
**Mitigation**
- Review and publish at the full package level
- Avoid lesson-by-lesson approval in this phase

### Risk 3. Rejections without actionable feedback
**Mitigation**
- Make rejection reason required
- Surface feedback clearly in instructor resubmission flows

### Risk 4. Admin actions become untraceable
**Mitigation**
- Log approval, rejection, and publication events with actor and notes

### Risk 5. Future RBAC becomes hard to add
**Mitigation**
- Introduce authorization boundaries around review, approval, admin authoring, and audit visibility now

## UI and UX Direction

### Admin Side
The admin panel becomes the governance hub for content operations.

Key views:
- review queue
- submission detail view
- review history
- admin-owned content authoring and management

### Instructor Side
The instructor experience should keep creation simple while making moderation status explicit.

Key additions:
- submission status badge
- feedback area for rejected content
- resubmit action
- clear indication when a new revision is pending while an older approved version remains live

### Learner Side
Learners should not need to understand the moderation model. They simply interact with stable, approved content.

## Authorization Direction

This phase assumes current admin access is broad, but the feature should be implemented with future permission seams in mind.

Likely future permission groups:
- content review access
- content approval and rejection authority
- admin direct publishing authority
- admin content authoring authority
- audit log visibility

The exact RBAC matrix is out of scope for this design, but the structure must support it cleanly.

## Testing Strategy

### Feature Coverage
- Instructor can submit a full module package for review
- Admin can view pending submissions
- Admin can reject with feedback
- Rejected package returns to `needs_revision`
- Instructor can revise and resubmit the same package
- Admin can approve a submission
- Approval makes the correct version learner-visible
- Admin-created content is credited correctly and can be published

### Visibility and Regression Coverage
- Learners cannot access drafts or pending revisions
- Learners continue to see the previously approved version until a revision is approved
- Existing instructor CRUD continues to work for authoring
- Existing learner module browsing continues to work with approved content resolution

### Authorization Coverage
- Non-admin users cannot perform admin review actions
- Instructor users cannot bypass the review workflow for instructor-owned content
- Admin-authored content paths are restricted appropriately

### Audit Coverage
- Approval actions are logged
- Rejection actions are logged with reasons
- Admin publication actions are logged

## Acceptance Criteria

- Admin panel can review instructor-created module packages before publication
- Review happens for the full package, not partial content fragments
- Admins can approve or reject with required feedback on rejection
- Rejected packages can be revised and resubmitted by the same instructor
- Instructor revisions do not overwrite the currently approved learner-visible version until approved
- Admins can create and publish admin-owned modules credited to admins
- Instructor-created content remains credited to the instructor after admin approval
- Learners only see approved published versions
- The design leaves clean boundaries for future admin RBAC

## Implementation Notes

The v1 rollout now ships with a module-level governance model backed by:
- `modules.content_owner_type`
- `modules.current_review_status`
- `modules.published_revision_id`
- `modules.published_by_admin_id`
- `module_revisions`
- `module_review_requests`

### Implemented Lifecycle States
- `draft` for instructor-owned authoring before submission
- `in_review` for pending admin decisions
- `needs_revision` for rejected submissions with required feedback
- `approved` for learner-visible published revisions

### Implemented Service Boundaries
- `App\Services\ContentGovernanceService::submitForReview()` snapshots the current module package and opens a review request.
- `App\Services\ContentGovernanceService::approveReview()` marks the revision and review request approved, updates the module's published revision pointer, and logs the admin action.
- `App\Services\ContentGovernanceService::rejectReview()` moves the package to `needs_revision`, stores feedback, and logs the admin action.
- `App\Services\ContentGovernanceService::createAdminOwnedModule()` creates admin-owned modules with an approved revision snapshot for learner read consistency.

### Implemented Entry Points
- Instructor submission routes:
  - `instructor.modules.review.submit`
  - `instructor.modules.review.resubmit`
- Admin review routes:
  - `admin.content-reviews.index`
  - `admin.content-reviews.show`
  - `admin.content-reviews.approve`
  - `admin.content-reviews.reject`
- Admin authoring routes:
  - `admin.modules.*`

### Learner Visibility Behavior
- Learner-facing module reads now resolve through the approved published revision when one exists.
- Instructor-owned draft or pending edits do not replace the currently approved learner-visible version.
- Admin-owned modules also create an approved revision snapshot so learner reads stay consistent across ownership types.

### Rollout Notes
- This rollout intentionally keeps moderation scoped to the module package level rather than introducing per-lesson or per-topic review records.
- Existing instructor authoring remains in place, but instructor module creation and updates now default to non-live governance states.
- Admin activity logging now records `content_reviews.approve`, `content_reviews.reject`, and `admin_modules.publish`.
- Future RBAC can split review access, approval authority, admin authoring, and audit visibility without changing the learner visibility model.

## Out of Scope

- Full admin RBAC matrix implementation
- Marketplace or monetization behavior for instructors
- Partial approval of individual lessons or topics
- Replacing the instructor panel with a merged shared panel
- Rewriting the entire content domain from scratch

## Next Step

Create a detailed implementation plan that breaks this feature into schema, services, controllers, routes, views, tests, and verification tasks while preserving the existing service-layer architecture and route ownership.
