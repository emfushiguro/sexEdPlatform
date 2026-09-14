# Conscious Connections design language

A role-aware, safety-first learning platform: recognizable purple brand cues, calm white work surfaces, readable typography, clear states, and small moments of encouragement. The interface should feel trustworthy enough for payments and guardian decisions, structured enough for administration, and welcoming enough for learning.

This spec is implementation-aware. Keep the existing Laravel, Blade, Alpine, Tailwind, Vite, CSS, and JavaScript stack. Extend the established shells and components; never add a new framework or visual system just to satisfy a screen.

This is the canonical design source of truth. It describes the current platform and the rules for future UI. Last reviewed 2026-09-12.

## Philosophy - read this first

Six decisions drive everything else:

**Role changes the mood, not the foundation.** Admin, instructor, learner, guardian, connector, authentication, and landing surfaces share brand tokens, spacing, controls, status semantics, and accessibility. They intentionally do not share one generic visual mood.

**The Payment Management dashboard is the admin baseline.** It is operational, data-dense, audit-friendly, and brand-accented without becoming a purple wallpaper. Use it as the reference for management tables, filters, summary cards, detail pages, receipts, and financial reports.

**Trust comes from context.** A status should sit near its owner, amount or outcome, date, source, next action, and reference. Never make a person infer a consequential state from color or an icon alone.

**Type, spacing, and state do the hierarchy work.** Poppins, restrained weights, generous rhythm, clear labels, and semantic pills create hierarchy. Do not solve a weak information structure with oversized type, extra gradients, or more boxes.

**Safety and eligibility are first-class UI.** Age bands, guardian relationships, permissions, publication review, subscription entitlement, and payment processing must be visible at the moment they affect a decision.

**Reuse the nearest working pattern.** Start with the role shell, then the closest page anatomy, then a shared component. Add a new variant only when the existing pattern cannot express the workflow clearly.

### Experience modes

| Surface | Audience | Mood | Density | Theme |
| --- | --- | --- | --- | --- |
| Admin operations | Platform staff | Calm control room; analytical and explicit | High | Light only |
| Instructor workspace | Educators and authors | Warm, branded, confident | Medium-high | Light only |
| Learner workspace | Learners and children | Supportive, age-aware, softly gamified | Medium | Light and dark |
| Parent / guardian | Guardians and families | Reassuring, transparent, protective | Medium | Light by default |
| Connector workspace | Organizations and local staff | Organized, membership-aware, practical | Medium | Light by default |
| Authentication | All roles | Focused, polished, confidence-building | Focused | Light |
| Public landing | Prospective users and partners | Expressive, optimistic, conversion-led | Spacious | Light |
| Seminars and live rooms | Learners, instructors, connectors | Task-focused and media-led | Variable | Shell-aware |

## Color

The palette is branded rather than monochrome. Purple is the product identity; semantic colors communicate state. Prefer the Tailwind tokens in tailwind.config.js over raw hex values.

### Brand scale

| Token | Value | Intended use |
| --- | --- | --- |
| brand-50 | #fdf4ff | Tinted backgrounds and selected surfaces |
| brand-100 | #f8e4ff | Soft borders, filter fields, selected controls |
| brand-200 | #f0c4fe | Hover borders and outlines |
| brand-300 | #e393fb | Intermediate emphasis and decoration |
| brand-400 | #cf56f3 | Strong accent |
| brand-500 | #A30EB2 | Primary brand action |
| brand-600 | #8A0DB3 | Hover and active primary action |
| brand-700 | #730DB1 | Deep gradient stop and emphasis |
| brand-800 | #550CB1 | High-contrast brand text |
| brand-900 | #3B0CB1 | Deep gradient stop |
| brand-950 | #1e0660 | Deep headings and high-contrast text |

The primary gradient is **#A30EB2 -> #730DB1 -> #3B0CB1**. Use it for a primary CTA, active navigation, a bounded role hero, a selected premium plan, or a learner achievement moment. Keep the surrounding page quiet.

### Semantic colors

| Role | Surface | Text / icon | Examples |
| --- | --- | --- | --- |
| Success / completed | emerald-50 or emerald-100 | emerald-700 | Completed payment, published module, passed quiz |
| Warning / attention | amber-50 or amber-100 | amber-700 | Pending review, renewal notice, guardian action |
| Destructive / failed | rose-50 or rose-100 | rose-700 | Failed payment, suspension, delete confirmation |
| Informational | blue-50 or brand-50 | blue-700 or brand-700 | Help, review context, secondary status |
| Neutral / inactive | gray-50 or gray-100 | gray-600 or gray-700 | Archived, disabled, no result |

