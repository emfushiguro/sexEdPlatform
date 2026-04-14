# Changelog: Learner Module Overview, Reviews, Reporting, and Instructor Background

Date: 2026-04-14

## Summary
This rollout updates learner-facing module details with clearer right-rail hierarchy, modal-based review/report UX, quiz visibility inside curriculum hierarchy, visual heart ratings, and learner-safe instructor background section parity.

## What Changed

### Learner module overview page
- Reordered right-rail cards to:
  - Enrollment/progress
  - Instructor information
  - Module info
  - Learner reviews
- Removed the standalone "Module Assessment" panel from learner module overview.
- Added lesson-level quiz markers directly inside the curriculum hierarchy.
- Converted inline review and report forms into modal workflows.
- Added report icon trigger with dual target actions:
  - Report Module
  - Report Instructor
- Preserved existing report submission endpoint and validation flow.
- Preserved existing chat payload contract and exposed message shortcut icon in instructor card.

### Review visuals and identity
- Added reusable review heart icon renderer component:
  - `resources/views/components/reviews/heart-rating.blade.php`
- Replaced text-heart output with icon-heart + numeric rating in:
  - learner module overview recent reviews
  - learner full reviews page
  - instructor module reviews section
- Updated learner review rendering to show:
  - learner display name
  - learner avatar image from `learner_profiles.avatar_path` with initials fallback

### Learner instructor background page
- Upgraded learner instructor background page to include structured sections aligned with instructor profile model data:
  - Professional Background
  - Certifications
  - Educational Background
  - About and Credentials side sections
- Added normalized certifications and educational entries data contract in learner instructor controller.

### Maintainability updates
- Extracted targeted learner module sidebar and modal partials:
  - `resources/views/learner/modules/partials/instructor-info-card.blade.php`
  - `resources/views/learner/modules/partials/module-info-card.blade.php`
  - `resources/views/learner/modules/partials/reviews-card.blade.php`
  - `resources/views/learner/modules/partials/review-modal.blade.php`
  - `resources/views/learner/modules/partials/report-modal.blade.php`

## Verification

### New focused tests
- `ModuleOverviewLayoutTest`
- `ModuleReviewVisualsTest`
- `LearnerInstructorBackgroundPageTest`

### Existing related regression tests
- `ModuleFeedbackFlowTest`
- `ContentReportFlowTest`
- `ChatWorkflowEntryLinksTest`

### Executed commands
- `php artisan test --filter="ModuleOverviewLayoutTest|ModuleReviewVisualsTest|LearnerInstructorBackgroundPageTest"`
  - Result: PASS (8 tests, 27 assertions)
- `php artisan test --filter="ModuleFeedbackFlowTest|ContentReportFlowTest|ChatWorkflowEntryLinksTest"`
  - Result: PASS (7 tests, 27 assertions)

## Notes
- Existing service-layer behavior and route contracts were preserved.
- No new authorization bypasses were introduced in learner or instructor flows.
