# Instructor Profile and Scalable Module Configuration Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Deliver instructor profile integration, secure profile management, and scalable module/quiz configuration (pricing, enrollment limit, attempt limit, timer) without changing instructor application intake fields.

**Architecture:** Use additive schema evolution on existing tables and keep controller layers thin by moving business rules into Form Requests, policies, and focused service methods. Reuse approved instructor application records to seed profile education/professional fields and preserve backward compatibility for existing module/quiz data.

**Tech Stack:** Laravel, Eloquent, Form Requests, Policies, Blade, PHPUnit Feature/Unit tests, MySQL.

---

## Task 1: Add Instructor Profile Schema Extensions

**Files:**
- Create: `database/migrations/2026_03_27_120000_add_professional_fields_to_instructor_profiles_table.php`
- Modify: `app/Models/InstructorProfile.php`
- Test: `tests/Feature/Instructor/InstructorProfileSchemaTest.php`

**Step 1: Write the failing test**

Create a test asserting new columns exist in `instructor_profiles`:
- educational_background
- professional_background
- primary_expertise
- expertise_tags
- years_experience
- certifications
- profile_photo_path

**Step 2: Run test to verify it fails**

Run: `php artisan test --filter=InstructorProfileSchemaTest`  
Expected: FAIL with missing column assertions

**Step 3: Write minimal implementation**

- Add migration for new nullable columns.
- Update `InstructorProfile` fillable and casts (`expertise_tags`, `certifications` arrays).

**Step 4: Run test to verify it passes**

Run: `php artisan test --filter=InstructorProfileSchemaTest`  
Expected: PASS

**Step 5: Commit**

Run:
`git add database/migrations/2026_03_27_120000_add_professional_fields_to_instructor_profiles_table.php app/Models/InstructorProfile.php tests/Feature/Instructor/InstructorProfileSchemaTest.php`  
`git commit -m "feat: extend instructor profile schema for professional identity"`

## Task 2: Add Module Pricing and Capacity Schema

**Files:**
- Create: `database/migrations/2026_03_27_121000_add_pricing_and_capacity_to_modules_table.php`
- Modify: `app/Models/Module.php`
- Test: `tests/Feature/Instructor/InstructorModulePricingCapacitySchemaTest.php`

**Step 1: Write the failing test**

Assert module table includes:
- access_type
- price_amount
- price_currency
- enrollment_limit

**Step 2: Run test to verify it fails**

Run: `php artisan test --filter=InstructorModulePricingCapacitySchemaTest`  
Expected: FAIL with missing columns

**Step 3: Write minimal implementation**

- Add migration with defaults (`access_type=free`, `price_currency=PHP`, nullable amount/limit).
- Add fields to module fillable/casts.

**Step 4: Run test to verify it passes**

Run: `php artisan test --filter=InstructorModulePricingCapacitySchemaTest`  
Expected: PASS

**Step 5: Commit**

Run:
`git add database/migrations/2026_03_27_121000_add_pricing_and_capacity_to_modules_table.php app/Models/Module.php tests/Feature/Instructor/InstructorModulePricingCapacitySchemaTest.php`  
`git commit -m "feat: add module pricing and enrollment capacity schema"`

## Task 3: Add Quiz Attempt Limit Field

**Files:**
- Create: `database/migrations/2026_03_27_122000_add_attempt_limit_to_quizzes_table.php`
- Modify: `app/Models/Quiz.php`
- Test: `tests/Feature/Instructor/InstructorQuizAttemptLimitSchemaTest.php`

**Step 1: Write the failing test**

Assert quizzes table has `attempt_limit` column and model casts include it.

**Step 2: Run test to verify it fails**

Run: `php artisan test --filter=InstructorQuizAttemptLimitSchemaTest`  
Expected: FAIL

**Step 3: Write minimal implementation**

- Add nullable unsigned integer `attempt_limit` on quizzes.
- Add fillable + cast updates in `Quiz` model.

