# Direct Instructor Application Operations

This guide covers the operational rollout of the direct instructor application flow. It does not replace privacy, legal, biometric, security, or Persona Dashboard review.

## Deployment order

1. Confirm the target database, take the normal verified backup, and record current row counts for `users`, `user_profiles`, `instructor_applications`, and `role_transitions`.
2. Confirm all prerequisite Persona/identity migrations are applied. Do not bypass unrelated pending migrations.
3. Apply the four direct-instructor migrations one at a time with normal forward migrations. Never use `migrate:fresh`, `db:wipe`, destructive seeders, or rollback for these history-bearing tables.
4. Run only the canonical `RolePermissionSeeder` after reviewing its diff. The `applicant` role must have no learner/instructor permissions; reviewers need the two instructor-review permissions.
5. Verify private storage permissions and the authorized document controller before enabling entry points.
6. Run the focused PHP/JavaScript tests and the isolated Playwright suite. Keep Persona Sandbox walkthrough results separate from automated evidence.
7. Set `INSTRUCTOR_IDENTITY_ENFORCED_FROM` deliberately, review queue/worker health, and enable `DIRECT_INSTRUCTOR_APPLICATIONS_ENABLED` only after the release gate is signed off.

## Runtime configuration

The direct entry point is disabled by default. Keep these values server-side:

- `DIRECT_INSTRUCTOR_APPLICATIONS_ENABLED=false` until rollout approval.
- `INSTRUCTOR_IDENTITY_ENFORCED_FROM` as an explicit reviewed timestamp, not deployment time.
- `INSTRUCTOR_APPLICATION_DOCUMENT_DISK=local` or the configured private disk.
- Persona API keys, webhook secrets, template IDs, session tokens, and signed media URLs must never be logged or placed in fixtures, screenshots, traces, or this guide.

Persona Sandbox results are simulated workflow evidence. A provider completion never approves Stage One or grants instructor access. Only an administrator's local identity decision followed by final application approval can activate the instructor role.

## Workers and monitoring

Run the normal queue worker and monitor failed jobs, webhook reconciliation, identity review queues, application review queues, and final role transitions. Inspect only sanitized metadata:

```powershell
php artisan queue:work --queue=default --tries=5
php artisan queue:failed
```

Do not dump serialized jobs, raw webhook bodies, Persona responses, document numbers, provider URLs, signed URLs, or document bytes. Alerts should include internal record IDs only where the operational system already permits them.

## Browser-test database

Browser tests require a pre-existing isolated database whose name ends exactly in `_playwright_test`, for example `cc_db_playwright_test`. The Playwright setup refuses local environments, development databases, occupied port 8000, and missing database prerequisites. The suite may run ordinary migrations only against that guarded database and never creates, drops, or resets it.

## Document migration

Legacy instructor documents may remain on the legacy disk until they are copied and verified. First run the audit only:

```powershell
php artisan instructor-applications:migrate-documents --no-interaction
```

Review missing files and verification counts. A committed copy requires a separate operator step with an explicitly confirmed target; source deletion is a separate, explicitly approved action using `--delete-verified-source`. New documents must always use the private disk. The migration command must not be used to erase or broadly rewrite existing evidence.

## Recovery and rollback

The direct migrations have refusal-only rollback methods once identity, audit, or document history can exist. Recover a failed deployment with forward-only repair migrations and the normal database recovery process. Do not roll back these migrations, reset the database, delete identity/audit rows, or replace existing rows with seed data.

If the flow must be stopped, set the feature flag to `false`, leave identity and application history intact, and keep existing instructors and legacy applications operable. Preserve pending review state for later recovery.

## Release gates still requiring external action

- Configure and verify the public HTTPS Persona webhook, secret rotation, selected lifecycle events, and the Philippine GovID plus Selfie template.
- Confirm every configured ID-class mapping in the Persona Dashboard and Sandbox.
- Operate and monitor the queue worker and scheduler.
- Complete the private copy/verification plan for legacy public documents without source deletion unless separately approved.
- Complete legal/privacy/biometric, retention, security, incident-response, accessibility, and production monitoring reviews.
