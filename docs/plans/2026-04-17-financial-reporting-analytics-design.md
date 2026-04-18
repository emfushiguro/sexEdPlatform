# Financial Reporting and Revenue Analytics Design

## 1. Purpose and Goals
Build a centralized financial reporting and analytics layer that unifies revenue data from payments, subscriptions, and module revenue into deterministic, time-filtered, exportable reports for admin and instructor flows.

Primary goals:
- Centralize financial computation logic in one service layer.
- Deliver accurate and deterministic totals for the same input filters.
- Provide actionable admin analytics with chart trends and clean dashboard summaries.
- Provide instructor earnings transparency using the same core calculations.
- Support professional exports (PDF, CSV, XLSX) without duplicating logic.
- Maintain clean architecture separation across Service, UI, and Export layers.

## 2. Approved Product Decisions
The following choices are approved and locked:
- Gross Revenue: completed, non-archived payments only.
- Net Revenue: gross revenue minus completed refunds in the same date window.
- Date field mapping:
  - Payments use paid_at.
  - Module revenue uses occurred_at.
  - Refunds use processed_at.
- Subscription Revenue uses cash basis from completed subscription-scope payments.
- Instructor hidden rows behavior:
  - Hidden rows are excluded from instructor table listing.
  - Hidden rows remain included in totals and exports for reporting consistency.
- Export execution: synchronous with a strict maximum custom-range limit.
- RBAC model: dedicated financial report permissions.
- Audit model: dedicated report generation log table plus existing activity logs.
- Trend granularity:
  - Weekly report: day-level points.
  - Monthly report: week-level points.
  - Yearly report: month-level points.
- Refund policy note:
  - Current terms do not operationally support refunds for subscriptions or module purchases.
  - Reporting logic remains refund-ready for future policy activation.

## 3. Scope
In scope:
- Admin Financial Reports and Revenue Analytics page.
- FinancialReportService as single source of truth.
- Time-based filtering: weekly, monthly, yearly, custom.
- Summary cards and trend visualization (Chart.js).
- Export support: PDF, CSV, XLSX.
- Instructor earnings reporting enhancements and export support.
- Audit and traceability logging for generated reports.
- Performance safeguards, deterministic behavior, and index hardening.

Out of scope:
- Forecasting and predictive financial modeling.
- External BI warehouse or ETL stack.
- Major redesign of payout operations.
- Cross-tenant or multi-region finance partitions.

## 4. Current State Findings
Current implementation already has financial domains, but calculations are fragmented:
- Payment totals are computed in admin payment controller.
- Module revenue totals are computed in module revenue controller.
- Instructor earnings totals are computed in instructor controller.
- Existing analytics service has generalized revenue metrics but does not serve as the financial reporting source of truth.

Additional findings:
- Timezone is currently UTC in app config.
- PDF package is installed (barryvdh/laravel-dompdf).
- CSV and XLSX package is not yet installed.
- No unified report contract currently exists across dashboard and export flows.

## 5. Target Architecture (Approach 3: Hybrid Deterministic)
### 5.1 Core Pattern
Use one centralized service for all financial calculations, then project that data into UI and export outputs.

Layer responsibilities:
- Service layer:
  - Aggregate and normalize all financial metrics.
  - Apply filter contracts and timezone boundaries.
  - Return structured deterministic payloads.
- Controller layer:
  - Validate input.
  - Enforce authorization.
  - Orchestrate response rendering or export dispatch.
- View layer:
  - Render already-calculated values only.
- Export layer:
  - Format service payload into PDF, CSV, XLSX.

### 5.2 Optional Performance Layer
Hybrid behavior:
- Live query aggregation for normal ranges.
- Optional cached summary payload for larger ranges or repeated requests.
- Deterministic cache keys derived from normalized filters and scope.

## 6. Canonical Financial Definitions
The service defines and owns these metrics:
- Gross Revenue:
  - Sum of payments.amount where status is completed and archived_at is null.
- Net Revenue:
  - Gross Revenue minus sum of completed refunds in the same reporting range.
- Subscription Revenue:
  - Sum of completed payments where payment scope is subscription.
- Module Revenue:
  - Sum of module_sale_ledgers.gross_amount where sale_status is not archived.
- Platform Earnings (Commission):
  - Sum of module_sale_ledgers.commission_amount.
- Instructor Earnings:
  - Sum of module_sale_ledgers.instructor_earnings_amount.

Determinism rules:
- Same normalized filter input returns same output.
- Currency rounded to two decimals at output stage.
- Grouped and trend outputs have stable ordering.

## 7. Unified Filter Contract
All report consumers use one filter payload shape:
- report_type: weekly, monthly, yearly, custom
- date_from
- date_to
- timezone: default Asia/Manila
- instructor_id (optional for scoped reporting)
- module_id (optional for drill-down)

Validation rules:
- report_type required and limited to supported values.
- date_from and date_to required for custom range.
- date_from must be less than or equal to date_to.
- custom range capped by configurable maximum (example: 365 days).

## 8. Timezone and Window Normalization
Timezone standard for reporting is Asia/Manila.

Normalization approach:
- Convert requested local-date boundaries to precise UTC query windows.
- Use source-specific date fields:
  - paid_at for payments.
  - occurred_at for module ledgers.
  - processed_at for refunds.
- Apply inclusive full-day boundaries for custom ranges.

Outcome:
- Consistent boundaries across all sources.
- No day drift between displayed range and queried data.

## 9. Service Design: FinancialReportService
Create:
- app/Services/Finance/FinancialReportService.php

