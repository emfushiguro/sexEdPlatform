# Learner Subscription Entitlements Rebalance and Premium Translator Access Design

## 1. Purpose and Goals
Refine the learner subscription value structure so it is clear, fair, and sustainable while preserving free core learning access.

Primary goals:
- Rebalance premium learner entitlements into a minimal, meaningful set.
- Move both translator capabilities (text and voice speech) to paid plans only.
- Remove certificate download from paid gating and make it freely accessible.
- Keep enforcement consistent across backend service checks and learner UI states.
- Keep entitlement behavior dynamically managed through admin plan configuration.

## 2. Approved Product Decisions
The following decisions were approved:
- Migration scope: normalize all active learner paid plans.
- Canonical translator keys: `text_translator` and `voice_speech_translator`.
- Compatibility mode: keep legacy aliases readable, canonical keys for writes.
- Free translator UX: show locked teaser with upgrade CTA.
- Translator blocked response: JSON 403 with explicit entitlement messaging.
- Certificate download: fully free, no premium gate or middleware dependence.
- Existing certificate entitlement rows: disable for audit trace, do not delete.
- Paid learner entitlements: enforce exact 4-feature premium set.
- Learner subscription comparison: include and clearly surface free baseline features.
- Translator scope: all learner surfaces where translator is exposed.
- Admin learner entitlement editor: learner-focused feature set.
- Testing depth: update existing tests and add enforcement tests.

## 3. Scope
In scope:
- Learner entitlement contract update.
- Translator entitlement enforcement in backend and UI.
- Certificate download gate removal.
- Active learner paid-plan normalization migration.
- Learner subscription page comparison and baseline visibility enhancements.
- Admin learner plan management alignment to canonical entitlement model.
- Unit and feature test updates for changed behavior.

Out of scope:
- Payment lifecycle architecture changes.
- Role/permission system changes.
- Cross-role non-learner entitlement redesign.

## 4. Current State Findings
Current implementation has mixed entitlement behavior:
- Core premium keys exist in centralized constants and service checks.
- Translator endpoints are currently enrollment-gated but not subscription-gated.
- Learner certificate download is gated by a paid entitlement check.
- Learner certificate views also conditionally hide download actions by premium status.
- Subscription highlights and comparison still reference certificate premium value.
- Feature catalog contains legacy aliases that remain in active compatibility paths.

Impact:
- Free vs paid boundaries are inconsistent across translation and certificate flows.
- Premium value messaging is diluted by certificate gating.
- Risk of backend and UI entitlement drift remains without canonical enforcement.

## 5. Target Entitlement Model
### 5.1 Premium Learner Canonical Entitlements
Learner paid plans should expose exactly these premium differentiators:
1. `unlimited_quiz_shields`
2. `unlimited_username_change`
3. `text_translator`
4. `voice_speech_translator`

### 5.2 Free Baseline Principles
- Free plan retains core learning access and completion progression.
- Certificate generation, viewing, and PDF download are baseline free capabilities.
- Premium value emphasizes convenience and enhanced experience, not access to core outcomes.

### 5.3 Compatibility Policy
- Keep legacy keys and aliases readable in resolver logic for transition safety.
- New writes (admin save/migration) use canonical keys.
- Legacy certificate premium keys remain disabled and unused for enforcement.

## 6. Backend Enforcement Design
### 6.1 Translator Access Control
Endpoints covered:
- topic text translation
- page translation
- speech synthesis

Access pattern:
1. Check authenticated learner entitlement using canonical key.
2. If missing, return JSON 403 with explicit entitlement code/message.
3. Only proceed to module enrollment/content visibility checks when entitlement passes.

This guarantees no free-tier fallback execution path for translation services.

### 6.2 Certificate Download Access
Certificate download should be governed by:
- ownership validation
- certificate/module eligibility constraints

It should not be governed by:
- `downloadable_certificates` entitlement
- generic premium middleware gate
- premium-only UI branching

