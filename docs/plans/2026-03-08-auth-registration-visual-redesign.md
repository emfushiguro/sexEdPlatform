# Auth & Registration Visual Redesign — Design Document

**Date:** 2026-03-08
**Status:** Approved
**Approach:** A — Unified `x-auth-split-layout` for all auth/registration/wizard pages

---

## 1. Overview

Unify the visual language across every auth and registration page so the experience is cohesive from first click to first lesson. The platform brand gradient (`#A30EB2 → #730DB1 → #3B0CB1`) already powers the learner dashboard sidebar and the `x-wizard-stepper` component — this redesign extends it to all pages in both registration paths.

**Two registration paths exist:**

**Path A — Age ≥ 13 (learner):**
`/register` → `/register/account` → `/verify-email` → `/profile/complete` → Dashboard

**Path B — Age < 13 (child via parent):**
`/register` → `/parent-registration-required` → `/parent/register` → `/parent/create-child` → Parent Dashboard

---

## 2. Approach

**Approach A — Unified `x-auth-split-layout` upgrade**

- Update the `auth-split-layout` Blade component to support a 3-stop brand gradient via a new `gradientMid` prop and a `$panel` named slot for right-panel content.
- All 8 pages use this single component — no new layout files created.
- All form logic, Alpine.js data, field names, and POST routes are untouched.

---

## 3. Component: `auth-split-layout` Changes

### New props
| Prop | Type | Default | Purpose |
|---|---|---|---|
| `gradientMid` | `string` | `#730DB1` | Middle stop of the 3-stop brand gradient |
| `gradientFrom` | `string` | `#A30EB2` | Updated from old default `#6D2994` |
| `gradientTo` | `string` | `#3B0CB1` | Updated from old default `#3C1255` |

### New `$panel` named slot
When provided by the calling view, replaces the existing logo+brandText right panel content. When absent, falls back to the current logo+brandText behavior (backward-compatible).

### Right panel shell structure (used by `$panel`)
```
[decorative blur circles — kept as atmosphere]
[logo top-left — small, 40×40]
[centered content area]
  [Heroicon outline SVG — white, 64×64, inside w-24 h-24 white/10 rounded-full]
  [bold white headline — text-3xl font-bold]
  [sub-text — text-white/70 text-sm, max-w-[200px] centered]
```

---

## 4. Color Tokens

| Token | Value | Applied to |
|---|---|---|
| Brand gradient start | `#A30EB2` | Right panel, all wizard steps |
| Brand gradient mid | `#730DB1` | Right panel middle stop |
| Brand gradient end | `#3B0CB1` | Right panel end stop |
| Active nav pill | `bg-white/20` | `x-wizard-stepper` (unchanged) |
| Focus ring | `focus:ring-purple-500` or `focus:ring-brand-purple-primary` | All form inputs |

---

## 5. Right Panel Content Per Page

Each panel: logo (top-left), centered Heroicon (white, outline, 64px inside 96px frosted circle), bold white headline, one short white/70 sub-line. No bullets.

| Page | Route | Heroicon | Headline | Sub-text |
|---|---|---|---|---|
| Learner Login | `/login` | `academic-cap` | "Welcome back" | "Continue your learning journey" |
| Personal Info | `/register` | `academic-cap` | "Start your learning journey" | "A safe, age-appropriate space to grow" |
| Account Info | `/register/account` | `shield-check` | "Almost there!" | "Create your credentials to protect your account" |
| Verify Email | `/verify-email` | `envelope` | "Check your inbox" | "We sent a verification link to your email address" |
| Parent Required | `/parent-registration-required` | `shield-exclamation` | "Safe learning for young ones" | "Children under 13 need a parent or guardian to get started" |
| Parent Register | `/parent/register` | `user-group` | "Guide their journey" | "Create a parent account to support your child's learning" |
| Complete Profile | `/profile/complete` | `identification` | "One last step!" | "Help us personalize your learning experience" |
| Create Child | `/parent/create-child` | `star` | "Set up their account" | "Age-appropriate content, curated just for them" |

---

## 6. Tab Visibility Rules

| Page | `showTabs` | `loginRoute` | `registerRoute` |
|---|---|---|---|
| Learner Login | `true` | `/login` | `/register` |
| Personal Info (register) | `true` | `/login` | `/register` |
| Account Info | `true` | `/login` | `/register` |
| Verify Email | `false` | — | — |
| Parent Required | `false` | — | — |
| Parent Register | `false` | — | — |
| Complete Profile | `false` | — | — |
| Create Child | `false` | — | — |

---

## 7. Files Modified

| File | Change type |
|---|---|
| `resources/views/components/auth-split-layout.blade.php` | Add `gradientMid` prop, update gradient defaults, add `$panel` named slot |
| `resources/views/auth/learner-login.blade.php` | Add `$panel` slot content |
| `resources/views/auth/register.blade.php` | Full rebuild into `x-auth-split-layout` |
| `resources/views/auth/register-account.blade.php` | Full rebuild into `x-auth-split-layout` |
| `resources/views/auth/verify-email.blade.php` | Swap `x-guest-layout` → `x-auth-split-layout` |
| `resources/views/auth/parent-registration-required.blade.php` | Swap `x-guest-layout` → `x-auth-split-layout` |
| `resources/views/auth/parent-register.blade.php` | Full rebuild into `x-auth-split-layout` |
| `resources/views/profile/complete.blade.php` | Swap `x-app-layout` → `x-auth-split-layout` |
| `resources/views/auth/create-child-account.blade.php` | Full rebuild into `x-auth-split-layout` |

---

## 8. Constraints

- No new Blade components, no new layout files
- No changes to form field names, validation, Alpine.js logic, or POST routes
- No new npm packages
- `x-guest-layout` and `layouts/app.blade.php` are not modified
- `x-wizard-stepper` is placed inside the form (left) panel, not the right panel
