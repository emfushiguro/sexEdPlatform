# Educational event thumbnail presentation

Implemented Task 2 in the existing checkout; no database commands or PHP test suite runs.

## Scope

- Shared `resources/views/seminars/_thumbnail.blade.php`: fixed aspect cover or 96x56 compact image, calendar for absent/broken images, decorative list images, named covers, lazy images except identity headers.
- Connector create/edit multipart authoring: optional JPG/PNG/WebP input, public saved preview, object URL preview with revoke on selection/reset/destruction, removal checkbox, exclusive upload/removal, validation errors. Objectives labelled optional; capacity 1–100000 integer range and full help.
- Learner/instructor discovery/detail, Connector list/detail, Admin list/review, instructor invitation list/detail, community upcoming panel, Connector attendance/registrants, Admin attendance and monitoring rows, participant join and Connector native livestream identity.
- Invitation organizer logo follows the existing verified Connector + configured logo disk convention. Accepted speakers have no Connector Cancel action. Accepted invitation detail omits pending accept/decline dialog markup.
- Added `tests/Feature/Seminars/SeminarThumbnailPresentationTest.php` for actual Connector fallback, multipart edit, list image, and Admin cover HTTP rendering. Parent owns serial execution.

## Evidence

- `php artisan view:cache`: passed.
- `php -l tests/Feature/Seminars/SeminarThumbnailPresentationTest.php`: passed.
- `git diff --check -- resources/views tests/Feature/Seminars/SeminarThumbnailPresentationTest.php`: passed.
- Node assertions against actual inline Alpine object: select clears removal, replacement revokes prior URL, removal clears file/URL while retaining removal flag, saved URL retained, destruction safe: passed.

## Not run / preserved scope

Browser desktop/mobile preview, visual rendering, keyboard and responsive manual QA remain for parent. HTTP test written but deliberately not run concurrently with parent database work. No build assets, commits, migrations, controllers or models changed. Inactive Admin create placeholder and Connector stubs untouched. Unrelated existing checkout edits retained.
