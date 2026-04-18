# Quick Testing Guide - Email Verification Flow

This is a quick reference for testing the new authentication system.

---

## Seeded Test Accounts (Reusable)

Seed dedicated role and workflow test accounts:

```bash
php artisan db:seed --class=Database\\Seeders\\TestUserSeeder
```

Shared password for all accounts: `password123`

- `admin@test.local` - Admin account for platform governance and management flows.
- `instructor@test.local` - Approved instructor account for module and publishing workflows.
- `instructor.paid@test.local` - Instructor with an active paid subscription plan.
- `instructor.pending@test.local` - Pending instructor applicant for moderation queue checks.
- `kid@test.local` - Learner account in kids age bracket.
- `teen@test.local` - Learner account in teens age bracket.
- `adult@test.local` - Learner account in adults age bracket.
- `premium.learner@test.local` - Learner with an active premium subscription plan.
- `parent@test.local` - Approved parent account.
- `linked.child@test.local` - Child account already linked to `parent@test.local`.

---

## Centralized Moderation Verification Pack (2026-04-18)

Run this full targeted pack before moderation cutover:

```bash
php artisan test --filter="Moderation|Suspension|Appeal|Automation|ContentReportFlowTest|AdminContentReviewWorkflowTest|AdminInstructorApplicationsUiTest"
```

Focused follow-up packs:

```bash
php artisan test --filter="ModerationDualWriteParityTest|BackfillCentralizedModerationTest|ModerationParityReconciliationTest"
php artisan test --filter="SuspensionAppealUiFlowTest|AdminAppealReviewUiTest|ModerationLifecycleNotificationTest"
php artisan moderation:backfill-centralized --reconcile-only
```

Checklist and recorded execution summary:

- [2026-04-17-centralized-moderation-cutover-checklist.md](plans/2026-04-17-centralized-moderation-cutover-checklist.md)

---

## Instructor Profile And Module/Quiz Configuration Smoke Tests

Run these after migrations to verify the instructor profile rollout and scalable module/quiz settings:

```bash
php artisan test --testsuite=Feature --filter=Instructor
php artisan test --filter=LearnerQuiz
php artisan test --filter=ModuleCapacity
php artisan test
```

Target checks covered by these suites:

- Instructor profile schema, page rendering, and secure profile updates
- Module pricing, enrollment limit, and paid entitlement gate
- Quiz attempt limits, timer normalization, and auto-submit fallback behavior
- Learner-side attempt cap and module capacity queue routing

---

## Learner Checkout Refinement QA (2026-04-05)

Run these commands to validate the summary-first learner checkout rollout:

```bash
php artisan test --filter=LearnerCheckoutFeatureFlagTest
php artisan test --filter=LearnerCheckoutRoutingFlowTest
php artisan test --filter=LearnerCheckoutSummaryViewTest
php artisan test --filter=LearnerCheckoutPayloadContractTest
php artisan test --filter=LearnerCheckoutCancelFailureFlowTest
php artisan test --filter=LearnerCheckoutCompletionIdempotencyTest
php artisan test --filter=LearnerModulePaymentWebhookTest
php artisan test --filter=LearnerPaymentHistoryModuleTransactionsTest
php artisan test --filter=LearnerSubscriptionCheckoutHistoryTest
php artisan test --filter=PayMongoPaymentLinkServiceTest
php artisan test --filter=Payment
php artisan test --filter=Webhook
```

Focus checklist for manual QA:

- Module checkout and subscription checkout open in summary-first pages before redirecting to PayMongo.
- Checkout summary page has no sandbox banner, no local payment method picker, and no billing form fields.
- PayMongo checkout displays multiple available methods (card, GCash, and other enabled options), not a locked QR-only path.
- Checkout request payload targets `/v1/checkout_sessions` and contains `line_items`, `success_url`, `cancel_url`, and `payment_method_types`.
- Failed/cancelled PayMongo returns users to the correct summary page with retry guidance.
- Duplicate webhook events do not duplicate module enrollments, sale ledger rows, or post-payment queue behavior.
- Payment History still shows both module and subscription transactions after rollout.

Sandbox note:

- PayMongo sandbox may still show QR-oriented hosted steps even for wallet selections. This is expected sandbox behavior; production method rendering can differ.

---

## ⚡ QUICK START

### 1. Configure Gmail SMTP (One-Time Setup)

```bash
# 1. Open .env file and update these lines:
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password-here
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your-email@gmail.com
MAIL_FROM_NAME="${APP_NAME}"
```

**Get App Password:**
1. Go to https://myaccount.google.com/security
2. Enable "2-Step Verification" if not already enabled
3. Search for "App Passwords" or go to https://myaccount.google.com/apppasswords
4. Create new app password for "Mail"
5. Copy the 16-character password (no spaces)
6. Paste into `MAIL_PASSWORD` in .env

