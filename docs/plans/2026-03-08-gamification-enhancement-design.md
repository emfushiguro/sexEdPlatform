# Gamification Enhancement — Design Document

**Date:** 2026-03-08
**Status:** Approved
**Author:** Brainstorming session

---

## 1. Overview

Enhance the existing gamification system with a fully implemented shield system (replacing per-quiz daily limits), a complete point economy with earning and spending rules, streak tracking with milestone rewards and streak savers, and rich gamification UI including a streak card, modals, a gamification rules page, and custom branded toasts.

The theme is **"Knowledge is your shield."** Shields represent the learner's active engagement protection — draining on failed quizzes, replenishable with earned points, and resetting daily. This metaphor aligns naturally with the sex education platform's mission: knowledge protects you.

---

## 2. Data Layer

### 2.1 Rename `quiz_daily_limits` → `user_daily_shields`

A new migration renames and restructures the existing table:

- **Table renamed:** `quiz_daily_limits` → `user_daily_shields`
- **Column dropped:** `quiz_id` (and its foreign key) — no more per-quiz tracking
- **Column renamed:** `attempts` → `shields_remaining`
- **Default changed:** `shields_remaining` defaults to `3`
- **Unique constraint:** changed from `(user_id, quiz_id, date)` → `(user_id, date)` — one row per learner per day

**Row lifecycle:** Created on first quiz interaction of the day with `shields_remaining = 3`. Each failed quiz decrements by 1. Reaching 0 blocks quiz access. Resets daily (new row next day).

### 2.2 `UserDailyShield` Model (replaces `QuizDailyLimit`)

File: `app/Models/UserDailyShield.php`

Key methods:
- `getShields(User $user): int` — returns today's shields (creates row at 3 on first call)
- `drainShield(User $user): void` — decrements by 1, floor at 0
- `refillOne(User $user): void` — increments by 1, ceiling at 3
- `refillFull(User $user): void` — sets to 3
- Premium bypass: users with `isPremium()` always return `PHP_INT_MAX`, no drain applied

### 2.3 `user_gamification` Table Additions

Two new columns via migration (one already has a migration stub at `2026_03_07_050903`):

| Column | Type | Default | Purpose |
|---|---|---|---|
| `longest_streak` | integer | 0 | All-time highest `streak_count` reached |
| `streak_savers` | integer | 0 | Held streak savers (max 3) |

`longest_streak` is updated inside `UserGamification::updateStreak()` whenever `streak_count` exceeds the stored value.

`streak_savers` is consumed inside `updateStreak()` before a streak resets — if `streak_savers > 0` and the learner missed a day, consume one saver and preserve the streak instead of resetting.

---

## 3. Point System

### 3.1 Earning Points

| Action | Points | Trigger |
|---|---|---|
| Complete a lesson topic | +10 | `LessonTopicProgress` record created |
| Complete a lesson (all topics done) | +15 | All topics in lesson marked complete |
| Pass a quiz (≥70%) | +25 | `QuizController::submit()` — existing |
| Perfect quiz score (100%) | +30 | `QuizController::submit()` — existing |
| Fail a quiz | +5 | `QuizController::submit()` — existing (participation) |
| Complete a module (all lessons done) | +100 | `QuizController::submit()` — existing |
| 7-day streak milestone | +50 | `streak_count % 7 === 0` |
| 30-day streak milestone | +200 | `streak_count % 30 === 0` (takes priority over 7-day) |

### 3.2 Spending Points

| Action | Cost | Result |
|---|---|---|
| Refill +1 shield | 50 pts | `shields_remaining += 1` (max 3) |
| Refill all shields (full) | 100 pts | `shields_remaining = 3` |
| Buy streak saver | 75 pts | `streak_savers += 1` (max 3 held) |

**Balance rule:** Points are deducted from `score` (spendable balance). `total_points` is a lifetime running total — **never decremented**. This preserves level/leaderboard integrity while allowing meaningful point spending.

### 3.3 Level Progression

Unchanged: `level = floor(score / 100) + 1`. No changes to existing formula.

---

## 4. `GamificationService` — `app/Services/GamificationService.php`

All point award and spend logic extracted from controllers into a single service. Controllers call this — no business logic in Blade, no duplicated logic across controllers.

### Methods

```php
public function awardPoints(User $user, string $reason, int $points): void
// Increments score + total_points, updates level, fires streak check if reason = 'topic_complete'

public function spendPoints(User $user, int $points): bool
// Returns false if score < $points. Decrements score only (not total_points).

public function updateStreak(User $user): void
// Wraps UserGamification::updateStreak() — adds longest_streak update and milestone check

public function checkStreakMilestone(User $user): ?int
// Returns bonus points if streak_count is a multiple of 7 or 30, else null
// 30-day takes priority over 7-day (not additive)

public function consumeStreakSaver(User $user): bool
// Consumes one saver if available, returns true if consumed
```

