# Verification and Upload UX Standardization Design

Date: 2026-04-10
Status: Approved for planning handoff

## Objective
Enhance and standardize Admin and User verification experiences to improve consistency, clarity, and safety while keeping the current platform visual language.

## Scope
- Admin Parent and Child verification queue and modal UX
- Rejection and approval interaction safety
- Parent and Child registration document upload preview/persistence
- My Children action cleanup

## Out of Scope
- Role/permission model changes
- Verification policy rule changes beyond requested UI behavior
- Full design system rewrite

## Success Criteria
- Parent and Child verification UI uses consistent modal structure and labels
- Redundant metadata and duplicate actions are removed
- Approval requires explicit Review then Confirm action
- Rejection reason Other uses TinyMCE rich text input
- Parent and Child uploads persist across multi-step navigation
- Users can preview and remove/replace uploaded files before submission

## Phase 1: Modal and Terminology Cleanup
### Admin Parent Verification
- Remove Reviewed At
- Remove Document Type in preview and queue table
- Modal header: Parent Verification - {User Name}
- Action-first compact layout with decision controls at top
- Document preview placed in expandable section

### Admin Child Verification
- Remove Document Type
- Remove Reviewed At
- Remove Parent Document Available
- Remove redundant queue columns and match parent table structure
- Modal header: Child Verification - {User Name}
- Same action-first compact structure as parent flow

### Terminology
- Replace Verification Transparency Details with Verification Details

## Phase 2: Rejection and Approval Interactions
### Rejection
- Keep reason selector
- If reason is Other, show TinyMCE rich text editor
- TinyMCE configuration baseline reuses instructor module creation setup
- Custom content required when Other is selected
- Remove Issue warning to account holder checkbox

### Approval
- Replace direct approve with Review then Confirm flow
- Confirmation modal message:
  - Are you sure you want to approve this verification?
- Actions:
  - Confirm
  - Cancel
- Standardize pending/success/error feedback behavior across parent and child moderation

## Phase 3: Upload Persistence and Preview
### Child Registration
- Restrict upload to PSA Birth Certificate only
- Show uploaded file preview immediately
- Provide X action to remove/replace file
- Validate preview-ready state before submit

### Parent Registration
- Show uploaded file preview immediately
- Provide X action to remove/replace file
- Fix back-navigation persistence gap
- Replace duplicate-upload blocking message by restoring and showing existing uploaded preview

### Shared Persistence Model
- Server-backed temporary upload record in session as source of truth
- Persist temp file metadata and storage path by flow + step
- Rehydrate preview state from session on page load
- Keep session state synchronized on remove/replace actions
- Prevent multi-step data loss on back/forward navigation

## UX Principles Applied
- Consistent parent/child interaction patterns
- Minimal required metadata and actions
- Clear role-aware modal headers
- Strong confirmation for critical moderation actions
- Smooth multi-step forms with data continuity

## Technical Design Notes
- Prefer shared partial/component for parent and child verification modal shell
- Prefer shared Alpine handlers for moderation actions and feedback states
- Implement a reusable temp-upload session service for both parent and child registration flows
- Keep controllers thin and business behavior in service layer

## Risks and Mitigations
- Risk: TinyMCE init conflicts in dynamic modal rendering
  - Mitigation: lifecycle-safe init/destroy and fallback handling
- Risk: Session temp file staleness
  - Mitigation: explicit replace/remove endpoints and cleanup policy
- Risk: Regression in existing verification decisions
  - Mitigation: preserve route contracts and payload keys where possible

## Verification and Test Plan
- Feature tests for parent/child approve/reject flows including confirm step
- Validation tests for Other rejection requiring rich-text content
- Feature tests for temp upload persistence across step navigation
- UI checks for queue column removals and modal header/label changes
- Manual checks for preview remove/replace behavior (image/pdf)

## Planned Handoff
Next mode: superpower-plan
Goal: produce implementation plan by phase with affected files, tests, and rollout sequence.
