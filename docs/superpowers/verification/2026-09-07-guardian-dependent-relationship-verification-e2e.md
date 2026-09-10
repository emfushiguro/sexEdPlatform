# Guardian/Dependent Relationship Verification — E2E Report

Date: 2026-09-08
Revision tested: `e2e34fd` (`main`)
Execution mode: shared `main` checkout; no separate worktree

## Scope

The implementation plan's ten tasks were executed on `main`. The feature now uses centralized relationship pathways, private multi-document evidence rounds, guarded lifecycle transitions, relationship-based authorization, administrator review, invitation staging/acceptance, and independent guardian rows and permissions. Health & Support Information and the Phase 2 invitation-profile/messaging enhancements remain out of scope.

## Scenario coverage

| Scenario | Result | Automated coverage |
|---|---|---|
| Biological parent registration and relationship review | Pass | Child registration persistence, evidence submission, and lifecycle suites |
| Adoptive/multi-document registration, previews, and admin round review | Pass | `ChildRegistrationUploadPersistenceTest`, guardian evidence and admin review suites |
| Non-parent circumstances/context and resubmission | Pass | Registration, evidence, and lifecycle suites |
| Multiple guardians with independent review, revocation, and permissions | Pass | `ParentChildMonitoringTest::test_multiple_guardians_have_independent_review_permissions_and_revocation` |
| Evidence privacy and authorized downloads | Pass | `GuardianRelationshipEvidenceSubmissionTest` |
| Existing-learner invitation, pending access, acceptance, rejection, and compensation | Pass | `ParentChildInvitationFlowTest` |
| Suspension, monitoring, chat, and moderation authorization | Pass | Monitoring, chat authorization, suspension appeal, and appeal messaging suites |
| Browser-driven UI walkthrough | Not executed | No in-app browser was available in the environment |

## Verification commands

| Command | Result |
|---|---|
| Focused relationship/authorization/regression matrix (`php vendor/bin/phpunit --do-not-cache-result ... --testdox`) | Pass — 143 tests, 749 assertions |
| `php vendor/bin/phpunit --do-not-cache-result tests/Feature/Seeders/TestUserSeederTest.php --testdox` | Pass — 2 tests, 63 assertions |
| `php vendor/bin/phpunit --do-not-cache-result tests/Feature/Parent/ParentChildInvitationFlowTest.php --testdox` | Pass — 24 tests, 165 assertions |
| `pnpm.cmd build` | Pass — Vite 7.3.0 build completed successfully |
| `php vendor/bin/phpunit --do-not-cache-result` | Exit 1 — 1,268 tests, 6,059 assertions, 13 errors, 5 failures. Remaining failures are outside the focused feature matrix and include missing GD media support, existing duplicate/seed-order fixture constraints, and an unrelated interactive-checkpoint assertion. |
| `php artisan test` | Exit 1 before test execution — PHP/Laravel could not resolve the Windows workspace as the provided current directory |
| Scoped `php vendor/bin/pint --test` | Exit 1 — 12 style findings in the scoped changed-file set; the unscoped command also cannot read the Windows workspace root. No broad reformat was applied to user-owned or generated files. |

Environment: PHP 8.2.12, PHPUnit 11.5.46, MySQL test database `cc_db_test` as configured by `phpunit.xml`.

## Follow-up: manually selected front/back documents

On 2026-09-10, dependent registration was reproduced with an adoptive-parent and a non-parent relationship using the same document category for manually selected `front` and `back` files. Both requests previously failed with duplicate pairing-key errors because the browser form left both hidden keys empty.

Commit `c8c3c51` normalizes matching unkeyed front/back rows before validation in dependent registration, relationship resubmission, and invitation requests. Explicit pairing keys and incomplete or mismatched pairs retain the existing validation behavior.

The regression test was first run red with both reported errors, then green after the fix:

- Child registration plus evidence suites: 25 tests, 151 assertions passed.
- Invitation suite: 27 tests, 218 assertions passed.
- Full relationship matrix: 153 tests, 859 assertions, with one unrelated pre-existing dirty admin-copy assertion failure; all pairing and relationship evidence cases passed.

## Acceptance checklist

- Every selectable relationship type resolves to an evidence pathway and starts pending: verified by policy and lifecycle tests.
- Relationship declarations, child-account approval, invitations, and administrator attachment cannot bypass centralized verification: verified by registration, invitation, and admin mutation tests.
- Multiple guardians remain independent rows with independent evidence, history, states, and permissions: verified by the multiple-guardian test and lifecycle suite.
- Evidence is categorized, private, size/count/type validated, hashed, immutable after submission, and resubmittable as a new round: verified by evidence tests.
- Approval, rejection, resubmission, revocation, deactivation, and reactivation are guarded, audited, and notification-safe: verified by lifecycle and moderation tests.
- Monitoring, content, chat, moderation, and suspension access require an active verified eligible relationship: verified by authorization and regression tests.
- Legacy access is explicitly labeled and preserved: verified by seed and relationship compatibility tests.
- User-facing copy uses administrative verification language and does not disclose sensitive evidence in notifications: verified by moderation/UI tests.
