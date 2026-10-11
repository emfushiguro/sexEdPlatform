# Help Center, Feedback, and Testimonials QA Remediation Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking. Execute inline in the current checkout; do not create a worktree or dispatch subagents.

**Goal:** Correct all 28 QA defect groups so Help Center, private Platform Feedback, and Testimonials satisfy the approved security, lifecycle, accessibility, responsive, and verification contracts.

**Architecture:** Repair shared invariants before individual screens. Focused services own audience resolution, section/file synchronization, feedback lifecycle, idempotent submission, and testimonial publication; controllers authorize and delegate, while Blade views consume context-safe route data and accessible field components.

**Tech Stack:** PHP 8.2, Laravel, Eloquent, MySQL `cc_db_test`, Blade, Alpine.js, Tailwind CSS, PHPUnit 11, Vite, Puppeteer/Chrome QA harness.

**Spec:** `docs/superpowers/specs/2026-09-08-help-center-feedback-testimonials-qa-remediation-design.md`

## Global Constraints

- Work directly in `C:\Users\tvace\CommunityHubSexEdPlat\sexEdPlatform` on `community-feed-v1`.
- Preserve all unrelated dirty files and user changes.
- Do not create a worktree, switch branches, commit, push, pull, merge, or create a pull request.
- Use `apply_patch` for source/test/document edits.
- Use the existing `.codex/conscious-connections-ui` rules; no `.agents` directory exists.
- Use TDD: demonstrate each intended failure before its implementation and rerun the focused test afterward.
- Use only `cc_db_test` for destructive test-database refreshes. Never refresh the ordinary development database.
- Keep Platform Feedback global; connector URLs are authorized contextual aliases, not connector-owned records.
- Store feedback attachments privately. Never render a direct public feedback-storage URL.
- Testimonial publication must recheck current adulthood, active account, active consent, source review, and allowed display fields.
- Treat the existing Dompdf 512 MiB exhaustion as a separate repository limitation; do not change memory configuration.
- Do not report success without fresh command output.
- End every task with a scoped status/diff review instead of a commit.

## QA Defect Traceability

| Defects | Owning tasks |
| --- | --- |
| D01, D09 | Task 1 |
| D10, D12 | Task 2 |
| D11, D13, D16 | Task 3 |
| D08, D14, D15 | Task 4 |
| D27 | Task 5 |
| D04, D05, D06, D07 | Task 6 |
| D17, D18, D19, D20 | Task 7 |
| D21 | Task 8 |
| D22, D25 | Task 9 |
| D02, D03 | Task 10 |
| D26 | Task 11 |
| D23, D24, D28 | Task 12 |
| All mappings and final evidence | Tasks 13-14 |

---

### Task 1: Remove Seeded Credentials and Correct Foundation Schema

**Files:**
- Modify: `database/migrations/2026_09_07_000001_create_help_center_tables.php`
- Modify: `database/migrations/2026_09_07_000002_create_platform_feedback_table.php`
- Modify: `database/seeders/HelpCenterSeeder.php`
- Modify: `app/Models/PlatformFeedback.php`
- Test: `tests/Feature/Support/SupportSchemaTest.php`
- Test: `tests/Feature/Support/HelpCenterSeederTest.php`
- Promote coverage from: `tests/QA/SupportQaReviewTest.php` QA12, QA35, QA36, QA38

**Interfaces:**
- Produces nullable `help_articles.created_by` and `help_articles.updated_by`.
- Produces `platform_feedback.submission_token CHAR(36) UNIQUE`.
- Produces seed records with null creator/updater and no login account.

- [ ] **Step 1: Add failing schema and seeder security tests**

Add these assertions:

```php
$this->assertContains('submission_token', Schema::getColumnListing('platform_feedback'));

$this->seed(HelpCenterSeeder::class);
$this->assertDatabaseMissing('users', [
    'email' => 'help-center@consciousconnections.local',
]);

$article = HelpArticle::where('slug', 'navigate-conscious-connections')->firstOrFail();
$this->assertNull($article->created_by);
$this->assertNull($article->updated_by);
```

Keep repeat-seed counts at 13 categories, 13 articles, and 39 sections. Create an article as Admin A, edit as Admin B, and assert creator A remains while updater becomes B.

- [ ] **Step 2: Run the focused tests and record the expected failures**

Run:

```powershell
php artisan test tests/Feature/Support/SupportSchemaTest.php tests/Feature/Support/HelpCenterSeederTest.php --filter="submission_token|seeded|creator"
```

Expected: FAIL because the token column is absent, the seeder creates the predictable identity, and update persistence overwrites creator attribution.

- [ ] **Step 3: Correct the unreleased migrations**

Use these schema shapes:

```php
$table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
$table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

$table->uuid('submission_token')->unique();
```

Add `submission_token` to `PlatformFeedback::$fillable`. Do not create a corrective live-data migration because the approved deployment assumption is unreleased.

- [ ] **Step 4: Remove user creation from the seeder**

Delete the `User::firstOrCreate(...password...)` behavior. Seed articles with:

```php
'created_by' => null,
'updated_by' => null,
```

Do not delete any existing non-test user from application code or seed execution.

- [ ] **Step 5: Rerun schema and seeder tests**

Run:

```powershell
php artisan test tests/Feature/Support/SupportSchemaTest.php tests/Feature/Support/HelpCenterSeederTest.php
```

Expected: PASS, including repeatability, null attribution, constraints, and absence of predictable credentials.

- [ ] **Step 6: Review Task 1 without committing**

Run:

```powershell
git diff --check
git status --short
git diff -- database/migrations/2026_09_07_000001_create_help_center_tables.php database/migrations/2026_09_07_000002_create_platform_feedback_table.php database/seeders/HelpCenterSeeder.php app/Models/PlatformFeedback.php tests/Feature/Support/SupportSchemaTest.php tests/Feature/Support/HelpCenterSeederTest.php
```

