# Conscious Connections

Conscious Connections is a Laravel learning and community platform for age-aware education about sexual health, consent, relationships, and personal development. It supports learners and their guardians, instructors, verified organizations (“connectors”), and platform administrators.

## What the platform includes

- Structured learning: modules, lessons, quizzes, interactive activities, progress, and certificates.
- Learner safety: age-aware content access, guardian-child workflows, identity verification, moderation, suspensions, and appeals.
- Instructor tools: applications, profiles, content authoring and review, learner assessment, subscriptions, and earnings.
- Connector tools: organization verification, memberships and roles, module access, seminars, livestreams, and community spaces.
- Platform operations: role and permission management, notifications, subscriptions, PayMongo payments, revenue reporting, and support workflows.

See the [system module audit](docs/system-module-audit.md) for a source-oriented module inventory and known incomplete areas. The [project context](docs/PROJECT_CONTEXT.md) and [platform feature overview](docs/PLATFORM_FEATURES_OVERVIEW.md) provide broader domain and feature detail.

## Technology

- PHP 8.2+ and Laravel 12
- MySQL or MariaDB
- Blade, Alpine.js, Tailwind CSS, and Vite 7
- Composer and Node.js/npm
- PHPUnit for PHP tests and Playwright for browser tests

The frontend is served by Laravel and built with Vite; this is not a separate SPA. Optional integrations include PayMongo, Persona, Google Cloud Text-to-Speech, Agora livestreaming, and Laravel Reverb. They require provider configuration and credentials to use.

## Local development setup

### Prerequisites

- PHP 8.2+ with the Laravel-required extensions, including `pdo_mysql`, `mbstring`, `openssl`, `fileinfo`, `xml`, and `zip`
- Composer 2
- Node.js 20+ and npm 10+
- A MySQL or MariaDB database for the application

See [fresh server setup](docs/FRESH_SERVER_SETUP.md) for the full PHP extension list and deployment-specific requirements.

### Install

1. Install dependencies:

   ```bash
   composer install
   npm install
   ```

2. Create a local `.env` file and set at least `APP_NAME`, `APP_URL`, `APP_KEY`, and the `DB_*` settings for a database dedicated to this checkout. Generate an application key with:

   ```bash
   php artisan key:generate
   ```

   This repository currently has no tracked `.env.example`; do not copy one from another environment. Configure optional integrations only when needed. See the focused setup guides for [Persona](docs/persona-identity-verification-sandbox-runbook.md), [email](docs/GMAIL_SMTP_SETUP.md), and [chat/realtime](docs/CHAT_REALTIME_SETUP_GUIDE.md).

3. Review the configured database connection and target before applying migrations. For a new, empty local database, run:

   ```bash
   php artisan migrate
   ```

   Migrations change the selected database schema. Never use `migrate:fresh`, `db:wipe`, or other reset commands against a database containing data you need to keep. See [AGENTS.md](AGENTS.md) for this repository’s database safety rules.

4. Build frontend assets, then start the development processes:

   ```bash
   npm run build
   composer run dev
   ```

   `composer run dev` starts Laravel, the queue listener, log tail, and Vite together. For asset hot reload, use this command instead of a production build:

   ```bash
   npm run dev
   ```

   Open the URL printed by `php artisan serve` (normally `http://127.0.0.1:8000`). The combined Composer command stops its sibling processes when one exits.

## Tests and useful commands

```bash
php artisan test          # PHP unit and feature tests
npm run test:browser      # Playwright browser tests
npm run build             # Production frontend build
```

The PHPUnit configuration points to a MySQL test database named `cc_db_test`. Configure and verify that test database separately before running tests; tests must not target a development or production database. Browser test setup is in [playwright.config.mjs](playwright.config.mjs). The [quick testing guide](docs/QUICK_TESTING_GUIDE.md) contains focused verification scenarios.

Other useful commands:

```bash
php artisan route:list
php artisan queue:work
php artisan schedule:work
```

## Repository map

| Path | Contents |
| --- | --- |
| `app/` | Laravel controllers, models, policies, services, jobs, events, and domain logic |
| `routes/` | Web, API, authentication, admin, instructor, and connector routes |
| `resources/views/` | Blade pages, layouts, and components |
| `resources/js/`, `resources/css/` | Frontend behavior and styles built by Vite |
| `database/migrations/`, `database/seeders/` | Incremental schema changes and explicit seeders |
| `tests/` | PHPUnit tests and JavaScript/browser test coverage |
| `docs/` | Operational guides, feature documentation, audits, designs, and QA records |

## Operations and integrations

This application handles sensitive learner, guardian, identity, messaging, and payment workflows. Keep secrets in environment configuration, use provider sandbox environments for local verification, and follow the relevant operational guide before enabling integrations or changing deployment configuration. See [fresh server setup](docs/FRESH_SERVER_SETUP.md) and the focused guides in [`docs/`](docs/).

## Contributing

Keep changes focused, follow existing Laravel patterns, and add or update focused tests for behavior changes. Before opening a change, run the checks relevant to the affected area. Do not use the development database as a disposable test database; database protections are documented in [AGENTS.md](AGENTS.md).

## License

No project-level `LICENSE` file is currently present. Contact the project owner for permission and licensing terms before redistributing this repository.
