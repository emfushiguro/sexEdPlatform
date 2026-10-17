# Connector marketplace UI, purchase separation and fixed packages — October 15, 2026

## Implemented

- Marketplace cards reuse the learner recommended-module card: cover, title, difficulty, published lesson count, duration and creator attribution. Connector prices/seats replace personal enrollment information. No progress bar, last-studied information or personal learning action is shown.
- Detail pages reuse the actual learner hero, description and creator attribution. The public curriculum preview never exposes protected topic bodies or quiz answers. The organization-license panel explains the buyer, eligibility, package prices, assignments, non-refundable terms, shared capacity and retained learner history.
- Existing licenses link to the purchased library instead of offering a duplicate purchase. Pending checkouts have a dedicated resume action, including when their own hold exhausts capacity.
- The existing instructor/admin listing drawer supports a default fixed package plus up to nine alternative fixed packages, each with a unique seat quantity and total price in the configured billing currency. Personal module pricing is unchanged. Add/remove rows, validation recovery, capacity hints and the existing keyboard/mobile drawer behavior remain.
- The additive `2026_10_15_000001_add_seat_packages_to_module_marketplace_listings.php` migration adds nullable JSON alternatives. Existing price/seat columns remain the default package; no listing, historical purchase or entitlement was backfilled or replaced.
- Checkout carries the selected seat quantity through its summary and POST. The server resolves the package price, rejects unoffered quantities and enforces shared capacity. Browser-supplied price fields cannot discount the purchase.
- A payment must match the purchase's payment FK, purchaser and saved amount before finalization. A redirected payment cannot finalize another organization's purchase or grant a license for an underpayment.
- Live pending holds cannot be overwritten while their provider session initializes. A different package request is rejected; a matching request resumes its saved checkout, even if the seller subsequently removes that package. Completed purchase/entitlement/ledger snapshots remain unchanged by listing or commission edits.
- The Connector workspace keeps its existing light-only presentation when reusing theme-aware learner components, without overwriting the learner's stored theme preference.

## Rules audited and retained

| Boundary | Individual learner | Connector organization |
| --- | --- | --- |
| Purchase record | `module_purchases.user_id` | `connector_module_purchases.connector_id`; payer recorded separately |
| Payment scope | `module_purchase` | `connector_module_purchase` |
| Price | Module's individual access/price settings | Chosen marketplace seat package |
| Access | Purchaser's individual grant | Explicit eligible-member seat assignments; payment alone does not enroll the payer |
| Capacity | Individual access and holds | Purchased seat reservation and assignment limits, using the same shared capacity service |
| Content ownership | Original module creator retained | Original module creator retained; organization owns a license, not the content |
| Learning records | Learner-owned progress/attempts/certificates | Still learner-owned; organizational revocation does not delete them |
| Commission | Existing instructor-module policy resolver | Same resolver, default 10%, configured instructor/global policies, immutable sale snapshots; platform-owned sales remain platform sales |
| Refunds/subscriptions | Existing personal purchase rules unchanged | Non-refundable; no Connector subscription dependency |

Existing instructor module-creation rules were not replaced. Shared module/instructor enrollment limits remain authoritative. Marketplace publication itself retains the earlier approved rule: an instructor subscription is not required merely to publish an offer.

## Automated evidence

The regression matrix passed **185 tests and 1,579 assertions**, with three existing PHPUnit metadata deprecation notices. Both browser scripts passed; production build, Blade compilation and scoped Pint/whitespace checks passed. The read-only review's two pending-checkout findings were reproduced by failing tests, fixed, and confirmed resolved on re-review.

Database tests run **sequentially against `cc_db_test`**, not development. The regression command includes the new presentation, fixed-package and channel-isolation tests plus the existing Phase B–D coverage: checkout/webhooks/replay, commissions, shared capacity, role/all/member rules, tombstones, revocation, learning endpoints/history, corrections/retries, verification expiry and private-evidence authorization.

