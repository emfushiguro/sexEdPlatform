# Registration Wizard Stepper Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Add a numbered step-indicator UI (wizard stepper) to all registration and onboarding pages so users always know where they are in the flow.

**Architecture:** A single route-name-aware Blade component (`WizardStepper`) with a companion PHP class reads `Route::currentRouteName()` and `session('is_parent_registration')` to auto-detect the flow (learner vs parent-child) and active step — no controller changes. The component is injected directly into 8 existing view files above their form cards.

**Tech Stack:** Laravel 12 Blade components (`app/View/Components`), Tailwind CSS v3, PHPUnit feature tests.

**Design doc:** `docs/plans/2026-03-07-registration-wizard-stepper-design.md`

---

## Task 1: Create the WizardStepper PHP component class

**Files:**
- Create: `app/View/Components/WizardStepper.php`

**Step 1: Write the failing test**

Create `tests/Feature/WizardStepperTest.php`:

```php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\View\Components\WizardStepper;

class WizardStepperTest extends TestCase
{
    use RefreshDatabase;

    public function test_learner_flow_detected_on_register_route(): void
    {
        $component = new WizardStepper('register', false);
        $steps = $component->steps;

        $this->assertCount(3, $steps);
        $this->assertTrue($steps[0]['isActive']);
        $this->assertFalse($steps[1]['isActive']);
        $this->assertFalse($steps[2]['isActive']);
    }

    public function test_learner_flow_step_2_active_on_verification_notice(): void
    {
        $component = new WizardStepper('verification.notice', false);
        $steps = $component->steps;

        $this->assertCount(3, $steps);
        $this->assertTrue($steps[0]['isCompleted']);
        $this->assertTrue($steps[1]['isActive']);
        $this->assertFalse($steps[2]['isActive']);
    }

    public function test_learner_flow_step_3_active_on_profile_complete(): void
    {
        $component = new WizardStepper('profile.complete', false);
        $steps = $component->steps;

        $this->assertCount(3, $steps);
        $this->assertTrue($steps[0]['isCompleted']);
        $this->assertTrue($steps[1]['isCompleted']);
        $this->assertTrue($steps[2]['isActive']);
    }

    public function test_parent_flow_detected_when_session_flag_is_set(): void
    {
        $component = new WizardStepper('verification.notice', true);
        $steps = $component->steps;

        $this->assertCount(5, $steps);
    }

    public function test_parent_flow_step_3_active_on_verification_notice(): void
    {
        $component = new WizardStepper('verification.notice', true);
        $steps = $component->steps;

        $this->assertCount(5, $steps);
        $this->assertTrue($steps[0]['isCompleted']);
        $this->assertTrue($steps[1]['isCompleted']);
        $this->assertTrue($steps[2]['isActive']);
        $this->assertFalse($steps[3]['isActive']);
        $this->assertFalse($steps[4]['isActive']);
    }

    public function test_parent_flow_step_5_active_on_create_child(): void
    {
        $component = new WizardStepper('parent.create-child', true);
        $steps = $component->steps;

        $this->assertTrue($steps[4]['isActive']);
        $this->assertTrue($steps[0]['isCompleted']);
        $this->assertTrue($steps[3]['isCompleted']);
    }

    public function test_unknown_route_returns_null_steps(): void
    {
        $component = new WizardStepper('some.unknown.route', false);

        $this->assertNull($component->steps);
    }
}
```

**Step 2: Run test to verify it fails**

```
php artisan test --filter=WizardStepperTest
```

Expected: FAIL — class `App\View\Components\WizardStepper` not found.

**Step 3: Create the PHP class**

Create `app/View/Components/WizardStepper.php`:

```php
<?php

namespace App\View\Components;

use Illuminate\View\Component;

class WizardStepper extends Component
{
    public ?array $steps;

    private const LEARNER_FLOW = [
        ['label' => 'Create Account',  'route' => 'register'],
        ['label' => 'Verify Email',    'route' => 'verification.notice'],
        ['label' => 'Complete Profile','route' => 'profile.complete'],
    ];

    private const PARENT_FLOW = [
        ['label' => 'Parent Required',      'route' => 'parent.registration.required'],
        ['label' => 'Parent Registers',     'route' => 'parent.register'],
        ['label' => 'Verify Email',         'route' => 'verification.notice'],
        ['label' => 'Complete Profile',     'route' => 'profile.complete'],
        ['label' => 'Create Child Account', 'route' => 'parent.create-child'],
    ];

    public function __construct(
        private string $currentRoute,
        private bool $isParentFlow,
    ) {
        $this->steps = $this->buildSteps();
    }

    private function buildSteps(): ?array
    {
        $map = $this->isParentFlow ? self::PARENT_FLOW : self::LEARNER_FLOW;

        $activeIndex = null;
        foreach ($map as $i => $step) {
            if ($step['route'] === $this->currentRoute) {
                $activeIndex = $i;
                break;
            }
        }

        if ($activeIndex === null) {
            return null;
        }

        return array_map(function (array $step, int $i) use ($activeIndex) {
            return [
                'label'       => $step['label'],
                'isCompleted' => $i < $activeIndex,
                'isActive'    => $i === $activeIndex,
                'isUpcoming'  => $i > $activeIndex,
            ];
        }, $map, array_keys($map));
    }

    public function render()
    {
        return view('components.wizard-stepper');
    }
}
```

