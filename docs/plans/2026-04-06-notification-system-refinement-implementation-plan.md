# Notification System Refinement and Unification Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Deliver a centralized, role-consistent notification system across learner, instructor, and admin by removing chat browser popups, adding full notification pages for all roles, fixing unread/read logic, implementing deep-link behavior, and covering high-confidence missing events.

**Architecture:** Use a service-led notification refinement approach. Keep controllers thin and role route ownership intact while introducing normalization and read-state services to unify behavior. Use database notifications as the in-app unread/read source and keep admin computed metrics as supplemental operational insights.

**Tech Stack:** Laravel 12, PHP 8.2, Blade, Alpine.js, Eloquent Notifications, PHPUnit Feature/Unit tests, Laravel Events/Listeners.

---

## Task 1: Remove Chat Browser Notification Popups

**Files:**
- Modify: `resources/js/chat/store.js`
- Modify: `resources/views/chat/index.blade.php`
- Modify: `tests/Feature/Chat/ChatNotificationBadgeTest.php`

**Step 1: Write the failing test**

Add assertions that browser notification popup and toggle strings are absent.

```php
public function test_browser_notification_popup_flow_is_removed(): void
{
    $store = File::get(resource_path('js/chat/store.js'));
    $view = File::get(resource_path('views/chat/index.blade.php'));

    $this->assertStringNotContainsString('new Notification(', $store);
    $this->assertStringNotContainsString('toggleNotificationsEnabled', $store);
    $this->assertStringNotContainsString('data-chat-notification-toggle', $view);
}
```

**Step 2: Run test to verify it fails**

Run: `php artisan test --filter=browser_notification_popup_flow_is_removed`
Expected: FAIL because browser notification code still exists.

**Step 3: Write minimal implementation**

1. Remove `shouldSuppressBrowserNotification` and `maybeShowBrowserNotification` paths in chat store.
2. Remove browser permission/toggle state storage.
3. Remove browser alert toggle button in chat index view.

**Step 4: Run test to verify it passes**

Run: `php artisan test --filter=ChatNotificationBadgeTest`
Expected: PASS for updated chat notification behavior assertions.

**Step 5: Commit**

```bash
git add resources/js/chat/store.js resources/views/chat/index.blade.php tests/Feature/Chat/ChatNotificationBadgeTest.php
git commit -m "refactor(chat): remove browser popup notifications"
```

## Task 2: Add Notification Payload Normalization Support

**Files:**
- Create: `app/Support/NotificationPayloadNormalizer.php`
- Create: `tests/Unit/NotificationPayloadNormalizerTest.php`

**Step 1: Write the failing test**

Create unit tests for key normalization behaviors:
1. URL key fallback (`action_url`, `url`, `module_url`).
2. Severity derivation (`success`, `error`, `info`).
3. Title/message fallbacks.

**Step 2: Run test to verify it fails**

Run: `php artisan test --filter=NotificationPayloadNormalizerTest`
Expected: FAIL because class does not exist.

**Step 3: Write minimal implementation**

Implement a small support class:
1. `normalize(array $data): array`
2. `resolveActionUrl(array $data): ?string`
3. `resolveSeverity(array $data): string`

**Step 4: Run test to verify it passes**

Run: `php artisan test --filter=NotificationPayloadNormalizerTest`
Expected: PASS.

**Step 5: Commit**

```bash
git add app/Support/NotificationPayloadNormalizer.php tests/Unit/NotificationPayloadNormalizerTest.php
git commit -m "feat(notifications): add payload normalizer"
```

## Task 3: Introduce Notification Read Service for Reusable Read Logic

**Files:**
- Create: `app/Services/Notification/NotificationReadService.php`
- Create: `tests/Feature/Notifications/NotificationReadServiceFlowTest.php`

**Step 1: Write the failing test**

Create feature tests validating:
1. Mark-all-read resets unread to zero.
2. Dropdown-open mark-read behavior works idempotently.
3. Single-notification read changes `read_at`.

**Step 2: Run test to verify it fails**

