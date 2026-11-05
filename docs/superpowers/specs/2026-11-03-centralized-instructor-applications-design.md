# Centralized Instructor Applications Review

## Goal

Make the admin **Instructor Applications** area the single review workspace for both stages of a direct instructor application:

- Stage One: Persona identity evidence and the existing identity decision workflow.
- Stage Two: the submitted instructor documents, teaching expertise, and the existing final application decision workflow.

The queue must include every Stage Two application, including older records that do not have a linked Stage One identity case. When a linked identity case exists, its evidence and Stage One controls must be shown on the same review page as Stage Two.

## Invariants

- Persona results remain evidence for manual review; they never approve an instructor or grant instructor access by themselves.
- Stage One approval remains required before a direct applicant can receive final Stage Two approval.
- Only the existing Stage Two approval service can activate the instructor role.
- Existing private document and Persona evidence authorization, no-store response headers, validation, audit, notification, and locking behavior remain unchanged.
- No database reset, destructive migration, data copy, or new persisted lifecycle state is needed.
- Stage One actions require `review instructor identities`; Stage Two actions and document access require `review instructor applications`. A reviewer may view the unified queue with either existing review permission, but each action stays independently protected.

## User experience

### Queue

- Rename the visible admin navigation and page headings from **Identity Review** to **Instructor Applications**.
- Use one queue route as the canonical destination.
- Show rows for the union of:
  - current instructor identity cases (including cases that have not reached Stage Two), and
  - instructor applications that have no current linked identity case.
- Preserve status filters and search in a form that makes the current stage/status clear. A row links to one review page and identifies whether Stage One, Stage Two, or both need attention.
- Combine the existing pending counts for the single navigation badge/queue summary without changing the underlying count sources.
- Keep old named routes available as compatibility redirects where a safe target can be reconstructed. Existing notification deep links must continue to resolve to the canonical review page.

### Review page

Use the existing identity review page as the visual base, with concise sections in this order:

1. Applicant and overall application status.
2. Stage One identity evidence, field-comparison summary, and existing identity decision controls when an identity case is present.
3. Stage Two personal/application details and private document links when an instructor application is present.
4. Stage Two decision controls using the existing approve/reject requests and service.
5. Combined audit/history context, without exposing provider secrets or raw identity values.

If only one stage exists, render only that stage and an explicit status message explaining what is missing or waiting. Do not invent an identity case for legacy Stage Two data.

### Navigation and compatibility

- The sidebar has one visible **Instructor Applications** item pointing to the canonical queue.
- Existing `admin.instructor-applications.identities.*` links remain valid or redirect to the canonical queue/detail page.
- Existing `admin.instructor-applications.*` Stage Two links remain valid or redirect to the centralized detail page when a linked identity case exists; standalone legacy applications remain reviewable through the same centralized experience.

## Implementation shape

- Extend the admin instructor-application review controller with a small, read-only queue projection that combines current identity cases and standalone applications. Do not add a new table or duplicate records.
- Add a resolver/helper in the controller or an existing service to load the current identity case and latest relevant Stage Two application for a review subject. Keep authorization decisions in the existing FormRequests/services/policies.
- Reuse the existing `InstructorIdentityReviewEvidence`, `InstructorIdentityService`, `InstructorApplicationService`, and `DocumentController` rather than reimplementing provider or role logic.
- Add only the minimum route aliases/redirects needed for old queue/detail URLs and notification links.
- Replace the old Stage Two-only modal page as the primary navigation target; retain its service behavior and compatibility route until all callers are migrated.

## Tests and verification

Add focused feature coverage for:

- unified queue visibility for identity-only, linked Stage One/Stage Two, and standalone legacy Stage Two records;
- canonical detail rendering of both stages and omission of absent stages;
- permission separation for Stage One and Stage Two actions/document links;
- old route and notification deep-link compatibility;
- unchanged Stage One and Stage Two decision side effects, including the Stage One prerequisite for final approval.

Run the focused admin/instructor application suites, view cache/lint checks, and `git diff --check`. Browser/provider-media QA remains a separate gate and must not be claimed as covered by PHPUnit.

## Explicit non-goals

- No permission rename or role model redesign.
- No automatic Persona approval.
- No migration that backfills or rewrites historical applications.
- No redesign of applicant-facing Stage Two pages in this change.
