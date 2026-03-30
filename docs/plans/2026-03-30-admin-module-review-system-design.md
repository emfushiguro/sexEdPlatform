# Admin Module Review System Design

**Date:** 2026-03-30  
**Status:** Approved for implementation  
**Decision:** Approach A (Snapshot-anchored review workspace) with selective optimization from Approach C

## 1. Objective

Implement a complete Admin Module Review System that allows administrators to inspect instructor-submitted modules before publication, protect learner safety, preserve educational accuracy, and enforce responsible instructor behavior through warnings and penalties.

This design applies to instructor-owned content submitted for moderation. Learner visibility remains blocked until admin approval.

## 2. Moderation Principles

1. Safety first for sex education content.
2. Manual review is mandatory before publication.
3. Review decisions must be auditable and reproducible.
4. Rejections must include actionable guidance.
5. Instructor accountability must be tracked with enforceable penalties.

## 3. Scope

In scope:
1. Full-package module review (module, lessons, lesson topics, quizzes, questions).
2. Admin review workspace with learner-like navigation hierarchy.
3. Topic content preview (text, media, file metadata, formatting).
4. Quiz inspection using all-questions-at-once overview (same pattern as instructor quiz overview page).
5. Approve and reject workflows with required rejection guidance.
6. Violation tracking and warning/penalty escalation.
7. Restriction enforcement in backend and UI.
8. In-app notifications for instructor outcomes.

Out of scope for this phase:
1. Email notifications for review outcomes.
2. Retroactive backfill of violations from historical rejections.
3. Full account suspension automation without admin confirmation.

## 4. Current-State Baseline

Existing platform assets already provide:
1. Moderation lifecycle entities: `module_revisions`, `module_review_requests`.
2. Content governance service for submit/approve/reject and snapshot capture.
3. Admin review routes and basic review pages.
4. Learner visibility gating against approved published revisions.

Gap addressed by this design:
1. Deep review workspace UX for full package inspection.
2. Structured rejection reason and guidance for module moderation.
3. Instructor violation history and restriction lifecycle.
4. Enforcement policy across instructor creation/submission flows.

## 5. Locked Decisions

1. Restriction enforcement: backend hard block plus UI disable state.
2. Violation trigger: every rejection automatically adds a violation.
3. Restriction scope: block module creation and review submission while restricted.
4. Escalation execution: system suggests escalation, admin confirms.
5. Escalation durations: second violation 3 days, third violation 14 days.
6. Historical handling: no backfill; only new rejections after release count.
7. Rejection UX: preset reason plus required custom guidance note.
8. Other reason custom note: required (no minimum length rule).
9. Performance strategy: tree-first rendering with lazy loading for heavy previews.
10. Review UI parity: same hierarchy as learner module experience.
11. Topic safety: sanitized allowlist rendering for rich content.
12. Quiz review display: show all quiz questions at once.
13. Notification channels: in-app only.
14. Approval side effects: publish, notify, audit, and update moderation stats.
15. Instructor credibility panel: warning summary plus recent violations and view-all history.

## 6. Architecture

### 6.1 Core Pattern

Use a snapshot-anchored review workspace:
1. Review structure and moderation decisions bind to submitted revision snapshot payload.
2. Admin sees exactly what was submitted at review time.
3. Instructor edits after submission do not alter active review data.

### 6.2 Selective Hybrid Optimization

Apply selective live fetch only for heavy preview assets:
1. Topic media embed metadata and large file preview details can lazy-load on expand.
2. Snapshot remains the moderation source of truth.
3. Lazy-loaded assets must be read-only and version-safe (no data mutation in review mode).

### 6.3 Responsibility Split

1. Controllers remain thin orchestration endpoints.
2. Service layer handles moderation decisions, violation writes, escalation suggestions, and enforcement checks.
3. Views/Alpine handle hierarchical navigation and read-only inspection UI.

## 7. Roles and Permissions

### 7.1 Admin

Can:
1. Open pending/in-review review requests.
2. Inspect full module package.
3. Approve and publish reviewed revision.
4. Reject with required reason and guidance.
5. Confirm escalation actions suggested by policy engine.
6. View instructor warning/restriction history.

### 7.2 Instructor

Can:
1. Create/edit module content when not restricted.
2. Submit/resubmit for review when not restricted.
3. Receive in-app moderation outcomes and guidance.

