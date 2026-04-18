# Financial Reporting and Revenue Analytics Implementation Plan

> For Claude: REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

Goal: Implement a centralized, deterministic financial reporting layer for admin and instructor flows with unified aggregation logic, trend analytics, and exportable reports (PDF, CSV, XLSX).

Architecture: Build a Finance service layer that is the single source of truth for all reporting metrics and trend datasets. Keep controllers thin with Form Request validation and strict RBAC checks, then feed the same service payload to UI and export formatters. Add audit logs and additive performance indexes to preserve traceability and scalability.

Tech Stack: Laravel 12, PHP 8.2, Blade, Alpine.js, Tailwind CSS v3, Chart.js, barryvdh/laravel-dompdf, maatwebsite/excel, PHPUnit.

---

I am using the writing-plans skill to create the implementation plan.

## Task 1: Add finance permission model and route placeholders

Files:
- Modify: database/seeders/PermissionSeeder.php
- Modify: database/seeders/RoleSeeder.php
- Modify: routes/admin.php
- Modify: routes/instructor.php
- Test: tests/Feature/Finance/FinanceReportingRouteAccessTest.php

Step 1: Write the failing test
- Add route access tests that assert:
  - Admin with financial report permission can access admin financial report page.
  - Admin without permission is forbidden.
  - Instructor with own financial permission can access instructor report page only.
  - Instructor cannot access admin report routes.

Step 2: Run test to verify it fails
- Run: php artisan test --filter=FinanceReportingRouteAccessTest
- Expected: FAIL due to missing permissions and routes.

Step 3: Write minimal implementation
- Add permissions:
  - view financial reports
  - export financial reports
  - view own financial reports
  - export own financial reports
- Assign admin full reporting permissions.
- Assign instructor own-scope reporting permissions.
- Add placeholder named routes for admin and instructor financial reports.

Step 4: Run test to verify it passes
- Run: php artisan test --filter=FinanceReportingRouteAccessTest
- Expected: PASS.

Step 5: Commit
- git add database/seeders/PermissionSeeder.php database/seeders/RoleSeeder.php routes/admin.php routes/instructor.php tests/Feature/Finance/FinanceReportingRouteAccessTest.php
- git commit -m "feat(finance): add reporting permissions and route access guards"

## Task 2: Install and configure spreadsheet export package

Files:
- Modify: composer.json
- Modify: composer.lock
- Optional Create: config/excel.php
- Test: tests/Feature/Finance/FinanceExportPackageBootTest.php

Step 1: Write the failing test
- Add a boot test that resolves Excel facade or export contract.

Step 2: Run test to verify it fails
- Run: php artisan test --filter=FinanceExportPackageBootTest
- Expected: FAIL due to missing package bindings.

Step 3: Write minimal implementation
- Install package:
  - composer require maatwebsite/excel
- Publish config only if needed by project conventions.

Step 4: Run test to verify it passes
- Run: php artisan test --filter=FinanceExportPackageBootTest
- Expected: PASS.

Step 5: Commit
- git add composer.json composer.lock config/excel.php tests/Feature/Finance/FinanceExportPackageBootTest.php
- git commit -m "chore(finance): add spreadsheet export dependency"

## Task 3: Add report generation audit table and performance indexes

Files:
- Create: database/migrations/2026_04_17_100000_create_report_generation_logs_table.php
- Create: database/migrations/2026_04_17_100100_add_finance_reporting_indexes.php
- Test: tests/Feature/Finance/FinanceReportingSchemaTest.php

Step 1: Write the failing test
- Assert report_generation_logs table exists with required columns.
- Assert expected indexes exist on payments, refunds, and module_sale_ledgers.

Step 2: Run test to verify it fails
- Run: php artisan test --filter=FinanceReportingSchemaTest
- Expected: FAIL due to missing migration artifacts.

