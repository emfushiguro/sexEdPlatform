# Persona single-submission review

Approved in conversation on 2026-10-27: government-ID learners and guardians submit ID and selfie once to Persona. Conscious Connections retains metadata and manual decisions, and displays Persona-hosted evidence through its authenticated administrator proxy. No Persona image bytes or URLs are stored.

An authoritative eligible synchronization submits the current government-ID application to the existing pending review queue exactly once. It never approves an application. Country must be PH and the exact mapped ID class must match. Completed and approved provider inquiries can qualify; declined and needs-review do not qualify. School/institution learners retain a local ID and use the Persona selfie, without a duplicate local selfie.

Persist the evidence source explicitly (`local`, `persona`, or `persona_selfie` for school/institution local IDs with a remote selfie), defaulting existing applications to local. Existing uploads, approvals, pending applications, and dependent/relationship flows are preserved. Reconciliation does not overwrite a local pending application or an admin decision. Rejected applications require a new-round inquiry. Source-dependent review gates remain enforced if new Persona starts are disabled.

Completed but ineligible/inconclusive attempts show a reason and permit replacement, including a corrected supported ID choice. Provider processing has a distinct refresh state. Administrator previews display same-origin proxy images with private/no-store headers and a generic availability error. The current review round owns its preview; unrelated, superseded, and unauthorized attempts are denied.

Production activation, real biometric accuracy, Persona retention configuration, and live dashboard changes remain outside this Sandbox change.
