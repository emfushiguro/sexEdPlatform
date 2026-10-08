# Reliable 100 MB Video Uploads Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Reliably accept lesson-topic video files through 100 MiB, reject larger files before transfer, and show accessible upload progress on both topic forms.

**Architecture:** Keep the existing single-request Laravel public-disk upload. Align the Hostinger PHP request envelope with the existing Laravel limit, return JSON from both AJAX topic mutations, and use one dependency-free browser module plus one shared Blade partial for validation and progress UI.

**Tech Stack:** Laravel 12, PHP 8.2+, Blade, native `XMLHttpRequest`/`FormData`, Tailwind CSS, Node's built-in test runner, PHPUnit 11, Vite 7.

## Global Constraints

- The video-file boundary is exactly 100 MiB: 104,857,600 bytes or 102,400 KiB.
- Set `upload_max_filesize=100M` and `post_max_size=110M`.
- A 118 MB video must be rejected in the browser before any request begins.
- Keep the existing `storage/app/public/videos` storage location and `/storage/videos/...` URLs.
- Add no database migration, dependency, compression, transcoding, chunking, or third-party storage service.
- Preserve standard non-video form submission when no local video file is selected.
- Never delete an existing video until its replacement is stored and the topic update succeeds.
- Do not stage or overwrite the user's existing `docs/FRESH_SERVER_SETUP.md` modifications.
- Tests may use only the isolated `cc_db_test` database configured by `phpunit.xml`; never reset development data.

---

### Task 1: Align the PHP and Laravel 100 MiB boundary

**Files:**
- Create: `public/.user.ini`
- Create: `tests/Feature/Instructor/VideoUploadTest.php`
- Verify: `app/Http/Controllers/Instructor/TopicController.php:87-89,349-351`

**Interfaces:**
- Consumes: Existing instructor topic routes and the controller's `max:102400` validation.
- Produces: A PHP request envelope of 100M per file and 110M per POST, plus boundary regression coverage used by later tasks.

- [ ] **Step 1: Write the failing runtime-configuration and boundary tests**

Create `tests/Feature/Instructor/VideoUploadTest.php`:

```php
<?php

namespace Tests\Feature\Instructor;

use App\Models\Lesson;
use App\Models\LessonTopic;
use App\Models\Module;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class VideoUploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_web_runtime_is_configured_for_the_100_mib_video_boundary(): void
    {
        $this->assertFileExists(public_path('.user.ini'));
        $settings = parse_ini_file(public_path('.user.ini'));

        $this->assertSame('100M', $settings['upload_max_filesize'] ?? null);
        $this->assertSame('110M', $settings['post_max_size'] ?? null);
    }

    public function test_instructor_can_upload_a_video_at_the_100_mib_boundary(): void
    {
        Storage::fake('public');
        [$instructor, $lesson] = $this->topicAuthoringFixture();

        $response = $this->actingAs($instructor)
            ->post(route('instructor.topics.store'), [
                'lesson_id' => $lesson->id,
                'title' => 'Boundary video',
                'type' => 'video',
                'duration' => 3,
                'video_source' => 'upload',
                'video_file' => UploadedFile::fake()->create(
                    'boundary.mp4',
                    102400,
                    'video/mp4',
                ),
            ]);

        $response->assertRedirect(route('instructor.lessons.show', $lesson));
        $this->assertDatabaseHas('lesson_topics', [
            'lesson_id' => $lesson->id,
            'title' => 'Boundary video',
            'video_provider' => 'local',
        ]);
        $this->assertCount(1, Storage::disk('public')->allFiles('videos'));
    }

    public function test_instructor_cannot_upload_a_video_over_the_100_mib_boundary(): void
    {
        Storage::fake('public');
        [$instructor, $lesson] = $this->topicAuthoringFixture();

        $response = $this->actingAs($instructor)
            ->from(route('instructor.topics.create', ['lesson' => $lesson]))
            ->post(route('instructor.topics.store'), [
                'lesson_id' => $lesson->id,
                'title' => 'Oversized video',
                'type' => 'video',
                'duration' => 3,
                'video_source' => 'upload',
                'video_file' => UploadedFile::fake()->create(
                    'oversized.mp4',
                    102401,
                    'video/mp4',
                ),
            ]);

        $response->assertSessionHasErrors('video_file');
        $this->assertDatabaseMissing('lesson_topics', [
            'lesson_id' => $lesson->id,
            'title' => 'Oversized video',
        ]);
        $this->assertSame([], Storage::disk('public')->allFiles('videos'));
    }

    /** @return array{User, Lesson} */
    private function topicAuthoringFixture(): array
    {
        $instructor = User::factory()->createOne();
        $instructor->assignRole('instructor');
        $module = Module::factory()->create(['created_by' => $instructor->id]);
        $lesson = Lesson::factory()->create(['module_id' => $module->id]);

        return [$instructor, $lesson];
    }
}
```

