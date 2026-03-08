# Auth & Registration Visual Redesign — Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Unify the visual language of all auth and registration pages by applying the platform brand gradient (`#A30EB2 → #730DB1 → #3B0CB1`) and a consistent `x-auth-split-layout` shell across all 8 pages in both registration paths.

**Architecture:** Extend `auth-split-layout.blade.php` with a `gradientMid` prop and a `$panel` named slot. Each page provides its own right-panel content via `$panel`. All form logic, Alpine.js data, field names, and POST routes are untouched — this is 100% a visual/layout change.

**Design doc:** `docs/plans/2026-03-08-auth-registration-visual-redesign.md`

**Tech Stack:** Laravel 12, Blade, Tailwind CSS v3, Alpine.js, inline SVG Heroicons (outline, stroke-width="1.5")

**Brand gradient:** `#A30EB2 → #730DB1 → #3B0CB1` (135deg)

**Right panel blueprint (reused by all pages):**
```blade
<x-slot name="panel">
    {{-- Blobs --}}
    <div class="absolute inset-0 overflow-hidden opacity-10 pointer-events-none">
        <div class="absolute top-0 right-0 w-64 h-64 bg-white rounded-full blur-3xl transform translate-x-1/2 -translate-y-1/2"></div>
        <div class="absolute bottom-0 left-0 w-96 h-96 bg-white rounded-full blur-3xl transform -translate-x-1/2 translate-y-1/2"></div>
    </div>
    {{-- Content --}}
    <div class="relative h-full flex flex-col items-center justify-center p-12 text-center">
        {{-- Small logo top-left --}}
        <div class="absolute top-8 left-8">
            <img src="{{ asset('media/Logo.png') }}" alt="ConciousConnections" class="w-10 h-10 object-contain opacity-80">
        </div>
        {{-- Icon bubble --}}
        <div class="w-24 h-24 rounded-full bg-white/10 flex items-center justify-center mb-6">
            <svg class="w-12 h-12 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                {{-- page-specific <path> --}}
            </svg>
        </div>
        <h2 class="text-3xl font-bold text-white mb-3">Headline</h2>
        <p class="text-white/70 text-sm max-w-[200px] leading-relaxed">Sub-text</p>
    </div>
</x-slot>
```

---

## Task 1: Extend `auth-split-layout` Component

**Files:**
- Modify: `resources/views/components/auth-split-layout.blade.php`
- Test: `tests/Feature/Auth/RegistrationTest.php` (extend existing)

### Step 1: Write a failing test that asserts the brand gradient color appears on the register page

Add this test to `tests/Feature/Auth/RegistrationTest.php`:

```php
public function test_register_page_uses_brand_purple_gradient(): void
{
    $response = $this->get('/register');
    $response->assertStatus(200);
    $response->assertSee('#A30EB2', false);
}
```

### Step 2: Run the test to verify it fails

```
php artisan test --filter=test_register_page_uses_brand_purple_gradient
```
Expected: FAIL — the page does not contain `#A30EB2` yet.

### Step 3: Update the component

In `resources/views/components/auth-split-layout.blade.php`, make these changes:

**a) Update the `@props` declaration** (top of file):

Old:
```blade
@props([
    'showTabs' => true,
    'activeTab' => 'login', // 'login' or 'register'
    'loginRoute' => null,
    'registerRoute' => null,
    'gradientFrom' => '#6D2994',
    'gradientTo' => '#3C1255',
    'logo' => '/media/Logo.png',
    'brandText' => 'Taboo',
])
```

New:
```blade
@props([
    'showTabs' => true,
    'activeTab' => 'login', // 'login' or 'register'
    'loginRoute' => null,
    'registerRoute' => null,
    'gradientFrom' => '#A30EB2',
    'gradientMid'  => '#730DB1',
    'gradientTo'   => '#3B0CB1',
    'logo' => '/media/Logo.png',
    'brandText' => 'Taboo',
])
```

**b) Update the right panel `<div>` inline style** — find the `<!-- RIGHT SIDE: Branding Area -->` div:

Old:
```
style="background: linear-gradient(135deg, {{ $gradientFrom }} 0%, {{ $gradientTo }} 100%);"
```

New:
```
style="background: linear-gradient(135deg, {{ $gradientFrom }} 0%, {{ $gradientMid }} 50%, {{ $gradientTo }} 100%);"
```

**c) Add the `$panel` named slot** — replace the inner content section of the right panel. Find everything from `<!-- Decorative Elements -->` down to and including the closing `</div>` of the logo section, and replace with:

Old (this whole block inside the gradient div):
```html
                    <!-- Decorative Elements -->
                    <div class="absolute inset-0 opacity-10">
                        <div class="absolute top-0 right-0 w-64 h-64 bg-white rounded-full blur-3xl transform translate-x-1/2 -translate-y-1/2"></div>
                        <div class="absolute bottom-0 left-0 w-96 h-96 bg-white rounded-full blur-3xl transform -translate-x-1/2 translate-y-1/2"></div>
                    </div>

                    <!-- Logo -->
                    <div class="relative h-full flex flex-col items-center justify-center p-12">
                        <div class="relative animate-fade-in">
                            <img src="{{ asset($logo) }}"
                                 alt="Logo"
                                 class="w-80 h-80 sm:w-96 sm:h-96 lg:w-[28rem] lg:h-[28rem] object-contain drop-shadow-2xl hover:scale-105 transition-transform duration-500"
                                 onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                            <!-- Fallback if logo doesn't exist -->
                            <div class="hidden text-center">
                                <div class="text-9xl mb-4">🌈</div>
                                <p class="text-white/50 text-sm">Logo placeholder</p>
                            </div>
                        </div>

                        @if($brandText)
                        <h1 class="mt-8 text-5xl sm:text-6xl font-bold text-white tracking-wider font-['Brush_Script_MT',cursive] drop-shadow-lg animate-fade-in"
                            style="animation-delay: 0.2s; font-family: 'Brush Script MT', cursive;">
                            {{ $brandText }}
                        </h1>
                        @endif
                    </div>
```

