# Instructor Panel Refinement Design

Date: 2026-03-19
Branch Context: feat/admin-panel-integration
Approach: A (Domain-first backend + reusable UI primitives + page-level redesign)
Release Shape: One large phased release with internal phases and quality gates per phase

## 1. Scope and Goals

### In Scope
- Instructor learners management converted to view-only with ownership-based visibility
- Module, lesson, quiz activation lifecycle (active/inactive) in create and edit flows
- Module list/detail UX cleanup and rejection-reason workflow with notifications
- Search routing fixes and global search scope restriction to instructor dashboard
- Instructor-wide table numbering, icon/font visibility, and UI consistency updates
- Legacy edit pages for module/lesson/quiz replaced by modal-based editing UX
- Image library redesign aligned to current instructor visual language
- Instructor notifications expansion
- Assessment logs expansion with selected metrics and flags
- Sidebar visual and interaction refinement for instructor panel
- Delete confirmation modal coverage for all instructor-side destructive actions

### Out of Scope
- Multi-channel notifications beyond in-app database notifications
- Cross-role sidebar redesign (admin and learner are not included)
- Feature flags (direct replacement strategy)

### Success Criteria
- All workflow fixes operate correctly with ownership-safe data access
- Search consistently routes to details/information pages, not edit pages
- Learners receive enrollment decision notifications with rejection details and instructor name
- Instructor UI consistency is materially improved across targeted pages
- Tests and manual QA pass per phase gate

## 2. Product Behavior Contract

### Instructor Learners Management
- Keep only View action in learners table
- Remove Add and Edit actions
- Show only learners enrolled in modules owned by current instructor
- Table columns:
  1. No. (continuous across pagination)
  2. Learner Name
  3. Role (age-group label only: Child, Teen, Adult, Parent)
  4. Modules Enrolled Count (instructor-owned only)
  5. Last Activity Page
  6. View Action
- Last activity fallback: N/A

### Module Active/Deactivate Learner-Side Policy (Hybrid)
- Active module: visible and fully usable
- Deactivated module:
  - stays visible in enrolled/history context with Deactivated badge
  - blocks lesson and quiz progression actions
  - keeps prior progress/history readable
  - already-earned certificate/history remains accessible where applicable

### Enrollment Rejection
- Reject uses predefined reason + optional custom note
- Store audit metadata (rejected_by, rejected_at, reason code, reason note)
- Notify learner with reason and instructor name

### Search and Navigation
- Search results always route to details/information pages
- Global search appears only on instructor dashboard
- Other pages keep local search/filter controls

## 3. Architecture and Service Boundaries

### Controller Boundaries
- Controllers remain thin
- Controllers handle validation, authorization checks, and response selection only

### Service Boundaries
- EnrollmentVisibilityService: instructor-scoped learner listing and metrics
- EnrollmentDecisionService: approve/reject, reason persistence, notification trigger
- ContentStatusService: activation toggles and learner-side enforcement checks
- InstructorSearchRoutingService: entity-to-show-route mapping for search outcomes
- InstructorAssessmentInsightsService: module distribution, attempts, at-risk computations

### Event/Notification Boundary
- Decision and activity notifications emitted through domain events/listeners
- Standard payload for consistent rendering and deep links

### Internal Phase Order
- Phase A1: domain behavior and persistence foundations
- Phase A2: shared UI primitives and style-system consistency
- Phase A3: page-level migrations and redesigns
- Phase A4: assessment logs, notifications polish, stabilization

## 4. Data Model and Persistence Changes

### Activation State
- Ensure active/inactive state exists for modules, lessons, quizzes
- Default new records to active

### Enrollment Rejection Audit
- Add/persist fields:
  - rejection_reason_code
  - rejection_reason_note (nullable)
  - rejected_by_instructor_id
  - rejected_at

### Last Activity Page
- Use existing activity source if present
- If no source exists, add lightweight snapshot for last page and last seen timestamp

### Assessment Query Performance
- Prefer computed metrics from attempts data
- Add indexes for learner/module/date dimensions used by monitoring screens

### Notification Payload Persistence
- In-app database notification payload includes:
  - type, title, message
  - module context
  - instructor name (when relevant)
  - action URL

## 5. Permissions and Access Control

