# Interactive Activity Item Images Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add optional, accessible Image Library media to existing Matching and Sequencing items while preserving their current interactions, scoring, progress, and text-only behavior.

**Architecture:** Extend schema-version-1 item envelopes with nullable `image_path` and `image_alt` fields, validate new paths against the authenticated author's existing Image Library, and derive `image_url` only in safe learner/authoring payloads. Reuse the existing handlers, Preview flow, Alpine components, public storage disk, and responsive card system; keep answer submission and evaluation ID-only.

**Tech Stack:** Laravel 12/PHP 8.2, Eloquent JSON casts, public Laravel storage, Blade, Alpine.js 3, Tailwind CSS, native Pointer Events/ResizeObserver, Node's built-in test runner, PHPUnit 11.

## Global Constraints

- Do not add an activity type, media table, schema-version increment, frontend package, or image-processing dependency.
- Support JPEG, PNG, and WebP files with a maximum size of 2 MB.
- Every item must contain text, an image, or both; `image_alt` is required whenever `image_path` exists.
- Preserve Matching evaluation, Sequencing evaluation, Retry, Continue, Practice, progress, completion, placement, permissions, and ownership rules.
- Store only trusted public-disk paths; derive presentation URLs server-side and never trust client-supplied URLs.
- Replacing/removing media detaches it. Deleting activities never deletes reusable Image Library assets.
- Block Image Library deletion while a saved Interactive Activity references the exact asset path.
- Never reset, wipe, truncate, drop, recreate, or destructively reseed the development database.
- Preserve unrelated worktree changes, including the existing untracked `storage/framework/lsp-b7c5039063be9f4e.php`.

---

## File Structure

**Backend domain and authoring**

- Modify `app/Services/Learning/InteractiveActivities/MatchingActivityHandler.php`: validate, normalize, fingerprint, and present optional item media.
- Modify `app/Services/Learning/InteractiveActivities/SequencingActivityHandler.php`: provide the same media contract for ordered items.
- Modify `app/Services/Learning/InteractiveActivities/InteractiveActivityAuthoringService.php`: validate new asset paths against the current user and preserve unchanged stored paths.
- Modify `app/Http/Controllers/Instructor/ImageLibraryController.php`: add WebP/path responses and prevent deletion of referenced images.

**Authoring frontend**

- Modify `resources/js/interactive-activity-authoring.js`: own upload, picker, attach, replace, remove, cleanup, accessible labels, and serialization state.
- Modify `resources/views/instructor/topics/partials/interactive-activity-fields.blade.php`: hydrate media URLs, pass Image Library endpoints, and render the shared picker.
- Modify `resources/views/instructor/topics/partials/matching-builder.blade.php`: add media controls to both sides of every pair.
- Modify `resources/views/instructor/topics/partials/sequencing-builder.blade.php`: add media controls to every ordered item.
- Modify `resources/views/instructor/topics/create.blade.php`: identify the shared Create submit control so pending uploads can disable it.
- Modify `resources/views/instructor/topics/edit-interactive-activity.blade.php`: identify the Edit submit control so pending uploads can disable it.

**Learner frontend**

- Modify `resources/js/matching-activity.js`: use text-or-alt accessible labels, track image failures, and refresh geometry after media layout changes.
- Modify `resources/js/sequencing-activity.js`: use text-or-alt labels and retain compact media state through dragging and payload replacement.
- Modify `resources/views/learner/lessons/partials/interactive-activities/matching.blade.php`: render contained item images without moving endpoint dots.
- Modify `resources/views/learner/lessons/partials/interactive-activities/sequencing.blade.php`: render compact non-draggable thumbnails and drag-overlay media.
- Modify `resources/css/components.css`: add bounded authoring, Matching, Sequencing, fallback, and responsive media styles.

**Tests and verification**

- Modify `tests/Unit/Services/Learning/InteractiveActivityHandlerTest.php`.
- Modify `tests/Feature/Instructor/InteractiveActivityAuthoringTest.php`.
- Create `tests/Feature/Instructor/InteractiveActivityImageLibraryTest.php`.
- Modify `tests/Feature/Learner/InteractiveActivityRenderingTest.php`.
- Modify `tests/JavaScript/interactive-activity-authoring.test.mjs`.
- Modify `tests/JavaScript/matching-activity.test.mjs`.
- Modify `tests/JavaScript/sequencing-activity.test.mjs`.
- Create `docs/superpowers/verification/2026-09-20-interactive-activity-item-images.md` after verification.

---

### Task 1: Extend the handler item contract without changing evaluation

**Files:**

- Modify: `tests/Unit/Services/Learning/InteractiveActivityHandlerTest.php`
- Modify: `app/Services/Learning/InteractiveActivities/MatchingActivityHandler.php`
- Modify: `app/Services/Learning/InteractiveActivities/SequencingActivityHandler.php`

**Interfaces:**

- Consumes: existing schema-version-1 Matching sides and Sequencing items.
- Produces: normalized items with `value: string`, `image_path: ?string`, and `image_alt: ?string`; learner items with `image_url: ?string` and `image_alt: ?string`.
- Preserves: `evaluate(array $configuration, array $answer, array $workingState): array` and all ID-based answer structures.

- [ ] **Step 1: Add failing handler tests for text-only, image-only, and mixed items**

Add `Storage` to the imports and add these focused tests before changing production code:

```php
use Illuminate\Support\Facades\Storage;

public function test_handlers_normalize_and_present_optional_item_images(): void
{
    Storage::fake('public');

    $matching = new MatchingActivityHandler;
    $matchingConfiguration = $matching->normalize(['pairs' => [
        [
            'left' => ['kind' => 'text', 'value' => '', 'image_path' => 'quiz-images/user-1/source.png', 'image_alt' => 'Source diagram'],
            'right' => ['kind' => 'text', 'value' => 'Target text'],
        ],
        [
            'left' => ['kind' => 'text', 'value' => 'Second source', 'image_path' => 'quiz-images/user-1/second.png', 'image_alt' => 'Supporting illustration'],
            'right' => ['kind' => 'text', 'value' => '', 'image_path' => 'quiz-images/user-1/target.webp', 'image_alt' => 'Target illustration'],
        ],
    ]]);
    $rightIds = array_column(array_column($matchingConfiguration['pairs'], 'right'), 'id');
    $matchingPayload = $matching->learnerPayload($matchingConfiguration, [
        'right_order' => $rightIds,
        'matched' => [],
    ]);

    $this->assertSame('', $matchingConfiguration['pairs'][0]['left']['value']);
    $this->assertSame('quiz-images/user-1/source.png', $matchingConfiguration['pairs'][0]['left']['image_path']);
    $this->assertSame('Source diagram', $matchingPayload['left_items'][0]['image_alt']);
    $this->assertStringEndsWith('/quiz-images/user-1/source.png', $matchingPayload['left_items'][0]['image_url']);
    $this->assertNull($matchingPayload['right_items'][0]['image_url']);

    $sequencing = new SequencingActivityHandler;
    $sequencingConfiguration = $sequencing->normalize(['items' => [
        ['kind' => 'text', 'value' => '', 'image_path' => 'quiz-images/user-1/one.png', 'image_alt' => 'First step'],
        ['kind' => 'text', 'value' => 'Second step'],
        ['kind' => 'text', 'value' => 'Third step', 'image_path' => 'quiz-images/user-1/three.png', 'image_alt' => 'Third-step diagram'],
    ]]);
    $itemIds = array_column($sequencingConfiguration['items'], 'id');
    $sequencingPayload = $sequencing->learnerPayload($sequencingConfiguration, ['item_order' => $itemIds]);

    $this->assertSame('First step', $sequencingPayload['items'][0]['image_alt']);
    $this->assertStringEndsWith('/quiz-images/user-1/one.png', $sequencingPayload['items'][0]['image_url']);
    $this->assertNull($sequencingPayload['items'][1]['image_url']);
}

public function test_handlers_require_text_or_image_and_alt_text_for_images(): void
{
    $matching = new MatchingActivityHandler;
    $this->assertValidationFails(fn (): array => $matching->normalize(['pairs' => [
        ['left' => ['kind' => 'text', 'value' => ''], 'right' => ['kind' => 'text', 'value' => 'One']],
        ['left' => ['kind' => 'text', 'value' => 'Two'], 'right' => ['kind' => 'text', 'value' => 'Second']],
    ]]));
    $this->assertValidationFails(fn (): array => $matching->normalize(['pairs' => [
        ['left' => ['kind' => 'text', 'value' => '', 'image_path' => 'quiz-images/user-1/a.png', 'image_alt' => ''], 'right' => ['kind' => 'text', 'value' => 'One']],
        ['left' => ['kind' => 'text', 'value' => 'Two'], 'right' => ['kind' => 'text', 'value' => 'Second']],
    ]]));

    $sequencing = new SequencingActivityHandler;
    $this->assertValidationFails(fn (): array => $sequencing->normalize(['items' => [
        ['kind' => 'text', 'value' => ''],
        ['kind' => 'text', 'value' => 'Second'],
        ['kind' => 'text', 'value' => 'Third'],
    ]]));
    $this->assertValidationFails(fn (): array => $sequencing->normalize(['items' => [
        ['kind' => 'text', 'value' => '', 'image_path' => 'quiz-images/user-1/a.png'],
        ['kind' => 'text', 'value' => 'Second'],
        ['kind' => 'text', 'value' => 'Third'],
    ]]));
}

public function test_image_only_duplicates_and_semantic_fingerprints_are_media_aware(): void
{
    $sequencing = new SequencingActivityHandler;
    $this->assertValidationFails(fn (): array => $sequencing->normalize(['items' => [
        ['kind' => 'text', 'value' => '', 'image_path' => 'quiz-images/user-1/same.png', 'image_alt' => 'First'],
        ['kind' => 'text', 'value' => '', 'image_path' => 'quiz-images/user-1/same.png', 'image_alt' => 'Second'],
        ['kind' => 'text', 'value' => 'Third'],
    ]]));

    $textBase = $sequencing->normalize(['items' => [
        ['kind' => 'text', 'value' => 'First', 'image_path' => 'quiz-images/user-1/first.png', 'image_alt' => 'First image'],
        ['kind' => 'text', 'value' => 'Second'],
        ['kind' => 'text', 'value' => 'Third'],
    ]]);
    $textMediaChanged = $textBase;
    $textMediaChanged['items'][0]['image_path'] = 'quiz-images/user-1/replacement.png';
    $textMediaChanged['items'][0]['image_alt'] = 'Replacement image';
    $this->assertSame($sequencing->answerFingerprint($textBase), $sequencing->answerFingerprint($textMediaChanged));

    $imageBase = $sequencing->normalize(['items' => [
        ['kind' => 'text', 'value' => '', 'image_path' => 'quiz-images/user-1/first.png', 'image_alt' => 'First image'],
        ['kind' => 'text', 'value' => 'Second'],
        ['kind' => 'text', 'value' => 'Third'],
    ]]);
    $imageChanged = $imageBase;
    $imageChanged['items'][0]['image_alt'] = 'A materially different first image';
    $this->assertNotSame($sequencing->answerFingerprint($imageBase), $sequencing->answerFingerprint($imageChanged));
}
```

