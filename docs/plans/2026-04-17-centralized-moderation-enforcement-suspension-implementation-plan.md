# Centralized Moderation, Violation, Enforcement, and Suspension Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Build a centralized moderation pipeline with dual-write transition, configurable hybrid automation, suspension enforcement, and appeal workflows with audit-ready governance.

**Architecture:** Use a central moderation orchestrator and normalized moderation domain tables while preserving existing source workflows through dual-write adapters during rollout. Follow strict build order for schema dependencies and enforce policy boundaries in services, middleware, and admin UI. Standardize moderation admin pages to the Payment Management table/palette baseline.

**Tech Stack:** Laravel 12, PHP 8.2, Eloquent, Form Requests, Blade, Alpine.js, Tailwind CSS v3, PHPUnit.

---

## Task 1: Create moderation_cases foundation

**Files:**
- Create: `database/migrations/2026_04_17_100000_create_moderation_cases_table.php`
- Create: `app/Models/ModerationCase.php`
- Create: `app/Enums/ModerationCaseStatus.php`
- Create: `app/Enums/ModerationCaseSource.php`
- Test: `tests/Feature/Admin/Moderation/ModerationCaseSchemaTest.php`

**Step 1: Write failing schema/model test**

Create tests for:
1. `moderation_cases` table exists with required columns
2. `case_reference_code` uniqueness
3. reporter is nullable

**Step 2: Run test to verify failure**

Run: `php artisan test --filter=ModerationCaseSchemaTest`
Expected: FAIL with missing table/column assertions.

**Step 3: Write minimal migration, model, and enums**

1. Add required fields and indexes
2. Add casts and fillable in model
3. Add source/status enums for canonical values

**Step 4: Run test to verify pass**

Run: `php artisan test --filter=ModerationCaseSchemaTest`
Expected: PASS.

**Step 5: Commit**

Run:
1. `git add database/migrations/2026_04_17_100000_create_moderation_cases_table.php app/Models/ModerationCase.php app/Enums/ModerationCaseStatus.php app/Enums/ModerationCaseSource.php tests/Feature/Admin/Moderation/ModerationCaseSchemaTest.php`
2. `git commit -m "feat(moderation): add moderation case foundation"`

## Task 2: Create violations domain (depends on confirmed moderation cases)

**Files:**
- Create: `database/migrations/2026_04_17_100100_create_violations_table.php`
- Create: `app/Models/Violation.php`
- Create: `app/Enums/ViolationSeverity.php`
- Create: `app/Services/Moderation/ViolationService.php`
- Test: `tests/Feature/Admin/Moderation/ViolationIssuanceRulesTest.php`

**Step 1: Write failing violation issuance tests**

Test scenarios:
1. violation cannot be created when case decision is not `confirmed_violation`
2. violation can be created from confirmed case
3. severity-based expiry is applied

**Step 2: Run failing test**

Run: `php artisan test --filter=ViolationIssuanceRulesTest`
Expected: FAIL.

**Step 3: Implement minimal schema + service rules**

1. Add `violations` table with required fields
2. Implement service guard for confirmed-case-only issuance
3. Implement severity-based expiration resolver

**Step 4: Run pass check**

Run: `php artisan test --filter=ViolationIssuanceRulesTest`
Expected: PASS.

**Step 5: Commit**

Run:
1. `git add database/migrations/2026_04_17_100100_create_violations_table.php app/Models/Violation.php app/Enums/ViolationSeverity.php app/Services/Moderation/ViolationService.php tests/Feature/Admin/Moderation/ViolationIssuanceRulesTest.php`
2. `git commit -m "feat(moderation): add confirmed-case violation tracking"`

## Task 3: Create enforcement_actions domain and escalation rules

**Files:**
- Create: `database/migrations/2026_04_17_100200_create_enforcement_actions_table.php`
- Create: `app/Models/EnforcementAction.php`
- Create: `app/Enums/EnforcementActionType.php`
- Create: `app/Services/Moderation/EnforcementActionService.php`
- Test: `tests/Feature/Admin/Moderation/EnforcementEscalationPolicyTest.php`

**Step 1: Write failing escalation tests**

Test scenarios:
1. minor/moderate cannot skip ladder
2. major/critical can skip with rationale
3. permanent suspension cannot be auto-issued

**Step 2: Run failing test**

Run: `php artisan test --filter=EnforcementEscalationPolicyTest`
Expected: FAIL.

**Step 3: Implement minimal escalation service + schema**