- Enforce instructor ownership scoping on read and write operations
- Keep learners table view-only; remove unauthorized edit/create access paths
- Restrict dashboard global search to instructor-owned entities
- Restrict local page searches to instructor-accessible datasets
- Enforce inactive-content progression blocking server-side
- Ensure notification visibility is recipient-specific

## 6. Instructor Learners Management UX

- Page purpose is learner inspection, not learner CRUD
- View page is read-only and includes instructor-relevant learner context
- Local filtering remains available on page
- Search result routes to learner information page only
- Empty/no-activity states are explicit and non-breaking

## 7. Modules Management and Module Details UX

- Remove book icon in thumbnail area
- Normalize thumbnail color treatment to current theme
- Move Published status inline with metadata under description
- Remove duplicated search bar and keep single local search below heading area
- Add View Learner action in enrolled learners table on module details
- Reject flow uses modal with reason and optional note
- Rejection triggers learner notification payload with module + reason + instructor
- Module delete flow uses page-level confirm modal (cancel/confirm)

## 8. Lessons and Quizzes Modal Editing UX

- Replace legacy edit pages with tabbed modals
- Include active/inactive controls in create and edit modals
- Keep modal open on validation errors and preserve entered values
- Standardize actions (save/cancel/close), spacing, and interaction behavior

## 9. Instructor-Wide Table/Icon/Typography Standards

- Apply numbering column and action-cell consistency to learners, lessons, quizzes, and related tables
- Improve icon size and contrast visibility
- Improve font scale/readability and row spacing for usability

## 10. Search Strategy and Routing Map

- Global multi-entity search only on instructor dashboard
- Local search/filter controls remain on individual management pages
- Route map:
  - Module search -> module details
  - Lesson search -> lesson details
  - Learner search -> learner information

## 11. Notifications Design

### Instructor Notifications (in-app)
- New quiz taking from learners (batched summary mode)
- Enrollment requests pending review
- Enrollment approvals/rejections context when relevant to instructor workflows
- Additional content-status notices where useful

### Learner Notifications (in-app)
- Enrollment approved notification
- Enrollment rejected notification including:
  - rejection reason
  - optional note
  - instructor name

### Behavior
- Unified payload shape with deep links
- Unread badge + mark-read support in notification center

## 12. Assessment Logs and Quiz Monitoring

Default view: per-module overview dashboard

Required metrics:
- Attempt count per learner
- At-risk learner flag (low score + low activity)
- Per-module score distribution

At-risk thresholds are configurable through instructor settings context.

Recommended add-ons:
- trend of module average over time
- most retried quizzes to identify confusing content

## 13. Image Library Redesign

- Visual-first gallery layout
- Metadata drawer for selected asset
- Themed controls and spacing matching instructor design language
- Local search/filter and improved empty/loading states

## 14. Sidebar Redesign (Instructor Only)

- Improve icon clarity and active state visibility
- Refine grouping hierarchy and interaction states
- Preserve responsive and keyboard-accessible behavior

## 15. Deletion Confirmation Strategy

- Use per-page confirm modals across instructor destructive actions
- Standardized destructive copy, cancel path, and confirm emphasis
- No deletion without explicit confirm

## 16. Error Handling and Empty States

- Inline form errors in modals
- Consistent toast/error messaging patterns
- Explicit empty states for no learners, no notifications, no logs, and no assets

## 17. Testing and Verification Strategy

### Feature Tests
- instructor learner visibility scoping
- learners table action restriction (view-only)
- search destination routing to show/information pages
- rejection reason persistence and learner notification payload
- active/inactive learner-side enforcement

### UI/Flow Verification
- modal create/edit flows for modules/lessons/quizzes
- per-page delete confirmation behavior
- dashboard-only global search visibility

### Analytics Verification
- attempt count computation
- per-module score distribution calculation
- at-risk flag threshold behavior

### Quality Gate Per Phase
- feature completion + automated tests pass + manual QA checklist pass

## 18. Rollout and Risk Management

- Execute as one large phased release with A1-A4 sequencing
- Backend authorization and ownership checks treated as non-negotiable guardrails
- Prioritize regression safety for learner-side access and progression logic
- Completion condition: all requested instructor refinements implemented and verified
