# Connector Educator Profile Implementation Plan

> **For agentic workers:** Execute inline, using the approved spec and test-first steps below.

**Goal:** Show instructors' managed public profiles from icon actions in Connector Educators.

**Architecture:** Share existing public profile normalization between controllers and render the current background Blade view in the appropriate workspace layout. Keep all authorization in the Connector action and existing learner action.

**Tech Stack:** Laravel/Eloquent, Blade, existing inline SVG icons, PHPUnit, Vite.

## Constraints

Preserve unrelated working-tree changes and development data. No schema changes or development fixtures. Run database tests serially in isolated `cc_db_test`. Reuse profile records and existing privacy filters. Icon actions retain accessible labels, tooltips, focus styles and 44px targets.

## Task 1: Shared public profile and Connector route

Files: new `app/Services/Instructor/PublicInstructorProfileService.php`, `app/Http/Controllers/Learner/InstructorProfileController.php`, `app/Http/Controllers/Connector/EducatorController.php`, `routes/connector.php`, new `tests/Feature/Connectors/ConnectorEducatorProfileTest.php`.

- [x] Test that the View action opens saved profile fields within Connector navigation, excludes private credentials/account details, reflects later edits, and does not create missing profiles.
- [x] Test denied members/outsiders/unverified Connectors and unavailable/non-instructor targets. Run `php artisan test tests/Feature/Connectors/ConnectorEducatorProfileTest.php --compact` and observe missing-route/action failures.
- [x] Move existing learner controller presentation helpers into `PublicInstructorProfileService::data(User $instructor): array` without changing their behavior. Delegate the learner action to it.
- [x] Add `EducatorController::show(Request $request, Connector $connector, User $instructor): View`, enforce educator permissions and existing active-instructor query, and return the shared profile view with Connector context.

## Task 2: Icon actions and shared view integration

Files: `resources/views/connectors/educators/index.blade.php`, new `resources/views/connectors/educators/partials/message-action.blade.php`, `resources/views/learner/instructors/show.blade.php`, `docs/connector-marketplace-and-verification-e2e-runbook.md`.

- [x] Replace the directory Message text button with an accessible chat icon, add an eye-icon View link, and reuse the Message form on the Connector profile header.
- [x] Select the Connector layout/back link only when Connector context is provided. Preserve learner layout/navigation and all profile fields/empty states.
- [x] Run the new feature tests with existing instructor profile/schema/security and Connector messaging tests. Check Pint, PHP syntax, route registration and whitespace.
- [x] Build assets to a separate output directory and compare generated assets/manifest. All matched existing files, so no replacement was needed; all manifest references exist.
- [x] Record executed evidence and remaining browser/manual gates in the runbook; review the connected diff and preserve uncommitted workspace work.
