# Educational Events and External Delivery Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Let connectors publish in-person seminars and externally delivered seminars or webinars, protect and update external links, notify eligible people, and record code or manual attendance while preserving legacy Agora webinars.

**Architecture:** Extend the existing `seminars` domain with a separate `event_format`, nullable delivery fields, and attendance provenance. Keep governance, registration, speakers, notifications, routes, and Agora services; put access decisions in focused seminar services and keep controllers thin. One forward migration preserves legacy data, and each feature is introduced behind tests before the related UI is changed.

**Tech Stack:** Laravel, PHP, Eloquent/MySQL, Blade/Alpine, Laravel notifications/scheduler/rate limiter, PHPUnit. No new provider SDK or dependency.

## Global Constraints

- Approved design: `docs/superpowers/specs/2026-09-28-educational-events-external-delivery-design.md`.
- Keep `seminars`, connector ownership, speaker assignments, governance states, and existing `/seminars` routes.
- New authoring allows `seminar/in_person`, `seminar/external`, and `webinar/external`; existing `webinar/native` stays functional but is hidden from creation.
- Persist dates in UTC and accept/show Philippine local time using `config('app.display_timezone')`.
- `description` is required for new writes; `purpose` is labeled Objectives; `location` is Venue Name; `schedule` mirrors `starts_at`.
- Participant external-link access requires an active confirmed registration and current eligibility. Accepted speakers can access before release; managers/admins use authorized management screens.
- Link-change, reminder, and release notices contain a protected event-page route, never `external_url`; published/completed link changes reach eligible confirmed registrants and accepted speakers, with organizer copied for admin edits.
- Codes are eight numeric digits, hashed at rest, shown once, optional on in-person/external events, with five failed attempts per user/event and twenty per IP in ten minutes.
- Preserve all existing development data. Never run `migrate:fresh`, `db:wipe`, destructive seeders, or destructive tests against the development database. Run tests only on the isolated `cc_db_test` configured by `phpunit.xml`; use normal forward migrations on development.
- Do not create a new event system, provider integration, recording, conferencing, QR workflow, or payment feature.

---

## File map and delivery order

| Area | Files and responsibility |
| --- | --- |
| Schema | `database/migrations/2026_09_28_000001_extend_seminars_for_external_delivery.php`: nullable event/delivery/code/notice columns, attendance provenance, idempotent legacy backfill, guarded rollback. |
| Domain models | `app/Enums/SeminarType.php`, new `app/Enums/SeminarFormat.php`, `app/Models/Seminar.php`, `app/Models/SeminarAttendance.php`: allowed values, casts, sensitive JSON hiding, format helpers. |
| Authoring | `app/Http/Requests/Connector/StoreSeminarRequest.php`, `UpdateSeminarRequest.php`, `app/Services/Seminars/SeminarPublicationValidator.php`, `app/Http/Controllers/Connector/SeminarController.php`, `SeminarLifecycleService.php`, `resources/views/connectors/seminars/_form.blade.php`: validate, persist, review/publish, show relevant fields. |
| Legacy native | `AgoraTokenService.php`, `SeminarLivestreamService.php`, `SeminarAttendanceService.php`, native controllers/interactions, native views: reject external webinar Agora paths and retain native behavior. |
| Discovery and access | `SeminarRegistrationService.php`, `SeminarDiscoveryService.php`, new `SeminarExternalAccessService.php`, `SeminarBrowseController.php`, `routes/web.php`, `resources/views/seminars/{index,show}.blade.php`: deadline, detail access, protected redirect, participant UI. |
| Link management | New `SeminarDeliveryService.php`, `SeminarNoticeService.php`, `SeminarDeliveryNotification.php`, `Connector/SeminarDeliveryController.php`, `Admin/SeminarDeliveryController.php`, shared `resources/views/seminars/_delivery-form.blade.php`, connector/admin routes and detail views. |
| Scheduled notices | New `app/Console/Commands/SendSeminarNotices.php`, `routes/console.php`, existing `SeminarReminderNotification.php`: atomic schedule markers and recipient notifications. |
| Attendance | New `SeminarCodeAttendanceService.php`, `SeminarManualAttendanceService.php`, code/manual controllers and requests, `Connector/SeminarAttendanceController.php`, `SeminarExportService.php`, attendance/learner views: secure code, correction audit, full roster/CSV. |
| Verification/docs | New targeted tests under `tests/Feature/Seminars`, `tests/Feature/Connectors`, `tests/Feature/Admin`; update legacy fixtures/tests; add `docs/educational-events.md`. |

Each task is a review checkpoint. Commit only after its focused tests pass. Use `php artisan test --filter=...` without `--env` overrides so `phpunit.xml` selects `cc_db_test`; confirm `DB_DATABASE=cc_db_test` before any command that migrates a database. The existing `Tests\TestCase` uses `RefreshDatabase` against that test database. Do not run the test suite if local environment overrides point at the development database.

### Task 1: Forward schema, legacy backfill, and model values

**Files:** Create `database/migrations/2026_09_28_000001_extend_seminars_for_external_delivery.php`, `app/Enums/SeminarFormat.php`, `tests/Feature/Seminars/EducationalEventMigrationTest.php`; modify `app/Enums/SeminarType.php`, `app/Models/Seminar.php`, `app/Models/SeminarAttendance.php`.

**Interfaces:** `Seminar::isNativeDelivery(): bool`; `Seminar::isExternalDelivery(): bool`; migration `backfill(): void` is idempotent so the backfill can be tested after the schema is installed. Later tasks use the exact column names in the approved spec and sent markers `reminder_sent_for_starts_at` and `link_available_sent_for_visible_at`.

- [x] **Step 1: Write failing migration/model tests.** Test schema columns and call the migration's `backfill()` on `physical`, `webinar`, existing native attendance, and a legacy attended registrant with no attendance row. Seed venue text, an Agora channel/status, and existing relationships, then assert each is unchanged. Use existing `ConnectorTestHelpers` fixtures and assert the second `backfill()` call changes no rows. Core assertions:

```php
$migration = require database_path('migrations/2026_09_28_000001_extend_seminars_for_external_delivery.php');
$migration->backfill();
$migration->backfill();
$this->assertDatabaseHas('seminars', ['id' => $physical->id, 'type' => 'seminar', 'event_format' => 'in_person']);
$this->assertDatabaseHas('seminars', ['id' => $webinar->id, 'type' => 'webinar', 'event_format' => 'native']);
$this->assertDatabaseHas('seminars', ['id' => $physical->id, 'location' => 'Old community hall']);
$this->assertDatabaseHas('seminars', ['id' => $webinar->id, 'livestream_channel' => 'legacy-agora-channel']);
$this->assertDatabaseHas('seminar_attendances', ['seminar_id' => $webinar->id, 'user_id' => $legacyUser->id, 'attendance_method' => 'legacy', 'status' => 'attended']);
$this->assertSame(1, \App\Models\SeminarAttendance::where('seminar_id', $webinar->id)->where('user_id', $legacyUser->id)->count());
$this->assertTrue(\Illuminate\Support\Facades\Schema::hasColumns('seminars', ['event_format', 'external_url', 'registration_deadline_at', 'attendance_code_hash']));
```

- [x] **Step 2: Run the focused test and observe failure.** Run `php artisan test --filter=EducationalEventMigrationTest`; expect failure because the migration, columns, and format helpers do not exist.
- [x] **Step 3: Implement the forward migration and model surface.** Add nullable delivery fields, `external_link_expiry_mode` default `ongoing`, boolean code enabled default false, nullable notice markers, and nullable `attendance_method`/`attended_at`. Backfill in batches. Do not create an external URL for legacy rows. Make `down()` throw if any seminar or attendance data exists, because new and old `seminar/in_person` rows cannot be distinguished safely during reversal; only an empty installation may drop the added columns. The backfill body is:

```php
public function backfill(): void
{
    DB::table('seminars')->where('type', 'physical')->whereNull('event_format')
        ->update(['type' => 'seminar', 'event_format' => 'in_person']);
    DB::table('seminars')->where('type', 'webinar')->whereNull('event_format')
        ->update(['event_format' => 'native']);
    DB::table('seminar_attendances')->whereNull('attendance_method')
        ->update(['attendance_method' => 'native']);
    DB::table('seminar_registrants')->where('status', 'attended')->orderBy('id')
        ->chunkById(200, function ($rows): void {
            foreach ($rows as $row) {
                DB::table('seminar_attendances')->insertOrIgnore([
                    'seminar_id' => $row->seminar_id,
                    'user_id' => $row->user_id,
                    'role' => 'audience',
                    'status' => 'attended',
                    'attendance_method' => 'legacy',
                    'attended_at' => $row->attended_at,
                    'total_seconds' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });
}
```

Add `SeminarType::Seminar = 'seminar'`; leave the old `Physical` case until Task 3 removes its request/controller references. Add `SeminarFormat` cases `InPerson`, `External`, `Native`; add the fields to model `$fillable`/`casts`, hide `external_url` and `attendance_code_hash` from serialization, and implement `isNativeDelivery()`/`isExternalDelivery()` with strict equality to format values.
- [x] **Step 4: Run the focused test.** Run `php artisan test --filter=EducationalEventMigrationTest`; expect pass, including the idempotent second backfill. Use the schema assertion in the test to verify installation on `cc_db_test`; do not run a development migration during plan execution without the normal deployment step.
- [x] **Step 5: Commit.** Stage the six listed source files and the test with `git add`, then run `git commit -m "feat: extend seminars for external delivery"`.

### Task 2: Gate Agora and native interactions by format

**Files:** Modify `app/Services/Seminars/AgoraTokenService.php`, `SeminarLivestreamService.php`, `SeminarAttendanceService.php`, `SeminarInteractionService.php`, `app/Http/Controllers/Connector/SeminarLivestreamController.php`, `app/Http/Controllers/SeminarBrowseController.php`, `resources/views/connectors/seminars/show.blade.php`, `resources/views/seminars/show.blade.php`; modify native fixtures in `tests/Feature/Seminars/SeminarLivestreamAccessTest.php`, `SeminarAttendanceTest.php`, `SeminarInteractionTest.php`, `tests/Unit/Services/Seminars/AgoraTokenServiceTest.php`.

**Interfaces:** All native paths depend on `Seminar::isNativeDelivery()`. `SeminarAttendanceService::finalize()` still accepts a `Seminar`, but only processes native events and preserves explicit manual decisions in native rows.

- [x] **Step 1: Add an external webinar regression test and update legacy fixtures.** In `SeminarLivestreamAccessTest`, create a published `webinar/external` with `external_url` and assert learner/host token and livestream endpoints return 403; a `webinar/native` fixture must still issue native access. Update every native test helper to set `event_format => 'native'`. Core assertion:

```php
$external = $this->seminar($connector, ['type' => 'webinar', 'event_format' => 'external', 'external_url' => 'https://meet.example.test/room']);
$this->actingAs($registeredLearner)->get(route('seminars.join', $external))->assertForbidden();
$this->actingAs($registeredLearner)->postJson(route('seminars.agora-token', $external))->assertForbidden();
$this->actingAs($owner)->get(route('connector.seminars.livestream', [$connector, $external]))->assertForbidden();
```

- [x] **Step 2: Run focused native tests and observe the new failure.** Run `php artisan test tests/Feature/Seminars/SeminarLivestreamAccessTest.php tests/Feature/Seminars/SeminarAttendanceTest.php tests/Feature/Seminars/SeminarInteractionTest.php tests/Unit/Services/Seminars/AgoraTokenServiceTest.php`; expect the external webinar assertions to fail before the guard.
- [x] **Step 3: Put a format guard in each shared native entry point.** Make `AgoraTokenService::isInJoinWindow()` require `isNativeDelivery()`, and guard `tokenFor()` before credential checks so the response is 403 even without Agora secrets. Apply the same guard in livestream prepare/start/end/status and connector show, participant join, attendance join/heartbeat/leave, and native comments/questions. Hide Host Livestream and Join Live UI unless native. Representative guard:

```php
abort_unless($seminar->isNativeDelivery(), 403);
```

In `SeminarAttendanceService::finalize()`, return immediately for a non-native event; for a native row with `attendance_method === 'manual'`, update duration and leave time without overwriting `status`, `attended_at`, or method. Native rows receive method `native` on first creation.
- [x] **Step 4: Re-run native tests.** Run the same four test files; expect all pass and external routes return 403 while legacy native behavior remains.
- [x] **Step 5: Commit.** Stage only the files in this task and commit with `git commit -m "fix: isolate native seminar delivery"`.

### Task 3: Authoring validation, immutable registration fields, and review gate