### Integration Points

- `Learner\LessonController` (or `LessonTopicProgress` observer) → `awardPoints(..., 'topic_complete', 10)` + `updateStreak()`
- `Learner\LessonController` → `awardPoints(..., 'lesson_complete', 15)` when all topics done
- `QuizController::submit()` → existing point logic migrated into `awardPoints()`
- `ShieldRefillController` → `spendPoints()` + `UserDailyShield::refillOne/Full()`
- `StreakSaverController` → `spendPoints()` + increment `streak_savers`

---

## 5. Shield System

### 5.1 Shield SVG Icon

A custom shield SVG stored as a Blade component: `resources/views/components/icons/shield.blade.php`

Three visual states via props:
- **`full`** — solid brand gradient fill (`#A30EB2 → #3B0CB1`), white inner highlight
- **`empty`** — gray fill (`#D1D5DB`), 40% opacity, slightly muted
- **`broken`** — cracked/split shield path, gray, used in 0-shields failure states

Used in: gamification bar, quiz start page, out-of-shields modal, streak card streak savers display.

### 5.2 In the Gamification Bar

The existing `<x-gamification-bar>` component replaces the "quiz attempts" indicator with shield icons:

- 3 shield SVGs rendered side by side — filled or empty based on `shields_remaining`
- Clicking opens the "How Shields Work" info modal
- When `shields_remaining === 0`: all shields pulsing gray with a red ring animation

### 5.3 Quiz Gate

**Server-side:** `QuizController::start()` checks `UserDailyShield::getShields($user)`. If `0` and not premium → redirect back with error flash, out-of-shields modal triggered via session flash.

**Client-side:** Alpine.js disables the quiz submit button and shows an inline warning banner when shields are 0 (data passed from controller to view).

**Scope:** Blocks both lesson quizzes and module final quizzes.

**Premium bypass:** `isPremium()` check in `UserDailyShield` returns `PHP_INT_MAX` — gate never triggers.

### 5.4 Shield Drain Trigger

In `QuizController::submit()` — after scoring: if learner **failed** (score < 70%) and is **not premium**:
1. Call `UserDailyShield::drainShield($user)`
2. Get remaining count
3. Flash session: shields remaining (for toast on redirect)
4. If shields reach 0, flash out-of-shields flag (triggers modal on result page)

---

## 6. Modals

### 6.1 "How Shields Work" Info Modal

Triggered by clicking the shield indicator in the gamification bar. Alpine.js `x-show` modal, no page reload.

**Left panel — Shields explanation**
- Large shield SVG (full state)
- `"You have 3 shields per day. Each failed quiz drains 1 shield."`
- `"Shields reset every midnight."`
- Current shields remaining display (filled/empty icons)

**Right panel — How to Earn Points**
Full annotated points table (all actions from Section 3.1), styled like the Sololearn "Your bits" panel with the platform's purple brand.

### 6.2 Out-of-Shields Modal

Displays after a failing quiz submission that drains the last shield, or when attempting to start a quiz with 0 shields. Session flash flag triggers Alpine.js modal on page load.

**Left panel**
- Broken shield SVG (cracked, gray)
- `"You're out of Shields"`
- `"No shields left today. Spend points to keep going, or come back tomorrow."`
- Five empty gray shield dots (current state)
- Current points balance displayed

**Right panel — Two refill options**

| Option | Cost | Button label |
|---|---|---|
| +1 Shield | 50 pts | `"One shield — ⚔ 50"` |
| Full Refill (3 shields) | 100 pts | `"Full refill — ⚔ 100"` |

Each is a POST form to `/learn/shields/refill` with a `type` field (`single` or `full`). Button disabled with tooltip `"Not enough points"` if balance is insufficient.

**Handler:** `ShieldRefillController::store()` — validates balance → `GamificationService::spendPoints()` → `UserDailyShield::refillOne()` or `refillFull()` → redirect back with Toastify flash.

---

## 7. Streak System

### 7.1 Streak Trigger

**Event:** `LessonTopicProgress` record created (learner marks a topic complete).

This is the only action that counts toward streak. Opening a module, viewing a lesson, starting a quiz — none of these count. Completing a topic is the minimum meaningful learning unit.

The `LessonTopicProgress` creation (already in `Learner\LessonController`) calls `GamificationService::updateStreak($user)`.

### 7.2 Streak Rules

