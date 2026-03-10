# Auth & Registration UX Overhaul — Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Redesign all auth/registration/parent-child pages for consistency, better UX, correct flow, and branded email.

**Architecture:** All pages use `x-auth-split-layout` (right panel = Logo.png hero + "Concious Connections" brand text + tagline). Registration flows use the existing `x-wizard-stepper` component. Parent registration is split into 2 steps. Child creation is split into 4 steps with a final monitoring info page.

**Tech Stack:** Laravel 12, Blade, Alpine.js, Tailwind CSS v3, Spatie Permissions, existing `x-auth-split-layout` + `x-wizard-stepper` components.

---

## Shared Panel Snippet (use on EVERY auth page right panel)

Replace every existing `<x-slot name="panel">` with this pattern:

```blade
<x-slot name="panel">
    <div class="relative h-full flex flex-col items-center justify-center p-12 text-center">
        {{-- Decorative top-left accent --}}
        <div class="absolute top-0 left-0 w-32 h-32 bg-white/5 rounded-br-full"></div>
        <div class="absolute bottom-0 right-0 w-48 h-48 bg-white/5 rounded-tl-full"></div>

        {{-- Logo --}}
        <div class="relative mb-6">
            <div class="w-28 h-28 bg-white/15 rounded-3xl flex items-center justify-center shadow-2xl backdrop-blur-sm border border-white/20">
                <img src="{{ asset('/media/Logo.png') }}" alt="Concious Connections" class="w-20 h-20 object-contain drop-shadow-lg">
            </div>
        </div>

        {{-- Brand name --}}
        <h1 class="text-2xl font-bold text-white mb-1 tracking-wide">Concious Connections</h1>
        <div class="w-12 h-0.5 bg-white/40 rounded-full mx-auto mb-6"></div>

        {{-- Page-specific headline --}}
        <h2 class="text-3xl font-bold text-white mb-3 leading-tight"><!-- HEADLINE --></h2>
        {{-- Page-specific sub-text --}}
        <p class="text-white/75 text-base max-w-[220px] leading-relaxed"><!-- SUBTEXT --></p>
    </div>
</x-slot>
```

Per-page headlines/subtexts:
| Page | Headline | Subtext |
|------|----------|---------|
| learner-login | Welcome back | Sign in to continue learning |
| register (personal info) | Start your journey | A safe space to grow and learn |
| register-account (credentials) | Almost there! | Just your login details left |
| verify-email | Check your inbox | We sent a link to your Gmail |
| profile/complete | One last step! | Personalize your experience |
| parent-registration-required | Young learner? | A parent account is needed first |
| parent-register step 1 | Guide their journey | Register as a parent or guardian |
| parent-register step 2 | Your credentials | Secure your parent account |
| child creation step 1 | Set up their account | Let's register your child |
| child creation step 2 | Where are you? | Location helps personalize content |
| child creation step 3 | Login details | Create their login credentials |
| monitoring info page | You're all set! | Here's how parental monitoring works |

---

## Task 1: Update shared panel design on all existing auth pages

**Files to Modify:**
- `resources/views/auth/learner-login.blade.php`
- `resources/views/auth/register.blade.php`
- `resources/views/auth/register-account.blade.php`
- `resources/views/auth/verify-email.blade.php`
- `resources/views/auth/parent-registration-required.blade.php`
- `resources/views/auth/parent-register.blade.php`
- `resources/views/profile/complete.blade.php`

**Also:** Remove `showTabs` / Login+Register tab buttons from `register.blade.php` and `register-account.blade.php` (`:showTabs="false"`).

**Step 1:** On each file, replace the entire `<x-slot name="panel">...</x-slot>` block with the shared panel pattern above (using the page-specific headline/subtext from the table).

**Step 2:** On `register.blade.php` and `register-account.blade.php`, change `:showTabs="true"` to `:showTabs="false"` and remove `activeTab`, `loginRoute`, `registerRoute` props.

**Step 3:** Run existing `PageRenderTest` to confirm still passing:
```
php artisan test tests/Feature/Auth/PageRenderTest.php --no-coverage
```
Expected: 9 passed.

