# Connector educator profiles and icons — 2026-10-17

## Implemented

- Add eye-icon View and chat-icon Message actions to Connector Educators, with accessible names, hover titles, focus rings and 44px targets.
- Add an authorized Connector profile route using the same active-instructor directory query and existing educator-management permission. Seminar/webinar hosts retain their default access.
- Reuse the instructor's existing saved profile and public background Blade page within Connector navigation, with Back to Educators and the shared Message icon form.
- Move existing public-profile formatting and credential filtering into `PublicInstructorProfileService`, shared with the learner profile action. Instructor profile management remains the source of saved information.
- Retain public certificate links and all existing empty states. Do not expose account email or private verification credentials, create missing profiles, or create conversations during a GET.

## Executed checks

- Test-first run: five new checks failed on the missing profile route; existing learner compatibility check passed.
- Focused tests: **6 tests / 62 assertions passed** in isolated `cc_db_test`.
- Profile/messaging regressions: **24 tests / 358 assertions passed**, including existing instructor profile management/security/schema and learner public-profile checks. Evidence: `storage/framework/connector-educator-profile-regressions.xml`.
- Independent read-only review found no correctness, authorization or privacy blockers.
- Scoped Pint, new service PHP syntax, route registration and whitespace checks passed.
- Vite production build succeeded in `storage/framework/connector-educator-profile-build`. All nine hashed assets and the manifest matched existing built files; no replacement was necessary. Every manifest asset reference exists.

Regression command (run serially; never against the development database):

```powershell
php artisan test tests/Feature/Connectors/ConnectorEducatorProfileTest.php tests/Feature/Connectors/ConnectorInstructorMessagingTest.php tests/Feature/Instructor/InstructorProfilePageTest.php tests/Feature/Instructor/InstructorProfileSchemaTest.php tests/Feature/Instructor/InstructorProfileUpdateSecurityTest.php tests/Feature/Learner/LearnerInstructorBackgroundPageTest.php --compact --log-junit storage/framework/connector-educator-profile-regressions.xml
```

No database schema changes, development fixtures, permission reseeding or development data writes were needed.

## Remaining checks

Interactive desktop/mobile, keyboard and screen-reader QA, live realtime messaging, staging and the full test suite remain unrun for this change. The feature tests exercise server-rendered pages and direct authorization requests; they do not substitute for browser interaction.