- [ ] **Step 2: Run the test and verify the configuration test fails**

Run:

```bash
php artisan test tests/Feature/Instructor/VideoUploadTest.php
```

Expected: FAIL in `test_web_runtime_is_configured_for_the_100_mib_video_boundary` because `public/.user.ini` does not exist. Confirm the two Laravel boundary tests pass, demonstrating that application validation already uses the requested 100 MiB boundary.

- [ ] **Step 3: Add the minimum per-directory PHP configuration**

Create `public/.user.ini`:

```ini
upload_max_filesize = 100M
post_max_size = 110M
```

Do not add memory or execution-time overrides; Hostinger enforces plan-specific ceilings and neither setting is needed to copy an uploaded file to the public disk.

- [ ] **Step 4: Re-run the focused test**

Run:

```bash
php artisan test tests/Feature/Instructor/VideoUploadTest.php
```

Expected: PASS, 3 tests and 0 failures.

- [ ] **Step 5: Commit only Task 1 files**

```bash
git add public/.user.ini tests/Feature/Instructor/VideoUploadTest.php
git commit -m "fix(upload): align 100 MB request limits"
```

---

### Task 2: Make video AJAX updates safe and redirectable

**Files:**
- Modify: `tests/Feature/Instructor/VideoUploadTest.php`
- Modify: `app/Http/Controllers/Instructor/TopicController.php:330-535`

**Interfaces:**
- Consumes: `X-Requested-With: XMLHttpRequest` and `Accept: application/json` from Task 3's browser module.
- Produces: `{success: true, message: string, redirect: string}` for AJAX updates; defers deletion of `$oldVideoPathToDelete` until after `$topic->update($validated)`.

- [ ] **Step 1: Add JSON-response and replacement-safety tests**

Add these imports to `VideoUploadTest.php`:

```php
use Illuminate\Contracts\Filesystem\Factory as FilesystemFactory;
use Illuminate\Contracts\Filesystem\Filesystem;
use Mockery;
use RuntimeException;
```

Add these methods before `topicAuthoringFixture()`:

```php
public function test_video_create_ajax_response_contains_the_lesson_redirect(): void
{
    Storage::fake('public');
    [$instructor, $lesson] = $this->topicAuthoringFixture();

    $response = $this->actingAs($instructor)
        ->withHeaders([
            'Accept' => 'application/json',
            'X-Requested-With' => 'XMLHttpRequest',
        ])
        ->post(route('instructor.topics.store'), [
            'lesson_id' => $lesson->id,
            'title' => 'AJAX video',
            'type' => 'video',
            'duration' => 3,
            'video_source' => 'upload',
            'video_file' => UploadedFile::fake()->create('ajax.mp4', 100, 'video/mp4'),
        ]);

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('redirect', route('instructor.lessons.show', $lesson));
}

public function test_video_update_ajax_response_contains_the_lesson_redirect(): void
{
    Storage::fake('public');
    [$instructor, $lesson] = $this->topicAuthoringFixture();
    Storage::disk('public')->put('videos/old.mp4', 'old video');
    $topic = LessonTopic::factory()->create([
        'lesson_id' => $lesson->id,
        'type' => 'video',
        'video_provider' => 'local',
        'video_file_path' => 'videos/old.mp4',
    ]);

    $response = $this->actingAs($instructor)
        ->withHeaders([
            'Accept' => 'application/json',
            'X-Requested-With' => 'XMLHttpRequest',
        ])
        ->put(route('instructor.topics.update', $topic), [
            'title' => 'Updated video',
            'type' => 'video',
            'duration' => 3,
            'video_source' => 'upload',
            'video_file' => UploadedFile::fake()->create('replacement.mp4', 100, 'video/mp4'),
        ]);

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('redirect', route('instructor.lessons.show', $lesson));
    Storage::disk('public')->assertMissing('videos/old.mp4');
    $this->assertNotSame('videos/old.mp4', $topic->fresh()->video_file_path);
}

public function test_failed_replacement_storage_does_not_delete_the_existing_video(): void
{
    [$instructor, $lesson] = $this->topicAuthoringFixture();
    $topic = LessonTopic::factory()->create([
        'lesson_id' => $lesson->id,
        'type' => 'video',
        'video_provider' => 'local',
        'video_file_path' => 'videos/old.mp4',
    ]);

    $deleted = false;
    $disk = Mockery::mock(Filesystem::class);
    $disk->shouldReceive('delete')
        ->zeroOrMoreTimes()
        ->andReturnUsing(function () use (&$deleted): bool {
            $deleted = true;

            return true;
        });
    $disk->shouldReceive('putFileAs')
        ->once()
        ->andThrow(new RuntimeException('disk full'));

    $manager = Mockery::mock(FilesystemFactory::class);
    $manager->shouldReceive('disk')->with('public')->andReturn($disk);
    Storage::swap($manager);

    $this->withoutExceptionHandling();

    try {
        $this->actingAs($instructor)
            ->put(route('instructor.topics.update', $topic), [
                'title' => 'Failed replacement',
                'type' => 'video',
                'duration' => 3,
                'video_source' => 'upload',
                'video_file' => UploadedFile::fake()->create('replacement.mp4', 100, 'video/mp4'),
            ]);

        $this->fail('Expected replacement storage to fail.');
    } catch (RuntimeException $exception) {
        $this->assertSame('disk full', $exception->getMessage());
    }

    $this->assertFalse($deleted);
    $this->assertSame('videos/old.mp4', $topic->fresh()->video_file_path);
}
```

- [ ] **Step 2: Run the three new tests and verify the two new behaviors fail for the intended reasons**

Run:

```bash
php artisan test tests/Feature/Instructor/VideoUploadTest.php --filter="video_create_ajax|video_update_ajax|failed_replacement"
```

Expected:

- `test_video_create_ajax_response_contains_the_lesson_redirect` passes as a characterization of the existing create response.
- `test_video_update_ajax_response_contains_the_lesson_redirect` fails because update returns a redirect instead of JSON.
- `test_failed_replacement_storage_does_not_delete_the_existing_video` fails because current code calls `delete()` before `store()`.

- [ ] **Step 3: Store first and delete only after persistence**

In `TopicController::update()`, initialize a deferred path immediately before the video-handling block:

```php
$oldVideoPathToDelete = null;
```

Replace the local-file and URL branches with:

```php
if ($validated['type'] === 'video') {
    if ($request->hasFile('video_file')) {
        $newVideoPath = $request->file('video_file')->store('videos', 'public');
        $oldVideoPathToDelete = $topic->video_file_path;
        $validated['video_file_path'] = $newVideoPath;
        $validated['video_provider'] = 'local';
        $validated['video_id'] = null;
    } elseif (! empty($validated['video_url'])) {
        $videoData = VideoEmbedHelper::parseVideoUrl($validated['video_url']);
        $oldVideoPathToDelete = $topic->video_file_path;
        $validated['video_provider'] = $videoData['provider'];
        $validated['video_id'] = $videoData['video_id'];
        $validated['video_file_path'] = null;
    }

    $validated['text_content'] = $request->input('video_description');
}
```

Immediately after `$topic->update($validated);`, add:

```php
if ($oldVideoPathToDelete) {
    Storage::disk('public')->delete($oldVideoPathToDelete);
}
```

- [ ] **Step 4: Return JSON from update when requested**

Immediately before the existing redirect at the end of `update()`, add:

```php
if ($request->wantsJson() || $request->ajax()) {
    return response()->json([
        'success' => true,
        'message' => 'Topic updated successfully!',
        'redirect' => route($this->routeName('lessons.show'), $topic->lesson),
    ]);
}
```

Keep the existing non-AJAX redirect unchanged.

