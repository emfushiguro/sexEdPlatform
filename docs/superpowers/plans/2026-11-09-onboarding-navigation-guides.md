# Dynamic Role-Based Onboarding Navigation Guides Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (- [ ]) syntax for tracking.

**Goal:** Add private, administrator-managed, versioned Learner and Instructor onboarding videos with a dismissible dashboard introduction, permanent role-specific Guide pages, and aggregate completion reporting.

**Architecture:** Add a focused three-table domain: stable guides, immutable published versions, and one user-state row per version. An onboarding lifecycle service owns draft media and publication changes; an audience service owns role eligibility, effective completion/dismissal, mutations, and analytics. Existing Laravel route groups, Blade/Alpine layouts, the private BinaryFileResponse pattern, and the existing 100 MiB upload module remain the platform boundaries.

**Tech Stack:** PHP 8/Laravel, Eloquent, Spatie Permission, Blade, Alpine.js, native HTML dialog, Vite, Node test runner, PHPUnit with the isolated MySQL cc_db_test database.

**Approved specification:** docs/superpowers/specs/2026-11-09-onboarding-navigation-guides-design.md

## Global Constraints

- Execute implementation in an isolated worktree because the source checkout already contains unrelated public/build changes. Never stage or overwrite those generated assets.
- Never reset, wipe, truncate, recreate, or destructively reseed the development database. Do not run migrate:fresh, db:wipe, TRUNCATE, DROP TABLE, or an unscoped DELETE.
- PHPUnit may use RefreshDatabase only through phpunit.xml, which fixes APP_ENV=testing and DB_DATABASE=cc_db_test. Confirm those values before the first test command.
- Use forward-only additive migrations. Their down methods must not drop onboarding history tables, delete state, or revoke the new permission.
- The initial guide key is dashboard_navigation. Initial target roles are learner and instructor.
- Only one guide may be current and published for a guide key/role pair. Multiple drafts may exist, but one guide has at most one unpublished draft version.
- Published versions are immutable. Unpublish/republish does not create a version.
- A separate guide that supersedes another guide is new onboarding. Only revisions of the same guide can inherit completion or dismissal.
- Store video files on the private local disk. Do not expose storage paths or use public Storage URLs.
- Accept MP4, MPEG, MOV, AVI, and WebM through exactly 102,400 KiB / 104,857,600 bytes. Keep the existing 100M upload and 110M POST deployment requirements.
- Store no video binary in the database. Do not add a thumbnail, transcoding, compression, adaptive streaming, or external video provider.
- Completion is explicit. Playback start, percentage, and ended events never create completion.
- Skip, Close, and Escape produce the same server-persisted dismissal. Dismissal never fills completed_at.
- Ordinary users may access only the current published version for their active dashboard context. Administrators may preview draft and historical versions.
- Current AppServiceProvider behavior treats the Admin role as a super-admin in Gate::before. Register and check manage onboarding guides without redesigning that global RBAC rule.
- Analytics are aggregate-only and use currently dashboard-eligible users. Do not add a named roster, export, or publication-time population snapshot.
- Preserve old published files and every state row on update, supersede, unpublish, and archive. Remove only failed uploads or replaced files referenced solely by an unpublished draft.
- Do not add a new dependency.
- Separate source/PHPUnit/build evidence from browser, Hostinger, and production evidence in the final report.

---

## Planned File Map

### Domain and persistence

- Create **database/migrations/2026_11_09_000001_create_onboarding_guide_tables.php** — additive guide, version, and user-state schema.
- Create **database/migrations/2026_11_09_000002_add_manage_onboarding_guides_permission.php** — idempotent permission creation and Admin grant.
- Create **app/Models/OnboardingGuide.php** — stable aggregate constants and relations.
- Create **app/Models/OnboardingGuideVersion.php** — immutable revision metadata and relations.
- Create **app/Models/UserOnboardingGuideState.php** — unique dismissal/completion state.
- Create **database/factories/OnboardingGuideFactory.php**, **OnboardingGuideVersionFactory.php**, and **UserOnboardingGuideStateFactory.php** — focused test fixtures.
- Modify **app/Models/User.php** — onboarding state and created-guide relations only.
- Modify **database/seeders/PermissionSeeder.php** — canonical permission catalog.

### Business logic and delivery

- Create **app/Services/Onboarding/OnboardingGuideLifecycleService.php** — draft storage, safe replacement, publish, unpublish, supersede, and archive.
- Create **app/Services/Onboarding/OnboardingGuideAudienceService.php** — current guide, eligibility, effective state, mutations, and aggregate analytics.
- Create **app/Http/Controllers/Onboarding/GuideVideoController.php** — private inline byte-range-capable response.
- Create **app/Http/Controllers/Onboarding/GuideStateController.php** — authenticated dismiss and complete endpoints.
- Create **app/Http/Controllers/Onboarding/GuideController.php** — fixed-context Learner and Instructor Guide pages.

### Administration

- Create **app/Http/Controllers/Admin/OnboardingGuideController.php**.
- Create **app/Http/Requests/Admin/StoreOnboardingGuideRequest.php**.
- Create **app/Http/Requests/Admin/UpdateOnboardingGuideRequest.php**.
- Create **app/Http/Requests/Admin/PublishOnboardingGuideRequest.php**.
- Create **resources/views/admin/onboarding-guides/index.blade.php**.
- Create **resources/views/admin/onboarding-guides/create.blade.php**.
- Create **resources/views/admin/onboarding-guides/edit.blade.php**.
- Create **resources/views/admin/onboarding-guides/history.blade.php**.
- Create **resources/views/admin/onboarding-guides/partials/form.blade.php**.
- Modify **routes/admin.php** and **resources/views/layouts/admin.blade.php**.
- Modify **resources/js/video-upload-form.js**, **resources/views/instructor/topics/partials/upload-progress.blade.php**, and **resources/views/instructor/topics/partials/video-file-preview.blade.php** to keep their current defaults while accepting guide-specific copy.

### Learner and Instructor presentation

- Create **resources/views/components/onboarding/guide-player.blade.php**.
- Create **resources/views/components/onboarding/guide-modal.blade.php**.
- Create **resources/views/learner/guide.blade.php** and **resources/views/instructor/guide.blade.php**.
- Create **resources/js/onboarding-guide-modal.js** and import it from **resources/js/app.js**.
- Modify **routes/web.php**, **routes/instructor.php**, **app/Http/Controllers/Learner/DashboardController.php**, **app/Http/Controllers/Instructor/DashboardController.php**, **resources/views/learner/dashboard.blade.php**, **resources/views/instructor/dashboard.blade.php**, **resources/views/layouts/learner-sidebar.blade.php**, and **resources/views/layouts/instructor-app.blade.php**.

### Tests

- Create **tests/Feature/Onboarding/OnboardingGuideDomainTest.php**.
- Create **tests/Feature/Onboarding/OnboardingGuideLifecycleTest.php**.
- Create **tests/Feature/Onboarding/OnboardingGuideAudienceTest.php**.
- Create **tests/Feature/Onboarding/OnboardingGuideVideoAccessTest.php**.
- Create **tests/Feature/Admin/AdminOnboardingGuideManagementTest.php**.
- Create **tests/Feature/Onboarding/OnboardingGuideStateTest.php**.
- Create **tests/Feature/Onboarding/OnboardingGuidePageTest.php**.
- Create **tests/Feature/Onboarding/OnboardingGuideModalTest.php**.
- Create **tests/Unit/JavaScript/onboarding-guide-modal.test.js**.
- Modify **tests/Unit/JavaScript/video-upload-form.test.js**.

