# Admin Module Review System Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Deliver a complete admin module moderation workspace that supports full package inspection, approve/reject decisions, automatic violation recording on rejection, and enforceable instructor warning/penalty restrictions before learner publication.

**Architecture:** Extend the existing content governance lifecycle (`module_revisions`, `module_review_requests`, `ContentGovernanceService`) rather than replacing it. Keep review decisions snapshot-anchored for integrity, and add selective lazy loading for heavy topic/quiz preview payloads. Implement restrictions with backend hard enforcement and matching instructor UI disable states.

**Tech Stack:** Laravel 12, PHP 8.2, Blade, Alpine.js, Tailwind CSS, Spatie roles/permissions, PHPUnit Feature/Unit tests

---

## Task 1: Add Moderation Profile and Violation History Schema

**Files:**
- Create: `database/migrations/2026_03_30_000001_create_instructor_moderation_profiles_table.php`
- Create: `database/migrations/2026_03_30_000002_create_instructor_violation_histories_table.php`
- Test: `tests/Feature/Admin/AdminInstructorModerationSchemaTest.php`

**Step 1: Write the failing test**

Create schema tests asserting:
1. `instructor_moderation_profiles` table exists.
2. `instructor_violation_histories` table exists.
3. Required columns exist: warning count, restriction status, restriction window, last violation date, escalation level.
4. Violation table links instructor + review request + module with reason/guidance snapshot fields.

**Step 2: Run test to verify it fails**

Run:
`php artisan test --filter=AdminInstructorModerationSchemaTest`

Expected:
- FAIL because moderation profile and violation history tables do not exist.

**Step 3: Write minimal implementation**

Add migrations with:
1. `instructor_moderation_profiles`
   - `user_id` (unique foreign key to users)
   - `warning_count` (unsigned integer default 0)
   - `current_restriction_status` (nullable string)
   - `restriction_starts_at`, `restriction_ends_at` (nullable timestamps)
   - `last_violation_at` (nullable timestamp)
   - `escalation_level` (unsigned tiny integer default 0)
2. `instructor_violation_histories`
   - `user_id`
   - `module_id`
   - `module_review_request_id`
   - `reason_code`
   - `guidance_note`
   - `violation_sequence`
   - `suggested_penalty_action`
   - `confirmed_penalty_action`
   - `confirmed_by_admin_id`
   - timestamps

**Step 4: Run test to verify it passes**

Run:
`php artisan test --filter=AdminInstructorModerationSchemaTest`

Expected:
- PASS.

**Step 5: Commit**

Run:
`git add database/migrations/2026_03_30_000001_create_instructor_moderation_profiles_table.php database/migrations/2026_03_30_000002_create_instructor_violation_histories_table.php tests/Feature/Admin/AdminInstructorModerationSchemaTest.php`
`git commit -m "feat: add instructor moderation profile and violation schema"`

---

## Task 2: Add Models, Relationships, and Reason/Penalty Enums

**Files:**
- Create: `app/Models/InstructorModerationProfile.php`
- Create: `app/Models/InstructorViolationHistory.php`
- Create: `app/Enums/ModuleReviewRejectionReason.php`
- Create: `app/Enums/InstructorRestrictionAction.php`
- Modify: `app/Models/User.php`
- Modify: `app/Models/ModuleReviewRequest.php`
- Test: `tests/Unit/Models/InstructorModerationRelationshipsTest.php`

**Step 1: Write the failing test**

Add relationship tests for:
1. `User::moderationProfile()`.
2. `User::violationHistories()`.
3. `ModuleReviewRequest::violationRecords()`.
4. Enum reason codes include required preset categories.

**Step 2: Run test to verify it fails**

Run:
`php artisan test --filter=InstructorModerationRelationshipsTest`

Expected:
- FAIL because models/relationships/enums do not exist.

**Step 3: Write minimal implementation**

Implement models and relations with casts for date and integer fields. Add enum values:
1. `inaccurate_educational_information`
2. `inappropriate_content`
3. `low_quality_lessons`
4. `missing_content`
5. `quiz_errors`
6. `poor_module_structure`
7. `other`

Add restriction actions enum values:
1. `warning_only`
2. `restrict_3_days`
3. `restrict_14_days`
4. `suspension_review`

**Step 4: Run test to verify it passes**

Run:
`php artisan test --filter=InstructorModerationRelationshipsTest`

Expected:
- PASS.

**Step 5: Commit**

