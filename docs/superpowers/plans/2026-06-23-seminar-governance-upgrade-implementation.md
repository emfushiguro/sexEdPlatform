# Seminar Governance Upgrade Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Upgrade the existing seminar system with moderation-first publishing, custom categories, improved lifecycle actions, instructor speaker selection, and learner discovery.

**Architecture:** Extend the current `Seminar` model, connector routes, admin seminar moderation routes, and learner seminar browsing flow. Keep status transitions and eligibility in services so Blade views only display actions allowed by backend rules.

**Tech Stack:** Laravel 12, Eloquent migrations, Form Requests, Blade, Alpine, Tailwind, PHPUnit feature/unit tests, existing connector/admin RBAC services.

---

## File Structure

Create:

- `database/migrations/2026_06_23_000001_upgrade_seminar_governance_workflow.php`: adds custom category and lifecycle metadata.
- `database/migrations/2026_06_23_000002_create_seminar_moderation_reviews_table.php`: preserves approval/rejection history.
- `app/Models/SeminarModerationReview.php`: review history model.
- `app/Services/Seminars/SeminarLifecycleService.php`: submit, approve, reject, publish, archive, complete, and cancel transition rules.
- `app/Services/Seminars/SeminarDiscoveryService.php`: learner/instructor visible seminar query and filters.
- `app/Services/Seminars/SeminarCategoryService.php`: category validation/display helper.
- `app/Services/Seminars/SeminarSpeakerEligibilityService.php`: eligible instructor query for modal search using instructor role data and `User::instructorProfile()`.
- `app/Http/Requests/Admin/RejectSeminarRequest.php`: rejection reason/note validation.

Modify:

- `app/Enums/SeminarStatus.php`: add `pending_review`, `approved`, `rejected`, and `archived`.
- `app/Models/Seminar.php`: add fillable fields, casts, moderation history relationships, and category display helper.
- `app/Http/Controllers/Connector/SeminarController.php`: remove description payload, replace direct publish rules, add submit/archive lifecycle actions.
- `app/Http/Controllers/Connector/SeminarSpeakerController.php`: use selected instructor ID and eligibility service.
- `app/Http/Controllers/Admin/SeminarModerationController.php`: add approve/reject governance actions and review page data.
- `app/Http/Controllers/SeminarBrowseController.php`: use discovery service and filters.
- `app/Http/Requests/Connector/StoreSeminarRequest.php`: remove description, add custom category validation.
- `app/Http/Requests/Connector/UpdateSeminarRequest.php`: remove description, add custom category validation.
- `app/Http/Requests/Connector/StoreSeminarSpeakerRequest.php`: remove speaker title and manual ID assumptions.
- `config/seminars.php`: add rejection reasons and category labels if missing.
- `routes/connector.php`: add submit/archive routes and speaker search route.
- `routes/admin.php`: add approve/reject routes if missing.
- `routes/web.php`: ensure learner discovery filters route to `/seminars`.
- `resources/views/connectors/seminars/_form.blade.php`: remove description and add conditional custom category.
- `resources/views/connectors/seminars/show.blade.php`: remove Channel Details, group actions, add completion modal, add speaker modal.
- `resources/views/connectors/seminars/index.blade.php`: show review statuses and custom category.
- `resources/views/admin/seminars/index.blade.php`: moderation table filters and icon actions.
- `resources/views/admin/seminars/show.blade.php`: dedicated review page fields and approve/reject flow.
- `resources/views/seminars/index.blade.php`: learner discovery filters and event cards.
- `resources/views/seminars/show.blade.php`: custom category and governance-safe registration state.
- `tests/Feature/Connectors/ConnectorSeminarManagementTest.php`
- `tests/Feature/Admin/AdminSeminarModerationTest.php`
- `tests/Feature/Seminars/SeminarRegistrationTest.php`
- `tests/Unit/Services/Seminars/SeminarAccessServiceTest.php`

---

### Task 1: Add Governance Schema And Status Values

**Files:**
- Create: `database/migrations/2026_06_23_000001_upgrade_seminar_governance_workflow.php`
- Create: `database/migrations/2026_06_23_000002_create_seminar_moderation_reviews_table.php`
- Create: `app/Models/SeminarModerationReview.php`
- Modify: `app/Enums/SeminarStatus.php`
- Modify: `app/Models/Seminar.php`
- Test: `tests/Unit/Services/Seminars/SeminarAccessServiceTest.php`