Run: `php artisan test --filter=NotificationReadServiceFlowTest`
Expected: FAIL because service and endpoints are not yet wired.

**Step 3: Write minimal implementation**

Implement service methods:
1. `markAllRead(User $user): int`
2. `markAllReadOnDropdownOpen(User $user): int`
3. `markOneRead(User $user, string $notificationId): DatabaseNotification`

**Step 4: Run test to verify it passes**

Run: `php artisan test --filter=NotificationReadServiceFlowTest`
Expected: PASS.

**Step 5: Commit**

```bash
git add app/Services/Notification/NotificationReadService.php tests/Feature/Notifications/NotificationReadServiceFlowTest.php
git commit -m "feat(notifications): add reusable read-state service"
```

## Task 4: Learner Notification UX and Read-Flow Corrections

**Files:**
- Modify: `app/Http/Controllers/Learner/NotificationController.php`
- Modify: `routes/web.php`
- Modify: `resources/views/layouts/learner-header.blade.php`
- Modify: `resources/views/learner/notifications/index.blade.php`
- Create: `tests/Feature/Learner/LearnerNotificationReadFlowTest.php`

**Step 1: Write the failing test**

Add tests:
1. Opening dropdown endpoint marks unread as read.
2. Click on notification deep-links when action URL exists.
3. Fallback click redirects to learner notification page.

**Step 2: Run test to verify it fails**

Run: `php artisan test --filter=LearnerNotificationReadFlowTest`
Expected: FAIL with missing route/action behavior.

**Step 3: Write minimal implementation**

1. Add learner dropdown-open POST route.
2. Update learner controller to use read service and deep-link fallback resolver.
3. Update learner header badge rendering to exact count and 9+ cap.
4. Ensure unread indicator uses red badge/dot.

**Step 4: Run test to verify it passes**

Run: `php artisan test --filter=LearnerNotificationReadFlowTest`
Expected: PASS.

**Step 5: Commit**

```bash
git add app/Http/Controllers/Learner/NotificationController.php routes/web.php resources/views/layouts/learner-header.blade.php resources/views/learner/notifications/index.blade.php tests/Feature/Learner/LearnerNotificationReadFlowTest.php
git commit -m "feat(learner): align notification read logic and badge behavior"
```

## Task 5: Add Instructor Full Notification Page and Read Endpoints

**Files:**
- Modify: `app/Http/Controllers/Instructor/NotificationController.php`
- Modify: `routes/instructor.php`
- Modify: `resources/views/layouts/instructor-header.blade.php`
- Create: `resources/views/instructor/notifications/index.blade.php`
- Modify: `tests/Feature/Instructor/InstructorNotificationCenterTest.php`

**Step 1: Write the failing test**

Add tests for:
1. Instructor notifications index page route and render.
2. Dropdown-open auto-read.
3. Mark-all-read from full page.

**Step 2: Run test to verify it fails**

Run: `php artisan test --filter=InstructorNotificationCenterTest`
Expected: FAIL due to missing route/page/read action.

**Step 3: Write minimal implementation**

1. Add `index`, `markRead`, `markDropdownRead` methods.
2. Add instructor notification routes under role prefix.
3. Build instructor full notification Blade list with green/red/neutral semantics.
4. Keep existing dashboard snapshot and dropdown behavior compatible.

**Step 4: Run test to verify it passes**

Run: `php artisan test --filter=InstructorNotificationCenterTest`
Expected: PASS.

**Step 5: Commit**

```bash
git add app/Http/Controllers/Instructor/NotificationController.php routes/instructor.php resources/views/layouts/instructor-header.blade.php resources/views/instructor/notifications/index.blade.php tests/Feature/Instructor/InstructorNotificationCenterTest.php
git commit -m "feat(instructor): add full notification page and read actions"
```

## Task 6: Add Admin DB Notification Center While Preserving Computed Metrics

**Files:**
- Create: `app/Http/Controllers/Admin/NotificationController.php`
- Modify: `routes/admin.php`
- Modify: `resources/views/layouts/admin.blade.php`
- Create: `resources/views/admin/notifications/index.blade.php`
- Modify: `app/Providers/AppServiceProvider.php`
- Create: `tests/Feature/Admin/AdminNotificationCenterTest.php`