**Step 4:** Commit.
```
git add resources/views/auth/ resources/views/profile/complete.blade.php
git commit -m "style: unified Logo+brand-name panel on all auth pages, removed login/register tabs"
```

---

## Task 2: Compact register.blade.php form (2-column layout)

**File:** `resources/views/auth/register.blade.php`

**Step 1:** Write failing test (add to `PageRenderTest.php`):
```php
public function test_register_page_has_compact_two_column_layout(): void
{
    $response = $this->get('/register');
    $response->assertStatus(200);
    $response->assertSee('grid-cols-2', false); // 2-col grid class present
}
```

**Step 2:** Run to confirm FAIL.
```
php artisan test --filter=test_register_page_has_compact_two_column_layout
```

**Step 3:** Rearrange the form so:
- First Name + Last Name → side-by-side `<div class="grid grid-cols-2 gap-3">`
- Middle Initial + Suffix → side-by-side `<div class="grid grid-cols-2 gap-3">`
- Birth Date row stays full-width (it has the age display below it)
- Remove extra padding/spacing (use `space-y-3` not `space-y-4`)
- Input `py-2.5` → `py-2` to reduce height

**Step 4:** Run tests.
```
php artisan test tests/Feature/Auth/PageRenderTest.php --no-coverage
```

**Step 5:** Commit.
```
git commit -am "style: compact 2-col form layout on register page"
```

---

## Task 3: Fix email verification flow

**Current:** Email link → `VerifyEmailController` → redirect to `profile.complete` directly.
**Target:** Email link → `VerifyEmailController` → redirect to `/verify-email?verified=1` → verify-email page shows success state with 3s countdown → auto-redirects to `profile.complete`.

**Files:**
- `app/Http/Controllers/Auth/VerifyEmailController.php`
- `app/Http/Controllers/Auth/EmailVerificationPromptController.php`
- `resources/views/auth/verify-email.blade.php`

**Step 1:** Update `VerifyEmailController::__invoke()`:
```php
// After: $request->user()->markEmailAsVerified() + event(new Verified($request->user()))
// Instead of redirecting to profile.complete, redirect to verify-email with ?verified=1
return redirect()->route('verification.notice', ['verified' => 1]);
```

**Step 2:** Update `EmailVerificationPromptController::__invoke()`:
```php
// If already verified AND ?verified=1 in query → show the page (success state)
// If already verified AND no ?verified=1 → redirect to profile.complete
public function __invoke(Request $request): RedirectResponse|View
{
    if ($request->user()->hasVerifiedEmail()) {
        if ($request->query('verified') === '1') {
            return view('auth.verify-email'); // show success state
        }
        if (!$request->user()->hasCompletedProfile()) {
            return redirect()->route('profile.complete');
        }
        return redirect()->route('learner.dashboard');
    }
    return view('auth.verify-email');
}
```

**Step 3:** Update `verify-email.blade.php` to show two states:
- **Normal state** (unverified): existing resend form + logout button
- **Success state** (`request()->query('verified') == '1'` OR `auth()->user()->hasVerifiedEmail()` AND landed here via `?verified=1`): 
  - Green checkmark animation
  - "Email verified successfully!"
  - Progress bar countdown (Alpine.js, 3 seconds)
  - Auto-redirect to `profile.complete` after countdown
  - "Continue now →" button for impatient users

Alpine.js countdown pattern:
```js
x-data="{
    progress: 0,
    init() {
        const interval = setInterval(() => {
            this.progress += (100/30); // 30 ticks over 3 seconds
            if (this.progress >= 100) {
                clearInterval(interval);
                window.location.href = '{{ route('profile.complete') }}';
            }
        }, 100);
    }
}"
```

**Step 4:** Write test:
```php
public function test_verified_email_shows_success_state_with_countdown(): void
{
    $user = User::factory()->create(['email_verified_at' => now()]);
    $response = $this->actingAs($user)->get('/verify-email?verified=1');
    $response->assertStatus(200);
    $response->assertSee('Email verified');
}
```

