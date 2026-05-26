# Connectors Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build authenticated learner-side Connector registration, admin verification, connector memberships, scoped roles/permissions, subscription entitlement checks, and a connector dashboard foundation.

**Architecture:** Replace the old single-user `Organization` concept with a fresh `Connector` domain. Platform roles remain Spatie/global roles, while connector roles and permissions are stored in connector-specific tables and evaluated with connector-aware services. Connector feature access requires verified connector status, active membership, connector permission, and connector subscription entitlement.

**Tech Stack:** Laravel, Blade, Tailwind CSS, Eloquent, Form Requests, Feature/Unit tests, existing PSGC tables, existing subscription plan/entitlement models.

---

## File Structure

Create:

- `app/Models/Connector.php`
- `app/Models/ConnectorMembership.php`
- `app/Models/ConnectorRole.php`
- `app/Models/ConnectorRolePermission.php`
- `app/Models/ConnectorInvitation.php`
- `app/Models/ConnectorReview.php`
- `app/Http/Controllers/Connector/RegistrationController.php`
- `app/Http/Controllers/Connector/DashboardController.php`
- `app/Http/Controllers/Connector/MemberController.php`
- `app/Http/Controllers/Connector/InvitationController.php`
- `app/Http/Controllers/Connector/RoleController.php`
- `app/Http/Controllers/Connector/SubscriptionController.php`
- `app/Http/Controllers/Admin/ConnectorController.php`
- `app/Http/Requests/Connector/StoreConnectorRequest.php`
- `app/Http/Requests/Connector/InviteConnectorMemberRequest.php`
- `app/Http/Requests/Connector/StoreConnectorRoleRequest.php`
- `app/Http/Requests/Connector/UpdateConnectorRoleRequest.php`
- `app/Http/Requests/Admin/ApproveConnectorRequest.php`
- `app/Http/Requests/Admin/RejectConnectorRequest.php`
- `app/Http/Requests/Admin/SuspendConnectorRequest.php`
- `app/Services/Connectors/ConnectorAccessService.php`
- `app/Services/Connectors/ConnectorRegistrationService.php`
- `app/Services/Connectors/ConnectorRoleService.php`
- `app/Services/Connectors/ConnectorInvitationService.php`
- `app/Services/Connectors/ConnectorEntitlementService.php`
- `config/connector_permissions.php`
- `routes/connector.php`
- connector migrations under `database/migrations`
- connector Blade views under `resources/views/connectors`
- admin connector Blade views under `resources/views/admin/connectors`
- tests under `tests/Feature/Connectors` and `tests/Unit/Services/Connectors`

Modify:

- `bootstrap/app.php` or route registration location used by this Laravel version to load `routes/connector.php`
- `app/Models/User.php`
- subscription migration/model/service paths needed to support connector-owned subscriptions
- admin navigation/layout partials where admin connector moderation should appear
- learner dashboard/profile entry point to show connector registration
- old organization model/routes/views references

---

### Task 1: Remove Old Organization Boundary And Add Connector Tables

**Files:**
- Create: `database/migrations/YYYY_MM_DD_HHMMSS_create_connectors_table.php`
- Create: `database/migrations/YYYY_MM_DD_HHMMSS_create_connector_roles_table.php`
- Create: `database/migrations/YYYY_MM_DD_HHMMSS_create_connector_role_permissions_table.php`
- Create: `database/migrations/YYYY_MM_DD_HHMMSS_create_connector_memberships_table.php`
- Create: `database/migrations/YYYY_MM_DD_HHMMSS_create_connector_invitations_table.php`
- Create: `database/migrations/YYYY_MM_DD_HHMMSS_create_connector_reviews_table.php`
- Modify: `app/Models/User.php`
- Delete or stop using: `app/Models/Organization.php`

- [ ] **Step 1: Write failing migration/model relationship tests**

