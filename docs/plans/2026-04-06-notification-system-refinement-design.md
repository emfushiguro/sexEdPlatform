# Notification System Refinement and Unification - Design

## 1. Context

Concious Connections currently has role-based notifications for key flows, but behavior is inconsistent across learner, instructor, and admin experiences. The chat system still emits browser-level popups, while the platform already has in-app notifications.

This design defines a unified, role-aware, centralized notification system that keeps controllers thin, preserves route ownership, and uses database notifications as the canonical in-app source.

## 2. Goals

1. Remove redundant chat browser popups and rely on platform in-app notifications.
2. Normalize notification UX across Learner, Instructor, and Admin.
3. Add dedicated full notifications pages for each role.
4. Fix unread/read behavior (dropdown open auto-read, mark all read, badge reset).
5. Support deep-link routing when notification payload provides destination.
6. Improve visual clarity through severity/status color indicators.
7. Add missing high-confidence notification events in critical workflows.

## 3. Non-Goals

1. Rewriting the entire notification stack to an API-first or SPA architecture.
2. Building a user-configurable notification preference center in this phase.
3. Migrating historical notification records in bulk.
4. Real-time push overhaul beyond existing Echo/Reverb setup.

## 4. Locked Product Decisions

1. Admin notification model: Hybrid.
2. Full page support: Learner + Instructor + Admin.
3. Notification click routing: deep-link when available, fallback to full notification page.
4. Dropdown open behavior: auto-mark as read for all database-backed role notifications.
5. Mark all read action: available in dropdown and full page.
6. Unread badge format: exact count, cap display to 9+.
7. Color semantics: Green success/approval, Red rejection/failure, Neutral for info.
8. Chat browser notifications: remove fully.
9. Chat message event behavior: create in-app notifications and show chat unread indicator.
10. Payload handling: normalize for new notifications with legacy key fallback.
11. Missing events scope: implement high-confidence events now; document deferred items.
12. Instructor quiz notifications: support per-attempt and summary views.
13. Admin hybrid enhancement: keep computed metrics as supplemental, DB notifications for unread/read state.
14. Subscription/payment learner notifications: include success and failure states.
15. Parent flow notifications: notify both learner and parent when applicable.

## 5. Current-State Findings

### 5.1 Existing notification surfaces

1. Learner:
- Full page exists: `resources/views/learner/notifications/index.blade.php`.
- Routes/controller exist in learner route group.

2. Instructor:
- Header dropdown exists in `resources/views/layouts/instructor-header.blade.php`.
- Mark-all-read endpoint exists.
- No full notification page yet.

3. Admin:
- Header dropdown exists in `resources/views/layouts/admin.blade.php`.
- Data currently comes from computed metrics via `AppServiceProvider` view composer.
- Not currently read/unread DB notification state driven.

### 5.2 Chat notification state

1. Browser Notification API is active in `resources/js/chat/store.js` (`new Notification(...)`).
2. Browser alert toggle is exposed in `resources/views/chat/index.blade.php`.

### 5.3 Event coverage gaps

1. Several learner payment/subscription and module purchase outcomes are not persisted as DB notifications.
2. Parent enrollment approval/rejection status changes do not notify learner/parent.
3. Admin notifications for module submission and payment/subscription events are incomplete in DB notification form.
4. Certificate issuance flow does not yet notify instructor/learner as required.

## 6. Target Architecture

### 6.1 Delivery channels and source of truth

1. In-app center source of truth: Laravel `database` notifications.
2. Existing mail notifications remain where already valuable.
3. Chat browser popups are removed; in-app notifications replace them.

### 6.2 Notification orchestration

Introduce a focused notification application layer to keep controllers thin and behavior consistent.

Proposed components:

1. `app/Services/Notification/NotificationCenterService.php`
- Builds role page datasets.
- Applies normalized presentation metadata.

2. `app/Services/Notification/NotificationReadService.php`
- Mark single read.
- Mark all read.
- Mark all read on dropdown-open action.

3. `app/Support/NotificationPayloadNormalizer.php`
- Resolves legacy keys (`url`, `module_url`, `action_url`) into a canonical action URL.
- Derives severity and display defaults.

4. `app/Support/NotificationDeepLinkResolver.php`
- Validates and resolves safe deep links by notification type.
- Fallback to role notification index when invalid/missing.

### 6.3 Admin hybrid model

1. Keep computed operational metrics in admin header as supplemental insight cards.
2. Unread badge and read state are driven by admin user database notifications.
3. No double-counting between computed metrics and DB unread counts.

## 7. Role UX and Routing Design

### 7.1 Learner

Routes under learner scope in `routes/web.php`:

1. Keep existing index route.
2. Keep mark-all-read route.
3. Add/align dropdown-open auto-read endpoint (POST).
4. Update click/read endpoint to deep-link fallback behavior.

### 7.2 Instructor

Routes under instructor scope in `routes/instructor.php`:

1. Add full notification index route.
2. Add read endpoint.
3. Keep mark-all-read route.
4. Add dropdown-open auto-read endpoint.

Views:

1. Add `resources/views/instructor/notifications/index.blade.php`.
2. Keep dropdown in instructor header, align color/indicator/read behavior.

### 7.3 Admin

Routes under admin scope in `routes/admin.php`:

1. Add full notification index route.
2. Add read endpoint.
3. Add mark-all-read route.
4. Add dropdown-open auto-read endpoint.

Views:

1. Add `resources/views/admin/notifications/index.blade.php`.
2. Update admin header dropdown to render DB notification list with unread/read.
3. Preserve computed metrics section as supplemental summary block.

