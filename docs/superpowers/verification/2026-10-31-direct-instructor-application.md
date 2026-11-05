# Direct Instructor Application Verification

## Baseline

- Date: 2026-10-31 (Asia/Taipei)
- Branch: `FacialVerification`
- Checkout: current repository; no separate worktree created
- Test database configuration: `phpunit.xml` contains `DB_DATABASE=cc_db_test`
- Development migration inventory: `php artisan migrate:status` completed read-only; all listed migrations were `Ran`, including the current Persona and relationship migrations
- Baseline command:

  ```powershell
  php vendor/bin/phpunit --do-not-cache-result tests/Feature/InstructorApplicationSubmissionTest.php tests/Feature/InstructorApplicationApprovalTest.php tests/Feature/Admin/AdminInstructorApplicationsUiTest.php tests/Feature/Identity/PersonaLearnerIntegrationTest.php tests/Feature/Auth/PersonaGuardianIntegrationTest.php tests/Feature/DependentSupport/DependentSupportRegistrationTest.php
  ```

- Baseline result: `OK, but there were issues!`; 32 tests, 159 assertions, 1 PHPUnit deprecation, 0 failures
- Pre-existing checkout changes: all paths reported by `git status --short` before implementation are user-owned. The implementation must preserve unrelated work and stage only feature-owned paths or hunks. This includes the earlier Persona/relationship controller, model, service, route, view, migration, asset, test, and document changes, plus the existing untracked support/test/document files.

## Verification sections

## Follow-up verification (2026-11-01, Asia/Taipei)

- The configured development database is `cc_db`. A read-only `php artisan migrate:status` check now reports all four direct-instructor migrations as `Ran` in batch `[15]`: `000000`, `000100`, `000200`, and `000300`. This status was observed during follow-up; this execution did not apply those migrations and no before/after row-count snapshot is available.
- Direct-instructor PHP subset:

  ```powershell
  php vendor/bin/phpunit --do-not-cache-result tests/Feature/InstructorApplication tests/Feature/Admin/InstructorIdentityVerificationAdminTest.php tests/Feature/Notifications/InstructorApplicationStatusNotificationTest.php tests/Unit/Services/InstructorIdentityComparisonTest.php tests/Unit/Services/CaviteAddressResolverTest.php tests/Unit/Services/InstructorApplicationDocumentStorageTest.php tests/Unit/Services/PersonaPolicyTest.php tests/Unit/Services/PersonaClientTest.php
  ```

  Result: `81` tests, `447` assertions, `0` failures, exit code `0`; one PHPUnit deprecation remains.
- Direct-instructor JavaScript test: `node --test tests/JavaScript/instructor-application.test.mjs` passed `5` tests with `0` failures.
- The exact focused regression command completed `354` tests and `2,445` assertions with `1` failure. The remaining failure is `PersonaSingleSubmissionTest::test_admin_previews_are_inline_and_a_foreign_case_cannot_be_viewed`, which expects the older `Viewed from Persona` copy while the current user-modified compact Persona presentation suppresses it. The guardian invitation regression now passes separately: `8` tests, `48` assertions.
- The isolated browser database check reports `PLAYWRIGHT_DB_MISSING` for `cc_db_playwright_test`. The Laravel development server port check reports `TcpSucceeded=True` for `127.0.0.1:8000`; the occupied server was not reused.

### Passed

- Implementation completed on the current `FacialVerification` branch in the task commits through `99d80d10`; no separate worktree was created. Unrelated dirty paths remain unstaged.
- Direct-instructor feature subset after the final test-harness adjustment: `131` tests, `691` assertions, `0` failures, `1` PHPUnit deprecation.
- Direct-instructor UI focused checks: `17` tests, `85` assertions; instructor application JavaScript test: `5` tests passed.
- Full JavaScript suite: `205` tests passed, `0` failed.
- Production build: `npm.cmd run build` passed; Vite transformed `102` modules and built in `27.11s`.
- PHP syntax checks for the changed Persona/dashboard controllers passed.
- Routes/configuration: `31` instructor-application routes and `12` admin instructor-application routes listed successfully. `instructor_application.enabled=false`, `document_disk=local`, `minimum_age=18`, Cavite province code `402100000`, and the cutover is unset.
- All four owned migrations passed `php artisan migrate --pretend --path=<exact-file>` checks, including the draft-column migration. Their later `Ran` status in `cc_db` is recorded in the follow-up section; application timing and data preservation were not established by this execution.
- Playwright infrastructure: `npm.cmd run test:browser -- --list` listed `19` tests across desktop Chromium, mobile Chromium, and Firefox smoke projects. Playwright and axe dependencies were installed, and Chromium/Firefox binaries were installed.
- The protected notification test now passes with production `DB::afterCommit()` behavior preserved; the fix is committed as `99d80d10`.
- `git diff --check` passed; it emitted only an existing CRLF normalization warning for a dirty Persona view.
- Operations and Sandbox documentation now describe deployment order, private storage, isolated browser testing, simulated Persona evidence, recovery, and external release gates.

### Failed

- The exact full focused command from Task 13 now completes with `354` tests, `2,445` assertions, and `1` failure: `PersonaSingleSubmissionTest::test_admin_previews_are_inline_and_a_foreign_case_cannot_be_viewed`. Its expected copy is out of sync with the current user-modified compact Persona presentation. The guardian invitation regression was repaired with the existing privacy-safe status label and now passes `8` tests/`48` assertions. The instructor notification transaction-boundary fix remains green in the owned subset.
- The broad Pint check reported `27` style issues across `101` historical PHP files, including dirty Persona/guardian files. The owned notification test passes Pint; unrelated files were not reformatted.
- The first actual browser run stopped before test execution because `127.0.0.1:8000` was already occupied. The non-reused web server guard prevented attaching to a development server.

### Blocked or unrun

- The canonical RBAC seed was not run. The legacy document migration audit stopped with `The instructor application document migration is not applied to this database.` No copy or source deletion occurred.
- The complete PHPUnit suite was not run because the required focused command is not green in the current dirty checkout.
- No Playwright scenario ran. The supervised QA agent stopped at the prerequisite gate: `APP_ENV` was `local`, `PLAYWRIGHT_DB_DATABASE` was unset, `cc_db_playwright_test` was not present, and port `8000` was occupied. It touched no database, browser, Persona, or files.
- Browser fixtures, isolated database existence, uploads, accessibility/keyboard behavior in a real browser, and failure traces remain unverified. The available browser list and static checks are not browser execution evidence.

### Manual and external gates

- Keep `DIRECT_INSTRUCTOR_APPLICATIONS_ENABLED=false` until the schema, isolated browser environment, authorization review, storage review, and external approvals are complete. `INSTRUCTOR_IDENTITY_ENFORCED_FROM` remains deliberately unset.
- Persona Dashboard webhook URL/secret rotation, lifecycle event selection, GovID plus Selfie template and ID-class mappings, queue worker, scheduler, retention, production privacy/security/legal review, and incident response remain external gates.
- Persona Sandbox evidence remains simulated and must not be described as authenticity, liveness, or face-match proof. Production activation and legacy public-document source deletion remain blocked pending separate operator approval.
