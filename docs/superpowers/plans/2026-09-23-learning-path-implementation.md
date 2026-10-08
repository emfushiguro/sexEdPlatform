# Learning Path Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add administrator-curated, ordered Learning Paths and a responsive learner journey view while keeping existing modules, access rules, and learner progress authoritative.

**Architecture:** Add three normalized catalog tables, one atomic authoring service, and one read-only presentation service. Admin Blade/Alpine screens manage paths and reuse the Sequencing pointer-reorder primitives; learner Blade screens render prepared semantic path data and link back into existing module, enrollment, payment, and lesson flows.

**Tech Stack:** PHP 8.2, Laravel 12, MySQL/SQLite-compatible migrations, Blade, Alpine.js 3, Tailwind CSS 3, native Pointer Events, native SVG, Node test runner, PHPUnit 11, Vite 7.

**Design reference:** `docs/superpowers/specs/2026-09-23-learning-path-design.md`

## Guardrails

- Use incremental migrations only. Never reset, wipe, truncate, recreate, or destructively reseed any database.
- Run tests against the configured isolated test database. Never treat development data as disposable.
- Existing `Module`, `ModuleEnrollment`, purchase, lesson/topic/activity/checkpoint/quiz progress, certificate, category, governance, and authorization systems remain authoritative.
- Do not add path enrollment, path progress storage, prerequisites, arbitrary locks, optional modules, branching, AI recommendations, gamification, React, Vue, graph libraries, or drag dependencies.
- Reuse `resources/js/pointer-reorder.js`; do not couple admin authoring to the learner-specific `createSequencingActivity()` controller.
- Removing or reordering membership must not mutate modules or learner history.
- Learner links must enter existing module details, enrollment, purchase, and lesson flows; the path never grants access.
- All state must be readable without color. Preserve keyboard operation, 44-pixel targets, semantic lists, visible focus, and reduced-motion behavior.
- Preserve the unrelated Instructor Guidelines work currently present in the worktree. Stage only files named by the active task.

## Planned File Map

**Create**

- `database/migrations/2026_09_23_000001_create_learning_path_tables.php`
- `app/Models/LearningPath.php`
- `app/Models/LearningPathModule.php`
- `app/Models/LearningPathLearnerCategory.php`
- `database/factories/LearningPathFactory.php`
- `app/Policies/LearningPathPolicy.php`
- `app/Http/Requests/Admin/SaveLearningPathRequest.php`
- `app/Services/LearningPathAuthoringService.php`
- `app/Services/LearningPathPresentationService.php`
- `app/Http/Controllers/Admin/LearningPathController.php`
- `app/Http/Controllers/Learner/LearningPathController.php`
- `resources/js/learning-path-builder.js`
- `resources/views/admin/learning-paths/index.blade.php`
- `resources/views/admin/learning-paths/form.blade.php`
- `resources/views/admin/learning-paths/create.blade.php`
- `resources/views/admin/learning-paths/edit.blade.php`
- `resources/views/admin/learning-paths/preview.blade.php`
- `resources/views/learner/learning-paths/index.blade.php`
- `resources/views/learner/learning-paths/show.blade.php`
- `resources/views/components/learning-path/module-node.blade.php`
- `tests/Feature/LearningPath/LearningPathSchemaTest.php`
- `tests/Feature/LearningPath/LearningPathAuthoringServiceTest.php`
- `tests/Feature/Admin/AdminLearningPathManagementTest.php`
- `tests/Feature/Admin/AdminLearningPathBuilderMarkupTest.php`
- `tests/Feature/Learner/LearningPathPresentationServiceTest.php`
- `tests/Feature/Learner/LearnerLearningPathIndexTest.php`
- `tests/Feature/Learner/LearnerLearningPathJourneyTest.php`
- `tests/Feature/Learner/LearningPathChangingContentTest.php`
- `tests/JavaScript/learning-path-builder.test.mjs`
- `docs/superpowers/verification/2026-09-23-learning-path-verification.md`

**Modify**

- `app/Models/Module.php`
- `app/Providers/AppServiceProvider.php`
- `resources/js/app.js`
- `resources/css/components.css`
- `resources/views/layouts/admin.blade.php`
- `resources/views/layouts/learner-sidebar.blade.php`
- `routes/admin.php`
- `routes/web.php`

## Task 1: Add the normalized catalog schema

**Files:**

- Create: `database/migrations/2026_09_23_000001_create_learning_path_tables.php`
- Create: `app/Models/LearningPath.php`
- Create: `app/Models/LearningPathModule.php`
- Create: `app/Models/LearningPathLearnerCategory.php`
- Create: `database/factories/LearningPathFactory.php`
- Modify: `app/Models/Module.php`
- Test: `tests/Feature/LearningPath/LearningPathSchemaTest.php`

- [ ] **Step 1: Write failing schema and relationship tests**

Test the three tables, column types, foreign keys, both pivot unique constraints, category uniqueness, ordered relationships, creator behavior, and the same module appearing in two paths.

```php
public function test_learning_path_schema_enforces_membership_invariants(): void
{
    $path = LearningPath::factory()->create();
    $module = Module::factory()->create();

    LearningPathModule::create([
        'learning_path_id' => $path->id,
        'module_id' => $module->id,
        'position' => 1,
    ]);

    $this->expectException(QueryException::class);

    LearningPathModule::create([
        'learning_path_id' => $path->id,
        'module_id' => $module->id,
        'position' => 2,
    ]);
}

public function test_module_can_belong_to_more_than_one_path(): void
{
    $module = Module::factory()->create();
    $paths = LearningPath::factory()->count(2)->create();

    foreach ($paths as $path) {
        LearningPathModule::create([
            'learning_path_id' => $path->id,
            'module_id' => $module->id,
            'position' => 1,
        ]);
    }

    $this->assertCount(2, $module->fresh()->learningPathMemberships);
}
```

- [ ] **Step 2: Confirm the tests fail for missing tables and classes**

Run:

```powershell
php artisan test tests/Feature/LearningPath/LearningPathSchemaTest.php
```

Expected: failure because Learning Path classes/tables do not exist.

- [ ] **Step 3: Create one incremental migration**

Use a single migration with this forward order and reverse drop order:

```php
Schema::create('learning_paths', function (Blueprint $table): void {
    $table->id();
    $table->string('title');
    $table->text('description');
    $table->string('thumbnail')->nullable();
    $table->string('status', 20)->default('draft')->index();
    $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
    $table->timestamps();
});

Schema::create('learning_path_modules', function (Blueprint $table): void {
    $table->id();
    $table->foreignId('learning_path_id')->constrained()->cascadeOnDelete();
    $table->foreignId('module_id')->constrained()->cascadeOnDelete();
    $table->unsignedInteger('position');
    $table->timestamps();
    $table->unique(['learning_path_id', 'module_id']);
    $table->unique(['learning_path_id', 'position']);
    $table->index(['module_id', 'learning_path_id']);
});

Schema::create('learning_path_learner_categories', function (Blueprint $table): void {
    $table->id();
    $table->foreignId('learning_path_id')->constrained()->cascadeOnDelete();
    $table->string('category', 16);
    $table->timestamps();
    $table->unique(['learning_path_id', 'category']);
    $table->index(['category', 'learning_path_id']);
});
```

Do not run this migration against development yet. The feature tests will apply it in isolation.

- [ ] **Step 4: Add focused models and ordered relationships**

`LearningPath` supplies constants and scopes:

```php
final public const STATUS_DRAFT = 'draft';
final public const STATUS_PUBLISHED = 'published';
final public const STATUS_ARCHIVED = 'archived';
final public const STATUSES = [
    self::STATUS_DRAFT,
    self::STATUS_PUBLISHED,
    self::STATUS_ARCHIVED,
];

public function pathModules(): HasMany
{
    return $this->hasMany(LearningPathModule::class)->orderBy('position');
}

public function modules(): BelongsToMany
{
    return $this->belongsToMany(Module::class, 'learning_path_modules')
        ->withPivot(['id', 'position'])
        ->withTimestamps()
        ->orderByPivot('position');
}

public function scopePublished(Builder $query): Builder
{
    return $query->where('status', self::STATUS_PUBLISHED);
}

public function scopeForLearnerCategory(Builder $query, string $category): Builder
{
    return $query->whereHas(
        'learnerCategories',
        fn (Builder $categories) => $categories->where('category', $category)
    );
}
```

Keep models fillable, typed, and relationship-only. Add to `Module`:

```php
public function learningPathMemberships(): HasMany
{
    return $this->hasMany(LearningPathModule::class);
}
```

`LearningPathModule::module()` uses `belongsTo(Module::class)->withTrashed()`.
That preserves enough context for admin warnings and completed historical
nodes. Learner presentation still filters deleted content and never creates a
working action URL for a soft-deleted module.

Add `LearningPathFactory` with draft defaults plus `published()` and
`forCategories(array $categories)` states. The category state uses
`afterCreating()` to create normalized category rows; it never seeds production
data.

- [ ] **Step 5: Run tests and commit**

```powershell
php artisan test tests/Feature/LearningPath/LearningPathSchemaTest.php
git add -- database/migrations/2026_09_23_000001_create_learning_path_tables.php app/Models/LearningPath.php app/Models/LearningPathModule.php app/Models/LearningPathLearnerCategory.php database/factories/LearningPathFactory.php app/Models/Module.php tests/Feature/LearningPath/LearningPathSchemaTest.php
git commit -m "feat: add learning path schema"
```

## Task 2: Save paths atomically without touching learner data

**Files:**

- Create: `app/Services/LearningPathAuthoringService.php`
- Test: `tests/Feature/LearningPath/LearningPathAuthoringServiceTest.php`

- [ ] **Step 1: Write failing service tests**

Cover create, append, remove, reorder, category synchronization, rollback, retained membership IDs, contiguous positions, and duplicate module rejection. Capture module, enrollment, purchase, `UserProgress`, lesson/topic/activity/checkpoint progress, quiz attempt, and certificate records before remove/reorder; assert they remain unchanged afterward.

```php
public function test_reordering_retains_membership_rows_and_changes_only_positions(): void
{
    [$path, $first, $second, $third] = $this->pathWithThreeModules();
    $membershipIds = $path->pathModules()->pluck('id', 'module_id');

    app(LearningPathAuthoringService::class)->save($path, [
        'title' => $path->title,
        'description' => $path->description,
        'status' => LearningPath::STATUS_DRAFT,
        'thumbnail' => $path->thumbnail,
        'categories' => ['teens'],
        'module_ids' => [$third->id, $first->id, $second->id],
    ], $path->creator);

    $actual = $path->fresh()->pathModules()->get();

    $this->assertSame([$third->id, $first->id, $second->id], $actual->pluck('module_id')->all());
    $this->assertSame([1, 2, 3], $actual->pluck('position')->all());
    $this->assertSame($membershipIds->sort()->values()->all(), $actual->pluck('id')->sort()->values()->all());
}
```

- [ ] **Step 2: Confirm the service tests fail**

```powershell
php artisan test tests/Feature/LearningPath/LearningPathAuthoringServiceTest.php
```

Expected: missing service.

- [ ] **Step 3: Implement the transaction and collision-safe ordering**

Public contract:

```php
public function save(?LearningPath $path, array $attributes, User $actor): LearningPath
```

Core algorithm:

```php
return DB::transaction(function () use ($path, $attributes, $actor): LearningPath {
    $moduleIds = array_values(array_map('intval', $attributes['module_ids'] ?? []));

    if (count($moduleIds) !== count(array_unique($moduleIds))) {
        throw ValidationException::withMessages([
            'module_ids' => 'Each module may appear only once in a learning path.',
        ]);
    }

    $path ??= new LearningPath(['created_by' => $actor->id]);
    $path->fill(Arr::only($attributes, ['title', 'description', 'thumbnail', 'status']))->save();

    $path->learnerCategories()->delete();
    $path->learnerCategories()->createMany(
        collect($attributes['categories'])->map(fn (string $category) => ['category' => $category])->all()
    );

    if ($moduleIds === []) {
        $path->pathModules()->delete();
    } else {
        $path->pathModules()->whereNotIn('module_id', $moduleIds)->delete();
    }

    $maxPosition = (int) $path->pathModules()->max('position');
    $offset = $maxPosition + count($moduleIds) + 1;
    $path->pathModules()->increment('position', $offset);

    foreach ($moduleIds as $index => $moduleId) {
        $path->pathModules()->updateOrCreate(
            ['module_id' => $moduleId],
            ['position' => $index + 1]
        );
    }

    return $path->fresh(['creator', 'learnerCategories', 'pathModules.module']);
});
```

The request layer validates module eligibility; the service owns atomic persistence and duplicate defense. Do not call `sync()` for memberships because retained pivot IDs must survive reorder.

- [ ] **Step 4: Run tests and commit**

