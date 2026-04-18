# Conscious Connections Parent Enrollment Notification and Parent Monitoring UX Implementation Plan

**Goal:** Strengthen parent enrollment notifications and parent monitoring UX so parents can move from notification to the exact enrollment decision context with clearer, safer decision controls.

**Architecture:** Keep Laravel server-rendered flow with thin controllers, notification payload contracts in notification classes, and Blade UX updates in parent monitoring views. Validation moves to Form Request per project rules.

**Tech Stack:** Laravel 12, PHP 8.2, Blade, Alpine.js, Tailwind CSS v3, PHPUnit.

**Design/Requirements Source:**
- `docs/specs/2026-04-17-parent-child-reliability-slice-spec.md`
- Existing parent monitoring and notification behavior in current codebase

---

## Task 1: Notification deep-link contract for parent enrollment lifecycle

**Step 1: Write failing tests (notification action URLs + payload contract)**
- File: `tests/Feature/Parent/ParentEnrollmentNotificationTest.php`
- Add assertions in existing tests:

```php
$this->assertSame(
    route('parent.children.enrollments.show', [$child, $enrollment]),
    data_get($parentNotification->data, 'action_url')
);
```

```php
$this->assertSame(
    route('parent.children.enrollments.show', [$child, $enrollment]),
    data_get($parentNotification->data, 'action_url')
);
```

```php
$pendingEnrollment = ModuleEnrollment::query()
    ->where('user_id', $child->id)
    ->where('module_id', $module->id)
    ->latest('id')
    ->firstOrFail();

$this->assertSame(
    route('parent.children.enrollments.show', [$child, $pendingEnrollment]),
    data_get($notification->data, 'action_url')
);
```

**Step 2: Run test and verify failure**
- Command:

```bash
php artisan test --filter=ParentEnrollmentNotificationTest
```

- Expected output:

```text
FAIL  Tests\Feature\Parent\ParentEnrollmentNotificationTest
  ⨯ parent approval notifies child and parent accounts
  ⨯ parent rejection includes reason when available
  ⨯ child enrollment request notifies parent when parent approval is required
```

**Step 3: Implement minimal notification payload updates**
- File: `app/Notifications/Parent/ChildEnrollmentApprovalRequestedNotification.php`
- Replace `action_url` in `toDatabase()`:

```php
'action_url' => route('parent.children.enrollments.show', [$this->child, $this->enrollment]),
```

- File: `app/Notifications/Parent/ChildEnrollmentApprovedNotification.php`
- Replace `action_url` in `toDatabase()`:

```php
'action_url' => route('parent.children.enrollments.show', [$this->child, $this->enrollment]),
```

- File: `app/Notifications/Parent/ChildEnrollmentRejectedNotification.php`
- Replace `action_url` in `toDatabase()`:

```php
'action_url' => route('parent.children.enrollments.show', [$this->child, $this->enrollment]),
```

**Step 4: Run test and verify success**
- Command:

```bash
php artisan test --filter=ParentEnrollmentNotificationTest
```

- Expected output:

```text
PASS  Tests\Feature\Parent\ParentEnrollmentNotificationTest
  ✓ parent approval notifies child and parent accounts
  ✓ parent rejection includes reason when available
  ✓ child enrollment request notifies parent when parent approval is required
```

---

## Task 2: Parent monitoring content approval card UX clarity

**Step 1: Write failing UI test for explicit pending status label in monitoring tab**
- File: `tests/Feature/ParentChildMonitoringTest.php`
- Add test method:

```php
public function test_parent_dashboard_shows_pending_parent_approval_label_and_detail_link(): void
{
    [$parent, $child] = $this->createParentWithChild();

    $module = Module::factory()->create(['title' => 'Body Boundaries']);
    $enrollment = ModuleEnrollment::create([
        'user_id' => $child->id,
        'module_id' => $module->id,
        'status' => 'pending_parent_approval',
        'enrolled_at' => null,
    ]);

    $response = $this->actingAs($parent)
        ->get(route('parent.children.show', $child));

    $response->assertOk()
        ->assertSee('Pending Parent Approval')
        ->assertSee(route('parent.children.enrollments.show', [$child, $enrollment]), false);
}
```

