# Provider-Neutral Email Validation Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Accept any syntactically valid email provider across registration and admin credential updates while preserving authentication, verification, uniqueness, and invalid-format rejection.

**Architecture:** Make the smallest provider-policy change at the three existing Gmail enforcement points, leaving already-neutral email paths untouched. Align active Blade copy with the new rule, keep Gmail inbox shortcuts conditional on the authenticated user's actual domain, and prove behavior with focused Laravel feature tests plus a database-free copy regression test.

**Tech Stack:** PHP 8.2+, Laravel 12, Blade, PHPUnit 11.5, MySQL test database

**Spec:** `docs/superpowers/specs/2026-10-25-provider-neutral-email-validation-design.md`

## Global Constraints

- Work in the current `feature/help-center-feedback-testimonials` checkout; do not create a worktree or switch branches.
- Preserve all unrelated dirty files and generated assets; scope every edit and diff command explicitly.
- Do not commit, push, stash, or use `git add .`.
- Never reset, wipe, recreate, or destructively seed the development database.
- Run database-backed tests only after confirming the effective environment is `testing` and both configured and connected database names are exactly `cc_db_test`.
- If the database preflight does not match exactly, stop database-backed testing and report the blocker.
- Use `email:rfc` for the affected registration/admin paths; do not add DNS checks, domain allowlists, denylists, disposable-domain checks, or custom domain regexes.
- Preserve existing normalization, uniqueness, password, verification, notification, role, and child-account behavior.
- Keep Gmail SMTP setup documentation, factual SMTP-provider text, public contact addresses, historical plans, and Gmail child-alias behavior unchanged.
- Do not rebuild `public/build`; this implementation changes only PHP and Blade source.

## Review Focus

- Mixed-case or padded learner input still follows `AccountInfoRequest::prepareForValidation()` normalization, and uniqueness remains case-safe; pin this in `RegistrationTest`.
- Valid provider examples (`gmail.com`, `yahoo.com`, `outlook.com`, `school.edu.ph`, `temporary-mail.com`) pass while malformed local/domain structures fail; pin this in `RegistrationTest`.
- A non-Gmail guardian completes account creation without changing the existing child-address fallback; pin registration in `ParentRegistrationUploadPersistenceTest` and leave child generation untouched.
- Admin credential updates accept non-Gmail syntax but still reject malformed and duplicate addresses; pin request and controller behavior in the two admin profile tests.
- Gmail users retain the inbox shortcut while non-Gmail users never receive a misleading Gmail link; pin both verification views in their owning feature tests.

---

### Task 1: Provider-Neutral Backend Validation

**Files:**
- Modify: `app/Http/Requests/AccountInfoRequest.php:25-32,45-51`
- Modify: `app/Http/Controllers/Auth/ParentRegistrationController.php:168-176`
- Modify: `app/Http/Requests/Admin/UpdateAdminCreatorProfileRequest.php:22-30,55-60`
- Modify: `tests/Feature/Auth/RegistrationTest.php`
- Modify: `tests/Feature/Auth/ParentRegistrationUploadPersistenceTest.php:14-42`
- Modify: `tests/Feature/Auth/AuthenticationTest.php`
- Modify: `tests/Feature/Admin/UpdateAdminCreatorProfileRequestTest.php:53-87`
- Modify: `tests/Feature/Admin/AdminProfileControllerTest.php:55-143`

**Interfaces:**
- Consumes: Laravel validation rule strings, the existing two-step learner/guardian registration routes, and current admin credentials update route.
- Produces: provider-neutral `email:rfc` validation at all three former Gmail gates; no new class or public method.

- [ ] **Step 1: Verify the isolated test database before any feature test**

Run in PowerShell:

```powershell
$env:APP_ENV='testing'
$env:APP_CONFIG_CACHE='bootstrap/cache/config-testing.php'
$env:DB_CONNECTION='mysql'
$env:DB_DATABASE='cc_db_test'
php artisan tinker --execute="dump(['environment' => app()->environment(), 'connection' => config('database.default'), 'configured_database' => config('database.connections.'.config('database.default').'.database'), 'connected_database' => DB::connection()->getDatabaseName()]);"
```

