# Learner Module Overview, Review UX, Reporting, and Instructor Background Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Deliver a clearer learner module overview layout, modal-based review/report interactions, quiz-in-curriculum hierarchy, and learner-safe instructor background structure parity.

**Architecture:** Use a hybrid Blade refactor with targeted partial extraction and minimal controller query updates. Preserve existing services, routes, and policy behavior. Implement test-first for each behavior slice, then apply the smallest production change needed to satisfy assertions.

**Tech Stack:** Laravel 12, PHP 8.2, Blade, Alpine.js, Tailwind CSS v3, TinyMCE, PHPUnit.

---

## Task 1: Lock Right-Rail Hierarchy and Remove Standalone Assessment Panel

**Files:**
- Modify: resources/views/learner/modules/show.blade.php
- Test: tests/Feature/Learner/ModuleOverviewLayoutTest.php

**Step 1: Write the failing test**
```php
public function test_module_show_page_uses_expected_right_rail_order_and_hides_module_assessment_section(): void
{
    $learner = User::factory()->create();
    $learner->assignRole('learner');

    $module = Module::factory()->published()->create();

    $this->actingAs($learner)
        ->get(route('learner.modules.show', $module))
        ->assertOk()
        ->assertDontSee('Module Assessment', false)
        ->assertSeeInOrder([
            'Your Progress',
            'Instructor Information',
            'Module Info',
            'Learner Reviews',
        ], false);
}
```

**Step 2: Run test to verify it fails**
Run: `php artisan test --filter=ModuleOverviewLayoutTest`
Expected: FAIL because current page still renders Module Assessment and old section ordering.

**Step 3: Write minimal implementation**
- Reorder right-rail sections in show view.
- Remove standalone Module Assessment block.

**Step 4: Run test to verify it passes**
Run: `php artisan test --filter=ModuleOverviewLayoutTest`
Expected: PASS.

**Step 5: Commit**
```bash
git add tests/Feature/Learner/ModuleOverviewLayoutTest.php resources/views/learner/modules/show.blade.php
git commit -m "feat: reorder learner module rail and remove assessment panel"
```

## Task 2: Move Quiz Visibility Into Curriculum Hierarchy Only

**Files:**
- Modify: resources/views/learner/modules/show.blade.php
- Test: tests/Feature/Learner/ModuleOverviewLayoutTest.php

**Step 1: Write the failing test**
```php
public function test_module_show_page_displays_quiz_markers_inside_curriculum_hierarchy(): void
{
    $learner = User::factory()->create();
    $learner->assignRole('learner');

    $module = Module::factory()->published()->create();
    $lesson = Lesson::factory()->for($module)->create();
    Quiz::factory()->for($lesson)->active()->create(['title' => 'Lesson 1 Quiz']);

    $this->actingAs($learner)
        ->get(route('learner.modules.show', $module))
        ->assertOk()
        ->assertSee('Module Curriculum', false)
        ->assertSee('Lesson 1 Quiz', false)
        ->assertDontSee('Module Assessment', false);
}
```

**Step 2: Run test to verify it fails**
Run: `php artisan test --filter=displays_quiz_markers_inside_curriculum_hierarchy`
Expected: FAIL because quiz placement currently depends on standalone assessment panel.

**Step 3: Write minimal implementation**
- Render quiz markers/items within lesson/curriculum breakdown only.
- Do not add quiz detail panel or extra metadata block.

**Step 4: Run test to verify it passes**
Run: `php artisan test --filter=displays_quiz_markers_inside_curriculum_hierarchy`
Expected: PASS.

**Step 5: Commit**
```bash
git add resources/views/learner/modules/show.blade.php tests/Feature/Learner/ModuleOverviewLayoutTest.php
git commit -m "feat: show quizzes inside curriculum hierarchy"
```

## Task 3: Add Reusable Heart Rating Renderer and Apply Across Module Review Surfaces

**Files:**
- Create: resources/views/components/reviews/heart-rating.blade.php
- Modify: resources/views/learner/modules/show.blade.php
- Modify: resources/views/learner/modules/reviews.blade.php
- Modify: resources/views/instructor/modules/show.blade.php
- Test: tests/Feature/Learner/ModuleReviewVisualsTest.php

