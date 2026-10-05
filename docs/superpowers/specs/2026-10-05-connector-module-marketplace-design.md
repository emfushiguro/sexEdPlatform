# Connector Module Marketplace and Organization Learning Library Design

- **Date:** 2026-10-05
- **Status:** Approved design
- **Scope:** Connector marketplace purchase, organization entitlement, seat assignment, and reuse of the existing learner module experience

## 1. Purpose, Scope, and Success Criteria

This feature lets a verified Connector purchase a fixed-seat license for an instructor-owned or platform-owned module, configure which active Connector members receive those seats, and let those members learn through the existing learner module system.

The authoritative lifecycle is:

```text
Module
-> Connector Marketplace Listing
-> Connector Purchase and Payment
-> Connector Module Entitlement
-> Access Rule and Seat Assignment
-> Module Access Grant
-> Existing Module Enrollment
-> Existing Progress, Completion, and Certificate
```

The Connector purchases a license, not the module or its content. Purchased and granted are deliberately separate: a completed purchase creates the organization entitlement, while a learner receives access only after a valid assignment consumes or is covered by a seat.

Success means that the entire lifecycle works without creating a second learning engine, bypassing instructor limits, or depending on a Connector subscription.

## 2. Selected Architecture and Alternative Approaches

The selected approach adds dedicated organization-commerce records and reuses the existing learning, payment, commission, authorization, and notification systems.

This is preferred because an organization purchase has a different buyer, seat lifecycle, and authorization boundary from an individual learner purchase. Keeping those records separate avoids destabilizing `module_purchases`, while shared services prevent duplicate business rules.

Two alternatives were rejected:

1. Generalizing every existing purchase into a polymorphic buyer would require a broad rewrite of the functioning individual-purchase path.
2. Storing Connector purchases only in `payments.payment_details` would not provide enforceable seats, durable entitlements, relational history, or safe concurrent assignment.

The implementation will remain one comprehensive plan, delivered through independently testable phases.

## 3. Existing Systems and Reusable Components

The implementation extends these existing systems:

- `Module`, including `created_by`, `content_owner_type`, publication state, learner categories, approved revisions, `access_type`, `enrollment_limit`, and `enrollment_mode`.
- `InstructorPlanCapabilityService`, including configured free/paid learner caps and paid-module capabilities.
- `ModulePurchaseService`, `Payment`, PayMongo checkout, webhook, pending-payment polling, receipts, and payment administration.
- `CommissionPolicyResolver`, `RevenueSplitCalculator`, `ModuleSaleLedgerService`, and `module_sale_ledgers`.
- `ModuleEnrollment`, lesson/topic/quiz progress, `LearnerModuleCompletionService`, and certificates.
- `ConnectorAccessService`, active Connector memberships, Connector roles, and `connector.manage_modules`.
- Existing learner module cards, module detail presentation, checkout styling, database notifications, and activity logs.

The implementation does not replace any of these systems. It introduces shared access and capacity services where the current rules are duplicated across controllers.

## 4. Domain Architecture and End-to-End Data Flow

The marketplace is an organization-commerce channel layered over the module domain:

```text
                                    +-> Individual Module Purchase --+
Module -> Published Revision -------+                                |
   |                                +-> Marketplace Listing           |
   |                                     |                            |
   |                                     v                            v
   |                              Connector Purchase             Module Access Grant
   |                                     |                            |
   |                                     v                            v
   +--------------------------> Connector Entitlement -------> Module Enrollment
                                          |                         |
                                          v                         v
                                    Access Rules              Existing Learning
                                          |                   Progress/Certificate
                                          v
                                    Seat Assignments
```

The module and its approved revision remain the single content source. A listing controls whether new Connector purchases are offered. A purchase records commerce history. An entitlement records the organization license. Rules determine intended recipients. Materialized assignments make seat usage auditable and concurrency-safe. Access grants explain why one existing enrollment is active when multiple independent sources may exist.

## 5. Database Schema and Data Relationships

### `module_marketplace_listings`

One row per module, with a unique `module_id`:

- `module_id`
- `seller_type`: `instructor` or `platform`
- nullable `seller_user_id`
- `status`: `draft`, `published`, `unpublished`, or `suspended`
- `price_amount` and `currency`
- `seat_capacity`
- publication, unpublication, and suspension actors/timestamps
- timestamps

The module remains authoritative for current ownership and content. Seller fields make the listing explicit and enable purchase snapshots.