New:
```blade
                    @if($panel->isNotEmpty())
                        {{ $panel }}
                    @else
                        <!-- Decorative Elements -->
                        <div class="absolute inset-0 opacity-10">
                            <div class="absolute top-0 right-0 w-64 h-64 bg-white rounded-full blur-3xl transform translate-x-1/2 -translate-y-1/2"></div>
                            <div class="absolute bottom-0 left-0 w-96 h-96 bg-white rounded-full blur-3xl transform -translate-x-1/2 translate-y-1/2"></div>
                        </div>

                        <!-- Logo -->
                        <div class="relative h-full flex flex-col items-center justify-center p-12">
                            <div class="relative animate-fade-in">
                                <img src="{{ asset($logo) }}"
                                     alt="Logo"
                                     class="w-80 h-80 sm:w-96 sm:h-96 lg:w-[28rem] lg:h-[28rem] object-contain drop-shadow-2xl hover:scale-105 transition-transform duration-500"
                                     onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                                <div class="hidden text-center">
                                    <p class="text-white/50 text-sm">Logo placeholder</p>
                                </div>
                            </div>

                            @if($brandText)
                            <h1 class="mt-8 text-5xl sm:text-6xl font-bold text-white tracking-wider drop-shadow-lg animate-fade-in"
                                style="animation-delay: 0.2s; font-family: 'Brush Script MT', cursive;">
                                {{ $brandText }}
                            </h1>
                            @endif
                        </div>
                    @endif
```

### Step 4: Run the test to verify it passes

```
php artisan test --filter=test_register_page_uses_brand_purple_gradient
```
Expected: PASS

### Step 5: Run full test suite to check no regressions

```
php artisan test
```
Expected: All tests that were passing before still pass.

### Step 6: Commit

```
git add resources/views/components/auth-split-layout.blade.php tests/Feature/Auth/RegistrationTest.php
git commit -m "feat: update auth-split-layout with 3-stop brand gradient and named panel slot"
```

---

## Task 2: Add Panel to `learner-login.blade.php`

**Files:**
- Modify: `resources/views/auth/learner-login.blade.php`

**Heroicon:** `academic-cap`
**Headline:** "Welcome back"
**Sub-text:** "Continue your learning journey"

### Step 1: Write a test

Add to `tests/Feature/Auth/AuthenticationTest.php` or a new `tests/Feature/Auth/PageRenderTest.php`:

```php
public function test_learner_login_page_shows_welcome_back_panel(): void
{
    $response = $this->get(route('learner.login'));
    $response->assertStatus(200);
    $response->assertSee('Welcome back');
}
```

### Step 2: Run test (expect FAIL)

```
php artisan test --filter=test_learner_login_page_shows_welcome_back_panel
```

### Step 3: Add the `$panel` slot

In `resources/views/auth/learner-login.blade.php`, add this as the **first child** of `<x-auth-split-layout ...>` (before the existing form content):

```blade
<x-slot name="panel">
    <div class="absolute inset-0 overflow-hidden opacity-10 pointer-events-none">
        <div class="absolute top-0 right-0 w-64 h-64 bg-white rounded-full blur-3xl transform translate-x-1/2 -translate-y-1/2"></div>
        <div class="absolute bottom-0 left-0 w-96 h-96 bg-white rounded-full blur-3xl transform -translate-x-1/2 translate-y-1/2"></div>
    </div>
    <div class="relative h-full flex flex-col items-center justify-center p-12 text-center">
        <div class="absolute top-8 left-8">
            <img src="{{ asset('media/Logo.png') }}" alt="ConciousConnections" class="w-10 h-10 object-contain opacity-80">
        </div>
        <div class="w-24 h-24 rounded-full bg-white/10 flex items-center justify-center mb-6">
            <svg class="w-12 h-12 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 14l9-5-9-5-9 5 9 5z" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z" />
            </svg>
        </div>
        <h2 class="text-3xl font-bold text-white mb-3">Welcome back</h2>
        <p class="text-white/70 text-sm max-w-[200px] leading-relaxed">Continue your learning journey</p>
    </div>
</x-slot>
```

Also remove the `brandText="Concious Connections"` prop from `<x-auth-split-layout>` since the `$panel` slot takes over.

### Step 4: Run test (expect PASS)

```
php artisan test --filter=test_learner_login_page_shows_welcome_back_panel
```

### Step 5: Commit

```
git add resources/views/auth/learner-login.blade.php
git commit -m "feat: add branded right panel to learner login page"
```

---

## Task 3: Rebuild `register.blade.php` (Personal Info — Step 1)

**Files:**
- Modify: `resources/views/auth/register.blade.php`

**Heroicon:** `academic-cap`
**Headline:** "Start your learning journey"
**Sub-text:** "A safe, age-appropriate space to grow"
**showTabs:** true, activeTab="register", loginRoute=`route('learner.login')`, registerRoute=`route('register')`

### Step 1: Write test

Add to `tests/Feature/Auth/PageRenderTest.php`:

```php
public function test_register_page_shows_start_your_learning_journey_panel(): void
{
    $response = $this->get('/register');
    $response->assertStatus(200);
    $response->assertSee('Start your learning journey');
}
```

### Step 2: Run test (expect FAIL)

```
php artisan test --filter=test_register_page_shows_start_your_learning_journey_panel
```

### Step 3: Rebuild the file

Replace the **entire** `register.blade.php` with the following. The form content (everything inside from `<x-wizard-stepper />` down through the footer links) is preserved exactly — only the outer HTML boilerplate and the heading block are changed:

```blade
<x-auth-split-layout
    :showTabs="true"
    activeTab="register"
    :loginRoute="route('learner.login')"
    :registerRoute="route('register')"
>
    {{-- RIGHT PANEL --}}
    <x-slot name="panel">
        <div class="absolute inset-0 overflow-hidden opacity-10 pointer-events-none">
            <div class="absolute top-0 right-0 w-64 h-64 bg-white rounded-full blur-3xl transform translate-x-1/2 -translate-y-1/2"></div>
            <div class="absolute bottom-0 left-0 w-96 h-96 bg-white rounded-full blur-3xl transform -translate-x-1/2 translate-y-1/2"></div>
        </div>
        <div class="relative h-full flex flex-col items-center justify-center p-12 text-center">
            <div class="absolute top-8 left-8">
                <img src="{{ asset('media/Logo.png') }}" alt="ConciousConnections" class="w-10 h-10 object-contain opacity-80">
            </div>
            <div class="w-24 h-24 rounded-full bg-white/10 flex items-center justify-center mb-6">
                <svg class="w-12 h-12 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 14l9-5-9-5-9 5 9 5z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z" />
                </svg>
            </div>
            <h2 class="text-3xl font-bold text-white mb-3">Start your learning journey</h2>
            <p class="text-white/70 text-sm max-w-[200px] leading-relaxed">A safe, age-appropriate space to grow</p>
        </div>
    </x-slot>

    {{-- LEFT PANEL — form content --}}
    <x-wizard-stepper />

    <div class="mb-6">
        <h2 class="text-3xl font-bold text-purple-900">Create your account</h2>
        <p class="mt-1 text-sm text-gray-500">Step 1 of 2 — Personal Information</p>
    </div>

    <form method="POST" action="{{ route('register') }}" x-data="{
        birthdate: '{{ old('birthdate') }}',
        age: null,
        loading: false,
        calculateAge() {
            if (this.birthdate) {
                const today = new Date();
                const birth = new Date(this.birthdate);
                let age = today.getFullYear() - birth.getFullYear();
                const monthDiff = today.getMonth() - birth.getMonth();
                if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birth.getDate())) {
                    age--;
                }
                this.age = age > 0 && age < 120 ? age : null;
            } else {
                this.age = null;
            }
        }
    }" x-init="calculateAge()" @submit="loading = true">
        @csrf

        <div class="space-y-4">

            <!-- First Name -->
            <div>
                <label for="first_name" class="block text-sm font-medium text-gray-700 mb-1">First Name</label>
                <input id="first_name" type="text" name="first_name" value="{{ old('first_name') }}" required autofocus autocomplete="given-name" placeholder="Juan"
                    class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-brand-purple-primary focus:border-transparent transition-all duration-200" />
                @error('first_name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <!-- Middle Initial (Optional) -->
            <div>
                <label for="middle_initial" class="block text-sm font-medium text-gray-700 mb-1">Middle Initial <span class="text-gray-400 font-normal">(Optional)</span></label>
                <input id="middle_initial" type="text" name="middle_initial" value="{{ old('middle_initial') }}" maxlength="10" autocomplete="additional-name" placeholder="D."
                    class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-brand-purple-primary focus:border-transparent transition-all duration-200" />
                <p class="mt-1 text-xs text-gray-500">Example: D. or De la</p>
                @error('middle_initial')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <!-- Last Name -->
            <div>
                <label for="last_name" class="block text-sm font-medium text-gray-700 mb-1">Last Name</label>
                <input id="last_name" type="text" name="last_name" value="{{ old('last_name') }}" required autocomplete="family-name" placeholder="dela Cruz"
                    class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-brand-purple-primary focus:border-transparent transition-all duration-200" />
                @error('last_name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <!-- Suffix (Optional) -->
            <div>
                <label for="suffix" class="block text-sm font-medium text-gray-700 mb-1">Suffix <span class="text-gray-400 font-normal">(Optional)</span></label>
                <select id="suffix" name="suffix"
                    class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-gray-900 focus:outline-none focus:ring-2 focus:ring-brand-purple-primary focus:border-transparent transition-all duration-200">
                    <option value="">-- None --</option>
                    <option value="Jr." {{ old('suffix') == 'Jr.' ? 'selected' : '' }}>Jr.</option>
                    <option value="Sr." {{ old('suffix') == 'Sr.' ? 'selected' : '' }}>Sr.</option>
                    <option value="II"  {{ old('suffix') == 'II'  ? 'selected' : '' }}>II</option>
                    <option value="III" {{ old('suffix') == 'III' ? 'selected' : '' }}>III</option>
                    <option value="IV"  {{ old('suffix') == 'IV'  ? 'selected' : '' }}>IV</option>
                    <option value="V"   {{ old('suffix') == 'V'   ? 'selected' : '' }}>V</option>
                </select>
                @error('suffix')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <!-- Birth Date -->
            <div>
                <label for="birthdate" class="block text-sm font-medium text-gray-700 mb-1">Birth Date</label>
                <input type="date" id="birthdate" name="birthdate" x-model="birthdate" @change="calculateAge()"
                    value="{{ old('birthdate') }}" required
                    min="{{ now()->subYears(100)->format('Y-m-d') }}"
                    max="{{ now()->subYears(5)->format('Y-m-d') }}"
                    class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-gray-900 focus:outline-none focus:ring-2 focus:ring-brand-purple-primary focus:border-transparent transition-all duration-200" />

                <div x-show="age !== null" class="mt-2">
                    <div x-show="age >= 13" class="flex items-center text-sm text-green-600">
                        <svg class="w-5 h-5 mr-1.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        <span>You are <strong x-text="age"></strong> years old — eligible to register!</span>
                    </div>
                    <div x-show="age < 13" class="flex items-center text-sm text-orange-600">
                        <svg class="w-5 h-5 mr-1.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                        </svg>
                        <span>You are <strong x-text="age"></strong> years old — a parent/guardian must register for you</span>
                    </div>
                </div>

                <p class="mt-1 text-xs text-gray-500">You must be at least 5 years old to use this platform</p>
                @error('birthdate')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <!-- Next Button -->
            <div class="pt-2">
                <button type="submit" :disabled="loading"
                    class="w-full bg-brand-purple-primary text-white py-3.5 px-6 rounded-xl font-semibold text-base hover:bg-brand-purple-dark transition-all duration-200 shadow-lg hover:shadow-xl disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2">
                    <span x-show="!loading">Next →</span>
                    <span x-show="loading" class="flex items-center gap-2">
                        <svg class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span>Checking...</span>
                    </span>
                </button>

                <div class="mt-4 text-center">
                    <p class="text-sm text-gray-600">
                        Already registered?
                        <a href="{{ route('learner.login') }}" class="text-brand-purple-primary font-medium hover:text-brand-purple-dark transition-colors">Login</a>
                    </p>
                </div>
            </div>

        </div>

        <!-- Footer Links -->
        <div class="mt-6 pt-4 border-t border-gray-200">
            <div class="flex items-center justify-center gap-4 text-xs text-gray-500">
                <a href="#" class="hover:text-brand-purple-primary transition-colors">Help</a>
                <span class="text-gray-300">|</span>
                <a href="{{ route('terms') }}" class="hover:text-brand-purple-primary transition-colors">Terms</a>
                <span class="text-gray-300">|</span>
                <a href="{{ route('privacy') }}" class="hover:text-brand-purple-primary transition-colors">Privacy</a>
            </div>
        </div>
    </form>
</x-auth-split-layout>
```

### Step 4: Run tests

```
php artisan test --filter=test_register_page_shows_start_your_learning_journey_panel
php artisan test --filter=RegistrationTest
```
Expected: All PASS.

