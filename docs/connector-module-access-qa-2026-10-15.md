# Administrator Connector module access — October 15, 2026

Implemented on the existing dirty checkout. Open **Admin → Connectors → Manage module access**, or use `/admin/connectors/module-access`. The Connector detail page also links to the workspace.

## Root cause and resulting behavior

The existing management routes were confined to Connector managers. The Connector rule form defaulted to all members and offered role/member target types without any target-ID control. Its roster displayed existing assignments only, so unassigned eligible people could not be selected. There was no administrator selection/review/bulk workflow or administrator grant/revoke history.

The new admin workspace selects an organization and its existing module license, shows module/publication/license details and seat usage, and lists members, staff/custom organizational roles, and historical assignees. Search and role/membership/access filters operate over a paginated roster. Counts include independent grants, valid access through other Connectors, and supported legacy approved enrollments, using the shared access rules.

Admins select users explicitly, review server-rendered names and statuses, then confirm. The server revalidates every user and the license at execution. A failed member/seat check cancels the entire bulk selection; the UI never reports a partially granted batch as successful. Repeated grant/revoke requests reuse existing assignments, rules, and grants. State-changing admin operations write an existing admin activity log per affected user; no-op retries do not duplicate those events. History is paginated and visible in the admin workspace.

## Authorization and safety

- All three routes require authenticated Spatie `admin` role; the FormRequest and domain mutation/review entry points also enforce that role. Existing Connector manager routes and permissions stay separate.
- Route Connector and entitlement must match. IDs, action, selection size (1–100), distinct user IDs, and confirmation are validated server-side. Roles/access flags sent by a client are not used as authority.
- Grants require a verified Connector, usable existing organizational entitlement, learner-visible published module, active membership, and existing account/age eligibility. Custom staff roles use normal memberships. Guardian-pending assignments reserve seats under existing rules.
- Execution locks the entitlement, Connector, module, selected memberships and users, uses the existing reconciler, and checks every resulting selected assignment. Insufficient capacity rolls back rules, assignments, grants, enrollment changes, and audit writes together.
- A revoke requires a previous assignment under the selected license. Historical/removed members and suspended licenses remain revocable; archived module cleanup uses the soft-deleted module row.
- Only the selected Connector assignment source is deactivated. Independent purchases/grants, other organizations' valid access, progress, quiz attempts, completion percentages and issued certificates remain. No learner purchase, charge, commission, or new licensing system is created.
- Page load and review are read-only. Existing broader Connector rules remain in force; the new workflow creates only explicit member rules.
- Unexpected failures are reported to normal application logging. The admin is told to reload before retrying, because a notification failure after commit can occur after access has already changed.

## Files changed for this task

| File | Change |
| --- | --- |
| `app/Http/Controllers/Admin/ConnectorModuleAccessController.php` | New thin roster/review/confirmation controller, filtering/pagination and feedback. |
| `app/Http/Requests/Admin/ManageConnectorModuleAccessRequest.php` | Admin authorization, selection/action/confirmation validation, safe GET error redirect. |
| `app/Services/Connectors/ConnectorModuleAccessService.php` | Selected-user review/mutation, transactional retryable grants/revokes, audit records and batched roster. |
| `app/Services/Modules/ModuleAccessService.php` | Batch access summary sharing normal access predicates; soft-deleted module lookup for source deactivation. Preserves existing unrelated working-tree changes in this file. |
| `routes/admin.php` | Three admin module-access routes. Preserves unrelated existing route changes. |
| `resources/views/admin/connectors/module-access/index.blade.php` | Organization/license selectors, metrics, filters, explicit selection and audit history. |
| `resources/views/admin/connectors/module-access/review.blade.php` | Review selected users, required confirmation, cancel. |
| `resources/views/admin/connectors/index.blade.php` and `show.blade.php` | Entry links only; existing verification UI work preserved. |
| `tests/Feature/Admin/ConnectorModuleAccessManagementTest.php` | 16 focused regression scenarios. |
| `docs/superpowers/plans/2026-10-15-admin-connector-module-access.md` | Plan and completion ledger. |
| `docs/connector-marketplace-and-verification-e2e-runbook.md` | Admin access QA scenarios and evidence pointer. |
| This report | Scope, safety, evidence and remaining gates. |