Status always has readable text. Add an icon when it improves scanning, but never use color or shape as the only signal.

### Usage rules

- Light page background is gray-50; primary work surfaces are white.
- Use gray-200 for ordinary borders and dividers. Use brand-100/200 for selection or active context.
- Keep saturated purple for actions, active navigation, focused summaries, and bounded hero panels.
- Use emerald, amber, rose, blue, and gray only for their semantic meanings.
- Do not introduce another purple, blue, or pink family. Legacy brand.purple, brand.blue, and brand.pink tokens may remain in untouched screens but are not new design guidance.
- Raw inline colors are acceptable only when an existing chart, gradient, or integration requires them; document a repeated value and move it into a token.
- A view may have one visually loud region. Everything else should support reading and action.

### Theming mechanics

Admin and instructor screens are light-only. Learner screens remap the same semantic roles for dark mode rather than becoming a different product. The learner preference is stored locally and supports light, dark, and system behavior where the shell provides the control. Authentication, parent, connector, seminar, and landing surfaces default to light unless the route documents a deliberate exception.

## Typography

Poppins is the platform voice. It is configured as the primary sans family and is used for navigation, headings, controls, cards, dashboards, and learner content. Figtree and the system sans stack remain compatibility fallbacks in legacy layouts and Toastify; do not create new Figtree-only screens.

### Roles

| Role | Treatment | Use |
| --- | --- | --- |
| Page title | Poppins, semibold/bold, roughly 24-32px | One clear title near the top of the page |
| Section heading | Poppins, semibold, roughly 18-22px | Card groups, panels, and form sections |
| Body/UI | Poppins, regular/medium, roughly 14-16px | Paragraphs, navigation, controls, table cells |
| Small label | Poppins, semibold, 11-12px, tracked | Field labels, compact context, stat labels |
| Overline/table header | Poppins, bold, 10-12px, uppercase and tracked | Dense admin tables and short section markers |
| Numeric value | Poppins, bold, roughly 24-32px | Summary cards, progress, amounts |
| Technical value | Monospace, readable size | Transaction IDs, references, generated codes |

Keep body copy comfortable and short. Use sentence case for user-facing headings and actions. Uppercase is a compact register for labels, table headers, and overlines; do not set whole paragraphs in uppercase.

For safety, consent, payment, and guardian copy, clarity wins over brand personality. For landing pages, larger display type and decorative treatment are allowed within the marketing shell only.

## Layout & spacing

The platform is shell-led. A fixed role sidebar and sticky header establish orientation; the content area carries the task.

### Shell dimensions

| Shell | Sidebar expanded | Sidebar collapsed | Main content |
| --- | --- | --- | --- |
| Admin | about 290px | about 90px | gray-50, p-4/md:p-6, centered up to about 1536px |
| Instructor | about 280px | about 84px | gray-50, p-4/md:p-6, screen-2xl |
| Learner | about 270px | about 80px | gray-50 or gray-900, p-4/md:p-6 |
| Connector | shared fixed-sidebar pattern | shell-defined | Organization-scoped content |

Sidebars become overlays on mobile. Use the existing shell stores and backdrops rather than creating a second navigation mechanism.

### Rhythm

- Horizontal page padding: 1rem on small screens, 1.5rem from md upward.
- Card padding: p-4 for compact tiles, p-5 for role panels, p-6 for primary work.
- Small component gaps: about 0.75rem.
- Card/grid gaps: gap-4 to gap-6.
- Major admin sections: space-y-8.
- Use Tailwind’s 0.25rem spacing rhythm. A repeated spacing value should become a utility or component pattern, not a new arbitrary number.

### Width and grids

- Admin work areas may use a 1536px cap; instructor uses screen-2xl; a dashboard article may use max-w-7xl when charts or queues need a narrower measure.
- Use two columns from sm where the content remains readable and three columns from xl for dashboard summaries.
- Let data tables scroll horizontally. Do not compress references, dates, amounts, or action targets until they are unusable.
- On learner lesson and media views, reserve the largest usable region for content and keep progress/next action visible.

### Page anatomy

Management page: title and context -> primary action -> summary cards -> filter bar -> table -> pagination -> feedback.