---

### Task 1: Add the forward-only domain schema and permission

**Files:**
- Create: database/migrations/2026_11_09_000001_create_onboarding_guide_tables.php
- Create: database/migrations/2026_11_09_000002_add_manage_onboarding_guides_permission.php
- Create: app/Models/OnboardingGuide.php
- Create: app/Models/OnboardingGuideVersion.php
- Create: app/Models/UserOnboardingGuideState.php
- Create: database/factories/OnboardingGuideFactory.php
- Create: database/factories/OnboardingGuideVersionFactory.php
- Create: database/factories/UserOnboardingGuideStateFactory.php
- Modify: app/Models/User.php
- Modify: database/seeders/PermissionSeeder.php
- Create: tests/Feature/Onboarding/OnboardingGuideDomainTest.php

**Interfaces:**
- Produces: OnboardingGuide constants GUIDE_KEY_DASHBOARD_NAVIGATION, ROLE_LEARNER, ROLE_INSTRUCTOR, STATUS_DRAFT, STATUS_PUBLISHED, and STATUS_ARCHIVED.
- Produces: OnboardingGuide::versions(), currentVersion(), creator(); OnboardingGuideVersion::guide(), states(), creator(); UserOnboardingGuideState::user(), version().
- Produces: User::onboardingGuideStates() and User::createdOnboardingGuides().
- Produces: the manage onboarding guides permission, granted idempotently to the Admin role.

- [ ] **Step 1: Confirm the isolated test database contract**

Run:

~~~powershell
rg -n 'APP_ENV.*testing|DB_DATABASE.*cc_db_test' phpunit.xml
~~~

Expected: one APP_ENV line containing testing and one DB_DATABASE line containing cc_db_test. Stop if either differs; do not run a database test against the development connection.

- [ ] **Step 2: Write the failing domain tests**

Create the test with these named cases and assertions:

~~~php
final class OnboardingGuideDomainTest extends TestCase
{
    public function test_onboarding_schema_and_relationships_exist(): void
    {
        $this->assertTrue(Schema::hasColumns('onboarding_guides', [
            'guide_key', 'target_role', 'status', 'current_version_id',
            'created_by', 'archived_at',
        ]));
        $this->assertTrue(Schema::hasColumns('onboarding_guide_versions', [
            'onboarding_guide_id', 'version_number', 'title', 'description',
            'video_disk', 'video_path', 'video_original_name', 'video_mime_type',
            'video_size', 'requires_recompletion', 'published_at', 'created_by',
        ]));
        $this->assertTrue(Schema::hasColumns('user_onboarding_guide_states', [
            'user_id', 'onboarding_guide_version_id', 'dismissed_at', 'completed_at',
        ]));

        $guide = OnboardingGuide::factory()->create();
        $version = OnboardingGuideVersion::factory()->for($guide, 'guide')->create();
        $guide->update(['current_version_id' => $version->id]);
        $state = UserOnboardingGuideState::factory()->for($version, 'version')->create();

        $this->assertTrue($guide->fresh()->currentVersion->is($version));
        $this->assertTrue($version->states->contains($state));
        $this->assertTrue($state->user->onboardingGuideStates->contains($state));
    }

    public function test_user_and_version_pair_is_unique(): void
    {
        $state = UserOnboardingGuideState::factory()->create();

        $this->expectException(QueryException::class);
        UserOnboardingGuideState::factory()->create([
            'user_id' => $state->user_id,
            'onboarding_guide_version_id' => $state->onboarding_guide_version_id,
        ]);
    }

    public function test_permission_migration_is_idempotent_and_preserving(): void
    {
        $migration = require database_path(
            'migrations/2026_11_09_000002_add_manage_onboarding_guides_permission.php'
        );

        $migration->up();
        $migration->up();
        $migration->down();

        $this->assertSame(1, Permission::query()
            ->where('name', 'manage onboarding guides')
            ->where('guard_name', 'web')
            ->count());
        $this->assertTrue(Role::findByName('admin', 'web')
            ->hasPermissionTo('manage onboarding guides'));
    }
}
~~~

Include the exact imports for Schema, QueryException, Permission, Role, the three models, and Tests\TestCase.

- [ ] **Step 3: Run the tests and verify RED**

Run:

~~~powershell
php artisan test --compact tests/Feature/Onboarding/OnboardingGuideDomainTest.php
~~~

Expected: FAIL because the onboarding tables and model classes do not exist.

- [ ] **Step 4: Create the additive schema migration**

Use this schema shape and explicit short constraint names so MySQL's identifier limit is not exceeded:

~~~php
public function up(): void
{
    Schema::create('onboarding_guides', function (Blueprint $table): void {
        $table->id();
        $table->string('guide_key', 100);
        $table->string('target_role', 50);
        $table->string('status', 20)->default('draft');
        $table->unsignedBigInteger('current_version_id')->nullable();
        $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
        $table->timestamp('archived_at')->nullable();
        $table->timestamps();
        $table->index(['guide_key', 'target_role', 'status'], 'og_lookup_index');
    });

    Schema::create('onboarding_guide_versions', function (Blueprint $table): void {
        $table->id();
        $table->foreignId('onboarding_guide_id');
        $table->unsignedInteger('version_number');
        $table->string('title');
        $table->text('description');
        $table->string('video_disk', 50)->default('local');
        $table->string('video_path');
        $table->string('video_original_name');
        $table->string('video_mime_type', 100);
        $table->unsignedBigInteger('video_size');
        $table->boolean('requires_recompletion')->default(false);
        $table->timestamp('published_at')->nullable();
        $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
        $table->timestamps();
        $table->foreign('onboarding_guide_id', 'ogv_guide_fk')
            ->references('id')->on('onboarding_guides')->restrictOnDelete();
        $table->unique(
            ['onboarding_guide_id', 'version_number'],
            'ogv_guide_number_unique'
        );
        $table->index(
            ['onboarding_guide_id', 'published_at'],
            'ogv_publication_index'
        );
    });

    Schema::table('onboarding_guides', function (Blueprint $table): void {
        $table->foreign('current_version_id', 'og_current_version_fk')
            ->references('id')->on('onboarding_guide_versions')->restrictOnDelete();
    });

    Schema::create('user_onboarding_guide_states', function (Blueprint $table): void {
        $table->id();
        $table->foreignId('user_id');
        $table->unsignedBigInteger('onboarding_guide_version_id');
        $table->timestamp('dismissed_at')->nullable();
        $table->timestamp('completed_at')->nullable();
        $table->timestamps();
        $table->foreign('user_id', 'uogs_user_fk')
            ->references('id')->on('users')->cascadeOnDelete();
        $table->foreign('onboarding_guide_version_id', 'uogs_version_fk')
            ->references('id')->on('onboarding_guide_versions')->restrictOnDelete();
        $table->unique(
            ['user_id', 'onboarding_guide_version_id'],
            'uogs_user_version_unique'
        );
    });
}

public function down(): void
{
    // Forward-only: retain guide versions and user history.
}
~~~

- [ ] **Step 5: Add the preserving permission migration and canonical catalog entry**

Copy the repository's 2026_11_02 permission-migration pattern:

~~~php
public function up(): void
{
    DB::transaction(function (): void {
        $permission = Permission::findOrCreate('manage onboarding guides', 'web');
        Role::query()
            ->where('name', 'admin')
            ->where('guard_name', 'web')
            ->first()
            ?->givePermissionTo($permission);
    });

    app(PermissionRegistrar::class)->forgetCachedPermissions();
}

