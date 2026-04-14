# Learner Feedback, Reporting, and Quiz UX Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Ship learner reviews, learner safety reporting, improved quiz Enter-key behavior, and post-quiz progression UX enhancements.

**Architecture:** Service-first layered implementation with thin controllers, Form Requests for validation, additive migrations, and Blade/Tailwind UI additions aligned to current visual system.

**Tech Stack:** Laravel 12, PHP 8.2, Blade, Alpine, Tailwind, TinyMCE, PHPUnit.

---

## Task 1: Add Review and Report Data Schema
Files:
- Create: database/migrations/*_create_module_feedback_table.php
- Create: database/migrations/*_create_content_reports_table.php
- Create: database/migrations/*_create_content_report_activities_table.php
- Create: app/Enums/ContentReportStatus.php
- Create: app/Enums/ContentReportReason.php
- Create: app/Enums/ContentReportTargetType.php

## Task 2: Add Models and Relationships
Files:
- Create: app/Models/ModuleFeedback.php
- Create: app/Models/ContentReport.php
- Create: app/Models/ContentReportActivity.php
- Modify: app/Models/Module.php
- Modify: app/Models/User.php

## Task 3: Add Service Layer
Files:
- Create: app/Services/LearnerModuleCompletionService.php
- Create: app/Services/ModuleFeedbackService.php
- Create: app/Services/ContentReportService.php

## Task 4: Add Form Requests and Controllers
Files:
- Create: app/Http/Requests/Learner/StoreModuleFeedbackRequest.php
- Create: app/Http/Requests/Learner/StoreContentReportRequest.php
- Create: app/Http/Requests/Admin/UpdateContentReportRequest.php
- Create: app/Http/Controllers/Learner/ModuleFeedbackController.php
- Create: app/Http/Controllers/Learner/ModuleReviewPageController.php
- Create: app/Http/Controllers/Learner/ContentReportController.php
- Create: app/Http/Controllers/Admin/LearnerReportController.php

## Task 5: Wire Routes
Files:
- Modify: routes/web.php
- Modify: routes/admin.php

## Task 6: Learner Module Overview + Full Reviews UI
Files:
- Modify: resources/views/learner/modules/show.blade.php
- Create: resources/views/learner/modules/reviews.blade.php

## Task 7: Instructor Review Transparency UI
Files:
- Modify: resources/views/instructor/modules/show.blade.php
- Modify: app/Http/Controllers/Instructor/ModuleController.php

## Task 8: Admin Report Moderation UI
Files:
- Create: resources/views/admin/reports/index.blade.php
- Create: resources/views/admin/reports/show.blade.php
- Modify: resources/views/layouts/admin.blade.php

## Task 9: Quiz Enter-Key UX + Review Flow
Files:
- Modify: resources/views/quizzes/take.blade.php
- Modify: resources/views/learner/lessons/partials/quiz-page.blade.php

## Task 10: Post-Quiz Progression and Completion Modal
Files:
- Modify: app/Http/Controllers/Learner/QuizController.php
- Modify: resources/views/quizzes/result.blade.php
- Modify: resources/views/learner/lessons/partials/quiz-page.blade.php

## Task 11: Automated Tests
Files:
- Create: tests/Feature/Learner/ModuleFeedbackFlowTest.php
- Create: tests/Feature/Learner/ContentReportFlowTest.php
- Create: tests/Feature/Learner/QuizProgressionUxTest.php

## Task 12: Verification
Commands:
- php artisan test --filter=ModuleFeedbackFlowTest
- php artisan test --filter=ContentReportFlowTest
- php artisan test --filter=QuizProgressionUxTest