```powershell
php artisan test tests/Feature/LearningPath/LearningPathAuthoringServiceTest.php
git add -- app/Services/LearningPathAuthoringService.php tests/Feature/LearningPath/LearningPathAuthoringServiceTest.php
git commit -m "feat: save learning path membership"
```

## Task 3: Add authorized admin CRUD and server validation

**Files:**

- Create: `app/Policies/LearningPathPolicy.php`
- Create: `app/Http/Requests/Admin/SaveLearningPathRequest.php`
- Create: `app/Http/Controllers/Admin/LearningPathController.php`
- Create: `resources/views/admin/learning-paths/index.blade.php`
- Create: `resources/views/admin/learning-paths/form.blade.php`
- Create: `resources/views/admin/learning-paths/create.blade.php`
- Create: `resources/views/admin/learning-paths/edit.blade.php`
- Modify: `app/Providers/AppServiceProvider.php`
- Modify: `routes/admin.php`
- Modify: `resources/views/layouts/admin.blade.php`
- Test: `tests/Feature/Admin/AdminLearningPathManagementTest.php`

- [ ] **Step 1: Write failing route, policy, validation, persistence, and archive tests**

Cover:

- policy mappings for `view modules`, `create modules`, `edit modules`, and
  `publish modules` using non-admin policy subjects;
- the existing admin-wide `Gate::before` override continuing to authorize an
  administrator, even when a granular permission is absent;
- learners and instructors receiving 403/route denial for authoring;
- drafts accepting zero modules, published paths requiring at least one;
- distinct IDs, missing modules, soft-deleted modules, unpublished modules, and category mismatches;
- platform- and instructor-owned learner-visible modules being selectable;
- old input preserving selected IDs and order after validation failure;
- edit persistence, archive, restore-to-draft, and no hard-delete route;
- thumbnail validation/storage and replacement using `Storage::fake('public')`.

```php
public function test_publishing_requires_eligible_modules_for_every_path_category(): void
{
    $admin = $this->adminWithPermissions(['view modules', 'create modules', 'publish modules']);
    $teensOnly = Module::factory()->learnerVisible()->forCategories(['teens'])->create();

    $response = $this->actingAs($admin)->post(route('admin.learning-paths.store'), [
        'title' => 'Healthy Connections',
        'description' => 'A guided path.',
        'status' => LearningPath::STATUS_PUBLISHED,
        'categories' => ['teens', 'adults'],
        'module_ids' => [$teensOnly->id],
    ]);

    $response->assertInvalid(['module_ids']);
    $this->assertDatabaseMissing('learning_paths', ['title' => 'Healthy Connections']);
}
```

- [ ] **Step 2: Confirm failures**

```powershell
php artisan test tests/Feature/Admin/AdminLearningPathManagementTest.php
```

- [ ] **Step 3: Implement the policy and register it**

```php
public function viewAny(User $user): bool { return $user->can('view modules'); }
public function view(User $user, LearningPath $path): bool { return $user->can('view modules'); }
public function create(User $user): bool { return $user->can('create modules'); }
public function update(User $user, LearningPath $path): bool { return $user->can('edit modules'); }
public function archive(User $user, LearningPath $path): bool { return $user->can('edit modules'); }
public function publish(User $user, ?LearningPath $path = null): bool { return $user->can('publish modules'); }
```

Map `LearningPath::class` to `LearningPathPolicy::class` in `AppServiceProvider` using the project's existing policy registration convention. Do not add new permissions.

The project's `Gate::before` grants administrators all abilities. Preserve and
test that behavior; do not write an impossible route test expecting a user with
the `admin` role to receive 403 after revoking one granular permission.

- [ ] **Step 4: Implement one request for create/update**

Base rules:

```php
return [
    'title' => ['required', 'string', 'max:255'],
    'description' => ['required', 'string'],
    'thumbnail' => ['nullable', 'image', 'max:2048'],
    'status' => ['required', Rule::in(LearningPath::STATUSES)],
    'categories' => ['required', 'array', 'min:1'],
    'categories.*' => ['string', 'distinct', Rule::in(['kids', 'teens', 'adults'])],
    'module_ids' => ['present', 'array'],
    'module_ids.*' => ['integer', 'distinct'],
];
```

In `after()`:

1. require one module when status is `published`;
2. fetch all submitted modules once with `learnerVisible()` and `learnerCategories`;
3. reject when returned IDs differ from submitted IDs;
4. reject when any selected module lacks any selected path category;
5. require `publish modules` when requested status is published.

`authorize()` checks `create` for store and `update` for an existing bound path,
then checks `publish` when the requested status is published. Admins still pass
through the existing global Gate override.

Use `array_diff($categories, $module->learnerCategoryKeys())` for category compatibility. Positions always come from array order.

- [ ] **Step 5: Implement controller routes and basic Blade forms**

Routes stay in the existing admin middleware group:

```php
Route::patch('learning-paths/{learningPath}/archive', [LearningPathController::class, 'archive'])
    ->name('learning-paths.archive');
Route::patch('learning-paths/{learningPath}/restore', [LearningPathController::class, 'restore'])
    ->name('learning-paths.restore');
Route::resource('learning-paths', LearningPathController::class)
    ->except(['show', 'destroy']);
```

Controller behavior:

- `index`: authorize `viewAny`, eager-load categories/creator and `withCount('pathModules')`, paginate 12;
- `create`/`edit`: load compact learner-visible candidates with categories and creator label;
- `store`/`update`: store optional thumbnail under `learning-paths` on public disk, call authoring service, flash success;
- `archive`: set only status to archived;
- `restore`: set only status to draft.

Render the initial selected module list server-side so the form works and reconstructs order from `old('module_ids', ...)`. Add Learning Paths beneath Learning Contents in `resources/views/layouts/admin.blade.php`, permission-gated consistently with neighboring items.

Associate every field with a visible label. Validation errors use
`aria-invalid`, field-specific `aria-describedby`, and an alert summary that
receives focus after failed submission.

- [ ] **Step 6: Run tests and commit**

```powershell
php artisan test tests/Feature/Admin/AdminLearningPathManagementTest.php
git add -- app/Policies/LearningPathPolicy.php app/Http/Requests/Admin/SaveLearningPathRequest.php app/Http/Controllers/Admin/LearningPathController.php app/Providers/AppServiceProvider.php routes/admin.php resources/views/admin/learning-paths resources/views/layouts/admin.blade.php tests/Feature/Admin/AdminLearningPathManagementTest.php
git commit -m "feat: manage learning paths"
```