### Step 5: Commit

```
git add resources/views/auth/register.blade.php tests/Feature/Auth/PageRenderTest.php
git commit -m "feat: rebuild register page with split-layout branded panel"
```

---

## Task 4: Rebuild `register-account.blade.php` (Account Info — Step 2)

**Files:**
- Modify: `resources/views/auth/register-account.blade.php`

**Heroicon:** `shield-check`
**Headline:** "Almost there!"
**Sub-text:** "Create your credentials to protect your account"
**showTabs:** true, activeTab="register"

### Step 1: Write test (add to `PageRenderTest.php`)

```php
public function test_register_account_page_shows_almost_there_panel(): void
{
    $this->withSession(['pending_personal_info' => [
        'first_name' => 'Juan', 'last_name' => 'dela Cruz',
        'birthdate' => '2000-01-01', 'age' => 25,
    ]]);
    $response = $this->get('/register/account');
    $response->assertStatus(200);
    $response->assertSee('Almost there!');
}
```

### Step 2: Run test (expect FAIL)

```
php artisan test --filter=test_register_account_page_shows_almost_there_panel
```

### Step 3: Rebuild the file

Replace the **entire** `register-account.blade.php` with:

```blade
<x-auth-split-layout
    :showTabs="true"
    activeTab="register"
    :loginRoute="route('learner.login')"
    :registerRoute="route('register')"
>
    {{-- RIGHT PANEL --}}
    <x-slot name="panel">
        <div class="absolute inset-0 overflow-hidden opacity-10 pointer-events-none">
            <div class="absolute top-0 right-0 w-64 h-64 bg-white rounded-full blur-3xl transform translate-x-1/2 -translate-y-1/2"></div>
            <div class="absolute bottom-0 left-0 w-96 h-96 bg-white rounded-full blur-3xl transform -translate-x-1/2 translate-y-1/2"></div>
        </div>
        <div class="relative h-full flex flex-col items-center justify-center p-12 text-center">
            <div class="absolute top-8 left-8">
                <img src="{{ asset('media/Logo.png') }}" alt="ConciousConnections" class="w-10 h-10 object-contain opacity-80">
            </div>
            <div class="w-24 h-24 rounded-full bg-white/10 flex items-center justify-center mb-6">
                <svg class="w-12 h-12 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
                </svg>
            </div>
            <h2 class="text-3xl font-bold text-white mb-3">Almost there!</h2>
            <p class="text-white/70 text-sm max-w-[200px] leading-relaxed">Create your credentials to protect your account</p>
        </div>
    </x-slot>

    {{-- LEFT PANEL --}}
    <x-wizard-stepper />

    <div class="mb-6">
        <h2 class="text-3xl font-bold text-purple-900">Account Information</h2>
        <p class="mt-1 text-sm text-gray-500">Step 2 of 2 — Set up your login credentials</p>
    </div>

    <form method="POST" action="{{ route('register.account') }}" x-data="{
        showPassword: false,
        showConfirmPassword: false,
        loading: false,
    }" @submit="loading = true">
        @csrf

        <div class="space-y-4">

            <!-- Email Address -->
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email Address <span class="text-gray-400 font-normal">(Gmail only)</span></label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username" placeholder="yourname@gmail.com"
                    class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-brand-purple-primary focus:border-transparent transition-all duration-200" />
                <p class="mt-1 text-xs text-gray-500">We'll send a verification link to this email</p>
                @error('email')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <!-- Password -->
            <div>
                <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                <div class="relative">
                    <input id="password" :type="showPassword ? 'text' : 'password'" name="password" required autocomplete="new-password"
                        class="w-full px-4 py-2.5 pr-12 bg-gray-50 border border-gray-200 rounded-xl text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-brand-purple-primary focus:border-transparent transition-all duration-200" />
                    <button type="button" @click="showPassword = !showPassword" class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-700 focus:outline-none">
                        <svg x-show="!showPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                        <svg x-show="showPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" /></svg>
                    </button>
                </div>
                <p class="mt-1 text-xs text-gray-500">Min 8 chars with uppercase, lowercase, numbers &amp; symbols</p>
                @error('password')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <!-- Confirm Password -->
            <div>
                <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">Confirm Password</label>
                <div class="relative">
                    <input id="password_confirmation" :type="showConfirmPassword ? 'text' : 'password'" name="password_confirmation" required autocomplete="new-password"
                        class="w-full px-4 py-2.5 pr-12 bg-gray-50 border border-gray-200 rounded-xl text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-brand-purple-primary focus:border-transparent transition-all duration-200" />
                    <button type="button" @click="showConfirmPassword = !showConfirmPassword" class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-700 focus:outline-none">
                        <svg x-show="!showConfirmPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                        <svg x-show="showConfirmPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" /></svg>
                    </button>
                </div>
                @error('password_confirmation')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <!-- Submit Button -->
            <div class="pt-2">
                <button type="submit" :disabled="loading"
                    class="w-full bg-brand-purple-primary text-white py-3.5 px-6 rounded-xl font-semibold text-base hover:bg-brand-purple-dark transition-all duration-200 shadow-lg hover:shadow-xl disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2">
                    <span x-show="!loading">Create Account</span>
                    <span x-show="loading" class="flex items-center gap-2">
                        <svg class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span>Creating account...</span>
                    </span>
                </button>

                <div class="mt-4 text-center">
                    <a href="{{ route('register') }}" class="text-sm text-gray-500 hover:text-brand-purple-primary transition-colors">
                        ← Back to Personal Info
                    </a>
                </div>
            </div>

        </div>
    </form>
</x-auth-split-layout>
```

### Step 4: Run tests

```
php artisan test --filter=test_register_account_page_shows_almost_there_panel
php artisan test --filter=RegistrationTest
```

### Step 5: Commit

```
git add resources/views/auth/register-account.blade.php
git commit -m "feat: rebuild register-account page with split-layout branded panel"
```

---

## Task 5: Rebuild `verify-email.blade.php`

**Files:**
- Modify: `resources/views/auth/verify-email.blade.php`

**Heroicon:** `envelope`
**Headline:** "Check your inbox"
**Sub-text:** "We sent a verification link to your email address"
**showTabs:** false

### Step 1: Write test (add to `PageRenderTest.php`)

```php
public function test_verify_email_page_shows_check_your_inbox_panel(): void
{
    $user = \App\Models\User::factory()->unverified()->create();
    $response = $this->actingAs($user)->get('/verify-email');
    $response->assertStatus(200);
    $response->assertSee('Check your inbox');
}
```

