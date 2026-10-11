# Connector Module Marketplace and Organization Learning Library Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Let verified Connectors buy fixed-seat licenses for instructor-owned or platform-owned modules, assign those seats to eligible members, and reuse the existing learner enrollment, progress, completion, and certificate experience.

**Architecture:** Add a Connector-specific listing, purchase, entitlement, rule, and seat-assignment layer while retaining `Module` as the content source. Route individual and organization access through shared access/capacity services, extend the existing PayMongo and module-sale ledger paths, and keep all authorization Connector-scoped and server-side. No Connector subscription participates in marketplace eligibility, capacity, or access.

**Tech Stack:** Laravel 12, PHP 8.2+, Eloquent/MySQL, Blade/Alpine/Tailwind, PayMongo through the existing `PayMongoPaymentLinkService`, Laravel database notifications, PHPUnit 11. No new dependency.

## Global Constraints

- Approved design: `docs/superpowers/specs/2026-10-05-connector-module-marketplace-design.md`.
- Use the lifecycle `Module -> Listing -> Connector Purchase -> Entitlement -> Seat Assignment -> Access Grant -> Existing Enrollment -> Existing Learning`.
- A purchase licenses a module; it never transfers module ownership.
- Every active member of a verified Connector may browse; purchase and access management require `connector.manage_modules`.
- Reuse the existing learner module cards and refactor the learner Module Details presentation for the Connector details page; do not expose protected lesson bodies, attachments, quiz questions, or answers before access.
- Organization price is listing-specific. V1 uses the currency configured by `billing.currency.code`, fixed seat packages, a 30-minute checkout hold, and terms version `2026-10-05`.
- Instructor-owned sales use the existing `InstructorPlanCapabilityService` and `CommissionPolicyResolver`; the current 10% policy must be resolved, not hard-coded. Platform-owned sales use 0% commission.
- Purchase does not automatically grant any member. All-member, role, and specific-member rules are separate from entitlement creation.
- Explicit revocation overrides inherited rules until explicitly cleared. Membership removal releases a seat; temporary suspension retains the assignment but blocks learning.
- Preserve progress, attempts, completion, and issued certificates when access becomes inactive.
- Existing individual module purchasing, instructor tiers, enrollments, payments, ledger entries, learning, and certificates must continue to work.
- Do not add Connector subscription plans, billing, renewal, expiration, organization-wide limits, or feature gating. Do not consult the existing organization subscription configuration.
- Connector purchases are non-refundable by default. Do not route them through automated `RefundService`; corrections are append-only administrator actions.
- Preserve all development data. Never run `migrate:fresh`, `db:wipe`, destructive seeders, truncation, or destructive tests against the development database.
- Run database tests only with the existing MySQL `cc_db_test` settings in `phpunit.xml`. Apply production/development schema changes only through normal forward migrations.

---

## File map and delivery order

| Area | Files and responsibility |
| --- | --- |
| Schema | Four forward migrations under `database/migrations/2026_10_05_*`: marketplace commerce tables, access grants/inactive enrollment, individual capacity holds, and organization-channel ledger fields/backfills. |
| Domain models | New marketplace, purchase, entitlement, rule, assignment, and grant models; extend `Module`, `Connector`, `User`, `Payment`, `ModulePurchase`, `ModuleEnrollment`, and `ModuleSaleLedger` relationships/casts/constants. |
| Shared decisions | New `app/Services/Modules/ModuleAccessService.php` and `ModuleCapacityService.php`: access provenance, eligibility, enrollment synchronization, effective instructor/module limits, holds, and reservations. |
| Listing management | New `ModuleMarketplaceListingService`, request, thin instructor controller, policy methods, instructor/admin routes, and a shared marketplace panel in existing module management. |
| Connector browse | New `Connector/ModuleMarketplaceController`, Connector routes/views, generalized learner module card, and shared overview/curriculum partials used by both details pages. |
| Connector checkout | New `ConnectorModulePurchaseService` and thin purchase controller; extend the existing checkout summary and PayMongo metadata using `connector_module_purchase`. |
| Financial completion | Extend `ModuleSaleLedgerService`, `ModuleSaleLedger`, admin/instructor revenue queries, and create entitlement plus ledger atomically and idempotently. |
| Payment plumbing | Extend `Payment`, `PaymentController`, API webhook, observer, receipt service/views, payment administration, and refund guard for the new payment scope. |
| Access management | New `ConnectorModuleAccessService`, request/controller/routes, deterministic rules, row-locked seat assignment, revocation tombstones, and membership reconciliation hooks. |
| Organization library | New Connector library/access/confirmation views plus purchase, seat, member, and status presentation. |
| Learner integration | Route module, lesson, quiz, interactive activity/checkpoint, translation, protected content, feedback/completion, chat entitlement, and certificate-generation checks through `ModuleAccessService`; add source attribution and Past Access. |
| Audit/notifications | New database notifications, existing `AdminActivityLogService`, administrator correction action, and protection against deleting organization-channel finance history. |
| Verification | Focused feature/unit tests under existing `tests/Feature/{Connectors,Learner,Instructor,Admin}` and `tests/Unit/Services`, then existing regression suites, Pint, route checks, and asset build. |

Each task is a review checkpoint. Commit only after its focused tests pass. Do not run a test command if configuration inspection shows a database other than `cc_db_test`.

### Task 1: Forward schema and domain model foundation

**Files:**
- Create: `database/migrations/2026_10_05_000001_create_connector_module_marketplace_tables.php`
- Create: `database/migrations/2026_10_05_000002_create_module_access_grants_and_inactivate_enrollments.php`
- Create: `database/migrations/2026_10_05_000003_add_capacity_holds_to_module_purchases.php`
- Create: `database/migrations/2026_10_05_000004_extend_module_sale_ledgers_for_connector_sales.php`
- Create: `app/Models/ModuleMarketplaceListing.php`
- Create: `app/Models/ConnectorModulePurchase.php`
- Create: `app/Models/ConnectorModuleEntitlement.php`
- Create: `app/Models/ConnectorModuleAccessRule.php`
- Create: `app/Models/ConnectorModuleSeatAssignment.php`
- Create: `app/Models/ModuleAccessGrant.php`
- Modify: `app/Enums/EnrollmentStatus.php`
- Modify: `app/Models/Module.php`
- Modify: `app/Models/Connector.php`
- Modify: `app/Models/User.php`
- Modify: `app/Models/Payment.php`
- Modify: `app/Models/ModulePurchase.php`
- Modify: `app/Models/ModuleEnrollment.php`
- Modify: `app/Models/ModuleSaleLedger.php`
- Test: `tests/Feature/Connectors/ConnectorModuleMarketplaceSchemaTest.php`

**Interfaces:** The new models expose standard Eloquent relationships named `listing`, `purchase`, `entitlement`, `rules`, `assignments`, `module`, `connector`, `payment`, and `actor` where applicable. Later tasks rely on model constants rather than repeated status strings.

- [ ] **Step 1: Write the failing schema/model test.** Use `RefreshDatabase`, assert every approved table/column, model cast, and relationship, and verify backfill without altering an existing enrollment or purchase. Core test body:

```php
public function test_marketplace_schema_and_access_backfill_preserve_existing_learning(): void
{
    $user = User::factory()->create();
    $module = Module::factory()->create();
    $enrollment = ModuleEnrollment::query()->create([
        'user_id' => $user->id,
        'module_id' => $module->id,
        'status' => EnrollmentStatus::Approved,
        'enrolled_at' => now(),
        'completion_percentage' => 40,
    ]);

    $this->assertTrue(Schema::hasColumns('module_marketplace_listings', [
        'module_id', 'seller_type', 'seller_user_id', 'status',
        'price_amount', 'currency', 'seat_capacity', 'published_by', 'published_at',
        'unpublished_by', 'unpublished_at', 'suspended_by', 'suspended_at', 'suspension_reason',
    ]));
    $this->assertTrue(Schema::hasColumns('connector_module_purchases', [
        'connector_id', 'module_marketplace_listing_id', 'module_id', 'purchased_by',
        'payment_id', 'status', 'gross_amount', 'currency', 'seat_capacity',
        'seller_type_snapshot', 'seller_user_id_snapshot', 'module_title_snapshot',
        'terms_version', 'terms_accepted_at', 'capacity_hold_expires_at', 'provider_paid_at',
        'purchased_at', 'failed_at', 'failure_reason', 'corrected_at', 'correction_reason',
    ]));
    $this->assertTrue(Schema::hasColumns('connector_module_entitlements', [
        'connector_id', 'connector_module_purchase_id', 'module_marketplace_listing_id',
        'module_id', 'status', 'gross_amount', 'currency', 'seat_capacity',
        'activated_at', 'suspended_at', 'status_reason',
    ]));
    $this->assertTrue(Schema::hasColumns('connector_module_access_rules', [
        'connector_module_entitlement_id', 'target_type', 'target_id', 'rule_key',
        'is_active', 'created_by',
    ]));
    $this->assertTrue(Schema::hasColumns('connector_module_seat_assignments', [
        'connector_module_entitlement_id', 'user_id', 'connector_membership_id',
        'connector_module_access_rule_id', 'source_type', 'status', 'inactive_reason',
        'consumes_seat', 'assigned_at', 'released_at', 'revoked_at', 'reactivated_at', 'revoked_by',
    ]));
    $this->assertTrue(Schema::hasColumns('module_access_grants', [
        'user_id', 'module_id', 'source_type', 'source_id', 'connector_id',
        'status', 'granted_at', 'ended_at', 'reason',
    ]));
    $this->assertTrue(Schema::hasColumns('module_enrollments', [
        'inactive_reason', 'inactivated_at',
    ]));
    $this->assertTrue(Schema::hasColumn('module_purchases', 'capacity_hold_expires_at'));
    $this->assertTrue(Schema::hasColumns('module_sale_ledgers', [
        'sale_channel', 'connector_module_purchase_id', 'connector_id', 'buyer_user_id',
        'seller_type', 'seller_user_id', 'seller_name_snapshot',
        'seat_capacity_snapshot', 'seller_net_amount',
    ]));

    $this->assertDatabaseHas('module_access_grants', [
        'user_id' => $user->id,
        'module_id' => $module->id,
        'source_type' => ModuleAccessGrant::SOURCE_DIRECT_ENROLLMENT,
        'source_id' => $enrollment->id,
        'status' => ModuleAccessGrant::STATUS_ACTIVE,
    ]);
    $this->assertSame(40, $enrollment->fresh()->completion_percentage);
}
```

