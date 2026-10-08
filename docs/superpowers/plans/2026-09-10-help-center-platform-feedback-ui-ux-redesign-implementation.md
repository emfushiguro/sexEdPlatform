# Help Center and Platform Feedback UI/UX Redesign Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Deliver one coherent, accessible Support workspace for Help Center discovery, article reading, Platform Feedback submission and history, plus consistent platform-admin support management screens.

**Architecture:** Centralize global-versus-connector route selection in a small `SupportRouteContext` service, then render shared Blade components for support navigation, feedback status, and feedback type. Keep the existing controllers, policies, requests, services, models, and role layouts authoritative while adding only bounded presentation queries and user-owned history filters.

**Tech Stack:** Laravel, PHP 8.2+, Blade, Tailwind CSS, Alpine.js, PHPUnit/Laravel feature tests, Vite.

**Spec:** `docs/superpowers/specs/2026-09-10-help-center-platform-feedback-ui-ux-redesign.md`

## Global Constraints

- Work only in the existing `community-feed-v1` checkout; do not create a worktree or switch branches.
- Do not pull, merge, commit, or push unless the user separately requests it.
- Preserve unrelated dirty files and inspect only task-owned diffs.
- Preserve all existing route names, middleware, authorization policies, throttling, idempotency, MIME/content validation, and testimonial-consent rules.
- Keep Platform Feedback separate from moderation and unsafe-content reports.
- Use the same `help` and `feedback` SVGs from `x-ui.support-icon` in every role shell and Support workspace heading.
- Use Poppins/Figtree through `font-sans`, the existing brand colors, literal Tailwind classes, and existing Blade/Alpine conventions.
- Learner pages support light and dark themes; instructor and admin pages remain light-mode operational workspaces.
- Never show raw enum values, internal notes to end users, storage paths, or another user's submission.
- Every task starts with a failing focused test and ends with focused verification plus `git diff --check`.

## File Structure

### Shared support foundation

- Create `app/Services/Support/SupportRouteContext.php`: return one stable route-definition array for global or connector context.
- Create `resources/views/components/support/navigation.blade.php`: render Help Center, Send Feedback, and My Feedback tabs from `supportRoutes`.
- Create `resources/views/components/support/page-header.blade.php`: provide the shared icon/title/description/action rhythm for authenticated support pages.
- Create `resources/views/components/support/category-icon.blade.php`: map the 13 validated Help Center category icon keys to stable outline SVGs.
- Create `resources/views/components/support/status-badge.blade.php`: map `PlatformFeedbackStatus` to label and literal Tailwind classes.
- Create `resources/views/components/support/type-icon.blade.php`: render stable outline icons for each `PlatformFeedbackType`.
- Preserve `resources/views/components/ui/support-icon.blade.php`: reuse its current `help` and `feedback` geometries without duplicating SVG markup.
- Modify `app/Http/Controllers/HelpCenterController.php`: inject shared route context and pass `supportRoutes`.
- Modify `app/Http/Controllers/PlatformFeedbackController.php`: inject shared route context and pass `supportRoutes`.

### Help Center

- Modify `resources/views/help/index.blade.php`: compact search banner, recommendations, category counts, popular list, results state, safety notice, feedback CTA.
- Modify `resources/views/help/partials/search-results.blade.php`: divided result list and actionable empty state.
- Modify `resources/views/help/show.blade.php`: article metadata, section anchors, responsive contents navigation, selected helpfulness state, contextual feedback link.
- Modify `app/Http/Controllers/HelpCenterController.php`: recommendation query, visible article counts, reading time, and section anchors.

### Platform Feedback

- Modify `resources/views/feedback/create.blade.php`: labelled progressive form, radio tiles, inline errors, upload preview, adult-only testimonial disclosure, preserved old input.
- Modify `resources/views/feedback/index.blade.php`: All/Active/Closed filtering, divided rows, badges, and empty states.
- Modify `resources/views/feedback/show.blade.php`: success banner, metadata, lifecycle indicator, response, attachment, and consent controls.
- Modify `app/Http/Controllers/PlatformFeedbackController.php`: normalize prefill input and apply owned history filters.
- Modify `app/Http/Requests/StorePlatformFeedbackRequest.php` only if normalization requires request preparation; final validation remains `regex:/^\//` and `max:500`.

### Admin support management

- Modify `resources/views/admin/help/articles/index.blade.php` and `resources/views/admin/help/categories/index.blade.php`: shared management tabs, filters, tables, labels, and empty states.
- Modify `resources/views/admin/help/articles/form.blade.php` and category form/preview views: grouped accessible fields and clearer publishing actions.
- Modify `resources/views/admin/feedback/index.blade.php` and `show.blade.php`: complete labelled filters, human-readable types, structured review panel, and correct attachment route.
- Modify `resources/views/admin/testimonials/index.blade.php` and `edit.blade.php`: consent-aware rows, clear publication actions, and labelled fields.
- Modify `routes/admin.php`: add the explicit policy-protected admin feedback attachment route.
- Modify admin controllers only for presentation data or existing filter support; do not change lifecycle or publication ownership.