Create `tests/Feature/Connectors/ConnectorSchemaTest.php` with tests that assert connector creation, category/status fields, nullable organization email, owner membership relation, role permissions, invitations, and reviews persist correctly.

- [ ] **Step 2: Run the schema test and verify it fails**

Run:

```bash
php artisan test tests/Feature/Connectors/ConnectorSchemaTest.php
```

Expected: failure because connector tables/models do not exist.

- [ ] **Step 3: Add migrations**

Add migrations for:

- `connectors`
- `connector_roles`
- `connector_role_permissions`
- `connector_memberships`
- `connector_invitations`
- `connector_reviews`

Use enum/string status columns with validation in application code. Add indexes for connector status, connector category, `created_by`, `primary_representative_user_id`, organization email, membership user, and invitation email/status.

- [ ] **Step 4: Add models and relationships**

Add connector models with fillable fields, casts, and relationships. Add these `User` relationships:

```php
public function connectorMemberships()
{
    return $this->hasMany(ConnectorMembership::class);
}

public function ownedConnectors()
{
    return $this->hasMany(Connector::class, 'created_by');
}
```

Remove or avoid using `User::organization()`.

- [ ] **Step 5: Run schema test and verify it passes**

Run:

```bash
php artisan test tests/Feature/Connectors/ConnectorSchemaTest.php
```

Expected: PASS.

---

### Task 2: Add Connector Permission Catalog And Access Services

**Files:**
- Create: `config/connector_permissions.php`
- Create: `app/Services/Connectors/ConnectorAccessService.php`
- Create: `app/Services/Connectors/ConnectorRoleService.php`
- Test: `tests/Unit/Services/Connectors/ConnectorAccessServiceTest.php`
- Test: `tests/Unit/Services/Connectors/ConnectorRoleServiceTest.php`

- [ ] **Step 1: Write failing permission catalog tests**

Tests should assert:

- known keys like `connector.manage_members` exist
- unknown keys are rejected
- platform/admin permission names are not allowed
- a pending connector denies workspace access
- a verified connector with active membership allows workspace access
- last active Owner cannot be removed or downgraded

- [ ] **Step 2: Run tests and verify failure**

Run:

```bash
php artisan test tests/Unit/Services/Connectors
```

Expected: failure because services do not exist.

- [ ] **Step 3: Implement config catalog**

Create `config/connector_permissions.php` grouped by profile, members, roles, seminars, modules, educators, and subscription. Each permission should have a stable key and human label.

- [ ] **Step 4: Implement `ConnectorAccessService`**

Responsibilities:

- resolve active membership for user and connector
- require connector `verified` status for workspace pages
- check connector role permission
- return explicit boolean methods such as `canAccessWorkspace`, `hasPermission`

- [ ] **Step 5: Implement `ConnectorRoleService`**

Responsibilities:

- create default Owner role
- validate permission keys against config
- prevent Owner role deletion
- prevent last-owner removal/downgrade
- create and update connector roles

- [ ] **Step 6: Run unit tests and verify pass**

Run:

```bash
php artisan test tests/Unit/Services/Connectors
```

Expected: PASS.

---

### Task 3: Implement Authenticated Learner Connector Registration

**Files:**
- Create: `app/Http/Controllers/Connector/RegistrationController.php`
- Create: `app/Http/Requests/Connector/StoreConnectorRequest.php`
- Create: `app/Services/Connectors/ConnectorRegistrationService.php`
- Create: `resources/views/connectors/register.blade.php`
- Create: `resources/views/connectors/status.blade.php`
- Modify: route registration to load `routes/connector.php`
- Create: `routes/connector.php`
- Test: `tests/Feature/Connectors/ConnectorRegistrationTest.php`

- [ ] **Step 1: Write failing registration tests**

Tests should assert:

- guests are redirected to login
- unverified users cannot register if verified middleware is used
- required fields are enforced
- `organization_email` is optional
- provided `organization_email` must be unique
- proof document is not required
- registration creates one connector, one Owner role, one pending owner membership
- no duplicate user account is created
- successful registration redirects to status page