- [ ] **Step 2: Run the test and verify the expected failure.** Run `php artisan test --filter=ConnectorModuleMarketplaceSchemaTest`; expect failure because the tables/models do not exist.

- [ ] **Step 3: Implement the four migrations and model surface.** Use `string` status columns for new domains, decimal `(10,2)` monetary snapshots, `char(3)` currency, restrictive historical foreign keys, and these uniqueness rules:

```php
$table->unique('module_id', 'uq_module_marketplace_listings_module');
$table->unique(['connector_id', 'module_id'], 'uq_connector_module_purchase');
$table->unique('connector_module_purchase_id', 'uq_connector_module_entitlement_purchase');
$table->unique(['connector_id', 'module_id'], 'uq_connector_module_entitlement');
$table->unique(['connector_module_entitlement_id', 'rule_key'], 'uq_connector_module_rule_key');
$table->unique(['connector_module_entitlement_id', 'user_id'], 'uq_connector_module_assignment_user');
$table->unique(['source_type', 'source_id'], 'uq_module_access_grant_source');
```

Use these exact model constants. The migration `down()` methods may reverse an empty installation, but must throw before dropping non-empty marketplace commerce/access tables or removing enrollment states still used by rows:

```php
// ModuleMarketplaceListing
public const STATUS_DRAFT = 'draft';
public const STATUS_PUBLISHED = 'published';
public const STATUS_UNPUBLISHED = 'unpublished';
public const STATUS_SUSPENDED = 'suspended';

// ConnectorModulePurchase
public const STATUS_PENDING = 'pending';
public const STATUS_COMPLETED = 'completed';
public const STATUS_FAILED = 'failed';
public const STATUS_CANCELLED = 'cancelled';
public const STATUS_ATTENTION_REQUIRED = 'attention_required';
public const STATUS_CORRECTED = 'corrected';
public const TERMS_VERSION = '2026-10-05';

// Existing ModulePurchase extension
public const STATUS_ATTENTION_REQUIRED = 'attention_required';

// ConnectorModuleEntitlement
public const STATUS_ACTIVE = 'active';
public const STATUS_SUSPENDED = 'suspended';
public const STATUS_VOIDED = 'voided';
public const STATUS_CORRECTED = 'corrected';

// ConnectorModuleAccessRule
public const TARGET_ALL_MEMBERS = 'all_members';
public const TARGET_ROLE = 'role';
public const TARGET_MEMBER = 'member';

// ConnectorModuleSeatAssignment
public const SOURCE_DIRECT = 'direct';
public const SOURCE_INHERITED = 'inherited';
public const STATUS_PENDING_GUARDIAN = 'pending_guardian';
public const STATUS_ACTIVE = 'active';
public const STATUS_INDEPENDENT = 'independent';
public const STATUS_INACTIVE = 'inactive';
public const STATUS_REVOKED = 'revoked';

// ModuleAccessGrant
public const SOURCE_DIRECT_ENROLLMENT = 'direct_enrollment';
public const SOURCE_INDIVIDUAL_PURCHASE = 'individual_purchase';
public const SOURCE_CONNECTOR_ASSIGNMENT = 'connector_assignment';
public const STATUS_PENDING = 'pending';
public const STATUS_ACTIVE = 'active';
public const STATUS_INACTIVE = 'inactive';
```

Add `EnrollmentStatus::Inactive = 'inactive'`, `inactive_reason`, and `inactivated_at`. Follow the existing MySQL `ALTER TABLE ... ENUM(...)` migration pattern and refuse a destructive rollback while inactive rows exist. Backfill direct grants from every current enrollment and purchase grants from completed `module_purchases` with `insertOrIgnore`; derive grant status from the current purchase/enrollment state and do not update progress/completion fields.

Extend `module_sale_ledgers` with `sale_channel`, nullable `connector_module_purchase_id`, nullable `connector_id`, `buyer_user_id`, `seller_type`, nullable `seller_user_id`, `seller_name_snapshot`, `seat_capacity_snapshot`, and `seller_net_amount`. Make the legacy `module_purchase_id`, `instructor_id`, and `learner_id` references nullable for organization/platform sales, retain their columns and relationships for individual-sale compatibility, and replace cascade-delete finance foreign keys with restrictive or nulling behavior so deleting a related record cannot delete ledger history. Backfill `sale_channel=individual`, buyer from `learner_id`, seller from `instructor_id`, and seller net from `instructor_earnings_amount`.

- [ ] **Step 4: Run schema and existing monetization tests.** Run `php artisan test tests/Feature/Connectors/ConnectorModuleMarketplaceSchemaTest.php tests/Feature/Admin/ModuleMonetizationSchemaTest.php tests/Feature/Learner/ModuleSaleLedgerCreationTest.php`; expect all pass.

- [ ] **Step 5: Commit.** Stage only the migrations, models, enum, and schema test; commit with `git commit -m "feat: add connector module marketplace schema"`.

### Task 2: Shared module access grants and enrollment synchronization

**Files:**
- Create: `app/Services/Modules/ModuleAccessService.php`
- Test: `tests/Feature/Learner/ModuleAccessGrantLifecycleTest.php`
- Modify: `app/Http/Controllers/Learner/ModuleController.php`
- Modify: `app/Http/Controllers/Instructor/EnrollmentController.php`
- Modify: `app/Http/Controllers/Instructor/UserController.php`
- Modify: `app/Http/Controllers/ParentController.php`
- Modify: `app/Services/ModulePurchaseService.php`

**Interfaces:**

```php
ModuleAccessService::grantDirectEnrollment(ModuleEnrollment $enrollment): ModuleAccessGrant
ModuleAccessService::grantIndividualPurchase(ModulePurchase $purchase): ModuleAccessGrant
ModuleAccessService::grantConnectorAssignment(ConnectorModuleSeatAssignment $assignment, bool $guardianPending = false): ModuleAccessGrant
ModuleAccessService::activateSource(string $sourceType, int $sourceId): ModuleAccessGrant
ModuleAccessService::deactivateSource(string $sourceType, int $sourceId, string $reason): void
ModuleAccessService::syncEnrollment(User $user, Module $module): ModuleEnrollment
ModuleAccessService::hasActiveAccess(User $user, Module $module): bool
ModuleAccessService::assertCanLearn(User $user, Module $module): void
ModuleAccessService::activeConnectorSources(User $user, Module $module): Collection
ModuleAccessService::hasHistoricalAccess(User $user, Module $module): bool
```

- [ ] **Step 1: Write failing lifecycle tests.** Cover direct enrollment, manual approval/rejection, guardian approval/rejection, completed individual purchase, two simultaneous source grants, and final-source deactivation. Assert that final deactivation changes only enrollment access state:

```php
$service->grantIndividualPurchase($purchase);
$service->grantConnectorAssignment($assignment);
$service->deactivateSource(ModuleAccessGrant::SOURCE_CONNECTOR_ASSIGNMENT, $assignment->id, 'membership_removed');
$this->assertTrue($service->hasActiveAccess($learner, $module));

$service->deactivateSource(ModuleAccessGrant::SOURCE_INDIVIDUAL_PURCHASE, $purchase->id, 'corrected');
$this->assertFalse($service->hasActiveAccess($learner, $module));
$this->assertDatabaseHas('module_enrollments', [
    'user_id' => $learner->id,
    'module_id' => $module->id,
    'status' => EnrollmentStatus::Inactive->value,
    'completion_percentage' => 65,
]);
$this->assertDatabaseHas('user_progress', ['user_id' => $learner->id, 'module_id' => $module->id]);
```

- [ ] **Step 2: Run the test and observe failure.** Run `php artisan test --filter=ModuleAccessGrantLifecycleTest`; expect missing service/method failures.

- [ ] **Step 3: Implement the service and route status mutations through it.** `syncEnrollment()` locks the unique learner/module enrollment and derives only its access status; it never deletes or zeroes learning fields. Its central branch is:

```php
$hasActiveGrant = ModuleAccessGrant::query()
    ->where('user_id', $user->id)
    ->where('module_id', $module->id)
    ->where('status', ModuleAccessGrant::STATUS_ACTIVE)
    ->exists();

if ($hasActiveGrant && ! in_array($enrollment->status, [
    EnrollmentStatus::PendingParentApproval,
    EnrollmentStatus::Rejected,
], true)) {
    $enrollment->forceFill([
        'status' => EnrollmentStatus::Approved,
        'enrolled_at' => $enrollment->enrolled_at ?? now(),
        'inactive_reason' => null,
        'inactivated_at' => null,
    ])->save();
} elseif (! $hasActiveGrant && $enrollment->status === EnrollmentStatus::Approved) {
    $enrollment->forceFill([
        'status' => EnrollmentStatus::Inactive,
        'inactive_reason' => 'no_active_access_source',
        'inactivated_at' => now(),
    ])->save();
}
```

Make new direct enrollments create a direct source, individual payment completion create a purchase source, instructor/guardian approval activate the matching source, and rejection/archive deactivate it. Treat pre-backfill approved enrollments as direct access only during the migration test; application runtime uses grants. Connector validation is added when Connector grants are introduced in Task 9.

- [ ] **Step 4: Run focused access and existing enrollment tests.** Run `php artisan test tests/Feature/Learner/ModuleAccessGrantLifecycleTest.php tests/Feature/Learner/LearnerPaidModulePurchaseFlowTest.php tests/Feature/Instructor/InstructorEnrollmentsRefinementTest.php`; expect pass.

- [ ] **Step 5: Commit.** Commit with `git commit -m "refactor: centralize module access grants"`.

### Task 3: Shared instructor/module capacity accounting and holds

