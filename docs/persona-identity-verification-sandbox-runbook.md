# Persona Identity Verification Sandbox Runbook

This runbook is for the Conscious Connections Persona Sandbox integration. It does not enable production processing. Do not place secret values in this file, tickets, screenshots, logs, or commits.

## Single-submission revision (2026-10-27)

The approved workflow now uses Persona-hosted government ID and selfie evidence directly for manual administrator review. Government-ID learners and guardians do not upload a second ID or selfie. An authoritative eligible synchronization submits a pending application exactly once; it does not approve the account. The administrator sees inline images through the existing protected proxy, with an availability message if retention or provider availability prevents retrieval. No local Persona image copy is retained. Reviewers must not approve without reviewing the required evidence.

The additive evidence-source migration defaults existing applications to `local`. Previously submitted local evidence, decisions, and dependent/relationship checks retain their original behavior. Teen school/institution learners still submit their local ID and use their Persona selfie; administrator portrait comparison remains mandatory.

Government eligibility requires authoritative `PH` country and the exact selected supported class. `completed` and `approved` provider statuses can qualify only with passed required resources; `declined` and `needs-review` do not qualify. Configure Sandbox simulated data to return PH and the selected class; default US simulation data is correctly blocked. Completed but mismatched/inconclusive attempts now explain the failure and allow a new inquiry with a corrected supported ID choice.

For local retesting: apply `2026_10_27_000000_add_persona_evidence_source` normally, restart queue workers, use authenticated refresh or complete a new supported-PH Sandbox attempt, and verify the application reaches the pending admin queue without any local government-ID/selfie upload. Check duplicate delivery, return/refresh, admin approval/rejection, and new-round resubmission. Legacy local submissions must remain reviewable. Existing blocked attempts are not reset or rewritten by the migration.

## Configuration

Set these server-side variables in the local environment only:

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

1. Teen school ID: Persona Selfie, then the existing local school ID; the administrator manually compares the local portrait and Persona selfie.
2. Teen institution ID: Persona Selfie, then the existing local institution ID; the same manual comparison applies.
3. Teen supported government ID: GovID + Selfie once through Persona, then pending manual review with remote previews.
4. Adult supported government ID: GovID + Selfie once through Persona, then pending manual review with remote previews.
5. Guardian supported government ID: GovID + Selfie once through Persona, then pending manual review with remote previews.
6. Dependent: verify that Persona is not used for dependent identity or relationship evidence.

For every user path, check same-tab return, a new-tab resume, refresh, provider-unavailable handling, expiration/replacement, rejection reason, resubmission, and that Persona success alone does not approve the application or unlock dependent creation. Local upload applies only to school/institution IDs and legacy local cases.

## Government-ID mappings

The application currently allows only these exact mappings when the configured Persona template and Sandbox result have been proven:

| Application choice | Persona class |
| --- | --- |
| Passport | `pp` |
| Driver's license | `dl` |
| UMID | `umid` |
| Postal ID | `pid` |
| Voter's ID | `vid` |

For each mapping, confirm that the template offers the document, run the documented Sandbox simulation, and verify an authoritative `PH` country and exact class. Remove any mapping that cannot be proven. Do not enable national ID, PhilHealth, senior-citizen ID, PWD ID, PRC ID, `other`, or generic Persona class `id` without a later reviewed contract change and tests.

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