- [ ] **Step 1: Write the failing status and relationship test**

Add assertions to `tests/Unit/Services/Seminars/SeminarAccessServiceTest.php`:

```php
public function test_seminar_governance_statuses_are_available(): void
{
    $this->assertSame('draft', \App\Enums\SeminarStatus::Draft->value);
    $this->assertSame('pending_review', \App\Enums\SeminarStatus::PendingReview->value);
    $this->assertSame('approved', \App\Enums\SeminarStatus::Approved->value);
    $this->assertSame('rejected', \App\Enums\SeminarStatus::Rejected->value);
    $this->assertSame('published', \App\Enums\SeminarStatus::Published->value);
    $this->assertSame('completed', \App\Enums\SeminarStatus::Completed->value);
    $this->assertSame('cancelled', \App\Enums\SeminarStatus::Cancelled->value);
    $this->assertSame('archived', \App\Enums\SeminarStatus::Archived->value);
}
```

- [ ] **Step 2: Run the test to verify failure**

Run:

```powershell
php vendor\bin\phpunit --do-not-cache-result tests\Unit\Services\Seminars\SeminarAccessServiceTest.php
```

Expected: FAIL because new enum cases do not exist.

- [ ] **Step 3: Add enum cases**

Update `app/Enums/SeminarStatus.php`:

```php
case PendingReview = 'pending_review';
case Approved = 'approved';
case Rejected = 'rejected';
case Archived = 'archived';
```

Extend `label()` for those values.

- [ ] **Step 4: Add migrations and model fields**

Migration `2026_06_23_000001_upgrade_seminar_governance_workflow.php` adds nullable fields:

```php
$table->string('custom_category')->nullable()->after('category');
$table->timestamp('submitted_for_review_at')->nullable();
$table->foreignId('submitted_for_review_by')->nullable()->constrained('users')->nullOnDelete();
$table->timestamp('approved_at')->nullable();
$table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
$table->timestamp('rejected_at')->nullable();
$table->foreignId('rejected_by')->nullable()->constrained('users')->nullOnDelete();
$table->string('rejection_reason')->nullable();
$table->text('moderator_note')->nullable();
$table->timestamp('published_at')->nullable();
$table->foreignId('published_by')->nullable()->constrained('users')->nullOnDelete();
$table->timestamp('archived_at')->nullable();
$table->foreignId('archived_by')->nullable()->constrained('users')->nullOnDelete();
```

Migration `2026_06_23_000002_create_seminar_moderation_reviews_table.php` creates:

```php
$table->id();
$table->foreignId('seminar_id')->constrained()->cascadeOnDelete();
$table->foreignId('moderator_id')->constrained('users')->cascadeOnDelete();
$table->string('from_status')->nullable();
$table->string('to_status');
$table->string('reason')->nullable();
$table->text('note')->nullable();
$table->timestamp('reviewed_at');
$table->timestamps();
```

- [ ] **Step 5: Update `Seminar` and add review model**

Add fillable/casts and relationships:

```php
public function moderationReviews(): HasMany
{
    return $this->hasMany(SeminarModerationReview::class);
}
```

`SeminarModerationReview` belongs to `seminar` and `moderator`.

- [ ] **Step 6: Run the test to verify pass**

Run the same PHPUnit command. Expected: PASS.

---

### Task 2: Remove Description And Add Custom Category Validation

**Files:**
- Create: `app/Services/Seminars/SeminarCategoryService.php`
- Modify: `app/Http/Requests/Connector/StoreSeminarRequest.php`
- Modify: `app/Http/Requests/Connector/UpdateSeminarRequest.php`
- Modify: `app/Http/Controllers/Connector/SeminarController.php`
- Modify: `resources/views/connectors/seminars/_form.blade.php`
- Test: `tests/Feature/Connectors/ConnectorSeminarManagementTest.php`

- [ ] **Step 1: Write failing form validation tests**

Add tests:

```php
public function test_connector_can_create_seminar_without_description(): void
{
    $response = $this->actingAs($this->connectorManager)->post(route('connector.seminars.store', $this->connector), [
        'title' => 'Family Learning Webinar',
        'purpose' => 'Help families understand safe online learning habits.',
        'type' => 'webinar',
        'category' => 'education',
        'starts_at' => now()->addWeek()->format('Y-m-d H:i:s'),
        'ends_at' => now()->addWeek()->addHour()->format('Y-m-d H:i:s'),
        'capacity' => 40,
        'target_participants' => 'learners',
        'learner_age_categories' => ['teen'],
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('seminars', [
        'title' => 'Family Learning Webinar',
        'purpose' => 'Help families understand safe online learning habits.',
        'description' => null,
        'category' => 'education',
    ]);
}

public function test_other_category_requires_custom_category(): void
{
    $response = $this->actingAs($this->connectorManager)->post(route('connector.seminars.store', $this->connector), [
        'title' => 'Local Skills Session',
        'purpose' => 'Introduce community learning options.',
        'type' => 'physical',
        'category' => 'other',
        'starts_at' => now()->addWeek()->format('Y-m-d H:i:s'),
        'ends_at' => now()->addWeek()->addHour()->format('Y-m-d H:i:s'),
        'capacity' => 20,
        'target_participants' => 'learners',
        'learner_age_categories' => ['adult'],
        'location' => 'Community Hall',
    ]);

    $response->assertSessionHasErrors('custom_category');
}
```

- [ ] **Step 2: Run tests to verify failure**

Run:

```powershell
php vendor\bin\phpunit --do-not-cache-result tests\Feature\Connectors\ConnectorSeminarManagementTest.php
```

Expected: FAIL because current validation/payload still includes description and lacks custom category.

- [ ] **Step 3: Update request validation**

Remove `description` rules. Add:

```php
'custom_category' => ['nullable', 'string', 'max:80', 'required_if:category,other'],
```

- [ ] **Step 4: Update controller payload**

Remove `description` from `Arr::only(...)`. Include `custom_category`, but normalize to null unless category is `other`.

- [ ] **Step 5: Update form**

Remove the Description field. Add an Alpine-controlled custom category input that appears only when category is `other`.

- [ ] **Step 6: Run tests to verify pass**

Run the same PHPUnit command. Expected: PASS.

---

### Task 3: Add Lifecycle Service And Connector Submit/Publish Rules

**Files:**
- Create: `app/Services/Seminars/SeminarLifecycleService.php`
- Modify: `app/Http/Controllers/Connector/SeminarController.php`
- Modify: `routes/connector.php`
- Modify: `resources/views/connectors/seminars/show.blade.php`
- Test: `tests/Feature/Connectors/ConnectorSeminarManagementTest.php`

- [ ] **Step 1: Write failing lifecycle tests**

Cover:

```php
public function test_connector_submits_draft_for_review(): void
public function test_connector_cannot_publish_draft_directly(): void
public function test_connector_can_publish_approved_seminar(): void
public function test_connector_can_archive_non_active_seminar(): void
```

Assert status and metadata after each action.

- [ ] **Step 2: Run tests to verify failure**

Run connector seminar feature test. Expected: FAIL because submit/archive and approved-only publish are not implemented.

- [ ] **Step 3: Implement lifecycle service**

Methods:

```php
public function submitForReview(Seminar $seminar, User $user): Seminar;
public function publishApproved(Seminar $seminar, User $user): Seminar;
public function archive(Seminar $seminar, User $user): Seminar;
public function complete(Seminar $seminar, User $user): Seminar;
public function cancel(Seminar $seminar, User $user, string $reason): Seminar;
```

Rules:

- Submit only from `draft` or `rejected`.
- Publish only from `approved`.
- Archive only from `draft`, `rejected`, `completed`, or `cancelled`.
- Complete only from `published`.
- Cancel only from `pending_review`, `approved`, or `published`.

- [ ] **Step 4: Update connector controller and routes**

Add routes:

```php
Route::post('/connector/{connector}/seminars/{seminar}/submit-review', [SeminarController::class, 'submitForReview'])->name('connector.seminars.submit-review');
Route::post('/connector/{connector}/seminars/{seminar}/archive', [SeminarController::class, 'archive'])->name('connector.seminars.archive');
```

Change `publish()` to call `publishApproved()`.