**Step 2: Run test and verify failure**
- Command:

```bash
php artisan test --filter=test_parent_dashboard_shows_pending_parent_approval_label_and_detail_link
```

- Expected output:

```text
FAIL  Tests\Feature\ParentChildMonitoringTest
  ⨯ parent dashboard shows pending parent approval label and detail link
  Failed asserting that the response contains: Pending Parent Approval
```

**Step 3: Implement minimal UI update**
- File: `resources/views/parent/children/show.blade.php`
- Inside Content Approval card loop (`@foreach($pendingEnrollments as $enrollment)`), add explicit status pill:

```php
<span class="mt-2 inline-flex items-center rounded-full border border-amber-200 bg-amber-50 px-2 py-0.5 text-[11px] font-semibold text-amber-700">
    Pending Parent Approval
</span>
```

- Keep existing `View Details` button and action routes unchanged.

**Step 4: Run test and verify success**
- Command:

```bash
php artisan test --filter=test_parent_dashboard_shows_pending_parent_approval_label_and_detail_link
```

- Expected output:

```text
PASS  Tests\Feature\ParentChildMonitoringTest
  ✓ parent dashboard shows pending parent approval label and detail link
```

---

## Task 3: Reject reason validation via Form Request + UX error state

**Step 1: Write failing test for overlong reject reason validation**
- File: `tests/Feature/Parent/ParentEnrollmentNotificationTest.php`
- Add test method:

```php
public function test_parent_reject_reason_must_be_500_chars_or_less(): void
{
    [$parent, $child] = $this->createParentChildPair();

    $module = Module::factory()->create([
        'enrollment_mode' => 'manual',
        'access_type' => 'free',
    ]);

    $enrollment = ModuleEnrollment::create([
        'user_id' => $child->id,
        'module_id' => $module->id,
        'status' => 'pending_parent_approval',
        'enrolled_at' => null,
    ]);

    $tooLongReason = str_repeat('a', 501);

    $this->actingAs($parent)
        ->from(route('parent.children.enrollments.show', [$child, $enrollment]))
        ->post(route('parent.children.enrollments.reject', [$child, $enrollment]), [
            'reason' => $tooLongReason,
        ])
        ->assertSessionHasErrors(['reason']);

    $this->assertDatabaseHas('module_enrollments', [
        'id' => $enrollment->id,
        'status' => 'pending_parent_approval',
    ]);
}
```

**Step 2: Run test and verify failure**
- Command:

```bash
php artisan test --filter=test_parent_reject_reason_must_be_500_chars_or_less
```

- Expected output:

```text
FAIL  Tests\Feature\Parent\ParentEnrollmentNotificationTest
  ⨯ parent reject reason must be 500 chars or less
  Failed asserting that the session has errors for key: reason
```

**Step 3: Implement Form Request + controller signature + view error output**
- Create file: `app/Http/Requests/Parent/RejectEnrollmentRequest.php`

```php
<?php

namespace App\Http\Requests\Parent;

use Illuminate\Foundation\Http\FormRequest;

class RejectEnrollmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reason' => ['nullable', 'string', 'max:500'],
        ];
    }
}
```

- File: `app/Http/Controllers/ParentController.php`
- Update method signature:

```php
public function rejectEnrollment(RejectEnrollmentRequest $request, User $child, ModuleEnrollment $enrollment): RedirectResponse
```

- Keep current reason normalization logic:

```php
$reason = trim((string) $request->input('reason', ''));
$normalizedReason = $reason !== '' ? $reason : null;
```

- File: `resources/views/parent/children/enrollment-show.blade.php`
- Add validation feedback under reason input:

```php
@error('reason')
    <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
@enderror
```

- Preserve old input value:

```php
value="{{ old('reason') }}"
```

**Step 4: Run test and verify success**
- Command:

```bash
php artisan test --filter=test_parent_reject_reason_must_be_500_chars_or_less
```

- Expected output:

```text
PASS  Tests\Feature\Parent\ParentEnrollmentNotificationTest
  ✓ parent reject reason must be 500 chars or less
```

---

## Task 4: Notification-entry context strip on enrollment detail page

**Step 1: Write failing test for notification-entry UX context**
- File: `tests/Feature/ParentChildMonitoringTest.php`
- Add test method:

```php
public function test_parent_enrollment_detail_shows_notification_context_when_opened_from_notification(): void
{
    [$parent, $child] = $this->createParentWithChild();

    $module = Module::factory()->create(['title' => 'Consent Basics']);
    $enrollment = ModuleEnrollment::create([
        'user_id' => $child->id,
        'module_id' => $module->id,
        'status' => 'pending_parent_approval',
        'enrolled_at' => null,
    ]);

    $this->actingAs($parent)
        ->get(route('parent.children.enrollments.show', [$child, $enrollment, 'from' => 'notification']))
        ->assertOk()
        ->assertSee('Opened from notification')
        ->assertSee('Return to notifications');
}
```

**Step 2: Run test and verify failure**
- Command:

```bash
php artisan test --filter=test_parent_enrollment_detail_shows_notification_context_when_opened_from_notification
```

- Expected output:

```text
FAIL  Tests\Feature\ParentChildMonitoringTest
  ⨯ parent enrollment detail shows notification context when opened from notification
  Failed asserting that the response contains: Opened from notification
```

**Step 3: Implement minimal view context wiring**
- File: `app/Http/Controllers/ParentController.php`
- In `showEnrollment(...)`, pass context flag to view:

```php
'openedFromNotification' => request()->query('from') === 'notification',
```

- File: `resources/views/parent/children/enrollment-show.blade.php`
- Add conditional context strip near top:

```php
@if($openedFromNotification)
    <div class="rounded-xl border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-900">
        Opened from notification.
        <a href="{{ route('learner.notifications.index') }}" class="ml-2 font-semibold underline">
            Return to notifications
        </a>
    </div>
@endif
```

- Update parent notification action URLs from Task 1 to append query flag:

```php
'action_url' => route('parent.children.enrollments.show', [$this->child, $this->enrollment, 'from' => 'notification']),
```

(Apply to all three parent enrollment notification classes.)

**Step 4: Run test and verify success**
- Command:

```bash
php artisan test --filter=test_parent_enrollment_detail_shows_notification_context_when_opened_from_notification
```

- Expected output:

```text
PASS  Tests\Feature\ParentChildMonitoringTest
  ✓ parent enrollment detail shows notification context when opened from notification
```

---

## Task 5: Targeted regression run and completion verification

**Step 1: Run parent notification and monitoring feature tests**
- Command:

```bash
php artisan test --filter=ParentEnrollmentNotificationTest
php artisan test --filter=ParentChildMonitoringTest
php artisan test --filter=ParentChildrenActionsUiTest
```

- Expected output:

```text
PASS  Tests\Feature\Parent\ParentEnrollmentNotificationTest
PASS  Tests\Feature\ParentChildMonitoringTest
PASS  Tests\Feature\Parent\ParentChildrenActionsUiTest
```

**Step 2: Run reliability slice-admin regression tests referenced by spec**
- Command:

```bash
php artisan test --filter=AdminParentChildVerificationUiTest
php artisan test --filter=AdminParentChildVerificationModerationWorkflowTest
```

- Expected output:

```text
PASS  Tests\Feature\Admin\AdminParentChildVerificationUiTest
PASS  Tests\Feature\Admin\AdminParentChildVerificationModerationWorkflowTest
```

**Step 3: Validate no syntax errors in modified PHP files**
- Command:

```bash
php -l app/Http/Controllers/ParentController.php
php -l app/Http/Requests/Parent/RejectEnrollmentRequest.php
php -l app/Notifications/Parent/ChildEnrollmentApprovalRequestedNotification.php
php -l app/Notifications/Parent/ChildEnrollmentApprovedNotification.php
php -l app/Notifications/Parent/ChildEnrollmentRejectedNotification.php
```

- Expected output:

```text
No syntax errors detected in ...
```

**Step 4: Final acceptance checklist**
- Parent notification action opens exact enrollment detail context.
- Parent monitoring content approval card clearly shows `Pending Parent Approval` and `View Details` flow.
- Reject reason validation protects UX and preserves form context.
- No regressions in parent-child moderation and monitoring test slices.

---

## Handoff

After completing these tasks in order, hand off to superpower-execute for implementation. Do not execute this plan in planning mode.