**Step 1: Write the failing test**
```php
public function test_review_surfaces_render_icon_hearts_with_numeric_rating(): void
{
    $learner = User::factory()->create();
    $learner->assignRole('learner');

    $module = Module::factory()->published()->create();

    ModuleFeedback::factory()->for($module)->create(['rating' => 4]);

    $this->actingAs($learner)
        ->get(route('learner.modules.show', $module))
        ->assertOk()
        ->assertSee('4.0', false)
        ->assertSee('aria-label="4 out of 5 hearts"', false);
}
```

**Step 2: Run test to verify it fails**
Run: `php artisan test --filter=ModuleReviewVisualsTest`
Expected: FAIL because current output uses text hearts only.

**Step 3: Write minimal implementation**
- Create reusable icon-heart component with filled and unfilled hearts plus numeric label.
- Replace text heart output in learner/instructor review surfaces.

**Step 4: Run test to verify it passes**
Run: `php artisan test --filter=ModuleReviewVisualsTest`
Expected: PASS.

**Step 5: Commit**
```bash
git add resources/views/components/reviews/heart-rating.blade.php resources/views/learner/modules/show.blade.php resources/views/learner/modules/reviews.blade.php resources/views/instructor/modules/show.blade.php tests/Feature/Learner/ModuleReviewVisualsTest.php
git commit -m "feat: standardize icon-heart ratings across module review views"
```

## Task 4: Render Learner Avatar and Display Name in Review Entries

**Files:**
- Modify: app/Http/Controllers/Learner/ModuleController.php
- Modify: app/Http/Controllers/Learner/ModuleReviewPageController.php
- Modify: resources/views/learner/modules/show.blade.php
- Modify: resources/views/learner/modules/reviews.blade.php
- Test: tests/Feature/Learner/ModuleReviewVisualsTest.php

**Step 1: Write the failing test**
```php
public function test_reviews_show_learner_avatar_and_display_name(): void
{
    $learner = User::factory()->create(['name' => 'Sample Learner']);
    $learner->assignRole('learner');
    $profile = LearnerProfile::factory()->for($learner)->create(['avatar_path' => 'avatars/sample.png']);

    $module = Module::factory()->published()->create();
    ModuleFeedback::factory()->for($module)->for($learner, 'learner')->create();

    $this->actingAs($learner)
        ->get(route('learner.modules.reviews', $module))
        ->assertOk()
        ->assertSee('Sample Learner', false)
        ->assertSee('avatars/sample.png', false);
}
```

**Step 2: Run test to verify it fails**
Run: `php artisan test --filter=reviews_show_learner_avatar_and_display_name`
Expected: FAIL because current query/view does not consistently render learner avatar.

**Step 3: Write minimal implementation**
- Eager load learner profile data in both learner review queries.
- Render avatar image with initials fallback.

**Step 4: Run test to verify it passes**
Run: `php artisan test --filter=reviews_show_learner_avatar_and_display_name`
Expected: PASS.

**Step 5: Commit**
```bash
git add app/Http/Controllers/Learner/ModuleController.php app/Http/Controllers/Learner/ModuleReviewPageController.php resources/views/learner/modules/show.blade.php resources/views/learner/modules/reviews.blade.php tests/Feature/Learner/ModuleReviewVisualsTest.php
git commit -m "feat: show learner identity and avatar in module reviews"
```

## Task 5: Convert Inline Write Review Form to Modal Workflow

**Files:**
- Modify: resources/views/learner/modules/show.blade.php
- Create: resources/views/learner/modules/partials/review-modal.blade.php
- Test: tests/Feature/Learner/ModuleOverviewLayoutTest.php
- Test: tests/Feature/Learner/ModuleFeedbackFlowTest.php

**Step 1: Write the failing test**
```php
public function test_module_show_uses_review_modal_trigger_instead_of_inline_form(): void
{
    $learner = User::factory()->create();
    $learner->assignRole('learner');
    $module = Module::factory()->published()->create();

    $this->actingAs($learner)
        ->get(route('learner.modules.show', $module))
        ->assertOk()
        ->assertSee('Write Review', false)
        ->assertDontSee('Your Review</label>', false);
}
```