- [ ] **Step 2: Run registration tests and verify failure**

Run:

```bash
php artisan test tests/Feature/Connectors/ConnectorRegistrationTest.php
```

Expected: failure because routes/controllers do not exist.

- [ ] **Step 3: Implement request validation**

Validate:

- `name`: required string max 255
- `category`: required in configured connector categories
- `organization_email`: nullable email unique connectors
- `contact_number`: required string max 30
- `city_code`: required Cavite PSGC city
- `barangay_code`: required barangay under selected city
- `address_line`: required string max 500
- optional description, website URL, verification notes

- [ ] **Step 4: Implement registration service**

Inside a transaction:

- create connector with status `pending`
- create protected Owner role
- attach full connector permissions to Owner
- create pending membership for authenticated user
- create initial connector review/status record

- [ ] **Step 5: Implement controller and views**

Registration view uses existing Blade/Tailwind form patterns. Status view shows pending, rejected, verified, or suspended state.

- [ ] **Step 6: Register routes**

Create authenticated, verified routes:

```php
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/connectors/register', [RegistrationController::class, 'create'])->name('connectors.register');
    Route::post('/connectors/register', [RegistrationController::class, 'store'])->name('connectors.store');
    Route::get('/connector/{connector}/status', [RegistrationController::class, 'status'])->name('connector.status');
});
```

- [ ] **Step 7: Run registration tests and verify pass**

Run:

```bash
php artisan test tests/Feature/Connectors/ConnectorRegistrationTest.php
```

Expected: PASS.

---

### Task 4: Implement Admin Connector Moderation

**Files:**
- Create: `app/Http/Controllers/Admin/ConnectorController.php`
- Create: `app/Http/Requests/Admin/ApproveConnectorRequest.php`
- Create: `app/Http/Requests/Admin/RejectConnectorRequest.php`
- Create: `app/Http/Requests/Admin/SuspendConnectorRequest.php`
- Create: `resources/views/admin/connectors/index.blade.php`
- Create: `resources/views/admin/connectors/show.blade.php`
- Modify: `routes/admin.php`
- Test: `tests/Feature/Connectors/AdminConnectorModerationTest.php`

- [ ] **Step 1: Write failing admin moderation tests**

Tests should assert:

- non-admin users cannot access admin connector pages
- admin can list pending connectors
- admin can approve a pending connector
- approval activates owner membership
- admin can reject with required reason
- rejected connector status page shows reason
- admin can suspend verified connector with reason

- [ ] **Step 2: Run tests and verify failure**

Run:

```bash
php artisan test tests/Feature/Connectors/AdminConnectorModerationTest.php
```

Expected: failure because admin connector routes do not exist.

- [ ] **Step 3: Implement admin requests**

Use existing admin permissions convention, likely `access admin panel` plus a specific permission if the app has one for moderation. Rejection and suspension require a reason.

- [ ] **Step 4: Implement controller**

Methods:

- `index`
- `show`
- `approve`
- `reject`
- `suspend`

Write review rows to `connector_reviews` for every status decision.

- [ ] **Step 5: Implement admin views**

Follow instructor application review UI patterns with status tabs, search, connector detail panel, and inline approve/reject/suspend forms.

- [ ] **Step 6: Add routes**

Under admin middleware:

```php
Route::prefix('connectors')->name('connectors.')->group(function () {
    Route::get('/', [Admin\ConnectorController::class, 'index'])->name('index');
    Route::get('/{connector}', [Admin\ConnectorController::class, 'show'])->name('show');
    Route::post('/{connector}/approve', [Admin\ConnectorController::class, 'approve'])->name('approve');
    Route::post('/{connector}/reject', [Admin\ConnectorController::class, 'reject'])->name('reject');
    Route::post('/{connector}/suspend', [Admin\ConnectorController::class, 'suspend'])->name('suspend');
});
```