| Condition | Result |
|---|---|
| `last_act_at` was yesterday | `streak_count += 1` |
| `last_act_at` is today | No change (already counted) |
| `last_act_at` was 2+ days ago, `streak_savers > 0` | Consume 1 saver, preserve streak, flash "Streak Saved!" message |
| `last_act_at` was 2+ days ago, no savers | `streak_count = 1` (reset) |
| `last_act_at` is null (first ever) | `streak_count = 1` |
| After any update | Compare `streak_count` vs `longest_streak`, update if exceeded |

### 7.3 Streak Milestone Rewards

| Milestone | Bonus | Logic |
|---|---|---|
| 7-day streak | +50 pts | `streak_count % 7 === 0` |
| 30-day streak | +200 pts | `streak_count % 30 === 0` (takes priority — not additive with 7-day) |

Milestone check fires inside `GamificationService::updateStreak()` after incrementing. Bonus is awarded via `awardPoints()`. A session flash stores the milestone toast message for display on next render.

### 7.4 Streak Savers

- Learner can hold **max 3 streak savers** at a time
- **Cost:** 75 points per saver
- **Purchase route:** `POST /learn/streak-savers/buy` → `StreakSaverController::store()`
- **Auto-consumed:** silently on missed day detection in `updateStreak()`, before reset
- **Flash message after auto-consume:** `"Your streak was saved! You have X savers left."` → `showStreakSaved()` toast

---

## 8. Streak Card UI

### 8.1 Component

New Blade component: `resources/views/components/learner/streak-card.blade.php`

Placed in the dashboard right column, stacked **below** `<x-learner.gamification-panel>`. White card, `rounded-2xl`, `shadow-sm`, `border border-gray-100`.

### 8.2 Layout

```
┌─────────────────────────────────────┐
│  🔥 Your Streak                     │
├─────────────────────────────────────┤
│  [S]  [M]  [T]  [W]  [T]  [F]  [S] │
│   ●    ●    ●    ○    ○    ●    ○   │
├─────────────────────────────────────┤
│  ⚡ Current Streak  ⚡ Longest Streak │
│       5 days             12 days    │
├─────────────────────────────────────┤
│  🛡 Streak Savers   [🛡][🛡][░]  2/3  │
│         [Buy Saver — ⭐ 75]          │
└─────────────────────────────────────┘
```

- **Weekly dots:** 7 circles (S M T W T F S). Filled with brand gradient + small shield icon inside = active day. Gray outline = inactive. Today's circle has a white ring border.
- **Active days data:** `$streakActiveDays` — array of day-of-week integers (0=Sun…6=Sat) for the current ISO week, from `LessonTopicProgress` grouped by `DAYOFWEEK(created_at)`.
- **Buy Saver button:** disabled + grayed if `streak_savers >= 3` or `score < 75`. POST form.

### 8.3 Dashboard Controller Additions

`Learner\DashboardController` passes:
- `$streakActiveDays` — array of active day integers this ISO week
- `$longestStreak` — `$gamification->longest_streak ?? 0`
- `$streakSavers` — `$gamification->streak_savers ?? 0`

---

## 9. Gamification Rules Page

### 9.1 Route

```
GET /learn/gamification  →  Learner\GamificationController@rules
```

Named: `learn.gamification`. Linked from gamification bar and streak card.

### 9.2 Sections

**Hero header** — brand gradient background, large shield SVG, `"How ConciousConnections Rewards You"` heading.

**Section A — Your Shields**
- What shields are, how they drain (failed quiz = -1 shield), how they reset (midnight)
- Three shield states illustrated side by side: Full / Partial / Empty
- Refill cost table

**Section B — Earning Points**
Full annotated points table from Section 3.1.

**Section C — Streak Rules**
- What counts (topic completion only)
- How streaks increment / reset
- Streak Savers explained (cost, max 3, auto-consume behavior)
- Milestone rewards table
- `"Missing a day resets your streak — but your Longest Streak is saved forever."`

**Section D — Levels**
- `Level = floor(spendable points / 100) + 1`
- Simple level ladder: levels 1–10 with point thresholds

**Section E — Premium Advantage**
- Unlimited shields (bypasses drain entirely)
- Brief upgrade CTA button

---

## 10. Custom Gamification Toasts

Built on the existing `toast.js` + `toast-custom.css` infrastructure. Four new functions added (no changes to existing functions).

### 10.1 New Toast Functions