```powershell
$env:DB_DATABASE = 'cc_db_test'
php vendor/bin/phpunit --do-not-cache-result --log-junit storage/framework/connector-marketplace-refinement-qa.xml --filter 'ConnectorModule|ConnectorVerification|AdminConnectorVerification|InstructorConnector|ConnectorMarketplace|ConnectorPurchaseChannelIsolation|SharedModuleCapacity|ModuleCapacityService|ConnectorAccessService|ModuleShowTest|ModuleAccessGrant|ChatAuthorizationService|ChatContextResolver|ConnectorRegistrationStepper|ConnectorResubmissionFormMarkup|InstructorModuleConfigValidation|LearnerPaidModulePurchaseFlow|LearnerModuleCapacityBehavior|InstructorModulePlanEnforcement|ModuleAccessService' tests
```

Payment provider creation and confirmation are simulated in isolated tests; no real payment was made. Existing default and non-default commission policies are exercised, along with replay after a policy change.

Browser checks use documented local test accounts and only browse the marketplace/checkout or edit unsaved drawer fields:

```powershell
node tests/Browser/connector-marketplace-presentation.mjs
node tests/Browser/connector-marketplace-drawer.mjs
```

The presentation script checks desktop/mobile cards, creator labels, absence of personal progress, organization-license details, selection propagation to the checkout summary and no horizontal overflow. The drawer script checks focus containment/return, Escape/backdrop/close, scroll lock, preset seats, additional package add/remove and actual form fields without saving. Browser-selected alternative-package purchase amounts are covered by HTTP/provider-boundary tests rather than modifying the user's existing offers.

Screenshots: `storage/framework/connector-marketplace-cards-{desktop,mobile}.png`, `connector-marketplace-details-{desktop,mobile}.png`, and `connector-marketplace-{desktop,mobile}.png`. JUnit: `storage/framework/connector-marketplace-refinement-qa.xml`.

Blade compilation, the production build with existing assets retained, scoped Pint and scoped whitespace checks are part of the final verification. The additive migration was applied only by its explicit path to `cc_db`; both existing listings retained PHP 5,000 / 50 seats and the existing Connector purchase count remained zero.

## Quick manual acceptance

1. Open the instructor's published module and **Manage connector listing**. Keep the existing package; add, for example, 10 seats / PHP 1,500 and 25 seats / PHP 3,000, using quantities that fit shared capacity. These are illustrative prices, not seeded defaults. Save/publish. Confirm personal learner pricing is unchanged.
2. Browse as a verified Connector member: inspect the shared card and details. Confirm no personal progress, protected lesson body or member checkout permission.
3. Browse as a manager: choose a package, continue to the summary and verify organization, amount, seats and non-refundable terms. Submit only through the approved payment-provider test mechanism.
4. Reopen details while payment is pending: use **Continue pending checkout**. A second tab cannot change the initializing purchase's seat package or payment binding.
5. Finalize/replay the provider event. Confirm one organization entitlement and ledger, no personal purchase for the payer and no automatic seat assignments.
6. Assign all eligible members, a role or selected members. Revoke/remove one; verify the seat is reusable, history is retained and independent personal access (if present) remains governed by its own grant.
7. Change/remove a listing package or commission policy after purchase. The purchased library must continue showing the original seats, gross amount and sale snapshot.
8. Complete the existing runbook's correction, expiration and privacy checks before release. The earlier October 14 report maps each Phase B–D item to its test coverage.

## Remaining release gates

Real PayMongo charging/webhook delivery, live translation output, full browser purchase-to-learning completion, screen-reader QA, simultaneous production load, the full repository suite and staging/retention sign-off remain unproven by these focused checks. Three pre-existing PHPUnit metadata deprecation notices remain.

The local promoted instructor still produces existing background `/chat/conversations` 403 responses. The drawer script reports them separately; this task does not change chat/RBAC permissions. Existing development listings were not given fabricated new packages just to produce browser evidence.