- [ ] **Step 2: Run the new handler tests and verify RED**

Run:

```powershell
php artisan test tests/Unit/Services/Learning/InteractiveActivityHandlerTest.php
```

Expected: failures show that empty text is rejected and learner payloads do not contain `image_url` or `image_alt`.

- [ ] **Step 3: Implement media-aware Matching normalization and payloads**

In `MatchingActivityHandler::rules()`, replace the two required `value` rules and add media rules for both sides:

```php
"{$prefix}pairs.*.left.value" => ['present', 'nullable', 'string', 'max:500'],
"{$prefix}pairs.*.left.image_path" => ['nullable', 'string', 'max:2048'],
"{$prefix}pairs.*.left.image_alt" => ['nullable', 'string', 'max:500'],
"{$prefix}pairs.*.right.value" => ['present', 'nullable', 'string', 'max:500'],
"{$prefix}pairs.*.right.image_path" => ['nullable', 'string', 'max:2048'],
"{$prefix}pairs.*.right.image_alt" => ['nullable', 'string', 'max:500'],
```

Add `use Illuminate\Support\Facades\Storage;`. Normalize every side through this complete helper:

```php
private function normalizeItem(array $item, string $id): array
{
    $path = trim((string) ($item['image_path'] ?? ''));

    return [
        'id' => $id,
        'kind' => 'text',
        'value' => trim((string) ($item['value'] ?? '')),
        'image_path' => $path !== '' ? $path : null,
        'image_alt' => $path !== '' ? trim((string) ($item['image_alt'] ?? '')) : null,
    ];
}

private function learnerItem(array $item): array
{
    return [
        'id' => $item['id'],
        'kind' => $item['kind'],
        'value' => $item['value'],
        'image_url' => $item['image_path'] ? Storage::disk('public')->url($item['image_path']) : null,
        'image_alt' => $item['image_alt'],
    ];
}

private function duplicateValue(array $item): string
{
    $text = $this->comparisonValue((string) ($item['value'] ?? ''));

    return $text !== '' ? "text:{$text}" : 'image:'.trim((string) ($item['image_path'] ?? ''));
}

private function semanticValue(array $item): string
{
    $text = $this->comparisonValue((string) ($item['value'] ?? ''));
    if ($text !== '') {
        return "text:{$text}";
    }

    return 'image:'.trim((string) ($item['image_path'] ?? '')).'|'.$this->comparisonValue((string) ($item['image_alt'] ?? ''));
}
```

In the validator callback, visit both sides of every pair. Add an error to `pairs.{index}.{side}.value` when both normalized text and path are empty, add an error to `pairs.{index}.{side}.image_alt` when a path has no nonblank alt, and calculate duplicates with `duplicateValue()`. Use `semanticValue()` inside `answerFingerprint()`. Map both left and right learner collections through `learnerItem()` while retaining the existing right-order shuffle and completed-match fields.

- [ ] **Step 4: Implement the equivalent Sequencing item contract**

Add the same `Storage` import and media rules to `SequencingActivityHandler::rules()`:

```php
"{$prefix}items.*.value" => ['present', 'nullable', 'string', 'max:500'],
"{$prefix}items.*.image_path" => ['nullable', 'string', 'max:2048'],
"{$prefix}items.*.image_alt" => ['nullable', 'string', 'max:500'],
```

Add the same `normalizeItem()`, `learnerItem()`, `duplicateValue()`, and `semanticValue()` helpers, with `correct_position` appended by the existing index-based normalizer. Apply the same text-or-image and alt validation per item. Use `semanticValue()` in canonical order inside `answerFingerprint()` and `learnerItem()` inside `learnerPayload()`.

- [ ] **Step 5: Run handler tests and the existing evaluator regression suite**

Run:

```powershell
php artisan test tests/Unit/Services/Learning/InteractiveActivityHandlerTest.php tests/Unit/Services/Learning/QuestionEvaluatorTest.php
```

Expected: all tests pass; existing evaluation result shapes remain unchanged.

- [ ] **Step 6: Commit the handler contract**

```powershell
git add app/Services/Learning/InteractiveActivities/MatchingActivityHandler.php app/Services/Learning/InteractiveActivities/SequencingActivityHandler.php tests/Unit/Services/Learning/InteractiveActivityHandlerTest.php
git commit -m "feat: support media in activity items"
```

---

### Task 2: Validate Image Library ownership in authoring and Preview

**Files:**

- Modify: `tests/Feature/Instructor/InteractiveActivityAuthoringTest.php`
- Modify: `app/Services/Learning/InteractiveActivities/InteractiveActivityAuthoringService.php`

**Interfaces:**

- Consumes: normalized configuration from Task 1 and `$request->user()`.
- Produces: validated configurations whose new media paths exist under `quiz-images/user-{id}/`.
- Preserves: an exact path already stored on the authorized activity, including when an admin is not the original uploader.

- [ ] **Step 1: Add failing feature tests for create, Preview, path authorization, and revisions**

Import `Storage` and add four tests:

```php
use Illuminate\Support\Facades\Storage;

public function test_instructor_can_create_and_preview_image_only_activity_items(): void
{
    Storage::fake('public');
    [$instructor, $lesson] = $this->authoringFixture();
    $path = "quiz-images/user-{$instructor->id}/diagram.png";
    Storage::disk('public')->put($path, 'image-bytes');
    $configuration = [
        'pairs' => [
            ['left' => ['value' => '', 'image_path' => $path, 'image_alt' => 'Consent diagram'], 'right' => ['value' => 'Freely given agreement']],
            ['left' => ['value' => 'Boundary'], 'right' => ['value' => '', 'image_path' => $path, 'image_alt' => 'Boundary illustration']],
        ],
    ];

    $this->actingAs($instructor)
        ->postJson(route('instructor.interactive-activities.preview'), $this->previewPayload($lesson, null, ['configuration' => $configuration]))
        ->assertOk()
        ->assertJsonPath('html', fn (string $html): bool => str_contains($html, 'Consent diagram'));

    $this->actingAs($instructor)
        ->post(route('instructor.topics.store'), $this->previewPayload($lesson, null, ['configuration' => $configuration]))
        ->assertRedirect(route('instructor.lessons.show', $lesson));

    $activity = InteractiveActivity::query()->latest('id')->firstOrFail();
    $this->assertSame($path, $activity->configuration['pairs'][0]['left']['image_path']);
    $this->assertSame('Consent diagram', $activity->configuration['pairs'][0]['left']['image_alt']);
}

public function test_authoring_rejects_new_image_paths_outside_the_current_users_library(): void
{
    Storage::fake('public');
    [$instructor, $lesson] = $this->authoringFixture();
    $foreignPath = 'quiz-images/user-999/foreign.png';
    Storage::disk('public')->put($foreignPath, 'image-bytes');
    $configuration = $this->matchingConfiguration();
    $configuration['pairs'][0]['left'] = ['value' => '', 'image_path' => $foreignPath, 'image_alt' => 'Foreign image'];

    $this->actingAs($instructor)
        ->post(route('instructor.topics.store'), $this->previewPayload($lesson, null, ['configuration' => $configuration]))
        ->assertSessionHasErrors('configuration.pairs.0.left.image_path');

    $this->assertDatabaseCount('interactive_activities', 0);
}

public function test_authorized_edit_preserves_an_unchanged_existing_image_path(): void
{
    Storage::fake('public');
    [$instructor, $lesson] = $this->authoringFixture();
    [, $activity] = $this->insideActivity($lesson);
    $configuration = $activity->configuration;
    $configuration['pairs'][0]['left']['value'] = '';
    $configuration['pairs'][0]['left']['image_path'] = 'quiz-images/user-999/legacy.png';
    $configuration['pairs'][0]['left']['image_alt'] = 'Existing legacy diagram';
    $activity->update(['configuration' => $configuration]);

    $this->actingAs($instructor)
        ->put(route('instructor.interactive-activities.update', $activity), $this->activityPayload($activity))
        ->assertRedirect(route('instructor.lessons.show', $lesson));

    $this->assertSame('quiz-images/user-999/legacy.png', $activity->refresh()->configuration['pairs'][0]['left']['image_path']);
}

public function test_media_revisions_distinguish_supporting_and_image_only_content(): void
{
    Storage::fake('public');
    [$instructor, $lesson] = $this->authoringFixture();
    [, $activity] = $this->insideActivity($lesson);
    foreach (['support.png', 'replacement.png'] as $filename) {
        Storage::disk('public')->put("quiz-images/user-{$instructor->id}/{$filename}", 'image-bytes');
    }

    $supporting = $activity->configuration;
    $supporting['pairs'][0]['left']['image_path'] = "quiz-images/user-{$instructor->id}/support.png";
    $supporting['pairs'][0]['left']['image_alt'] = 'Supporting diagram';
    $this->actingAs($instructor)->put(route('instructor.interactive-activities.update', $activity), $this->activityPayload($activity, ['configuration' => $supporting]))->assertRedirect();
    $this->assertSame(1, $activity->refresh()->revision);

    $imageOnly = $activity->configuration;
    $imageOnly['pairs'][0]['left']['value'] = '';
    $this->actingAs($instructor)->put(route('instructor.interactive-activities.update', $activity), $this->activityPayload($activity, ['configuration' => $imageOnly]))->assertRedirect();
    $this->assertSame(2, $activity->refresh()->revision);

    $replacement = $activity->configuration;
    $replacement['pairs'][0]['left']['image_path'] = "quiz-images/user-{$instructor->id}/replacement.png";
    $replacement['pairs'][0]['left']['image_alt'] = 'Replacement semantic diagram';
    $this->actingAs($instructor)->put(route('instructor.interactive-activities.update', $activity), $this->activityPayload($activity, ['configuration' => $replacement]))->assertRedirect();
    $this->assertSame(3, $activity->refresh()->revision);
}
```

- [ ] **Step 2: Run the new authoring tests and verify RED**

Run:

```powershell
php artisan test tests/Feature/Instructor/InteractiveActivityAuthoringTest.php --filter='image|media'
```

Expected: create/Preview currently rejects empty text, and foreign paths are not yet rejected at the authoring boundary.

- [ ] **Step 3: Add exact-path authorization after handler normalization**

Import `Storage` and call this validation immediately after `$handler->normalize(...)` in `InteractiveActivityAuthoringService::validate()`:

```php
$this->validateImagePaths(
    $normalized,
    $validated['activity_type'],
    $request->user(),
    $activity?->configuration,
);
```

Add these complete private methods:

```php
private function validateImagePaths(array $configuration, string $activityType, User $author, ?array $existingConfiguration): void
{
    $existingPaths = array_values(array_filter(array_map(
        static fn (array $entry): mixed => $entry['item']['image_path'] ?? null,
        $this->configuredItems($existingConfiguration ?? [], $activityType),
    ), 'is_string'));
    $directory = 'quiz-images/user-'.$author->id.'/';

    foreach ($this->configuredItems($configuration, $activityType) as $entry) {
        $path = $entry['item']['image_path'] ?? null;
        if (! is_string($path) || $path === '' || in_array($path, $existingPaths, true)) {
            continue;
        }

        if (! str_starts_with($path, $directory) || ! Storage::disk('public')->exists($path)) {
            throw ValidationException::withMessages([
                $entry['key'].'.image_path' => 'Choose an image from your Image Library.',
            ]);
        }
    }
}

/** @return list<array{key: string, item: array<string, mixed>}> */
private function configuredItems(array $configuration, string $activityType): array
{
    $items = [];
    if ($activityType === InteractiveActivityType::MATCHING->value) {
        foreach (($configuration['pairs'] ?? []) as $index => $pair) {
            foreach (['left', 'right'] as $side) {
                if (is_array($pair[$side] ?? null)) {
                    $items[] = ['key' => "configuration.pairs.{$index}.{$side}", 'item' => $pair[$side]];
                }
            }
        }

        return $items;
    }

    foreach (($configuration['items'] ?? []) as $index => $item) {
        if (is_array($item)) {
            $items[] = ['key' => "configuration.items.{$index}", 'item' => $item];
        }
    }

    return $items;
}
```

Do not check file existence for an exact path already stored on the activity; the learner-side missing-image fallback handles external storage inconsistency, and authorized editors must be able to retain legacy references.

- [ ] **Step 4: Run all authoring feature tests**

Run:

```powershell
php artisan test tests/Feature/Instructor/InteractiveActivityAuthoringTest.php
```

Expected: all tests pass, including existing ownership, placement, immutable-type, Preview-token, and revision tests.

- [ ] **Step 5: Commit the authoring trust boundary**

```powershell
git add app/Services/Learning/InteractiveActivities/InteractiveActivityAuthoringService.php tests/Feature/Instructor/InteractiveActivityAuthoringTest.php
git commit -m "feat: validate activity image references"
```

---

### Task 3: Extend the existing Image Library safely

**Files:**

- Create: `tests/Feature/Instructor/InteractiveActivityImageLibraryTest.php`
- Modify: `app/Http/Controllers/Instructor/ImageLibraryController.php`

**Interfaces:**

- Consumes: existing `instructor.image-library.*` and `admin.image-library.*` routes.
- Produces: upload/list JSON entries with `path`; WebP acceptance; deletion refusal for saved activity references.
- Preserves: current user-scoped directories and HTML redirect behavior.

- [ ] **Step 1: Write failing Image Library feature tests**

Create the test file with these tests:

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Instructor;