Detail/receipt page: back link -> identity and status -> key amount or outcome -> related records -> audit identifiers -> actions.

Dashboard: greeting/context -> high-value stats -> queues or next actions -> trends -> recent activity.

Authoring page: breadcrumb/context -> objective and metadata -> structured editor -> validation -> preview -> save/publish.

Browse page: age/eligibility context -> search/filter -> recommended or active cards -> empty/loading state.

Wizard: step indicator -> one decision group -> inline validation -> back/next -> final confirmation.

## Components

Every component should have a clear purpose, a semantic state, and a predictable responsive behavior. The usual recipe is a light surface, a thin border, a generous radius, a restrained shadow, and a label or action that explains why the component is present.

### Radius ladder

| Context | Default |
| --- | --- |
| Main work shell | rounded-2xl; Payment baseline may use 24-30px |
| Standard card or section | rounded-2xl |
| Compact card | rounded-xl |
| Button, input, select | rounded-lg or rounded-xl |
| Payment baseline filter/action | rounded-2xl |
| Pill, status, count | rounded-full |
| Avatar or circular icon tile | rounded-full |
| Thumbnail | about 10-12px |

Custom 24px, 28px, and 30px radii are part of the current admin financial language. Use them for the primary shell, not every nested element.

### Shadows

Use theme-xs/sm/md for admin elevation and soft/medium for role cards. Large or glow shadows belong to a clearly bounded hero, premium selection, gamification reward, or landing element. In learner dark mode, rely more on borders and less on shadow.

Resting cards should feel grounded, not raised like a separate application. A border plus a low-alpha shadow is usually enough.

### Buttons

| Intent | Treatment |
| --- | --- |
| Primary | brand-600/700 solid or the primary gradient for a major role action |
| Secondary | white/gray surface with gray or brand border |
| Success | emerald only when the action confirms or completes |
| Destructive | rose/red and followed by an explicit confirmation |
| Outline | brand border and text with a light surface |
| Ghost | low-emphasis gray text for noncritical navigation |

Use verbs: Create user, Review payment, Publish module, Save changes. Keep one dominant action per region. Show loading and disabled states without changing the button’s dimensions. Keep touch targets around 40px or larger.

The x-ui button component is the default API. Existing instructor and learner gradient button classes may be used inside their shells when the role pattern calls for them.

### Cards and stat surfaces

Use white rounded-2xl cards with a gray-200 or lightly branded border and soft/theme shadow. Stat cards show a label, value, unit or time scope, and an icon or trend when useful. Values are usually 24-32px; labels are compact and muted.

Payment, users, financial reports, and similar admin surfaces use the Payment baseline: a white rounded 24-30px shell, gray border, theme-xs shadow, and a lightly branded header. Nested panels use a tinted surface or lighter border instead of another heavy shadow.

### Inputs and filters

Labels are visible and associated with controls. Standard controls use gray-50/white surfaces, gray-200/300 borders, rounded-lg/xl, and a brand focus ring. Payment filters use p-3, rounded-2xl, brand-100 borders, compact labels, and a visible Reset action.

Placeholder text is an example, never the only label. When filters can hide records, expose the active filters and a way to clear them. Preserve entered values after validation and show errors next to the field.

### Tables

The Payment Management table is the table recipe:

- overflow-x-auto around wide data;
- lightly tinted header with uppercase, tracked, muted labels;
- white body with gray-100 dividers and a subtle brand-50 hover;
- consistent alignment for amounts and dates;
- status/type/method pills;
- icon-first actions at the right with accessible names and a title/tooltip;
- pagination or an explicit result count for large datasets;
- an empty state that says what is empty and how to broaden/reset the search.

Do not hide critical ownership, amount, date, status, or reference data behind a hover-only interaction.

### Badges and status pills

Pills are fully rounded, compact, and readable. Use pale semantic surfaces with darker text. Common state mapping:

| Domain | Positive | Attention | Negative | Neutral |
| --- | --- | --- | --- | --- |
| Payments | Completed | Pending, Processing | Failed | Refunded |
| Content | Published | Draft, In review | Rejected | Unpublished |
| Moderation | Approved | Pending review, Reported | Suspended | Resolved |
| Enrollment | Active, Completed | Pending | Cancelled, Declined | Not started |
| Subscription | Active | Trial, Renewal due | Expired, Cancelled | Inactive |
| Verification | Verified | Pending | Rejected | Not submitted |