Expected: only Task 1 plus pre-existing user changes; no Git write action.

---

### Task 2: Centralize Help Audience and Visibility Rules

**Files:**
- Create: `app/Services/Support/HelpAudienceResolver.php`
- Modify: `app/Models/HelpCategory.php`
- Modify: `app/Models/HelpArticle.php`
- Modify: `app/Http/Controllers/HelpCenterController.php`
- Test: `tests/Unit/Support/HelpAudienceResolverTest.php`
- Modify: `tests/Feature/Support/RoleAwareHelpCenterTest.php`
- Modify: `tests/Feature/Support/PublicHelpCenterTest.php`
- Promote coverage from: QA01, QA06, QA26 visibility subset, QA31, QA40

**Interfaces:**
- Produces: `HelpAudienceResolver::resolve(?User $user, ?Connector $connector = null): string`.
- Produces: `HelpCategory::scopeVisibleToAudience(Builder $query, string $audience): Builder`.
- Produces: `HelpArticle::scopePublishedForAudience(Builder $query, string $audience): Builder`, including active category.

- [ ] **Step 1: Write failing resolver and visibility tests**

Use this exact role matrix:

```php
yield 'guest' => [null, null, 'guest'];
yield 'learner' => [$this->learner('learner-adult'), null, 'learner'];
yield 'guardian' => [$this->learner('parent'), null, 'parent'];
yield 'instructor' => [$this->instructor(), null, 'instructor'];
yield 'admin' => [$this->admin(), null, 'admin'];
yield 'connector context' => [$member, $verifiedConnector, 'connector'];
```

Assert categories and articles both honor their audience arrays; an inactive category hides its otherwise published article at index and detail.

- [ ] **Step 2: Run and confirm failures**

Run:

```powershell
php artisan test tests/Unit/Support/HelpAudienceResolverTest.php tests/Feature/Support/RoleAwareHelpCenterTest.php tests/Feature/Support/PublicHelpCenterTest.php
```

Expected: FAIL for guardian, connector context, restricted category, or inactive-category article.

- [ ] **Step 3: Implement the resolver**

The method must resolve in this order:

```php
public function resolve(?User $user, ?Connector $connector = null): string
{
    if ($connector !== null) {
        return 'connector';
    }

    if ($user === null) {
        return 'guest';
    }

    if ($user->role === 'learner' && $user->account_type === 'parent') {
        return 'parent';
    }

    return in_array($user->role, ['learner', 'instructor', 'admin'], true)
        ? $user->role
        : 'guest';
}
```

The controller must authorize connector context before passing it to this resolver; this service does not grant connector access itself.

- [ ] **Step 4: Implement shared scopes and controller use**

Category scope filters `is_active=true` and JSON audience. Article scope filters status, publication timestamp, article audience, and `whereHas('category', ...visible scope...)`. Replace direct `$request->user()?->role` reads in index/show.

- [ ] **Step 5: Rerun Task 2 tests**

Run the Step 2 command.

Expected: PASS for all roles, category metadata, direct URLs, drafts, archives, and inactive categories.

- [ ] **Step 6: Review Task 2 without committing**

Run `git diff --check` and a scoped diff for the Task 2 files. Confirm no Community Hub visibility scope changed.

---

### Task 3: Repair Help Search, Category Navigation, Keywords, and Voting UI

**Files:**
- Modify: `app/Http/Controllers/HelpCenterController.php`
- Modify: `app/Http/Requests/Admin/StoreHelpArticleRequest.php`
- Modify: `app/Http/Requests/Admin/UpdateHelpArticleRequest.php`
- Modify: `resources/views/help/index.blade.php`
- Modify: `resources/views/help/partials/search-results.blade.php`
- Modify: `resources/views/help/show.blade.php`
- Modify: `app/Http/Controllers/HelpArticleHelpfulnessController.php`
- Modify: `tests/Feature/Support/PublicHelpCenterTest.php`
- Modify: `tests/Feature/Support/HelpArticleHelpfulnessTest.php`
- Test: `tests/Feature/Support/HelpSearchAndKeywordsTest.php`
- Promote coverage from: QA05, QA07, QA08, QA09, QA41, QA46

**Interfaces:**
- Consumes `HelpAudienceResolver`.
- Accepts optional query keys `q` and `category`.
- Normalizes `keywords` from newline text into `array<int,string>`.

- [ ] **Step 1: Write failing category/keyword/action tests**

Assert:

```php
$this->get(route('help.index', ['category' => 'getting-started']))
    ->assertOk()
    ->assertSee('Navigate Conscious Connections');

$this->put(route('admin.help.articles.update', $article), [
    'help_category_id' => $article->help_category_id,
    'title' => $article->title,
    'slug' => $article->slug,
    'summary' => $article->summary,
    'keywords_text' => "kryptonium\r\n zirconium \n\nKRYPTONIUM",
    'audiences' => ['all'],
    'status' => 'published',
    'sort_order' => 0,
    'sections' => [['heading' => 'Search', 'body' => 'Searchable body.']],
])->assertSessionHasNoErrors();

$this->assertSame(['kryptonium', 'zirconium'], $article->fresh()->keywords);
$this->get(route('help.index', ['q' => 'zirconium']))->assertSee($article->title);

$this->actingAs($learner)->get(route('help.show', $article->slug))
    ->assertSee(route('help.helpfulness.update', $article), false)
    ->assertSee(route('feedback.create'), false);
```

Retain exact/partial/mixed-case/space/no-result/wildcard/restricted-result assertions.

- [ ] **Step 2: Run and confirm failures**

Run:

```powershell
php artisan test tests/Feature/Support/PublicHelpCenterTest.php tests/Feature/Support/HelpArticleHelpfulnessTest.php tests/Feature/Support/HelpSearchAndKeywordsTest.php
```

