# Task 2 TDD Report: Validate Image Library ownership in authoring and Preview

## Scope

- Modified only `app/Services/Learning/InteractiveActivities/InteractiveActivityAuthoringService.php` and `tests/Feature/Instructor/InteractiveActivityAuthoringTest.php` for Task 2.
- Did not stage unrelated dirty work. No development database command, reset, wipe, seed, or migration was run.

## RED evidence

Added the four prescribed feature tests before production changes:

1. Image-only matching items can Preview and persist from the current instructor's Image Library.
2. A new foreign-library image path is rejected at `configuration.pairs.0.left.image_path`.
3. An exact existing legacy path survives an authorized edit without requiring ownership or current storage existence.
4. Supporting images do not change revision, while image-only content and an image replacement do.

The requested `php artisan test ... --filter='image|media'` cannot start its subprocess in this Windows environment because Symfony rejects the Windows-style project CWD. A direct PHPUnit fallback ran the four tests (`--filter=image --debug`): three passed and the foreign-path test failed exactly as expected, with `Session is missing expected key [errors]`.

## GREEN implementation

After handler normalization in `InteractiveActivityAuthoringService::validate()`, validation now:

- collects exact existing image paths from the authorized activity;
- permits those exact paths without an ownership or existence check;
- requires every new path to begin with `quiz-images/user-{author-id}/` and exist on the public disk;
- reports failures against the relevant normalized matching/sequence item image path.

## Verification

- `php -l app/Services/Learning/InteractiveActivities/InteractiveActivityAuthoringService.php`: passed.
- `php -l tests/Feature/Instructor/InteractiveActivityAuthoringTest.php`: passed.
- `git diff --check`: passed with no whitespace errors.
- `git show --check 0f6355d`: passed; the commit contains only the two Task 2 files.
- Direct PHPUnit GREEN attempt (`--filter=image --do-not-cache-result`) was blocked before assertions: the isolated `cc_db_test` schema is incomplete (`migrations` missing, then `cache` already exists). No database repair/reset was attempted because the task explicitly prohibits destructive database operations.

## Self-review

- Validation is at the shared authoring trust boundary used by persistence and Preview input validation.
- Exact comparisons prevent an admin/editor from losing a stored reference outside their directory.
- New references require both authorization-by-directory and actual public-disk presence.
- The implementation also handles sequencing `configuration.items` without adding a separate abstraction.

## Concerns

The final feature-suite GREEN verification remains blocked by the broken isolated test schema and the Artisan subprocess CWD incompatibility. Restore `cc_db_test` through the project's approved test-environment setup before rerunning the required authoring suite; do not reset the development database.

No Task 2 test process remained running at handoff; the two running PHP processes predated this task and were left untouched.