1. Add action type/status fields
2. Implement skip-policy guard
3. Enforce manual-only permanent suspension constraint

**Step 4: Run pass check**

Run: `php artisan test --filter=EnforcementEscalationPolicyTest`
Expected: PASS.

**Step 5: Commit**

Run:
1. `git add database/migrations/2026_04_17_100200_create_enforcement_actions_table.php app/Models/EnforcementAction.php app/Enums/EnforcementActionType.php app/Services/Moderation/EnforcementActionService.php tests/Feature/Admin/Moderation/EnforcementEscalationPolicyTest.php`
2. `git commit -m "feat(moderation): add enforcement action layer and escalation policy"`

## Task 4: Create automation rules and automation_rule_logs

**Files:**
- Create: `database/migrations/2026_04_17_100300_create_moderation_automation_rules_table.php`
- Create: `database/migrations/2026_04_17_100350_create_moderation_automation_rule_versions_table.php`
- Create: `database/migrations/2026_04_17_100400_create_automation_rule_logs_table.php`
- Create: `app/Models/ModerationAutomationRule.php`
- Create: `app/Models/ModerationAutomationRuleVersion.php`
- Create: `app/Models/AutomationRuleLog.php`
- Create: `app/Http/Requests/Admin/UpdateModerationAutomationRulesRequest.php`
- Create: `app/Services/Moderation/Automation/ModerationAutomationValidator.php`
- Create: `app/Services/Moderation/Automation/ModerationAutomationService.php`
- Create: `app/Jobs/Moderation/EvaluateAutomationRulesJob.php`
- Test: `tests/Feature/Admin/Moderation/AutomationRulesSafetyTest.php`
- Test: `tests/Feature/Admin/Moderation/AutomationExecutionLogTest.php`

**Step 1: Write failing tests for rule validation and logging**

Cover:
1. invalid thresholds rejected
2. invalid resulting actions rejected
3. conflicting rule conditions rejected
4. highest-severity action chosen when conflicts exist
5. every execution writes `automation_rule_logs`

**Step 2: Run failing tests**

Run: `php artisan test --filter="AutomationRulesSafetyTest|AutomationExecutionLogTest"`
Expected: FAIL.

**Step 3: Implement minimal rules schema + validator + job**

1. Add rule versioning tables
2. Add log table with execution outcome snapshots
3. Validate rules before save
4. Process async rule evaluation via queue job with idempotency key

**Step 4: Seed default presets and add service tests**

1. Add default presets as DB records, not hardcoded runtime constants
2. Add tests for three approved defaults

**Step 5: Run pass check**

Run: `php artisan test --filter="AutomationRulesSafetyTest|AutomationExecutionLogTest"`
Expected: PASS.

**Step 6: Commit**

Run:
1. `git add database/migrations/2026_04_17_100300_create_moderation_automation_rules_table.php database/migrations/2026_04_17_100350_create_moderation_automation_rule_versions_table.php database/migrations/2026_04_17_100400_create_automation_rule_logs_table.php app/Models/ModerationAutomationRule.php app/Models/ModerationAutomationRuleVersion.php app/Models/AutomationRuleLog.php app/Http/Requests/Admin/UpdateModerationAutomationRulesRequest.php app/Services/Moderation/Automation/ModerationAutomationValidator.php app/Services/Moderation/Automation/ModerationAutomationService.php app/Jobs/Moderation/EvaluateAutomationRulesJob.php tests/Feature/Admin/Moderation/AutomationRulesSafetyTest.php tests/Feature/Admin/Moderation/AutomationExecutionLogTest.php`
2. `git commit -m "feat(moderation): add configurable automation rules and audit logs"`

## Task 5: Create user_suspensions domain

**Files:**
- Create: `database/migrations/2026_04_17_100500_create_user_suspensions_table.php`
- Create: `app/Models/UserSuspension.php`
- Create: `app/Services/Moderation/SuspensionService.php`
- Test: `tests/Feature/Admin/Moderation/UserSuspensionLifecycleTest.php`

**Step 1: Write failing suspension lifecycle tests**

Cover:
1. active/expired/revoked state transitions
2. permanent suspension stores `ends_at` as null
3. appeal_pending state handling

**Step 2: Run failing test**

Run: `php artisan test --filter=UserSuspensionLifecycleTest`
Expected: FAIL.

**Step 3: Implement minimal schema + suspension service**

1. Add central suspension table
2. Add status and appeal status handling
3. Add permanent-suspension guardrails

**Step 4: Run pass check**

