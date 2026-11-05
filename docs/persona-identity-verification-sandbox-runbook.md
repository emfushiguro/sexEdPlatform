# Persona Identity Verification Sandbox Runbook

This runbook is for the Conscious Connections Persona Sandbox integration. It does not enable production processing. Do not place secret values in this file, tickets, screenshots, logs, or commits.

## Sandbox manual-review fallback (2026-10-28)

An explicitly enabled local or deployed demonstration fallback allows completed/approved Sandbox inquiries with passed Government ID and Selfie resources to enter manual review despite simulated country/class mismatches. It does not rewrite Persona data or declare the normal PH/class policies eligible. The attempt instead records `sandbox_review`; its original reported country, ID class and policy flags remain intact.

The fallback requires `PERSONA_ENABLED=true`, `PERSONA_ENVIRONMENT=sandbox`, and one explicitly enabled environment:

- Local: `APP_ENV=local` and `PERSONA_LOCAL_MANUAL_REVIEW=true`.
- Deployed demonstration: `APP_ENV=staging` or `APP_ENV=production`, with `PERSONA_DEMO_MANUAL_REVIEW=true`.

Both flags default false and cannot be supplied by browser parameters. The local flag cannot enable staging or production, and the demo flag cannot enable local. Automated tests force both flags off unless a test explicitly enables one. The integration remains Sandbox-only; setting the Persona environment to production is not a production implementation. See the [Hostinger demonstration setup](persona-hostinger-demo-setup.md) for deployed configuration.

Apply the additive `2026_10_27_100000_add_local_persona_review_markers` migration normally. It adds `learner_identity_verifications.sandbox_manual_review` and `users.parent_identity_sandbox_manual_review`, both false for existing rows. It neither replaces evidence nor resets users, decisions, or attempts. After setting the local environment flag, run `php artisan config:clear` and restart the existing queue worker using `php artisan queue:work --queue=default --tries=5`.

For an existing current completed mismatch, use **Recheck existing Persona evidence** on the identity page. The authenticated refresh re-fetches the real provider result and submits the owned current round once if eligible for this fallback; no new upload is required. Failed/declined/expired or incomplete verifications, missing resources/metadata, unsupported selections and superseded attempts do not qualify. A previously finalized admin decision is not reopened by refresh.

Administrator management already exists at `/admin/parent-verifications`: select the Learners or Guardians queue, then open Review / View Details. Inline previews use the existing protected media proxy. For a fallback application, approval requires all three explicit confirmations: Philippine issuance, selected ID type, and ID/selfie portrait comparison. The service enforces these confirmations as well as the form. Guardian approvals also bind confirmations to the local Persona attempt shown on the form; a newer submission returns 409 and requires reload. Audit reasons retain the exception and manual decision, and the persistent test-only markers remain after approval.

Disabling the applicable flag, Persona integration, or the permitted environment blocks fallback approval and recognition of existing test-only approvals. Learner access, guardian onboarding/learning access, relationship authorization and relationship access queries use that restriction. These test-only approvals must not be migrated or presented as real identity verification. School/institution-ID and legacy local evidence paths retain their existing behavior.

Test records and images used by automated/browser fixtures are synthetic. This mode tests submission, review and onboarding; it cannot measure document authenticity, genuine country/type extraction, liveness, or selfie matching accuracy.

## Single-submission revision (2026-10-27)

The approved workflow now uses Persona-hosted government ID and selfie evidence directly for manual administrator review. Government-ID learners and guardians do not upload a second ID or selfie. An authoritative eligible synchronization submits a pending application exactly once; it does not approve the account. The administrator sees inline images through the existing protected proxy, with an availability message if retention or provider availability prevents retrieval. No local Persona image copy is retained. Reviewers must not approve without reviewing the required evidence.

The additive evidence-source migration defaults existing applications to `local`. Previously submitted local evidence, decisions, and dependent/relationship checks retain their original behavior. Teen school/institution learners still submit their local ID and use their Persona selfie; administrator portrait comparison remains mandatory.

Government eligibility requires authoritative `PH` country and the exact selected supported class. `completed` and `approved` provider statuses can qualify only with passed required resources; `declined` and `needs-review` do not qualify. Configure Sandbox simulated data to return PH and the selected class; default US simulation data is correctly blocked. Completed but mismatched/inconclusive attempts now explain the failure and allow a new inquiry with a corrected supported ID choice.