**Clear config cache:**
```bash
php artisan config:clear
```

### 2. Test Email Sending

```bash
php artisan tinker
```

```php
// In tinker console:
Mail::raw('Test email from Laravel', function($msg) {
    $msg->to('your-email@gmail.com')->subject('Test');
});

// Should output: null (means success)
// Check your Gmail inbox for the test email
```

If you get an error, see [GMAIL_SMTP_SETUP.md](GMAIL_SMTP_SETUP.md) for troubleshooting.

---

## 🧪 TEST SCENARIOS

### Scenario 1: Register as 13+ User (Email Verification Required)

**Steps:**
1. Visit: http://localhost:8000/register
2. Fill form:
   - First Name: "Juan"
   - Middle Initial: "D." (optional)
   - Last Name: "Cruz"
   - Suffix: Leave blank or select "Jr."
   - Birthdate: Select a date that makes you 13-17 years old
     - Example: If today is 2025-02-11, use 2010-02-11 (15 years old)
   - Email: your-email@gmail.com
   - Password: Password123!
   - Confirm Password: Password123!
3. Watch the age indicator turn **green** (eligible)
4. Check "I agree to Terms and Privacy"
5. Click "Register"

**Expected Result:**
- Redirects to `/email/verify` (verification notice page)
- Gmail receives verification email
- Email contains verification link

**Next Steps:**
6. Check Gmail inbox
7. Click verification link
8. Should redirect to `/profile/complete`
9. Complete profile (username, location, bio)
10. Redirects to dashboard

---

### Scenario 2: Register as Under 13 (Parent Required)

**Steps:**
1. Visit: http://localhost:8000/register
2. Fill form with birthdate that makes you under 13
   - Example: If today is 2025-02-11, use 2018-02-11 (7 years old)
3. Watch age indicator turn **orange** (parent required)
4. Submit form

**Expected Result:**
- Redirects to `/parent-registration-required`
- Shows explanation page with 4 steps
- Provides link to parent registration

---

### Scenario 3: Parent Creates Account → Child Account

**Part A: Parent Registration**
1. Visit: http://localhost:8000/parent/register
2. Fill form:
   - First Name: "Maria"
   - Last Name: "Santos"
   - Birthdate: Select date making you 18+ (e.g., 1985-05-15)
   - Email: parent-email@gmail.com
   - Password: ParentPass123!
3. Age indicator shows **green** (18+, eligible)
4. Submit form

**Expected Result:**
- Parent account created
- Verification email sent
- Redirects to `/email/verify`

**Part B: Verify Parent Email**
5. Check Gmail inbox
6. Click verification link
7. Redirects to dashboard or profile completion

**Part C: Create Child Account**
8. Visit: http://localhost:8000/parent/create-child
9. Fill child's info:
   - First Name: "Pedro"
   - Last Name: "Santos"
   - Birthdate: Select date making child 5-17 (e.g., 2015-03-20)
   - Username: "pedro_santos"
   - Password: ChildPass123!
   - Permissions: Check "View Progress" and "View Quiz Answers"
10. Submit form

**Expected Result:**
- Child account created
- **No email verification needed** for child (auto-verified)
- Parent-child relationship created
- Redirects to `/parent/children` (children list)

**Part D: View Child Dashboard**
11. Visit: http://localhost:8000/parent/children
12. Should see Pedro's card with:
    - Name, age
    - Username
    - Stats (0 modules, 0 quizzes, 0 achievements)
    - Action buttons (placeholders for now)

**Part E: Test Child Login**
13. Logout as parent
14. Visit: http://localhost:8000/login
15. Login as child:
    - Email or Username: pedro_santos
    - Password: ChildPass123!
16. Should login successfully without email verification

---

## 🔍 VERIFICATION CHECKLIST

After testing, verify:

- [ ] 13+ users receive verification email
- [ ] Verification link works and redirects correctly
- [ ] <13 users redirected to parent required page
- [ ] Parent must be 18+ to register
- [ ] Parent receives verification email
- [ ] Parent cannot create child until email verified
- [ ] Child account created successfully
- [ ] Child does NOT need email verification
- [ ] Child appears in parent dashboard
- [ ] Child can login with username/password
- [ ] Terms and privacy pages load correctly
- [ ] Profile completion doesn't ask for birthdate or grade_level
- [ ] Registration form shows real-time age calculation

---

## 🐛 COMMON ISSUES

### "Connection could not be established with host smtp.gmail.com"
- **Cause:** Wrong Gmail credentials or App Password not generated
- **Fix:** Double-check MAIL_USERNAME and MAIL_PASSWORD in .env
- **Fix:** Make sure you're using App Password, not regular password

### "Too many login attempts"
- **Cause:** Gmail blocked access after multiple failed attempts
- **Fix:** Wait 30 minutes or review security settings in Google account