**Files:**
- Create: `app/Services/Modules/ModuleCapacityService.php`
- Modify: `config/billing.php`
- Modify: `app/Services/ModulePurchaseService.php`
- Modify: `app/Http/Controllers/Learner/ModuleController.php`
- Modify: `app/Http/Controllers/Instructor/EnrollmentController.php`
- Test: `tests/Feature/Learner/SharedModuleCapacityTest.php`
- Test: `tests/Unit/Services/Modules/ModuleCapacityServiceTest.php`

**Interfaces:**

```php
ModuleCapacityService::lock(Module $module): Module
ModuleCapacityService::effectiveLimit(Module $module): ?int
ModuleCapacityService::usage(Module $module, ?int $excludeConnectorPurchaseId = null, ?int $excludeModulePurchaseId = null): array
ModuleCapacityService::assertAvailable(Module $module, int $requestedSeats, ?int $excludeConnectorPurchaseId = null, ?int $excludeModulePurchaseId = null): void
```

`usage()` returns `independent_learners`, `connector_reserved_seats`, `pending_hold_seats`, `used`, `limit`, and `available`.

- [ ] **Step 1: Write failing capacity tests.** Test platform module limit, instructor free/paid plan cap, the lower of plan/module limits, unlimited null, distinct independent learners, unexpired/expired individual holds, an individual provider payment arriving after its hold expired, Connector package reservations, no double count for Connector learners, and tier downgrade preservation. The core formula assertion is:

```php
$usage = $capacity->usage($module);

$this->assertSame(2, $usage['independent_learners']);
$this->assertSame(5, $usage['connector_reserved_seats']);
$this->assertSame(3, $usage['pending_hold_seats']);
$this->assertSame(10, $usage['used']);
$this->assertSame(12, $usage['limit']);
$this->assertSame(2, $usage['available']);
```

- [ ] **Step 2: Run the tests and observe failure.** Run `php artisan test tests/Unit/Services/Modules/ModuleCapacityServiceTest.php tests/Feature/Learner/SharedModuleCapacityTest.php`; expect the service/config/hold behavior to be absent.

- [ ] **Step 3: Implement capacity calculation and replace controller-local calculations.** Add:

```php
'module_marketplace' => [
    'capacity_hold_minutes' => 30,
    'terms_version' => '2026-10-05',
],
```

Resolve instructor limits with the existing service and module access type:

```php
$moduleLimit = $module->enrollment_limit !== null ? (int) $module->enrollment_limit : null;
if ((string) $module->content_owner_type !== 'instructor' || ! $module->creator) {
    return $moduleLimit;
}

$planLimit = $this->instructorPlans->getLearnerCapForModule(
    $module->creator,
    $module->isPaidAccess() ? 'paid' : 'free',
);

return match (true) {
    $moduleLimit === null => $planLimit,
    $planLimit === null => $moduleLimit,
    default => min($moduleLimit, $planLimit),
};
```

Count distinct active non-Connector grant users, active/suspended/corrected entitlement seat packages, and unexpired individual/Connector holds. All capacity-changing callers begin a database transaction, call `lock()`, then `assertAvailable()`. Existing individual checkout writes `module_purchases.capacity_hold_expires_at`; completion excludes its own hold and converts it into active access. Expired holds are ignored without deleting the purchase. If an individual provider payment arrives after its hold expires and capacity cannot be reacquired, preserve the completed payment, mark the purchase `attention_required`, and grant no access until an administrator resolves capacity.

- [ ] **Step 4: Run capacity and existing tier tests.** Run `php artisan test tests/Unit/Services/Modules/ModuleCapacityServiceTest.php tests/Feature/Learner/SharedModuleCapacityTest.php tests/Feature/Learner/LearnerModuleCapacityBehaviorTest.php tests/Feature/Learner/LearnerEnrollmentCapByInstructorPlanTest.php tests/Feature/Instructor/InstructorModulePlanEnforcementTest.php`; expect pass.

- [ ] **Step 5: Commit.** Commit with `git commit -m "refactor: share module capacity accounting"`.

### Task 4: Instructor and platform marketplace listing management

**Files:**
- Create: `app/Services/Modules/ModuleMarketplaceListingService.php`
- Create: `app/Http/Requests/Instructor/UpsertModuleMarketplaceListingRequest.php`
- Create: `app/Http/Controllers/Instructor/ModuleMarketplaceListingController.php`
- Create: `resources/views/instructor/modules/partials/connector-marketplace.blade.php`
- Modify: `app/Policies/ModulePolicy.php`
- Modify: `routes/instructor.php`
- Modify: `routes/admin.php`
- Modify: `app/Http/Controllers/Instructor/ModuleController.php`
- Modify: `resources/views/instructor/modules/edit.blade.php`
- Modify: `resources/views/instructor/modules/show.blade.php`
- Test: `tests/Feature/Instructor/InstructorConnectorMarketplaceListingTest.php`
- Test: `tests/Feature/Admin/AdminConnectorMarketplaceListingTest.php`

**Interfaces:**

```php
ModuleMarketplaceListingService::upsert(Module $module, User $actor, array $data): ModuleMarketplaceListing
ModuleMarketplaceListingService::unpublish(ModuleMarketplaceListing $listing, User $actor): ModuleMarketplaceListing
ModuleMarketplaceListingService::suspend(ModuleMarketplaceListing $listing, User $admin, string $reason): ModuleMarketplaceListing
ModulePolicy::manageMarketplaceListing(User $user, Module $module): bool
ModulePolicy::suspendMarketplaceListing(User $user, Module $module): bool
```

- [ ] **Step 1: Write failing instructor/admin lifecycle tests.** Cover owner-only instructor changes, platform-admin ownership, approved/published requirement, positive price/seats, configured currency, plan paid-distribution capability, capacity, unpublish preserving an entitlement fixture, and admin suspension. Representative assertions:

```php
$this->actingAs($instructor)
    ->put(route('instructor.modules.marketplace.update', $module), [
        'price_amount' => 2500,
        'currency' => config('billing.currency.code'),
        'seat_capacity' => 10,
        'action' => 'publish',
    ])->assertRedirect();

$this->assertDatabaseHas('module_marketplace_listings', [
    'module_id' => $module->id,
    'seller_type' => 'instructor',
    'seller_user_id' => $instructor->id,
    'status' => ModuleMarketplaceListing::STATUS_PUBLISHED,
]);

$this->actingAs($otherInstructor)
    ->put(route('instructor.modules.marketplace.update', $module), $payload)
    ->assertForbidden();
```

- [ ] **Step 2: Run lifecycle tests and observe failure.** Run `php artisan test --filter=ConnectorMarketplaceListingTest`; expect missing routes/service failures.

- [ ] **Step 3: Implement the listing service, policy, routes, and shared management panel.** Add these routes in both panel files, pointing to the same thin controller:

```php
Route::put('modules/{module}/marketplace', [Instructor\ModuleMarketplaceListingController::class, 'update'])
    ->name('modules.marketplace.update');
Route::delete('modules/{module}/marketplace', [Instructor\ModuleMarketplaceListingController::class, 'destroy'])
    ->name('modules.marketplace.destroy');
```

Add the admin-only suspension route in `routes/admin.php`. The request validates `price_amount => numeric|min:0.01`, configured three-letter currency, `seat_capacity => integer|min:1`, and action `save|publish`. The service derives seller identity from the module, calls `ModuleCapacityService::assertAvailable()` when publishing, requires the current approved/published revision, and checks instructor `canPublishPaidModules()` plus `canReceivePaidEnrollments()`. The Blade partial shows organization price, seats, availability, status, publish/unpublish, and admin suspension without changing individual module pricing fields.

- [ ] **Step 4: Run listing and existing module-governance tests.** Run `php artisan test tests/Feature/Instructor/InstructorConnectorMarketplaceListingTest.php tests/Feature/Admin/AdminConnectorMarketplaceListingTest.php tests/Feature/Instructor/InstructorModuleStatusLifecycleTest.php tests/Feature/Instructor/InstructorModulePlanEnforcementTest.php tests/Feature/Admin/AdminModuleAuthoringWorkflowTest.php`; expect pass.

- [ ] **Step 5: Commit.** Commit with `git commit -m "feat: manage connector marketplace listings"`.

### Task 5: Connector marketplace browse and shared Module Details presentation

**Files:**
- Create: `app/Http/Controllers/Connector/ModuleMarketplaceController.php`
- Create: `resources/views/connectors/modules/partials/tabs.blade.php`
- Create: `resources/views/connectors/modules/marketplace/index.blade.php`
- Create: `resources/views/connectors/modules/marketplace/show.blade.php`
- Create: `resources/views/modules/partials/details-overview.blade.php`
- Create: `resources/views/modules/partials/details-curriculum.blade.php`
- Modify: `routes/connector.php`
- Modify: `app/Http/Controllers/Connector/DashboardController.php`
- Modify: `resources/views/layouts/connector-app.blade.php`
- Modify: `resources/views/components/learner/module-card-recommended.blade.php`
- Modify: `resources/views/learner/modules/show.blade.php`
- Test: `tests/Feature/Connectors/ConnectorModuleMarketplaceBrowseTest.php`
- Test: `tests/Feature/Learner/ModuleDetailsSharedPresentationTest.php`

**Interfaces:** `ModuleMarketplaceController::index(Request $request, Connector $connector): View`; `ModuleMarketplaceController::show(Request $request, Connector $connector, ModuleMarketplaceListing $listing): View`. Shared curriculum receives `$lessons` and `$canOpenLessons`; Connector context always passes false.

- [ ] **Step 1: Write failing browse/detail tests.** Cover verified active member browse, ordinary member visibility without `connector.manage_modules`, suspended/unverified Connector denial, unpublished/suspended listing absence, purchase button only for managers, existing entitlement status, and protected-content omission. Use assertions that inspect controller view data as well as HTML:

```php
$response = $this->actingAs($ordinaryMember)
    ->get(route('connector.modules', $connector));

$response->assertOk()
    ->assertSee($module->title)
    ->assertSee('10 seats')
    ->assertDontSee('Purchase for Connector');

$detail = $this->actingAs($ordinaryMember)
    ->get(route('connector.modules.marketplace.show', [$connector, $listing]));
$detail->assertOk()
    ->assertSee($lesson->title)
    ->assertDontSee($topic->content, false)
    ->assertDontSee($quizQuestion->question_text);
```

