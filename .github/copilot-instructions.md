# Copilot Instructions — Sex Education Platform

## Project Vision

A Philippine-focused **Sex Education Learning Platform** called **Concious Connections**. It delivers age-appropriate sexual health education to learners (kids, teens, adults) through structured modules, quizzes, and gamification. Three roles drive the platform: **Admin** (system manager, content creator), **Instructor** (content creator), and **Learner** (student).

---

## Project Stack

| Layer | Technology |
|---|---|
| Backend | Laravel 12, PHP 8.2 |
| Frontend | Blade templates, Alpine.js, Tailwind CSS v3 |
| Build | Vite + laravel-vite-plugin |
| Auth / Roles | Spatie Laravel Permission — single `web` guard, all roles share `users` table |
| Rich Text Editor | TinyMCE (with image library upload) |
| Payments | Paymongo (webhook at `POST /webhook/paymongo`) |
| Geography | `schoolees/laravel-psgc` — region → province → city → barangay |
| Flash Notifications | Toastify JS (CDN) — green = success, red = error, blue = info |
| Testing | PHPUnit (`php artisan test`) |
| Fonts | Poppins (Google Fonts) |

**Auth note:** There is only one guard (`web`). Admin, Instructor, and Learner all authenticate against the same `users` table, differentiated by Spatie roles. Three separate login controllers handle each role's login page and post-login redirect:
- `AdminAuthController` → `/admin/login`
- `InstructorAuthController` → `/instructor/login`
- `AuthenticatedSessionController` → `/login` (learners)

---

## Architecture Notes (Read Before Writing Any Code)

### Services layer exists — use it
`app/Services/` **exists** with the following services already in place:
- `GamificationService` — award/spend points, update streaks
- `SubscriptionService` — subscription lifecycle management
- `SubscriptionDunningService` — handles overdue/failed billing
- `InvoiceService` — invoice generation
- `RefundService` — refund processing via Paymongo
- `ParentChildService` — parent-child account logic
- `PayMongoPaymentLinkService` — Paymongo payment link creation
- `AnalyticsService` — platform-wide analytics queries

`app/Actions/` still does not exist. Keep controllers thin — put business logic in `app/Services/` classes.

### Server-rendered only
No REST API, no SPA. Everything is Blade + Alpine.js. The only API-style routes are the PSGC location lookup endpoints (`/api/cities/{provinceCode}`, `/api/barangays/{cityCode}`) for form dropdowns.

### Age bracket system
Learners have a profile with an `age_bracket` field. Modules are targeted to specific age brackets. Always filter module queries by the learner's age bracket — do not show all modules to all learners.

---

## What Is Already Built

### Authentication & User Management
- Three-path login split: Admin, Instructor, Learner (each has dedicated login page + controller)
- Learner registration (`RegisteredUserController`) + email verification, including a **wizard-stepper** multi-step registration flow
- Password reset flow
- Parent-child account system: `ParentChildAccount` model, `ParentRegistrationController`, `ParentChildService`, `ParentController` — functional, with monitoring views under `/parent`
- Profile completion gate: Learners must complete their profile before accessing the platform (`ProfileCompletionController`, `profile.completed` middleware)
- Learner profile: display name, username (rate-limited change for free users), avatar, age bracket, PSGC location, date of birth
- Account deletion, password change via `ProfileCompletionController`

### Instructor Panel (`/instructor`)
- **Dashboard:** Real stat cards (total learners, modules published/total, quizzes, pending enrollments, enrolled learners) via `Instructor\DashboardController`
- **Modules:** Full CRUD, thumbnail upload, enrollment mode (open/approval/pending-parent-approval), premium flag, age bracket targeting, final quiz assignment, `created_by` tracking
- **Lessons:** Full CRUD, rich content support (video embed, TinyMCE, PDF), ordering/reordering
- **Lesson Topics:** Sub-units within lessons, TinyMCE content, worksheet file support
- **Quizzes:** Full CRUD, question management (multiple choice, true/false, short answer, matching, ordering, fill-in-the-blank), CSV bulk import
- **Enrollments:** View, approve, and reject learner enrollment requests
- **Image Library:** Upload/manage/delete images for use in TinyMCE editor
- **User management:** View and manage learner accounts
- **Search:** Live AJAX search (`Instructor\SearchController`)