| Function | Trigger | Color | Duration | Position |
|---|---|---|---|---|
| `showShieldLost(remaining)` | Failed quiz, shield drained | `#ef4444 → #b91c1c` (red) | 5s | Top-right |
| `showShieldRefilled(type)` | Refill purchase success | `#A30EB2 → #3B0CB1` (brand) | 4s | Top-right |
| `showPointsEarned(points, reason)` | Points awarded | `#6366f1 → #4f46e5` (indigo) | 3s | Top-right, compact |
| `showStreakMilestone(days, bonus)` | 7 or 30-day milestone | `#f59e0b → #d97706` (amber) | 7s | Top-center |
| `showStreakSaved(streak, saversLeft)` | Streak saver auto-consumed | `#A30EB2 → #3B0CB1` (brand) | 6s | Top-center |

### 10.2 Message Templates

- **Shield lost:** `"Shield lost! {remaining} shield(s) remaining today."` — cracked shield SVG icon
- **Shield refilled (single):** `"+1 Shield restored."` — full shield SVG icon
- **Shield refilled (full):** `"Full shield refill! You're back to 3 shields."` — full shield SVG icon
- **Points earned:** `"+{points} pts — {reason}"` — star SVG, compact size
- **Streak milestone (7-day):** `"🔥 7-day streak! +50 points awarded. Keep it up!"` — centered, large
- **Streak milestone (30-day):** `"🏆 30-day streak! +200 points awarded. Incredible!"` — centered, large
- **Streak saved:** `"🛡 Streak Saved! Your {streak}-day streak is protected. {saversLeft} savers left."` — centered

### 10.3 New CSS Classes

Appended to `toast-custom.css`:
- `.toast-shield-lost` — red gradient, 5s
- `.toast-shield-refill` — brand purple gradient
- `.toast-points` — indigo-violet, compact (`min-width: 160px`, `font-size: 13px`)
- `.toast-streak-milestone` — amber gradient, centered, large, reuses `achievementBounce` animation
- `.toast-streak-saved` — brand purple gradient, centered, 6s

### 10.4 Server-Side Flash → JS Toast Bridge

Session flash keys consumed in `learner-app.blade.php` (or a shared `@stack('scripts')` partial) to fire the correct JS toast function on page load. Pattern already used elsewhere in the platform.

---

## 11. Files Changed / Created

### New Files
| File | Purpose |
|---|---|
| `app/Models/UserDailyShield.php` | Replaces `QuizDailyLimit` |
| `app/Services/GamificationService.php` | Centralized point/streak/shield logic |
| `app/Http/Controllers/Learner/ShieldRefillController.php` | Handle shield refill purchases |
| `app/Http/Controllers/Learner/StreakSaverController.php` | Handle streak saver purchases |
| `app/Http/Controllers/Learner/GamificationController.php` | Gamification rules page |
| `resources/views/learner/gamification/rules.blade.php` | Gamification rules page view |
| `resources/views/components/icons/shield.blade.php` | Shield SVG component (3 states) |
| `resources/views/components/learner/streak-card.blade.php` | Streak card dashboard widget |
| `resources/views/components/learner/shields-info-modal.blade.php` | "How Shields Work" modal |
| `resources/views/components/learner/out-of-shields-modal.blade.php` | Out-of-shields refill modal |
| `database/migrations/XXXX_rename_quiz_daily_limits_to_user_daily_shields.php` | Table rename + restructure |
| `database/migrations/XXXX_add_longest_streak_and_streak_savers_to_user_gamification.php` | New columns |

### Modified Files
| File | Change |
|---|---|
| `app/Models/UserGamification.php` | Add `longest_streak` update + streak saver consume logic |
| `app/Models/QuizDailyLimit.php` | Deleted/replaced by `UserDailyShield` |
| `app/Http/Controllers/QuizController.php` | Migrate point logic to `GamificationService`, add shield drain |
| `app/Http/Controllers/Learner/LessonController.php` | Add topic/lesson complete point awards + streak trigger |
| `app/Http/Controllers/Learner/DashboardController.php` | Pass `$streakActiveDays`, `$longestStreak`, `$streakSavers` |
| `resources/js/toast.js` | Add 5 new gamification toast functions |
| `resources/css/toast-custom.css` | Add 5 new toast CSS classes |
| `resources/views/components/learner/gamification-panel.blade.php` | Replace attempts with shields display |
| `resources/views/components/gamification-bar.blade.php` | Replace attempts with shield icons + modal trigger |
| `resources/views/learner/dashboard.blade.php` | Add `<x-learner.streak-card>` below gamification panel |
| `routes/web.php` | Add `/learn/shields/refill`, `/learn/streak-savers/buy`, `/learn/gamification` routes |

---

## 12. What Is Skipped

- **Achievements system** — models and migrations exist; no UI or unlock logic (deferred, no UI designer)
- **Leaderboard** — deferred
- **Admin achievement management** — deferred