### Tests

- Create `tests/Feature/Support/SupportWorkspaceUiTest.php`: route-context, shared tabs, enum presentation, and cross-role contracts.
- Create `tests/Feature/Support/HelpCenterExperienceTest.php`: category counts, contextual links, recommendation, article metadata, and feedback prefill.
- Create `tests/Feature/Support/PlatformFeedbackUiTest.php`: form accessibility, prefill normalization, preserved choices, history filters, and detail states.
- Extend `tests/QA/SupportUiQaTest.php`: admin labels, filters, enum labels, and user-facing controls.
- Preserve and rerun all existing tests under `tests/Feature/Support` and `tests/Unit/Support`.

---

### Task 1: Shared Support Route Context and UI Primitives

**Files:**

- Create: `app/Services/Support/SupportRouteContext.php`
- Create: `resources/views/components/support/navigation.blade.php`
- Create: `resources/views/components/support/page-header.blade.php`
- Create: `resources/views/components/support/category-icon.blade.php`
- Create: `resources/views/components/support/status-badge.blade.php`
- Create: `resources/views/components/support/type-icon.blade.php`
- Preserve: `resources/views/components/ui/support-icon.blade.php`
- Modify: `app/Http/Controllers/HelpCenterController.php`
- Modify: `app/Http/Controllers/PlatformFeedbackController.php`
- Create: `tests/Feature/Support/SupportWorkspaceUiTest.php`

**Interfaces:**

- Consumes: existing global and `connector.*` route names and optional `App\Models\Connector`.
- Produces: `SupportRouteContext::for(?Connector $connector): array` with `help.index`, `help.show`, `feedback.create`, `feedback.store`, `feedback.index`, `feedback.show`, `feedback.attachment`, and `feedback.withdraw` entries; each entry contains `name` and `parameters`.
- Produces: `<x-support.navigation :routes="$supportRoutes" />`, `<x-support.page-header icon="feedback" title="Send feedback" description="Tell us how the platform can work better for you." />`, `<x-support.category-icon :name="$category->icon_key" />`, `<x-support.status-badge :status="$status" />`, and `<x-support.type-icon :type="$type" />`.

- [ ] **Step 1: Write failing route-context and shared-tab tests**

Add tests that assert the exact global and connector route names/parameters and that authenticated Help Center and feedback pages render all three workspace links.

```php
public function test_route_context_preserves_connector_scope(): void
{
    $connector = Connector::factory()->make(['id' => 42]);
    $routes = app(SupportRouteContext::class)->for($connector);

    $this->assertSame('connector.help.index', $routes['help']['index']['name']);
    $this->assertSame(['connector' => $connector], $routes['help']['index']['parameters']);
    $this->assertSame('connector.feedback.create', $routes['feedback']['create']['name']);
    $this->assertSame('connector.feedback.index', $routes['feedback']['index']['name']);
}

public function test_authenticated_help_renders_the_support_workspace_tabs(): void
{
    $user = User::factory()->create(['role' => 'learner']);

    $this->actingAs($user)->get(route('help.index'))
        ->assertOk()
        ->assertSee('data-support-workspace-nav', false)
        ->assertSee(route('help.index'), false)
        ->assertSee(route('feedback.create'), false)
        ->assertSee(route('feedback.index'), false);
}
```

- [ ] **Step 2: Run the new tests and confirm the missing service/navigation failure**

Run:

```powershell
php vendor/phpunit/phpunit/phpunit tests/Feature/Support/SupportWorkspaceUiTest.php
```

Expected: FAIL because `SupportRouteContext` and the workspace navigation do not exist.

- [ ] **Step 3: Implement `SupportRouteContext`**

Use one stable shape and keep route parameters as model objects so Laravel URL generation matches current behavior.

```php
final class SupportRouteContext
{
    public function for(?Connector $connector = null): array
    {
        $parameters = $connector ? ['connector' => $connector] : [];
        $prefix = $connector ? 'connector.' : '';

        return [
            'help' => [
                'index' => ['name' => $prefix.'help.index', 'parameters' => $parameters],
                'show' => ['name' => $prefix.'help.show', 'parameters' => $parameters],
            ],
            'feedback' => [
                'create' => ['name' => $prefix.'feedback.create', 'parameters' => $parameters],
                'store' => ['name' => $prefix.'feedback.store', 'parameters' => $parameters],
                'index' => ['name' => $prefix.'feedback.index', 'parameters' => $parameters],
                'show' => ['name' => $prefix.'feedback.show', 'parameters' => $parameters],
                'attachment' => ['name' => $prefix.'feedback.attachment.show', 'parameters' => $parameters],
                'withdraw' => ['name' => $prefix.'feedback.testimonial-consent.destroy', 'parameters' => $parameters],
            ],
        ];
    }
}
```

