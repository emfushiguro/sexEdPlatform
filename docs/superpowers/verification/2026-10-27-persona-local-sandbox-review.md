# Local Persona Sandbox review verification — 2026-10-27

## Implemented behavior

The approved local-only exception moves current, supported, completed/approved Sandbox government-ID/selfie submissions into pending administrator review when simulated country/class data fails the normal policy. It preserves the reported values and strict policy flags, records `sandbox_review`, persists test-only provenance, and audits submission and approval. Missing/failed resources and declined/expired inquiries do not qualify. Default configuration remains strict and off for this exception.

Existing learner and guardian queues, protected image proxy, decision forms, rejection/resubmission rounds and onboarding routes are reused. All three manual confirmations are enforced in controllers and services. Guardian confirmation forms bind to the reviewed attempt; stale forms cannot approve a newer attempt, even when the newer result becomes normally eligible. Disabling the exception or leaving local mode prevents recognition of its approvals, including guardian relationship access/submission and sparse user metadata queries.

## Automated evidence

Final serial MySQL `cc_db_test` regression: **240 tests, 1,846 assertions, zero failures/errors**. PHPUnit reported three deprecation notices from the selected suites.

Covered Identity feature tests, Persona client/policy, guardian integration/identity/onboarding, parent/child registration and resubmission, admin review/media, guardian relationship lifecycle/evidence, and parent invitation flows. New regressions were observed failing before fixes for the country/type fallback, mandatory manual confirmations, production/disabled-mode access, stale guardian confirmations, relationship authorization/access, sparse-model provenance, existing-attempt recovery and test-only onboarding copy.

Scoped Pint and `git diff --check` passed. The full repository suite and a new asset build were not run; existing unrelated build outputs were preserved.

## Browser evidence

Playwright used a separate PHP server on `127.0.0.1:8011`, isolated `cc_db_test` records, synthetic accounts, an array mailer, mocked Persona HTTP responses and an explicitly mocked hosted verification page. The application routes, authentication, CSRF, database transitions, authorization and admin forms were real. Real Persona camera capture, hosting, accuracy and webhook delivery were not part of this browser run.

Observed:

- New adult account created through both registration forms; signed email verification accepted.
- All twelve Philippine ID choices were present.
- National ID selection with a mocked passed `US`/`dl` Persona result reached pending local manual review.
- Pending learner and guardian dashboard access was blocked.
- Existing learner and guardian admin queues opened their review pages.
- Three synthetic image previews loaded through the protected proxy for each account; upstream URLs were not shown.
- Direct browser approval requests missing confirmations returned 422 for both account types.
- Completed manual confirmations approved the synthetic learner; the learner continued to profile completion.
- A synthetic guardian selected TIN Card; its queue label remained correct, manual approval succeeded, and the applicant advanced to guardian onboarding.

Browser timing/assertion and hosted-fixture interception issues were corrected during QA. The later stale-attempt and provenance hardening was verified by the final automated regression, including rendered guardian attempt-ID binding. Local screenshot artifacts are under `.playwright-cli/persona-*.png`; all pictured accounts/evidence are synthetic.

The existing frontend also attempted an unavailable Reverb WebSocket at localhost:8080. This was separate from the verified registration/review HTTP flow.

## Real local/provider checks

The additive `2026_10_27_100000_add_local_persona_review_markers` migration ran normally in development, batch 12. Counts stayed at 11 users and one learner identity case; no development rows were reset or deleted.

The user enabled `PERSONA_LOCAL_MANUAL_REVIEW=true`, cleared configuration and restarted the worker. A subsequent read-only check confirmed local application/Sandbox configuration and that the existing completed inquiry was already `sandbox_review`; the original reported values remained `us` / `dl`. The case was pending with its test-only marker and exactly one submitted audit. The check did not approve the real registration or force another submission.

Authenticated provider reads and bounded transient media downloads confirmed that its real selfie, ID front and ID back were available as JPEGs. Only MIME types and byte counts were reported; no image files, signed URLs, raw payloads or credentials were saved or printed.

## Limits

This validates local registration/review behavior, not document authenticity, real country/type extraction, selfie matching, liveness, production activation, exhaustive accessibility, or a fresh full live provider/browser/webhook journey. Persona images remain dependent on provider retention and availability. The exception is forbidden outside explicitly enabled local Sandbox mode.