### Learner Features (`/learn`)
- **Dashboard:** Redesigned, uses `@extends('layouts.learner-app')` — hero banner with greeting, age bracket badge, gamification bar component, active module cards, recommended modules, streak card, mini calendar
- Module browsing: age-bracket-filtered catalog, enrollment flow (`Learner\ModuleController`)
- Lesson viewing + completion: progress tracked per user (`Learner\LessonController`)
- Topic-level completion: fine-grained progress via `LessonTopicProgress`
- **Gamification (partial UI built):**
  - `GamificationService` handles all XP/points/streak logic
  - `UserGamification` model: level, score (spendable XP), total_points (lifetime), streak_count, longest_streak, streak_savers
  - `UserDailyShield` model (renamed from `QuizDailyLimit`) — shields consumed on quiz attempts
  - `ShieldRefillController` — spend points to refill shields
  - `StreakSaverController` — buy streak savers with points
  - `<x-gamification-bar>` component — displays XP, level, streak in header
  - `<x-learner.gamification-panel>`, `<x-learner.streak-card>`, `<x-learner.out-of-shields-modal>` components
  - Gamification rules page at `/learn/gamification`
  - Achievement/badge display UI: **not yet built**
- **Certificates (working):** `Learner\CertificateController` — index, show, PDF download (premium only), public verification at `/certificates/verify`
- **Notifications:** `Learner\NotificationController` — mark-read, mark-all-read; DB notifications via Laravel's notifications table
- **Search:** Live AJAX search (`Learner\SearchController`)

### Subscription & Payment
- `Learner\SubscriptionController`: view plans, upgrade, cancel, refund request, renew, status check
- `PaymentController`: Paymongo payment link creation, success/failure callbacks, payment history, receipt
- `PayMongoPaymentLinkService` + `SubscriptionService` + `InvoiceService` + `RefundService` back-end logic
- `SubscriptionDunningService` — handles expired/overdue subscriptions
- `Invoice` + `Refund` models with migrations
- `CheckPremiumStatus` middleware (alias: `premium`) — guards premium-only routes
- Free vs. premium: premium unlocks certificate downloads, unlimited quiz attempts, module attachment downloads
- Views: `subscriptions/index`, `subscriptions/upgrade`, `payments/create`, `payments/pending`, `payments/success`, `payments/cancel`, `payments/receipt`, `payments/history`

### Quizzes (Learner-facing)
- `QuizController`: start, submit, result, attempt history
- Daily attempt gates via `UserDailyShield` (shields consumed per attempt; **renamed from `QuizDailyLimit`**)
- Points-based shield refill via `ShieldRefillController`
- Streak savers purchasable via `StreakSaverController`

### Admin Panel (`/admin`)
- **Dashboard:** Live stat cards — total users, instructors, total modules (data pulled in Blade directly)
- **User Management:** Full CRUD via `UserAdminController` — create, edit, show, delete users; filter by role
- **Subscriber Management:** `SubscriberAdminController` — list all subscribers with quick-action (cancel/activate), detail view with subscription history; `UnifiedSubscriptionAdminController` — create/edit/manage plans from the same UI
- **Subscription Plans:** `SubscriptionPlanAdminController` — full CRUD (create, edit, show, delete, toggle active/inactive, reorder)
- **Payment Management:** `PaymentAdminController` — list payments, view detail, process refund, mark as completed
- **Calendar:** Stub view at `/admin/calendar`
- **Seminars:** Stub views (index, create, show) — no controller logic yet
- **Organizations:** Stub views (index, show) — no controller logic yet
- **Messages:** Stub view at `/admin/messages`
- **Email Announcements:** Stub views (index, compose)

---

## What Is NOT Yet Built

| Feature | Status |
|---|---|
| **Gamification — Achievement/Badge UI** | Service + models exist; achievement display, badge unlocks, and reward history UI not built |
| **Seminars** | Admin stub views exist; no `SeminarController`, no learner-facing pages |
| **Organizations** | Admin stub views exist; no controller logic |
| **Health Centers & Clinics** | Models and migrations only — no views or controllers |
| **Counselors** | Models and migrations only — no views or controllers |
| **Consultations** | Models and migrations only — no views or controllers |
| **Admin Analytics (deep)** | Dashboard has basic counts; no charts, revenue graphs, or drill-down analytics |
| **app/Actions layer** | Not started — `app/Services/` exists; `app/Actions/` does not |

## UI Architecture

