# Copilot Instructions - Sex Education Platform

## Project Vision
A Philippine-focused sex education learning platform called Concious Connections. It delivers age-appropriate sexual health learning for kids, teens, and adults through modules, quizzes, gamification, and guided role workflows.

Core roles:
- Admin: platform governance and moderation
- Instructor: content creation and learner guidance
- Learner: module and assessment completion
- Parent: module and assessment completion + guardian monitoring and enrollment approvals

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

## Current Snapshot (As of 2026-06-02)
- Phase: Beta hardening with connector domain rollout, centralized moderation, chat moderation, and subscription/admin UX hardening active
- Branch: main
- Latest observed commit: 0a03825 (connectors design + implementation plan docs)
- Current focus:
  - connector organization registration, admin verification, workspace, members, roles, and entitlements
  - centralized moderation/suspension dashboard and chat report review workflow
  - chat UI reliability and message/report enums
  - subscription plan/admin UI refinements
  - learner dashboard and navigation entry-point cleanup
- Latest changelog file in repo: docs/changelogs/2026-04-17-centralized-moderation-rollout.md
- Planning anchor: docs/superpowers/plans/2026-05-26-connectors-implementation.md

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

### Content ownership boundaries and transparency
- Central ownership guard now enforces admin read-only behavior for instructor-owned module/lesson/topic/quiz mutation flows
- Admin module cards and learner module surfaces now render normalized ownership/publisher identity
- Admin creator profile domain added (profile persistence, policy, admin edit/update flow, learner-facing creator page)

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
- Learner module overview/reviews/reporting UX refresh delivered (right-rail hierarchy, review/report modals, quiz markers, heart rating visuals)
- Instructor background page expanded with structured professional profile sections and normalized datasets

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
- Message report reason/action enums and admin review flow are being integrated

### Admin panel
- Dashboard, user CRUD, subscription/payment operations
- Role-permission management aligned to Spatie authorization boundaries
- Content governance review queue and decision workflow
- Instructor application moderation workflow
- Payment-style table/palette standardization applied across major admin management surfaces
- Module revenue flow expanded with transaction details and instructor roll-up pages; payout transition UI removed from dashboard
- Calendar/seminars/organizations/messages/email areas remain stub-first

### Connectors (active rollout)
- New Connector domain is replacing the old Organization concept
- Authenticated learner-side registration, status pages, connector dashboard, members/invitations, roles/permissions, and subscription entitlement services are in WIP
- Admin connector moderation routes/views and connector notifications are in WIP
- Connector implementation files and tests are currently uncommitted workspace work

## Not Yet Built (Major)
- Gamification achievement/badge UI and reward history UX
- Connector rollout final verification and full-suite stabilization
- Dynamic gamification policy management implementation hardening
- Seminar module end-to-end implementation
- Old Organization surface final removal/replacement by Connectors
- Health centers, counselors, consultations product surfaces
- Deep analytics dashboards (charts, revenue drill-down)
- Global pop-up chat rollout completion
- Learner feedback/reporting moderation UX hardening and analytics follow-through
- app/Actions layer

## Key Reference Docs
- PLATFORM_FEATURES_OVERVIEW.md
- ADMIN_DEVELOPMENT_GUIDE.md
- QUICK_TESTING_GUIDE.md
- docs/changelogs/2026-04-16-admin-creator-profile-transparency.md
- docs/changelogs/2026-04-17-centralized-moderation-rollout.md
- docs/superpowers/specs/2026-05-26-connectors-design.md
- docs/superpowers/plans/2026-05-26-connectors-implementation.md
- docs/changelogs/2026-04-14-admin-management-table-standardization.md
- docs/changelogs/2026-04-14-admin-learning-content-ownership-ui-filtering.md
- docs/changelogs/2026-04-14-learner-module-overview-review-report-instructor-background.md
- docs/changelogs/2026-04-10-complete-rbac-system.md
- docs/plans/2026-04-16-dynamic-gamification-configuration-implementation-plan.md
- docs/plans/2026-04-16-dynamic-gamification-configuration-design.md
- docs/plans/2026-04-16-admin-creator-profile-transparency-implementation-plan.md
- docs/plans/2026-04-16-admin-creator-profile-transparency-design.md
- docs/plans/2026-04-11-learner-feedback-reporting-quiz-ux-implementation-plan.md
- docs/plans/2026-04-06-subscription-lifecycle-stabilization-implementation-plan.md