Step 3: Write minimal implementation
- Create report_generation_logs with generated metadata and filter snapshot fields.
- Add additive indexes:
  - payments(status, archived_at, paid_at)
  - payments(paid_at)
  - refunds(status, processed_at)
  - module_sale_ledgers(sale_status, occurred_at)
  - module_sale_ledgers(occurred_at)

Step 4: Run test to verify it passes
- Run: php artisan test --filter=FinanceReportingSchemaTest
- Expected: PASS.

Step 5: Commit
- git add database/migrations/2026_04_17_100000_create_report_generation_logs_table.php database/migrations/2026_04_17_100100_add_finance_reporting_indexes.php tests/Feature/Finance/FinanceReportingSchemaTest.php
- git commit -m "feat(finance): add report audit log table and reporting indexes"

## Task 4: Add finance DTO and filter normalizer

Files:
- Create: app/Services/Finance/FinancialReportFilterNormalizer.php
- Create: app/Support/Finance/FinancialReportFilter.php
- Test: tests/Unit/Finance/FinancialReportFilterNormalizerTest.php

Step 1: Write the failing test
- Cover normalization of weekly, monthly, yearly, and custom ranges.
- Assert Asia/Manila timezone conversion and UTC query boundaries.
- Assert invalid range and over-limit custom range failures.

Step 2: Run test to verify it fails
- Run: php artisan test --filter=FinancialReportFilterNormalizerTest
- Expected: FAIL because filter normalizer classes do not exist.

Step 3: Write minimal implementation
- Add a normalized filter object with resolved date range and granularity.
- Implement one normalization path used by all reporting consumers.

Step 4: Run test to verify it passes
- Run: php artisan test --filter=FinancialReportFilterNormalizerTest
- Expected: PASS.

Step 5: Commit
- git add app/Services/Finance/FinancialReportFilterNormalizer.php app/Support/Finance/FinancialReportFilter.php tests/Unit/Finance/FinancialReportFilterNormalizerTest.php
- git commit -m "feat(finance): add normalized report filter contract"

## Task 5: Create FinancialReportService as single source of truth

Files:
- Create: app/Services/Finance/FinancialReportService.php
- Test: tests/Unit/Finance/FinancialReportServiceSummaryTest.php
- Test: tests/Unit/Finance/FinancialReportServiceBreakdownTest.php

Step 1: Write the failing test
- Validate formulas:
  - Gross revenue from completed non-archived payments.
  - Net revenue equals gross minus completed refunds.
  - Subscription revenue from completed subscription-scope payments.
  - Module revenue, platform earnings, instructor earnings from ledger.
- Validate deterministic same-input same-output behavior.

Step 2: Run test to verify it fails
- Run: php artisan test --filter=FinancialReportService
- Expected: FAIL due to missing service implementation.

Step 3: Write minimal implementation
- Implement methods:
  - getSummary(filters)
  - getRevenueBreakdown(filters)
  - getInstructorEarnings(filters)
- Ensure no blade/controller recomputation is needed.

Step 4: Run test to verify it passes
- Run: php artisan test --filter=FinancialReportService
- Expected: PASS.

Step 5: Commit
- git add app/Services/Finance/FinancialReportService.php tests/Unit/Finance/FinancialReportServiceSummaryTest.php tests/Unit/Finance/FinancialReportServiceBreakdownTest.php
- git commit -m "feat(finance): add centralized financial report service"

## Task 6: Add chart dataset builder and granularity mapping

Files:
- Create: app/Services/Finance/FinancialTrendDatasetBuilder.php
- Modify: app/Services/Finance/FinancialReportService.php
- Test: tests/Unit/Finance/FinancialTrendDatasetBuilderTest.php

Step 1: Write the failing test
- Assert granularity mapping:
  - Weekly maps to day buckets.
  - Monthly maps to week buckets.
  - Yearly maps to month buckets.
- Assert sorted labels and aligned dataset lengths.

Step 2: Run test to verify it fails
- Run: php artisan test --filter=FinancialTrendDatasetBuilderTest
- Expected: FAIL because trend builder is missing.

Step 3: Write minimal implementation
- Build deterministic chart labels and values from normalized ranges.
- Integrate into service output payload.