**Files:** Create `app/Services/Seminars/SeminarPublicationValidator.php`, `tests/Feature/Connectors/EducationalEventAuthoringTest.php`; modify `app/Http/Requests/Connector/StoreSeminarRequest.php`, `UpdateSeminarRequest.php`, `app/Http/Controllers/Connector/SeminarController.php`, `app/Services/Seminars/SeminarLifecycleService.php`, `tests/Feature/Connectors/ConnectorTestHelpers.php`, `ConnectorSeminarManagementTest.php`, `tests/Feature/Admin/AdminSeminarModerationTest.php`.

**Interfaces:** `SeminarPublicationValidator::assertReady(Seminar $seminar): void`; connector payload persists the approved fields and synchronizes `schedule`. Request validation permits `native` only while editing the same existing native webinar; creation never permits it.

- [x] **Step 1: Write failing feature tests.** Cover all three new type/format combinations; reject in-person webinar, missing description/objectives/venue/address/platform/Other name/absolute HTTPS or HTTP URL, deadline at/after start, and new native authoring. Test PHT-to-UTC for release/expiry/deadline. Test that a registered event rejects type/format/start/end changes, capacity below active count, and audience narrowing that excludes an active registrant. Direct review/publish calls on an incomplete persisted event must fail. Core requests:

```php
$valid = $this->educationalEventPayload([
    'type' => 'webinar', 'event_format' => 'external',
    'external_platform' => 'zoom', 'external_url' => 'https://zoom.example.test/j/123',
    'description' => 'Community education', 'purpose' => 'Learn safe practices',
]);
$this->actingAs($owner)->post(route('connector.seminars.store', $connector), $valid)->assertRedirect();
$this->actingAs($owner)->post(route('connector.seminars.store', $connector), [
    ...$valid, 'type' => 'webinar', 'event_format' => 'in_person',
])->assertSessionHasErrors('event_format');
```

The new tests use `ConnectorTestHelpers` with this shared fixture method:

```php
private function educationalEventPayload(array $overrides = []): array
{
    return array_merge([
        'title' => 'Community learning event',
        'description' => 'A guided education session.',
        'purpose' => 'Learn practical safety skills.',
        'type' => 'webinar',
        'event_format' => 'external',
        'category' => 'health',
        'starts_at' => now()->addDay()->format('Y-m-d H:i:s'),
        'ends_at' => now()->addDay()->addHour()->format('Y-m-d H:i:s'),
        'capacity' => 50,
        'registration_approval_mode' => 'auto_approve',
        'target_participants' => 'learners_and_instructors',
        'learner_age_categories' => ['kids', 'teen', 'adult'],
        'external_platform' => 'zoom',
        'external_url' => 'https://zoom.example.test/j/123',
    ], $overrides);
}
```

- [x] **Step 2: Run the authoring tests and observe failure.** Run `php artisan test --filter=EducationalEventAuthoringTest`; expect missing format rules and immutable-field checks to fail.
- [x] **Step 3: Implement request rules and common persisted-state validation.** Add `educationalEventPayload(array $overrides = []): array` to `ConnectorTestHelpers` with the same common fields as the existing `seminarPayload()`, plus `description`, `event_format = external`, `external_platform = zoom`, and an HTTPS test URL; new feature tests use this trait. Extend `validationData()` conversion to `registration_deadline_at`, `external_link_visible_at`, `external_link_expires_at`, `attendance_start_at`, `attendance_end_at`; use `$validator->getData()` for cross-field date comparisons after conversion. Require `description`/`purpose`; validate type/format pair, deadline before start, URL with `url` plus parsed `http`/`https` scheme, custom expiry after release, and `event_end` against release. On update, reject type/format changes after *any* registration row exists and start/end changes after any row exists. Recheck active registrants for capacity and eligibility using a copy of the proposed audience fields before saving. Persist only relevant format fields, clear irrelevant delivery values for new records, and keep legacy native values intact. Reuse these decision rules in `assertReady()` before `submitForReview()` and `publishApproved()`. Remove the old `SeminarType::Physical` case and update all direct fixture values to `seminar/in_person`. The pair rule is:

```php
$allowed = [
    'seminar' => ['in_person', 'external'],
    'webinar' => ['external'],
];
$legacyNativeEdit = $this->route('seminar')?->isNativeDelivery()
    && $this->input('type') === 'webinar'
    && $this->input('event_format') === 'native';
if (! in_array($this->input('event_format'), $allowed[$this->input('type')] ?? [], true)
    && ! $legacyNativeEdit) {
    $validator->errors()->add('event_format', 'Choose a supported event type and format.');
}
```

The payload must include `description`, `event_format`, the approved delivery/deadline fields, and `'schedule' => $validated['starts_at']`. Generate `livestream_channel` only for a legacy native webinar that genuinely lacks one; a new external webinar must never receive an Agora channel. `publishApproved()` changes `livestream_status` only for native records.
- [x] **Step 4: Run authoring and governance tests.** Run `php artisan test tests/Feature/Connectors/EducationalEventAuthoringTest.php tests/Feature/Connectors/ConnectorSeminarManagementTest.php tests/Feature/Admin/AdminSeminarModerationTest.php`; expect pass. Update old assertions that deliberately allowed missing description, `physical`, or newly created native webinars to the approved contract; retain direct legacy native fixtures for backward-compatibility checks.
- [x] **Step 5: Commit.** Stage this task's files and commit with `git commit -m "feat: validate educational event authoring"`.

### Task 4: Connector authoring UI and event classification

**Files:** Modify `resources/views/connectors/seminars/_form.blade.php`, `create.blade.php`, `edit.blade.php`, `show.blade.php`, `resources/views/connectors/seminars/index.blade.php`; create `tests/Feature/Connectors/EducationalEventFormTest.php`.

**Interfaces:** Form names match Task 3 requests exactly. `event_format` options depend on type; legacy native edit shows its fixed format and existing Agora controls remain native-only.

- [x] **Step 1: Write failing HTML tests.** Create page shows Seminar/Webinar and format choices, description and Objectives, platform/venue fields and deadline. Native does not appear on create. Editing a native fixture still identifies native delivery without offering conversion after registrations.

```php
$this->actingAs($owner)->get(route('connector.seminars.create', $connector))
    ->assertOk()->assertSee('Educational Event')->assertSee('Objectives')
    ->assertSee('external_platform', false)->assertSee('venue_address', false)
    ->assertDontSee('value="native"', false);
```

