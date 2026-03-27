# Instructor Profile and Scalable Module Configuration Design

**Date:** 2026-03-27  
**Status:** Approved  
**Approach:** Approach A (Monolith-Safe Evolution)

## Section 0: Scope, Goals, and Locked Decisions

### Goal
Enhance the instructor system by:
- introducing a complete instructor profile experience
- enabling secure instructor-managed profile editing
- expanding module and quiz configuration for pricing, capacity, attempts, and timers

### In Scope
- Instructor profile display and edit architecture
- Profile data sourcing between user, learner profile, instructor profile, and instructor application
- Module configuration for free/paid pricing (PHP), enrollment limits, and capacity behavior
- Quiz attempt limits and timer configuration using hour/minute/second input
- Security boundaries for profile edits and password updates

### Out of Scope
- Ratings implementation (placeholder only)
- Reworking the instructor application form fields
- Full payment checkout flow for paid modules (separate initiative)

### Locked Decisions
1. Shared identity source of truth is user + learner profile; instructor profile stores instructor-only data.
2. Full profile visibility is learners + admins.
3. Certifications are structured list + optional file references.
4. Location uses PSGC-coded region/province/city display.
5. Profile photo is optional with fallback avatar.
6. Expertise uses primary specialization + tags.
7. Learner metric uses unique approved learners.
8. Average rating is not implemented in this phase.
9. Paid modules require entitlement/admin permission.
10. Pricing uses access type and decimal amount with PHP currency.
11. Enrollment limit uses nullable integer (null = unlimited).
12. Capacity behavior is block immediate access and shift behavior to manual queue path.
13. Attempt limit is per learner, per quiz, lifetime.
14. Timer input supports hours/minutes/seconds; storage is normalized.
15. Timer expiry auto-submits attempt.
16. Ownership remains created_by + content_owner_type.
17. Security enforcement is Form Request + Policy + Service guard.
18. Password update requires current password verification.

## Section 1: Current-State Baseline and Gap Analysis

### Current Assets
- Instructor profile exists with minimal fields: `bio`, `specialization`, `credentials`.
- Learner profile already stores location and identity-adjacent data used across learning flows.
- Module supports `is_premium` and `enrollment_mode`, but no direct module-level PHP amount and no enrollment cap.
- Quiz already has `time_limit` in seconds but create/update currently force `null`.
- Secure password update flows exist and already enforce `current_password`.

### Gaps
- Instructor profile does not yet model complete educational/professional identity.
- Profile page architecture does not yet merge instructor role identity + learner activity context.
- No module-level price amount/currency strategy.
- No module enrollment capacity model.
- No quiz attempt cap model.
- Existing timer storage is present, but instructor tooling does not expose h/m/s controls.

## Section 2: Data Model Architecture (Instructor + Learner Relationship)

### Source-of-Truth Boundaries
- `users`: account identity, authentication, canonical names, birthdate/age lifecycle.
- `learner_profiles`: PSGC location and learner-side profile metadata.
- `instructor_profiles`: instructor-specific educational/professional presentation fields.
- `instructor_applications`: onboarding and compliance snapshot, immutable historical record.

### Relationship Contract
- One user can have both learner and instructor roles.
- One user can have one learner profile and one instructor profile.
- Instructor profile page is a composed read model:
  - personal/location from user + learner profile
  - instructor credentials from instructor profile
  - summary metrics from module, enrollment, and quiz aggregates

## Section 3: Instructor Profile Schema and Application-Seeded Data

### Critical Constraint
Do not add fields to current instructor application flow in this phase.

### Approved Data Fetch Rule
On approval/activation, fetch and seed from existing approved application only:
- school background from `instructor_applications.educational_background`
- professional background (optional) from `instructor_applications.bio`
- credential evidence references from existing document paths in `instructor_applications`

### No Application Flow Changes
- No added input fields in application request.
- No added migration columns in instructor applications for this phase.

