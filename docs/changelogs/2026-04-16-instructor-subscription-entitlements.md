# 2026-04-16 Instructor Subscription Entitlements

## Summary
Implemented the instructor subscription entitlement rollout end-to-end across validation, capability resolution, governance enforcement, learner purchase/enrollment checks, instructor offers UX wiring, and admin baseline transparency.

## Delivered
- Added plan-aware enrollment limit validation in `StoreModuleRequest` using `InstructorPlanCapabilityService` instead of hardcoded caps.
- Enforced strict paid-publish entitlement in `ContentAuthoringService` and surfaced validation failures in `Instructor\ModuleController`.
- Applied rollout-mode-aware publish quota checks in `ContentGovernanceService` and warning handling in `Admin\ContentReviewController`.
- Added earnings visibility entitlement gate in `Instructor\ModuleEarningsController`.
- Hardened paid enrollment checks and plan-aware cap resolution in `Learner\ModuleController` and `ModulePurchaseService`.
- Implemented instructor subscriptions offers backend in `Instructor\SubscriptionController`.
- Added instructor subscriptions route in `routes/instructor.php` and sidebar navigation link in `layouts/instructor-app.blade.php`.
- Updated instructor module modal UI to show dynamic free/paid plan caps and removed hardcoded max-20 messaging.
- Cleaned malformed instructor module card stats markup and retained plan-aware capacity indicator.
- Expanded admin subscription plans baseline banner to show concrete default entitlement values.
- Added rollout mode configuration support in `config/subscription_features.php` with backward-compatible fallback from `config/billing.php`.

## Tests Added or Updated
- Added: `tests/Feature/Admin/AdminInstructorPlanEntitlementDefaultsTest.php`
- Added: `tests/Unit/Services/InstructorPlanCapabilityServiceTest.php`
- Added: `tests/Feature/Instructor/InstructorModulePlanEnforcementTest.php`
- Added: `tests/Feature/Instructor/InstructorSubscriptionOffersPageTest.php`
- Added: `tests/Feature/Instructor/InstructorSubscriptionRolloutModeTest.php`
- Added: `tests/Feature/Learner/LearnerEnrollmentCapByInstructorPlanTest.php`
- Updated: `tests/Feature/Instructor/InstructorModuleConfigValidationTest.php` (plan-aware cap assertions)
- Updated: `tests/Feature/Instructor/InstructorPaidModuleEntitlementTest.php` (explicit soft rollout)

## Verification
1. `php artisan test tests/Unit/Services/SubscriptionServiceFeatureEntitlementTest.php tests/Unit/Services/InstructorPlanCapabilityServiceTest.php`
- Result: 7 passed (18 assertions)

2. `php artisan test tests/Feature/Admin/AdminInstructorPlanEntitlementDefaultsTest.php tests/Feature/Instructor/InstructorModuleConfigValidationTest.php tests/Feature/Instructor/InstructorPaidModuleEntitlementTest.php tests/Feature/Instructor/InstructorModulePlanEnforcementTest.php tests/Feature/Instructor/InstructorSubscriptionOffersPageTest.php tests/Feature/Instructor/InstructorSubscriptionRolloutModeTest.php`
- Result: 14 passed (48 assertions)

3. `runTests` on:
- `tests/Feature/Learner/LearnerEnrollmentCapByInstructorPlanTest.php`
- `tests/Feature/Learner/LearnerModuleCapacityBehaviorTest.php`
- `tests/Feature/Learner/LearnerPaidModulePurchaseFlowTest.php`
- Result: 7 passed, 0 failed

## Notes
- Existing unrelated dirty workspace changes were preserved and not modified.