use App\Models\InteractiveActivity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class InteractiveActivityImageLibraryTest extends TestCase
{
    use RefreshDatabase;

    public function test_upload_and_listing_return_the_scoped_storage_path_and_accept_webp(): void
    {
        Storage::fake('public');
        $instructor = User::factory()->create(['role' => 'instructor']);
        $instructor->assignRole('instructor');

        $upload = $this->actingAs($instructor)->postJson(route('instructor.image-library.upload'), [
            'image' => UploadedFile::fake()->image('diagram.webp'),
        ])->assertOk();

        $path = $upload->json('path');
        $this->assertIsString($path);
        $this->assertStringStartsWith("quiz-images/user-{$instructor->id}/", $path);
        Storage::disk('public')->assertExists($path);

        $this->actingAs($instructor)
            ->getJson(route('instructor.image-library.json'))
            ->assertOk()
            ->assertJsonFragment(['path' => $path]);
    }

    public function test_referenced_activity_image_cannot_be_deleted_from_the_library(): void
    {
        Storage::fake('public');
        $instructor = User::factory()->create(['role' => 'instructor']);
        $instructor->assignRole('instructor');
        $path = "quiz-images/user-{$instructor->id}/used.png";
        Storage::disk('public')->put($path, 'image-bytes');
        InteractiveActivity::factory()->create(['configuration' => [
            'schema_version' => 1,
            'pairs' => [
                ['id' => 'pair-1', 'left' => ['id' => 'left-1', 'kind' => 'text', 'value' => '', 'image_path' => $path, 'image_alt' => 'Used diagram'], 'right' => ['id' => 'right-1', 'kind' => 'text', 'value' => 'One', 'image_path' => null, 'image_alt' => null]],
                ['id' => 'pair-2', 'left' => ['id' => 'left-2', 'kind' => 'text', 'value' => 'Two', 'image_path' => null, 'image_alt' => null], 'right' => ['id' => 'right-2', 'kind' => 'text', 'value' => 'Second', 'image_path' => null, 'image_alt' => null]],
            ],
        ]]);

        $this->actingAs($instructor)
            ->from(route('instructor.image-library.index'))
            ->delete(route('instructor.image-library.delete', basename($path)))
            ->assertRedirect(route('instructor.image-library.index'))
            ->assertSessionHas('error', 'This image is used by an interactive activity and cannot be deleted.');

        Storage::disk('public')->assertExists($path);
    }

    public function test_unreferenced_library_image_retains_existing_delete_behavior(): void
    {
        Storage::fake('public');
        $instructor = User::factory()->create(['role' => 'instructor']);
        $instructor->assignRole('instructor');
        $path = "quiz-images/user-{$instructor->id}/unused.png";
        Storage::disk('public')->put($path, 'image-bytes');

        $this->actingAs($instructor)
            ->delete(route('instructor.image-library.delete', basename($path)))
            ->assertSessionHas('success');

        Storage::disk('public')->assertMissing($path);
    }
}
```

- [ ] **Step 2: Run the new feature test and verify RED**

Run:

```powershell
php artisan test tests/Feature/Instructor/InteractiveActivityImageLibraryTest.php
```

Expected: the upload response has no `path`, WebP may fail validation, and a referenced file is currently deleted.

- [ ] **Step 3: Return paths and support WebP**

Change the upload rule to:

```php
'image' => ['required', 'image', 'mimes:jpeg,jpg,png,webp', 'max:2048'],
```

Add `'path' => $path` to JSON upload responses and `'path' => $file` to each JSON listing entry. Keep the existing filename, URL, and size fields.

- [ ] **Step 4: Guard deletion with an exact recursive configuration check**

Import `InteractiveActivity`. After resolving the scoped path and before deleting it, return with the exact test message when `isUsedByInteractiveActivity($path)` is true. Add these methods:

```php
private function isUsedByInteractiveActivity(string $path): bool
{
    return InteractiveActivity::query()
        ->select(['id', 'configuration'])
        ->lazyById()
        ->contains(fn (InteractiveActivity $activity): bool => $this->configurationContainsPath($activity->configuration, $path));
}

private function configurationContainsPath(mixed $value, string $path): bool
{
    if (! is_array($value)) {
        return false;
    }

    foreach ($value as $key => $child) {
        if ($key === 'image_path' && $child === $path) {
            return true;
        }
        if ($this->configurationContainsPath($child, $path)) {
            return true;
        }
    }

    return false;
}
```

The lazy scan keeps this infrequent delete guard database-portable and memory-bounded; `configurationContainsPath()` performs the exact comparison.

- [ ] **Step 5: Run Image Library and authoring tests**

Run:

```powershell
php artisan test tests/Feature/Instructor/InteractiveActivityImageLibraryTest.php tests/Feature/Instructor/InstructorImageLibraryThemeTest.php tests/Feature/Instructor/InteractiveActivityAuthoringTest.php
```

Expected: all tests pass.

- [ ] **Step 6: Commit Image Library reuse**

```powershell
git add app/Http/Controllers/Instructor/ImageLibraryController.php tests/Feature/Instructor/InteractiveActivityImageLibraryTest.php
git commit -m "feat: reuse image library for activities"
```

---

### Task 4: Add test-driven authoring media state

**Files:**

- Modify: `tests/JavaScript/interactive-activity-authoring.test.mjs`
- Modify: `resources/js/interactive-activity-authoring.js`

**Interfaces:**

- Consumes: `imageUploadUrl`, `imageLibraryUrl`, `csrf`, and the existing injectable `request` function.
- Produces: `uploadImage(kind, index, side, file)`, `openImageLibrary(kind, index, side, trigger)`, `selectLibraryImage(image)`, `removeImage(kind, index, side)`, `itemLabel(item)`, and media-aware `configuration()`.
- Preserves: existing Preview, pair reorder, sequence reorder, focus restoration, and validation APIs.

- [ ] **Step 1: Write failing serialization and attachment tests**

Add these tests:

```js
test('authoring serializes optional media and removes transient preview state', () => {
    const authoring = createInteractiveActivityAuthoring({
        pairs: [
            { left: { value: '', image_path: 'quiz-images/user-1/left.png', image_url: '/storage/left.png', image_alt: 'Left diagram' }, right: { value: 'Right' } },
            { left: { value: 'Second left' }, right: { value: 'Second right' } },
        ],
        items: [
            { value: '', image_path: 'quiz-images/user-1/one.png', image_url: '/storage/one.png', image_alt: 'First step' },
            { value: 'Second' },
            { value: 'Third' },
        ],
    });

    assert.deepEqual(authoring.configuration().pairs[0].left, {
        kind: 'text',
        value: '',
        image_path: 'quiz-images/user-1/left.png',
        image_alt: 'Left diagram',
    });
    authoring.setActivityType('sequencing');
    assert.equal(authoring.configuration().items[0].image_path, 'quiz-images/user-1/one.png');
    assert.equal(authoring.configuration().items[0].image_url, undefined);
    assert.equal(authoring.itemLabel(authoring.items[0]), 'First step');
});

test('removeImage detaches reusable media and clears its alt text', () => {
    const authoring = createInteractiveActivityAuthoring({
        items: [
            { value: '', image_path: 'quiz-images/user-1/one.png', image_url: '/storage/one.png', image_alt: 'First step' },
            { value: 'Second' },
            { value: 'Third' },
        ],
    });

    authoring.removeImage('items', 0, null);

    assert.equal(authoring.items[0].image_path, null);
    assert.equal(authoring.items[0].image_url, null);
    assert.equal(authoring.items[0].image_alt, '');
});

test('pending uploads disable and restore the owning activity form submit control', () => {
    const submit = { disabled: false };
    const form = {
        querySelectorAll(selector) {
            assert.equal(selector, '[data-interactive-activity-submit]');
            return [submit];
        },
    };
    const authoring = createInteractiveActivityAuthoring();
    authoring.$root = { closest: (selector) => selector === 'form' ? form : null };

    authoring.mediaUploadCount = 1;
    authoring.syncMediaControls();
    assert.equal(submit.disabled, true);

    authoring.mediaUploadCount = 0;
    authoring.syncMediaControls();
    assert.equal(submit.disabled, false);
});
```

- [ ] **Step 2: Write failing upload and library-picker tests**

```js
test('uploadImage attaches the returned reusable library asset and restores failures safely', async () => {
    const originalFormData = globalThis.FormData;
    const originalUrl = globalThis.URL;
    const revoked = [];
    globalThis.FormData = class {
        constructor() { this.values = []; }
        append(key, value) { this.values.push([key, value]); }
    };
    globalThis.URL = {
        createObjectURL: () => 'blob:preview',
        revokeObjectURL: (url) => revoked.push(url),
    };

    try {
        const authoring = createInteractiveActivityAuthoring({
            imageUploadUrl: '/image-library/upload',
            request: async () => ({
                ok: true,
                async json() {
                    return { path: 'quiz-images/user-1/new.webp', url: '/storage/new.webp' };
                },
            }),
        });
        await authoring.uploadImage('pairs', 0, 'left', { name: 'new.webp' });

        assert.equal(authoring.pairs[0].left.image_path, 'quiz-images/user-1/new.webp');
        assert.equal(authoring.pairs[0].left.image_url, '/storage/new.webp');
        assert.equal(authoring.pairs[0].left.imageUploading, false);
        assert.deepEqual(revoked, ['blob:preview']);
    } finally {
        globalThis.FormData = originalFormData;
        globalThis.URL = originalUrl;
    }
});

