Task 1 implementation report

Files touched:
- resources/js/seminar-schedule.js (new pure civil-time Alpine state)
- resources/js/app.js (one module import)
- resources/views/connectors/seminars/_form.blade.php (grouped split PHT date/time controls, canonical hidden payloads, schedule lock explanation, duration buttons and suggestions)
- resources/views/seminars/_delivery-drawer.blade.php (new shared drawer wrapping unchanged delivery form)
- resources/views/connectors/seminars/show.blade.php (drawer near heading actions)
- resources/views/admin/seminars/show.blade.php (drawer near title only; preserve preexisting admin additions)
- resources/views/seminars/_participant-profile.blade.php (new shared photos + initials fallback)
- resources/views/seminars/_attendance-roster.blade.php (photo identity; inline decision/save; expand required reason for existing decisions or removal)
- resources/views/connectors/seminars/registrants.blade.php (photo identity)
- app/Http/Controllers/Connector/SeminarAttendanceController.php (index eager-load user learner/instructor profiles only)
- app/Http/Controllers/Admin/SeminarAttendanceController.php (same)
- tests/JavaScript/seminar-schedule.test.mjs (new)
- tests/Feature/Seminars/SeminarAttendanceRosterTest.php (focused markup assertions only)

Defaults: only fresh creation without flashed input automatically suggests end +60 min, registration -30 min, external link -15 min. Existing and failed-validation values, including blanks, remain untouched. Start changes follow suggestions until edit/clear; Use suggested time restores following. Duration buttons set manual end. UTC-tagged civil arithmetic preserves PHT civil semantics regardless of browser timezone and handles overnight/year/leap transitions; existing seconds preserved.

Checks: node --test tests/JavaScript/seminar-schedule.test.mjs PASS (4 tests: civil boundaries/timezone independence; manual/clear/restore suggestion lifecycle; persisted precision/blank preservation; split field entry). node --check resources/js/seminar-schedule.js PASS. PHP syntax connector/admin attendance controllers PASS. No DB tests run as instructed; parent runs serially.

Remaining checks: browser keyboard/mobile/native date controls and drawer focus/scroll QA, Blade compilation, focused PHP rendering tests, build are parent gates. Default delivery error bag kept and drawer only auto-opens for delivery field validation keys per parent instruction. No public/build or staging changes.

Task 1 review follow-up:
- Added immediate registration deadline ordering feedback (deadline <= start inclusive valid) and readable PHT date/time range summary using explicit UTC formatting for civil dates.
- Native time and suggested datetime controls now use minute steps unless an existing nonzero second requires second precision. Canonical payload stays unchanged; tests verify actual stored seconds survive unrelated updates.
- Updated 3 stale approved-UI assertions in EducationalEventFormTest and ConnectorSeminarManagementTest while strengthening PHT split-control and hidden canonical-payload checks.
- New EducationalEventPresentationTest renders real admin/connector pages with delivery and moderation errors, guarding drawer reopening, rejection isolation, delivery error text, accessible dialog markup, and drawer z-index. Parent had already corrected admin rejectOpen and drawer stacking; these lines were not changed here.

Verification: Node 5/5 passed. Focused PHP serial run passed 26 tests, 146 assertions:
php vendor/phpunit/phpunit/phpunit --do-not-cache-result --bootstrap tests/existing-database-bootstrap.php tests/Feature/Seminars/EducationalEventPresentationTest.php tests/Feature/Connectors/EducationalEventFormTest.php tests/Feature/Connectors/ConnectorSeminarManagementTest.php
The artisan wrapper failed on Symfony subprocess cwd visibility; direct PHPUnit first completed all tests but failed writing the result cache. Re-run with --do-not-cache-result passed cleanly. Browser native-control and focus/scroll behavior still require QA.
