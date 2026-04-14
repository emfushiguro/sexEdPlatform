# AI UI/UX Style Guide

This guide is for AI-assisted front-end work in this codebase. It is based on the current implementation, not an idealized redesign.

Use this when adding or updating UI so new screens match the platform that already exists.

## Purpose

The platform does not have one generic UI theme. It has one shared brand and several role-specific presentation modes:

- `Admin`: operational, clean, data-heavy, light-only
- `Instructor`: branded but still professional, light-only
- `Learner`: softer, more gamified, supports dark mode in many screens
- `Auth`: split-screen, polished, brand-led onboarding/login flow
- `Landing/Marketing`: the most expressive area, with custom motion and decorative backgrounds

AI must first identify which mode it is working in, then mirror that mode exactly.

## Non-Negotiable Global Rules

1. Use `Poppins` everywhere. Do not introduce Inter, Roboto, Arial, or a new font stack.
2. Keep the brand gradient consistent: `#A30EB2 -> #730DB1 -> #3B0CB1`.
3. Prefer existing Tailwind classes and existing Blade patterns over inventing new CSS.
4. Match nearby files before creating anything new. The nearest existing screen is the source of truth.
5. Use rounded corners generously, but consistently:
   - main cards/panels: `rounded-2xl`
   - buttons/inputs/smaller panels: `rounded-lg` or `rounded-xl`
   - pills/status badges: `rounded-full`
6. Use light gray structure and subtle borders instead of heavy separators:
   - typical borders: `border-gray-100`, `border-gray-200`
   - typical page background: `bg-gray-50`
   - main content surface: `bg-white`
7. Use concise transitions. Most interactions use `transition-colors`, `transition-all`, or `transition-opacity` in the `150ms-300ms` range.
8. Default icon style is inline SVG / Heroicon-style outline icons. Do not switch to a different icon family unless the screen already does.
9. Do not create a new color system. Reuse purple/indigo brand accents plus neutral grays and standard semantic tones like emerald, amber, red, sky.
10. Do not redesign an existing page while adding a feature. Extend the current screen.

## Brand Tokens

### Primary brand colors

- `brand-500`: `#A30EB2`
- `brand-700`: `#730DB1`
- `brand-900`: `#3B0CB1`
- Common gradient: `linear-gradient(135deg, #A30EB2 0%, #730DB1 50-55%, #3B0CB1 100%)`

### Common neutrals

- page background: `bg-gray-50`
- primary surface: `bg-white`
- soft header/footer rows: `bg-gray-50` or `bg-gray-50/50`
- structure borders: `border-gray-100` or `border-gray-200`
- primary text: `text-gray-900`
- secondary text: `text-gray-500` to `text-gray-600`

### Semantic colors already used

- success: emerald / green
- warning: amber / yellow
- error: red / rose
- info: sky / blue / indigo depending screen

## Shared Component Language

### Cards and panels

- Main shells are usually `bg-white border border-gray-200 rounded-2xl shadow-theme-xs` in admin.
- Instructor and learner cards are usually `rounded-2xl border ... bg-white shadow-sm`.
- Learner feature sections often use tinted backgrounds like `bg-purple-50/40`, `bg-indigo-50/30`, `bg-amber-50`, `bg-emerald-50`.
- Use `overflow-hidden` when the card has a header, accent bar, image, or modal-style structure.

### Buttons

- Primary admin CTA: solid brand fill, usually `bg-brand-500 hover:bg-brand-600 text-white rounded-lg`.
- Primary instructor/learner/auth CTA: brand gradient background, white text, soft shadow, slight opacity/lift hover.
- Secondary neutral CTA: white or gray background, gray border, gray text, hover to `bg-gray-50`.
- Outline brand CTA: light border, purple text, hover to pale purple background.
- Destructive actions:
  - inline/icon actions: gray icon until hover, then red/rose tint
  - confirm buttons: solid red/emerald where appropriate

### Inputs and selects