- [ ] **Step 2: Run browse/detail tests and observe failure.** Run `php artisan test tests/Feature/Connectors/ConnectorModuleMarketplaceBrowseTest.php tests/Feature/Learner/ModuleDetailsSharedPresentationTest.php`; expect missing controller/views/routes.

- [ ] **Step 3: Replace the stub with the scoped marketplace and extract shared presentation.** In `routes/connector.php`, replace the Dashboard `modules` route and add static library/access routes before the listing wildcard used in later tasks:

```php
Route::get('/connector/{connector}/modules', [ModuleMarketplaceController::class, 'index'])
    ->name('connector.modules');
Route::get('/connector/{connector}/modules/marketplace/{listing}', [ModuleMarketplaceController::class, 'show'])
    ->name('connector.modules.marketplace.show');
```

Retain the existing `connector.modules` name as the canonical marketplace index so current navigation keeps working without an alias route. Authorize both actions with `ConnectorAccessService::abortUnlessWorkspace()`. Query only `STATUS_PUBLISHED` listings whose module is learner-visible, load owner/public review metadata, and select published lesson metadata without topic bodies, attachments, quiz questions, or answers.

Generalize the learner card with safe defaults:

```blade
@props([
    'module',
    'href' => null,
    'badge' => null,
    'actionLabel' => 'View Module',
    'priceLabel' => null,
    'seatLabel' => null,
])
@php($cardHref = $href ?? route('learner.modules.show', $module))
```

Extract overview and curriculum markup from `learner/modules/show.blade.php`; the learner page passes its existing lesson/progress/action data, while Connector detail passes `canOpenLessons=false`. Change the Connector layout's Modules navigation permission from `connector.manage_modules` to `null`; management buttons remain permission-gated.

- [ ] **Step 4: Run browse, shared-layout, and learner overview regressions.** Run `php artisan test tests/Feature/Connectors/ConnectorModuleMarketplaceBrowseTest.php tests/Feature/Learner/ModuleDetailsSharedPresentationTest.php tests/Feature/Learner/ModuleOverviewLayoutTest.php tests/Feature/Learner/LearnerPublishedModuleVisibilityTest.php`; expect pass.

- [ ] **Step 5: Commit.** Commit with `git commit -m "feat: add connector module marketplace browse"`.

### Task 6: Connector checkout intent, terms snapshot, and capacity hold

**Files:**
- Create: `app/Services/Connectors/ConnectorModulePurchaseService.php`
- Create: `app/Http/Controllers/Connector/ModulePurchaseController.php`
- Create: `resources/views/payments/partials/checkout-item-connector-module.blade.php`
- Modify: `routes/connector.php`
- Modify: `resources/views/payments/checkout-summary.blade.php`
- Test: `tests/Feature/Connectors/ConnectorModuleCheckoutTest.php`

**Interfaces:**

```php
ConnectorModulePurchaseService::createCheckout(
    Connector $connector,
    User $actor,
    ModuleMarketplaceListing $listing,
    ?string $paymentMethod,
    array $billing = []
): array

ModulePurchaseController::checkout(Request $request, Connector $connector, ModuleMarketplaceListing $listing): View|RedirectResponse
ModulePurchaseController::store(ProcessModulePaymentRequest $request, Connector $connector, ModuleMarketplaceListing $listing): RedirectResponse
```

- [ ] **Step 1: Write failing checkout tests.** Mock only `PayMongoPaymentLinkService`, not purchase/capacity logic. Cover missing permission, missing acknowledgement, duplicate completed purchase, listing unpublished, module/seller invalid, capacity shortfall, reusable unexpired checkout, failed provider call releasing hold, current snapshots, and no member assignments. Core successful assertions:

```php
$this->actingAs($manager)->post(
    route('connector.modules.purchase.store', [$connector, $listing]),
    ['payment_method' => 'gcash', 'accept_terms' => '1'],
)->assertRedirect('https://checkout.example.test/org-module');

$this->assertDatabaseHas('connector_module_purchases', [
    'connector_id' => $connector->id,
    'module_id' => $module->id,
    'status' => ConnectorModulePurchase::STATUS_PENDING,
    'gross_amount' => '2500.00',
    'currency' => 'PHP',
    'seat_capacity' => 10,
    'terms_version' => '2026-10-05',
]);
$this->assertDatabaseHas('payments', [
    'user_id' => $manager->id,
    'status' => PaymentStatus::Pending->value,
    'amount' => '2500.00',
]);
$this->assertDatabaseCount('connector_module_entitlements', 0);
$this->assertDatabaseCount('connector_module_seat_assignments', 0);
```

- [ ] **Step 2: Run checkout tests and observe failure.** Run `php artisan test --filter=ConnectorModuleCheckoutTest`; expect missing service/controller behavior.

- [ ] **Step 3: Implement two-phase checkout creation using existing payment infrastructure.** Add GET checkout and POST store routes. Reuse `ProcessModulePaymentRequest` because its payment/billing/accepted-terms contract already matches. Pass `layout => 'layouts.connector-app'` and `scope => 'connector_module_purchase'` into the existing checkout view; add Connector name, seller, seats, price, and explicit non-refundable/no-auto-assignment text in the new partial.

In the service, transaction one locks the module, validates access/listing/duplicate/capacity, creates or resets the unique purchase, creates a pending `Payment`, records snapshots/terms, and sets:

```php
'capacity_hold_expires_at' => now()->addMinutes(
    (int) config('billing.module_marketplace.capacity_hold_minutes', 30)
),
```

Commit before calling PayMongo. Call `createCheckoutSession()` with metadata keys `payment_scope`, `connector_id`, `connector_module_purchase_id`, `marketplace_listing_id`, `module_id`, `user_id`, and `payment_id`. A second short transaction saves checkout id/URL. If the provider call fails, mark that payment/purchase failed and clear the hold; do not delete either row. An unexpired pending checkout returns its existing URL. A retry after failure creates a new `Payment`, updates purchase snapshots from the current listing, and retains older payment history.

- [ ] **Step 4: Run checkout and individual purchase regressions.** Run `php artisan test tests/Feature/Connectors/ConnectorModuleCheckoutTest.php tests/Feature/Learner/LearnerPaidModulePurchaseFlowTest.php tests/Feature/Learner/LearnerCheckoutCompletionIdempotencyTest.php`; expect pass.

- [ ] **Step 5: Commit.** Commit with `git commit -m "feat: start connector module checkout"`.

### Task 7: Atomic payment finalization, entitlement, and commission ledger

**Files:**
- Modify: `app/Services/Connectors/ConnectorModulePurchaseService.php`
- Modify: `app/Services/Monetization/ModuleSaleLedgerService.php`
- Modify: `app/Models/ModuleSaleLedger.php`
- Modify: `app/Models/ConnectorModulePurchase.php`
- Modify: `app/Models/ConnectorModuleEntitlement.php`
- Test: `tests/Feature/Connectors/ConnectorModulePurchaseCompletionTest.php`
- Test: `tests/Feature/Connectors/ConnectorModuleCommissionTest.php`
- Test: `tests/Feature/Connectors/ConnectorModulePurchaseIdempotencyTest.php`

**Interfaces:**

```php
ConnectorModulePurchaseService::completePayment(Payment $payment, ?string $paymentMethod = null, ?string $paymongoPaymentId = null): bool
ConnectorModulePurchaseService::verifyAndCompletePendingPayment(Payment $payment): bool
ModuleSaleLedgerService::createForCompletedConnectorModulePayment(
    Payment $payment,
    ConnectorModulePurchase $purchase,
    ConnectorModuleEntitlement $entitlement
): ModuleSaleLedger
```

- [ ] **Step 1: Write failing completion/financial tests.** Cover instructor sale at configured 10%, later policy change not rewriting history, platform sale at 0%, seller net, immutable price/currency/seats, entitlement without assignments, repeated callback, poll-then-webhook, ledger exception rollback, related-party flag, unpublish after valid checkout, and paid-after-expired-hold capacity conflict. Key assertions:

```php
$this->assertDatabaseHas('module_sale_ledgers', [
    'payment_id' => $payment->id,
    'sale_channel' => 'connector_marketplace',
    'connector_id' => $connector->id,
    'gross_amount' => '2500.00',
    'commission_percent_snapshot' => '10.00',
    'commission_amount' => '250.00',
    'seller_net_amount' => '2250.00',
    'seat_capacity_snapshot' => 10,
]);
$this->assertDatabaseHas('connector_module_entitlements', [
    'connector_module_purchase_id' => $purchase->id,
    'connector_id' => $connector->id,
    'module_id' => $module->id,
    'status' => ConnectorModuleEntitlement::STATUS_ACTIVE,
    'seat_capacity' => 10,
]);
$this->assertDatabaseCount('connector_module_seat_assignments', 0);
```

- [ ] **Step 2: Run completion tests and observe failure.** Run `php artisan test tests/Feature/Connectors/ConnectorModulePurchaseCompletionTest.php tests/Feature/Connectors/ConnectorModuleCommissionTest.php tests/Feature/Connectors/ConnectorModulePurchaseIdempotencyTest.php`; expect no completion/ledger path.

- [ ] **Step 3: Implement locked, idempotent local finalization.** Verify scope and provider result before entering the local transaction. Inside it, lock payment/purchase/module, return the existing entitlement when already completed, and either consume the still-valid hold or reacquire capacity. Create entitlement and ledger before marking purchase completed. Do not catch ledger exceptions inside the transaction.

Instructor split uses the existing resolver/calculator:

```php
$policy = $this->policyResolver->resolveForInstructor($owner->id, $payment->paid_at ?? now());
$split = $this->splitCalculator->calculate(
    grossAmount: (float) $purchase->gross_amount,
    commissionPercent: (float) $policy->commission_percent,
    taxBasis: (string) $policy->tax_basis,
);
```