Expected: FAIL for category selection, keyword normalization, and missing guide controls.

- [ ] **Step 3: Normalize keywords before validation**

Replace `keywords[]` with `keywords_text` in the form. In `prepareForValidation()` merge:

```php
$keywords = collect(preg_split('/\R/u', (string) $this->input('keywords_text', '')))
    ->map(fn (string $keyword): string => mb_strtolower(trim($keyword)))
    ->filter()
    ->unique()
    ->values()
    ->all();

$this->merge(['keywords' => $keywords]);
```

Keep `keywords.* => string|max:80`.

- [ ] **Step 4: Add explicit category filtering and context-safe routes**

Validate category by slug through query logic. Generate search, category, related-guide, breadcrumb, and feedback URLs from controller-provided route names/parameters, not hard-coded globals.

- [ ] **Step 5: Render vote controls and totals**

The guide view shows two authenticated PUT forms with `is_helpful=1/0`, current state, and totals. Guest users see a sign-in CTA instead of a write form. Use `aria-pressed` for the current vote.

- [ ] **Step 6: Rerun Task 3 tests and review**

Run Step 2, then `git diff --check`. Expected: PASS; one vote row remains after repeated or changed votes.

---

### Task 4: Synchronize Help Sections and Admin Ordering Safely

**Files:**
- Create: `app/Services/Support/HelpArticlePersistenceService.php`
- Create: `app/Http/Requests/Admin/OrderHelpCategoriesRequest.php`
- Create: `app/Http/Requests/Admin/OrderHelpArticlesRequest.php`
- Modify: `app/Http/Controllers/Admin/HelpArticleController.php`
- Modify: `app/Http/Controllers/Admin/HelpCategoryController.php`
- Modify: `app/Http/Requests/Admin/StoreHelpArticleRequest.php`
- Modify: `resources/views/admin/help/articles/form.blade.php`
- Modify: `resources/views/admin/help/articles/index.blade.php`
- Modify: `resources/views/admin/help/categories/form.blade.php`
- Modify: `resources/views/admin/help/categories/index.blade.php`
- Modify: `routes/admin.php`
- Modify: `tests/Feature/Support/AdminHelpCenterTest.php`
- Test: `tests/Unit/Support/HelpArticlePersistenceServiceTest.php`
- Promote coverage from: QA10-QA15 and browser repeater evidence

**Interfaces:**
- Produces: `HelpArticlePersistenceService::create(array $data, User $editor): HelpArticle`.
- Produces: `HelpArticlePersistenceService::update(HelpArticle $article, array $data, User $editor): HelpArticle`.
- Section input shape: `['id'=>?int,'heading'=>?string,'body'=>string,'image'=>?UploadedFile,'image_alt_text'=>?string]`.
- Ordering request shape: `['items'=>[['id'=>int,'sort_order'=>int], ...]]`.

- [ ] **Step 1: Write failing persistence and ordering tests**

Cover retaining an existing screenshot, replacing/deleting the old file after success, removed-section cleanup, forged section ID rejection, rollback cleanup, immutable creator, updated updater, inactive checkbox, filters, and order persistence.

- [ ] **Step 2: Run and confirm failures**

Run:

```powershell
php artisan test tests/Unit/Support/HelpArticlePersistenceServiceTest.php tests/Feature/Support/AdminHelpCenterTest.php
```

Expected: FAIL for current delete/recreate image behavior, creator rewrite, inactive checkbox, ignored filters, and missing ordering routes.

- [ ] **Step 3: Implement identity-aware section synchronization**

Use an ownership map:

```php
$existing = $article->sections()->get()->keyBy('id');
$submittedIds = collect($sections)->pluck('id')->filter()->map(fn ($id) => (int) $id);

abort_if($submittedIds->diff($existing->keys())->isNotEmpty(), 422);
```

Track `$newPaths` for rollback and `$obsoletePaths` for after-commit deletion. Do not accept `existing_image_path` from the browser.

- [ ] **Step 4: Wire controller and validation**

Inject the service into `HelpArticleController`. Add `sections.*.id => nullable|integer`. Set creator only in `create()`; set updater on every create/update. Add hidden `is_active=0` before the checkbox.

- [ ] **Step 5: Implement filters and ordering**

Validate article filters `search`, `status`, `category`, and `audience`. Add admin POST order routes for categories/articles and update rows transactionally after verifying that all IDs exist.

- [ ] **Step 6: Improve the article editor**

Give every dynamic field a unique `id`, visible `label`, associated error, and retained section ID. Add accessible Move up/Move down controls; array order is authoritative.

- [ ] **Step 7: Rerun and review**

Run Step 2 plus:

```powershell
php artisan route:list --name=admin.help
git diff --check
```

Expected: all Help admin tests pass and the route list includes order actions.

---

### Task 5: Replace Generic Seeded Guide Bodies

**Files:**
- Modify: `database/seeders/HelpCenterSeeder.php`
- Modify: `tests/Feature/Support/HelpCenterSeederTest.php`
- Promote coverage from: QA48

**Interfaces:**
- Keeps stable category/article slugs and exact counts.
- Seed data stores explicit section heading/body pairs; no `bodyFor()` generic template.

- [ ] **Step 1: Write failing content-quality tests**

For representative learner, guardian, instructor, connector, safety, accessibility, and troubleshooting guides, assert route-grounded phrases and reject:

```php
$this->assertStringNotContainsString(
    'then follow the on-screen instructions',
    $article->sections->pluck('body')->join(' ')
);
```

- [ ] **Step 2: Run and confirm failure**

Run `php artisan test tests/Feature/Support/HelpCenterSeederTest.php`.

Expected: FAIL on the generic body template.

- [ ] **Step 3: Replace generated bodies with explicit content**

Represent each article as:

```php
[
    'category' => 'getting-started',
    'title' => 'Navigate Conscious Connections',
    'audiences' => ['all'],
    'sections' => [
        ['heading' => 'Sign in', 'body' => 'Use Sign in from the landing-page navigation...'],
        ['heading' => 'Open your dashboard', 'body' => 'After authentication, the platform redirects...'],
        ['heading' => 'Use the main navigation', 'body' => 'Choose the labeled navigation item...'],
    ],
],
```

Write equally concrete content for all 13 guides using verified named-route/navigation terminology. Do not include passwords, medical claims, or promises that the application cannot perform.

- [ ] **Step 4: Rerun and review**

Run the full seeder test twice in one case. Expected: PASS with 13/13/39 and no login identity.

---

### Task 6: Restore Canonical Feedback and Connector Route Contracts

**Files:**
- Modify: `routes/web.php`
- Modify: `routes/connector.php`
- Modify: `app/Http/Controllers/PlatformFeedbackController.php`
- Modify: `app/Http/Controllers/PlatformFeedbackAttachmentController.php`
- Modify: `app/Http/Controllers/WithdrawTestimonialConsentController.php`
- Modify: `app/Services/Support/SupportLayoutResolver.php`
- Modify: `resources/views/feedback/create.blade.php`
- Modify: `resources/views/feedback/index.blade.php`
- Modify: `resources/views/feedback/show.blade.php`
- Test: `tests/Feature/Support/PlatformFeedbackRoutingTest.php`
- Test: `tests/Feature/Support/ConnectorSupportRoutingTest.php`
- Promote coverage from: QA01, QA03, QA28-QA30, QA39

**Interfaces:**
- Produces the canonical route contract from the approved spec.
- Produces separate explicit connector controller methods or argument-safe signatures.
- Produces a route-context array containing index/create/store/show/attachment/withdraw route names and connector parameters.

- [ ] **Step 1: Write failing route and connector tests**

Assert exact methods, URIs, middleware, owner/admin authorization, valid member details, nonmember POST 403/no row, and connector-aware form/search/detail/redirect URLs.

- [ ] **Step 2: Run and confirm failures**

Run:

```powershell
php artisan test tests/Feature/Support/PlatformFeedbackRoutingTest.php tests/Feature/Support/ConnectorSupportRoutingTest.php
```

Expected: FAIL for current URIs, compressed detail Blade parse error, connector argument binding, missing POST scope check, and global actions.

- [ ] **Step 3: Correct canonical routes and readable Blade syntax**

Move history/detail/attachment/withdrawal beneath `/feedback/submissions`. Expand `feedback/show.blade.php` directives onto valid separate Blade blocks:

```blade
@if ($feedback->staff_response)
    <section aria-labelledby="staff-response-heading">
        <h2 id="staff-response-heading">Staff response</h2>
        <p>{{ $feedback->staff_response }}</p>
    </section>
@endif
```

- [ ] **Step 4: Add support-specific connector authorization**

Before scoped actions:

```php
if (! $user->hasRole('admin')) {
    $this->connectorAccess->abortUnlessWorkspace($user, $connector);
}
```

Do not change `ConnectorAccessService` globally. Keep owner/admin authorization on the feedback record after connector-context authorization.

- [ ] **Step 5: Pass explicit route context to shared views**

Use a value array such as:

```php
[
    'index' => ['name' => 'connector.feedback.index', 'parameters' => ['connector' => $connector]],
    'create' => ['name' => 'connector.feedback.create', 'parameters' => ['connector' => $connector]],
    'store' => ['name' => 'connector.feedback.store', 'parameters' => ['connector' => $connector]],
]
```

Extend it for show, attachment, withdrawal, Help search and related-guide navigation.

- [ ] **Step 6: Rerun, compile, lint, and review**

Run:

```powershell
php artisan test tests/Feature/Support/PlatformFeedbackRoutingTest.php tests/Feature/Support/ConnectorSupportRoutingTest.php
php artisan view:cache
php artisan route:list --name=feedback
php artisan route:list --name=connector.feedback
```

Lint compiled views with:

```powershell
Get-ChildItem storage/framework/views -Filter *.php | ForEach-Object {
    php -l $_.FullName
    if ($LASTEXITCODE -ne 0) { throw "Compiled Blade lint failed: $($_.FullName)" }
}
```

Expected: routes return intended 200/403/404 and every compiled view is syntactically valid.

---

### Task 7: Make Feedback Submission Idempotent, Limited, and Upload-Safe

**Files:**
- Create: `app/Rules/ValidSupportImage.php`
- Modify: `app/Http/Requests/StorePlatformFeedbackRequest.php`
- Modify: `app/Services/Support/PlatformFeedbackSubmissionService.php`
- Modify: `app/Providers/AppServiceProvider.php`
- Modify: `routes/web.php`
- Modify: `routes/connector.php`
- Modify: `resources/views/feedback/create.blade.php`
- Modify: `tests/Feature/Support/PlatformFeedbackValidationTest.php`
- Modify: `tests/Unit/Support/PlatformFeedbackSubmissionServiceTest.php`
- Test: `tests/Feature/Support/PlatformFeedbackIdempotencyTest.php`
- Promote coverage from: QA16-QA21, QA43, QA45

**Interfaces:**
- Produces `ValidSupportImage::validate(string $attribute, mixed $value, Closure $fail): void`.
- `PlatformFeedbackSubmissionService::submit(User $user, array $validated, ?UploadedFile $attachment, string $userAgent): PlatformFeedback` consumes `submission_token`.
- Produces rate limiters `support-feedback` and `helpfulness`.

- [ ] **Step 1: Write failing upload, storage, limiter, and idempotency tests**

Cover valid JPEG/PNG/WebP, text/SVG/oversize/renamed PHP/truncated image, dimension/pixel limits, private disk, `store() === false`, DB rollback cleanup, same-user duplicate token, cross-user token collision, and route middleware.