Expected: `environment` is `testing`, `connection` is `mysql`, and both database values are `cc_db_test`. Stop if any value differs.

- [ ] **Step 2: Write failing provider and format regression tests**

In `RegistrationTest`, retain the existing Gmail success test and add:

```php
use PHPUnit\Framework\Attributes\DataProvider;

#[DataProvider('providerNeutralEmails')]
public function test_registration_accepts_provider_neutral_email_domains(string $email): void

#[DataProvider('malformedEmails')]
public function test_registration_rejects_clearly_malformed_email_formats(string $email): void

public function test_registration_normalizes_mixed_case_and_surrounding_whitespace(): void

public function test_registration_still_rejects_duplicate_email_addresses(): void
```

The valid provider supplies exactly:

```php
'Gmail' => ['provider.test@gmail.com'],
'Yahoo' => ['provider.test@yahoo.com'],
'Outlook' => ['provider.test@outlook.com'],
'Education' => ['provider.test@school.edu.ph'],
'Temporary' => ['provider.test@temporary-mail.com'],
```

Each valid case submits the existing account-step session and strong password, asserts no email error, asserts authentication, and asserts the email exists. The normalization test submits `  Provider.Test@Yahoo.COM  ` and asserts the stored address is exactly `provider.test@yahoo.com`.

Malformed cases cover `plain-address`, `person@`, `@example.com`, `person@@example.com`, and `person example@example.com`; assert an email validation error, guest state, and no user creation. The duplicate case creates an existing user and asserts the current duplicate-email message remains.

Change the existing guardian registration success fixture to `newparent@yahoo.com`, rename the test to state that a non-Gmail guardian is accepted, and assert that exact normalized address exists.

Replace the admin request's Yahoo-rejection test with `test_credentials_tab_accepts_non_gmail_email()` and add `test_credentials_tab_rejects_malformed_email()`. Replace the controller's rejection test with `test_credentials_tab_accepts_non_gmail_admin_login_email()`, assert no validation errors, refresh the user, and assert the Yahoo address was saved. Retain the existing Gmail admin update test.

Add `AuthenticationTest::test_users_can_authenticate_with_a_non_gmail_email_address()` using a Yahoo user and the existing login endpoint.

- [ ] **Step 3: Run the focused tests and confirm the new positive cases fail for the Gmail restriction**

Run in the same PowerShell process with the explicit testing variables from Step 1:

```powershell
$env:APP_ENV='testing'
$env:APP_CONFIG_CACHE='bootstrap/cache/config-testing.php'
$env:DB_CONNECTION='mysql'
$env:DB_DATABASE='cc_db_test'
php vendor/phpunit/phpunit/phpunit tests/Feature/Auth/RegistrationTest.php tests/Feature/Auth/ParentRegistrationUploadPersistenceTest.php tests/Feature/Auth/AuthenticationTest.php tests/Feature/Admin/UpdateAdminCreatorProfileRequestTest.php tests/Feature/Admin/AdminProfileControllerTest.php
```

Expected before implementation: FAIL on one or more non-Gmail registration/admin assertions because `ends_with:@gmail.com` still applies. Existing Gmail, invalid-format, duplicate, and login controls should not introduce unrelated failures.

- [ ] **Step 4: Remove only the three provider gates**

In learner and guardian registration, replace `email:rfc,dns` with `email:rfc`, remove `ends_with:@gmail.com`, and remove the `email.ends_with` learner message. In the admin credentials request, retain `email:rfc` and remove only `ends_with:@gmail.com` plus `email.ends_with`.

Do not change `prepareForValidation()`, post-validation guardian lowercasing, uniqueness, passwords, controller redirects, notifications, or child alias/fallback code.

- [ ] **Step 5: Run the focused backend tests to green**