- Typical field shape: `rounded-lg` or `rounded-xl`
- Typical auth field: `bg-gray-50 border border-gray-200`
- Typical admin/instructor field: white or transparent background with light border
- Focus state should use brand ring, for example:
  - `focus:ring-2 focus:ring-brand-500/30`
  - `focus:ring-purple-400`
  - `focus:border-transparent` on rounded-xl learner/auth fields
- Avoid harsh dark borders or thick outlines

### Tables

- Wrap in `overflow-x-auto`
- Header row uses `bg-gray-50`
- Header text is small, uppercase, tracked, muted:
  - `text-xs font-semibold uppercase tracking-wider text-gray-500`
- Body rows use thin dividers like `divide-y divide-gray-100` or `divide-gray-50`
- Row hover is subtle:
  - admin: `hover:bg-gray-50`
  - instructor: may use a softly tinted hover like `hover:bg-green-50/30`
- Statuses appear as pills, not plain text
- Actions are right-aligned and usually icon-first

### Badges and pills

- Use `inline-flex items-center rounded-full`
- Small badge text is usually `text-xs font-medium` or `font-semibold`
- Active/status badges use pale background + darker text, not heavy fills
- Count badges in nav use small rounded-full bubbles with amber/rose/sky tones

### Modals

- Use full-screen fixed overlay
- Backdrop is usually `bg-black/50` or `bg-gray-900/50`, often with `backdrop-blur-sm`
- Panel is centered, white, rounded-2xl, shadow-xl or shadow-2xl
- Complex modals often have:
  - header
  - content body
  - footer with actions
- Separate sections with `border-b border-gray-100` and `border-t border-gray-100`
- Close button is usually top-right with a gray hover state
- For learner modals, a brand gradient accent bar or gradient header is common

### Hover and motion

- Admin:
  - mostly color change only
  - occasional soft hover background
  - very limited scale effects
- Instructor:
  - subtle icon scaling
  - subtle shadow or lift
  - active items may use full brand gradient
- Learner:
  - small scale, opacity, or lift effects are allowed
  - gamified surfaces can feel more playful
- Marketing:
  - hover lift, glow, shimmer, animated blobs/orbs, reveal animations are acceptable

## Role-Specific Design Modes

## Admin Mode

### Visual character

- Clean, structured, operational UI
- Light-only
- White panels on `bg-gray-50`
- Purple brand is an accent, not the full page background
- Information density is higher than in learner/auth screens

### Common admin patterns

- Sidebar/header shell with white surfaces and gray borders
- Active nav item uses the full brand gradient with white text
- Inactive nav item stays neutral and uses `hover:bg-purple-50 hover:text-purple-700`
- Main containers: `rounded-2xl bg-white border border-gray-200 shadow-theme-xs`
- Section headers often use `px-6 py-4 border-b border-gray-100`
- CTA buttons are usually solid brand buttons, not gradient pills
- Tabs are simple rounded pills with active solid brand fill

### Admin tables

- Prefer understated tables over decorative cards
- Use muted headers, subtle row hover, compact spacing
- Avatar/initial + name + secondary email text is a common first column pattern
- Action buttons are icon-only with color-coded hover states

### Admin modals

- Neutral, businesslike structure
- Header and footer often use a faint gray background
- Use semantic highlight boxes inside the modal for approvals, warnings, or destructive context
- Avoid playful illustration, large glow, or gamified effects

### Admin references

- `resources/views/layouts/admin.blade.php`
- `resources/views/admin/users/index.blade.php`
- `resources/views/admin/users/partials/filter-toolbar.blade.php`
- `resources/views/admin/users/partials/users-table.blade.php`
- `resources/views/admin/content-reviews/_approve-modal.blade.php`

## Instructor Mode

### Visual character

- Light-only
- Same brand palette as admin, but more branded and slightly warmer
- Professional, but less severe than admin
- White surfaces with branded gradient highlights and colored section tones

### Common instructor patterns

- Sidebar/header structure is similar to admin, but active nav uses `bg-gradient-to-r from-brand-500 via-brand-700 to-brand-900`
- Search bars and cards are slightly softer, often `rounded-xl`
- Section shells can use tonal framing: purple, amber, green, indigo
- CTAs and wizard actions often use gradient buttons instead of flat brand fills