The word is authoritative. If a backend state changes, update the label and action rather than reusing a visually similar color.

### Modals

Use a full-viewport dark translucent overlay with optional blur and a centered white rounded-2xl panel with shadow-2xl. In dark learner mode, use the learner surface tokens.

The header identifies the action. The body names the record or consequence. The footer groups cancel and confirm. Destructive confirmations repeat the consequence: Archive payment or Delete user, not just Confirm. Escape closes when safe; focus enters the dialog and returns to the trigger.

Long forms belong on a page or in a stepper, not in a crowded confirmation modal.

### Alerts and toasts

The shared Toastify wrapper in resources/js/toast.js is the transient feedback channel. Use window.toast for success, error, warning, info, primary, achievement, level-up, and XP events.

Use a toast after a completed action, redirect, or background event. Use an inline alert when the information must remain visible in context: a blocking eligibility explanation, payment instruction, persistent error, or audit note. Do not show both for the same transient event, and never use window.alert as product feedback.

Toasts need an appropriate aria-live level, a close affordance, a readable duration, and reduced-motion behavior. Celebration toasts must not mask payment failures, access loss, or safety warnings.

### Progress, loading, and empty states

Progress bars show a visual fill and a text value when the number matters. Skeletons reserve the final layout and use the established shimmer. Empty states explain what is empty, why it may be empty, and the next useful action.

Pending, processing, loading, forbidden, and failed are different states. A request that can recover should offer Retry, Resume checkout, or another appropriate next action rather than an infinite spinner.

### Forms and authoring

The instructor authoring flow covers modules, lessons, topics, video, text, worksheets, quizzes, and interactive activities. Separate metadata from learning content; keep age bracket and publication/review state near the relevant control; preserve drafts; show save state; preview activities before publishing; and validate without losing authored content.

### Avatars, ownership, and privacy

When a record belongs to or was created by a person, show enough context to distinguish it: avatar or stable initial, name, role or organization, and a detail link where permitted. Respect guardian restrictions and do not expose private information in avatar alternative text, notification previews, or empty states.

### Charts

Admin dashboards and financial reports use restrained Chart.js charts with the brand palette and semantic highlights. Show titles, units, time ranges, and adjacent text for key trends. Keep series count low and provide empty, partial, and error states. A chart is never the only representation of an important number.

### Dropdowns, notifications, and chat

Dropdowns use a white surface, thin border, rounded-xl/2xl, and shadow, aligned to their trigger. Notification items distinguish unread, severity, timestamp, and destination. The global chat popup supports up to three persistent windows per user, minimize/restore, unread counts, loading/error states, and realtime updates when Echo/Reverb is configured.

Chat and notification surfaces must not cover a primary form action on small screens.

## Texture - brand emphasis

This platform’s texture is controlled use of the brand gradient, soft radial washes, dot grids, and low-opacity decorative shapes.

- Use the primary gradient for active navigation, primary CTAs, bounded role heroes, selected premium plans, and learner achievements.
- The Payment Management filter header uses a subtle radial brand wash over a white-to-lavender surface. It is a work-surface accent, not a page background.
- Dot grids, blobs, waves, orbs, glows, and changing gradients belong to landing pages or bounded hero/achievement panels.
- Use at most one or two decorative treatments per page. Texture should dissolve toward the background and never compete with a safety warning, payment amount, or data table.
- Admin content remains a quiet gray-50/white workspace. Do not copy landing decoration into operational screens.

## Motion

Motion is brief, purposeful, and optional:

- 150ms for control color/opacity changes.
- 200-300ms for ordinary panel, hover, and button transitions.
- Around 500ms for nonessential reveals.
- Existing utilities include transition-smooth, transition-fast, transition-slow, hover-lift, btn-press, fade, slide, scale-in, shimmer, and notification pulse.
- Card hover may lift slightly; it must not move the reading target unexpectedly.
- Loading and status changes should be communicated in text or live regions, not only animation.
- Disable or simplify decorative motion under prefers-reduced-motion.

Motion must never delay navigation, hide a consequential state, or make a form difficult to operate.

## Role recipes and product behavior

### Admin

Use the admin shell and the Payment baseline. Prioritize summary metrics, moderation queues, user/content ownership, filters, audit identifiers, and explicit next actions. The admin dashboard covers learners, instructors, modules, revenue, applications, reviews, verifications, payments needing review, trends, demographics, and recent activity.

