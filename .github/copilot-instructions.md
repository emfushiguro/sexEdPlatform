# Copilot Instructions - Sex Education Platform

## Project Vision
A Philippine-focused sex education learning platform called Concious Connections. It delivers age-appropriate sexual health learning for kids, teens, and adults through modules, quizzes, gamification, and guided role workflows.

Core roles:
- Admin: platform governance and moderation
- Instructor: content creation and learner guidance
- Learner: module and assessment completion
- Parent: guardian monitoring and enrollment approvals

## Project Constitution
1. Learner safety and age appropriateness come first.
2. Server-rendered Laravel (Blade + Alpine) is the default architecture.
3. Service layer owns domain behavior; controllers orchestrate only.
4. Authorization boundaries are strict and role-aware.
5. Verifiable tests are required before completion claims.

## Non-Negotiable Engineering Rules
- Keep controllers thin; business logic belongs in app/Services.
- Use Form Requests for validation; avoid inline controller validation.
- Prefer Eloquent relationships; avoid raw SQL unless justified.
- Preserve route ownership:
  - Admin routes: routes/admin.php
  - Instructor routes: routes/instructor.php
  - Learner/public/shared routes: routes/web.php
- Preserve age-bracket filtering and governance visibility behavior.
- Prefer additive, reversible migrations for in-progress rollouts.
- Keep existing Blade component patterns and UI language.
- Run relevant tests and report actual output before handoff.

## Current Snapshot (As of 2026-04-11)
- Phase: Beta hardening with permission-first RBAC completed and learner safety/reporting UX rollout in progress
- Branch: main
- Latest observed commit: 419316a (Instructor fixes, module revenue fixes, and admin table refinement)
- Current focus:
  - learner feedback, safety reporting, and quiz UX rollout
  - global pop-up chat UX integration and reliability hardening
  - subscription lifecycle stabilization and checkout parity
  - notification refinement and cross-role unread badge consistency
  - post-RBAC permission/policy regression protection
- Latest changelog file in repo: docs/changelogs/2026-04-10-complete-rbac-system.md
- Planning anchor: docs/plans/2026-04-11-learner-feedback-reporting-quiz-ux-implementation-plan.md

## Project Stack
| Layer | Technology |
|---|---|
| Backend | Laravel 12, PHP 8.2 |
| Frontend | Blade templates, Alpine.js, Tailwind CSS v3 |
| Build | Vite + laravel-vite-plugin |
| Auth/Roles | Spatie Laravel Permission, single web guard |
| Editor | TinyMCE |
| Payments | PayMongo |
| Geography | schoolees/laravel-psgc |
| Notifications | Toastify |
| Testing | PHPUnit |
| Fonts | Poppins |

## Architecture Quick Facts
- Single auth guard: web (admin, instructor, learner share users table).
- Spatie permission matrix is the authorization source of truth; users.role remains as compatibility metadata via sync service.
- Service layer available and active for gamification, subscriptions, governance, instructor applications, and chat.
- No SPA/API-first conversion without explicit request.
- PSGC endpoints are the only API-style UX helpers.
- Module visibility must always respect learner age_bracket and governance status.

## Built Surface (Current)
### Authorization and governance hardening
- Permission-first RBAC rollout implemented with canonical permission/role seeders and compatibility bridge
- Super-admin gate behavior, policy coverage, and shared content authorization services integrated
- Route/controller/blade migration from role-string checks to permission/policy checks completed

### Authentication and user management
- Role-specific login flows, email verification, password reset
- Parent-child account linking and monitoring views
- Profile completion gate and learner profile controls

### Instructor panel
- Dashboard metrics, module/lesson/topic/quiz management, enrollments
- Instructor profile pages with professional identity fields
- Module pricing and enrollment limit configuration
- Governance submit/resubmit lifecycle for instructor modules

### Learner experience
- Redesigned dashboard with gamification components
- Age-filtered and governance-filtered module browsing
- Lesson/topic progression tracking
- Quiz attempt limits and timer fallback enforcement
- Certificate flow and learner notifications
- Learner feedback/reporting and quiz progression UX improvements are in active rollout

### Subscription and payments
- Plan management, upgrade/renew/cancel/refund requests
- PayMongo link flow with payment history and receipt views
- Premium gating middleware and dunning/invoice/refund support

### Cross-role chat (active rollout)
- Conversation discovery/start and message send/list/since
- Message update/delete/report and request accept/decline
- Conversation read-state and user chat status update
- Realtime channel authorization and Echo/Reverb pipeline
- Full-page chat exists; global pop-up UX is in active rollout

### Admin panel
- Dashboard, user CRUD, subscription/payment operations
- Role-permission management aligned to Spatie authorization boundaries
- Content governance review queue and decision workflow
- Instructor application moderation workflow
- Calendar/seminars/organizations/messages/email areas remain stub-first

## Not Yet Built (Major)
- Gamification achievement/badge UI and reward history UX
- Seminar module end-to-end implementation
- Organization management implementation
- Health centers, counselors, consultations product surfaces
- Deep analytics dashboards (charts, revenue drill-down)
- Global pop-up chat rollout completion
- Learner feedback/reporting moderation UX hardening and analytics follow-through
- app/Actions layer

