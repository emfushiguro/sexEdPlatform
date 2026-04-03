# 2026-03-29 Instructor Profile And Module Configuration Rollout

## Summary

Implemented end-to-end instructor profile integration and scalable module/quiz configuration with additive schema evolution.

## Delivered

- Instructor profile schema extension fields in instructor_profiles:
  - educational_background
  - professional_background
  - primary_expertise
  - expertise_tags (json)
  - years_experience
  - certifications (json)
  - profile_photo_path
- Module schema extension fields in modules:
  - access_type (free or paid)
  - price_amount
  - price_currency (default PHP)
  - enrollment_limit
- Quiz schema extension field in quizzes:
  - attempt_limit
- Instructor application approval now seeds instructor profile educational and professional background from approved application data.
- New instructor profile show/edit routes, controller, policy enforcement, and request validation.
- Module create/update now uses dedicated Form Requests and persists pricing/capacity settings.
- Paid module save is gated by entitlement, with admin override support.
- Quiz create/update now uses dedicated Form Requests and stores normalized timer values from hour/minute/second inputs.
- Learner quiz flow now enforces attempt limits and includes timer auto-submit fallback support.
- Learner module enrollment now enforces capacity by routing at-limit enrollments to pending/manual review queue.
- Added one-time backfill command:
  - instructor-profile:backfill-from-applications

## Verification

Focused verification:

- php artisan test --testsuite=Feature --filter=Instructor
- php artisan test --filter=LearnerQuiz
- php artisan test --filter=ModuleCapacity

Broader verification:

- php artisan test

Result:

- Focused suites: 65 passed, 0 failed
- Instructor suite: 61 passed (242 assertions)
- Learner quiz suite: 3 passed (8 assertions)
- Module capacity suite: 1 passed (4 assertions)