**Database changes: none.** No migrations, development fixtures, purchases, or learner records were modified. Tests used the existing isolated MySQL `cc_db_test` schema and rollback transactions through `tests/existing-database-bootstrap.php`; no schema reset or destructive command was run.

## Automated evidence

- New regression tests initially failed on missing admin routes. Follow-up tests reproduced the archived-module crash, incorrect independent-access counts, POST-only error redirect, and missing unexpected-failure feedback before fixes.
- Final module/access/marketplace/channel regression set: **98 tests, 633 assertions, zero failures/errors**, with three existing PHPUnit metadata deprecations. Includes grant/revoke/regrant retries, bulk atomic success/failure, cross-organization IDs and entitlement mismatch, unauthorized direct requests and domain calls, seat exhaustion/reuse, guardian-pending seats, full-capacity independent purchase, audit rollback, learning-record preservation, archived modules, existing browsing/lifecycle/access endpoints, and roster query-count stability.
- Focused Pint: five task PHP files pass. Blade compilation and admin route listing pass. Whitespace check passes.
- Vite production build passed in isolated `storage/framework/connector-module-access-build`; disposable output was removed after verification. No new JS entry point or dependency is required.
- Independent code review found the archived-module and count defects; both have regression coverage and were resolved. Follow-up review reported no remaining important findings.

Reproduce the regression run in PowerShell:

```powershell
$env:APP_ENV='testing'
$env:DB_CONNECTION='mysql'
$env:DB_DATABASE='cc_db_test'
$env:APP_CONFIG_CACHE='bootstrap/cache/config-testing.php'
php vendor/bin/phpunit --bootstrap tests/existing-database-bootstrap.php --do-not-cache-result --log-junit storage/framework/connector-module-access-regressions.xml --filter 'ConnectorModule|ConnectorMarketplace|ConnectorPurchaseChannel' tests/Feature
```

Run database tests sequentially against this shared test database. The bootstrap refuses other databases/environments and cached configuration.

## Browser evidence and remaining gates

The in-app browser was unavailable. Installed headless Chromium checked actual Blade output from transaction-only test fixtures with requests intercepted; it did not mutate the running development app. Desktop (1440 px) and mobile (390 px) layouts, horizontal table containment, Alpine select-all/individual selection, keyboard checkbox toggling, submitted user IDs/action, and required confirmation passed. Screenshots are in `storage/framework/connector-module-access-{desktop,mobile,review-desktop,review-mobile}.png`. A missing local WebSocket service produced a connection warning in one fixture run; it did not affect selection or form assertions.

These checks do not replace a signed-in staging journey against real HTTP responses, a screen-reader audit, true simultaneous multi-session race testing, production-volume profiling, the full PHPUnit suite, or repository-wide formatting. The roster avoids N+1 queries but loads the selected organization's roster in memory for shared eligibility/counts; move filtering to SQL if very large organizations require it. Historical Connector-manager changes made before this task are not backfilled into the new admin-action history.

An intermittent disk-space error interrupted one verification attempt; removing only this task's disposable build output allowed tests, formatting and Blade compilation to finish. No user logs or unrelated artifacts were deleted. Commit/deploy/release activation remains outside this task.

## Follow-up: member photos, learner details and row actions

The Connector **Access Management** page now uses **Grant Access** instead of **Add Rule**. Its default action selects one eligible member explicitly, rather than creating an implicit all-member rule. The full membership roster includes people without assignments; opening it no longer reconciles or changes access.

Both Connector and admin rosters display the existing learner/instructor photo with initials fallback, a **View** learner-information dialog, age group, eligibility indicator and explanation, and appropriate per-user actions. Details contain name/email, age/group, organization role, membership, module audience, eligibility and assignment status. Private identity documents, address and exact birthdate are not included. Native modal dialogs support focus containment, Escape and return to the trigger.

