# Connectors Design

## Goal

Implement Connectors as organization-based accounts for government and non-government groups, barangays, communities, schools, health organizations, advocacy groups, and other organizations participating in the sex-education learning ecosystem.

This design replaces the existing `Organization` concept with a fresh Connector domain. Connector creation is authenticated-only through the learner side. Public connector registration is intentionally out of scope to avoid bypassing normal platform account and learner onboarding rules.

## Scope

In scope:

- Learner-side connector registration.
- Connector profile and status lifecycle.
- Admin connector verification and moderation.
- Connector members with invitation acceptance.
- Dynamic connector-scoped roles and permissions.
- Connector dashboard with admin-inspired layout.
- Connector-side subscription visibility.
- Admin-controlled connector plans and entitlements using existing subscription infrastructure where practical.
- Strict separation between platform roles, connector roles, and subscription entitlements.

Out of scope for this first implementation:

- Public unauthenticated connector registration.
- Proof document upload.
- Full Seminars, Modules, and Educators feature implementation.
- Non-user email invitations.
- Ownership transfer workflow beyond last-owner safety.

## Connector Domain Model

Use simple connector naming in code and database tables:

- `connectors`
- `connector_memberships`
- `connector_roles`
- `connector_role_permissions`
- `connector_invitations`
- `connector_reviews`

The UI may say "Connector Organization" where helpful, but the code should use `Connector`.

### Connector Fields

`connectors` stores the organization account:

- `id`
- `name`
- `slug`
- `category`
- `organization_email`, nullable
- `contact_number`
- `description`, nullable
- `website_url`, nullable
- `verification_notes`, nullable
- `city_code`
- `barangay_code`
- `address_line`
- `status`
- `created_by`
- `primary_representative_user_id`
- `reviewed_by`, nullable
- `reviewed_at`, nullable
- `rejection_reason`, nullable
- `suspended_at`, nullable
- timestamps

Allowed `category` values:

- `government`
- `ngo`
- `community_based_organization`
- `school_educational_institution`
- `health_organization`
- `advocacy_group`
- `other`

Allowed `status` values:

- `pending`
- `verified`
- `rejected`
- `suspended`

`organization_email` is optional. When present, it must be valid and unique across connectors.

## Learner-Side Registration

Only authenticated platform users can register connectors. The initial implementation should require authentication and verified email. If the current app also requires profile completion before learner features, connector registration should follow the existing learner gating conventions.

Route:

```text
GET  /connectors/register
POST /connectors/register
```

Required fields:

- Connector / Organization Name
- Connector Category
- Cavite City / Municipality
- Barangay
- Address Line
- Contact Number

Optional fields:

- Organization Email
- Organization Description
- Website / Social Link
- Verification Notes

Deferred field:

- Proof Document

Flow:

```text
learner submits connector registration
-> connector is created with status pending
-> default Owner role is created for that connector
-> learner receives pending Owner membership
-> learner sees connector status page
-> admin reviews connector
-> approval activates connector and owner membership
```

No duplicate user account is created. The learner profile and platform role remain intact.

## Platform Role Boundary

Connector access is additive. Platform roles stay in Spatie/global RBAC. Connector roles live only inside connector tables.

A learner who owns a connector remains a learner globally. The owner capability is represented by `connector_memberships` plus a connector-scoped Owner role.

Connector permissions must never grant admin/platform permissions and must never be stored as Spatie permissions for users.

Access to connector resources requires:

```text
authenticated user
+ verified email
+ active accepted connector membership
+ connector status verified
+ connector role permission
+ connector subscription entitlement where applicable
```

## Admin Verification Workflow

Admin can:

- View connector registrations.
- Filter by `pending`, `verified`, `rejected`, and `suspended`.
- Search by connector name, organization email, representative, category, and location.
- View connector details.
- Approve pending or resubmitted connectors.
- Reject connectors with a required reason.
- Suspend verified connectors with a required reason.
- View status/review history.

Approval:

- Sets connector status to `verified`.
- Sets `reviewed_by` and `reviewed_at`.
- Activates the initial Owner membership.
- Leaves the user platform role unchanged.

Rejection:

- Sets connector status to `rejected`.
- Stores rejection reason.
- Keeps connector visible to the applicant on a status page.
- Allows resubmission.

Suspension:

- Sets connector status to `suspended`.
- Keeps records visible.
- Blocks connector actions.
- Shows a read-only suspended state in connector UI.

## Memberships And Invitations

Connectors support many members.

Membership statuses:

- `pending`
- `active`
- `rejected`
- `removed`

Invitation statuses:

- `pending`
- `accepted`
- `rejected`
- `expired`
- `cancelled`

Invitation flow:

```text
authorized connector member searches existing user by email
-> chooses connector role
-> sends invitation
-> invited user accepts or rejects
-> accepted user receives active connector membership
```

Rules:

- Members are added by invitation and acceptance.
- Users can belong to multiple connectors.
- Inviting a user does not change that user's platform role.
- Any existing non-suspended platform user may be invited.
- Non-user invitations are deferred.

## Connector Roles And Permissions

