# Learner Subscription Entitlements Rebalance Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Rebalance learner paid entitlements to the approved 4-feature set, enforce translators as premium-only, make certificate downloads free for all learners, and align admin + learner UI with dynamic entitlement behavior.

**Architecture:** Keep the subscription service and feature catalog as the dynamic source of truth, add a safe migration to normalize active learner paid plans, and update service/controller/view checks so backend policy and UI states always match. Preserve legacy aliases for transition safety but write canonical keys only.

**Tech Stack:** Laravel 12, PHP 8.2, Blade, Alpine.js, Tailwind CSS v3, PHPUnit.

---

I'm using the writing-plans skill to create the implementation plan.

## Task 1: Add canonical learner entitlement keys and compatibility aliases

**Files:**
- Modify: `app/Support/SubscriptionFeatureKeys.php`
- Modify: `app/Services/SubscriptionService.php`
- Test: `tests/Unit/Services/SubscriptionServiceFeatureEntitlementTest.php`

**Step 1: Write the failing test**
Add assertions that `hasFeature()` resolves the new canonical translator keys and still resolves old aliases for backward compatibility.

**Step 2: Run test to verify it fails**
Run: `php artisan test --filter=SubscriptionServiceFeatureEntitlementTest`
Expected: FAIL with missing canonical key/alias coverage.

**Step 3: Write minimal implementation**
1. Add canonical constants for `text_translator` and `voice_speech_translator`.
2. Keep current alias support and include translator compatibility mapping in `featureAliases()`.
3. Ensure canonical keys are preferred for all new checks.

**Step 4: Run test to verify it passes**
Run: `php artisan test --filter=SubscriptionServiceFeatureEntitlementTest`
Expected: PASS.

**Step 5: Commit**
```bash
git add app/Support/SubscriptionFeatureKeys.php app/Services/SubscriptionService.php tests/Unit/Services/SubscriptionServiceFeatureEntitlementTest.php
git commit -m "feat(subscription): add canonical translator entitlement keys"
```

## Task 2: Add migration to normalize active learner paid entitlements

**Files:**
- Create: `database/migrations/2026_04_16_150000_normalize_learner_paid_entitlements_for_translator_and_certificate_policy.php`
- Test: `tests/Feature/Learner/LearnerPaidEntitlementNormalizationMigrationTest.php`

**Step 1: Write the failing test**
Create a feature test that seeds active learner paid plans with mixed entitlements and verifies migration result:
1. Only the 4 approved premium features remain enabled.
2. Certificate premium keys are disabled (not deleted).
3. Translator features exist and are enabled for scoped plans.

**Step 2: Run test to verify it fails**
Run: `php artisan test --filter=LearnerPaidEntitlementNormalizationMigrationTest`
Expected: FAIL because normalization migration does not exist.

**Step 3: Write minimal implementation**
1. Upsert feature catalog records for `text_translator` and `voice_speech_translator`.
2. Scope to active learner paid plans.
3. Disable non-approved learner premium entitlements for scoped plans.
4. Enable exactly:
   - `unlimited_quiz_shields`
   - `unlimited_username_change`
   - `text_translator`
   - `voice_speech_translator`
5. Disable `downloadable_certificates` and legacy certificate aliases.

**Step 4: Run test to verify it passes**
Run: `php artisan test --filter=LearnerPaidEntitlementNormalizationMigrationTest`
Expected: PASS.

**Step 5: Commit**
```bash
git add database/migrations/2026_04_16_150000_normalize_learner_paid_entitlements_for_translator_and_certificate_policy.php tests/Feature/Learner/LearnerPaidEntitlementNormalizationMigrationTest.php
git commit -m "feat(subscription): normalize active learner paid entitlements"
```

## Task 3: Enforce translator premium entitlement in learner translation controller

**Files:**
- Modify: `app/Http/Controllers/Learner/TopicTranslationController.php`
- Test: `tests/Feature/Learner/LearnerTranslatorEntitlementEnforcementTest.php`

**Step 1: Write the failing test**
Add tests for all translator endpoints:
1. Free learner receives JSON 403 with clear premium message/code.
2. Paid learner passes entitlement gate and reaches existing flow.

**Step 2: Run test to verify it fails**
Run: `php artisan test --filter=LearnerTranslatorEntitlementEnforcementTest`
Expected: FAIL because translator endpoints are not entitlement-gated.

**Step 3: Write minimal implementation**
1. Add service-layer entitlement checks in `translateText`, `translatePage`, and `synthesizeSpeech`.
2. Return JSON 403 response with clear entitlement code/message.
3. Keep existing enrollment and publication checks after entitlement pass.