Platform split writes gross/basis/seller net equal to purchase gross and both commission fields as zero. Use `firstOrCreate(['payment_id' => $payment->id], [...])` and snapshot `refund_policy_snapshot=disabled`. If an expired paid purchase cannot reacquire capacity, record the payment as completed and purchase as `attention_required` in a separate recovery transaction, notify in Task 13, and create neither entitlement nor ledger. A valid snapshotted checkout may complete after listing unpublish; current module safety invalidation sends it to attention instead.

- [ ] **Step 4: Run completion, commission, and existing ledger tests.** Run `php artisan test tests/Feature/Connectors/ConnectorModulePurchaseCompletionTest.php tests/Feature/Connectors/ConnectorModuleCommissionTest.php tests/Feature/Connectors/ConnectorModulePurchaseIdempotencyTest.php tests/Feature/Learner/ModuleSaleLedgerCreationTest.php tests/Feature/Learner/ModuleSaleLedgerIdempotencyTest.php tests/Feature/Admin/AdminCommissionSettingsTest.php`; expect pass.

- [ ] **Step 5: Commit.** Commit with `git commit -m "feat: finalize connector module purchases"`.

### Task 8: Payment webhook, polling, receipts, history, and refund boundary

**Files:**
- Modify: `app/Models/Payment.php`
- Modify: `app/Http/Controllers/Api/WebhookController.php`
- Modify: `app/Http/Controllers/PaymentController.php`
- Modify: `app/Observers/PaymentObserver.php`
- Modify: `app/Services/PaymentReceiptService.php`
- Modify: `app/Services/RefundService.php`
- Modify: `app/Http/Controllers/Admin/PaymentAdminController.php`
- Modify: `resources/views/payments/pending.blade.php`
- Modify: `resources/views/payments/success.blade.php`
- Modify: `resources/views/payments/cancel.blade.php`
- Modify: `resources/views/payments/history.blade.php`
- Modify: `resources/views/payments/receipt.blade.php`
- Modify: `resources/views/admin/payments/index.blade.php`
- Modify: `resources/views/admin/payments/show.blade.php`
- Test: `tests/Feature/Connectors/ConnectorModulePaymentWebhookTest.php`
- Test: `tests/Feature/Connectors/ConnectorModulePaymentStatusTest.php`
- Test: `tests/Feature/Connectors/ConnectorModulePaymentReceiptTest.php`
- Test: `tests/Feature/Connectors/ConnectorModuleRefundBoundaryTest.php`

**Interfaces:**

```php
Payment::isConnectorModulePurchase(): bool
Payment::isModuleCommercePurchase(): bool
Payment::connectorModulePurchase(): HasOne
```

- [ ] **Step 1: Write failing payment-channel tests.** Send the existing signed webhook shape with `payment_scope=connector_module_purchase`, poll pending status twice, render actor/admin receipts, reject a different user, assert no subscription activation/event path, assert admin channel count, and assert `RefundService` throws for this scope.

```php
$payload = $this->paymongoPaidPayload([
    'payment_scope' => 'connector_module_purchase',
    'connector_id' => $connector->id,
    'connector_module_purchase_id' => $purchase->id,
    'payment_id' => $payment->id,
    'user_id' => $manager->id,
]);

$this->postJson('/api/webhooks/paymongo', $payload, $this->signedHeaders($payload))
    ->assertOk();
$this->assertTrue($payment->fresh()->isCompleted());
$this->assertSame(1, ConnectorModuleEntitlement::where('connector_module_purchase_id', $purchase->id)->count());
```

- [ ] **Step 2: Run payment-channel tests and observe failure.** Run `php artisan test tests/Feature/Connectors/ConnectorModulePaymentWebhookTest.php tests/Feature/Connectors/ConnectorModulePaymentStatusTest.php tests/Feature/Connectors/ConnectorModulePaymentReceiptTest.php tests/Feature/Connectors/ConnectorModuleRefundBoundaryTest.php`; expect dispatch/receipt/refund failures.

- [ ] **Step 3: Add explicit payment-scope dispatch without changing subscription semantics.** Keep `isModulePurchase()` individual-only; add:

```php
public function isConnectorModulePurchase(): bool
{
    return (string) data_get($this->payment_details, 'payment_scope') === 'connector_module_purchase';
}

public function isModuleCommercePurchase(): bool
{
    return $this->isModulePurchase() || $this->isConnectorModulePurchase();
}
```

Inject `ConnectorModulePurchaseService` into webhook/payment controllers and branch by exact scope. Pending/status polling authorizes `payments.user_id`, retries Connector finalization even when provider-paid metadata exists, and redirects completed purchases to the Connector confirmation route. `PaymentObserver` uses `isModuleCommercePurchase()` to avoid subscription activation. Receipt/history data load Connector, organization purchase, entitlement, module, seller, seats, and terms. Admin payment stats add `connector_module_purchase`. `RefundService` begins with:

```php
if ($payment->isConnectorModulePurchase()) {
    throw new InvalidArgumentException('Connector module purchases are non-refundable and require an administrator correction.');
}
```

- [ ] **Step 4: Run Connector and existing subscription/individual payment tests.** Run `php artisan test tests/Feature/Connectors/ConnectorModulePaymentWebhookTest.php tests/Feature/Connectors/ConnectorModulePaymentStatusTest.php tests/Feature/Connectors/ConnectorModulePaymentReceiptTest.php tests/Feature/Connectors/ConnectorModuleRefundBoundaryTest.php tests/Feature/Learner/LearnerModulePaymentWebhookTest.php tests/Feature/Learner/LearnerPaymentHistoryModuleTransactionsTest.php tests/Feature/Payment/PaymongoSuccessActivationGuardTest.php tests/Feature/Payment/CheckoutRouteAudienceRedirectTest.php tests/Feature/Notifications/SubscriptionAndPaymentNotificationTest.php tests/Unit/Services/SubscriptionServiceCreatePaymentTest.php tests/Unit/Services/PayMongoPaymentLinkServiceTest.php`; expect pass.

- [ ] **Step 5: Commit.** Commit with `git commit -m "feat: route connector module payments"`.

### Task 9: Transaction-safe access rules, seat assignment, and revocation

**Files:**
- Create: `app/Services/Connectors/ConnectorModuleAccessService.php`
- Create: `app/Http/Requests/Connector/StoreConnectorModuleAccessRuleRequest.php`
- Create: `app/Http/Controllers/Connector/ModuleAccessController.php`
- Modify: `routes/connector.php`
- Modify: `app/Models/ConnectorModuleAccessRule.php`
- Modify: `app/Models/ConnectorModuleSeatAssignment.php`
- Test: `tests/Feature/Connectors/ConnectorModuleAccessRuleTest.php`
- Test: `tests/Feature/Connectors/ConnectorModuleSeatAssignmentTest.php`
- Test: `tests/Feature/Connectors/ConnectorModuleSeatConcurrencyTest.php`

**Interfaces:**

```php
ConnectorModuleAccessService::storeRule(ConnectorModuleEntitlement $entitlement, User $actor, array $data): ConnectorModuleAccessRule
ConnectorModuleAccessService::removeRule(ConnectorModuleAccessRule $rule, User $actor): void
ConnectorModuleAccessService::reconcileEntitlement(ConnectorModuleEntitlement $entitlement): void
ConnectorModuleAccessService::reconcileMember(ConnectorMembership $membership): void
ConnectorModuleAccessService::releaseMembership(ConnectorMembership $membership, string $reason = 'membership_removed'): void
ConnectorModuleAccessService::revoke(ConnectorModuleEntitlement $entitlement, User $learner, User $actor, string $reason): ConnectorModuleSeatAssignment
ConnectorModuleAccessService::clearRevocation(ConnectorModuleEntitlement $entitlement, User $learner, User $actor): ConnectorModuleSeatAssignment
```

- [ ] **Step 1: Write failing rule/seat tests.** Cover all-member, role, and member rule keys; manager authorization; cross-Connector denial; eligibility; guardian-pending reservation; existing independent access; duplicate rule; initial oversize rejection; future overflow; direct-over-inherited precedence; explicit revocation and clear; two Connectors assigning one learner; and a one-seat allocation invariant. For the sample below, create the rule while only the first eligible membership is active, then activate the second membership to exercise later overflow:

```php
$service->storeRule($entitlement, $manager, [
    'target_type' => ConnectorModuleAccessRule::TARGET_ALL_MEMBERS,
    'target_id' => null,
]);

$secondMembership->forceFill([
    'status' => 'active',
    'accepted_at' => now(),
])->save();
$service->reconcileMember($secondMembership);

$this->assertSame(1, ConnectorModuleSeatAssignment::query()
    ->where('connector_module_entitlement_id', $entitlement->id)
    ->where('consumes_seat', true)
    ->whereNull('released_at')
    ->count());
$this->assertDatabaseHas('connector_module_seat_assignments', [
    'connector_module_entitlement_id' => $entitlement->id,
    'user_id' => $secondEligibleMember->id,
    'status' => ConnectorModuleSeatAssignment::STATUS_INACTIVE,
    'inactive_reason' => 'capacity_unavailable',
    'consumes_seat' => false,
]);
```

For the lock assertion, enable the query log around a successful allocation and assert one SQL statement against `connector_module_entitlements` contains `for update`; then call a second assignment for the final seat and assert a validation error plus one unreleased seat-consuming row.

- [ ] **Step 2: Run assignment tests and observe failure.** Run `php artisan test tests/Feature/Connectors/ConnectorModuleAccessRuleTest.php tests/Feature/Connectors/ConnectorModuleSeatAssignmentTest.php tests/Feature/Connectors/ConnectorModuleSeatConcurrencyTest.php`; expect missing service/routes.

- [ ] **Step 3: Implement rules and deterministic reconciliation.** Validate target shape exactly:

```php
'target_type' => ['required', Rule::in(['all_members', 'role', 'member'])],
'target_id' => [
    Rule::requiredIf(fn () => in_array($this->string('target_type')->toString(), ['role', 'member'], true)),
    'nullable', 'integer',
],
```