**Step 1: Write the failing test**

Add tests asserting:
1. Admin page uses DB notification unread count.
2. Mark-all-read and dropdown-open read actions work.
3. Computed metrics still render independently.

**Step 2: Run test to verify it fails**

Run: `php artisan test --filter=AdminNotificationCenterTest`
Expected: FAIL due to missing admin controller/routes.

**Step 3: Write minimal implementation**

1. Add admin notification controller with index/read/mark methods.
2. Add admin routes under `routes/admin.php`.
3. Update admin header to render DB notification list and badge.
4. Keep computed metrics from view composer in supplemental section.

**Step 4: Run test to verify it passes**

Run: `php artisan test --filter=AdminNotificationCenterTest`
Expected: PASS.

**Step 5: Commit**

```bash
git add app/Http/Controllers/Admin/NotificationController.php routes/admin.php resources/views/layouts/admin.blade.php resources/views/admin/notifications/index.blade.php app/Providers/AppServiceProvider.php tests/Feature/Admin/AdminNotificationCenterTest.php
git commit -m "feat(admin): unify db notification center with hybrid metrics"
```

## Task 7: Implement Notification Deep-Link Resolver

**Files:**
- Create: `app/Support/NotificationDeepLinkResolver.php`
- Modify: `app/Http/Controllers/Learner/NotificationController.php`
- Modify: `app/Http/Controllers/Instructor/NotificationController.php`
- Modify: `app/Http/Controllers/Admin/NotificationController.php`
- Create: `tests/Feature/Notifications/NotificationDeepLinkRoutingTest.php`

**Step 1: Write the failing test**

Test cases:
1. Known type with valid action URL redirects to target.
2. Missing URL falls back to role notification index.
3. Invalid target falls back safely.

**Step 2: Run test to verify it fails**

Run: `php artisan test --filter=NotificationDeepLinkRoutingTest`
Expected: FAIL due to missing resolver wiring.

**Step 3: Write minimal implementation**

Create resolver that:
1. Normalizes action target from payload.
2. Validates safe internal target.
3. Returns fallback role index route when unresolved.

**Step 4: Run test to verify it passes**

Run: `php artisan test --filter=NotificationDeepLinkRoutingTest`
Expected: PASS.

**Step 5: Commit**

```bash
git add app/Support/NotificationDeepLinkResolver.php app/Http/Controllers/Learner/NotificationController.php app/Http/Controllers/Instructor/NotificationController.php app/Http/Controllers/Admin/NotificationController.php tests/Feature/Notifications/NotificationDeepLinkRoutingTest.php
git commit -m "feat(notifications): add deep-link resolver with safe fallback"
```

## Task 8: Parent Approval/Rejection Notifications for Learner and Parent

**Files:**
- Create: `app/Notifications/Learner/ParentEnrollmentApprovedNotification.php`
- Create: `app/Notifications/Learner/ParentEnrollmentRejectedNotification.php`
- Create: `app/Notifications/Parent/ChildEnrollmentApprovedNotification.php`
- Create: `app/Notifications/Parent/ChildEnrollmentRejectedNotification.php`
- Modify: `app/Http/Controllers/ParentController.php`
- Create: `tests/Feature/Parent/ParentEnrollmentNotificationTest.php`

**Step 1: Write the failing test**

Add tests for parent approval and rejection flow:
1. Child learner receives corresponding notification.
2. Parent account receives confirmation/audit notification.
3. Rejection includes reason text where available.

**Step 2: Run test to verify it fails**

Run: `php artisan test --filter=ParentEnrollmentNotificationTest`
Expected: FAIL because parent controller currently only updates status.

**Step 3: Write minimal implementation**

1. Send learner notification on parent approve/reject.
2. Send parent-side confirmation notification.
3. Include action URL fallback to role notification page.

**Step 4: Run test to verify it passes**

