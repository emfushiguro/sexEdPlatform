# Connector Seminars Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build the full free connector Seminar/Webinar v1: connector management, participant registration, speaker assignment, Agora livestream access, realtime comments/Q&A, attendance, notifications, exports, and admin moderation.

**Architecture:** Reuse and extend the existing `Seminar` and `SeminarRegistrant` models instead of creating a parallel event domain. Keep responsibilities separated through focused services: seminar access, registration, speaker management, Agora token generation, realtime interaction moderation, attendance aggregation, exports, and admin moderation. Enforce connector ownership, connector permissions, participant eligibility, and livestream roles at the server boundary.

**Tech Stack:** Laravel 12, Eloquent, Blade, Alpine, Tailwind CSS, Laravel Echo/Reverb, Agora RTC live broadcasting, PHPUnit/Laravel feature tests, CSV streamed responses, Laravel notifications.

---

## File Structure

Create:

- `app/Enums/SeminarStatus.php`: status values and helper labels.
- `app/Enums/SeminarType.php`: webinar and physical type values.
- `app/Enums/SeminarParticipantType.php`: learner/instructor targeting values.
- `app/Enums/SeminarInteractionStatus.php`: comment/Q&A visibility states.
- `app/Http/Controllers/Connector/SeminarController.php`: connector seminar CRUD and lifecycle actions.
- `app/Http/Controllers/Connector/SeminarRegistrantController.php`: connector registrant list and exports.
- `app/Http/Controllers/Connector/SeminarSpeakerController.php`: speaker assignment and removal.
- `app/Http/Controllers/Connector/SeminarLivestreamController.php`: host/speaker livestream page and token endpoint.
- `app/Http/Controllers/Connector/SeminarInteractionController.php`: connector comment/Q&A moderation.
- `app/Http/Controllers/Connector/SeminarAttendanceController.php`: attendance list and exports.
- `app/Http/Controllers/SeminarBrowseController.php`: participant seminar list, detail, registration, cancellation, join page, and token endpoint.
- `app/Http/Controllers/SeminarInteractionController.php`: participant comment and Q&A posting.
- `app/Http/Controllers/SeminarAttendanceController.php`: participant join/leave heartbeat endpoints.
- `app/Http/Controllers/Admin/SeminarModerationController.php`: admin seminar moderation dashboard and actions.
- `app/Http/Requests/Connector/StoreSeminarRequest.php`
- `app/Http/Requests/Connector/UpdateSeminarRequest.php`
- `app/Http/Requests/Connector/StoreSeminarSpeakerRequest.php`
- `app/Http/Requests/Seminars/RegisterSeminarRequest.php`
- `app/Http/Requests/Seminars/StoreSeminarCommentRequest.php`
- `app/Http/Requests/Seminars/StoreSeminarQuestionRequest.php`
- `app/Models/SeminarSpeaker.php`
- `app/Models/SeminarComment.php`
- `app/Models/SeminarQuestion.php`
- `app/Models/SeminarAttendance.php`
- `app/Services/Seminars/SeminarAccessService.php`
- `app/Services/Seminars/SeminarRegistrationService.php`
- `app/Services/Seminars/SeminarSpeakerService.php`
- `app/Services/Seminars/AgoraTokenService.php`
- `app/Services/Seminars/SeminarInteractionService.php`
- `app/Services/Seminars/SeminarAttendanceService.php`
- `app/Services/Seminars/SeminarExportService.php`
- `app/Notifications/Seminars/SeminarRegistrationConfirmedNotification.php`
- `app/Notifications/Seminars/SeminarReminderNotification.php`
- `app/Notifications/Seminars/SeminarCancelledNotification.php`
- `app/Notifications/Seminars/SeminarSpeakerAssignedNotification.php`
- `config/seminars.php`
- `resources/views/connectors/seminars/index.blade.php`
- `resources/views/connectors/seminars/create.blade.php`
- `resources/views/connectors/seminars/edit.blade.php`
- `resources/views/connectors/seminars/show.blade.php`
- `resources/views/connectors/seminars/registrants.blade.php`
- `resources/views/connectors/seminars/attendance.blade.php`
- `resources/views/connectors/seminars/livestream.blade.php`
- `resources/views/seminars/index.blade.php`
- `resources/views/seminars/show.blade.php`
- `resources/views/seminars/join.blade.php`
- `resources/views/admin/seminars/index.blade.php`
- `resources/views/admin/seminars/show.blade.php`
- `tests/Feature/Connectors/ConnectorSeminarManagementTest.php`
- `tests/Feature/Seminars/SeminarRegistrationTest.php`
- `tests/Feature/Seminars/SeminarLivestreamAccessTest.php`
- `tests/Feature/Seminars/SeminarInteractionTest.php`
- `tests/Feature/Seminars/SeminarAttendanceTest.php`
- `tests/Feature/Admin/AdminSeminarModerationTest.php`
- `tests/Unit/Services/Seminars/AgoraTokenServiceTest.php`
- `tests/Unit/Services/Seminars/SeminarAccessServiceTest.php`
- `tests/Unit/Services/Seminars/SeminarAttendanceServiceTest.php`

Modify:

- `app/Models/Seminar.php`: add fields, casts, scopes, connector, speakers, comments, questions, attendance, and registration relationships.
- `app/Models/SeminarRegistrant.php`: add cancellation/payment-neutral status fields and relationships.
- `app/Models/Connector.php`: add `seminars()` relationship.
- `app/Models/User.php`: add `seminarSpeakerAssignments()`, `seminarComments()`, `seminarQuestions()`, and `seminarAttendances()` relationships.
- `config/services.php`: add Agora config entries.
- `config/connector_permissions.php`: ensure `connector.manage_seminars` exists and is used by the Owner role.
- `routes/connector.php`: replace the seminar stub route with resourceful connector seminar routes.
- `routes/web.php`: add authenticated participant seminar routes.
- `routes/admin.php`: add admin seminar moderation routes.
- `resources/views/layouts/connector-app.blade.php`: point Seminars navigation to the real seminar list.
- `resources/views/layouts/admin.blade.php`: add admin seminar moderation navigation where current admin navigation conventions place moderation features.
- `resources/js/app.js` or existing Echo bootstrap file: add seminar channel listeners only if the project does not already initialize them on the Blade page.

---

### Task 1: Add Seminar Config, Enums, And Agora Service Config

**Files:**
- Create: `config/seminars.php`
- Create: `app/Enums/SeminarStatus.php`
- Create: `app/Enums/SeminarType.php`
- Create: `app/Enums/SeminarParticipantType.php`
- Create: `app/Enums/SeminarInteractionStatus.php`
- Modify: `config/services.php`
- Test: `tests/Unit/Services/Seminars/SeminarAccessServiceTest.php`

- [ ] **Step 1: Write enum/config tests**

Create `tests/Unit/Services/Seminars/SeminarAccessServiceTest.php` with assertions for allowed statuses, types, participant targets, learner age categories, default join window, default attendance threshold, and Agora config keys.

```php
public function test_seminar_config_exposes_expected_values(): void
{
    $this->assertSame(['kids', 'teen', 'adult'], array_keys(config('seminars.learner_age_categories')));
    $this->assertSame(15, config('seminars.join_window_before_minutes'));
    $this->assertSame(5, config('seminars.attendance.minimum_minutes'));
    $this->assertSame('webinar', \App\Enums\SeminarType::Webinar->value);
    $this->assertSame('published', \App\Enums\SeminarStatus::Published->value);
}
```

- [ ] **Step 2: Run test to verify failure**

Run:

```bash
php artisan test tests/Unit/Services/Seminars/SeminarAccessServiceTest.php
```

Expected: FAIL because config and enums do not exist.

- [ ] **Step 3: Add `config/seminars.php`**

```php
<?php

return [
    'categories' => [
        'education' => 'Education',
        'awareness' => 'Awareness',
        'health' => 'Health',
        'community' => 'Community',
        'other' => 'Other',
    ],
    'learner_age_categories' => [
        'kids' => 'Kids',
        'teen' => 'Teen',
        'adult' => 'Adult',
    ],
    'join_window_before_minutes' => 15,
    'attendance' => [
        'minimum_minutes' => 5,
    ],
    'reminders' => [
        'minutes_before_start' => 60,
    ],
];
```

- [ ] **Step 4: Add enums**

Add backed string enums:

```php
enum SeminarStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Cancelled = 'cancelled';
    case Completed = 'completed';
}
```

```php
enum SeminarType: string
{
    case Webinar = 'webinar';
    case Physical = 'physical';
}
```

```php
enum SeminarParticipantType: string
{
    case Learners = 'learners';
    case Instructors = 'instructors';
    case LearnersAndInstructors = 'learners_and_instructors';
}
```

```php
enum SeminarInteractionStatus: string
{
    case Visible = 'visible';
    case Pending = 'pending';
    case Answered = 'answered';
    case Hidden = 'hidden';
}
```

- [ ] **Step 5: Add Agora config to `config/services.php`**

Add:

```php
'agora' => [
    'app_id' => env('AGORA_APP_ID'),
    'app_certificate' => env('AGORA_APP_CERTIFICATE'),
    'token_ttl_seconds' => (int) env('AGORA_TOKEN_TTL_SECONDS', 900),
],
```

- [ ] **Step 6: Run test to verify pass**

Run:

```bash
php artisan test tests/Unit/Services/Seminars/SeminarAccessServiceTest.php
```

Expected: PASS.

- [ ] **Step 7: Commit**

```bash
git add config/seminars.php config/services.php app/Enums/SeminarStatus.php app/Enums/SeminarType.php app/Enums/SeminarParticipantType.php app/Enums/SeminarInteractionStatus.php tests/Unit/Services/Seminars/SeminarAccessServiceTest.php
git commit -m "feat: add seminar config and enums"
```

---

### Task 2: Extend Seminar Schema And Models

**Files:**
- Create: `database/migrations/2026_06_02_000001_extend_seminars_for_connectors.php`
- Create: `database/migrations/2026_06_02_000002_create_seminar_speakers_table.php`
- Create: `database/migrations/2026_06_02_000003_create_seminar_comments_table.php`
- Create: `database/migrations/2026_06_02_000004_create_seminar_questions_table.php`
- Create: `database/migrations/2026_06_02_000005_create_seminar_attendances_table.php`
- Create: `app/Models/SeminarSpeaker.php`
- Create: `app/Models/SeminarComment.php`
- Create: `app/Models/SeminarQuestion.php`
- Create: `app/Models/SeminarAttendance.php`
- Modify: `app/Models/Seminar.php`
- Modify: `app/Models/SeminarRegistrant.php`
- Modify: `app/Models/Connector.php`
- Test: `tests/Feature/Connectors/ConnectorSeminarManagementTest.php`

- [ ] **Step 1: Write failing schema relationship test**

Create a test that creates a verified connector, creates a seminar with connector ownership, adds a registrant, speaker, comment, question, and attendance record, then asserts relationships resolve correctly.