test('image library loads once, attaches to the active target, and restores focus', async () => {
    let calls = 0;
    let focused = 0;
    const authoring = createInteractiveActivityAuthoring({
        imageLibraryUrl: '/image-library/json',
        request: async () => {
            calls += 1;
            return { ok: true, async json() { return { images: [{ path: 'quiz-images/user-1/library.png', url: '/storage/library.png' }] }; } };
        },
    });
    const trigger = { focus() { focused += 1; } };

    await authoring.openImageLibrary('items', 0, null, trigger);
    await authoring.openImageLibrary('items', 0, null, trigger);
    authoring.selectLibraryImage(authoring.imageLibraryImages[0]);

    assert.equal(calls, 1);
    assert.equal(authoring.items[0].image_path, 'quiz-images/user-1/library.png');
    assert.equal(authoring.imageLibraryOpen, false);
    assert.equal(focused, 1);
});
```

- [ ] **Step 3: Run authoring JavaScript tests and verify RED**

Run:

```powershell
node --test tests/JavaScript/interactive-activity-authoring.test.mjs
```

Expected: the new methods and media fields do not exist.

- [ ] **Step 4: Normalize media state in default and stored items**

Replace the default item helpers with:

```js
const defaultContentItem = () => ({
    value: '',
    image_path: null,
    image_url: null,
    image_alt: '',
    imageUploading: false,
    imageError: '',
    localPreviewUrl: null,
});

const contentItem = (item = {}) => ({ ...defaultContentItem(), ...item, value: item?.value ?? '' });
const defaultPair = () => ({ left: defaultContentItem(), right: defaultContentItem() });
const defaultItem = () => defaultContentItem();
const authoringPair = (pair = {}) => ({ ...pair, left: contentItem(pair.left), right: contentItem(pair.right) });
```

Hydrate initial pairs with `authoringPair`, initial items with `contentItem`, and keep stable IDs through object spreads.

- [ ] **Step 5: Implement target lookup, labels, attachment, removal, and serialization**

Add state for `mediaUploadCount`, `imageLibraryOpen`, `imageLibraryLoading`, `imageLibraryImages`, `imageLibraryError`, `mediaTarget`, and `mediaTrigger`. Add these core methods:

```js
mediaItem(kind, index, side = null) {
    if (kind === 'pairs') return this.pairs[index]?.[side] ?? null;
    if (kind === 'items') return this.items[index] ?? null;
    return null;
},

itemLabel(item) {
    return item?.value?.trim?.() || item?.image_alt?.trim?.() || 'Item';
},

attachImage(kind, index, side, image) {
    const item = this.mediaItem(kind, index, side);
    if (!item || !image?.path || !image?.url) return this;
    item.image_path = image.path;
    item.image_url = image.url;
    item.imageError = '';
    return this;
},

removeImage(kind, index, side = null) {
    const item = this.mediaItem(kind, index, side);
    if (!item) return this;
    if (item.localPreviewUrl) globalThis.URL?.revokeObjectURL?.(item.localPreviewUrl);
    item.image_path = null;
    item.image_url = null;
    item.image_alt = '';
    item.imageError = '';
    item.localPreviewUrl = null;
    return this;
},

serializedItem(item, extra = {}) {
    return {
        ...extra,
        kind: 'text',
        value: item?.value ?? '',
        image_path: item?.image_path || null,
        image_alt: item?.image_path ? (item?.image_alt ?? '') : null,
    };
},
```

Use `serializedItem()` for both Matching sides and Sequencing items. Update authoring drag labels to call `itemLabel()` so image-only rows are announced by alt text.

- [ ] **Step 6: Implement immediate upload and cached library selection**

Implement `uploadImage()` with the injected request function, CSRF header, `FormData`, a temporary object URL, previous-image restoration on failure, `mediaUploadCount`, and accessible `imageError`. Increment `mediaUploadCount` before the request, decrement it in `finally`, and invoke this method after both transitions:

```js
syncMediaControls() {
    const form = this.$root?.closest?.('form');
    form?.querySelectorAll?.('[data-interactive-activity-submit]').forEach((button) => {
        button.disabled = this.hasPendingMedia();
    });
    return this;
},
```

Implement `openImageLibrary()` so it fetches once, validates `response.ok`, stores `data.images`, and retains the invoking target and focus trigger. Implement `selectLibraryImage()` through `attachImage()` and `closeImageLibrary()` with focus restoration.

Add `hasPendingMedia()` and stop `openPreview()` with `previewError = 'Wait for image uploads to finish.'` while pending. Add `cleanupMedia()` to revoke every remaining `localPreviewUrl`. Do not change the existing Preview request body or modal initialization.

- [ ] **Step 7: Run JavaScript authoring tests**

Run:

```powershell
node --test tests/JavaScript/interactive-activity-authoring.test.mjs
```

Expected: all existing and new tests pass.

- [ ] **Step 8: Commit authoring media state**

```powershell
git add resources/js/interactive-activity-authoring.js tests/JavaScript/interactive-activity-authoring.test.mjs
git commit -m "feat: add activity media authoring state"
```

---

### Task 5: Build the shared Create/Edit media authoring UI

**Files:**

- Modify: `tests/Feature/Instructor/InteractiveActivityAuthoringTest.php`
- Modify: `resources/views/instructor/topics/partials/interactive-activity-fields.blade.php`
- Modify: `resources/views/instructor/topics/partials/matching-builder.blade.php`
- Modify: `resources/views/instructor/topics/partials/sequencing-builder.blade.php`
- Modify: `resources/views/instructor/topics/create.blade.php`
- Modify: `resources/views/instructor/topics/edit-interactive-activity.blade.php`
- Modify: `resources/css/components.css`

**Interfaces:**

- Consumes: Task 4 Alpine methods and named Image Library routes.
- Produces: identical Create/Edit upload, preview, replace, remove, alt, and library-picker controls.
- Keeps: the current pair relationship visualization and handle-only authoring reorder.

- [ ] **Step 1: Add failing authoring markup assertions**

Extend `test_create_and_edit_forms_render_handle_based_activity_builders()` so both rendered pages assert:

```php
->assertSee('imageUploadUrl:', false)
->assertSee('imageLibraryUrl:', false)
->assertSee('Upload image')
->assertSee('Choose from Image Library')
->assertSee('Replace image')
->assertSee('Remove image')
->assertSee('Image alt text')
->assertSee('role="dialog" aria-modal="true" aria-labelledby="activity-image-library-title"', false)
->assertSee('accept="image/jpeg,image/png,image/webp"', false)
->assertSee('data-interactive-activity-submit', false)
```

- [ ] **Step 2: Run the focused markup test and verify RED**

Run:

```powershell
php artisan test tests/Feature/Instructor/InteractiveActivityAuthoringTest.php --filter=create_and_edit_forms_render_handle_based_activity_builders
```

Expected: the new URLs, controls, and dialog are absent.

- [ ] **Step 3: Hydrate transient authoring URLs and pass endpoints**

At the top of `interactive-activity-fields.blade.php`, build `$activityPairs` and `$activityItems` from old input or stored configuration. For every item with `image_path`, add a transient `image_url` using `Storage::disk('public')->url($path)`. Pass those hydrated arrays plus:

```php
imageUploadUrl: @js(route($contentRoutePrefix . '.image-library.upload')),
imageLibraryUrl: @js(route($contentRoutePrefix . '.image-library.json')),
```

Use `x-init="return () => cleanupMedia()"` on the common Alpine root. Change the Preview button binding to `:disabled="isLoading || hasPendingMedia()"`, with disabled status text that says uploads must finish. Add `data-interactive-activity-submit` and disabled styles to the Create Topic button in `create.blade.php` and the Save activity button in `edit-interactive-activity.blade.php`; Task 4's `syncMediaControls()` owns their native `disabled` property while uploads are pending. Keep all existing drag, Preview, placement, and validation bindings.

- [ ] **Step 4: Add media controls to both Matching sides**

For each side, keep the existing hidden ID/kind fields and change the text input to `:required="!pair.{side}.image_path"`. Add hidden path input, preview, file label, picker button, remove button, and conditional alt field. The left-side names and actions are:

```html
<input type="hidden" :name="`configuration[pairs][${index}][left][image_path]`" :value="pair.left.image_path || ''" :disabled="activityType !== 'matching'">
<img x-cloak x-show="pair.left.image_url" :src="pair.left.image_url" :alt="pair.left.image_alt || ''" class="interactive-authoring-media-preview" draggable="false">
<label class="interactive-authoring-media-action">
    <span x-text="pair.left.image_path ? 'Replace image' : 'Upload image'"></span>
    <input type="file" class="sr-only" accept="image/jpeg,image/png,image/webp" :disabled="activityType !== 'matching' || pair.left.imageUploading" @change="uploadImage('pairs', index, 'left', $event.target.files[0]); $event.target.value = ''">
