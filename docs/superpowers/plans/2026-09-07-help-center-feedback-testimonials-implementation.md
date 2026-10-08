# Help Center, Platform Feedback, and Testimonials Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add a public, admin-managed Help Center, a private authenticated Platform Feedback workflow, and an adult-only consent-controlled testimonial section.

**Architecture:** Implement three native Laravel modules with separate models, controllers, policies, requests, services, and Blade views. Help content is public and role-aware, feedback remains private to its owner and platform admins, and testimonials are projections of eligible consented feedback rather than a second submission channel.

**Tech Stack:** PHP 8.2, Laravel 12, Eloquent, PHPUnit 11, Blade, Tailwind CSS 3, Alpine.js, Laravel database notifications, Laravel `local` and `public` filesystem disks.

**Spec:** `docs/superpowers/specs/2026-09-07-help-center-feedback-testimonials-design.md`

## Global Constraints

- Work only on the existing `community-feed-v1` branch.
- Do not create a worktree or switch branches.
- Do not pull, merge, commit, or push unless the user separately requests it.
- Preserve the existing untracked `.claude/` directory and all unrelated user changes.
- Use TDD: add a failing focused test, confirm the expected failure, write the smallest implementation, and rerun the focused test.
- Use existing Laravel, Blade, Tailwind, Alpine, storage, notification, and authorization patterns; add no dependency.
- Keep Help Center, Platform Feedback, module/instructor reviews, and Community Hub moderation as separate domains.
- Keep feedback attachments private on the `local` disk; store administrator-authored Help Center screenshots on the `public` disk.
- Never publish feedback from a user whom the server identifies as a minor.
- Render article, feedback, notes, responses, and testimonial quotation text escaped; do not render untrusted HTML.
- Use the project-local Conscious Connections UI rules and finite literal Tailwind classes.
- Each task ends with a local diff/test checkpoint instead of a Git commit.

## File Map

### Domain foundation

- Create `app/Enums/HelpArticleStatus.php` — help publication states and labels.
- Create `app/Enums/PlatformFeedbackType.php` — allowed feedback types and labels.
- Create `app/Enums/PlatformFeedbackStatus.php` — feedback workflow states and labels.
- Create `app/Enums/TestimonialStatus.php` — testimonial publication states and labels.
- Create `database/migrations/2026_09_07_000001_create_help_center_tables.php` — categories, articles, sections, and votes.
- Create `database/migrations/2026_09_07_000002_create_platform_feedback_table.php` — private product feedback.
- Create `database/migrations/2026_09_07_000003_create_testimonials_table.php` — publishable testimonial projection.
- Create `app/Models/HelpCategory.php`, `HelpArticle.php`, `HelpArticleSection.php`, `HelpArticleVote.php`, `PlatformFeedback.php`, and `Testimonial.php`.
- Modify `app/Models/User.php` — support-domain relationships only.
- Create factories for categories, articles, feedback, and testimonials.

### Help Center

- Create `app/Http/Controllers/HelpCenterController.php` — public search/index/show.
- Create `app/Http/Controllers/HelpArticleHelpfulnessController.php` — authenticated vote upsert.
- Create `app/Http/Requests/UpdateHelpArticleHelpfulnessRequest.php`.
- Create `database/seeders/HelpCenterSeeder.php` and modify `database/seeders/DatabaseSeeder.php`.
- Create shared Help Center partials and two dynamic-layout views under `resources/views/help/`.
- Create admin category/article controllers, requests, and views under `app/Http/Controllers/Admin`, `app/Http/Requests/Admin`, and `resources/views/admin/help`.
- Modify `routes/web.php`, `routes/admin.php`, and `routes/connector.php` for named routes.

### Platform Feedback

- Create `app/Http/Requests/StorePlatformFeedbackRequest.php`.
- Create `app/Services/Support/PlatformFeedbackSubmissionService.php`.
- Create `app/Policies/PlatformFeedbackPolicy.php`.
- Create user controllers and views under `app/Http/Controllers` and `resources/views/feedback/`.
- Create admin feedback request/controller/query service/views under the admin namespaces.
- Create `app/Notifications/PlatformFeedbackUpdatedNotification.php`.
- Create `app/Services/Support/TestimonialEligibility.php` — fail-closed adult eligibility shared by feedback and testimonial publication.

### Testimonials and integration

- Create `app/Services/Support/TestimonialPublicationService.php`.
- Create admin testimonial request/controller/views.
- Modify the public home query and `resources/views/landing/index.blade.php`.
- Modify learner, instructor, connector, admin, and public navigation surfaces.
- Create focused tests under `tests/Feature/Support` and `tests/Unit/Support`.

---

### Task 1: Add support enums and database schema

**Files:**
- Create: `app/Enums/HelpArticleStatus.php`
- Create: `app/Enums/PlatformFeedbackType.php`
- Create: `app/Enums/PlatformFeedbackStatus.php`
- Create: `app/Enums/TestimonialStatus.php`
- Create: `database/migrations/2026_09_07_000001_create_help_center_tables.php`
- Create: `database/migrations/2026_09_07_000002_create_platform_feedback_table.php`
- Create: `database/migrations/2026_09_07_000003_create_testimonials_table.php`
- Test: `tests/Feature/Support/SupportSchemaTest.php`
- Test: `tests/Unit/Support/SupportEnumTest.php`

**Interfaces:**
- Produces: `HelpArticleStatus::values()`, `PlatformFeedbackType::values()`, `PlatformFeedbackStatus::values()`, and `TestimonialStatus::values()` returning `array<int,string>`.
- Produces: schema tables consumed by every later task.

- [ ] **Step 1: Write enum tests**

Create `tests/Unit/Support/SupportEnumTest.php` asserting exact values and labels:

```php
<?php

namespace Tests\Unit\Support;

use App\Enums\HelpArticleStatus;
use App\Enums\PlatformFeedbackStatus;
use App\Enums\PlatformFeedbackType;
use App\Enums\TestimonialStatus;
use PHPUnit\Framework\TestCase;

class SupportEnumTest extends TestCase
{
    public function test_support_enums_expose_stable_values_and_plain_labels(): void
    {
        $this->assertSame(['draft', 'published', 'archived'], HelpArticleStatus::values());
        $this->assertSame(['general', 'bug_report', 'feature_suggestion', 'accessibility_issue', 'help_content_issue'], PlatformFeedbackType::values());
        $this->assertSame(['new', 'reviewed', 'planned', 'resolved', 'archived'], PlatformFeedbackStatus::values());
        $this->assertSame(['draft', 'published', 'withdrawn'], TestimonialStatus::values());
        $this->assertSame('Bug Report', PlatformFeedbackType::BugReport->label());
        $this->assertSame('In Review', PlatformFeedbackStatus::Reviewed->label());
    }
}
```

- [ ] **Step 2: Run the enum test and confirm failure**

Run:

```powershell
php artisan test tests/Unit/Support/SupportEnumTest.php
```

Expected: FAIL because the four enum classes do not exist.

- [ ] **Step 3: Implement the four enums**

Use backed string enums with `label()` and `values()` methods. The feedback type enum must be:

```php
<?php

namespace App\Enums;

enum PlatformFeedbackType: string
{
    case General = 'general';
    case BugReport = 'bug_report';
    case FeatureSuggestion = 'feature_suggestion';
    case AccessibilityIssue = 'accessibility_issue';
    case HelpContentIssue = 'help_content_issue';

    public function label(): string
    {
        return match ($this) {
            self::General => 'General Feedback',
            self::BugReport => 'Bug Report',
            self::FeatureSuggestion => 'Feature Suggestion',
            self::AccessibilityIssue => 'Accessibility Issue',
            self::HelpContentIssue => 'Help Content Issue',
        };
    }

    public static function values(): array
    {
        return array_map(static fn (self $type): string => $type->value, self::cases());
    }
}
```

Implement the other enums with the exact values asserted by the test. Label `reviewed` as `In Review`; all other labels are headline versions of their values.

- [ ] **Step 4: Run the enum test and confirm it passes**

Run the Step 2 command. Expected: PASS.

- [ ] **Step 5: Write the failing schema test**

Create `tests/Feature/Support/SupportSchemaTest.php` using `Tests\DatabaseTestCase` and `RefreshDatabase`. Assert each table and its critical columns:

```php
public function test_support_tables_have_the_required_privacy_and_lifecycle_columns(): void
{
    $this->assertTrue(Schema::hasColumns('help_categories', ['name', 'slug', 'audiences', 'sort_order', 'is_active']));
    $this->assertTrue(Schema::hasColumns('help_articles', ['help_category_id', 'created_by', 'title', 'slug', 'status', 'published_at']));
    $this->assertTrue(Schema::hasColumns('help_article_sections', ['help_article_id', 'body', 'image_path', 'image_alt_text', 'sort_order']));
    $this->assertTrue(Schema::hasColumns('help_article_votes', ['help_article_id', 'user_id', 'is_helpful']));
    $this->assertTrue(Schema::hasColumns('platform_feedback', ['reference_number', 'user_id', 'type', 'attachment_path', 'status', 'testimonial_consent', 'testimonial_consent_withdrawn_at']));
    $this->assertTrue(Schema::hasColumns('testimonials', ['platform_feedback_id', 'user_id', 'display_name', 'quotation', 'status', 'published_at', 'withdrawn_at']));
}
```

- [ ] **Step 6: Run the schema test and confirm failure**

Run:

```powershell
php artisan test tests/Feature/Support/SupportSchemaTest.php
```

Expected: FAIL because the tables do not exist.

- [ ] **Step 7: Implement the help schema migration**

Create all four help tables in dependency order and drop them in reverse order. Use these exact column contracts:

```php
Schema::create('help_categories', function (Blueprint $table): void {
    $table->id();
    $table->string('name', 120);
    $table->string('slug', 140)->unique();
    $table->string('description', 500)->nullable();
    $table->string('icon_key', 50)->nullable();
    $table->json('audiences');
    $table->unsignedInteger('sort_order')->default(0);
    $table->boolean('is_active')->default(true);
    $table->timestamps();
});

Schema::create('help_articles', function (Blueprint $table): void {
    $table->id();
    $table->foreignId('help_category_id')->constrained()->cascadeOnDelete();
    $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
    $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
    $table->string('title', 180);
    $table->string('slug', 200)->unique();
    $table->string('summary', 500);
    $table->json('keywords')->nullable();
    $table->json('audiences');
    $table->string('status', 30)->default('draft')->index();
    $table->unsignedInteger('sort_order')->default(0);
    $table->timestamp('published_at')->nullable()->index();
    $table->timestamps();
});

Schema::create('help_article_sections', function (Blueprint $table): void {
    $table->id();
    $table->foreignId('help_article_id')->constrained()->cascadeOnDelete();
    $table->string('heading', 180)->nullable();
    $table->longText('body');
    $table->string('image_path')->nullable();
    $table->string('image_alt_text', 255)->nullable();
    $table->unsignedInteger('sort_order')->default(0);
    $table->timestamps();
    $table->index(['help_article_id', 'sort_order']);
});

Schema::create('help_article_votes', function (Blueprint $table): void {
    $table->id();
    $table->foreignId('help_article_id')->constrained()->cascadeOnDelete();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->boolean('is_helpful');
    $table->timestamps();
    $table->unique(['help_article_id', 'user_id']);
});
```

- [ ] **Step 8: Implement the feedback and testimonial migrations**

Create `platform_feedback` with the fields from the spec and these bounds:

```php
Schema::create('platform_feedback', function (Blueprint $table): void {
    $table->id();
    $table->string('reference_number', 30)->unique();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->string('user_role', 40);
    $table->string('type', 40)->index();
    $table->string('subject', 180);
    $table->longText('description');
    $table->unsignedTinyInteger('rating')->nullable();
    $table->string('affected_path', 500)->nullable();
    $table->string('user_agent', 500)->nullable();
    $table->boolean('may_contact')->default(false);
    $table->string('attachment_path')->nullable();
    $table->string('status', 30)->default('new')->index();
    $table->text('internal_note')->nullable();
    $table->text('staff_response')->nullable();
    $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
    $table->timestamp('reviewed_at')->nullable();
    $table->timestamp('resolved_at')->nullable();
    $table->boolean('testimonial_consent')->default(false);
    $table->string('testimonial_display_name', 100)->nullable();
    $table->boolean('testimonial_show_role')->default(false);
    $table->boolean('testimonial_show_profile_image')->default(false);
    $table->timestamp('testimonial_consented_at')->nullable();
    $table->timestamp('testimonial_consent_withdrawn_at')->nullable();
    $table->timestamps();
    $table->index(['user_id', 'created_at']);
    $table->index(['type', 'status', 'created_at']);
});
```

Create `testimonials` with a unique source feedback foreign key, display fields, enum-backed string status, ordering, and publication/withdrawal timestamps. Use `cascadeOnDelete()` for the source feedback and user, and `nullOnDelete()` for `approved_by`.

- [ ] **Step 9: Run schema and enum tests**

Run:

```powershell
php artisan test tests/Unit/Support/SupportEnumTest.php tests/Feature/Support/SupportSchemaTest.php
```

Expected: PASS.

- [ ] **Step 10: Local checkpoint**

Run `git diff --check` and `git status --short`. Confirm only Task 1 files plus the approved spec/plan are present; do not stage or commit.

---

### Task 2: Add Eloquent models, relationships, factories, and query scopes

**Files:**
- Create: `app/Models/HelpCategory.php`
- Create: `app/Models/HelpArticle.php`
- Create: `app/Models/HelpArticleSection.php`
- Create: `app/Models/HelpArticleVote.php`
- Create: `app/Models/PlatformFeedback.php`
- Create: `app/Models/Testimonial.php`
- Modify: `app/Models/User.php:330-355`
- Create: `database/factories/HelpCategoryFactory.php`
- Create: `database/factories/HelpArticleFactory.php`
- Create: `database/factories/PlatformFeedbackFactory.php`
- Create: `database/factories/TestimonialFactory.php`
- Test: `tests/Unit/Support/SupportModelTest.php`

**Interfaces:**
- Consumes: enum values and schema from Task 1.
- Produces: `HelpArticle::publishedForAudience(Builder,string)`, `PlatformFeedback::forAdminFilters(Builder,array)`, and `Testimonial::publiclyVisible(Builder)` scopes.
- Produces: Eloquent relationships used by controllers, policies, and services.

- [ ] **Step 1: Write failing model tests**

Cover enum/date/JSON casts, relationship traversal, public article filtering, owner feedback filtering, and public testimonial filtering. Include this audience assertion:

```php
$public = HelpArticle::factory()->for($category)->create([
    'audiences' => ['all'],
    'status' => HelpArticleStatus::Published,
    'published_at' => now(),
]);
$adminOnly = HelpArticle::factory()->for($category)->create([
    'audiences' => ['admin'],
    'status' => HelpArticleStatus::Published,
    'published_at' => now(),
]);

$this->assertEqualsCanonicalizing(
    [$public->id],
    HelpArticle::query()->publishedForAudience('learner')->pluck('id')->all()
);
```

- [ ] **Step 2: Run the model test and confirm failure**

Run `php artisan test tests/Unit/Support/SupportModelTest.php`.

Expected: FAIL because models and factories do not exist.

- [ ] **Step 3: Implement help models and scopes**

Use `HasFactory`, explicit `$fillable`, enum/array/datetime casts, typed relationship return values, and these scope semantics:

```php
public function scopePublishedForAudience(Builder $query, string $audience): Builder
{
    return $query
        ->where('status', HelpArticleStatus::Published)
        ->whereNotNull('published_at')
        ->whereHas('category', fn (Builder $category) => $category->where('is_active', true))
        ->where(function (Builder $audiences) use ($audience): void {
            $audiences->whereJsonContains('audiences', 'all')
                ->orWhereJsonContains('audiences', $audience);
        });
}
```

`HelpArticle` has `category`, `sections`, `votes`, `creator`, and `editor` relationships. `HelpCategory` has ordered `articles`. `HelpArticleSection` and `HelpArticleVote` belong to their parent records.

- [ ] **Step 4: Implement feedback and testimonial models**

`PlatformFeedback` casts `type`, `status`, booleans, rating, and timestamps. It belongs to `user` and `reviewer`, and has one `testimonial`. `Testimonial` casts `status`, `show_profile_image`, `published_at`, and `withdrawn_at`, and belongs to `feedback`, `user`, and `approver`.

Use this public scope:

```php
public function scopePubliclyVisible(Builder $query): Builder
{
    return $query
        ->where('status', TestimonialStatus::Published)
        ->whereNotNull('published_at')
        ->whereHas('feedback', function (Builder $feedback): void {
            $feedback->where('testimonial_consent', true)
                ->whereNull('testimonial_consent_withdrawn_at');
        });
}
```

Implement `forAdminFilters()` for exact `status`, exact `type`, optional rating, inclusive `from`/`to` dates, and a length-limited search across reference number, subject, and the related user's name/email.

- [ ] **Step 5: Add User relationships**

Add these methods without changing existing module/instructor feedback methods:

```php
public function platformFeedbackSubmissions()
{
    return $this->hasMany(PlatformFeedback::class);
}

public function helpArticleVotes()
{
    return $this->hasMany(HelpArticleVote::class);
}

public function testimonials()
{
    return $this->hasMany(Testimonial::class);
}
```

- [ ] **Step 6: Implement focused factories**

Factories must create valid default published help records, new private feedback, and draft testimonials. Factory states include `draft()`, `published()`, `minorConsentAttempt()`, and `withdrawn()` where relevant. Use real enum values and timestamps, not arbitrary strings.

- [ ] **Step 7: Run model tests**

Run `php artisan test tests/Unit/Support/SupportModelTest.php`.

Expected: PASS.

- [ ] **Step 8: Local checkpoint**

Run `git diff --check` and inspect only Task 2 paths. Do not stage or commit.

---

### Task 3: Build public Help Center search, guides, and seeded content

**Files:**
- Create: `app/Http/Controllers/HelpCenterController.php`
- Create: `database/seeders/HelpCenterSeeder.php`
- Modify: `database/seeders/DatabaseSeeder.php:13-25`
- Modify: `routes/web.php:100-180`
- Create: `resources/views/help/index.blade.php`
- Create: `resources/views/help/show.blade.php`
- Create: `resources/views/help/partials/index-content.blade.php`
- Create: `resources/views/help/partials/article-content.blade.php`
- Create: `resources/views/help/partials/search-results.blade.php`
- Test: `tests/Feature/Support/PublicHelpCenterTest.php`
- Test: `tests/Feature/Support/HelpCenterSeederTest.php`

**Interfaces:**
- Consumes: `HelpArticle::publishedForAudience()` from Task 2.
- Produces: `help.index` and `help.show` public routes.
- Produces: seeded categories and starter navigation guides.

- [ ] **Step 1: Write failing public Help Center tests**

Test guest index rendering, published article rendering, draft/archived/inactive hiding, audience filtering, search, pagination, escaped article text, related articles, and not-found behavior. Assert route names rather than hard-coded paths.

```php
$this->get(route('help.show', $draft->slug))->assertNotFound();
$this->get(route('help.index', ['q' => 'certificate']))
    ->assertOk()
    ->assertSee($matching->title)
    ->assertDontSee($unrelated->title);
```

- [ ] **Step 2: Run the public test and confirm failure**

Run `php artisan test tests/Feature/Support/PublicHelpCenterTest.php`.

Expected: FAIL because routes and controller do not exist.

- [ ] **Step 3: Add public routes and controller query contract**

Add public routes before broad authenticated route groups:

```php
Route::get('/help', [HelpCenterController::class, 'index'])->name('help.index');
Route::get('/help/{helpArticle:slug}', [HelpCenterController::class, 'show'])->name('help.show');
```

In `index()`, derive audience as `guest` when unauthenticated, otherwise the normalized user role. Query active categories, recommended articles for that audience, and paginated search results. Limit `q` to 100 characters and escape wildcard characters before `LIKE` matching.

In `show()`, abort with 404 unless the bound article is included by `publishedForAudience($audience)`. Load ordered sections and three related published articles from the same category.

- [ ] **Step 4: Build shared public views**

Use `layouts.landing` for the first public wrapper. Render shared partials with:

- Brand-gradient search hero.
- Category grid.
- Role-relevant and general guide lists.
- Empty search state.
- Escaped section bodies with `whitespace-pre-line`.
- Optional public screenshot via `Storage::disk('public')->url($section->image_path)`.
- Feedback CTA that sends guests to login and authenticated users to `feedback.create` after Task 6.
- Safety copy that points users to existing report controls rather than accepting safety reports here.

- [ ] **Step 5: Write failing seeder test**

Run the seeder twice and assert exactly 13 unique slugs, stable order, and at least one published starter article for `Getting Started`, `Community Hub`, and `Privacy and Safety`.

- [ ] **Step 6: Run the seeder test and confirm failure**

Run `php artisan test tests/Feature/Support/HelpCenterSeederTest.php`.

Expected: FAIL because `HelpCenterSeeder` does not exist.

- [ ] **Step 7: Implement idempotent seed data**

Use `updateOrCreate(['slug' => $slug], [...])` for all 13 categories. Seed one article per category with these exact titles, audiences, and ordered section headings:

| Category | Article title | Audiences | Section headings |
| --- | --- | --- | --- |
| Getting Started | Navigate Conscious Connections | `all` | Sign in; Open your dashboard; Use the main navigation |
| Account and Profile | Update Your Profile | `learner`, `parent`, `instructor`, `admin` | Open profile settings; Review your information; Save changes |
| Learning and Modules | Find and Start a Learning Module | `learner`, `parent` | Browse modules; Review module details; Enroll and begin |
| Quizzes and Certificates | Take a Quiz and View Your Results | `learner`, `parent` | Open the lesson quiz; Submit answers; Review results and certificates |
| Seminars | Find and Join a Seminar | `learner`, `parent`, `instructor`, `connector` | Browse seminars; Review event details; Register or attend |
| Community Hub | Use Community Hub Safely | `learner`, `connector`, `admin` | Confirm access; Read and participate; Report unsafe content |
| Parent and Guardian Support | Review a Dependent Enrollment Request | `parent` | Open My Children; Review the request; Approve or reject |
| Instructor Tools | Submit a Module for Review | `instructor`, `admin` | Check module readiness; Submit for review; Track the decision |
| Connector Tools | Open Your Connector Workspace | `connector`, `admin` | Choose a connector; Review workspace status; Open members seminars or community |
| Payments and Subscriptions | Review Subscription and Payment Status | `learner`, `parent`, `instructor`, `connector`, `admin` | Open subscriptions; Review payment status; Find receipts or next actions |
| Privacy and Safety | Report Unsafe or Inappropriate Content | `all` | Use the content report action; Describe the concern; Follow status updates |
| Accessibility | Use Language and Accessibility Controls | `all` | Find available controls; Change language or playback; Send an accessibility issue |
| Troubleshooting | Fix Common Sign-in and Navigation Problems | `all` | Confirm account and connection; Refresh safely; Ask for platform help |

Write concise plain-text bodies that explain each heading in two to four sentences using route labels that exist in the current UI. Use `updateOrCreate()` for articles and replace their sections transactionally by stable `sort_order`. Call `HelpCenterSeeder::class` after the role/permission seeders in `DatabaseSeeder`.

- [ ] **Step 8: Run public and seeder tests**

Run:

```powershell
php artisan test tests/Feature/Support/PublicHelpCenterTest.php tests/Feature/Support/HelpCenterSeederTest.php
```

Expected: PASS.

- [ ] **Step 9: Local checkpoint**

Run `git diff --check`; inspect the Help Center route order and verify no existing public route was renamed.

---

### Task 4: Add role-aware Help Center wrappers and helpfulness voting

**Files:**
- Create: `app/Http/Requests/UpdateHelpArticleHelpfulnessRequest.php`
- Create: `app/Http/Controllers/HelpArticleHelpfulnessController.php`
- Create: `app/Services/Support/SupportLayoutResolver.php`
- Modify: `app/Http/Controllers/HelpCenterController.php`
- Modify: `routes/web.php`
- Modify: `routes/connector.php`
- Modify: `resources/views/help/index.blade.php`
- Modify: `resources/views/help/show.blade.php`
- Test: `tests/Feature/Support/RoleAwareHelpCenterTest.php`
- Test: `tests/Feature/Support/HelpArticleHelpfulnessTest.php`

**Interfaces:**
- Consumes: public Help Center partials from Task 3.
- Produces: `SupportLayoutResolver::resolve(?User,?Connector): string`, authenticated shell selection, and `help.helpfulness.update`.
- Produces: connector-scoped Help Center aliases that preserve `$connector` for layout rendering without changing help authorization.

- [ ] **Step 1: Write failing role-wrapper tests**

Authenticate learner, parent, instructor, admin, and connector-member users. Assert the route renders the expected shell marker and only recommended articles for the role. For connector routes, assert a non-member receives 403 and a member sees the connector name.

- [ ] **Step 2: Run role-wrapper tests and confirm failure**

Run `php artisan test tests/Feature/Support/RoleAwareHelpCenterTest.php`.

Expected: FAIL because dynamic role layouts and connector aliases do not exist.

- [ ] **Step 3: Implement safe dynamic layout selection**

Keep all content in shared partials. `SupportLayoutResolver` injects `ConnectorAccessService`; when a connector is present it calls `abortUnlessWorkspace($user, $connector)` before returning the connector shell. Otherwise it returns an explicit role shell:

```php
public function resolve(?User $user, ?Connector $connector = null): string
{
    if ($connector !== null) {
        abort_unless($user !== null, 403);
        $this->connectorAccess->abortUnlessWorkspace($user, $connector);

        return 'layouts.connector-app';
    }

    return match ($user?->role) {
        'admin' => 'layouts.admin',
        'instructor' => 'layouts.instructor-app',
        'learner', 'parent' => 'layouts.learner-app',
        default => 'layouts.landing',
    };
}
```

Change the two Help views to `@extends($supportLayout)` and keep their existing shared partial includes. `HelpCenterController::index(Request $request, ?Connector $connector = null)` and `show(Request $request, HelpArticle $helpArticle, ?Connector $connector = null)` pass `$supportLayout`, `$connector`, and route-name/parameter values to the partials. Add `connector.help.index` and `connector.help.show` aliases inside the existing authenticated connector group; both call the same controller actions and therefore receive the same article filtering.

- [ ] **Step 4: Write failing helpfulness tests**

Assert guests are redirected to login, invalid values fail validation, inaccessible/draft articles return 404, and repeat voting updates one row:

```php
$this->actingAs($learner)->put(route('help.helpfulness.update', $article), ['is_helpful' => true]);
$this->actingAs($learner)->put(route('help.helpfulness.update', $article), ['is_helpful' => false]);
$this->assertDatabaseCount('help_article_votes', 1);
$this->assertDatabaseHas('help_article_votes', ['user_id' => $learner->id, 'is_helpful' => false]);
```

- [ ] **Step 5: Run helpfulness tests and confirm failure**

Run `php artisan test tests/Feature/Support/HelpArticleHelpfulnessTest.php`.

Expected: FAIL because the route does not exist.

- [ ] **Step 6: Implement validated vote upsert**

Authorize any authenticated user who can view the published article. Validate `is_helpful` as required boolean and execute:

```php
$article->votes()->updateOrCreate(
    ['user_id' => $request->user()->id],
    ['is_helpful' => $request->boolean('is_helpful')],
);
```

Return back with `Thanks for helping us improve this guide.` and rate-limit the route.

- [ ] **Step 7: Run focused tests**

Run:

```powershell
php artisan test tests/Feature/Support/RoleAwareHelpCenterTest.php tests/Feature/Support/HelpArticleHelpfulnessTest.php
```

Expected: PASS.

- [ ] **Step 8: Local checkpoint**

Inspect wrapper duplication: wrappers may contain layout directives and includes only; move repeated content back into shared partials.

---

### Task 5: Build Help Center administration

**Files:**
- Create: `app/Http/Controllers/Admin/HelpCategoryController.php`
- Create: `app/Http/Controllers/Admin/HelpArticleController.php`
- Create: `app/Http/Requests/Admin/StoreHelpCategoryRequest.php`
- Create: `app/Http/Requests/Admin/UpdateHelpCategoryRequest.php`
- Create: `app/Http/Requests/Admin/StoreHelpArticleRequest.php`
- Create: `app/Http/Requests/Admin/UpdateHelpArticleRequest.php`
- Modify: `routes/admin.php:17-90`
- Create: `resources/views/admin/help/categories/index.blade.php`
- Create: `resources/views/admin/help/categories/form.blade.php`
- Create: `resources/views/admin/help/articles/index.blade.php`
- Create: `resources/views/admin/help/articles/form.blade.php`
- Create: `resources/views/admin/help/articles/preview.blade.php`
- Test: `tests/Feature/Support/AdminHelpCenterTest.php`