```php
$seminar = Seminar::factory()->create([
    'connector_id' => $connector->id,
    'type' => 'webinar',
    'status' => 'draft',
    'target_participants' => 'learners_and_instructors',
    'learner_age_categories' => ['kids', 'teen'],
]);

$this->assertTrue($connector->seminars()->whereKey($seminar)->exists());
$this->assertSame($connector->id, $seminar->connector->id);
```

- [ ] **Step 2: Run test to verify failure**

Run:

```bash
php artisan test tests/Feature/Connectors/ConnectorSeminarManagementTest.php
```

Expected: FAIL because schema and relationships are missing.

- [ ] **Step 3: Add seminar extension migration**

Add columns to `seminars`: `connector_id`, `type`, `purpose`, `category`, `status`, `starts_at`, `ends_at`, `capacity`, `target_participants`, `learner_age_categories`, `livestream_channel`, `cancelled_at`, `cancelled_by`, `cancellation_reason`, `completed_at`, `completed_by`, `admin_moderation_status`, `admin_moderation_reason`.

Use indexes for `connector_id`, `status`, `type`, `starts_at`, `category`, and `admin_moderation_status`.

- [ ] **Step 4: Add supporting migrations**

Create:

- `seminar_speakers`: seminar, optional user, display name, title, bio, role, timestamps.
- `seminar_comments`: seminar, user, body, status, hidden_by, hidden_at, hidden_reason, timestamps.
- `seminar_questions`: seminar, user, question, status, answer, answered_by, answered_at, hidden metadata, pinned flag, timestamps.
- `seminar_attendances`: seminar, user, joined_at, left_at, total_seconds, status, timestamps.

Add unique constraints for one user speaker assignment per seminar and one attendance summary per seminar/user.

- [ ] **Step 5: Update models**

Add fillable fields, casts, and relationships. `Seminar` should have `connector()`, `registrants()`, `speakers()`, `comments()`, `questions()`, `attendances()`, `scopePublished()`, `scopeUpcoming()`, and `scopeOwnedByConnector()`.

- [ ] **Step 6: Run schema relationship test**

Run:

```bash
php artisan test tests/Feature/Connectors/ConnectorSeminarManagementTest.php
```

Expected: PASS for relationship coverage.

- [ ] **Step 7: Commit**

```bash
git add database/migrations app/Models/Seminar.php app/Models/SeminarRegistrant.php app/Models/Connector.php app/Models/SeminarSpeaker.php app/Models/SeminarComment.php app/Models/SeminarQuestion.php app/Models/SeminarAttendance.php tests/Feature/Connectors/ConnectorSeminarManagementTest.php
git commit -m "feat: extend seminar data model for connectors"
```

---

### Task 3: Implement Connector Seminar Management

**Files:**
- Create: `app/Http/Controllers/Connector/SeminarController.php`
- Create: `app/Http/Requests/Connector/StoreSeminarRequest.php`
- Create: `app/Http/Requests/Connector/UpdateSeminarRequest.php`
- Create: `app/Services/Seminars/SeminarAccessService.php`
- Create: `resources/views/connectors/seminars/index.blade.php`
- Create: `resources/views/connectors/seminars/create.blade.php`
- Create: `resources/views/connectors/seminars/edit.blade.php`
- Create: `resources/views/connectors/seminars/show.blade.php`
- Modify: `routes/connector.php`
- Modify: `resources/views/layouts/connector-app.blade.php`
- Test: `tests/Feature/Connectors/ConnectorSeminarManagementTest.php`

- [ ] **Step 1: Add failing management tests**

Cover: unverified connector cannot create, member without `connector.manage_seminars` gets 403, authorized member can create draft, publish, cancel with reason, complete, and cannot manage another connector's seminar.

- [ ] **Step 2: Run tests to verify failure**

```bash
php artisan test tests/Feature/Connectors/ConnectorSeminarManagementTest.php
```

Expected: FAIL because controller, routes, service, and views are incomplete.

- [ ] **Step 3: Implement `SeminarAccessService` connector methods**

Add methods:

```php
public function canManageConnectorSeminars(User $user, Connector $connector): bool;
public function abortUnlessCanManageConnectorSeminars(User $user, Connector $connector): void;
public function abortUnlessConnectorOwnsSeminar(Connector $connector, Seminar $seminar): void;
```

Use `ConnectorAccessService::hasPermission($user, $connector, 'connector.manage_seminars')`.

- [ ] **Step 4: Implement create/update request validation**

Validate title, description, purpose, type, category, starts_at, ends_at, capacity, target_participants, learner_age_categories, and location for physical seminars. Require `ends_at` after `starts_at`. Require at least one learner age category when target includes learners.

- [ ] **Step 5: Implement controller actions**

Methods:

- `index`
- `create`
- `store`
- `show`
- `edit`
- `update`
- `publish`
- `cancel`
- `complete`

Generate `livestream_channel` for webinars during create if not present. Use a stable prefix such as `seminar-{id}` after create, or a random unique value stored server-side.

- [ ] **Step 6: Add connector routes**

Replace the current seminar stub route with:

```php
Route::get('/connector/{connector}/seminars', [SeminarController::class, 'index'])->name('connector.seminars.index');
Route::get('/connector/{connector}/seminars/create', [SeminarController::class, 'create'])->name('connector.seminars.create');
Route::post('/connector/{connector}/seminars', [SeminarController::class, 'store'])->name('connector.seminars.store');
Route::get('/connector/{connector}/seminars/{seminar}', [SeminarController::class, 'show'])->name('connector.seminars.show');
Route::get('/connector/{connector}/seminars/{seminar}/edit', [SeminarController::class, 'edit'])->name('connector.seminars.edit');
Route::put('/connector/{connector}/seminars/{seminar}', [SeminarController::class, 'update'])->name('connector.seminars.update');
Route::post('/connector/{connector}/seminars/{seminar}/publish', [SeminarController::class, 'publish'])->name('connector.seminars.publish');
Route::post('/connector/{connector}/seminars/{seminar}/cancel', [SeminarController::class, 'cancel'])->name('connector.seminars.cancel');
Route::post('/connector/{connector}/seminars/{seminar}/complete', [SeminarController::class, 'complete'])->name('connector.seminars.complete');
```

- [ ] **Step 7: Add Blade pages**

Follow existing connector layout. Show actions only when the user can manage seminars. Keep publish/cancel/complete buttons backed by real routes.

- [ ] **Step 8: Run tests**

```bash
php artisan test tests/Feature/Connectors/ConnectorSeminarManagementTest.php
```

Expected: PASS.

- [ ] **Step 9: Commit**

```bash
git add app/Http/Controllers/Connector/SeminarController.php app/Http/Requests/Connector/StoreSeminarRequest.php app/Http/Requests/Connector/UpdateSeminarRequest.php app/Services/Seminars/SeminarAccessService.php resources/views/connectors/seminars resources/views/layouts/connector-app.blade.php routes/connector.php tests/Feature/Connectors/ConnectorSeminarManagementTest.php
git commit -m "feat: add connector seminar management"
```

---

### Task 4: Implement Participant Browsing And Registration

**Files:**
- Create: `app/Http/Controllers/SeminarBrowseController.php`
- Create: `app/Http/Requests/Seminars/RegisterSeminarRequest.php`
- Create: `app/Services/Seminars/SeminarRegistrationService.php`
- Create: `resources/views/seminars/index.blade.php`
- Create: `resources/views/seminars/show.blade.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/Seminars/SeminarRegistrationTest.php`

- [ ] **Step 1: Write failing registration tests**

Cover learner and instructor browsing, learner age category eligibility, registration success, duplicate prevention, capacity reached, registration close at start time, and cancellation before start.

- [ ] **Step 2: Run tests to verify failure**

```bash
php artisan test tests/Feature/Seminars/SeminarRegistrationTest.php
```

Expected: FAIL because routes and service are missing.

- [ ] **Step 3: Implement `SeminarRegistrationService`**

Methods:

```php
public function canRegister(User $user, Seminar $seminar): bool;
public function register(User $user, Seminar $seminar): SeminarRegistrant;
public function cancel(User $user, Seminar $seminar): void;
```

Checks: `published`, not started, participant type, learner age category, capacity, duplicate active registration.

- [ ] **Step 4: Add participant routes**

```php
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/seminars', [SeminarBrowseController::class, 'index'])->name('seminars.index');
    Route::get('/seminars/{seminar}', [SeminarBrowseController::class, 'show'])->name('seminars.show');
    Route::post('/seminars/{seminar}/register', [SeminarBrowseController::class, 'register'])->name('seminars.register');
    Route::post('/seminars/{seminar}/cancel-registration', [SeminarBrowseController::class, 'cancelRegistration'])->name('seminars.cancel-registration');
});
```

- [ ] **Step 5: Add participant views**

Use shared authenticated UI conventions. Show register/cancel/join state based on service-provided booleans. Do not show ineligible seminars in the main list unless filters explicitly include unavailable items.

- [ ] **Step 6: Run tests**

```bash
php artisan test tests/Feature/Seminars/SeminarRegistrationTest.php
```

Expected: PASS.

- [ ] **Step 7: Commit**

```bash
git add app/Http/Controllers/SeminarBrowseController.php app/Http/Requests/Seminars/RegisterSeminarRequest.php app/Services/Seminars/SeminarRegistrationService.php resources/views/seminars/index.blade.php resources/views/seminars/show.blade.php routes/web.php tests/Feature/Seminars/SeminarRegistrationTest.php
git commit -m "feat: add seminar browsing and registration"
```

---

### Task 5: Implement Speaker Assignment

**Files:**
- Create: `app/Http/Controllers/Connector/SeminarSpeakerController.php`
- Create: `app/Http/Requests/Connector/StoreSeminarSpeakerRequest.php`
- Create: `app/Services/Seminars/SeminarSpeakerService.php`
- Modify: `resources/views/connectors/seminars/show.blade.php`
- Modify: `routes/connector.php`
- Test: `tests/Feature/Connectors/ConnectorSeminarManagementTest.php`

- [ ] **Step 1: Add failing speaker tests**

Cover assigning an instructor user, assigning a non-instructor platform user, creating an external display-only speaker, rejecting duplicate platform speaker assignment, and blocking speaker edits for another connector.

- [ ] **Step 2: Run test to verify failure**

```bash
php artisan test tests/Feature/Connectors/ConnectorSeminarManagementTest.php --filter=speaker
```

Expected: FAIL because speaker routes and service do not exist.

- [ ] **Step 3: Implement speaker service**

Methods:

```php
public function addPlatformSpeaker(Seminar $seminar, User $user, array $attributes): SeminarSpeaker;
public function addExternalSpeaker(Seminar $seminar, array $attributes): SeminarSpeaker;
public function removeSpeaker(Seminar $seminar, SeminarSpeaker $speaker): void;
public function isSpeaker(User $user, Seminar $seminar): bool;
```