### Instructor tables and panels

- Tables are still light and clean, but can use soft tint hover tied to the section tone
- Empty states are simple white dashed cards
- Summary sections often pair a white card with a tinted informational card

### Instructor modals

- Complex interactions often use guided wizards or steppers
- Stepper circles and progress lines may use purple/indigo gradients
- White body, gray section dividers, gradient CTA buttons
- More visual guidance than admin, but still not playful like learner

### Instructor references

- `resources/views/layouts/instructor-app.blade.php`
- `resources/views/instructor/dashboard.blade.php`
- `resources/views/instructor/quizzes/partials/quiz-modal.blade.php`

## Learner Mode

### Visual character

- Soft, encouraging, slightly gamified
- Allows more visual warmth than admin/instructor
- Often supports dark mode with matching `dark:` classes
- Uses brand gradient more visibly, especially in hero areas and key CTAs

### Common learner patterns

- Dashboard and module screens use tinted section wrappers:
  - purple
  - indigo
  - amber
  - emerald
- Cards are rounded-2xl with soft shadows and gentle borders
- Important cards may have top accent borders or gradient bars
- CTA buttons often use gradient fills with subtle scale/opacity hover
- Copy tone is supportive and encouraging

### Learner modals

- Can include gradient headers or thin gradient accent bars
- Can include tabs, reward/status pills, progress/strength indicators
- Rounded-xl and rounded-2xl are common
- Backdrop blur is common

### Learner motion

- Slight scale on hover is acceptable
- Progress/achievement UI can be more animated
- Still keep everything polished, not game-like chaos

### Learner references

- `resources/views/layouts/learner-app.blade.php`
- `resources/views/learner/dashboard.blade.php`
- `resources/views/learner/partials/edit-profile-modal.blade.php`
- `resources/views/components/learner/out-of-shields-modal.blade.php`

## Auth Mode

### Visual character

- Polished split-screen experience
- Left side: clean form card area
- Right side: full-height gradient brand panel with logo/artwork
- Inputs are rounded-xl and sit on light gray backgrounds
- Primary actions are full-width brand buttons

### Common auth patterns

- Outer shell is a large white card with `rounded-2xl shadow-2xl overflow-hidden`
- Page background is light gray
- Fields: `bg-gray-50 border border-gray-200 rounded-xl`
- Submit buttons: full-width, white text, rounded-xl, shadow, brand gradient
- Secondary links and helper actions stay neutral or brand-purple

### Auth references

- `resources/views/components/auth-split-layout.blade.php`
- `resources/views/auth/learner-login.blade.php`
- `resources/views/auth/instructor-login.blade.php`
- `resources/views/auth/register.blade.php`

## Landing / Marketing Mode

### Visual character

- Most expressive surface in the product
- Large gradients, animated orbs, wave separators, decorative glows, reveal animations
- Still brand-led and purple-first
- White content sections interrupt purple gradient sections for contrast

### Rules for marketing screens

- Bold visuals are allowed here only
- Use custom backgrounds, floating shapes, animated reveal, shimmer, and hover-lift cards
- Keep CTA buttons on brand gradient
- Do not import this level of decoration into admin/instructor tables or forms

### Marketing references

- `resources/views/layouts/landing.blade.php`
- `resources/views/landing/index.blade.php`

## What AI Must Do Before Writing UI

1. Identify the area: `admin`, `instructor`, `learner`, `auth`, or `landing`.
2. Open the nearest existing page in the same area.
3. Reuse that page's:
   - container shape
   - spacing rhythm
   - button style
   - table style
   - modal structure
   - hover behavior
   - typography scale
4. Only then add the new feature.

If a new feature is being added to an existing page, the AI must copy the page's existing visual grammar first and insert the new block into that grammar.

## What AI Must Avoid