**Interfaces:**
- Consumes: help models/statuses from Tasks 1-2.
- Produces: `admin.help.categories.*` and `admin.help.articles.*` routes.
- Produces: validated ordered article sections and public screenshot paths.

- [ ] **Step 1: Write failing admin authorization and lifecycle tests**

Cover guest redirect, learner/instructor 403, admin index rendering, category activation/order, article create/update, draft preview, publish, archive, section order, required image alt text, invalid image rejection, and old-image cleanup after a successful update.

- [ ] **Step 2: Run the admin Help Center test and confirm failure**

Run `php artisan test tests/Feature/Support/AdminHelpCenterTest.php`.

Expected: FAIL because admin support routes do not exist.

- [ ] **Step 3: Implement category requests and controller**

Validate:

```php
return [
    'name' => ['required', 'string', 'max:120'],
    'slug' => ['required', 'alpha_dash', 'max:140', Rule::unique('help_categories', 'slug')->ignore($this->route('helpCategory'))],
    'description' => ['nullable', 'string', 'max:500'],
    'icon_key' => ['nullable', Rule::in(['rocket', 'account', 'book', 'quiz', 'seminar', 'community', 'guardian', 'instructor', 'connector', 'payment', 'shield', 'accessibility', 'tools'])],
    'audiences' => ['required', 'array', 'min:1'],
    'audiences.*' => [Rule::in(['all', 'guest', 'learner', 'parent', 'instructor', 'connector', 'admin'])],
    'sort_order' => ['required', 'integer', 'min:0'],
    'is_active' => ['required', 'boolean'],
];
```

Add an `after()` validator that rejects `all` combined with another audience. Use standard resource actions plus explicit order update; do not hard-delete categories.

- [ ] **Step 4: Implement article requests and transactional controller**

Validate title, unique slug, summary, keyword array, audience array, category, status, and `sections`:

```php
'sections' => ['required', 'array', 'min:1'],
'sections.*.heading' => ['nullable', 'string', 'max:180'],
'sections.*.body' => ['required', 'string', 'max:20000'],
'sections.*.image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
'sections.*.image_alt_text' => ['nullable', 'required_with:sections.*.image', 'string', 'max:255'],
'sections.*.existing_image_path' => ['nullable', 'string', 'max:500'],
```

Only accept `existing_image_path` when it belongs to the article being edited. Within a database transaction, save article metadata and replace/update sections in submitted order. Store new guide images under `help/articles/{article-id}` on `public`. Delete superseded files only after the database transaction commits; delete newly stored files if the transaction fails.

Publishing sets `status=published` and `published_at` if absent. Archiving sets `status=archived` without deleting article/sections.

- [ ] **Step 5: Build admin Help views**

Use `layouts.admin`, existing table/filter patterns, status badges, responsive overflow wrappers, and an Alpine section repeater with literal Tailwind classes. The preview renders the same shared article partial but disables voting and feedback actions.

- [ ] **Step 6: Run admin Help Center tests**

Run `php artisan test tests/Feature/Support/AdminHelpCenterTest.php`.

Expected: PASS.

- [ ] **Step 7: Run all Help Center tests**

Run:

```powershell
php artisan test tests/Feature/Support/PublicHelpCenterTest.php tests/Feature/Support/HelpCenterSeederTest.php tests/Feature/Support/RoleAwareHelpCenterTest.php tests/Feature/Support/HelpArticleHelpfulnessTest.php tests/Feature/Support/AdminHelpCenterTest.php
```

Expected: PASS.

- [ ] **Step 8: Local checkpoint**

Run `git diff --check` and confirm no raw article body uses `{!! !!}`.

---

### Task 6: Implement Platform Feedback validation, policy, and submission service

**Files:**
- Create: `app/Http/Requests/StorePlatformFeedbackRequest.php`
- Create: `app/Policies/PlatformFeedbackPolicy.php`
- Create: `app/Services/Support/TestimonialEligibility.php`
- Create: `app/Services/Support/PlatformFeedbackSubmissionService.php`
- Test: `tests/Unit/Support/PlatformFeedbackSubmissionServiceTest.php`
- Test: `tests/Feature/Support/PlatformFeedbackValidationTest.php`

**Interfaces:**
- Consumes: `PlatformFeedback`, feedback enums, and existing user age/account-type data.
- Produces: `TestimonialEligibility::isAdult(User): bool`, which returns false when adulthood cannot be proved.
- Produces: `PlatformFeedbackSubmissionService::submit(User,array,?UploadedFile,string): PlatformFeedback`.
- Produces: owner/admin `view` and admin-only `update` policy decisions.

- [ ] **Step 1: Write failing service tests**

Test reference generation, role snapshot, relative-path normalization, limited user agent, private attachment storage, cleanup on database failure, adult consent recording, and consent stripping for known minors or users whose adulthood cannot be proved.

```php
$minor = User::factory()->create([
    'role' => 'learner',
    'birthdate' => now()->subYears(16),
]);

$feedback = $service->submit($minor, $payload + [
    'testimonial_consent' => true,
    'testimonial_display_name' => 'Minor Name',
], null, 'Browser/1.0');

$this->assertFalse($feedback->testimonial_consent);
$this->assertNull($feedback->testimonial_display_name);
```

- [ ] **Step 2: Run the service test and confirm failure**

Run `php artisan test tests/Unit/Support/PlatformFeedbackSubmissionServiceTest.php`.

Expected: FAIL because the service does not exist.

- [ ] **Step 3: Implement fail-closed adult eligibility and the submission service**

Create one shared eligibility service:

```php
public function isAdult(User $user): bool
{
    if (in_array($user->account_type, [
        User::ACCOUNT_TYPE_LEARNER_CHILD,
        User::ACCOUNT_TYPE_LEARNER_TEEN,
    ], true)) {
        return false;
    }

    $age = $user->calculateAge() ?? $user->age;

    return $age !== null && (int) $age >= 18;
}
```

This intentionally differs from merely negating `isMinorForCommunityFeed()`:
missing age evidence must not be interpreted as proof that testimonial
publication is allowed.

The exact public method is:

```php
public function submit(User $user, array $validated, ?UploadedFile $attachment, string $userAgent): PlatformFeedback
```

Generate references as `FB-YYYYMMDD-XXXXXXXX` using uppercase random characters and retry on collision. Normalize `affected_path` by parsing it and accepting only a path beginning with `/`; discard scheme, host, query, and fragment. Truncate user agent to 500 characters.

Store optional files on `local` under `platform-feedback/{user-id}/{uuid}.{extension}`. Persist with `DB::transaction()`. When `TestimonialEligibility::isAdult($user)` is false, force all testimonial fields false/null. On any throwable after file storage, delete the stored file and rethrow.

- [ ] **Step 4: Write failing request and policy tests**

Validate each type, subject length, description length, rating bounds, relative affected path, boolean flags, conditional adult display name, and image limit. Assert owners/admins can view, unrelated users cannot view, and only admins can update.

- [ ] **Step 5: Run validation tests and confirm failure**

Run `php artisan test tests/Feature/Support/PlatformFeedbackValidationTest.php`.

Expected: FAIL because request and policy do not exist.

- [ ] **Step 6: Implement request rules and policy**

Use:

```php
'type' => ['required', Rule::enum(PlatformFeedbackType::class)],
'subject' => ['required', 'string', 'max:180'],
'description' => ['required', 'string', 'max:20000'],
'rating' => ['nullable', 'integer', 'between:1,5'],
'affected_path' => ['nullable', 'string', 'max:500', 'regex:/^\//'],
'may_contact' => ['nullable', 'boolean'],
'attachment' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
'testimonial_consent' => ['nullable', 'boolean'],
'testimonial_display_name' => ['nullable', 'required_if:testimonial_consent,1', 'string', 'max:100'],
'testimonial_show_role' => ['nullable', 'boolean'],
'testimonial_show_profile_image' => ['nullable', 'boolean'],
```

Policy `view()` returns true only for the owner or `role === 'admin'`; `update()` and `viewAny()` return true only for admins.

- [ ] **Step 7: Run Task 6 tests**

Run:

```powershell
php artisan test tests/Unit/Support/PlatformFeedbackSubmissionServiceTest.php tests/Feature/Support/PlatformFeedbackValidationTest.php
```

Expected: PASS.

- [ ] **Step 8: Local checkpoint**

Confirm no IP address is collected and no attachment uses the `public` disk.

---

### Task 7: Build user feedback form, history, detail, attachment, and consent withdrawal

**Files:**
- Create: `app/Http/Controllers/PlatformFeedbackController.php`
- Create: `app/Http/Controllers/PlatformFeedbackAttachmentController.php`
- Create: `app/Http/Controllers/WithdrawTestimonialConsentController.php`
- Modify: `routes/web.php`
- Modify: `routes/connector.php`
- Create: `resources/views/feedback/create.blade.php`
- Create: `resources/views/feedback/index.blade.php`
- Create: `resources/views/feedback/show.blade.php`
- Create: `resources/views/feedback/partials/create-content.blade.php`
- Create: `resources/views/feedback/partials/index-content.blade.php`
- Create: `resources/views/feedback/partials/show-content.blade.php`
- Test: `tests/Feature/Support/PlatformFeedbackUserFlowTest.php`
- Test: `tests/Feature/Support/PlatformFeedbackAuthorizationTest.php`

**Interfaces:**
- Consumes: Task 6 request, service, and policy.
- Produces: `feedback.create`, `feedback.store`, `feedback.index`, `feedback.show`, `feedback.attachment.show`, and `feedback.testimonial-consent.destroy`, plus connector-prefixed aliases that use the same actions and policies.

- [ ] **Step 1: Write failing user-flow tests**

Cover authenticated role access, guest redirect, successful submission/reference, private attachment, minor form without testimonial controls, adult form with separate permissions, paginated owner history, immutable detail, and consent withdrawal.

- [ ] **Step 2: Run user-flow tests and confirm failure**

Run:

```powershell
php artisan test tests/Feature/Support/PlatformFeedbackUserFlowTest.php tests/Feature/Support/PlatformFeedbackAuthorizationTest.php
```

Expected: FAIL because routes/controllers/views do not exist.

- [ ] **Step 3: Add authenticated route group and controller actions**

Register the exact routes from the spec under `middleware('auth')`. `store()` passes `$request->validated()`, `$request->file('attachment')`, and the request user-agent to the service. Redirect to `feedback.show` with:

```php
return redirect()
    ->route('feedback.show', $feedback)
    ->with('success', "Thanks for your feedback. Your reference is {$feedback->reference_number}.");
```

`index()` queries only `$request->user()->platformFeedbackSubmissions()` ordered newest first. `show()` and attachment delivery call `$this->authorize('view', $platformFeedback)`.

- [ ] **Step 4: Implement authorized private attachment delivery**

Abort 404 when `attachment_path` is null or missing from `local`. Return an inline response with a safe generated filename and `X-Content-Type-Options: nosniff`; never redirect to a storage URL.

- [ ] **Step 5: Implement consent withdrawal transaction**

Authorize owner/admin, then atomically set:

```php
[
    'testimonial_consent' => false,
    'testimonial_consent_withdrawn_at' => now(),
]
```

If a testimonial exists, set it to `withdrawn`, clear `published_at`, and set `withdrawn_at`. Return a success message without deleting private feedback.

- [ ] **Step 6: Build shared feedback views with safe role layouts**

Inject `SupportLayoutResolver` into the feedback controllers and pass `$supportLayout` to views. Each root feedback view begins with `@extends($supportLayout)` and includes its matching shared partial. Add connector-prefixed feedback aliases inside the authenticated connector route group; call `abortUnlessWorkspace()` through the resolver and pass connector route parameters through form actions, pagination links, detail links, attachment links, and redirects. The form must:

- Use `multipart/form-data`.
- Display privacy/sensitive-information guidance.
- Link safety concerns to existing reporting guidance.
- Show testimonial controls only when the controller-provided `$testimonialEligible` value from `TestimonialEligibility::isAdult($user)` is true.
- Require explicit separate checkboxes for role and profile-image display.
- Preserve non-file values after validation errors.
- State that an image must be selected again after validation failure.

- [ ] **Step 7: Run user-flow and authorization tests**

Run the Step 2 command. Expected: PASS.

- [ ] **Step 8: Local checkpoint**

Search feedback views for `{!!` and direct `/storage/` links. Expected: no matches for private feedback content or attachments.

---

### Task 8: Build admin feedback inbox, lifecycle, responses, and insights

**Files:**
- Create: `app/Http/Requests/Admin/IndexPlatformFeedbackRequest.php`
- Create: `app/Http/Requests/Admin/UpdatePlatformFeedbackRequest.php`
- Create: `app/Http/Controllers/Admin/PlatformFeedbackController.php`
- Create: `app/Services/Support/PlatformFeedbackInsights.php`
- Modify: `routes/admin.php`
- Create: `resources/views/admin/feedback/index.blade.php`
- Create: `resources/views/admin/feedback/show.blade.php`
- Create: `resources/views/admin/feedback/partials/filters.blade.php`
- Create: `resources/views/admin/feedback/partials/insights.blade.php`
- Test: `tests/Feature/Support/AdminPlatformFeedbackTest.php`
- Test: `tests/Unit/Support/PlatformFeedbackInsightsTest.php`

**Interfaces:**
- Consumes: `PlatformFeedback::forAdminFilters()` and policy from Tasks 2 and 6.
- Produces: `PlatformFeedbackInsights::summary(array $filters = []): array`.
- Produces: admin inbox/show/update route family.

- [ ] **Step 1: Write failing admin inbox tests**

Assert admin-only access, status/type/rating/date/search filters, pagination, stable status counts, private attachment visibility, immutable source content, internal-note update, status timestamps, and no notification on an unchanged update.

- [ ] **Step 2: Run admin inbox tests and confirm failure**

Run `php artisan test tests/Feature/Support/AdminPlatformFeedbackTest.php`.

Expected: FAIL because admin feedback routes do not exist.

- [ ] **Step 3: Implement index validation and controller queries**

Allow only enum-backed status/type, rating 1-5, valid dates, and a 100-character search string. `index()` returns filtered pagination plus unfiltered status counts so selecting a filter does not change sidebar totals.

- [ ] **Step 4: Implement lifecycle update semantics**

Validate:

```php
'status' => ['required', Rule::enum(PlatformFeedbackStatus::class)],
'internal_note' => ['nullable', 'string', 'max:10000'],
'staff_response' => ['nullable', 'string', 'max:10000'],
```