**Step 5:** Run tests.

**Step 6:** Commit.
```
git commit -am "feat: verify-email redirect to ?verified=1 success state with 3s countdown"
```

---

## Task 4: Custom branded email template

**Current:** `CustomVerifyEmail` uses `MailMessage` chained calls → plain Laravel styled email.
**Target:** Full HTML Blade template with brand gradient header, Logo.png, "Concious Connections", purple CTA button.

**Files:**
- `app/Notifications/CustomVerifyEmail.php` — switch to `toMail()` returning a `MailMessage` with `view()`
- Create: `resources/views/emails/verify-email.blade.php`

**Step 1:** Create email Blade template at `resources/views/emails/verify-email.blade.php`:

```html
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Verify Your Email — Concious Connections</title>
    <style>
        body { margin: 0; padding: 0; background-color: #f3f4f6; font-family: 'Segoe UI', Arial, sans-serif; }
        .wrapper { max-width: 600px; margin: 32px auto; background: #fff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 24px rgba(0,0,0,0.08); }
        .header { background: linear-gradient(135deg, #A30EB2 0%, #730DB1 50%, #3B0CB1 100%); padding: 40px 32px; text-align: center; }
        .header img { width: 72px; height: 72px; object-fit: contain; margin-bottom: 12px; }
        .header h1 { color: #fff; font-size: 22px; font-weight: 700; margin: 0 0 4px; letter-spacing: 0.5px; }
        .header p { color: rgba(255,255,255,0.75); font-size: 13px; margin: 0; }
        .body { padding: 40px 40px 32px; }
        .body h2 { font-size: 20px; color: #1f1235; margin: 0 0 12px; font-weight: 700; }
        .body p { font-size: 14px; color: #4b5563; line-height: 1.7; margin: 0 0 20px; }
        .btn-wrap { text-align: center; margin: 28px 0; }
        .btn { display: inline-block; background: linear-gradient(135deg, #A30EB2, #3B0CB1); color: #fff !important; text-decoration: none; padding: 14px 36px; border-radius: 12px; font-size: 15px; font-weight: 700; letter-spacing: 0.3px; box-shadow: 0 4px 16px rgba(163,14,178,0.35); }
        .note { font-size: 12px; color: #9ca3af; line-height: 1.6; }
        .url-box { background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px; padding: 10px 14px; word-break: break-all; font-size: 11px; color: #6b7280; margin: 12px 0 24px; }
        .footer { padding: 20px 32px 28px; text-align: center; border-top: 1px solid #f3f4f6; }
        .footer p { font-size: 11px; color: #9ca3af; margin: 0; line-height: 1.7; }
        .footer a { color: #730DB1; text-decoration: none; }
    </style>
</head>
<body>
<div class="wrapper">
    <div class="header">
        <img src="{{ asset('/media/Logo.png') }}" alt="Concious Connections Logo">
        <h1>Concious Connections</h1>
        <p>Safe, age-appropriate sexual health education</p>
    </div>
    <div class="body">
        <h2>Verify your email address</h2>
        <p>Hi {{ $user->first_name ?? $user->name }},</p>
        <p>Thank you for joining <strong>Concious Connections</strong>! To get started, please verify your email address by clicking the button below.</p>
        <div class="btn-wrap">
            <a href="{{ $verificationUrl }}" class="btn">Verify Email Address</a>
        </div>
        <p class="note">This link expires in <strong>60 minutes</strong>. If you did not create an account, you can safely ignore this email.</p>
        <p class="note">If the button doesn't work, copy and paste this link into your browser:</p>
        <div class="url-box">{{ $verificationUrl }}</div>
    </div>
    <div class="footer">
        <p>&copy; {{ date('Y') }} Concious Connections · <a href="{{ route('privacy') }}">Privacy Policy</a> · <a href="{{ route('terms') }}">Terms of Service</a></p>
        <p style="margin-top:6px;">This email was sent to {{ $user->email }}</p>
    </div>
</div>
</body>
</html>
```

