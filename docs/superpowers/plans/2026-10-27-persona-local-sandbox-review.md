# Local Persona Sandbox manual review

Approved scope: local-only fallback for completed Persona Sandbox government-ID/selfie submissions, using the existing administrator verification queues and remote image proxy. Preserve current branch, unrelated edits, development data, reported country/class, and single submission. No production activation or real `.env` edits.

## Design

- Explicit `PERSONA_LOCAL_MANUAL_REVIEW` setting defaults off; requires application environment `local`, Persona enabled in `sandbox`, a current Sandbox attempt, supported government-ID choice, authoritative completed/approved inquiry, and passed government-ID/selfie resources with identifiers and country/class metadata.
- Persist outcome `sandbox_review` separately from normal policy eligibility. Preserve authoritative country/class and policy flags. Normal failures, missing resources, declined/expired inquiries and foreign/superseded ownership remain blocked.
- Submit once into the existing pending queue. Persist a boolean test-only marker on learner cases and guardian users using a forward-only additive migration. Audit submission and review with the exception reason. Disabling the setting/environment blocks fallback approval and recognition of existing test-only approvals.
- Administrator must confirm Philippine issuance, selected ID type, and ID/selfie portrait comparison server-side before approving a fallback case. Display clear Sandbox/manual-review notices, retained reported metadata, and existing protected Persona image previews. Keep legacy and school paths intact.
- Existing current completed mismatches may be refreshed without new uploads. Preserve finalized decisions and submission rounds.

## Tasks

- [x] Add failing policy/flow/authorization tests, including production/disabled-mode rejection and test approval provenance.
- [x] Add configuration, additive provenance columns, shared eligibility checks and submission/approval/access guards.
- [x] Extend existing admin and applicant screens and wire review confirmations; no duplicate dashboard.
- [x] Run focused tests serially on `cc_db_test`, formatting and diff checks; review changes.
- [x] Apply migration non-destructively; test browser flow on isolated synthetic accounts/provider fixtures with Playwright; verify the existing real inquiry after the user enables the local setting (already pending; no forced replay required).
- [x] Update runbook and report automated/browser evidence separately from real provider verification.

## Results

See `docs/superpowers/verification/2026-10-27-persona-local-sandbox-review.md`. The final regression command passed 240 tests / 1,846 assertions. The user enabled the setting and restarted the worker. Their existing attempt is `sandbox_review`, its case is pending with the test-only marker and one submitted audit, and all three real provider-hosted images are retrievable. No test-only approval was granted to that real registration.
