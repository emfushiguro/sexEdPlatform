---
name: conscious-connections-ui
description: Use when Codex changes, reviews, designs, or documents UI for the Conscious Connections Laravel sexual-health platform, especially Blade/Tailwind learner, instructor, admin, connector, moderation, learning-path, dashboard, gamification, enrollment, payment, or safety workflow surfaces.
---

# Conscious Connections UI

Use this skill before editing, reviewing, or proposing UI in this repository. The root design.md is the platform design source of truth; this skill is the execution guide for applying it to the existing codebase.

## Required reading

1. Read design.md at the repository root before making a UI decision. It defines the product philosophy, role modes, tokens, Payment Management baseline, component rules, accessibility bar, and migration policy.
2. Read [references/ui-system.md](references/ui-system.md) as the implementation companion. It records current shell details, component locations, Tailwind constraints, safety copy, and verification commands.
3. Inspect the target view, its layout, the nearest canonical reference, and relevant components/tests.

If these sources disagree, use this order: root design.md, the nearest current implementation, references/ui-system.md, then older docs. Do not invent a replacement system because a screen is incomplete. If design.md is missing, report that before making a platform-wide visual decision.

## Workflow

1. Identify the role, shell, route/workflow, age or safety sensitivity, permission, and entitlement conditions.
2. Choose the nearest source map reference. For admin management, start with admin payments/index.blade.php.
3. Inspect the target files. Prefer `rg --files resources/views` and read the relevant layout, page, and component files.
4. Match the role shell already in use:
   - Learner: `resources/views/layouts/learner-app.blade.php` plus learner components.
   - Instructor: `resources/views/layouts/instructor-app.blade.php` plus instructor components.
   - Admin: `resources/views/layouts/admin.blade.php` plus admin partials and shared UI components.
   - Connector: `resources/views/layouts/connector-app.blade.php` and community components when connector-scoped.
5. Preserve existing Blade, Alpine, Tailwind, Vite, route, data-testid, and test contracts. Do not rename structural classes, data attributes, or text that tests likely assert unless the task requires it.
6. Reuse x-ui, learner, instructor, community, and existing page components before adding markup or CSS.
7. For safety-sensitive sexual-health surfaces, keep copy plain, calm, and action-oriented. Make parent, instructor, connector moderator, and platform admin responsibilities visibly distinct.
8. Design loading, empty, success, error, pending/processing, forbidden, and recovery states.
9. After edits, run the narrowest relevant feature tests and `npm.cmd run build` when Tailwind classes, Blade markup, or JS behavior changed.

## Quick Rules

- Use Poppins/Figtree through the existing Tailwind `font-sans`; do not introduce a new typeface.
- Use the brand gradient `#A30EB2 -> #730DB1 -> #3B0CB1` or Tailwind `brand-*` colors for primary identity.
- Keep role UI recognizable: playful progress for learners, calm operational workspace for instructors and admins.
- Prefer existing components in `resources/views/components/ui`, `components/learner`, `components/instructor`, and `components/community` before creating new ones.
- Use finite literal Tailwind classes. Avoid dynamic class interpolation that Vite cannot detect.
- Keep text inside controls short and scannable. Use icons for compact actions when the existing surface already uses them.
- Admin management uses the Payment Management baseline: gray-50 canvas, white rounded work shell, resettable filters, semantic status pills, audit context, and accessible icon-first actions.
- Admin and instructor are light-only; preserve learner dark-mode behavior where the shell already supports it.
- Use the shared toast API for transient feedback and inline alerts for context that must remain visible. Never use browser alert as product feedback.
- Keep parent approval, instructor approval, platform moderation, age eligibility, and subscription entitlement visibly distinct.
