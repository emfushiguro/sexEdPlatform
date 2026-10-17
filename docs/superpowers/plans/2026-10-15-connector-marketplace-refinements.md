# Connector marketplace refinements implementation plan

> Execute inline on the current branch. Preserve unrelated edits and development data.

**Goal:** Reuse learner module presentation, keep individual and organizational ownership separate, and offer several fixed seat packages.

**Architecture:** Keep one module and one marketplace listing. Retain the listing's existing default price/seats and store optional alternative fixed packages in a nullable JSON column. Checkout resolves the selected seat quantity against current server-side packages; existing purchase and entitlement snapshots remain authoritative. Reuse the learner card and hero, never learner progress or protected lesson content.

**Constraints:** No Connector subscriptions, new commission system, destructive migrations, data resets, or commits. Instructor creation/enrollment limits remain enforced. Organizational purchases are non-refundable; member revocation preserves learner history.

## 1. Presentation and channel boundary

- [x] Add failing `ConnectorMarketplacePresentationTest` and `ConnectorPurchaseChannelIsolationTest`; observe missing thumbnails/license information and cross-purchase payment binding failure.
- [x] Reuse `components/learner/module-card-recommended.blade.php` with organizational labels/actions; extract the learner hero into `modules/partials/details-overview.blade.php`.
- [x] Show public curriculum metadata and organizational eligibility, assignment, capacity and refund terms. Link existing licenses to the library.
- [x] Bind finalization to payment FK, purchaser and saved purchase amount before any mutation.

## 2. Fixed seat packages

- [x] Add `ConnectorMarketplaceSeatPackagesTest` for package publishing/validation, selection, tampering, capacity, immutable purchase snapshots and replay.
- [x] Inspect existing listing columns. Add only nullable `seat_packages` JSON via a forward migration; retain legacy columns as default package.
- [x] Extend `ModuleMarketplaceListing::packages()` / `packageForSeats()` and existing listing service/request validation; reject duplicate seat quantities, invalid prices and over-capacity publication.
- [x] Extend the existing instructor/admin listing drawer with optional package rows, independent from personal pricing.
- [x] Carry selected `seat_capacity` through details, checkout summary and POST; resolve price server-side in `ConnectorModulePurchaseService::createCheckout(..., ?int $seatCapacity = null)`.
- [x] Prevent silently continuing a different pending package; preserve completed snapshots when offers change.

## 3. Verification and handoff

- [x] Run isolated tests sequentially using `DB_DATABASE=cc_db_test`, never the development database.
- [x] Run focused style checks, Blade compilation, frontend build and available desktop/mobile browser checks.
- [x] Apply only the new additive migration to development; document verified rules, test results and remaining external-provider gates in an October 15 QA report and update the runbook.

Commands: `php vendor/bin/phpunit --do-not-cache-result --filter 'ConnectorMarketplace|ConnectorPurchaseChannelIsolation' tests`; then the existing Connector marketplace/access/correction/verification matrix. Build: `npm.cmd run build -- --emptyOutDir false`.

## Completion ledger

- User confirmed several fixed packages, not arbitrary seat quantities/volume-discount pricing.
- Ruling: retain the existing default package columns and use nullable JSON alternatives; existing snapshot columns already record the selected price/seats, so no duplicate tier/entitlement architecture is needed.
- Review findings: protect initializing live holds and resume the saved package when its own hold consumes available capacity. Both reproduced RED, fixed, tested GREEN and confirmed on re-review.
- Visual finding: shared learner components inherited system dark mode inside the otherwise light-only Connector shell. Reproduced in the browser; reuse the existing force-light flag without changing the saved learner theme.
- Regression: 185 tests / 1,579 assertions; three existing metadata deprecations. Browser presentation and drawer checks, production build, Blade cache, scoped Pint and whitespace checks passed.
- Development: only the explicit additive migration applied. Existing two listings remain PHP 5,000 / 50 seats; Connector purchase count remains zero. No commit, worktree, merge or push.
- Remaining release gates are recorded in `docs/connector-marketplace-qa-2026-10-15.md`.