- [ ] **Step 5: Run the entire upload feature test**

Run:

```bash
php artisan test tests/Feature/Instructor/VideoUploadTest.php
```

Expected: PASS, 6 tests and 0 failures.

- [ ] **Step 6: Commit only Task 2 files**

```bash
git add app/Http/Controllers/Instructor/TopicController.php tests/Feature/Instructor/VideoUploadTest.php
git commit -m "fix(upload): preserve videos on failed replace"
```

---

### Task 3: Implement tested upload validation and progress calculations

**Files:**
- Create: `resources/js/video-upload-form.js`
- Create: `tests/Unit/JavaScript/video-upload-form.test.js`

**Interfaces:**
- Consumes: A form with `data-video-upload-form`, a file input with `data-video-file-input`, and the overlay hooks created by Task 4.
- Produces: `VIDEO_UPLOAD_MAX_BYTES`, `allowedVideoFileError(file, maxBytes)`, `uploadPercent(loaded, total)`, `formatMiB(bytes)`, and automatic form initialization.

- [ ] **Step 1: Write failing unit tests with Node's standard library**

Create `tests/Unit/JavaScript/video-upload-form.test.js`:

```js
import test from 'node:test';
import assert from 'node:assert/strict';

import {
    VIDEO_UPLOAD_MAX_BYTES,
    allowedVideoFileError,
    formatMiB,
    uploadPercent,
} from '../../../resources/js/video-upload-form.js';

test('uses an exact 100 MiB upload boundary', () => {
    assert.equal(VIDEO_UPLOAD_MAX_BYTES, 104857600);
    assert.equal(allowedVideoFileError({
        name: 'boundary.mp4',
        size: VIDEO_UPLOAD_MAX_BYTES,
        type: 'video/mp4',
    }), null);
});

test('rejects oversized and unsupported videos before upload', () => {
    assert.match(allowedVideoFileError({
        name: 'oversized.mp4',
        size: VIDEO_UPLOAD_MAX_BYTES + 1,
        type: 'video/mp4',
    }), /100 MB or smaller/);

    assert.match(allowedVideoFileError({
        name: 'clip.ogg',
        size: 1024,
        type: 'video/ogg',
    }), /MP4, MPEG, MOV, AVI, and WebM/);
});

test('formats byte counts and clamps upload percentages', () => {
    assert.equal(formatMiB(52428800), '50.0 MB');
    assert.equal(uploadPercent(25, 100), 25);
    assert.equal(uploadPercent(120, 100), 100);
    assert.equal(uploadPercent(20, 0), 0);
});
```

- [ ] **Step 2: Run the test and verify it fails because the module is absent**

Run:

```bash
node --test tests/Unit/JavaScript/video-upload-form.test.js
```

Expected: FAIL with `ERR_MODULE_NOT_FOUND` for `resources/js/video-upload-form.js`.

- [ ] **Step 3: Create the dependency-free browser module**

Create `resources/js/video-upload-form.js`:

```js
const MEBIBYTE = 1024 * 1024;
const SUPPORTED_VIDEO_TYPES = new Set([
    'video/mp4',
    'video/mpeg',
    'video/quicktime',
    'video/x-msvideo',
    'video/webm',
]);

export const VIDEO_UPLOAD_MAX_BYTES = 100 * MEBIBYTE;

export function formatMiB(bytes) {
    return `${(Number(bytes || 0) / MEBIBYTE).toFixed(1)} MB`;
}

export function uploadPercent(loaded, total) {
    if (!Number.isFinite(total) || total <= 0) {
        return 0;
    }

    return Math.min(100, Math.max(0, Math.round((loaded / total) * 100)));
}

export function allowedVideoFileError(file, maxBytes = VIDEO_UPLOAD_MAX_BYTES) {
    if (!file) {
        return null;
    }

    if (file.size > maxBytes) {
        return `${file.name} is ${formatMiB(file.size)}. Videos must be 100 MB or smaller.`;
    }

    if (file.type && !SUPPORTED_VIDEO_TYPES.has(file.type)) {
        return 'Video must use one of these formats: MP4, MPEG, MOV, AVI, and WebM.';
    }

    return null;
}

function toggle(element, hidden) {
    element?.classList.toggle('hidden', hidden);
}

function setText(element, value) {
    if (element) {
        element.textContent = value;
    }
}

function parsePayload(xhr) {
    if (xhr.response && typeof xhr.response === 'object') {
        return xhr.response;
    }

    try {
        return JSON.parse(xhr.responseText || '{}');
    } catch {
        return {};
    }
}

export function initializeVideoUploadForm(form, xhrFactory = () => new XMLHttpRequest()) {
    const fileInput = form.querySelector('[data-video-file-input]');
    const fileName = form.querySelector('[data-video-file-name]');
    const fileError = form.querySelector('[data-video-error]');
    const overlay = document.querySelector('[data-video-upload-overlay]');
    const spinner = overlay?.querySelector('[data-upload-spinner]');
    const progressPanel = overlay?.querySelector('[data-upload-progress-panel]');
    const progressBar = overlay?.querySelector('[data-upload-progress]');
    const percentage = overlay?.querySelector('[data-upload-percentage]');
    const status = overlay?.querySelector('[data-upload-status]');
    const detail = overlay?.querySelector('[data-upload-detail]');
    const formError = document.querySelector('[data-video-upload-form-error]');
    const submitButton = form.querySelector('[type="submit"]');
    const originalButtonHtml = submitButton?.innerHTML;
    const maxBytes = Number(form.dataset.videoMaxBytes || VIDEO_UPLOAD_MAX_BYTES);

    const showError = (message) => {
        setText(formError, message);
        toggle(formError, false);
    };

    const restoreAfterFailure = (message) => {
        toggle(overlay, true);
        showError(message);
        if (submitButton) {
            submitButton.disabled = false;
            submitButton.innerHTML = originalButtonHtml;
        }
    };

    const updateProgress = (loaded, total) => {
        const percent = uploadPercent(loaded, total);
        if (progressBar) {
            progressBar.style.width = `${percent}%`;
            progressBar.setAttribute('aria-valuenow', String(percent));
        }
        setText(percentage, `${percent}%`);
        setText(detail, `${formatMiB(loaded)} of ${formatMiB(total)}`);
    };

    fileInput?.addEventListener('change', () => {
        const file = fileInput.files?.[0];
        const error = allowedVideoFileError(file, maxBytes);

        setText(fileError, error || '');
        toggle(fileError, !error);

        if (error) {
            fileInput.value = '';
            setText(fileName, 'MP4, MPEG, MOV, AVI, or WebM up to 100 MB');
            return;
        }

        if (file) {
            setText(fileName, `${file.name} (${formatMiB(file.size)})`);
        }
    });

    form.addEventListener('submit', (event) => {
        const file = fileInput?.files?.[0];
        const error = allowedVideoFileError(file, maxBytes);

        toggle(formError, true);

        if (error) {
            event.preventDefault();
            setText(fileError, error);
            toggle(fileError, false);
            return;
        }

        toggle(overlay, false);
        setText(status, file ? 'Uploading video...' : 'Saving topic...');
        toggle(spinner, Boolean(file));
        toggle(progressPanel, !file);

        if (submitButton) {
            submitButton.disabled = true;
        }

        if (!file) {
            return;
        }

        event.preventDefault();
        updateProgress(0, file.size);

        const xhr = xhrFactory();
        xhr.open(form.method || 'POST', form.action);
        xhr.responseType = 'json';
        xhr.setRequestHeader('Accept', 'application/json');
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

        const csrfToken = form.querySelector('input[name="_token"]')?.value;
        if (csrfToken) {
            xhr.setRequestHeader('X-CSRF-TOKEN', csrfToken);
        }

        xhr.upload.addEventListener('progress', (progressEvent) => {
            if (progressEvent.lengthComputable) {
                updateProgress(progressEvent.loaded, progressEvent.total);
            }
        });

        xhr.upload.addEventListener('load', () => {
            setText(status, 'Saving topic...');
            setText(detail, 'Upload complete. Finalizing the topic...');
        });

        xhr.addEventListener('load', () => {
            const payload = parsePayload(xhr);
            if (xhr.status >= 200 && xhr.status < 300 && payload.redirect) {
                window.location.assign(payload.redirect);
                return;
            }

            const message = xhr.status === 413
                ? 'The video is larger than the server request limit. Choose a video of 100 MB or less.'
                : payload.errors?.video_file?.[0]
                    || payload.message
                    || 'The video could not be uploaded. Please try again.';
            restoreAfterFailure(message);
        });

        xhr.addEventListener('error', () => {
            restoreAfterFailure('The network interrupted the upload. Check your connection and try again.');
        });

        xhr.addEventListener('abort', () => {
            restoreAfterFailure('The video upload was cancelled.');
        });

        xhr.send(new FormData(form));
    });
}

if (typeof document !== 'undefined') {
    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('[data-video-upload-form]')
            .forEach((form) => initializeVideoUploadForm(form));
    });
}
```