- Do not invent a new theme for a single feature.
- Do not switch to generic SaaS UI styling.
- Do not introduce glassmorphism into admin unless the existing screen already uses it.
- Do not use colorful gradients as large page backgrounds in admin or instructor areas.
- Do not use square corners for new components.
- Do not mix multiple button systems in one screen.
- Do not replace subtle admin tables with large card grids unless the surrounding page already uses cards.
- Do not add random purple everywhere; admin uses purple as accent, not wallpaper.
- Do not ignore dark mode in learner screens that already support dark mode.
- Do not create a totally new modal pattern if the section already has one.

## Default Build Recipes

### If you need a new admin page

- Page background: `bg-gray-50`
- Main shell: `rounded-2xl bg-white border border-gray-200 shadow-theme-xs`
- Section header: `px-6 py-4 border-b border-gray-100`
- Filters: rounded-lg inputs/selects with light borders and brand ring on focus
- Table: gray-50 header, gray-100 dividers, subtle row hover
- Primary CTA: solid `bg-brand-500 hover:bg-brand-600`

### If you need a new instructor modal

- Backdrop: dark semi-transparent overlay
- Panel: `rounded-2xl bg-white shadow-2xl`
- Header: white or gray-50 with optional stepper
- Body: white with structured grouped fields
- Footer: gray-50 with neutral cancel + gradient primary button

### If you need a new learner modal or card

- Use `rounded-2xl`, soft border, soft shadow
- Add a gradient accent bar or small gradient header if it is an important action
- Use supportive copy and status chips
- Use gentle hover scale only if nearby learner components already do

## Copy-Paste Prompt For Future AI Tasks

Use this block when asking an AI to build UI in this project:

```text
Follow the existing UI system in this repository exactly. Do not redesign the product.

First, identify whether this screen belongs to admin, instructor, learner, auth, or landing, then mirror the nearest existing screen in that same area.

Global rules:
- Use Poppins only.
- Keep the brand gradient exactly: #A30EB2 -> #730DB1 -> #3B0CB1.
- Reuse existing Tailwind utility patterns and Blade UI structure.
- Main cards/panels should usually be rounded-2xl.
- Inputs and buttons should usually be rounded-lg or rounded-xl.
- Use white surfaces, gray borders, and subtle shadows instead of inventing a new style.
- Keep transitions subtle and consistent with nearby screens.

Area-specific rules:
- Admin: clean, data-heavy, light-only, white cards on gray background, purple only as accent, subtle tables, icon-based actions, neutral modals.
- Instructor: light-only, branded but professional, white cards with purple/indigo accents, gradient active states, wizard-style modals allowed.
- Learner: soft, supportive, slightly gamified, rounded cards, tinted section backgrounds, gradient CTAs, dark mode support where the surrounding screen already supports it.
- Auth: split-screen white card + gradient brand panel, rounded-xl gray inputs, full-width gradient primary buttons.
- Landing: expressive marketing visuals are allowed here only.

Component rules:
- Tables: gray-50 header, uppercase muted labels, thin dividers, subtle hover.
- Modals: centered white rounded-2xl panel, dark translucent backdrop, clear header/body/footer structure.
- Buttons: do not invent new variants; match the nearby screen's existing button style first.
- Badges/status pills: rounded-full with pale background + darker text.

Before coding, inspect the nearest existing Blade file and copy its visual grammar. Extend the current design; do not create a new one.
```

## Best Matching Files To Show AI

When a task is specific, give AI 1 to 3 concrete reference files from the same area.

Good examples:

- Admin tables/forms:
  - `resources/views/admin/users/index.blade.php`
  - `resources/views/admin/users/partials/filter-toolbar.blade.php`
  - `resources/views/admin/users/partials/users-table.blade.php`
- Instructor modal/wizard work:
  - `resources/views/instructor/quizzes/partials/quiz-modal.blade.php`
  - `resources/views/instructor/dashboard.blade.php`
- Learner dashboard/modal work:
  - `resources/views/learner/dashboard.blade.php`
  - `resources/views/learner/partials/edit-profile-modal.blade.php`
- Auth work:
  - `resources/views/components/auth-split-layout.blade.php`
  - `resources/views/auth/learner-login.blade.php`

This works better than saying "follow the existing theme" by itself.