Run: `php artisan test --filter=ParentEnrollmentNotificationTest`
Expected: PASS.

**Step 5: Commit**

```bash
git add app/Notifications/Learner/ParentEnrollmentApprovedNotification.php app/Notifications/Learner/ParentEnrollmentRejectedNotification.php app/Notifications/Parent/ChildEnrollmentApprovedNotification.php app/Notifications/Parent/ChildEnrollmentRejectedNotification.php app/Http/Controllers/ParentController.php tests/Feature/Parent/ParentEnrollmentNotificationTest.php
git commit -m "feat(parent): notify learner and parent on enrollment decisions"
```

## Task 9: Subscription and Payment Outcome Notifications

**Files:**
- Create: `app/Notifications/Learner/SubscriptionResultNotification.php`
- Create: `app/Notifications/Learner/SubscriptionExpirationReminderNotification.php`
- Create: `app/Notifications/Admin/NewSubscriptionPurchaseNotification.php`
- Create: `app/Notifications/Admin/NewPaymentTransactionNotification.php`
- Modify: `app/Observers/PaymentObserver.php`
- Modify: `app/Console/Commands/ExpireSubscriptions.php`
- Modify: `app/Services/SubscriptionDunningService.php`
- Create: `tests/Feature/Notifications/SubscriptionAndPaymentNotificationTest.php`

**Step 1: Write the failing test**

Cover:
1. Learner subscription success/failure DB notifications.
2. Admin receives new subscription/purchase event notifications.
3. Expiration reminder path creates learner notification.

**Step 2: Run test to verify it fails**

Run: `php artisan test --filter=SubscriptionAndPaymentNotificationTest`
Expected: FAIL due to missing notification dispatches.

**Step 3: Write minimal implementation**

1. Wire observer and command/service trigger points to emit DB notifications.
2. Keep existing mail behavior intact.
3. Use normalized payload keys and severity.

**Step 4: Run test to verify it passes**

Run: `php artisan test --filter=SubscriptionAndPaymentNotificationTest`
Expected: PASS.

**Step 5: Commit**

```bash
git add app/Notifications/Learner/SubscriptionResultNotification.php app/Notifications/Learner/SubscriptionExpirationReminderNotification.php app/Notifications/Admin/NewSubscriptionPurchaseNotification.php app/Notifications/Admin/NewPaymentTransactionNotification.php app/Observers/PaymentObserver.php app/Console/Commands/ExpireSubscriptions.php app/Services/SubscriptionDunningService.php tests/Feature/Notifications/SubscriptionAndPaymentNotificationTest.php
git commit -m "feat(notifications): add subscription and payment outcome events"
```

## Task 10: Module Purchase Result Notifications (Learner)

**Files:**
- Create: `app/Notifications/Learner/ModulePurchaseResultNotification.php`
- Modify: `app/Http/Controllers/Learner/ModuleController.php`
- Create: `tests/Feature/Learner/ModulePurchaseNotificationTest.php`

**Step 1: Write the failing test**

Cases:
1. Successful purchase confirmation sends success notification.
2. Failed/cancelled purchase sends failure notification.

**Step 2: Run test to verify it fails**

Run: `php artisan test --filter=ModulePurchaseNotificationTest`
Expected: FAIL due to no notifications in purchase success/failed handlers.

**Step 3: Write minimal implementation**

1. Dispatch success notification in `purchaseSuccess` after confirmation path.
2. Dispatch failure notification in `purchaseFailed` path.
3. Include module context and action links.

**Step 4: Run test to verify it passes**

Run: `php artisan test --filter=ModulePurchaseNotificationTest`
Expected: PASS.

**Step 5: Commit**

```bash
git add app/Notifications/Learner/ModulePurchaseResultNotification.php app/Http/Controllers/Learner/ModuleController.php tests/Feature/Learner/ModulePurchaseNotificationTest.php
git commit -m "feat(learner): add module purchase result notifications"
```

## Task 11: Certificate Issued Notifications for Learner and Instructor