### `connector_module_purchases`

One durable purchase lifecycle per Connector/module, enforced by a unique key:

- Connector, listing, module, acting administrator, and latest/settled payment references
- `status`: `pending`, `completed`, `failed`, `cancelled`, `attention_required`, or `corrected`
- gross price, currency, and seat snapshots
- seller type/user and module title snapshots
- non-refundable terms version and acceptance timestamp
- capacity-hold expiry
- provider-paid, purchased, failure, and correction metadata/timestamps
- timestamps

Failed attempts reuse this purchase lifecycle; separate `payments` rows preserve each payment attempt.

### `connector_module_entitlements`

One entitlement per Connector/module and one per completed purchase:

- Connector, purchase, listing, and module references
- `status`: `active`, `suspended`, `voided`, or `corrected`
- purchase price, currency, and seat snapshots
- activation/suspension timestamps and status reason
- timestamps

Entitlements are never deleted through normal application flows.

### `connector_module_access_rules`

Rules contain:

- entitlement
- `target_type`: `all_members`, `role`, or `member`
- nullable role or member target
- stable `rule_key`, unique within the entitlement
- active state, creator, and timestamps

The stable key prevents duplicate all-member, role, or member rules even when nullable target columns are involved.

### `connector_module_seat_assignments`

One retained row per entitlement/user:

- entitlement, user, and current membership
- representative source rule and source type (`direct` or `inherited`)
- status and inactive reason
- whether the assignment consumes a seat
- assigned, released, revoked, and reactivated timestamps
- revocation actor
- timestamps

A seat is occupied when `consumes_seat` is true and `released_at` is null. This remains unambiguous even when access is temporarily blocked by suspension.

### `module_access_grants`

This is a provenance bridge, not a second enrollment system:

- user and module
- source type and source identifier
- nullable Connector
- `status`: `pending`, `active`, or `inactive`
- grant/end timestamps and reason
- timestamps

Source types include direct enrollment, individual purchase, and Connector assignment. The source tuple is unique. Existing approved enrollments and completed individual purchases are backfilled without changing progress.

### Existing-table extensions

- `module_enrollments` gains the `inactive` status plus inactive reason/time fields.
- `module_purchases` gains an expiring capacity-hold timestamp and attention-required support so individual and Connector checkout use the same capacity accounting.
- `module_sale_ledgers` gains channel, Connector purchase, Connector, buyer, owner, seat, and generic seller-net snapshots. Existing individual fields stay compatible.
- `payments.payment_details.payment_scope` gains `connector_module_purchase`; no Connector subscription foreign key is added.

New commerce history uses restrictive or nullable historical foreign-key behavior rather than cascade deletion.

## 6. Marketplace Listing Lifecycle

Only a currently approved and published module can publish a Connector listing. Instructor-owned modules require the owning instructor to opt in. Platform-owned modules are controlled by platform administrators.

Publishing validates:

- module ownership and current approval/publication state
- authorized seller
- price greater than zero
- currency supported by the existing checkout configuration
- seat capacity greater than zero
- instructor paid-distribution capability where applicable
- the current effective module/instructor capacity

There is no second content-review workflow. The existing module review and published revision remain authoritative.

Unpublishing changes only the listing. It prevents new checkout creation but does not modify completed purchases, entitlements, assignments, or learning records. An already-created, unexpired checkout may still complete from its immutable purchase snapshot so a user is not stranded after paying.

Platform administrators may suspend a listing independently for marketplace enforcement. A module-level safety or moderation restriction can still block learning access.

## 7. Roles, Permissions, Authorization, and Security

### Platform administrator

- Manage platform-owned listings.
- Suspend any listing.
- Review Connector purchases and organization-channel ledger entries.
- Perform exceptional manual corrections.
- Continue managing Commission Settings and instructor tiers.

### Instructor

- Manage listings only for instructor-owned modules they own.
- Set organization price and fixed seats within current capabilities.
- Publish or unpublish the listing.
- View their organization sales through the existing earnings area.

### Connector member

Every active member of a verified Connector may browse marketplace listings and details.

### Connector administrator

Purchasing, entitlement management, rules, assignments, revocations, and correction requests require `connector.manage_modules` in addition to active membership and verified Connector status.

### Learner

Learning mutations require an active access grant, an approved enrollment, applicable eligibility, and no platform restriction.

Laravel policies, `ConnectorAccessService`, Connector-scoped queries, and database constraints provide the current application-equivalent of row-level security. No frontend-only check is authoritative, and a new database-RLS subsystem will not be invented for this MySQL application.