- [ ] **Step 2: Run and confirm failures**

Run:

```powershell
php artisan test tests/Feature/Support/PlatformFeedbackValidationTest.php tests/Feature/Support/PlatformFeedbackIdempotencyTest.php tests/Unit/Support/PlatformFeedbackSubmissionServiceTest.php
```

Expected: FAIL for truncated image, false storage, token behavior, and missing throttles.

- [ ] **Step 3: Implement decoded-image validation**

The rule rejects when `getimagesize($path)` fails, either dimension exceeds 8000, total pixels exceed 40000000, or detected type is not JPEG/PNG/WebP. Keep `mimes` and `max:5120` in request validation.

- [ ] **Step 4: Implement idempotent storage flow**

Before upload, query by current user/token. After storing, reject `false`. Inside the transaction create the feedback with token. On a unique race, delete the newly stored file and return only a record whose `user_id` matches the current user; otherwise throw a safe conflict validation error.

- [ ] **Step 5: Add rate limiters and form state**

Register:

```php
RateLimiter::for('support-feedback', fn (Request $request) =>
    Limit::perMinute(5)->by('support-feedback:'.(int) $request->user()->id)
);

RateLimiter::for('helpfulness', fn (Request $request) =>
    Limit::perMinute(30)->by('helpfulness:'.(int) $request->user()->id)
);
```

Apply them to both feedback POST aliases and helpfulness PUT. Add hidden UUID, `x-data="{ submitting: false }"`, `@submit="submitting = true"`, `aria-busy`, and a disabled submit label. Preserve the token and non-file values after validation failure.

- [ ] **Step 6: Rerun and review**

Run Step 2 and explicit threshold tests. Expected: PASS with one feedback row for duplicate token and no orphan files.

---

### Task 8: Enforce Feedback Lifecycle and Audit Semantics

**Files:**
- Create: `app/Services/Support/PlatformFeedbackLifecycleService.php`
- Modify: `app/Enums/PlatformFeedbackStatus.php`
- Modify: `app/Http/Requests/Admin/UpdatePlatformFeedbackRequest.php`
- Modify: `app/Http/Controllers/Admin/PlatformFeedbackController.php`
- Modify: `app/Notifications/PlatformFeedbackUpdatedNotification.php`
- Test: `tests/Unit/Support/PlatformFeedbackLifecycleServiceTest.php`
- Modify: `tests/Feature/Support/AdminPlatformFeedbackTest.php`
- Test: `tests/Feature/Support/PlatformFeedbackNotificationTest.php`
- Promote coverage from: QA32-QA34, QA44, and lifecycle gap

**Interfaces:**
- Produces `PlatformFeedbackStatus::canTransitionTo(self $target): bool`.
- Produces `PlatformFeedbackLifecycleService::update(PlatformFeedback $feedback, array $data, User $admin): PlatformFeedback`.

- [ ] **Step 1: Write the lifecycle matrix as failing tests**

Assert every allowed transition from the spec and reject every other pair. Also assert same-state no-op, first review attribution, new/private-note-only behavior, resolved timestamp entry/exit, immutable original fields, and one owner-only notification for visible change.

- [ ] **Step 2: Run and confirm failures**

Run:

```powershell
php artisan test tests/Unit/Support/PlatformFeedbackLifecycleServiceTest.php tests/Feature/Support/AdminPlatformFeedbackTest.php tests/Feature/Support/PlatformFeedbackNotificationTest.php
```

Expected: FAIL because no lifecycle service exists and current updates always mark reviewed.

- [ ] **Step 3: Implement the transition map**

Use:

```php
return match ($this) {
    self::New => in_array($target, [self::New, self::Reviewed, self::Archived], true),
    self::Reviewed => in_array($target, [self::Reviewed, self::Planned, self::Resolved, self::Archived], true),
    self::Planned => in_array($target, [self::Planned, self::Reviewed, self::Resolved, self::Archived], true),
    self::Resolved => in_array($target, [self::Resolved, self::Reviewed, self::Planned, self::Archived], true),
    self::Archived => in_array($target, [self::Archived, self::Reviewed], true),
};
```

- [ ] **Step 4: Implement transactional audit/notification behavior**

Capture original status/response. Set review fields only on the first transition away from new. Set/clear resolved timestamp by target. Save in a transaction, then notify once after commit when status or response meaningfully changed.

- [ ] **Step 5: Rerun and review**

Run Step 2. Inspect serialized notification payload and assert no internal note, attachment path, description, birthdate, or external URL.

---

### Task 9: Complete Admin Feedback Inbox and Privacy-Safe Detail UI

**Files:**
- Modify: `app/Http/Controllers/Admin/PlatformFeedbackController.php`
- Modify: `app/Services/Support/PlatformFeedbackInsights.php`
- Modify: `resources/views/admin/feedback/index.blade.php`
- Modify: `resources/views/admin/feedback/show.blade.php`
- Create: `resources/views/admin/feedback/partials/filters.blade.php`
- Create: `resources/views/admin/feedback/partials/insights.blade.php`
- Modify: `tests/Feature/Support/AdminPlatformFeedbackTest.php`
- Test: `tests/Feature/Support/AdminPlatformFeedbackAccessibilityTest.php`
- Promote coverage from: QA32, QA34, UI04, UI08

**Interfaces:**
- Consumes `PlatformFeedbackInsights::summary(array $filters = []): array`.
- Displays stable unfiltered counts plus filtered paginated results.
- Uses canonical authorized attachment route.

- [ ] **Step 1: Write failing rendered-UI tests**

Assert named controls `search,status,type,rating,from,to`; average/type/status/monthly/path insights; attachment URL; and permanent labels containing “Private internal note” and “Response visible to the submitter”.

- [ ] **Step 2: Run and confirm failures**

Run:

```powershell
php artisan test tests/Feature/Support/AdminPlatformFeedbackTest.php tests/Feature/Support/AdminPlatformFeedbackAccessibilityTest.php
```

Expected: FAIL because current templates expose only four counts, no filters/attachment, and unlabeled textareas.

- [ ] **Step 3: Implement the inbox**

Render GET filters with preserved values, reset action, status tabs, stable counts, horizontally contained table, pagination, and empty state. Present all existing aggregate fields; do not calculate them in Blade.

- [ ] **Step 4: Implement privacy-safe detail**

Use separate labeled sections with descriptions and field errors. Render the attachment only through `feedback.attachment.show`. Mark original user content as immutable/read-only presentation.

- [ ] **Step 5: Rerun and review**

Run Step 2 and inspect desktop/mobile HTML. Expected: PASS with no private note in owner views or notification payloads.

---

### Task 10: Centralize Testimonial Eligibility and Public Visibility

**Files:**
- Create: `app/Services/Support/TestimonialPublicationService.php`
- Modify: `app/Services/Support/PlatformFeedbackSubmissionService.php`
- Modify: `app/Models/Testimonial.php`
- Modify: `app/Http/Controllers/WithdrawTestimonialConsentController.php`
- Modify: `app/Http/Controllers/Admin/TestimonialController.php`
- Test: `tests/Unit/Support/TestimonialPublicationServiceTest.php`
- Test: `tests/Feature/Support/TestimonialEligibilityTest.php`
- Create: `tests/Feature/Support/PublicTestimonialTest.php`
- Promote coverage from: QA22-QA27, QA47

**Interfaces:**
- Produces:
  - `draftFromFeedback(PlatformFeedback $feedback): Testimonial`
  - `updateDraft(Testimonial $testimonial, array $data, User $admin): Testimonial`
  - `publish(Testimonial $testimonial, User $admin): Testimonial`
  - `withdraw(Testimonial $testimonial, User $admin): Testimonial`
- Keeps `Testimonial::scopePubliclyVisible(Builder $query, ?CarbonInterface $asOf = null): Builder` fail-closed.

- [ ] **Step 1: Write failing eligibility tests**

Cover current minor, unknown age, inactive/deleted author, missing/withdrawn consent, missing display name, new/unreviewed source, archived source, disallowed role/photo choices, successful reviewed adult publication, and immediate public removal after eligibility loss.

- [ ] **Step 2: Run and confirm failures**

Run:

```powershell
php artisan test tests/Unit/Support/TestimonialPublicationServiceTest.php tests/Feature/Support/TestimonialEligibilityTest.php tests/Feature/Support/PublicTestimonialTest.php
```

Expected: FAIL for unconditional publication and stale public visibility.

- [ ] **Step 3: Implement the eligibility guard**

The private guard loads source and author and throws `DomainException` unless all approved conditions pass. Allowed source statuses are Reviewed, Planned, and Resolved. Force role null/profile false when the corresponding source consent flag is false.

- [ ] **Step 4: Implement fail-closed public scope**

Require published state/timestamp, active unwithdrawn source consent, source review/status, and an existing active non-deleted adult author. The author relation excludes child/teen account types and requires either `birthdate <= ($asOf ?? now())->subYears(18)` or a null birthdate with stored `age >= 18`. Publication still calls `TestimonialEligibility::isAdult()` so both the write and read boundaries fail closed.

- [ ] **Step 5: Route every state change through the service**

Remove direct `testimonial->update(...published...)` calls from controllers. Make consent withdrawal transactional and idempotent.

- [ ] **Step 6: Rerun and review**

Run Step 2. Expected: PASS; no private source data appears on landing.

---

### Task 11: Add Testimonial Curation, Ordering, and Landing Projection

**Files:**
- Create: `app/Http/Requests/Admin/StoreTestimonialRequest.php`
- Create: `app/Http/Requests/Admin/UpdateTestimonialRequest.php`
- Create: `app/Http/Requests/Admin/OrderTestimonialsRequest.php`
- Modify: `app/Http/Controllers/Admin/TestimonialController.php`
- Modify: `routes/admin.php`
- Modify: `resources/views/admin/testimonials/index.blade.php`
- Create: `resources/views/admin/testimonials/form.blade.php`
- Modify: `resources/views/landing/index.blade.php`
- Create: `tests/Feature/Support/AdminTestimonialTest.php`
- Modify: `tests/Feature/Support/PublicTestimonialTest.php`
- Promote coverage from: UI07 and long-quotation stress evidence

**Interfaces:**
- Request fields: `display_name`, `quotation`, `display_role`, `show_profile_image`, `sort_order`.
- Quotation maximum: 1000 characters.
- Public query order: `sort_order ASC, published_at DESC`, maximum six.

- [ ] **Step 1: Write failing CRUD/order/projection tests**

Assert admin-only candidate creation/edit/order, immutable source feedback, accurate excerpt limit, consent-limited role/photo, publish/withdraw actions, public rating, order, maximum six, and no email/note/attachment/user-agent leakage.

- [ ] **Step 2: Run and confirm failures**

Run:

```powershell
php artisan test tests/Feature/Support/AdminTestimonialTest.php tests/Feature/Support/PublicTestimonialTest.php
```

Expected: FAIL because store/update/order routes and curation form are absent.

- [ ] **Step 3: Implement requests and routes**

Validation:

```php
'display_name' => ['required', 'string', 'max:100'],
'quotation' => ['required', 'string', 'max:1000'],
'display_role' => ['nullable', 'string', 'max:60'],
'show_profile_image' => ['nullable', 'boolean'],
'sort_order' => ['required', 'integer', 'min:0'],
```

Add named store/update/order routes and retain publish/withdraw names.

- [ ] **Step 4: Build curation UI and public projection**