Run the Step 3 command again with the verified testing variables.

Expected: PASS for all five files with no Gmail/non-Gmail distinction and malformed/duplicate behavior intact.

- [ ] **Step 6: Inspect the scoped backend diff**

```powershell
git diff -- app/Http/Requests/AccountInfoRequest.php app/Http/Controllers/Auth/ParentRegistrationController.php app/Http/Requests/Admin/UpdateAdminCreatorProfileRequest.php tests/Feature/Auth/RegistrationTest.php tests/Feature/Auth/ParentRegistrationUploadPersistenceTest.php tests/Feature/Auth/AuthenticationTest.php tests/Feature/Admin/UpdateAdminCreatorProfileRequestTest.php tests/Feature/Admin/AdminProfileControllerTest.php
```

Expected: only provider-policy rules/messages and their focused tests changed; child alias generation and unrelated controller code are untouched.

---

### Task 2: Provider-Neutral Forms, Verification Guidance, and Policy Copy

**Files:**
- Create: `tests/Unit/ProviderNeutralEmailCopyTest.php`
- Modify: `tests/Feature/Auth/EmailVerificationTest.php`
- Modify: `tests/Feature/Auth/GuardianIdentityVerificationFlowTest.php`
- Modify: `resources/views/auth/register-account.blade.php:61-70`
- Modify: `resources/views/auth/parent-register-account.blade.php:66-70`
- Modify: `resources/views/auth/forgot-password.blade.php:48-50`
- Modify: `resources/views/auth/reset-password.blade.php:43-45`
- Modify: `resources/views/admin/profile/show.blade.php:214-215`
- Modify: `resources/views/auth/verify-email.blade.php:44,354-357`
- Modify: `resources/views/auth/parent-verification-status.blade.php:149-154`
- Modify: `resources/views/components/legal-modals.blade.php:81,360-366`
- Modify: `resources/views/legal/privacy.blade.php:79,235-236`
- Modify: `resources/views/legal/terms.blade.php:87`

**Interfaces:**
- Consumes: authenticated `User::email`, existing verification routes, and existing Blade layouts.
- Produces: provider-neutral active copy and a presentation-only `$usesGmail` boolean in each verification view; no domain policy or reusable service.

- [ ] **Step 1: Write failing copy and conditional-link tests**

Create a database-free `PHPUnit\Framework\TestCase` named `ProviderNeutralEmailCopyTest`. It reads the ten active form/policy Blade files with `dirname(__DIR__, 2)` and asserts they do not contain Gmail-only eligibility fragments or Gmail-only placeholders, including `Only Gmail`, `only accept Gmail`, `Must be a Gmail`, `valid Gmail address`, `registered Gmail address`, `your Gmail address`, `Gmail Requirement`, `yourname@gmail.com`, `guardian@gmail.com`, and `your@gmail.com`. Assert the four authentication form placeholders use `name@example.com`, the admin guidance uses a provider-neutral example, and the policy files describe a valid email address without naming an allowed provider.

In `EmailVerificationTest`, add:

```php
public function test_gmail_user_sees_gmail_inbox_shortcut(): void
public function test_non_gmail_user_does_not_see_gmail_inbox_shortcut(): void
```

Create unverified Gmail/Yahoo users, render `/verify-email`, and assert the Gmail label and `https://mail.google.com` are present only for Gmail.

In `GuardianIdentityVerificationFlowTest`, add equivalent pending-guardian tests against `guardian.verification.status`, using verified guardian accounts with `parent_verification_status = 'pending'`.

- [ ] **Step 2: Run the new tests and verify the current Gmail-specific UI fails**

Run the database-free copy test first:

```powershell
php vendor/phpunit/phpunit/phpunit tests/Unit/ProviderNeutralEmailCopyTest.php
```

Expected: FAIL on the current placeholders and Gmail-only statements.

After reusing the exact database preflight from Task 1, run:

```powershell
$env:APP_ENV='testing'
$env:APP_CONFIG_CACHE='bootstrap/cache/config-testing.php'
$env:DB_CONNECTION='mysql'
$env:DB_DATABASE='cc_db_test'
php vendor/phpunit/phpunit/phpunit tests/Feature/Auth/EmailVerificationTest.php tests/Feature/Auth/GuardianIdentityVerificationFlowTest.php
```

Expected before implementation: FAIL because non-Gmail users currently see hard-coded Gmail links.

- [ ] **Step 3: Update form and admin guidance without changing structure**

Use `name@example.com` for learner, guardian, forgot-password, and reset-password placeholders. Replace the guardian helper with a provider-neutral verification-link sentence. Replace the admin helper with provider-neutral valid-email/login wording. Do not change input types, names, autocomplete, layout, Alpine behavior, actions, or validation rendering.

- [ ] **Step 4: Make both Gmail inbox shortcuts conditional**

In `verify-email.blade.php`, derive `$usesGmail` from the authenticated user's lowercased email with an exact `@gmail.com` suffix. Render the existing Gmail anchor only when true; when false, retain provider-neutral inbox instructions and let the resend form occupy the available grid width.

In `parent-verification-status.blade.php`, derive the same boolean from `$user->email` and render `Open Gmail Inbox` only for Gmail users. Do not construct inbox URLs from arbitrary domains and do not introduce a provider map.

- [ ] **Step 5: Update FAQ, privacy, and terms copy**

Replace Gmail eligibility claims with provider-neutral statements about providing a valid address, receiving a verification/reset email, and checking inbox/spam folders. Remove the obsolete promise that additional providers will be supported later.

Leave `resources/views/legal/privacy.blade.php`'s factual Gmail SMTP service-provider reference untouched. Leave contact addresses, `docs/GMAIL_SMTP_SETUP.md`, other operational documentation, and historical plans untouched.

- [ ] **Step 6: Run the UI and copy tests to green**

Run the Step 2 unit test and the two feature test files again, with feature tests gated by the verified `cc_db_test` preflight.

Expected: PASS; Gmail users retain the shortcut, non-Gmail users do not see it, and active copy no longer claims Gmail is required.

- [ ] **Step 7: Inspect the scoped UI diff**

```powershell
git diff -- tests/Unit/ProviderNeutralEmailCopyTest.php tests/Feature/Auth/EmailVerificationTest.php tests/Feature/Auth/GuardianIdentityVerificationFlowTest.php resources/views/auth/register-account.blade.php resources/views/auth/parent-register-account.blade.php resources/views/auth/forgot-password.blade.php resources/views/auth/reset-password.blade.php resources/views/admin/profile/show.blade.php resources/views/auth/verify-email.blade.php resources/views/auth/parent-verification-status.blade.php resources/views/components/legal-modals.blade.php resources/views/legal/privacy.blade.php resources/views/legal/terms.blade.php
```

Expected: copy and conditional rendering only; no layout redesign, route change, or unrelated policy edit.

---

### Task 3: Restriction Sweep and Final Verification

**Files:**
- Verify only: all files owned by Tasks 1 and 2
- Verify only: `app`, `routes`, `config`, `database`, `resources`, and focused tests

**Interfaces:**
- Consumes: provider-neutral validators and copy from Tasks 1-2.
- Produces: verification evidence and a scoped handoff; no additional behavior unless the audit finds a missed active Gmail gate.

- [ ] **Step 1: Run PHP syntax checks on changed PHP test and request/controller files**

Run:

```powershell
php -l app/Http/Requests/AccountInfoRequest.php
php -l app/Http/Controllers/Auth/ParentRegistrationController.php
php -l app/Http/Requests/Admin/UpdateAdminCreatorProfileRequest.php
php -l tests/Feature/Auth/RegistrationTest.php
php -l tests/Feature/Auth/ParentRegistrationUploadPersistenceTest.php
php -l tests/Feature/Auth/AuthenticationTest.php
php -l tests/Feature/Auth/EmailVerificationTest.php
php -l tests/Feature/Auth/GuardianIdentityVerificationFlowTest.php
php -l tests/Feature/Admin/UpdateAdminCreatorProfileRequestTest.php
php -l tests/Feature/Admin/AdminProfileControllerTest.php
php -l tests/Unit/ProviderNeutralEmailCopyTest.php
```