## 8. Connector Marketplace Browse Experience

The existing `/connector/{connector}/modules` stub becomes the marketplace entry point. The Connector module area has three tabs:

1. Marketplace
2. Purchased Modules
3. Access Management

Marketplace cards reuse the learner module card visual language and expose:

- title and owner
- description/summary
- eligibility
- organization price and currency
- fixed seat package
- lesson count
- purchase/entitlement status
- capacity-unavailable state where applicable

The Connector navigation item becomes visible to every active Connector member. Purchase and management controls are rendered only when the member has `connector.manage_modules`, with identical server-side enforcement.

## 9. Shared Learner and Connector Module Details Page

The learner Module Details view will be refactored into shared presentation partials for the module overview and curriculum. Learner-specific progress, enrollment, purchase, certificate, review, and report controls remain in the learner view.

The Connector details page uses the same overview and curriculum presentation with a Connector-specific sidebar showing price, seats, status, and purchase action.

Before purchase, the Connector controller loads only safe metadata:

- approved module summary and owner
- lesson titles, order, duration, and counts
- curriculum structure allowed by the existing browse experience
- eligibility and public reviews

It does not load or expose topic bodies, protected attachments, quiz questions, answers, or learning endpoints.

## 10. Purchase and Payment Workflow

Checkout creation performs these server-side steps:

1. Confirm verified Connector and active acting membership.
2. Require `connector.manage_modules`.
3. Lock the module capacity row.
4. Revalidate listing publication, module approval/publication, ownership, price, currency, seats, duplicate status, and seller capability.
5. Reject a completed purchase or entitlement for the same Connector/module.
6. Create or reuse the pending purchase and record non-refundable terms acceptance.
7. Reserve an expiring capacity hold.
8. Create a normal `Payment` with the acting administrator as `user_id` and `payment_scope=connector_module_purchase`.
9. Create the existing PayMongo checkout session with Connector, listing, purchase, module, payment, and actor metadata.

Provider webhook, return polling, and status polling all call the same idempotent finalization method.

Finalization locks the payment, purchase, module, and relevant capacity records. One local database transaction marks the purchase completed and creates its entitlement and ledger. The transaction does not report success unless all required local records exist.

## 11. Payment Idempotency and Capacity Holds

The technical capacity-hold window is 30 minutes and is configurable under the existing billing configuration. Capacity calculations ignore expired holds; rows remain for history.

Idempotency is enforced through:

- unique Connector/module purchase lifecycle
- unique payment-to-ledger relationship
- unique purchase-to-entitlement relationship
- provider/payment metadata
- row locks during completion
- returning the existing completed result on repeated callbacks

If provider payment succeeds after the hold expires, finalization attempts to reacquire capacity. If capacity exists, it completes normally. If not, the payment is recorded as provider-paid and the purchase becomes `attention_required`; no entitlement is fabricated and administrators are notified.

If a transient local exception occurs before commit, the transaction rolls back and a later webhook, poll, or reconciliation retry can finish it safely.

## 12. Commission Calculation and Financial History

Instructor-owned Connector sales use the existing `CommissionPolicyResolver` at the payment completion time. The currently configured instructor module commission is expected to resolve to 10%, but the marketplace does not hard-code that rate.

Each ledger entry snapshots:

- gross amount and currency
- commission basis and rate
- commission amount
- seller net amount
- owner type and owner user
- sale channel
- Connector and organization purchase
- acting buyer
- purchased seats
- refund-policy snapshot
- occurrence time

Platform-owned modules use a 0% commission and a seller/platform net equal to gross. They still use `module_sale_ledgers` so finance reporting has one source.

Changing Commission Settings later never rewrites existing ledger entries. Related-party sales between an instructor and a Connector they participate in are allowed but flagged in purchase metadata and finance views.

## 13. Non-Refundable Policy and Manual Corrections

Connector module purchases are non-refundable by default. Checkout shows a required acknowledgement that includes the versioned policy, fixed seats, total price, and the fact that purchase does not automatically assign members.

The first policy version is `2026-10-05`. The purchase stores the version, actor, and acceptance timestamp.

The automated `RefundService` rejects `connector_module_purchase` scope. Exceptional duplicate-payment, fraud, provider, or platform-error cases are handled through an administrator-only correction action.