**Step 4: Run test to verify it passes**

```
php artisan test --filter=WizardStepperTest
```

Expected: All 7 tests PASS.

**Step 5: Commit**

```bash
git add app/View/Components/WizardStepper.php tests/Feature/WizardStepperTest.php
git commit -m "feat: add WizardStepper PHP component class with tests"
```

---

## Task 2: Create the WizardStepper Blade template

**Files:**
- Create: `resources/views/components/wizard-stepper.blade.php`

**Step 1: No automated test needed for pure Blade markup** — this is reviewed visually. Skip to implementation.

**Step 2: Create the Blade template**

Create `resources/views/components/wizard-stepper.blade.php`:

```blade
@php
    $currentRoute  = Route::currentRouteName();
    $isParentFlow  = (bool) session('is_parent_registration');
    $component     = new \App\View\Components\WizardStepper($currentRoute, $isParentFlow);
    $steps         = $component->steps;
@endphp

@if($steps)
<div class="max-w-lg mx-auto mb-6">
    <div class="bg-white rounded-2xl border border-purple-100/60 shadow-sm px-6 py-4">
        <div class="flex items-center justify-between relative">

            {{-- Connector lines (drawn behind the circles) --}}
            @foreach($steps as $i => $step)
                @if(!$loop->last)
                    @php
                        $nextStep = $steps[$i + 1];
                        $lineCompleted = $step['isCompleted'] || $step['isActive'];
                    @endphp
                    <div class="flex-1 h-0.5 mx-1 {{ $lineCompleted ? 'bg-gradient-to-r from-purple-600 to-indigo-700' : 'bg-gray-200' }}"></div>
                @endif

                {{-- Step circle + label --}}
                <div class="flex flex-col items-center flex-shrink-0" style="min-width: 2.5rem;">
                    @if($step['isCompleted'])
                        {{-- Completed: filled gradient circle with checkmark --}}
                        <div class="w-8 h-8 rounded-full flex items-center justify-center"
                             style="background: linear-gradient(135deg, #A30EB2, #3B0CB1);">
                            <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                            </svg>
                        </div>
                    @elseif($step['isActive'])
                        {{-- Active: white circle with purple ring and bold number --}}
                        <div class="w-8 h-8 rounded-full bg-white ring-2 ring-purple-400 shadow-sm flex items-center justify-center">
                            <span class="text-sm font-bold text-purple-700">{{ $loop->index + 1 }}</span>
                        </div>
                    @else
                        {{-- Upcoming: white circle with grey border --}}
                        <div class="w-8 h-8 rounded-full bg-white border border-gray-200 flex items-center justify-center">
                            <span class="text-sm font-medium text-gray-400">{{ $loop->index + 1 }}</span>
                        </div>
                    @endif

                    {{-- Label --}}
                    <span class="mt-1.5 text-center leading-tight"
                          style="font-size: 0.65rem; white-space: nowrap;"
                          @class([
                              'font-semibold text-purple-700' => $step['isActive'],
                              'font-medium text-purple-500'   => $step['isCompleted'],
                              'font-medium text-gray-400'     => $step['isUpcoming'],
                          ])>
                        {{ $step['label'] }}
                    </span>
                </div>
            @endforeach

        </div>
    </div>
</div>
@endif
```

**Note on layout:** The `@foreach` interleaves circles and connector lines. The connector `div` between circles uses `flex-1` to fill the gap. Each circle `div` has `flex-shrink-0` so it never compresses.

**Step 3: Visual smoke test**

Start the dev server. Visit each page and verify:
- `/register` → learner stepper, step 1 active, 3 steps total
- The stepper does NOT render on pages not in either step map (safe due to `@if($steps)` guard)

**Step 4: Commit**

```bash
git add resources/views/components/wizard-stepper.blade.php
git commit -m "feat: add wizard-stepper Blade template"
```

---

## Task 3: Inject stepper into learner flow views

**Files:**
- Modify: `resources/views/auth/register.blade.php`
- Modify: `resources/views/auth/verify-email.blade.php`
- Modify: `resources/views/profile/complete.blade.php`

**For each file:** Add `<x-wizard-stepper />` as the first element inside the page content, above the form card. Find the outermost wrapping `div` or `form` and prepend the component before it.

**Step 1: Inject into `auth/register.blade.php`**