- [ ] **Step 7: Run admin tests and verify pass**

Run:

```bash
php artisan test tests/Feature/Connectors/AdminConnectorModerationTest.php
```

Expected: PASS.

---

### Task 5: Build Connector Dashboard Layout And Status-Gated Workspace

**Files:**
- Create: `resources/views/layouts/connector-app.blade.php`
- Create: `app/Http/Controllers/Connector/DashboardController.php`
- Create: `resources/views/connectors/dashboard.blade.php`
- Create: `resources/views/connectors/stubs/seminars.blade.php`
- Create: `resources/views/connectors/stubs/modules.blade.php`
- Create: `resources/views/connectors/stubs/educators.blade.php`
- Modify: `routes/connector.php`
- Test: `tests/Feature/Connectors/ConnectorDashboardAccessTest.php`

- [ ] **Step 1: Write failing dashboard access tests**

Tests should assert:

- pending connector redirects to status page
- rejected connector redirects to status page
- suspended connector redirects to status page
- verified connector with active membership can access dashboard
- user without membership gets 403

- [ ] **Step 2: Run tests and verify failure**

Run:

```bash
php artisan test tests/Feature/Connectors/ConnectorDashboardAccessTest.php
```

Expected: failure because dashboard routes/views do not exist.

- [ ] **Step 3: Implement dashboard controller**

Load connector, active membership, role, pending invitation count, member count, plan summary, and entitlement summary.

- [ ] **Step 4: Implement connector layout**

Use admin-inspired sidebar with:

- Dashboard
- Members
- Roles & Permissions
- Seminars
- Modules
- Educators
- Subscription

- [ ] **Step 5: Implement dashboard and stubs**

Dashboard uses compact overview cards. Stub pages use clean empty states and do not present unavailable features as active.

- [ ] **Step 6: Add routes**

Add connector workspace routes under `auth` and `verified`, with service checks in controllers or middleware:

```php
Route::get('/connector/{connector}/dashboard', [DashboardController::class, 'index'])->name('connector.dashboard');
Route::get('/connector/{connector}/seminars', [DashboardController::class, 'seminars'])->name('connector.seminars');
Route::get('/connector/{connector}/modules', [DashboardController::class, 'modules'])->name('connector.modules');
Route::get('/connector/{connector}/educators', [DashboardController::class, 'educators'])->name('connector.educators');
```

- [ ] **Step 7: Run dashboard tests and verify pass**

Run:

```bash
php artisan test tests/Feature/Connectors/ConnectorDashboardAccessTest.php
```

Expected: PASS.

---

### Task 6: Implement Connector Members And Invitations

**Files:**
- Create: `app/Http/Controllers/Connector/MemberController.php`
- Create: `app/Http/Controllers/Connector/InvitationController.php`
- Create: `app/Http/Requests/Connector/InviteConnectorMemberRequest.php`
- Create: `app/Services/Connectors/ConnectorInvitationService.php`
- Create: `resources/views/connectors/members/index.blade.php`
- Modify: `routes/connector.php`
- Test: `tests/Feature/Connectors/ConnectorInvitationTest.php`

- [ ] **Step 1: Write failing invitation tests**

Tests should assert:

- member without `connector.invite_members` cannot invite
- authorized owner can search/invite existing user by email
- invitation starts as pending
- invited user can accept
- invited user can reject
- accepted invitation creates active membership
- removing the last owner is blocked

- [ ] **Step 2: Run tests and verify failure**

Run:

```bash
php artisan test tests/Feature/Connectors/ConnectorInvitationTest.php
```

Expected: failure because invitation flow does not exist.

- [ ] **Step 3: Implement invite request and service**

Validate user email exists, connector role belongs to connector, invite target is not already active member, and actor has invite permission.

- [ ] **Step 4: Implement controllers**

Methods:

- members index
- send invitation
- accept invitation
- reject invitation
- remove member
- update member role

- [ ] **Step 5: Implement members view**