For local retesting: apply `2026_10_27_000000_add_persona_evidence_source` normally, restart queue workers, use authenticated refresh or complete a new supported-PH Sandbox attempt, and verify the application reaches the pending admin queue without any local government-ID/selfie upload. Check duplicate delivery, return/refresh, admin approval/rejection, and new-round resubmission. Legacy local submissions must remain reviewable. Existing blocked attempts are not reset or rewritten by the migration.

## Registration and correction refinements (2026-10-28)

Apply the additive `2026_10_28_000000_add_learner_correction_requests` and `2026_10_28_000100_add_relationship_correction_requests` migrations normally and deploy the rebuilt assets. They preserve existing accounts, submissions and decisions. Environment notices are removed from application screens and moderation dialogs; provider provenance, audit history and the explicit approval/access guards remain enforced. Persona-owned hosted notices and simulation controls are unchanged.

Teen school/institution applicants now select and capture their ID in the application first, complete Persona selfie verification second, and confirm submission for review last. Capture uses the browser camera with preview/retake and private temporary storage. Camera capture does not prove authenticity; administrator portrait comparison remains required. Government ID and selfie stay together in Hosted Flow. Configure all relevant Persona templates for left, center and right selfie capture; the protected admin preview displays each available pose, with an absence message for legacy or unavailable images.

Administrators can request specific learner evidence corrections or make a final rejection. Accepted local IDs and eligible Persona selfies can be retained where appropriate; replacing an ID requires renewed administrator portrait comparison. A requested selfie replacement requires new verification. Government ID/selfie corrections remain one linked Persona unit. Old correction forms are rejected against the current submission round. Final rejection prevents direct resubmission and provider synchronization from reopening the case.

Relationship review supports targeted document replacements and missing document types, retaining acceptable documents without duplicating private files. Front/back pairs are corrected together. Corrections belong to the guardian who submitted the relationship claim; dependent notifications show status rather than granting document-editing authority. Known verification notifications resolve current internal routes independently of old localhost/ngrok hostnames, while arbitrary external targets remain rejected.

For relationship corrections, **Save replacement** persists each requested file privately for the signed-in session and evidence round. Reloading restores saved filenames; final submission accepts only a complete unchanged saved set. Successful submission clears those drafts after commit; validation failures retain them. Expired and abandoned draft files are cleaned by `guardian-relationships:prune-correction-drafts`, scheduled hourly. Ensure the deployment runs Laravel's scheduler (`php artisan schedule:run` each minute) in addition to the queue worker. Relationship approval forms bind the displayed evidence round, and Persona-backed guardian decisions bind the displayed attempt, preventing outdated forms from deciding newer submissions.

## Configuration

Set these server-side variables in the local environment or the explicitly enabled deployed demonstration site:

- `PERSONA_ENABLED=false` until the Sandbox checks below are complete.
- `PERSONA_ENVIRONMENT=sandbox`.
- `PERSONA_API_KEY`.
- `PERSONA_SELFIE_TEMPLATE_ID`.
- `PERSONA_GOVID_SELFIE_TEMPLATE_ID`.
- `PERSONA_WEBHOOK_SECRET`.
- `PERSONA_WEBHOOK_SECRET_PREVIOUS` only during secret rotation.
- `PERSONA_ENFORCED_FROM` as the reviewed enablement cutover timestamp.

The application reads these through `config/persona.php`. Never print the API key, webhook secret, Hosted Flow session token, raw webhook body, signed media URL, or Persona image bytes. The API contract is pinned to `Persona-Version: 2026-09-29`.

## Webhook and queue setup

Configure Persona Dashboard to deliver a signed `POST /api/webhooks/persona` request to the current application domain. The endpoint is outside browser authentication and requires the Persona signature. Use a local tunnel for local Sandbox delivery; do not expose a development endpoint without the signature middleware.

Confirm the exact inquiry event names available in the Dashboard for API version `2026-09-29`. Subscribe only to events that can change inquiry progress, completion, failure, or expiration. The Dashboard event list is the source of truth; this repository intentionally does not invent or hard-code an event allowlist.

Run the existing queue worker:

```powershell
php artisan queue:work --queue=default --tries=5
```

The webhook records a small delivery ledger, deduplicates event IDs, and queues reconciliation. The job retrieves authoritative inquiry and verification resources; it never trusts an outcome from the webhook body. Inspect failed jobs using sanitized metadata only, for example `php artisan queue:failed`; do not dump serialized payloads or environment values.