public function down(): void
{
    // Retain the permission and grant on rollback.
}
~~~

Add manage onboarding guides to PermissionSeeder::permissionNames(). Do not change RoleSeeder: its existing Admin sync already consumes the full canonical permission catalog.

- [ ] **Step 6: Add the models, casts, and factories**

Use these exact model boundaries:

~~~php
final class OnboardingGuide extends Model
{
    use HasFactory;

    public const GUIDE_KEY_DASHBOARD_NAVIGATION = 'dashboard_navigation';
    public const ROLE_LEARNER = 'learner';
    public const ROLE_INSTRUCTOR = 'instructor';
    public const STATUS_DRAFT = 'draft';
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_ARCHIVED = 'archived';

    protected $fillable = [
        'guide_key', 'target_role', 'status', 'current_version_id',
        'created_by', 'archived_at',
    ];

    protected function casts(): array
    {
        return ['archived_at' => 'datetime'];
    }
}
~~~

OnboardingGuideVersion fillable fields must exactly match the version migration and cast requires_recompletion to boolean and published_at to datetime. UserOnboardingGuideState casts dismissed_at and completed_at to datetime. Add typed BelongsTo and HasMany relations named in the Interfaces block.

Factory defaults:

~~~php
// OnboardingGuideFactory
return [
    'guide_key' => OnboardingGuide::GUIDE_KEY_DASHBOARD_NAVIGATION,
    'target_role' => OnboardingGuide::ROLE_LEARNER,
    'status' => OnboardingGuide::STATUS_DRAFT,
    'created_by' => User::factory(),
];

// OnboardingGuideVersionFactory
return [
    'onboarding_guide_id' => OnboardingGuide::factory(),
    'version_number' => 1,
    'title' => fake()->sentence(4),
    'description' => fake()->paragraph(),
    'video_disk' => 'local',
    'video_path' => 'onboarding-guides/testing/guide.mp4',
    'video_original_name' => 'guide.mp4',
    'video_mime_type' => 'video/mp4',
    'video_size' => 1024,
    'requires_recompletion' => true,
    'published_at' => null,
    'created_by' => User::factory(),
];
~~~

The state factory uses User::factory(), OnboardingGuideVersion::factory(), and null timestamps.

- [ ] **Step 7: Run the domain tests and verify GREEN**

Run:

~~~powershell
php artisan test --compact tests/Feature/Onboarding/OnboardingGuideDomainTest.php tests/Feature/Rbac/RbacPermissionCatalogSeederTest.php
~~~

Expected: PASS; the new permission is present, the Admin role receives it, other canonical permissions remain present, and the unique state constraint is enforced.

- [ ] **Step 8: Commit the domain foundation**

~~~powershell
git add database/migrations/2026_11_09_000001_create_onboarding_guide_tables.php database/migrations/2026_11_09_000002_add_manage_onboarding_guides_permission.php app/Models/OnboardingGuide.php app/Models/OnboardingGuideVersion.php app/Models/UserOnboardingGuideState.php database/factories/OnboardingGuideFactory.php database/factories/OnboardingGuideVersionFactory.php database/factories/UserOnboardingGuideStateFactory.php app/Models/User.php database/seeders/PermissionSeeder.php tests/Feature/Onboarding/OnboardingGuideDomainTest.php
git commit -m "feat(onboarding): add guide domain"
~~~

---

### Task 2: Implement safe draft and publication lifecycle

**Files:**
- Create: app/Services/Onboarding/OnboardingGuideLifecycleService.php
- Create: tests/Feature/Onboarding/OnboardingGuideLifecycleTest.php

**Interfaces:**
- Consumes: the Task 1 models and the private local disk.
- Produces:
  - createDraft(User $actor, array $attributes, UploadedFile $video): OnboardingGuide
  - updateDraft(User $actor, OnboardingGuide $guide, array $attributes, ?UploadedFile $video): OnboardingGuide
  - publish(User $actor, OnboardingGuide $guide, bool $requiresRecompletion, bool $confirmSupersede): OnboardingGuide
  - unpublish(OnboardingGuide $guide): OnboardingGuide
  - archive(OnboardingGuide $guide): OnboardingGuide
  - draftVersion(OnboardingGuide $guide): ?OnboardingGuideVersion

- [ ] **Step 1: Write the failing lifecycle tests**

Cover these exact behaviors with Storage::fake('local'):

~~~php
public function test_create_draft_stores_private_uuid_video_metadata(): void
{
    Storage::fake('local');
    $actor = User::factory()->create();
    $service = app(OnboardingGuideLifecycleService::class);

    $guide = $service->createDraft($actor, [
        'title' => 'Learner dashboard guide',
        'description' => 'Learn the dashboard.',
        'target_role' => 'learner',
    ], UploadedFile::fake()->create('walkthrough.mp4', 512, 'video/mp4'));

    $version = $service->draftVersion($guide);
    $this->assertSame(1, $version->version_number);
    $this->assertSame('local', $version->video_disk);
    $this->assertStringStartsWith(
        'onboarding-guides/'.$guide->id.'/',
        $version->video_path
    );
    Storage::disk('local')->assertExists($version->video_path);
}
~~~

Also add named tests for:

- text-only editing of a published guide creates one unpublished next version sharing the existing media safely;
- repeated editing reuses the same draft version;
- replacing draft media stores first and never deletes a path still referenced by a published version;
- a failed database update removes only the new upload and leaves the prior draft intact;
- first publication forces requires_recompletion=true;
- an informational revision records false and preserves earlier rows;
- publication supersedes one other guide for the same key/role only when confirmSupersede is true;
- a separate superseding guide keeps its own state boundary;
- unpublish/republish without a new draft creates no version;
- archive retains versions, files, and states;
- target_role changes before first publication and is rejected afterward;
- publication rejects a current_version_id that points to another guide's version;
- duplicate publish requests are idempotent.

- [ ] **Step 2: Run the lifecycle test and verify RED**

~~~powershell
php artisan test --compact tests/Feature/Onboarding/OnboardingGuideLifecycleTest.php
~~~

Expected: FAIL because OnboardingGuideLifecycleService does not exist.

- [ ] **Step 3: Implement private file storage and draft creation**

Store UUID filenames and trusted metadata, never the client filename:

~~~php
private function storeVideo(OnboardingGuide $guide, UploadedFile $video): array
{
    $extension = strtolower((string) $video->getClientOriginalExtension());
    $filename = Str::uuid().($extension === '' ? '' : '.'.$extension);
    $directory = 'onboarding-guides/'.$guide->id;
    $path = Storage::disk('local')->putFileAs($directory, $video, $filename);

    if (! is_string($path) || $path === '') {
        throw new RuntimeException('Failed to store onboarding guide video.');
    }

    return [
        'video_disk' => 'local',
        'video_path' => $path,
        'video_original_name' => $this->safeDisplayName(
            $video->getClientOriginalName(),
            $extension
        ),
        'video_mime_type' => (string) $video->getMimeType(),
        'video_size' => (int) $video->getSize(),
    ];
}
~~~

createDraft first creates the guide in a transaction, then stores the file, then creates version 1. If storage or version persistence fails, remove only the new path and rethrow. Keep title and description on the version, not on the guide.

- [ ] **Step 4: Implement draft cloning and safe replacement**

updateDraft must:

1. reject archived guides;
2. reject target_role changes after current_version_id is non-null;
3. reuse the existing unpublished version, or clone the current version into version_number + 1;
4. store replacement media before updating the draft;
5. delete the old path after commit only when no remaining version references it.