Use table layout with invite form, pending invite rows, member rows, role select, and guarded remove actions.

- [ ] **Step 6: Add routes**

Add:

```php
Route::get('/connector/{connector}/members', [MemberController::class, 'index'])->name('connector.members.index');
Route::post('/connector/{connector}/invitations', [InvitationController::class, 'store'])->name('connector.invitations.store');
Route::post('/connector/{connector}/invitations/{invitation}/accept', [InvitationController::class, 'accept'])->name('connector.invitations.accept');
Route::post('/connector/{connector}/invitations/{invitation}/reject', [InvitationController::class, 'reject'])->name('connector.invitations.reject');
```

- [ ] **Step 7: Run invitation tests and verify pass**

Run:

```bash
php artisan test tests/Feature/Connectors/ConnectorInvitationTest.php
```

Expected: PASS.

---

### Task 7: Implement Roles And Permissions UI

**Files:**
- Create: `app/Http/Controllers/Connector/RoleController.php`
- Create: `app/Http/Requests/Connector/StoreConnectorRoleRequest.php`
- Create: `app/Http/Requests/Connector/UpdateConnectorRoleRequest.php`
- Create: `resources/views/connectors/roles/index.blade.php`
- Modify: `routes/connector.php`
- Test: `tests/Feature/Connectors/ConnectorRoleManagementTest.php`

- [ ] **Step 1: Write failing role management tests**

Tests should assert:

- only users with `connector.manage_roles` can manage roles
- owner can create custom role
- role permissions must exist in config catalog
- owner role cannot be deleted
- last owner cannot be downgraded
- connector role permissions do not grant platform permissions

- [ ] **Step 2: Run tests and verify failure**

Run:

```bash
php artisan test tests/Feature/Connectors/ConnectorRoleManagementTest.php
```

Expected: failure because role UI/actions do not exist.

- [ ] **Step 3: Implement requests**

Validate role name, role description, and permission keys. Reject unknown keys and platform permission names.

- [ ] **Step 4: Implement controller actions**

Methods:

- `index`
- `store`
- `update`
- `destroy`

Use `ConnectorRoleService` for safety checks.

- [ ] **Step 5: Implement roles view**

Use left role list and right permission groups. Mark Owner as protected and disable delete.

- [ ] **Step 6: Add routes**

Add:

```php
Route::get('/connector/{connector}/roles', [RoleController::class, 'index'])->name('connector.roles.index');
Route::post('/connector/{connector}/roles', [RoleController::class, 'store'])->name('connector.roles.store');
Route::put('/connector/{connector}/roles/{role}', [RoleController::class, 'update'])->name('connector.roles.update');
Route::delete('/connector/{connector}/roles/{role}', [RoleController::class, 'destroy'])->name('connector.roles.destroy');
```

- [ ] **Step 7: Run role tests and verify pass**

Run:

```bash
php artisan test tests/Feature/Connectors/ConnectorRoleManagementTest.php
```

Expected: PASS.

---

### Task 8: Add Connector Subscription Entitlement Support

**Files:**
- Create: `app/Services/Connectors/ConnectorEntitlementService.php`
- Create: `app/Http/Controllers/Connector/SubscriptionController.php`
- Create: `resources/views/connectors/subscription.blade.php`
- Create: migration to support connector-owned subscriptions if existing subscription table is user-only
- Modify: `app/Models/Subscription.php`
- Modify: `app/Models/SubscriptionPlan.php` if helper scopes are needed
- Test: `tests/Feature/Connectors/ConnectorSubscriptionEntitlementTest.php`
- Test: `tests/Unit/Services/Connectors/ConnectorEntitlementServiceTest.php`

- [ ] **Step 1: Write failing entitlement tests**

Tests should assert:

- connector plan audience `connectors` is recognized
- connector entitlement service returns false without active connector subscription
- role permission alone does not grant entitlement-gated feature access
- permission plus active entitlement grants access
- connector subscription page shows current plan and upgrade options

