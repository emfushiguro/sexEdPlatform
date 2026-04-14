# Learner Feedback, Reporting, and Quiz UX Design

## Goal
Implement learner-facing module reviews, learner safety reporting, quiz keyboard UX fixes, and stronger post-quiz progression while preserving current Laravel server-rendered architecture, governance safety, and role boundaries.

## Approved Decisions
- Reviews: one review per learner per module, editable, only after full module completion.
- Rating scale: 1-5 hearts, whole values only.
- Review publishing: immediate public display.
- Review editor: TinyMCE limited formatting, sanitized HTML persisted.
- Instructor reply: one editable public reply.
- Reviews panel: right-side module overview with average, count, and latest 3 reviews.
- Full reviews page: newest default, supports highest/lowest sort, text search, rating filters, pagination.
- Report flow: unified report target (module or instructor) with fixed reasons + optional TinyMCE explanation.
- Duplicate report policy: one active report per learner per target; updates append to same active report.
- Report visibility: learner sees Submitted/Under Review/Resolved/Dismissed with generic outcome messaging.
- Admin moderation: full action timeline and explicit action recording.
- Notifications: in-app notifications to relevant parties and admin visibility for report queue.
- Quiz Enter key: Enter advances to next question; on last question Enter goes to review answers screen.
- Post-quiz pass UX: show proceed-to-next-lesson when available; if final lesson passed show completion modal with Claim Certificate CTA.

## Architecture
- Keep controllers orchestration-only.
- Add service layer for review lifecycle and report lifecycle.
- Add additive migrations and isolated models for learner reviews and learner reports.
- Add learner and instructor read surfaces for review transparency.
- Add admin moderation surface for learner reports with structured transitions and actions.

## Data Model
- module_feedback table:
  - id, module_id, learner_id, rating (1-5), review_html, instructor_reply_html nullable, submitted_at, last_edited_at, timestamps
  - unique(module_id, learner_id)
- content_reports table:
  - id, reporter_id, target_type (module|instructor), target_id, reason_code, status, details_html nullable, latest_outcome_message nullable, assigned_admin_id nullable, resolved_by nullable, resolved_at nullable, dismissed_at nullable, timestamps
  - index(reporter_id, target_type, target_id, status)
- content_report_activities table:
  - id, content_report_id, actor_id nullable, activity_type, from_status nullable, to_status nullable, action_code nullable, notes nullable, metadata nullable, created_at

## User Experience
- Learner module detail page gains:
  - reviews summary card on right rail
  - recent reviews list (latest 3)
  - submit/edit review form for eligible learners
  - report module/instructor actions
- Learner full reviews page:
  - list with filters and pagination
- Instructor module detail page gains:
  - module reviews section with average, distribution, and full list
- Admin moderation gains:
  - report queue index and report detail handling actions

## Error Handling
- Disallow review submission for non-completed learners (clear validation message).
- Disallow invalid target report submissions.
- Enforce one active report per target per reporter.
- Ensure all rich text content is sanitized before persistence.

## Testing Strategy
- Feature tests:
  - learner can submit review only after full completion
  - review upsert behavior and public visibility
  - report creation + duplicate active merge behavior
  - admin report lifecycle transitions and action logging
  - quiz Enter key navigation behavior (view-level assertions where feasible)
  - post-quiz next-lesson button and final-lesson completion modal conditions

## Rollout Notes
- Migrations are additive and reversible.
- Existing governance moderation routes remain unchanged.
- New report moderation is additive under admin route group.