Lock the guide row before selecting or allocating the draft so concurrent edits cannot create the same next version number.

Use this reference guard:

~~~php
private function deleteIfUnreferenced(string $disk, string $path): void
{
    $referenced = OnboardingGuideVersion::query()
        ->where('video_disk', $disk)
        ->where('video_path', $path)
        ->exists();

    if (! $referenced) {
        Storage::disk($disk)->delete($path);
    }
}
~~~

- [ ] **Step 5: Implement locked lifecycle transitions**

publish must lock every guide for the same indexed guide_key/target_role and reload the selected guide inside the transaction:

~~~php
$lockedGuides = OnboardingGuide::query()
    ->where('guide_key', $guide->guide_key)
    ->where('target_role', $guide->target_role)
    ->lockForUpdate()
    ->get();

$locked = $lockedGuides->firstWhere('id', $guide->id)
    ?? throw new ModelNotFoundException();
$draft = $locked->versions()
    ->whereNull('published_at')
    ->latest('version_number')
    ->lockForUpdate()
    ->first();
~~~

If no draft exists and the selected guide is already published, return it unchanged. Otherwise fail with a validation error. Verify Storage::disk($draft->video_disk)->exists($draft->video_path) before changing state.

If another guide is published and confirmSupersede is false, throw ValidationException with confirm_supersede. If confirmed, change the other guide to draft without clearing current_version_id. Publish the selected draft, force requires_recompletion=true when the guide has never had a current version, and set status/current_version_id atomically.

unpublish is idempotent and changes only published to draft. archive sets archived and archived_at without any Storage delete. Neither method creates versions.

- [ ] **Step 6: Run the lifecycle tests and verify GREEN**

~~~powershell
php artisan test --compact tests/Feature/Onboarding/OnboardingGuideLifecycleTest.php
~~~

Expected: PASS with private file preservation and every status/version assertion green.

- [ ] **Step 7: Commit the lifecycle**

~~~powershell
git add app/Services/Onboarding/OnboardingGuideLifecycleService.php tests/Feature/Onboarding/OnboardingGuideLifecycleTest.php
git commit -m "feat(onboarding): add guide lifecycle"
~~~

---

### Task 3: Resolve role eligibility, effective state, and analytics

**Files:**
- Create: app/Services/Onboarding/OnboardingGuideAudienceService.php
- Create: tests/Feature/Onboarding/OnboardingGuideAudienceTest.php

**Interfaces:**
- Consumes: OnboardingGuide, OnboardingGuideVersion, UserOnboardingGuideState, existing User role/profile/identity/transition/suspension relations.
- Produces:
  - currentFor(User $user, string $role): ?OnboardingGuideVersion
  - presentationFor(User $user, string $role): ?array
  - statusFor(User $user, OnboardingGuideVersion $version): array
  - assertCanUseVersion(User $user, OnboardingGuideVersion $version): void
  - analyticsFor(OnboardingGuide $guide): array
- statusFor returns status, completed_at, dismissed_at, source_version_id, and boundary_version_id.

- [ ] **Step 1: Write the failing audience tests**

Build fixtures for both roles and assert:

~~~php
$presentation = $service->presentationFor($learner, 'learner');
$this->assertTrue($presentation['version']->is($learnerVersion));
$this->assertSame('incomplete', $presentation['status']['status']);
$this->assertNull($service->currentFor($learner, 'instructor'));
~~~

Add cases for:

- all current and future eligible users resolve the current role guide;
- no guide, draft, unpublished, archived, historical, and wrong-role versions resolve null;
- Instructor is the default classification, while canSwitchToLearnerView permits the fixed Learner route context;
- inactive and actively suspended accounts are ineligible;
- incomplete Learner profiles, pending/rejected identity cases, incomplete guardian onboarding, and pending child verification are ineligible;
- legacy Learners without an identity case remain eligible, matching current middleware;
- completion or dismissal on an informational version boundary suppresses presentation;
- completion wins over dismissal;
- a required revision starts a new boundary and presents again;
- informational revisions create no copied state rows;
- analytics return eligible, completed, dismissed, incomplete, and completion_percentage with dismissed included in incomplete;
- zero eligible users produce 0.0 percent.

- [ ] **Step 2: Run the audience test and verify RED**

~~~powershell
php artisan test --compact tests/Feature/Onboarding/OnboardingGuideAudienceTest.php
~~~

Expected: FAIL because OnboardingGuideAudienceService does not exist.

- [ ] **Step 3: Implement fixed-context access checks**

Use constants and reject unsupported role values:

~~~php
public function canAccessRole(User $user, string $role): bool
{
    if ($user->status !== 'active' || $this->hasActiveSuspension($user)) {
        return false;
    }

    return match ($role) {
        OnboardingGuide::ROLE_INSTRUCTOR => $user->isInstructor(),
        OnboardingGuide::ROLE_LEARNER => $user->canSwitchToLearnerView()
            || $this->canAccessLearnerDashboard($user),
        default => false,
    };
}
~~~

canAccessLearnerDashboard must mirror current middleware without mutating identity cases:

- require user->isLearner() and a LearnerProfile whose username, birthdate, city_code, and barangay_code are non-empty;
- for parent registration, require isParentVerificationApproved() and hasCompletedGuardianOnboarding();
- reject a child link with verification_document_path and pending/rejected verification_status;
- for standard learners, accept no identity history as legacy access; otherwise require the non-superseded case to be approved and reject sandbox_manual_review when local manual review is disabled.

- [ ] **Step 4: Implement current guide and boundary status**

currentFor queries status=published, the fixed guide key and role, eager-loads currentVersion, and returns null unless current_version_id belongs to that same guide and has published_at.

statusFor finds the latest required boundary at or before the current version:

~~~php
$boundary = $version->guide->versions()
    ->where('version_number', '<=', $version->version_number)
    ->where('requires_recompletion', true)
    ->latest('version_number')
    ->first() ?? $version;

$versionIds = $version->guide->versions()
    ->whereBetween('version_number', [
        $boundary->version_number,
        $version->version_number,
    ])
    ->pluck('id');

$states = UserOnboardingGuideState::query()
    ->where('user_id', $user->id)
    ->whereIn('onboarding_guide_version_id', $versionIds)
    ->with('version:id,version_number')
    ->get();
~~~

Select the newest completed state first. If none exists, select the newest dismissed state. Keep the original timestamp and source version ID.

presentationFor returns null when currentFor is null or status is completed/dismissed; otherwise return version and status.

- [ ] **Step 5: Implement aggregate eligibility and analytics**

Start from active role candidates and eager-load only the relations used by canAccessRole. Filter through the same method rather than creating a second definition:

~~~php
// shortcut: evaluate eligibility in PHP for the current user volume;
// move this predicate to dedicated SQL scopes if analytics latency grows.
$eligible = $this->candidateUsers($guide->target_role)
    ->filter(fn (User $user): bool => $this->canAccessRole(
        $user,
        $guide->target_role
    ))
    ->values();
~~~

Use one state query for all eligible IDs and the current boundary's version IDs. Count unique user IDs. completed takes precedence; dismissed includes only users without effective completion; incomplete equals eligible minus completed.

- [ ] **Step 6: Run the audience tests and verify GREEN**

~~~powershell
php artisan test --compact tests/Feature/Onboarding/OnboardingGuideAudienceTest.php
~~~

Expected: PASS for role access, version boundaries, and aggregate counts.

- [ ] **Step 7: Commit the audience resolver**