**Step 4: Run test to verify it passes**

Run: `php artisan test --filter=InstructorQuizAttemptLimitSchemaTest`  
Expected: PASS

**Step 5: Commit**

Run:
`git add database/migrations/2026_03_27_122000_add_attempt_limit_to_quizzes_table.php app/Models/Quiz.php tests/Feature/Instructor/InstructorQuizAttemptLimitSchemaTest.php`  
`git commit -m "feat: add quiz attempt limit schema"`

## Task 4: Seed Instructor Profile from Approved Application Only

**Files:**
- Modify: `app/Services/InstructorApplicationService.php`
- Test: `tests/Feature/InstructorApplicationApprovalTest.php`
- Test: `tests/Unit/Services/InstructorProfileSeedingTest.php`

**Step 1: Write the failing test**

- Assert approval seeds `educational_background` and `professional_background` from application.
- Assert no new application fields are required.

**Step 2: Run test to verify it fails**

Run: `php artisan test --filter=InstructorProfileSeedingTest`  
Expected: FAIL (missing seeded fields)

**Step 3: Write minimal implementation**

- Extend existing `InstructorProfile::updateOrCreate` payload in approval flow.
- Map:
  - application educational_background -> profile educational_background
  - application bio -> profile professional_background and/or bio
- Keep existing application flow untouched.

**Step 4: Run tests to verify they pass**

Run: `php artisan test --filter=InstructorProfileSeedingTest`  
Run: `php artisan test --filter=InstructorApplicationApprovalTest`  
Expected: PASS

**Step 5: Commit**

Run:
`git add app/Services/InstructorApplicationService.php tests/Feature/InstructorApplicationApprovalTest.php tests/Unit/Services/InstructorProfileSeedingTest.php`  
`git commit -m "feat: seed instructor profile fields from approved application"`

## Task 5: Add Instructor Profile Page Read Model and Routes

**Files:**
- Create: `app/Http/Controllers/Instructor/ProfileController.php`
- Modify: `routes/instructor.php`
- Create: `resources/views/instructor/profile/show.blade.php`
- Test: `tests/Feature/Instructor/InstructorProfilePageTest.php`

**Step 1: Write the failing test**

- Assert instructor can open profile page.
- Assert page includes personal, educational, professional, and overview sections.
- Assert learner role users cannot access instructor profile route.

**Step 2: Run test to verify it fails**

Run: `php artisan test --filter=InstructorProfilePageTest`  
Expected: FAIL (route/controller/view missing)

**Step 3: Write minimal implementation**

- Add profile show route under instructor group.
- Create controller that composes data from user + learner profile + instructor profile + aggregates.
- Build show Blade with sectioned layout and rating placeholder.

**Step 4: Run test to verify it passes**

Run: `php artisan test --filter=InstructorProfilePageTest`  
Expected: PASS

**Step 5: Commit**

Run:
`git add app/Http/Controllers/Instructor/ProfileController.php routes/instructor.php resources/views/instructor/profile/show.blade.php tests/Feature/Instructor/InstructorProfilePageTest.php`  
`git commit -m "feat: add instructor profile page and overview metrics"`

## Task 6: Add Secure Instructor Profile Editing

**Files:**
- Create: `app/Http/Requests/Instructor/UpdateInstructorProfileRequest.php`
- Modify: `app/Http/Controllers/Instructor/ProfileController.php`
- Create: `app/Policies/InstructorProfilePolicy.php`
- Modify: `app/Providers/AuthServiceProvider.php`
- Create: `resources/views/instructor/profile/edit.blade.php`
- Test: `tests/Feature/Instructor/InstructorProfileUpdateSecurityTest.php`

**Step 1: Write the failing test**

- Assert editable fields update successfully.
- Assert restricted fields (role, approval/system stats) are rejected or ignored.

**Step 2: Run test to verify it fails**