For secret rotation, put the new secret in `PERSONA_WEBHOOK_SECRET` and the old secret in `PERSONA_WEBHOOK_SECRET_PREVIOUS`, test a delivery signed by either secret, then remove the previous value after the rotation window. Do not echo either value.

## Sandbox scenarios

For each scenario, record the date, browser/device, synthetic account, template, selected document, result, and any sanitized error. Never record identity images, document numbers, raw JSON, inquiry IDs, signed URLs, or screenshots containing evidence.

1. Teen school ID: capture and save the school ID, complete Persona Selfie, then submit for review; the administrator manually compares the local portrait and Persona selfie.
2. Teen institution ID: capture and save the institution ID, complete Persona Selfie, then submit for review; the same manual comparison applies.
3. Teen supported government ID: GovID + Selfie once through Persona, then pending manual review with remote previews.
4. Adult supported government ID: GovID + Selfie once through Persona, then pending manual review with remote previews.
5. Guardian supported government ID: GovID + Selfie once through Persona, then pending manual review with remote previews.
6. Dependent: verify that Persona is not used for dependent identity or relationship evidence.

For every user path, check same-tab return, a new-tab resume, refresh, provider-unavailable handling, expiration/replacement, rejection reason, resubmission, and that Persona success alone does not approve the application or unlock dependent creation. Local upload applies only to school/institution IDs and legacy local cases.

## Government-ID mappings

The application selector matches the twelve Philippine document types enabled in the reviewed Persona template. It uses these exact class mappings:

| Application choice | Persona class |
| --- | --- |
| Driver's license | `dl` |
| National ID (PhilSys) | `id` |
| Passport | `pp` |
| Healthcare Insurance Card (PhilHealth) | `hic` |
| NBI Clearance | `nbi` |
| OFW ID | `ofw` |
| Postal ID | `pid` |
| Seafarer's ID | `sid` |
| SSS ID | `sss` |
| TIN Card | `tin` |
| UMID | `umid` |
| Voter's ID | `vid` |