Inject this service into both public controllers, remove their duplicated private route maps, and pass `supportRoutes`. During the same task, update every support view reference from `$helpRoutes` or `$feedbackRoutes` to the appropriate nested key under `$supportRoutes`; do not retain two competing route-context shapes.

- [ ] **Step 4: Implement shared navigation and enum presentation components**

The navigation uses route definitions, explicit labels, the shared SVGs, and route-family active checks.

```blade
@props(['routes'])
@auth
<nav data-support-workspace-nav aria-label="Support pages" class="overflow-x-auto">
    <div class="inline-flex min-w-full gap-1 rounded-xl border border-purple-100 bg-white p-1 shadow-sm sm:min-w-0">
        <a href="{{ route($routes['help']['index']['name'], $routes['help']['index']['parameters']) }}"
           @class(['min-h-11 inline-flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-semibold focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500', 'bg-brand-700 text-white' => request()->routeIs('help.*', 'connector.help.*'), 'text-gray-600 hover:bg-purple-50 hover:text-brand-800' => ! request()->routeIs('help.*', 'connector.help.*')])
           @if(request()->routeIs('help.*', 'connector.help.*')) aria-current="page" @endif>
            <x-ui.support-icon name="help" class="h-4 w-4 shrink-0" />
            <span>Help Center</span>
        </a>
        <a href="{{ route($routes['feedback']['create']['name'], $routes['feedback']['create']['parameters']) }}"
           @class(['min-h-11 inline-flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-semibold focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500', 'bg-brand-700 text-white' => request()->routeIs('feedback.create', 'feedback.create.legacy', 'connector.feedback.create', 'connector.feedback.create.legacy'), 'text-gray-600 hover:bg-purple-50 hover:text-brand-800' => ! request()->routeIs('feedback.create', 'feedback.create.legacy', 'connector.feedback.create', 'connector.feedback.create.legacy')])
           @if(request()->routeIs('feedback.create', 'feedback.create.legacy', 'connector.feedback.create', 'connector.feedback.create.legacy')) aria-current="page" @endif>
            <x-ui.support-icon name="feedback" class="h-4 w-4 shrink-0" />
            <span>Send Feedback</span>
        </a>
        <a href="{{ route($routes['feedback']['index']['name'], $routes['feedback']['index']['parameters']) }}"
           @class(['min-h-11 inline-flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-semibold focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500', 'bg-brand-700 text-white' => request()->routeIs('feedback.index', 'feedback.show', 'feedback.attachment.show', 'connector.feedback.index', 'connector.feedback.show', 'connector.feedback.attachment.show'), 'text-gray-600 hover:bg-purple-50 hover:text-brand-800' => ! request()->routeIs('feedback.index', 'feedback.show', 'feedback.attachment.show', 'connector.feedback.index', 'connector.feedback.show', 'connector.feedback.attachment.show')])
           @if(request()->routeIs('feedback.index', 'feedback.show', 'feedback.attachment.show', 'connector.feedback.index', 'connector.feedback.show', 'connector.feedback.attachment.show')) aria-current="page" @endif>
            <x-ui.support-icon name="feedback" class="h-4 w-4 shrink-0" />
            <span>My Feedback</span>
        </a>
    </div>
</nav>
@endauth
```

Create `page-header.blade.php` with `icon`, `title`, `description`, and optional `eyebrow` props plus an optional `action` slot. Map the validated category keys `rocket`, `account`, `book`, `quiz`, `seminar`, `community`, `guardian`, `instructor`, `connector`, `payment`, `shield`, `accessibility`, and `tools` in `category-icon.blade.php`, falling back to `tools` only for null. Use enum `match` expressions with literal Tailwind classes in the badge/type components.

- [ ] **Step 5: Run focused navigation and existing sidebar tests**

Run:

```powershell
php vendor/phpunit/phpunit/phpunit tests/Feature/Support/SupportWorkspaceUiTest.php tests/Feature/Support/SupportSidebarNavigationTest.php
git diff --check
```

Expected: PASS; existing sidebar icon canonicalization remains unchanged.

---

### Task 2: Help Center Landing, Search, and Category Experience

**Files:**

- Modify: `app/Http/Controllers/HelpCenterController.php`
- Modify: `resources/views/help/index.blade.php`
- Modify: `resources/views/help/partials/search-results.blade.php`
- Create: `tests/Feature/Support/HelpCenterExperienceTest.php`

**Interfaces:**

- Consumes: `supportRoutes`, `HelpAudienceResolver`, published article/category scopes.
- Produces: `recommendedArticles`, `categories[*].visible_articles_count`, existing paginated `articles`, `search`, and `categorySlug` view data.

- [ ] **Step 1: Write failing landing-page tests**

Cover role-relevant recommendations, visible category counts, contextual connector category links, a clear search state, and the empty-result actions.