Admin is light-only, data-dense, and purple-accented. Use active gradient navigation, white cards, gray-50 page background, semantic states, and icon-first outline actions.

### Instructor

Use the instructor shell, light-only theme, gradient active navigation, hero-banner, section-shell, stat-card, and quick-actions patterns where appropriate. Keep the draft -> review -> publish lifecycle visible. Make age bracket, ownership, validation, save state, preview, learner progress, assessments, seminars, earnings, and payment history understandable without adding dashboard noise.

### Learner

Use the learner shell and support light/dark mode. Lead with what the learner can do now, how far they have progressed, and what happens next. Active/recommended module cards, progress, creator attribution, difficulty, quizzes, certificates, streaks, shields, levels, and rewards should encourage learning without becoming distracting or competitive.

### Parent / guardian

Use calm surfaces and clear relationship context. Explain who is connected, what the guardian can see or approve, which action is waiting, and how to recover from an expired, rejected, or revoked relationship. Do not reveal restricted child information through a notification preview or empty state.

### Connector

Make organization scope, membership, local roles, invitations, seminars, speakers, subscriptions, and permissions explicit. Distinguish no data from no permission and from not yet invited. Keep the shared brand and status system while avoiding admin-only assumptions.

### Authentication and landing

Authentication uses the split layout: focused white form panel, gradient role panel, rounded-2xl outer card, gray-50 inputs, rounded-xl controls, and a full-width gradient action. Instructor authentication may retain its established deep blue role gradient.

Landing may use large expressive gradients, orbs, waves, dot grids, glows, and reveal animation. Marketing decoration stops at the shell boundary.

### Learning, safety, and entitlement

The age bands are Kids 5-12, Teens 13-17, and Adults 18+. The content model includes modules, lessons, topics, video, text, worksheets, interactive checkpoints, quizzes, progress, certificates, and gamification. A blocked or unavailable item should explain the safe next step without exposing internal authorization logic.

### Payments and subscriptions

Subscription UI explains tier, billing period, features, limits, eligibility, current selection, renewal, and cancellation implications. Payment UI distinguishes module purchases from subscriptions and shows pending, processing, completed, failed, and refunded states.

Display Philippine peso amounts consistently. Keep transaction IDs and references copyable in monospace. Show receipt/history access and recovery for pending or failed payments. Do not imply access, completion, renewal, or earnings before the server confirms the relevant state.

### Seminars, media, moderation, and shared services

Seminars and livestreams expose schedule, speaker, capacity, access, recording, connection, and permission states. Video uses the established Plyr integration where applicable; live rooms may use Agora.

Moderation screens preserve the reviewed record, use neutral language, and make the reviewer’s next action explicit. Notifications and chat provide inspectable destinations; unread counts are supplemental, not proof that an action completed.

## Accessibility & quality bar

Non-negotiables:

- Use semantic landmarks: nav, header, main, article/section, form, table, and dialog where appropriate.
- Provide one clear page heading and a logical heading hierarchy.
- Associate every input with a visible label, help text, and error state where needed.
- Make navigation, menus, tabs, dialogs, tables, quizzes, and media keyboard-operable.
- Preserve a visible focus-visible ring and sufficient contrast on white, tinted, dark, and gradient surfaces.
- Convey status with text/icon as well as color.
- Give icon-only controls accessible names and meaningful images descriptive alternative text.
- Move focus into dialogs and return it to the trigger when they close.
- Do not discard unsaved work through outside-click or Escape without a safe decision.
- Announce important asynchronous outcomes through an appropriate live region without flooding the user.
- Respect prefers-reduced-motion and keep the design complete when still.
- Keep touch targets around 40px or larger.
- Test both light and learner dark themes, keyboard-only operation, and small/large breakpoints.

## Applying this to the existing project

When adding or restyling a screen, work in this order:

1. Identify the role, shell, workflow, age/safety sensitivity, and permission/entitlement conditions.
2. Start from the nearest reference in the source map below. For admin management, start with Payment Management.
3. Keep the existing Laravel/Blade/Alpine/Tailwind implementation; reuse x-ui or role components before writing new markup.
4. Apply the canonical brand, semantic colors, Poppins typography, spacing rhythm, radius ladder, and shadow vocabulary.
5. Make the primary action, ownership, status, amount/outcome, date, and next step explicit.
6. Add loading, empty, success, error, pending/processing, forbidden, and recovery states.
7. Verify mobile sidebars, filter stacking, table overflow, dialog focus, toast behavior, and learner dark mode where applicable.
8. Check keyboard behavior, labels, contrast, reduced motion, and status text.
9. Run the relevant feature/browser/policy tests and perform visual QA at 360px, 768px, 1024px, and 1280px.
10. Update this file only when the decision is reusable beyond the one screen.