Show immutable source beside curated fields. Explain display consent. Render escaped public cards with `break-words [overflow-wrap:anywhere]`, rating when present, permitted role/photo, and safe avatar fallback.

- [ ] **Step 5: Rerun and review**

Run Step 2 and search rendered landing HTML for private fields. Expected: PASS.

---

### Task 12: Add Cross-Role Navigation and Accessible Feedback Forms

**Files:**
- Modify: `resources/views/layouts/learner-sidebar.blade.php`
- Modify: `resources/views/layouts/instructor-app.blade.php`
- Modify: `resources/views/layouts/connector-app.blade.php`
- Modify: `resources/views/layouts/admin.blade.php`
- Modify: `resources/views/layouts/navigation.blade.php`
- Modify: `resources/views/landing/index.blade.php`
- Modify: `resources/views/feedback/create.blade.php`
- Modify: `resources/views/feedback/index.blade.php`
- Modify: `resources/views/help/index.blade.php`
- Modify: `resources/views/help/show.blade.php`
- Test: `tests/Feature/Support/SupportNavigationTest.php`
- Test: `tests/Feature/Support/SupportAccessibilityTest.php`
- Promote coverage from: UI01-UI06 and responsive stress evidence

**Interfaces:**
- Uses named routes exclusively.
- Admin sidebar adds SUPPORT with Help Articles, User Feedback, Testimonials.
- Connector links always include current connector.

- [ ] **Step 1: Write failing navigation and form-accessibility tests**

Assert role-shell links, connector parameters, labels for every named control, field-specific error IDs, `aria-describedby`, retained type/checkbox state, file retry guidance, status text, focus classes, and no raw enum-only presentation.

- [ ] **Step 2: Run and confirm failures**

Run:

```powershell
php artisan test tests/Feature/Support/SupportNavigationTest.php tests/Feature/Support/SupportAccessibilityTest.php
```

Expected: FAIL for missing navigation, labels, field associations, and retained selections.

- [ ] **Step 3: Add navigation using each shell's existing pattern**

Do not remove or reorder unrelated Community Hub, moderation, learning, or account entries. Add active-state checks with `request()->routeIs(...)`. Keep Send feedback as the primary Help page CTA rather than duplicating it as a prominent item in every sidebar.

- [ ] **Step 4: Rebuild form semantics**

Every field gets a visible label and exact error target:

```blade
<label for="feedback-rating">Experience rating (optional)</label>
<input
    id="feedback-rating"
    name="rating"
    aria-describedby="feedback-rating-help @error('rating') feedback-rating-error @enderror"
    @error('rating') aria-invalid="true" @enderror
>
@error('rating')
    <p id="feedback-rating-error" role="alert">{{ $message }}</p>
@enderror
```

Apply the pattern to all feedback and dynamic Help admin fields. Restore `old()` for selects and checkboxes.

- [ ] **Step 5: Add safe wrapping and containment**

Apply `min-w-0`, `break-words`, and targeted `[overflow-wrap:anywhere]` to titles, subjects, reference links, filenames, and public excerpts. Keep `overflow-x-auto` limited to admin tables.

- [ ] **Step 6: Rerun and review**

Run Step 2. Render guest, learner, guardian, instructor, connector, and admin pages. Expected: PASS.

---

### Task 13: Promote QA Coverage and Verify Migrations

**Files:**
- Retain for review: `tests/QA/SupportQaReviewTest.php`
- Retain for review: `tests/QA/SupportUiQaTest.php`
- Create: `docs/qa/2026-09-08-support/remediation-test-map.md`
- Modify: `docs/qa/2026-09-08-support/qa-report.md`
- Create: `docs/qa/2026-09-08-support/remediation-verification.md`

**Interfaces:**
- Normal Support suites own durable regression coverage.
- QA suites remain explicit commands until all cases are green and coverage mapping is documented.

- [ ] **Step 1: Build a QA-to-production-test mapping**

Record each QA01-QA48 and UI01-UI08 against its permanent test method. Do not delete a QA assertion until its behavior exists in the normal suite.

- [ ] **Step 2: Restore isolated test database availability**

Check MySQL service/process and connection for `cc_db_test`. If starting a Windows service requires escalation, request it through the command approval mechanism. Do not point PHPUnit at the development database.

- [ ] **Step 3: Run clean migration and rollback verification**

Set a process-local testing database override, verify the resolved database name, and only then run:

```powershell
$env:APP_ENV = 'testing'
$env:DB_CONNECTION = 'mysql'
$env:DB_DATABASE = 'cc_db_test'
try {
    php artisan tinker --execute="throw_unless(config('database.default') === 'mysql' && config('database.connections.mysql.database') === 'cc_db_test'); dump(config('database.connections.mysql.database'));"
    if ($LASTEXITCODE -ne 0) { throw 'Refusing migration verification: database preflight failed.' }
    php artisan migrate:fresh
    if ($LASTEXITCODE -ne 0) { throw 'Fresh migration failed.' }
    php artisan migrate:rollback --step=3
    if ($LASTEXITCODE -ne 0) { throw 'Support rollback failed.' }
    php artisan migrate
    if ($LASTEXITCODE -ne 0) { throw 'Support migration replay failed.' }
} finally {
    Remove-Item Env:APP_ENV, Env:DB_CONNECTION, Env:DB_DATABASE
}
```

Expected: support migrations go down/up cleanly. Immediately rerun SupportSchemaTest. Do not execute these commands if environment resolution indicates any database other than `cc_db_test`.

- [ ] **Step 4: Run permanent and QA suites**

Run:

```powershell
php artisan test tests/Feature/Support tests/Unit/Support
php vendor/phpunit/phpunit/phpunit tests/QA/SupportQaReviewTest.php
php vendor/phpunit/phpunit/phpunit tests/QA/SupportUiQaTest.php
```

Expected: all feature assertions PASS; no blocked DB setup.