- [ ] **Step 2: Run tests and verify failure**

Run:

```bash
php artisan test tests/Feature/Connectors/ConnectorSubscriptionEntitlementTest.php tests/Unit/Services/Connectors/ConnectorEntitlementServiceTest.php
```

Expected: failure because connector entitlement support does not exist.

- [ ] **Step 3: Extend subscription ownership**

If `subscriptions` is user-only, add nullable `connector_id` and enforce that a subscription belongs to either a user or connector. Add Eloquent relationships from `Connector` to subscriptions and active subscription.

- [ ] **Step 4: Implement entitlement service**

Resolve connector active subscription, plan, feature entitlements, and boolean checks for keys such as seminars/modules/educators.

- [ ] **Step 5: Implement subscription page**

Show current plan, enabled entitlements, locked features, and connector plan upgrade options.

- [ ] **Step 6: Run entitlement tests and verify pass**

Run:

```bash
php artisan test tests/Feature/Connectors/ConnectorSubscriptionEntitlementTest.php tests/Unit/Services/Connectors/ConnectorEntitlementServiceTest.php
```

Expected: PASS.

---

### Task 9: Remove Or Replace Existing Organization UI And References

**Files:**
- Modify or delete: `resources/views/admin/organizations/index.blade.php`
- Modify or delete: `resources/views/admin/organizations/show.blade.php`
- Modify: routes and controllers that reference old organizations
- Modify: seminar organization relationships only if required by failing tests or database constraints
- Test: relevant connector and existing test suite

- [ ] **Step 1: Find old Organization references structurally and literally**

Use CodeGraph for symbols and `rg` for literal Blade route/view strings.

- [ ] **Step 2: Write or update regression tests**

Add tests proving admin connector pages replace old admin organization pages and that connector registration/dashboard routes are the supported organization path.

- [ ] **Step 3: Remove stale routes/views/model references**

Delete or stop routing to old organization pages. Keep unrelated seminar functionality stable unless a direct organization dependency breaks migrations/tests.

- [ ] **Step 4: Run targeted tests**

Run:

```bash
php artisan test tests/Feature/Connectors
```

Expected: PASS.

---

### Task 10: Navigation, UI Polish, And Full Verification

**Files:**
- Modify: learner dashboard/profile view for connector registration entry point
- Modify: admin navigation for connector moderation entry point
- Modify: connector/admin views from earlier tasks as needed
- Test: full relevant test suite

- [ ] **Step 1: Add learner entry point**

Add a concise “Register Connector” card/action to the learner dashboard or profile area using existing learner UI patterns.

- [ ] **Step 2: Add admin navigation entry point**

Add connector moderation to the admin sidebar/navigation using current admin naming and spacing.

- [ ] **Step 3: Review UI states**

Check:

- registration validation errors
- pending status page
- rejected status page
- suspended status page
- verified dashboard
- members empty state
- roles Owner protected state
- subscription locked state

- [ ] **Step 4: Run tests**

Run:

```bash
php artisan test tests/Feature/Connectors tests/Unit/Services/Connectors
```

Expected: PASS.

- [ ] **Step 5: Run broader suite if feasible**

Run:

```bash
php artisan test
```

Expected: PASS or document pre-existing unrelated failures.

- [ ] **Step 6: Run frontend build if views/assets changed**

Run:

```bash
npm run build
```

Expected: build completes without errors.

---

## Self-Review Checklist

- Spec coverage: learner-side registration, admin review, membership invitations, scoped roles, safety rules, subscription entitlements, route separation, and UI pages are covered.
- Public registration removed: no public unauthenticated account creation is planned.
- Optional organization email: plan requires nullable email with uniqueness only when present.
- Proof document deferred: no upload field is planned for first implementation.
- Role separation: connector permissions remain separate from Spatie platform permissions.
- Subscription separation: connector permissions and entitlements are checked independently.