- [ ] **Step 4: Implement controller and routes**

Add:

```php
Route::post('/connector/{connector}/seminars/{seminar}/speakers', [SeminarSpeakerController::class, 'store'])->name('connector.seminars.speakers.store');
Route::delete('/connector/{connector}/seminars/{seminar}/speakers/{speaker}', [SeminarSpeakerController::class, 'destroy'])->name('connector.seminars.speakers.destroy');
```

- [ ] **Step 5: Update connector seminar detail**

Show assigned platform speakers first, prioritize instructor users in search/select UI, and show external speakers as display-only. Label external speakers clearly as display-only.

- [ ] **Step 6: Run tests**

```bash
php artisan test tests/Feature/Connectors/ConnectorSeminarManagementTest.php
```

Expected: PASS.

- [ ] **Step 7: Commit**

```bash
git add app/Http/Controllers/Connector/SeminarSpeakerController.php app/Http/Requests/Connector/StoreSeminarSpeakerRequest.php app/Services/Seminars/SeminarSpeakerService.php resources/views/connectors/seminars/show.blade.php routes/connector.php tests/Feature/Connectors/ConnectorSeminarManagementTest.php
git commit -m "feat: add seminar speaker assignment"
```

---

### Task 6: Implement Agora Token And Livestream Join Flow

**Files:**
- Create: `app/Http/Controllers/Connector/SeminarLivestreamController.php`
- Create: `app/Services/Seminars/AgoraTokenService.php`
- Create: `resources/views/connectors/seminars/livestream.blade.php`
- Create: `resources/views/seminars/join.blade.php`
- Modify: `app/Http/Controllers/SeminarBrowseController.php`
- Modify: `routes/connector.php`
- Modify: `routes/web.php`
- Test: `tests/Unit/Services/Seminars/AgoraTokenServiceTest.php`
- Test: `tests/Feature/Seminars/SeminarLivestreamAccessTest.php`

- [ ] **Step 1: Write failing Agora unit tests**

Cover missing config fails closed, audience receives subscribe-only role, host and speaker receive publish role, token TTL is read from config, and token channel matches the seminar livestream channel.

- [ ] **Step 2: Write failing livestream feature tests**

Cover registered participant can join 15 minutes before start, unregistered user cannot join, audience cannot request publisher token, assigned speaker can publish, external speaker cannot join, host can publish, and access closes after end/cancel/complete.

- [ ] **Step 3: Run tests to verify failure**

```bash
php artisan test tests/Unit/Services/Seminars/AgoraTokenServiceTest.php tests/Feature/Seminars/SeminarLivestreamAccessTest.php
```

Expected: FAIL because service and routes are missing.

- [ ] **Step 4: Implement token service boundary**

Create:

```php
public function tokenFor(User $user, Seminar $seminar, string $role): array;
public function agoraUidFor(User $user): int;
public function canJoinAsAudience(User $user, Seminar $seminar): bool;
public function canPublish(User $user, Seminar $seminar): bool;
```

Return `app_id`, `channel`, `uid`, `role`, `token`, and `expires_at`. Use a small wrapper method for Agora token building so tests can mock the generated token string without calling the network.

- [ ] **Step 5: Implement participant join route**

Add:

```php
Route::get('/seminars/{seminar}/join', [SeminarBrowseController::class, 'join'])->name('seminars.join');
Route::post('/seminars/{seminar}/agora-token', [SeminarBrowseController::class, 'agoraToken'])->name('seminars.agora-token');
```

- [ ] **Step 6: Implement connector host/speaker route**

Add:

```php
Route::get('/connector/{connector}/seminars/{seminar}/livestream', [SeminarLivestreamController::class, 'show'])->name('connector.seminars.livestream');
Route::post('/connector/{connector}/seminars/{seminar}/agora-token', [SeminarLivestreamController::class, 'token'])->name('connector.seminars.agora-token');
```

- [ ] **Step 7: Add livestream Blade pages**

Load Agora client script through the view or Vite asset path chosen by the project. Host/speaker controls should be shown only for publish-capable roles. Audience view should not render publish controls.

- [ ] **Step 8: Run tests**

```bash
php artisan test tests/Unit/Services/Seminars/AgoraTokenServiceTest.php tests/Feature/Seminars/SeminarLivestreamAccessTest.php
```

Expected: PASS.

- [ ] **Step 9: Commit**

```bash
git add app/Http/Controllers/Connector/SeminarLivestreamController.php app/Services/Seminars/AgoraTokenService.php resources/views/connectors/seminars/livestream.blade.php resources/views/seminars/join.blade.php app/Http/Controllers/SeminarBrowseController.php routes/connector.php routes/web.php tests/Unit/Services/Seminars/AgoraTokenServiceTest.php tests/Feature/Seminars/SeminarLivestreamAccessTest.php
git commit -m "feat: add seminar agora livestream access"
```

---

### Task 7: Implement Realtime Comments, Q&A, And Viewer Count

**Files:**
- Create: `app/Http/Controllers/SeminarInteractionController.php`
- Create: `app/Http/Controllers/Connector/SeminarInteractionController.php`
- Create: `app/Http/Requests/Seminars/StoreSeminarCommentRequest.php`
- Create: `app/Http/Requests/Seminars/StoreSeminarQuestionRequest.php`
- Create: `app/Services/Seminars/SeminarInteractionService.php`
- Modify: `routes/web.php`
- Modify: `routes/connector.php`
- Modify: `routes/channels.php`
- Modify: `resources/views/seminars/join.blade.php`
- Modify: `resources/views/connectors/seminars/livestream.blade.php`
- Test: `tests/Feature/Seminars/SeminarInteractionTest.php`