```php
public function test_category_link_and_count_preserve_connector_context(): void
{
    $this->seedCaviteAddress();
    $owner = User::factory()->create([
        'role' => 'learner',
        'account_type' => 'learner-adult',
        'age' => 25,
        'birthdate' => now()->subYears(25),
    ]);
    $owner->assignRole('learner');
    $connector = $this->createVerifiedConnector($owner);
    $category = HelpCategory::factory()->create(['slug' => 'learning', 'audiences' => ['all']]);
    HelpArticle::factory()->for($category, 'category')->create([
        'status' => HelpArticleStatus::Published,
        'published_at' => now(),
        'audiences' => ['all'],
    ]);

    $this->actingAs($owner)
        ->get(route('connector.help.index', $connector))
        ->assertOk()
        ->assertSee(route('connector.help.index', ['connector' => $connector, 'category' => 'learning']), false)
        ->assertSee('1 guide');
}
```

- [ ] **Step 2: Run the Help Center experience test and confirm it fails**

Run:

```powershell
php vendor/phpunit/phpunit/phpunit tests/Feature/Support/HelpCenterExperienceTest.php
```

Expected: FAIL because counts, recommendations, and contextual category URLs are not rendered.

- [ ] **Step 3: Add bounded presentation queries**

Use the same audience constraints for category counts and recommendations.

```php
$categories = HelpCategory::query()
    ->visibleToAudience($audience)
    ->withCount(['articles as visible_articles_count' => fn (Builder $query) => $query
        ->where('status', HelpArticleStatus::Published)
        ->whereNotNull('published_at')
        ->where(fn (Builder $audiences) => $audiences
            ->whereJsonContains('audiences', 'all')
            ->orWhereJsonContains('audiences', $audience))])
    ->orderBy('sort_order')
    ->orderBy('name')
    ->get();

$recommendedArticles = HelpArticle::query()
    ->publishedForAudience($audience)
    ->with('category')
    ->orderBy('sort_order')
    ->orderBy('title')
    ->limit(4)
    ->get();
```

Avoid selecting a separate recommendation group during an active search or category filter.

- [ ] **Step 4: Replace the landing page with the approved hierarchy**

Render, in order: shared navigation for authenticated users, compact gradient search banner, role recommendation list, category grid with icon/count, popular divided list, amber safety notice, and feedback CTA. Use the contextual route definition for category links:

```blade
<a href="{{ route($supportRoutes['help']['index']['name'], array_merge($supportRoutes['help']['index']['parameters'], ['category' => $category->slug])) }}">
    <span>{{ $category->name }}</span>
    <span>{{ $category->visible_articles_count }} {{ Str::plural('guide', $category->visible_articles_count) }}</span>
</a>
```

Search results must show result count and clear action. The no-results view must offer Browse all guides and, for authenticated users, Send Feedback.

- [ ] **Step 5: Run public, role-aware, and landing tests**

Run:

```powershell
php vendor/phpunit/phpunit/phpunit tests/Feature/Support/HelpCenterExperienceTest.php tests/Feature/Support/PublicHelpCenterTest.php tests/Feature/Support/RoleAwareHelpCenterTest.php
git diff --check
```

Expected: PASS with no audience leakage and connector links preserved.

---

### Task 3: Help Article Reading Experience and Contextual Feedback

**Files:**

- Modify: `app/Http/Controllers/HelpCenterController.php`
- Modify: `resources/views/help/show.blade.php`
- Modify: `tests/Feature/Support/HelpCenterExperienceTest.php`
- Preserve: `tests/Feature/Support/HelpArticleHelpfulnessTest.php`

**Interfaces:**

- Consumes: loaded article sections and `supportRoutes`.
- Produces: `articleSections` entries with `model`, `anchor`, and `number`; integer `readingMinutes`; internal-path feedback URL with `type=help_content_issue` and `affected_path`.

- [ ] **Step 1: Add failing article-presentation tests**

```php
public function test_article_has_contents_metadata_and_contextual_feedback_link(): void
{
    $user = User::factory()->create(['role' => 'learner']);
    $category = HelpCategory::factory()->create();
    $article = HelpArticle::factory()->for($category, 'category')->create([
        'status' => HelpArticleStatus::Published,
        'published_at' => now(),
        'audiences' => ['all'],
    ]);
    foreach (['Open dashboard', 'Choose a module'] as $index => $heading) {
        HelpArticleSection::query()->create([
            'help_article_id' => $article->id,
            'heading' => $heading,
            'body' => str_repeat('Helpful guide text ', 40),
            'sort_order' => $index,
        ]);
    }

    $this->actingAs($user)->get(route('help.show', $article->slug))
        ->assertOk()
        ->assertSee('On this page')
        ->assertSee('Last updated')
        ->assertSee('min read')
        ->assertSee(route('feedback.create', [
            'type' => 'help_content_issue',
            'affected_path' => route('help.show', $article->slug, false),
        ]), false);
}
```