Run:
`git add app/Models/InstructorModerationProfile.php app/Models/InstructorViolationHistory.php app/Enums/ModuleReviewRejectionReason.php app/Enums/InstructorRestrictionAction.php app/Models/User.php app/Models/ModuleReviewRequest.php tests/Unit/Models/InstructorModerationRelationshipsTest.php`
`git commit -m "feat: add moderation models relationships and enums"`

---

## Task 3: Extend Reject Validation Contract for Structured Moderation Input

**Files:**
- Modify: `app/Http/Requests/Admin/RejectModuleReviewRequest.php`
- Test: `tests/Feature/Admin/ModuleReviewRejectValidationTest.php`

**Step 1: Write the failing test**

Add feature tests asserting reject endpoint requires:
1. `reason_code` from allowed enum values.
2. `guidance_note` required and non-empty.
3. Reject request fails when either field is missing.

**Step 2: Run test to verify it fails**

Run:
`php artisan test --filter=ModuleReviewRejectValidationTest`

Expected:
- FAIL because current request validates only `feedback`.

**Step 3: Write minimal implementation**

Update request rules to:
1. Require `reason_code` with enum validation.
2. Require `guidance_note` as string (max length set by project pattern).
3. Keep backward compatibility by mapping composed feedback downstream if required.

**Step 4: Run test to verify it passes**

Run:
`php artisan test --filter=ModuleReviewRejectValidationTest`

Expected:
- PASS.

**Step 5: Commit**

Run:
`git add app/Http/Requests/Admin/RejectModuleReviewRequest.php tests/Feature/Admin/ModuleReviewRejectValidationTest.php`
`git commit -m "feat: enforce structured module rejection validation"`

---

## Task 4: Implement Moderation Penalty Engine Service

**Files:**
- Create: `app/Services/InstructorModerationPenaltyService.php`
- Modify: `app/Services/ContentGovernanceService.php`
- Test: `tests/Unit/InstructorModerationPenaltyServiceTest.php`

**Step 1: Write the failing test**

Create tests asserting:
1. Every reject creates a violation record.
2. Violation 1 suggests warning only.
3. Violation 2 suggests 3-day restriction.
4. Violation 3 suggests 14-day restriction.
5. Violation 4+ suggests suspension review.

**Step 2: Run test to verify it fails**

Run:
`php artisan test --filter=InstructorModerationPenaltyServiceTest`

Expected:
- FAIL because penalty service does not exist.

**Step 3: Write minimal implementation**

Implement penalty service methods:
1. `recordViolation(...)`.
2. `suggestActionForNextViolation(...)`.
3. `applyConfirmedAction(...)`.

Wire `ContentGovernanceService::rejectReview` to:
1. Save reason/guidance to review and revision.
2. Create violation record.
3. Return suggested action data to controller layer for confirmation flow.

**Step 4: Run test to verify it passes**

Run:
`php artisan test --filter=InstructorModerationPenaltyServiceTest`

Expected:
- PASS.

**Step 5: Commit**

Run:
`git add app/Services/InstructorModerationPenaltyService.php app/Services/ContentGovernanceService.php tests/Unit/InstructorModerationPenaltyServiceTest.php`
`git commit -m "feat: add instructor moderation penalty engine"`

---

## Task 5: Add Admin Confirmation Action for Suggested Penalties

**Files:**
- Modify: `app/Http/Controllers/Admin/ContentReviewController.php`
- Modify: `routes/admin.php`
- Create: `app/Http/Requests/Admin/ConfirmInstructorPenaltyRequest.php`
- Test: `tests/Feature/Admin/AdminModulePenaltyConfirmationTest.php`

**Step 1: Write the failing test**

Add tests for new endpoint to confirm penalty action after rejection:
1. Admin can confirm suggested action.
2. Confirmed action updates moderation profile restriction fields.
3. Non-admin blocked.

**Step 2: Run test to verify it fails**

Run:
`php artisan test --filter=AdminModulePenaltyConfirmationTest`

Expected:
- FAIL because confirmation endpoint/contract does not exist.

**Step 3: Write minimal implementation**

1. Add route: `admin.content-reviews.{reviewRequest}.penalty.confirm`.
2. Add form request validating action enum.
3. Controller delegates to penalty service apply method.
4. Persist admin actor and confirmation timestamp.

**Step 4: Run test to verify it passes**

Run:
`php artisan test --filter=AdminModulePenaltyConfirmationTest`

Expected:
- PASS.

**Step 5: Commit**

