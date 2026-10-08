# Guardian-Dependent Health & Support Information - E2E Verification

Date: 2026-09-10

## Code revision and execution mode

- Branch: `main`
- Revision: `185a3b80fde82181da283b820f464da606790eca`
- Execution used the shared checkout directly, as requested. No isolated worktree was created.
- Unrelated pre-existing working-tree changes were preserved and were not staged.

## Environment

| Item | Observed value |
| --- | --- |
| PHP | `PHP 8.2.12 (cli) (built: Oct 24 2023 21:15:15) (ZTS Visual C++ 2019 x64)` |
| Laravel | `Laravel Framework 12.44.0` |
| PHPUnit | `PHPUnit 11.5.46 by Sebastian Bergmann and contributors.` |
| Node | `v22.18.0` |
| pnpm | `10.33.2` |
| Testing database | MySQL (`DB_CONNECTION=mysql`), database `cc_db_test` from `phpunit.xml`; credentials not recorded |
| Browser | No browser instances were available to the configured browser-control runtime; browser QA was not run |
| Relevant PHP extensions | `pdo_mysql` present; `gd` absent, which explains the image-fixture errors in the full suite |

## Verification commands

| Exact command | Exit | Result |
| --- | ---: | --- |
| `php artisan migrate:fresh --env=testing --force` | 0 | Test schema rebuilt successfully |
| `php artisan migrate:rollback --env=testing --step=1 --force` | 0 | Phase 3 migration rolled back successfully |
| `php artisan migrate --env=testing --force` | 0 | Phase 3 migration reapplied successfully |
| `php vendor/bin/phpunit --do-not-cache-result tests/Feature/DependentSupport --testdox` | 0 | 68 tests, 239 assertions; 1 PHPUnit deprecation |
| `php vendor/bin/phpunit --do-not-cache-result tests/Feature/Auth/ChildRegistrationUploadPersistenceTest.php tests/Feature/GuardianRelationshipSchemaTest.php tests/Feature/GuardianRelationshipEvidenceSubmissionTest.php tests/Feature/GuardianRelationshipLifecycleTest.php tests/Feature/Parent/ParentChildInvitationFlowTest.php tests/Feature/Parent/GuardianInvitationMessagingTest.php tests/Feature/Parent/ParentChildrenActionsUiTest.php tests/Feature/ParentChildMonitoringTest.php tests/Feature/Admin/AdminParentChildVerificationModerationWorkflowTest.php tests/Feature/Learner/LearnerNotificationReadFlowTest.php tests/Feature/Notifications/NotificationDeepLinkRoutingTest.php tests/Feature/Moderation/SuspensionMiddlewareEnforcementTest.php --testdox` | 0 | 114 tests, 573 assertions |
| `vendor\bin\pint --test app/Models/DependentSupportProfile.php app/Models/DependentSupportInformationAudit.php app/Models/User.php app/Models/ParentChildAccount.php app/Policies/DependentSupportProfilePolicy.php app/Providers/AppServiceProvider.php app/Http/Requests/DependentSupport app/Services/DependentSupportInformationService.php app/Notifications/DependentSupportInformationChangedNotification.php app/Notifications/GuardianSupportAccessChangedNotification.php app/Http/Controllers/Auth/DependentSupportRegistrationController.php app/Http/Controllers/Auth/ParentRegistrationController.php app/Http/Controllers/Learner/DependentSupportInformationController.php app/Http/Controllers/Learner/ParentVisibilityController.php app/Http/Controllers/Parent/DependentSupportInformationController.php routes/auth.php routes/web.php database/migrations/2026_09_09_100000_create_dependent_support_information.php tests/Feature/DependentSupport` | 0 | 28 files passed formatting checks |
| `pnpm.cmd build` | 0 | Vite production build succeeded; 89 modules transformed |
| `php vendor/bin/phpunit --do-not-cache-result` | 2 | 1,364 tests, 6,573 assertions, 14 errors, 6 failures, 1 PHPUnit deprecation; details are recorded below |

## Browser scenarios

No browser instance was available, so these scenarios were not run. Server-side tests must not be treated as browser evidence.