- [ ] **Step 2: Run the article test and confirm it fails**

Run:

```powershell
php vendor/phpunit/phpunit/phpunit tests/Feature/Support/HelpCenterExperienceTest.php --filter article
```

Expected: FAIL because article metadata, contents navigation, and prefill URL are absent.

- [ ] **Step 3: Prepare stable article presentation data**

Build unique anchors from position plus slug so repeated headings cannot collide, and compute reading time from body text.

```php
$articleSections = $article->sections->values()->map(fn ($section, int $index) => [
    'model' => $section,
    'number' => $index + 1,
    'anchor' => 'section-'.($index + 1).'-'.Str::slug($section->heading ?: 'guide'),
]);
$wordCount = $article->sections->sum(fn ($section) => str_word_count(strip_tags($section->body)));
$readingMinutes = max(1, (int) ceil($wordCount / 200));
$affectedPath = route($supportRoutes['help']['show']['name'], array_merge(
    $supportRoutes['help']['show']['parameters'],
    ['helpArticle' => $article->slug]
), false);
```

- [ ] **Step 4: Implement the responsive article layout**

Use breadcrumb, category, title, summary, reading time, updated date, main article, desktop sticky contents, mobile `<details>`, escaped bodies, images with alt text, and related divided list. Render helpfulness controls with selected styling tied to the same expression used for `aria-pressed`.

```blade
<button type="submit"
        aria-pressed="{{ $currentVote === true || $currentVote === 1 ? 'true' : 'false' }}"
        @class(['min-h-11 rounded-xl border px-4 py-2 text-sm font-semibold focus-visible:ring-2 focus-visible:ring-brand-500', 'border-brand-700 bg-brand-700 text-white' => $currentVote === true || $currentVote === 1, 'border-purple-200 text-purple-800 hover:bg-purple-50' => ! ($currentVote === true || $currentVote === 1)])>
    Yes
</button>
```

- [ ] **Step 5: Run article and helpfulness tests**

Run:

```powershell
php vendor/phpunit/phpunit/phpunit tests/Feature/Support/HelpCenterExperienceTest.php tests/Feature/Support/HelpArticleHelpfulnessTest.php
git diff --check
```

Expected: PASS; hidden/draft articles remain inaccessible.

---

### Task 4: Accessible Platform Feedback Form and Safe Prefill

**Files:**

- Modify: `app/Http/Controllers/PlatformFeedbackController.php`
- Modify: `resources/views/feedback/create.blade.php`
- Create: `tests/Feature/Support/PlatformFeedbackUiTest.php`
- Extend: `tests/QA/SupportUiQaTest.php`
- Preserve: `tests/Feature/Support/PlatformFeedbackValidationTest.php`

**Interfaces:**

- Consumes: query `type` and `affected_path`, authenticated user, `testimonialEligible`, `supportRoutes`, and existing `StorePlatformFeedbackRequest` validation.
- Produces: `feedbackTypes`, `selectedType`, and `affectedPath` view values; posts the unchanged storage payload and submission token.

- [ ] **Step 1: Write failing form-contract and normalization tests**

Test visible labels, inline error associations, every old-input choice, adult/minor testimonial visibility, and safe query prefill.

```php
public function test_feedback_prefill_accepts_only_an_internal_path_without_query_or_fragment(): void
{
    $user = User::factory()->create(['role' => 'learner', 'age' => 25, 'birthdate' => now()->subYears(25)]);

    $this->actingAs($user)->get(route('feedback.create', [
        'type' => 'help_content_issue',
        'affected_path' => '/help/example?token=secret#private',
    ]))->assertOk()
        ->assertSee('value="help_content_issue"', false)
        ->assertSee('value="/help/example"', false)
        ->assertDontSee('token=secret');

    $this->get(route('feedback.create', ['affected_path' => 'https://example.com/private']))
        ->assertOk()
        ->assertDontSee('https://example.com/private');
}
```

- [ ] **Step 2: Run form UI and validation tests and confirm the expected failures**

Run:

```powershell
php vendor/phpunit/phpunit/phpunit tests/Feature/Support/PlatformFeedbackUiTest.php tests/QA/SupportUiQaTest.php --filter "ui0[123]"
```

Expected: FAIL on labels, field-associated errors, preserved controls, and safe prefill.

- [ ] **Step 3: Normalize optional GET prefill in the controller**

Accept only an enum value and a path beginning with one slash. Strip query and fragment before limiting to 500 characters.

```php
$requestedType = (string) $request->query('type', '');
$selectedType = in_array($requestedType, PlatformFeedbackType::values(), true)
    ? $requestedType
    : PlatformFeedbackType::General->value;
$rawPath = trim((string) $request->query('affected_path', ''));
$pathOnly = str_starts_with($rawPath, '/') && ! str_starts_with($rawPath, '//')
    ? (parse_url($rawPath, PHP_URL_PATH) ?: '')
    : '';
$affectedPath = mb_substr($pathOnly, 0, 500);
```