### Source map

| Pattern | Current implementation |
| --- | --- |
| Admin shell and navigation | resources/views/layouts/admin.blade.php |
| Admin Payment baseline table | resources/views/admin/payments/index.blade.php |
| Admin transaction detail | resources/views/admin/payments/show.blade.php |
| Printable payment receipt | resources/views/admin/payments/receipt.blade.php |
| Admin users management | resources/views/admin/users/index.blade.php and users/partials |
| Admin financial reports | resources/views/admin/financial-reports/index.blade.php |
| Instructor shell | resources/views/layouts/instructor-app.blade.php and instructor-header.blade.php |
| Instructor dashboard | resources/views/instructor/dashboard.blade.php |
| Instructor hero/sections/stats/actions | resources/views/components/instructor |
| Learner shell | resources/views/layouts/learner-app.blade.php, learner-sidebar.blade.php, learner-header.blade.php |
| Learner dashboard and gamification | resources/views/learner/dashboard.blade.php and resources/views/components/learner |
| Learner module cards | resources/views/components/learner/module-card-active.blade.php and module-card-recommended.blade.php |
| Subscription plans | resources/views/subscriptions/index.blade.php |
| Learner payment flow | resources/views/payments and resources/views/payments/checkout-summary.blade.php |
| Authentication | resources/views/components/auth-split-layout.blade.php and resources/views/auth |
| Connector shell | resources/views/layouts/connector-app.blade.php and resources/views/connectors |
| Marketing | resources/views/layouts/landing.blade.php and resources/views/landing/index.blade.php |
| Shared UI components | resources/views/components/ui |
| Global tokens and CSS | tailwind.config.js, resources/css/app.css, resources/css/components.css |
| Motion and toast styling | resources/css/animations.css, resources/css/toast-custom.css |
| Frontend stores and toast API | resources/js/app.js, resources/js/toast.js |
| Chat and realtime | resources/js/chat/store.js, global-popup.js, echo.js |

### Migration notes

The repository contains earlier visual generations. They remain compatibility surfaces until touched:

- docs/AI_UI_UX_STYLE_GUIDE.md and docs/UI_COMPONENTS_GUIDE.md are historical references; this file wins when they conflict.
- layouts/app.blade.php, layouts/guest.blade.php, Breeze navigation, and the default application-logo are legacy shells.
- Figtree-only declarations, nested brand families, raw inline colors, and dark classes in admin markup are legacy drift.
- Some admin screens still show an inline flash banner alongside a layout toast. New work uses one appropriate transient or persistent channel.
- Older subscription-details markup and a residual seminar-create browser alert are known exceptions.

When touching a legacy screen, migrate the nearest visible pattern first: typography, spacing, focus, status semantics, feedback, and responsive behavior. Do not redesign unrelated routes or remove a legacy token/component without checking its consumers.

Stop before the screen becomes busy. If a management screen feels like a landing page, remove decoration. If a learner page feels like an admin table, restore encouragement, progress, and age-appropriate context.

### Design review checklist

- [ ] Role and shell are explicit.
- [ ] Nearest canonical reference was reused.
- [ ] Primary task and next action are obvious.
- [ ] Permissions, age, guardian, subscription, and status constraints are represented where relevant.
- [ ] Brand and semantic tokens, typography, radius, shadow, and spacing match the role.
- [ ] Loading, empty, success, error, pending/processing, forbidden, and recovery states exist.
- [ ] Toasts, alerts, dialogs, tables, and chat do not obscure the task.
- [ ] Keyboard, focus, labels, contrast, status text, reduced motion, and responsive behavior were checked.
- [ ] Relevant tests and visual QA were run.
- [ ] A reusable platform decision was added here with its implementation reference.

## Change log

- 2026-09-12 - Recast the source of truth into an instructional design-language format, using the existing codebase and the Payment Management dashboard as the canonical admin reference.
- 2026-09-06 - Initial platform-wide visual inventory, role shells, components, product flows, accessibility rules, and migration guidance.