Each connector owns its roles. Every connector receives a protected Owner role during creation.

Suggested connector permissions:

- `connector.manage_profile`
- `connector.manage_members`
- `connector.invite_members`
- `connector.manage_roles`
- `connector.manage_seminars`
- `connector.manage_modules`
- `connector.manage_educators`
- `connector.view_subscription`

Permissions should come from a fixed config catalog, for example `config/connector_permissions.php`. Roles can map only to keys in this catalog.

Role safety rules:

- Every connector must always have at least one active Owner.
- Owner role cannot be deleted.
- Last active owner cannot be removed, downgraded, disabled, or changed to a non-owner role.
- Only users with `connector.manage_roles` can create, edit, or delete connector roles.
- Only users with `connector.manage_members` can remove members or change member roles.
- Only users with `connector.invite_members` can send invitations.
- Connector permission keys cannot overlap with platform/admin permission names.

## Subscription And Entitlements

Connector subscriptions are controlled from the admin/platform side.

Admin controls:

- connector plans
- pricing
- entitlements
- subscription payments
- plan availability

Connector side can:

- view current plan
- view included entitlements
- view upgrade options
- attempt subscription/upgrade through existing payment flows when available

Permissions and entitlements remain separate:

```text
connector role permission + connector plan entitlement = feature access
```

Example: a member with `connector.manage_seminars` still cannot manage seminars if the connector plan lacks the seminars entitlement.

Reuse the existing `subscription_plans`, `plan_prices`, `feature_catalog`, and `plan_feature_entitlements` infrastructure where practical. Extend subscription ownership so a subscription can belong to a connector without breaking user-owned subscriptions.

## Routes

Create a dedicated connector routes file:

```text
routes/connector.php
```

Authenticated registration routes:

```text
/connectors/register
```

Connector workspace routes:

```text
/connector/{connector}/status
/connector/{connector}/dashboard
/connector/{connector}/members
/connector/{connector}/roles
/connector/{connector}/seminars
/connector/{connector}/modules
/connector/{connector}/educators
/connector/{connector}/subscription
```

Admin routes:

```text
/admin/connectors
/admin/connectors/{connector}
/admin/connectors/{connector}/approve
/admin/connectors/{connector}/reject
/admin/connectors/{connector}/suspend
```

## UI Design

Use existing Blade and Tailwind conventions. Connector workspace should feel related to the admin-side layout but scoped to organization users.

Create:

```text
resources/views/layouts/connector-app.blade.php
```

Sidebar pages:

- Dashboard
- Members
- Roles & Permissions
- Seminars
- Modules
- Educators
- Subscription

Dashboard home:

- connector status
- member count
- pending invitations
- current plan
- enabled and locked feature summary

Members page:

- table layout
- user, email, role, status, joined date, actions
- invite form with email search and role select

Roles page:

- role list on the left
- selected role details and grouped permission checkboxes on the right
- protected Owner state
- disabled delete for Owner

Stub pages:

- Seminars
- Modules
- Educators

Each stub page should use a clean empty state with a short governance-focused message and disabled or entitlement-gated action.

Admin connector moderation UI should follow the instructor application review pattern: status tabs, search, concise cards/tables, inline approve/reject/suspend actions, and detail panels.

## PSGC Address

Connector registration uses Cavite-only PSGC data:

- City/Municipality dropdown filtered to Cavite.
- Barangay dropdown filtered by selected city/municipality.
- Store PSGC codes in `city_code` and `barangay_code`.
- Store detailed street/building information in `address_line`.

## Existing Organization Removal

Remove or supersede the old organization implementation:

- `app/Models/Organization.php`
- old `organizations` migration usage where safe
- old admin organization routes/views/controllers if present
- `User::organization()` one-user relationship

Existing old organization rows do not require preservation.

Any seminar or feature relationship that currently points at organizations should be updated to connector naming when that feature is touched. First implementation can keep Seminars as a connector stub unless existing database constraints force an immediate migration.

## Testing Strategy

Use test-driven implementation.

Feature tests should cover:

- learner connector registration creates connector, Owner role, and pending owner membership
- organization email is optional
- organization email is unique when present
- proof document is not required
- duplicate user account is not created
- pending connector shows status page and cannot access full dashboard
- admin can approve connector and activate owner membership
- admin can reject connector with reason
- admin can suspend verified connector
- active verified owner can access connector dashboard
- member invitation accept/reject flow
- last-owner guardrails
- connector permission checks
- subscription entitlement checks

Unit tests should cover:

- connector permission catalog validation
- connector access resolver
- connector role safety service
- connector entitlement resolver

## Acceptance Criteria

- Public connector registration does not exist.
- Authenticated learners can register connector organizations.
- Connector registration does not create duplicate users.
- Connector status remains pending until admin approval.
- Verified connectors can access the connector workspace.
- Rejected and suspended connectors see status-specific read-only pages.
- Connector members are invitation-based.
- Connector roles and permissions are connector-scoped.
- Platform roles remain separate from connector roles.
- Subscription entitlements remain separate from connector permissions.
- UI follows existing Blade, Tailwind, and admin-layout patterns.
