# Fresh Server Setup Guide

This guide is based on the current project configuration and verified checks in this workspace.

## 1. Required Runtime Versions

- PHP 8.2+
- Composer 2.x
- Node.js 20+ (22 is also fine)
- npm 10+
- MySQL or MariaDB

## 2. Required PHP Extensions

Install and enable these extensions on the server:

- bcmath
- ctype
- curl
- dom
- fileinfo
- json
- mbstring
- openssl
- pdo
- pdo_mysql
- session
- tokenizer
- xml
- xmlwriter
- zip

## 3. Install Project Dependencies

From project root:

```bash
composer install --no-dev --optimize-autoloader
npm install
npm run build
```

If `npm run build` fails with `vite is not recognized`, Node packages are missing. Run `npm install` first.

## 4. Environment File Setup

1. Copy [.env.example](.env.example) to `.env`.
2. Set production values:

- `APP_URL`
- `DB_*` values
- `MAIL_*` values
- `PAYMONGO_SECRET_KEY`
- `PAYMONGO_PUBLIC_KEY`
- `PAYMONGO_WEBHOOK_SECRET`
- `COMPANY_*` values (for invoice metadata)

3. Generate app key:

```bash
php artisan key:generate
```

## 5. Database and Laravel Bootstrap

```bash
php artisan migrate --force
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## 6. Background Processes (Required)

### Queue Worker

Queue default is `database`, so run a persistent worker:

```bash
php artisan queue:work --tries=1
```

Use Supervisor or systemd in production.

### Scheduler

Add a cron entry (every minute):

```cron
* * * * * cd /path/to/sexEdPlatform && php artisan schedule:run >> /dev/null 2>&1
```

This project uses scheduled commands in [routes/console.php](routes/console.php).

## 7. Web Server

- Point web root to [public/index.php](public/index.php).
- Ensure write permissions for `storage` and `bootstrap/cache`.

## 8. Payment and Webhook Notes

- Payment flow requires PayMongo keys from [.env](.env).
- Webhook endpoint: `POST /webhook/paymongo`
- Ensure your server can receive HTTPS webhooks.

## 9. Optional Package for PDF Invoices

Invoice service supports fallback HTML invoices if DomPDF is missing. To enable real PDF generation:

```bash
composer require barryvdh/laravel-dompdf
```

## 10. Post-Deployment Verification

Run these commands and confirm success:

```bash
php -v
php -m
composer check-platform-reqs
php artisan migrate:status
php artisan about
php artisan test --stop-on-failure
```

Frontend verification:

```bash
npm run build
```

If all commands pass and queue + cron are running, the server setup is complete.