### Step 2: Run test (expect FAIL)

```
php artisan test --filter=test_verify_email_page_shows_check_your_inbox_panel
```

### Step 3: Replace the file content

```blade
<x-auth-split-layout :showTabs="false">
    {{-- RIGHT PANEL --}}
    <x-slot name="panel">
        <div class="absolute inset-0 overflow-hidden opacity-10 pointer-events-none">
            <div class="absolute top-0 right-0 w-64 h-64 bg-white rounded-full blur-3xl transform translate-x-1/2 -translate-y-1/2"></div>
            <div class="absolute bottom-0 left-0 w-96 h-96 bg-white rounded-full blur-3xl transform -translate-x-1/2 translate-y-1/2"></div>
        </div>
        <div class="relative h-full flex flex-col items-center justify-center p-12 text-center">
            <div class="absolute top-8 left-8">
                <img src="{{ asset('media/Logo.png') }}" alt="ConciousConnections" class="w-10 h-10 object-contain opacity-80">
            </div>
            <div class="w-24 h-24 rounded-full bg-white/10 flex items-center justify-center mb-6">
                <svg class="w-12 h-12 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" />
                </svg>
            </div>
            <h2 class="text-3xl font-bold text-white mb-3">Check your inbox</h2>
            <p class="text-white/70 text-sm max-w-[200px] leading-relaxed">We sent a verification link to your email address</p>
        </div>
    </x-slot>

    {{-- LEFT PANEL --}}
    <x-wizard-stepper />

    <div class="mb-6">
        <h2 class="text-3xl font-bold text-purple-900">Verify your email</h2>
        <p class="mt-2 text-sm text-gray-600">
            Thanks for signing up! Before getting started, please verify your email address by clicking the link we just sent you. If you didn't receive it, we can send another.
        </p>
    </div>

    @if (session('status') == 'verification-link-sent')
        <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-xl">
            <p class="text-sm font-medium text-green-700">A new verification link has been sent to your email address.</p>
        </div>
    @endif

    <div class="space-y-3">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <button type="submit"
                class="w-full bg-brand-purple-primary text-white py-3.5 px-6 rounded-xl font-semibold text-base hover:bg-brand-purple-dark transition-all duration-200 shadow-lg hover:shadow-xl">
                Resend Verification Email
            </button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit"
                class="w-full py-3 px-6 rounded-xl text-sm text-gray-500 hover:text-gray-700 hover:bg-gray-100 transition-all duration-200 font-medium">
                Log Out
            </button>
        </form>
    </div>
</x-auth-split-layout>
```

### Step 4: Run tests

```
php artisan test --filter=test_verify_email_page_shows_check_your_inbox_panel
php artisan test --filter=EmailVerificationTest
```

### Step 5: Commit

```
git add resources/views/auth/verify-email.blade.php
git commit -m "feat: rebuild verify-email page with split-layout branded panel"
```

---

## Task 6: Rebuild `parent-registration-required.blade.php`

**Files:**
- Modify: `resources/views/auth/parent-registration-required.blade.php`

**Heroicon:** `shield-exclamation`
**Headline:** "Safe learning for young ones"
**Sub-text:** "Children under 13 need a parent or guardian to get started"
**showTabs:** false

The left panel preserves all existing content (How It Works steps, action buttons, privacy notice). The decorative warning icon and blue info box are replaced with a cleaner branded treatment using purple accents.

### Step 1: Write test

```php
public function test_parent_required_page_shows_safe_learning_panel(): void
{
    $response = $this->get(route('parent.registration.required'));
    $response->assertStatus(200);
    $response->assertSee('Safe learning for young ones');
}
```

### Step 2: Run test (expect FAIL)

```
php artisan test --filter=test_parent_required_page_shows_safe_learning_panel
```

### Step 3: Replace the file content

```blade
<x-auth-split-layout :showTabs="false">
    {{-- RIGHT PANEL --}}
    <x-slot name="panel">
        <div class="absolute inset-0 overflow-hidden opacity-10 pointer-events-none">
            <div class="absolute top-0 right-0 w-64 h-64 bg-white rounded-full blur-3xl transform translate-x-1/2 -translate-y-1/2"></div>
            <div class="absolute bottom-0 left-0 w-96 h-96 bg-white rounded-full blur-3xl transform -translate-x-1/2 translate-y-1/2"></div>
        </div>
        <div class="relative h-full flex flex-col items-center justify-center p-12 text-center">
            <div class="absolute top-8 left-8">
                <img src="{{ asset('media/Logo.png') }}" alt="ConciousConnections" class="w-10 h-10 object-contain opacity-80">
            </div>
            <div class="w-24 h-24 rounded-full bg-white/10 flex items-center justify-center mb-6">
                <svg class="w-12 h-12 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0-10.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.75c0 5.592 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.57-.598-3.75h-.152c-3.196 0-6.1-1.249-8.25-3.286zm0 13.036h.008v.008H12v-.008z" />
                </svg>
            </div>
            <h2 class="text-3xl font-bold text-white mb-3">Safe learning for young ones</h2>
            <p class="text-white/70 text-sm max-w-[220px] leading-relaxed">Children under 13 need a parent or guardian to get started</p>
        </div>
    </x-slot>

    {{-- LEFT PANEL --}}
    <x-wizard-stepper />

    <div class="mb-6">
        <h2 class="text-2xl font-bold text-purple-900">Parent/Guardian Required</h2>
        <p class="mt-2 text-sm text-gray-600">Children under 13 years old need a parent or guardian to create their account.</p>
    </div>

    {{-- Why required --}}
    <div class="bg-purple-50 border-l-4 rounded-r-xl p-4 mb-6" style="border-color: #730DB1;">
        <p class="text-sm text-purple-900">For your child's safety and to comply with online privacy laws, we require parental consent for users under 13.</p>
    </div>

    {{-- How it works --}}
    <div class="mb-6">
        <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider mb-3">How It Works</h3>
        <ol class="space-y-3">
            @foreach([
                ['Parent/Guardian Registers', 'Create your parent account (must be 18+)'],
                ['Verify Your Email', 'We\'ll send a verification link to your Gmail'],
                ['Create Child Account', 'After verification, set up your child\'s account'],
                ['Monitor Progress', 'Track your child\'s learning and quiz results'],
            ] as $i => $step)
            <li class="flex items-start gap-3">
                <div class="w-6 h-6 rounded-full flex-shrink-0 flex items-center justify-center text-white text-xs font-bold mt-0.5"
                    style="background: linear-gradient(135deg, #A30EB2, #3B0CB1);">{{ $i + 1 }}</div>
                <div>
                    <p class="text-sm font-semibold text-gray-900">{{ $step[0] }}</p>
                    <p class="text-xs text-gray-500">{{ $step[1] }}</p>
                </div>
            </li>
            @endforeach
        </ol>
    </div>

    {{-- Action buttons --}}
    <div class="space-y-3">
        <a href="{{ route('parent.register') }}"
            class="w-full flex items-center justify-center gap-2 bg-brand-purple-primary text-white py-3.5 px-6 rounded-xl font-semibold text-sm hover:bg-brand-purple-dark transition-all duration-200 shadow-lg hover:shadow-xl">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
            </svg>
            Register as Parent/Guardian
        </a>
        <a href="{{ route('learner.login') }}"
            class="w-full flex items-center justify-center py-3 px-6 rounded-xl text-sm text-gray-500 hover:text-gray-700 hover:bg-gray-100 transition-all duration-200 font-medium">
            Already have an account? Login
        </a>
    </div>

    {{-- Privacy note --}}
    <p class="mt-6 text-xs text-gray-400 text-center">
        We take your child's privacy seriously.
        <a href="{{ route('privacy') }}" class="text-brand-purple-primary hover:underline">Privacy Policy</a> ·
        <a href="{{ route('terms') }}" class="text-brand-purple-primary hover:underline">Terms</a>
    </p>
</x-auth-split-layout>
```