### Instructor Profile Field Model (Phase Scope)
Additive schema extension in `instructor_profiles`:
- `educational_background` (nullable string)
- `professional_background` (nullable text)
- `primary_expertise` (nullable string)
- `expertise_tags` (nullable json)
- `years_experience` (nullable unsigned integer)
- `certifications` (nullable json)
- `profile_photo_path` (nullable string)

Existing fields retained:
- `bio` (mapped to professional bio copy shown in profile)
- `specialization` (can be normalized into primary_expertise)
- `credentials` (json for proof file references)

### Seeding Policy
- Initial profile values are seeded from latest approved instructor application.
- Missing optional fields stay null and are editable later in instructor profile management.
- Application row remains audit source; profile becomes editable presentation layer.

## Section 4: Location and Identity Normalization

### Display Source
- Full Name: `users` (existing composed name helper support)
- Age: calculated from user birthdate (existing age helper)
- Location: learner profile PSGC-linked fields (region/province/city resolved for display)
- Profile photo: instructor profile photo if set, else learner/avatar fallback, else default avatar

### Integrity Rule
Do not duplicate canonical personal identity fields in instructor profile.

## Section 5: Instructor Overview Metrics Contract

### Required Metrics
- Modules created: count of modules where `created_by = instructor_id`
- Total learners enrolled: count of distinct learner users with approved enrollment in instructor modules
- Total quizzes created: count of quizzes attached to instructor-owned modules/lessons
- Average rating: placeholder label (`Not yet available`)

### Query Notes
- Use aggregate queries with module ownership scope.
- Cache short-lived summary payload to avoid dashboard/profile N+1 load pressure.

## Section 6: Module Settings Schema Expansion

### Additive Module Fields
Extend `modules` table with:
- `access_type` enum(`free`,`paid`) default `free`
- `price_amount` decimal(10,2) nullable
- `price_currency` char(3) default `PHP`
- `enrollment_limit` unsigned integer nullable

### Compatibility
- Keep `is_premium` temporarily for compatibility and map from `access_type` in service layer.
- `price_amount` required when `access_type = paid`; null when free.

### Ownership Preservation
Continue using:
- `created_by`
- `content_owner_type`

## Section 7: Quiz Configuration Schema Expansion

### Data Model
Use existing `quizzes.time_limit` as normalized seconds storage.

Additive field:
- `attempt_limit` unsigned integer nullable (null = unlimited)

### Input Model
Instructor UI collects:
- `time_limit_hours`
- `time_limit_minutes`
- `time_limit_seconds`

Service/controller normalizes to:
- `time_limit = (hours * 3600) + (minutes * 60) + seconds`

### Runtime Behavior
- When timer expires, attempt is auto-submitted.
- Attempt cap check is evaluated per learner per quiz (lifetime count).

## Section 8: Core Business Rules and Lifecycle Behavior

### Paid Module Gate
- Paid module creation/editing is allowed only if instructor has required entitlement or admin override.

### Enrollment Capacity Rule (A + C)
- When approved enrollments reach `enrollment_limit`, do not auto-grant enrollment access.
- New requests route through manual approval queue behavior.
- If queueing is not applicable in context, return clear capacity-reached message.

### Validation Rules
- `price_amount >= 0.01` when paid
- `enrollment_limit >= 1` when provided
- `attempt_limit >= 1` when provided
- normalized time limit must be `>= 1 second` when timer is enabled

## Section 9: Secure Profile Editing Model

### Editable by Instructor
- profile photo
- location fields (through canonical profile routes)
- educational background
- professional background
- expertise and tags
- professional biography

### Restricted from Instructor Self-Edit
- user role
- instructor approval status
- system-generated metrics
- platform-generated records

### Enforcement Layers
- Form Request: input whitelist and rule constraints
- Policy: ownership and role checks
- Service guard: final server-side field filtering before persistence

## Section 10: Password Update Security

### Contract
- require current password for any password update
- enforce Laravel password strength defaults
- preserve secure hash flow already present in auth controllers

### UX Requirement
- profile page shows dedicated password update form with clear security notice

## Section 11: Controller, Service, Request, and Policy Boundaries

### Controllers
- Keep controllers thin (request validation + service orchestration only).