## Task 4: Reuse Sequencing pointer and keyboard ordering

**Files:**

- Create: `resources/js/learning-path-builder.js`
- Modify: `resources/js/app.js`
- Modify: `resources/views/admin/learning-paths/form.blade.php`
- Modify: `resources/css/components.css`
- Create: `tests/JavaScript/learning-path-builder.test.mjs`
- Create: `tests/Feature/Admin/AdminLearningPathBuilderMarkupTest.php`

- [ ] **Step 1: Write failing JavaScript unit tests**

Import the builder directly. Test add, remove, search, category eligibility,
order preservation, duplicate prevention, Move Up/Down buttons, pointer session
updates, outside-drop cancellation, keyboard pickup/move/drop/cancel, Home/End,
and live announcements.

```js
test('keyboard ordering uses the shared Sequencing destination rules', () => {
    const builder = createLearningPathBuilder({
        modules: fixtures,
        selectedIds: [11, 22, 33],
        categories: ['teens'],
    });

    builder.handleDragKey(1, keyEvent(' '));
    assert.equal(builder.isDragging(), true);
    assert.match(builder.dragAnnouncement, /picked up/i);

    builder.handleDragKey(1, keyEvent('Home'));
    builder.handleDragKey(1, keyEvent('Enter'));

    assert.deepEqual(builder.moduleIds, [22, 11, 33]);
    assert.match(builder.dragAnnouncement, /position 1 of 3/i);
});

test('pointer cancellation leaves the original order intact', () => {
    const builder = createLearningPathBuilder({
        modules: fixtures,
        selectedIds: [11, 22, 33],
        categories: ['teens'],
    });

    builder.beginPointerDrag(0, pointerEvent());
    builder.dropPointerDrag(pointerEvent({ clientX: -10, clientY: -10 }));

    assert.deepEqual(builder.moduleIds, [11, 22, 33]);
});
```

- [ ] **Step 2: Write failing markup contract tests**

Assert the form contains:

- a semantic selected-module `<ol>`;
- one 44-by-44 drag handle per row;
- visible 44-by-44 Move Up and Move Down buttons with module-specific accessible
  names and disabled boundary states;
- `aria-describedby`, `aria-pressed`, and `aria-posinset` bindings;
- a hidden instruction block and `aria-live="polite"` region;
- `module_ids[]` inputs emitted in visual order;
- pointer, touch, keyboard, window move/up/cancel, and edge-scroll bindings;
- mismatch copy that remains visible after category changes.

```php
$response->assertSee('data-learning-path-index', false)
    ->assertSee('module_ids[]', false)
    ->assertSee('aria-live="polite"', false)
    ->assertSee('@pointerdown', false)
    ->assertSee('@keydown', false);
```

- [ ] **Step 3: Confirm both test files fail**

```powershell
node --test tests/JavaScript/learning-path-builder.test.mjs
php artisan test tests/Feature/Admin/AdminLearningPathBuilderMarkupTest.php
```

- [ ] **Step 4: Implement the focused Alpine data object**

Import the shared primitives:

```js
import {
    createReorderSession,
    edgeScrollDelta,
    keyboardDestination,
    moveAt,
} from './pointer-reorder.js';
```

Public state and derived methods:

```js
export function createLearningPathBuilder({ modules, selectedIds, categories }) {
    return {
        modules,
        moduleIds: selectedIds.map(Number),
        categories: [...categories],
        query: '',
        candidateOrder: selectedIds.map(Number),
        reorder: createReorderSession(),
        draggedId: null,
        dragPoint: null,
        dragRect: null,
        dragIndex: null,
        dragOverIndex: null,
        dragAnnouncement: '',
        autoScrollFrame: null,
        lastPointerY: null,

        moduleFor(id) {
            return this.modules.find((module) => module.id === Number(id));
        },
        selected(id) {
            return this.moduleIds.includes(Number(id));
        },
        eligible(module) {
            return this.categories.every((category) => module.categories.includes(category));
        },
        get eligibleModules() {
            const needle = this.query.trim().toLocaleLowerCase();
            return this.modules.filter((module) =>
                this.eligible(module)
                && !this.selected(module.id)
                && (!needle || module.title.toLocaleLowerCase().includes(needle))
            );
        },
        get mismatchedModuleIds() {
            return this.moduleIds.filter((id) => !this.eligible(this.moduleFor(id)));
        },
        add(id) {
            if (!this.selected(id) && this.eligible(this.moduleFor(id))) this.moduleIds.push(Number(id));
        },
        remove(id) {
            this.moduleIds = this.moduleIds.filter((moduleId) => moduleId !== Number(id));
        },
        moveUp(index) {
            if (index > 0) this.moduleIds = moveAt(this.moduleIds, index, index - 1);
        },
        moveDown(index) {
            if (index < this.moduleIds.length - 1) {
                this.moduleIds = moveAt(this.moduleIds, index, index + 1);
            }
        },
    };
}
```

Add Sequencing-equivalent methods on that object:

- `beginPointerDrag(index, event)` captures original order, source index, row rectangle, and overlay dimensions;
- `movePointerDrag(event)` finds `[data-learning-path-index]`, updates destination/insertion marker, and uses `edgeScrollDelta()`;
- `dropPointerDrag(event)` commits only for a valid target, then announces the final position;
- `cancelDrag()` leaves persisted order unchanged and clears overlay/session state;
- `handleDragKey(index, event)` uses `keyboardDestination()` for Arrow Up/Down, Home, and End; Space/Enter toggles pickup/drop and Escape cancels.

Do not duplicate the math inside `pointer-reorder.js`. Do not import answer checking, audio, practice, or learner progress code.

- [ ] **Step 5: Register the builder and wire the form**

In `resources/js/app.js`:

```js
import { createLearningPathBuilder } from './learning-path-builder.js';

window.learningPathBuilder = createLearningPathBuilder;
```

The form initializes from JSON-encoded compact candidate data and old input.
Render each selected module once in `<ol>`, include its hidden `module_ids[]`,
and keep the native Save Path submit action. Render Move Up/Down buttons beside
the drag handle so mouse and touch users can reorder without dragging. Add
source-row, insertion-line, overlay, focus, touch-action, and reduced-motion
classes to `components.css` using existing palette tokens.

- [ ] **Step 6: Run tests and commit**