Cannot (while restricted):
1. Create new modules.
2. Submit/resubmit modules for review.

### 7.3 Learner

1. Sees only approved/published module revisions.
2. Never sees in-review or rejected revisions.

## 8. Admin Review Workspace Information Architecture

Primary page sections:
1. Header and review status bar.
2. Module overview card.
3. Instructor credibility and moderation profile card.
4. Hierarchical content tree (module -> lessons -> topics -> quizzes).
5. Topic preview pane.
6. Quiz inspection pane (all questions visible).
7. Moderation action panel (approve/reject + consequences).
8. Violation history quick panel.

## 9. Module Overview Section Spec

Display fields:
1. Module title.
2. Instructor name and avatar.
3. Instructor profile quick view action.
4. Module description.
5. Category label mapping: Age 5-12, 13-17, 18+.
6. Pricing: Free or Paid.
7. Enrollment limit.
8. Current enrolled learners.
9. Submission date.
10. Module status (Pending Review/In Review).

Instructor credibility fields:
1. Warning count.
2. Violation history summary.
3. Last violation date.
4. Current restriction status.

## 10. Hierarchical Content Navigation

### 10.1 Structure

Tree order:
1. Module root.
2. Lessons (ordered).
3. Lesson topics (ordered).
4. Lesson/module quizzes.
5. Quiz questions under each quiz node.

### 10.2 Interaction

1. Expand/collapse at each level.
2. Preserve expanded state during intra-page review.
3. Jump-to-node navigation from top-level review controls.
4. Smooth scrolling to selected node.

### 10.3 Scale Handling

1. Render lesson list first.
2. Lazy render topic/quiz details on expand.
3. Maintain deterministic ordering from snapshot payload.

## 11. Topic Content Preview

### 11.1 Supported preview content

1. Text blocks and formatted content.
2. Images and attachments.
3. Embedded media metadata and playable embeds where safe.
4. File references with type and size metadata.

### 11.2 Safety model

1. Sanitize HTML/rich content using allowlist policy.
2. Remove scripts and unsafe inline handlers.
3. Render fallback warning block when preview content is unsafe/unrenderable.

## 12. Quiz Inspection

### 12.1 Quiz metadata

Display:
1. Quiz title.
2. Quiz description.
3. Attempt limit.
4. Timer.
5. Passing score.

### 12.2 Question display mode

Use full overview mode (all questions shown at once), mirroring instructor quiz overview behavior:
1. Question type.
2. Question text.
3. Answer choices.
4. Correct answer indicator.
5. Supporting media if attached.

### 12.3 Moderation intent cues

Provide visual cues for:
1. Missing correct answer.
2. Ambiguous or duplicate options.
3. Extremely low-quality question text.

## 13. Moderation Actions

### 13.1 Approve

On approval:
1. Mark review request approved.
2. Mark revision approved.
3. Publish revision to learner-visible pointer.
4. Update module review status.
5. Write audit entry.
6. Update moderation counters.
7. Send in-app notification to instructor.

### 13.2 Reject

On rejection:
1. Require preset reason code.
2. Require custom guidance note.
3. Mark review request needs revision.
4. Mark revision needs revision.
5. Keep learner-facing published revision unchanged.
6. Create violation record (automatic).
7. Run escalation policy engine and require admin confirmation for suggested penalty action.
8. Send in-app rejection notification with guidance.
9. Write audit entry.

## 14. Rejection Reason Taxonomy

Initial preset reasons:
1. Inaccurate educational information.
2. Inappropriate content.
3. Low-quality lessons.
4. Missing content.
5. Quiz errors.
6. Poor module structure.
7. Other.

Rules:
1. Preset reason is required.
2. Custom guidance note is required for every rejection.
3. Other also requires custom guidance note.

## 15. Warning and Penalty Engine

### 15.1 Escalation policy

1. First violation: formal warning.
2. Second violation: temporary restriction for 3 days.
3. Third violation: temporary restriction for 14 days.
4. Repeated violations beyond third: admin review for account suspension decision.

### 15.2 Confirmation model

1. Engine computes suggested action.
2. Admin must confirm action before finalization.
3. Confirmed action is stored with actor and timestamp.

## 16. Restriction Enforcement Design

### 16.1 Backend hard checks