Expected: `No syntax errors detected` for each file.

- [ ] **Step 2: Run the repository-wide Gmail restriction sweep**

```powershell
rg -n -i --glob '!vendor/**' --glob '!node_modules/**' --glob '!public/build/**' --glob '!storage/**' --glob '!.git/**' "gmail\.com|gmail|ends_with:@|email:rfc,dns" app routes config database resources tests
```

Classify every remaining hit. Expected allowed hits are the conditional Gmail inbox links, Gmail child-alias behavior, Gmail SMTP/provider facts, contact addresses, historical fixtures/examples, and the copy regression's forbidden-string list. There must be no active Gmail-only eligibility rule, message, placeholder, legal claim, or unconditional Gmail inbox link.

- [ ] **Step 3: Run the complete focused verification set**

After repeating the exact `cc_db_test` preflight, run:

```powershell
$env:APP_ENV='testing'
$env:APP_CONFIG_CACHE='bootstrap/cache/config-testing.php'
$env:DB_CONNECTION='mysql'
$env:DB_DATABASE='cc_db_test'
php vendor/phpunit/phpunit/phpunit tests/Unit/ProviderNeutralEmailCopyTest.php tests/Feature/Auth/RegistrationTest.php tests/Feature/Auth/ParentRegistrationUploadPersistenceTest.php tests/Feature/Auth/AuthenticationTest.php tests/Feature/Auth/EmailVerificationTest.php tests/Feature/Auth/GuardianIdentityVerificationFlowTest.php tests/Feature/Admin/UpdateAdminCreatorProfileRequestTest.php tests/Feature/Admin/AdminProfileControllerTest.php tests/Feature/Admin/AdminProfileViewTest.php
```

Expected: all focused tests pass. If the database preflight fails, run only the database-free unit test and report database-backed verification as blocked rather than touching development data.

- [ ] **Step 4: Check whitespace and final scope**

```powershell
git diff --check -- app/Http/Requests/AccountInfoRequest.php app/Http/Controllers/Auth/ParentRegistrationController.php app/Http/Requests/Admin/UpdateAdminCreatorProfileRequest.php tests/Feature/Auth/RegistrationTest.php tests/Feature/Auth/ParentRegistrationUploadPersistenceTest.php tests/Feature/Auth/AuthenticationTest.php tests/Feature/Auth/EmailVerificationTest.php tests/Feature/Auth/GuardianIdentityVerificationFlowTest.php tests/Feature/Admin/UpdateAdminCreatorProfileRequestTest.php tests/Feature/Admin/AdminProfileControllerTest.php tests/Unit/ProviderNeutralEmailCopyTest.php resources/views/auth/register-account.blade.php resources/views/auth/parent-register-account.blade.php resources/views/auth/forgot-password.blade.php resources/views/auth/reset-password.blade.php resources/views/admin/profile/show.blade.php resources/views/auth/verify-email.blade.php resources/views/auth/parent-verification-status.blade.php resources/views/components/legal-modals.blade.php resources/views/legal/privacy.blade.php resources/views/legal/terms.blade.php docs/superpowers/specs/2026-10-25-provider-neutral-email-validation-design.md docs/superpowers/plans/2026-10-25-provider-neutral-email-validation.md
git status --short
```

Then review `git diff --` with the exact owned paths from Tasks 1 and 2. Confirm no existing unrelated changes were altered and no `public/build`, dependency, config, migration, or database file was added to this task.

- [ ] **Step 5: Report evidence without publishing**

Report the changed files, exact test commands/results, allowed remaining Gmail references, database preflight result, and any blocked verification. Do not commit, push, open a pull request, stash, or switch branches.
