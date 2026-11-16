# Suspension and appeal navigation / UI design

Status: implemented locally after user approval and navigation amendment; uncommitted and not deployed. See [verification and manual guide](../../qa/2026-11-16-suspension-appeal-navigation.md).
Branch: `feature/suspension-appeal-navigation`, current checkout without a worktree.

## Intent and accepted choices

Admins must find and review user appeals without relying on notifications. User selected navigation, badges and contextual links, pending review by default and UI improvements. Final amendment: the main admin dashboard entry belongs only in Quick Actions and must read **Manage Suspension**. No new sidebar appeal entry or other main-dashboard appeal button/card is added. Existing sidebar navigation remains unchanged. Within the suspension workspace, the Appeals action and pending-card link provide access to the queue.

Preserve the uncommitted moderation fixes already present in this checkout, existing stashes and development data. No commit, push or deployment is included.

## Approaches

1. Selected: improve existing dashboard, suspension details and appeal pages with visible navigation and consistent presentation. Reuses working routes/services and keeps the scope manageable.
2. Button only: smaller but leaves appeal-history rows and contextual navigation difficult to use.
3. Dashboard tabs/full redesign: a larger layout change affecting report review unnecessarily. Not selected.

## Proposed UI sections

### 1. Dashboard and sidebar

Add Manage Suspension to main admin dashboard Quick Actions, linking to the existing suspension index. In the suspension workspace, add a clear page introduction with an Appeals button and pending count. Make its existing Appeals Pending summary card open pending review. Do not add a sidebar entry. Retain existing report/suspension queues and filters.

Use existing purple brand tokens, Poppins/admin shell, white surfaces, light borders and restrained rounding. Reduce oversized spacing and visual competition; use prominent text labels for primary actions instead of relying on eye icons. Do not add dependencies, a new global theme or decorative imagery.

### 2. Suspension list and detail

Show readable text badges for appeal states. Add Review appeal for a pending appeal, otherwise View appeal for the latest existing appeal. Choose newest pending appeal first, then newest appeal by submitted_at with ID tie-breaker; links target a real appeal ID, never a suspension ID. With no appeal, show No appeal submitted and omit the appeal action.

Each detail-history row gets its own View/Review appeal link. Keep the suspension-details action and existing duration editor. Long names, emails and reasons wrap safely, and deleted/unavailable users have readable fallbacks.

### 3. Appeal queue

Default omitted status to `pending_review`. Explicit All statuses must remain selectable and survive pagination; reset returns to pending review. Use `status=all` for generated links so blank-query normalization cannot lose the selection; accept legacy `status=` too. Preserve existing search and pagination. Provide filters for pending review, clarification requested, approved, rejected and all statuses.

Use a clean title/action row, aligned filter bar, distinct text/color status badges, submitted date/time and a visible Review appeal / View appeal action. Replace Learner labels with User because suspensions may involve other roles. Provide a useful empty state: no pending appeals, with an All appeals link, versus no search matches, with a clear-filter action.

Pending counts must count actual SuspensionAppeal rows with `status=pending_review`, matching the queue. Clarification requested is separate and not counted as awaiting an admin decision. Counts are global for authorized admins and do not inherit unrelated suspension/report filters. No new global count provider is needed without a new sidebar badge; avoid per-row queries.

### 4. Appeal review workspace

Show an obvious back-to-queue action and View suspension link. Group user/case/status/window information above the appeal reason. Improve decision-form labels, purple primary action, inline validation with preserved input, and readable discussion messages with author/time. On wide screens, use a main content area plus a decision panel; on small screens stack sections with discussion and decision controls reachable.

Keep existing review service, request rules, available outcomes, thread behavior and user-visible versus internal-note semantics. Presentation must not bypass terminal-state checks or change appeal eligibility, enforcement effects, notifications or duration policy. UI labels should describe existing behavior without claiming new policy.

### 5. Accessibility and mobile

Use semantic links/buttons, visible focus, meaningful accessible names and text in every status badge. Aim for 44px action targets. Keep page content within 320px/390px viewports; allow deliberate table-container scrolling where necessary without page overflow. Maintain contrast, wrapped long content and useful validation/empty states.

## Verification and boundaries

Add focused regression coverage for Quick Action/card/button destinations, exact row/history appeal IDs, pending-first selection, no-appeal state, matching counts, pending default, explicit all-status filter/search/pagination and admin-only access. Preserve existing notification navigation and suspension/appeal lifecycle tests.

Database tests run only with a verified isolated schema, never default development settings. Render browser fixtures for dashboard, queue and review at phone/desktop widths; check long content, empty states, keyboard actions and links. Run relevant PHP checks, asset build if styles require it, manifest checks and diff whitespace checks. Report existing project-wide failures separately; do not claim deployed verification.

No new database tables, migrations, production suspensions, identity-document submissions, policies, reminder jobs, exports or bulk moderation are included. Exact spacing/icon selection and small component extraction are implementation decisions using the existing UI conventions.

## Approval

User approved all sections and visual direction with the Quick Actions / Manage Suspension amendment. Execute with Caveman / Superpowers executing-plans in this branch, without a worktree. Do not stage, commit, push or deploy.
