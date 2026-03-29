# Admin Moderation, Dashboard, and Instructor Application Design

Date: 2026-03-29
Status: Approved
Approach: Evolutionary consistency

## 1. Goal

Improve admin workflow speed and consistency by:

1. Adding direct moderation shortcuts in the admin sidebar.
2. Redesigning the admin dashboard into a central command center aligned with existing admin UI patterns.
3. Upgrading the Instructor Applications management UI for clearer review decisions.
4. Implementing structured rejection reasons (preset reason + optional custom note) with clear learner notifications.

## 2. Context Snapshot

Current implementation already provides:

1. Existing admin sidebar shell with expandable desktop and mobile drawer behaviors.
2. Existing routes for Instructor Applications and Content Reviews.
3. Existing dashboard service/controller pattern with metric cards.
4. Existing Instructor Application review flow using free-text rejection reason.
5. Existing learner notification pipeline for instructor application status updates.

Design intent is to build on those assets with minimal disruption.

## 3. Locked Decisions

1. Implementation strategy: Evolutionary consistency (no wide structural rewrite in this phase).
2. Module Published Review link target: Existing content review queue.
3. Dashboard activity depth: Lightweight unified feed from existing models (no new audit subsystem in this phase).
4. Applicant location display: City or municipality + barangay from learner profile when available.
5. Rejection model: Preset reason code + optional custom note.

## 4. Information Architecture

### 4.1 Sidebar Structure

Keep the existing sidebar shell and interaction model. Introduce clearer grouping:

1. Main
   - Dashboard
2. Moderation
   - Instructor Applications
   - Module Published Review
3. Management
   - Subscribers
   - Plans
   - Payments

### 4.2 Navigation Rules

1. Instructor Applications points to the existing instructor applications listing route.
2. Module Published Review points to the existing content reviews queue route.
3. Active-state highlighting uses route-pattern matching for both listing and detail views.
4. Optional badges show:
   - Pending instructor applications
   - In-review module review requests

### 4.3 UX Consistency

1. Preserve icon sizing, spacing rhythm, hover styles, and active states used in existing admin links.
2. Do not introduce a second navigation style.

## 5. Dashboard Redesign

### 5.1 Page Objective

Convert dashboard into a command center that answers:

1. What needs attention right now?
2. What is the platform health snapshot?
3. Where can admins act immediately?

### 5.2 Layout Blueprint

1. Header section
   - Title and short description
   - Quick actions to moderation queues
2. Snapshot metrics grid
   - 8 cards, responsive (1/2/4 columns)
3. Action band
   - Queue chips and direct action links
4. Recent system activity panel
   - Unified lightweight activity feed

### 5.3 Snapshot Metrics

Proposed cards:

1. Total users
2. Total instructors
3. Total learners
4. Total modules
5. Active subscriptions
6. Pending instructor applications
7. Pending module reviews
8. Payments needing review

### 5.4 Visual Direction

Align with established admin pages:

1. Rounded large cards and soft gradient accents by domain.
2. Uppercase micro-label, bold numeric value, concise helper text.
3. Icon badge in each stat card.
4. Same table, chip, and button patterns used by subscribers and payments pages.

### 5.5 Recent Activity Feed (Phase 1)

No new activity domain in this phase. Build feed from existing records:

1. Instructor application submissions and decisions
2. Content review status transitions
3. Payment status changes
4. Recent subscriber changes (optional if query cost remains acceptable)

### 5.6 Data Source and Flow

1. Controller stays thin.
2. Dashboard service returns normalized payload:
   - metrics
   - quick actions
   - recent activity list
3. View renders payload without business logic.
4. Empty states render gracefully if any segment has no rows.

## 6. Instructor Application UI Redesign

### 6.1 Index Page Structure

1. Keep status summary cards (pending/approved/rejected).
2. Add modern filter header pattern:
   - Search field
   - Status controls
3. Upgrade data table styling to match subscribers/payments pages.
4. Required columns:
   - Applicant name
   - Username
   - Location (city/municipality + barangay)
   - Educational background
   - Professional background preview
   - Date applied
   - Status
   - Actions