**Step 2: Run test to verify it fails**
Run: `php artisan test --filter=review_modal_trigger`
Expected: FAIL due current inline form.

**Step 3: Write minimal implementation**
- Add Write or Update Review trigger button.
- Move review fields into modal partial.
- Keep existing post route and validation behavior.

**Step 4: Run test to verify it passes**
Run: `php artisan test --filter="review_modal_trigger|ModuleFeedbackFlowTest"`
Expected: PASS.

**Step 5: Commit**
```bash
git add resources/views/learner/modules/show.blade.php resources/views/learner/modules/partials/review-modal.blade.php tests/Feature/Learner/ModuleOverviewLayoutTest.php tests/Feature/Learner/ModuleFeedbackFlowTest.php
git commit -m "feat: switch learner review submission to modal workflow"
```

## Task 6: Implement Report Icon Trigger and Dual Target Modal Actions

**Files:**
- Modify: resources/views/learner/modules/show.blade.php
- Create: resources/views/learner/modules/partials/report-modal.blade.php
- Test: tests/Feature/Learner/ContentReportFlowTest.php
- Test: tests/Feature/Learner/ModuleOverviewLayoutTest.php

**Step 1: Write the failing test**
```php
public function test_module_show_uses_report_icon_and_dual_target_modal_actions(): void
{
    $learner = User::factory()->create();
    $learner->assignRole('learner');
    $module = Module::factory()->published()->create();

    $this->actingAs($learner)
        ->get(route('learner.modules.show', $module))
        ->assertOk()
        ->assertSee('aria-label="Report module or instructor"', false)
        ->assertSee('Report Module', false)
        ->assertSee('Report Instructor', false);
}
```

**Step 2: Run test to verify it fails**
Run: `php artisan test --filter=dual_target_modal_actions`
Expected: FAIL because current page uses inline report form.

**Step 3: Write minimal implementation**
- Replace inline report form with icon button trigger.
- Add single report modal with two preselect target actions.
- Keep existing submit endpoint and fields.

**Step 4: Run test to verify it passes**
Run: `php artisan test --filter="dual_target_modal_actions|ContentReportFlowTest"`
Expected: PASS.

**Step 5: Commit**
```bash
git add resources/views/learner/modules/show.blade.php resources/views/learner/modules/partials/report-modal.blade.php tests/Feature/Learner/ModuleOverviewLayoutTest.php tests/Feature/Learner/ContentReportFlowTest.php
git commit -m "feat: add report icon modal with module and instructor targets"
```

## Task 7: Add Instructor Card Message Icon Shortcut with Existing Chat Contract

**Files:**
- Modify: resources/views/learner/modules/show.blade.php
- Test: tests/Feature/Learner/ModuleOverviewLayoutTest.php

**Step 1: Write the failing test**
```php
public function test_instructor_card_exposes_message_icon_with_module_chat_context_payload(): void
{
    $learner = User::factory()->create();
    $learner->assignRole('learner');

    $instructor = User::factory()->create();
    $instructor->assignRole('instructor');

    $module = Module::factory()->published()->create(['created_by' => $instructor->id]);

    $this->actingAs($learner)
        ->get(route('learner.modules.show', $module))
        ->assertOk()
        ->assertSee('open-global-chat', false)
        ->assertSee('module_chat', false);
}
```

**Step 2: Run test to verify it fails**
Run: `php artisan test --filter=message_icon_with_module_chat_context_payload`
Expected: FAIL if icon behavior is not in required shape.

**Step 3: Write minimal implementation**
- Add icon button in instructor card.
- Keep existing payload contract with target_user_id, conversation_type, module_id.

**Step 4: Run test to verify it passes**
Run: `php artisan test --filter=message_icon_with_module_chat_context_payload`
Expected: PASS.

**Step 5: Commit**
```bash
git add resources/views/learner/modules/show.blade.php tests/Feature/Learner/ModuleOverviewLayoutTest.php
git commit -m "feat: add instructor message icon shortcut on module overview"
```

## Task 8: Upgrade Learner Instructor Background View to Instructor-Structure Parity

