# Gift Subscription Active Plan UI

**Goal:** Match the regular subscription page's active/current plan display and prevent premature repeat gift purchases.

**User direction:** Remove the explanatory hero copy and purple checkout gift notice shown in the supplied crops. Retain dependent identification in the page heading and ordinary checkout details. Active subscriptions use a status/expiry banner, a Current badge, disabled plan cards and explicit renewal through checkout.

**Implementation choice:** Reuse SubscriptionService renewal warning/window logic, including admin-configured renewal_warning_days. Same-plan extensions are available inside that window; different active plans remain blocked. Expired dependents can purchase again. Preserve server-side PayMongo confirmation and frozen purchase terms.

## Work

- [x] Reproduce active plan cards remaining selectable and early same-plan POST creating payment.
- [x] Pass the dependent's current subscription into the existing catalogue cards and render current/disabled states plus expiry and eligible renewal banner.
- [x] Enforce the existing renewal window in gift preparation and unstarted checkout reservations.
- [x] Remove the hero explanation and checkout notice; retain recipient in ordinary checkout details. Collapse feature comparison as in the reference.
- [x] Verify active, near-expiry, expired, different-plan, stale-checkout, repeat and payment regression cases on the isolated test database; build assets if needed and review changes.

## Verification

- New active-state/renewal-window tests reproduced four failures before implementation; all five then passed. Review added four regression cases for overlapping active records and stale checkout targets, reproduced RED and fixed with shared active selection and authoritative-target validation.
- Final focused suite: 82 tests, 505 assertions passed on isolated cc_db_test at port 33309, including gift concurrency and existing ordinary payment/subscription/gateway cases.
- npm run build, PHP lint and git diff --check passed.
- Desktop (1440px) and mobile (390px) rendered snapshots inspected. Current plan disabled, active purchase buttons absent, comparison initially collapsed and opens correctly, no horizontal overflow. Snapshot browser used no authenticated browser session, so unrelated background account requests returned 401; route behavior is covered separately by authenticated feature tests.
- Reviewer rechecked overlap and stale same-plan checkout fixes: no remaining findings in the reviewed changes.

No migrations, duplicate plans, gateway rewrite, unrelated subscription redesign or publication.

## Comparison follow-up from reference image

- [x] Share the regular page's existing Free baseline labels and deduplicated inheritance mapping with the gift catalogue; retain live admin plan features.
- [x] Extract the regular comparison table to a shared Blade partial, preserving Current/Baseline labels and included/missing badge styling. Add the same subtitle and chevron to the gift disclosure.
- [x] Verify comparison parity and later admin edits, run gift/regular-page regressions, build and inspect the open table on desktop/mobile.

Comparison follow-up verification: 73 focused tests, 514 assertions passed; build, PHP lint and diff checks passed. Open-table snapshots show the six baseline features included across Free/Basic/Premium and the four configured premium features excluded only from Free, matching the reference. Admin updates and duplicate labels are covered. Review also identified and verified the missing-Free-record fallback; comparison now reuses the regular informational baseline without adding a selectable gift plan. Reviewer recheck found no remaining issues.