Set `reviewed_by` and `reviewed_at` when leaving `new`. Set `resolved_at` only for `resolved`; clear it when moving back to another active state. Detect changes with `wasChanged(['status', 'staff_response'])` for Task 9 notification dispatch. Never update subject, description, rating, or attachment fields.

- [ ] **Step 5: Write failing insights tests**

Seed dated feedback across types/statuses and assert exact totals, average rating excluding nulls, type/status group counts, monthly totals, monthly averages, and top affected paths.

- [ ] **Step 6: Run insights tests and confirm failure**

Run `php artisan test tests/Unit/Support/PlatformFeedbackInsightsTest.php`.

Expected: FAIL because the insights class does not exist.

- [ ] **Step 7: Implement native aggregate queries**

Return a stable array:

```php
[
    'total' => int,
    'new' => int,
    'unresolved' => int,
    'resolved' => int,
    'average_rating' => ?float,
    'by_type' => array,
    'by_status' => array,
    'monthly' => array,
    'top_affected_paths' => array,
]
```

Use database aggregate queries and fill absent enum buckets with zero. Treat `new`, `reviewed`, and `planned` as unresolved; exclude `archived` from unresolved.

- [ ] **Step 8: Build admin feedback views**

Use `layouts.admin`, compact insight cards, a filter bar, accessible status tabs, horizontally scrollable tables, stable action columns, and clear separation between `Private internal note` and `Response visible to user`.

- [ ] **Step 9: Run admin feedback and insights tests**

Run:

```powershell
php artisan test tests/Feature/Support/AdminPlatformFeedbackTest.php tests/Unit/Support/PlatformFeedbackInsightsTest.php
```

Expected: PASS.

- [ ] **Step 10: Local checkpoint**

Verify the admin update request exposes no source-content fields and no view renders private notes to the submitter.

---

### Task 9: Notify users about meaningful feedback updates

**Files:**
- Create: `app/Notifications/PlatformFeedbackUpdatedNotification.php`
- Modify: `app/Http/Controllers/Admin/PlatformFeedbackController.php`
- Test: `tests/Feature/Support/PlatformFeedbackNotificationTest.php`
- Modify: `tests/Feature/Notifications/NotificationDeepLinkRoutingTest.php`

**Interfaces:**
- Consumes: saved feedback plus old/new status and response values.
- Produces: database notification type `platform_feedback_updated` with a safe internal action URL.

- [ ] **Step 1: Write failing notification tests**

Use `Notification::fake()` to assert:

- Status change sends one notification.
- New/changed staff response sends one notification.
- Saving identical values sends none.
- Internal-note-only changes send none.
- Payload action URL is `route('feedback.show', $feedback)`.
- Existing notification deep-link logic accepts the internal feedback URL and rejects an external URL.

- [ ] **Step 2: Run notification tests and confirm failure**

Run:

```powershell
php artisan test tests/Feature/Support/PlatformFeedbackNotificationTest.php tests/Feature/Notifications/NotificationDeepLinkRoutingTest.php
```

Expected: support notification test FAIL because the class is missing; existing deep-link tests remain green.

- [ ] **Step 3: Implement database notification payload**

Use the existing notification payload keys:

```php
return [
    'type' => 'platform_feedback_updated',
    'status' => $status->value,
    'title' => 'Your Feedback Was Updated',
    'message' => $message,
    'feedback_id' => $this->feedback->id,
    'reference_number' => $this->feedback->reference_number,
    'action_url' => route('feedback.show', $this->feedback),
    'severity' => $status === PlatformFeedbackStatus::Resolved ? 'success' : 'info',
];
```

Use `via(): ['database']`. Keep the message plain and exclude private notes.

- [ ] **Step 4: Dispatch only after successful update**

Capture the original status/response, persist the update, then notify once when either user-visible value changed. Do not dispatch from a model observer; explicit controller/service dispatch keeps the trigger auditable.

- [ ] **Step 5: Run notification tests**

Run the Step 2 command. Expected: PASS.

- [ ] **Step 6: Local checkpoint**

Inspect the serialized payload and confirm it contains no description, attachment path, internal note, birthdate, or sensitive metadata.

---

### Task 10: Implement testimonial eligibility, publication, withdrawal, and admin UI

**Files:**
- Create: `app/Services/Support/TestimonialPublicationService.php`
- Create: `app/Http/Requests/Admin/StoreTestimonialRequest.php`
- Create: `app/Http/Requests/Admin/UpdateTestimonialRequest.php`
- Create: `app/Http/Controllers/Admin/TestimonialController.php`
- Modify: `routes/admin.php`
- Create: `resources/views/admin/testimonials/index.blade.php`
- Create: `resources/views/admin/testimonials/form.blade.php`
- Test: `tests/Unit/Support/TestimonialPublicationServiceTest.php`
- Test: `tests/Feature/Support/AdminTestimonialTest.php`

**Interfaces:**
- Consumes: `PlatformFeedback` consent fields and `TestimonialEligibility::isAdult()` from Task 6.
- Produces: `TestimonialPublicationService::draftFromFeedback(PlatformFeedback,User,array): Testimonial`, `publish(Testimonial,User): Testimonial`, and `withdraw(Testimonial,User): Testimonial`.
- Produces: `admin.testimonials.*` route family.

- [ ] **Step 1: Write failing publication-service tests**

Cover rejection for minors, missing consent, withdrawn consent, missing display name, unreviewed feedback, disallowed role/photo display, successful draft creation, re-check on publish, public ordering, and withdrawal.

- [ ] **Step 2: Run service tests and confirm failure**

Run `php artisan test tests/Unit/Support/TestimonialPublicationServiceTest.php`.

Expected: FAIL because the publication service does not exist.

- [ ] **Step 3: Implement centralized eligibility checks**

Use a private guard called by both draft and publish:

```php
private function assertEligible(PlatformFeedback $feedback): void
{
    $feedback->loadMissing('user');

    throw_unless($this->eligibility->isAdult($feedback->user), DomainException::class, 'Verified adult eligibility is required.');
    throw_unless($feedback->testimonial_consent, DomainException::class, 'Active testimonial consent is required.');
    throw_if($feedback->testimonial_consent_withdrawn_at !== null, DomainException::class, 'Testimonial consent was withdrawn.');
    throw_if(blank($feedback->testimonial_display_name), DomainException::class, 'A public display name is required.');
    throw_if($feedback->reviewed_at === null, DomainException::class, 'Feedback must be reviewed first.');
}
```

When role or image permission is false, force `display_role` null or `show_profile_image` false regardless of submitted admin values. `draftFromFeedback()` uses `updateOrCreate(['platform_feedback_id' => $feedback->id], ...)` to preserve the one-to-one rule.

- [ ] **Step 4: Write failing admin testimonial tests**

Assert admin-only access, candidate list excludes minors/non-consenters/withdrawn consent, quotation and ordering validation, publish/withdraw actions, and source feedback immutability.

- [ ] **Step 5: Run admin testimonial tests and confirm failure**

Run `php artisan test tests/Feature/Support/AdminTestimonialTest.php`.

Expected: FAIL because routes/controller/views do not exist.

- [ ] **Step 6: Implement admin requests, controller, and views**

Validate:

```php
'display_name' => ['required', 'string', 'max:100'],
'display_role' => ['nullable', 'string', 'max:60'],
'quotation' => ['required', 'string', 'max:1000'],
'show_profile_image' => ['nullable', 'boolean'],
'sort_order' => ['required', 'integer', 'min:0'],
```