- [x] **Step 2: Run the form test and observe failure.** Run `php artisan test --filter=EducationalEventFormTest`; expect missing labels/fields.
- [x] **Step 3: Change the existing Alpine form.** Add a type selector and format selector with only allowed combinations, `description` textarea, Objectives label, conditional venue name/address/room/instructions and conditional platform/custom name/URL/release/expiry controls; use `datetime-local` in PHT with the existing `localStartsAt()` pattern. Show registration deadline. Display type and format badges in connector list/detail and hide native controls for external webinars. Example conditional markup:

```blade
<div x-show="eventFormat === 'external'">
    <label for="external_url">External event link</label>
    <input id="external_url" name="external_url" type="url" value="{{ old('external_url', $seminar->external_url) }}">
</div>
<div x-show="eventFormat === 'in_person'">
    <label for="venue_address">Venue address</label>
    <input id="venue_address" name="venue_address" value="{{ old('venue_address', $seminar->venue_address) }}">
</div>
```

Render errors for each field and explain that a blank release time means immediate access after confirmation and `ongoing` means the link remains after completion. The server request remains authoritative.
- [x] **Step 4: Re-run form and authoring tests.** Run `php artisan test tests/Feature/Connectors/EducationalEventFormTest.php tests/Feature/Connectors/EducationalEventAuthoringTest.php`; expect pass.
- [x] **Step 5: Commit.** Stage the form/list/detail/test files and commit with `git commit -m "feat: add educational event authoring UI"`.

### Task 5: Registration deadline and event detail access

**Files:** Modify `app/Services/Seminars/SeminarRegistrationService.php`, `SeminarDiscoveryService.php`, `SeminarAccessService.php`, `app/Http/Controllers/SeminarBrowseController.php`, `app/Http/Controllers/Connector/SeminarController.php`, `resources/views/seminars/index.blade.php`, `show.blade.php`, `tests/Feature/Seminars/SeminarRegistrationTest.php`; create `tests/Feature/Seminars/EducationalEventDiscoveryTest.php`.

**Interfaces:** `SeminarDiscoveryService::canView(User $user, Seminar $seminar): bool` admits published eligible participants and authorized accepted speakers/managers/admins; completed detail is allowed for active registrants and accepted speakers (and managers/admins). It never makes completed events appear in normal discovery.

- [x] **Step 1: Write failing tests.** Freeze time at deadline and start boundaries; manual-approval `pending` does not grant protected access. Test an accepted speaker outside target audience can view detail; a registered user and speaker can view completed detail; an unrelated user cannot; list still shows only published eligible events. Assert page contains type, format, organizer, speaker, venue/platform label, and attendance wording but not raw URL.

```php
$seminar->update(['registration_deadline_at' => now()->addMinute()]);
$this->travel(61)->seconds();
$this->actingAs($learner)->post(route('seminars.register', $seminar))->assertSessionHasErrors('seminar');
$this->actingAs($unrelated)->get(route('seminars.show', $completed))->assertForbidden();
$this->actingAs($registered)->get(route('seminars.show', $completed))->assertOk();
```

- [x] **Step 2: Run focused tests and observe failure.** Run `php artisan test --filter=EducationalEventDiscoveryTest`; expect deadline/detail access assertions to fail.
- [x] **Step 3: Implement deadline and detail policy.** In `registrationError()`, close at `registration_deadline_at ?? starts_at ?? schedule`; preserve cancellation's separate start boundary. In discovery, use active registration plus current eligibility for participant access, accepted `seminar_speakers` row for speaker access, connector permission/ownership for manager access, and admin role. Allow published managers/speakers even if audience filter excludes them, and completed detail for active registrants/speakers/managers/admins. Remove the connector controller's duplicate, narrower detail policy by delegating to discovery. Render classification and safe delivery metadata, with no `external_url` in participant HTML or serialized data.

```php
$deadline = $seminar->registration_deadline_at ?? $seminar->starts_at ?? $seminar->schedule;
if ($deadline !== null && now()->greaterThanOrEqualTo($deadline)) {
    return 'Registration for this event has closed.';
}
```

- [x] **Step 4: Run discovery and existing registration tests.** Run `php artisan test tests/Feature/Seminars/EducationalEventDiscoveryTest.php tests/Feature/Seminars/SeminarRegistrationTest.php`; expect pass.
- [x] **Step 5: Commit.** Stage this task's files and commit with `git commit -m "feat: show educational events by role and deadline"`.

### Task 6: Protected external join redirect

**Files:** Create `app/Services/Seminars/SeminarExternalAccessService.php`, `app/Http/Controllers/SeminarExternalJoinController.php`, `tests/Feature/Seminars/SeminarExternalAccessTest.php`; modify `routes/web.php`, `app/Http/Controllers/SeminarBrowseController.php`, `resources/views/seminars/show.blade.php`.

**Interfaces:** `SeminarExternalAccessService::canJoin(User $user, Seminar $seminar): bool` is safe for showing a button; `redirectUrl(User $user, Seminar $seminar): string` repeats the decision and returns the URL only for the authorized redirect. Route name: `seminars.external.join` at `GET /seminars/{seminar}/external`.

- [x] **Step 1: Write failing boundary and leakage tests.** Freeze time one second before/at release and one second before/at expiry. Cover confirmed eligible registrant, pending/cancelled/now-ineligible registrant, accepted speaker before release, unaccepted speaker, manager/admin, completed ongoing, completed event-end, cancelled/archived, missing URL, and a malformed legacy URL. Verify the detail HTML and notification payload do not contain the raw URL. Core assertions:

```php
$this->actingAs($registered)->get(route('seminars.external.join', $seminar))->assertForbidden();
$this->travelTo($seminar->external_link_visible_at);
$this->actingAs($registered)->get(route('seminars.external.join', $seminar))
    ->assertRedirect('https://classroom.example.test/course/1');
$this->actingAs($pending)->get(route('seminars.external.join', $seminar))->assertForbidden();
$this->actingAs($registered)->get(route('seminars.show', $seminar))
    ->assertDontSee('https://classroom.example.test/course/1', false);
```

- [x] **Step 2: Run access tests and observe failure.** Run `php artisan test --filter=SeminarExternalAccessTest`; expect route/service missing.
- [x] **Step 3: Implement the one access policy and redirect.** `canJoin()` requires `event_format === external`, published/completed status, a stored absolute HTTP(S) URL, and no effective expiry (`ongoing`, `ends_at` for `event_end`, or `external_link_expires_at` for `custom`). A manager/admin/accepted speaker may enter before `external_link_visible_at`; a participant must be actively registered, still eligible, and past release. Nobody enters a cancelled/archived event. `redirectUrl()` must call `canJoin()` again on a fresh seminar row. The controller does not serialize the model:

