# Admin Management Table Standardization Design

## 1. Purpose
Standardize all admin-side management tables using Payment Management as the reference UI and interaction pattern, while keeping business workflows intact and preventing breaking changes.

## 2. Problem Statement
The admin panel currently mixes multiple table patterns (different filter layouts, pagination behaviors, card styling, action ordering, and icon treatment). This creates avoidable cognitive load, inconsistent moderation flow, and higher maintenance cost.

## 3. Approved Decisions
Confirmed during brainstorming:

1. Rollout style: B (Approach 3 / Hybrid)
2. Enrollments ownership in this pass: B (reuse existing enrollment pages and align them)
3. Naming: A (keep labels/routes as-is, standardize UI)
4. Filtering model: A (real-time filtering behavior)
5. Pagination model: A (real-time pagination behavior)
6. Action strategy: B (View-first baseline, domain actions allowed)
7. Module revenue payment-state logic: B (finalized paid model; remove payout transition workflow)
8. Module revenue transaction details depth: B (full detail page)
9. Instructor roll-up view depth: B (full dedicated instructor page)
10. Archive/Delete policy: B (keep both where already supported)
11. Visual strictness: A (strict parity with Payment Management, including palette/icon consistency)
12. Verification model: D (targeted automated tests plus manual checklist)

## 4. In-Scope Pages
All listed pages are included:

1. User Management
2. Instructor Application Management
3. Module Published Review Management
4. Enrollments Management (including details)
5. Users Management (including view user page)
6. Subscribers Management (including subscriber details)
7. Plans Management (including plan details)
8. Payment Management (including payment details)
9. Module Revenue Management
10. Commission Settings

Plus required additions:

1. Module Revenue transaction details page (new)
2. Instructor Roll-up dedicated view page (new)

## 5. Baseline Reference
Canonical baseline is:

1. resources/views/admin/payments/index.blade.php

Reference attributes to replicate:

1. Stat cards placement and visual hierarchy
2. Table shell spacing and section grouping
3. Filter bar behavior and field rhythm
4. Pagination placement and readability
5. Icon-first action controls
6. Action ordering and button shape
7. Header typography, divider rhythm, and row hover style

## 6. Global Admin Table UX Contract

### 6.1 Page Composition
Every standardized management page uses the same hierarchy:

1. Stat cards row
2. Filter/search section
3. Table section
4. Pagination footer

### 6.2 Table Contract

1. First column is always No.
2. Last column is always Actions.
3. Entity-identifying columns appear early (name/module/user).
4. Status and date columns are consistent in relative order.
5. Row hover remains subtle and operational.

### 6.3 Filter Contract

1. Real-time search filtering
2. Real-time column/status filtering where applicable
3. Real-time date filtering where applicable
4. Visible reset control on every management table

### 6.4 Pagination Contract

1. Consistent footer placement and control style
2. Previous/Page X of Y/Next readability pattern
3. Real-time page updates after filter changes
4. Record count retained where already available

### 6.5 Action Contract

1. View appears first
2. Archive appears before Delete when both exist
3. Domain actions (approve/reject/start review) remain but use consistent visual language
4. Action buttons remain icon-first with accessible titles/labels

### 6.6 Badge And Icon Contract

1. Status colors are semantically consistent across pages
2. View/archive/delete icon metaphors stay identical
3. Icon size and button hit area match Payment baseline

## 7. Visual System Standard
Strict payment-parity visual rules:

1. Same card/table shell radii and border density
2. Same panel grouping rhythm and spacing scale
3. Same accent usage policy (brand accent, not heavy backgrounds)
4. Same action button shape and hover treatment
5. Same table header style and row divider rhythm

Constraint:

1. Keep admin UI lightweight and readable
2. Avoid heavy or conflicting visual components

### 7.1 Payment Palette Lock
All standardized pages must use the same color families already present in Payment Management.

Core UI palette:

1. Brand accents: brand-50, brand-100, brand-200, brand-500, brand-600, brand-700, brand-800, brand-900
2. Neutral structure: gray-50, gray-100, gray-200, gray-500, gray-600, gray-700, gray-900
3. Success states: emerald-100 and emerald-700
4. Warning states: amber-100 and amber-700
5. Error/destructive states: rose-100 and rose-700

Gradient and highlight policy:

1. Reuse payment-style top container gradients and stat-card gradient direction
2. Do not introduce unrelated accent families (for example sky, violet, indigo) on standardized table shells unless the value is semantic and explicitly approved
3. Keep icon button fills, borders, and hover states aligned to Payment Management behavior

Icon and badge consistency:

1. View action uses the same neutral/brand treatment as Payment Management
2. Archive action uses the same brand/amber treatment as Payment Management
3. Delete action uses the same destructive treatment as Payment Management
4. Status badges must map consistently to the semantic color system above

## 8. Architecture Strategy (Approach 3: Hybrid)
Use a shared standard layer plus page-level adaptation.

### 8.1 Shared Standard Layer
Leverage and extend shared partials for consistency:

1. resources/views/admin/partials/table-filter-bar.blade.php
2. resources/views/admin/partials/row-actions.blade.php

Add small reusable wrappers/components only where needed for:

1. Standard table shell
2. Standard action icon set
3. Standard pagination footer wrapper

### 8.2 Page-Level Adaptation
Each page keeps business-specific content and controller behavior while aligning UI shell and interaction patterns.

## 9. Page-By-Page Target Design

### 9.1 User Management / Users Management
Files:

1. resources/views/admin/users/index.blade.php
2. resources/views/admin/users/partials/users-table.blade.php
3. resources/views/admin/users/show.blade.php

Target:

1. Align table shell/filter/pagination styling with Payment baseline
2. Keep existing wizard create/edit behavior
3. Keep existing role/status logic and actions

### 9.2 Instructor Applications
Files:

1. resources/views/admin/instructor-applications/index.blade.php
2. resources/views/admin/instructor-applications/show.blade.php

Target:

1. Keep approve/reject/archive/delete business flow
2. Normalize filter area and table shell to baseline
3. Preserve review detail panel behavior

### 9.3 Module Published Review
Files:

1. resources/views/admin/content-reviews/index.blade.php
2. resources/views/admin/content-reviews/show.blade.php

Target:

1. Keep moderation-specific actions (Start Review, Review, Archive)
2. Standardize table/filter/pagination visual contract
3. Keep review workspace behavior intact

### 9.4 Enrollments Management (Reuse Existing)
Files:

1. resources/views/instructor/enrollments/index.blade.php
2. resources/views/instructor/enrollments/show.blade.php

Target:

1. Align to admin baseline shell while preserving enrollment logic
2. Keep approve/reject/archive/delete flows
3. Keep details page but align layout/readability to admin detail standards

### 9.5 Subscribers Management
Files:

1. resources/views/admin/subscriber/index.blade.php
2. resources/views/admin/subscriber/show.blade.php

Target:

1. Standardize stats/filter/table/pagination to Payment baseline
2. Keep existing subscriber actions and detail data

### 9.6 Plans Management
Files:

1. resources/views/admin/subscription-plans/index.blade.php
2. resources/views/admin/subscription-plans/show.blade.php

Target:

1. Align list/table area to baseline where table interactions are present
2. Keep existing plan wizard flow and behavior
3. Ensure details page visual parity with admin detail pages

### 9.7 Payment Management
Files:

1. resources/views/admin/payments/index.blade.php
2. resources/views/admin/payments/show.blade.php

Target:

1. Treat as canonical reference
2. Apply only minor normalization if needed for strict consistency tokens

### 9.8 Module Revenue Management
Files:

1. resources/views/admin/monetization/module-revenue.blade.php
2. app/Http/Controllers/Admin/ModuleRevenueController.php
3. app/Services/Monetization/ModuleSaleLedgerService.php

Target:

1. Add dedicated transaction details page per row
2. Add archive/delete row actions as standard controls
3. Remove Mark as Payable and Mark as Paid actions from active UI
4. Present transactions as finalized paid from learner completion lifecycle

### 9.9 Commission Settings
Files:

1. resources/views/admin/monetization/commission-settings.blade.php
2. app/Http/Controllers/Admin/CommissionSettingsController.php

Target:

1. Align table/filter/visual contract to baseline
2. Keep policy creation/edit workflow intact

## 10. Module Revenue Finalized-State Redesign
Current implementation creates ledger rows as payout_status pending and supports pending->payable->paid transitions.

Approved redesign:

1. Backfill historical ledger rows to payout_status paid
2. Create all new ledger rows with payout_status paid
3. Remove payout transition actions from dashboard UI
4. Keep safe compatibility for existing route/controller methods where needed

Business outcome:

1. Revenue rows reflect learner-side completion as finalized transactions
2. Admin UI shows operational review actions (view/archive/delete), not payout progression steps

## 11. New Pages Required

### 11.1 Transaction Details (Module Revenue)
A dedicated page per transaction with:

1. Transaction metadata and references
2. Learner snapshot and profile links
3. Instructor snapshot and profile links
4. Module details
5. Revenue split breakdown
6. Payment context and timestamps

### 11.2 Instructor Roll-up View
A dedicated page per instructor roll-up row with:

1. Instructor financial summary cards
2. Instructor-scoped transaction table
3. Real-time filters and pagination
4. Archive/delete behavior aligned with standard controls where applicable

## 12. Routing And Controller Design
Additive route extensions under admin monetization:

1. Module revenue transaction show route
2. Instructor roll-up show route

Controllers remain thin and service-oriented.

## 13. Non-Breaking Guarantees

1. No destructive route removals
2. Existing policy/permission boundaries preserved
3. Existing business workflows retained except approved payout transition removal from UI
4. Existing detail pages remain reachable with enhanced consistency

## 14. Testing And Verification Strategy

### 14.1 Automated
Targeted Feature tests for:

1. Standard table structure expectations
2. Real-time filter and pagination interactions
3. Action button consistency and ordering
4. Module revenue finalized-state behavior
5. New transaction details page
6. New instructor roll-up page

### 14.2 Manual
Checklist across all target pages:

1. Visual parity to Payment baseline
2. Icon and status color consistency
3. Filter reset and real-time responsiveness
4. Pagination readability and placement
5. Detail-page consistency

## 15. Rollout Plan

1. Build shared standard layer
2. Standardize users/instructor applications/content review/subscribers
3. Standardize plans/commission/enrollments
4. Implement module revenue redesign and new detail pages
5. Run verification matrix and finalize

## 16. Acceptance Criteria
Done when:

1. All in-scope pages follow one table UX contract
2. Real-time filtering and pagination are consistently applied
3. Module revenue reflects finalized paid model
4. Transaction details and instructor roll-up dedicated views exist
5. UI remains lightweight and consistent with admin theme
6. Targeted tests and manual checklist pass

---

Approval status: Approved by user during brainstorming on 2026-04-14.