Build rule keys as `all`, `role:{id}`, and `member:{id}` after proving the role/member belongs to the current Connector. Every mutation rechecks `connector.manage_modules` and entitlement ownership.

In `reconcileEntitlement()`, lock the entitlement, load active Connector memberships, evaluate profile/age/category/guardian eligibility through `ModuleAccessService`, collapse multiple rule matches per user, and sort candidates by:

```php
[$isDirect ? 0 : 1, $rule->created_at->getTimestamp(), $membership->accepted_at?->getTimestamp() ?? PHP_INT_MAX, $user->id]
```

Reject a newly created rule before commit when its current eligible matches cannot fit. For later overflow, retain the rule and materialize `STATUS_INACTIVE` with `inactive_reason=capacity_unavailable`. An existing active independent grant produces `STATUS_INDEPENDENT` with `consumes_seat=false`. Guardian-required matches produce `STATUS_PENDING_GUARDIAN`, consume a seat, create a pending Connector grant, and keep enrollment `pending_parent_approval`. Active assignment creates/activates its Connector grant and synchronizes the one enrollment. Revocation upserts a retained `STATUS_REVOKED` tombstone, releases the seat, deactivates its grant, and overrides all rules until clear.

- [ ] **Step 4: Run assignment, access-grant, and capacity tests.** Run `php artisan test tests/Feature/Connectors/ConnectorModuleAccessRuleTest.php tests/Feature/Connectors/ConnectorModuleSeatAssignmentTest.php tests/Feature/Connectors/ConnectorModuleSeatConcurrencyTest.php tests/Feature/Learner/ModuleAccessGrantLifecycleTest.php tests/Feature/Learner/SharedModuleCapacityTest.php`; expect pass.

- [ ] **Step 5: Commit.** Commit with `git commit -m "feat: allocate connector module seats"`.

### Task 10: Membership, role, guardian, and suspension reconciliation

**Files:**
- Modify: `app/Services/Connectors/ConnectorInvitationService.php`
- Modify: `app/Services/Connectors/ConnectorMembershipRequestService.php`
- Modify: `app/Http/Controllers/Connector/MemberController.php`
- Modify: `app/Http/Controllers/Connector/RegistrationController.php`
- Modify: `app/Http/Controllers/ParentController.php`
- Modify: `app/Services/Modules/ModuleAccessService.php`
- Modify: `app/Services/Connectors/ConnectorModuleAccessService.php`
- Test: `tests/Feature/Connectors/ConnectorModuleMembershipLifecycleTest.php`
- Test: `tests/Feature/Connectors/ConnectorModuleRoleReconciliationTest.php`
- Test: `tests/Feature/Connectors/ConnectorModuleSuspensionTest.php`
- Test: `tests/Feature/Connectors/ConnectorModuleGuardianApprovalTest.php`

**Interfaces:** Existing membership methods keep their signatures. After their current successful state change, they call `reconcileMember()` for activation/role changes or `releaseMembership()` for removal. `ModuleAccessService::assertCanLearn()` independently rechecks current user, Connector, membership, entitlement, and assignment state.

- [ ] **Step 1: Write failing lifecycle tests.** Cover invitation accept, membership-request approval, role change into/out of a rule, removal, owner withdrawal, rejoin with a new/current membership id, explicit revocation surviving rejoin, no-seat rejoin, Connector suspension/restoration, user suspension, tier downgrade, guardian approval/rejection, and preserved progress/certificate rows.

```php
$this->actingAs($manager)
    ->delete(route('connector.members.destroy', [$connector, $membership]))
    ->assertRedirect();

$this->assertDatabaseHas('connector_module_seat_assignments', [
    'user_id' => $learner->id,
    'status' => ConnectorModuleSeatAssignment::STATUS_INACTIVE,
    'inactive_reason' => 'membership_removed',
    'consumes_seat' => false,
]);
$this->assertDatabaseHas('module_enrollments', [
    'user_id' => $learner->id,
    'module_id' => $module->id,
    'status' => EnrollmentStatus::Inactive->value,
    'completion_percentage' => 55,
]);
$this->assertDatabaseHas('certificates', ['user_id' => $learner->id, 'module_id' => $module->id]);
```

- [ ] **Step 2: Run lifecycle tests and observe failure.** Run `php artisan test tests/Feature/Connectors/ConnectorModuleMembershipLifecycleTest.php tests/Feature/Connectors/ConnectorModuleRoleReconciliationTest.php tests/Feature/Connectors/ConnectorModuleSuspensionTest.php tests/Feature/Connectors/ConnectorModuleGuardianApprovalTest.php`; expect assignments/grants not to reconcile.

- [ ] **Step 3: Add explicit reconciliation at every membership mutation.** Call inside the existing membership transaction after the membership state is saved:

```php
$this->moduleAccess->reconcileMember($membership->fresh(['connector', 'role']));
```

On removal/withdrawal call `releaseMembership()` inside the same transaction as the membership status change so seat release and grant deactivation cannot diverge. In role update, save the new role then reconcile inherited rules in that transaction; direct/revoked rows remain authoritative. Parent approval activates pending Connector grants before enrollment synchronization; rejection deactivates them and releases seats. Queue only notifications after commit. `assertCanLearn()` denies `User::STATUS_SUSPENDED`, non-verified Connector, inactive membership, suspended/voided entitlement, released/revoked assignment, and non-visible module, without deleting any assignment or learning row. Tier downgrade affects new capacity only.

- [ ] **Step 4: Run lifecycle and existing Connector membership tests.** Run `php artisan test tests/Feature/Connectors/ConnectorModuleMembershipLifecycleTest.php tests/Feature/Connectors/ConnectorModuleRoleReconciliationTest.php tests/Feature/Connectors/ConnectorModuleSuspensionTest.php tests/Feature/Connectors/ConnectorModuleGuardianApprovalTest.php tests/Feature/Connectors/ConnectorInvitationTest.php tests/Feature/Connectors/ConnectorMembershipRequestTest.php tests/Feature/Connectors/ConnectorMemberDirectoryAccessTest.php tests/Feature/Connectors/ConnectorRoleManagementTest.php tests/Feature/Connectors/ConnectorWithdrawalTest.php tests/Feature/Connectors/ConnectorDashboardAccessTest.php`; expect pass.

- [ ] **Step 5: Commit.** Commit with `git commit -m "feat: reconcile module access with membership"`.

### Task 11: Organization learning library, access management, and purchase confirmation UI

**Files:**
- Create: `app/Http/Controllers/Connector/ModuleLibraryController.php`
- Create: `resources/views/connectors/modules/library/index.blade.php`
- Create: `resources/views/connectors/modules/library/show.blade.php`
- Create: `resources/views/connectors/modules/library/access.blade.php`
- Create: `resources/views/connectors/modules/purchase-confirmation.blade.php`
- Modify: `app/Http/Controllers/Connector/ModulePurchaseController.php`
- Modify: `app/Http/Controllers/Connector/ModuleAccessController.php`
- Modify: `routes/connector.php`
- Modify: `resources/views/connectors/modules/partials/tabs.blade.php`
- Test: `tests/Feature/Connectors/ConnectorModuleLibraryTest.php`
- Test: `tests/Feature/Connectors/ConnectorModuleAccessManagementUiTest.php`
- Test: `tests/Feature/Connectors/ConnectorModulePurchaseConfirmationTest.php`

**Interfaces:** `ModuleLibraryController::index()` lists purchased entitlements; `show()` presents immutable purchase data; `access()` calls reconciliation then presents rules and assignments. Every action checks entitlement/purchase `connector_id` against the route Connector.

- [ ] **Step 1: Write failing library/UI tests.** Verify Marketplace/Purchased Modules/Access Management tabs, immutable price/date/status, used/available seat counts, member source/status/reason, manager-only controls, cross-Connector 404/403, and confirmation copy/actions without automatic assignments.

```php
$this->actingAs($manager)
    ->get(route('connector.modules.purchase.confirmation', [$connector, $purchase]))
    ->assertOk()
    ->assertSee('successfully purchased')
    ->assertSee('organization learning library')
    ->assertSee('Configure Access')
    ->assertSee('View Learning Library');
```

- [ ] **Step 2: Run library/UI tests and observe failure.** Run `php artisan test tests/Feature/Connectors/ConnectorModuleLibraryTest.php tests/Feature/Connectors/ConnectorModuleAccessManagementUiTest.php tests/Feature/Connectors/ConnectorModulePurchaseConfirmationTest.php`; expect missing controllers/views/routes.

- [ ] **Step 3: Implement the library with derived seat counts.** Add static routes before marketplace wildcard routes:

```php
Route::get('/connector/{connector}/modules/library', [ModuleLibraryController::class, 'index'])
    ->name('connector.modules.library.index');
Route::get('/connector/{connector}/modules/library/{entitlement}', [ModuleLibraryController::class, 'show'])
    ->name('connector.modules.library.show');
Route::get('/connector/{connector}/modules/library/{entitlement}/access', [ModuleLibraryController::class, 'access'])
    ->name('connector.modules.library.access');
```

Add rule/revoke/clear routes below the access page. The listing/library pages use `abortUnlessWorkspace`; purchase and mutation controls use `abortUnlessPermission(..., 'connector.manage_modules')`. Derive used seats from unreleased `consumes_seat=true` assignments and available seats as `max(0, entitlement seat_capacity - used)`. Do not copy counts into entitlement columns. Confirmation reads the completed purchase and entitlement and never calls reconciliation or creates a rule.

- [ ] **Step 4: Run library and marketplace tests.** Run `php artisan test tests/Feature/Connectors/ConnectorModuleLibraryTest.php tests/Feature/Connectors/ConnectorModuleAccessManagementUiTest.php tests/Feature/Connectors/ConnectorModulePurchaseConfirmationTest.php tests/Feature/Connectors/ConnectorModuleMarketplaceBrowseTest.php`; expect pass.

- [ ] **Step 5: Commit.** Commit with `git commit -m "feat: add connector learning library"`.

### Task 12: Enforce shared access across the learner experience and preserve history