```php
public function __invoke(Request $request, Seminar $seminar, SeminarExternalAccessService $access): RedirectResponse
{
    $url = $access->redirectUrl($request->user(), $seminar->fresh());

    return redirect()->away($url)->withHeaders([
        'Cache-Control' => 'no-store',
        'Referrer-Policy' => 'no-referrer',
    ]);
}
```

Render a protected-route button or a safe scheduled/expired/unavailable message on the detail page; never print `external_url`, including Alpine state or hidden inputs.
- [x] **Step 4: Re-run access and discovery tests.** Run `php artisan test tests/Feature/Seminars/SeminarExternalAccessTest.php tests/Feature/Seminars/EducationalEventDiscoveryTest.php`; expect pass.
- [x] **Step 5: Commit.** Stage this task's files and commit with `git commit -m "feat: protect external event links"`.

### Task 7: Host/admin link management and change notices

**Files:** Create `app/Http/Requests/Seminars/UpdateSeminarDeliveryRequest.php`, `app/Services/Seminars/SeminarDeliveryService.php`, `SeminarNoticeService.php`, `app/Notifications/Seminars/SeminarDeliveryNotification.php`, `app/Http/Controllers/Connector/SeminarDeliveryController.php`, `app/Http/Controllers/Admin/SeminarDeliveryController.php`, `resources/views/seminars/_delivery-form.blade.php`, `tests/Feature/Connectors/SeminarDeliveryManagementTest.php`, `tests/Feature/Admin/AdminSeminarDeliveryTest.php`; modify `routes/connector.php`, `routes/admin.php`, connector/admin seminar detail views.

**Interfaces:** `SeminarDeliveryService::update(Seminar $seminar, array $data, User $actor): Seminar`; `SeminarNoticeService::recipients(Seminar $seminar, ?User $actor = null): Collection` returns deduplicated users. `SeminarDeliveryNotification::__construct(int $seminarId, string $title, string $kind)` accepts only safe fields; `kind` is `changed` or `available`. New route names: `connector.seminars.delivery.update`, `admin.seminars.delivery.update`.

- [x] **Step 1: Write failing permission and notification tests.** A connector manager may change only their own external published/completed event; an admin may change any external published/completed event through the narrow action, but cannot edit other event content through it. Deny ordinary members, wrong connector, native/in-person, cancelled/archived, invalid release/expiry/URL. Change URL plus instructions in one save and assert exactly one notice per eligible confirmed registrant/accepted speaker; exclude pending, cancelled, ineligible, and unregistered; include organizer once for admin action. Assert `toMail()` and `toDatabase()` never contain either old or new URL.

```php
\Illuminate\Support\Facades\Notification::fake();
$this->actingAs($owner)->put(route('connector.seminars.delivery.update', [$connector, $seminar]), [
    'external_url' => 'https://zoom.example.test/new',
    'external_link_expiry_mode' => 'ongoing',
    'delivery_instructions' => 'Use your registered name.',
])->assertRedirect();
\Illuminate\Support\Facades\Notification::assertSentToTimes($eligible, \App\Notifications\Seminars\SeminarDeliveryNotification::class, 1);
\Illuminate\Support\Facades\Notification::assertNotSentTo($pending, \App\Notifications\Seminars\SeminarDeliveryNotification::class);
```

- [x] **Step 2: Run focused tests and observe failure.** Run `php artisan test tests/Feature/Connectors/SeminarDeliveryManagementTest.php tests/Feature/Admin/AdminSeminarDeliveryTest.php`; expect missing routes/action failures.
- [x] **Step 3: Implement validated updates and recipients.** Request accepts only `external_url`, `external_link_visible_at`, `external_link_expiry_mode`, `external_link_expires_at`, and `delivery_instructions`, with the same UTC conversion and expiry checks as Task 3. Controller checks permission/ownership (or admin role) and delegates. `update()` locks the event row in a transaction, rejects non-external or cancelled/archived states, compares only these five fields, saves once, and dispatches one notification batch after the transaction returns if status is published/completed and a value changed. Clear `link_available_sent_for_visible_at` on every release-time change, including a change to null, so returning to an older release time is a fresh schedule. A failed notification dispatch is logged and does not reverse the saved change. `recipients()` takes active registrants, filters with `matchesParticipantEligibility()`, adds accepted speakers with `user_id`, adds the connector organizer only for an admin actor, and deduplicates by user ID. The notification uses only IDs/title/kind and `route('seminars.show', $seminarId)`. Its database payload and mail action contain no `external_url`.

```php
$fields = ['external_url', 'external_link_visible_at', 'external_link_expiry_mode', 'external_link_expires_at', 'delivery_instructions'];
[$updated, $changed] = DB::transaction(function () use ($seminar, $data, $fields): array {
    $locked = Seminar::query()->lockForUpdate()->findOrFail($seminar->id);
    abort_unless($locked->isExternalDelivery() && in_array($locked->status, ['draft', 'pending_review', 'approved', 'published', 'completed'], true), 422);
    $changed = collect($fields)->contains(fn (string $field): bool => array_key_exists($field, $data) && $locked->{$field} != $data[$field]);
    if (array_key_exists('external_link_visible_at', $data) && $locked->external_link_visible_at != $data['external_link_visible_at']) {
        $locked->link_available_sent_for_visible_at = null;
    }
    $locked->fill($data)->save();

    return [$locked->fresh(), $changed];
});
if ($changed && in_array($updated->status, ['published', 'completed'], true)) {
    try {
        $this->notices->notifyDeliveryChanged($updated, $actor);
    } catch (\Throwable $exception) {
        Log::warning('Seminar delivery notice dispatch failed', ['seminar_id' => $updated->id, 'exception' => $exception]);
    }
}
return $updated;
```

Make `SeminarDeliveryNotification` queueable so mail failure is retried independently of the saved update. The authorized shared form may display the current URL to managers/admins; the participant page never does.
- [x] **Step 4: Re-run management, admin, and external access tests.** Run `php artisan test tests/Feature/Connectors/SeminarDeliveryManagementTest.php tests/Feature/Admin/AdminSeminarDeliveryTest.php tests/Feature/Seminars/SeminarExternalAccessTest.php`; expect pass.
- [x] **Step 5: Commit.** Stage this task's files and commit with `git commit -m "feat: manage and announce external event links"`.

### Task 8: Scheduled reminder and link-availability notices