Corrections never delete or rewrite the original payment, purchase, entitlement, or ledger. They update correction/status fields, append an admin activity log, and disable or restore the entitlement as explicitly selected.

## 14. Connector Module Entitlement Lifecycle

A completed purchase creates exactly one organization entitlement. Purchase completion does not create member assignments automatically.

An entitlement remains valid when its listing is unpublished. It references the same module and follows newly approved revisions without repurchase.

States are:

- `active`: assignments may provide access.
- `suspended`: assignments are retained but cannot authorize learning.
- `voided`: an exceptional correction has disabled the license.
- `corrected`: historical marker used after an administrator resolves an exceptional case.

Connector suspension does not delete or rewrite the entitlement. Access evaluation independently blocks the suspended Connector and restores normal evaluation when verification returns.

## 15. Instructor Tier and Enrollment-Capacity Integration

`ModuleCapacityService` becomes the shared authority for individual and Connector capacity decisions.

The effective limit is:

```text
platform-owned module: module enrollment_limit

instructor-owned module:
minimum of module enrollment_limit and
InstructorPlanCapabilityService learner cap for the module access_type
```

A null applicable limit means unlimited. The module's existing `access_type` selects the instructor free/paid learner cap; independent organization pricing does not silently change the module classification.

Capacity usage is:

```text
distinct independent learners with active access
+ active Connector entitlement seat reservations
+ unexpired checkout holds
```

Connector-assigned learners are not counted again because the entire entitlement seat package was reserved at purchase. Tier downgrades preserve existing entitlements and learners but block new reservations until usage is compliant.

Instructor marketplace publication also requires the existing paid-distribution capabilities because the organization listing is a paid sale, even when individual access to the underlying module is free.

## 16. Seat Capacity and Concurrent Assignment Safety

Seat assignment locks the entitlement row with `lockForUpdate()`, counts unreleased seat-consuming assignments, and changes the assignment within the same transaction.

Database uniqueness prevents duplicate entitlement/user rows. Two administrators cannot allocate the final seat twice.

Completed learners retain their assigned seat until membership removal or explicit revocation. Guardian-pending access also reserves a seat. Temporary user or Connector suspension retains the seat so access can restore without changing the administrator's assignment decision.

A member with active independent module access is materialized as `independent`, does not consume a Connector seat, and remains visible to the administrator.

## 17. Access Rules, Direct Assignments, and Revocations

Supported rule targets are all members, a Connector role, and a specific member.

Assignment precedence is:

```text
explicit revocation
> direct member rule
> any matching inherited role/all-members rule
> no access
```

An explicit revocation is a retained tombstone. It releases the seat and continues to override inherited rules until an authorized administrator explicitly clears it.

Creating a rule whose current eligible matches exceed available seats is rejected atomically. If future membership or role changes create overflow, the rule remains active while excess assignments become inactive with `capacity_unavailable`.

Reconciliation order is deterministic: direct assignments first, then inherited assignments ordered by rule creation, membership acceptance, and user id. A new direct assignment may displace the lowest-priority inherited assignment into the capacity-inactive state.

Multiple matching inherited rules produce one assignment. Reconciliation recomputes the representative source rather than deleting history.

## 18. Learner Access Resolution and Enrollment Integration

`ModuleAccessService` becomes the server-side authority for whether a learner may perform a learning action. It evaluates:

- active user and platform enforcement state
- module learning availability and approved revision
- approved enrollment
- at least one active access grant
- active Connector membership for Connector grants
- active Connector entitlement
- unreleased, non-revoked seat assignment
- current module eligibility
- guardian approval where applicable

Connector distribution bypasses the instructor's per-learner manual approval because the instructor opted into the paid organization distribution. It does not bypass guardian approval, module eligibility, instructor tier capacity, or platform enforcement.

One `ModuleEnrollment` remains the learner's learning record. Multiple grants may keep it approved. When the final active grant ends, the enrollment becomes `inactive`; progress and completion fields are unchanged.

The shared access check will replace inconsistent enrollment-only checks in module, lesson, quiz, topic translation, interactive activity/checkpoint, protected-content, completion, feedback, and certificate-generation entry points.

## 19. Membership Changes, Suspensions, and Access Restoration

When a membership becomes removed:

- related assignments become inactive and released
- Connector grants end
- the shared service inactivates the enrollment only if no other grant remains
- progress, attempts, completion, and certificates remain

When a user rejoins, all-member, role, and direct rules reconcile against the new active membership. Access reactivates only if eligibility, guardian, entitlement, seat, and revocation checks pass.