</label>
<button type="button" @click="openImageLibrary('pairs', index, 'left', $event.currentTarget)" class="interactive-authoring-media-action">Choose from Image Library</button>
<button type="button" x-cloak x-show="pair.left.image_path" @click="removeImage('pairs', index, 'left')" class="interactive-authoring-media-remove">Remove image</button>
<label x-cloak x-show="pair.left.image_path" class="block text-xs font-semibold text-gray-700">
    Image alt text
    <input type="text" :name="`configuration[pairs][${index}][left][image_alt]`" x-model="pair.left.image_alt" maxlength="500" :required="Boolean(pair.left.image_path)" :disabled="activityType !== 'matching'" class="mt-1 w-full rounded-lg border-gray-300 text-sm">
</label>
<p x-cloak x-show="pair.left.imageUploading" role="status" class="text-xs text-gray-600">Uploading image...</p>
<p x-cloak x-show="pair.left.imageError" x-text="pair.left.imageError" role="alert" class="text-xs text-red-600"></p>
```

Add this explicit right-side block beside the existing right text field:

```html
<input type="hidden" :name="`configuration[pairs][${index}][right][image_path]`" :value="pair.right.image_path || ''" :disabled="activityType !== 'matching'">
<img x-cloak x-show="pair.right.image_url" :src="pair.right.image_url" :alt="pair.right.image_alt || ''" class="interactive-authoring-media-preview" draggable="false">
<label class="interactive-authoring-media-action">
    <span x-text="pair.right.image_path ? 'Replace image' : 'Upload image'"></span>
    <input type="file" class="sr-only" accept="image/jpeg,image/png,image/webp" :disabled="activityType !== 'matching' || pair.right.imageUploading" @change="uploadImage('pairs', index, 'right', $event.target.files[0]); $event.target.value = ''">
</label>
<button type="button" @click="openImageLibrary('pairs', index, 'right', $event.currentTarget)" class="interactive-authoring-media-action">Choose from Image Library</button>
<button type="button" x-cloak x-show="pair.right.image_path" @click="removeImage('pairs', index, 'right')" class="interactive-authoring-media-remove">Remove image</button>
<label x-cloak x-show="pair.right.image_path" class="block text-xs font-semibold text-gray-700">
    Image alt text
    <input type="text" :name="`configuration[pairs][${index}][right][image_alt]`" x-model="pair.right.image_alt" maxlength="500" :required="Boolean(pair.right.image_path)" :disabled="activityType !== 'matching'" class="mt-1 w-full rounded-lg border-gray-300 text-sm">
</label>
<p x-cloak x-show="pair.right.imageUploading" role="status" class="text-xs text-gray-600">Uploading image...</p>
<p x-cloak x-show="pair.right.imageError" x-text="pair.right.imageError" role="alert" class="text-xs text-red-600"></p>
```

Change the right text input to `:required="!pair.right.image_path"`. Preserve the relationship dots between the two side editors and keep the Remove Pair button unchanged.

- [ ] **Step 5: Add the same controls to Sequencing items**

Change the item text input to `:required="!item.image_path"`, then add:

```html
<input type="hidden" :name="`configuration[items][${index}][image_path]`" :value="item.image_path || ''" :disabled="activityType !== 'sequencing'">
<img x-cloak x-show="item.image_url" :src="item.image_url" :alt="item.image_alt || ''" class="interactive-authoring-media-preview" draggable="false">
<label class="interactive-authoring-media-action">
    <span x-text="item.image_path ? 'Replace image' : 'Upload image'"></span>
    <input type="file" class="sr-only" accept="image/jpeg,image/png,image/webp" :disabled="activityType !== 'sequencing' || item.imageUploading" @change="uploadImage('items', index, null, $event.target.files[0]); $event.target.value = ''">
</label>
<button type="button" @click="openImageLibrary('items', index, null, $event.currentTarget)" class="interactive-authoring-media-action">Choose from Image Library</button>
<button type="button" x-cloak x-show="item.image_path" @click="removeImage('items', index, null)" class="interactive-authoring-media-remove">Remove image</button>
<label x-cloak x-show="item.image_path" class="block text-xs font-semibold text-gray-700">
    Image alt text
    <input type="text" :name="`configuration[items][${index}][image_alt]`" x-model="item.image_alt" maxlength="500" :required="Boolean(item.image_path)" :disabled="activityType !== 'sequencing'" class="mt-1 w-full rounded-lg border-gray-300 text-sm">
</label>
<p x-cloak x-show="item.imageUploading" role="status" class="text-xs text-gray-600">Uploading image...</p>
<p x-cloak x-show="item.imageError" x-text="item.imageError" role="alert" class="text-xs text-red-600"></p>
```

- [ ] **Step 6: Add one shared library picker dialog**

Render one dialog in `interactive-activity-fields.blade.php` after both builders. It must use `imageLibraryOpen`, close on Escape and overlay click, show loading/error states, loop over `imageLibraryImages`, render contained previews with empty alt because the images are selectable controls, call `selectLibraryImage(image)`, and include a labelled Close button. Use `role="dialog"`, `aria-modal="true"`, `aria-labelledby="activity-image-library-title"`, and a scrollable responsive grid.

- [ ] **Step 7: Add bounded authoring media styles**

Add these component classes in `resources/css/components.css`:

```css
.interactive-authoring-media-preview {
    @apply h-24 w-full rounded-lg border border-gray-200 bg-white object-contain p-1;
}
.interactive-authoring-media-action {
    @apply inline-flex min-h-11 cursor-pointer items-center justify-center rounded-lg border border-purple-200 px-3 py-2 text-xs font-semibold text-purple-700 focus-within:outline focus-within:outline-2 focus-within:outline-offset-2 focus-within:outline-purple-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-purple-700;
}
.interactive-authoring-media-remove {
    @apply inline-flex min-h-11 items-center justify-center rounded-lg border border-red-200 px-3 py-2 text-xs font-semibold text-red-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-purple-700;
}
```

Adjust the authoring row grids so each content editor can stack its text and media controls without shrinking the drag handle, relationship marker, position label, or remove-row action.

- [ ] **Step 8: Run authoring UI tests and build CSS**

Run:

```powershell
node --test tests/JavaScript/interactive-activity-authoring.test.mjs
php artisan test tests/Feature/Instructor/InteractiveActivityAuthoringTest.php
npm run build
```

Expected: all tests pass and Vite completes without template or Tailwind errors.

- [ ] **Step 9: Commit the shared Create/Edit UI**

```powershell
git add resources/views/instructor/topics/create.blade.php resources/views/instructor/topics/edit-interactive-activity.blade.php resources/views/instructor/topics/partials/interactive-activity-fields.blade.php resources/views/instructor/topics/partials/matching-builder.blade.php resources/views/instructor/topics/partials/sequencing-builder.blade.php resources/css/components.css tests/Feature/Instructor/InteractiveActivityAuthoringTest.php
git commit -m "feat: add activity image authoring controls"
```

---

### Task 6: Make learner interaction state media-aware

**Files:**

- Modify: `tests/JavaScript/matching-activity.test.mjs`
- Modify: `tests/JavaScript/sequencing-activity.test.mjs`
- Modify: `resources/js/matching-activity.js`
- Modify: `resources/js/sequencing-activity.js`

**Interfaces:**

- Consumes: learner items containing `value`, `image_url`, and `image_alt`.
- Produces: accessible text-or-alt labels, image-failure state, and explicit Matching geometry refresh hooks.
- Preserves: connection and reorder state machines and network request bodies.

- [ ] **Step 1: Add failing Matching label and geometry-refresh tests**

```js
test('image-only matching items use alt text in endpoint and connection labels', () => {
    const activity = createMatchingActivity({
        leftItems: [{ id: 'left-image', value: '', image_alt: 'Water cycle diagram' }],
        rightItems: [{ id: 'right-text', value: 'Evaporation' }],
    });

    assert.equal(activity.itemLabel(activity.leftItems[0]), 'Water cycle diagram');
    assert.match(activity.endpointLabel('left', 'left-image'), /Water cycle diagram/);
    activity.matchedPairs = [{ left_id: 'left-image', right_id: 'right-text' }];
    assert.match(activity.endpointLabel('left', 'left-image'), /connected to Evaporation/);
});