~~~powershell
git add app/Services/Onboarding/OnboardingGuideAudienceService.php tests/Feature/Onboarding/OnboardingGuideAudienceTest.php
git commit -m "feat(onboarding): resolve guide audience"
~~~

---

### Task 4: Serve authorized private video

**Files:**
- Create: app/Http/Controllers/Onboarding/GuideVideoController.php
- Modify: routes/web.php
- Create: tests/Feature/Onboarding/OnboardingGuideVideoAccessTest.php

**Interfaces:**
- Consumes: OnboardingGuideAudienceService::assertCanUseVersion().
- Produces: GET route onboarding-guides.video at /onboarding-guides/versions/{version}/video.
- Produces: inline BinaryFileResponse with private/no-store and nosniff headers.

- [ ] **Step 1: Write the failing media authorization tests**

Use Storage::fake('local') for existence/authorization tests and a temporary real local file for the byte-range test. Assert:

~~~php
$this->actingAs($learner)
    ->get(route('onboarding-guides.video', $learnerVersion))
    ->assertOk()
    ->assertHeader('Content-Type', 'video/mp4')
    ->assertHeader('X-Content-Type-Options', 'nosniff')
    ->assertHeader('Cache-Control', 'private, no-store, max-age=0');

$this->actingAs($learner)
    ->get(route('onboarding-guides.video', $instructorVersion))
    ->assertForbidden();
~~~

Also test guests, unpublished/archived/historical versions, missing files, Admin draft/history preview, and Range: bytes=0-3 returning 206 with Content-Range when using a real local file.

- [ ] **Step 2: Run the media tests and verify RED**

~~~powershell
php artisan test --compact tests/Feature/Onboarding/OnboardingGuideVideoAccessTest.php
~~~

Expected: FAIL because the route/controller is missing.

- [ ] **Step 3: Add the authenticated route**

Inside the existing auth group in routes/web.php:

~~~php
Route::get(
    '/onboarding-guides/versions/{version}/video',
    \App\Http\Controllers\Onboarding\GuideVideoController::class
)->name('onboarding-guides.video');
~~~

Use explicit parameter naming for OnboardingGuideVersion model binding if Laravel cannot infer version.

- [ ] **Step 4: Implement the controller**

Use the existing Connector VerificationDocumentController response pattern:

~~~php
public function __invoke(
    Request $request,
    OnboardingGuideVersion $version,
): BinaryFileResponse {
    $user = $request->user();
    $isAdminPreview = (bool) $user?->can('manage onboarding guides');

    if (! $isAdminPreview) {
        $this->audience->assertCanUseVersion($user, $version);
    }

    abort_unless($version->video_disk === 'local', 404);
    abort_unless(in_array($version->video_mime_type, self::ALLOWED_MIME_TYPES, true), 404);

    $disk = Storage::disk('local');
    abort_unless($disk->exists($version->video_path), 404);
    $absolutePath = $disk->path($version->video_path);
    abort_unless(is_file($absolutePath), 404);

    $response = response()->file($absolutePath);
    $response->headers->set('Content-Type', $version->video_mime_type);
    $response->headers->set('X-Content-Type-Options', 'nosniff');
    $response->headers->set('Cache-Control', 'private, no-store, max-age=0');
    $response->headers->set('Pragma', 'no-cache');
    $response->headers->set(
        'Content-Disposition',
        $response->headers->makeDisposition('inline', 'onboarding-guide')
    );

    return $response;
}
~~~

ALLOWED_MIME_TYPES contains video/mp4, video/mpeg, video/quicktime, video/x-msvideo, and video/webm. Never accept a disk/path query parameter.

- [ ] **Step 5: Run the media tests and verify GREEN**

~~~powershell
php artisan test --compact tests/Feature/Onboarding/OnboardingGuideVideoAccessTest.php
~~~

Expected: PASS, including 403/404 boundaries and byte-range behavior.

- [ ] **Step 6: Commit private playback**

~~~powershell
git add app/Http/Controllers/Onboarding/GuideVideoController.php routes/web.php tests/Feature/Onboarding/OnboardingGuideVideoAccessTest.php
git commit -m "feat(onboarding): stream private guide videos"
~~~

---

### Task 5: Add end-to-end Admin guide management

**Files:**
- Create: app/Http/Controllers/Admin/OnboardingGuideController.php
- Create: app/Http/Requests/Admin/StoreOnboardingGuideRequest.php
- Create: app/Http/Requests/Admin/UpdateOnboardingGuideRequest.php
- Create: app/Http/Requests/Admin/PublishOnboardingGuideRequest.php
- Modify: routes/admin.php
- Modify: resources/views/layouts/admin.blade.php
- Create: resources/views/admin/onboarding-guides/index.blade.php
- Create: resources/views/admin/onboarding-guides/create.blade.php
- Create: resources/views/admin/onboarding-guides/edit.blade.php
- Create: resources/views/admin/onboarding-guides/history.blade.php
- Create: resources/views/admin/onboarding-guides/partials/form.blade.php
- Modify: resources/js/video-upload-form.js
- Modify: resources/views/instructor/topics/partials/upload-progress.blade.php
- Modify: resources/views/instructor/topics/partials/video-file-preview.blade.php
- Create: tests/Feature/Admin/AdminOnboardingGuideManagementTest.php
- Modify: tests/Unit/JavaScript/video-upload-form.test.js

**Interfaces:**
- Consumes: lifecycle and audience services plus onboarding-guides.video.
- Produces: Admin named routes admin.onboarding-guides.index/create/store/edit/update/history/publish/unpublish/archive.
- Produces: XHR success payload with redirect and existing upload module hooks.

- [ ] **Step 1: Write failing Admin and JavaScript tests**

Feature coverage must assert:

- non-admin users cannot reach any Admin guide route;
- Admin users can create Learner/Instructor drafts and receive validation for missing/invalid/oversized files;
- draft edit permits target_role changes, while published history forbids them;
- publish requires an explicit revision choice and supersede confirmation;
- unpublish, republish, and archive preserve records and files;
- index and history show role, version, status, aggregate counts, and direct version counts;
- draft preview points to the authorized video route;
- Admin navigation contains Onboarding Guides and ordinary layouts do not;
- XHR create/update responses contain redirect.

Add this JavaScript test:

~~~javascript
test('uses form-specific saving and failure copy', () => {
    form.dataset.videoSavingLabel = 'Saving guide...';
    form.dataset.videoFinalizingLabel = 'Finalizing guide...';
    form.dataset.videoFailureLabel = 'The guide could not be saved.';

    initializeVideoUploadForm(form, () => new FakeXhr({ status: 500 }));
    form.dispatch('submit', { preventDefault() {} });

    assert.equal(formError.textContent, 'The guide could not be saved.');
});
~~~

- [ ] **Step 2: Run Admin/JS tests and verify RED**

~~~powershell
php artisan test --compact tests/Feature/Admin/AdminOnboardingGuideManagementTest.php
node --test tests/Unit/JavaScript/video-upload-form.test.js
~~~

Expected: PHP FAIL for missing routes/controller/views and JS FAIL for missing configurable copy.

- [ ] **Step 3: Add FormRequest authorization and validation**

All three requests return:

~~~php
public function authorize(): bool
{
    return (bool) $this->user()?->can('manage onboarding guides');
}
~~~

Store rules:

~~~php
return [
    'title' => ['required', 'string', 'max:255'],
    'description' => ['required', 'string', 'max:5000'],
    'target_role' => ['required', Rule::in(['learner', 'instructor'])],
    'video' => [
        'required', 'file', 'max:102400',
        'mimetypes:video/mp4,video/mpeg,video/quicktime,video/x-msvideo,video/webm',
        'mimes:mp4,mpeg,mpg,mov,avi,webm',
    ],
];
~~~