**Files:** Create `app/Console/Commands/SendSeminarNotices.php`, `tests/Feature/Seminars/SeminarScheduledNoticesTest.php`; modify `routes/console.php`, `app/Services/Seminars/SeminarNoticeService.php`, `app/Notifications/Seminars/SeminarReminderNotification.php`, `app/Services/Seminars/SeminarDeliveryService.php`.

**Interfaces:** Artisan command `seminars:send-notices`; `SeminarNoticeService::sendReminder(Seminar $seminar): void` and `sendLinkAvailable(Seminar $seminar): void` use the Task 7 recipient set. Markers are schedule timestamps, so a changed release time is a new delivery slot without adding a notice table.

- [x] **Step 1: Write failing scheduler tests.** Freeze time at 60 minutes before start and at scheduled release. Run command twice; assert one reminder and one availability notice per eligible recipient. Change release time using the Task 7 action and assert availability notice fires once at the new time. Assert no notice for pending/cancelled/ineligible registrations, expired links, cancelled/archived events, or immediate release (`external_link_visible_at = null`). Check notification content contains the protected page route and no raw URL.

```php
\Illuminate\Support\Facades\Notification::fake();
$this->artisan('seminars:send-notices')->assertExitCode(0);
$this->artisan('seminars:send-notices')->assertExitCode(0);
\Illuminate\Support\Facades\Notification::assertSentToTimes($registered, \App\Notifications\Seminars\SeminarDeliveryNotification::class, 1);
$this->assertEquals($seminar->external_link_visible_at, $seminar->fresh()->link_available_sent_for_visible_at);
```

- [x] **Step 2: Run the notice test and observe failure.** Run `php artisan test --filter=SeminarScheduledNoticesTest`; expect the command is undefined.
- [x] **Step 3: Implement the command and schedule.** `routes/console.php` schedules `seminars:send-notices` every minute with `withoutOverlapping()`. The command selects published events whose start is from now through 60 minutes ahead and published/completed external events with a non-null release at/before now. For each candidate, atomically claim the marker only if it differs from the current schedule value. Include `where('starts_at', $seminar->starts_at)` or `where('external_link_visible_at', $seminar->external_link_visible_at)` in the update to avoid claiming an obsolete schedule. After a successful claim, call the notice service; on dispatch failure, log and clear the marker only if it still equals the claimed schedule so a later run retries. Keep the existing reminder notification but update its copy to Educational Event. Claim shape:

```php
$claimed = Seminar::query()->whereKey($seminar->id)
    ->where('external_link_visible_at', $seminar->external_link_visible_at)
    ->where(function ($query): void {
        $query->whereNull('link_available_sent_for_visible_at')
            ->orWhereColumn('link_available_sent_for_visible_at', '!=', 'external_link_visible_at');
    })
    ->update(['link_available_sent_for_visible_at' => $seminar->external_link_visible_at]);
if ($claimed === 1) {
    $this->notices->sendLinkAvailable($seminar->fresh());
}
```

Apply the same pattern to `reminder_sent_for_starts_at`. The link-change action in Task 7 clears the availability marker on every release-time change; the timestamp comparison also guards stale scheduler runs. Dispatch through the existing mail/database channels, with a queue worker configured in deployment.
- [x] **Step 4: Re-run notice and delivery tests.** Run `php artisan test tests/Feature/Seminars/SeminarScheduledNoticesTest.php tests/Feature/Connectors/SeminarDeliveryManagementTest.php`; expect pass.
- [x] **Step 5: Commit.** Stage this task's files and commit with `git commit -m "feat: schedule educational event notices"`.

### Task 9: Generate and submit optional attendance codes

**Files:** Create `app/Services/Seminars/SeminarCodeAttendanceService.php`, `app/Http/Requests/Seminars/ManageSeminarCodeRequest.php`, `app/Http/Requests/Seminars/SubmitSeminarCodeRequest.php`, `app/Http/Controllers/SeminarCodeAttendanceController.php`, `app/Http/Controllers/Admin/SeminarAttendanceController.php`, `tests/Feature/Seminars/SeminarCodeAttendanceTest.php`; modify `app/Http/Controllers/Connector/SeminarAttendanceController.php`, `routes/web.php`, `routes/connector.php`, `routes/admin.php`, `resources/views/seminars/show.blade.php`, `resources/views/connectors/seminars/attendance.blade.php`, `resources/views/admin/seminars/show.blade.php`.

**Interfaces:** `SeminarCodeAttendanceService::generate(Seminar $seminar, ?Carbon $opensAt, ?Carbon $closesAt): string` returns the raw code once; `disable(Seminar $seminar): void`; `submit(User $user, Seminar $seminar, string $code, string $ip): SeminarAttendance`. Route names: `seminars.attendance.code.submit`, `connector.seminars.attendance.code.generate`, `.disable`, and matching `admin.seminars.attendance.code.generate`, `.disable`.

- [x] **Step 1: Write failing code tests.** Assert eight digits, hash stored but raw code absent from database/notifications, regeneration invalidates old code, disable blocks use, default and custom window boundaries, registration/eligibility/status checks, accepted speaker without registration cannot submit, external/in-person allowed and native denied, duplicate submission has one row, manual override cannot be replaced, and rate limits at 5 failures per user/event or 20 per IP within ten minutes. Test PHT input conversion for overrides. Core assertions:

```php
$response = $this->actingAs($owner)->post(route('connector.seminars.attendance.code.generate', [$connector, $seminar]));
$response->assertSessionHas('generated_attendance_code');
$code = session('generated_attendance_code');
$this->assertMatchesRegularExpression('/^\d{8}$/', $code);
$this->assertNotSame($code, $seminar->fresh()->attendance_code_hash);
$this->actingAs($registered)->post(route('seminars.attendance.code.submit', $seminar), ['code' => $code])->assertRedirect();
$this->assertDatabaseHas('seminar_attendances', ['seminar_id' => $seminar->id, 'user_id' => $registered->id, 'attendance_method' => 'attendance_code', 'status' => 'attended']);
```