test('matching refreshes dot geometry after image load and image failure', () => {
    const activity = createMatchingActivity();
    let refreshes = 0;
    activity.scheduleConnectorRefresh = () => { refreshes += 1; return activity; };

    activity.mediaLoaded();
    activity.mediaFailed('left', 'left-image');

    assert.equal(refreshes, 2);
    assert.equal(activity.isMediaFailed('left', 'left-image'), true);
});
```

- [ ] **Step 2: Add failing Sequencing label and payload tests**

```js
test('image-only sequencing items use alt text in drag announcements', () => {
    const activity = createSequencingActivity({
        items: [{ id: 'one', value: '', image_url: '/storage/one.png', image_alt: 'First illustrated step' }],
        initialOrder: ['one'],
    });

    assert.equal(activity.itemLabel(activity.items[0]), 'First illustrated step');
    assert.match(activity.announcement('Picked up', 0), /First illustrated step/);
});

test('sequencing payload replacement retains image fields and clears failed media state', () => {
    const activity = createSequencingActivity();
    activity.failedMedia.one = true;
    activity.loadPayload({ items: [{ id: 'one', value: '', image_url: '/storage/one.png', image_alt: 'First step' }] }, 'practice');

    assert.equal(activity.itemFor('one').image_url, '/storage/one.png');
    assert.deepEqual(activity.failedMedia, {});
});
```

- [ ] **Step 3: Run both JavaScript suites and verify RED**

Run:

```powershell
node --test tests/JavaScript/matching-activity.test.mjs tests/JavaScript/sequencing-activity.test.mjs
```

Expected: `itemLabel`, media failure, and media layout methods are missing.

- [ ] **Step 4: Implement Matching media labels and layout events**

Add `failedMedia: {}` to Matching state and these methods:

```js
mediaKey(side, id) {
    return `${side}:${id}`;
},

itemLabel(item) {
    return item?.value?.trim?.() || item?.image_alt?.trim?.() || 'Matching item';
},

isMediaFailed(side, id) {
    return this.failedMedia[this.mediaKey(side, id)] === true;
},

mediaLoaded() {
    return this.scheduleConnectorRefresh();
},

mediaFailed(side, id) {
    this.failedMedia[this.mediaKey(side, id)] = true;
    return this.scheduleConnectorRefresh();
},
```

Change `endpointLabel()` to resolve its own item and call `itemLabel()`. Change `labelFor()` to return `itemLabel(foundItem)`. Reset `failedMedia` inside `loadPayload()`. Do not change `connectorPoint()`, `connectionLine()`, request bodies, result handling, Retry, or Practice logic.

- [ ] **Step 5: Implement Sequencing media labels and failure state**

Add `failedMedia: {}`, `itemLabel(item)`, `isMediaFailed(id)`, and `mediaFailed(id)`. Use `itemLabel()` in `announcement()`, pointer-drop labels, cancellation labels, and accessible handle labels exposed to Blade. Clear `failedMedia` in `loadPayload()` and reset methods. Keep the order array and drag session ID-only.

- [ ] **Step 6: Run all learner JavaScript tests**

Run:

```powershell
node --test tests/JavaScript/matching-activity.test.mjs tests/JavaScript/sequencing-activity.test.mjs tests/JavaScript/interactive-activity.test.mjs
```

Expected: all existing interaction, evaluation, Retry, Practice, audio, and new media tests pass.

- [ ] **Step 7: Commit media-aware learner state**

```powershell
git add resources/js/matching-activity.js resources/js/sequencing-activity.js tests/JavaScript/matching-activity.test.mjs tests/JavaScript/sequencing-activity.test.mjs
git commit -m "feat: support media in activity interactions"
```

---

### Task 7: Render responsive learner media without disturbing controls

**Files:**

- Modify: `tests/Feature/Learner/InteractiveActivityRenderingTest.php`
- Modify: `resources/views/learner/lessons/partials/interactive-activities/matching.blade.php`
- Modify: `resources/views/learner/lessons/partials/interactive-activities/sequencing.blade.php`
- Modify: `resources/css/components.css`

**Interfaces:**

- Consumes: Task 1 learner payload media fields and Task 6 Alpine methods.
- Produces: contained Matching images, compact Sequencing thumbnails, missing-image fallbacks, and unchanged dot/handle DOM targets.

- [ ] **Step 1: Add failing rendering tests for Matching and Sequencing media**

Add this test:

```php
public function test_matching_and_sequencing_render_accessible_responsive_item_media(): void
{
    $matching = view('learner.lessons.partials.interactive-activities.matching', [
        'activity' => ['id' => 'matching-media', 'payload' => [
            'left_items' => [['id' => 'left-1', 'value' => '', 'image_url' => '/storage/source.png', 'image_alt' => 'Source diagram']],
            'right_items' => [['id' => 'right-1', 'value' => 'Target', 'image_url' => null, 'image_alt' => null]],
        ]],
    ])->render();
    $this->assertStringContainsString('class="interactive-activity-item-image interactive-match-item-image"', $matching);
    $this->assertStringContainsString('alt="Source diagram"', $matching);
    $this->assertStringContainsString('@load="mediaLoaded()"', $matching);
    $this->assertStringContainsString("@error=\"mediaFailed('left', 'left-1')\"", $matching);
    $this->assertStringContainsString('data-match-dot-side="left"', $matching);
    $this->assertStringContainsString('Image unavailable: Source diagram', $matching);

    $sequencing = view('learner.lessons.partials.interactive-activities.sequencing', [
        'activity' => ['id' => 'sequencing-media', 'payload' => ['items' => [
            ['id' => 'item-1', 'value' => '', 'image_url' => '/storage/step.png', 'image_alt' => 'Illustrated first step'],
        ]]],
    ])->render();
    $this->assertStringContainsString('interactive-sequence-item-image', $sequencing);
    $this->assertStringContainsString(':alt="itemFor(itemId).image_alt"', $sequencing);
    $this->assertStringContainsString('draggable="false"', $sequencing);
    $this->assertStringContainsString('@pointerdown.prevent.stop="beginPointerDrag(index, $event)"', $sequencing);
}
```

- [ ] **Step 2: Run the rendering test and verify RED**

Run:

```powershell
php artisan test tests/Feature/Learner/InteractiveActivityRenderingTest.php --filter=responsive_item_media
```

Expected: media classes, image events, and fallbacks are absent.

- [ ] **Step 3: Render Matching media inside a content wrapper**

For each static left/right item card, wrap the image and optional text in `.interactive-match-item-content`. Set `@php($matchSide = 'left')` immediately before the left-item loop and `@php($matchSide = 'right')` immediately before the right-item loop, then render the image only when `image_url` is present:

```blade
<img src="{{ $item['image_url'] }}"
     alt="{{ $item['image_alt'] }}"
     loading="lazy"
     decoding="async"
     draggable="false"
     @load="mediaLoaded()"
     @error="mediaFailed(@js($matchSide), @js($item['id']))"
     x-show="!isMediaFailed(@js($matchSide), @js($item['id']))"
     class="interactive-activity-item-image interactive-match-item-image">
