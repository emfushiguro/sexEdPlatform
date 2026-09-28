# Task 4 report: Connector authoring UI and event classification

## Changes

- Added the Educational Event create/edit labels and exposed the Task 3 request fields in the existing Alpine form: description, Objectives, type, format, venue details, external platform/link, release and expiry, and registration deadline.
- Limited format choices by type in the form. Existing native webinars show their fixed Agora format; type and format are fixed once registrations exist. Conditional fields are disabled when irrelevant, while the server request remains authoritative.
- Added type and format badges to the connector index and detail pages. The detail page shows description and Objectives; its existing Host Livestream control remains native-only.
- Added feature tests for create fields, release and expiry explanations, registered native edit, and external webinar classification.

## Verification

- RED: `php vendor/bin/phpunit --do-not-cache-result tests/Feature/Connectors/EducationalEventFormTest.php` produced 4 expected HTML failures (4 tests, 8 assertions).
- GREEN: `php vendor/bin/phpunit --do-not-cache-result tests/Feature/Connectors/EducationalEventFormTest.php tests/Feature/Connectors/EducationalEventAuthoringTest.php tests/Feature/Connectors/ConnectorSeminarManagementTest.php` passed: 32 tests, 201 assertions.
- `git diff --check` passed.

## Scope note

The feature tests check rendered markup and server behavior. They do not drive Alpine interactions in a browser; the form uses `x-model`, `x-show`, and conditional `:disabled` bindings for those interactions.