**Files:**
- Modify: `app/Http/Controllers/Learner/ModuleController.php`
- Modify: `app/Http/Controllers/Learner/LessonController.php`
- Modify: `app/Http/Controllers/Learner/QuizController.php`
- Modify: `app/Http/Controllers/Learner/InteractiveActivityController.php`
- Modify: `app/Http/Controllers/Learner/InteractiveCheckpointController.php`
- Modify: `app/Http/Controllers/Learner/TopicTranslationController.php`
- Modify: `app/Http/Controllers/Learner/ModuleReviewPageController.php`
- Modify: `app/Http/Controllers/Learner/ModuleFeedbackController.php`
- Modify: `app/Http/Controllers/Learner/CertificateController.php`
- Modify: `app/Services/Content/ContentAccessService.php`
- Modify: `app/Services/Chat/ChatAuthorizationService.php`
- Modify: `app/Services/LearnerModuleCompletionService.php`
- Modify: `resources/views/learner/modules/index.blade.php`
- Modify: `resources/views/learner/modules/show.blade.php`
- Test: `tests/Feature/Learner/ConnectorProvidedModuleAccessTest.php`
- Test: `tests/Feature/Learner/ConnectorModuleAccessEndpointMatrixTest.php`
- Test: `tests/Feature/Learner/ConnectorModuleProgressPreservationTest.php`
- Test: `tests/Feature/Learner/ConnectorModuleCertificateHistoryTest.php`

**Interfaces:** Every learning mutation calls `ModuleAccessService::assertCanLearn($user, $module)`. Module browse/details additionally use `hasHistoricalAccess()` and `activeConnectorSources()` for Past Access and attribution.

- [ ] **Step 1: Write failing end-to-end access tests.** Build one active Connector assignment and assert access to module, lesson, quiz start/submit, interactive activity/checkpoint, translation, protected content, feedback/review, completion, and certificate generation. Then revoke/remove membership and assert all new learning mutations fail while progress, attempts, completion, and issued certificate view/download remain.

```php
$this->actingAs($learner)->get(route('learner.lessons.show', $lesson))->assertOk();
$this->actingAs($learner)->get(route('quizzes.start', $quiz))->assertOk();

$access->revoke($entitlement, $learner, $manager, 'Access removed by Connector administrator.');

$this->actingAs($learner)->get(route('learner.lessons.show', $lesson))->assertForbidden();
$this->actingAs($learner)->post(route('quizzes.submit', $quiz), ['answers' => []])->assertForbidden();
$this->actingAs($learner)->get(route('learner.certificates.show', $certificate))->assertOk();
$this->actingAs($learner)->get(route('learner.certificates.download', $certificate))->assertOk();
$this->assertDatabaseHas('user_progress', ['user_id' => $learner->id, 'module_id' => $module->id]);
```

- [ ] **Step 2: Run learner access tests and observe the existing inconsistent checks.** Run `php artisan test tests/Feature/Learner/ConnectorProvidedModuleAccessTest.php tests/Feature/Learner/ConnectorModuleAccessEndpointMatrixTest.php tests/Feature/Learner/ConnectorModuleProgressPreservationTest.php tests/Feature/Learner/ConnectorModuleCertificateHistoryTest.php`; expect quiz's any-enrollment check and other enrollment-only endpoints to demonstrate failures.

- [ ] **Step 3: Replace endpoint-local enrollment checks with the shared service.** Resolve a quiz's module through `quiz.module` or `quiz.lesson.module`, then call `assertCanLearn()` before attempts or mutations. Apply the same pattern to every listed endpoint and to protected attachments/content. Keep ownership/admin authoring policies unchanged.

In module index, group approved active-grant modules under current learning and inactive/historical grant modules under Past Access. In details, show each active Connector source as `Provided by {Connector}`. Historical view loads safe metadata, progress, and issued certificates but hides Start/Continue, lesson links, quizzes, and progress mutation controls.

Certificate `check()` requires active access and completion. Existing certificate `show()` and `download()` require only certificate ownership; remove the current revalidation that redirects a former learner away from their issued certificate. `LearnerModuleCompletionService` still evaluates existing progress/enrollment but is entered only after shared access authorization for a new completion/certificate action.

- [ ] **Step 4: Run the new matrix and existing learner regressions.** Run `php artisan test tests/Feature/Learner/ConnectorProvidedModuleAccessTest.php tests/Feature/Learner/ConnectorModuleAccessEndpointMatrixTest.php tests/Feature/Learner/ConnectorModuleProgressPreservationTest.php tests/Feature/Learner/ConnectorModuleCertificateHistoryTest.php tests/Feature/Learner/LessonPageTest.php tests/Feature/Learner/LearnerFinalQuizCompletionFlowTest.php tests/Feature/Learner/InteractiveActivityProgressTest.php tests/Feature/Learner/InteractiveCheckpointProgressIsolationTest.php tests/Feature/Learner/LearnerCertificateDownloadFreeAccessTest.php tests/Feature/Learner/ModuleFeedbackFlowTest.php`; expect pass.

- [ ] **Step 5: Commit.** Commit with `git commit -m "feat: authorize connector-provided learning"`.

### Task 13: Notifications, finance visibility, audit, and exceptional corrections

**Files:**
- Create: `app/Notifications/Connectors/ConnectorModulePurchaseNotification.php`
- Create: `app/Notifications/Connectors/ConnectorModuleAccessNotification.php`
- Create: `app/Notifications/Instructor/ModuleMarketplaceStatusNotification.php`
- Create: `app/Http/Requests/Admin/CorrectConnectorModulePurchaseRequest.php`
- Create: `app/Http/Controllers/Admin/ConnectorModulePurchaseCorrectionController.php`
- Modify: `app/Services/Connectors/ConnectorModulePurchaseService.php`
- Modify: `app/Services/Connectors/ConnectorModuleAccessService.php`
- Modify: `app/Services/Modules/ModuleMarketplaceListingService.php`
- Modify: `app/Services/AdminActivityLogService.php`
- Modify: `app/Http/Controllers/Admin/ModuleRevenueController.php`
- Modify: `app/Http/Controllers/Admin/PaymentAdminController.php`
- Modify: `app/Http/Controllers/Instructor/ModuleEarningsController.php`
- Modify: `routes/admin.php`
- Modify: `resources/views/admin/monetization/module-revenue.blade.php`
- Modify: `resources/views/admin/monetization/module-revenue-transaction-show.blade.php`
- Modify: `resources/views/instructor/modules/show.blade.php`
- Modify: `resources/views/instructor/payments/history.blade.php`
- Test: `tests/Feature/Connectors/ConnectorModuleNotificationTest.php`
- Test: `tests/Feature/Admin/ConnectorModulePurchaseCorrectionTest.php`
- Test: `tests/Feature/Admin/ConnectorModuleRevenueVisibilityTest.php`
- Test: `tests/Feature/Instructor/InstructorConnectorModuleSalesTest.php`

**Interfaces:** Correction actions are `void`, `restore`, and `retry_finalization`. They require an admin, a non-empty reason, and an organization purchase. No action calls `RefundService`.

- [ ] **Step 1: Write failing notification/reporting/correction tests.** Assert purchase completion/attention notification, learner grant/revoke notification, seller notification on admin suspension, `DB::afterCommit` behavior, channel/Connector/seats in finance and earnings pages, append-only correction activity, progress preservation, and hard-delete denial for organization-channel payment/ledger.

```php
$this->actingAs($admin)->post(
    route('admin.monetization.connector-module-purchases.correct', $purchase),
    ['action' => 'void', 'reason' => 'Duplicate provider charge under manual review.'],
)->assertRedirect();

$this->assertDatabaseHas('connector_module_entitlements', [
    'id' => $entitlement->id,
    'status' => ConnectorModuleEntitlement::STATUS_VOIDED,
]);
$this->assertDatabaseHas('admin_activity_logs', [
    'action' => 'connector_module_purchases.void',
    'entity_id' => $purchase->id,
]);
$this->assertDatabaseHas('user_progress', ['user_id' => $learner->id, 'module_id' => $module->id]);
```

- [ ] **Step 2: Run reporting/correction tests and observe failure.** Run `php artisan test tests/Feature/Connectors/ConnectorModuleNotificationTest.php tests/Feature/Admin/ConnectorModulePurchaseCorrectionTest.php tests/Feature/Admin/ConnectorModuleRevenueVisibilityTest.php tests/Feature/Instructor/InstructorConnectorModuleSalesTest.php`; expect missing notification/controller/reporting behavior.

- [ ] **Step 3: Implement post-commit notifications and append-only corrections.** Purchase completion notifies the acting buyer and active Connector members holding `connector.manage_modules`; attention-required also notifies platform admins. Assignment activation/revocation notifies the learner once per real status transition. Admin listing suspension notifies an instructor seller. Schedule each with `DB::afterCommit()`.

Add the correction route under `admin.monetization`. `void` marks entitlement voided, releases/deactivates Connector assignments/grants, and preserves all history. `restore` requires capacity and changes the entitlement to corrected/usable before reconciliation. `retry_finalization` reruns the idempotent paid-purchase finalizer and succeeds only when capacity and module safety permit. Log before/after status, reason, actor, Connector, module, payment, and ledger identifiers with `AdminActivityLogService`.

Extend finance/earnings views with `sale_channel`, Connector, acting buyer, owner, seats, gross, rate, commission, and seller net. Block `destroy()` for `sale_channel=connector_marketplace` ledger entries and `connector_module_purchase` payments; archive remains a display concern and must not alter purchase/entitlement. Do not add an automated refund button.

- [ ] **Step 4: Run notification/reporting and existing finance tests.** Run `php artisan test tests/Feature/Connectors/ConnectorModuleNotificationTest.php tests/Feature/Admin/ConnectorModulePurchaseCorrectionTest.php tests/Feature/Admin/ConnectorModuleRevenueVisibilityTest.php tests/Feature/Instructor/InstructorConnectorModuleSalesTest.php tests/Feature/Admin/AdminModuleRevenueDashboardTest.php tests/Feature/Admin/AdminModuleRevenueTransactionDetailsPageTest.php tests/Feature/Admin/AdminModuleRevenueInstructorRollupViewTest.php tests/Feature/Admin/AdminCommissionPolicyAuditLogTest.php tests/Feature/Instructor/InstructorModuleEarningsTest.php`; expect pass.