```powershell
node --test tests/JavaScript/pointer-reorder.test.mjs tests/JavaScript/learning-path-builder.test.mjs
php artisan test tests/Feature/Admin/AdminLearningPathBuilderMarkupTest.php tests/Feature/Admin/AdminLearningPathManagementTest.php
git add -- resources/js/learning-path-builder.js resources/js/app.js resources/views/admin/learning-paths/form.blade.php resources/css/components.css tests/JavaScript/learning-path-builder.test.mjs tests/Feature/Admin/AdminLearningPathBuilderMarkupTest.php
git commit -m "feat: add accessible path ordering"
```

## Task 5: Centralize learner progress and recommendation presentation

**Files:**

- Create: `app/Services/LearningPathPresentationService.php`
- Test: `tests/Feature/Learner/LearningPathPresentationServiceTest.php`

- [ ] **Step 1: Write a failing state matrix**

Build compact fixtures and data providers for:

- all not started: first actionable node recommended, rest available;
- first and middle module in progress;
- multiple in-progress modules: first by path order is current;
- canonical completion by `completed_at` and by `completion_percentage >= 100`;
- completed out of order;
- all complete: no recommendation and completed-path action;
- ordinary topic, checkpoint, and interactive activity completion;
- published-topic percentage and lesson fallback when no topics exist;
- approved, pending, and rejected enrollments;
- paid unpurchased module linking to details/payment flow;
- later-deactivated module with and without approved historical access;
- omitted modules producing contiguous learner-facing display ordinals;
- zero actionable modules returning zero percent;
- continue to first incomplete published lesson, then details when only quiz/completion work remains.

```php
public function test_overall_progress_excludes_unavailable_incomplete_nodes(): void
{
    [$learner, $path, $completed, $available, $unavailable] = $this->mixedPath();

    $view = app(LearningPathPresentationService::class)->presentFor($learner, $path);

    $this->assertSame(50, $view['progress_percentage']);
    $this->assertSame(1, $view['completed_modules']);
    $this->assertSame(2, $view['actionable_modules']);
    $this->assertSame('unavailable', $view['nodes'][2]['state']);
    $this->assertFalse($view['nodes'][2]['is_current']);
}
```

- [ ] **Step 2: Confirm the presenter tests fail**

```powershell
php artisan test tests/Feature/Learner/LearningPathPresentationServiceTest.php
```

- [ ] **Step 3: Implement one read-only presenter**

Public methods:

```php
public function summariesFor(User $user, Collection $paths): array;
public function presentFor(User $user, LearningPath $path): array;
public function preview(LearningPath $path): array;
```

Each learner node contains:

```php
[
    'module' => $module,
    'position' => $membership->position,
    'state' => 'completed|unavailable|in_progress|recommended|available',
    'state_label' => 'Completed|Unavailable|In Progress|Recommended Next|Available',
    'reason' => null,
    'progress_percentage' => 0,
    'completed_lessons' => 0,
    'total_lessons' => 0,
    'is_current' => false,
    'action_url' => null,
    'action_label' => null,
]
```

Data loading rules:

1. Load ordered memberships, non-deleted module summary fields, creator/category metadata, published lesson IDs/counts, and minimal published topic IDs/types.
2. Load this user's enrollments, purchases, lesson progress, and completion inputs in set-based queries.
3. Call `LearnerModuleCompletionService::completedTopicIds()` once for the requested module/topic set, never once per node.
4. Never load lesson bodies, topic media, quiz questions, or complete histories.
5. Filter hidden nodes, then assign contiguous learner-facing `position` values
   without changing persisted membership positions.
6. Build all arrays before rendering; Blade must issue no queries.

State algorithm:

```text
canonical completed enrollment -> completed, 100%
not currently usable under existing access rules -> unavailable
approved enrollment and calculated progress > 0 -> in_progress
first actionable incomplete node when no in-progress current exists -> recommended
remaining actionable incomplete nodes -> available
```

Canonical completion requires approved enrollment plus `completed_at !== null` or `completion_percentage >= 100`. Topic percentage uses completed published topics divided by all published instructional topics. Use published-lesson completion only when the module has no instructional topics. Do not write calculated values back to enrollment.

Current/continue algorithm:

```php
$current = $nodes->firstWhere('state', 'in_progress')
    ?? $nodes->firstWhere('state', 'recommended');
```

For approved enrollment, point Continue to the first incomplete published lesson. When no incomplete lesson exists, or learner is not approved, use existing Module Details. Paid/unpurchased remains actionable and also uses Module Details. A completed path exposes review copy rather than a false recommendation.

Overall percentage is `completed actionable / actionable`, rounded consistently. Completed historical modules count; unavailable incomplete modules do not enter the denominator.

`summariesFor()` must batch all paths in the current page. It must not call `presentFor()` in a loop. `preview()` returns 0 percent, recommends the first module, marks later modules available, and performs no user-specific query.

- [ ] **Step 4: Run tests and commit**

```powershell
php artisan test tests/Feature/Learner/LearningPathPresentationServiceTest.php
git add -- app/Services/LearningPathPresentationService.php tests/Feature/Learner/LearningPathPresentationServiceTest.php
git commit -m "feat: present learning path progress"
```

## Task 6: Add learner discovery and category-safe routes

**Files:**

- Create: `app/Http/Controllers/Learner/LearningPathController.php`
- Create: `resources/views/learner/learning-paths/index.blade.php`
- Modify: `routes/web.php`
- Modify: `resources/views/layouts/learner-sidebar.blade.php`
- Test: `tests/Feature/Learner/LearnerLearningPathIndexTest.php`

- [ ] **Step 1: Write failing index/access tests**

Cover no paths, one path, multiple paths, pagination, thumbnail fallback, completion summary, learner categories, draft/archived exclusion, wrong-category direct 404, unauthenticated redirect, incomplete-profile middleware, and no sidebar regression for My Modules.

```php
public function test_learner_sees_only_published_paths_for_their_existing_category(): void
{
    $learner = $this->learnerWithAgeBracket('teens');
    $visible = LearningPath::factory()->published()->forCategories(['teens'])->create();
    $adult = LearningPath::factory()->published()->forCategories(['adults'])->create();
    $draft = LearningPath::factory()->forCategories(['teens'])->create();

    $this->actingAs($learner)
        ->get(route('learner.learning-paths.index'))
        ->assertOk()
        ->assertSee($visible->title)
        ->assertDontSee($adult->title)
        ->assertDontSee($draft->title);
}
```

- [ ] **Step 2: Confirm index tests fail**

```powershell
php artisan test tests/Feature/Learner/LearnerLearningPathIndexTest.php
```

- [ ] **Step 3: Add routes before generic module routes**