- [x] **Step 2: Run focused code tests and observe failure.** Run `php artisan test --filter=SeminarCodeAttendanceTest`; expect missing endpoints/service.
- [x] **Step 3: Implement code lifecycle and submission.** Controllers enforce connector ownership/permission or admin role before calling the service. Validate optional opening/closing PHT fields into UTC and require opening before closing. `generate()` rejects native/cancelled/archived events, uses `str_pad((string) random_int(0, 99999999), 8, '0', STR_PAD_LEFT)`, saves `Hash::make($code)`, enabled=true, generated_at=now, bounds, and returns the code for a one-time flash. `disable()` sets enabled=false and removes the hash. `submit()` checks published/completed status, current active registration and eligibility, and window `[attendance_start_at ?? starts_at-15m, attendance_end_at ?? ends_at+30m]` inclusively. Use `RateLimiter::tooManyAttempts()`/`hit($key, 600)` for `seminar-code:{seminarId}:{userId}` and `seminar-code-ip:{hash('sha256', $ip)}` for the per-IP limit across events; hit both only on a wrong code, clear both on success. Lock the seminar and registrant in a transaction and reread the current hash after acquiring locks, so regeneration immediately invalidates an older code; then create/update the unique attendance row. Reject a manual override and return a clear already-submitted response for existing code attendance. Mirror attended_at to registrant in the same transaction.

```php
$code = str_pad((string) random_int(0, 99999999), 8, '0', STR_PAD_LEFT);
$seminar->forceFill([
    'attendance_code_hash' => Hash::make($code),
    'attendance_code_enabled' => true,
    'attendance_code_generated_at' => now(),
    'attendance_start_at' => $opensAt,
    'attendance_end_at' => $closesAt,
])->save();
return $code;
```

The learner form posts the code only after authentication; the management screen shows the flashed raw code once, never stores it in markup after refresh, and explains that submitting a code does not prove full participation.
- [x] **Step 4: Re-run code, access, and native attendance tests.** Run `php artisan test tests/Feature/Seminars/SeminarCodeAttendanceTest.php tests/Feature/Seminars/SeminarExternalAccessTest.php tests/Feature/Seminars/SeminarAttendanceTest.php`; expect pass.
- [x] **Step 5: Commit.** Stage this task's files and commit with `git commit -m "feat: add optional event attendance codes"`.

### Task 10: Manual attendance corrections and native decision preservation

**Files:** Create `app/Services/Seminars/SeminarManualAttendanceService.php`, `app/Http/Requests/Seminars/SetSeminarAttendanceRequest.php`, `tests/Feature/Connectors/SeminarManualAttendanceTest.php`, `tests/Feature/Admin/AdminSeminarAttendanceTest.php`; modify `app/Http/Controllers/Connector/SeminarAttendanceController.php`, `app/Http/Controllers/Admin/SeminarAttendanceController.php`, `app/Services/Seminars/SeminarAttendanceService.php`, `app/Http/Controllers/Connector/SeminarController.php`, `routes/connector.php`, `routes/admin.php`.

**Interfaces:** `SeminarManualAttendanceService::set(Seminar $seminar, SeminarRegistrant $registrant, User $actor, bool $attended, ?string $reason): SeminarAttendance`. Route names: `connector.seminars.attendance.manual` and `admin.seminars.attendance.manual`. Request field `attended` is boolean; `reason` is required for removal or any correction of an existing decision.

- [x] **Step 1: Write failing correction tests.** Test manager/admin authorization, other connector denial, unregistered walk-in denial, first mark, correction from code to manual, required reason for removal/correction, repeated same-state action, ActivityLog metadata, mirrored registrant timestamp, retained native duration fields, and code rejection after manual override. For native webinars, heartbeat and completion may update duration but cannot replace the manual attendance decision. Core assertions:

```php
$this->actingAs($owner)->post(route('connector.seminars.attendance.manual', [$connector, $seminar, $registrant]), [
    'attended' => false,
    'reason' => 'Host corrected mistaken check-in',
])->assertRedirect();
$this->assertDatabaseHas('seminar_attendances', [
    'seminar_id' => $seminar->id, 'user_id' => $registrant->user_id,
    'attendance_method' => 'manual', 'status' => 'not_present', 'attended_at' => null,
]);
$this->assertDatabaseHas('seminar_registrants', ['id' => $registrant->id, 'attended_at' => null]);
$this->assertDatabaseHas('activity_logs', ['activity_type' => 'seminar_attendance_corrected']);
```

- [x] **Step 2: Run manual attendance tests and observe failure.** Run `php artisan test tests/Feature/Connectors/SeminarManualAttendanceTest.php tests/Feature/Admin/AdminSeminarAttendanceTest.php`; expect missing action/service failures.
- [x] **Step 3: Implement transactional decisions and audit.** Verify the registrant belongs to the event and is active/confirmed. Lock the registrant and attendance row, require reason for a state change when any decision exists and for removal, and retain `joined_at`, `left_at`, `total_seconds`, and `role`. Set method manual with `status` `attended` or `not_present`; set `attended_at` to now or null and mirror it to registrant. Log `seminar_attendance_corrected` inside the same transaction with actor/event/participant IDs, action, reason, and before/after snapshots. Native join/heartbeat/leave/finalize may still measure seconds on a manually overridden native row but must preserve method, status, and attended_at. Completion calls native duration finalization only when `isNativeDelivery()`. Decision write shape:

```php
$after = [
    'attendance_method' => 'manual',
    'status' => $attended ? 'attended' : 'not_present',
    'attended_at' => $attended ? now() : null,
];
$attendance->fill($after)->save();
$registrant->forceFill(['attended_at' => $after['attended_at']])->save();
ActivityLog::log('seminar_attendance_corrected', 'Event attendance corrected', [
    'actor_id' => $actor->id,
    'seminar_id' => $seminar->id,
    'participant_id' => $registrant->user_id,
    'reason' => $reason,
    'before' => $before,
    'after' => $after,
]);
```

- [x] **Step 4: Re-run manual, code, and native tests.** Run `php artisan test tests/Feature/Connectors/SeminarManualAttendanceTest.php tests/Feature/Admin/AdminSeminarAttendanceTest.php tests/Feature/Seminars/SeminarCodeAttendanceTest.php tests/Feature/Seminars/SeminarAttendanceTest.php`; expect pass.
- [x] **Step 5: Commit.** Stage this task's files and commit with `git commit -m "feat: audit manual event attendance"`.

### Task 11: Full attendance roster, CSV, learner wording, and operations guide

**Files:** Modify `app/Http/Controllers/Connector/SeminarAttendanceController.php`, `app/Http/Controllers/Admin/SeminarAttendanceController.php`, `app/Services/Seminars/SeminarExportService.php`, `routes/admin.php`, `resources/views/connectors/seminars/attendance.blade.php`, `resources/views/admin/seminars/show.blade.php`, `resources/views/seminars/show.blade.php`, `tests/Feature/Seminars/SeminarAttendanceTest.php`; create `resources/views/admin/seminars/attendance.blade.php`, `docs/educational-events.md`, `tests/Feature/Seminars/SeminarAttendanceRosterTest.php`.

