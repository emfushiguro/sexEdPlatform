# Connector Module Marketplace Operations

This runbook covers one-time Connector purchases of published learning modules. Connector subscriptions are unrelated: no subscription record, plan, or subscription entitlement is read or required by this marketplace flow.

## Lifecycle at a glance

1. An instructor or platform owner publishes an approved, learner-visible module listing with a price, currency, and seat capacity.
2. A verified Connector member can browse published listings. Only a Connector manager can start checkout.
3. Checkout creates a historical pending purchase and payment attempt, then holds the requested capacity for 30 minutes.
4. Provider confirmation finalizes payment, purchase, entitlement, and the append-only sale ledger atomically and idempotently.
5. Completion does not assign members. A Connector manager configures all-member, role, or individual-member access rules.
6. Active assignments create the shared module access grant and approved enrollment needed for learning.
7. Revocation, membership removal, entitlement release, or an ineligible member ends new learning access without deleting progress, attempts, completion state, or certificates.

## Listing states

| State | Meaning | Operational action |
| --- | --- | --- |
| 'draft' | Saved but not discoverable | Complete module approval/publication and listing validation. |
| 'published' | Discoverable and purchasable by eligible Connector managers | Monitor price, seat capacity, and module visibility. |
| 'unpublished' | Deliberately removed from browse/checkout | Existing completed purchases and entitlements are not deleted. |
| 'suspended' | Admin safety hold | Resolve the reason before restoring marketplace availability. |

Publishing requires an approved and learner-visible module. Instructor paid distribution also requires the instructor plan capabilities for publishing paid modules and receiving paid enrollments. Listing price and seat capacity are independent values; a published listing uses the configured billing currency and at least one seat.

## Purchase and finalization states

### Purchase

- 'pending': checkout or provider confirmation is outstanding; the capacity hold is active until its expiry.
- 'completed': payment and entitlement finalization succeeded.
- 'failed': provider checkout failed; the attempt remains in history and its hold is released.
- 'cancelled': the payment flow was cancelled.
- 'attention_required': payment was received or inspected, but safe finalization needs manual capacity or module review.
- 'corrected': an administrator applied a documented correction.

Payment attempts remain historical. Completion must be safe to retry: an already completed payment does not create duplicate purchases, entitlements, or ledgers.

### Entitlement

- 'active': the Connector owns usable access to the module.
- 'suspended': access is temporarily unavailable.
- 'voided': access was invalidated by correction or safety action.
- 'corrected': access was restored through a documented correction.

The entitlement retains its purchase, Connector, module, price, currency, seat-capacity, and seller snapshots. Connector subscriptions are not part of this chain.

## Seats and access rules

There are two capacity layers. At module level, a completed Connector entitlement reserves its purchase seat_capacity, and a pending Connector purchase reserves its seat_capacity while its 30-minute hold is valid. At entitlement level, a used seat is an assignment where consumes_seat is true, released_at is null, and the status is active or pending_guardian. Inactive, released, and revoked assignments do not consume the package's assignable seats. Reassignment releases an assignment seat for another member; it does not change the module-level reservation. Seat capacity is taken from the completed purchase snapshot, not recalculated from a later listing edit.

Managers can create:

- an all-member rule;
- a role rule; or
- an individual-member rule.

The reconciler applies the most specific eligible rule first, then assigns available seats in a stable order. A direct revoke is a tombstone until cleared; it is not silently recreated by an inherited rule. Removing a membership releases its assignment and makes the seat available for reassignment.

Learning access requires all of the following:

- an active, valid Connector assignment and shared module access grant;
- an approved module enrollment;
- an active Connector membership;
- a verified Connector and usable entitlement; and
- module visibility and learner age eligibility.

Access revocation blocks new lessons, quizzes, progress mutations, completion checks, and certificate generation. It does not delete user_progress, topic/activity progress, quiz attempts, enrollment completion, or issued certificates. Issued certificates remain viewable and downloadable by their owner.

## Manual corrections

Only an administrator should correct a Connector purchase, and every correction requires a reason and an activity-log entry.

### void

Use when the entitlement must not remain usable. The purchase becomes corrected, the entitlement becomes voided, active assignments are released, and existing learning history remains.

### restore

Use after a void when the module is safe and capacity is available. The entitlement and purchase become corrected, then assignments are reconciled. Do not restore an unpublished/unsafe module or exceed current capacity.

### retry_finalization

Use when a payment exists but finalization stopped at a recoverable boundary. The normal idempotent completion path is retried. If the entitlement is voided, restore it first.

There is no automated Connector refund path in this module. Do not delete payment, ledger, purchase, entitlement, assignment, or learning-history rows to resolve a discrepancy; preserve the audit trail and use the correction action that matches the evidence.

## Troubleshooting

### Checkout is unavailable

Confirm the Connector is verified, the actor has manager permission, the listing is published, the module is learner-visible, and the module still passes age/visibility checks. Check that the listing and requested seat capacity are valid.

### Payment is attention_required

Inspect the purchase, payment metadata, listing/module visibility, entitlement status, and current seat usage. If the provider payment is valid and capacity is recoverable, use retry_finalization; otherwise record the reason and use void or another approved correction. Do not create a second manual purchase.

### A member has no access

Check the active Connector membership, the entitlement status, the matching access rule, assignment status/reason, consumes_seat, released_at, and learner eligibility. Run reconciliation through the existing Connector access workflow after correcting the rule or membership state.

### A seat appears stuck

Look for assignments that are active or pending guardian while released_at is null. A membership removal, revocation, or entitlement release should change the assignment to a non-consuming state. Preserve the released row; it is part of the audit history.

### Learning is blocked after a legitimate change

Confirm that the assignment, grant, enrollment, Connector verification, module visibility, and age range are all current. Historical progress and certificates should still be present even when the current grant is inactive.

### A route appears stale

Compare the declared route with php artisan route:list --path=connector --except-vendor. If declarations are correct but the running application serves an old route, clear/rebuild the route cache in the intended environment before editing source files. Do not alter database data to fix a route-cache problem.

## Operational verification checklist

- [ ] Approved instructor/platform module is published with the intended fixed currency and seat capacity.
- [ ] Ordinary Connector members can browse but cannot purchase; managers can checkout.
- [ ] Provider callback/finalization is idempotent and payment metadata identifies connector_module_purchase.
- [ ] Completed purchase has exactly one usable entitlement and one sale-ledger record.
- [ ] Manager assignment covers all-member, role, and individual-member behavior as applicable.
- [ ] Revocation/membership removal releases the seat and blocks new learning.
- [ ] Progress, quiz attempts, completion state, and issued certificates remain after access ends.
- [ ] Replacement assignment can consume a released seat without exceeding capacity.
- [ ] Admin corrections include a reason and leave the append-only audit trail intact.
- [ ] No Connector subscription is required or modified.

For browser release QA, use signed-in instructor, ordinary-member, manager, learner, and admin sessions. Check desktop/mobile layout, keyboard navigation, confirmation/library links, assignment/revocation controls, finance/correction views, and browser console/server logs before release.