- [ ] **Step 5: Commit.** Commit with `git commit -m "feat: audit connector module commerce"`.

### Task 14: Protect existing progress across approved module revisions

**Files:**
- Create: `app/Services/Content/ModuleRevisionCompatibilityService.php`
- Modify: `app/Services/ContentGovernanceService.php`
- Test: `tests/Feature/Admin/ConnectorModuleRevisionCompatibilityTest.php`
- Test: `tests/Feature/Learner/ConnectorModuleRevisionDeliveryTest.php`

**Interfaces:** `ModuleRevisionCompatibilityService::assertCompatible(Module $module, ModuleRevision $candidate): void`. It returns normally for additions/edits and throws `ValidationException` when a removed identifier has persisted learner progress.

- [ ] **Step 1: Write failing compatibility tests.** Create a published revision and candidate revision snapshots. Cover added lessons/topics/quizzes, edited titles/content, removal with no progress, and removal of a lesson/topic/quiz referenced by `user_progress`, `lesson_topic_progress`, interactive progress, or `quiz_attempts`. Assert entitlement remains attached to the module and a compatible approved revision appears without repurchase.

```php
$this->expectException(ValidationException::class);
$compatibility->assertCompatible($module, $candidateWithoutCompletedLesson);

$governance->approveReview($compatibleReview, $admin);
$this->assertSame($compatibleRevision->id, $module->fresh()->published_revision_id);
$this->assertDatabaseHas('connector_module_entitlements', [
    'id' => $entitlement->id,
    'module_id' => $module->id,
]);
```

- [ ] **Step 2: Run revision tests and observe failure.** Run `php artisan test tests/Feature/Admin/ConnectorModuleRevisionCompatibilityTest.php tests/Feature/Learner/ConnectorModuleRevisionDeliveryTest.php`; expect destructive approval currently to pass.

- [ ] **Step 3: Compare stable identifiers before approval.** Read lesson/topic/quiz/activity ids from the current published revision snapshot and candidate snapshot. For ids absent from the candidate, query their corresponding progress/attempt tables. Throw a field-level validation error listing the content type/count when any removed id has progress. Call the service in `ContentGovernanceService::approveReview()` after loading module/revision but before marking either approved. Do not clone the module or pin entitlements to old revisions.

- [ ] **Step 4: Run revision, governance, and progress tests.** Run `php artisan test tests/Feature/Admin/ConnectorModuleRevisionCompatibilityTest.php tests/Feature/Learner/ConnectorModuleRevisionDeliveryTest.php tests/Feature/Instructor/InstructorModuleReviewSubmissionTest.php tests/Feature/Admin/InstructorModuleReviewDecisionNotificationTest.php tests/Feature/Learner/LearningPathChangingContentTest.php`; expect compatible updates and existing governance to pass.

- [ ] **Step 5: Commit.** Commit with `git commit -m "feat: guard module progress compatibility"`.

### Task 15: Full lifecycle QA, migration safety, and operational documentation

**Files:**
- Create: `docs/connector-module-marketplace.md`
- Test: `tests/Feature/Connectors/ConnectorModuleMarketplaceLifecycleTest.php`
- Modify: focused fixtures/tests only when a legitimate regression uncovered by this task requires correction

**Interfaces:** The operations document explains listing states, purchase finalization states, manual correction actions, seat calculation, troubleshooting, and confirms that Connector subscriptions are unrelated.

- [ ] **Step 1: Add one full lifecycle feature test.** Create `tests/Feature/Connectors/ConnectorModuleMarketplaceLifecycleTest.php` that performs instructor listing, ordinary member browse, manager checkout, payment completion, entitlement confirmation, member assignment, learner lesson/progress/completion/certificate, revocation, preserved history, seat reassignment, and listing unpublish. Reuse `ConnectorTestHelpers`; fake only the provider boundary and notifications.

- [ ] **Step 2: Run the complete targeted feature set.** Run:

```bash
php artisan test tests/Feature/Connectors/ConnectorModuleMarketplaceSchemaTest.php \
  tests/Feature/Learner/ModuleAccessGrantLifecycleTest.php \
  tests/Unit/Services/Modules/ModuleCapacityServiceTest.php \
  tests/Feature/Learner/SharedModuleCapacityTest.php \
  tests/Feature/Instructor/InstructorConnectorMarketplaceListingTest.php \
  tests/Feature/Admin/AdminConnectorMarketplaceListingTest.php \
  tests/Feature/Connectors/ConnectorModuleMarketplaceBrowseTest.php \
  tests/Feature/Learner/ModuleDetailsSharedPresentationTest.php \
  tests/Feature/Connectors/ConnectorModuleCheckoutTest.php \
  tests/Feature/Connectors/ConnectorModulePurchaseCompletionTest.php \
  tests/Feature/Connectors/ConnectorModuleCommissionTest.php \
  tests/Feature/Connectors/ConnectorModulePurchaseIdempotencyTest.php \
  tests/Feature/Connectors/ConnectorModulePaymentWebhookTest.php \
  tests/Feature/Connectors/ConnectorModulePaymentStatusTest.php \
  tests/Feature/Connectors/ConnectorModulePaymentReceiptTest.php \
  tests/Feature/Connectors/ConnectorModuleRefundBoundaryTest.php \
  tests/Feature/Connectors/ConnectorModuleAccessRuleTest.php \
  tests/Feature/Connectors/ConnectorModuleSeatAssignmentTest.php \
  tests/Feature/Connectors/ConnectorModuleSeatConcurrencyTest.php \
  tests/Feature/Connectors/ConnectorModuleMembershipLifecycleTest.php \
  tests/Feature/Connectors/ConnectorModuleRoleReconciliationTest.php \
  tests/Feature/Connectors/ConnectorModuleSuspensionTest.php \
  tests/Feature/Connectors/ConnectorModuleGuardianApprovalTest.php \
  tests/Feature/Connectors/ConnectorModuleLibraryTest.php \
  tests/Feature/Connectors/ConnectorModuleAccessManagementUiTest.php \
  tests/Feature/Connectors/ConnectorModulePurchaseConfirmationTest.php \
  tests/Feature/Learner/ConnectorProvidedModuleAccessTest.php \
  tests/Feature/Learner/ConnectorModuleAccessEndpointMatrixTest.php \
  tests/Feature/Learner/ConnectorModuleProgressPreservationTest.php \
  tests/Feature/Learner/ConnectorModuleCertificateHistoryTest.php \
  tests/Feature/Connectors/ConnectorModuleNotificationTest.php \
  tests/Feature/Admin/ConnectorModulePurchaseCorrectionTest.php \
  tests/Feature/Admin/ConnectorModuleRevenueVisibilityTest.php \
  tests/Feature/Instructor/InstructorConnectorModuleSalesTest.php \
  tests/Feature/Admin/ConnectorModuleRevisionCompatibilityTest.php \
  tests/Feature/Learner/ConnectorModuleRevisionDeliveryTest.php \
  tests/Feature/Connectors/ConnectorModuleMarketplaceLifecycleTest.php
```

Expect all pass on `cc_db_test`. On PowerShell, place the paths on one line or use PowerShell backticks instead of Bash continuations.

- [ ] **Step 3: Run regression, formatting, route, and asset checks.** Run these exact commands, still without any destructive development-database command:

```bash
php artisan migrate --pretend
php artisan migrate
php artisan migrate:status
php artisan test tests/Feature/Learner/LearnerPaidModulePurchaseFlowTest.php
php artisan test tests/Feature/Learner/LearnerEnrollmentCapByInstructorPlanTest.php
php artisan test tests/Feature/Learner/LearnerFinalQuizCompletionFlowTest.php
php artisan test tests/Feature/Learner/LearnerCertificateDownloadFreeAccessTest.php
php artisan test tests/Feature/Instructor/InstructorModulePlanEnforcementTest.php
php artisan test tests/Feature/Admin/AdminCommissionSettingsTest.php
php artisan test tests/Feature/Connectors/ConnectorInvitationTest.php tests/Feature/Connectors/ConnectorDashboardAccessTest.php
php artisan test --testsuite=Unit
vendor/bin/pint --test
php artisan route:list --path=connector --except-vendor
npm run build
git diff --check
```

Before the real `php artisan migrate`, confirm the configured target is the intended local development database and inspect the pretend SQL for the absence of `DROP`, `TRUNCATE`, or data-deleting statements. Expected results: all forward migrations apply while preserving current rows, tests pass, Pint reports no changes required, route names are unique and show auth/verified middleware, Vite build succeeds, and diff check is empty. If local PayMongo credentials prevent service construction in tests, bind the existing provider fake in the affected test rather than changing production validation.

- [ ] **Step 4: Write the operations document and perform manual browser QA.** Document:

```text
Publish: module must be approved/published; organization price and fixed seats are independent.
Checkout: 30-minute capacity hold; payment attempts remain historical.
Completion: payment + purchase + entitlement + ledger are idempotent; no members are auto-assigned.
Seats: used means unreleased seat-consuming assignment; membership removal/revocation releases it.
Access: active grant + approved enrollment + membership/entitlement/eligibility; progress is never deleted.
Corrections: void, restore, retry finalization; no automated Connector refund.
Subscriptions: no Connector subscription is read or required.
```

In a signed-in browser, verify instructor publish/unpublish, ordinary-member browse, manager purchase/confirmation/library, all/role/member assignment, revocation/reassignment, learner attribution/learning/certificate, admin finance/correction, and mobile/keyboard usability. Record any failing route or console/server error in the implementation report and fix it before claiming completion.

- [ ] **Step 5: Commit the lifecycle test and operations documentation.** Commit with `git commit -m "test: verify connector module marketplace lifecycle"`.

---

## Execution handoff

Implement this plan task-by-task. The recommended execution mode is `superpowers:subagent-driven-development`, with a fresh implementation worker and review gate for each task. If implementation must remain inline in one session, use `superpowers:executing-plans` and stop for review after Tasks 4, 8, 11, and 15.
