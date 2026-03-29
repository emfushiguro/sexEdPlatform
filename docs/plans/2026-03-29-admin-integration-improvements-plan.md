# Admin Integration & Instructor Application Improvements Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Enhance the Admin UI/UX by overhauling the dashboard and sidebar, and improve the Instructor Application review workflow with guided rejection features.

**Architecture:** We will implement database updates via migrations for rejection fields, create notification classes, update layout Blade components for the sidebar and dashboard, and use Alpine.js/Blade components for the application rejection modal.

**Tech Stack:** Laravel, Blade, Tailwind CSS, Alpine.js, PHPUnit

---

### Task 1: Update Instructor Applications Database Schema

**Files:**
- Create: `database/migrations/[timestamp]_add_rejection_fields_to_instructor_applications_table.php` (via artisan)
- Modify: `app/Models/InstructorApplication.php`
- Modify: `database/factories/InstructorApplicationFactory.php` (if exists) or create test data logic
- Test: `tests/Feature/Admin/InstructorApplicationFeatureTest.php`

**Step 1: Write the passing test (if existing) or failing test for schema**
```php
// in tests/Feature/Admin/InstructorApplicationFeatureTest.php or similar
public function test_it_can_store_rejection_reasons(): void
{
    $application = InstructorApplication::factory()->create([
        'status' => 'Rejected',
        'rejection_reason' => 'incomplete_info',
        'rejection_notes' => 'Missing degree document.'
    ]);
    
    $this->assertDatabaseHas('instructor_applications', [
        'id' => $application->id,
        'rejection_reason' => 'incomplete_info',
        'rejection_notes' => 'Missing degree document.'
    ]);
}
```

**Step 2: Run test to verify it fails**
Run: `php artisan test --filter=test_it_can_store_rejection_reasons`
Expected: FAIL against undefined columns

**Step 3: Write minimal implementation**
Generate migration: `php artisan make:migration add_rejection_fields_to_instructor_applications_table`

```php
public function up(): void
{
    Schema::table('instructor_applications', function (Blueprint $table) {
        $table->string('rejection_reason')->nullable()->after('status');
        $table->text('rejection_notes')->nullable()->after('rejection_reason');
    });
}
```
Update `app/Models/InstructorApplication.php` `$fillable` array to include `rejection_reason` and `rejection_notes`.

**Step 4: Run test to verify it passes**
Run: `php artisan migrate:fresh --env=testing` then `php artisan test --filter=test_it_can_store_rejection_reasons`
Expected: PASS

**Step 5: Commit**
```bash
git add database/migrations/ app/Models/InstructorApplication.php tests/Feature/Admin/InstructorApplicationFeatureTest.php
git commit -m "feat: add rejection reason fields to instructor applications"
```

### Task 2: Create Rejection Notification

**Files:**
- Create: `app/Notifications/ApplicationRejectedNotification.php`
- Test: `tests/Feature/Admin/InstructorApplicationNotificationTest.php`

**Step 1: Write the failing test**
```php
public function test_it_sends_rejection_notification(): void
{
    Notification::fake();
    $user = User::factory()->create();
    $user->notify(new ApplicationRejectedNotification('incomplete_info', 'Please provide X.'));
    
    Notification::assertSentTo($user, ApplicationRejectedNotification::class);
}
```

**Step 2: Run test to verify it fails**
Run: `php artisan test --filter=test_it_sends_rejection_notification`
Expected: FAIL (Class not found)

**Step 3: Write minimal implementation**
Run: `php artisan make:notification ApplicationRejectedNotification`
Add `$presetReason` and `$customNotes` to constructor, format `toMail` logic nicely.

**Step 4: Run test to verify it passes**
Run: `php artisan test --filter=test_it_sends_rejection_notification`
Expected: PASS

**Step 5: Commit**
```bash
git add app/Notifications/ tests/Feature/Admin/InstructorApplicationNotificationTest.php
git commit -m "feat: create application rejected notification class"
```

### Task 3: Enhance Admin Sidebar Navigation

**Files:**
- Modify: `resources/views/layouts/admin/sidebar.blade.php` (or relevant admin layout file containing the sidebar).

**Step 1: Write failing test / visual verification**
There are no dedicated backend tests for simple HTML rendering changes unless navigation logic enforces auth.
Visual Verification: We will manually ensure the sidebar renders properly.