Update uses required title/description, nullable video, and sometimes|required target_role with the same allow-list; omission means retain the persisted role. The service remains the authority for role immutability.

Publish validates requires_recompletion as nullable boolean and confirm_supersede as sometimes boolean. In withValidator, require requires_recompletion only when the route-bound guide already has current_version_id; the first publication is forced required by the lifecycle service and needs no meaningless choice.

- [ ] **Step 4: Add routes and thin controller actions**

Under the existing role:admin group:

~~~php
Route::prefix('onboarding-guides')
    ->name('onboarding-guides.')
    ->middleware('permission:manage onboarding guides')
    ->group(function (): void {
        Route::get('/', [Admin\OnboardingGuideController::class, 'index'])->name('index');
        Route::get('/create', [Admin\OnboardingGuideController::class, 'create'])->name('create');
        Route::post('/', [Admin\OnboardingGuideController::class, 'store'])->name('store');
        Route::get('/{guide}/edit', [Admin\OnboardingGuideController::class, 'edit'])->name('edit');
        Route::put('/{guide}', [Admin\OnboardingGuideController::class, 'update'])->name('update');
        Route::get('/{guide}/history', [Admin\OnboardingGuideController::class, 'history'])->name('history');
        Route::post('/{guide}/publish', [Admin\OnboardingGuideController::class, 'publish'])->name('publish');
        Route::post('/{guide}/unpublish', [Admin\OnboardingGuideController::class, 'unpublish'])->name('unpublish');
        Route::post('/{guide}/archive', [Admin\OnboardingGuideController::class, 'archive'])->name('archive');
    });
~~~

Controller mutation methods call only the lifecycle service. index eager-loads currentVersion/latest draft and maps analyticsFor only for current published guides. history eager-loads versions with direct state counts.

For store/update XHR success:

~~~php
if ($request->expectsJson()) {
    return response()->json([
        'success' => true,
        'redirect' => route('admin.onboarding-guides.edit', $guide),
    ]);
}
~~~

- [ ] **Step 5: Extend the existing upload module without changing topic defaults**

Add one copy resolver:

~~~javascript
function uploadCopy(form) {
    return {
        saving: form.dataset.videoSavingLabel || 'Saving topic...',
        finalizing: form.dataset.videoFinalizingLabel
            || 'Upload complete. Finalizing the topic...',
        failure: form.dataset.videoFailureLabel
            || 'The topic could not be saved. Please try again.',
    };
}
~~~

Use it in initializeVideoUploadForm for initial saving text, upload-load detail, and fallback failure. Keep existing file validation, object URL revocation, CSRF headers, and topic tests unchanged.

Change the two existing Blade partials to accept optional savingLabel, previewLabel, and previewStatusId variables while preserving their current literal defaults. The Admin form includes those same partials with guide-specific labels.

- [ ] **Step 6: Build the Admin views using existing conventions**

The partial form must have:

~~~blade
<form
    method="POST"
    action="{{ $action }}"
    enctype="multipart/form-data"
    data-video-upload-form
    data-video-max-bytes="104857600"
    data-video-saving-label="Saving guide..."
    data-video-finalizing-label="Upload complete. Finalizing the guide..."
    data-video-failure-label="The guide could not be saved. Please try again."
>
~~~

Render title, description, target role, and video. Disable target_role when current_version_id is non-null and send no hidden override; the server uses the persisted value. Show the existing current/draft video through onboarding-guides.video and the local selected-file preview separately.

The index follows current Admin summary-card/table/status-badge conventions. The history view lists immutable version number, publication date, re-completion setting, file size/MIME, direct completion count, and direct dismissal-without-completion count.

Revision publication UI requires Informational update or Require users to complete this new version. First publication explains that initial onboarding is required and does not show the revision choice. When another guide would be superseded, render an explicit confirm_supersede checkbox.

Add an @can('manage onboarding guides') navigation item in resources/views/layouts/admin.blade.php and active state admin.onboarding-guides.*.

- [ ] **Step 7: Run Admin, JavaScript, and view checks**

~~~powershell
php artisan test --compact tests/Feature/Admin/AdminOnboardingGuideManagementTest.php tests/Feature/Onboarding/OnboardingGuideLifecycleTest.php tests/Feature/Rbac/RbacBladePermissionVisibilityTest.php
node --test tests/Unit/JavaScript/video-upload-form.test.js
php artisan view:cache
~~~

Expected: PASS; existing topic upload tests retain their original copy and the Admin workflow renders.

- [ ] **Step 8: Commit Admin management**

~~~powershell
git add app/Http/Controllers/Admin/OnboardingGuideController.php app/Http/Requests/Admin/StoreOnboardingGuideRequest.php app/Http/Requests/Admin/UpdateOnboardingGuideRequest.php app/Http/Requests/Admin/PublishOnboardingGuideRequest.php routes/admin.php resources/views/layouts/admin.blade.php resources/views/admin/onboarding-guides resources/js/video-upload-form.js resources/views/instructor/topics/partials/upload-progress.blade.php resources/views/instructor/topics/partials/video-file-preview.blade.php tests/Feature/Admin/AdminOnboardingGuideManagementTest.php tests/Unit/JavaScript/video-upload-form.test.js
git commit -m "feat(admin): manage onboarding guides"
~~~

---

### Task 6: Add idempotent dismissal and completion endpoints

**Files:**
- Create: app/Http/Controllers/Onboarding/GuideStateController.php
- Modify: app/Services/Onboarding/OnboardingGuideAudienceService.php
- Modify: routes/web.php
- Create: tests/Feature/Onboarding/OnboardingGuideStateTest.php

**Interfaces:**
- Adds to audience service:
  - dismiss(User $user, OnboardingGuideVersion $version): UserOnboardingGuideState
  - complete(User $user, OnboardingGuideVersion $version): UserOnboardingGuideState
- Produces: POST onboarding-guides.dismiss and onboarding-guides.complete routes.

- [ ] **Step 1: Write failing state tests**

Assert:

- the route takes no user_id;
- a user can mutate only the current published version for an accessible role;
- Skip/Close-equivalent dismissal fills dismissed_at and leaves completed_at null;
- duplicate dismissal preserves the first timestamp and one row;
- completion fills completed_at, preserves earlier dismissed_at, and duplicates remain one row;
- dismissal after direct completion is a no-op;
- wrong-role, historical, unpublished, archived, stale, and forged version requests are forbidden;
- an active suspension receives the existing 423 JSON response before the controller.

- [ ] **Step 2: Run the state tests and verify RED**

~~~powershell
php artisan test --compact tests/Feature/Onboarding/OnboardingGuideStateTest.php
~~~

Expected: FAIL because the routes/controller/mutation methods are missing.

- [ ] **Step 3: Implement serialized idempotent mutations**

Use the authenticated user's row as the lock so simultaneous first submissions cannot race the unique constraint:

~~~php
return DB::transaction(function () use ($user, $version): UserOnboardingGuideState {
    User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();
    $this->assertCanUseVersion($user, $version);

    $state = UserOnboardingGuideState::query()->firstOrCreate([
        'user_id' => $user->id,
        'onboarding_guide_version_id' => $version->id,
    ]);

    if ($state->completed_at === null && $state->dismissed_at === null) {
        $state->forceFill(['dismissed_at' => now()])->save();
    }

    return $state->refresh();
});
~~~

complete uses the same lock and firstOrCreate, then fills completed_at only when null. It never clears dismissed_at.