## 7. Data Migration Design
### 7.1 Migration Responsibilities
Create a dedicated migration that:
1. Upserts canonical translator features in `feature_catalog`.
2. Identifies active learner paid plans.
3. Normalizes enabled entitlements to exact 4 canonical premium keys.
4. Disables certificate premium entitlement rows (`downloadable_certificates` and legacy aliases).

### 7.2 Safety and Idempotence
- Migration must be re-runnable without duplicate drift.
- Use transactions for each plan update batch.
- Preserve existing rows where needed, favor disable over delete for audit traceability.

### 7.3 Paid Plan Selection Rules
Active learner paid plans are those with:
- learner audience
- active status
- non-free pricing behavior

## 8. Admin Plan Management Alignment
### 8.1 Learner Entitlement Definitions
Admin learner plan flows should:
- prioritize learner-relevant canonical features
- avoid reintroducing deprecated certificate premium keys in new writes
- preserve dynamic catalog behavior while constraining learner recommendation set

### 8.2 New Plan Baseline Behavior
When creating a learner plan, comparison display should clearly communicate:
- all free baseline features are included
- premium entitlements are additional enhancements

This is a display and communication requirement while keeping data model focused on premium differentiators.

## 9. Learner Subscription UI and Comparison Design
### 9.1 Entitlement Highlights
Update highlights to reflect:
- Unlimited username changes
- Unlimited quiz shields
- Text translator
- Voice speech translator

Remove certificate download as premium highlight.

### 9.2 Comparison Matrix
Enhance matrix to:
- always include free baseline feature rows
- show paid plans as baseline plus premium additions
- keep labels clear for free baseline versus premium add-ons

### 9.3 Translator UI States
- Free users: locked premium teaser state with upgrade CTA.
- Paid users: full interaction.
- UI state must align with backend 403 behavior to avoid mismatch.

## 10. Legacy Compatibility and Deprecation Path
Short-term:
- keep resolver aliases for old keys
- disable deprecated certificate premium rows

Mid-term:
- monitor usage and data consistency
- remove legacy aliases in a dedicated cleanup phase once safe

## 11. Testing Strategy
### 11.1 Unit Tests
- entitlement resolution for canonical + alias compatibility
- quota/boolean behavior unchanged for unaffected keys

### 11.2 Feature Tests
- free learner translator requests return 403
- paid learner translator requests pass normal checks
- certificate download succeeds for free eligible users
- learner paid entitlement activation asserts translator entitlements and no certificate dependency
- migration normalization verifies exact 4-feature paid set and certificate row disable behavior

### 11.3 UI Assertions
- subscription comparison includes free baseline rows
- premium highlights reflect updated four-feature set
- certificate download actions render consistently without premium branch

## 12. Rollout and Verification Plan
Recommended rollout order:
1. Canonical key and compatibility updates.
2. Migration for active learner paid plans.
3. Translator backend enforcement.
4. Certificate gate removal.
5. Learner and admin UI alignment.
6. Test suite updates and execution.

Verification should include targeted entitlement, translator, certificate, and subscription page test groups, followed by broader regression checks for learner subscription flows.

## 13. Risks and Mitigations
- Risk: hidden legacy dependencies on certificate premium key.
  - Mitigation: preserve aliases, disable instead of delete, update tests comprehensively.
- Risk: UI lock state mismatch with backend behavior.
  - Mitigation: backend 403 as source of truth and matching UI branch updates.
- Risk: plan normalization affects custom paid learner configurations.
  - Mitigation: scope strictly to active learner paid plans and keep migration deterministic.

## 14. Acceptance Criteria
Design is successful when:
- active learner paid plans are normalized to the approved 4-feature premium set
- translator features are paid-only in backend and UI
- certificate download is fully free and consistent
- learner subscription comparison clearly includes free baseline features and premium additions
- admin learner plan flows remain dynamic and aligned to canonical entitlement writes
- updated tests confirm policy and guard against regressions

---

Approval status: Approved by user on 2026-04-16 before documentation finalization.