**Step 2:** Update `CustomVerifyEmail.php` to use this view:
```php
public function toMail($notifiable)
{
    $verificationUrl = $this->verificationUrl($notifiable);
    return (new MailMessage)
        ->subject('Verify Your Email Address — Concious Connections')
        ->view('emails.verify-email', [
            'user' => $notifiable,
            'verificationUrl' => $verificationUrl,
        ]);
}
```

**Step 3:** No automated test needed for email HTML (visual-only change). Manually test by registering a new account in browser.

**Step 4:** Commit.
```
git commit -am "style: branded HTML email template for verification emails"
```

---

## Task 5: Profile completion page — compact layout + dashboard redirect

**Files:**
- `resources/views/profile/complete.blade.php`
- `app/Http/Controllers/ProfileCompletionController.php`

**Step 1:** In `ProfileCompletionController::store()`, find the final redirect and change:
```php
// OLD:
return redirect()->route('learner.modules.index')
// NEW:
return redirect()->route('learner.dashboard')
```

**Step 2:** In `complete.blade.php`, restructure the form:
- Username field: full-width (keep)
- Gender: full-width dropdown (keep)
- City + Barangay: **side-by-side** `<div class="grid grid-cols-2 gap-3">`
- Input class: use consistent `px-4 py-2 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-brand-purple-primary` (same as login page)
- Remove heavy padding, use `space-y-3`
- Remove the `<x-wizard-stepper />` line (profile complete is step 4 of learner wizard — the stepper was for the multi-step form pages; profile is standalone after email verification)

Wait — keep the stepper for 13+ learner flow it shows: Personal Info ✓ → Account Info ✓ → Verify Email ✓ → **Complete Profile** (active). This helps orientation. Keep `<x-wizard-stepper />`.

**Step 3:** Write test:
```php
public function test_profile_completion_redirects_to_learner_dashboard(): void
{
    $user = User::factory()->create(['email_verified_at' => now()]);
    $user->assignRole('learner');
    $user->refresh();
    $this->actingAs($user)->post(route('profile.store'), [
        'username' => 'testuser123',
        'gender' => 'male',
        'city_code' => '042102000', // Bacoor
        'barangay_code' => '042102001',
        'age_bracket' => 'teens',
    ])->assertRedirect(route('learner.dashboard'));
}
```

**Step 4:** Run tests.

**Step 5:** Commit.
```
git commit -am "fix: profile completion redirects to learner.dashboard; compact form layout"
```

---

## Task 6: Parent-registration-required page — remove stepper, resize consistent

**File:** `resources/views/auth/parent-registration-required.blade.php`

