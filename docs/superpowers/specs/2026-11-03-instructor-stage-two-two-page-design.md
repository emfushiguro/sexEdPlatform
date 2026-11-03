# Instructor Stage Two Two-Page Application Design

**Date:** 2026-11-03  
**Status:** Approved direction; implementation pending written-spec review

## Goal

Turn the Stage Two instructor application into two server-backed pages: applicants save required documents first, then complete teaching expertise and submit the final application.

## Scope and constraints

- Keep the existing `InstructorApplication` draft record and private document storage.
- Add no database tables, columns, or provider integrations.
- Preserve Stage One identity approval gates, current file validation limits, moderation, and final instructor approval behavior.
- Preserve the existing final submission endpoint and admin review data shape.
- Keep the existing split-layout visual language, purple wizard treatment, and learner registration spacing/focus conventions.
- Do not expose stored document paths or make private files public.

## User flow

### Step 1: Required documents

Route: existing `instructor-application.application.edit` GET route.

The page shows the applicant summary, a two-step wizard with **Required documents** active, and three document cards:

- Government ID: optional when the approved Stage One identity review already covers it; otherwise required.
- NBI / Police clearance: required unless already present in the draft.
- CV or resume: required unless already present in the draft.

Each card uses a keyboard-accessible purple dashed upload area. It supports click-to-browse, selected-file name/type/size feedback, clearing a newly selected file, and an existing-draft file state. Accepted types and the 5 MB limit remain the current backend rules.

The primary action is **Save and continue**. The server validates the required document boundary, stores any uploaded files through `InstructorApplicationDocumentStorage`, keeps the application in `draft` status, and redirects to the teaching-expertise page. Validation errors return to this page with the existing error summary and no data loss.

### Step 2: Teaching expertise

Route: new `instructor-application.application.expertise` GET route.

The page shows the same applicant summary treatment, the wizard with **Teaching expertise** active and **Required documents** completed, and:

- Educational background, required.
- Conditional “If other, specify” field, required only for `other`.
- Professional background text area, maximum 500 characters.
- Tier 2 evidence cards for teaching credential, certificate, and professional license. At least one existing or newly uploaded Tier 2 document is required before final submission.

The page includes **Back to required documents**, **Save draft**, and **Submit application**. Saving keeps the application in `draft` status and returns to this page. Submitting uses the existing final validation and submission service, including the confirmation checkbox and administrator notification flow.

Direct access to the expertise route without a draft or without the required document paths redirects to Step 1 with a clear status message.

## Wizard and visual system

- Add a focused two-step application wizard at the top of both pages: completed, active, and upcoming states match the existing `x-wizard-stepper` visual language.
- The completed document step links back to the document page from Step 2; Step 2 remains the active destination.
- Keep the existing Stage Two split layout and right-side purple panel, but use shorter headings and consistent card spacing.
- Reuse the relationship-verification upload treatment: dashed drop zone, “Drop a file here or browse” copy, selected-file card, clear action, and concise format/size helper text.
- Keep all controls keyboard reachable with visible focus rings and live status text for selected files and save/submit progress.

## Server design

1. Extend `ApplicationController` with a document-save action and an expertise-page action.
2. Add a dedicated request for Step 1 document requirements so server validation cannot be bypassed by skipping browser validation.
3. Reuse `InstructorApplicationService::saveDraft()` for file storage and draft persistence; do not duplicate storage logic.
4. Keep `SaveInstructorApplicationDraftRequest` for Step 2 draft saves, and keep `SubmitInstructorApplicationRequest` for final submission.
5. Add the new GET/POST route names under the existing authenticated, verified, and feature-enabled route group.
6. Keep the current edit route as the document-step entry point so dashboard links and existing notifications continue to work.

## Error handling and edge cases

- Existing draft paths satisfy their corresponding required document rule, so applicants do not need to reselect files after returning to Step 1.
- New uploads replace only their matching draft path and metadata; unrelated draft documents remain intact.
- Invalid uploads return to the relevant step with field-level errors and the current error summary.
- Final submission still requires at least one Tier 2 document from either the draft or the current request.
- A pending application continues to redirect to the submitted/pending view; no new application can be created through these pages.
- The two-page UI does not change who can approve, reject, or activate an instructor.

## Verification plan

- Add feature coverage for document-step validation, successful save-and-continue redirect, expertise-route gating, expertise draft save, and final submission using saved required documents.
- Keep existing instructor identity and application tests passing.
- Run `php artisan view:cache` and the focused instructor-application PHPUnit suite.
- Run `git diff --check` on owned files.
- Browser visual QA is a separate follow-up; the automated checks do not prove screenshot-level layout fidelity.

## Non-goals

- No new application status values or database migrations.
- No changes to Persona, Stage One identity review, admin moderation, or role activation.
- No third confirmation step; the requested flow has exactly two pages.