## Execution Priorities
1. docs/superpowers/plans/2026-05-26-connectors-implementation.md
2. docs/plans/2026-04-17-centralized-moderation-enforcement-suspension-implementation-plan.md
3. docs/plans/2026-04-17-financial-reporting-analytics-implementation-plan.md
4. docs/plans/2026-04-16-dynamic-gamification-configuration-implementation-plan.md
5. docs/plans/2026-04-04-global-popup-chat-system.md

## Active Implementation Map
- Routes: routes/admin.php, routes/instructor.php, routes/web.php, routes/channels.php
- Realtime bootstrap: resources/js/echo.js
- RBAC seeders: database/seeders/PermissionSeeder.php, database/seeders/RoleSeeder.php, database/seeders/RolePermissionSeeder.php
- Governance service: app/Services/ContentGovernanceService.php
- Subscription service: app/Services/SubscriptionService.php
- Role and content authorization services: app/Services/Admin/RoleSyncService.php, app/Services/Content/ContentAuthoringService.php, app/Services/Content/ContentAccessService.php, app/Services/Content/ContentOwnershipGuard.php
- Admin creator profile and ownership transparency:
  - app/Services/Admin/AdminCreatorProfileService.php
  - app/Services/Content/AdminOwnershipDisplayService.php
  - app/Http/Controllers/Learner/AdminCreatorProfileController.php
  - app/Models/AdminCreatorProfile.php
- Chat services: app/Services/Chat/ChatService.php, app/Services/Chat/ChatAuthorizationService.php, app/Services/Chat/ChatContextResolver.php
- Learner feedback/reporting services:
  - app/Services/ModuleFeedbackService.php
  - app/Services/ContentReportService.php
- Gamification service: app/Services/GamificationService.php
- Connector domain:
  - routes/connector.php
  - app/Services/Connectors/*
  - app/Http/Controllers/Connector/*
  - app/Http/Controllers/Admin/ConnectorController.php
  - config/connector_permissions.php
  - resources/views/connectors/*
  - resources/views/admin/connectors/*
- Admin moderation controller: app/Http/Controllers/Admin/InstructorApplicationController.php
- Admin monetization controller: app/Http/Controllers/Admin/ModuleRevenueController.php
- Learner quiz controller: app/Http/Controllers/Learner/QuizController.php
- Learner feedback controller: app/Http/Controllers/Learner/ModuleFeedbackController.php
- Popup chat partial: resources/views/chat/partials/global-popup.blade.php

## Workspace Progress (As of 2026-06-02)
- Branch: main
- Latest observed commit: 0a03825
- Latest changelog file in repo: docs/changelogs/2026-04-17-centralized-moderation-rollout.md
- Latest planning wave:
  - 2026-05-26 connectors design and implementation plan
  - 2026-04-17 centralized moderation rollout
  - 2026-04-17 financial reporting analytics
  - 2026-04-16 dynamic gamification configuration
- Active WIP includes connector rollout files/tests, chat report moderation, suspension dashboard updates, subscription plan UI, and learner/admin navigation cleanup.

## Current Reality Checks
- RBAC migration is now permission-first with broad policy/route/blade coverage and compatibility-safe legacy role sync.
- Admin creator profile transparency domain is implemented and integrated into learner module ownership displays.
- Admin management pages are actively being standardized to the Payment baseline design language.
- Centralized moderation and governance are active; dual-write/backfill parity remains important before cutover.
- Several admin areas still ship as stubs.
- Chat foundation is live; popup UX is still under implementation.
- Chat report moderation is being promoted into the admin moderation workflow.
- Learner feedback/reporting flow is implemented with ongoing moderation/UX hardening in progress.
- Interactive activities remain documented but not fully implemented.
- Subscription/payment systems are implemented; current work is mostly checkout refinement + lifecycle stabilization layered on top of existing billing.
- Paid module entitlement hard-gating is currently relaxed during rollout.
- Preserve learner quiz timer fallback behavior while extending quiz UX contracts.
- Dynamic gamification policy architecture is planned/partially implemented; continue validating tests before extending.
- Connectors are the supported replacement path for old Organization surfaces.
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