Run: `php artisan test --filter=InstructorProfileUpdateSecurityTest`  
Expected: FAIL

**Step 3: Write minimal implementation**

- Add edit/update actions and route entries.
- Use request whitelist + policy + service-safe assignment.
- Keep restricted fields server-guarded.

**Step 4: Run test to verify it passes**

Run: `php artisan test --filter=InstructorProfileUpdateSecurityTest`  
Expected: PASS

**Step 5: Commit**

Run:
`git add app/Http/Requests/Instructor/UpdateInstructorProfileRequest.php app/Http/Controllers/Instructor/ProfileController.php app/Policies/InstructorProfilePolicy.php app/Providers/AuthServiceProvider.php resources/views/instructor/profile/edit.blade.php tests/Feature/Instructor/InstructorProfileUpdateSecurityTest.php`  
`git commit -m "feat: enforce secure instructor profile editing boundaries"`

## Task 7: Extend Module Create/Edit Validation and Persistence

**Files:**
- Create: `app/Http/Requests/Instructor/StoreModuleRequest.php`
- Create: `app/Http/Requests/Instructor/UpdateModuleRequest.php`
- Modify: `app/Http/Controllers/Instructor/ModuleController.php`
- Modify: `resources/views/instructor/modules/create.blade.php`
- Modify: `resources/views/instructor/modules/edit.blade.php`
- Test: `tests/Feature/Instructor/InstructorModuleConfigValidationTest.php`

**Step 1: Write the failing test**

- Paid module requires `price_amount`.
- Free module stores null amount.
- Enrollment limit accepts null or positive integer.

**Step 2: Run test to verify it fails**

Run: `php artisan test --filter=InstructorModuleConfigValidationTest`  
Expected: FAIL

**Step 3: Write minimal implementation**

- Replace inline validation with Form Requests.
- Persist access_type, price_amount, price_currency, enrollment_limit.
- Preserve existing age bracket and ownership logic.

**Step 4: Run test to verify it passes**

Run: `php artisan test --filter=InstructorModuleConfigValidationTest`  
Expected: PASS

**Step 5: Commit**

Run:
`git add app/Http/Requests/Instructor/StoreModuleRequest.php app/Http/Requests/Instructor/UpdateModuleRequest.php app/Http/Controllers/Instructor/ModuleController.php resources/views/instructor/modules/create.blade.php resources/views/instructor/modules/edit.blade.php tests/Feature/Instructor/InstructorModuleConfigValidationTest.php`  
`git commit -m "feat: add module pricing and enrollment limit configuration"`

## Task 8: Enforce Paid Access Entitlement Gate

**Files:**
- Modify: `app/Http/Controllers/Instructor/ModuleController.php`
- Modify: `app/Services/EntitlementService.php` (or existing entitlement check service)
- Test: `tests/Feature/Instructor/InstructorPaidModuleEntitlementTest.php`

**Step 1: Write the failing test**

- Instructor without entitlement cannot save paid module.
- Entitled instructor or admin acting as instructor can save paid module.

**Step 2: Run test to verify it fails**

Run: `php artisan test --filter=InstructorPaidModuleEntitlementTest`  
Expected: FAIL

**Step 3: Write minimal implementation**

- Add entitlement gate before persisting paid access type.
- Return clear validation/authorization error message.

**Step 4: Run test to verify it passes**

Run: `php artisan test --filter=InstructorPaidModuleEntitlementTest`  
Expected: PASS

**Step 5: Commit**

Run:
`git add app/Http/Controllers/Instructor/ModuleController.php app/Services/EntitlementService.php tests/Feature/Instructor/InstructorPaidModuleEntitlementTest.php`  
`git commit -m "feat: gate paid modules by entitlement"`

## Task 9: Add Quiz Settings Inputs and Persistence