### Step 4: Run tests

```
php artisan test --filter=test_parent_required_page_shows_safe_learning_panel
```

### Step 5: Commit

```
git add resources/views/auth/parent-registration-required.blade.php
git commit -m "feat: rebuild parent-registration-required page with split-layout branded panel"
```

---

## Task 7: Rebuild `parent-register.blade.php`

**Files:**
- Modify: `resources/views/auth/parent-register.blade.php`

**Heroicon:** `user-group`
**Headline:** "Guide their journey"
**Sub-text:** "Create a parent account to support your child's learning"
**showTabs:** false

This is a large form. The implementation is to strip the standalone HTML boilerplate (entire `<!DOCTYPE html>` through `<body>` and closing `</body></html>`) and the outer centering div, then wrap the inner content with `x-auth-split-layout`. All Alpine.js `x-data`, form fields, validation display, and `@submit` handlers are preserved exactly.

### Step 1: Write test

```php
public function test_parent_register_page_shows_guide_their_journey_panel(): void
{
    $response = $this->get(route('parent.register'));
    $response->assertStatus(200);
    $response->assertSee('Guide their journey');
}
```

### Step 2: Run test (expect FAIL)

```
php artisan test --filter=test_parent_register_page_shows_guide_their_journey_panel
```

### Step 3: Rebuild the file

Replace the **entire** file. The new wrapper is:

```blade
<x-auth-split-layout :showTabs="false">
    {{-- RIGHT PANEL --}}
    <x-slot name="panel">
        <div class="absolute inset-0 overflow-hidden opacity-10 pointer-events-none">
            <div class="absolute top-0 right-0 w-64 h-64 bg-white rounded-full blur-3xl transform translate-x-1/2 -translate-y-1/2"></div>
            <div class="absolute bottom-0 left-0 w-96 h-96 bg-white rounded-full blur-3xl transform -translate-x-1/2 translate-y-1/2"></div>
        </div>
        <div class="relative h-full flex flex-col items-center justify-center p-12 text-center">
            <div class="absolute top-8 left-8">
                <img src="{{ asset('media/Logo.png') }}" alt="ConciousConnections" class="w-10 h-10 object-contain opacity-80">
            </div>
            <div class="w-24 h-24 rounded-full bg-white/10 flex items-center justify-center mb-6">
                <svg class="w-12 h-12 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                </svg>
            </div>
            <h2 class="text-3xl font-bold text-white mb-3">Guide their journey</h2>
            <p class="text-white/70 text-sm max-w-[200px] leading-relaxed">Create a parent account to support your child's learning</p>
        </div>
    </x-slot>

    {{-- LEFT PANEL — preserve all original form content below --}}
    <x-wizard-stepper />

    <div class="mb-6">
        <h2 class="text-2xl font-bold text-purple-900">Create Parent Account</h2>
        <p class="mt-1 text-sm text-gray-500">Register as a parent to manage your child's learning</p>
    </div>

    {{-- Info Banner --}}
    <div class="bg-purple-50 border-l-4 rounded-r-xl p-4 mb-6" style="border-color: #730DB1;">
        <p class="text-sm text-purple-900"><strong>Parent accounts allow you to:</strong> Create and manage accounts for children under 13, monitor their learning progress, view quiz results, and approve content.</p>
    </div>

    {{-- Preserve the original $errors block and registration form exactly as-is --}}
    [PASTE ORIGINAL ERRORS BLOCK AND FORM HERE — from @if ($errors->any()) to </form> — no changes]
</x-auth-split-layout>
```

**Critical implementation note:** In the `[PASTE ORIGINAL...]` section above, copy the `@if ($errors->any()) ... @endif` block and the entire `<form method="POST" action="{{ route('parent.register.store') }}" x-data="{ ... }" ...>` tag through the closing `</form>` tag **verbatim from the original file**. Do not alter any field names, Alpine.js expressions, validation messages, or submit handlers.

Only these elements are removed from the original:
- The `<!DOCTYPE html>` through `<body class="...">` opening (HTML shell)
- The outer `<div class="min-h-screen flex flex-col items-center justify-center py-12 px-4 sm:px-6 lg:px-8">` wrapper
- The `<x-wizard-stepper />` and old heading `<h1 class="text-3xl font-bold text-gray-900">Create Parent Account</h1>` block (replaced above)
- The old `<!-- Info Banner -->` blue div (replaced with purple above)
- The closing `</div></body></html>`

### Step 4: Run tests

```
php artisan test --filter=test_parent_register_page_shows_guide_their_journey_panel
```

### Step 5: Commit

```
git add resources/views/auth/parent-register.blade.php
git commit -m "feat: rebuild parent-register page with split-layout branded panel"
```

---

## Task 8: Rebuild `profile/complete.blade.php`