- [ ] **Step 5: Run tests to verify pass**

Run connector seminar feature test. Expected: PASS.

---

### Task 4: Implement Admin Approval, Rejection, And History

**Files:**
- Create: `app/Http/Requests/Admin/RejectSeminarRequest.php`
- Modify: `app/Http/Controllers/Admin/SeminarModerationController.php`
- Modify: `config/seminars.php`
- Modify: `routes/admin.php`
- Modify: `resources/views/admin/seminars/index.blade.php`
- Modify: `resources/views/admin/seminars/show.blade.php`
- Test: `tests/Feature/Admin/AdminSeminarModerationTest.php`

- [ ] **Step 1: Write failing admin governance tests**

Cover:

```php
public function test_admin_can_approve_pending_review_seminar(): void
public function test_admin_can_reject_pending_review_seminar_with_reason_and_note(): void
public function test_admin_moderation_history_is_preserved(): void
public function test_moderation_table_filters_by_search_status_and_type(): void
```

- [ ] **Step 2: Run tests to verify failure**

Run:

```powershell
php vendor\bin\phpunit --do-not-cache-result tests\Feature\Admin\AdminSeminarModerationTest.php
```

Expected: FAIL because approval/rejection workflow is not implemented.

- [ ] **Step 3: Add rejection reasons to config**

Add:

```php
'rejection_reasons' => [
    'incomplete_information' => 'Incomplete information',
    'inappropriate_content' => 'Inappropriate content',
    'eligibility_unclear' => 'Eligibility rules are unclear',
    'schedule_conflict' => 'Schedule or venue details need correction',
    'other' => 'Other',
],
```

- [ ] **Step 4: Implement approve/reject controller actions**

Admin approval calls lifecycle service `approve($seminar, $admin)`. Rejection validates `reason` and `note`, calls `reject($seminar, $admin, $reason, $note)`, and stores `seminar_moderation_reviews`.

- [ ] **Step 5: Update admin table and review page**

Index filters: search, status, type, category. Actions: view, approve, reject icons where status allows.

Review page shows seminar details, host connector, speakers, registration settings, event details, eligibility, and moderation history.

- [ ] **Step 6: Run tests to verify pass**

Run admin moderation test. Expected: PASS.

---

### Task 5: Update Connector Detail UX

**Files:**
- Modify: `resources/views/connectors/seminars/show.blade.php`
- Test: `tests/Feature/Connectors/ConnectorSeminarManagementTest.php`

- [ ] **Step 1: Write failing view assertions**

Assert the show response:

```php
$response->assertDontSee('Channel Details');
$response->assertSee('Edit');
$response->assertSee('Archive');
$response->assertSee('Complete Seminar');
$response->assertSee('Cancel Seminar');
$response->assertSee('Are you sure you want to complete');
```

- [ ] **Step 2: Run test to verify failure**

Run connector seminar feature test. Expected: FAIL while the old section and action layout still exist.

- [ ] **Step 3: Update Blade**

Remove Channel Details section. Group actions in the detail header. Add Alpine modal for completion confirmation. Keep completion backed by the existing POST route.

- [ ] **Step 4: Run test to verify pass**

Run connector seminar feature test. Expected: PASS.

---

### Task 6: Replace Speaker ID Entry With Instructor Modal

**Files:**
- Create: `app/Services/Seminars/SeminarSpeakerEligibilityService.php`
- Modify: `app/Http/Requests/Connector/StoreSeminarSpeakerRequest.php`
- Modify: `app/Http/Controllers/Connector/SeminarSpeakerController.php`
- Modify: `routes/connector.php`
- Modify: `resources/views/connectors/seminars/show.blade.php`
- Test: `tests/Feature/Connectors/ConnectorSeminarManagementTest.php`

- [ ] **Step 1: Write failing speaker tests**

Cover:

```php
public function test_speaker_search_returns_only_active_approved_instructors(): void
public function test_connector_adds_speaker_by_selected_instructor(): void
public function test_ineligible_user_cannot_be_added_as_speaker(): void
```

- [ ] **Step 2: Run tests to verify failure**

Run connector seminar feature test. Expected: FAIL because instructor search and eligibility are missing.

- [ ] **Step 3: Implement eligibility service**