- [ ] **Step 5: Update QA documentation with fresh evidence**

Record exact commands, counts, fixed defect IDs, remaining gaps, and evidence paths. Do not overwrite historical logs in a way that makes the original not-ready result appear to have passed.

---

### Task 14: Full Verification and Handoff

**Files:**
- Create: `database/seeders/SupportBrowserQaSeeder.php`
- Create: `docs/qa/2026-09-08-support/browser-live-review.mjs`
- Test: `tests/Feature/Support/SupportBrowserQaSeederTest.php`
- Update: `docs/qa/2026-09-08-support/remediation-verification.md`
- Rebuild: `public/build/manifest.json` and hashed Vite assets

**Interfaces:**
- Produces final local, uncommitted verification evidence.
- Does not perform Git publication.

- [ ] **Step 1: Run focused Support verification**

Run the three commands from Task 13 Step 4 and capture exact totals.

Expected: zero Support/QA failures and zero blocked UI tests.

- [ ] **Step 2: Run adjacent regressions**

Run:

```powershell
php artisan test tests/Feature/LandingApkDownloadTest.php tests/Feature/Community tests/Unit/Services/Community tests/Feature/Auth tests/Feature/Connectors/ConnectorHomeTest.php tests/Unit/Services/Connectors tests/Feature/Notifications tests/Feature/Learner/ModuleFeedbackFlowTest.php tests/Feature/Learner/ModuleAndInstructorFeedbackSeparationTest.php tests/Feature/Admin/AdminDashboardMetricsTest.php tests/Feature/Admin/AdminNotificationCenterTest.php tests/Feature/Connectors/ConnectorNotificationCenterTest.php tests/Unit/Services/RegistrationTempUploadServiceTest.php
```

Expected: no regression from the previously passing 213-case selection.

- [ ] **Step 3: Run route, syntax, formatting, and safety checks**

Run:

```powershell
php artisan route:list --name=help
php artisan route:list --name=feedback
php artisan route:list --name=admin.help
php artisan route:list --name=admin.feedback
php artisan route:list --name=admin.testimonials
php artisan route:list --name=connector.help
php artisan route:list --name=connector.feedback
vendor\bin\pint.bat --dirty
php artisan view:cache
npm.cmd run build
rg -n "\{!!" resources/views/help resources/views/feedback resources/views/admin/help resources/views/admin/feedback resources/views/admin/testimonials
rg -n "platform-feedback" resources/views public
git diff --check
```

Review formatter/build changes so unrelated dirty files are preserved. Expected: named routes/middleware match the spec, PHP/Blade/build checks succeed, no unescaped support content, and no direct public feedback path.

- [ ] **Step 4: Run browser workflows**

Create `SupportBrowserQaSeeder` with an `app()->environment('testing')` guard and synthetic adult, teen, child, guardian, instructor, connector, and admin fixtures. Its feature test temporarily sets a non-testing environment, asserts the seeder throws before writing, and restores the original environment in `finally`. Create `browser-live-review.mjs` to launch the bundled Puppeteer Chrome, authenticate through actual forms, operate only on synthetic fixtures, save screenshots/results under `docs/qa/2026-09-08-support/evidence/remediation/`, block external requests, and close its browser in `finally`.

Start the Laravel server as one exact hidden process with the explicit `cc_db_test` environment already verified in Task 13, seed only the browser fixtures, run the browser script, and stop that exact process:

```powershell
$env:APP_ENV = 'testing'
$env:DB_CONNECTION = 'mysql'
$env:DB_DATABASE = 'cc_db_test'
php artisan db:seed --class=SupportBrowserQaSeeder
$qaServer = Start-Process -FilePath php -ArgumentList 'artisan','serve','--host=127.0.0.1','--port=8001' -PassThru -WindowStyle Hidden
try {
    node docs/qa/2026-09-08-support/browser-live-review.mjs --base-url http://127.0.0.1:8001
    if ($LASTEXITCODE -ne 0) { throw 'Support browser QA failed.' }
} finally {
    Stop-Process -Id $qaServer.Id
    Remove-Item Env:APP_ENV, Env:DB_CONNECTION, Env:DB_DATABASE
}
```

The script executes:

1. Guest Help search/category/guide.
2. Adult feedback submission/history/detail/attachment/withdrawal.
3. Teen and child feedback without testimonial controls.
4. Guardian parent-targeted Help.
5. Instructor Help and My Feedback.
6. Connector scoped Help/form/history/detail.
7. Admin Help CRUD/order, feedback triage, response/insights, testimonial curation/publication.
8. Guest landing testimonials.

Check 1440 and 390 widths, long accepted text, keyboard navigation, focus, labels, errors, disabled state, empty/no-result states, and XSS payloads. Capture Chromium's accessibility tree for form controls and icon-only actions, and calculate text/background contrast for the support status and action styles using computed CSS colors. Expected: no page-level overflow, unnamed interactive controls, insufficient required text contrast, script execution, or unauthorized data exposure.

- [ ] **Step 5: Attempt the full suite**

Run:

```powershell
php artisan test
```

Expected: record the actual outcome. If it stops at the unchanged Dompdf `Cpdf.php:6289` 512 MiB exhaustion, report it as the separate known repository limitation and retain the green focused results. Do not increase memory.

- [ ] **Step 6: Perform final task-owned diff review**

Run:

```powershell
git branch --show-current
git status --short
git diff --check
git diff --stat
```

Then inspect scoped diffs for all Task 1-12 paths. Confirm branch is `community-feed-v1`, unrelated files are preserved, and no commit/push/merge/worktree action occurred.

- [ ] **Step 7: Deliver the completion report**

Report:

- Exact passed, failed, and blocked counts.
- Each D01-D28 resolution and its permanent regression test.
- Migration, route, build, browser, and security evidence.
- Any remaining limitation without calling it fixed.
- The local uncommitted Git state.