Find the opening content area (usually the first `<div>` after any layout directives or just inside the body). Add before the main card/form wrapper:

```blade
<x-wizard-stepper />
```

**Step 2: Inject into `auth/verify-email.blade.php`**

Same — add `<x-wizard-stepper />` before the main card content.

**Step 3: Inject into `profile/complete.blade.php`**

Same — add `<x-wizard-stepper />` before the form card. Note: this view uses `<x-app-layout>`, not `<x-guest-layout>`. The stepper will still render correctly since it has its own container styling.

**Step 4: Visual smoke test**

Walk through the full learner registration flow in a browser. Verify:
- Step 1 active on `/register`, steps 2–3 upcoming
- Step 2 active on `/verify-email` (when arriving as a fresh non-parent registrant), step 1 completed
- Step 3 active on `/profile/complete`, steps 1–2 completed

**Step 5: Commit**

```bash
git add resources/views/auth/register.blade.php \
        resources/views/auth/verify-email.blade.php \
        resources/views/profile/complete.blade.php
git commit -m "feat: inject wizard stepper into learner registration flow"
```

---

## Task 4: Inject stepper into parent-child flow views

**Files:**
- Modify: `resources/views/auth/parent-registration-required.blade.php`
- Modify: `resources/views/auth/parent-register.blade.php`
- Modify: `resources/views/auth/create-child-account.blade.php`
- (verify-email and profile/complete already done in Task 3 — same files, component auto-switches flow)

**Step 1: Inject into `parent-registration-required.blade.php`**

Add `<x-wizard-stepper />` before the main info card content.

**Step 2: Inject into `parent-register.blade.php`**

Add `<x-wizard-stepper />` before the registration form card.

**Step 3: Inject into `create-child-account.blade.php`**

Add `<x-wizard-stepper />` before the form card. Note: this view is only accessible when `session('is_parent_registration')` was set (via the profile completion controller flow), so the parent flow stepper will always render correctly here.

**Step 4: Visual smoke test — full parent-child flow**

Walk through in browser with a sub-13 birthdate:

1. `/register` with age < 13 → redirected, no stepper on register page (correct — this page uses learner stepper, which is also fine to show at step 1)
2. `/parent-registration-required` → 5-step parent stepper, step 1 active
3. `/parent/register` → step 2 active
4. `/verify-email` → step 3 active (5 steps visible, NOT 3-step learner flow — confirm `is_parent_registration` is in session)
5. `/profile/complete` → step 4 active
6. `/parent/create-child` → step 5 active, all previous completed

**Step 5: Commit**

```bash
git add resources/views/auth/parent-registration-required.blade.php \
        resources/views/auth/parent-register.blade.php \
        resources/views/auth/create-child-account.blade.php
git commit -m "feat: inject wizard stepper into parent-child registration flow"
```

---

## Task 5: Edge case — register page shows learner stepper even for under-13 users

**Context:** When a child under 13 submits `/register`, the server redirects them away — so the stepper on the register page is always displaying the learner flow (step 1 active). This is correct UX: the child sees step 1 while filling in their info, then the system takes over. No change needed, but verify this is visually sensible.

**Step 1: Write a feature test verifying disambiguation on shared routes**

Add to `tests/Feature/WizardStepperTest.php`:

```php
public function test_verification_notice_shows_learner_flow_without_session_flag(): void
{
    // Simulate no session flag
    $component = new WizardStepper('verification.notice', false);
    $this->assertCount(3, $component->steps);
}

public function test_verification_notice_shows_parent_flow_with_session_flag(): void
{
    $component = new WizardStepper('verification.notice', true);
    $this->assertCount(5, $component->steps);
}

public function test_profile_complete_shows_parent_flow_with_session_flag(): void
{
    $component = new WizardStepper('profile.complete', true);
    $steps = $component->steps;
    $this->assertCount(5, $steps);
    $this->assertTrue($steps[3]['isActive']); // step 4 of 5
}
```

**Step 2: Run tests**

```
php artisan test --filter=WizardStepperTest
```

Expected: All tests PASS.

**Step 3: Commit**

```bash
git add tests/Feature/WizardStepperTest.php
git commit -m "test: add disambiguation edge case tests for WizardStepper"
```

---

## Task 6: Final verification

**Step 1: Run full test suite**

```
php artisan test
```

Expected: All tests pass. No regressions.

**Step 2: Clear view cache**

```
php artisan view:clear
```

**Step 3: Browser walkthrough**

Run through both flows end-to-end (learner and parent-child) and confirm:
- Correct number of steps per flow
- Active step highlights correctly on every page
- Completed steps show checkmark
- No stepper on pages outside both flows (e.g. login pages, instructor pages)
- Mobile: stepper labels don't overflow (test at 375px width)

**Step 4: Final commit if any tweaks made**

```bash
git add -A
git commit -m "fix: wizard stepper visual tweaks after browser review"
```