Run: `php artisan test --filter=UserSuspensionLifecycleTest`
Expected: PASS.

**Step 5: Commit**

Run:
1. `git add database/migrations/2026_04_17_100500_create_user_suspensions_table.php app/Models/UserSuspension.php app/Services/Moderation/SuspensionService.php tests/Feature/Admin/Moderation/UserSuspensionLifecycleTest.php`
2. `git commit -m "feat(moderation): add centralized suspension domain"`

## Task 6: Create suspension_appeals workflow

**Files:**
- Create: `database/migrations/2026_04_17_100600_create_suspension_appeals_table.php`
- Create: `app/Models/SuspensionAppeal.php`
- Create: `app/Http/Requests/Moderation/SubmitSuspensionAppealRequest.php`
- Create: `app/Http/Requests/Admin/ReviewSuspensionAppealRequest.php`
- Create: `app/Services/Moderation/SuspensionAppealService.php`
- Test: `tests/Feature/Moderation/SuspensionAppealSubmissionTest.php`
- Test: `tests/Feature/Admin/Moderation/SuspensionAppealReviewTest.php`

**Step 1: Write failing appeal submission/review tests**

Cover:
1. default status is `pending_review`
2. temporary/extended suspensions are appealable
3. permanent suspension appeal eligibility is admin-controlled
4. review actions: approve/reject/clarification_requested

**Step 2: Run failing tests**

Run: `php artisan test --filter="SuspensionAppealSubmissionTest|SuspensionAppealReviewTest"`
Expected: FAIL.

**Step 3: Implement minimal appeal schema + service + requests**

1. Add appeal fields and timestamps
2. Enforce eligibility rules and 14-day submission window
3. Implement review transitions and decision notes

**Step 4: Run pass check**

Run: `php artisan test --filter="SuspensionAppealSubmissionTest|SuspensionAppealReviewTest"`
Expected: PASS.

**Step 5: Commit**

Run:
1. `git add database/migrations/2026_04_17_100600_create_suspension_appeals_table.php app/Models/SuspensionAppeal.php app/Http/Requests/Moderation/SubmitSuspensionAppealRequest.php app/Http/Requests/Admin/ReviewSuspensionAppealRequest.php app/Services/Moderation/SuspensionAppealService.php tests/Feature/Moderation/SuspensionAppealSubmissionTest.php tests/Feature/Admin/Moderation/SuspensionAppealReviewTest.php`
2. `git commit -m "feat(moderation): add suspension appeal workflow"`

## Task 7: Create appeal_thread_messages and threaded communication

**Files:**
- Create: `database/migrations/2026_04_17_100700_create_appeal_thread_messages_table.php`
- Create: `app/Models/AppealThreadMessage.php`
- Create: `app/Http/Requests/Moderation/StoreAppealThreadMessageRequest.php`
- Modify: `app/Services/Moderation/SuspensionAppealService.php`
- Test: `tests/Feature/Moderation/AppealThreadMessagingTest.php`

**Step 1: Write failing thread messaging tests**

Cover:
1. messages are attached to appeals only
2. sender roles are validated
3. parent visibility for learner-linked accounts is enforced

**Step 2: Run failing test**

Run: `php artisan test --filter=AppealThreadMessagingTest`
Expected: FAIL.

**Step 3: Implement minimal thread schema + service extension**

1. Add thread message table
2. Add sender role validation and authorization checks
3. Ensure no chat table linkage or chat route reuse

**Step 4: Run pass check**

Run: `php artisan test --filter=AppealThreadMessagingTest`
Expected: PASS.

**Step 5: Commit**

Run:
1. `git add database/migrations/2026_04_17_100700_create_appeal_thread_messages_table.php app/Models/AppealThreadMessage.php app/Http/Requests/Moderation/StoreAppealThreadMessageRequest.php app/Services/Moderation/SuspensionAppealService.php tests/Feature/Moderation/AppealThreadMessagingTest.php`
2. `git commit -m "feat(moderation): add appeal thread messaging"`

## Task 8: Add global suspension middleware and status route guard

**Files:**
- Create: `app/Http/Middleware/CheckUserSuspensionStatus.php`
- Modify: `bootstrap/app.php`
- Modify: `routes/web.php`
- Create: `app/Http/Controllers/Moderation/SuspensionStatusController.php`
- Create: `resources/views/moderation/suspension-status.blade.php`
- Test: `tests/Feature/Moderation/SuspensionMiddlewareEnforcementTest.php`

**Step 1: Write failing middleware tests**