**Files:**
- Create: `app/Http/Requests/Instructor/StoreQuizRequest.php`
- Create: `app/Http/Requests/Instructor/UpdateQuizRequest.php`
- Modify: `app/Http/Controllers/Instructor/QuizManagementController.php`
- Modify: `resources/views/instructor/quizzes/create.blade.php`
- Modify: `resources/views/instructor/quizzes/edit.blade.php`
- Test: `tests/Feature/Instructor/InstructorQuizSettingsValidationTest.php`

**Step 1: Write the failing test**

- Accept h/m/s inputs and persist normalized seconds.
- Accept attempt_limit null or positive integer.

**Step 2: Run test to verify it fails**

Run: `php artisan test --filter=InstructorQuizSettingsValidationTest`  
Expected: FAIL

**Step 3: Write minimal implementation**

- Add Form Requests for quiz settings.
- Remove hardcoded `time_limit = null` override.
- Normalize input into `time_limit` seconds.

**Step 4: Run test to verify it passes**

Run: `php artisan test --filter=InstructorQuizSettingsValidationTest`  
Expected: PASS

**Step 5: Commit**

Run:
`git add app/Http/Requests/Instructor/StoreQuizRequest.php app/Http/Requests/Instructor/UpdateQuizRequest.php app/Http/Controllers/Instructor/QuizManagementController.php resources/views/instructor/quizzes/create.blade.php resources/views/instructor/quizzes/edit.blade.php tests/Feature/Instructor/InstructorQuizSettingsValidationTest.php`  
`git commit -m "feat: add quiz attempt limits and timer configuration"`

## Task 10: Enforce Attempt Limit and Timer Auto-Submit in Learner Flow

**Files:**
- Modify: `app/Http/Controllers/Learner/QuizController.php`
- Modify: `resources/views/quizzes/take.blade.php`
- Create: `tests/Feature/Learner/LearnerQuizAttemptLimitTest.php`
- Create: `tests/Feature/Learner/LearnerQuizTimerAutoSubmitTest.php`

**Step 1: Write the failing tests**

- Learner blocked after reaching attempt limit.
- Timer expiry triggers auto-submit behavior.

**Step 2: Run tests to verify they fail**

Run: `php artisan test --filter=LearnerQuizAttemptLimitTest`  
Run: `php artisan test --filter=LearnerQuizTimerAutoSubmitTest`  
Expected: FAIL

**Step 3: Write minimal implementation**

- Before quiz start/submit, enforce attempt count policy.
- Add timer script/form behavior that submits when countdown reaches zero.
- Ensure secure server-side fallback checks (never trust client timer alone).

**Step 4: Run tests to verify they pass**

Run: `php artisan test --filter=LearnerQuizAttemptLimitTest`  
Run: `php artisan test --filter=LearnerQuizTimerAutoSubmitTest`  
Expected: PASS

**Step 5: Commit**

Run:
`git add app/Http/Controllers/Learner/QuizController.php resources/views/quizzes/take.blade.php tests/Feature/Learner/LearnerQuizAttemptLimitTest.php tests/Feature/Learner/LearnerQuizTimerAutoSubmitTest.php`  
`git commit -m "feat: enforce learner quiz attempt caps and timer auto-submit"`

## Task 11: Implement Capacity Behavior (Block + Manual Queue Path)

**Files:**
- Modify: `app/Http/Controllers/Learner/ModuleController.php`
- Modify: `app/Models/ModuleEnrollment.php` (if helper scopes needed)
- Test: `tests/Feature/Learner/LearnerModuleCapacityBehaviorTest.php`

**Step 1: Write the failing test**

- At capacity, module cannot auto-approve enrollment.
- Enrollment request is routed to pending/manual queue path when applicable.

**Step 2: Run test to verify it fails**

Run: `php artisan test --filter=LearnerModuleCapacityBehaviorTest`  
Expected: FAIL

**Step 3: Write minimal implementation**

- Add capacity check against approved enrollment count.
- Apply block + queue decision consistent with enrollment_mode and current policy.

**Step 4: Run test to verify it passes**