Pass enum cases and these defaults to the view; `old()` always takes priority.

- [ ] **Step 4: Replace the form with accessible progressive sections**

Use an Alpine root for selected type, character count, submission state, testimonial disclosure, and browser-local attachment preview. Each field receives an ID, visible label/legend, helper text, `aria-invalid`, and `aria-describedby` containing helper and error IDs.

```blade
<fieldset aria-describedby="type-help @error('type') type-error @enderror">
    <legend class="text-base font-semibold text-gray-900 dark:text-white">What would you like to share?</legend>
    <p id="type-help" class="mt-1 text-sm text-gray-500 dark:text-gray-400">Choose the option that best matches your feedback.</p>
    <div class="mt-4 grid gap-3 sm:grid-cols-2">
        @foreach($feedbackTypes as $type)
            <label class="min-h-11 cursor-pointer rounded-xl border p-4 focus-within:ring-2 focus-within:ring-brand-500">
                <input type="radio" name="type" value="{{ $type->value }}" class="sr-only" @checked(old('type', $selectedType) === $type->value)>
                <x-support.type-icon :type="$type" class="h-5 w-5" />
                <span>{{ $type->label() }}</span>
            </label>
        @endforeach
    </div>
    @error('type')<p id="type-error" class="mt-2 text-sm text-rose-700">{{ $message }}</p>@enderror
</fieldset>
```

Implement the rating as five native radios, attachment as a labelled file input with preview/remove, contact permission as an unchecked checkbox, and testimonial fields inside an adult-only disclosure. Every testimonial checkbox uses `@checked(old(...))`.

- [ ] **Step 5: Preserve submission behavior and run form suites**

Run:

```powershell
php vendor/phpunit/phpunit/phpunit tests/Feature/Support/PlatformFeedbackUiTest.php tests/Feature/Support/PlatformFeedbackValidationTest.php tests/Unit/Support/PlatformFeedbackSubmissionServiceTest.php tests/QA/SupportUiQaTest.php --filter "ui0[123]"
git diff --check
```

Expected: PASS; invalid data is rejected, old values remain visible, and minor users never receive testimonial controls.

---

### Task 5: My Feedback and Submission Detail

**Files:**

- Modify: `app/Http/Controllers/PlatformFeedbackController.php`
- Modify: `resources/views/feedback/index.blade.php`
- Modify: `resources/views/feedback/show.blade.php`
- Modify: `tests/Feature/Support/PlatformFeedbackUiTest.php`

**Interfaces:**

- Consumes: optional `view=all|active|closed`, the authenticated user's `platformFeedbackSubmissions()` relationship, and status/type components.
- Produces: owned paginated `feedback`, normalized `viewFilter`, active navigation state, and lifecycle presentation with only the current status emphasized.

- [ ] **Step 1: Add failing owned-filter and detail-presentation tests**

```php
public function test_history_filters_only_the_owners_active_feedback(): void
{
    $owner = User::factory()->create(['role' => 'learner']);
    $other = User::factory()->create(['role' => 'learner']);
    $active = PlatformFeedback::factory()->for($owner)->create(['status' => PlatformFeedbackStatus::Reviewed]);
    $closed = PlatformFeedback::factory()->for($owner)->create(['status' => PlatformFeedbackStatus::Resolved]);
    PlatformFeedback::factory()->for($other)->create(['subject' => 'Private other feedback']);

    $this->actingAs($owner)->get(route('feedback.index', ['view' => 'active']))
        ->assertOk()
        ->assertSee($active->subject)
        ->assertDontSee($closed->subject)
        ->assertDontSee('Private other feedback');
}
```

Also assert enum labels, feedback type, submitted date, response visibility, current-only lifecycle emphasis, archived terminal state, and the success banner after store redirect.

- [ ] **Step 2: Run the feedback history/detail tests and confirm they fail**

Run:

```powershell
php vendor/phpunit/phpunit/phpunit tests/Feature/Support/PlatformFeedbackUiTest.php --filter "history|detail|success"
```

Expected: FAIL because filtering and structured presentation do not exist.

- [ ] **Step 3: Add normalized owned history filtering**

```php
$viewFilter = in_array($request->query('view'), ['active', 'closed'], true)
    ? $request->query('view')
    : 'all';
$feedback = $request->user()->platformFeedbackSubmissions()
    ->when($viewFilter === 'active', fn ($query) => $query->whereIn('status', [
        PlatformFeedbackStatus::New->value,
        PlatformFeedbackStatus::Reviewed->value,
        PlatformFeedbackStatus::Planned->value,
    ]))
    ->when($viewFilter === 'closed', fn ($query) => $query->whereIn('status', [
        PlatformFeedbackStatus::Resolved->value,
        PlatformFeedbackStatus::Archived->value,
    ]))
    ->latest()
    ->paginate(15)
    ->withQueryString();
```

