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

Run this from the application root after Hostinger finishes the Git checkout.
The `cp`/`mv` block is safe to repeat: it only runs when `public/storage` is
a real directory instead of a symbolic link. It preserves that directory's
files in Laravel's canonical public disk and moves the old directory to a
recoverable backup outside the web root.

```bash
cd /path/to/your/project

php artisan optimize:clear
php artisan migrate --force

mkdir -p storage/app/public
if [ -L public/storage ]; then
    unlink public/storage
elif [ -d public/storage ]; then
    cp -a public/storage/. storage/app/public/
    mkdir -p storage/backups
    mv public/storage "storage/backups/public-storage-$(date +%Y%m%d-%H%M%S)"
elif [ -e public/storage ]; then
    echo "Unexpected non-directory at public/storage; stopping." >&2
    exit 1
fi
ln -s "$(pwd)/storage/app/public" public/storage

php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
php artisan optimize

test -L public/storage
test -f public/storage/modules/cDSb0q7slODyGtKDKwYTO40Ba5JZ8ztGiSXwHJtw.png
```

Hostinger may disable PHP `exec()`, so do not use `php artisan storage:link`
for this deployment. The shell `ln -s` command above creates the link without
requiring that PHP function. The domain document root must be the project's
`public` directory (for example,
`/home/u789110384/domains/consciousconnections.online/public_html/public`),
not the Laravel application root.

Do not replace the `cp`/`mv` block with `rm -rf public/storage`. The old
directory can contain uploaded media or other runtime files. The backup must
remain under `storage/backups`, not under `public/`, because it may contain
sensitive uploaded documents.

For the currently reported missing module image, verify that the file still
exists on the server and is served successfully:

```bash
test -f storage/app/public/modules/cDSb0q7slODyGtKDKwYTO40Ba5JZ8ztGiSXwHJtw.png
curl -I https://consciousconnections.online/storage/modules/cDSb0q7slODyGtKDKwYTO40Ba5JZ8ztGiSXwHJtw.png
```

The first command must succeed before the URL can return `200`. If it fails,
restore the file from the Hostinger backup or re-upload it; Artisan cache
commands cannot recreate a missing upload.

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