- [ ] **Step 1: Write failing interaction tests**

Cover registered user can post comment and question during join window, unregistered user cannot post, connector seminar manager can hide comment/question, admin can hide comment/question, hidden records keep moderator metadata, and completed seminar interactions are read-only for participants.

- [ ] **Step 2: Run tests to verify failure**

```bash
php artisan test tests/Feature/Seminars/SeminarInteractionTest.php
```

Expected: FAIL because interaction routes and service are missing.

- [ ] **Step 3: Implement interaction service**

Methods:

```php
public function postComment(User $user, Seminar $seminar, string $body): SeminarComment;
public function postQuestion(User $user, Seminar $seminar, string $question): SeminarQuestion;
public function hideComment(User $moderator, SeminarComment $comment, string $reason): SeminarComment;
public function hideQuestion(User $moderator, SeminarQuestion $question, string $reason): SeminarQuestion;
public function markQuestionAnswered(User $moderator, SeminarQuestion $question, string $answer): SeminarQuestion;
```

- [ ] **Step 4: Add interaction routes**

Participant routes:

```php
Route::post('/seminars/{seminar}/comments', [SeminarInteractionController::class, 'storeComment'])->name('seminars.comments.store');
Route::post('/seminars/{seminar}/questions', [SeminarInteractionController::class, 'storeQuestion'])->name('seminars.questions.store');
```

Connector moderation routes:

```php
Route::post('/connector/{connector}/seminars/{seminar}/comments/{comment}/hide', [\App\Http\Controllers\Connector\SeminarInteractionController::class, 'hideComment'])->name('connector.seminars.comments.hide');
Route::post('/connector/{connector}/seminars/{seminar}/questions/{question}/hide', [\App\Http\Controllers\Connector\SeminarInteractionController::class, 'hideQuestion'])->name('connector.seminars.questions.hide');
Route::post('/connector/{connector}/seminars/{seminar}/questions/{question}/answer', [\App\Http\Controllers\Connector\SeminarInteractionController::class, 'answerQuestion'])->name('connector.seminars.questions.answer');
```

- [ ] **Step 5: Add broadcast channel authorization**

In `routes/channels.php`, authorize seminar channels for registered participants, hosts, speakers, connector seminar managers, and admins:

```php
Broadcast::channel('seminars.{seminarId}', function (User $user, int $seminarId) {
    $seminar = \App\Models\Seminar::find($seminarId);
    return $seminar && app(\App\Services\Seminars\SeminarAccessService::class)->canViewLiveChannel($user, $seminar);
});
```

- [ ] **Step 6: Add Blade interaction panels**

Render separate Comments and Q&A panels. Use Alpine to append broadcasted events and submit forms. Render hidden records only for moderators.

- [ ] **Step 7: Run tests**

```bash
php artisan test tests/Feature/Seminars/SeminarInteractionTest.php
```

Expected: PASS.

- [ ] **Step 8: Commit**

```bash
git add app/Http/Controllers/SeminarInteractionController.php app/Http/Controllers/Connector/SeminarInteractionController.php app/Http/Requests/Seminars/StoreSeminarCommentRequest.php app/Http/Requests/Seminars/StoreSeminarQuestionRequest.php app/Services/Seminars/SeminarInteractionService.php routes/web.php routes/connector.php routes/channels.php resources/views/seminars/join.blade.php resources/views/connectors/seminars/livestream.blade.php tests/Feature/Seminars/SeminarInteractionTest.php
git commit -m "feat: add seminar realtime interactions"
```

---

### Task 8: Implement Attendance Tracking

**Files:**
- Create: `app/Http/Controllers/SeminarAttendanceController.php`
- Create: `app/Http/Controllers/Connector/SeminarAttendanceController.php`
- Create: `app/Services/Seminars/SeminarAttendanceService.php`
- Create: `resources/views/connectors/seminars/attendance.blade.php`
- Modify: `resources/views/seminars/join.blade.php`
- Modify: `resources/views/connectors/seminars/livestream.blade.php`
- Modify: `routes/web.php`
- Modify: `routes/connector.php`
- Test: `tests/Unit/Services/Seminars/SeminarAttendanceServiceTest.php`
- Test: `tests/Feature/Seminars/SeminarAttendanceTest.php`

- [ ] **Step 1: Write failing attendance tests**

Cover join creates/updates attendance, leave stores left time and total duration, multiple sessions aggregate duration, five minutes marks attended, under five minutes remains joined/left, connector can view attendance only for owned seminar, and completion finalizes attendance.

- [ ] **Step 2: Run tests to verify failure**

```bash
php artisan test tests/Unit/Services/Seminars/SeminarAttendanceServiceTest.php tests/Feature/Seminars/SeminarAttendanceTest.php
```

Expected: FAIL because attendance service and endpoints are missing.

- [ ] **Step 3: Implement attendance service**

Methods:

```php
public function recordJoin(User $user, Seminar $seminar): SeminarAttendance;
public function recordLeave(User $user, Seminar $seminar): SeminarAttendance;
public function heartbeat(User $user, Seminar $seminar): SeminarAttendance;
public function finalize(Seminar $seminar): void;
```

Use `config('seminars.attendance.minimum_minutes')` to set attended status.

- [ ] **Step 4: Add attendance endpoints**

Participant routes:

```php
Route::post('/seminars/{seminar}/attendance/join', [SeminarAttendanceController::class, 'join'])->name('seminars.attendance.join');
Route::post('/seminars/{seminar}/attendance/heartbeat', [SeminarAttendanceController::class, 'heartbeat'])->name('seminars.attendance.heartbeat');
Route::post('/seminars/{seminar}/attendance/leave', [SeminarAttendanceController::class, 'leave'])->name('seminars.attendance.leave');
```

Connector route:

```php
Route::get('/connector/{connector}/seminars/{seminar}/attendance', [\App\Http\Controllers\Connector\SeminarAttendanceController::class, 'index'])->name('connector.seminars.attendance');
```

- [ ] **Step 5: Add attendance calls to join pages**

Use Alpine lifecycle hooks to call join on page load, heartbeat periodically, and leave on page unload or explicit leave action. Keep server authorization as the source of truth.

- [ ] **Step 6: Add connector attendance page**

Show user, role, joined time, left time, total duration, and attendance status. Link export action from Task 10 when export service exists.

- [ ] **Step 7: Run tests**

```bash
php artisan test tests/Unit/Services/Seminars/SeminarAttendanceServiceTest.php tests/Feature/Seminars/SeminarAttendanceTest.php
```

Expected: PASS.

- [ ] **Step 8: Commit**

```bash
git add app/Http/Controllers/SeminarAttendanceController.php app/Http/Controllers/Connector/SeminarAttendanceController.php app/Services/Seminars/SeminarAttendanceService.php resources/views/connectors/seminars/attendance.blade.php resources/views/seminars/join.blade.php resources/views/connectors/seminars/livestream.blade.php routes/web.php routes/connector.php tests/Unit/Services/Seminars/SeminarAttendanceServiceTest.php tests/Feature/Seminars/SeminarAttendanceTest.php
git commit -m "feat: add seminar attendance tracking"
```

---

### Task 9: Implement Admin Seminar Moderation

**Files:**
- Create: `app/Http/Controllers/Admin/SeminarModerationController.php`
- Create: `resources/views/admin/seminars/index.blade.php`
- Create: `resources/views/admin/seminars/show.blade.php`
- Modify: `routes/admin.php`
- Modify: `resources/views/layouts/admin.blade.php`
- Test: `tests/Feature/Admin/AdminSeminarModerationTest.php`

- [ ] **Step 1: Write failing admin moderation tests**

Cover non-admin denial, admin list filters, admin detail view, admin cancellation/suspension with required reason, hiding comments/questions, attendance visibility, and connector ownership displayed.

- [ ] **Step 2: Run test to verify failure**

```bash
php artisan test tests/Feature/Admin/AdminSeminarModerationTest.php
```

Expected: FAIL because admin moderation routes and views are missing.

- [ ] **Step 3: Implement admin controller**

Methods:

- `index`
- `show`
- `cancel`
- `hideComment`
- `hideQuestion`
- `answerQuestion`

Use existing admin middleware/permission conventions already used in `routes/admin.php`.

- [ ] **Step 4: Add admin routes**

Under the existing admin middleware group:

```php
Route::prefix('seminars')->name('seminars.')->group(function () {
    Route::get('/', [SeminarModerationController::class, 'index'])->name('index');
    Route::get('/{seminar}', [SeminarModerationController::class, 'show'])->name('show');
    Route::post('/{seminar}/cancel', [SeminarModerationController::class, 'cancel'])->name('cancel');
    Route::post('/{seminar}/comments/{comment}/hide', [SeminarModerationController::class, 'hideComment'])->name('comments.hide');
    Route::post('/{seminar}/questions/{question}/hide', [SeminarModerationController::class, 'hideQuestion'])->name('questions.hide');
    Route::post('/{seminar}/questions/{question}/answer', [SeminarModerationController::class, 'answerQuestion'])->name('questions.answer');
});
```

- [ ] **Step 5: Add admin pages and navigation**

Follow the admin moderation table/detail patterns already used for centralized moderation pages. Include filters by status, connector, category, date, and moderation state. Add navigation under the same admin governance section that links to moderation and suspension tools.

- [ ] **Step 6: Run tests**

```bash
php artisan test tests/Feature/Admin/AdminSeminarModerationTest.php
```

Expected: PASS.

- [ ] **Step 7: Commit**

```bash
git add app/Http/Controllers/Admin/SeminarModerationController.php resources/views/admin/seminars routes/admin.php resources/views/layouts/admin.blade.php tests/Feature/Admin/AdminSeminarModerationTest.php
git commit -m "feat: add admin seminar moderation"
```

---

### Task 10: Add Notifications And CSV Exports

**Files:**
- Create: `app/Services/Seminars/SeminarExportService.php`
- Create: `app/Notifications/Seminars/SeminarRegistrationConfirmedNotification.php`
- Create: `app/Notifications/Seminars/SeminarReminderNotification.php`
- Create: `app/Notifications/Seminars/SeminarCancelledNotification.php`
- Create: `app/Notifications/Seminars/SeminarSpeakerAssignedNotification.php`
- Modify: `app/Http/Controllers/Connector/SeminarRegistrantController.php`
- Modify: `app/Http/Controllers/Connector/SeminarAttendanceController.php`
- Modify: `app/Services/Seminars/SeminarRegistrationService.php`
- Modify: `app/Services/Seminars/SeminarSpeakerService.php`
- Modify: `app/Http/Controllers/Connector/SeminarController.php`
- Modify: `routes/connector.php`
- Test: `tests/Feature/Seminars/SeminarRegistrationTest.php`
- Test: `tests/Feature/Seminars/SeminarAttendanceTest.php`

- [ ] **Step 1: Add failing notification/export tests**

