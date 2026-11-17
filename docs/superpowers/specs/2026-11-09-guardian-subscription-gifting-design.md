# Guardian subscription gifting — approved minimal design

Approved in chat on 2026-11-09: implement the simplified purchase/manual-renewal slice in the current checkout, without a worktree or publication. User clarified that payments are non-refundable under the platform terms.

## Intent and scope

Verified guardians buy existing paid learner plans for one verified dependent at a time through My Dependents. The guardian owns the payment; the dependent owns the subscription and its benefits. Existing checkout, PayMongo service, signatures, plan catalogue, receipt generation and entitlement queries are reused.

## Approach

Keep `subscribers.user_id` as beneficiary and `payments.user_id` as purchaser. Store server-authored gift context, frozen quote, checkout reservation and fulfillment marker in existing `payment_details`. Use the existing unique transaction reference for request idempotency. No new schema, shadow subscriptions, gateway, generic term ledger or webhook-event table.

Keeping every payment file unchanged was rejected: checkout/receipt guards currently authorize the subscription owner. Transferring a guardian subscription after payment was rejected because that changes history/access and breaks those guards.

## Rules

- Guardian: active, verified email, approved parent registration, adult, completed profile; relationship must be approved, timestamp verified and not deleted. Dependent must be active, different from guardian, and a learner. Progress/quiz permissions are not purchasing permissions.
- Existing active paid learner plans and active prices only. Freeze price, currency and prepaid duration at purchase; show confirmation using existing checkout.
- One unresolved purchase per dependent. Ordinary and legacy checkout preparation share the beneficiary lock and refuse an unresolved gift; gift preparation refuses an existing unpaid self-purchase. Repeated identical requests reuse the exact payment; changed request parameters do not reuse another purchase. Preserve existing history.
- Updated by the UI request on 2026-11-10: show the dependent's active plan and expiry, mark its card Current, and disable ordinary purchase actions while paid access remains active. Offer explicit same-plan renewal only inside SubscriptionService's existing admin-configured renewal window, and enforce that rule during preparation and before reserving an unstarted checkout. Select the greatest expiry and inspect all current subscription records consistently in the page and server checks. A verified renewal extends from the later of existing expiry or confirmation time. Block a different active plan, indefinite/ambiguous active access and unresolved competing checkout. Expired access may be bought again.
- Updated by user direction on 2026-11-10: gift assignment may use the regular subscription flow's authenticated PayMongo checkout-session lookup, so local/sandbox purchases work without webhook configuration. Return, pending, polling and purchaser history paths must verify the stored checkout session server-side, including purchaser, subscription, payment, amount, currency and mode. Signed webhooks remain supported; browser parameters, admin overrides and simulation alone cannot grant gifts. Both confirmation sources share the same payment completion, expiry and application marker transaction under beneficiary/subscription/payment locks. Duplicate confirmations cannot extend twice; failures remain retryable.
- No automatic gift renewal, retry payments, trial or unpaid grace extension. Expiry must re-read current dates while locked. Cleanup cannot discard a still-payable or unresolved gift checkout.
- Guardian finances remain private to that payer. Dependent subscription views are read-only for guardian-funded financial actions. Receipt history remains payer-owned after relationship removal; relationship removal prevents new purchases/management, not already-paid access. A valid in-flight payment is fulfilled without reauthorizing the former guardian. Return, pending and polling confirmation routes require the original payer, while new purchases and checkout management still require the verified relationship.
- Existing self-purchase flows stay compatible. Do not expose guardian financial information in dependent HTML, JSON or notifications. Send dependent welcome after first activation and payment receipts to purchaser.
- Track follow-up dispatch separately from term application in existing metadata. Signed webhook redelivery or a later purchaser history visit retries missing follow-ups without applying another term. History retries include only applied gift payments with missing expected dispatch markers. Printable receipts retain the frozen gift recipient, purchased term and that payment's resulting expiry.

## Non-refundable payments and excluded scope

Gift payments are final and non-refundable, matching the platform terms. Do not add automatic, manual, or admin-assisted gift refund functionality. Prevent legacy refund endpoints from refunding guardian-funded or mixed gift subscriptions. Existing non-gift backend behavior is outside this feature. A billing dashboard and family plans are also excluded.

## Data and verification

Preserve existing data. Confirm actual schema and isolated test connection before database tests. No reset/reseed of development data. Verify ownership, signed confirmation, failed/pending states, repeats, renewal conflicts, concurrent mutation, revoked relationships, privacy and existing learner/instructor/module regression flows. Use TDD and a fresh final code review. No commit/push requested.
