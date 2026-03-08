# SexEd Platform

A comprehensive sexual education platform built with Laravel 12, featuring age-appropriate learning modules, quizzes, certificates, and subscription-based premium content.

## Tech Stack

- **Backend:** PHP 8.2+, Laravel 12
- **Frontend:** Blade, Tailwind CSS, Alpine.js
- **Build:** Vite
- **Database:** MySQL
- **Payments:** PayMongo
- **Roles/Permissions:** Spatie Laravel Permission

## Quick Start

```bash
# Install dependencies
composer install
npm install

# Configure environment
cp .env.example .env
php artisan key:generate

# Run migrations and seed
php artisan migrate
php artisan db:seed

# Start development server
composer dev
```

## Project Structure

```
app/
├── Console/Commands/     # Artisan commands (subscriptions, analytics, etc.)
├── Events/               # Domain events (payment, subscription)
├── Helpers/              # Utility classes (VideoEmbedHelper)
├── Http/
│   ├── Controllers/
│   │   ├── Admin/        # Admin panel controllers
│   │   ├── Api/          # API endpoints
│   │   ├── Auth/         # Authentication controllers
│   │   ├── Instructor/   # Content management controllers
│   │   └── Learner/      # Learner-facing controllers
│   ├── Middleware/        # Custom middleware (premium, profile, webhook)
│   └── Requests/         # Form request validation
├── Jobs/                 # Queued jobs (emails, invoices)
├── Listeners/            # Event listeners
├── Mail/                 # Mailable classes
├── Models/               # Eloquent models
├── Notifications/        # Notification classes
├── Observers/            # Model observers
├── Providers/            # Service providers
├── Services/             # Business logic services
└── View/Components/      # Blade components

docs/                     # Project documentation
routes/
├── api.php               # API routes
├── auth.php              # Authentication routes
├── console.php           # Scheduled commands
└── web.php               # Web routes
```

## Documentation

See the [docs/](docs/) directory for detailed guides:

- [Admin Development Guide](docs/admin-development-guide.md)
- [Payment & Subscription Documentation](docs/payment-subscription.md)
- [Platform Features Overview](docs/platform-features-overview.md)
- [Quick Testing Guide](docs/quick-testing-guide.md)
- [CSV Import Guide](docs/csv-import-guide.md)
- [Gmail SMTP Setup](docs/gmail-smtp-setup.md)

## Testing

```bash
php artisan test
```

## License

MIT