**Files:**
- Modify: `resources/views/profile/complete.blade.php`

**Heroicon:** `identification`
**Headline:** "One last step!"
**Sub-text:** "Help us personalize your learning experience"
**showTabs:** false

Key change: swaps `x-app-layout` (which renders the top navigation bar) to `x-auth-split-layout`. The PSGC dynamic barangay loading script is preserved.

### Step 1: Write test

```php
public function test_complete_profile_page_shows_one_last_step_panel(): void
{
    $user = \App\Models\User::factory()->create();
    $user->assignRole('learner');
    $response = $this->actingAs($user)->get(route('profile.complete'));
    $response->assertStatus(200);
    $response->assertSee('One last step!');
}
```

### Step 2: Run test (expect FAIL)

```
php artisan test --filter=test_complete_profile_page_shows_one_last_step_panel
```

### Step 3: Rebuild the file

The new structure is `x-auth-split-layout` with the panel slot + all original form content. Remove:
- `<x-app-layout>` opening and closing tags
- `<x-slot name="header">...</x-slot>` block
- The outer `<div class="py-12">` wrapper div

Replace with:

```blade
<x-auth-split-layout :showTabs="false">
    {{-- RIGHT PANEL --}}
    <x-slot name="panel">
        <div class="absolute inset-0 overflow-hidden opacity-10 pointer-events-none">
            <div class="absolute top-0 right-0 w-64 h-64 bg-white rounded-full blur-3xl transform translate-x-1/2 -translate-y-1/2"></div>
            <div class="absolute bottom-0 left-0 w-96 h-96 bg-white rounded-full blur-3xl transform -translate-x-1/2 translate-y-1/2"></div>
        </div>
        <div class="relative h-full flex flex-col items-center justify-center p-12 text-center">
            <div class="absolute top-8 left-8">
                <img src="{{ asset('media/Logo.png') }}" alt="ConciousConnections" class="w-10 h-10 object-contain opacity-80">
            </div>
            <div class="w-24 h-24 rounded-full bg-white/10 flex items-center justify-center mb-6">
                <svg class="w-12 h-12 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 9h3.75M15 12h3.75M15 15h3.75M4.5 19.5h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5zm6-10.125a1.875 1.875 0 11-3.75 0 1.875 1.875 0 013.75 0zm1.294 6.336a6.721 6.721 0 01-3.17.789 6.721 6.721 0 01-3.168-.789 3.376 3.376 0 016.338 0z" />
                </svg>
            </div>
            <h2 class="text-3xl font-bold text-white mb-3">One last step!</h2>
            <p class="text-white/70 text-sm max-w-[200px] leading-relaxed">Help us personalize your learning experience</p>
        </div>
    </x-slot>

    {{-- LEFT PANEL --}}
    <x-wizard-stepper />

    <div class="mb-6">
        <h2 class="text-2xl font-bold text-purple-900">Welcome, {{ Auth::user()->full_name }}!</h2>
        <p class="mt-1 text-sm text-gray-500">Just a couple more details to start learning. Fields marked * are required.</p>
        @if(Auth::user()->email_verified_at)
            <div class="mt-3 flex items-center gap-2 text-sm text-green-700 bg-green-50 border border-green-200 rounded-xl px-3 py-2">
                <svg class="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                <span><strong>Email verified:</strong> {{ Auth::user()->email }}</span>
            </div>
        @endif
    </div>

    <form method="POST" action="{{ route('profile.store') }}">
        @csrf

        {{-- USERNAME --}}
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">Username *</label>
            <input type="text" name="username" value="{{ old('username', $learnerProfile?->username) }}" required
                class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-brand-purple-primary focus:border-transparent transition-all duration-200"
                placeholder="e.g., cool_learner_123" pattern="[a-z0-9_-]+" maxlength="30">
            <p class="mt-1 text-xs text-gray-500">3–30 chars: lowercase letters, numbers, underscores, hyphens</p>
            @error('username')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>

        {{-- GENDER --}}
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">Gender</label>
            <select name="gender" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-gray-900 focus:outline-none focus:ring-2 focus:ring-brand-purple-primary focus:border-transparent transition-all duration-200">
                <option value="">Select (optional)</option>
                <option value="male" {{ old('gender', $learnerProfile?->gender) === 'male' ? 'selected' : '' }}>Male</option>
                <option value="female" {{ old('gender', $learnerProfile?->gender) === 'female' ? 'selected' : '' }}>Female</option>
                <option value="prefer_not_to_say" {{ old('gender', $learnerProfile?->gender) === 'prefer_not_to_say' ? 'selected' : '' }}>Prefer not to say</option>
            </select>
            @error('gender')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>

        {{-- CITY --}}
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">Municipality/City (Cavite) *</label>
            <select name="city_code" id="city_code" required
                class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-gray-900 focus:outline-none focus:ring-2 focus:ring-brand-purple-primary focus:border-transparent transition-all duration-200">
                <option value="">Select your municipality/city</option>
                @foreach($cities as $city)
                    <option value="{{ $city->code }}" {{ old('city_code', $learnerProfile?->city_code) === $city->code ? 'selected' : '' }}>{{ $city->name }}</option>
                @endforeach
            </select>
            @error('city_code')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>

        {{-- BARANGAY --}}
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">Barangay *</label>
            <select name="barangay_code" id="barangay_code" required
                class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-gray-900 focus:outline-none focus:ring-2 focus:ring-brand-purple-primary focus:border-transparent transition-all duration-200">
                <option value="">Select municipality first</option>
                @if(old('barangay_code', $learnerProfile?->barangay_code))
                    @foreach($barangays ?? [] as $barangay)
                        <option value="{{ $barangay->code }}" {{ old('barangay_code', $learnerProfile?->barangay_code) === $barangay->code ? 'selected' : '' }}>{{ $barangay->name }}</option>
                    @endforeach
                @endif
            </select>
            @error('barangay_code')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>

        {{-- BIO --}}
        <div class="mb-6">
            <label class="block text-sm font-medium text-gray-700 mb-1">Bio <span class="text-gray-400 font-normal">(Optional)</span></label>
            <textarea name="bio" rows="3" maxlength="500"
                class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-brand-purple-primary focus:border-transparent transition-all duration-200"
                placeholder="Tell us a bit about yourself...">{{ old('bio', $learnerProfile?->bio) }}</textarea>
            <p class="mt-1 text-xs text-gray-500">Maximum 500 characters</p>
            @error('bio')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>

        <button type="submit"
            class="w-full bg-brand-purple-primary text-white py-3.5 px-6 rounded-xl font-semibold text-base hover:bg-brand-purple-dark transition-all duration-200 shadow-lg hover:shadow-xl">
            Complete Profile &amp; Start Learning
        </button>
    </form>

    {{-- Dynamic Barangay Loading Script — preserved exactly --}}
    <script>
        document.getElementById('city_code').addEventListener('change', function() {
            const cityCode = this.value;
            const barangaySelect = document.getElementById('barangay_code');
            if (!cityCode) {
                barangaySelect.innerHTML = '<option value="">Select municipality first</option>';
                return;
            }
            fetch(`/api/barangays/${cityCode}`)
                .then(response => response.json())
                .then(data => {
                    barangaySelect.innerHTML = '<option value="">Select barangay</option>';
                    data.forEach(barangay => {
                        const option = document.createElement('option');
                        option.value = barangay.code;
                        option.textContent = barangay.name;
                        barangaySelect.appendChild(option);
                    });
                })
                .catch(error => console.error('Error loading barangays:', error));
        });
    </script>
</x-auth-split-layout>
```