- [ ] **Step 4: Re-run the Node test**

Run:

```bash
node --test tests/Unit/JavaScript/video-upload-form.test.js
```

Expected: PASS, 3 tests and 0 failures.

- [ ] **Step 5: Commit only Task 3 files**

```bash
git add resources/js/video-upload-form.js tests/Unit/JavaScript/video-upload-form.test.js
git commit -m "feat(upload): add progress controller"
```

---

### Task 4: Wire the accessible progress UI into create and edit

**Files:**
- Create: `resources/views/instructor/topics/partials/upload-progress.blade.php`
- Modify: `resources/views/instructor/topics/create.blade.php:16-33,195-218,402-469,630-638`
- Modify: `resources/views/instructor/topics/edit.blade.php:15-31,42,238-253,424-432`
- Modify: `resources/js/app.js:1-12`
- Modify: `tests/Feature/Instructor/VideoUploadTest.php`

**Interfaces:**
- Consumes: `initializeVideoUploadForm()` and its `data-*` selectors from Task 3.
- Produces: Shared accessible overlay markup and identical 104,857,600-byte client limits on both authoring forms.

- [ ] **Step 1: Add a failing authoring-page markup test**

Add this method to `VideoUploadTest.php` before `topicAuthoringFixture()`:

```php
public function test_topic_authoring_pages_render_the_video_upload_progress_contract(): void
{
    [$instructor, $lesson] = $this->topicAuthoringFixture();
    $topic = LessonTopic::factory()->create([
        'lesson_id' => $lesson->id,
        'type' => 'video',
        'video_provider' => 'local',
        'video_file_path' => 'videos/current.mp4',
    ]);

    $responses = [
        $this->actingAs($instructor)
            ->get(route('instructor.topics.create', ['lesson' => $lesson])),
        $this->actingAs($instructor)
            ->get(route('instructor.topics.edit', $topic)),
    ];

    foreach ($responses as $response) {
        $response->assertOk()
            ->assertSee('data-video-upload-form', false)
            ->assertSee('data-video-max-bytes="104857600"', false)
            ->assertSee('data-video-file-input', false)
            ->assertSee('data-video-error', false)
            ->assertSee('data-video-upload-overlay', false)
            ->assertSee('data-upload-progress', false)
            ->assertSee('aria-live="polite"', false);
    }
}
```

- [ ] **Step 2: Run the markup test and verify it fails**

Run:

```bash
php artisan test tests/Feature/Instructor/VideoUploadTest.php --filter=progress_contract
```

Expected: FAIL because the create and edit forms do not contain the shared progress hooks.

- [ ] **Step 3: Create the shared progress partial**

Create `resources/views/instructor/topics/partials/upload-progress.blade.php`:

```blade
<div
    data-video-upload-overlay
    class="fixed inset-0 z-50 hidden flex items-center justify-center bg-black/50 px-4"
    role="dialog"
    aria-modal="true"
    aria-labelledby="videoUploadStatus"
>
    <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl">
        <div data-upload-spinner class="mx-auto mb-4 h-12 w-12 animate-spin rounded-full border-4 border-purple-100 border-t-purple-700"></div>

        <div data-upload-progress-panel class="hidden">
            <div class="mb-3 flex items-center justify-between gap-4">
                <span class="text-sm font-semibold text-gray-700">Upload progress</span>
                <span data-upload-percentage class="text-lg font-bold text-purple-700">0%</span>
            </div>
            <div class="h-3 overflow-hidden rounded-full bg-purple-100">
                <div
                    data-upload-progress
                    class="h-full rounded-full bg-purple-700 transition-[width] duration-150"
                    style="width: 0%"
                    role="progressbar"
                    aria-label="Video upload progress"
                    aria-valuemin="0"
                    aria-valuemax="100"
                    aria-valuenow="0"
                ></div>
            </div>
        </div>

        <p id="videoUploadStatus" data-upload-status class="mt-4 text-center text-lg font-semibold text-gray-900" aria-live="polite">
            Saving topic...
        </p>
        <p data-upload-detail class="mt-1 text-center text-sm text-gray-500">
            Please keep this page open.
        </p>
    </div>
</div>

<div
    data-video-upload-form-error
    class="mb-4 hidden rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"
    role="alert"
></div>
```