Cover:
1. suspended user is redirected from authenticated routes
2. allowlist routes remain accessible
3. non-suspended users continue normally

**Step 2: Run failing test**

Run: `php artisan test --filter=SuspensionMiddlewareEnforcementTest`
Expected: FAIL.

**Step 3: Implement minimal middleware + route + status page**

1. Register middleware alias and global application to authenticated stacks
2. Add `/suspension-status` route and controller
3. Render reason, severity, duration, case reference, appeal info

**Step 4: Run pass check**

Run: `php artisan test --filter=SuspensionMiddlewareEnforcementTest`
Expected: PASS.

**Step 5: Commit**

Run:
1. `git add app/Http/Middleware/CheckUserSuspensionStatus.php bootstrap/app.php routes/web.php app/Http/Controllers/Moderation/SuspensionStatusController.php resources/views/moderation/suspension-status.blade.php tests/Feature/Moderation/SuspensionMiddlewareEnforcementTest.php`
2. `git commit -m "feat(moderation): enforce suspension middleware globally"`

## Task 9: Implement dual-write adapters for existing moderation sources

**Files:**
- Create: `app/Services/Moderation/ModerationCaseIntakeService.php`
- Create: `app/Services/Moderation/SourceAdapters/ModuleReviewModerationAdapter.php`
- Create: `app/Services/Moderation/SourceAdapters/ChatReportModerationAdapter.php`
- Create: `app/Services/Moderation/SourceAdapters/LearnerReportModerationAdapter.php`
- Create: `app/Services/Moderation/SourceAdapters/InstructorApplicationModerationAdapter.php`
- Modify: `app/Services/ContentGovernanceService.php`
- Modify: `app/Services/ContentReportService.php`
- Modify: `app/Http/Controllers/Chat/MessageController.php`
- Modify: `app/Services/InstructorApplicationService.php`
- Test: `tests/Feature/Moderation/ModerationDualWriteParityTest.php`

**Step 1: Write failing dual-write parity test**

Cover:
1. source flow still writes legacy table behavior
2. same action also creates/modifies centralized moderation case
3. adapter metadata stores source trace context

**Step 2: Run failing test**

Run: `php artisan test --filter=ModerationDualWriteParityTest`
Expected: FAIL.

**Step 3: Implement minimal adapters and service wiring**

1. Add intake service and source adapters
2. Inject central intake into each existing moderation flow
3. Keep legacy behavior intact during transition

**Step 4: Run pass check**

Run: `php artisan test --filter=ModerationDualWriteParityTest`
Expected: PASS.

**Step 5: Commit**

Run:
1. `git add app/Services/Moderation/ModerationCaseIntakeService.php app/Services/Moderation/SourceAdapters/ModuleReviewModerationAdapter.php app/Services/Moderation/SourceAdapters/ChatReportModerationAdapter.php app/Services/Moderation/SourceAdapters/LearnerReportModerationAdapter.php app/Services/Moderation/SourceAdapters/InstructorApplicationModerationAdapter.php app/Services/ContentGovernanceService.php app/Services/ContentReportService.php app/Http/Controllers/Chat/MessageController.php app/Services/InstructorApplicationService.php tests/Feature/Moderation/ModerationDualWriteParityTest.php`
2. `git commit -m "feat(moderation): dual-write existing moderation sources into centralized pipeline"`

## Task 10: Build admin suspension dashboard and moderation UI parity

**Files:**
- Modify: `routes/admin.php`
- Create: `app/Http/Controllers/Admin/ModerationSuspensionController.php`
- Create: `app/Http/Requests/Admin/FilterModerationSuspensionsRequest.php`
- Create: `app/Services/Admin/ModerationSuspensionDashboardService.php`
- Create: `resources/views/admin/moderation/suspensions/index.blade.php`
- Create: `resources/views/admin/moderation/suspensions/show.blade.php`
- Modify: `resources/views/layouts/admin.blade.php`
- Reuse/Modify: `resources/views/admin/partials/table-filter-bar.blade.php`
- Reuse/Modify: `resources/views/admin/partials/row-actions.blade.php`
- Reuse/Modify: `resources/views/admin/partials/table-pagination-footer.blade.php`
- Test: `tests/Feature/Admin/Moderation/AdminSuspensionDashboardUiTest.php`

**Step 1: Write failing admin dashboard UI tests**

Cover:
1. stat cards and table render
2. role/severity/trigger/status filters work
3. search and pagination work
4. Payment Management style markers and class conventions are present

**Step 2: Run failing test**