Inside the authenticated/profile-complete learner group:

```php
Route::get('/learning-paths', [LearningPathController::class, 'index'])
    ->name('learning-paths.index');
Route::get('/learning-paths/{learningPath}', [LearningPathController::class, 'show'])
    ->whereNumber('learningPath')
    ->name('learning-paths.show');
```

These declarations live inside the existing `prefix('learn')->name('learner.')`
group, so their public URLs are `/learn/learning-paths` and
`/learn/learning-paths/{learningPath}`. Keep them ahead of broader learner
content patterns.

- [ ] **Step 4: Implement category-constrained controller queries**

Resolve the bracket through the existing learner-profile method. Query only:

```php
$paths = LearningPath::query()
    ->published()
    ->forLearnerCategory($category)
    ->with(['learnerCategories', 'pathModules.module'])
    ->latest()
    ->paginate(12);
```

Pass the page collection once to `summariesFor()`. In `show`, repeat the published/category constraint and use `firstOrFail()` so draft, archived, and mismatched direct requests return 404 without metadata.

Use the numeric route key rather than unconstrained implicit binding:

```php
public function show(Request $request, int $learningPath): View
{
    $category = $request->user()->learnerProfile->getAgeBracket();
    $path = LearningPath::query()
        ->published()
        ->forLearnerCategory($category)
        ->findOrFail($learningPath);

    return view('learner.learning-paths.show', [
        'path' => $this->presentation->presentFor($request->user(), $path),
    ]);
}
```

Within the presenter, reuse `Module::isLearnerVisible()` and
`Module::isAppropriateForAge()` with approved-enrollment historical access,
matching the existing learner Module controller. Do not create a path-specific
access flag or entitlement.

- [ ] **Step 5: Render index cards and navigation**

Each card shows image/fallback, title, truncated description, categories, completed/actionable module count, accessible progressbar, and Continue/View. Empty state links to the existing module browser. Add a separate Learning Paths sidebar item while retaining My Modules and dashboard entries.

- [ ] **Step 6: Run tests and commit**

```powershell
php artisan test tests/Feature/Learner/LearnerLearningPathIndexTest.php
git add -- app/Http/Controllers/Learner/LearningPathController.php resources/views/learner/learning-paths/index.blade.php routes/web.php resources/views/layouts/learner-sidebar.blade.php tests/Feature/Learner/LearnerLearningPathIndexTest.php
git commit -m "feat: browse learning paths"
```

## Task 7: Render the responsive semantic learner journey

**Files:**

- Create: `resources/views/learner/learning-paths/show.blade.php`
- Create: `resources/views/components/learning-path/module-node.blade.php`
- Modify: `resources/css/components.css`
- Test: `tests/Feature/Learner/LearnerLearningPathJourneyTest.php`

- [ ] **Step 1: Write failing journey and accessibility tests**

Assert:

- one visible semantic `<ol>` and one `<li>` per visible node;
- completed, in-progress, recommended, available, and unavailable textual labels;
- current state, progress percentage, completed/total lessons, and action copy;
- overall and node progressbars with accessible names/values;
- Continue Learning URL for first incomplete lesson and Module Details fallbacks;
- completed path copy/action;
- SVG connectors are `aria-hidden="true"` and contain no links;
- long titles have wrapping classes and no horizontal-scroll layout;
- touch targets/focus classes and reduced-motion CSS hooks;
- thumbnail alt behavior, 4.5:1 text contrast, 3:1 control/focus contrast, and
  logical heading order;
- no duplicated hidden accessibility list, modal, mascot, Duolingo asset, or path-specific enrollment action.

```php
$response->assertOk()
    ->assertSee('<ol', false)
    ->assertSee('aria-label="Learning path modules"', false)
    ->assertSee('aria-valuenow="67"', false)
    ->assertSee('Recommended Next')
    ->assertSee('aria-hidden="true"', false);
```

- [ ] **Step 2: Confirm journey tests fail**

```powershell
php artisan test tests/Feature/Learner/LearnerLearningPathJourneyTest.php
```

- [ ] **Step 3: Build the shared node component**

Component inputs are the prepared node array plus `preview` defaulting to false. Render:

```blade
<li class="learning-path-node learning-path-node--{{ $node['state'] }}"
    aria-current="{{ $node['is_current'] ? 'step' : 'false' }}">
    <article>
        <p>Module {{ $node['position'] }}</p>
        <h2>{{ $node['module']->title }}</h2>
        <p>{{ $node['state_label'] }}</p>

        @if ($node['reason'])
            <p>{{ $node['reason'] }}</p>
        @endif

        <div role="progressbar"
             aria-label="Progress for {{ $node['module']->title }}"
             aria-valuemin="0"
             aria-valuemax="100"
             aria-valuenow="{{ $node['progress_percentage'] }}">
            <span style="width: {{ $node['progress_percentage'] }}%"></span>
        </div>

        @if (! $preview && $node['action_url'])
            <a href="{{ $node['action_url'] }}">{{ $node['action_label'] }}</a>
        @endif
    </article>
</li>
```

Use the project's Tabler-style inline SVG icons. Include an icon plus visible text for every state.

- [ ] **Step 4: Compose the learner page**

Order: heading/description, overall progress, completed/actionable text, Continue Learning card, then the single semantic path `<ol>`. Connect adjacent cards with small native SVG segments placed between normal HTML nodes. SVG is decorative only.

CSS behavior:

- mobile: single vertical rail with small controlled alternating offsets, bounded width, no horizontal scrolling;
- tablet/desktop: centered alternating route with gentle horizontal offsets;
- current node: restrained ring/pulse emphasis;
- completed emerald, recommended amber/indigo, in-progress purple, available neutral, unavailable muted;
- titles wrap with `overflow-wrap:anywhere`;
- links are at least 44 pixels high and have visible `focus-visible` rings;
- component colors use existing design tokens and meet WCAG AA in light, dark,
  and Windows High Contrast modes;
- `@media (prefers-reduced-motion: reduce)` removes transform, pulse, and connector-entry animation.

- [ ] **Step 5: Run tests and commit**

```powershell
php artisan test tests/Feature/Learner/LearnerLearningPathJourneyTest.php tests/Feature/Learner/LearningPathPresentationServiceTest.php
git add -- resources/views/learner/learning-paths/show.blade.php resources/views/components/learning-path/module-node.blade.php resources/css/components.css tests/Feature/Learner/LearnerLearningPathJourneyTest.php
git commit -m "feat: render learner path journey"
```