> **Note:** The fetch URL `/api/barangays/${cityCode}` — verify this matches the actual API route in `routes/web.php` before committing. Adjust if the route is different.

### Step 4: Run tests

```
php artisan test --filter=test_complete_profile_page_shows_one_last_step_panel
php artisan test --filter=ProfileTest
```

### Step 5: Commit

```
git add resources/views/profile/complete.blade.php
git commit -m "feat: rebuild complete-profile page with split-layout branded panel"
```

---

## Task 9: Rebuild `create-child-account.blade.php`

**Files:**
- Modify: `resources/views/auth/create-child-account.blade.php`

**Heroicon:** `star`
**Headline:** "Set up their account"
**Sub-text:** "Age-appropriate content, curated just for them"
**showTabs:** false

This is the longest form in the flow (child info, location, credentials, monitoring permissions). Implementation follows the same pattern: strip HTML shell, wrap with layout, preserve all form fields and Alpine.js logic verbatim. Update any `focus:ring-blue-500` instances to `focus:ring-brand-purple-primary` for consistency.

### Step 1: Write test

```php
public function test_create_child_page_shows_set_up_their_account_panel(): void
{
    $parent = \App\Models\User::factory()->create();
    $parent->assignRole('learner'); // parents use learner role
    $parent->markEmailAsVerified();
    $response = $this->actingAs($parent)->get(route('parent.create-child'));
    $response->assertStatus(200);
    $response->assertSee('Set up their account');
}
```

### Step 2: Run test (expect FAIL)

```
php artisan test --filter=test_create_child_page_shows_set_up_their_account_panel
```

### Step 3: Rebuild the file

Structure:

```blade
<x-auth-split-layout :showTabs="false">
    {{-- RIGHT PANEL --}}
    <x-slot name="panel">
        <div class="absolute inset-0 overflow-hidden opacity-10 pointer-events-none">
            <div class="absolute top-0 right-0 w-64 h-64 bg-white rounded-full blur-3xl transform translate-x-1/2 -translate-y-1/2"></div>
            <div class="absolute bottom-0 left-0 w-96 h-96 bg-white rounded-full blur-3xl transform -translate-x-1/2 translate-y-1/2"></div>
        </div>
        <div class="relative h-full flex flex-col items-center justify-center p-12 text-center">
            <div class="absolute top-8 left-8">
                <img src="{{ asset('media/Logo.png') }}" alt="ConciousConnections" class="w-10 h-10 object-contain opacity-80">
            </div>
            <div class="w-24 h-24 rounded-full bg-white/10 flex items-center justify-center mb-6">
                <svg class="w-12 h-12 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.563.563 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z" />
                </svg>
            </div>
            <h2 class="text-3xl font-bold text-white mb-3">Set up their account</h2>
            <p class="text-white/70 text-sm max-w-[200px] leading-relaxed">Age-appropriate content, curated just for them</p>
        </div>
    </x-slot>

    {{-- LEFT PANEL --}}
    <x-wizard-stepper />

    <div class="mb-6">
        <h2 class="text-2xl font-bold text-purple-900">Create Child Account</h2>
        <p class="mt-1 text-sm text-gray-500">Add a learning account for your child</p>
    </div>

    [PASTE ALL ORIGINAL FORM CONTENT VERBATIM — from @if ($errors->any()) through the closing Alpine.js script tag]

</x-auth-split-layout>
```

Remove from original:
- `<!DOCTYPE html>` through `<body class="bg-gray-50">` (HTML shell)
- The outer `<div class="min-h-screen py-12 px-4 sm:px-6 lg:px-8">` wrapper
- The old header div with `<h1 class="text-3xl font-bold text-gray-900">Create Child Account</h1>` and back link
- The closing `</body></html>`

Additionally, update all `focus:ring-blue-500` and `focus:ring-2 focus:ring-blue-500` occurrences in the form inputs to `focus:ring-brand-purple-primary` for consistency.

### Step 4: Run tests

```
php artisan test --filter=test_create_child_page_shows_set_up_their_account_panel
```

### Step 5: Run full test suite

```
php artisan test
```
Expected: All tests pass.

### Step 6: Commit

```
git add resources/views/auth/create-child-account.blade.php
git commit -m "feat: rebuild create-child-account page with split-layout branded panel"
```

---

## Final Verification Checklist

After all tasks are committed, manually visit each page in a browser (or use `php artisan serve`) and verify:

- [ ] Learner login: right panel shows "Welcome back" on purple gradient
- [ ] Register (step 1): right panel shows academic-cap icon + "Start your learning journey"
- [ ] Register account (step 2): right panel shows shield-check + "Almost there!"
- [ ] Verify email: right panel shows envelope + "Check your inbox"; no top nav
- [ ] Parent required: right panel shows shield-exclamation + "Safe learning for young ones"; purple how-it-works steps
- [ ] Parent register: right panel shows user-group + "Guide their journey"
- [ ] Complete profile: right panel shows ID card + "One last step!"; no top nav bar
- [ ] Create child account: right panel shows star + "Set up their account"
- [ ] All pages: gradient is purple `#A30EB2 → #730DB1 → #3B0CB1` (not the old darker defaults)
- [ ] Wizard stepper visible on all pages, correct step highlighted

Run final test suite:

```
php artisan test
```