**Step 1:** Remove `<x-wizard-stepper />` line (the stepper on parent registration doesn't start here — it starts on the actual parent-register page).

**Step 2:** The page already uses `x-auth-split-layout :showTabs="false"` so sizing is consistent. No further layout changes needed.

**Step 3:** Update panel slot to use the new Logo-based design (covered by Task 1).

No new test needed. Task 1's test run covers this.

**Step 4:** Commit included in Task 1 commit.

---

## Task 7: Parent registration — split into 2 steps

**Current:** A single long form at `GET /parent/register` handles all fields (personal + credentials).  
**Target:** Step 1 = personal info, Step 2 = credentials. Same session-handoff pattern as learner registration.

### New Routes (add to `routes/auth.php` under the existing guest middleware):
```php
Route::get('parent/register/account', [ParentRegistrationController::class, 'createAccount'])
    ->name('parent.register.account');
Route::post('parent/register/account', [ParentRegistrationController::class, 'storeAccount'])
    ->name('parent.register.account.store');
```
Keep existing `parent.register` (GET + POST) but rename POST handler to `storePersonal()`.

### Controller changes (`ParentRegistrationController.php`):

**Rename `store()` → `storePersonal()`** — validates first_name, middle_initial, last_name, suffix, birthdate. Stores to session as `pending_parent_info`. Redirects to `parent.register.account`.

**Add `createAccount(): View`** — checks session has `pending_parent_info`, else redirects to `parent.register`. Returns view `auth.parent-register-account`.

**Add `storeAccount(): RedirectResponse`** — validates email + password (same rules as current `store()`). Merges with session data. Creates the user. Fires `Registered` event. Logs in. Clears session. Redirects to `verification.notice`.

### New view: `resources/views/auth/parent-register-account.blade.php`
- Same layout as `register-account.blade.php`
- Panel: "Your credentials" / "Secure your parent account"
- Wizard stepper: Personal Info (done) → **Account Info** (active) → Verify Email → Profile
- Form fields: Email, Password (with show/hide toggle), Confirm Password (with show/hide toggle)
- Back link → `parent.register`

### Update `parent-register.blade.php`:
- Panel: "Guide their journey" (same as current — keep)
- Wizard stepper: **Personal Info** (active) → Account Info → Verify Email → Profile
- Form fields: only personal info (first name, last name, middle initial, suffix, birthdate)
- Submit goes to `parent.register` (POST → `storePersonal`)
- Password fields removed from this page

**Step 1:** Add routes.
**Step 2:** Write tests:
```php
public function test_parent_register_account_page_redirects_without_session(): void
{
    $response = $this->get(route('parent.register.account'));
    $response->assertRedirect(route('parent.register'));
}

public function test_parent_register_stores_personal_info_to_session(): void
{
    $response = $this->post(route('parent.register.store'), [
        'first_name' => 'Maria', 'last_name' => 'Santos',
        'birthdate' => '1985-05-15',
    ]);
    $response->assertSessionHas('pending_parent_info');
    $response->assertRedirect(route('parent.register.account'));
}
```
**Step 3:** Implement controller methods.
**Step 4:** Create `parent-register-account.blade.php`.
**Step 5:** Update `parent-register.blade.php` to personal-info-only.
**Step 6:** Run tests.
**Step 7:** Commit.
```
git commit -am "feat: split parent registration into personal info + credentials steps"
```

---

## Task 8: Child account creation — 4-step wizard

**Current:** Single long form at `GET /parent/create-child` with ALL fields.
**Target:** 4 separate steps (4 routes/views), session-carried data.

### New Routes (add inside `auth` + `verified` middleware group):
```php
// Step 1 is existing parent.create-child
// Step 2:
Route::get('parent/create-child/location', [ParentRegistrationController::class, 'childLocationForm'])
    ->name('parent.create-child.location');
Route::post('parent/create-child/location', [ParentRegistrationController::class, 'storeChildLocation'])
    ->name('parent.create-child.location.store');
// Step 3:
Route::get('parent/create-child/credentials', [ParentRegistrationController::class, 'childCredentialsForm'])
    ->name('parent.create-child.credentials');
Route::post('parent/create-child/credentials', [ParentRegistrationController::class, 'storeChildCredentials'])
    ->name('parent.create-child.credentials.store');
// Step 4 (monitoring info - GET only, no POST):
Route::get('parent/create-child/done', [ParentRegistrationController::class, 'childDone'])
    ->name('parent.create-child.done');
```

### Session keys:
- `child_step1`: first_name, last_name, middle_initial, suffix, birthdate, gender
- `child_step2`: city_code, barangay_code
- `child_step3`: username, password (hashed), email — then immediately create the child user when step 3 is stored, redirect to done.

### Controller methods to add:
1. **`createChildForm()`** (existing, update): Only first_name, last_name, middle_initial, suffix, birthdate, gender. On POST → `storeChildInfo()` → save to `child_step1` session → redirect to location.
2. **`storeChildInfo(Request $r)`**: Validates personal fields. Stores to session. Redirects to `parent.create-child.location`.
3. **`childLocationForm(): View`**: Checks `child_step1` session exists. Shows location form.
4. **`storeChildLocation(Request $r)`**: Validates city_code, barangay_code. Stores to `child_step2` session. Redirects to `parent.create-child.credentials`.
5. **`childCredentialsForm(): View`**: Checks `child_step1` + `child_step2` sessions. Shows credentials form with Gmail+ suggestion.
6. **`storeChildCredentials(Request $r)`**: Validates username, password. Merges all session data. Creates child user + learner profile + ParentChildAccount (all existing logic from `storeChild()`). Clears sessions. Redirects to `parent.create-child.done`.
7. **`childDone(): View`**: Shows monitoring info page. No session data needed.

### Views to create:
- `resources/views/auth/child/step1-info.blade.php` (repurpose existing `create-child-account.blade.php` to be step 1 only)
- `resources/views/auth/child/step2-location.blade.php`
- `resources/views/auth/child/step3-credentials.blade.php` (password show/hide toggle)
- `resources/views/auth/child/done.blade.php` — monitoring features info + "Go to My Children" button

### Wizard stepper labels for child flow:
Step 1: Child Info | Step 2: Location | Step 3: Credentials | Step 4: All Done!

**Step 1:** Add routes to `auth.php`.
**Step 2:** Write tests:
```php
public function test_child_location_form_redirects_without_step1_session(): void
{
    $parent = User::factory()->create(['birthdate' => now()->subYears(25), 'email_verified_at' => now()]);
    $response = $this->actingAs($parent)->get(route('parent.create-child.location'));
    $response->assertRedirect(route('parent.create-child'));
}

public function test_child_done_page_accessible_after_creation(): void
{
    $parent = User::factory()->create(['birthdate' => now()->subYears(25), 'email_verified_at' => now()]);
    $response = $this->actingAs($parent)->get(route('parent.create-child.done'));
    $response->assertStatus(200);
    $response->assertSee("You're all set");
}
```
**Step 3:** Implement controller methods.
**Step 4:** Create all 4 views.
**Step 5:** Update existing `create-child-account` route to point to step 1 view.
**Step 6:** Run tests.
**Step 7:** Commit.
```
git commit -am "feat: split child account creation into 4-step wizard"
```

---

## Task 9: My Children page redesign

**File:** `resources/views/parent/children/index.blade.php` (create if doesn't exist — check first)

**Design:** Use `layouts.learner-app` layout (same as learner dashboard). 
- Page header: gradient banner (same brand gradient) with "My Children" title + "Add Child" button
- Each child: white card with:
  - Left: circular avatar (initial letter, purple gradient background)
  - Center: Full name, age, age bracket badge, last-active time
  - Right: "View Progress" button (→ `parent.children.show`) + quick stats (modules enrolled count)
- Empty state: friendly illustration-style box with "No children yet" + "Add your first child" CTA

**Step 1:** Check if view exists: `resources/views/parent/children/index.blade.php`
**Step 2:** Write/update the view.
**Step 3:** No new test needed.
**Step 4:** Commit.
```
git commit -am "style: redesign My Children page with dashboard-aligned card layout"
```

---

## Task 10: Final test run + commit

**Step 1:** Run full auth test suite:
```
php artisan test tests/Feature/Auth/ --no-coverage
```

**Step 2:** Run full test suite:
```
php artisan test --no-coverage
```

**Step 3:** Fix any failures.

**Step 4:** Final commit if any fixes were needed.

---

## Wizard Stepper Reference

The existing `x-wizard-stepper` component accepts a `$steps` array prop. Each page must pass its own steps config. Pattern from existing views:

```php
// In the Blade view, pass steps as a prop:
<x-wizard-stepper :steps="[
    ['label' => 'Personal Info', 'isCompleted' => true,  'isActive' => false, 'isUpcoming' => false],
    ['label' => 'Account Info',  'isCompleted' => false, 'isActive' => true,  'isUpcoming' => false],
    ['label' => 'Verify Email',  'isCompleted' => false, 'isActive' => false, 'isUpcoming' => true],
    ['label' => 'Profile',       'isCompleted' => false, 'isActive' => false, 'isUpcoming' => true],
]" />
```

**Learner 13+ wizard steps:** Personal Info → Account Info → Verify Email → Complete Profile  
**Parent wizard steps:** Personal Info → Account Info → Verify Email → Profile  
**Child wizard steps:** Child Info → Location → Credentials → All Done!