## 8. Read/Unread Behavior Specification

1. Unread badge rendering:
- `0`: hidden.
- `1..9`: exact count.
- `>=10`: `9+`.

2. Dropdown open action:
- Calls role endpoint to mark unread notifications as read.
- Badge count resets to zero immediately after success.

3. Mark all read action:
- Works from dropdown and full page.
- Idempotent behavior.

4. Single notification click:
- Marks clicked notification as read.
- Redirects to deep-link URL if available and safe.
- Otherwise redirects to role full notifications page.

## 9. Color and Visual Semantics

1. Success/approved/completed: green tone.
2. Rejected/failed/declined: red tone.
3. Informational/system update: neutral gray/blue-neutral tone.
4. Unread indicator: red dot/badge.

Severity derivation order:

1. Explicit payload `severity`.
2. Payload status/value inference.
3. Type-based fallback map.
4. Neutral default.

## 10. Event and Trigger Matrix

## 10.1 Learner notifications

Implement now:

1. Subscription success.
2. Subscription failure.
3. Module purchase success.
4. Module purchase failure.
5. Parent enrollment approval.
6. Parent enrollment rejection with reason.
7. Instructor enrollment approval.
8. Instructor enrollment rejection with reason.
9. Certificate issued after successful generation.
10. Instructor application approved.
11. Instructor application rejected.
12. Subscription expiration reminder.

## 10.2 Instructor notifications

Implement now:

1. New module enrollments / enrollment requests.
2. Module submission status (submitted/resubmitted acknowledgement).
3. Module review result approved/rejected.
4. New certificates issued by learners in instructor-owned modules.
5. Quiz activity per attempt.
6. Quiz summary rollup for dashboard/dropdown.

## 10.3 Admin notifications

Implement now:

1. New subscription purchases.
2. New successful payment transactions.
3. New module submissions for review.
4. New instructor applications.

## 10.4 Chat notifications

Implement now:

1. On message sent, notify recipient participant in-app (not sender).
2. Notification action deep-links to chat conversation/page context.
3. Header unread indicator and chat page indicator stay synchronized.
4. Remove browser notification popup flow entirely.

## 11. Deep-Link Map

1. `instructor_application_submitted` -> admin instructor applications index with focus query.
2. `module_submission_created` -> admin content reviews index with focus.
3. `module_review_decision` -> instructor module detail page.
4. `enrollment_approved` / `enrollment_rejected` -> learner module detail or notifications page fallback.
5. `module_purchase_success` / `module_purchase_failed` -> learner module purchase status context.
6. `subscription_*` -> subscription history/details page.
7. `chat_new_message` -> chat page with conversation query.

Fallback for all unresolved links:

1. Learner -> learner notifications index.
2. Instructor -> instructor notifications index.
3. Admin -> admin notifications index.

## 12. Backward Compatibility Strategy

1. Keep rendering old notifications by key fallback mapping.
2. Avoid destructive migration of historical notification rows.
3. Standardize all new notifications on canonical payload keys.

## 13. Security and Authorization

1. Route guards remain role-scoped and authenticated.
2. Notification read actions must be restricted to owner notification records.
3. Deep-link destination validation must avoid open redirect behavior.
4. Missing/deleted resource targets gracefully fallback to role notification index.

## 14. Error Handling

1. Notification dispatch failures must not break core business transaction completion.
2. Failures are logged with notification type, recipient id, and entity metadata.
3. Read-state endpoints return safe fallback redirects for non-JSON requests.

## 15. Testing Strategy

### 15.1 Feature tests

1. Learner/instructor/admin notification index pages render and paginate.
2. Dropdown open marks unread as read.
3. Mark all read resets unread count.
4. Notification click deep-links when valid, falls back when missing.
5. Severity color class selection per event type/status.
6. Badge count rendering exact and 9+ cap.
7. Chat browser popup code path removed.
8. Chat in-app recipient notifications are created.

### 15.2 Regression tests

1. Existing enrollment decision notifications remain functional.
2. Existing instructor module review decision notifications remain functional.
3. Existing instructor application status notifications remain functional.
4. Admin computed metrics still render after DB-center integration.

## 16. Risks and Mitigations

1. Risk: duplicate notifications from overlapping triggers.
- Mitigation: centralize dispatch points and idempotency checks where needed.

2. Risk: admin unread mismatch if computed metrics are treated as unread.
- Mitigation: DB notification unread count is the only badge source.

3. Risk: noisy instructor quiz notifications.
- Mitigation: include both per-attempt and summarized display with sensible UI grouping.

4. Risk: legacy payload inconsistency.
- Mitigation: payload normalizer with strict fallback order.

## 17. Acceptance Criteria

1. No browser notifications are emitted from chat UI.
2. Learner, instructor, and admin each have a full notifications page.
3. Unread indicators use exact count with 9+ cap.
4. Dropdown open marks unread as read and clears badge.
5. Mark all read works in dropdown and full page for all roles.
6. Notification click deep-links when available; fallback otherwise.
7. Green/red/neutral color indicators match status semantics.
8. New high-confidence events generate DB notifications as specified.
9. Admin hybrid behavior is preserved and enhanced (computed metrics + DB read state).

## 18. Deferred Backlog (Post-Phase)

1. Notification preference center (channel/topic opt-in/out).
2. Digest scheduling controls.
3. Historical notification migration to canonical payload schema.
4. Advanced analytics dashboard for notification trends.