**Step 4: Run test to verify it passes**
Run: `php artisan test --filter=LearnerTranslatorEntitlementEnforcementTest`
Expected: PASS.

**Step 5: Commit**
```bash
git add app/Http/Controllers/Learner/TopicTranslationController.php tests/Feature/Learner/LearnerTranslatorEntitlementEnforcementTest.php
git commit -m "feat(learner): enforce premium translator entitlements"
```

## Task 4: Remove certificate premium gating in learner backend flow

**Files:**
- Modify: `app/Http/Controllers/Learner/CertificateController.php`
- Test: `tests/Feature/Learner/LearnerCertificateDownloadAccessTest.php`

**Step 1: Write the failing test**
Add tests that:
1. Eligible free learner can download own certificate PDF.
2. Ownership checks still block cross-user access.
3. No redirect to subscription upgrade for certificate download.

**Step 2: Run test to verify it fails**
Run: `php artisan test --filter=LearnerCertificateDownloadAccessTest`
Expected: FAIL because download is still premium-gated.

**Step 3: Write minimal implementation**
1. Remove entitlement gate for certificate download.
2. Keep ownership and certificate validity checks.
3. Keep PDF generation/storage behavior unchanged.

**Step 4: Run test to verify it passes**
Run: `php artisan test --filter=LearnerCertificateDownloadAccessTest`
Expected: PASS.

**Step 5: Commit**
```bash
git add app/Http/Controllers/Learner/CertificateController.php tests/Feature/Learner/LearnerCertificateDownloadAccessTest.php
git commit -m "refactor(certificates): make learner certificate downloads free"
```

## Task 5: Remove certificate premium UI branches from learner surfaces

**Files:**
- Modify: `resources/views/learner/certificates/show.blade.php`
- Modify: `resources/views/learner/certificates/index.blade.php`
- Modify: `resources/views/learner/modules/show.blade.php`
- Modify: `resources/views/learner/lessons/show.blade.php`
- Test: `tests/Feature/Learner/LearnerCertificateUiParityTest.php`

**Step 1: Write the failing test**
Add view assertions for free learner scenarios confirming download actions are present where certificate exists.

**Step 2: Run test to verify it fails**
Run: `php artisan test --filter=LearnerCertificateUiParityTest`
Expected: FAIL because UI still shows premium-only branches.

**Step 3: Write minimal implementation**
1. Remove `isPremium()` branching around certificate download actions.
2. Remove upgrade-to-download copy.
3. Keep existing style and flow language.

**Step 4: Run test to verify it passes**
Run: `php artisan test --filter=LearnerCertificateUiParityTest`
Expected: PASS.

**Step 5: Commit**
```bash
git add resources/views/learner/certificates/show.blade.php resources/views/learner/certificates/index.blade.php resources/views/learner/modules/show.blade.php resources/views/learner/lessons/show.blade.php tests/Feature/Learner/LearnerCertificateUiParityTest.php
git commit -m "refactor(learner-ui): remove certificate premium download branches"
```

## Task 6: Add translator lock-state teaser UI for free learners

**Files:**
- Modify: `resources/views/learner/partials/global-page-translator.blade.php`
- Modify: `resources/views/layouts/learner-app.blade.php`
- Modify: `resources/views/layouts/learner-fullscreen.blade.php`
- Modify: `resources/views/learner/lessons/partials/topic-page.blade.php`
- Test: `tests/Feature/Learner/LearnerTranslatorUiGateTest.php`

**Step 1: Write the failing test**
Add tests asserting:
1. free learners see locked teaser/upgrade UI state.
2. paid learners see active translator controls.

**Step 2: Run test to verify it fails**
Run: `php artisan test --filter=LearnerTranslatorUiGateTest`
Expected: FAIL because translator controls are currently always visible.

**Step 3: Write minimal implementation**
1. Pass entitlement flags from layout-level context.
2. Render locked teaser when entitlement missing.
3. Prevent translation requests from UI when locked.
4. Preserve existing visual language and accessibility states.

**Step 4: Run test to verify it passes**
Run: `php artisan test --filter=LearnerTranslatorUiGateTest`
Expected: PASS.

**Step 5: Commit**
```bash
git add resources/views/learner/partials/global-page-translator.blade.php resources/views/layouts/learner-app.blade.php resources/views/layouts/learner-fullscreen.blade.php resources/views/learner/lessons/partials/topic-page.blade.php tests/Feature/Learner/LearnerTranslatorUiGateTest.php
git commit -m "feat(learner-ui): add premium lock states for translator features"
```