### "Verification link expired"
- **Cause:** Default verification links expire after 60 minutes
- **Fix:** Register again or extend expiration in config/auth.php

### "Child cannot be created"
- **Cause:** Parent email not verified
- **Fix:** Make sure parent clicked verification link before creating child

### Email not received
- **Cause:** Check spam folder, wrong email, or SMTP not configured
- **Fix:** 
  1. Check spam/junk folder
  2. Test with `php artisan tinker` (see Quick Start #2)
  3. Review GMAIL_SMTP_SETUP.md for detailed troubleshooting

---

## 📧 EMAIL TEMPLATES

### Verification Email (Laravel Default)
**Subject:** Verify Email Address

**Content:**
> Hello!
> 
> Please click the button below to verify your email address.
> 
> [Verify Email Address]
> 
> If you did not create an account, no further action is required.

**Customize:**
To customize, publish Laravel's email templates:
```bash
php artisan vendor:publish --tag=laravel-mail
```
Then edit: `resources/views/vendor/mail/html/button.blade.php`

---

## 🎯 TEST DATA SUGGESTIONS

### Teen Registration (13-17)
- Birthdate: 2008-01-15 (will be ~17 years old)
- Email: teen.test@gmail.com
- Username: teen_learner

### Adult Registration (18+)
- Birthdate: 1995-06-20 (will be ~30 years old)
- Email: adult.test@gmail.com
- Username: adult_learner

### Parent Account
- Birthdate: 1985-03-10 (will be ~40 years old)
- Email: parent.test@gmail.com
- Name: Maria Santos

### Child Account (Under 13)
- Birthdate: 2017-05-15 (will be ~8 years old)
- Username: child_learner
- Parent: parent.test@gmail.com

---

## 🚀 DEPLOYMENT CHECKLIST

---

## Instructor Panel Refinement QA Checklist (2026-03-19)

Use this checklist after deploying instructor refinement updates.

### 1. Learners Management Scope + View-Only

- Login as instructor and open `/instructor/users`
- Confirm only learners enrolled in instructor-owned modules are listed
- Confirm actions are view-only (no add/edit learner mutation paths)

### 2. Search Routing + Visibility Rules

- On dashboard, use global search and verify routes:
   - Module -> module details page
   - Lesson -> lesson details page
   - Learner -> learner information page
- Confirm global search is dashboard-only
- Confirm local search/filter controls still work on management pages

### 3. Enrollment Rejection Workflow

- From module details/enrollments, reject an enrollment
- Confirm reason code is required
- Confirm optional note is saved
- Confirm learner receives notification containing:
   - module title
   - rejection reason/note
   - instructor name

### 4. Active/Inactive Hybrid Learner Behavior

- Deactivate a module/lesson/quiz from instructor flow
- Confirm learner can still see deactivated module in enrolled/history context
- Confirm lesson/quiz progression actions are blocked for deactivated content
- Confirm historical progress remains viewable

### 5. Modal Edit + Delete Confirmation UX

- From lessons and quizzes index pages, verify edit opens modal workflow
- Validate modal preserves input and errors when validation fails
- Trigger delete from key instructor pages and confirm modal appears before mutation

### 6. Assessment Insights

- Open `/instructor/assessments`
- Verify sections render:
   - score distribution by module
   - attempt count per learner
   - at-risk learner table
- Test thresholds with query params:
   - `?low_score_threshold=60&low_activity_threshold=2`

### 7. Notification Center + Sidebar Polish

- On instructor dashboard, open notification bell
- Confirm quiz-taking summary appears when attempts occurred in last 24h
- Confirm enrollment decision notifications are listed with title + message
- Confirm sidebar includes Assessment Logs entry and icon/readability remains consistent

### 8. Regression Commands

```bash
php artisan test tests/Feature/Instructor
php artisan test
```

Expected: all passing, no new failures.

Before deploying to production:

- [ ] Use real Gmail account for MAIL_FROM_ADDRESS
- [ ] Or use Gmail Workspace for higher limits (2000 emails/day)
- [ ] Or switch to professional email service (SendGrid, Mailgun)
- [ ] Customize email templates with platform branding
- [ ] Test all email flows in production environment
- [ ] Set up email monitoring (track delivery rates)
- [ ] Configure SPF and DKIM records for better deliverability
- [ ] Review Gmail sending limits (500/day for free)
- [ ] Consider SMS backup for critical verifications

---

## 📞 SUPPORT

If you encounter issues:
1. Check [GMAIL_SMTP_SETUP.md](GMAIL_SMTP_SETUP.md) for detailed Gmail setup
2. Check [AUTHENTICATION_IMPLEMENTATION_SUMMARY.md](AUTHENTICATION_IMPLEMENTATION_SUMMARY.md) for complete feature documentation
3. Review Laravel logs: `storage/logs/laravel.log`
4. Check email queue: `php artisan queue:work` (if using queues)

---

**Happy Testing! 🎉**