### 6.2 Detail Page Structure

1. Applicant identity and account snapshot card
2. Application profile card (education + professional background)
3. Credential/document cards
4. Decision history card
5. Sticky action bar for pending records

### 6.3 Clarity and Usability Enhancements

1. Standard status chips with consistent color semantics.
2. Clear empty and missing-data placeholders.
3. Strong visual separation between applicant-provided info and admin decisions.

## 7. Rejection Reason System

### 7.1 UX Model

Reject modal/input includes:

1. Required preset reason selector.
2. Optional custom note field.
3. If preset reason is Other, custom note becomes required.
4. Character guidance and validation feedback inline.

### 7.2 Preset Reason Catalog (Initial)

1. Incomplete application information
2. Insufficient educational or professional background
3. Application does not meet platform guidelines
4. Invalid or unverifiable credentials
5. Content expertise not aligned with platform topics
6. Other

### 7.3 Data Model Direction

Persist structured and readable fields:

1. rejection_reason_code (string)
2. rejection_reason_note (nullable text)
3. rejection_reason (readable composed string for compatibility)

Compatibility rule:

1. Continue supporting legacy displays that read rejection_reason.
2. New UI and notifications should prefer structured fields.

### 7.4 Validation Rules

1. rejection_reason_code: required, in allowed reason codes.
2. rejection_reason_note: nullable string with max length.
3. rejection_reason_note required when rejection_reason_code is Other.
4. Composed message cannot be empty.

### 7.5 Service Behavior

On reject action:

1. Validate payload.
2. Compose final readable rationale from preset label + optional note.
3. Persist status, approver metadata, and reason fields.
4. Dispatch notification with structured reason payload.

## 8. Learner Notification Behavior

### 8.1 Database Notification Payload

Include:

1. status
2. reason_code
3. reason_label
4. reason_note
5. readable_reason

### 8.2 Email Content

1. Keep respectful tone.
2. Explain why the application was rejected in clear language.
3. Include actionable guidance for reapplication.

### 8.3 Transparency Requirement

Learner must understand:

1. What category of issue was found.
2. What specific improvements are needed (when note is provided).

## 9. Error Handling and Risk Controls

### 9.1 Primary Risks

1. Sidebar clutter or duplicated navigation semantics
2. Dashboard becoming query-heavy
3. Inconsistent rejection messaging between UI and notifications
4. Legacy reason fields drifting from structured reason fields

### 9.2 Mitigations

1. Keep moderation links in a single dedicated group.
2. Keep activity feed lightweight and query-constrained.
3. Centralize reason composition in service layer.
4. Use one composition path for admin UI, database notification, and email payload.

## 10. Testing Strategy

### 10.1 Feature Tests

1. Sidebar renders new moderation links and correct active states.
2. Dashboard loads all command-center sections without errors.
3. Instructor application table shows new required columns and filters.
4. Reject action validates reason code and conditional note requirement.
5. Rejection persists structured reason fields and legacy readable reason.
6. Learner receives rejection notification with clear reason data.

### 10.2 Regression Tests

1. Existing instructor application approve flow remains unchanged.
2. Existing content review queue route remains accessible.
3. Existing admin notifications and sidebar behavior still work on mobile and desktop.

### 10.3 UI Verification

1. Dashboard visual consistency with Subscribers, Plans, and Payments pages.
2. Status chips and action buttons match existing admin design language.
3. Empty states and error messaging are readable and consistent.

## 11. Acceptance Criteria

1. Admin sidebar includes Instructor Applications and Module Published Review shortcuts.
2. Dashboard functions as a central command center with required metrics and activity panel.
3. Instructor Applications index and detail pages reflect upgraded, consistent admin UI patterns.
4. Rejection flow uses preset reason code plus optional custom note.
5. Learners receive clear rejection rationale in notification channels.
6. Design remains consistent, maintainable, and scalable for future moderation features.

## 12. Out of Scope (This Phase)

1. Building a new dedicated moderation micro-app.
2. Implementing full event-sourced audit timeline infrastructure.
3. Replacing all existing admin pages with a new component framework.
4. Building admin-manageable rejection reason CRUD.