## Task 7: Update learner subscription highlights to the new 4-feature premium model

**Files:**
- Modify: `app/Http/Controllers/Learner/SubscriptionController.php`
- Test: `tests/Feature/Learner/LearnerSubscriptionPageUiParityTest.php`

**Step 1: Write the failing test**
Add assertions that entitlement highlights show:
1. unlimited shields
2. unlimited username changes
3. text translator
4. voice speech translator
And no certificate download premium highlight.

**Step 2: Run test to verify it fails**
Run: `php artisan test --filter=LearnerSubscriptionPageUiParityTest`
Expected: FAIL due old highlight mapping.

**Step 3: Write minimal implementation**
1. Replace certificate highlight with translator highlights.
2. Keep descriptions clear for free vs paid boundaries.
3. Keep all value text dynamic via entitlement checks.

**Step 4: Run test to verify it passes**
Run: `php artisan test --filter=LearnerSubscriptionPageUiParityTest`
Expected: PASS.

**Step 5: Commit**
```bash
git add app/Http/Controllers/Learner/SubscriptionController.php tests/Feature/Learner/LearnerSubscriptionPageUiParityTest.php
git commit -m "feat(subscription): align learner highlights to translator-first premium value"
```

## Task 8: Improve learner subscription comparison with explicit free baseline feature visibility

**Files:**
- Modify: `app/Http/Controllers/Learner/SubscriptionController.php`
- Modify: `resources/views/subscriptions/index.blade.php`
- Test: `tests/Feature/Learner/LearnerSubscriptionComparisonBaselineTest.php`

**Step 1: Write the failing test**
Add assertions that the comparison UI clearly includes free baseline features and that paid cards communicate baseline plus premium additions.

**Step 2: Run test to verify it fails**
Run: `php artisan test --filter=LearnerSubscriptionComparisonBaselineTest`
Expected: FAIL because baseline details are not sufficiently explicit.

**Step 3: Write minimal implementation**
1. Ensure free baseline features are always included in matrix rows.
2. Improve labels/copy so premium plans indicate baseline plus premium additions.
3. Keep existing component structure and style system.

**Step 4: Run test to verify it passes**
Run: `php artisan test --filter=LearnerSubscriptionComparisonBaselineTest`
Expected: PASS.

**Step 5: Commit**
```bash
git add app/Http/Controllers/Learner/SubscriptionController.php resources/views/subscriptions/index.blade.php tests/Feature/Learner/LearnerSubscriptionComparisonBaselineTest.php
git commit -m "feat(subscription-ui): surface free baseline features in learner comparison"
```

## Task 9: Align admin learner entitlement definitions for new plan creation/editing

**Files:**
- Modify: `app/Http/Controllers/Admin/UnifiedSubscriptionAdminController.php`
- Modify: `app/Http/Controllers/Admin/SubscriptionPlanAdminController.php`
- Modify: `database/seeders/FeatureCatalogSeeder.php`
- Modify: `config/subscription_features.php`
- Test: `tests/Feature/Admin/AdminLearnerPlanEntitlementDefaultsTest.php`

**Step 1: Write the failing test**
Add tests that learner plan management:
1. exposes learner-relevant canonical entitlement keys.
2. includes translator premium options.
3. does not present certificate premium gating as required value proposition.
4. supports baseline-plus messaging for new learner plans.

**Step 2: Run test to verify it fails**
Run: `php artisan test --filter=AdminLearnerPlanEntitlementDefaultsTest`
Expected: FAIL due old learner entitlement definitions and labels.

**Step 3: Write minimal implementation**
1. Update learner entitlement defaults/definitions to canonical set.
2. Add translator features in catalog and labels.
3. Keep dynamic catalog-driven behavior and avoid hardcoded plan slugs.

**Step 4: Run test to verify it passes**
Run: `php artisan test --filter=AdminLearnerPlanEntitlementDefaultsTest`
Expected: PASS.

**Step 5: Commit**
```bash
git add app/Http/Controllers/Admin/UnifiedSubscriptionAdminController.php app/Http/Controllers/Admin/SubscriptionPlanAdminController.php database/seeders/FeatureCatalogSeeder.php config/subscription_features.php tests/Feature/Admin/AdminLearnerPlanEntitlementDefaultsTest.php
git commit -m "feat(admin-plans): align learner entitlement defaults with new premium structure"
```

## Task 10: Update entitlement activation and parity tests for new premium contract

**Files:**
- Modify: `tests/Feature/Learner/LearnerSubscriptionEntitlementsActivationTest.php`
- Modify: `tests/Feature/Learner/LearnerSubscriptionExpiryEntitlementFallbackTest.php`
- Modify: `tests/Unit/Services/EntitlementServiceTest.php`

