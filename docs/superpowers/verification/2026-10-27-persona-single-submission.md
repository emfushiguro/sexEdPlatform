# Persona Single Submission — Verification Record

Checked: 2026-10-27, 13:16 Asia/Taipei.

## Implemented behavior

- Government-ID learners and guardians submit ID/selfie once through Persona. Authoritative eligible reconciliation submits one pending manual-review application; no local Persona images are copied and no automatic approval occurs.
- Government eligibility still requires PH issuance, an exact mapped supported ID class, passed required resources, the current owned attempt and submission round. Completed and approved provider statuses may qualify; declined, foreign, mismatched, missing, and needs-review outcomes cannot submit.
- Evidence provenance is explicit: local, persona, or persona_selfie for local school/institution IDs with remote selfies. Existing rows default to local; old evidence and decisions are preserved.
- Admin detail pages show inline same-origin, authorized Persona previews with availability errors and private/no-store/nosniff delivery. Old local images are hidden when the current submission is Persona-only. Foreign-case, old-round and superseded previews fail closed.
- School/institution form selection and administrator comparison checkbox are fixed. A local selfie is not required when a same-round Persona selfie is used. These review requirements remain enforced when Persona starts are disabled.
- Completed but unsuccessful attempts explain mismatches and allow replacement with a corrected ID choice. Replacement retains the old attempt. Browser return works when a guardian webhook has already submitted the application.
- Test configuration excludes local Persona credentials and starts with Persona disabled; individual Persona tests explicitly enable synthetic configuration.

## Automated evidence

Combined regression command:

```powershell
php vendor/bin/phpunit --do-not-cache-result tests/Feature/Identity tests/Unit/Services/PersonaClientTest.php tests/Unit/Services/PersonaPolicyTest.php tests/Feature/Api/PersonaWebhookTest.php tests/Feature/Auth/PersonaGuardianIntegrationTest.php tests/Feature/Auth/GuardianIdentityVerificationFlowTest.php tests/Feature/Auth/ParentChildVerificationResubmissionTest.php tests/Feature/Admin/PersonaEvidenceTest.php tests/Feature/Admin/PersonaReviewTest.php tests/Feature/Admin/GuardianIdentityVerificationAdminTest.php tests/Feature/Admin/AdminParentChildVerificationUiTest.php tests/Feature/Admin/AdminParentChildVerificationModerationWorkflowTest.php
```

Result: **179 tests, 1,199 assertions, zero failures/errors, 2 PHPUnit deprecations** (2m58.664s).

After the final display-only fix that hides a previous local selfie on school resubmission, the covering new-flow, admin-review, admin-evidence, and learner-review suites were rerun:

```powershell
php vendor/bin/phpunit --do-not-cache-result tests/Feature/Identity/PersonaSingleSubmissionTest.php tests/Feature/Admin/PersonaReviewTest.php tests/Feature/Admin/PersonaEvidenceTest.php tests/Feature/Identity/LearnerIdentityReviewTest.php
```

Result: **37 tests, 302 assertions, zero failures/errors** (1m09.613s). The added test first reproduced the stale-image display before the fix and verifies that the old file remains stored. Independent static review returned no remaining findings after the school selection/comparison, disabled-toggle enforcement, guardian return race, and historical-image issues were addressed.

New regressions were first observed failing before fixes: automatic pending submission, school approval without a duplicate selfie, mismatch recovery, corrected document selection, frozen school selection, and webhook-first guardian return. Final combined coverage includes these regressions plus authorization, signed webhooks, policy denial, source-dependent approval gates, legacy local applications and dependent workflows.

Other checks:

- PHP syntax: 18 changed/new non-Blade PHP files passed.
- Focused Pint: 11 model/service/controller/test/migration files passed. Repository-wide formatting was not rerun; unrelated legacy style is outside this change.
- `git diff --check`: passed.
- `php artisan route:list --name=persona`: 7 intended routes registered.
- `npm.cmd run build -- --outDir .playwright-cli/persona-build-check-20261027`: passed (101 modules, 21.45s). Sandbox initially prevented esbuild from reading the parent directory; the identical elevated build passed. Output was isolated to preserve pre-existing build changes.
- Migration SQL inspection showed exactly two additive columns, both defaulting to local; no identity status or evidence deletion statements.
- `php artisan migrate --path=database/migrations/2026_10_27_000000_add_persona_evidence_source.php`: passed; batch 11 confirmed.

## Unrun live checks

No real Persona inquiry, signed-media download, webhook replay, SMTP delivery, live queue processing, authenticated browser walkthrough, accessibility audit, staging deployment, production activation, or repository-wide PHPUnit run was performed for this revision. HTTP/provider responses in automated tests are synthetic. Prior repository-wide failures are documented in the 2026-10-26 verification record and have not been reclassified as passing.

## Local retest

Restart the queue worker to load the revised application code. Keep the HTTPS tunnel, redirect allowlist and signed webhook configured. Complete a supported government-ID Sandbox inquiry whose simulated data returns PH and the same selected class. Confirm that return or refresh leads to pending review without a second upload. In Admin, open Guardian, Child & Learner Verifications, select the learner or guardian list, and review the inline Persona images before deciding. Test rejection followed by a new inquiry, webhook-first return, replacement after a mismatch, and the school/institution comparison checkbox.

Persona retention or unavailability may prevent previews. The page provides a generic error; administrators must not approve without reviewing required evidence. Sandbox does not establish real document or face-match accuracy.

No real .env values, provider identifiers, identity images, credentials, or signed URLs are included in this record. The unrelated pre-existing .gitignore and public/build changes are outside the implementation.