Query users whose primary `role` is `instructor` or who have an assigned `instructor` role, require an existing `instructorProfile`, and exclude users already assigned to the seminar:

```php
User::query()
    ->with('instructorProfile')
    ->where(function ($query): void {
        $query->where('role', 'instructor')
            ->orWhereHas('roles', fn ($roleQuery) => $roleQuery->where('name', 'instructor'));
    })
    ->whereHas('instructorProfile')
    ->whereDoesntHave('seminarSpeakerAssignments', fn ($speakerQuery) => $speakerQuery->where('seminar_id', $seminar->id))
    ->when($search !== '', function ($query) use ($search): void {
        $query->where(function ($searchQuery) use ($search): void {
            $searchQuery->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%");
        });
    })
    ->orderBy('name')
    ->limit(20)
    ->get(['id', 'name', 'email']);
```

- [ ] **Step 4: Update request and controller**

Validation accepts only `user_id` and optional `role`. Remove `title` from accepted UI payload. Controller verifies the user is eligible before assigning.

- [ ] **Step 5: Update modal UI**

Add `Add Speaker` button, search input, avatar/name list, and hidden selected `user_id` field. Use existing profile image paths and fallback initials.

- [ ] **Step 6: Run tests to verify pass**

Run connector seminar feature test. Expected: PASS.

---

### Task 7: Add Learner Discovery Service And Filters

**Files:**
- Create: `app/Services/Seminars/SeminarDiscoveryService.php`
- Modify: `app/Http/Controllers/SeminarBrowseController.php`
- Modify: `resources/views/seminars/index.blade.php`
- Modify: `resources/views/seminars/show.blade.php`
- Test: `tests/Feature/Seminars/SeminarRegistrationTest.php`

- [ ] **Step 1: Write failing discovery tests**

Cover:

```php
public function test_learner_discovery_only_shows_published_seminars(): void
public function test_learner_discovery_filters_by_type_category_and_upcoming(): void
public function test_learner_discovery_respects_age_category(): void
public function test_custom_category_is_displayed_to_learners(): void
```

- [ ] **Step 2: Run tests to verify failure**

Run:

```powershell
php vendor\bin\phpunit --do-not-cache-result tests\Feature\Seminars\SeminarRegistrationTest.php
```

Expected: FAIL because discovery has not been tightened to governance rules.

- [ ] **Step 3: Implement discovery service**

Return only `published` seminars for normal discovery. Apply filters for type, category, upcoming, and user eligibility.

- [ ] **Step 4: Update learner views**

Show thumbnail/fallback, title, category display, host, speakers, type, registration availability, capacity count, and date/time.

- [ ] **Step 5: Run tests to verify pass**

Run seminar registration test. Expected: PASS.

---

### Task 8: Final Verification And Build

**Files:**
- All seminar files modified above.

- [ ] **Step 1: Run focused seminar tests**

Run:

```powershell
php vendor\bin\phpunit --do-not-cache-result tests\Feature\Connectors\ConnectorSeminarManagementTest.php tests\Feature\Admin\AdminSeminarModerationTest.php tests\Feature\Seminars\SeminarRegistrationTest.php tests\Unit\Services\Seminars\SeminarAccessServiceTest.php
```

Expected: PASS.

- [ ] **Step 2: Run frontend build**

Run:

```powershell
npm run build
```

Expected: build completes without errors.

- [ ] **Step 3: Browser verify core flows**

Verify:

- Connector creates draft without description.
- Connector selects `Others` and custom category appears.
- Connector submits for review.
- Admin approves.
- Connector publishes.
- Learner sees seminar in discovery.
- Admin rejects a different seminar and history appears.
- Speaker modal lists instructors and assigns one.
- Complete action opens confirmation modal.

Expected: all flows render and state transitions persist.

## Self-Review Checklist

- Schema supports all approved statuses and history.
- Existing published seminars remain published.
- Learner discovery never shows unapproved seminars.
- Connector publish is blocked until admin approval.
- Rejected seminars are editable and resubmittable.
- Description is removed from current create/edit UI.
- Custom category is required only for `other`.
- Speaker title is removed from the connector speaker UI.
- Completion confirmation is present before status update.
- Tests are written before implementation in each task.