## Key Reference Docs
- PLATFORM_FEATURES_OVERVIEW.md
- ADMIN_DEVELOPMENT_GUIDE.md
- QUICK_TESTING_GUIDE.md
- docs/changelogs/2026-04-10-complete-rbac-system.md
- docs/changelogs/2026-04-05-learner-payment-checkout-refinement.md
- docs/changelogs/2026-03-30-admin-module-review-system.md
- docs/changelogs/2026-03-30-admin-ui-ux-alignment.md
- docs/plans/2026-04-11-learner-feedback-reporting-quiz-ux-implementation-plan.md
- docs/plans/2026-04-10-complete-rbac-system-implementation-plan.md
- docs/plans/2026-04-04-global-popup-chat-system-design.md
- docs/plans/2026-04-04-global-popup-chat-system.md
- docs/plans/2026-04-05-learner-payment-checkout-refinement-design.md
- docs/plans/2026-04-05-learner-payment-checkout-refinement-implementation-plan.md
- docs/plans/2026-04-06-notification-system-refinement-implementation-plan.md
- docs/plans/2026-04-06-subscription-lifecycle-stabilization-implementation-plan.md
- docs/plans/2026-04-06-admin-user-management-system-implementation-plan.md
- docs/plans/2026-04-02-real-time-chat-system-design.md
- docs/plans/2026-04-02-real-time-chat-system-implementation-plan.md
- docs/plans/2026-03-31-learner-module-monetization-implementation-plan.md

## Execution Priorities
1. docs/plans/2026-04-11-learner-feedback-reporting-quiz-ux-implementation-plan.md
2. docs/plans/2026-04-06-subscription-lifecycle-stabilization-implementation-plan.md
3. docs/plans/2026-04-04-global-popup-chat-system.md
4. docs/plans/2026-04-06-notification-system-refinement-implementation-plan.md
5. docs/plans/2026-04-05-learner-payment-checkout-refinement-implementation-plan.md

## Active Implementation Map
- Routes: routes/admin.php, routes/instructor.php, routes/web.php, routes/channels.php
- Realtime bootstrap: resources/js/echo.js
- RBAC seeders: database/seeders/PermissionSeeder.php, database/seeders/RoleSeeder.php, database/seeders/RolePermissionSeeder.php
- Governance service: app/Services/ContentGovernanceService.php
- Subscription service: app/Services/SubscriptionService.php
- Role and content authorization services:
  - app/Services/Admin/RoleSyncService.php
  - app/Services/Content/ContentAuthoringService.php
  - app/Services/Content/ContentAccessService.php
- Chat services:
  - app/Services/Chat/ChatService.php
  - app/Services/Chat/ChatAuthorizationService.php
  - app/Services/Chat/ChatContextResolver.php
- Learner feedback/reporting services:
  - app/Services/ModuleFeedbackService.php
  - app/Services/ContentReportService.php
- Gamification service: app/Services/GamificationService.php
- Admin moderation controller: app/Http/Controllers/Admin/InstructorApplicationController.php
- Admin learner report controller: app/Http/Controllers/Admin/LearnerReportController.php
- Learner quiz controller: app/Http/Controllers/Learner/QuizController.php
- Learner feedback controller: app/Http/Controllers/Learner/ModuleFeedbackController.php
- Popup chat partial: resources/views/chat/partials/global-popup.blade.php

## Workspace Progress (As of 2026-04-11)
- Branch: main
- Latest observed commit: 419316a
- Latest changelog file in repo: docs/changelogs/2026-04-10-complete-rbac-system.md
- Latest planning wave:
  - 2026-04-11 learner feedback, reporting, and quiz UX
  - 2026-04-10 complete RBAC system
  - 2026-04-06 subscription lifecycle and notification refinement
  - 2026-04-04 global pop-up chat
  - 2026-04-05 learner payment checkout refinement
- Active WIP areas include learner feedback/reporting migrations/controllers/views, quiz UX progression updates, popup chat reliability hardening, subscription/notification stabilization, and cross-role chat/reporting test coverage.

## Current Reality Checks
- RBAC migration is now permission-first with broad policy/route/blade coverage and compatibility-safe legacy role sync.
- Admin moderation and governance are active.
- Several admin areas still ship as stubs.
- Chat foundation is live; popup UX is still under implementation.
- Learner feedback/reporting and moderation workflow are in active implementation.
- Interactive activities remain documented but not fully implemented.
- Subscription/payment systems are implemented; current work is mostly checkout refinement + lifecycle stabilization layered on top of existing billing.
- Paid module entitlement hard-gating is currently relaxed during rollout.
- Preserve learner quiz timer fallback behavior while extending quiz UX contracts.
- Treat routes/admin.php and routes/instructor.php as canonical for new role-route additions.

## Skills Workflow
- Source of truth: !skills/MY-WORKFLOW.md
- Standard flow: Brainstorming -> Writing Plans -> Executing Plans
- Use supporting skills as needed:
  - brainstorming
  - writing-plans
  - executing-plans
  - systematic-debugging
  - verification-before-completion
  - requesting-code-review / receiving-code-review

## Operating Workflow Rules
### Before coding
- Clarify goal and constraints.
- Propose 2-3 approaches with trade-offs.
- Confirm approach before substantial edits.

### During implementation
- Prefer test-first where feasible.
- Keep changes scoped and layered (service -> controller -> view).
- Avoid bypassing roles, policies, and visibility rules.

### Before completion
- Run relevant tests (or full suite when needed).
- Report actual command output and residual risks.
- Do not claim done without verification evidence.

## Code Style
- PHP: PSR-12
- Blade: kebab-case components, avoid logic-heavy templates
- Tailwind: utilities first
- Alpine: keep x-data small, extract when complex
- Primary brand gradient: #A30EB2 -> #730DB1 -> #3B0CB1