Step 4: Run test to verify it passes
- Run: php artisan test --filter=FinancialTrendDatasetBuilderTest
- Expected: PASS.

Step 5: Commit
- git add app/Services/Finance/FinancialTrendDatasetBuilder.php app/Services/Finance/FinancialReportService.php tests/Unit/Finance/FinancialTrendDatasetBuilderTest.php
- git commit -m "feat(finance): add trend dataset builder for report charts"

## Task 7: Add admin reporting requests and controller

Files:
- Create: app/Http/Requests/Admin/FinancialReportFilterRequest.php
- Create: app/Http/Requests/Admin/FinancialReportExportRequest.php
- Create: app/Http/Controllers/Admin/FinancialReportController.php
- Modify: routes/admin.php
- Test: tests/Feature/Admin/AdminFinancialReportControllerTest.php

Step 1: Write the failing test
- Assert admin report page returns expected service payload.
- Assert invalid filter inputs are rejected.
- Assert custom range guard is enforced.

Step 2: Run test to verify it fails
- Run: php artisan test --filter=AdminFinancialReportControllerTest
- Expected: FAIL because request and controller classes do not exist.

Step 3: Write minimal implementation
- Keep controller thin and delegate all calculations to FinancialReportService.
- Add route names:
  - admin.financial-reports.index
  - admin.financial-reports.export

Step 4: Run test to verify it passes
- Run: php artisan test --filter=AdminFinancialReportControllerTest
- Expected: PASS.

Step 5: Commit
- git add app/Http/Requests/Admin/FinancialReportFilterRequest.php app/Http/Requests/Admin/FinancialReportExportRequest.php app/Http/Controllers/Admin/FinancialReportController.php routes/admin.php tests/Feature/Admin/AdminFinancialReportControllerTest.php
- git commit -m "feat(admin): add financial report controller and validated filters"

## Task 8: Build admin financial reporting UI and chart rendering

Files:
- Create: resources/views/admin/financial-reports/index.blade.php
- Create: resources/views/admin/financial-reports/partials/chart-scripts.blade.php
- Modify: resources/views/layouts/admin.blade.php (navigation entry)
- Test: tests/Feature/Admin/AdminFinancialReportPageUiTest.php

Step 1: Write the failing test
- Assert page contains required cards:
  - Total Revenue
  - Subscription Revenue
  - Module Revenue
  - Platform Earnings
- Assert filter controls and export actions are present.

Step 2: Run test to verify it fails
- Run: php artisan test --filter=AdminFinancialReportPageUiTest
- Expected: FAIL because view and nav entry do not exist.

Step 3: Write minimal implementation
- Follow payment management visual shell and spacing conventions.
- Render chart payload passed by service (no UI-side financial math).

Step 4: Run test to verify it passes
- Run: php artisan test --filter=AdminFinancialReportPageUiTest
- Expected: PASS.

Step 5: Commit
- git add resources/views/admin/financial-reports/index.blade.php resources/views/admin/financial-reports/partials/chart-scripts.blade.php resources/views/layouts/admin.blade.php tests/Feature/Admin/AdminFinancialReportPageUiTest.php
- git commit -m "feat(admin-ui): add financial reporting dashboard page"

## Task 9: Implement export formatters and PDF template

Files:
- Create: app/Exports/FinancialReportSummaryExport.php
- Create: app/Exports/FinancialReportBreakdownExport.php
- Create: app/Services/Finance/FinancialReportExportService.php
- Create: resources/views/admin/financial-reports/pdf/summary.blade.php
- Modify: app/Http/Controllers/Admin/FinancialReportController.php
- Test: tests/Feature/Admin/AdminFinancialReportExportTest.php

Step 1: Write the failing test
- Assert PDF export downloads with expected content markers.
- Assert CSV and XLSX endpoints return downloadable files.
- Assert exports use same summary totals as page payload for same filters.

Step 2: Run test to verify it fails
- Run: php artisan test --filter=AdminFinancialReportExportTest
- Expected: FAIL because export handlers are missing.