Role changes recalculate inherited matches. Direct rules and revocation tombstones do not change merely because the role changes.

Connector and user suspension block learning without releasing assignments. Instructor tier downgrade alone does not revoke access.

Membership invitation acceptance, membership-request approval, role change, removal, and owner withdrawal call the same reconciliation service explicitly; no hidden parallel membership system is introduced.

## 20. Connector Organization Learning Library

Purchased Modules shows one row/card per organization entitlement with:

- module and owner
- purchase status/date
- gross price and currency
- entitlement status
- fixed seats, used seats, and available seats
- members with active, pending, inactive, independent, or revoked access
- links to details and Access Management

Access Management provides rule creation/removal, direct assignment, revocation, revocation clearing, and assignment-status explanations. Only `connector.manage_modules` users see or can call these controls.

The library never mutates the purchase when assignments change.

## 21. Learner, Instructor, Connector, and Admin Experiences

After purchase, a dedicated confirmation states that the module was added to the organization learning library and was not automatically assigned. It offers **Configure Access** and **View Learning Library**.

Learner module cards/details identify active organization access as `Provided by {Connector}`. Multiple active sources are all shown on details. Historical inactive modules appear under Past Access with progress and issued certificate links, but without learning actions.

Instructor module management gains a marketplace panel with availability, organization price, seats, status, and sales summary. Existing earnings pages show the Connector channel rather than introducing a second earnings dashboard.

Platform administration extends existing module, payment, and module-revenue screens with listing, Connector, seats, channel, and correction information.

Connector administrators see assignment, eligibility, and seat state only. Detailed learner progress surveillance is not part of this version.

## 22. Module Updates, Marketplace Unpublishing, and Safety Enforcement

Entitlements reference the module, not a copied revision. Existing purchasers receive the latest approved published revision automatically.

Marketplace unpublishing never revokes existing access. A module-level moderation or safety restriction may block learning while retaining entitlement and progress history.

The existing revision workflow remains authoritative. Before approving a destructive revision, compatibility validation must detect removal or replacement of lesson/topic/quiz identifiers that already have learner progress. Compatible additions and edits require no repurchase.

## 23. Notifications, Auditing, and Historical Records

Database notifications cover:

- purchase completion or attention-required finalization
- assignment activation and revocation
- administrator listing suspension

Capacity validation failures are displayed synchronously and written to existing application logs; they do not create noisy notifications for ordinary rejected form submissions.

All notifications are dispatched after transaction commit. Existing admin payment notifications continue to receive organization-channel payments.

Existing activity logging records listing changes and manual corrections with actor, Connector, module/purchase, previous state, resulting state, reason, and timestamp.

## 24. Failure Recovery, Data Integrity, and Edge Cases

- Duplicate purchase: unique Connector/module lifecycle returns the existing state.
- Failed payment: no entitlement; hold expires automatically.
- Duplicate webhook/poll: idempotent completion returns the existing entitlement.
- Provider success plus transient local failure: retry finalization.
- Provider success after unavailable capacity: `attention_required`, admin notification, no false entitlement.
- Concurrent assignment: entitlement row lock and unique assignment prevent oversubscription.
- Removed member: assignment released; learning history retained.
- Explicit revocation: inherited access remains blocked until cleared.
- Listing unpublished during checkout: an unexpired snapshotted checkout may complete; no new checkout may start.
- Module update: entitlement follows the compatible approved revision.
- Tier downgrade: no revocation; new capacity use blocked.
- Suspended account/Connector: learning blocked; assignment retained.
- Same learner assigned by two Connectors: each organization consumes its own seat; either active source keeps learner access.
- Independent learner ownership: no Connector seat is consumed.
- Hard deletion: protected by restrictive relationships and application authorization for marketplace history.

## 25. Incremental Migrations and Backward Compatibility

All changes use forward, incremental migrations. Development data must never be reset, wiped, truncated, recreated, or replaced with seed data.

Migration order is:

1. Marketplace commerce tables and model relationships.
2. Enrollment/access-source fields and safe backfill.
3. Module-purchase capacity hold.
4. Ledger organization-channel fields and backfill.

Backfills are idempotent. Existing approved enrollments receive direct-enrollment grants; existing completed purchases receive purchase grants. No progress or completion row changes.

Tests run only through the existing isolated MySQL `cc_db_test` configuration in `phpunit.xml`.

## 26. End-to-End and Regression Testing Strategy