Run:
`git add app/Http/Controllers/Admin/ContentReviewController.php routes/admin.php app/Http/Requests/Admin/ConfirmInstructorPenaltyRequest.php tests/Feature/Admin/AdminModulePenaltyConfirmationTest.php`
`git commit -m "feat: add admin confirmation flow for instructor penalties"`

---

## Task 6: Enforce Instructor Restrictions in Backend Flows

**Files:**
- Create: `app/Support/InstructorRestrictionGate.php`
- Modify: `app/Http/Controllers/Instructor/ModuleController.php`
- Modify: `app/Http/Controllers/Instructor/ModuleReviewController.php`
- Test: `tests/Feature/Instructor/InstructorRestrictionEnforcementTest.php`

**Step 1: Write the failing test**

Create tests asserting restricted instructors cannot:
1. Create/store modules.
2. Submit/resubmit modules for review.

Expected response:
1. Action blocked with meaningful error message.
2. No data mutation occurs.

**Step 2: Run test to verify it fails**

Run:
`php artisan test --filter=InstructorRestrictionEnforcementTest`

Expected:
- FAIL because restriction checks are not implemented.

**Step 3: Write minimal implementation**

1. Implement restriction gate helper to evaluate active restriction window.
2. Add guard checks in controller actions (create/store/submit/resubmit).
3. Return redirect with policy error toast/session message.

**Step 4: Run test to verify it passes**

Run:
`php artisan test --filter=InstructorRestrictionEnforcementTest`

Expected:
- PASS.

**Step 5: Commit**

Run:
`git add app/Support/InstructorRestrictionGate.php app/Http/Controllers/Instructor/ModuleController.php app/Http/Controllers/Instructor/ModuleReviewController.php tests/Feature/Instructor/InstructorRestrictionEnforcementTest.php`
`git commit -m "feat: enforce instructor moderation restrictions in backend"`

---

## Task 7: Build Admin Review Workspace Data Composition

**Files:**
- Create: `app/Services/AdminModuleReviewWorkspaceService.php`
- Modify: `app/Http/Controllers/Admin/ContentReviewController.php`
- Test: `tests/Feature/Admin/AdminContentReviewWorkspaceDataTest.php`

**Step 1: Write the failing test**

Add feature test for review show endpoint asserting presence of:
1. Module metadata block data.
2. Hierarchical structure payload (lessons/topics/quizzes).
3. Instructor moderation summary (warning count/status/last violation).

**Step 2: Run test to verify it fails**

Run:
`php artisan test --filter=AdminContentReviewWorkspaceDataTest`

Expected:
- FAIL because controller currently returns minimal review data.

**Step 3: Write minimal implementation**

1. Compose workspace DTO from revision snapshot payload.
2. Attach moderation profile + last violations.
3. Provide sorted tree nodes and derived counts for UI.

**Step 4: Run test to verify it passes**

Run:
`php artisan test --filter=AdminContentReviewWorkspaceDataTest`

Expected:
- PASS.

**Step 5: Commit**

Run:
`git add app/Services/AdminModuleReviewWorkspaceService.php app/Http/Controllers/Admin/ContentReviewController.php tests/Feature/Admin/AdminContentReviewWorkspaceDataTest.php`
`git commit -m "feat: add admin module review workspace data composition"`

---

## Task 8: Implement Admin Review Workspace UI and Hierarchy

**Files:**
- Modify: `resources/views/admin/content-reviews/show.blade.php`
- Modify: `resources/views/admin/content-reviews/_approve-modal.blade.php`
- Modify: `resources/views/admin/content-reviews/_reject-modal.blade.php`
- Create: `resources/views/admin/content-reviews/partials/workspace-tree.blade.php`
- Create: `resources/views/admin/content-reviews/partials/module-overview.blade.php`
- Create: `resources/views/admin/content-reviews/partials/instructor-credibility.blade.php`
- Test: `tests/Feature/Admin/AdminContentReviewWorkspaceUiTest.php`

**Step 1: Write the failing test**

Add UI assertions for:
1. Module metadata fields.
2. Expand/collapse hierarchy markers.
3. Instructor credibility summary fields.
4. Rejection reason selector and required guidance textarea.

**Step 2: Run test to verify it fails**

Run:
`php artisan test --filter=AdminContentReviewWorkspaceUiTest`

Expected:
- FAIL because page currently has only minimal submission/feedback/actions.

**Step 3: Write minimal implementation**

1. Replace current show layout with workspace sections.
2. Add Alpine-based expand/collapse tree.
3. Keep admin visual style and brand colors.
4. Keep approve action simple; reject form uses structured inputs.

**Step 4: Run test to verify it passes**