Step 3: Write minimal implementation
- Implement format-specific export paths that call FinancialReportService exactly once.
- Add consistent column naming for CSV/XLSX outputs.

Step 4: Run test to verify it passes
- Run: php artisan test --filter=AdminFinancialReportExportTest
- Expected: PASS.

Step 5: Commit
- git add app/Exports/FinancialReportSummaryExport.php app/Exports/FinancialReportBreakdownExport.php app/Services/Finance/FinancialReportExportService.php resources/views/admin/financial-reports/pdf/summary.blade.php app/Http/Controllers/Admin/FinancialReportController.php tests/Feature/Admin/AdminFinancialReportExportTest.php
- git commit -m "feat(finance): add admin financial report exports"

## Task 10: Add report generation audit logging

Files:
- Create: app/Models/ReportGenerationLog.php
- Modify: app/Http/Controllers/Admin/FinancialReportController.php
- Modify: app/Services/AdminActivityLogService.php (only if helper method is needed)
- Test: tests/Feature/Admin/AdminFinancialReportAuditLogTest.php

Step 1: Write the failing test
- Assert report view generation writes an audit row.
- Assert each export format writes an audit row with filters snapshot.

Step 2: Run test to verify it fails
- Run: php artisan test --filter=AdminFinancialReportAuditLogTest
- Expected: FAIL because log write path is missing.

Step 3: Write minimal implementation
- Write report generation metadata for page and export actions.
- Include generated_by, format, normalized filters, and summary snapshot.

Step 4: Run test to verify it passes
- Run: php artisan test --filter=AdminFinancialReportAuditLogTest
- Expected: PASS.

Step 5: Commit
- git add app/Models/ReportGenerationLog.php app/Http/Controllers/Admin/FinancialReportController.php app/Services/AdminActivityLogService.php tests/Feature/Admin/AdminFinancialReportAuditLogTest.php
- git commit -m "feat(finance): add report generation audit logging"

## Task 11: Enhance instructor earnings reporting with shared service filters

Files:
- Create: app/Http/Requests/Instructor/InstructorFinancialReportFilterRequest.php
- Modify: app/Http/Controllers/Instructor/ModuleEarningsController.php
- Modify: routes/instructor.php
- Test: tests/Feature/Instructor/InstructorFinancialReportFilteringTest.php

Step 1: Write the failing test
- Assert instructor can filter by weekly, monthly, yearly, custom ranges.
- Assert totals are computed from FinancialReportService and scoped to instructor.
- Assert admin-only metrics are not exposed.

Step 2: Run test to verify it fails
- Run: php artisan test --filter=InstructorFinancialReportFilteringTest
- Expected: FAIL due to missing filter request and service integration.

Step 3: Write minimal implementation
- Replace inline aggregate calculations in instructor controller with FinancialReportService calls.
- Preserve existing capability check and visibility behavior.

Step 4: Run test to verify it passes
- Run: php artisan test --filter=InstructorFinancialReportFilteringTest
- Expected: PASS.

Step 5: Commit
- git add app/Http/Requests/Instructor/InstructorFinancialReportFilterRequest.php app/Http/Controllers/Instructor/ModuleEarningsController.php routes/instructor.php tests/Feature/Instructor/InstructorFinancialReportFilteringTest.php
- git commit -m "refactor(instructor): use centralized financial service for earnings reporting"

## Task 12: Add instructor report exports

Files:
- Create: app/Http/Controllers/Instructor/InstructorFinancialReportExportController.php
- Modify: routes/instructor.php
- Modify: resources/views/instructor/earnings/index.blade.php
- Create: resources/views/instructor/earnings/pdf/summary.blade.php
- Test: tests/Feature/Instructor/InstructorFinancialReportExportTest.php

Step 1: Write the failing test
- Assert instructor can export own report in PDF, CSV, XLSX.
- Assert export contains only instructor-scoped data.

