# Learner Dashboard Visual Enhancement — Design Document

**Date:** 2026-03-07  
**Status:** Approved  
**Approach:** A + B hybrid — "Polished & Inviting"

---

## Overview

Enhance the existing learner dashboard visually without changing any layout or wireframe. The goal is a single unified style that works across all age brackets (kids, teens, adults). Focus areas: Heroicon replacement of emojis/plain text glyphs, icon pill containers for context color, hover interactions on cards, section background tints, XP bar glow, stat chips in the gamification panel, and enhanced nav-link pills.

No new files. No layout changes. No JS changes. All effects are pure Tailwind utility classes + inline SVG Heroicons (outline unless noted).

---

## Files Affected

| File | Change scope |
|---|---|
| `resources/views/components/learner/gamification-panel.blade.php` | Stat chips, icon containers, XP bar, achievement icons, quiz row |
| `resources/views/components/learner/module-card-active.blade.php` | Group hover, thumbnail scale, ring, icon upgrades, button micro-press |
| `resources/views/components/learner/module-card-recommended.blade.php` | Same card hover pattern, icon upgrades, button micro-press |
| `resources/views/learner/dashboard.blade.php` | Section wrappers, header left-border accent, pill nav links |

---

## Section 1 — Gamification Panel

### Stat chips (2×2 grid)
Each stat becomes a chip: icon container on left, number + label stacked on right.

| Stat | Heroicon | Container |
|---|---|---|
| Enrolled Modules | `academic-cap` outline | `bg-purple-100 text-purple-600 dark:bg-purple-900/40 dark:text-purple-400` |
| Current Level | `shield-check` outline | `bg-indigo-100 text-indigo-600 dark:bg-indigo-900/40 dark:text-indigo-400` |
| Total Points | `star` outline | `bg-amber-100 text-amber-600 dark:bg-amber-900/40 dark:text-amber-400` |
| Day Streak | `fire` outline | `bg-orange-100 text-orange-600 dark:bg-orange-900/40 dark:text-orange-400` |

Chip structure:
```html
<div class="flex items-center gap-3 bg-gray-50 dark:bg-gray-700/50 rounded-xl p-3">
  <div class="w-9 h-9 rounded-lg flex items-center justify-center flex-shrink-0 [icon container classes]">
    <!-- Heroicon w-5 h-5 -->
  </div>
  <div>
    <div class="text-xl font-bold text-gray-900 dark:text-white">{{ value }}</div>
    <div class="text-[11px] text-gray-500 dark:text-gray-400 leading-tight">Label</div>
  </div>
</div>
```

### XP progress bar
- Height: `h-2` (up from `h-1.5`)
- Filled bar: add `shadow-[0_0_8px_rgba(163,14,178,0.35)]`

### Quiz attempts row
- Icon moves into `w-8 h-8 rounded-lg bg-purple-100 dark:bg-purple-900/40` container
- Icon: `clipboard-document-check` outline, `w-4 h-4 text-purple-600`
- Row background: `bg-purple-50 dark:bg-purple-900/20` (unchanged)

### Recent achievements
- Fallback icon: `trophy` outline inside `bg-amber-100 dark:bg-amber-900/40` bubble (replaces emoji fallback `🏆`)
- Bubble size: `w-9 h-9` (unchanged)
- Each real achievement icon remains emoji-agnostic; if `$achievement->icon` is null, render Heroicon trophy

### Typography
- Stat labels: `text-[11px]` (up from `text-xs` = `text-[12px]`)
- Stat numbers: `text-xl font-bold` (unchanged)
- XP label "Level X": `text-sm font-semibold`
- XP counter "50/100 XP": `text-xs text-gray-400`

---

## Section 2 — Module Cards

### Shared hover pattern (both card types)
- Root `<div>`: add `group` class
- Root `<div>` hover classes: `hover:ring-2 hover:ring-purple-200 dark:hover:ring-purple-700 hover:shadow-lg hover:-translate-y-0.5 transition-all duration-200`
- Thumbnail `<img>`: add `transition-transform duration-300 group-hover:scale-105`

### Active card icon upgrades
- Thumbnail fallback placeholder: `book-open` outline `w-10 h-10 text-purple-400` inside `w-16 h-16 bg-purple-100 dark:bg-purple-900/40 rounded-xl flex items-center justify-center`
- "✓ Completed" badge: replace `✓` text glyph with `check-circle` solid `w-3 h-3` inline SVG
- Lessons meta: prepend `book-open` outline `w-3 h-3 text-purple-400` icon
- CTA button: add `hover:scale-[1.02] active:scale-[0.98] transition-transform`

### Recommended card icon upgrades
- Thumbnail fallback: same `book-open` pattern as active card
- Lesson count icon: replace existing inline book SVG with standard Heroicon `book-open` outline `w-3 h-3`
- Duration icon: replace existing clock SVG with Heroicon `clock` outline `w-3 h-3`
- Enroll/View button: add `hover:scale-[1.02] active:scale-[0.98] transition-transform`

---

## Section 3 — Dashboard (`dashboard.blade.php`)

### Section wrappers
**Active Learning Modules section:**
```html
<section class="bg-purple-50/40 dark:bg-purple-900/10 rounded-2xl p-5 border border-purple-100/60 dark:border-purple-800/30">
```

**Recommended For You section:**
```html
<section class="bg-indigo-50/30 dark:bg-indigo-900/10 rounded-2xl p-5 border border-indigo-100/50 dark:border-indigo-800/30">
```

### Section header left-border accent
The inner title `<div>` (containing h2 + subtitle p) gets:
- Active Modules: `border-l-4 border-purple-400 pl-3`
- Recommended: `border-l-4 border-indigo-400 pl-3`

### Greeting
- `text-2xl font-bold tracking-tight` (add `tracking-tight`)
- Subtitle: change `text-gray-500` → `text-gray-400 dark:text-gray-500`

### "View All" and "Browse All" pill links
Both links replace plain text + `&rarr;` with pill buttons containing a `arrow-right` Heroicon:

**View All (Active Modules — purple):**
```html
<a href="..." class="group inline-flex items-center gap-1.5 text-xs font-semibold px-3 py-1.5 rounded-full bg-purple-100 text-purple-700 hover:bg-purple-200 transition-colors duration-150 dark:bg-purple-900/40 dark:text-purple-300 dark:hover:bg-purple-800/50">
  View All
  <!-- arrow-right w-3.5 h-3.5 group-hover:translate-x-0.5 transition-transform -->
</a>
```

**Browse All (Recommended — indigo):**
```html
<a href="..." class="group inline-flex items-center gap-1.5 text-xs font-semibold px-3 py-1.5 rounded-full bg-indigo-100 text-indigo-700 hover:bg-indigo-200 transition-colors duration-150 dark:bg-indigo-900/40 dark:text-indigo-300 dark:hover:bg-indigo-800/50">
  Browse All
  <!-- arrow-right w-3.5 h-3.5 group-hover:translate-x-0.5 transition-transform -->
</a>
```

---

## Design Principles Applied

- **One unified visual style** — same icon family (Heroicons outline), same interaction pattern, same color vocabulary across all age brackets
- **No complex animations** — all transitions are `duration-150` to `duration-300`, transform-based only (translate, scale), no keyframe animations
- **No emojis** — all fallback glyphs replaced with Heroicons
- **No layout changes** — grid, column, spacing structure is untouched
- **Accessibility** — color is never the sole indicator; icons are supplementary to text labels; contrast ratios maintained
- **Dark mode** — all new classes include dark: variants