### Services
- Add/extend instructor profile service for composed read model and restricted update operations.
- Extend module service/controller path for pricing + capacity + entitlement checks.
- Extend quiz management path for attempt/timer normalization and persistence.

### Requests
- Introduce dedicated Form Requests for:
  - instructor profile update
  - module settings update/create enhancements
  - quiz settings update/create enhancements

### Policies
- Enforce ownership for profile and module/quiz management updates.

## Section 12: UI/UX Structure and Frontend Considerations

### Instructor Profile Page Sections
1. Personal Information
2. Educational Background
3. Professional Background / Expertise
4. Instructor Overview

### Edit UX
- Explicitly mark editable vs locked fields.
- Show origin hint for seeded fields (from application) where useful.

### Module Form UX
- Access type toggle (free/paid)
- PHP amount input for paid modules
- Enrollment limit switch (unlimited vs limited)

### Quiz Form UX
- Attempt limit selector (1, 3, unlimited + custom)
- h/m/s timer grouped inputs with normalization hint

## Section 13: Route, View, and API Contract Changes

### Routing
- Add instructor profile show/edit/update routes under instructor middleware group.

### Views
- New profile show/edit blade pages in instructor view namespace.
- Extend module create/edit blade with pricing and enrollment cap controls.
- Extend quiz create/edit blade with attempt limit and h/m/s timer controls.

### Payload Contract
- Keep existing route signatures stable where possible.
- Use additive request fields and nullable defaults for backward compatibility.

## Section 14: Migration, Backfill, and Rollout Strategy

### Migration Order
1. Extend instructor_profiles fields
2. Extend modules pricing/capacity fields
3. Extend quizzes attempt_limit field

### Backfill
- Existing modules default to `access_type=free`, `price_currency=PHP`, `enrollment_limit=null`.
- Existing quizzes default to `attempt_limit=null`; preserve current `time_limit` values.
- For instructors with approved applications, seed profile educational/professional fields from latest approved application where null.

### Rollout
- Deploy migrations first.
- Deploy code using null-safe defaults.
- Run one-time backfill command/job.

## Section 15: Testing Strategy

### Feature Tests
- Instructor profile page composition and visibility
- Profile update allows only editable fields
- Restricted field mutation attempts are ignored/blocked
- Module create/edit pricing validations and entitlement gate
- Enrollment limit behavior at capacity
- Quiz attempt-limit enforcement
- Quiz timer normalization and auto-submit behavior

### Unit Tests
- Profile seeding mapper from approved application
- Timer h/m/s normalization helper
- Capacity decision logic

### Regression Coverage
- Existing instructor module lifecycle tests remain green
- Existing learner quiz flow tests remain green

## Section 16: Scalability and Maintainability

### Design Hygiene
- Additive schema only; no destructive rewrites.
- Keep compatibility mapping for old boolean premium flag until deprecation phase.
- Centralize business rules in services to avoid controller drift.

### Future Extension Hooks
- ratings table integration into instructor overview
- richer module commerce checkout and discounting
- queue and waitlist enhancements on capacity overflow

## Section 17: Risks and Mitigations

### Risk: Legacy field drift (`is_premium` vs `access_type`)
Mitigation: service-level canonical mapping and transitional assertions.

### Risk: Capacity race conditions
Mitigation: transaction + conditional checks on approved enrollment count before granting access.

### Risk: Timer abuse or invalid values
Mitigation: strict normalization and boundary validation at request layer.

### Risk: Sensitive field tampering
Mitigation: multi-layer field whitelisting and policy enforcement.

## Section 18: Implementation Sequence Preview

1. Extend schema (profiles/modules/quizzes)
2. Add profile composition + update services
3. Add instructor profile routes/controllers/views
4. Add module settings persistence and validation rules
5. Add quiz attempt/timer persistence and enforcement
6. Add backfill from approved application into instructor profiles
7. Add tests and run full verification

## Final Decision Summary

Approach A is confirmed with one explicit Section 3 constraint:
- profile seeds from existing approved application data only
- no expansion of instructor application flow in this phase
