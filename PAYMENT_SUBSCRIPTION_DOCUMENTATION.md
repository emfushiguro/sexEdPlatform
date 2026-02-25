# Payment & Subscription System — Full Documentation

> **Project:** SexEd Platform
> **Payment Gateway:** PayMongo (Philippines)
> **Stack:** Laravel 11, PHP 8.2, MySQL, Redis/Database Queue
> **Last Updated:** February 2026

---

## Table of Contents

1. [System Overview](#1-system-overview)
2. [Subscription Plans & Pricing](#2-subscription-plans--pricing)
3. [Payment Flow](#3-payment-flow)
4. [Subscription Lifecycle](#4-subscription-lifecycle)
5. [Webhook System](#5-webhook-system)
6. [Event-Driven Architecture](#6-event-driven-architecture)
7. [Queue Jobs](#7-queue-jobs)
8. [Email Notifications](#8-email-notifications)
9. [Refund System](#9-refund-system)
10. [Invoice System](#10-invoice-system)
11. [Admin Management](#11-admin-management)
12. [Routes Reference](#12-routes-reference)
13. [Key Files Reference](#13-key-files-reference)
14. [Environment Variables](#14-environment-variables)
15. [Implementation Phases](#15-implementation-phases)
16. [Deployment Checklist](#16-deployment-checklist)
17. [Troubleshooting](#17-troubleshooting)
18. [Database Transaction Safety](#18-database-transaction-safety)
19. [Race Condition & Idempotency Protection](#19-race-condition--idempotency-protection)
20. [Dead Letter Strategy](#20-dead-letter-strategy)
21. [Rate Limiting](#21-rate-limiting)
22. [Refund Idempotency](#22-refund-idempotency)
23. [Financial Audit Trail](#23-financial-audit-trail)
24. [Disaster Recovery Plan](#24-disaster-recovery-plan)

---

## 1. System Overview

The platform uses a **freemium model** — users get limited access for free, then pay for premium access via subscription.

### Architecture at a Glance

```
User clicks Subscribe
       │
       ▼
SubscriptionController
  └── SubscriptionService::create()
         │  Creates pending Subscription + Payment records
         ▼
PaymentController::process()
  └── PayMongoPaymentLinkService::createPaymentLink()
         │  Returns PayMongo checkout URL
         ▼
User pays on PayMongo checkout page
         │
         ▼ (webhook fires)
PaymentController::webhook()
  └── Payment marked "completed"
         │
         ▼ (observer fires)
PaymentObserver::updated()
  └── event(PaymentSuccessful)
  └── SubscriptionService::activate()
         │  Subscription marked "active"
         │  event(SubscriptionCreated)
         ▼
Jobs dispatched to queue:
  ├── GenerateInvoiceJob       → PDF invoice created
  ├── SendPaymentReceiptEmail  → Receipt email to user
  └── SendSubscriptionWelcomeEmail → Welcome email to user
```

### Core Services

| Service | Responsibility |
|---|---|
| `SubscriptionService` | All subscription business logic — create, activate, cancel, renew, expire, switch plan |
| `PayMongoPaymentLinkService` | Creates PayMongo payment links and handles API calls |
| `InvoiceService` | Generates PDF invoices using DomPDF |
| `RefundService` | Processes refunds via PayMongo API |
| `SubscriptionDunningService` | Handles failed payments, grace periods, and retry logic |
| `AnalyticsService` | MRR, ARR, churn rate, revenue metrics |

---

## 2. Subscription Plans & Pricing

Plans are stored in the `subscription_plans` database table and managed via the Admin panel.

### Current Pricing

| Plan | Monthly | Annual | Savings |
|---|---|---|---|
| Free | ₱0 | — | — |
| Premium Monthly | ₱299/mo | — | — |
| Premium Annual | — | ₱2,999/yr | ~16% vs monthly |

### Plan Features

**Free Plan:**
- Limited quiz attempts per day
- Access to selected modules only
- Standard support
- Basic certificates

**Premium Plan:**
- Unlimited quiz attempts
- Access to all modules and lessons
- Priority support
- Certificates of completion
- Offline downloadable content

### Managing Plans

Plans are managed in the Admin panel at `/superadmin/subscriptions`. You can:
- Create new plans with custom pricing
- Set feature flags per plan
- Enable/disable plans without deleting them
- Reorder how plans appear on the upgrade page
- Configure trial periods (`trial_days` field)
- Configure short test plans via `duration_minutes` feature flag

---

## 3. Payment Flow

### Standard Flow (PayMongo Payment Link)

```
Step 1: User selects plan on /subscription/upgrade
        POST /subscription/subscribe { plan_id, billing_cycle }
        SubscriptionService::create() → Subscription (status: pending) + Payment (status: pending)

Step 2: Redirect to /payment/create/{subscription}
        User selects payment method (GCash, PayMaya, Card)
        POST /payment/process/{subscription} { payment_method, accept_terms }

Step 3: PayMongo payment link created
        PayMongoPaymentLinkService::createPaymentLink()
        → User redirected to PayMongo checkout URL

Step 4a: Payment successful
        PayMongo fires webhook → POST /webhook/paymongo
        Payment status → "completed"
        Subscription status → "active"
        Invoice generated, emails sent (via queue)

Step 4b: Payment failed / cancelled
        User redirected to /payment/paymongo/failed/{subscription}
        Subscription remains "pending"
        User can retry

Step 5: PayMongo success callback
        GET /payment/paymongo/success/{subscription}
        Redundant check — activates subscription if webhook was missed
```

### Legacy Direct Flow (PayMongo Subscription Links)

For backward compatibility, two legacy routes exist that bypass the payment form and go directly to a PayMongo checkout:

```
POST /subscribe/monthly → SubscriptionController::subscribeMonthly()
POST /subscribe/annual  → SubscriptionController::subscribeAnnual()
     │
     └── SubscriptionService::createWithPayMongoLink() (if plan exists)
         SubscriptionService::createLegacy()           (if no plan record)
         → redirect to PayMongo checkout URL directly
```

### Development / Testing Flow

In `local` environment, a simulate button is available:

```
GET /payment/simulate-success/{payment}
    → Marks payment as completed
    → Activates subscription
    → Triggers all downstream events/jobs
```

---

## 4. Subscription Lifecycle

### Status Values

| Status | Meaning |
|---|---|
| `pending` | Created, payment not yet received |
| `active` | Payment received, user has premium access |
| `cancelled` | User or admin cancelled; user loses access at `end_date` |
| `expired` | `end_date` passed, daily cron set this automatically |

### State Machine

```
                    ┌──────────────┐
                    │   pending    │
                    └──────┬───────┘
                           │ payment confirmed (webhook)
                           │ SubscriptionService::activate()
                           ▼
                    ┌──────────────┐
             ┌─────►│   active     │◄─────────────────────┐
             │      └──────┬───────┘                      │
             │             │                               │
             │   user cancels              renew()         │
             │   cancel()  │                              │
             │             ▼                               │
             │      ┌──────────────┐                      │
             │      │  cancelled   ├──────────────────────┘
             │      └──────────────┘
             │
             │  end_date > now()
             │  daily cron: subscriptions:expire
             │
             ▼
      ┌──────────────┐
      │   expired    │
      └──────────────┘
```

### Key SubscriptionService Methods

```php
// Create a new pending subscription + payment record
$subscription = $subscriptionService->create($user, $plan, 'monthly');

// Activate after payment confirmed
$subscriptionService->activate($subscription);

// Cancel with optional reason
$subscriptionService->cancel($subscription, 'Too expensive');

// Reactivate a cancelled subscription
$subscriptionService->renew($subscription);

// Mark as expired (called by cron)
$subscriptionService->expire($subscription);

// Switch to a different plan
$newSub = $subscriptionService->switchPlan($currentSub, $newPlan, 'annual');

// Create with PayMongo checkout link in one step
$result = $subscriptionService->createWithPayMongoLink($user, $plan, 'monthly', $paymongoService);
// $result = ['subscription' => Subscription, 'checkout_url' => 'https://...']
```

### Scheduled Commands

These run automatically via the Laravel scheduler (cron must be set up on the server):

```bash
# Required cron entry on server:
* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
```

| Command | Schedule | What it does |
|---|---|---|
| `subscriptions:expire` | Daily | Marks active subscriptions past their `end_date` as expired |
| `subscriptions:process-renewals` | Daily | Processes auto-renewals for subscriptions expiring soon |
| `billing:send-expiry-reminders` | Daily at 09:00 | Sends expiry reminder emails to users whose subscription expires in 7 days |

---

## 5. Webhook System

PayMongo sends webhook events to your server when payments occur.

### Endpoint

```
POST /webhook/paymongo
```

This route is **outside** the auth middleware so PayMongo can call it without a session.

### Security

Every incoming webhook is validated against `PAYMONGO_WEBHOOK_SECRET`:

```
Paymongo-Signature header → HMAC-SHA256(payload, secret) → must match
```

If the signature is missing or invalid, the webhook returns `401 Unauthorized`.

### Idempotency

Duplicate webhook deliveries are handled automatically:

```
Cache::has("webhook_processed_{$eventId}") → return 200 (already processed)
Otherwise: process event, then Cache::put(..., ttl: 24 hours)
```

This prevents double-charges or double-activations if PayMongo retries a delivery.

### Handled Events

| Event Type | What Happens |
|---|---|
| `link.payment.paid` | Payment marked `completed`, subscription activated, invoice + emails queued |

### Testing Webhooks Locally

Use [ngrok](https://ngrok.com) to expose your local server:

```bash
ngrok http 8000
# Copy the https URL, e.g. https://abc123.ngrok.io

# Register the webhook in PayMongo dashboard:
# URL: https://abc123.ngrok.io/webhook/paymongo
# Events: link.payment.paid
```

---

## 6. Event-Driven Architecture

Events are fired at key moments. Listeners handle side-effects asynchronously (via queued jobs) so the HTTP response is never blocked.

### Event Map

```
PaymentSuccessful (fired by PaymentObserver when payment.status → "completed")
    └── HandlePaymentSuccessful (queued listener)
            ├── GenerateInvoiceJob      → queue: invoices
            └── SendPaymentReceiptEmail → queue: emails

SubscriptionCreated (fired by SubscriptionService::activate())
    └── HandleSubscriptionCreated (queued listener)
            └── SendSubscriptionWelcomeEmail → queue: emails

SubscriptionExpired (fired by ExpireSubscriptions command)
    └── HandleSubscriptionExpired (queued listener)
            └── SendSubscriptionExpiredEmail → queue: emails
```

### Event Classes

| Class | Payload |
|---|---|
| `App\Events\PaymentSuccessful` | `Payment $payment` |
| `App\Events\SubscriptionCreated` | `Subscription $subscription` |
| `App\Events\SubscriptionExpired` | `Subscription $subscription` |

### Adding a New Listener

1. Create `app/Listeners/YourListener.php` implementing `ShouldQueue`
2. Register in `AppServiceProvider::boot()`:
   ```php
   Event::listen(SomeEvent::class, YourListener::class);
   ```

---

## 7. Queue Jobs

All slow operations (email sending, PDF generation) run as background queue jobs.

### Running the Queue Worker

```bash
# Development
php artisan queue:work --queue=invoices,emails,default

# Production (use Supervisor to keep worker running)
php artisan queue:work --queue=invoices,emails,default --sleep=3 --tries=3 --max-time=3600
```

### Supervisor Config (Production)

Create `/etc/supervisor/conf.d/sexed-worker.conf`:

```ini
[program:sexed-queue-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/sexEdPlatform/artisan queue:work database --queue=invoices,emails,default --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/log/sexed-worker.log
stopwaitsecs=3600
```

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start sexed-queue-worker:*
```

### Job Reference

| Job | Queue | Retries | Backoff | What it does |
|---|---|---|---|---|
| `GenerateInvoiceJob` | `invoices` | 3 | 60s | Generates PDF invoice via `InvoiceService`, skips if invoice already exists |
| `SendPaymentReceiptEmail` | `emails` | 3 | 30s | Sends `PaymentReceiptMail` to user |
| `SendSubscriptionWelcomeEmail` | `emails` | 3 | 30s | Sends `SubscriptionWelcomeMail` to user |
| `SendSubscriptionExpiredEmail` | `emails` | 3 | 30s | Sends `SubscriptionExpiredMail` to user |

### Monitoring Failed Jobs

```bash
# See all failed jobs
php artisan queue:failed

# Retry a specific failed job
php artisan queue:retry {id}

# Retry all failed jobs
php artisan queue:retry all

# Clear failed jobs
php artisan queue:flush
```

---

## 8. Email Notifications

### Emails Sent

| Email | Mailable Class | Trigger | Template |
|---|---|---|---|
| Payment Receipt | `PaymentReceiptMail` | Payment marked completed | `emails/payment-receipt` |
| Welcome to Premium | `SubscriptionWelcomeMail` | Subscription activated | `emails/subscription-welcome` |
| Subscription Expired | `SubscriptionExpiredMail` | Subscription marked expired | `emails/subscription-expired` |
| Subscription Expiring | `SubscriptionExpiringNotification` | 7 days before expiry (cron) | `emails/subscription-expiring` |
| Payment Failed | `PaymentFailedNotification` | Payment dunning (dunning service) | `emails/payment-failed` |

### Email Configuration (.env)

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@yourdomain.com
MAIL_FROM_NAME="SexEd Platform"
```

> See `GMAIL_SMTP_SETUP.md` for detailed Gmail SMTP setup instructions.

---

## 9. Refund System

### How Refunds Work (Current Implementation)

There are **two separate refund paths** in the system:

---

#### Path A — Admin Quick Refund (`PaymentAdminController::processRefund`)

Used via the admin payments panel. This is a **database-only refund** — it does **not** call the PayMongo API.

1. Admin navigates to `/superadmin/payments/{payment}`
2. Admin fills in a reason and submits the refund form
3. The controller validates:
   - Payment must be `completed`
   - Payment must be within **3 days** of `paid_at`
4. Inside a `DB::transaction()`:
   - `payment.status` → `refunded`
   - Refund reason + timestamp stored in `payment.payment_details` JSON
   - If a subscription is linked: `subscription.status → cancelled`, `end_date → now()`, `auto_renew → false`
5. If anything fails, the transaction rolls back

> **Important:** This path does NOT return money to the user via PayMongo. It only records the refund in your database and deactivates the subscription. The actual money transfer must be done manually via the PayMongo dashboard.

---

#### Path B — RefundService (Full API Refund)

`app/Services/RefundService.php` contains a full refund implementation that also calls the PayMongo Refunds API. This service is **not yet wired to any admin UI route** — it exists for programmatic use and future integration.

```php
// Full refund — calls PayMongo API + creates Refund record
$refund = $refundService->processRefund($payment, reason: 'Customer request');

// Partial refund
$refund = $refundService->processRefund($payment, amount: 150.00, reason: 'Partial refund');

// Admin bypass of 3-day policy
$refund = $refundService->processRefund($payment, reason: '...', bypassTimeLimit: true);

// Check eligibility before refunding
$check = $refundService->isRefundEligible($payment);
// Returns: ['eligible' => true/false, 'reason' => '...', 'days_since_payment' => 2, 'refund_deadline' => '2026-02-25 10:00']
```

**What RefundService does step by step:**
1. Validates: amount ≤ payment amount, payment is `completed`
2. Enforces **3-day policy** unless `$bypassTimeLimit = true`
3. Checks for duplicate refund (idempotency — blocks if a `pending`/`completed`/`manual_processing` refund already exists)
4. Creates a `Refund` record with `status: pending`
5. **If `paymongo_payment_id` is present in `payment_details`:** calls PayMongo Refunds API → updates refund to `completed`
6. **If no `paymongo_payment_id`:** sets refund to `manual_processing` (old or non-PayMongo payments)
7. If total refunded ≥ payment amount → marks `payment.status = refunded`, cancels linked subscription
8. On API failure: sets refund to `failed`, logs error, re-throws exception

---

### Refund Status Values

| Status | Meaning |
|---|---|
| `pending` | Refund record created, API call not yet made |
| `completed` | PayMongo API accepted the refund |
| `manual_processing` | No PayMongo payment ID — must process manually in PayMongo dashboard |
| `failed` | PayMongo API returned an error |

### Refund Policy

- Only **completed** payments can be refunded
- Refunds must be initiated within **3 days** of `paid_at`
- Admins can bypass the 3-day limit via `RefundService` with `bypassTimeLimit: true`
- A refund immediately cancels the linked subscription (`end_date → now()`)

### Refund Database Schema

```
refunds
├── id
├── payment_id          → payments.id (CASCADE DELETE)
├── user_id             → users.id
├── amount              decimal(10,2)
├── reason              string
├── admin_notes         text (nullable)
├── status              enum: pending | completed | failed | manual_processing
├── refund_id           string (internal: REF-XXXX)
├── paymongo_refund_id  string (PayMongo's refund ID, nullable)
├── processed_by        → users.id (admin who triggered it)
├── processed_at        timestamp
├── refund_details      json
├── created_at
└── updated_at
```

---

## 10. Invoice System

### How Invoices are Generated

1. When a payment is completed, `PaymentSuccessful` event fires
2. `HandlePaymentSuccessful` listener dispatches `GenerateInvoiceJob`
3. `GenerateInvoiceJob` calls `InvoiceService::generateInvoice($payment)`
4. A PDF is generated via DomPDF and stored in `storage/app/invoices/`
5. An `Invoice` record is saved in the database

### Invoice Database Schema

```
invoices
├── id
├── payment_id        → payments.id (CASCADE DELETE)
├── user_id           → users.id
├── invoice_number    string (unique, e.g. INV-2026-00001)
├── pdf_path          string (relative path in storage)
├── subtotal          decimal(10,2)
├── tax               decimal(10,2)
├── total             decimal(10,2)
├── created_at
└── updated_at
```

### Accessing Invoices

```bash
# Make storage publicly accessible (run once after deployment)
php artisan storage:link
```

---

## 11. Admin Management

### Admin Panel Routes

| Route | Description |
|---|---|
| `GET /superadmin/subscriptions` | View all subscriptions and plans |
| `GET /superadmin/subscription-plans` | Manage subscription plans |
| `GET /superadmin/payments` | View all payments |
| `GET /superadmin/payments/{payment}` | View payment details + refund |
| `POST /superadmin/payments/{payment}/refund` | Process refund |
| `POST /superadmin/payments/{payment}/complete` | Manually mark payment completed |
| `POST /superadmin/subscriptions/quick-action` | Bulk subscription actions |

### Admin Roles

The admin panel requires either `superadmin` or `admin` role (enforced via middleware).

---

## 12. Routes Reference

### User-Facing Subscription Routes (auth required)

| Method | URI | Controller | Description |
|---|---|---|---|
| GET | `/subscription` | `SubscriptionController@index` | View current subscription |
| GET | `/subscription/upgrade` | `SubscriptionController@upgrade` | View available plans |
| POST | `/subscription/subscribe` | `SubscriptionController@subscribe` | Subscribe to a plan |
| POST | `/subscription/upgrade` | `SubscriptionController@processUpgrade` | Upgrade to a plan |
| POST | `/subscription/cancel` | `SubscriptionController@cancel` | Cancel subscription |
| POST | `/subscription/renew` | `SubscriptionController@renew` | Renew cancelled subscription |
| GET | `/subscription/status` | `SubscriptionController@checkStatus` | JSON status endpoint |

### User-Facing Payment Routes (auth required)

| Method | URI | Controller | Description |
|---|---|---|---|
| GET | `/payment/create/{subscription}` | `PaymentController@create` | Payment form |
| POST | `/payment/process/{subscription}` | `PaymentController@process` | Submit payment |
| GET | `/payment/pending/{payment}` | `PaymentController@pending` | Pending payment status |
| GET | `/payment/history` | `PaymentController@history` | Payment history |
| GET | `/payment/receipt/{payment}` | `PaymentController@receipt` | View receipt |
| GET | `/payment/paymongo/success/{subscription}` | `PaymentController@paymongoSuccess` | PayMongo success callback |
| GET | `/payment/paymongo/failed/{subscription}` | `PaymentController@paymongoFailed` | PayMongo failed callback |

### Legacy Direct PayMongo Routes (auth required)

| Method | URI | Controller | Description |
|---|---|---|---|
| POST | `/subscribe/monthly` | `SubscriptionController@subscribeMonthly` | Legacy monthly subscribe |
| POST | `/subscribe/annual` | `SubscriptionController@subscribeAnnual` | Legacy annual subscribe |

### Webhook Route (no auth — public)

| Method | URI | Controller | Description |
|---|---|---|---|
| POST | `/webhook/paymongo` | `PaymentController@webhook` | PayMongo webhook receiver |

---

## 13. Key Files Reference

### Controllers

| File | Responsibility |
|---|---|
| `app/Http/Controllers/SubscriptionController.php` | User subscription actions (subscribe, cancel, renew) |
| `app/Http/Controllers/PaymentController.php` | Payment processing, webhook, callbacks |
| `app/Http/Controllers/Admin/PaymentAdminController.php` | Admin payment management + refunds |
| `app/Http/Controllers/Admin/UnifiedSubscriptionAdminController.php` | Admin subscription + plan management |

### Services

| File | Responsibility |
|---|---|
| `app/Services/SubscriptionService.php` | Core subscription business logic |
| `app/Services/PayMongoPaymentLinkService.php` | PayMongo API integration |
| `app/Services/InvoiceService.php` | PDF invoice generation |
| `app/Services/RefundService.php` | PayMongo refund API |
| `app/Services/SubscriptionDunningService.php` | Failed payment retry + grace periods |
| `app/Services/AnalyticsService.php` | Revenue metrics (MRR, ARR, churn) |

### Models

| File | Table | Key Relationships |
|---|---|---|
| `app/Models/Subscription.php` | `subscriptions` | belongsTo User, belongsTo SubscriptionPlan, hasMany Payments |
| `app/Models/SubscriptionPlan.php` | `subscription_plans` | hasMany Subscriptions |
| `app/Models/Payment.php` | `payments` | belongsTo Subscription, belongsTo User, hasOne Invoice, hasMany Refunds |
| `app/Models/Invoice.php` | `invoices` | belongsTo Payment, belongsTo User |
| `app/Models/Refund.php` | `refunds` | belongsTo Payment |

### Jobs

| File | Queue | Trigger |
|---|---|---|
| `app/Jobs/GenerateInvoiceJob.php` | `invoices` | `HandlePaymentSuccessful` listener |
| `app/Jobs/SendPaymentReceiptEmail.php` | `emails` | `HandlePaymentSuccessful` listener |
| `app/Jobs/SendSubscriptionWelcomeEmail.php` | `emails` | `HandleSubscriptionCreated` listener |
| `app/Jobs/SendSubscriptionExpiredEmail.php` | `emails` | `HandleSubscriptionExpired` listener |

### Events & Listeners

| Event | Listener |
|---|---|
| `app/Events/PaymentSuccessful.php` | `app/Listeners/HandlePaymentSuccessful.php` |
| `app/Events/SubscriptionCreated.php` | `app/Listeners/HandleSubscriptionCreated.php` |
| `app/Events/SubscriptionExpired.php` | `app/Listeners/HandleSubscriptionExpired.php` |

### Form Requests

| File | Used In |
|---|---|
| `app/Http/Requests/SubscribeRequest.php` | `SubscriptionController::subscribe()` + `processUpgrade()` |
| `app/Http/Requests/ProcessPaymentRequest.php` | `PaymentController::process()` |
| `app/Http/Requests/CancelSubscriptionRequest.php` | `SubscriptionController::cancel()` |

### Migrations (in run order)

| Migration | What it creates |
|---|---|
| `2026_02_17_000001_create_subscription_plans_table.php` | `subscription_plans` table |
| `2026_02_17_000002_create_refunds_table.php` | `refunds` table |
| `2026_02_17_000003_create_invoices_table.php` | `invoices` table |
| `2026_02_17_000005_add_billing_indexes.php` | Performance indexes on billing tables |

### Console Commands

| Command | Schedule | File |
|---|---|---|
| `subscriptions:expire` | Daily | `app/Console/Commands/ExpireSubscriptions.php` |
| `subscriptions:process-renewals` | Daily | `app/Console/Commands/ProcessSubscriptionRenewals.php` |
| `billing:send-expiry-reminders` | Daily 09:00 | `app/Console/Commands/SendExpiryReminders.php` |
| `analytics:generate-report` | Weekly | `app/Console/Commands/GenerateAnalyticsReport.php` |

---

## 14. Environment Variables

Add all of the following to your `.env` file:

```env
# ─── PayMongo ────────────────────────────────────────────
PAYMONGO_PUBLIC_KEY=pk_test_xxxxxxxxxxxxxxxxxxxx
PAYMONGO_SECRET_KEY=sk_test_xxxxxxxxxxxxxxxxxxxx
PAYMONGO_WEBHOOK_SECRET=whsec_xxxxxxxxxxxxxxxxxxxx

# ─── Company Info (used on invoices) ─────────────────────
COMPANY_NAME="SexEd Platform"
COMPANY_ADDRESS="Your Physical Address"
COMPANY_TIN="123-456-789-000"

# ─── Billing Behaviour ───────────────────────────────────
PAYMENT_RETRY_ATTEMPTS=3
PAYMENT_GRACE_PERIOD_DAYS=7

# ─── Queue ───────────────────────────────────────────────
QUEUE_CONNECTION=database

# ─── Mail (Gmail SMTP example) ───────────────────────────
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your@gmail.com
MAIL_PASSWORD=your-16-char-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@yourdomain.com
MAIL_FROM_NAME="SexEd Platform"

# ─── App ─────────────────────────────────────────────────
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com
```

---

## 15. Implementation Phases

### Phase 1: Critical Fixes ✅ COMPLETE

Hardening the live system against data corruption and silent failures.

- [x] **Webhook signature verification** — `VerifyPayMongoWebhook` middleware validates `Paymongo-Signature`; invalid signatures rejected with 401
- [x] **Refund deactivates subscription immediately** — `end_date` set to `now()` and `auto_renew → false` on refund
- [x] **Subscription status cache invalidation** — `Subscription::booted()` calls `Cache::forget("user_is_premium_{$userId}")` on every save/delete
- [x] **Cron scheduler registered** — `routes/console.php` schedules all three daily commands

---

### Phase 2: Database & Architecture ✅ COMPLETE

Structural improvements for reliability and performance.

- [x] **Database indexes** — 6 indexes added via `2026_02_17_000005_add_billing_indexes.php`
- [x] **Webhook idempotency** — Cache-based deduplication with 24-hour TTL prevents double-processing
- [x] **Foreign key cascades** — All billing tables cascade on user/subscription delete
- [x] **Refund & Invoice models** — Models, migrations, services fully implemented
- [x] **SubscriptionService** — All subscription business logic centralized; controller is a thin HTTP layer
- [x] **`Auth` facade** — All `auth()->user()` calls replaced with `Auth::user()` + `/** @var User $user */` PHPDoc hints for IDE support

---

### Phase 3: Architecture Quality ✅ COMPLETE

- [x] **Event-driven architecture** — 3 events wired to 3 queued listeners
- [x] **Job queue** — 4 background jobs for email and PDF generation
- [x] **Form Request classes** — Validation extracted into `ProcessPaymentRequest`, `SubscribeRequest`, `CancelSubscriptionRequest`
- [x] **Standardized datetime** — All date columns cast to Carbon via Eloquent `$casts`
- [x] **Standardized logging** — All `\Log::` / `\Cache::` global calls replaced with imported facades; structured log arrays throughout
- [x] **DashboardController auto-verify** — `autoVerifyPendingPayment()` fires on dashboard load as a safety net for missed webhooks
- [x] **Subscription index view** — Plan features displayed dynamically from the `SubscriptionPlan` model (matches upgrade page)
- [x] **Dynamic plan features on dashboard** — Adults/teens/kids dashboards render plan features from real plan model

---

### Phase 3.5: Refund System ⚠️ PARTIAL

Two refund paths exist — admin quick refund is live; full PayMongo API refund needs UI wiring.

- [x] **`RefundService`** — Full service class: 3-day policy, idempotency, PayMongo API call, partial refunds, eligibility check
- [x] **`PaymentAdminController::processRefund`** — Admin quick refund (DB-only, no PayMongo API call); cancels subscription immediately
- [x] **3-day refund policy** — Enforced independently in both the controller and `RefundService`
- [x] **Duplicate refund guard** — `RefundService` blocks if a `pending`/`completed`/`manual_processing` refund already exists
- [ ] **Wire `RefundService` to admin UI** — Currently the admin panel uses the simple controller path; `RefundService` (which calls PayMongo API to actually return money) is not yet connected to a route
- [ ] **Admin refund approve/reject workflow** — UI for reviewing refund requests before processing

---

### Phase 4: Pre-Deployment (Do Before Going Live) ⚠️ REQUIRED

**Phase 4 must be completed before deploying to production.**

#### 4a — Feature Flags ❌ NOT STARTED
- [ ] Add `spatie/laravel-feature-flags` or a simple `config/features.php`
- [ ] Flags needed: `subscriptions.enabled`, `payments.paymongo_live`, `emails.enabled`, `queue.enabled`

#### 4b — Comprehensive Testing ❌ NOT STARTED

```
tests/Feature/
├── SubscriptionLifecycleTest.php
│     ✓ User can subscribe to monthly plan
│     ✓ Subscription is pending after creation
│     ✓ Subscription activates when payment completed
│     ✓ User cannot subscribe if already premium
│     ✓ User can cancel active subscription
│     ✓ Subscription expires via cron command
│
├── WebhookTest.php
│     ✓ Valid webhook signature is accepted
│     ✓ Invalid webhook signature is rejected (401)
│     ✓ Duplicate webhook is silently ignored (idempotency)
│     ✓ link.payment.paid activates subscription
│
├── RefundTest.php
│     ✓ Admin can process quick refund
│     ✓ Refund cancels subscription immediately
│     ✓ Refund blocked after 3 days
│     ✓ Duplicate refund blocked
│
└── InvoiceTest.php
      ✓ Invoice generated after payment completed
      ✓ Duplicate invoice not created (idempotent)
```

#### 4c — Monitoring & Alerting ❌ NOT STARTED
- [ ] Set up Laravel Telescope in staging
- [ ] Set up Sentry or Bugsnag for error tracking
- [ ] Configure `critical` log channel to alert via Slack/email
- [ ] Queue monitoring

#### 4d — API & Webhook Documentation ❌ NOT STARTED
- [ ] Register production webhook URL in PayMongo dashboard
- [ ] Store a PayMongo webhook payload sample in `tests/fixtures/`

---

### Phase 5: Analytics (Post-Launch) 🔜

- [ ] MRR / ARR / churn dashboard widgets in admin panel
- [ ] Automated weekly analytics email (`WeeklyAnalyticsReport`)
- [ ] Export revenue data to CSV

---

### Phase 6: Financial Improvements (Post-Launch) 🔜

- [ ] Wire `RefundService` to admin UI so actual PayMongo API refunds happen from the panel
- [ ] Admin refund UI (approve / reject workflow)
- [ ] Invoice download endpoint for users (`GET /invoices/{invoice}/download`)
- [ ] Tax rate configuration per region
- [ ] Monthly revenue report email to owner

---

## 16. Deployment Checklist

Complete this checklist in order before going live.

### Database
- [ ] Run `php artisan migrate --force` on production
- [ ] Run `php artisan db:seed --class=SubscriptionPlanSeeder` to create initial plans
- [ ] Verify all 6 billing indexes exist: `php artisan migrate:status`
- [ ] Run `php artisan storage:link` for invoice PDF public access

### Environment
- [ ] Set `APP_ENV=production`, `APP_DEBUG=false`
- [ ] Set all PayMongo **live** keys (not test keys)
- [ ] Set `PAYMONGO_WEBHOOK_SECRET` to the value from PayMongo dashboard
- [ ] Set `QUEUE_CONNECTION=database`
- [ ] Configure mail SMTP settings and test with `php artisan tinker` → `Mail::raw('test', fn($m) => $m->to('you@email.com'))`

### PayMongo Dashboard
- [ ] Register production webhook URL: `https://yourdomain.com/webhook/paymongo`
- [ ] Enable event: `link.payment.paid`
- [ ] Copy the webhook secret into your `.env`

### Server
- [ ] Set up Supervisor for queue worker (see Section 7)
- [ ] Set up cron job for Laravel scheduler: `* * * * * php /path/artisan schedule:run`
- [ ] Verify cron is working: `php artisan schedule:list`
- [ ] Set up SSL certificate (HTTPS required for PayMongo callbacks)

### Caches
- [ ] `php artisan config:cache`
- [ ] `php artisan route:cache`
- [ ] `php artisan view:cache`
- [ ] `php artisan event:cache`

### Testing (Production Smoke Test)
- [ ] Create a test subscription with a ₱1 test plan
- [ ] Complete payment with PayMongo test card
- [ ] Verify webhook received (check `storage/logs/laravel.log`)
- [ ] Verify subscription activated
- [ ] Verify receipt email received
- [ ] Verify invoice PDF generated
- [ ] Test cancellation flow
- [ ] Switch PayMongo keys to live
- [ ] Remove `/payment/simulate-success` route (it's gated to `local` env already, but confirm)

---

## 17. Troubleshooting

### Subscription not activating after payment

1. Check `storage/logs/laravel.log` for webhook logs
2. Verify `PAYMONGO_WEBHOOK_SECRET` matches the value in PayMongo dashboard
3. Verify queue worker is running: `php artisan queue:work`
4. Check failed jobs: `php artisan queue:failed`
5. In test mode: use the "Simulate Payment" button on the pending payment page

### Webhook returns 401

- `PAYMONGO_WEBHOOK_SECRET` in `.env` does not match the secret in PayMongo dashboard
- If testing locally with no secret set yet, the system logs a warning but allows it through

### Emails not sending

1. Check queue worker is running
2. Check failed jobs: `php artisan queue:failed`
3. Verify mail config: `php artisan config:show mail`
4. Test directly: `php artisan tinker` → `Mail::raw('test', fn($m) => $m->to('you@test.com'))`

### Duplicate subscription created

- Protected by the check in `SubscriptionService::create()` — all existing `pending` subs are cancelled before a new one is created
- `payments(transaction_id)` unique index prevents duplicate payment records

### Queue jobs not processing

```bash
# Check worker status
php artisan queue:work --once  # process one job manually

# Check for failed jobs
php artisan queue:failed

# Restart all workers
php artisan queue:restart
```

### Cron not running

```bash
# Test scheduler manually
php artisan schedule:run

# Check what's scheduled
php artisan schedule:list

# Check cron is set up
crontab -l
```

---

*This document reflects the state of the system as of February 2026. Update this file whenever a new phase is completed or the payment flow changes.*

---

## 18. Database Transaction Safety

Every operation that touches two or more tables atomically is wrapped in a `DB::transaction()`. If any step fails, the entire block rolls back — preventing partial state (e.g., "user paid but never got access").

### Webhook Activation Transaction

The most critical transaction is in `PaymentController::webhook()`:

```php
DB::transaction(function () use ($payment, $subscription, $paymentData, $paymentMethod) {
    // Step 1: Mark payment as completed
    $payment->update([
        'status'     => 'completed',
        'method'     => $paymentMethod,
        'paid_at'    => now(),
        'payment_details' => [...],
    ]);

    // Step 2: Activate subscription via service (handles its own nested transaction + cache + event)
    $this->subscriptionService->activate($subscription);
});
```

If the server crashes between steps 1 and 2, the entire transaction is rolled back. PayMongo will retry the webhook, and the process starts clean.

### SubscriptionService Transactions

Every state-changing method in `SubscriptionService` is individually wrapped:

| Method | What's in the transaction |
|---|---|
| `activate()` | `subscription.status → active` |
| `cancel()` | `subscription.status`, `cancelled_at`, `cancellation_reason`, `auto_renew` |
| `expire()` | `subscription.status → expired`, `auto_renew → false` |
| `renew()` | `subscription.status → active`, `start_date`, `end_date`, `auto_renew` |
| `switchPlan()` | Cancels old subscription + creates new pending subscription |

> **Rule:** No billing state change is ever made with a plain `->update()` call outside of a transaction.

---

## 19. Race Condition & Idempotency Protection

Several scenarios can cause the same activation logic to be triggered more than once:

- PayMongo retries a webhook delivery
- User opens the "success" callback page while the webhook is still processing
- Two queue workers pick up the same job simultaneously

Protection is implemented at multiple layers:

### Layer 1 — Webhook Idempotency (Cache)

```php
$cacheKey = "webhook_processed_{$eventId}";
if (Cache::has($cacheKey)) {
    return response()->json(['success' => true, 'already_processed' => true]);
}
Cache::put($cacheKey, true, now()->addDay()); // 24-hour TTL
```

The same PayMongo event ID will never be processed twice within 24 hours.

### Layer 2 — SubscriptionService::activate() Guard

```php
public function activate(Subscription $subscription): void
{
    if ($subscription->status === 'active') {
        return; // Already active — idempotent, no-op
    }
    // ...
}
```

Even if `activate()` is called twice (e.g., by both the webhook handler and the PaymentObserver), the second call returns immediately. The subscription is never double-activated.

### Layer 3 — PaymentObserver as Safety Net

`PaymentObserver::updated()` fires automatically whenever `payment.status → 'completed'`. This provides a second activation path — so if the webhook fails to call `activate()` directly, the observer catches it when the payment record is written.

### Layer 4 — `paymongoSuccess` Callback

`GET /payment/paymongo/success/{subscription}` also attempts activation. Combined with the idempotency guard in `activate()` and the cache-based webhook deduplication, no amount of concurrent requests can cause double-activation.

---

## 20. Dead Letter Strategy

All four background jobs retry up to **3 times** with a **30–60 second backoff** before being permanently failed. When a job exhausts all retries, the `failed()` method is called and a `Log::critical()` entry is written.

### Why `critical` Level?

`Log::error` — something went wrong, worth investigating.
`Log::critical` — **revenue or user trust is directly affected**. A payment receipt never delivered or an invoice never generated = a real support ticket waiting to happen.

If you configure a log channel to alert on `critical` (Slack, email, Sentry), you'll be notified immediately.

### What the `failed()` Handler Does

| Job | Consequence of permanent failure | Remediation |
|---|---|---|
| `GenerateInvoiceJob` | User has no downloadable invoice | Manual: `app(InvoiceService::class)->generateInvoice($payment)` in tinker |
| `SendPaymentReceiptEmail` | User never received receipt | Resend from admin panel, or `php artisan queue:retry all` |
| `SendSubscriptionWelcomeEmail` | User never received onboarding email | Resend from admin panel |
| `SendSubscriptionExpiredEmail` | User not notified of expiry | Low severity — user will notice on next login |

### Monitoring Failed Jobs

```bash
# List all permanently failed jobs
php artisan queue:failed

# Retry all (safe to run repeatedly — activate() and generateInvoice() are idempotent)
php artisan queue:retry all

# Clear after resolving
php artisan queue:flush
```

> **Key principle:** Because `activate()` and `generateInvoice()` are idempotent, retrying jobs is always safe. You will never double-charge or double-generate an invoice.

---

## 21. Rate Limiting

### Webhook Route

The webhook endpoint is protected by Laravel's built-in rate limiter:

```php
Route::post('/webhook/paymongo', [PaymentController::class, 'webhook'])
    ->middleware('throttle:60,1')  // 60 requests per minute
    ->name('webhook.paymongo');
```

This prevents:
- Brute-force signature cracking via rapid repeated requests
- Denial-of-service via webhook flooding

**Note:** PayMongo's actual webhook delivery rate is far below 60/min in normal operations. If your logs show the throttle being hit, investigate immediately — it likely indicates a misconfigured or malicious source.

### Auth Routes

Login and email verification routes already have `throttle:6,1` (6 attempts per minute).

---

## 22. Refund Idempotency

`RefundService::processRefund()` checks for an existing non-failed refund before creating a new one:

```php
$existingRefund = Refund::where('payment_id', $payment->id)
    ->whereIn('status', ['pending', 'completed', 'manual_processing'])
    ->first();

if ($existingRefund) {
    Log::warning('Duplicate refund attempt blocked', [...]);
    throw new \RuntimeException(
        "A refund for this payment already exists (ID: {$existingRefund->refund_id}, status: {$existingRefund->status}). " .
        "Contact support if you believe this is an error."
    );
}
```

> **Note:** The admin quick-refund path (`PaymentAdminController::processRefund`) has its own duplicate check — it verifies `payment.status !== 'refunded'` before proceeding. Both paths are independently guarded.

---

## 23. Financial Audit Trail

Every financially significant event is logged with structured data. Logs are retained for at least 30 days in production (configure in `config/logging.php`).

### What Is Logged

| Event | Level | Location |
|---|---|---|
| Payment created | `info` | `PaymentObserver::created()` |
| Payment status changed to `completed` | `info` | `PaymentController::webhook()` |
| Payment status changed to `failed` | `warning` | `PaymentObserver::updated()` |
| Subscription activated | `info` | `SubscriptionService::activate()` |
| Subscription cancelled | `info` | `SubscriptionService::cancel()` |
| Subscription expired | `info` | `SubscriptionService::expire()` |
| Refund processed | `info` | `RefundService::processRefund()` |
| Duplicate refund blocked | `warning` | `RefundService::processRefund()` |
| Webhook received | `info` | `PaymentController::webhook()` |
| Webhook signature invalid | `error` | `PaymentController::webhook()` |
| Duplicate webhook ignored | `info` | `PaymentController::webhook()` |
| Invoice generation failed permanently | `critical` | `GenerateInvoiceJob::failed()` |
| Receipt email failed permanently | `critical` | `SendPaymentReceiptEmail::failed()` |

### Log Format

All log entries include at minimum:

```
[timestamp] [level] Message Context:
  - payment_id or subscription_id
  - user_id
  - amount (where applicable)
  - error message (on failures)
  - failed_at + action instructions (on critical failures)
```

### Configuring Log Retention

In `config/logging.php`, set the daily log retention:

```php
'daily' => [
    'driver' => 'daily',
    'path'   => storage_path('logs/laravel.log'),
    'level'  => 'debug',
    'days'   => 30,  // Retain 30 days of logs
],
```

### Legal Protection

Audit logs protect you if a user disputes a charge. You can demonstrate:
- Exact timestamp of webhook receipt
- Exact timestamp of subscription activation
- IP address and payment method used (from PayMongo metadata)
- Any refund attempts and their outcomes

---

## 24. Disaster Recovery Plan

### Scenario 1 — PayMongo Webhook Down for 1+ Hours

**Symptom:** Users pay but subscriptions don't activate.

**Detection:** Check `storage/logs/laravel.log` — no `PayMongo Webhook Received` entries after a successful payment.

**Recovery steps:**
1. Log into the PayMongo dashboard → Webhooks → check delivery status
2. Use the PayMongo dashboard to manually redeliver failed webhooks
3. If PayMongo delivery fails, manually activate in admin: `/superadmin/payments/{payment}` → "Mark Completed"
4. This triggers `PaymentObserver::updated()` → `SubscriptionService::activate()` → all downstream jobs

**Prevention:** The `/payment/paymongo/success/{subscription}` callback also attempts activation as a backup path.

---

### Scenario 2 — Queue Worker Crashed / Not Running

**Symptom:** Subscriptions activate but no emails or invoices are generated.

**Detection:** `php artisan queue:failed` shows jobs, or users report not receiving emails.

**Recovery steps:**
```bash
# Check if worker is running
php artisan queue:work --once

# Restart Supervisor to bring workers back
sudo supervisorctl restart sexed-queue-worker:*

# Retry all failed jobs (idempotent — safe to run)
php artisan queue:retry all
```

---

### Scenario 3 — Database Corruption / Rollback Needed

**Symptom:** Subscriptions in inconsistent state after a failed deployment or migration.

**Recovery steps:**
```bash
# Roll back the last migration
php artisan migrate:rollback

# Or roll back to a specific batch
php artisan migrate:rollback --step=2

# Restore from daily backup (see Backup Strategy below)
```

---

### Scenario 4 — PayMongo API Down (Refund Fails)

**Symptom:** Admin attempts refund, `RefundService` gets an HTTP error from PayMongo API.

**Recovery:**
- The `Refund` record is created with `status: failed`
- On retry, the idempotency check allows retry because the existing record status is `failed`
- Retry when PayMongo API is back: navigate to admin → payments → process refund again

---

### Backup Strategy

#### Database Backups

Set up automated daily MySQL backups:

```bash
# Example: mysqldump to S3 via cron
0 2 * * * mysqldump -u root -p[password] sexed_db | gzip > /backups/sexed_$(date +\%Y\%m\%d).sql.gz

# Or use a Laravel backup package:
composer require spatie/laravel-backup
php artisan backup:run
```

Retain: minimum 30 days of daily backups.

#### Invoice Storage Backups

Invoices are PDF files stored in `storage/app/invoices/`. Back up this directory:

```bash
# Example: sync to S3
aws s3 sync storage/app/invoices/ s3://your-bucket/invoices/
```

Or configure `config/filesystems.php` to use S3 as the default disk so invoices are stored in S3 natively.

#### `.env` Secret Backup

Never commit `.env` to Git. Store a copy of your production `.env` in a password manager or a secrets manager (AWS Secrets Manager, HashiCorp Vault).

At minimum, keep a record of:
- `PAYMONGO_SECRET_KEY`
- `PAYMONGO_WEBHOOK_SECRET`
- `APP_KEY`
- Database credentials

#### Recovery Time Objectives

| Asset | Backup frequency | Max acceptable loss | Recovery time |
|---|---|---|---|
| Database | Daily | 24 hours | < 1 hour (restore from dump) |
| Invoice PDFs | Daily | 24 hours | < 2 hours (restore from S3) |
| Application code | Git (always) | 0 | < 30 min (pull + deploy) |
| `.env` secrets | Manual (on change) | Last known good | < 15 min |