- Eligible unassigned/revoked users can receive **Grant Access**; existing assignments can be revoked. Ineligible users show the reason and cannot grant through the UI. Inactive historical assignments remain revocable.
- Connector managers confirm a single action in a dialog; admins use the existing review/confirmation page. Admin row forms submit only that row, independently of bulk selection.
- The new manager endpoint checks entitlement ownership, `connector.manage_modules`, validated action/user ID, confirmation, membership, eligibility and seats. It reuses the same transactional mutation engine as admin operations. Admin-only bulk entry points remain admin-only. Manager transitions use existing activity logging; admin transitions retain admin audit logging.
- Eligibility now uses the same `Module::isAppropriateForAge()` check as learner access, respecting explicit category exclusions such as Kids + Adults with no Teens. Loaded categories are reused and invalidated when categories change. Photos and categories are eager-loaded, including all-unknown-age rosters, to avoid per-row queries.
- Unknown ages remain visibly marked **Age unavailable**, with the existing eligibility policy preserved. Guardian-pending access remains pending; learner purchases and progress preservation are unchanged.

Additional files: `app/Models/Module.php`, Connector `ModuleLibraryController` and `ModuleAccessController`, `routes/connector.php`, `resources/views/connectors/modules/library/access.blade.php`, `resources/views/components/module-access-dialog.blade.php`, shared `modules/partials/access-member` and `access-eligibility` Blade partials, and `tests/Feature/Connectors/ConnectorModuleAccessManagementUiTest.php`. The admin roster and shared Connector access service were updated in place. No database changes or new dependencies.

Final verification: **104 tests / 680 assertions**, no failures/errors, three existing metadata deprecations; JUnit output `storage/framework/connector-access-members-regressions.xml`. Focused Pint, Blade compilation and route listing passed. Earlier fixture deadlocks in the shared test database were absent from the final sequential run.

Intercepted test-fixture Chromium checks passed at desktop/mobile widths: photos, age match/mismatch, role-appropriate actions, learner dialog sizing/focus/Escape, required confirmation, member grant payload, and admin row form ownership even when bulk checkboxes are selected. Screenshots: `storage/framework/access-members-{connector-desktop,connector-mobile,learner-desktop,learner-mobile,admin-desktop}.png`. No live access changes were submitted.

The full asset build encountered disk exhaustion. A stylesheet-only Vite production build using the existing PostCSS configuration passed with public-directory copying disabled. Its `app-BY_BaldP.css` was byte-identical to the stylesheet already referenced by `public/build/manifest.json`, so no public manifest/asset replacement was needed for this follow-up. No JavaScript source changed. Full-suite, live staging, screen-reader and simultaneous-session gates above remain open.

## Action modal refinement — October 16, 2026

Each Connector/admin roster row now has a single **Actions** button. Its modal contains **View learner information** and the allowed grant/revoke actions; View no longer appears in the Member column. Ineligible members can still view their information and see why a grant is unavailable. Connector grant/revoke actions lead to the existing confirmation dialogs; admin actions retain the existing server review page and external per-row form ownership. **Remove grant** now opens a confirmation dialog instead of submitting immediately.

Changes are limited to the Connector/admin access templates, shared `modules/partials/access-member` and new `access-actions` partial, and the Connector UI regression test. Existing authorization, transactions and learning records are unchanged. The modal reuses the existing component and compiled styles; no new assets or database changes are required.

Verification: **24 tests / 199 assertions** passed using rollback transactions in `cc_db_test`, plus focused Pint and Blade compilation. Intercepted Chromium fixtures passed desktop/mobile modal opening, View placement, allowed/ineligible choices, grant/revoke confirmation transitions, one open dialog, Escape/focus return, removal confirmation, mobile overflow and admin row/bulk form isolation. A browser regression caught hidden accessible text causing mobile overflow; replacing it with an `aria-label` resolved the issue. Evidence: `storage/framework/access-action-modals.xml`, `access-actions-table-desktop.png`, `access-actions-modal-desktop.png`, and `access-actions-modal-mobile.png`. Live staging and the full suite were not rerun for this presentation-only refinement.