Do not move filtering to an unrestricted model query; relationship ownership is the security boundary.

- [ ] **Step 4: Implement divided history rows and explicit empty states**

Render All, Active, and Closed links from contextual routes, then use `@forelse` for rows containing type icon, subject, reference, date, status badge, description preview, and detail URL. The empty account state links to Send Feedback; a filtered empty state links to All.

- [ ] **Step 5: Implement the durable feedback record**

Render shared navigation, optional session success alert, subject/reference, status/type metadata, current-only lifecycle positions, original fields, authorized attachment action, staff response, contact state, and testimonial withdrawal.

```blade
@foreach([PlatformFeedbackStatus::New, PlatformFeedbackStatus::Reviewed, PlatformFeedbackStatus::Planned, PlatformFeedbackStatus::Resolved] as $position)
    <li @class(['rounded-lg border px-3 py-2 text-sm', 'border-brand-700 bg-brand-50 font-semibold text-brand-900' => $feedback->status === $position, 'border-gray-200 text-gray-500' => $feedback->status !== $position])>
        {{ $position->label() }}
    </li>
@endforeach
```

If status is Archived, replace the progression positions with one gray Archived terminal panel.

- [ ] **Step 6: Run feedback policy, history, attachment, and consent tests**

Run:

```powershell
php vendor/phpunit/phpunit/phpunit tests/Feature/Support/PlatformFeedbackUiTest.php tests/Feature/Support/PlatformFeedbackValidationTest.php tests/Feature/Support/SupportSidebarNavigationTest.php
git diff --check
```

Expected: PASS with owner/admin authorization unchanged.

---

### Task 6: Admin Support Management Refinement

**Files:**

- Modify: `app/Http/Controllers/Admin/HelpArticleController.php`
- Modify: `resources/views/admin/help/articles/index.blade.php`
- Modify: `resources/views/admin/help/articles/form.blade.php`
- Modify: `resources/views/admin/help/articles/preview.blade.php`
- Modify: `resources/views/admin/help/categories/index.blade.php`
- Modify: `resources/views/admin/help/categories/form.blade.php`
- Modify: `resources/views/admin/feedback/index.blade.php`
- Modify: `resources/views/admin/feedback/show.blade.php`
- Modify: `resources/views/admin/testimonials/index.blade.php`
- Modify: `resources/views/admin/testimonials/edit.blade.php`
- Modify: `routes/admin.php`
- Extend: `tests/QA/SupportUiQaTest.php`
- Preserve: `tests/Feature/Support/AdminHelpCenterTest.php`
- Preserve: `tests/Feature/Support/AdminPlatformFeedbackTest.php`

**Interfaces:**

- Consumes: existing admin routes, requests, lifecycle/publication services, paginators, enum labels, and support badge/type components.
- Produces: consistent management tabs, complete labelled filters, human-readable table data, accessible forms, and clear private-versus-public response semantics.

- [ ] **Step 1: Extend failing admin UI assertions**

Require `for` labels for search/status/type/rating/from/to, Help Articles/Categories management tabs, enum labels without underscores, internal/public response descriptions, authorized admin attachment URL, and testimonial consent/publication state.

```php
public function test_admin_feedback_filters_are_labelled_and_types_are_human_readable(): void
{
    $this->user('admin');
    $feedback = PlatformFeedback::factory()->create(['type' => PlatformFeedbackType::BugReport]);
    $html = $this->get(route('admin.feedback.index'))->assertOk()->getContent();
    $xpath = $this->dom($html);

    foreach (['search', 'status', 'type', 'rating', 'from', 'to'] as $name) {
        $control = $xpath->query('//*[@name="'.$name.'"]')->item(0);
        $this->assertNotNull($control);
        $this->assertGreaterThan(0, $xpath->query('//label[@for="'.$control->getAttribute('id').'"]')->length);
    }

    $this->assertStringContainsString('Bug Report', $html);
    $this->assertStringNotContainsString('bug_report', $html);
}
```

- [ ] **Step 2: Run admin support and QA tests and confirm the failures**

Run:

```powershell
php vendor/phpunit/phpunit/phpunit tests/QA/SupportUiQaTest.php tests/Feature/Support/AdminHelpCenterTest.php tests/Feature/Support/AdminPlatformFeedbackTest.php
```

Expected: FAIL on missing date filters, labels, raw type values, and incomplete admin presentation.

- [ ] **Step 3: Refine Help Articles and Categories**

Add shared management tabs, a labelled `search` field, the existing status filter, a clear action, stable accessible tables, human-readable audience/status labels, and one-action empty states. Update `HelpArticleController::index()` with this bounded query while keeping create/edit/preview/publish/archive behavior untouched:

```php
$search = trim(mb_substr((string) $request->query('search', ''), 0, 100));
$articles = HelpArticle::query()
    ->with('category')
    ->when(in_array($status, HelpArticleStatus::values(), true), fn ($query) => $query->where('status', $status))
    ->when($search !== '', fn ($query) => $query->where(function ($nested) use ($search) {
        $term = '%'.addcslashes($search, '%_\\').'%';
        $nested->where('title', 'like', $term)
            ->orWhere('slug', 'like', $term)
            ->orWhereHas('category', fn ($category) => $category->where('name', 'like', $term));
    }))
    ->orderByDesc('updated_at')
    ->paginate(20)
    ->withQueryString();
```

- [ ] **Step 4: Add the admin attachment route and refine the Feedback Inbox and review page**

Register the explicit admin attachment route inside the existing admin feedback group:

```php
Route::get('/{platformFeedback}/attachment', [\App\Http\Controllers\PlatformFeedbackAttachmentController::class, 'show'])
    ->name('attachment.show');
```

Render all six existing request filters with IDs and labels, use enum `label()` methods, retain query parameters in pagination, and use the shared status/type components. On detail, separate the response fields explicitly:

```blade
<label for="internal_note" class="text-sm font-semibold text-gray-800">Private internal note</label>
<p id="internal-note-help" class="text-xs text-gray-500">Only platform administrators can see this note.</p>
<textarea id="internal_note" name="internal_note" aria-describedby="internal-note-help" class="mt-1 w-full rounded-xl border-gray-300">{{ old('internal_note', $feedback->internal_note) }}</textarea>

<label for="staff_response" class="text-sm font-semibold text-gray-800">Response to the user</label>
<p id="staff-response-help" class="text-xs text-gray-500">The person who submitted this feedback can read this response.</p>
<textarea id="staff_response" name="staff_response" aria-describedby="staff-response-help" class="mt-1 w-full rounded-xl border-gray-300">{{ old('staff_response', $feedback->staff_response) }}</textarea>
```

Use `admin.feedback.attachment.show` for admin attachment access. The existing attachment controller calls the `view` policy, so admin authorization and file-existence checks remain authoritative.

- [ ] **Step 5: Refine Testimonials management**

Render divided management rows with display name, excerpt, source reference, consent state, status, order, and explicit Edit/Publish/Withdraw actions. The edit form uses separate labels and helpers for display name, display role, quotation, order, and profile-image permission. Hide or disable Publish when source consent is withdrawn; keep `TestimonialPublicationService` as the final enforcement layer.

- [ ] **Step 6: Run the complete admin support suite**

Run:

```powershell
php vendor/phpunit/phpunit/phpunit tests/QA/SupportUiQaTest.php tests/Feature/Support/AdminHelpCenterTest.php tests/Feature/Support/AdminPlatformFeedbackTest.php
git diff --check
```

Expected: PASS with existing admin authorization and lifecycle transitions unchanged.

---

### Task 7: Cross-role Regression, Build, and Browser Verification

**Files:**

- Modify only failing task-owned files from Tasks 1-6.
- Test: all files under `tests/Feature/Support`, `tests/Unit/Support`, and `tests/QA/SupportUiQaTest.php`.
- Build output: `public/build/manifest.json` and hashed CSS/JS assets may change; report them separately from source.

**Interfaces:**

- Consumes: all implemented support UI contracts.
- Produces: objective test/build evidence and a role-by-role visual checklist.

- [ ] **Step 1: Run all focused support tests**

Run:

```powershell
php vendor/phpunit/phpunit/phpunit tests/Feature/Support tests/Unit/Support tests/QA/SupportUiQaTest.php
```

Expected: all support tests PASS. If a failure is outside task-owned code, capture the exact test and error before deciding whether it is in scope.

- [ ] **Step 2: Run formatting, Blade, and asset verification**

Run scoped formatting against changed PHP files, then:

```powershell
php artisan view:clear
php artisan view:cache
npm.cmd run build
git diff --check
```

Expected: all commands exit 0. Do not delete generated files to hide a manifest change.

- [ ] **Step 3: Inspect support pages in a browser**

Verify desktop and mobile widths for:

- Guest Help Center landing, search results, empty results, and article.
- Learner Help Center, feedback form, history, and detail in light and dark mode.
- Parent, instructor, and admin Support tab/side-navigation active states.
- Connector Help Center category URL, article URL, feedback form, history, and detail.
- Adult testimonial disclosure and minor absence.
- Invalid form summary, inline messages, preserved radio/checkbox state, attachment preview/remove, submit busy state, and success confirmation.
- Admin Help Articles/Categories, Feedback Inbox/detail, and Testimonials.

- [ ] **Step 4: Audit authorization and safety wording**

Confirm that another authenticated user receives 403/404 for feedback detail and attachment, connector context does not broaden access, and every feedback entry point says unsafe content must use the report action on the relevant surface.

- [ ] **Step 5: Review final task-owned diff and report without committing**

Run:

```powershell
git status --short
git diff --stat
git diff --check
```

Report source files, generated build files, focused test totals, browser checks, and any pre-existing failures separately. Do not describe the work as committed, pushed, or merged.