- [ ] **Step 4: Replace both loading overlays and mark both forms**

In both `create.blade.php` and `edit.blade.php`, replace the existing `#loadingOverlay` block with:

```blade
@include('instructor.topics.partials.upload-progress')
```

Add the shared form attributes while preserving each form's current action, method, ID, encoding, and classes:

```blade
data-video-upload-form
data-video-max-bytes="104857600"
```

For example, the create form becomes:

```blade
<form
    action="{{ route($contentRoutePrefix . '.topics.store') }}"
    method="POST"
    enctype="multipart/form-data"
    id="topicForm"
    data-video-upload-form
    data-video-max-bytes="104857600"
>
```

- [ ] **Step 5: Align both video inputs and inline error hooks**

On both video file inputs, replace `accept="video/*"` with:

```blade
accept=".mp4,.mpeg,.mpg,.mov,.avi,.webm,video/mp4,video/mpeg,video/quicktime,video/x-msvideo,video/webm"
data-video-file-input
aria-describedby="videoFileHelp videoFileClientError"
```

Mark the create-page filename/help node:

```blade
<p class="mt-1 text-xs text-gray-500" id="videoFileHelp" data-video-file-name>
    MP4, MPEG, MOV, AVI, or WebM up to 100 MB
</p>
```

Remove the create input's inline `onchange="updateFileName(...)"` attribute and delete the now-unused `updateFileName()` function.

Replace the edit-page format help with the same copy and hook:

```blade
<p class="mt-1 text-sm text-gray-500" id="videoFileHelp" data-video-file-name>
    MP4, MPEG, MOV, AVI, or WebM up to 100 MB
</p>
```

Add this client-error element after the help node on both pages:

```blade
<p id="videoFileClientError" data-video-error class="mt-1 hidden text-sm text-red-600" role="alert"></p>
```

Keep each existing Blade `@error('video_file')` block for authoritative server validation.

- [ ] **Step 6: Remove duplicate overlay submission code**

In the create page's existing submit listener, keep TinyMCE synchronization and excluded-image hidden inputs. Remove `loadingOverlay.classList.remove('hidden')`, `submitButton.disabled = true`, and the complete multiline assignment to `submitButton.innerHTML`, because the shared module owns those states. Also remove the now-unused `loadingOverlay` and `submitButton` declarations.

In the edit page, delete only this old listener:

```js
const topicEditForm = document.getElementById('topicEditForm');
const loadingOverlay = document.getElementById('loadingOverlay');

topicEditForm?.addEventListener('submit', function() {
    if (loadingOverlay) {
        loadingOverlay.style.display = 'flex';
    }
});
```

Do not change TinyMCE or topic-type behavior.

- [ ] **Step 7: Import the browser module**

Add this side-effect import beside the other local imports at the top of `resources/js/app.js`:

```js
import './video-upload-form';
```

- [ ] **Step 8: Run focused server and browser-logic tests**

Run:

```bash
php artisan test tests/Feature/Instructor/VideoUploadTest.php
node --test tests/Unit/JavaScript/video-upload-form.test.js
```

Expected: PASS, 7 PHP tests and 3 Node tests with 0 failures.

- [ ] **Step 9: Commit only Task 4 source and tests**

```bash
git add resources/views/instructor/topics/partials/upload-progress.blade.php resources/views/instructor/topics/create.blade.php resources/views/instructor/topics/edit.blade.php resources/js/app.js tests/Feature/Instructor/VideoUploadTest.php
git commit -m "feat(upload): show video upload progress"
```

---

### Task 5: Document deployment and verify the complete change