### Layouts
- `layouts/learner-app.blade.php` — main learner layout (sidebar + header + gamification bar)
- `layouts/learner-fullscreen.blade.php` — full-screen layout for quizzes/lessons
- `layouts/instructor-app.blade.php` — instructor panel layout
- `layouts/admin.blade.php` — admin panel layout
- `layouts/guest.blade.php` — auth/registration pages

### Blade Component Libraries
**Generic UI components** (`resources/views/components/ui/`):
`alert`, `badge`, `button`, `card`, `empty-state`, `progress-bar`, `skeleton`, `spinner`

**Learner-specific components** (`resources/views/components/learner/`):
`gamification-panel`, `mini-calendar`, `module-card-active`, `module-card-recommended`, `out-of-shields-modal`, `streak-card`

**Other top-level components:** `gamification-bar`, `wizard-stepper`, `breadcrumb`, `modal`, `dropdown`, `legal-modals`

---

## Key Reference Docs

- `PLATFORM_FEATURES_OVERVIEW.md` — full feature list by role
- `ADMIN_DEVELOPMENT_GUIDE.md` — admin panel spec and DB schema
- `FUTURE_IMPROVEMENTS.md` — planned enhancements
- `QUICK_TESTING_GUIDE.md` — how to test features manually
- `CSV_IMPORT_GUIDE.md` — quiz CSV bulk import format

## Current Workspace Progress (As of 2026-03-15)

- Active feature branch: `feat/admin-panel-integration`
- Latest implemented milestone: instructor dashboard phase 1 component refresh (see commit `cdf496d`)
- Recent learner-side stabilization completed: enrollment status checks, gamification dependency wiring, shield-related fixes, and subscription/payment integration updates
- Latest planning artifact added: `docs/plans/2026-03-15-lesson-viewer-enhancement-design.md`
- Current workflow source of truth: `!skills/MY-WORKFLOW.md` (3-stage flow)

---

## Skills-Based Workflow

This project uses the **Superpowers skills system** for structured AI-assisted development. The skills folder is at `!skills/` in the workspace root.

**Read `!skills/MY-WORKFLOW.md` for the complete 3-stage development flow (Brainstorming -> Writing Plans -> Executing Plans).**

Quick reference:

| Situation | Skill to attach |
|---|---|
| New feature / change idea | `!skills/brainstorming/SKILL.md` |
| Approved design, need a plan | `!skills/writing-plans/SKILL.md` |
| Implementing the approved plan | `!skills/executing-plans/SKILL.md` |
| Writing code with strict red-green-refactor (optional mode) | `!skills/test-driven-development/SKILL.md` |
| Something is broken | `!skills/systematic-debugging/SKILL.md` |
| About to say "done" | `!skills/verification-before-completion/SKILL.md` |
| Feature complete, need review | `!skills/requesting-code-review/SKILL.md` |
| Got review feedback | `!skills/receiving-code-review/SKILL.md` |
| Ready to merge or PR | `!skills/finishing-a-development-branch/SKILL.md` |
| Multiple independent bugs | `!skills/dispatching-parallel-agents/SKILL.md` |

---

## Workflow Rules

### Before writing ANY code
Ask clarifying questions to understand what is being built. Propose 2-3 approaches with trade-offs. Get approval on the design before touching any file.

### When implementing
Write the failing test FIRST using PHPUnit. Confirm it fails (`php artisan test --filter=TestName`). Then write the minimal code to make it pass. Never write production code before a failing test exists.

### When debugging
Find the root cause BEFORE proposing any fix. Read the full stack trace. Reproduce the issue consistently. No guesses, no "try this and see".

### Before claiming anything is done
Run `php artisan test` and show the actual output. Do not say "it should work" — run the tests and show the result.

### Laravel conventions to follow
- Controllers stay thin — business logic goes in `app/Services/` classes
- Use Form Requests for validation, never validate in controllers directly
- Use Eloquent relationships, never raw SQL unless absolutely necessary
- Blade components for reusable UI, not copy-pasted markup
- Route model binding wherever applicable
- Spatie permissions for all role/permission checks — never hand-roll authorization

### When receiving feedback on code
Restate what was understood from each point. Verify against the actual codebase. Implement one item at a time. Do not blindly agree and implement everything at once.

### Code style
- PHP: PSR-12
- Blade: kebab-case component names, no logic in templates
- Tailwind: utility classes only, no custom CSS unless Tailwind cannot do it
- Alpine.js: keep `x-data` minimal; extract to a JS file if it grows large
- Brand gradient: `#A30EB2 → #730DB1 → #3B0CB1` (use for primary UI elements)