**Interfaces:** Roster and CSV start from `seminar_registrants`, not `seminar_attendances`, and left-match attendance on both `(seminar_id, user_id)`. The ordinary learner view receives only its own attendance summary; management views receive paginated rows and controls from Tasks 9–10. New admin route names: `admin.seminars.attendance` and `admin.seminars.attendance.export`.

- [x] **Step 1: Write failing roster/export/display tests.** Create a confirmed registrant with no attendance, code-attended participant, manually removed participant, and native participant with duration. Assert all appear on paginated manager/admin roster and CSV with registration status, attendance status/method/time, and native duration where present. Assert ordinary learners cannot open management/CSV, see only their own status, and see “Attendance submitted” for code entry and distinct host-entered/native wording.

```php
$this->actingAs($owner)->get(route('connector.seminars.attendance', [$connector, $seminar]))
    ->assertOk()->assertSee($notSubmitted->name)->assertSee('Not submitted')
    ->assertSee($codeParticipant->name)->assertSee('Attendance submitted');
$csv = $this->actingAs($owner)->get(route('connector.seminars.attendance.export', [$connector, $seminar]));
$csv->assertOk();
$this->assertStringContainsString($notSubmitted->email, $this->streamedContent($csv));
```

Add this helper to `SeminarAttendanceRosterTest` for the streamed response:

```php
private function streamedContent(\Illuminate\Testing\TestResponse $response): string
{
    ob_start();
    $response->baseResponse->sendContent();

    return (string) ob_get_clean();
}
```

- [x] **Step 2: Run roster test and observe failure.** Run `php artisan test --filter=SeminarAttendanceRosterTest`; expect the no-attendance registrant to be missing.
- [x] **Step 3: Render and export the same registration-first view.** Add admin attendance index/export routes to the admin attendance controller. Paginate registrants with users, fetch attendance for current page's user IDs in one query keyed by user ID, and pair records by the parent seminar ID; for CSV, stream registrants in chunks of 200 and query attendance for that chunk. Include all registration states but enable manual actions only for active confirmed rows. CSV columns: name, email, participant type, registration status, attendance status, method, attended at, joined at, left at, total minutes. Reuse current streamed response and authorization. Add code controls/one-time code display and manual mark/correct/remove forms to manager/admin views. Learner detail shows only their own attendance status and code form in the allowed window. Add `docs/educational-events.md` documenting authoring combinations, protected external-link behavior, release/expiry options, notification scheduler and queue worker, eight-digit code handling, PHT/UTC conversion, the limit of code-based attendance evidence, and safe migration/test database commands.

```php
$registrants = $seminar->registrants()->with('user')->orderBy('registered_at')->paginate(25);
$attendances = $seminar->attendances()
    ->whereIn('user_id', $registrants->getCollection()->pluck('user_id'))
    ->get()->keyBy('user_id');
return view('connectors.seminars.attendance', compact('seminar', 'registrants', 'attendances', 'connector'));
```

- [x] **Step 4: Run roster and neighboring tests.** Run `php artisan test tests/Feature/Seminars/SeminarAttendanceRosterTest.php tests/Feature/Seminars/SeminarAttendanceTest.php tests/Feature/Connectors/SeminarManualAttendanceTest.php tests/Feature/Admin/AdminSeminarAttendanceTest.php`; expect pass.
- [x] **Step 5: Commit.** Stage this task's files and commit with `git commit -m "feat: show complete educational event attendance"`.

## Final verification and rollout gate

- [x] Confirm the test process points to `cc_db_test` (`phpunit.xml`) before executing any suite. Do not run destructive migration commands against the development database.
- [ ] Run the complete suite: `php artisan test`; expect zero failures. If unrelated failures exist, report exact failures and do not claim a clean suite.
- [x] Run `vendor/bin/pint --test` on changed PHP files and `npm run build` if the changed Blade/Alpine assets require the repository build; expect zero formatter errors and a successful build. Run `git diff --check`; expect no whitespace errors.
- [ ] On a backed-up staging database with representative legacy records, run the normal `php artisan migrate` forward path, then inspect counts and spot-check old `physical`→`seminar/in_person`, old `webinar`→`webinar/native`, channel/attendance preservation, and published visibility. Never reset the database. Do not apply the migration to development merely to satisfy plan verification.
- [x] Review the final diff against every section of the approved spec: authoring, lifecycle, eligibility, protected URL, link change recipients, scheduled idempotency, attendance code/limits, manual audit, native regression, exports, and docs. Confirm no ordinary event HTML/JSON/notification contains `external_url` or `attendance_code_hash`.


Verification evidence recorded 2026-10-04:

- Focused seminar and connector tests passed: 75 tests, 603 assertions. The final roster/export regression passed separately: 3 tests, 54 assertions.
- Pint checks passed for the changed PHP files, the Vite production build passed, and the committed diff passed `git diff --check`.
- Direct PHPUnit suite against `cc_db_test`: 1,692 tests, 8,666 assertions, 72 errors and 7 failures. The errors are in SQLite-dependent learning-path/media suites while `phpunit.xml` targets MySQL. The failures are `ConnectorNotificationTest::test_connector_submission_moderation_invitation_and_withdrawal_notifications_are_sent`, `ConnectorRegistrationTest::test_authenticated_verified_learner_can_register_connector_without_creating_user`, `LearnerIdentityReviewTest::test_rejection_requires_reason_and_records_decision`, `InstructorLearnerCategoryClassificationTest::test_adult_learner_without_verified_child_link_is_categorized_as_adult`, `InstructorLearnerCategoryClassificationTest::test_adult_learner_with_verified_child_link_is_categorized_as_adult_parent`, `GuardianInvitationMessagingTest::test_invitation_detail_exposes_the_guardians_full_profile_to_the_dependent`, and `ParentChildInvitationFlowTest::test_verification_required_invitation_requires_supporting_document`.
- `php artisan test` could not start because Symfony Process reported the configured Windows cwd as nonexistent in this environment. No staging database was available for the forward-migration check; no migration was applied to development.

## Execution checkpoints

Tasks 1–3 establish the compatible domain and request contracts. Tasks 4–6 make new events usable without leaking links. Tasks 7–8 complete link management and notices. Tasks 9–11 complete attendance and user-facing reporting. Review after each commit; do not begin a later task while an earlier focused test is failing. The staging migration and complete suite are the release gate.
