# Persona Sandbox demonstration on Hostinger

This setup runs Sandbox registration and administrator review on the existing Hostinger site used for a thesis demonstration. It does not enable Persona Production or genuine identity verification. The fallback requires an explicit server-side opt-in in staging or production; existing approval, ownership, evidence and access checks still apply.

## 1. Prepare the demonstration site

Use the existing HTTPS domain and its existing database as requested for the team-only thesis demonstration. Back up the database and private storage before deployment; use synthetic/test accounts and restrict access to the team. The Sandbox flow is not a real identity check.

In Hostinger hPanel, connect the repository through **Websites > Dashboard > Advanced > Git** and select the intended branch/commit. These changes must be committed and pushed before Git deployment can receive them. This guide does not itself push or deploy anything.

The repository requires PHP 8.2 or newer. Confirm the web and CLI PHP versions and the Composer platform requirements. The public document root must expose Laravel's `public/` directory, not the repository root; `.env`, `.git`, private storage and source files must not be served. Hostinger's Git destination directory is not itself proof that the public document root is correct. Preserve any existing working Laravel deployment layout rather than relocating it blindly.

Official Hostinger references:

- [Git deployment](https://www.hostinger.com/support/1583302-how-to-deploy-a-git-repository-in-hostinger/)
- [SSH access](https://www.hostinger.com/support/1583645-how-to-enable-ssh-access-in-hostinger/)
- [Server capabilities and background tasks](https://www.hostinger.com/support/which-server-capabilities-are-supported-at-hostinger/)

## 2. Set the server environment

Edit the deployed site's `.env` through the server or hosting environment manager. Do not commit credentials. Replace the example domain:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://demo.example.com
SESSION_SECURE_COOKIE=true

PERSONA_ENABLED=true
PERSONA_ENVIRONMENT=sandbox
PERSONA_LOCAL_MANUAL_REVIEW=false
PERSONA_DEMO_MANUAL_REVIEW=true

QUEUE_CONNECTION=database
```

Also configure the site's own `DB_*`, mail settings, existing `APP_KEY`, and these Persona Sandbox values:

```dotenv
PERSONA_API_KEY=<sandbox-api-key>
PERSONA_SELFIE_TEMPLATE_ID=<selfie-template-id>
PERSONA_GOVID_SELFIE_TEMPLATE_ID=<government-id-and-selfie-template-id>
PERSONA_WEBHOOK_SECRET=<secret-for-this-webhook-endpoint>
PERSONA_ENFORCED_FROM=<reviewed-enablement-timestamp>
```

Set the cutover timestamp before the new demonstration registrations that must use Persona. Do not move it backward over existing accounts without reviewing the consequences. Create an application key only for a brand-new installation; do not regenerate an existing application's key. Configure the demo's own mail transport so email verification and review notifications can be tested.

The demo flag explicitly enables the fallback in `staging` or `production`, provided Persona is enabled and configured as Sandbox. It defaults to false. Do not use `APP_ENV=local` on the deployed site. Turning the flag off also prevents access based on fallback approvals until it is re-enabled.

## 3. Deploy dependencies, assets and normal migrations

From the directory containing `artisan`, using the matching CLI PHP:

```sh
composer install --no-dev --prefer-dist --optimize-autoloader
php artisan config:clear
php artisan migrate --force
php artisan config:cache
```

The migration command applies outstanding normal migrations; it does not reset or reseed the database. Review pending migrations and back up any existing database before applying them. Do not use `migrate:fresh`, `db:wipe`, destructive seeders or database reset scripts.

Deploy the built Vite assets using the repository's normal asset-build process. Do not depend on a local Vite development server. Ensure `storage/` and `bootstrap/cache/` are writable by the application. Git file deployment alone does not prove dependencies, assets, migrations or environment configuration are ready.

## 4. Configure Persona for the deployed domain

In the Persona **Sandbox** environment:

1. Keep the government-ID/selfie and selfie-only templates configured for the intended paths.
2. Allow the demonstration domain for Hosted Flow redirects.
3. Add or update the Sandbox webhook endpoint to:

   ```text
   https://demo.example.com/api/webhooks/persona
   ```

4. Subscribe to the inquiry lifecycle events used by the existing integration, following the main Sandbox runbook.
5. Put this endpoint's signing secret in the deployed `PERSONA_WEBHOOK_SECRET`. A newly created webhook may have a different secret from the local endpoint.

The deployed domain replaces ngrok for this site. Keep local and deployed endpoint secrets separate where Persona supplies separate secrets. Browser authentication is not required for the webhook, but its Persona signature is required. Opening the URL in a browser is not a valid webhook test; check an actual signed POST delivery.

## 5. Process queued webhook jobs

The web application and queue process must read the same deployed configuration and database. Use the `database` queue and an appropriate worker arrangement supported by the hosting plan.

For a managed persistent worker (for example, a VPS process manager):

```sh
php artisan queue:work --queue=default --tries=5
```

Restart managed workers after deployments/configuration changes:

```sh
php artisan queue:restart
```

On shared/Web/Cloud hosting without a managed persistent worker, hPanel supports scheduled tasks. A bounded worker can be scheduled every minute; use the actual CLI PHP and absolute `artisan` paths for the hosting account:

```sh
php /absolute/path/to/project/artisan queue:work --queue=default --stop-when-empty --max-time=50 --tries=5
```

Run one scheduled worker arrangement and prevent overlapping launches with the hosting-supported lock/process mechanism. `--max-time` is checked between jobs; it does not interrupt an individual job. Do not assume this creates a permanent worker or guarantees delivery within seconds. Confirm PHP process limits and queue timeout/retry settings on the actual plan. If the plan cannot run the worker reliably, use a supported managed worker/VPS; do not leave webhook jobs unprocessed.

## 6. Verify the complete demonstration

Also configure the existing Laravel scheduler once per minute using the hosting account's CLI PHP and project `artisan` path:

```sh
php /absolute/path/to/project/artisan schedule:run
```

The scheduler includes hourly expired correction-draft cleanup. This is separate from the webhook queue worker.

1. Register a fresh synthetic adult learner and a guardian using the deployed site; confirm verification emails use its HTTPS domain.
2. Complete their app-created Hosted Flow inquiries. Default simulated US/class data may enter the existing manual-review path; original provider data must remain unchanged.
3. Check Persona's signed webhook delivery and confirm queued jobs are processed.
4. Confirm each application reaches the pending admin queue exactly once. No automatic approval or duplicate local ID/selfie upload should occur.
5. Review evidence at `/admin/parent-verifications`, complete all required confirmations, and approve the synthetic accounts. Confirm learner profile completion and guardian onboarding work.
6. Check failed/missing evidence remains blocked. Exercise teen school/institution and dependent relationship paths separately according to the main runbook.
7. Test disabling `PERSONA_DEMO_MANUAL_REVIEW` in an isolated test deployment: pending fallback approvals and access based on fallback approvals must stop. Re-enable only for the demonstration site. Never change a real-user production environment to test this.

Keep approval provenance and audit records. Do not copy demonstration approvals into a real-user production database. Persona-owned Sandbox banners/toggles remain under Persona's control.

## Disable the deployed workaround

Set `PERSONA_DEMO_MANUAL_REVIEW=false`, refresh the cached configuration and restart managed workers. This prevents fallback approval and access based on its persisted markers without deleting accounts, inquiries, evidence or audit history. It does not implement a migration to real Persona Production verification.

## Local verification (2026-10-28)

Earlier focused suites used an isolated database and mocked Persona HTTP responses. Their prior production-blocking expectation predates the 2026-11-05 change allowing the explicitly enabled production demonstration. No Hostinger deployment, hosted provider/browser journey or genuine document verification was performed.

On 2026-11-05, the updated guard, learner/guardian workflows and instructor production-demo approval passed 22 focused tests / 222 assertions against an isolated local MariaDB with mocked Persona responses. A broader instructor admin run separately reported an existing mismatch: one test expects approval without a mismatch rationale, while the service requires one. That issue is outside the demo guard change and remains unresolved.