Apply policy checks in instructor flows:
1. Module creation/store endpoints.
2. Module review submit/resubmit endpoints.

If restricted:
1. Reject action with policy message.
2. Preserve attempted action audit context.

### 16.2 UI enforcement

1. Disable create module CTA while restricted.
2. Disable submit/resubmit controls while restricted.
3. Show restriction badge and expiry countdown on instructor pages.

## 17. Violation Tracking Data Design

### 17.1 Instructor moderation profile

Store per instructor:
1. Warning count.
2. Current restriction status.
3. Restriction starts at.
4. Restriction ends at.
5. Last violation date.
6. Escalation level.

### 17.2 Violation history records

Store per violation:
1. Instructor id.
2. Related review request and module.
3. Reason code and guidance snapshot.
4. Violation sequence number.
5. Suggested and confirmed penalty action.
6. Admin actor.
7. Timestamps.

## 18. Data Flow

### 18.1 Submit flow

1. Instructor submits module.
2. Snapshot created.
3. Review request created as in review.

### 18.2 Review read flow

1. Admin opens review request.
2. Page loads snapshot hierarchy and metadata.
3. Expanding heavy nodes triggers lazy preview fetch.
4. Decision panel remains bound to current review request id.

### 18.3 Decision flow

1. Admin chooses approve/reject.
2. Service validates state transition and payload.
3. Service applies transaction updates.
4. Notification and audit writes complete.

## 19. Performance Strategy

1. Tree-first initial payload with module + lesson skeleton.
2. Deferred detail payload for topic media and large quiz metadata blocks.
3. Cache short-lived computed review summaries by review request id.
4. Keep moderation action writes uncached and transactional.

## 20. UI/UX Alignment Rules

1. Follow existing admin layout and card conventions.
2. Use brand colors `#730DB1` and `#A30EB2` for emphasis states.
3. Use collapsible sections for large module handling.
4. Keep moderation action panel visible and clear.
5. Use Toastify for approval success, rejection confirmation, and warning action feedback.

## 21. Notifications and Audit

### 21.1 In-app notification payload

Include:
1. Decision status.
2. Module title.
3. Reason label.
4. Guidance note.
5. Restriction action summary (if applied).

### 21.2 Audit

Log:
1. Reviewer id.
2. Decision.
3. Reason payload.
4. Violation and penalty outcomes.
5. Timestamped status transitions.

## 22. Error Handling

1. Block invalid transitions (approved review cannot be rejected later without new submission).
2. Reject missing reason/guidance payloads.
3. Guard against stale review forms with optimistic concurrency checks.
4. Fallback gracefully when preview assets are unavailable.

## 23. Testing Strategy

### 23.1 Feature tests

1. Admin review page renders full hierarchy and metadata.
2. Topic preview renders sanitized content.
3. Quiz overview shows all questions and correct indicators.
4. Approve flow publishes correct revision and sends in-app notification.
5. Reject flow requires reason plus guidance and sends in-app notification.
6. Rejection auto-creates violation.
7. Second and third violation suggestions map to 3-day and 14-day restrictions.
8. Admin confirmation required before restriction action persists.
9. Restricted instructor cannot create/submit modules.
10. Learner cannot access unapproved content.

### 23.2 Regression tests

1. Existing content governance service behavior remains valid.
2. Existing instructor review submission flow remains stable for unrestricted users.
3. Existing admin content review queue routes remain functional.

## 24. Rollout and Migration

1. Add moderation profile and violation history schema.
2. Add service-layer policy engine and enforcement guards.
3. Upgrade admin review UI to full workspace.
4. Release with no historical violation backfill.
5. Monitor review throughput, rejection quality, and restriction correctness.

## 25. Acceptance Criteria

1. Admin can inspect full module package in learner-like hierarchy.
2. Admin can preview topic content safely and inspect quiz questions in full overview mode.
3. Admin can approve/reject with required reason and guidance.
4. Rejection automatically creates a violation record.
5. Escalation policy suggests and admin confirms penalties.
6. Restriction is enforced in backend and reflected in instructor UI.
7. In-app notifications are sent for decisions.
8. Learners only see approved published revisions.

## 26. Handoff

Next step after this approved design document is a task-level implementation plan (`writing-plans`) that sequences schema changes, service updates, UI implementation, enforcement rules, and verification checkpoints.