- [ ] **Step 4: Add controller and routes**

~~~php
public function dismiss(
    Request $request,
    OnboardingGuideVersion $version,
): JsonResponse|RedirectResponse
{
    $state = $this->audience->dismiss($request->user(), $version);

    if ($request->expectsJson()) {
        return response()->json([
            'dismissed_at' => $state->dismissed_at?->toIso8601String(),
            'completed_at' => $state->completed_at?->toIso8601String(),
        ]);
    }

    return back()->with('success', 'Your guide preference was saved.');
}
~~~

complete returns the same JSON shape for fetch requests and redirects back with Guide marked complete for a normal Guide-page form submission. Register both POST routes inside the auth group with no user parameter.

- [ ] **Step 5: Run the state tests and verify GREEN**

~~~powershell
php artisan test --compact tests/Feature/Onboarding/OnboardingGuideStateTest.php tests/Feature/Onboarding/OnboardingGuideAudienceTest.php
~~~

Expected: PASS with one row per user/version under repeat calls.

- [ ] **Step 6: Commit state endpoints**

~~~powershell
git add app/Http/Controllers/Onboarding/GuideStateController.php app/Services/Onboarding/OnboardingGuideAudienceService.php routes/web.php tests/Feature/Onboarding/OnboardingGuideStateTest.php
git commit -m "feat(onboarding): persist guide state"
~~~

---

### Task 7: Add permanent role-specific Guide pages

**Files:**
- Create: app/Http/Controllers/Onboarding/GuideController.php
- Create: resources/views/components/onboarding/guide-player.blade.php
- Create: resources/views/learner/guide.blade.php
- Create: resources/views/instructor/guide.blade.php
- Modify: routes/web.php
- Modify: routes/instructor.php
- Modify: resources/views/layouts/learner-sidebar.blade.php
- Modify: resources/views/layouts/instructor-app.blade.php
- Create: tests/Feature/Onboarding/OnboardingGuidePageTest.php

**Interfaces:**
- Consumes: audience currentFor/statusFor, video route, complete endpoint.
- Produces: learner.guide and instructor.guide routes with fixed role context.
- Produces: reusable guide-player component attributes version, status, and showCompletionAction.

- [ ] **Step 1: Write failing Guide page tests**

Assert:

~~~php
$this->actingAs($learner)
    ->get(route('learner.guide'))
    ->assertOk()
    ->assertSee($version->title)
    ->assertSee(route('onboarding-guides.video', $version), false)
    ->assertSee('Mark guide complete');
~~~

Cover current role only, Instructor/Learner route context switching, inherited completion date, dismissed-but-incomplete completion action, completed replay with no duplicate state, empty state, archived/unpublished exclusion, navigation active state, and manual wrong-role video denial.

- [ ] **Step 2: Run Guide page tests and verify RED**

~~~powershell
php artisan test --compact tests/Feature/Onboarding/OnboardingGuidePageTest.php
~~~

Expected: FAIL because the routes/controller/views/navigation items are missing.

- [ ] **Step 3: Add fixed-context routes and controller methods**

Inside the existing Learner profile.completed group:

~~~php
Route::get('/guide', [\App\Http\Controllers\Onboarding\GuideController::class, 'learner'])
    ->name('guide');
~~~

Inside the existing Instructor group:

~~~php
Route::get('/guide', [\App\Http\Controllers\Onboarding\GuideController::class, 'instructor'])
    ->name('guide');
~~~

Both methods call one private render method with a constant role:

~~~php
private function render(Request $request, string $role, string $view): View
{
    $version = $this->audience->currentFor($request->user(), $role);

    return view($view, [
        'guideVersion' => $version,
        'guideStatus' => $version
            ? $this->audience->statusFor($request->user(), $version)
            : null,
    ]);
}
~~~

- [ ] **Step 4: Build the shared player and two layout wrappers**

The player renders database title/description, video with controls/playsinline/preload=metadata, completion status/date, and the completion form/button only while incomplete. Do not autoplay and do not listen for ended as completion.

Learner wrapper extends layouts.learner-app. Instructor wrapper extends layouts.instructor-app. Both render a clean no-guide empty state when guideVersion is null.

Add Guide directly after Dashboard in learner-sidebar and the Instructor MAIN group. Use learner.guide and instructor.guide active checks.

- [ ] **Step 5: Run Guide page and media tests**

~~~powershell
php artisan test --compact tests/Feature/Onboarding/OnboardingGuidePageTest.php tests/Feature/Onboarding/OnboardingGuideVideoAccessTest.php tests/Feature/Onboarding/OnboardingGuideStateTest.php
php artisan view:cache
~~~

Expected: PASS; user pages expose only the current applicable version.

- [ ] **Step 6: Commit Guide pages**

~~~powershell
git add app/Http/Controllers/Onboarding/GuideController.php resources/views/components/onboarding/guide-player.blade.php resources/views/learner/guide.blade.php resources/views/instructor/guide.blade.php routes/web.php routes/instructor.php resources/views/layouts/learner-sidebar.blade.php resources/views/layouts/instructor-app.blade.php tests/Feature/Onboarding/OnboardingGuidePageTest.php
git commit -m "feat(onboarding): add role guide pages"
~~~

---

### Task 8: Add the accessible dashboard introduction dialog

**Files:**
- Create: resources/views/components/onboarding/guide-modal.blade.php
- Create: resources/js/onboarding-guide-modal.js
- Modify: resources/js/app.js
- Modify: app/Http/Controllers/Learner/DashboardController.php
- Modify: app/Http/Controllers/Instructor/DashboardController.php
- Modify: resources/views/learner/dashboard.blade.php
- Modify: resources/views/instructor/dashboard.blade.php
- Create: tests/Feature/Onboarding/OnboardingGuideModalTest.php
- Create: tests/Unit/JavaScript/onboarding-guide-modal.test.js

**Interfaces:**
- Consumes: audience presentationFor and state endpoints.
- Produces: createOnboardingGuideModal(config, request, notifyError), exposed as window.onboardingGuideModal for Blade.
- Produces: optional onboardingGuidePresentation view variable on only the two landing dashboards.

- [ ] **Step 1: Write failing feature and JavaScript tests**

Feature tests assert modal markup appears only for an incomplete current version on learner.dashboard/instructor.dashboard and not on other pages, dismissed/completed/current-required states behave correctly, no guide produces no modal markup, and title/description/video URLs are dynamic.

JavaScript tests assert:

- Start Watching calls play but no completion request;
- Skip and Close close the native dialog before awaiting dismissal;
- Escape uses the same dismiss action;
- dismissal failure calls the non-blocking error notifier with a message that the guide may appear again;
- Complete closes only after a successful response;
- completion failure keeps the dialog open and exposes an inline retry message;
- saving blocks duplicate completion clicks.

- [ ] **Step 2: Run modal tests and verify RED**

~~~powershell
php artisan test --compact tests/Feature/Onboarding/OnboardingGuideModalTest.php
node --test tests/Unit/JavaScript/onboarding-guide-modal.test.js
~~~

Expected: FAIL because the component/module/dashboard data are missing.

- [ ] **Step 3: Implement the small JavaScript state object**

Use injected request/notifier functions for deterministic tests:

~~~javascript
export function createOnboardingGuideModal(
    config,
    request = window.fetch.bind(window),
    notifyError = message => window.toast?.error(message),
) {
    return {
        saving: false,
        error: '',

        startWatching() {
            this.$refs.video?.play();
        },

        dismiss() {
            this.$refs.dialog.close();
            void this.post(config.dismissUrl).catch(() => {
                notifyError('Your choice could not be saved. The guide may appear again.');
            });
        },

        async complete() {
            if (this.saving) return;
            this.saving = true;
            this.error = '';
            try {
                await this.post(config.completeUrl);
                this.$refs.dialog.close();
            } catch {
                this.error = 'Completion could not be saved. Try again or skip for now.';
            } finally {
                this.saving = false;
            }
        },

        async post(url) {
            const response = await request(url, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': config.csrfToken,
                },
            });
            if (!response.ok) throw new Error('Request failed');
            return response.json();
        },
    };
}

if (typeof window !== 'undefined') {
    window.onboardingGuideModal = createOnboardingGuideModal;
}
~~~

Import the module from resources/js/app.js.

- [ ] **Step 4: Build the native dialog component**

Use a native dialog so showModal supplies background inertness and browser focus management:

~~~blade
<dialog
    x-data="onboardingGuideModal(@js([
        'dismissUrl' => route('onboarding-guides.dismiss', $version),
        'completeUrl' => route('onboarding-guides.complete', $version),
        'csrfToken' => csrf_token(),
    ]))"
    x-ref="dialog"
    x-init="$nextTick(() => $refs.dialog.showModal())"
    @cancel.prevent="dismiss()"
    aria-labelledby="onboarding-guide-title"
    aria-describedby="onboarding-guide-description"
    class="m-auto max-h-[95vh] w-[min(94vw,64rem)] overflow-y-auto rounded-3xl border-0 p-0 shadow-2xl backdrop:bg-slate-950/70"
>
~~~

Include a generic Welcome to Conscious Connections message, dynamic title/description, video controls, Start Watching, Skip, close icon, and explicit completion. Both Skip and close icon call dismiss. Do not add backdrop dismissal. Bind x-ref=video and x-ref=dialog. Use min-h-11 controls and visible focus rings.

- [ ] **Step 5: Inject presentation into only dashboard landing controllers**

Add OnboardingGuideAudienceService to each dashboard controller constructor. Before returning the view:

~~~php
$onboardingGuidePresentation = $this->onboardingGuides->presentationFor(
    $user,
    OnboardingGuide::ROLE_LEARNER
);
~~~

Use ROLE_INSTRUCTOR in the Instructor controller. Add the variable to compact/view data and render the component only when non-null at the end of each dashboard view.

- [ ] **Step 6: Run modal, page, JavaScript, and view tests**

~~~powershell
php artisan test --compact tests/Feature/Onboarding/OnboardingGuideModalTest.php tests/Feature/Onboarding/OnboardingGuidePageTest.php tests/Feature/Onboarding/OnboardingGuideStateTest.php
node --test tests/Unit/JavaScript/onboarding-guide-modal.test.js tests/Unit/JavaScript/video-upload-form.test.js
php artisan view:cache
~~~

Expected: PASS; no playback event creates completion and every dismissal path uses the same endpoint.

- [ ] **Step 7: Commit the dashboard modal**

~~~powershell
git add resources/views/components/onboarding/guide-modal.blade.php resources/js/onboarding-guide-modal.js resources/js/app.js app/Http/Controllers/Learner/DashboardController.php app/Http/Controllers/Instructor/DashboardController.php resources/views/learner/dashboard.blade.php resources/views/instructor/dashboard.blade.php tests/Feature/Onboarding/OnboardingGuideModalTest.php tests/Unit/JavaScript/onboarding-guide-modal.test.js
git commit -m "feat(onboarding): show dashboard guide modal"
~~~

---

### Task 9: Run regression, browser, and deployment-readiness gates

**Files:**
- Modify only implementation or test files required by a reproduced failure.
- Do not stage public/build changes that predate the isolated worktree or belong to another checkout.

**Interfaces:**
- Consumes: all prior task outputs.
- Produces: verified source/test/build evidence and a separate list of unverified Hostinger/production gates.

- [ ] **Step 1: Run every focused onboarding test**

~~~powershell
php artisan test --compact tests/Feature/Onboarding tests/Feature/Admin/AdminOnboardingGuideManagementTest.php tests/Feature/Rbac/RbacPermissionCatalogSeederTest.php tests/Feature/Rbac/RbacBladePermissionVisibilityTest.php tests/Feature/Instructor/VideoUploadTest.php
~~~

Expected: PASS with the exact test/assertion count recorded.

- [ ] **Step 2: Run JavaScript, view, formatting, and build checks**

~~~powershell
node --test tests/Unit/JavaScript/video-upload-form.test.js tests/Unit/JavaScript/onboarding-guide-modal.test.js tests/Unit/JavaScript/video-player.test.js
php artisan view:cache
vendor\bin\pint --test
pnpm.cmd build
git diff --check
~~~

Expected: all commands exit 0. Run pnpm build only in the isolated implementation worktree so generated assets cannot overwrite the user's unrelated public/build changes.

- [ ] **Step 3: Inspect routes and migration safety**

~~~powershell
php artisan route:list --name=onboarding-guides
php artisan route:list --name=learner.guide
php artisan route:list --name=instructor.guide
rg -n 'migrate:fresh|db:wipe|TRUNCATE|DROP TABLE|DELETE FROM' database/migrations/2026_11_09_000001_create_onboarding_guide_tables.php database/migrations/2026_11_09_000002_add_manage_onboarding_guides_permission.php
~~~

Expected: all named routes are present and the destructive-SQL scan returns no matches.

- [ ] **Step 4: Perform authenticated browser verification**

Use the browser automation skill against a local server and record separate results for:

1. Admin create draft with a small MP4, selected-file preview, byte/percentage progress, draft playback, informational/required publication, history, unpublish, republish, and archive.
2. Existing eligible Learner first dashboard visit, Start Watching, Skip, no repeat, Guide page completion, replay, and no duplicate state.
3. Eligible Instructor receives only Instructor content; a transitioned Instructor using Switch to Learner sees only Learner content in that context.
4. Escape and close icon persist dismissal; completion failure leaves the dialog actionable; a dismissal failure closes it and shows the warning.
5. Keyboard focus, native dialog background inertness, visible focus rings, and all actions at 1366 px, 390 px, and 320 px without horizontal overflow.
6. Video seek sends/accepts byte ranges on desktop and mobile browser emulation.
7. Wrong-role, unpublished, archived, and historical direct URLs fail.

Do not use production accounts or production data for these checks.

- [ ] **Step 5: Record deployment-only gates**

Do not claim these from local tests:

- Hostinger effective upload_max_filesize=100M and post_max_size=110M.
- Private 100 MiB upload duration and web-server timeout behavior.
- Production byte-range seeking and PHP worker impact.
- Production disk capacity and backup coverage for retained historical videos.

- [ ] **Step 6: Review scope and commit verified fixes**

Confirm the final diff contains no thumbnail, tutorial sequencing, public URL, hard delete, named analytics roster, dependency, database reset, or unrelated refactor.

If verification required owned fixes, inspect git status and stage each changed path individually from the Planned File Map. Never use git add -A or git add . in the dirty checkout. Then commit:

~~~powershell
git commit -m "fix(onboarding): close verification gaps"
~~~

If no fixes were needed, do not create an empty commit.

- [ ] **Step 7: Produce the implementation report**

Report:

- commits created per task;
- exact focused PHP and JavaScript results;
- view cache, Pint, build, and diff-check results;
- browser viewport and interaction evidence;
- migration/data-preservation audit;
- the deployment-only gates that remain open.

Never label the feature production-verified until the Hostinger and production playback gates have actually run.