## Task 8: Add neutral administrator preview

**Files:**

- Modify: `app/Http/Controllers/Admin/LearningPathController.php`
- Modify: `routes/admin.php`
- Create: `resources/views/admin/learning-paths/preview.blade.php`
- Modify: `resources/views/components/learning-path/module-node.blade.php`
- Modify: `resources/views/admin/learning-paths/index.blade.php`
- Modify: `resources/views/admin/learning-paths/edit.blade.php`
- Test: `tests/Feature/Admin/AdminLearningPathManagementTest.php`

- [ ] **Step 1: Add failing preview tests**

Verify authorized access, permission denial, route links, explicit preview banner, 0 percent progress, first module Recommended Next, later modules Available, absence of learner-specific names/data/actions, and no enrollment/purchase/progress mutations.

```php
public function test_preview_is_neutral_and_never_impersonates_a_learner(): void
{
    $admin = $this->adminWithPermissions(['view modules']);
    $path = $this->publishedPathWithModules(3);

    $before = ModuleEnrollment::count();

    $this->actingAs($admin)
        ->get(route('admin.learning-paths.preview', $path))
        ->assertOk()
        ->assertSee('Administrator Preview')
        ->assertSee('Recommended Next')
        ->assertSee('Available')
        ->assertDontSee('Continue Learning');

    $this->assertSame($before, ModuleEnrollment::count());
}
```

- [ ] **Step 2: Add preview route and controller method**

Place the explicit route before the resource declaration:

```php
Route::get('learning-paths/{learningPath}/preview', [LearningPathController::class, 'preview'])
    ->name('learning-paths.preview');
```

Authorize `view`, eager-load ordered modules/categories, call `LearningPathPresentationService::preview()`, and render without resolving a learner.

- [ ] **Step 3: Render the shared component safely**

Pass `:preview="true"`. The component renders the same node hierarchy and styles, but no module action link. Show a persistent admin-only banner explaining that progress and recommendation are illustrative. Link Preview from the index and edit page.

- [ ] **Step 4: Run tests and commit**

```powershell
php artisan test tests/Feature/Admin/AdminLearningPathManagementTest.php
git add -- app/Http/Controllers/Admin/LearningPathController.php routes/admin.php resources/views/admin/learning-paths/preview.blade.php resources/views/components/learning-path/module-node.blade.php resources/views/admin/learning-paths/index.blade.php resources/views/admin/learning-paths/edit.blade.php tests/Feature/Admin/AdminLearningPathManagementTest.php
git commit -m "feat: preview learning paths"
```

## Task 9: Harden changing-content behavior and query bounds

**Files:**

- Modify: `app/Services/LearningPathPresentationService.php`
- Modify: `resources/views/admin/learning-paths/form.blade.php`
- Create: `tests/Feature/Learner/LearningPathChangingContentTest.php`
- Modify: `tests/Feature/LearningPath/LearningPathAuthoringServiceTest.php`
- Modify: `tests/Feature/Learner/LearningPathPresentationServiceTest.php`

- [ ] **Step 1: Write failing changing-content and history-isolation tests**

Cover after-assignment changes:

- unpublished module omitted for learner without approved access;
- approved completed history remains Completed;
- approved incomplete deactivated module is Unavailable and never current;
- category-incompatible module omitted for learner without historical access;
- omitted/unavailable incomplete modules excluded from denominator;
- soft-deleted membership is warned about in admin edit without learner exposure;
- removing membership preserves module, enrollment, purchase, ordinary/topic/activity/checkpoint progress, quiz attempt, and certificate records;
- archive and restore change only path status;
- force-deleting a module removes its path membership only through the foreign key.

```php
public function test_removing_membership_preserves_every_existing_learning_record(): void
{
    [$path, $module, $records] = $this->pathWithCompleteLearnerHistory();
    $before = collect($records)->mapWithKeys(
        fn (Model $record) => [$record::class.'#'.$record->getKey() => $record->getAttributes()]
    );

    app(LearningPathAuthoringService::class)->save($path, [
        'title' => $path->title,
        'description' => $path->description,
        'thumbnail' => $path->thumbnail,
        'status' => LearningPath::STATUS_DRAFT,
        'categories' => ['teens'],
        'module_ids' => [],
    ], $path->creator);

    $this->assertDatabaseHas('modules', ['id' => $module->id]);
    $this->assertDatabaseMissing('learning_path_modules', [
        'learning_path_id' => $path->id,
        'module_id' => $module->id,
    ]);

    foreach ($records as $record) {
        $this->assertSame($before[$record::class.'#'.$record->getKey()], $record->fresh()->getAttributes());
    }
}
```

- [ ] **Step 2: Add a query-scaling test**

Enable the query log only around presenter execution. Compare the same scenario with 5 and 10 modules after fixtures are created and relationships cleared. Assert the 10-module run uses no more than two additional queries over the 5-module run:

```php
$fiveModuleQueries = $this->queryCountForPathOfSize(5);
$tenModuleQueries = $this->queryCountForPathOfSize(10);

$this->assertLessThanOrEqual($fiveModuleQueries + 2, $tenModuleQueries);
```

Also use `Model::preventLazyLoading()` in the test to prove no Blade/presenter N+1 dependency.

- [ ] **Step 3: Make presenter filtering explicit**

Before assigning state, classify each path membership:

1. Completed approved historical enrollment remains visible and completed.
2. Approved enrollment may use the existing deactivated-module review behavior.
3. Unapproved learner plus a module that no longer passes current visibility/category is omitted.
4. Approved but unusable incomplete module is visible as unavailable, with a reason and no action/current flag.

Collect invalid membership warnings separately for admin forms. Never mutate the path automatically when module governance changes.

- [ ] **Step 4: Run hardening and regression tests**

```powershell
php artisan test tests/Feature/Learner/LearningPathChangingContentTest.php tests/Feature/LearningPath/LearningPathAuthoringServiceTest.php tests/Feature/Learner/LearningPathPresentationServiceTest.php
php artisan test tests/Feature/Learner/LearnerPublishedModuleVisibilityTest.php tests/Feature/Learner/ModuleMultiCategoryEligibilityTest.php tests/Feature/Learner/LearnerDeactivatedModuleBehaviorTest.php tests/Feature/Learner/LearnerPaidModulePurchaseFlowTest.php tests/Feature/Learner/ModuleInteractionCompletionTest.php tests/Feature/CertificateNumberFormatTest.php
```

- [ ] **Step 5: Commit hardening**

