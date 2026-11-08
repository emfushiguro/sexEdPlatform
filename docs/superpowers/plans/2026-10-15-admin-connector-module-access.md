# Admin Connector Module Access implementation plan

**Goal:** Explicit, auditable admin grants and revocations against existing Connector module licenses.

**Architecture:** Reuse Connector memberships/roles, per-member rules, seat assignments, ModuleAccessService, and AdminActivityLogService. Add an admin controller/request and Blade workspace with organization/license selectors, roster filters, a server-rendered review step, and paginated history. Do not change marketplace, purchase, commission, or learner access architecture.

**Constraints:** Preserve the dirty current checkout. No commits, dependency additions, migrations, development data mutations, or implicit all-member grants. Run tests sequentially in existing `cc_db_test` with transaction-only bootstrap.

- [x] Add regression tests and observe missing admin routes/workflow failing.
- [x] Extend ConnectorModuleAccessService with admin-only, transactional selected-member operations. Lock the entitlement; validate organization, published module, membership and eligibility; reuse reconciliation; rollback an entire bulk operation if any selected user cannot receive a seat. Preserve revocation tombstones and independent grants.
- [x] Add server-side request validation and thin admin routes/controller, including a read-only review step. Repeat domain validation at execution. Reuse existing admin role authorization, without granting admins fake Connector membership.
- [x] Add existing-style Blade workspace, explicit checkboxes, search/role/membership/access filters, global counts, module/license details, action review, feedback, and audit history. Link from Connector administration.
- [x] Run focused new tests, existing assignment/guardian/membership/access/browsing/purchase regressions, style, Blade compilation, and build checks. Inspect final diff and document results and remaining manual QA.

## Decisions

- Existing organizational entitlement is the license authority; unpublishing a marketplace listing does not invalidate a completed purchase.
- A staff/custom organizational role is supported through the existing active Connector membership; platform role is not a substitute for organizational membership.
- Bulk actions are atomic and retryable. Guardian-pending grants reserve seats under existing rules and are labeled pending, not active learning access.
- Revoke remains available for historical assignments even when membership or license eligibility has ended. It never creates a new assignment for a stranger.
- Use a server-rendered review page for accurate names and status; validate again on confirmation. No new JavaScript bundle is required.

## Completion ledger

- Confirmed missing admin workflow and incomplete Connector rule form before implementation; new route tests failed first.
- Review caught archived-module roster/cleanup and independent-access count defects. Added failing regressions and fixed both through shared access validation.
- Reused the existing admin role boundary, rather than changing Connector manager permissions or creating organization memberships for admins.
- Added batch access summaries with the same predicates as normal learner access; verified query count stays constant as roster size grows.
- Added neutral failure feedback and a GET roster redirect for validation after the POST review page. Unexpected failures are reported; after-commit notification failures instruct the admin to reload before retrying.
- No database/schema changes, commits, worktrees, merges, or dependency changes. Final results and remaining gates: [QA report](../../connector-module-access-qa-2026-10-15.md).