Candidate feedback query requires active consent, no withdrawal, non-null reviewed timestamp, non-empty display name, and an adult user verified again in application logic. The admin UI shows the immutable source beside the proposed excerpt and a warning not to change its meaning.

- [ ] **Step 7: Run testimonial tests**

Run:

```powershell
php artisan test tests/Unit/Support/TestimonialPublicationServiceTest.php tests/Feature/Support/AdminTestimonialTest.php
```

Expected: PASS.

- [ ] **Step 8: Local checkpoint**

Search for testimonial queries outside the `publiclyVisible()` scope and document each intentional admin-only exception in code comments.

---

### Task 11: Add landing testimonials and cross-role navigation

**Files:**
- Modify: `routes/web.php:100-180`
- Modify: `resources/views/landing/index.blade.php:560-655`
- Modify: `resources/views/layouts/learner-sidebar.blade.php:100-210`
- Modify: `resources/views/layouts/navigation.blade.php:1-120`
- Modify: `resources/views/layouts/instructor-app.blade.php:150-215`
- Modify: `resources/views/layouts/connector-app.blade.php:60-100`
- Modify: `resources/views/layouts/admin.blade.php:150-710`
- Test: `tests/Feature/Support/PublicTestimonialTest.php`
- Test: `tests/Feature/Support/SupportNavigationTest.php`

**Interfaces:**
- Consumes: `Testimonial::publiclyVisible()` and all support named routes.
- Produces: public testimonial section and discoverable Help/Feedback navigation.

- [ ] **Step 1: Write failing public testimonial tests**

Seed published eligible, draft, withdrawn, minor, and consent-withdrawn records. Assert only eligible published testimonials appear, order uses `sort_order` then `published_at`, display-role/photo permissions are honored, and the landing page caps results at six.

- [ ] **Step 2: Run the testimonial test and confirm failure**

Run `php artisan test tests/Feature/Support/PublicTestimonialTest.php`.

Expected: FAIL because the landing page does not query/render testimonials.

- [ ] **Step 3: Supply testimonials to the landing page**

Replace the current home closure with a small controller or extend it only if it remains readable. Query:

```php
$testimonials = Testimonial::query()
    ->publiclyVisible()
    ->with('user')
    ->orderBy('sort_order')
    ->orderByDesc('published_at')
    ->get()
    ->filter(fn (Testimonial $testimonial): bool => $eligibility->isAdult($testimonial->user))
    ->take(6)
    ->values();
```

Render a responsive, non-carousel `What Our Community Says` grid before the final landing CTA/footer. Escape quotation and display text. Use an initial avatar unless image permission is active and an existing safe profile image can be resolved.

- [ ] **Step 4: Write failing navigation tests**

Assert:

- Public footer includes `Help Center`.
- Learner/parent, instructor, connector, and admin shells include a named Help Center link.
- Authenticated support/account surfaces include `My Feedback`.
- Admin has a `SUPPORT` group linking Help Articles, User Feedback, and Testimonials.
- Connector Help links preserve the connector route parameter.
- Feedback is not added as a separate primary item to every sidebar.

- [ ] **Step 5: Run navigation tests and confirm failure**

Run `php artisan test tests/Feature/Support/SupportNavigationTest.php`.

Expected: FAIL because links are absent.

- [ ] **Step 6: Add navigation links using named routes**

Follow each shell's existing item-array or inline-link pattern. Use literal active-state checks such as `request()->routeIs('help.*')` and `request()->routeIs('admin.help.*')`. Preserve existing mobile/collapsed sidebar behavior and data attributes.

- [ ] **Step 7: Run landing and navigation tests**

Run:

```powershell
php artisan test tests/Feature/Support/PublicTestimonialTest.php tests/Feature/Support/SupportNavigationTest.php
```

Expected: PASS.

- [ ] **Step 8: Local checkpoint**

Inspect only the relevant nav blocks and landing insertion. Confirm no existing route, label, or Community Hub action was removed.

---

### Task 12: Complete accessibility, safety, and regression verification

**Files:**
- Modify only files identified by failing verification from Tasks 1-11.
- Test: all `tests/Feature/Support/*`
- Test: all `tests/Unit/Support/*`
- Test: existing feedback, notification, landing, and Community Hub suites.

**Interfaces:**
- Consumes: all prior tasks.
- Produces: verified implementation with no known spec gaps.

- [ ] **Step 1: Run the complete support test group**

Run:

```powershell
php artisan test tests/Feature/Support tests/Unit/Support
```

Expected: all tests PASS.

- [ ] **Step 2: Run adjacent regression tests**

Run:

```powershell
php artisan test tests/Feature/Learner/ModuleFeedbackFlowTest.php tests/Feature/Learner/ModuleAndInstructorFeedbackSeparationTest.php tests/Feature/Notifications/NotificationDeepLinkRoutingTest.php tests/Feature/LandingApkDownloadTest.php tests/Feature/Community
```

Expected: all tests PASS. If a failure appears, invoke `superpowers:systematic-debugging` before editing and fix only regressions caused by this work.

- [ ] **Step 3: Verify named routes**

Run:

```powershell
php artisan route:list --name=help
php artisan route:list --name=feedback
php artisan route:list --name=admin.help
php artisan route:list --name=admin.feedback
php artisan route:list --name=admin.testimonials
```

Expected: every route named in the spec exists with the intended auth/admin middleware.

- [ ] **Step 4: Run PHP formatting**

Run:

```powershell
vendor\bin\pint.bat --dirty
```

Review formatter changes and ensure it did not touch unrelated user files.

- [ ] **Step 5: Build frontend assets**

Run:

```powershell
npm.cmd run build
```

Expected: Vite build succeeds. Keep generated `public/build` changes separate from source changes during review.

- [ ] **Step 6: Perform browser checks**

Check desktop and mobile widths for:

1. Guest Help Center search, category, article, and feedback sign-in CTA.
2. Adult learner feedback submission, history, attachment, and consent withdrawal.
3. Minor learner feedback submission without testimonial controls.
4. Instructor Help Center shell and feedback history.
5. Connector-scoped Help Center navigation and connector context.
6. Admin Help CRUD, feedback triage, response, insights, and testimonial publication.
7. Public landing page with only eligible testimonials.

Verify keyboard focus, heading order, form labels, image alternative text, table scrolling, empty states, validation messages, and dark-mode behavior on learner surfaces.

- [ ] **Step 7: Run final safety searches**

Run:

```powershell
rg -n "\{!!" resources/views/help resources/views/feedback resources/views/admin/help resources/views/admin/feedback resources/views/admin/testimonials
rg -n "Storage::disk\('public'\).*platform-feedback|/storage/.*platform-feedback" app resources/views
rg -n "testimonial" app/Http/Controllers app/Services/Support | rg -n "TestimonialEligibility|testimonial_consent|withdrawn"
```

Expected: no unescaped support content, no public feedback attachment paths, and centralized testimonial eligibility checks are present.

- [ ] **Step 8: Review task-owned diff**

Run `git status --short`, `git diff --check`, and scoped `git diff --` commands for support files. Confirm `.claude/` and unrelated files were not changed. Do not stage, commit, push, merge, or pull.

- [ ] **Step 9: Report completion evidence**

Report exact test counts, build result, route verification, browser roles checked, created/modified paths, and any pre-existing failures separately. State explicitly that work remains local and uncommitted on `community-feed-v1`.