**Files:**
- Create: `app/Notifications/Learner/CertificateIssuedNotification.php`
- Create: `app/Notifications/Instructor/LearnerCertificateIssuedNotification.php`
- Modify: `app/Http/Controllers/Learner/CertificateController.php`
- Create: `tests/Feature/Learner/CertificateIssuedNotificationTest.php`

**Step 1: Write the failing test**

Test:
1. Learner certificate generation emits learner certificate-issued notification.
2. Module instructor receives learner certificate-issued notification.

**Step 2: Run test to verify it fails**

Run: `php artisan test --filter=CertificateIssuedNotificationTest`
Expected: FAIL because certificate generation currently only returns flash success.

**Step 3: Write minimal implementation**

1. Notify learner after certificate create.
2. Load module owner and notify instructor.
3. Include action URL to certificate/module contexts.

**Step 4: Run test to verify it passes**

Run: `php artisan test --filter=CertificateIssuedNotificationTest`
Expected: PASS.

**Step 5: Commit**

```bash
git add app/Notifications/Learner/CertificateIssuedNotification.php app/Notifications/Instructor/LearnerCertificateIssuedNotification.php app/Http/Controllers/Learner/CertificateController.php tests/Feature/Learner/CertificateIssuedNotificationTest.php
git commit -m "feat(notifications): add certificate issued events"
```

## Task 12: Admin Notifications for New Module Submissions

**Files:**
- Create: `app/Notifications/Admin/NewModuleSubmissionNotification.php`
- Modify: `app/Services/ContentGovernanceService.php`
- Create: `tests/Feature/Admin/AdminModuleSubmissionNotificationTest.php`

**Step 1: Write the failing test**

Test that `submitForReview` sends a DB notification to admins with deep-link to content review queue.

**Step 2: Run test to verify it fails**

Run: `php artisan test --filter=AdminModuleSubmissionNotificationTest`
Expected: FAIL because submission currently does not notify admins.

**Step 3: Write minimal implementation**

1. Dispatch new admin module submission notification in `submitForReview`.
2. Include review request id/module id for focus query deep-link.

**Step 4: Run test to verify it passes**

Run: `php artisan test --filter=AdminModuleSubmissionNotificationTest`
Expected: PASS.

**Step 5: Commit**

```bash
git add app/Notifications/Admin/NewModuleSubmissionNotification.php app/Services/ContentGovernanceService.php tests/Feature/Admin/AdminModuleSubmissionNotificationTest.php
git commit -m "feat(admin): notify on new module submissions"
```

## Task 13: Chat Message In-App Notification Dispatch

**Files:**
- Create: `app/Notifications/Chat/NewChatMessageNotification.php`
- Create: `app/Listeners/Chat/SendInAppChatMessageNotification.php`
- Modify: `app/Providers/AppServiceProvider.php`
- Modify: `resources/views/chat/index.blade.php`
- Create: `tests/Feature/Chat/ChatInAppMessageNotificationTest.php`

**Step 1: Write the failing test**

Cover:
1. On `MessageSent` event, recipient gets DB notification.
2. Sender does not get self-notification.
3. Notification includes deep-link to chat conversation.

**Step 2: Run test to verify it fails**

Run: `php artisan test --filter=ChatInAppMessageNotificationTest`
Expected: FAIL because listener/notification is missing.

**Step 3: Write minimal implementation**

1. Create listener subscribed to `App\Events\Chat\MessageSent`.
2. Resolve recipient participant and send `NewChatMessageNotification`.
3. Surface unread indicator in chat page shell using existing unread hooks.

**Step 4: Run test to verify it passes**

Run: `php artisan test --filter=ChatInAppMessageNotificationTest`
Expected: PASS.

**Step 5: Commit**

```bash
git add app/Notifications/Chat/NewChatMessageNotification.php app/Listeners/Chat/SendInAppChatMessageNotification.php app/Providers/AppServiceProvider.php resources/views/chat/index.blade.php tests/Feature/Chat/ChatInAppMessageNotificationTest.php
git commit -m "feat(chat): route new message alerts through in-app notifications"
```