Required public methods:
- getSummary(array filters): array
- getRevenueBreakdown(array filters): array
- getInstructorEarnings(array filters): array

Supporting responsibilities:
- Normalize and validate effective filter payload.
- Query financial sources using deterministic predicates.
- Build chart trend datasets by approved granularity mapping.
- Build grouped breakdowns for modules and instructors.
- Return one structured payload format reusable by UI and exports.

## 10. Admin Reporting Module
Add new admin module:
- Admin > Financial Reports / Revenue Analytics

Primary UI blocks:
- Filter bar:
  - Report type selector
  - Date range selector
- Stat cards:
  - Total Revenue
  - Subscription Revenue
  - Module Revenue
  - Platform Earnings
- Chart panel:
  - Revenue trend over time
  - Optional composition chart
- Breakdown tables:
  - Revenue by source
  - Top instructors
  - Top paid modules
- Export action bar:
  - PDF, CSV, XLSX

Design language:
- Follow current Payment Management shell and table standards.
- Preserve spacing, typography, and brand palette conventions.

## 11. Export Architecture
### 11.1 Separation Contract
- Service computes data.
- Export classes format data.
- Export templates render presentation.

Do not:
- Recompute totals inside Blade export templates.
- Reuse UI Blade directly as export source.

### 11.2 Supported Formats
- PDF:
  - Use barryvdh/laravel-dompdf.
  - Include title, range, timezone, summary totals, and breakdown sections.
- CSV and XLSX:
  - Use maatwebsite/excel.
  - Use stable columns suitable for spreadsheet analysis.

### 11.3 Export Determinism
- Exports use same service payload used by on-screen report.
- Any export output differences are formatting-only, not computation differences.

## 12. Instructor Reporting Enhancements
Extend instructor earnings reporting:
- Add weekly, monthly, yearly, custom filtering.
- Add total earnings and per-module breakdown views.
- Add export support for instructor scoped data.

Consistency guarantees:
- Instructor metrics come from FinancialReportService.
- Calculation formulas match admin-side definitions.
- Instructor visibility control remains applied to row display only, while totals and exports remain consistent with approved rule.

## 13. RBAC and Authorization Boundaries
Add permissions:
- view financial reports
- export financial reports
- view own financial reports
- export own financial reports

Authorization rules:
- Admin routes require admin financial report permissions.
- Instructor routes require own-scope permissions and strict instructor scoping.
- Service-level scoping is enforced even if route checks pass.

Route ownership:
- Admin routes in routes/admin.php.
- Instructor routes in routes/instructor.php.

## 14. Data Integrity and Performance
Integrity safeguards:
- Avoid double-counting between payment totals and module split analytics.
- Exclude archived payment records from gross/net calculations.
- Keep source-specific status filters explicit and documented.

Performance safeguards:
- Eager load only where needed for breakdown tables.
- Use aggregate SQL queries for sums and grouped trends.
- Add additive indexes for financial query paths:
  - payments(status, archived_at, paid_at)
  - payments(paid_at)
  - refunds(status, processed_at)
  - module_sale_ledgers(sale_status, occurred_at)
  - module_sale_ledgers(occurred_at)

## 15. Audit and Traceability
Add a dedicated report generation log table.

Proposed table: report_generation_logs
Fields:
- generated_at
- generated_by_user_id
- generated_by_role
- report_scope
- export_format
- filters_json
- checksum_hash
- row_count
- summary_snapshot_json
- created_at, updated_at

Additional logging:
- Admin generation actions also recorded through existing admin activity logging service.

## 16. Error Handling and UX Behavior
Expected failure and edge states:
- Invalid date combinations.
- Unsupported report type.
- Date range exceeds allowed window.
- Empty datasets.
- Export writer failure.

UX behavior:
- Show clear validation messages.
- Show empty-state cards and tables with zeroed metrics.
- Keep export controls available but disable on invalid filter input.

## 17. Testing Strategy
Unit tests:
- Service metric formulas.
- Filter normalization and timezone conversion.
- Trend bucket generation and ordering.
- Deterministic same-input same-output behavior.

Feature tests:
- Admin report page authorization and rendering.
- Instructor report scope isolation.
- Export endpoint behavior by format and role.
- Audit log write assertions.

Regression tests:
- Existing payment management pages remain stable.
- Existing module revenue pages remain stable.
- Existing instructor earnings baseline flows remain stable.

## 18. Delivery Phasing
Phase 1:
- FinancialReportService, filter requests, and admin report summary page.

Phase 2:
- Chart datasets, PDF export, CSV/XLSX export, audit logging.

Phase 3:
- Instructor report enhancements and instructor export.

Phase 4:
- Performance hardening, index rollout, and full regression verification.

## 19. Risks and Mitigations
Risk: metric mismatch between screens and exports
- Mitigation: single service payload source for all surfaces.

Risk: timezone boundary drift
- Mitigation: centralized filter normalization and boundary tests.

Risk: heavy exports on broad ranges
- Mitigation: strict max range and optimized indexes.

Risk: instructor scope leakage
- Mitigation: route permissions plus service-level instructor scoping.

## 20. Acceptance Criteria
- Admin can generate weekly, monthly, yearly, and custom reports.
- Financial totals are consistent across dashboard and exports.
- Exports are available in PDF, CSV, XLSX with clean structure.
- Instructor can view and export own earnings with matching formulas.
- Financial computations are centralized in FinancialReportService only.
- Report generation events are logged for audit traceability.
- Relevant tests validate correctness, authorization, and determinism.