**Step 1: Write the failing test updates**
Adjust expectations so paid entitlements assert translator access and no longer require certificate premium entitlement.

**Step 2: Run test to verify it fails**
Run: `php artisan test --filter=LearnerSubscriptionEntitlementsActivationTest`
Expected: FAIL due outdated certificate assertions.

**Step 3: Write minimal implementation**
1. Replace certificate entitlement assertions with translator entitlement assertions.
2. Keep fallback behavior assertions for expired plans.
3. Keep alias compatibility tests where necessary.

**Step 4: Run test to verify it passes**
Run: `php artisan test --filter=LearnerSubscriptionEntitlementsActivationTest`
Expected: PASS.

**Step 5: Commit**
```bash
git add tests/Feature/Learner/LearnerSubscriptionEntitlementsActivationTest.php tests/Feature/Learner/LearnerSubscriptionExpiryEntitlementFallbackTest.php tests/Unit/Services/EntitlementServiceTest.php
git commit -m "test(subscription): update entitlement contract expectations for translator features"
```

## Task 11: Add focused translator and certificate regression tests

**Files:**
- Create: `tests/Feature/Learner/LearnerTranslatorApiEntitlementTest.php`
- Create: `tests/Feature/Learner/LearnerCertificateDownloadFreeAccessTest.php`

**Step 1: Write the failing tests**
1. Confirm all translator endpoints 403 for free learners and succeed for entitled learners.
2. Confirm certificate download route is accessible to free eligible owners.

**Step 2: Run test to verify it fails**
Run: `php artisan test --filter=LearnerTranslatorApiEntitlementTest`
Expected: FAIL before implementation complete.

**Step 3: Write minimal implementation updates if needed**
Patch any remaining guard/message inconsistencies discovered by tests.

**Step 4: Run tests to verify they pass**
Run:
- `php artisan test --filter=LearnerTranslatorApiEntitlementTest`
- `php artisan test --filter=LearnerCertificateDownloadFreeAccessTest`
Expected: PASS.

**Step 5: Commit**
```bash
git add tests/Feature/Learner/LearnerTranslatorApiEntitlementTest.php tests/Feature/Learner/LearnerCertificateDownloadFreeAccessTest.php
git commit -m "test(learner): add translator entitlement and free certificate download regressions"
```

## Task 12: Run verification suite and document outcomes

**Files:**
- Modify: `docs/changelogs/2026-04-16-learner-subscription-entitlements-rebalance.md`

**Step 1: Run targeted verification**
Run:
- `php artisan test --filter=SubscriptionServiceFeatureEntitlementTest`
- `php artisan test --filter=LearnerSubscriptionEntitlementsActivationTest`
- `php artisan test --filter=LearnerTranslatorEntitlementEnforcementTest`
- `php artisan test --filter=LearnerCertificateDownloadAccessTest`
- `php artisan test --filter=LearnerSubscriptionPageUiParityTest`

Expected: all PASS.

**Step 2: Run broader learner/admin subscription checks**
Run:
- `php artisan test tests/Feature/Learner`
- `php artisan test tests/Feature/Admin --filter=Subscription`

Expected: PASS or clearly reported pre-existing failures.

**Step 3: Update changelog**
Document:
1. entitlement contract change
2. translator premium gating
3. certificate free-access change
4. UI comparison/baseline updates
5. migration behavior
6. test evidence

**Step 4: Commit**
```bash
git add docs/changelogs/2026-04-16-learner-subscription-entitlements-rebalance.md
git commit -m "docs(changelog): record learner entitlement rebalance and enforcement updates"
```

## Task 13: Final branch validation and handoff prep

**Files:**
- Modify: `docs/plans/2026-04-16-learner-subscription-entitlements-rebalance-implementation-plan.md` (optional status notes)

**Step 1: Confirm no unintended file drift**
Run: `git status --short --untracked-files=all`
Expected: only intended files are modified.

**Step 2: Confirm migration execution path**
Run:
- `php artisan migrate --pretend`
- `php artisan migrate`
Expected: normalization migration applies cleanly.

**Step 3: Prepare review summary**
Summarize:
1. exact files touched
2. final entitlement matrix
3. verification command outputs
4. known risks/residual follow-ups

**Step 4: Commit plan note if changed**
```bash
git add docs/plans/2026-04-16-learner-subscription-entitlements-rebalance-implementation-plan.md
git commit -m "docs(plan): annotate implementation completion checkpoints"
```
