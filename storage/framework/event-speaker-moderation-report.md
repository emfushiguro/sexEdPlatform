# Speaker and moderation refinements

Implemented Task 3 in the existing checkout. Preserved prior admin delivery drawer, reason/note-only rejection reopening, read-only admin attendance, and unrelated edits.

Changed instructor invitation controller and index/detail views; added shared event-state Blade partial. Invitation state (including elapsed pending expiry) is distinct from Upcoming/Happening now/Completed/Cancelled/Awaiting publication. Accepted external speakers use existing authorization-rechecking external redirect before participant release, without raw URL in HTML. Native access follows Agora readiness; in-person uses venue/instructions. Other speakers are accepted-only and exclude self. Confirmation copy follows format.

Expanded admin metadata with description/objectives, format/platform or venue/address/room, duration, registration deadline, release/expiry, instructions, submitter/publication actor and time. Added profile eager loading used by existing avatars. Host/admin confirmed speakers get green check text while other manager-visible states remain available.

Own feature tests: php vendor/phpunit/phpunit/phpunit --do-not-cache-result --bootstrap tests/existing-database-bootstrap.php tests/Feature/Seminars/SeminarSpeakerExperienceTest.php
Result: PASS, 5 tests / 78 assertions (storage/framework/speaker-tests.log). Covers actual instructor/admin HTTP rendering and redirect access, pending/declined/expired/ownership, all delivery modes and event lifecycle, accepted-only colleagues, expired/unavailable external link, early preparation, null release, metadata and secret non-leak. One intermediate fixture deadlock passed on isolated retry; no schema reset or development-data mutation.

Focused Pint fixed style in the two owned controllers and new test; owned-path git diff --check passed.

Unrun here: browser visual/keyboard checks and full cross-task suites/build; parent owns final validation. No commits/staging/dependencies/public build changes.
