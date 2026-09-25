# Learner Identity Verification - Integrated Verification

Verified 2026-09-25 on the existing `RegistrationEnhancement` branch and workspace.

## Database and migration

- PHPUnit is configured for `cc_db_test`; the local `.env` default is `cc_db`. All PHPUnit runs used `phpunit.xml` and did not use the development database as a test database.
- An earlier `php artisan migrate --env=testing` attempt had targeted the default `cc_db` and left the new learner identity table without its composite index. No existing rows were dropped or changed. Before completing the migration, read-only inspection found the table's expected columns and existing keys, with the evidence/audit tables and composite index still pending.
- `php artisan migrate --pretend --no-interaction` stopped before printing SQL with `LogicException: Existing learner_identity_verifications table is missing required column id`. Read-only schema inspection confirmed `id` existed; the pretend command made no changes.
- After reviewing the migration and live schema, the normal incremental `php artisan migrate --no-interaction` completed on `cc_db`: it created the evidence and audit tables and added the missing index. `migrate:status` reported the migration as `Ran`, and a schema check confirmed expected columns, indexes, and foreign keys. No reset, destructive migration, or reseed was used.
- The `php artisan test` launcher could not create its test subprocess because it reported the workspace cwd as missing. Tests were run with `vendor/bin/phpunit --do-not-cache-result`, which used the same project `phpunit.xml` and isolated test database.

## Automated verification

| Check | Result |
|---|---:|
| `vendor/bin/phpunit --do-not-cache-result tests/Feature/Identity` | 64 tests, 527 assertions passed |
| Registration, email, guardian, child, admin, invitation, and wizard regression set from Task 9 | 95 tests, 614 assertions passed; 2 PHPUnit deprecations |
| Laravel Pint on the 9 changed implementation/test PHP files | Passed |
| `node --test tests/JavaScript/identity-selfie.test.mjs` | 11 tests passed |
| `npm.cmd run build` | Vite production build passed; generated outputs were restored afterward |
| `php artisan route:list --name=learner.identity --no-ansi` | Exactly 3 learner-owned routes: create, store, status |

The two PHPUnit deprecations are existing doc-comment data providers in `GuardianIdentityVerificationFlowTest` and `ChildRegistrationUploadPersistenceTest`.

The integrated suite covers browser-shaped null values for inactive government ID fields, stale review rounds on both decision forms, soft-deleted records in private-storage inventory, and the admin sidebar badge count for current pending learner cases. An existing admin UI assertion was also tightened to check the exact child deletion endpoint rather than matching the shared document-preview URL prefix. The final read-only code review found no remaining issues.

## Development storage migration

The initial default-database dry run found 25 allowlisted candidate references, 10 public files ready to move, no missing files or conflicts, and one path outside the allowlist. Inspection showed that outside path was a stale `seed/` child-document reference with no file on either the public or private disk, so it was retained unchanged.

The inventory also found guardian relationship evidence under the application's canonical `guardian-relationship-verifications/` directory. Those files were already on the private disk with `disk=local`; the migration allowlist and fake-storage tests cover that canonical prefix. The command now includes references held by soft-deleted guardians and relationships.

The apply run reported `candidates=25 ready=10 moved=10 missing=0 conflicts=0 unsafe=1 failures=0`; no guardian metadata update was needed. The post-apply dry run reported `candidates=20 ready=0 moved=0 missing=0 conflicts=0 unsafe=1 failures=0`. A final dry run after the soft-delete inventory fix returned the same counts. The only remaining unsafe reference is the missing `seed/` path described above. No public files remained ready to move, and the command found no conflicts or copy failures.

## Manual checks and limits

The in-app browser runtime reported no available browsers, so an interactive desktop smoke test could not be performed. Physical mobile, tablet, laptop webcam, desktop-without-webcam, and screen-reader checks were also unavailable. A live browser network trace was not available.

Automated camera tests cover capture, permission denial, upload fallback, preview, retake, cancel, stream cleanup, and stale camera requests. Source inspection shows camera capture stays in a local Blob/File until the learner chooses to use it, and the form submits to the application's same-origin `learner.identity.store` route. No third-party AI endpoint is present in this flow. This source inspection does not replace live device, accessibility, or network-trace checks.