Run:
`php artisan test --filter=AdminContentReviewWorkspaceUiTest`

Expected:
- PASS.

**Step 5: Commit**

Run:
`git add resources/views/admin/content-reviews/show.blade.php resources/views/admin/content-reviews/_approve-modal.blade.php resources/views/admin/content-reviews/_reject-modal.blade.php resources/views/admin/content-reviews/partials/workspace-tree.blade.php resources/views/admin/content-reviews/partials/module-overview.blade.php resources/views/admin/content-reviews/partials/instructor-credibility.blade.php tests/Feature/Admin/AdminContentReviewWorkspaceUiTest.php`
`git commit -m "feat: implement admin module review workspace interface"`

---

## Task 9: Add Topic Preview Safety and Lazy Detail Endpoints

**Files:**
- Create: `app/Http/Controllers/Admin/ContentReviewPreviewController.php`
- Create: `app/Http/Requests/Admin/ContentReviewPreviewRequest.php`
- Modify: `routes/admin.php`
- Modify: `app/Services/AdminModuleReviewWorkspaceService.php`
- Test: `tests/Feature/Admin/AdminContentReviewPreviewEndpointTest.php`

**Step 1: Write the failing test**

Add endpoint tests asserting:
1. Admin can request topic/quiz detail payload by node id.
2. Payload returns sanitized content.
3. Non-admin access denied.

**Step 2: Run test to verify it fails**

Run:
`php artisan test --filter=AdminContentReviewPreviewEndpointTest`

Expected:
- FAIL because preview endpoint does not exist.

**Step 3: Write minimal implementation**

1. Add preview route under `admin.content-reviews` prefix.
2. Resolve node data from snapshot, sanitize rich content fields, and return JSON.
3. Use this endpoint only for heavy preview blocks.

**Step 4: Run test to verify it passes**

Run:
`php artisan test --filter=AdminContentReviewPreviewEndpointTest`

Expected:
- PASS.

**Step 5: Commit**

Run:
`git add app/Http/Controllers/Admin/ContentReviewPreviewController.php app/Http/Requests/Admin/ContentReviewPreviewRequest.php routes/admin.php app/Services/AdminModuleReviewWorkspaceService.php tests/Feature/Admin/AdminContentReviewPreviewEndpointTest.php`
`git commit -m "feat: add lazy and sanitized admin review preview endpoints"`

---

## Task 10: Implement Full Quiz Overview Review Mode

**Files:**
- Modify: `resources/views/admin/content-reviews/partials/workspace-tree.blade.php`
- Modify: `app/Services/AdminModuleReviewWorkspaceService.php`
- Test: `tests/Feature/Admin/AdminQuizReviewOverviewModeTest.php`

**Step 1: Write the failing test**

Create assertions that quiz review panel displays all questions at once, including:
1. Question type.
2. Question text.
3. Options.
4. Correct answer indicators.

**Step 2: Run test to verify it fails**

Run:
`php artisan test --filter=AdminQuizReviewOverviewModeTest`

Expected:
- FAIL because quiz details are currently minimal.

**Step 3: Write minimal implementation**

1. Render full question list in single overview card per quiz.
2. Match instructor-side quiz overview readability pattern.
3. Keep review mode read-only.

**Step 4: Run test to verify it passes**

Run:
`php artisan test --filter=AdminQuizReviewOverviewModeTest`

Expected:
- PASS.

**Step 5: Commit**

Run:
`git add resources/views/admin/content-reviews/partials/workspace-tree.blade.php app/Services/AdminModuleReviewWorkspaceService.php tests/Feature/Admin/AdminQuizReviewOverviewModeTest.php`
`git commit -m "feat: add full quiz overview mode for admin review"`

---

## Task 11: Add Instructor In-App Notifications for Review Outcomes

**Files:**
- Create: `app/Notifications/InstructorModuleReviewDecisionNotification.php`
- Modify: `app/Services/ContentGovernanceService.php`
- Test: `tests/Feature/Admin/InstructorModuleReviewDecisionNotificationTest.php`

**Step 1: Write the failing test**

Add tests asserting:
1. Approval sends in-app notification.
2. Rejection sends in-app notification with reason/guidance and penalty summary.

**Step 2: Run test to verify it fails**

Run:
`php artisan test --filter=InstructorModuleReviewDecisionNotificationTest`

Expected:
- FAIL because dedicated review decision notification is not dispatched.

**Step 3: Write minimal implementation**

1. Create database notification class.
2. Dispatch notification in approve/reject flows.
3. Include required payload fields from design.