**Step 2: Write minimal implementation**
Locate the existing Admin Sidebar layout.
Add a new structural group for "Moderation", matching the UI of existing tags.
Add links for `route('admin.instructor-applications.index')` (or similar) and modules review.

**Step 3: Verify visually in browser**
Ensure it matches the aesthetic of existing grouped lists in the sidebar.

**Step 4: Commit**
```bash
git add resources/views/layouts/
git commit -m "ui: add moderation group to admin sidebar"
```

### Task 4: Revamp Admin Dashboard Layout

**Files:**
- Modify: `resources/views/admin/dashboard.blade.php`
- Modify: `app/Http/Controllers/Admin/DashboardController.php` (to pass metrics variables).
- Test: `tests/Feature/Admin/DashboardTest.php`

**Step 1: Write the failing test**
```php
public function test_admin_dashboard_receives_metrics(): void
{
    // Need logged in admin
    // actingAs admin
    $response = $this->get(route('admin.dashboard'));
    $response->assertOk()
             ->assertViewHasAll(['totalUsers', 'totalInstructors', 'activeSubscriptions', 'pendingApplications', 'pendingModules']);
}
```

**Step 2: Run test to verify it fails**
Run: `php artisan test --filter=test_admin_dashboard_receives_metrics`
Expected: FAIL, variables missing.

**Step 3: Write minimal implementation**
Update Controller to fetch and pass the required stats.
Build the fresh Blade view using existing platform styling (cards, tables). 
Include a top metrics grid and bottom two-column layout.

**Step 4: Run test to verify it passes**
Run: `php artisan test --filter=test_admin_dashboard_receives_metrics`
Expected: PASS

**Step 5: Commit**
```bash
git add resources/views/admin/dashboard.blade.php app/Http/Controllers/Admin/DashboardController.php tests/Feature/Admin/DashboardTest.php
git commit -m "feat: overhaul admin dashboard layout and metrics"
```

### Task 5: Instructor Application Management Pages (Index & Detail)

**Files:**
- Modify/Create: `app/Http/Controllers/Admin/InstructorApplicationController.php`
- Modify/Create: `resources/views/admin/instructor-applications/index.blade.php`
- Modify/Create: `resources/views/admin/instructor-applications/show.blade.php`
- Modify: `routes/admin.php`

**Step 1: Write the failing test**
```php
public function test_admin_can_view_applications(): void
{
    $application = InstructorApplication::factory()->create();
    $this->actingAsAdmin()->get(route('admin.instructor-applications.index'))->assertOk();
    $this->actingAsAdmin()->get(route('admin.instructor-applications.show', $application))->assertOk();
}
```

**Step 2: Run test to verify it fails**

**Step 3: Write minimal implementation**
Create basic data table in index.blade.php.
Create detail card view in show.blade.php.
Register routes.

**Step 4: Run test to verify it passes**

**Step 5: Commit**
```bash
git add app/Http/Controllers/Admin/ resources/views/admin/instructor-applications/ routes/admin.php
git commit -m "feat: implement instructor applications index and show views"
```

### Task 6: Implement Rejection Flow Modal (Alpine.js)

**Files:**
- Modify: `resources/views/admin/instructor-applications/show.blade.php`
- Modify/Create: `app/Http/Controllers/Admin/InstructorApplicationController.php` (update logic)

**Step 1: Write the failing test for rejection logic**
```php
public function test_admin_can_reject_application_with_reason(): void
{
    $application = InstructorApplication::factory()->create(['status' => 'Pending']);
    
    $this->actingAsAdmin()->post(route('admin.instructor-applications.reject', $application), [
        'rejection_reason' => 'incomplete_info',
        'rejection_notes' => 'testing custom reasoning'
    ])->assertRedirect();
    
    $application->refresh();
    $this->assertEquals('Rejected', $application->status);
    $this->assertEquals('incomplete_info', $application->rejection_reason);
}
```

**Step 2: Run test to verify it fails**

**Step 3: Write minimal implementation**
Define endpoint `$router->post('/applications/{id}/reject')`.
Add logic to controller to update the model and dispatch `ApplicationRejectedNotification`.
Add Alpine.js `<div x-data="{ open: false, reason: '' }">` modal inside the show template.

**Step 4: Run test to verify it passes**

**Step 5: Commit**
```bash
git add app/Http/Controllers/Admin/ resources/views/admin/instructor-applications/
git commit -m "feat: implement application rejection modal and processing logic"
```