Cover registration notification, cancellation notification to registrants, speaker assignment notification for platform-user speakers, registrants CSV export, attendance CSV export, and export denial for another connector.

- [ ] **Step 2: Run tests to verify failure**

```bash
php artisan test tests/Feature/Seminars/SeminarRegistrationTest.php tests/Feature/Seminars/SeminarAttendanceTest.php --filter=notification
```

Expected: FAIL because notifications and exports are missing.

- [ ] **Step 3: Implement notifications**

Use the database notification channel and mail channel when the target user already receives mail notifications elsewhere in the app. Message content should include seminar title, connector name, schedule, and action-specific state.

- [ ] **Step 4: Implement export service**

Methods:

```php
public function registrantsCsv(Seminar $seminar): StreamedResponse;
public function attendanceCsv(Seminar $seminar): StreamedResponse;
```

CSV columns:

- Registrants: name, email, participant type, learner age category, status, registered at, cancelled at.
- Attendance: name, email, role, joined at, left at, total minutes, status.

- [ ] **Step 5: Add export routes**

```php
Route::get('/connector/{connector}/seminars/{seminar}/registrants/export', [SeminarRegistrantController::class, 'export'])->name('connector.seminars.registrants.export');
Route::get('/connector/{connector}/seminars/{seminar}/attendance/export', [SeminarAttendanceController::class, 'export'])->name('connector.seminars.attendance.export');
```

- [ ] **Step 6: Wire notifications to services**

Send registration confirmation in `SeminarRegistrationService::register`, speaker assignment in `SeminarSpeakerService`, and cancellation notices in connector/admin cancellation actions.

- [ ] **Step 7: Run tests**

```bash
php artisan test tests/Feature/Seminars/SeminarRegistrationTest.php tests/Feature/Seminars/SeminarAttendanceTest.php tests/Feature/Connectors/ConnectorSeminarManagementTest.php
```

Expected: PASS.

- [ ] **Step 8: Commit**

```bash
git add app/Services/Seminars/SeminarExportService.php app/Notifications/Seminars app/Http/Controllers/Connector/SeminarRegistrantController.php app/Http/Controllers/Connector/SeminarAttendanceController.php app/Services/Seminars/SeminarRegistrationService.php app/Services/Seminars/SeminarSpeakerService.php app/Http/Controllers/Connector/SeminarController.php routes/connector.php tests/Feature/Seminars/SeminarRegistrationTest.php tests/Feature/Seminars/SeminarAttendanceTest.php tests/Feature/Connectors/ConnectorSeminarManagementTest.php
git commit -m "feat: add seminar notifications and exports"
```

---

### Task 11: Final Verification And UI Polish

**Files:**
- Modify: `resources/views/connectors/seminars/index.blade.php`
- Modify: `resources/views/connectors/seminars/create.blade.php`
- Modify: `resources/views/connectors/seminars/edit.blade.php`
- Modify: `resources/views/connectors/seminars/show.blade.php`
- Modify: `resources/views/connectors/seminars/attendance.blade.php`
- Modify: `resources/views/connectors/seminars/livestream.blade.php`
- Modify: `resources/views/seminars/index.blade.php`
- Modify: `resources/views/seminars/show.blade.php`
- Modify: `resources/views/seminars/join.blade.php`
- Modify: `resources/views/admin/seminars/index.blade.php`
- Modify: `resources/views/admin/seminars/show.blade.php`
- Modify: `resources/js/app.js`
- Test: all seminar-related tests.

- [ ] **Step 1: Run focused backend tests**

```bash
php artisan test tests/Feature/Connectors/ConnectorSeminarManagementTest.php tests/Feature/Seminars/SeminarRegistrationTest.php tests/Feature/Seminars/SeminarLivestreamAccessTest.php tests/Feature/Seminars/SeminarInteractionTest.php tests/Feature/Seminars/SeminarAttendanceTest.php tests/Feature/Admin/AdminSeminarModerationTest.php tests/Unit/Services/Seminars
```

Expected: PASS.

- [ ] **Step 2: Run broader relevant tests**

```bash
php artisan test tests/Feature/Connectors tests/Feature/Seminars tests/Feature/Admin tests/Unit/Services/Seminars
```

Expected: PASS or documented unrelated pre-existing failures.

- [ ] **Step 3: Run frontend build**

```bash
npm run build
```

Expected: build completes without errors.

- [ ] **Step 4: Manually verify key browser flows**

Verify:

- Connector seminar list loads.
- Connector can create draft webinar.
- Connector can publish webinar.
- Learner matching selected age category can register.
- Learner outside selected age category cannot register.
- Registered participant sees join state.
- Host/speaker/audience pages render different controls.
- Comments and Q&A panels render.
- Connector attendance page renders.
- Admin seminar moderation list and detail render.

- [ ] **Step 5: Commit final polish**

```bash
git add resources/views resources/js tests
git commit -m "chore: polish connector seminar experience"
```

---

## Self-Review Checklist

- Spec coverage: every design section maps to at least one task.
- Payment scope: paid seminars are not included in this implementation plan.
- Recording scope: Agora Cloud Recording is not included.
- Ownership: every connector route checks connector ownership.
- Permissions: connector management uses `connector.manage_seminars`.
- Eligibility: learner, instructor, and learner age category checks are covered.
- Livestream safety: token generation is server-side and role-specific.
- Realtime safety: interactions are persisted and moderateable.
- Attendance: join/leave duration and threshold behavior are covered.
- Tests: Agora tests mock token generation and do not call the Agora network.
