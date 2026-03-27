# Sequential Quiz Wizard — Implementation Design
**Date:** 2026-03-14
**Status:** Approved & Ready for Implementation
**Branch:** feat/admin-panel-integration

---

## Overview

Transform the quiz-taking UX from a single scrollable page (all questions visible at once) to a **sequential card wizard** — one question visible at a time, inspired by Sololearn's clean sequential flow. Simultaneously introduce a **Pass Protection gamification model** for shields, and redesign both the take and result pages to align with the established learner dashboard theme.

---

## User-Confirmed Decisions

| Decision | Choice |
|---|---|
| Back navigation | ✅ Free navigation — learners can revisit any answered question |
| Answer feedback during quiz | Selection highlight only (no right/wrong until result) |
| Shield deduction model | **Approach 3: Pass Protection** — -1 on attempt, +1 refunded on pass, net -1 on fail. Premium = no deduction. |
| Result page theme | Aligned to learner dashboard design language |

---

## Files Changed

1. `resources/views/quizzes/take.blade.php` — Complete rewrite (Alpine.js wizard)
2. `resources/views/quizzes/result.blade.php` — Complete redesign (learner dashboard theme)
3. `app/Http/Controllers/Learner/QuizController.php` — Pass Protection logic in `submit()` + fix `result()`
4. `app/Models/UserDailyShield.php` — Already has `refillOne()` — no changes needed

---

## 1. take.blade.php — Sequential Quiz Wizard

### Architecture
- **Zero backend changes** — existing POST to `quizzes.submit` unchanged
- **Alpine.js step wizard** — all questions pre-rendered in DOM, one shown at a time via `x-show`
- **Layout**: `@extends('layouts.learner-app')` (migrated from `x-app-layout`)

### UI Zones

**Sticky Progress Zone** (top of page):
- Quiz title + module breadcrumb
- Dot progress tracker — filled dot = answered, outline = unanswered, active = brand gradient ring
- Question counter (e.g. "Question 3 of 10")
- Inline countdown timer (if `time_limit` set) — turns orange at 60s, red at 30s
- Shield chip (free users only): `🛡 N/3 — 1 at stake`

**Question Card** (main content):
- Slide-in/out CSS transition between questions (`translate-x`)
- Question number badge (brand gradient circle)
- Question type badge (colored pill)
- Question text — large, readable
- Answer area per type:
  - `multiple_choice` / `true_false` — radio buttons with selection highlight (brand gradient border + bg on select)
  - `multiple_select` — checkboxes with same highlight
  - `fill_blank_text` — inline text inputs embedded in sentence
  - `fill_blank_select` — word bank chips (existing Alpine.js logic preserved)
  - `identification` — optional image + text input

**Navigation Row** (bottom of card):
- **Back** — left arrow button (hidden on Q1)
- **Skip** — ghost button (marks question as skipped)
- **Next** — brand gradient button, **disabled until question is answered**; last question shows "Review"

**Review Screen** (pre-submit):
- Summary grid of all questions with answer status (answered / skipped)
- Each row clickable — jumps back to that question
- Score estimate NOT shown (no spoilers)
- "Submit Quiz" CTA — brand gradient, full width
- Back to editing button

**Timer** (if time_limit):
- Inline in progress zone (not floating)
- Auto-submits the form on expiry
- Shows warning at 60s (orange) and 30s (red pulse animation)

### Answered Detection Logic (Alpine.js)
- `multiple_choice`/`true_false`: radio selected
- `multiple_select`: at least one checkbox checked
- `fill_blank_text`/`identification`: text input non-empty
- `fill_blank_select`: all blanks filled (selectedWords has no null)

---

## 2. QuizController.php — Pass Protection

### submit() Changes

```
Before (old):
  1. Score
  2. Create attempt
  3. drainShield (always, for free users)
  4. Award points

After (Pass Protection):
  1. Score
  2. Create attempt
  3. drainShield (free users, always)
  4. If passed → refillOne (net zero cost on pass)
  5. Award points
  6. Flash shield_delta to session (-1 or 0 for free, null for premium)
```

### result() Changes
- Load `$shieldDelta` from `session('shield_delta')`
- Load `$shieldsRemaining = UserDailyShield::getShields($user)`
- Load `$remainingAttempts = UserDailyShield::getShields($user)` (fixes undefined var bug)
- Pass all three to view

---

## 3. result.blade.php — Redesign

### Layout
- `@extends('layouts.learner-app')` (migrated from `x-app-layout`)
- `@section('title', 'Quiz Result')`

### Visual Hierarchy

**Hero Result Card** (`rounded-2xl bg-white shadow-sm border border-gray-100`):
- Animated radial gradient score ring:
  - Pass: brand gradient (`#A30EB2 → #3B0CB1`) fill
  - Fail: red gradient
  - CSS `@keyframes spin-ring` animation on load
- Score percentage (large, white text inside ring)
- Pass: "You Passed! 🎉" headline in `text-green-600`
- Fail: "Keep Going! 💪" headline in `text-red-500`
- Subtitle: "X of Y questions correct"

**Gamification Delta Row** (horizontal chips):
- Shield chip: `🛡 -1 Shield` (fail, red) / `🛡 ±0 Shield Protected` (pass, green) / hidden for premium
- XP chip: `⭐ +25 XP Earned` (amber badge) / `⭐ +5 XP` for try
- Remaining shields: `🛡 N/3 shields left today` (free only)

**Stats Grid** (3 chips, `bg-gray-50 rounded-xl p-3`):
- Total Questions
- Correct (green icon)
- Incorrect (red icon)

**Action Buttons**:
- "Back to Modules" — outline/ghost style
- "Try Again" — brand gradient (if failed + shields > 0)
- "Daily Limit Reached" — disabled gray (if failed + no shields)
- Premium upsell (if failed + no shields + free user)

**Question Review Section** (`rounded-2xl border border-gray-100`):
- Section header with left-border accent (brand gradient)
- Each question card: `rounded-xl border-2` — green border + `bg-green-50` for correct, red for incorrect
- Correct indicator: brand-gradient circle with ✓
- Incorrect indicator: red circle with ✗
- Options shown with correct answer highlighted in green, wrong selected in red
- Text answer questions show "Your answer" vs "Correct answer" comparison cards

---

## 4. Design System Alignment

All new UI uses these established patterns:

| Element | Pattern |
|---|---|
| Primary brand gradient | `background: linear-gradient(135deg, #A30EB2, #730DB1, #3B0CB1)` |
| Cards | `rounded-2xl bg-white shadow-sm border border-gray-100 dark:border-gray-700` |
| Section backgrounds | `bg-purple-50/40 rounded-2xl p-5 border border-purple-100/60` |
| Stat chips | `bg-gray-50 dark:bg-gray-700/50 rounded-xl p-3` |
| Primary buttons | Brand gradient + `hover:opacity-90 hover:scale-[1.02] active:scale-[0.98]` |
| Typography | Poppins (via body `font-sans`) |
| Spacing | `p-5` cards, `gap-4` chips, `space-y-6` sections |
| Dark mode | All elements have `dark:` variants |

---

## TailAdmin Components Used

- `x-ui.badge` — question type badges and XP/shield chips
- `x-ui.alert` — limit reached / premium upsell notices
- `x-common.component-card` — base card pattern for question review