```

Render text only when nonblank. Render a failure message containing `Image unavailable: {alt}` when `isMediaFailed()` is true. Keep the endpoint button outside the content wrapper at the current inner edge, with unchanged `data-match-dot-side` and `data-match-id` attributes. Change endpoint label calls to `endpointLabel(side, id)`.

- [ ] **Step 4: Render compact Sequencing media and drag-overlay media**

Within each row's content region, render an Alpine-bound image when `itemFor(itemId).image_url` exists and has not failed:

```html
<img x-show="itemFor(itemId).image_url && !isMediaFailed(itemId)"
     :src="itemFor(itemId).image_url"
     :alt="itemFor(itemId).image_alt"
     @error="mediaFailed(itemId)"
     draggable="false"
     class="interactive-activity-item-image interactive-sequence-item-image">
```

Render text only when nonblank and render `Image unavailable: {alt}` after failure. Update the handle `aria-label` to use `itemLabel(itemFor(itemId))`. Add the same image to the drag overlay with a smaller overlay class. Keep the handle, pointer bindings, position, feedback labels, insertion bar, and badges in their current roles.

- [ ] **Step 5: Add responsive learner media styles**

Add:

```css
.interactive-activity-item-image {
    @apply block max-w-full rounded-lg bg-white object-contain;
}
.interactive-match-item-content {
    @apply flex min-w-0 flex-1 flex-col gap-2;
}
.interactive-match-item-image {
    width: 100%;
    max-height: 10rem;
}
.interactive-sequence-item-content {
    @apply flex min-w-0 flex-1 items-center gap-3;
}
.interactive-sequence-item-image {
    @apply h-16 w-20 shrink-0;
}
.interactive-sequence-overlay-image {
    @apply h-12 w-16 shrink-0;
}
.interactive-item-media-fallback {
    @apply text-xs font-medium text-amber-800;
}
```

Under the existing narrow container query, stack only `.interactive-sequence-item-content` while retaining bounded image height. Do not change `.interactive-match-grid`, `.interactive-match-dot`, `.interactive-match-svg`, or handle touch-action rules beyond spacing needed for the content wrapper.

- [ ] **Step 6: Run rendering, interaction, and build checks**

Run:

```powershell
php artisan test tests/Feature/Learner/InteractiveActivityRenderingTest.php
node --test tests/JavaScript/matching-activity.test.mjs tests/JavaScript/sequencing-activity.test.mjs
npm run build
```

Expected: all tests pass and the production bundle builds.

- [ ] **Step 7: Commit learner rendering**

```powershell
git add resources/views/learner/lessons/partials/interactive-activities/matching.blade.php resources/views/learner/lessons/partials/interactive-activities/sequencing.blade.php resources/css/components.css tests/Feature/Learner/InteractiveActivityRenderingTest.php
git commit -m "feat: render activity item images"
```

---

### Task 8: Full regression, browser QA, and verification record

**Files:**

- Create: `docs/superpowers/verification/2026-09-20-interactive-activity-item-images.md`
- Verify only: all implementation files from Tasks 1-7.

**Interfaces:**

- Consumes: the completed feature.
- Produces: recorded automated and browser evidence with no destructive database operations.

- [ ] **Step 1: Format changed PHP files**

Run:

```powershell
vendor/bin/pint --dirty
```

Expected: Pint completes successfully. Review its diff and keep only formatting changes in task files.

- [ ] **Step 2: Run focused JavaScript suites**

```powershell
node --test tests/JavaScript/interactive-activity-authoring.test.mjs tests/JavaScript/matching-activity.test.mjs tests/JavaScript/sequencing-activity.test.mjs tests/JavaScript/interactive-activity.test.mjs tests/JavaScript/pointer-reorder.test.mjs
```

Expected: all tests pass with no warnings or unhandled rejections.

- [ ] **Step 3: Run focused PHP suites**

```powershell
php artisan test tests/Unit/Services/Learning/InteractiveActivityHandlerTest.php tests/Feature/Instructor/InteractiveActivityAuthoringTest.php tests/Feature/Instructor/InteractiveActivityImageLibraryTest.php tests/Feature/Instructor/InstructorImageLibraryThemeTest.php tests/Feature/Learner/InteractiveActivityRenderingTest.php tests/Feature/Learner/InteractiveActivityProgressTest.php tests/Feature/Learner/InteractiveActivityProgressIsolationTest.php tests/Feature/Learner/InteractiveActivitySchemaTest.php
```

Expected: all tests pass against the isolated test database.

- [ ] **Step 4: Run the full regression suite**

```powershell
php artisan test
```

Expected: the full Laravel suite passes. Do not substitute any database reset, wipe, truncation, drop, recreation, or reseed command.

- [ ] **Step 5: Build production assets**

```powershell
npm run build
```

Expected: Vite exits successfully and publishes the current source bundle without unresolved imports or Tailwind errors.

- [ ] **Step 6: Run authenticated authoring browser QA**

Use the existing local application and authenticated instructor/admin sessions. Verify Create and Edit for Matching and Sequencing with these cases:

1. text-only items;
2. image-only items;
3. mixed text/image items;
4. upload, immediate preview, Replace, Remove, and library selection;
5. required alt errors and text-or-image errors;
6. cancel after upload leaves the asset in the library;
7. referenced library deletion is blocked;
8. Preview evaluates without persisting activity or learner progress.

Record viewport, route, and observed result for each case.

- [ ] **Step 7: Run learner Matching browser QA**

At desktop and mobile widths, verify temporary and persistent lines, correct/incorrect states, Retry, Continue, keyboard endpoint selection, touch endpoints, delayed image load, missing-image fallback, scrolling, container resize, viewport resize, and orientation change. Confirm every line remains centered on its dot and no image covers a dot or status label.

- [ ] **Step 8: Run learner Sequencing browser QA**

At desktop and mobile widths, verify compact thumbnails, image-only labels, pointer/touch/keyboard reorder, drag overlay, insertion bar, current positions, correct/incorrect feedback, Retry preserving the arrangement, Continue after success, and normal page scrolling outside the drag handle.

- [ ] **Step 9: Write the verification record**

Create `docs/superpowers/verification/2026-09-20-interactive-activity-item-images.md` with:

```markdown
# Interactive Activity Item Images Verification

**Date:** 2026-09-20
**Automated checks:** Record each exact command, exit code, and pass/fail count.
**Matching browser QA:** Record desktop, mobile, keyboard, touch, geometry, Retry, and Continue results.
**Sequencing browser QA:** Record desktop, mobile, drag modes, feedback, Retry, and Continue results.
**Authoring QA:** Record Create, Edit, Preview, upload, library selection, Replace, Remove, validation, and deletion-guard results.
**Database safety:** Confirm that no reset, wipe, truncate, drop, recreation, or destructive reseed command was executed.
**Outstanding issues:** Write `None` only when every required check passed; otherwise record the exact failing check and evidence.
```

- [ ] **Step 10: Review the final diff and commit verification**

Run:

```powershell
git diff --check
git status --short
git diff --stat
```

Confirm that the unrelated `storage/framework/lsp-b7c5039063be9f4e.php` remains untracked and unstaged. Then commit only feature and verification files:

```powershell
git add app/Http/Controllers/Instructor/ImageLibraryController.php app/Services/Learning/InteractiveActivities resources/js/interactive-activity-authoring.js resources/js/matching-activity.js resources/js/sequencing-activity.js resources/views/instructor/topics/create.blade.php resources/views/instructor/topics/edit-interactive-activity.blade.php resources/views/instructor/topics/partials/interactive-activity-fields.blade.php resources/views/instructor/topics/partials/matching-builder.blade.php resources/views/instructor/topics/partials/sequencing-builder.blade.php resources/views/learner/lessons/partials/interactive-activities/matching.blade.php resources/views/learner/lessons/partials/interactive-activities/sequencing.blade.php resources/css/components.css tests/Unit/Services/Learning/InteractiveActivityHandlerTest.php tests/Feature/Instructor/InteractiveActivityAuthoringTest.php tests/Feature/Instructor/InteractiveActivityImageLibraryTest.php tests/Feature/Learner/InteractiveActivityRenderingTest.php tests/JavaScript/interactive-activity-authoring.test.mjs tests/JavaScript/matching-activity.test.mjs tests/JavaScript/sequencing-activity.test.mjs docs/superpowers/verification/2026-09-20-interactive-activity-item-images.md
git commit -m "test: verify activity item images"
```