```powershell
git add -- app/Services/LearningPathPresentationService.php resources/views/admin/learning-paths/form.blade.php tests/Feature/Learner/LearningPathChangingContentTest.php tests/Feature/LearningPath/LearningPathAuthoringServiceTest.php tests/Feature/Learner/LearningPathPresentationServiceTest.php
git commit -m "test: harden learning path access"
```

## Task 10: Verify the complete feature

**Files:**

- Create: `docs/superpowers/verification/2026-09-23-learning-path-verification.md`
- Modify only if verification exposes a scoped Learning Path defect.

- [ ] **Step 1: Run JavaScript tests**

```powershell
node --test tests/JavaScript/pointer-reorder.test.mjs tests/JavaScript/learning-path-builder.test.mjs
```

Expected: all pointer helper and path builder tests pass.

- [ ] **Step 2: Run all focused PHPUnit tests**

```powershell
php artisan test tests/Feature/LearningPath tests/Feature/Admin/AdminLearningPathManagementTest.php tests/Feature/Admin/AdminLearningPathBuilderMarkupTest.php tests/Feature/Learner/LearningPathPresentationServiceTest.php tests/Feature/Learner/LearnerLearningPathIndexTest.php tests/Feature/Learner/LearnerLearningPathJourneyTest.php tests/Feature/Learner/LearningPathChangingContentTest.php
```

Expected: all Learning Path tests pass with no warnings or risky tests.

- [ ] **Step 3: Run targeted regressions**

```powershell
php artisan test tests/Feature/Admin/AdminSidebarLearningContentNavTest.php tests/Feature/Rbac/RbacContentPolicyEnforcementTest.php tests/Feature/Learner/LearnerPublishedModuleVisibilityTest.php tests/Feature/Learner/ModuleMultiCategoryEligibilityTest.php tests/Feature/Learner/LearnerDeactivatedModuleBehaviorTest.php tests/Feature/Learner/LearnerPaidModulePurchaseFlowTest.php tests/Feature/Learner/LessonPageTest.php tests/Feature/Learner/InteractiveActivityProgressTest.php tests/Feature/Learner/InteractiveCheckpointFlowTest.php tests/Feature/Learner/LearnerFinalQuizCompletionFlowTest.php tests/Feature/CertificatePdfFlowTest.php
```

Expected: existing module access, lesson/topic navigation, activities, checkpoints, quizzes, progress, payment, certificates, navigation, and RBAC remain green.

- [ ] **Step 4: Format, check whitespace, and build production assets**

Run Pint only on PHP files changed by this feature:

```powershell
vendor/bin/pint app/Models/LearningPath.php app/Models/LearningPathModule.php app/Models/LearningPathLearnerCategory.php app/Models/Module.php app/Policies/LearningPathPolicy.php app/Http/Requests/Admin/SaveLearningPathRequest.php app/Http/Controllers/Admin/LearningPathController.php app/Http/Controllers/Learner/LearningPathController.php app/Services/LearningPathAuthoringService.php app/Services/LearningPathPresentationService.php tests/Feature/LearningPath tests/Feature/Admin/AdminLearningPathManagementTest.php tests/Feature/Admin/AdminLearningPathBuilderMarkupTest.php tests/Feature/Learner/LearningPathPresentationServiceTest.php tests/Feature/Learner/LearnerLearningPathIndexTest.php tests/Feature/Learner/LearnerLearningPathJourneyTest.php tests/Feature/Learner/LearningPathChangingContentTest.php
git diff --check
pnpm.cmd build
```

Expected: formatter succeeds, no whitespace errors, and Vite production build exits 0.

- [ ] **Step 5: Apply only the new migration to development after automated proof**

Review pending migrations first:

```powershell
php artisan migrate:status
```

If only expected migrations are pending, run the ordinary incremental command:

```powershell
php artisan migrate
```

Never run `migrate:fresh`, `db:wipe`, destructive seeders, table truncation, or database recreation. If unrelated migrations are pending, stop and report them before changing development data.

- [ ] **Step 6: Perform browser QA at three viewports**

Use an administrator and learner account through normal local application flows. Do not impersonate or alter unrelated accounts.

Verify at 375px, 768px, and 1440px:

1. create draft, add modules, reorder by mouse/touch-equivalent pointer, save, reopen, and confirm order;
2. reorder using Space/Enter, Arrow keys, Home/End, Escape, and confirm live announcements;
3. publish, preview, archive, restore, and verify restore returns to draft;
4. learner index, one-module path, long path, long titles, empty state, all-not-started, in-progress, middle-progress, completed, all-complete, unavailable, and paid module states;
5. Continue Learning destinations and existing Module Details actions;
6. no horizontal scrolling at 200-percent zoom, usable small-height viewport,
   visible and unobscured focus, dark mode, Windows High Contrast mode, and
   reduced motion;
7. touch targets remain at least 44 by 44 pixels and status remains
   understandable without color;
8. NVDA reads list length, node headings/status/progress, drag instructions,
   Move Up/Down controls, and live reorder announcements in logical order.

- [ ] **Step 7: Record evidence**

Write `docs/superpowers/verification/2026-09-23-learning-path-verification.md` with:

- commit/branch tested;
- commands and exit results;
- migration status before/after;
- viewport/browser matrix;
- accessibility interaction results;
- screenshots or local artifact paths;
- known limitations, if any;
- confirmation that unrelated Instructor Guidelines files were untouched.

- [ ] **Step 8: Review the final diff and commit verification**

```powershell
git status --short
git diff --stat
git diff --check
git add -- docs/superpowers/verification/2026-09-23-learning-path-verification.md
git commit -m "test: verify learning paths"
```

Do not stage the pre-existing Instructor Guidelines changes. If verification required a scoped code fix, stage that file explicitly and rerun its focused test before the final commit.

## Done Criteria

- Authorized admins can create, edit, publish, preview, archive, and restore paths.
- Admin module selection enforces existing publication/category rules server-side.
- Pointer, touch, and keyboard ordering matches the existing Sequencing interaction and persists atomically.
- Removing/reordering membership preserves all module and learner data.
- Learners see only published, category-eligible paths and never gain access through a path.
- One presentation service produces module progress, overall progress, current, recommendation, and Continue Learning destinations from existing data.
- The visual journey is a semantic ordered list, responsive, keyboard-accessible, color-independent, and reduced-motion safe.
- Queries remain batched as path length grows.
- Focused tests, regressions, formatting, whitespace check, and production build pass.
- Only the incremental migration is applied; no database reset or destructive reseed occurs.