The [Persona Government ID API reference](https://docs.withpersona.com/api-reference/verifications/government-id-verifications) documents most classes. The public [Persona verification application](https://inquiry.withpersona.com/verify) supplies the labels `id: National ID`, `hic: Healthcare Insurance card`, `sid: Seafarer's ID`, and `tin: TIN Card` (checked 2026-10-27). The API reference still describes `hic` as a Canadian document and omits `sid`/`tin`; the configured Philippine template and actual verification response must therefore be checked during live Sandbox QA.

National ID accepts `id` only when the returned country is `PH` and the selected type is `national_id`; `id` is never a fallback for other choices. Senior-citizen ID, PWD ID, PRC ID, and `other` remain excluded from the Persona selector. Historical local-only choices remain available for legacy evidence.

For each mapping, confirm that the published template offers the document, run the Sandbox simulation, and verify an authoritative `PH` country and exact class. Automated response fixtures prove application handling, not live provider classification. A wrong country, missing class, or mismatched class remains blocked. If Dashboard settings change, review this application mapping too; the selector does not automatically synchronize template settings.

## Troubleshooting: Philippine National ID selection returns a US license

Confirmed in a local app-created inquiry on 2026-10-27: the selection was `national_id`, but the authoritative Government ID resource returned `country-code: US`, `id-class: dl`, and `status: passed`. The inquiry was `completed`. Both country and document policies correctly rejected that result. The verification stored by the application matched the one returned by Persona; this was not a country-parsing or stale-verification error.

Persona's [Sandbox documentation](https://help.withpersona.com/articles/6I2kGhfPvSuUjYq4z6tpmB/) states that Sandbox does not extract or verify the uploaded ID. Its [Simulate guide](https://help.withpersona.com/articles/5NB4eGgy0k8oRs5O5sDOR1/) identifies a valid US driver's license as the default sample. Restricting the template to Philippine IDs controls accepted input choices; it does not change that simulated extraction. The hosted **Pass verifications** toggle controls pass/fail checks, not the simulated country or class.

The application now explains the mismatch using only the returned two-letter country and a known ID label, alongside the expected Philippine ID selection. It does not expose credentials, images, signed URLs, or raw verification payloads.

To complete a positive National ID integration test, the same app-created inquiry must return `PH` and `id`, with passed Government ID and Selfie resources. For other choices, use the exact class in the mapping table above. In Persona's available simulation controls, configure the **Government ID verification's simulated extraction**, not just an inquiry address-country field or the accepted-country list. A separate template **Preview / Simulate / Advanced** run is useful for checking a preset, but creates a separate inquiry and does not finish the application's owned attempt. Confirm that the preset can be applied to the app-created hosted inquiry before calling this an end-to-end test.

If the hosted inquiry offers only the pass/fail toggle and its Dashboard actions do not expose extraction simulation, configuring those values remains a Persona-side setup/capability prerequisite. The documented [Set Simulated Data API](https://docs.withpersona.com/api-reference/inquiries/set-simulate-stubs) describes inquiry-field stubs; it does not document a Government ID extraction override. Do not assume setting inquiry address-country or selected-ID fields changes an authoritative verification resource. Obtain the supported provider configuration/contract before automating it.

Once the supported simulation is configured, use **Start a new Persona attempt** in the application, complete that inquiry, and check the pending admin queue. Preserve the prior failed attempt. Do not rewrite `US` to `PH`, infer the verified class from the dropdown, disable the country policy, or treat a simulated passing check as proof that a real Philippine ID was classified correctly. Real extraction and ID/selfie accuracy require Persona's production verification environment; this integration remains Sandbox-only.

## Media and privacy checks

As an administrator, open only the semantic evidence actions on an existing review page. Confirm that the response is private/no-store, `nosniff`, and contains no upstream URL. Test a non-admin, another subject's attempt, an unsupported artifact, an upstream failure, an HTTP URL, a deceptive subdomain, a redirect, a non-image MIME type, and an oversized response. Confirm that no Persona-specific storage file is created.

Search logs and storage only for synthetic operational markers. Expected: local attempt metadata may be present; API keys, webhook secrets, raw payloads, session tokens, signed URLs, provider URLs, and Persona bytes must not be present.

## Retry, replay, and recovery

- Replay the same signed event: it must be accepted without duplicate reconciliation or notification.
- Deliver an older event after a newer one: it must not regress the local attempt.
- Stop the queue worker, deliver an event, then restart it: the user remains in a safe processing state until reconciliation completes.
- Exercise failed/expired inquiry recovery and explicit replacement; the old attempt remains in history.
- Use the authenticated refresh action when webhook delivery is delayed; it must use authoritative retrieval.
- Simulate unavailable media; the review page remains usable and exposes only a generic preview error.

## Rollback and release gates

To stop Sandbox starts, set `PERSONA_ENABLED=false` and restart the application. Retain Persona rows, guardian audits, existing local evidence, pending reviews, and approvals. Existing pending and approved cases must remain operable.

Production activation requires separate approval for privacy notice and consent, biometric processing, minors, legal basis, retention/deletion, security review, Dashboard configuration, verified ID mappings, queue/webhook operations, monitoring, incident response, and browser/accessibility checks. This Sandbox change does not satisfy those gates.

## Direct instructor application flow (2026-10-31)

The public **Be Our Partner** entry point is disabled until the direct-instructor release gate is approved. When enabled, an adult applicant completes personal information, Persona Government ID plus Selfie evidence, administrator Stage One identity review, and the existing instructor application. A Persona Sandbox completion remains simulated evidence: it does not approve identity, unlock Stage Two, or grant instructor access.

Direct applicants use an applicant-only account and do not receive learner permissions or a learner profile. Existing adult learners retain their learner account and may reuse their locally approved learner identity case when the application rules allow it. Cavite address values are server-derived and validated; browser-supplied province/city/barangay pairs are not trusted.

Administrators review protected evidence through the instructor identity queue. New instructor documents are stored on the private disk and served only through authorized no-store responses. Stage One approval and Stage Two application approval remain separate decisions; only final application approval activates the instructor role and profile. Rejection and action-required states use controlled resubmission paths without deleting prior identity or audit history.

For a real Sandbox walkthrough, use synthetic accounts, record only sanitized outcomes, and keep the walkthrough separate from mocked PHPUnit and Playwright evidence. Do not record document numbers, inquiry IDs, raw Persona payloads, provider URLs, signed media URLs, identity images, or screenshots containing evidence. The direct-instructor operations guide covers deployment order, isolated browser-test setup, private document migration, recovery, and external release gates.