| # | Scenario | Result | Evidence or limitation |
| ---: | --- | --- | --- |
| 1 | Create a dependent through all existing steps; evidence submits before the optional page | Not run | No browser session was available |
| 2 | Skip support information; account and relationship creation remain complete | Not run | No browser session was available |
| 3 | Submit each optional field; no medical document control exists | Not run | No browser session was available |
| 4 | Dependent views, edits, clears a field, and removes the record | Not run | No browser session was available |
| 5 | Validation errors do not repopulate sensitive text after a new page response | Not run | No browser session was available |
| 6 | Purpose notice, optionality, keyboard focus, and removal confirmation are understandable and reachable | Not run | No browser session was available |
| 7 | Guardian A is activated and granted access; Guardian A can view and edit | Not run | No browser session was available |
| 8 | Guardian B is denied until separately granted | Not run | No browser session was available |
| 9 | Revoking Guardian A leaves Guardian B active and immediately denies Guardian A | Not run | No browser session was available |
| 10 | Reactivating Guardian A does not restore support access automatically | Not run | No browser session was available |
| 11 | Instructor and relationship-review administrator cannot open detailed support URLs | Not run | No browser session was available |
| 12 | Verification, invitations, chat, notifications, progress, quiz, and instructor pages omit the private marker | Not run | No browser session was available |
| 13 | Browser back navigation does not reveal cached sensitive content after logout or revocation | Not run | No browser session was available |
| 14 | Support information does not change relationship approval or rejection results | Not run | No browser session was available |

## Privacy and authorization checks

Observed through the focused Phase 3 and regression suites:

- All three support content fields use Laravel encrypted casts. Tests verify decrypted round trips and avoid ciphertext-equality assumptions.
- Validation tests verify the three sensitive fields are not flashed back into session data.
- Sensitive GET responses set private, no-store cache directives; the edit form does not use `old()` for sensitive values.
- Dependent and guardian operations are routed through the dedicated policy/service boundary. Guardian access requires the exact active, verified, access-eligible relationship and `can_manage_support_information = true`.
- Independent guardian permissions are scoped per relationship. Revocation, rejection, deactivation, and reactivation lifecycle tests verify no cross-guardian access restoration.
- Authorization-denial, foreign-record, stale-version, removal, and missing-dependent cases are covered by the focused suite.
- Notification tests verify content-free payloads and fresh authorization behavior; sensitive values are not placed in notifications, audits, sessions, or relationship records.

## Verification isolation

- Approval and rejection tests produce the same relationship decisions whether a support profile exists or not.
- Query-listener checks confirm relationship verification does not query `dependent_support_profiles`.
- Admin relationship detail rendering omits support markers and support fields.
- The regression/security matrix covering registration, evidence, lifecycle, invitations, monitoring, admin moderation, notifications, and suspension enforcement passed without failures.

## Full-suite comparison

The Phase 2 baseline at `aa00211` recorded 1,285 tests, 6,214 assertions, 13 errors, and 4 failures. The current run recorded 1,364 tests, 6,573 assertions, 14 errors, and 6 failures: +79 tests, +359 assertions, +1 error, and +2 failures.

The current errors were:

- 10 community media tests failing because the PHP GD extension is absent.
- 2 parent-children UI tests failing on duplicate synthetic city code `402101000`.
- 2 test-seeder tests failing on synthetic location fixture foreign-key/duplicate-data setup.

The current failures were:

- `ConnectorNotificationTest` notification expectation.
- `ConnectorRegistrationTest` generated status URL expectation.
- Two `InstructorLearnerCategoryClassificationTest` database/HTML expectations.
- `InteractiveCheckpointAuthoringTest` topic-create HTML expectation.
- `ParentChildInvitationFlowTest::test_verification_required_invitation_requires_supporting_document` database isolation expectation.

The Phase 3 focused suite and the focused regression/security matrix both passed, so none of the current full-suite failures is a failure in the dependent-support test scope. The full suite nevertheless remains non-green and the additional full-suite delta is recorded rather than classified as pre-existing without proof.

## Remaining limitations

- Browser QA could not be performed because no browser instance was available.
- The full PHPUnit suite remains non-green as documented above.
- PHPUnit reported one deprecation.
- The build command regenerated existing `public/build` artifacts; those unrelated working-tree changes were preserved and not included in the feature commits.

## Production privacy review

A Philippine privacy-governance review by the designated DPO or another qualified privacy reviewer was not performed in this run, and no reviewer/status record was available. Production release therefore remains pending that review; this verification report does not make a legal or regulatory determination.
