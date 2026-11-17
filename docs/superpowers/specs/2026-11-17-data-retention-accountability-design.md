# Data Retention and Accountability Design

**Date:** November 17, 2026

**Status:** Approved design

**Scope:** Personal data retention, secure disposal, accountability records, public notices, and operational controls.

## Objective

Give Conscious Connections a documented and enforceable lifecycle for personal data, especially IDs, selfies, relationship evidence, health and support information, learning records, and records involving minors. Retention must be purpose based, time limited, visible to users, and supported by deletion evidence.

This schedule is an operating policy that must be validated by the platform operator's Data Protection Officer or Philippine privacy counsel. A legal hold, active complaint, regulatory direction, or applicable accounting requirement can override scheduled deletion for specifically identified records.

## Retention Schedule

| Data category | Retention period and trigger |
|---|---|
| Temporary unsubmitted ID or selfie upload | 7 days from upload |
| Abandoned verification submission | 90 days after last activity |
| Rejected or withdrawn ID or selfie submission | 6 months after final decision or withdrawal |
| Approved learner ID and selfie | 12 months after the verification decision becomes final |
| Approved guardian ID and selfie | 12 months after the verification decision becomes final |
| Guardian relationship evidence | Active relationship plus 12 months after it ends |
| Superseded verification evidence | 6 months after successful replacement |
| Adult representative authority evidence | Active authority plus 12 months after it ends |
| Instructor ID and selfie | Active identity verification plus 2 years after instructor access ends |
| Instructor qualifications and credentials | Active instructor relationship plus 5 years |
| Connector identity and authority documents | Active verification plus 2 years after the connector relationship ends |
| Verification decision and minimal metadata | 7 years after account or relationship closure |
| Verification audit history | 7 years after the last relevant action |
| Optional health and support information | While required, then 12 months after removal or account closure |
| Module enrollment and completion records | Account lifetime plus 5 years |
| Quiz scores and submitted answers | Account lifetime plus 3 years |
| Certificate record | Account lifetime; minimal validation data for 7 years after closure |
| Feedback and reviews | While published plus 2 years after removal |
| Private messages | 3 years after the last message in the conversation |
| Community content | While published; deleted content for 90 days for moderation and appeals |
| Support tickets and privacy requests | 5 years after closure |
| Payment and transaction records | 10 years, subject to accounting and tax validation |
| General activity logs | 1 year |
| Authentication, administrator, and security logs | 2 years |
| Confirmed incident and breach records | 7 years after resolution, or longer when required |
| Backups | Rolling 90-day period |

A decision is final only when review is complete and no correction, appeal, investigation, or legal hold remains open.

## Disposal Model

Raw IDs, selfies, and supporting documents are deleted from private storage when their retention deadline is reached. The application then removes or minimizes sensitive database fields and requests deletion from any identity provider that holds a copy.

The retained verification record is limited to the result, document category, provider reference where required, reviewer, decision date, reason code, and deletion receipt. It must not retain document images, selfies, full document numbers, or extracted document contents after the raw-evidence period.

Backups expire through the documented 90-day rotation. Restoring an older backup must reapply the deletion ledger before the restored system is made available.

## Application Design

### Policy configuration

Store the approved periods in a central, versioned configuration. Each protected record receives a retention category and computed `retention_expires_at`. A policy version is recorded so existing deadlines remain explainable when the schedule changes.

### Retention processing

A daily scheduled command processes eligible records in bounded batches. It must:

1. Skip records under an active legal hold.
2. Delete the private file.
3. Request provider-side deletion where applicable.
4. Minimize or delete corresponding database data.
5. Create a deletion receipt without copying the deleted personal data.
6. Retry failures and expose them to administrators.

Expired status and actual deletion are separate states. Marking a document expired does not satisfy disposal.

### Legal holds

A legal hold identifies the reason, records in scope, responsible administrator, creation time, review date, and release time. Applying or releasing a hold is audited. Indefinite holds are not allowed without periodic review.

### Accountability

Audit access to IDs, selfies, relationship evidence, support information, and quiz answers. Capture actor, action, subject, timestamp, purpose or workflow, and outcome. Avoid storing protected contents in audit metadata.

Deletion receipts record category, record identifier, policy version, scheduled time, completion time, local deletion result, provider deletion result, and failure reason. Receipts are append only.

An administrator retention workspace shows upcoming deletions, completed deletions, active holds, provider status, and failures. Access is limited to authorized administrators.

### Privacy requests

Track access, correction, deletion, objection, and export requests. Verify the requester and any representative authority. Show what was deleted, retained, anonymized, held, or scheduled, together with the applicable reason.

### Provider accountability

Maintain a register of storage, identity, email, payment, and other processors. Record their retention commitment, deletion mechanism, incident-notification terms, and contract owner. Local completion is not reported until required provider deletion has succeeded or the exception is documented.

## User-Facing Placement

### Privacy Policy

Publish the complete schedule in readable groups. Explain triggers, legal holds, backup rotation, provider handling, deletion requests, and the difference between raw evidence and retained decisions. Include a child-friendly summary.

### Terms of Service

Explain that some records remain after account closure for verification accountability, safeguarding, disputes, financial obligations, and legal requirements. Link to the detailed Privacy Policy.

### Help Center

Add a retention and deletion guide covering account closure, verification documents, learning history, certificates, privacy requests, and expected outcomes.

### Just-in-time notices

Before uploading an ID, selfie, relationship document, representative-authority document, instructor credential, or connector evidence, show the purpose, viewers, applicable retention period, and link to the Privacy Policy.

### Account deletion

Before confirmation, show which data is deleted promptly, scheduled for deletion, anonymized, or retained and why. Account deletion cannot silently imply immediate erasure of all records.

### Administrative review pages

Show the retention deadline and legal-hold state beside protected submissions without exposing these controls to unauthorized users.

## Security and Failure Handling

Private evidence remains on non-public storage and is served only through authorized, no-store responses. Retention processing uses idempotent operations so retries cannot recreate data or produce inconsistent outcomes.

Failed local or provider deletion creates an alert and remains visible until resolved. Scheduled jobs report their last successful run. A deployment is not considered retention compliant merely because the schedule exists in code; the production scheduler and provider deletion paths must be monitored.

## Verification

Focused checks must cover deadline computation, final-decision triggers, active and released holds, local file deletion, provider failure and retry, database minimization, deletion receipts, restored-backup suppression, role authorization, and public-notice rendering.

Production verification must confirm that the scheduler runs, private evidence is inaccessible after deletion, provider deletion completes, alerts surface failures, and public wording matches the configured policy.

## Explicit Limitations

The schedule does not itself establish a legal basis or override statutory, contractual, safeguarding, tax, or litigation requirements. Exact payment-record retention and third-party provider commitments require operator validation before enforcement. Existing records need a reviewed backfill plan before deadlines are assigned; no development or production data may be reset or broadly deleted to introduce this feature.
