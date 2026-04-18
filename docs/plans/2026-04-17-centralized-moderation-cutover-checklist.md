# Centralized Moderation Cutover Readiness Checklist

Date: 2026-04-18
Owner: Platform Moderation Engineering

## 1. Final Verification Command Pack

Run the following command pack before enabling centralized moderation cutover:

```bash
php artisan test --filter="Moderation|Suspension|Appeal|Automation|ContentReportFlowTest|AdminContentReviewWorkflowTest|AdminInstructorApplicationsUiTest"
```

Optional focused commands when diagnosing failures:

```bash
php artisan test --filter="ModerationDualWriteParityTest|BackfillCentralizedModerationTest|ModerationParityReconciliationTest"
php artisan test --filter="SuspensionAppealUiFlowTest|AdminAppealReviewUiTest|ModerationLifecycleNotificationTest"
php artisan moderation:backfill-centralized --reconcile-only
```

## 2. Executed Suite Result (Recorded)

- Command executed: `php artisan test --filter="Moderation|Suspension|Appeal|Automation|ContentReportFlowTest|AdminContentReviewWorkflowTest|AdminInstructorApplicationsUiTest"`
- Result: PASS
- Tests: 80 passed
- Assertions: 249
- Duration: 30.37s

## 3. Residual Risk Notes

- Backfill module review records require a resolvable actor (`submitted_by` or module owner). Records without a valid actor are skipped and reported by the command.
- Lifecycle notifications currently dispatch synchronously (`mail` + `database`) and are not queued; monitor latency if volume increases.
- Dual-write remains active by design. Keep it enabled until post-cutover parity runs consistently report zero mismatches.

## 4. Go / No-Go Criteria

- [x] Core moderation schema and service suites pass
- [x] Suspension middleware and appeal lifecycle suites pass
- [x] Admin suspension and appeal UI suites pass
- [x] Dual-write parity suite passes
- [x] Backfill + reconciliation tooling passes
- [x] Notification lifecycle suite passes

Decision: GO for controlled rollout, with parity reconciliation checks required before switching off dual-write.
