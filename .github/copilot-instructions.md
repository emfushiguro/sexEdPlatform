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

### No Services or Actions layer yet — this is tech debt
`app/Services/` and `app/Actions/` **do not exist**. Business logic is currently written directly in controllers. **New code should NOT follow this pattern.** Extract logic into Service classes in `app/Services/` and keep controllers thin.

### Server-rendered only
No REST API, no SPA. Everything is Blade + Alpine.js. The only API-style routes are the PSGC location lookup endpoints (`/api/cities/{provinceCode}`, `/api/barangays/{cityCode}`) for form dropdowns.

### Age bracket system
Learners have a profile with an `age_bracket` field. Modules are targeted to specific age brackets. Always filter module queries by the learner's age bracket — do not show all modules to all learners.

---

## What Is Already Built

### Authentication & User Management
- Three-path login split: Admin, Instructor, Learner (each has dedicated login page + controller)
- Learner registration (`RegisteredUserController`) + email verification
- Password reset flow
- Parent-child account system (`ParentChildAccount` model, `ParentRegistrationController` — may be incomplete)
- Profile completion gate: Learners must complete their profile before accessing the platform (`ProfileCompletionController`, `profile.completed` middleware)
- Learner profile: display name, username (rate-limited change for free users), avatar, age bracket, PSGC location, date of birth

### Instructor Panel (`/instructor`)
- **Modules:** Full CRUD, thumbnail upload, enrollment mode (open/approval), premium flag, age bracket targeting, final quiz assignment
- **Lessons:** Full CRUD, rich content support (video embed, TinyMCE, PDF), ordering/reordering
- **Lesson Topics:** Sub-units within lessons, TinyMCE content, worksheet file support
- **Quizzes:** Full CRUD, question management (multiple choice, true/false, short answer, matching, ordering, fill-in-the-blank), CSV bulk import
- **Enrollments:** View, approve, and reject learner enrollment requests
- **Image Library:** Upload/manage/delete images for use in TinyMCE editor
- **User management:** View and manage learner accounts

### Learner Features (`/learn`)
- Module browsing: age-bracket-filtered catalog, enrollment flow (`Learner\ModuleController`)
- Lesson viewing + completion: progress tracked per user (`Learner\LessonController`)
- Topic-level completion: fine-grained progress via `LessonTopicProgress`
- Basic dashboard: exists but uses old `<x-app-layout>` layout — **the redesigned dashboard (Figma) is not yet built**
- Gamification: `UserGamification` (level, XP, streak, total points), `Achievement`, `RewardLog` models exist — full UI is **not yet built**

### Subscription & Payment
- `SubscriptionController`: upgrade, cancel, renew, status check
- `PaymentController`: Paymongo integration, webhook handling, payment receipts
- Free vs. premium: premium unlocks certificate downloads, unlimited quiz attempts, module attachment downloads

### Quizzes (Learner-facing)
- `QuizController`: start, submit, result, attempt history
- Daily attempt limits via `QuizDailyLimit`
- Points-based attempt recharge (spend gamification points for more attempts)

---

## What Is NOT Yet Built

| Feature | Status |
| **Certificates** | Not started — models and migrations exist, no working UI or controller logic |
| **Seminars** | Not started — models and migrations only |
| **Organizations** | Not started — models and migrations only |
| **Health Centers & Clinics** | Not started — models and migrations only |
| **Counselors** | Not started — models and migrations only |
| **Consultations** | Not started — models and migrations only |
| **Admin panel** | Stub only — `/admin/dashboard` view exists, no CRUD or analytics |
| **Services / Actions layer** | Not started — tech debt from fat controllers |

---

## Key Reference Docs

- `PLATFORM_FEATURES_OVERVIEW.md` — full feature list by role
- `ADMIN_DEVELOPMENT_GUIDE.md` — admin panel spec and DB schema
- `FUTURE_IMPROVEMENTS.md` — planned enhancements
- `QUICK_TESTING_GUIDE.md` — how to test features manually
- `CSV_IMPORT_GUIDE.md` — quiz CSV bulk import format

---

## Skills-Based Workflow

This project uses the **Superpowers skills system** for structured AI-assisted development. The skills folder is at `!skills/` in the workspace root.

**Read `!skills/MY-WORKFLOW.md` for the complete 7-session development flow.**

Quick reference:

| Situation | Skill to attach |
|---|---|
| New feature / change idea | `!skills/brainstorming/SKILL.md` |
| Approved design, need a plan | `!skills/writing-plans/SKILL.md` |
| Writing code for a task | `!skills/test-driven-development/SKILL.md` |
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