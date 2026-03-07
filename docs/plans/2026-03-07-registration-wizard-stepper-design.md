# Registration Wizard Stepper — Design Document

**Date:** 2026-03-07
**Status:** Approved

---

## Problem

The registration flows (regular learner and parent-child) span multiple separate pages with no visual continuity. Users have no sense of progress or how many steps remain, which increases abandonment — especially on the longer 5-step parent-child path.

---

## Goal

Add a step indicator UI (numbered circles with a connecting line and labels, matching the learner dashboard's design language) to all registration and onboarding pages so users always know where they are and what comes next.

---

## Approach: Route-name-aware Blade component (Approach A)

A single `<x-wizard-stepper />` Blade component reads the current route name (`Route::currentRouteName()`) and an existing session flag (`is_parent_registration`) to auto-detect the correct flow and active step. No controller changes. No new session variables. No new routes.

---

## Step Maps

### Learner Flow (regular 13+ registration)

| Step | Label | Route name | URL |
|---|---|---|---|
| 1 | Create Account | `register` | `/register` |
| 2 | Verify Email | `verification.notice` | `/verify-email` |
| 3 | Complete Profile | `profile.complete` | `/profile/complete` |

### Parent-Child Flow (triggered when child is under 13)

| Step | Label | Route name | URL |
|---|---|---|---|
| 1 | Parent Required | `parent.registration.required` | `/parent-registration-required` |
| 2 | Parent Registers | `parent.register` | `/parent/register` |
| 3 | Verify Email | `verification.notice` | `/verify-email` |
| 4 | Complete Profile | `profile.complete` | `/profile/complete` |
| 5 | Create Child Account | `parent.create-child` | `/parent/create-child` |

**Disambiguation:** Steps 3 and 4 of the parent-child flow share routes with steps 2 and 3 of the learner flow (`verification.notice`, `profile.complete`). The component checks `session('is_parent_registration')` — truthy = parent flow, falsy = learner flow.

---

## Component Architecture

### Files created

| File | Purpose |
|---|---|
| `app/View/Components/WizardStepper.php` | PHP class — detects flow & active step |
| `resources/views/components/wizard-stepper.blade.php` | Blade template — renders the UI |

### PHP class logic (`WizardStepper.php`)

1. `Route::currentRouteName()` → get active route
2. `session('is_parent_registration')` → pick flow (`learner` or `parent`)
3. Look up the active step index in the flow's step map
4. Build `$steps` array: each item has `label`, `isActive`, `isCompleted`, `isUpcoming`
5. Pass `$steps` to the Blade template

### No changes needed to

- Any controller
- Any route
- Any layout file
- Any session variable

### Views modified (stepper injected above form card)

**Learner flow:**
- `resources/views/auth/register.blade.php`
- `resources/views/auth/verify-email.blade.php`
- `resources/views/profile/complete.blade.php`

**Parent-child flow:**
- `resources/views/auth/parent-registration-required.blade.php`
- `resources/views/auth/parent-register.blade.php`
- `resources/views/auth/verify-email.blade.php` *(same file, shared step)*
- `resources/views/profile/complete.blade.php` *(same file, shared step)*
- `resources/views/auth/create-child-account.blade.php`

---

## Visual Specification

Design language matches the learner dashboard exactly (purple-indigo palette, same gradients and border styles).

### Step circle states

| State | Background | Number/Icon | Border/Ring |
|---|---|---|---|
| **Completed** | `linear-gradient(135deg, #A30EB2, #3B0CB1)` | White checkmark SVG | None |
| **Active** | `bg-white` | Brand purple bold number | `ring-2 ring-purple-400 shadow-sm` |
| **Upcoming** | `bg-white` | `text-gray-400` number | `border border-gray-200` |

- Circle size: `w-8 h-8` (32×32px), `rounded-full`

### Connector line

- `h-0.5` horizontal bar between circles
- Segment before active step: `bg-gradient-to-r from-purple-600 to-indigo-700` (brand purple)
- Segment after active step: `bg-gray-200`

### Labels

| State | Style |
|---|---|
| Active | `text-purple-700 font-semibold text-xs` |
| Completed | `text-purple-500 font-medium text-xs` |
| Upcoming | `text-gray-400 font-medium text-xs` |

### Container

```
bg-white rounded-2xl border border-purple-100/60 shadow-sm px-6 py-4 max-w-lg mx-auto mb-6
```

Matches the card style used in dashboard section headers (`border-purple-100/60`, `rounded-2xl`, `shadow-sm`).

---

## What Is NOT in scope

- No animation or transition effects
- No JavaScript — purely server-rendered
- No changes to regular learner flow layout beyond inserting the component
- No back-navigation logic added to existing pages
- No changes to how session data is stored or cleared

---

## Testing

Manual testing only (no automated tests for UI components):

1. Register as a 14-year-old → verify stepper shows 3 steps, step 1 active on `/register`
2. Submit registration → `/verify-email` shows step 2 active, step 1 completed
3. Complete profile → `/profile/complete` shows step 3 active, steps 1–2 completed
4. Register as an under-13 child → verify stepper does NOT appear on `/register` (no stepper on child's initial form)
5. Land on `/parent-registration-required` → stepper shows 5 steps, step 1 active
6. Click "Register as Parent" → `/parent/register` shows step 2 active
7. Submit parent registration → `/verify-email` shows step 3 active (parent flow, 5 steps visible)
8. Verify email → `/profile/complete` shows step 4 active (parent flow)
9. Submit profile → `/parent/create-child` shows step 5 active, all previous completed