**Files:**
- Modify: app/Http/Controllers/Learner/InstructorProfileController.php
- Modify: resources/views/learner/instructors/show.blade.php
- Test: tests/Feature/Learner/LearnerInstructorBackgroundPageTest.php

**Step 1: Write the failing test**
```php
public function test_learner_instructor_background_page_renders_certifications_education_and_professional_sections(): void
{
    $learner = User::factory()->create();
    $learner->assignRole('learner');

    $instructor = User::factory()->create();
    $instructor->assignRole('instructor');

    InstructorProfile::factory()->for($instructor)->create();

    $this->actingAs($learner)
        ->get(route('learner.instructors.show', $instructor))
        ->assertOk()
        ->assertSee('Professional Background', false)
        ->assertSee('Certifications', false)
        ->assertSee('Educational Background', false);
}
```

**Step 2: Run test to verify it fails**
Run: `php artisan test --filter=LearnerInstructorBackgroundPageTest`
Expected: FAIL because learner page currently uses reduced structure.

**Step 3: Write minimal implementation**
- Expand learner instructor show controller read data.
- Rebuild learner instructor view sections to mirror instructor profile structure in learner-safe form.

**Step 4: Run test to verify it passes**
Run: `php artisan test --filter=LearnerInstructorBackgroundPageTest`
Expected: PASS.

**Step 5: Commit**
```bash
git add app/Http/Controllers/Learner/InstructorProfileController.php resources/views/learner/instructors/show.blade.php tests/Feature/Learner/LearnerInstructorBackgroundPageTest.php
git commit -m "feat: align learner instructor background page with instructor profile sections"
```

## Task 9: Extract Targeted Partials for Maintainability (Approach 2)

**Files:**
- Create: resources/views/learner/modules/partials/instructor-info-card.blade.php
- Create: resources/views/learner/modules/partials/module-info-card.blade.php
- Create: resources/views/learner/modules/partials/reviews-card.blade.php
- Modify: resources/views/learner/modules/show.blade.php
- Test: tests/Feature/Learner/ModuleOverviewLayoutTest.php

**Step 1: Write the failing test**
- Reuse existing layout test with assertions for core text and order after partial extraction.

**Step 2: Run test to verify it can detect regressions**
Run: `php artisan test --filter=ModuleOverviewLayoutTest`
Expected: catches any render/order regressions.

**Step 3: Write minimal implementation**
- Move repeated card blocks to partial files.
- Keep all route names and variable contracts unchanged.

**Step 4: Run test to verify it passes**
Run: `php artisan test --filter=ModuleOverviewLayoutTest`
Expected: PASS.

**Step 5: Commit**
```bash
git add resources/views/learner/modules/show.blade.php resources/views/learner/modules/partials/instructor-info-card.blade.php resources/views/learner/modules/partials/module-info-card.blade.php resources/views/learner/modules/partials/reviews-card.blade.php
git commit -m "refactor: extract learner module sidebar partials"
```

## Task 10: Final Verification and Documentation Sync

**Files:**
- Modify: docs/changelogs/2026-04-14-learner-module-overview-review-report-instructor-background.md

**Step 1: Run focused test suite**
Run:
- `php artisan test --filter=ModuleOverviewLayoutTest`
- `php artisan test --filter=ModuleReviewVisualsTest`
- `php artisan test --filter=ModuleFeedbackFlowTest`
- `php artisan test --filter=ContentReportFlowTest`
- `php artisan test --filter=LearnerInstructorBackgroundPageTest`

Expected: PASS with no new regressions for touched learner flows.

**Step 2: Run additional related regression tests**
Run:
- `php artisan test --filter="InstructorProfileSchemaTest|InstructorProfilePageTest|InstructorProfileUpdateSecurityTest|InstructorPasswordUpdateSecurityTest"`
Expected: PASS.

**Step 3: Add release note/changelog entry**
- Summarize UX updates, modal conversion, quiz placement changes, and instructor background parity.

**Step 4: Commit verification and changelog**
```bash
git add docs/changelogs/2026-04-14-learner-module-overview-review-report-instructor-background.md
git commit -m "docs: add learner module overview and instructor background rollout notes"
```