Run: `php artisan test --filter=AdminSuspensionDashboardUiTest`
Expected: FAIL.

**Step 3: Implement minimal dashboard and Payment-style UI**

1. Add admin moderation routes and controller
2. Add dashboard service for stat cards plus table payload
3. Build Blade screens using Payment-style visual language and table partials

**Step 4: Run pass check**

Run: `php artisan test --filter=AdminSuspensionDashboardUiTest`
Expected: PASS.

**Step 5: Commit**

Run:
1. `git add routes/admin.php app/Http/Controllers/Admin/ModerationSuspensionController.php app/Http/Requests/Admin/FilterModerationSuspensionsRequest.php app/Services/Admin/ModerationSuspensionDashboardService.php resources/views/admin/moderation/suspensions/index.blade.php resources/views/admin/moderation/suspensions/show.blade.php resources/views/layouts/admin.blade.php resources/views/admin/partials/table-filter-bar.blade.php resources/views/admin/partials/row-actions.blade.php resources/views/admin/partials/table-pagination-footer.blade.php tests/Feature/Admin/Moderation/AdminSuspensionDashboardUiTest.php`
2. `git commit -m "feat(admin): add moderation suspensions dashboard with payment-style parity"`

## Task 11: Add appeal submission and admin appeal review interfaces

**Files:**
- Modify: `routes/web.php`
- Modify: `routes/admin.php`
- Create: `app/Http/Controllers/Moderation/SuspensionAppealController.php`
- Create: `app/Http/Controllers/Admin/ModerationAppealController.php`
- Create: `resources/views/moderation/appeals/create.blade.php`
- Create: `resources/views/admin/moderation/appeals/index.blade.php`
- Create: `resources/views/admin/moderation/appeals/show.blade.php`
- Test: `tests/Feature/Moderation/SuspensionAppealUiFlowTest.php`
- Test: `tests/Feature/Admin/Moderation/AdminAppealReviewUiTest.php`

**Step 1: Write failing UI flow tests**

Cover:
1. user can submit appeal from suspension status
2. admin can view and process appeals
3. admin can post thread responses
4. status transitions and messages are visible

**Step 2: Run failing tests**

Run: `php artisan test --filter="SuspensionAppealUiFlowTest|AdminAppealReviewUiTest"`
Expected: FAIL.

**Step 3: Implement minimal controllers + views**

1. Add user appeal create/store routes
2. Add admin appeal list/detail/review/thread routes
3. Render role-safe thread communication views

**Step 4: Run pass check**

Run: `php artisan test --filter="SuspensionAppealUiFlowTest|AdminAppealReviewUiTest"`
Expected: PASS.

**Step 5: Commit**

Run:
1. `git add routes/web.php routes/admin.php app/Http/Controllers/Moderation/SuspensionAppealController.php app/Http/Controllers/Admin/ModerationAppealController.php resources/views/moderation/appeals/create.blade.php resources/views/admin/moderation/appeals/index.blade.php resources/views/admin/moderation/appeals/show.blade.php tests/Feature/Moderation/SuspensionAppealUiFlowTest.php tests/Feature/Admin/Moderation/AdminAppealReviewUiTest.php`
2. `git commit -m "feat(moderation): add user appeal and admin appeal review interfaces"`

## Task 12: Add notifications for moderation lifecycle and appeals

**Files:**
- Create: `app/Notifications/Moderation/EnforcementIssuedNotification.php`
- Create: `app/Notifications/Moderation/SuspensionIssuedNotification.php`
- Create: `app/Notifications/Moderation/AppealSubmittedNotification.php`
- Create: `app/Notifications/Moderation/AppealDecisionNotification.php`
- Modify: `app/Services/Moderation/EnforcementActionService.php`
- Modify: `app/Services/Moderation/SuspensionAppealService.php`
- Test: `tests/Feature/Notifications/ModerationLifecycleNotificationTest.php`

**Step 1: Write failing notification tests**

Cover:
1. enforcement issued sends in-app + email
2. suspension issued sends in-app + email
3. appeal submitted notifies admins
4. appeal decision notifies user

**Step 2: Run failing test**

Run: `php artisan test --filter=ModerationLifecycleNotificationTest`
Expected: FAIL.

**Step 3: Implement minimal notification classes and dispatch points**

1. Add notification payload structure for audit-safe message context
2. Dispatch notifications from service layer only

**Step 4: Run pass check**

Run: `php artisan test --filter=ModerationLifecycleNotificationTest`
Expected: PASS.

**Step 5: Commit**