Unit tests cover effective capacity, commission snapshots, access precedence, and deterministic reconciliation.

Feature tests cover:

- instructor and platform listing authorization/lifecycle
- browsing by ordinary active Connector members
- purchase/manage denial without `connector.manage_modules`
- checkout terms, snapshots, capacity holds, duplicate prevention, and failure
- webhook/poll idempotency and atomic entitlement/ledger creation
- configured 10% instructor commission and 0% platform commission
- concurrent seat allocation
- all/role/member rules, overflow, revocation, and clearing
- membership removal/rejoin, role changes, guardian approval, and suspension
- module/lesson/quiz/activity/content/certificate access
- progress and certificate preservation
- listing unpublishing and module revision behavior

Regression tests cover existing individual purchase, instructor plans, enrollments, commissions, payments, learning, certificates, Connector membership, and authorization.

## 27. Phased Implementation Sequence

The single implementation plan is delivered in these checkpoints:

1. Forward schema and domain models.
2. Shared access-grant and capacity services.
3. Marketplace listing management.
4. Connector browse and shared details UI.
5. Checkout, payment dispatch, finalization, entitlement, and ledger.
6. Access rules, seats, and membership reconciliation.
7. Organization learning library and confirmation UI.
8. Learner endpoint integration and Past Access.
9. Notifications, reporting, corrections, and complete regression QA.

Each checkpoint has focused tests and a reviewable commit.

## 28. Explicit Non-Goals and Connector Subscription Boundary

This version does not implement:

- Connector subscription tiers, billing, renewal, or expiration
- organization-wide member, module, seminar, or feature limits
- subscription-based Connector feature gating
- automated refunds
- self-service seat upgrades or add-on purchases
- multiple listing price tiers or currencies unsupported by current checkout
- channel-specific commission settings
- Connector learner-progress analytics
- a second enrollment, lesson, quiz, progress, completion, or certificate engine

The existing legacy organization subscription configuration is not consulted by marketplace authorization, checkout, entitlements, or assignments.

## 29. Future Connector Subscription Compatibility

A future subscription system may sit above the marketplace as an independent organization-policy layer:

```text
Connector Subscription -> Organization Limits and Feature Entitlements

Module Purchase -> Module Entitlement -> Seat Assignment -> Learner Access
```

That future layer may decide whether a Connector can initiate a purchase or may cap aggregate organization behavior. It must not replace purchase history, module entitlements, or seat assignments. No current marketplace table requires a subscription identifier.

## 30. Acceptance-Criteria Traceability Matrix

| Acceptance criterion | Authority | Primary verification |
| --- | --- | --- |
| Connectors discover eligible modules | Published listing query plus module publication | Connector marketplace feature test |
| Ordinary active members browse | `ConnectorAccessService::abortUnlessWorkspace()` | Permission matrix test |
| Only authorized admins purchase/manage | `connector.manage_modules` server check | 403 feature tests |
| Purchase creates durable entitlement | Connector purchase finalization transaction | Webhook/poll idempotency tests |
| Price, currency, terms, seats, and seller remain historical | Purchase and ledger snapshots | Snapshot regression test |
| Configured commission remains historical | Existing resolver plus ledger snapshot | 10% and policy-change tests |
| Platform sale uses 0% commission | Ledger finalizer owner branch | Platform-owned sale test |
| Seats cannot be oversubscribed | Entitlement lock plus unique assignment | Concurrent assignment test |
| Instructor limits cannot be bypassed | Shared `ModuleCapacityService` | Individual/Connector capacity tests |
| Purchased differs from granted | Entitlement exists without assignments | Purchase lifecycle test |
| All/role/member rules work | Rule reconciliation service | Rule matrix tests |
| Revocation overrides inherited rules | Retained assignment tombstone | Revoke/clear tests |
| Membership removal releases a seat | Membership reconciliation | Remove/reassign test |
| Progress and certificates survive access loss | Existing learning rows are never deleted | Access-loss regression test |
| Listing unpublish preserves purchases | Listing and entitlement states are separate | Unpublish test |
| Learners reuse existing learning engine | One enrollment and existing progress tables | Full learner flow test |
| Non-refundable by default | Versioned checkout acceptance and refund guard | Checkout/refund-scope test |
| No Connector subscription dependency | No subscription FK/service lookup | Architecture/schema test |
| Existing systems remain functional | Existing focused suites plus full regression | Regression commands in implementation plan |
