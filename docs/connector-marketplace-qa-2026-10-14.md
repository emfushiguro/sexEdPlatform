# Connector marketplace refinement and QA — 2026-10-14

## Changes and root cause

The local `adult@test.local` account (user 7) is an approved instructor. Its module 3 is approved and published, and marketplace listing 2 is published with a PHP 5,000 package for 50 seats. The module's overall learner limit is 100. These records were inspected without changing them.

The Connector Modules route pointed to the older dashboard controller, and the marketplace detail, checkout, library, and access-management routes had been removed. The routes now use the existing marketplace controllers again, preserving the unrelated connector route changes.

The instructor module page now has a compact listing summary and **Manage connector listing** button. Its right-hand drawer contains the package price, seats per purchase, 10/25/50-seat suggestions, an available-capacity summary, and a link to the existing module learner-limit editor. It supports keyboard focus containment, focus return, Escape/backdrop dismissal, mobile layout, and validation errors with retained input. Connector publication does not require an instructor subscription or baseline plan.

Additional checklist tests exposed and fixed two issues: connector checkout described a subscription instead of a seat package, and existing Connector learning grants did not recheck learner age eligibility at access time. The age check now runs through the shared access service without deleting grants or learning history.

## Automated evidence

The final regression matrix passed **165 tests and 1,464 assertions**, with three existing PHPUnit metadata deprecation notices. Database tests ran sequentially against `cc_db_test`; no development database reset, migration, or reseed was performed.

```powershell
$env:DB_DATABASE = 'cc_db_test'
php vendor/bin/phpunit --do-not-cache-result --log-junit storage/framework/connector-marketplace-qa.xml --filter 'ConnectorModule|ConnectorVerification|AdminConnectorVerification|InstructorConnector|ConnectorMarketplaceDrawer|SharedModuleCapacity|ModuleCapacityService|ConnectorAccessService|ModuleShowTest|ModuleAccessGrant|ChatAuthorizationService|ChatContextResolver|ConnectorRegistrationStepper|ConnectorResubmissionFormMarkup|InstructorModuleConfigValidation|LearnerPaidModulePurchaseFlow|LearnerModuleCapacityBehavior|InstructorModulePlanEnforcement|ModuleAccessService' tests
```

The JUnit evidence is at `storage/framework/connector-marketplace-qa.xml`. Provider checkout and webhook confirmation are simulated; this is not evidence of a real PayMongo charge or live signature/delivery rehearsal.

After strengthening the source-listing edit scenario, the focused library rerun passed **1 test and 10 assertions**.

| Checklist | Result and evidence |
| --- | --- |
| B1: approved learner becomes instructor | PASS: `InstructorConnectorMarketplaceListingTest` exercises admin approval followed by publication without a subscription. The user's existing local approval was also confirmed. |
| B2: save/publish without a plan | PASS: no-plan and restrictive-plan publication, draft save, ownership denial, module visibility, and capacity rejection. `ConnectorMarketplaceDrawerTest` covers the rendered drawer and retained failed input. |
| B3–B4: browse, manager-only checkout, detail | PASS: `ConnectorModuleMarketplaceBrowseTest` and `ConnectorModuleCheckoutTest`, including the promoted-instructor listing and forbidden guest/unrelated/inactive/unverified access. |
| B5: purchase snapshots and hold | PASS: checkout snapshots, accepted terms, pending payment/purchase, a 30-minute hold, and no automatic assignments. |
| B6–B7: confirmation and replay | PASS: completion and webhook tests, including replaying the same provider event and checking one purchase, entitlement, and ledger. |
| B8: library and purchase confirmation | PASS: library/confirmation tests show the historical package and derived seat counts. The library regression also changes the source listing's price and seats after purchase to verify the purchased package retains its original values. |
| C1–C3: rules, seat limits, revocation tombstones | PASS: access-rule, seat-assignment, seat-concurrency, and role-reconciliation tests. The concurrency regression checks seat uniqueness and repeated reconciliation, not simultaneous production load. |
| C4: learning and chat boundaries | PASS for automated module/lesson/quiz/activity access, lifecycle certificate flow, chat authorization/context, and revoked-access checks across translation, checkpoints, feedback, completion, and certificates. Live translation and a complete browser-driven learner journey remain unrun. |
| C5: unauthorized/ineligible learners | PASS: revoked access, suspension, inactive membership, Connector verification state, guardian-pending access, and age-ineligible lesson denial. |
| C6–C7: removal, preserved history, seat reuse | PASS: marketplace lifecycle, membership lifecycle, progress-preservation, and certificate-history tests. |
| D1: void | PASS: correction logs, released access, retained payments/ledger/progress, and admin/reason authorization. |
| D2: restore | PASS: assignment reconciliation without duplicate purchases or ledgers; unsafe modules and insufficient capacity remain rejected. |
| D3: retry finalization | PASS: an expired hold plus exhausted capacity becomes an attention case; after capacity recovery, retries finalize once without duplicate financial/access rows. |
| D4: unpublish | PASS: lifecycle checks disappearance from browse/detail and preservation of historical purchase/access records. |
| D5: expiry | PASS: warning thresholds, repeated command execution, expired evidence, and reverification/resubmission transitions. |
| D6: independent verification/marketplace state | PASS: expiry preserves operational verification status, memberships, completed payment/purchase, active entitlement, and sale ledger. |
| Private evidence and privacy matrix | PASS: applicant/admin access, member/unrelated/guest denial, connector/document ownership, private cache/security headers, historical evidence, and internal-note/path privacy. |
| Remaining negative cases | PASS: incompatible requirements, stale rounds, accepted-evidence preservation, duplicate hashes, server-side capacity checks, and failed-upload rollback that preserves an unrelated existing file. |

Two stale verification-workspace test expectations were updated to match the already-present UI: empty regulatory groups stay hidden, and the section is labelled **Submitted documents**. The privacy assertions remain intact.

## Browser and build evidence

The installed headless browser signed in as the documented local test account and inspected module 3 without submitting listing or purchase changes. The drawer passed desktop and 390-pixel mobile checks, Tab/Shift+Tab containment, focus return, Escape/backdrop/close-button dismissal, body scroll locking, seat presets, over-capacity feedback, and the real form's publish/currency/seat fields.

```powershell
node tests/Browser/connector-marketplace-drawer.mjs
```

This script requires the running local app at `http://127.0.0.1:8000` and the documented seeded account. Set `CC_QA_PASSWORD` if its password has been changed. It deliberately performs no listing updates or purchases. Screenshots are at `storage/framework/connector-marketplace-desktop.png` and `storage/framework/connector-marketplace-mobile.png`.

The production frontend build passed using `npm run build -- --emptyOutDir false`, preserving existing generated assets. The live route table contains the 12 Connector module marketplace/library/access routes. Focused Pint passed for the 12 selected service/route/test files, and the focused diff whitespace check passed. The existing large instructor module controller still has baseline formatting issues; repository-wide formatting was not applied.

## Remaining limits

- The promoted local instructor's background `/chat/conversations` requests return 403 from the existing chat permission middleware. The browser test reports these separately and rejects other failed requests or JavaScript errors. This is not an instructor-subscription requirement for marketplace publication, and it remains a separate local chat/RBAC issue.
- Real PayMongo checkout/webhook delivery, live translation-provider output, screen-reader testing, and a complete browser-driven purchase-to-learning journey were not performed. Automated mocks and HTTP/service tests must not be presented as those checks.
- The full repository suite, staging rehearsal, and retention approval remain separate release gates from this feature QA.