Step 2: Run test to verify it fails
- Run: php artisan test --filter=InstructorFinancialReportExportTest
- Expected: FAIL because export route/controller is missing.

Step 3: Write minimal implementation
- Reuse FinancialReportService and export service with instructor scope.
- Add export controls to instructor earnings page.

Step 4: Run test to verify it passes
- Run: php artisan test --filter=InstructorFinancialReportExportTest
- Expected: PASS.

Step 5: Commit
- git add app/Http/Controllers/Instructor/InstructorFinancialReportExportController.php routes/instructor.php resources/views/instructor/earnings/index.blade.php resources/views/instructor/earnings/pdf/summary.blade.php tests/Feature/Instructor/InstructorFinancialReportExportTest.php
- git commit -m "feat(instructor): add scoped financial report exports"

## Task 13: Add deterministic regression and parity tests

Files:
- Create: tests/Feature/Finance/FinancialReportDeterminismTest.php
- Create: tests/Feature/Finance/FinancialReportUiExportParityTest.php
- Modify: tests/Feature/Admin/AdminModuleRevenueDashboardTest.php (if required for compatibility assertions)
- Modify: tests/Feature/Instructor/ModuleEarningsPageTest.php (if required)

Step 1: Write the failing test
- Assert repeated same filter payload returns same summary hash.
- Assert on-screen summary values match exported summary values.

Step 2: Run test to verify it fails
- Run: php artisan test --filter=FinancialReportDeterminismTest
- Run: php artisan test --filter=FinancialReportUiExportParityTest
- Expected: FAIL until parity and determinism are fully enforced.

Step 3: Write minimal implementation
- Stabilize output ordering and final rounding positions.
- Ensure all surfaces source metrics from same service payload.

Step 4: Run test to verify it passes
- Run: php artisan test --filter=FinancialReportDeterminismTest
- Run: php artisan test --filter=FinancialReportUiExportParityTest
- Expected: PASS.

Step 5: Commit
- git add tests/Feature/Finance/FinancialReportDeterminismTest.php tests/Feature/Finance/FinancialReportUiExportParityTest.php tests/Feature/Admin/AdminModuleRevenueDashboardTest.php tests/Feature/Instructor/ModuleEarningsPageTest.php
- git commit -m "test(finance): enforce deterministic and parity guarantees"

## Task 14: Run targeted and full verification, then document rollout

Files:
- Create: docs/changelogs/2026-04-17-financial-reporting-analytics.md
- Modify: docs/plans/2026-04-17-financial-reporting-analytics-implementation-plan.md (mark completion notes after execution)

Step 1: Run targeted finance test suite
- Run:
  - php artisan test --filter=FinanceReportingRouteAccessTest
  - php artisan test --filter=AdminFinancialReport
  - php artisan test --filter=InstructorFinancialReport
  - php artisan test --filter=FinancialReportDeterminismTest
- Expected: PASS.

Step 2: Run regression anchors
- Run:
  - php artisan test --filter=AdminModuleRevenueDashboardTest
  - php artisan test --filter=PlanManagementFlowTest
  - php artisan test --filter=LearnerPaidModulePurchaseFlowTest
- Expected: PASS.

Step 3: Run full suite if time budget allows
- Run: php artisan test
- Expected: PASS or documented pre-existing failures only.

Step 4: Document implementation evidence
- Write changelog with:
  - delivered scope
  - test commands and actual outcomes
  - residual risks

Step 5: Commit
- git add docs/changelogs/2026-04-17-financial-reporting-analytics.md docs/plans/2026-04-17-financial-reporting-analytics-implementation-plan.md
- git commit -m "docs(finance): record financial reporting rollout and verification"

---

Plan complete and saved to docs/plans/2026-04-17-financial-reporting-analytics-implementation-plan.md.

Two execution options:

1. Subagent-Driven (this session)
- Dispatch a fresh subagent per task with review gates between tasks for faster iteration.

2. Parallel Session (separate)
- Open a dedicated session using executing-plans for focused batched implementation with checkpoints.

Which approach?