## Task 14: Instructor Quiz Activity Notifications (Per Attempt + Summary Compatibility)

**Files:**
- Create: `app/Notifications/Instructor/QuizAttemptActivityNotification.php`
- Modify: `app/Http/Controllers/Learner/QuizController.php`
- Modify: `app/Http/Controllers/Instructor/DashboardController.php`
- Create: `tests/Feature/Instructor/InstructorQuizActivityNotificationTest.php`

**Step 1: Write the failing test**

Test:
1. Quiz attempt emits instructor notification.
2. Dashboard summary remains visible and compatible with per-attempt notifications.

**Step 2: Run test to verify it fails**

Run: `php artisan test --filter=InstructorQuizActivityNotificationTest`
Expected: FAIL due to absent dispatch.

**Step 3: Write minimal implementation**

1. Trigger instructor notification after attempt creation in quiz submit flow.
2. Keep existing dashboard summary query and ensure UI still renders summary block.

**Step 4: Run test to verify it passes**

Run: `php artisan test --filter=InstructorQuizActivityNotificationTest`
Expected: PASS.

**Step 5: Commit**

```bash
git add app/Notifications/Instructor/QuizAttemptActivityNotification.php app/Http/Controllers/Learner/QuizController.php app/Http/Controllers/Instructor/DashboardController.php tests/Feature/Instructor/InstructorQuizActivityNotificationTest.php
git commit -m "feat(instructor): add quiz per-attempt notifications with summary compatibility"
```

## Task 15: UI Semantics and Badge Normalization Across Role Headers

**Files:**
- Modify: `resources/views/layouts/learner-header.blade.php`
- Modify: `resources/views/layouts/instructor-header.blade.php`
- Modify: `resources/views/layouts/admin.blade.php`
- Create: `tests/Feature/Notifications/NotificationUiSemanticsTest.php`

**Step 1: Write the failing test**

Assert:
1. Unread badge exact/9+ logic.
2. Red unread indicators present.
3. Green/red severity classes applied to success/failure events.

**Step 2: Run test to verify it fails**

Run: `php artisan test --filter=NotificationUiSemanticsTest`
Expected: FAIL due to inconsistent role rendering behavior.

**Step 3: Write minimal implementation**

1. Standardize badge count logic in all headers.
2. Apply severity class mapping consistently.
3. Preserve role-specific UI language and layout style.

**Step 4: Run test to verify it passes**

Run: `php artisan test --filter=NotificationUiSemanticsTest`
Expected: PASS.

**Step 5: Commit**

```bash
git add resources/views/layouts/learner-header.blade.php resources/views/layouts/instructor-header.blade.php resources/views/layouts/admin.blade.php tests/Feature/Notifications/NotificationUiSemanticsTest.php
git commit -m "style(notifications): unify role badge and severity semantics"
```

## Task 16: End-to-End Verification and Report

**Files:**
- Create: `docs/plans/2026-04-06-notification-system-refinement-test-report.md`

**Step 1: Run targeted suites**

Run:
1. `php artisan test --filter=Notification`
2. `php artisan test --filter=Chat`
3. `php artisan test --filter=InstructorNotificationCenterTest`
4. `php artisan test --filter=AdminNotificationCenterTest`

Expected: PASS.

**Step 2: Run full regression sweep if time allows**

Run: `php artisan test`
Expected: PASS or documented unrelated failures.

**Step 3: Write test report**

Document:
1. Executed commands.
2. Pass/fail outcomes.
3. Residual risks and deferred backlog.

**Step 4: Commit report**

```bash
git add docs/plans/2026-04-06-notification-system-refinement-test-report.md
git commit -m "docs: add notification refinement verification report"
```

## Notes for Execution

1. Keep controller methods orchestration-only; notification construction/dispatch logic belongs in services/listeners/notification classes.
2. Preserve route ownership boundaries by role route files.
3. Use payload normalization and deep-link resolution everywhere notification rows are rendered.
4. Favor additive changes; do not rewrite existing notification history rows.
5. Ensure failures in notification delivery do not fail core domain transactions.