**Files:**
- Create: `docs/VIDEO_UPLOADS.md`
- Modify: `public/build/manifest.json`
- Modify/Create/Delete: Vite-generated files under `public/build/assets/`

**Interfaces:**
- Consumes: The completed upload boundary, JSON backend, and progress UI from Tasks 1-4.
- Produces: Hostinger operator instructions and deployable compiled assets.

- [ ] **Step 1: Add focused Hostinger deployment documentation**

Create `docs/VIDEO_UPLOADS.md`:

````markdown
# Video uploads

Lesson-topic video uploads accept MP4, MPEG, MOV, AVI, and WebM files through
100 MiB (104,857,600 bytes). Larger files must be compressed below the limit or
hosted through the existing YouTube/Vimeo URL option.

## Hostinger PHP settings

The repository's `public/.user.ini` requests these per-directory values:

```ini
upload_max_filesize = 100M
post_max_size = 110M
```

The POST limit is deliberately larger because multipart form boundaries and
topic fields are sent alongside the video. Laravel still rejects any video
larger than 100 MiB.

After deployment, open hPanel -> Websites -> Dashboard -> PHP Configuration
and confirm the effective values are 100M and 110M. If Hostinger does not apply
the committed `.user.ini`, set the same values in hPanel. Plan-level Hostinger
limits take precedence over repository settings.

## Verification

1. Select a video larger than 100 MiB and confirm the page rejects it before
   the request begins.
2. Upload a disposable small video and confirm percentage and transferred-size
   values advance.
3. Upload a disposable video close to 100 MiB and confirm it is saved under
   `storage/app/public/videos` and plays from `/storage/videos/...`.
4. Delete disposable test topics through the application's normal delete flow.

The progress UI reports bytes transferred; it does not compress the video or
increase the instructor's network upload speed.
````

- [ ] **Step 2: Run formatting/static checks before the build**

Run:

```bash
vendor/bin/pint --test app/Http/Controllers/Instructor/TopicController.php tests/Feature/Instructor/VideoUploadTest.php
git diff --check
```

Expected: both commands exit 0 with no formatting errors or whitespace errors. If Pint reports formatting changes, run it without `--test` only on the two named PHP files, inspect the diff, and repeat this step.

- [ ] **Step 3: Build deployable frontend assets**

Run:

```bash
npm run build
```

Expected: Vite exits 0 and updates `public/build/manifest.json` plus the hashed app bundle under `public/build/assets/`. Do not hand-edit generated assets.

- [ ] **Step 4: Run the focused regression checks again after the build**

Run:

```bash
php artisan test tests/Feature/Instructor/VideoUploadTest.php
node --test tests/Unit/JavaScript/video-upload-form.test.js
```

Expected: PASS, 7 PHP tests and 3 Node tests with 0 failures.

- [ ] **Step 5: Run the broader instructor regression suite**

Run:

```bash
php artisan test tests/Feature/Instructor
```

Expected: PASS with 0 failures. This suite uses `cc_db_test` and must not point at the development database.

- [ ] **Step 6: Run the full application test suite**

Run:

```bash
php artisan test
```

Expected: PASS with 0 failures. If an unrelated pre-existing failure appears, record its exact test and output instead of changing unrelated code.

- [ ] **Step 7: Inspect scope and generated output**

Run:

```bash
git status --short
git diff --stat
git diff --check
```

Expected: only `docs/VIDEO_UPLOADS.md` and Vite-generated `public/build` files remain from this task, plus the user's pre-existing `docs/FRESH_SERVER_SETUP.md` modification. Confirm no storage upload, environment file, database, or unrelated source file is staged.

- [ ] **Step 8: Commit documentation and generated assets without staging user work**

```bash
git add docs/VIDEO_UPLOADS.md public/build/manifest.json public/build/assets
git commit -m "docs(upload): add Hostinger verification"
```

- [ ] **Step 9: Perform production verification after deployment**

In Hostinger hPanel, confirm effective `upload_max_filesize=100M` and `post_max_size=110M`. Then follow `docs/VIDEO_UPLOADS.md` with an oversized file, a small disposable file, and a near-boundary disposable file. Record the browser percentage behavior and final playable URL; do not claim production acceptance based only on local tests.