**Step 4: Run test to verify it passes**

Run:
`php artisan test --filter=InstructorModuleReviewDecisionNotificationTest`

Expected:
- PASS.

**Step 5: Commit**

Run:
`git add app/Notifications/InstructorModuleReviewDecisionNotification.php app/Services/ContentGovernanceService.php tests/Feature/Admin/InstructorModuleReviewDecisionNotificationTest.php`
`git commit -m "feat: notify instructors of module review decisions"`

---

## Task 12: Add Instructor UI Restriction State Rendering

**Files:**
- Modify: `resources/views/instructor/modules/index.blade.php`
- Modify: `resources/views/instructor/modules/create.blade.php`
- Modify: `resources/views/instructor/modules/edit.blade.php`
- Test: `tests/Feature/Instructor/InstructorRestrictionUiStateTest.php`

**Step 1: Write the failing test**

Add tests asserting restricted instructors see:
1. Restriction banner/status chip.
2. Disabled create/submit actions.
3. Restriction end date guidance.

**Step 2: Run test to verify it fails**

Run:
`php artisan test --filter=InstructorRestrictionUiStateTest`

Expected:
- FAIL because UI does not yet reflect moderation restrictions.

**Step 3: Write minimal implementation**

1. Inject moderation profile summary into relevant instructor pages.
2. Disable actions in Blade when restriction active.
3. Show contextual explanatory copy.

**Step 4: Run test to verify it passes**

Run:
`php artisan test --filter=InstructorRestrictionUiStateTest`

Expected:
- PASS.

**Step 5: Commit**

Run:
`git add resources/views/instructor/modules/index.blade.php resources/views/instructor/modules/create.blade.php resources/views/instructor/modules/edit.blade.php tests/Feature/Instructor/InstructorRestrictionUiStateTest.php`
`git commit -m "feat: surface moderation restriction states in instructor ui"`

---

## Task 13: Final Verification and Documentation

**Files:**
- Create: `docs/changelogs/2026-03-30-admin-module-review-system.md`

**Step 1: Run targeted moderation tests**

Run:
`php artisan test --filter=AdminInstructorModerationSchemaTest`
`php artisan test --filter=InstructorModerationRelationshipsTest`
`php artisan test --filter=ModuleReviewRejectValidationTest`
`php artisan test --filter=InstructorModerationPenaltyServiceTest`
`php artisan test --filter=AdminModulePenaltyConfirmationTest`
`php artisan test --filter=InstructorRestrictionEnforcementTest`
`php artisan test --filter=AdminContentReviewWorkspaceDataTest`
`php artisan test --filter=AdminContentReviewWorkspaceUiTest`
`php artisan test --filter=AdminContentReviewPreviewEndpointTest`
`php artisan test --filter=AdminQuizReviewOverviewModeTest`
`php artisan test --filter=InstructorModuleReviewDecisionNotificationTest`
`php artisan test --filter=InstructorRestrictionUiStateTest`

Expected:
- PASS or only clearly documented pre-existing unrelated failures.

**Step 2: Run governance regression subset**

Run:
`php artisan test --filter=ContentGovernanceServiceTest`
`php artisan test --filter=LearnerPublishedModuleVisibilityTest`

Expected:
- PASS.

**Step 3: Run frontend build smoke check**

Run:
`npm run build`

Expected:
- Build success.

**Step 4: Write changelog summary**

Document:
1. Implemented scope.
2. Test commands and results.
3. Known residual risks.
4. Rollout notes (no historical violation backfill).

**Step 5: Commit**

Run:
`git add docs/changelogs/2026-03-30-admin-module-review-system.md`
`git commit -m "docs: record admin module review system rollout verification"`

---

## Implementation Notes

1. Keep changes additive to existing governance design.
2. Do not break current review queue routes and status semantics.
3. Keep snapshot payload as moderation source of truth.
4. Keep all restriction rules server-enforced first; UI is assistive.
5. Use Toastify for admin action feedback to match current admin UX alignment.
6. Prefer DRY service methods for reason composition and action suggestion.

## Definition of Done

1. Admin can inspect full module package with learner-like hierarchy and full quiz overview.
2. Reject requires preset reason plus guidance note and auto-records violation.
3. Penalty escalation suggestions follow 1/2/3+ policy and require admin confirmation.
4. Restricted instructors are blocked from create/submit in backend and clearly informed in UI.
5. Instructors receive in-app decision notifications.
6. Learner published visibility behavior remains governed and unchanged.
7. Targeted tests and build verification pass.
