# Video Source Attribution Implementation Plan

Goal: Record optional video credits in topic authoring and show public credits in an accessible learner modal.

Inspected: `LessonTopic`, its creation/alteration migrations, shared Instructor/Admin `TopicController`, `TopicPolicy`, `ContentOwnershipGuard`, create/edit Blade forms, learner `topic-page`, `VideoEmbedHelper`, caption services/tests, native dialog and modal conventions, and the transactional `DatabaseTestCase` infrastructure.

Existing video fields cover provider, provider ID, upload path and topic instructions. Existing JSON fields serve interactive content, images, worksheets and slideshows; none is suitable for video credits. Add only nullable `video_attribution` JSON. Preserve existing records without backfill. Provider stays canonical in `video_provider`; original external URL is recorded once in attribution and reused when editing.

- [x] Add failing transactional feature and helper tests: create/update, optional fields, conditional other-license name, invalid URLs, ownership, Admin scope, legacy/upload compatibility, escaped public rendering, and YouTube/Vimeo playback.
- [x] Add incremental migration and model cast; shared attribution validation service called after existing controller authorization and before storage writes. Keep permission status private and independent of publishing. Never infer ownership, license or permission.
- [x] Tighten existing provider-aware URL helper: allow HTTP/HTTPS supported provider URLs and direct media files; only trusted provider IDs generate iframe URLs. Direct external media uses existing HTML5/Plyr rendering. Do not fetch metadata.
- [x] Share authoring partial across create/edit, preserve old input, and reuse native dialog/Alpine conventions in reusable public Blade component with escaped fields, safe links, keyboard dismissal and focus restoration.
- [x] Apply only the new migration to development and isolated test databases; verify counts and legacy rows. Run focused feature/helper/caption/upload tests, Pint, Blade compilation, Vite build and diff checks. Report manual browser checks separately.

Constraints: no reset/reseed, no dependencies, no external APIs, no player replacement, no route/publish/progress changes. All credit fields optional; an explicitly selected Other license requires its name. Unknown remains unknown. Source URLs never imply license or permission. Only public credit fields render to learners.

## Implementation and verification

- `app/Services/VideoAttributionService.php`: shared validation, license/status options, normalization and canonical source URL handling. Direct file URLs stay only in existing `video_id`.
- `app/Models/LessonTopic.php`: nullable JSON cast/fillable and source URL fallback for legacy edit forms; direct video files resolve through the existing file player path.
- `app/Http/Controllers/Instructor/TopicController.php`: service integration after existing authorization, persistence in existing transactions, and respect selected video source when hidden inputs remain populated.
- `app/Helpers/VideoEmbedHelper.php`: host-aware HTTP/HTTPS provider parsing and validated iframe IDs. Arbitrary custom iframe URLs are rejected; direct media files use HTML5.
- `database/migrations/2026_10_18_000001_add_video_attribution_to_lesson_topics.php`: additive nullable JSON, no backfill. Rollback deliberately retains recorded data. Applied to `cc_db` and `cc_db_test`; all 5 development topics retained null credits and topic/progress counts remained 5/0.
- Shared authoring partial `resources/views/instructor/topics/partials/video-attribution.blade.php` included by existing create/edit forms; Admin uses these same views.
- Reusable public `resources/views/components/video-attribution.blade.php` included by learner `topic-page.blade.php`: escaped credit fields, safe external link, native dialog, explicit Tab wrapping, Escape, Close and native focus restoration. No permission status or private notes rendered.
- Instructor and parent lesson preview templates omit a hardcoded MP4 MIME type so external WebM and other supported videos can use their actual response type. Existing caption tracks remain intact.
- Compiled Vite CSS and manifest updated, as required by this repository's deployment convention.

Executed:

1. Focused helper, attribution authoring/rendering, caption management/rendering and Admin shared-controller tests: **53 tests, 254 assertions, passed**. `php vendor/phpunit/phpunit/phpunit --do-not-cache-result` with those six test files.
2. Existing `VideoUploadTest` methods through a temporary subclass that skips schema recreation and uses the inherited transaction wrapper against provisioned `cc_db_test`: **9 tests, 91 assertions, passed**. Temporary runner removed after execution.
3. `node tests/Browser/video-attribution.mjs`: **passed** with real Blade-rendered components at 1366px and 390px; checks keyboard opening, focus wrapping, dismissal/restoration, responsive fit, escaped text, saved values and conditional form fields. Uses an unsaved model; no database writes or provider requests. Mobile screenshot visually inspected.
4. `php vendor/bin/pint --test --dirty`, `php artisan view:cache`, `npm.cmd run build`, and `git diff --check`: **passed**. Initial build/cache attempts failed from disk exhaustion; retries passed after temporary runtime-cache cleanup. Tracked old build artifacts were restored before rebuilding.

Remaining manual checks: signed-in create/edit/publish end-to-end in both portals, real provider playback and captions, physical mobile devices and screen-reader testing. The complete application test suite was not run. Deployments must apply the single migration before using the new code; no database reset or reseed is needed.
