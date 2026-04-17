# Instructor Subscription Entitlements and Plan Management Integration Design

## 1. Purpose and Goals
Introduce a scalable instructor subscription system that reuses the existing subscription and entitlement architecture and is fully controlled by admin plan management.

Primary goals:
- support platform sustainability through structured instructor monetization
- provide clear free versus premium value for instructor growth
- avoid duplicate subscription logic
- keep all entitlement values dynamic and admin-configurable
- enforce limits consistently in service logic, policy boundaries, and instructor UI

## 2. Approved Product Decisions
The following decisions are approved:
- Plan architecture: reuse existing subscription records and distinguish by plan audience.
- Free baseline source: explicit admin-managed free instructor plan.
- Free published module limit counting: instructor-owned modules that are approved and published.
- Free paid-module learner cap: dynamic entitlement with free baseline default of 50.
- Free module learner cap: dynamic entitlement, admin-configurable per plan.
- Monetization permission model: split permissions into separate entitlements.
- Premium learner cap strategy: high quota by default, admin-editable.
- Interactive activity entitlements: excluded from this implementation phase.
- Publish limit enforcement checkpoint: publish and approval transition.
- Enrollment cap enforcement checkpoint: module settings and enrollment runtime.
- Instructor UX placement: module index header, create/edit modal, publish feedback, plus dedicated subscription offers page in instructor sidebar.
- Baseline visibility in admin: explicit free baseline feature panel.
- Existing instructor plans migration: add keys only, no entitlement rewrites.
- Compatibility policy: read legacy aliases, write canonical keys.
- Rollout strategy: soft rollout first, strict enforcement after validation.

## 3. Scope
In scope:
- instructor entitlement contract (canonical keys)
- dynamic free baseline and premium plan entitlement resolution
- service-layer limit and permission enforcement
- admin plan management integration for instructor quota and boolean entitlements
- instructor UX transparency and upgrade guidance
- dedicated instructor subscription offers page
- focused test coverage for enforcement, UI, and admin plan behavior

Out of scope:
- interactive activities entitlement enforcement
- payout engine redesign
- payment lifecycle architecture rewrite

## 4. Architecture Approach (Approved Approach 1)
Use a unified entitlement-first architecture that extends the current subscription and feature entitlement system.

Core principles:
- keep existing subscription lifecycle logic as the single subscription authority
- resolve instructor capabilities from plan entitlements using audience-aware plan selection
- treat free baseline as a first-class plan record, not hardcoded constants
- keep controllers orchestration-only and move logic to services

## 5. Instructor Entitlement Contract
### 5.1 Canonical Quota Keys
- instructor_published_modules_limit
- instructor_max_learners_per_free_module
- instructor_max_learners_per_paid_module

### 5.2 Canonical Boolean Keys
- instructor_can_publish_paid_modules
- instructor_can_receive_paid_enrollments
- instructor_can_view_earnings

### 5.3 Compatibility Policy
- writes use canonical keys only
- reads allow alias compatibility when old keys exist
- migration in this phase only adds missing keys and does not rewrite active instructor plans

## 6. Free Baseline Plan Design
The free instructor plan must be explicit and admin-managed in subscription plans.

Baseline defaults (editable by admin):
- published modules limit default: 3
- paid module learner cap default: 50
- free module learner cap default: admin-defined baseline value
- monetization booleans: admin-configurable

Resolution rule:
- if instructor has an eligible active instructor-audience paid plan, use it
- otherwise resolve capabilities from the free instructor baseline plan

## 7. Capability Resolution Service
Add an instructor capability service that provides a normalized API for instructor-side checks.

Proposed methods:
- getPublishedModuleLimit(User $instructor): ?int
- getLearnerCapForModule(User $instructor, string $accessType): ?int
- canPublishPaidModules(User $instructor): bool
- canReceivePaidEnrollments(User $instructor): bool
- canViewEarnings(User $instructor): bool
- getUsageSnapshot(User $instructor): array

Service responsibilities:
- resolve effective instructor plan (paid or free baseline)
- read entitlement booleans and quotas
- provide compatibility-aware feature lookup
- return clear decision payloads for UI and enforcement responses

## 8. Enforcement Design
### 8.1 Publish Limit Enforcement
Checkpoint: publish and approval transition.

Behavior:
- count published instructor-owned modules
- block transition to published state when published module quota reached
- return actionable feedback and upgrade prompt context

### 8.2 Enrollment Cap Enforcement
Checkpoints:
- module settings validation and update flow
- learner enrollment and paid purchase enrollment flow

Behavior:
- instructor cannot configure module enrollment limit above plan cap for module type
- runtime enrollment prevents exceeding effective cap
- paid-module cap and free-module cap resolved dynamically per plan

### 8.3 Monetization Permission Split Enforcement
- publish paid modules gate
- receive paid enrollments gate
- earnings visibility gate

Each gate is independently evaluated and surfaced clearly in UI.

## 9. Admin Plan Management Integration
Admin plan management must remain the single configuration surface.

Design updates:
- ensure instructor canonical feature keys exist in feature catalog
- ensure plan wizard and feature API expose instructor quota and boolean keys
- add explicit baseline free-plan feature visibility for instructor audience
- no hardcoded limit constants in domain logic

## 10. Instructor UX and Transparency
### 10.1 Module Index and Authoring UX
Show:
- current plan name
- published modules used versus limit
- learner cap rules by module type
- contextual upgrade prompts when blocked

### 10.2 Dedicated Subscription Offers Page
Add instructor panel menu item and page aligned with learner-side discoverability.

Page content:
- current plan summary
- free baseline versus premium comparison
- entitlement highlights and usage status
- upgrade actions filtered to instructor audience plans

### 10.3 Messaging Principles
- clear, non-punitive, growth-oriented copy
- explain what is blocked and why
- include next best action with upgrade path

## 11. Authorization and Consistency
- keep policy permissions as role and action boundary checks
- use capability service for plan-based entitlements and quotas
- keep behavior consistent across backend checks and Blade states

## 12. Rollout Strategy
Phase 1 (soft rollout):
- show warnings, limit indicators, and upgrade messaging
- collect logs on blocked intent paths
- keep change scope observable and reversible

Phase 2 (strict rollout):
- enforce hard blocks for publish and monetization constraints
- keep same UI messaging and upgrade guidance

## 13. Data and Migration Strategy
- add missing instructor feature catalog keys (idempotent)
- do not rewrite existing instructor paid entitlements in this phase
- preserve additive, reversible migration standards

## 14. Testing Strategy
### 14.1 Unit Tests
- capability service resolution for paid and free baseline
- quota and boolean entitlement behavior
- alias compatibility behavior

### 14.2 Feature Tests
- publish blocked at module limit
- enrollment cap enforced at settings and runtime
- split monetization permissions enforced independently
- admin plan management persists instructor entitlements
- instructor offers page visibility and content

### 14.3 Regression Coverage
- learner subscription behavior remains stable
- governance lifecycle behavior remains stable
- instructor module creation and review lifecycle remains stable

## 15. Acceptance Criteria
Design is successful when:
- instructor limits and permissions are fully dynamic from admin plans
- free baseline and premium behavior are clearly differentiated
- publish and enrollment constraints are enforced consistently
- instructor users can understand plan usage and upgrade options
- no duplicate subscription system is introduced
- rollout can move from soft to strict without architecture changes

---

Approval status: Approved by user on 2026-04-16 with Approach 1 and dynamic free baseline entitlement controls.