Run: `php artisan test --filter=LearnerModuleCapacityBehaviorTest`  
Expected: PASS

**Step 5: Commit**

Run:
`git add app/Http/Controllers/Learner/ModuleController.php app/Models/ModuleEnrollment.php tests/Feature/Learner/LearnerModuleCapacityBehaviorTest.php`  
`git commit -m "feat: enforce module capacity blocking and manual queue routing"`

## Task 12: Add Password Update Coverage for Instructor Profile Context

**Files:**
- Modify: `resources/views/instructor/profile/edit.blade.php`
- Test: `tests/Feature/Instructor/InstructorPasswordUpdateSecurityTest.php`

**Step 1: Write the failing test**

- Assert password update requires current password from instructor context.

**Step 2: Run test to verify it fails**

Run: `php artisan test --filter=InstructorPasswordUpdateSecurityTest`  
Expected: FAIL

**Step 3: Write minimal implementation**

- Reuse existing secure route/controller and include proper form fields from instructor profile edit page.

**Step 4: Run test to verify it passes**

Run: `php artisan test --filter=InstructorPasswordUpdateSecurityTest`  
Expected: PASS

**Step 5: Commit**

Run:
`git add resources/views/instructor/profile/edit.blade.php tests/Feature/Instructor/InstructorPasswordUpdateSecurityTest.php`  
`git commit -m "test: verify secure password updates from instructor profile context"`

## Task 13: Backfill Existing Data Safely

**Files:**
- Create: `app/Console/Commands/BackfillInstructorProfileFromApplications.php`
- Modify: `app/Console/Kernel.php`
- Test: `tests/Feature/Console/BackfillInstructorProfileFromApplicationsTest.php`

**Step 1: Write the failing test**

- Assert command fills null profile educational/professional fields from latest approved application only.

**Step 2: Run test to verify it fails**

Run: `php artisan test --filter=BackfillInstructorProfileFromApplicationsTest`  
Expected: FAIL

**Step 3: Write minimal implementation**

- Add artisan command for one-time backfill.
- Keep idempotent behavior and skip non-approved applications.

**Step 4: Run test to verify it passes**

Run: `php artisan test --filter=BackfillInstructorProfileFromApplicationsTest`  
Expected: PASS

**Step 5: Commit**

Run:
`git add app/Console/Commands/BackfillInstructorProfileFromApplications.php app/Console/Kernel.php tests/Feature/Console/BackfillInstructorProfileFromApplicationsTest.php`  
`git commit -m "feat: add idempotent instructor profile backfill command"`

## Task 14: Full Verification and Docs Sync

**Files:**
- Modify: `docs/QUICK_TESTING_GUIDE.md` (if needed)
- Modify: `docs/changelogs/...` (if your release workflow requires)

**Step 1: Run focused suite**

Run:
- `php artisan test --testsuite=Feature --filter=Instructor`
- `php artisan test --filter=LearnerQuiz`
- `php artisan test --filter=ModuleCapacity`

Expected: PASS

**Step 2: Run broader regression set**

Run: `php artisan test`  
Expected: PASS

**Step 3: Update docs/changelog minimally**

- Document new fields and behavior notes.

**Step 4: Commit**

Run:
`git add docs/QUICK_TESTING_GUIDE.md docs/changelogs`  
`git commit -m "docs: capture instructor profile and module settings rollout notes"`

---

## Execution Notes

- DRY: avoid duplicating validation rules between create/update requests.
- YAGNI: do not implement ratings now.
- Keep application intake unchanged; only consume existing approved application fields.
- Preserve compatibility for legacy module premium behavior while introducing new pricing fields.

## Suggested Rollout Order

1. Schema tasks (1-3)
2. Profile seeding and profile UX tasks (4-6)
3. Module and entitlement tasks (7-8)
4. Quiz settings and learner enforcement tasks (9-10)
5. Capacity and backfill tasks (11-13)
6. Verification and docs (14)
