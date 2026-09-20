# Local Video Caption Support Verification

Date: 2026-09-20
Branch: `enhancement`

## Automated checks

- `php artisan migrate:status` — exit 0; `2026_09_20_000001_create_lesson_topic_captions_table` is `[4] Ran`.
- `php artisan migrate` — exit 0; `Nothing to migrate.`
- `php artisan test ...` — exit 1 before PHPUnit; Symfony rejects the Windows CWD as nonexistent.
- `php vendor/bin/phpunit --do-not-cache-result tests/Feature/Database/VideoCaptionMigrationTest.php tests/Unit/Rules/WebVttFileTest.php tests/Feature/Instructor/VideoCaptionManagementTest.php tests/Feature/Instructor/VideoUploadTest.php tests/Feature/Learner/VideoCaptionRenderingTest.php tests/Feature/Learner/LessonPageTest.php` — exit 0; **32 tests, 180 assertions**.
- `node --test <all tests/Unit/JavaScript/*.test.js files>` — exit 0; **11 tests, 11 pass**. PowerShell expanded files explicitly because directory form is unsupported here.
- `npm.cmd run build` — exit 0; Vite 7.3.0 built successfully after sandbox retry.
- `vendor\\bin\\pint --test <caption-related PHP files>` — exit 0; **10 files pass**.
- `git diff --check` — exit 0.

The full regression command `php vendor/bin/phpunit --do-not-cache-result` completed with **1466 tests and 7229 assertions**, exit 1: **11 errors** from missing GD and **7 unrelated auth/parent-page failures**. No unrelated code was changed for those failures. Full-repository Pint was not used as a completion gate because its configuration cannot resolve this Windows workspace; caption-related Pint passed.

## Automated authoring coverage

The caption management feature tests cover:

- creating multiple local WebVTT tracks with one default;
- normalized duplicate-language rejection;
- invalid, oversized, external-provider, and foreign-caption rejection;
- replacing and removing files;
- changing or omitting the default;
- cleanup after provider switching and topic deletion;
- cleanup after failed caption persistence;
- owner-only topic management and authoring controls;
- learner rendering for zero, multiple, escaped, default, and external-provider cases.

## Manual QA

No browser session was available in this environment (`agent.browsers.list()` returned `[]`). Therefore these checks remain manual follow-up items:

- create/edit workflow in a real browser;
- caption toggle and language selection;
- cue synchronization after seeking;
- desktop/mobile responsive layout;
- fullscreen captions;
- playback, progress, speed, volume, mute, and responsive regressions.

Static and unit coverage confirms that local videos render native tracks, zero-track videos omit caption controls, configured defaults activate, no-default tracks remain off with automatic language selection, and YouTube/Vimeo topics retain the iframe path without local tracks.

## Outstanding issues

- Browser/device QA remains outstanding because no browser session was connected.
- Full PHPUnit remains non-green for the unrelated GD and auth/parent-page failures listed above.

## Database safety

The caption migration was already applied; `php artisan migrate` found no pending migrations. No reset, wipe, truncate, drop, recreation, or destructive reseed was executed. Automated database tests use `cc_db_test` from `phpunit.xml`.