Run:
1. `git add app/Notifications/Moderation/EnforcementIssuedNotification.php app/Notifications/Moderation/SuspensionIssuedNotification.php app/Notifications/Moderation/AppealSubmittedNotification.php app/Notifications/Moderation/AppealDecisionNotification.php app/Services/Moderation/EnforcementActionService.php app/Services/Moderation/SuspensionAppealService.php tests/Feature/Notifications/ModerationLifecycleNotificationTest.php`
2. `git commit -m "feat(moderation): add lifecycle and appeal notifications"`

## Task 13: Add historical backfill and parity verification tooling

**Files:**
- Create: `app/Console/Commands/BackfillCentralizedModeration.php`
- Create: `app/Services/Moderation/Backfill/CentralizedModerationBackfillService.php`
- Create: `tests/Feature/Console/BackfillCentralizedModerationTest.php`
- Create: `tests/Feature/Moderation/ModerationParityReconciliationTest.php`
- Create: `docs/changelogs/2026-04-17-centralized-moderation-rollout.md`

**Step 1: Write failing backfill and parity tests**

Cover:
1. legacy artifacts backfill into centralized tables
2. idempotent reruns do not duplicate records
3. parity reconciliation reports mismatches clearly

**Step 2: Run failing tests**

Run: `php artisan test --filter="BackfillCentralizedModerationTest|ModerationParityReconciliationTest"`
Expected: FAIL.

**Step 3: Implement minimal command + service**

1. Add backfill command for all historical moderation artifacts
2. Add parity reconciliation checks for case/violation/enforcement counts and status mapping

**Step 4: Run pass check**

Run: `php artisan test --filter="BackfillCentralizedModerationTest|ModerationParityReconciliationTest"`
Expected: PASS.

**Step 5: Commit**

Run:
1. `git add app/Console/Commands/BackfillCentralizedModeration.php app/Services/Moderation/Backfill/CentralizedModerationBackfillService.php tests/Feature/Console/BackfillCentralizedModerationTest.php tests/Feature/Moderation/ModerationParityReconciliationTest.php docs/changelogs/2026-04-17-centralized-moderation-rollout.md`
2. `git commit -m "feat(moderation): add historical backfill and parity reconciliation tooling"`

## Task 14: Final verification suite and cutover readiness checklist

**Files:**
- Create: `docs/plans/2026-04-17-centralized-moderation-cutover-checklist.md`
- Modify: `docs/QUICK_TESTING_GUIDE.md`

**Step 1: Define final verification command pack**

Include exact commands for:
1. moderation source regression
2. automation safety and logging
3. suspension middleware enforcement
4. appeal lifecycle and thread flow
5. admin UI parity pack

**Step 2: Run full targeted suite**

Run:
1. `php artisan test --filter="Moderation|Suspension|Appeal|Automation|ContentReportFlowTest|AdminContentReviewWorkflowTest|AdminInstructorApplicationsUiTest"`

Expected: PASS.

**Step 3: Record real output summary in checklist**

1. copy pass/fail counts
2. note residual risk items
3. mark go/no-go criteria

**Step 4: Commit**

Run:
1. `git add docs/plans/2026-04-17-centralized-moderation-cutover-checklist.md docs/QUICK_TESTING_GUIDE.md`
2. `git commit -m "docs(moderation): add cutover readiness checklist and verification pack"`

## Implementation Notes

1. Keep controllers orchestration-only; business logic in services.
2. Use Form Requests for all new write endpoints.
3. Prefer additive migrations and avoid destructive schema drops in this rollout.
4. Keep dual-write until parity criteria and admin signoff are complete.
5. Preserve strict ownership boundaries for instructor-authored content.
6. Reuse Payment Management UI language and existing admin table partials.

## Required Sequence Compliance

This plan follows required sequence:

1. moderation_cases -> Task 1
2. violations -> Task 2
3. enforcement_actions -> Task 3
4. automation_rule_logs -> Task 4
5. user_suspensions -> Task 5
6. suspension_appeals -> Task 6
7. appeal_thread_messages -> Task 7
8. middleware -> Task 8
9. dashboards -> Task 10
10. notifications -> Task 12

---

Plan complete and saved to `docs/plans/2026-04-17-centralized-moderation-enforcement-suspension-implementation-plan.md`. Two execution options:

**1. Subagent-Driven (this session)** - I dispatch fresh subagent per task, review between tasks, fast iteration

**2. Parallel Session (separate)** - Open new session with executing-plans, batch execution with checkpoints

Which approach?
